-- ============================================================
-- Org Info: config keys + patterns สำหรับ rich menu ข้อมูลเทศบาล
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_org_info.sql
-- ============================================================

-- 1. เพิ่ม config keys (ค่าว่าง ให้ admin กรอกใน Config tab)
INSERT INTO chat_bot_config (key_name, value) VALUES
  ('org_name',          ''),
  ('org_address',       ''),
  ('org_tel',           ''),
  ('org_emergency_tel', ''),
  ('org_website',       ''),
  ('org_line',          ''),
  ('org_facebook',      ''),
  ('org_lat',           ''),
  ('org_lng',           '')
ON DUPLICATE KEY UPDATE key_name = key_name;

-- 2. เพิ่ม menu item ข้อมูลเทศบาล
INSERT INTO chat_menu_items (icon, label, message_text, sort_order, is_active)
VALUES ('🏛️', 'ข้อมูลเทศบาล', 'org_info', 5, 1)
ON DUPLICATE KEY UPDATE label = label;

-- 3. Pattern หลัก: ข้อมูลเทศบาล → response + 6 choices
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_info',
  'keyword',
  'ต้องการทราบข้อมูลด้านใดครับ? 🏛️',
  '[{"label":"📞 เบอร์โทร","message":"org_tel"},{"label":"📍 ที่อยู่","message":"org_address"},{"label":"🌐 เว็บไซต์","message":"org_website"},{"label":"💬 Line OA","message":"org_line"},{"label":"📘 Facebook","message":"org_facebook"},{"label":"🗺️ แผนที่/นำทาง","message":"org_map"}]',
  75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), choices = VALUES(choices), is_active = 1;

-- 4. Pattern เบอร์โทร
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_tel',
  'keyword',
  '📞 เบอร์โทรหลัก: {org_tel}\n🆘 ฉุกเฉิน (24 ชม.): {org_emergency_tel}',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;

-- 5. Pattern ที่อยู่
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_address',
  'keyword',
  '📍 {org_name}\n{org_address}',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;

-- 6. Pattern เว็บไซต์ (rich card + QR)
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_website',
  'keyword',
  '<div class="rich-card"><div class="rich-section" style="border-left:3px solid #1565C0"><div class="rich-section-title">🌐 เว็บไซต์ทางการ</div><div style="display:flex;gap:10px;align-items:flex-start;margin-top:6px"><div style="flex:1"><a href="{org_website}" target="_blank">{org_website}</a></div><img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={org_website_encoded}&margin=2" alt="QR" style="width:80px;height:80px;border-radius:4px;flex-shrink:0;background:#fff;padding:2px"></div></div></div>',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;

-- 7. Pattern Line OA
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_line',
  'keyword',
  '💬 Line OA: {org_line}',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;

-- 8. Pattern Facebook
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_facebook',
  'keyword',
  '📘 Facebook: {org_facebook}',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;

-- 9. Pattern แผนที่ (rich card)
INSERT INTO chat_bot_patterns (pattern, match_type, response, choices, priority, is_active, use_ai)
VALUES (
  'org_map',
  'keyword',
  '<div class="rich-card"><div style="font-size:12px;font-weight:700;margin-bottom:10px;color:#42A5F5">📍 {org_name}</div><div class="rich-section" style="border-left:3px solid #2ea043"><div class="rich-meta">{org_address}</div><a href="https://www.google.com/maps/dir/?api=1&destination={org_lat},{org_lng}&travelmode=driving" target="_blank" class="rich-nav-btn">🗺️ นำทาง Google Maps</a></div></div>',
  NULL, 75, 1, 0
)
ON DUPLICATE KEY UPDATE response = VALUES(response), is_active = 1;
