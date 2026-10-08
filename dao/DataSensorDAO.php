<?php
    class DataSensorDAO
    {
        public function __construct(private PDO $pdo) {}

        public function insertTelemetry(string $controllerCode, array $values, string $recordedAt, string $rawPayload): void
        {
            $stmt = $this->pdo->prepare('
                INSERT INTO data_sensors (sensor_id, value, recorded_at, received_at, raw_payload)
                SELECT id, ?, ?, NOW(), ? FROM sensors
                WHERE sensor_type = ? AND controller_code = ? AND is_active = 1
            ');

            $this->pdo->beginTransaction();
            try {
                foreach ($values as $sensorType => $value) {
                    $stmt->execute([$value, $recordedAt, $rawPayload, $sensorType, $controllerCode]);
                }
                $this->pdo->commit();
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }

        public function maxId(): int
        {
            return (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) FROM data_sensors')->fetchColumn();
        }

        public function latest(): array
        {
            return $this->pdo->query('
                SELECT s.sensor_type, ds.value, ds.recorded_at
                FROM sensors s
                JOIN data_sensors ds ON ds.id = (
                    SELECT d2.id FROM data_sensors d2
                    WHERE d2.sensor_id = s.id
                    ORDER BY d2.recorded_at DESC, d2.id DESC
                    LIMIT 1
                )
                WHERE s.is_active = 1
            ')->fetchAll();
        }

        public function chart(int $points): array
        {
            return $this->pdo->query("
                SELECT ds.recorded_at,
                       MAX(CASE WHEN s.sensor_type = 'TEMPERATURE' THEN ds.value END) AS temperature,
                       MAX(CASE WHEN s.sensor_type = 'HUMIDITY' THEN ds.value END) AS humidity,
                       MAX(CASE WHEN s.sensor_type = 'LIGHT' THEN ds.value END) AS light
                FROM data_sensors ds JOIN sensors s ON s.id = ds.sensor_id
                WHERE ds.recorded_at >= (
                    SELECT MIN(t.recorded_at) FROM (
                        SELECT DISTINCT recorded_at FROM data_sensors ORDER BY recorded_at DESC LIMIT $points
                    ) t
                )
                GROUP BY ds.recorded_at
                ORDER BY ds.recorded_at
            ")->fetchAll();
        }

        public function search(array $filters, int $page, int $size): array
        {
            $where = [];
            $params = [];

            if ($filters['sensor_type'] !== '') {
                $where[] = 's.sensor_type = ?';
                $params[] = $filters['sensor_type'];
            }
            if ($filters['keyword'] !== '') {
                $like = '%' . addcslashes($filters['keyword'], '%_\\') . '%';
                $where[] = '(s.sensor_name LIKE ? OR s.sensor_code LIKE ? OR CAST(ds.value AS CHAR) LIKE ?)';
                array_push($params, $like, $like, $like);
            }
            if ($filters['time_range'] !== null) {
                $where[] = 'ds.recorded_at >= ? AND ds.recorded_at < ?';
                array_push($params, ...$filters['time_range']);
            }

            $from = 'FROM data_sensors ds JOIN sensors s ON s.id = ds.sensor_id '
                . ($where ? 'WHERE ' . implode(' AND ', $where) : '');

            $stmt = $this->pdo->prepare("SELECT COUNT(*) $from");
            $stmt->execute($params);
            $totalItems = (int) $stmt->fetchColumn();

            $offset = ($page - 1) * $size;
            $stmt = $this->pdo->prepare("
                SELECT ds.id, s.sensor_code, s.sensor_name, s.sensor_type, ds.value, s.unit, ds.recorded_at
                $from
                ORDER BY ds.recorded_at DESC, ds.id DESC
                LIMIT $size OFFSET $offset
            ");
            $stmt->execute($params);

            $items = array_map(function ($row) {
                $row['value'] = (float) $row['value'];
                return $row;
            }, $stmt->fetchAll());

            return ['items' => $items, 'totalItems' => $totalItems];
        }
    }
?>