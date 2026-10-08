<?php
    class SensorDAO
    {
        public function __construct(private PDO $pdo) {}

        public function allActive(): array
        {
            return $this->pdo
                ->query('SELECT sensor_code, sensor_name, sensor_type, unit FROM sensors WHERE is_active = 1 ORDER BY id')
                ->fetchAll();
        }

        public function types(): array
        {
            return $this->pdo->query('SELECT DISTINCT sensor_type FROM sensors')->fetchAll(PDO::FETCH_COLUMN);
        }
    }
?>