-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.32-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.17.0.7270
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for sfas_db
CREATE DATABASE IF NOT EXISTS `sfas_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `sfas_db`;

-- Dumping structure for table sfas_db.advisory_tips
CREATE TABLE IF NOT EXISTS `advisory_tips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('Crop Management','Pest & Disease','Soil Health','Irrigation','Harvest & Post-Harvest','Market','General') DEFAULT 'General',
  `crop_id` int(11) DEFAULT NULL COMMENT 'NULL = applies to all crops',
  `season` varchar(60) DEFAULT NULL,
  `district` varchar(60) DEFAULT NULL COMMENT 'NULL = all districts',
  `author_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_at_crop` (`crop_id`),
  KEY `idx_at_category` (`category`),
  KEY `idx_at_district` (`district`),
  KEY `fk_at_author` (`author_id`),
  CONSTRAINT `fk_at_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_at_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.advisory_tips: ~5 rows (approximately)
REPLACE INTO `advisory_tips` (`id`, `title`, `content`, `category`, `crop_id`, `season`, `district`, `author_id`, `is_active`, `views`, `created_at`, `updated_at`) VALUES
	(1, 'Maize Planting Best Practices', 'Plant maize at the onset of rains (Season A: Sep–Oct). Space rows 75cm apart, hills 25cm. Apply DAP fertilizer at planting — 50kg/ha. Top-dress with urea at knee height. Weed twice before canopy closure.', 'Crop Management', 1, 'Season A', 'Nyagatare', 1, 1, 0, '2026-06-26 23:34:15', '2026-06-26 23:34:15'),
	(2, 'Bean Rust Alert — Season B 2026', 'Bean rust (Uromyces appendiculatus) has been confirmed in Karangazi and Rwempasha sectors. Apply Mancozeb 80% WP (2g/L) at first sign of orange pustules on leaves. Repeat every 10 days. Avoid overhead irrigation.', 'Pest & Disease', 2, 'Season B', NULL, 1, 1, 0, '2026-06-26 23:34:15', '2026-06-26 23:34:15'),
	(3, 'Soil Preparation for Irish Potato', 'Deep-till to 30cm before planting. Incorporate 10 tonnes/ha well-rotted farmyard manure. Irish potatoes prefer slightly acidic soil (pH 5.5–6.5). Apply lime if pH < 5.0. Ridge planting reduces waterlogging.', 'Soil Health', 3, NULL, 'Nyagatare', 1, 1, 0, '2026-06-26 23:34:15', '2026-06-26 23:34:15'),
	(4, 'Tomato Blight Management', 'Early blight (Alternaria solani) causes dark spots on older leaves. Remove and burn infected leaves. Spray copper-based fungicide (Copper oxychloride 50% WP, 3g/L) every 7 days. Ensure good air circulation between plants.', 'Pest & Disease', 8, 'Season A', NULL, 1, 1, 0, '2026-06-26 23:34:15', '2026-06-26 23:34:15'),
	(5, 'Post-Harvest Maize Storage', 'Sun-dry maize cobs to below 13% moisture before shelling. Store in hermetic bags (e.g. PICS bags) to prevent weevil damage — no chemicals needed. Label bags with crop, date, and quantity. Store off the ground on wooden pallets.', 'Harvest & Post-Harvest', 1, NULL, NULL, 1, 1, 0, '2026-06-26 23:34:15', '2026-06-26 23:34:15');

-- Dumping structure for table sfas_db.ai_chat_logs
CREATE TABLE IF NOT EXISTS `ai_chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `role` enum('user','assistant') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_user` (`user_id`),
  KEY `idx_chat_session` (`session_id`),
  CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.ai_chat_logs: ~0 rows (approximately)

-- Dumping structure for table sfas_db.crops
CREATE TABLE IF NOT EXISTS `crops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `local_name` varchar(100) DEFAULT NULL COMMENT 'Kinyarwanda name',
  `category` enum('Cereal','Legume','Vegetable','Fruit','Root','Cash Crop','Forage') DEFAULT NULL,
  `growing_season` varchar(100) DEFAULT NULL COMMENT 'e.g. Season A (Sep-Feb)',
  `min_rainfall_mm` int(11) DEFAULT NULL,
  `max_rainfall_mm` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.crops: ~9 rows (approximately)
