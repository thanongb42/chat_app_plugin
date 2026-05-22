-- admin_security_setup.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < admin_security_setup.sql

CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
  `id`           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  `ip_address`   VARCHAR(45)     NOT NULL,
  `success`      TINYINT(1)      NOT NULL DEFAULT 0,
  `attempted_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
