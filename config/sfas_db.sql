-- ============================================================
-- SMART FARMING ADVISORY SYSTEM (SFAS)
-- Database: sfas_db
-- Case Study: Nyagatare District — Anny Green Harvest
-- Student: Daniella UMURERWA | University of Kigali
-- ============================================================

CREATE DATABASE IF NOT EXISTS `sfas_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `sfas_db`;

-- ── PASSWORD RESETS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150)  NOT NULL,
  `otp`        VARCHAR(10)   NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  `used`       TINYINT(1)    DEFAULT 0,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ROLES ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)  NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`description`) VALUES
  (1,'Admin',   'Full system administrator'),
  (2,'Agronomist','Agricultural advisor who creates tips and responds to farmers'),
  (3,'Farmer',  'Registered farmer who receives advice and logs farm data');

-- ── PERMISSIONS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `key`         VARCHAR(100) NOT NULL,
  `module`      VARCHAR(100) NOT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`,`key`,`module`,`action`,`description`) VALUES
  (1,  'users.view',          'Users',    'view',   'View user list'),
  (2,  'users.create',        'Users',    'create', 'Create new users'),
  (3,  'users.edit',          'Users',    'edit',   'Edit user info'),
  (4,  'users.delete',        'Users',    'delete', 'Delete users'),
  (5,  'farms.view',          'Farms',    'view',   'View all farms'),
  (6,  'farms.edit',          'Farms',    'edit',   'Edit farm records'),
  (7,  'farms.delete',        'Farms',    'delete', 'Delete farms'),
  (8,  'advisory.view',       'Advisory', 'view',   'View all advisory tips'),
  (9,  'advisory.create',     'Advisory', 'create', 'Create advisory tips'),
  (10, 'advisory.edit',       'Advisory', 'edit',   'Edit advisory tips'),
  (11, 'advisory.delete',     'Advisory', 'delete', 'Delete advisory tips'),
  (12, 'alerts.manage',       'Alerts',   'manage', 'Send and manage alerts'),
  (13, 'market.manage',       'Market',   'manage', 'Manage market prices'),
  (14, 'reports.view',        'Reports',  'view',   'View reports'),
  (15, 'reports.export',      'Reports',  'export', 'Export reports'),
  (16, 'ai.use',              'AI',       'use',    'Use AI assistant'),
  (17, 'settings.manage',     'Settings', 'manage', 'Manage system settings'),
  (18, 'chat.use',            'AI',       'chat',   'Use AI farming chat'),
  (19, 'weather.view',        'Weather',  'view',   'View weather data');

-- ── ROLE PERMISSIONS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id`       INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_perm` (`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`)       REFERENCES `roles`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin gets everything
INSERT INTO `role_permissions` (`role_id`,`permission_id`)
  SELECT 1,id FROM `permissions`;

-- Agronomist permissions
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES
  (2,5),(2,8),(2,9),(2,10),(2,12),(2,13),(2,14),(2,16),(2,18),(2,19);