REPLACE INTO `crops` (`id`, `name`, `local_name`, `category`, `growing_season`, `min_rainfall_mm`, `max_rainfall_mm`, `description`, `image`, `created_at`) VALUES
	(1, 'Maize', 'Ibigori', 'Cereal', 'Season A & B', 500, 800, 'Staple crop grown widely across Rwanda. Thrives in well-drained loam soils.', NULL, '2026-06-26 23:34:15'),
	(2, 'Beans', 'Ibishyimbo', 'Legume', 'Season A & B', 300, 600, 'Primary protein source; fixes nitrogen improving soil fertility.', NULL, '2026-06-26 23:34:15'),
	(3, 'Irish Potato', 'Ibirayi', 'Root', 'Season A & B', 700, 1200, 'Key cash crop in highlands. Nyagatare suits short-season varieties.', NULL, '2026-06-26 23:34:15'),
	(4, 'Cassava', 'Imyumbati', 'Root', 'Year-round', 500, 1000, 'Drought-tolerant staple; important food security crop.', NULL, '2026-06-26 23:34:15'),
	(5, 'Sorghum', 'Amasaka', 'Cereal', 'Season A', 400, 700, 'Drought-resistant grain; used for food and local brew.', NULL, '2026-06-26 23:34:15'),
	(6, 'Groundnut', 'Ikijumba', 'Legume', 'Season B', 500, 800, 'Oil crop and protein source; good intercrop with maize.', NULL, '2026-06-26 23:34:15'),
	(7, 'Sweet Potato', 'Ibijumba', 'Root', 'Year-round', 450, 750, 'Nutritious root crop; orange-flesh varieties promoted for Vit A.', NULL, '2026-06-26 23:34:15'),
	(8, 'Tomato', 'Inyanya', 'Vegetable', 'Season A & B', 600, 1000, 'High-value cash crop; sensitive to pests and blight.', NULL, '2026-06-26 23:34:15'),
	(9, 'Onion', 'Icyunamo', 'Vegetable', 'Season B', 400, 700, 'Irrigated onion production in Nyagatare is growing rapidly.', NULL, '2026-06-26 23:34:15');

-- Dumping structure for table sfas_db.farm_crops
CREATE TABLE IF NOT EXISTS `farm_crops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `season` varchar(60) DEFAULT NULL,
  `area_ha` decimal(6,2) DEFAULT NULL,
  `planted_at` date DEFAULT NULL,
  `expected_harvest` date DEFAULT NULL,
  `status` enum('Planning','Growing','Harvested','Failed') DEFAULT 'Growing',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fc_farm` (`farm_id`),
  KEY `fk_fc_crop` (`crop_id`),
  CONSTRAINT `fk_fc_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  CONSTRAINT `fk_fc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.farm_crops: ~0 rows (approximately)

-- Dumping structure for table sfas_db.farms
CREATE TABLE IF NOT EXISTS `farms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmer_id` int(11) NOT NULL,
  `farm_name` varchar(150) NOT NULL,
  `district` varchar(60) DEFAULT 'Nyagatare',
  `sector` varchar(60) DEFAULT NULL,
  `cell` varchar(60) DEFAULT NULL,
  `size_ha` decimal(8,2) DEFAULT NULL COMMENT 'Farm size in hectares',
  `soil_type` enum('Clay','Sandy','Loam','Sandy-Loam','Clay-Loam','Silt') DEFAULT NULL,
  `water_source` enum('Rain-fed','Irrigation','Both') DEFAULT 'Rain-fed',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_farm_farmer` (`farmer_id`),
  CONSTRAINT `fk_farm_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.farms: ~0 rows (approximately)
