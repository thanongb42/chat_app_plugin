-- ============================================================
-- Migration: Operator Chat Features
-- Run once:
--   mysql -u root --default-character-set=utf8mb4 chat_app < add_operator_features.sql
-- ============================================================

-- 1. Conversation Sessions (tracks bot/operator mode, status per conversation)
CREATE TABLE IF NOT EXISTS chat_conversation_sessions (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id     VARCHAR(64) NOT NULL,
  room_id             INT NOT NULL DEFAULT 1,
  user_id             INT UNSIGNED,
  user_name           VARCHAR(100) DEFAULT '',
  status              ENUM('open','operator','resolved','closed') NOT NULL DEFAULT 'open',
  assigned_to         INT UNSIGNED DEFAULT NULL,
  assigned_name       VARCHAR(100) DEFAULT NULL,
  bot_enabled         TINYINT(1) NOT NULL DEFAULT 1,
  last_message        TEXT,
  last_msg_at         DATETIME DEFAULT NULL,
  admin_last_read_id  INT UNSIGNED NOT NULL DEFAULT 0,
  resolved_at         DATETIME DEFAULT NULL,
  resolved_by         VARCHAR(100) DEFAULT NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_conv (conversation_id),
  KEY idx_room_status (room_id, status),
  KEY idx_last_msg    (last_msg_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Typing Status (TTL-based: expired after 5 seconds via updated_at check)
CREATE TABLE IF NOT EXISTS chat_typing_status (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id      INT NOT NULL,
  username     VARCHAR(100) NOT NULL,
  display_name VARCHAR(100) DEFAULT '',
  is_admin     TINYINT(1) NOT NULL DEFAULT 0,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_room_user (room_id, username),
  KEY idx_room_updated (room_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Canned Responses (operator quick-reply templates)
CREATE TABLE IF NOT EXISTS chat_canned_responses (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shortcut   VARCHAR(50) NOT NULL DEFAULT '',
  title      VARCHAR(200) NOT NULL DEFAULT '',
  content    TEXT NOT NULL,
  category   VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
  sort_order INT NOT NULL DEFAULT 50,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order),
  KEY idx_shortcut     (shortcut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CSAT Ratings (customer satisfaction after conversation)
CREATE TABLE IF NOT EXISTS chat_csat_ratings (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id VARCHAR(64) NOT NULL DEFAULT '',
  room_id         INT NOT NULL DEFAULT 1,
  user_name       VARCHAR(100) DEFAULT '',
  rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
  comment         TEXT,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conv    (conversation_id),
  KEY idx_created (created_at),
  KEY idx_rating  (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Rate Limits (per user/IP per minute)
CREATE TABLE IF NOT EXISTS chat_rate_limits (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED DEFAULT NULL,
  ip_address VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (user_id, created_at),
  KEY idx_ip_time   (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Add operator availability columns to admin_users
DROP PROCEDURE IF EXISTS _chat_migrate_admin_cols;
DELIMITER $$
CREATE PROCEDURE _chat_migrate_admin_cols()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'admin_users'
      AND COLUMN_NAME  = 'is_available'
  ) THEN
    ALTER TABLE admin_users
      ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 0,
      ADD COLUMN last_active  DATETIME DEFAULT NULL;
  END IF;
END$$
DELIMITER ;
CALL _chat_migrate_admin_cols();
DROP PROCEDURE IF EXISTS _chat_migrate_admin_cols;

-- 7. Seed default canned responses (INSERT IGNORE = skip if shortcut already exists)
INSERT IGNORE INTO chat_canned_responses (shortcut, title, content, category, sort_order) VALUES
('/สวัสดี',   'ทักทาย',              'สวัสดีครับ ยินดีให้บริการครับ มีอะไรให้ช่วยไหมครับ?', 'ทั่วไป', 10),
('/ขอบคุณ',   'ขอบคุณ',             'ขอบคุณที่ติดต่อเทศบาลนครรังสิตนะครับ หากมีข้อสงสัยเพิ่มเติมยินดีให้บริการครับ 🙏', 'ทั่วไป', 20),
('/รอสักครู่', 'รอสักครู่',          'กรุณารอสักครู่นะครับ เจ้าหน้าที่กำลังตรวจสอบข้อมูลให้ครับ ⏳', 'ทั่วไป', 30),
('/ติดต่อ',   'ข้อมูลติดต่อ',        "สามารถติดต่อสอบถามเพิ่มเติมได้ที่\n📞 โทร 0 2567 6000\n🏛️ 151 ถ.รังสิต-ปทุมธานี ต.ประชาธิปัตย์\nวันจันทร์–ศุกร์ เวลา 08:30–16:30 น. ครับ", 'ข้อมูล', 40),
('/ปิดทำการ', 'นอกเวลาราชการ',      'ขณะนี้อยู่นอกเวลาทำการครับ (จ–ศ 08:30–16:30 น.) ท่านสามารถฝากข้อความไว้ได้ เจ้าหน้าที่จะตอบกลับในเวลาทำการครับ', 'แจ้งเตือน', 50),
('/แก้ไข',    'ขออภัย',             'ต้องขออภัยในความไม่สะดวกครับ เจ้าหน้าที่จะดำเนินการแก้ไขให้โดยเร็วที่สุดครับ 🙏', 'ทั่วไป', 60),
('/เสร็จสิ้น', 'จบการสนทนา',         'ขอบคุณที่ใช้บริการเทศบาลนครรังสิตครับ หากมีข้อสงสัยเพิ่มเติมสามารถติดต่อกลับมาได้ตลอดเวลาครับ 😊', 'ทั่วไป', 70);
