-- add_admin_users.sql — สร้างตารางเจ้าหน้าที่
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_admin_users.sql
-- จากนั้นรัน: php setup_admin_users.php

USE `chat_app`;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)     NOT NULL,
  `display_name`  VARCHAR(100)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `role`          ENUM('superadmin','staff') NOT NULL DEFAULT 'staff',
  `avatar_color`  VARCHAR(7)      NOT NULL DEFAULT '#1565C0',
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login`    DATETIME        NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_username` (`username`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
