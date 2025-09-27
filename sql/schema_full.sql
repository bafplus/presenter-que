-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS `live_presenter` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `live_presenter`;

-- Messages table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `k` VARCHAR(100) PRIMARY KEY,
  `v` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- State table (currently active message)
CREATE TABLE IF NOT EXISTS `state` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `active_message_id` INT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initialize state row
INSERT INTO `state` (`id`, `active_message_id`) VALUES (1, NULL)
ON DUPLICATE KEY UPDATE id=id;

-- Optional: initial settings
INSERT INTO `settings` (`k`, `v`) VALUES 
('font_size', '48'),
('color', '#ffffff')
ON DUPLICATE KEY UPDATE v=VALUES(v);
