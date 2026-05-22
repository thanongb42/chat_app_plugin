-- add_resolved_status.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_resolved_status.sql

ALTER TABLE `chat_bot_log`
  ADD COLUMN `is_resolved`  TINYINT(1)   NOT NULL DEFAULT 0   AFTER `latency_ms`,
  ADD COLUMN `resolved_at`  DATETIME     NULL                  AFTER `is_resolved`,
  ADD COLUMN `resolved_by`  VARCHAR(100) NULL                  AFTER `resolved_at`;

ALTER TABLE `chat_bot_log`
  ADD INDEX `idx_resolved` (`is_resolved`);
