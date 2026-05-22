-- =============================================
-- เพิ่มตาราง Bot — รัน SQL นี้เพิ่มต่อจาก chat_db.sql
-- =============================================

USE `chat_app`;

-- ตาราง Pattern Rules (keyword → ตอบกลับ)
CREATE TABLE IF NOT EXISTS `chat_bot_patterns` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `pattern`     VARCHAR(255) NOT NULL COMMENT 'keyword หรือ regex pattern',
  `match_type`  ENUM('keyword','contains','starts','ends','regex') DEFAULT 'contains'
                COMMENT 'วิธี match: keyword=ตรงทั้งหมด, contains=มีในข้อความ, regex=regexp',
  `response`    TEXT NOT NULL COMMENT 'ข้อความตอบกลับ (รองรับ {name} = ชื่อผู้ใช้)',
  `room_id`     INT DEFAULT NULL COMMENT 'NULL = ทุกห้อง, ใส่ id = เฉพาะห้องนั้น',
  `priority`    INT DEFAULT 0 COMMENT 'เรียงลำดับ: สูงสุดทำงานก่อน',
  `is_active`   TINYINT(1) DEFAULT 1,
  `use_ai`      TINYINT(1) DEFAULT 0 COMMENT '1 = ส่งให้ AI ตอบแทน response นี้',
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_priority` (`priority` DESC, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง Bot Log (บันทึกการตอบ)
CREATE TABLE IF NOT EXISTS `chat_bot_log` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `room_id`     INT NOT NULL,
  `trigger_msg` TEXT COMMENT 'ข้อความที่กระตุ้นให้ bot ตอบ',
  `user_name`   VARCHAR(100),
  `bot_response` TEXT,
  `response_type` ENUM('pattern','ai','fallback') DEFAULT 'pattern',
  `pattern_id`  INT DEFAULT NULL,
  `latency_ms`  INT DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง Bot Config
CREATE TABLE IF NOT EXISTS `chat_bot_config` (
  `key_name`  VARCHAR(100) PRIMARY KEY,
  `value`     TEXT,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ตัวอย่าง Pattern เริ่มต้น ───────────────────────────────
INSERT INTO `chat_bot_patterns`
  (`pattern`, `match_type`, `response`, `priority`, `is_active`) VALUES

-- ทักทาย
('สวัสดี|hello|hi|หวัดดี', 'regex',
 'สวัสดีครับ {name}! 👋 มีอะไรให้ช่วยไหมครับ?', 100, 1),

('ขอบคุณ|thank|ขอบใจ', 'regex',
 'ยินดีครับ {name}! 😊', 90, 1),

-- ถามเรื่อง Bot
('คุณเป็นใคร|bot คือ|ai คือ|คุณคือ', 'regex',
 'ผมคือ ChatBot ครับ! 🤖 ทำงานด้วย Pattern Matching + AI ช่วยตอบคำถามเบื้องต้นครับ', 80, 1),

-- ถามราคา/สินค้า
('ราคา|price|เท่าไร|เท่าไหร่|กี่บาท', 'regex',
 '💰 สอบถามราคาสินค้าเพิ่มเติม กรุณาติดต่อ LINE: @yourbrand หรือโทร 02-xxx-xxxx นะครับ', 70, 1),

-- เวลาทำการ
('เปิดกี่โมง|เวลาทำการ|open|ปิด|หยุด', 'regex',
 '🕐 เวลาทำการ: จันทร์–ศุกร์ 09:00–18:00 น.\nเสาร์ 09:00–13:00 น.\nอาทิตย์ หยุดทำการครับ', 70, 1),

-- ที่ตั้ง
('ที่ตั้ง|ที่อยู่|address|อยู่ที่ไหน|map|แผนที่', 'regex',
 '📍 ที่ตั้ง: 123 ถนนสุขุมวิท กรุงเทพฯ 10110\nGoogle Maps: https://maps.google.com', 60, 1),

-- ช่องทางติดต่อ
('ติดต่อ|contact|โทร|tel|line|email|อีเมล', 'regex',
 '📞 ช่องทางติดต่อ:\n• LINE: @yourbrand\n• Tel: 02-xxx-xxxx\n• Email: info@yourdomain.com', 60, 1),

-- ไม่รู้จัก → ส่งให้ AI (use_ai = 1)
('.*', 'regex',
 '', 0, 1);

-- อัปเดต row สุดท้ายให้ use_ai = 1 (Fallback to AI)
UPDATE `chat_bot_patterns` SET `use_ai` = 1 WHERE `pattern` = '.*' AND `priority` = 0;

-- ─── Bot Config เริ่มต้น ──────────────────────────────────────
INSERT INTO `chat_bot_config` (`key_name`, `value`) VALUES
('bot_name',        'ChatBot 🤖'),
('bot_color',       '#7C6AF7'),
('bot_enabled',     '1'),
('ai_enabled',      '1'),
('ai_provider',     'claude'),          -- claude / openai
('claude_api_key',  'YOUR_API_KEY_HERE'),
('claude_model',    'claude-sonnet-4-20250514'),
('ai_system_prompt', 'คุณคือ ChatBot ผู้ช่วยของเว็บไซต์ ตอบคำถามภาษาไทยอย่างสุภาพ กระชับ ได้ใจความ ไม่เกิน 3 ประโยค ถ้าไม่แน่ใจให้แนะนำให้ติดต่อเจ้าหน้าที่'),
('openai_api_key',  ''),
('reply_delay_ms',  '800');             -- หน่วงเวลาก่อนตอบ (ms)
