-- add_conversation_id.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_conversation_id.sql

ALTER TABLE `chat_messages`
  ADD COLUMN `conversation_id` VARCHAR(32) NULL AFTER `room_id`;

ALTER TABLE `chat_messages`
  ADD INDEX `idx_conversation` (`conversation_id`);
