-- add_choices_support.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_choices_support.sql

-- ตัวเลือกย่อยของแต่ละ pattern (JSON array of {label, message})
ALTER TABLE `chat_bot_patterns`
  ADD COLUMN `choices` TEXT NULL AFTER `response`;

-- metadata ของแต่ละข้อความ (เก็บ choices ที่บอตแนบไปพร้อมข้อความ)
ALTER TABLE `chat_messages`
  ADD COLUMN `metadata` TEXT NULL AFTER `msg_type`;