-- Farmer permissions
INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES
  (3,14),(3,16),(3,18),(3,19);

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `firstname`      VARCHAR(80)   NOT NULL,
  `lastname`       VARCHAR(80)   NOT NULL,
  `username`       VARCHAR(80)   DEFAULT NULL,
  `email`          VARCHAR(150)  NOT NULL,
  `phone`          VARCHAR(20)   DEFAULT NULL,
  `password`       VARCHAR(255)  NOT NULL,
  `role_id`        INT(11)       DEFAULT 3,
  `role_name`      VARCHAR(80)   DEFAULT 'Farmer',
  `is_super_admin` TINYINT(1)    DEFAULT 0,
  `account_status` ENUM('active','pending','inactive','suspended') DEFAULT 'pending',
  `photo`          VARCHAR(255)  DEFAULT NULL,
  `district`       VARCHAR(60)   DEFAULT NULL  COMMENT 'Farmer location district',
  `sector`         VARCHAR(60)   DEFAULT NULL,
  `otp_code`       VARCHAR(10)   DEFAULT NULL,
  `otp_expiry`     DATETIME      DEFAULT NULL,
  `last_login`     DATETIME      DEFAULT NULL,
  `created_by`     INT(11)       DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role`   (`role_id`),
  KEY `idx_status` (`account_status`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default super-admin (password: Admin@1234)
INSERT INTO `users`
  (`id`,`firstname`,`lastname`,`email`,`password`,`role_id`,`role_name`,`is_super_admin`,`account_status`,`district`)
VALUES
  (1,'Daniella','UMURERWA','admin@sfas.rw',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   1,'Admin',1,'active','Nyagatare');

-- ── USER ACTIVITY LOG ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NOT NULL,
  `action`      VARCHAR(80)  NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ual_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CROPS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `crops` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(100) NOT NULL,
  `local_name`      VARCHAR(100) DEFAULT NULL  COMMENT 'Kinyarwanda name',
  `category`        ENUM('Cereal','Legume','Vegetable','Fruit','Root','Cash Crop','Forage') DEFAULT NULL,
  `growing_season`  VARCHAR(100) DEFAULT NULL  COMMENT 'e.g. Season A (Sep-Feb)',
  `min_rainfall_mm` INT(11)      DEFAULT NULL,
  `max_rainfall_mm` INT(11)      DEFAULT NULL,
  `description`     TEXT         DEFAULT NULL,
  `image`           VARCHAR(255) DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crops` (`id`,`name`,`local_name`,`category`,`growing_season`,`min_rainfall_mm`,`max_rainfall_mm`,`description`) VALUES
  (1,'Maize','Ibigori','Cereal','Season A & B',500,800,'Staple crop grown widely across Rwanda. Thrives in well-drained loam soils.'),
  (2,'Beans','Ibishyimbo','Legume','Season A & B',300,600,'Primary protein source; fixes nitrogen improving soil fertility.'),
  (3,'Irish Potato','Ibirayi','Root','Season A & B',700,1200,'Key cash crop in highlands. Nyagatare suits short-season varieties.'),
  (4,'Cassava','Imyumbati','Root','Year-round',500,1000,'Drought-tolerant staple; important food security crop.'),
  (5,'Sorghum','Amasaka','Cereal','Season A',400,700,'Drought-resistant grain; used for food and local brew.'),
  (6,'Groundnut','Ikijumba','Legume','Season B',500,800,'Oil crop and protein source; good intercrop with maize.'),
  (7,'Sweet Potato','Ibijumba','Root','Year-round',450,750,'Nutritious root crop; orange-flesh varieties promoted for Vit A.'),
  (8,'Tomato','Inyanya','Vegetable','Season A & B',600,1000,'High-value cash crop; sensitive to pests and blight.'),
  (9,'Onion','Icyunamo','Vegetable','Season B',400,700,'Irrigated onion production in Nyagatare is growing rapidly.');

-- ── FARMS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `farms` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `farmer_id`    INT(11)       NOT NULL,
  `farm_name`    VARCHAR(150)  NOT NULL,
  `district`     VARCHAR(60)   DEFAULT 'Nyagatare',
  `sector`       VARCHAR(60)   DEFAULT NULL,
  `cell`         VARCHAR(60)   DEFAULT NULL,
  `size_ha`      DECIMAL(8,2)  DEFAULT NULL  COMMENT 'Farm size in hectares',
  `soil_type`    ENUM('Clay','Sandy','Loam','Sandy-Loam','Clay-Loam','Silt') DEFAULT NULL,
  `water_source` ENUM('Rain-fed','Irrigation','Both') DEFAULT 'Rain-fed',
  `latitude`     DECIMAL(10,7) DEFAULT NULL,
  `longitude`    DECIMAL(10,7) DEFAULT NULL,
  `notes`        TEXT          DEFAULT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm_farmer` (`farmer_id`),
  CONSTRAINT `fk_farm_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── FARM CROPS (what a farm currently grows) ──────────────────
