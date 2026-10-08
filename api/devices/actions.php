<?php
    require_once __DIR__ . '/../_common.php';
    require_once __DIR__ . '/../../lib/mqtt.php';

    require_method('POST');
    $userId = require_login();

    $body = read_json_body();
    $action = strtoupper(trim((string) ($body['action'] ?? '')));
    if (!in_array($action, ['ON', 'OFF'], true)) {
        json_error(400, 'VALIDATION_ERROR', 'action phải là ON hoặc OFF');
    }

    $pdo = db();
    $devices = new DeviceDAO($pdo);
    $actions = new ActionDAO($pdo);

    $device = $devices->findByCode((string) ($_GET['device'] ?? ''));
    if (!$device) {
        json_error(404, 'NOT_FOUND', 'Không tìm thấy thiết bị');
    }

    if (!$device['is_online']) {
        json_error(503, 'SERVICE_UNAVAILABLE', 'Không thể kết nối thiết bị, vui lòng kiểm tra ESP32');
    }

    $actions->expireTimeouts(ACTION_TIMEOUT_SECONDS);
    if ($actions->findLoadingByDevice((int) $device['id'])) {
        json_error(409, 'CONFLICT', 'Thiết bị đang xử lý lệnh trước đó, vui lòng chờ');
    }

    $requestId = uuid_v4();
    $actionId = $actions->create($requestId, (int) $device['id'], $userId, 'TURN_' . $action);

    try {
        mqtt_publish_json($device['command_topic'], [
            'requestId' => $requestId,
            'deviceId'  => DeviceDAO::mqttId($device['device_code']),
            'action'    => $action,
            'issuedAt'  => date('c'),
        ]);
    } catch (Throwable $e) {
        error_log('Publish command thất bại: ' . $e->getMessage());
        $actions->fail($actionId, 'Không kết nối được MQTT Broker');
        json_error(503, 'SERVICE_UNAVAILABLE', 'Không kết nối được MQTT Broker, vui lòng thử lại');
    }

    json_response(202, [
        'status' => 'success',
        'data'   => [
            'requestId'  => $requestId,
            'deviceCode' => $device['device_code'],
            'action'     => $action,
            'status'     => 'LOADING',
        ],
    ]);
?>