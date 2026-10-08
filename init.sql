SET NAMES utf8mb4;

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS ptit_iot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ptit_iot;

CREATE TABLE `user` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `student_id` VARCHAR(20) NOT NULL UNIQUE,
    `class_name` VARCHAR(50) NULL,
    `group_name` VARCHAR(30) NULL,
    `avatar_url` VARCHAR(500) NULL,
    `github_url` VARCHAR(500) NULL,
    `figma_url` VARCHAR(500) NULL,
    `api_docs_url` VARCHAR(500) NULL,
    `report_pdf_url` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `devices` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `device_code` VARCHAR(50) NOT NULL UNIQUE,
    `device_name` VARCHAR(100) NOT NULL,
    `device_type` VARCHAR(30) NOT NULL,
    `controller_code` VARCHAR(50) NOT NULL,
    `command_topic` VARCHAR(255) NOT NULL,
    `state_topic` VARCHAR(255) NOT NULL,
    `current_state` VARCHAR(20) NOT NULL,
    `is_online` BOOLEAN NOT NULL DEFAULT FALSE,
    `last_seen_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `chk_devices_state` CHECK (`current_state` IN ('ON', 'OFF', 'UNKNOWN'))
) ENGINE=InnoDB;

CREATE TABLE `sensors` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `sensor_code` VARCHAR(50) NOT NULL UNIQUE,
    `sensor_name` VARCHAR(100) NOT NULL,
    `sensor_type` VARCHAR(30) NOT NULL,
    `unit` VARCHAR(20) NOT NULL,
    `controller_code` VARCHAR(50) NOT NULL,
    `data_topic` VARCHAR(255) NOT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `actions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `request_id` CHAR(36) NOT NULL UNIQUE,
    `device_id` BIGINT NOT NULL,
    `user_id` BIGINT NOT NULL,
    `action` VARCHAR(20) NOT NULL,
    `status` VARCHAR(20) NOT NULL,
    `time_action` DATETIME NOT NULL,
    `time_status` DATETIME NULL,
    `error_message` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_action_device` FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_action_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `chk_actions_action` CHECK (`action` IN ('TURN_ON', 'TURN_OFF')),
    CONSTRAINT `chk_actions_status` CHECK (`status` IN ('LOADING', 'ON', 'OFF', 'FAILED', 'TIMEOUT')),
    CONSTRAINT `chk_actions_time` CHECK (
        (`status` = 'LOADING' AND `time_status` IS NULL)
        OR (`status` <> 'LOADING' AND `time_status` IS NOT NULL AND `time_status` >= `time_action`)
    )
) ENGINE=InnoDB;

CREATE TABLE `data_sensors` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `sensor_id` BIGINT NOT NULL,
    `value` DECIMAL(10,2) NOT NULL,
    `recorded_at` DATETIME NOT NULL,
    `received_at` DATETIME NOT NULL,
    `raw_payload` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_data_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_data_sensors_recorded ON `data_sensors`(`sensor_id`, `recorded_at` DESC);
CREATE INDEX idx_actions_device_time ON `actions`(`device_id`, `time_action` DESC);
CREATE INDEX idx_actions_time ON `actions`(`time_action` DESC);
CREATE INDEX idx_data_sensors_time ON `data_sensors`(`recorded_at` DESC, `id` DESC);

INSERT INTO `user` (`username`, `password_hash`, `full_name`, `student_id`, `group_name`, `github_url`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doãn Duy Phúc', 'B23DCAT238', '13', 'https://github.com/quyt0/iot-web');

INSERT INTO `devices` (`device_code`, `device_name`, `device_type`, `controller_code`, `command_topic`, `state_topic`, `current_state`, `is_online`)
VALUES ('LED_01', 'Đèn LED phòng IoT', 'LED', 'ESP32_01', 'ptit/iot/room-01/devices/led-01/command', 'ptit/iot/room-01/devices/led-01/state', 'UNKNOWN', FALSE);

INSERT INTO `sensors` (`sensor_code`, `sensor_name`, `sensor_type`, `unit`, `controller_code`, `data_topic`, `is_active`) 
VALUES 
('TEMP_01', 'Nhiệt độ', 'TEMPERATURE', '°C', 'ESP32_01', 'ptit/iot/room-01/telemetry', TRUE),
('HUM_01', 'Độ ẩm', 'HUMIDITY', '%', 'ESP32_01', 'ptit/iot/room-01/telemetry', TRUE),
('LIGHT_01', 'Ánh sáng', 'LIGHT', '%', 'ESP32_01', 'ptit/iot/room-01/telemetry', TRUE);