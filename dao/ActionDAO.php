<?php
    class ActionDAO
    {
        public function __construct(private PDO $pdo) {}

        public function create(string $requestId, int $deviceId, int $userId, string $action): int
        {
            $this->pdo
                ->prepare("INSERT INTO actions (request_id, device_id, user_id, action, status, time_action) VALUES (?, ?, ?, ?, 'LOADING', NOW())")
                ->execute([$requestId, $deviceId, $userId, $action]);
            return (int) $this->pdo->lastInsertId();
        }

        public function findByRequestId(string $requestId): ?array
        {
            $stmt = $this->pdo->prepare('SELECT * FROM actions WHERE request_id = ?');
            $stmt->execute([$requestId]);
            return $stmt->fetch() ?: null;
        }

        public function findLoadingByDevice(int $deviceId): ?array
        {
            $stmt = $this->pdo->prepare("SELECT * FROM actions WHERE device_id = ? AND status = 'LOADING' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$deviceId]);
            return $stmt->fetch() ?: null;
        }

        public function latestByDevice(int $deviceId): ?array
        {
            $stmt = $this->pdo->prepare('SELECT request_id, action, status, error_message FROM actions WHERE device_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$deviceId]);
            return $stmt->fetch() ?: null;
        }

        public function maxId(): int
        {
            return (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) FROM actions')->fetchColumn();
        }

        public function complete(string $requestId, string $state): bool
        {
            $stmt = $this->pdo->prepare("
                UPDATE actions SET status = ?, time_status = NOW(), error_message = NULL
                WHERE request_id = ? AND status = 'LOADING'
            ");
            $stmt->execute([$state, $requestId]);
            return $stmt->rowCount() > 0;
        }

        public function fail(int $id, string $message): void
        {
            $this->pdo
                ->prepare("UPDATE actions SET status = 'FAILED', time_status = NOW(), error_message = ? WHERE id = ? AND status = 'LOADING'")
                ->execute([$message, $id]);
        }

        public function expireTimeouts(int $seconds): int
        {
            $stmt = $this->pdo->prepare("
                UPDATE actions SET status = 'TIMEOUT', time_status = NOW(), error_message = ?
                WHERE status = 'LOADING' AND time_action < NOW() - INTERVAL ? SECOND
            ");
            $stmt->execute(["ESP32 không phản hồi trong {$seconds} giây", $seconds]);
            return $stmt->rowCount();
        }

        public function changedSince(int $afterId, int $recentSeconds): array
        {
            $stmt = $this->pdo->prepare('
                SELECT a.id, a.request_id, d.device_code, a.action, a.status, a.time_action, a.time_status, a.error_message
                FROM actions a JOIN devices d ON d.id = a.device_id
                WHERE a.id > ? OR a.time_status >= NOW() - INTERVAL ? SECOND
                ORDER BY a.id
            ');
            $stmt->execute([$afterId, $recentSeconds]);
            return $stmt->fetchAll();
        }

        public function search(array $filters, int $page, int $size): array
        {
            $where = [];
            $params = [];

            if ($filters['device'] !== '') {
                $where[] = 'd.device_code = ?';
                $params[] = DeviceDAO::normalizeCode($filters['device']);
            }
            if ($filters['status'] !== '') {
                $where[] = 'a.status = ?';
                $params[] = $filters['status'];
            }
            if ($filters['action'] !== '') {
                $where[] = 'a.action = ?';
                $params[] = $filters['action'];
            }
            if ($filters['time_range'] !== null) {
                $where[] = 'a.time_action >= ? AND a.time_action < ?';
                array_push($params, ...$filters['time_range']);
            }

            $from = 'FROM actions a JOIN devices d ON d.id = a.device_id JOIN `user` u ON u.id = a.user_id '
                . ($where ? 'WHERE ' . implode(' AND ', $where) : '');

            $stmt = $this->pdo->prepare("SELECT COUNT(*) $from");
            $stmt->execute($params);
            $totalItems = (int) $stmt->fetchColumn();

            $offset = ($page - 1) * $size;
            $stmt = $this->pdo->prepare("
                SELECT a.id, a.request_id, d.device_code, d.device_name, u.username, a.action, a.status,
                       a.time_action, a.time_status, a.error_message
                $from
                ORDER BY a.time_action DESC, a.id DESC
                LIMIT $size OFFSET $offset
            ");
            $stmt->execute($params);

            return ['items' => $stmt->fetchAll(), 'totalItems' => $totalItems];
        }
    }
?>