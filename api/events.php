<?php
    require_once __DIR__ . '/_common.php';
    require_once __DIR__ . '/../lib/dashboard.php';

    require_method('GET');
    require_login();

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    set_exception_handler(function (Throwable $e) {
        error_log((string) $e);
    });

    @ini_set('zlib.output_compression', '0');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    set_time_limit(0);

    function sse_send(string $event, array $data): void
    {
        echo "event: $event\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        flush();
    }

    $pdo = db();
    $dataSensors = new DataSensorDAO($pdo);
    $devices = new DeviceDAO($pdo);
    $actions = new ActionDAO($pdo);

    echo "retry: 2000\n\n";
    flush();

    $lastDataId = $dataSensors->maxId();
    $lastActionId = $actions->maxId();
    $lastDeviceFingerprint = null;
    $sentActions = [];
    $deadline = time() + 30;

    while (time() < $deadline && !connection_aborted()) {
        $maxDataId = $dataSensors->maxId();
        if ($maxDataId > $lastDataId) {
            $lastDataId = $maxDataId;
            sse_send('telemetry', dashboard_sensor_values($dataSensors));
        }

        $deviceList = dashboard_devices($devices, $actions);
        $esp32 = dashboard_esp32($devices);
        $fingerprint = json_encode([
            $esp32['status'],
            array_map(fn ($d) => [$d['device_code'], $d['current_state'], $d['is_online']], $deviceList),
        ]);
        if ($fingerprint !== $lastDeviceFingerprint) {
            $lastDeviceFingerprint = $fingerprint;
            sse_send('device_status', ['esp32' => $esp32, 'devices' => $deviceList]);
        }

        foreach ($actions->changedSince($lastActionId, 5) as $action) {
            $lastActionId = max($lastActionId, (int) $action['id']);
            $key = $action['id'] . ':' . $action['status'];
            if (isset($sentActions[$key])) {
                continue;
            }
            $sentActions[$key] = true;
            sse_send('action_status', $action);
        }

        echo ": ping\n\n";
        flush();
        sleep(1);
    }
?>