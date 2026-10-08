<?php
    require_once __DIR__ . '/_common.php';

    require_method('GET');
    require_login();

    json_response(200, ['status' => 'success', 'data' => (new SensorDAO(db()))->allActive()]);
?>