CREATE TABLE IF NOT EXISTS `farm_crops` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `farm_id`     INT(11) NOT NULL,
  `crop_id`     INT(11) NOT NULL,
  `season`      VARCHAR(60)  DEFAULT NULL,
  `area_ha`     DECIMAL(6,2) DEFAULT NULL,
  `planted_at`  DATE         DEFAULT NULL,
  `expected_harvest` DATE    DEFAULT NULL,
  `status`      ENUM('Planning','Growing','Harvested','Failed') DEFAULT 'Growing',
  `notes`       TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fc_farm` (`farm_id`),
  KEY `fk_fc_crop` (`crop_id`),
  CONSTRAINT `fk_fc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fc_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ADVISORY TIPS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `advisory_tips` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200)  NOT NULL,
  `content`     TEXT          NOT NULL,
  `category`    ENUM('Crop Management','Pest & Disease','Soil Health','Irrigation','Harvest & Post-Harvest','Market','General') DEFAULT 'General',
  `crop_id`     INT(11)       DEFAULT NULL  COMMENT 'NULL = applies to all crops',
  `season`      VARCHAR(60)   DEFAULT NULL,
  `district`    VARCHAR(60)   DEFAULT NULL  COMMENT 'NULL = all districts',
  `author_id`   INT(11)       DEFAULT NULL,
  `is_active`   TINYINT(1)    DEFAULT 1,
  `views`       INT(11)       DEFAULT 0,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_at_crop`     (`crop_id`),
  KEY `idx_at_category` (`category`),
  KEY `idx_at_district` (`district`),
  CONSTRAINT `fk_at_crop`   FOREIGN KEY (`crop_id`)   REFERENCES `crops`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_at_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `advisory_tips` (`id`,`title`,`content`,`category`,`crop_id`,`season`,`district`,`author_id`,`is_active`) VALUES
  (1,'Maize Planting Best Practices','Plant maize at the onset of rains (Season A: Sep–Oct). Space rows 75cm apart, hills 25cm. Apply DAP fertilizer at planting — 50kg/ha. Top-dress with urea at knee height. Weed twice before canopy closure.','Crop Management',1,'Season A','Nyagatare',1,1),
  (2,'Bean Rust Alert — Season B 2026','Bean rust (Uromyces appendiculatus) has been confirmed in Karangazi and Rwempasha sectors. Apply Mancozeb 80% WP (2g/L) at first sign of orange pustules on leaves. Repeat every 10 days. Avoid overhead irrigation.','Pest & Disease',2,'Season B',NULL,1,1),
  (3,'Soil Preparation for Irish Potato','Deep-till to 30cm before planting. Incorporate 10 tonnes/ha well-rotted farmyard manure. Irish potatoes prefer slightly acidic soil (pH 5.5–6.5). Apply lime if pH < 5.0. Ridge planting reduces waterlogging.','Soil Health',3,NULL,'Nyagatare',1,1),
  (4,'Tomato Blight Management','Early blight (Alternaria solani) causes dark spots on older leaves. Remove and burn infected leaves. Spray copper-based fungicide (Copper oxychloride 50% WP, 3g/L) every 7 days. Ensure good air circulation between plants.','Pest & Disease',8,'Season A',NULL,1,1),
  (5,'Post-Harvest Maize Storage','Sun-dry maize cobs to below 13% moisture before shelling. Store in hermetic bags (e.g. PICS bags) to prevent weevil damage — no chemicals needed. Label bags with crop, date, and quantity. Store off the ground on wooden pallets.','Harvest & Post-Harvest',1,NULL,NULL,1,1);

-- ── PEST ALERTS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pest_alerts` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200)  NOT NULL,
  `description` TEXT          NOT NULL,
  `pest_name`   VARCHAR(100)  DEFAULT NULL,
  `crop_id`     INT(11)       DEFAULT NULL,
  `severity`    ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
  `district`    VARCHAR(60)   DEFAULT NULL,
  `sector`      VARCHAR(60)   DEFAULT NULL,
  `reported_by` INT(11)       DEFAULT NULL,
  `is_active`   TINYINT(1)    DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pa_crop`     (`crop_id`),
  KEY `idx_pa_district` (`district`),
  CONSTRAINT `fk_pa_crop`     FOREIGN KEY (`crop_id`)    REFERENCES `crops`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pa_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pest_alerts` (`id`,`title`,`description`,`pest_name`,`crop_id`,`severity`,`district`,`sector`,`reported_by`,`is_active`) VALUES
  (1,'Fall Armyworm in Maize','Fall Armyworm (Spodoptera frugiperda) has been sighted in Karama sector. Check plants at night. Look for ragged leaf edges and window-pane feeding on young leaves. Apply Emamectin benzoate 1.9% EC (200ml/ha) in the evening.','Fall Armyworm',1,'High','Nyagatare','Karama',1,1),
  (2,'Cassava Mosaic Disease','Cassava mosaic virus detected in Kiyombe sector. Symptoms: mosaic yellowing, leaf distortion. Remove and burn infected plants. Source clean planting material from certified nurseries. Control whitefly vectors.','Cassava Mosaic Virus',4,'Medium','Nyagatare','Kiyombe',1,1);

