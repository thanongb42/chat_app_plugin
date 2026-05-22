-- add_device_id.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_device_id.sql

ALTER TABLE `chat_users`
  ADD COLUMN `device_id` VARCHAR(64) NULL AFTER `avatar_color`;

ALTER TABLE `chat_users`
  ADD INDEX `idx_device_id` (`device_id`);
