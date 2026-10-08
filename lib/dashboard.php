<?php
    require_once __DIR__ . '/app.php';

    const DASHBOARD_SENSOR_KEYS = ['TEMPERATURE' => 'temperature', 'HUMIDITY' => 'humidity', 'LIGHT' => 'light'];

    function dashboard_sensor_values(DataSensorDAO $dataSensors): array
    {
        $values = ['temperature' => null, 'humidity' => null, 'light' => null, 'updated_at' => null];

        foreach ($dataSensors->latest() as $row) {
            $key = DASHBOARD_SENSOR_KEYS[$row['sensor_type']] ?? null;
            if ($key === null) {
                continue;
            }
            $values[$key] = (float) $row['value'];
            if ($values['updated_at'] === null || $row['recorded_at'] > $values['updated_at']) {
                $values['updated_at'] = $row['recorded_at'];
            }
        }

        return $values;
    }

    function dashboard_esp32(DeviceDAO $devices): array
    {
        $status = $devices->controllerStatus(ESP32_CONTROLLER_CODE);
        return [
            'status'       => $status['is_online'] ? 'ONLINE' : 'OFFLINE',
            'last_seen_at' => $status['last_seen_at'],
        ];
    }

    function dashboard_devices(DeviceDAO $devices, ActionDAO $actions): array
    {
        return array_map(function ($device) use ($actions) {
            return [
                'device_code'   => $device['device_code'],
                'device_name'   => $device['device_name'],
                'current_state' => $device['current_state'],
                'is_online'     => (bool) $device['is_online'],
                'last_seen_at'  => $device['last_seen_at'],
                'last_action'   => $actions->latestByDevice((int) $device['id']),
            ];
        }, $devices->all());
    }

    function dashboard_chart(DataSensorDAO $dataSensors): array
    {
        $chart = ['labels' => [], 'temperature' => [], 'humidity' => [], 'light' => []];

        foreach ($dataSensors->chart(CHART_POINTS) as $row) {
            $chart['labels'][] = substr($row['recorded_at'], 11);
            foreach (DASHBOARD_SENSOR_KEYS as $key) {
                $chart[$key][] = $row[$key] === null ? null : (float) $row[$key];
            }
        }

        return $chart;
    }
?>