-- ── MARKET PRICES ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `market_prices` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `crop_id`     INT(11)       NOT NULL,
  `market`      VARCHAR(100)  NOT NULL  COMMENT 'Market name/location',
  `district`    VARCHAR(60)   DEFAULT NULL,
  `price_rwf`   DECIMAL(10,2) NOT NULL  COMMENT 'Price in RWF per kg',
  `unit`        VARCHAR(20)   DEFAULT 'kg',
  `price_date`  DATE          NOT NULL,
  `source`      VARCHAR(100)  DEFAULT 'Field Survey',
  `updated_by`  INT(11)       DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mp_crop` (`crop_id`),
  KEY `idx_mp_date` (`price_date`),
  CONSTRAINT `fk_mp_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `market_prices` (`id`,`crop_id`,`market`,`district`,`price_rwf`,`unit`,`price_date`,`source`,`updated_by`) VALUES
  (1, 1,'Nyagatare Main Market',  'Nyagatare',350.00,'kg','2026-06-20','Field Survey',1),
  (2, 2,'Nyagatare Main Market',  'Nyagatare',650.00,'kg','2026-06-20','Field Survey',1),
  (3, 3,'Nyagatare Main Market',  'Nyagatare',280.00,'kg','2026-06-20','Field Survey',1),
  (4, 4,'Nyagatare Main Market',  'Nyagatare',200.00,'kg','2026-06-20','Field Survey',1),
  (5, 5,'Nyagatare Main Market',  'Nyagatare',300.00,'kg','2026-06-20','Field Survey',1),
  (6, 8,'Musanze Market',         'Musanze',   900.00,'kg','2026-06-20','Field Survey',1),
  (7, 9,'Musanze Market',         'Musanze',   750.00,'kg','2026-06-20','Field Survey',1),
  (8, 1,'Kigali Kimironko Market','Kigali',    420.00,'kg','2026-06-20','REMA Data',1),
  (9, 2,'Kigali Kimironko Market','Kigali',    750.00,'kg','2026-06-20','REMA Data',1),
  (10,3,'Kigali Kimironko Market','Kigali',    320.00,'kg','2026-06-20','REMA Data',1),
  (11,8,'Kigali Kimironko Market','Kigali',    1100.00,'kg','2026-06-20','REMA Data',1);

-- ── AI CHAT LOGS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ai_chat_logs` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11) NOT NULL,
  `session_id`  VARCHAR(64) DEFAULT NULL,
  `role`        ENUM('user','assistant') NOT NULL,
  `message`     TEXT NOT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_user`    (`user_id`),
  KEY `idx_chat_session` (`session_id`),
  CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── WEATHER CACHE ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `weather_cache` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `district`    VARCHAR(60)   NOT NULL,
  `lat`         DECIMAL(10,7) DEFAULT NULL,
  `lon`         DECIMAL(10,7) DEFAULT NULL,
  `data_json`   LONGTEXT      NOT NULL,
  `fetched_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
