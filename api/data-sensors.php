<?php
    require_once __DIR__ . '/_common.php';

    require_method('GET');
    require_login();

    [$page, $size] = read_pagination();

    $sensorType = strtoupper(trim((string) ($_GET['sensor_type'] ?? '')));
    if ($sensorType !== '' && !in_array($sensorType, (new SensorDAO(db()))->types(), true)) {
        json_error(400, 'VALIDATION_ERROR', 'Loại cảm biến không hợp lệ');
    }

    $filters = [
        'sensor_type' => $sensorType,
        'keyword'     => trim((string) ($_GET['keyword'] ?? '')),
        'time_range'  => read_time_filter(),
    ];

    paginated_response((new DataSensorDAO(db()))->search($filters, $page, $size), $page, $size);
?>