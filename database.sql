-- Database: presenter_queue

-- Table for messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table to track the active message
CREATE TABLE IF NOT EXISTS `state` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `active_message_id` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default row for state table
INSERT INTO `state` (`id`, `active_message_id`) VALUES (1, NULL)
ON DUPLICATE KEY UPDATE `id`=1;

-- Table for settings
CREATE TABLE IF NOT EXISTS `settings` (
  `k` VARCHAR(50) PRIMARY KEY,
  `v` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`k`, `v`) VALUES
('font_size', '48'),
('color', '#ffffff'),
('theme', 'light')
ON DUPLICATE KEY UPDATE `k`=VALUES(`k`);
