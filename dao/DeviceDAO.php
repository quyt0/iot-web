<?php
    class DeviceDAO
    {
        public function __construct(private PDO $pdo) {}

        public static function normalizeCode(string $code): string
        {
            return strtoupper(str_replace('-', '_', trim($code)));
        }

        public static function mqttId(string $code): string
        {
            return strtolower(str_replace('_', '-', $code));
        }

        private const ONLINE_SQL = '(is_online = 1 AND last_seen_at >= NOW() - INTERVAL ' . DEVICE_OFFLINE_SECONDS . ' SECOND)';

        private const COLUMNS = 'id, device_code, device_name, device_type, controller_code, command_topic, state_topic,
            current_state, last_seen_at, ' . self::ONLINE_SQL . ' AS is_online';

        public function all(): array
        {
            return $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM devices ORDER BY id')->fetchAll();
        }

        public function findByCode(string $code): ?array
        {
            $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM devices WHERE device_code = ?');
            $stmt->execute([self::normalizeCode($code)]);
            return $stmt->fetch() ?: null;
        }

        public function findByStateTopic(string $topic): ?array
        {
            $stmt = $this->pdo->prepare('SELECT * FROM devices WHERE state_topic = ?');
            $stmt->execute([$topic]);
            return $stmt->fetch() ?: null;
        }

        public function controllerExists(string $controllerCode): bool
        {
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM devices WHERE controller_code = ?
                UNION SELECT 1 FROM sensors WHERE controller_code = ?
                LIMIT 1
            ');
            $stmt->execute([$controllerCode, $controllerCode]);
            return (bool) $stmt->fetchColumn();
        }

        public function updateState(int $id, string $state): void
        {
            $this->pdo
                ->prepare('UPDATE devices SET current_state = ?, is_online = 1, last_seen_at = NOW() WHERE id = ?')
                ->execute([$state, $id]);
        }

        public function markControllerOnline(string $controllerCode): bool
        {
            $offline = 'NOT ' . self::ONLINE_SQL;

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM devices WHERE controller_code = ? AND $offline");
            $stmt->execute([$controllerCode]);
            $reconnected = (int) $stmt->fetchColumn() > 0;

            $this->pdo
                ->prepare("
                    UPDATE devices
                    SET current_state = IF($offline, 'UNKNOWN', current_state), is_online = 1, last_seen_at = NOW()
                    WHERE controller_code = ?
                ")
                ->execute([$controllerCode]);

            return $reconnected;
        }

        public function markControllerOffline(string $controllerCode): void
        {
            $this->pdo
                ->prepare('UPDATE devices SET is_online = 0 WHERE controller_code = ?')
                ->execute([$controllerCode]);
        }

        public function markStaleOffline(int $seconds): int
        {
            $stmt = $this->pdo->prepare('
                UPDATE devices SET is_online = 0
                WHERE is_online = 1 AND (last_seen_at IS NULL OR last_seen_at < NOW() - INTERVAL ? SECOND)
            ');
            $stmt->execute([$seconds]);
            return $stmt->rowCount();
        }

        public function controllerStatus(string $controllerCode): array
        {
            $stmt = $this->pdo->prepare('
                SELECT COALESCE(MAX(' . self::ONLINE_SQL . '), 0) AS is_online, MAX(last_seen_at) AS last_seen_at
                FROM devices WHERE controller_code = ?
            ');
            $stmt->execute([$controllerCode]);
            return $stmt->fetch();
        }
    }
?>