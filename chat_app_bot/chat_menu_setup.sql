-- chat_menu_setup.sql
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < chat_menu_setup.sql

CREATE TABLE IF NOT EXISTS `chat_menu_items` (
  `id`           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  `icon`         VARCHAR(10)   NOT NULL DEFAULT '📋',
  `label`        VARCHAR(50)   NOT NULL,
  `message_text` VARCHAR(200)  NOT NULL,
  `sort_order`   SMALLINT      NOT NULL DEFAULT 50,
  `is_active`    TINYINT(1)    NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chat_menu_items` (`icon`, `label`, `message_text`, `sort_order`) VALUES
('🏢', 'ข้อมูลเทศบาล',      'ข้อมูลเทศบาลนครรังสิต',         10),
('📋', 'งานทะเบียนราษฎร์', 'งานทะเบียนราษฎร์',              20),
('💰', 'ชำระภาษี',           'ชำระภาษีที่ดินและสิ่งปลูกสร้าง', 30),
('🗑️', 'จัดเก็บขยะ',         'การจัดเก็บขยะมูลฝอย',           40),
('🏥', 'สาธารณสุข',          'บริการสาธารณสุขเทศบาล',          50),
('📢', 'ข่าวประกาศ',         'ข่าวประกาศเทศบาล',               60),
('🔧', 'แจ้งซ่อม',           'แจ้งซ่อมบำรุงสาธารณูปโภค',       70),
('📞', 'ติดต่อเทศบาล',       'ช่องทางติดต่อเทศบาลนครรังสิต',   80),
('🎓', 'ทุนการศึกษา',        'ทุนการศึกษาเทศบาล',              90);
