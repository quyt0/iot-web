<?php
    function app_env(string $key, $default)
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    date_default_timezone_set(app_env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));

    define('DB_HOST', app_env('DB_HOST', 'db'));
    define('DB_NAME', app_env('DB_NAME', 'ptit_iot'));
    define('DB_USER', app_env('DB_USER', 'admin'));
    define('DB_PASS', app_env('DB_PASS', 'Adminp@ssw0rd'));

    define('MQTT_HOST', app_env('MQTT_HOST', '192.168.137.1'));
    define('MQTT_PORT', (int) app_env('MQTT_PORT', 2005));
    define('MQTT_USER', app_env('MQTT_USER', 'DoanDuyPhuc'));
    define('MQTT_PASS', app_env('MQTT_PASS', 'B23DCAT238'));
    define('MQTT_BASE_TOPIC', 'ptit/iot/room-01');

    define('ESP32_CONTROLLER_CODE', 'ESP32_01');

    define('ACTION_TIMEOUT_SECONDS', 10);
    define('DEVICE_OFFLINE_SECONDS', 6);
    define('CHART_POINTS', 20);
?>