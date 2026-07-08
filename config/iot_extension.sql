-- ============================================================
-- SFAS IoT Extension
-- Run this in phpMyAdmin after importing sfas_db.sql
-- File: config/iot_extension.sql
-- ============================================================

USE `sfas_db`;

-- ── IoT Sensor Readings ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `iot_readings` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `device_id`     VARCHAR(50)   NOT NULL DEFAULT 'SFAS-NODE-01'   COMMENT 'Unique sensor node ID',
  `location`      VARCHAR(100)  DEFAULT  'Nyagatare Farm'          COMMENT 'Farm or field name',
  `temperature`   DECIMAL(5,2)  DEFAULT  NULL                      COMMENT 'Air temperature in Celsius (DHT22)',
  `humidity`      DECIMAL(5,2)  DEFAULT  NULL                      COMMENT 'Air humidity percent (DHT22)',
  `soil_moisture` DECIMAL(5,2)  DEFAULT  NULL                      COMMENT 'Soil moisture percent (optional sensor)',
  `rainfall_mm`   DECIMAL(6,2)  DEFAULT  NULL                      COMMENT 'Rainfall in mm (optional rain gauge)',
  `light_lux`     DECIMAL(10,2) DEFAULT  NULL                      COMMENT 'Light intensity lux (optional LDR)',
  `battery_pct`   INT(3)        DEFAULT  NULL                      COMMENT 'Node battery percent',
  `raw_json`      TEXT          DEFAULT  NULL                      COMMENT 'Full raw payload from sensor',
  `recorded_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_iot_device`  (`device_id`),
  INDEX `idx_iot_time`    (`recorded_at`),
  INDEX `idx_iot_loc`     (`location`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Readings from ESP8266/Arduino IoT field sensors';

-- ── IoT Devices Registry ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `iot_devices` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `device_id`   VARCHAR(50)  NOT NULL,
  `name`        VARCHAR(100) DEFAULT NULL,
  `location`    VARCHAR(100) DEFAULT NULL,
  `farm_id`     INT(11)      DEFAULT NULL COMMENT 'Links to farms table',
  `sensors`     VARCHAR(255) DEFAULT 'DHT22' COMMENT 'Comma-separated sensor list',
  `is_active`   TINYINT(1)   DEFAULT 1,
  `last_seen`   DATETIME     DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_id` (`device_id`),
  KEY `fk_iot_farm` (`farm_id`),
  CONSTRAINT `fk_iot_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default demo device
INSERT IGNORE INTO `iot_devices` (`device_id`, `name`, `location`, `sensors`, `is_active`)
VALUES ('SFAS-NODE-01', 'Field Sensor Node 1', 'Nyagatare Demo Farm', 'DHT22 (Temp+Humidity)', 1);

-- ── Add IOT_SECRET to .env (reminder comment) ──────────────
-- Add this line to your .env file:
-- IOT_SECRET=sfas_iot_secret_2026