REPLACE INTO `farms` (`id`, `farmer_id`, `farm_name`, `district`, `sector`, `cell`, `size_ha`, `soil_type`, `water_source`, `latitude`, `longitude`, `notes`, `created_at`, `updated_at`) VALUES
	(1, 2, 'Rwenkorere Farm Valley Nyagatare', 'Nyagatare', 'Karama', 'Nyagahanga', 5.00, 'Clay-Loam', 'Both', NULL, NULL, 'My Farm is always green and fertile', '2026-06-27 08:14:06', '2026-06-27 08:14:06');

-- Dumping structure for table sfas_db.market_prices
CREATE TABLE IF NOT EXISTS `market_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crop_id` int(11) NOT NULL,
  `market` varchar(100) NOT NULL COMMENT 'Market name/location',
  `district` varchar(60) DEFAULT NULL,
  `price_rwf` decimal(10,2) NOT NULL COMMENT 'Price in RWF per kg',
  `unit` varchar(20) DEFAULT 'kg',
  `price_date` date NOT NULL,
  `source` varchar(100) DEFAULT 'Field Survey',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mp_crop` (`crop_id`),
  KEY `idx_mp_date` (`price_date`),
  CONSTRAINT `fk_mp_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.market_prices: ~11 rows (approximately)
REPLACE INTO `market_prices` (`id`, `crop_id`, `market`, `district`, `price_rwf`, `unit`, `price_date`, `source`, `updated_by`, `created_at`) VALUES
	(1, 1, 'Nyagatare Main Market', 'Nyagatare', 350.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(2, 2, 'Nyagatare Main Market', 'Nyagatare', 650.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(3, 3, 'Nyagatare Main Market', 'Nyagatare', 280.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(4, 4, 'Nyagatare Main Market', 'Nyagatare', 200.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(5, 5, 'Nyagatare Main Market', 'Nyagatare', 300.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(6, 8, 'Musanze Market', 'Musanze', 900.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(7, 9, 'Musanze Market', 'Musanze', 750.00, 'kg', '2026-06-20', 'Field Survey', 1, '2026-06-26 23:34:15'),
	(8, 1, 'Kigali Kimironko Market', 'Kigali', 420.00, 'kg', '2026-06-20', 'REMA Data', 1, '2026-06-26 23:34:15'),
	(9, 2, 'Kigali Kimironko Market', 'Kigali', 750.00, 'kg', '2026-06-20', 'REMA Data', 1, '2026-06-26 23:34:15'),
	(10, 3, 'Kigali Kimironko Market', 'Kigali', 320.00, 'kg', '2026-06-20', 'REMA Data', 1, '2026-06-26 23:34:15'),
	(11, 8, 'Kigali Kimironko Market', 'Kigali', 1100.00, 'kg', '2026-06-20', 'REMA Data', 1, '2026-06-26 23:34:15'),
	(12, 1, 'Rubirizi Market Place', 'Rubavu', 480.00, 'kg', '2026-06-27', 'Trader Interview', 1, '2026-06-27 08:19:11');

-- Dumping structure for table sfas_db.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.password_resets: ~1 rows (approximately)
REPLACE INTO `password_resets` (`id`, `email`, `otp`, `expires_at`, `used`, `created_at`) VALUES
	(5, 'info.abaremy@gmail.com', '626765', '2026-06-27 10:00:15', 0, '2026-06-27 07:45:15');

-- Dumping structure for table sfas_db.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perm_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.permissions: ~19 rows (approximately)
REPLACE INTO `permissions` (`id`, `key`, `module`, `action`, `description`) VALUES
	(1, 'users.view', 'Users', 'view', 'View user list'),
	(2, 'users.create', 'Users', 'create', 'Create new users'),
	(3, 'users.edit', 'Users', 'edit', 'Edit user info'),
	(4, 'users.delete', 'Users', 'delete', 'Delete users'),
	(5, 'farms.view', 'Farms', 'view', 'View all farms'),
	(6, 'farms.edit', 'Farms', 'edit', 'Edit farm records'),
	(7, 'farms.delete', 'Farms', 'delete', 'Delete farms'),
	(8, 'advisory.view', 'Advisory', 'view', 'View all advisory tips'),
	(9, 'advisory.create', 'Advisory', 'create', 'Create advisory tips'),
	(10, 'advisory.edit', 'Advisory', 'edit', 'Edit advisory tips'),
	(11, 'advisory.delete', 'Advisory', 'delete', 'Delete advisory tips'),
	(12, 'alerts.manage', 'Alerts', 'manage', 'Send and manage alerts'),
	(13, 'market.manage', 'Market', 'manage', 'Manage market prices'),
	(14, 'reports.view', 'Reports', 'view', 'View reports'),
	(15, 'reports.export', 'Reports', 'export', 'Export reports'),
	(16, 'ai.use', 'AI', 'use', 'Use AI assistant'),
	(17, 'settings.manage', 'Settings', 'manage', 'Manage system settings'),
	(18, 'chat.use', 'AI', 'chat', 'Use AI farming chat'),
	(19, 'weather.view', 'Weather', 'view', 'View weather data');

-- Dumping structure for table sfas_db.pest_alerts
CREATE TABLE IF NOT EXISTS `pest_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `pest_name` varchar(100) DEFAULT NULL,
  `crop_id` int(11) DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `district` varchar(60) DEFAULT NULL,
  `sector` varchar(60) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pa_crop` (`crop_id`),
  KEY `idx_pa_district` (`district`),
  KEY `fk_pa_reporter` (`reported_by`),
  CONSTRAINT `fk_pa_crop` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pa_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.pest_alerts: ~2 rows (approximately)
REPLACE INTO `pest_alerts` (`id`, `title`, `description`, `pest_name`, `crop_id`, `severity`, `district`, `sector`, `reported_by`, `is_active`, `created_at`) VALUES
	(1, 'Fall Armyworm in Maize', 'Fall Armyworm (Spodoptera frugiperda) has been sighted in Karama sector. Check plants at night. Look for ragged leaf edges and window-pane feeding on young leaves. Apply Emamectin benzoate 1.9% EC (200ml/ha) in the evening.', 'Fall Armyworm', 1, 'High', 'Nyagatare', 'Karama', 1, 1, '2026-06-26 23:34:15'),
	(2, 'Cassava Mosaic Disease', 'Cassava mosaic virus detected in Kiyombe sector. Symptoms: mosaic yellowing, leaf distortion. Remove and burn infected plants. Source clean planting material from certified nurseries. Control whitefly vectors.', 'Cassava Mosaic Virus', 4, 'Medium', 'Nyagatare', 'Kiyombe', 1, 1, '2026-06-26 23:34:15');

-- Dumping structure for table sfas_db.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_perm` (`permission_id`),
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.role_permissions: ~33 rows (approximately)
REPLACE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
	(1, 1),
	(1, 2),
	(1, 3),
	(1, 4),
	(1, 5),
	(1, 6),
	(1, 7),
	(1, 8),
	(1, 9),
	(1, 10),
	(1, 11),
	(1, 12),
	(1, 13),
	(1, 14),
	(1, 15),
	(1, 16),
	(1, 17),
	(1, 18),
	(1, 19),
	(2, 5),
	(2, 8),
	(2, 9),
	(2, 10),
	(2, 12),
	(2, 13),
	(2, 14),
	(2, 16),
	(2, 18),
	(2, 19),
	(3, 14),
	(3, 15),
	(3, 16),
	(3, 18),
	(3, 19);

-- Dumping structure for table sfas_db.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.roles: ~3 rows (approximately)
REPLACE INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
	(1, 'Admin', 'Full system administrator', '2026-06-26 23:34:15'),
	(2, 'Agronomist', 'Agricultural advisor who creates tips and responds to farmers', '2026-06-26 23:34:15'),
	(3, 'Farmer', 'Registered farmer who receives advice and logs farm data', '2026-06-26 23:34:15');

-- Dumping structure for table sfas_db.user_activity_log
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ual_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.user_activity_log: ~0 rows (approximately)
REPLACE INTO `user_activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
	(1, 2, 'create', 'User account created', '::1', '2026-06-27 08:12:23');

-- Dumping structure for table sfas_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(80) NOT NULL,
  `lastname` varchar(80) NOT NULL,
  `username` varchar(80) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT 3,
  `role_name` varchar(80) DEFAULT 'Farmer',
  `is_super_admin` tinyint(1) DEFAULT 0,
  `account_status` enum('active','pending','inactive','suspended') DEFAULT 'pending',
  `photo` varchar(255) DEFAULT NULL,
  `district` varchar(60) DEFAULT NULL COMMENT 'Farmer location district',
  `sector` varchar(60) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role_id`),
  KEY `idx_status` (`account_status`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.users: ~2 rows (approximately)
REPLACE INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `phone`, `password`, `role_id`, `role_name`, `is_super_admin`, `account_status`, `photo`, `district`, `sector`, `otp_code`, `otp_expiry`, `last_login`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 'Daniella', 'UMURERWA', NULL, 'info.abaremy@gmail.com', NULL, '$2y$10$QjaSCGU.93W2MaMnS3msJOO0Um0/GOEjz2UatFmqk29wWm1Ufdk/y', 1, 'Admin', 1, 'active', NULL, 'Nyagatare', NULL, NULL, NULL, '2026-06-27 10:22:51', NULL, '2026-06-26 23:34:15', '2026-06-27 08:22:51'),
	(2, 'GASHAYIJA', 'Amza', NULL, 'aba1remy@gmail.com', '078952432', '$2y$10$Ic/txIMXnza0L.PEkbmwiOPJ05ndspwWesIZasHJrxJzvlsLu1Kou', 3, 'Farmer', 0, 'active', NULL, 'Nyagatare', NULL, NULL, NULL, '2026-06-27 10:21:20', 1, '2026-06-27 08:12:23', '2026-06-27 08:21:20');

-- Dumping structure for table sfas_db.weather_cache
CREATE TABLE IF NOT EXISTS `weather_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district` varchar(60) NOT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lon` decimal(10,7) DEFAULT NULL,
  `data_json` longtext NOT NULL,
  `fetched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sfas_db.weather_cache: ~0 rows (approximately)
REPLACE INTO `weather_cache` (`id`, `district`, `lat`, `lon`, `data_json`, `fetched_at`) VALUES
	(1, 'Nyagatare', -1.2956000, 30.3256000, '{"latitude":-1.3005272,"longitude":30.355452,"generationtime_ms":4.311919212341309,"utc_offset_seconds":7200,"timezone":"Africa\\/Kigali","timezone_abbreviation":"GMT+2","elevation":1345,"current_units":{"time":"iso8601","interval":"seconds","temperature_2m":"\\u00b0C","relative_humidity_2m":"%","precipitation":"mm","wind_speed_10m":"km\\/h","weather_code":"wmo code","apparent_temperature":"\\u00b0C"},"current":{"time":"2026-06-27T10:00","interval":900,"temperature_2m":26.3,"relative_humidity_2m":25,"precipitation":0,"wind_speed_10m":20.2,"weather_code":2,"apparent_temperature":23},"hourly_units":{"time":"iso8601","temperature_2m":"\\u00b0C","precipitation_probability":"%","weather_code":"wmo code"},"hourly":{"time":["2026-06-27T00:00","2026-06-27T01:00","2026-06-27T02:00","2026-06-27T03:00","2026-06-27T04:00","2026-06-27T05:00","2026-06-27T06:00","2026-06-27T07:00","2026-06-27T08:00","2026-06-27T09:00","2026-06-27T10:00","2026-06-27T11:00","2026-06-27T12:00","2026-06-27T13:00","2026-06-27T14:00","2026-06-27T15:00","2026-06-27T16:00","2026-06-27T17:00","2026-06-27T18:00","2026-06-27T19:00","2026-06-27T20:00","2026-06-27T21:00","2026-06-27T22:00","2026-06-27T23:00","2026-06-28T00:00","2026-06-28T01:00","2026-06-28T02:00","2026-06-28T03:00","2026-06-28T04:00","2026-06-28T05:00","2026-06-28T06:00","2026-06-28T07:00","2026-06-28T08:00","2026-06-28T09:00","2026-06-28T10:00","2026-06-28T11:00","2026-06-28T12:00","2026-06-28T13:00","2026-06-28T14:00","2026-06-28T15:00","2026-06-28T16:00","2026-06-28T17:00","2026-06-28T18:00","2026-06-28T19:00","2026-06-28T20:00","2026-06-28T21:00","2026-06-28T22:00","2026-06-28T23:00","2026-06-29T00:00","2026-06-29T01:00","2026-06-29T02:00","2026-06-29T03:00","2026-06-29T04:00","2026-06-29T05:00","2026-06-29T06:00","2026-06-29T07:00","2026-06-29T08:00","2026-06-29T09:00","2026-06-29T10:00","2026-06-29T11:00","2026-06-29T12:00","2026-06-29T13:00","2026-06-29T14:00","2026-06-29T15:00","2026-06-29T16:00","2026-06-29T17:00","2026-06-29T18:00","2026-06-29T19:00","2026-06-29T20:00","2026-06-29T21:00","2026-06-29T22:00","2026-06-29T23:00","2026-06-30T00:00","2026-06-30T01:00","2026-06-30T02:00","2026-06-30T03:00","2026-06-30T04:00","2026-06-30T05:00","2026-06-30T06:00","2026-06-30T07:00","2026-06-30T08:00","2026-06-30T09:00","2026-06-30T10:00","2026-06-30T11:00","2026-06-30T12:00","2026-06-30T13:00","2026-06-30T14:00","2026-06-30T15:00","2026-06-30T16:00","2026-06-30T17:00","2026-06-30T18:00","2026-06-30T19:00","2026-06-30T20:00","2026-06-30T21:00","2026-06-30T22:00","2026-06-30T23:00","2026-07-01T00:00","2026-07-01T01:00","2026-07-01T02:00","2026-07-01T03:00","2026-07-01T04:00","2026-07-01T05:00","2026-07-01T06:00","2026-07-01T07:00","2026-07-01T08:00","2026-07-01T09:00","2026-07-01T10:00","2026-07-01T11:00","2026-07-01T12:00","2026-07-01T13:00","2026-07-01T14:00","2026-07-01T15:00","2026-07-01T16:00","2026-07-01T17:00","2026-07-01T18:00","2026-07-01T19:00","2026-07-01T20:00","2026-07-01T21:00","2026-07-01T22:00","2026-07-01T23:00","2026-07-02T00:00","2026-07-02T01:00","2026-07-02T02:00","2026-07-02T03:00","2026-07-02T04:00","2026-07-02T05:00","2026-07-02T06:00","2026-07-02T07:00","2026-07-02T08:00","2026-07-02T09:00","2026-07-02T10:00","2026-07-02T11:00","2026-07-02T12:00","2026-07-02T13:00","2026-07-02T14:00","2026-07-02T15:00","2026-07-02T16:00","2026-07-02T17:00","2026-07-02T18:00","2026-07-02T19:00","2026-07-02T20:00","2026-07-02T21:00","2026-07-02T22:00","2026-07-02T23:00","2026-07-03T00:00","2026-07-03T01:00","2026-07-03T02:00","2026-07-03T03:00","2026-07-03T04:00","2026-07-03T05:00","2026-07-03T06:00","2026-07-03T07:00","2026-07-03T08:00","2026-07-03T09:00","2026-07-03T10:00","2026-07-03T11:00","2026-07-03T12:00","2026-07-03T13:00","2026-07-03T14:00","2026-07-03T15:00","2026-07-03T16:00","2026-07-03T17:00","2026-07-03T18:00","2026-07-03T19:00","2026-07-03T20:00","2026-07-03T21:00","2026-07-03T22:00","2026-07-03T23:00"],"temperature_2m":[19,17.8,18.1,17.6,17.1,16.9,16.6,18.5,22.2,24.7,26.3,27.3,28.3,29.1,29.5,29.3,28.7,27.7,25.6,23.8,22.6,21,20.4,19.5,18.8,18.1,17.9,17.4,16.6,15.7,14.8,16.6,20.6,24.6,26.9,28.4,29.4,30.1,30.5,30.4,29.8,28.8,26.4,24.3,22.6,22,20.4,19.4,18.7,18.3,18,17.9,17.1,16.3,15.6,17.3,21.2,25.5,27.8,29.2,30.1,30.7,31,30.9,30.4,29.5,26.7,24.7,23.2,22.3,21.9,20.9,20,19.2,19.2,18.9,18.2,17.1,16.4,18,21.9,25.9,28.2,29.5,30.2,31,30.5,30,29.8,29.2,26.9,25.1,24.2,23.1,22.2,21.5,20.8,20.3,19.7,18.7,17.6,17.5,18.8,21,23.2,25.3,27.4,29,29.9,30.4,30.5,30.3,29.8,28.9,27.5,25.6,24.1,23.3,22.8,22.2,21.2,20.1,19.2,18.5,18,18.2,19.3,21.1,23,25.3,27.8,29.6,30.1,29.9,29.6,29.3,28.8,28.1,27.1,25.8,24.5,23.3,22.1,21.1,20.8,20.2,19.9,19.9,20.2,20.7,21.4,22.2,23.1,24.3,25.9,27.6,29.2,30.3,30.9,30.6,29.7,28.5,27,25.6,24.5,23.4,22.3,21.3],"precipitation_probability":[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,3,4,4,3,2,2,2,2,1,1,0,0,0,0,0,0,0,0,0,0,1,2,2,1,1,0,1,1,2,2,1,0,0,1,1,1,0,0,0,0,0,0,0,0,0,1,1,1,2,2,2,1,1,1,0,0,0,0,0],"weather_code":[0,0,2,2,1,1,1,1,1,1,2,1,2,2,3,1,1,0,0,0,0,0,0,0,0,1,1,1,2,3,3,3,3,2,1,2,2,2,1,0,0,0,0,0,0,0,0,0,1,2,3,3,3,3,3,3,2,1,2,3,2,2,2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,2,1,0,0,0,1,3,3,3,3,3,2,2,2,2,2,1,1,0,0,0,0,0,0,1,1,0,1,1,2,3,3,3,3,3,3,3,3,3,3,3,3,3,2,2,1,1,1,1,1,2,2,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,2,2,2,2,1,1,1,1,2,2,2,2,3]},"daily_units":{"time":"iso8601","weather_code":"wmo code","temperature_2m_max":"\\u00b0C","temperature_2m_min":"\\u00b0C","precipitation_sum":"mm","wind_speed_10m_max":"km\\/h","uv_index_max":""},"daily":{"time":["2026-06-27","2026-06-28","2026-06-29","2026-06-30","2026-07-01","2026-07-02","2026-07-03"],"weather_code":[3,3,3,3,3,3,3],"temperature_2m_max":[29.5,30.5,31,31,30.5,30.1,30.9],"temperature_2m_min":[16.6,14.8,15.6,16.4,17.5,18,19.9],"precipitation_sum":[0,0,0,0,0,0,0],"wind_speed_10m_max":[20.2,15.9,15.7,17.3,19.8,17.8,13],"uv_index_max":[8.15,8.15,8.2,8.2,5.7,8,7.6]},"meta":{"district":"Nyagatare","latitude":-1.2956,"longitude":30.3256,"fetched_at":"2026-06-27 10:08:48","success":true}}', '2026-06-27 08:08:48');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
