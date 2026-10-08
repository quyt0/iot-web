<?php
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/mqtt.php';

use PhpMqtt\Client\MqttClient;

const TELEMETRY_FIELDS = [
    'temperature' => ['TEMPERATURE', -40, 80],
    'humidity'    => ['HUMIDITY', 0, 100],
    'light'       => ['LIGHT', 0, 100],
];

function worker_log(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . "] $message\n";
}

function decode_object(string $message): array
{
    $data = json_decode($message, true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('payload không phải JSON object');
    }
    return $data;
}

try {
    $pdo = db();
    $devices = new DeviceDAO($pdo);
    $dataSensors = new DataSensorDAO($pdo);
    $actions = new ActionDAO($pdo);

    $handleTelemetry = function (string $topic, string $message) use ($devices, $dataSensors) {
        $data = decode_object($message);

        $controller = DeviceDAO::normalizeCode((string) ($data['deviceId'] ?? ''));
        if ($controller === '' || !$devices->controllerExists($controller)) {
            throw new InvalidArgumentException('deviceId không hợp lệ');
        }

        $timestamp = is_string($data['timestamp'] ?? null) ? strtotime($data['timestamp']) : false;
        if ($timestamp === false || abs(time() - $timestamp) > 86400) {
            throw new InvalidArgumentException('timestamp không hợp lệ');
        }

        $values = [];
        foreach (TELEMETRY_FIELDS as $field => [$sensorType, $min, $max]) {
            $value = $data[$field] ?? null;
            if (!is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException("$field phải là số");
            }
            if ($value < $min || $value > $max) {
                throw new InvalidArgumentException("$field nằm ngoài miền [$min, $max]");
            }
            $values[$sensorType] = $value;
        }

        $dataSensors->insertTelemetry($controller, $values, date('Y-m-d H:i:s', $timestamp), $message);
        if ($devices->markControllerOnline($controller)) {
            worker_log("$controller kết nối lại -> trạng thái thiết bị chuyển UNKNOWN cho tới khi nhận state mới");
        }
        worker_log("telemetry $controller: " . json_encode($values));
    };

    $handleState = function (string $topic, string $message, bool $retained = false) use ($devices, $actions) {
        $device = $devices->findByStateTopic($topic);
        if (!$device) {
            throw new InvalidArgumentException('topic không gắn với thiết bị nào');
        }

        $data = decode_object($message);
        $state = strtoupper((string) ($data['state'] ?? ''));
        if (!in_array($state, ['ON', 'OFF'], true)) {
            throw new InvalidArgumentException('state phải là ON hoặc OFF');
        }
        if (isset($data['deviceId']) && DeviceDAO::normalizeCode((string) $data['deviceId']) !== $device['device_code']) {
            throw new InvalidArgumentException('deviceId không khớp với topic');
        }

        $requestId = $data['requestId'] ?? null;
        $requestId = is_string($requestId) && $requestId !== '' ? $requestId : null;
        $action = $requestId !== null ? $actions->findByRequestId($requestId) : null;

        if ($retained && $action && $action['status'] !== 'LOADING') {
            worker_log("bỏ qua state retained cũ của {$device['device_code']} ($state, requestId $requestId)");
            return;
        }

        $devices->updateState((int) $device['id'], $state);

        if ($requestId === null) {
            worker_log("state {$device['device_code']} = $state (không kèm requestId)");
            return;
        }

        if (!$action || (int) $action['device_id'] !== (int) $device['id']) {
            worker_log("state {$device['device_code']} = $state, requestId $requestId không tồn tại -> không cập nhật action");
            return;
        }

        if ($actions->complete($requestId, $state)) {
            worker_log("action #{$action['id']} ({$action['action']}) -> $state");
        } else {
            worker_log("requestId $requestId đã được xử lý trước đó ({$action['status']})");
        }
    };

    $handleAvailability = function (string $topic, string $message) use ($devices) {
        $parts = explode('/', $topic);
        $controller = DeviceDAO::normalizeCode($parts[count($parts) - 2] ?? '');
        if (!$devices->controllerExists($controller)) {
            throw new InvalidArgumentException('controller không tồn tại');
        }

        $data = json_decode($message, true);
        $status = strtoupper(trim(is_array($data) ? (string) ($data['status'] ?? $data['state'] ?? '') : $message));

        if ($status === 'ONLINE') {
            if ($devices->markControllerOnline($controller)) {
                worker_log("$controller kết nối lại -> trạng thái thiết bị chuyển UNKNOWN cho tới khi nhận state mới");
            }
        } elseif ($status === 'OFFLINE') {
            $devices->markControllerOffline($controller);
        } else {
            throw new InvalidArgumentException('availability phải là ONLINE hoặc OFFLINE');
        }
        worker_log("availability $controller = $status");
    };

    $guard = fn (string $name, callable $handler) => function (string $topic, string $message, bool $retained = false) use ($name, $handler) {
        try {
            $handler($topic, $message, $retained);
        } catch (InvalidArgumentException $e) {
            worker_log("[$name] bỏ qua payload không hợp lệ ({$e->getMessage()}): $message");
        }
    };

    $client = new MqttClient(MQTT_HOST, MQTT_PORT, 'ptit-iot-worker-' . bin2hex(random_bytes(3)));
    $client->connect(mqtt_connection_settings()->setKeepAliveInterval(60), true);
    worker_log('Đã kết nối MQTT Broker tại ' . MQTT_HOST . ':' . MQTT_PORT);

    $client->subscribe(MQTT_BASE_TOPIC . '/telemetry', $guard('telemetry', $handleTelemetry), MqttClient::QOS_AT_MOST_ONCE);
    $client->subscribe(MQTT_BASE_TOPIC . '/devices/+/state', $guard('state', $handleState), MqttClient::QOS_AT_LEAST_ONCE);
    $client->subscribe(MQTT_BASE_TOPIC . '/+/availability', $guard('availability', $handleAvailability), MqttClient::QOS_AT_LEAST_ONCE);

    $lastHousekeeping = microtime(true) + 3;
    $client->registerLoopEventHandler(function () use (&$lastHousekeeping, $actions, $devices) {
        if (microtime(true) - $lastHousekeeping < 1) {
            return;
        }
        $lastHousekeeping = microtime(true);

        $expired = $actions->expireTimeouts(ACTION_TIMEOUT_SECONDS);
        if ($expired > 0) {
            worker_log("$expired action không nhận được phản hồi -> TIMEOUT");
        }
        if ($devices->markStaleOffline(DEVICE_OFFLINE_SECONDS) > 0) {
            worker_log('Không nhận bản tin từ ESP32 quá ' . DEVICE_OFFLINE_SECONDS . ' giây -> OFFLINE');
        }
    });

    $client->loop(true);
} catch (Throwable $e) {
    worker_log('Lỗi MQTT Worker: ' . $e->getMessage());
    sleep(5);
    exit(1);
}
?>