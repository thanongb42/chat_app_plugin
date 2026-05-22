-- =============================================
-- PHP Chat Application - Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS `chat_app` 
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `chat_app`;

-- ตารางผู้ใช้
CREATE TABLE IF NOT EXISTS `chat_users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `avatar_color` VARCHAR(7) DEFAULT '#4ECDC4',
  `is_online`  TINYINT(1) DEFAULT 0,
  `last_seen`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางห้องแชท
CREATE TABLE IF NOT EXISTS `chat_rooms` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT '',
  `is_public`   TINYINT(1) DEFAULT 1,
  `created_by`  INT,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `chat_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางข้อความ
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `room_id`    INT NOT NULL DEFAULT 1,
  `user_id`    INT,
  `username`   VARCHAR(50) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `avatar_color` VARCHAR(7) DEFAULT '#4ECDC4',
  `message`    TEXT NOT NULL,
  `msg_type`   ENUM('text','image','file','system') DEFAULT 'text',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_room_created` (`room_id`, `created_at`),
  FOREIGN KEY (`room_id`) REFERENCES `chat_rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ห้องเริ่มต้น
INSERT INTO `chat_rooms` (`name`, `description`, `is_public`) 
VALUES ('ห้องทั่วไป', 'ห้องสนทนาสาธารณะ', 1);

INSERT INTO `chat_rooms` (`name`, `description`, `is_public`) 
VALUES ('ประกาศ', 'ห้องสำหรับประกาศข่าวสาร', 1);

INSERT INTO `chat_rooms` (`name`, `description`, `is_public`) 
VALUES ('ถามตอบ', 'ห้องถามตอบปัญหา', 1);

-- ข้อความตัวอย่าง
INSERT INTO `chat_messages` (`room_id`, `username`, `display_name`, `avatar_color`, `message`, `msg_type`) 
VALUES 
(1, 'system', 'ระบบ', '#888888', 'ยินดีต้อนรับสู่ห้องสนทนา! 🎉', 'system'),
(1, 'admin', 'ผู้ดูแลระบบ', '#FF6B6B', 'สวัสดีทุกคน! ยินดีต้อนรับครับ 👋', 'text');
