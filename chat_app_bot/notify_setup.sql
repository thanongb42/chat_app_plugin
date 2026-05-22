-- notify_setup.sql — ตาราง Notification สำหรับระบบแจ้งเตือน
USE `chat_app`;

-- ตาราง Log การแจ้งเตือนที่ส่งออก
CREATE TABLE IF NOT EXISTS `chat_notifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `trigger_type` ENUM('fallback','offhours','unanswered','manual') NOT NULL,
  `channel`     ENUM('line','webhook','email','all') DEFAULT 'line',
  `room_id`     INT DEFAULT NULL,
  `user_name`   VARCHAR(100) DEFAULT NULL,
  `trigger_msg` TEXT,
  `sent_msg`    TEXT,
  `status`      ENUM('sent','failed','skipped') DEFAULT 'sent',
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created` (`created_at`),
  INDEX `idx_trigger`  (`trigger_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- เพิ่ม config keys สำหรับ notification
INSERT INTO `chat_bot_config` (`key_name`, `value`) VALUES
  ('line_notify_token',    ''),
  ('line_enabled',         '0'),
  ('webhook_url',          ''),
  ('webhook_secret',       ''),
  ('webhook_enabled',      '0'),
  ('notify_email',         ''),
  ('notify_email_enabled', '0'),
  ('notify_on_fallback',   '1'),
  ('notify_on_offhours',   '1'),
  ('notify_unanswered_min','10'),
  ('notify_cooldown_min',  '5'),
  ('office_start',         '08:30'),
  ('office_end',           '16:30'),
  ('office_days',          '1,2,3,4,5')
ON DUPLICATE KEY UPDATE `value` = `value`;
