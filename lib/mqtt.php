<?php
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    use PhpMqtt\Client\ConnectionSettings;
    use PhpMqtt\Client\MqttClient;

    function mqtt_connection_settings(): ConnectionSettings
    {
        return (new ConnectionSettings())
            ->setUsername(MQTT_USER)
            ->setPassword(MQTT_PASS)
            ->setConnectTimeout(3)
            ->setSocketTimeout(5);
    }

    function mqtt_publish_json(string $topic, array $payload, int $qos = 1): void
    {
        $client = new MqttClient(MQTT_HOST, MQTT_PORT, 'ptit-iot-web-' . bin2hex(random_bytes(4)));
        $client->connect(mqtt_connection_settings(), true);

        try {
            $client->publish($topic, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $qos, false);
            if ($qos > 0) {
                $client->loop(true, true, 5);
            }
        } finally {
            $client->disconnect();
        }
    }
?>