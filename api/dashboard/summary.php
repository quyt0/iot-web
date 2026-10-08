<?php
    require_once __DIR__ . '/../_common.php';
    require_once __DIR__ . '/../../lib/dashboard.php';

    require_method('GET');
    require_login();

    $pdo = db();
    $dataSensors = new DataSensorDAO($pdo);
    $devices = new DeviceDAO($pdo);
    $actions = new ActionDAO($pdo);

    $summary = dashboard_sensor_values($dataSensors);
    $summary['esp32'] = dashboard_esp32($devices);
    $summary['devices'] = dashboard_devices($devices, $actions);

    json_response(200, [
        'status' => 'success',
        'data'   => [
            'summary' => $summary,
            'chart'   => dashboard_chart($dataSensors),
        ],
    ]);
?>