-- ============================================================
-- Pattern: ร้องเรียน-ร้องทุกข์ (Rich HTML response)
-- Run: mysql -u root --default-character-set=utf8mb4 chat_app < add_complaint_pattern.sql
-- ============================================================

INSERT INTO chat_bot_patterns (pattern, match_type, response, priority, is_active, use_ai)
VALUES (
  'ร้องเรียน|ร้องทุกข์|แจ้งเรื่อง|แจ้งร้องเรียน|ขอร้องเรียน',
  'regex',
  '<div class="rich-card"><div style="font-size:12px;font-weight:700;margin-bottom:10px;color:#42A5F5">📋 ช่องทางร้องเรียน-ร้องทุกข์<br><span style="font-size:10px;font-weight:400;color:#8b949e">เทศบาลนครรังสิต</span></div><div class="rich-section" style="border-left:3px solid #2ea043"><div class="rich-section-title">🏛️ ยื่นเรื่องด้วยตนเอง</div><div class="rich-meta">ศูนย์ดำรงธรรม ชั้น 1 สำนักงานเทศบาล<br>151 ถ.รังสิต-ปทุมธานี ต.ประชาธิปัตย์</div><a href="https://www.google.com/maps/dir/?api=1&destination=14.04768,100.74567&travelmode=driving" target="_blank" class="rich-nav-btn">🗺️ นำทาง Google Maps</a></div><div class="rich-section" style="border-left:3px solid #1565C0"><div class="rich-section-title">🌐 ร้องเรียนออนไลน์</div><div style="display:flex;gap:10px;align-items:flex-start;margin-top:4px"><div style="flex:1"><a href="https://www.rangsitcity.go.th" target="_blank">www.rangsitcity.go.th</a><div class="rich-meta">สแกน QR Code เพื่อเข้าเว็บไซต์</div></div><img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=https://www.rangsitcity.go.th&margin=2" alt="QR" style="width:90px;height:90px;border-radius:6px;flex-shrink:0;background:#fff;padding:2px"></div></div><div class="rich-section" style="border-left:3px solid #d29922"><div class="rich-section-title">📞 โทรศัพท์</div><div>0 2567 6000 ต่อ 101</div><div class="rich-meta">กองรับเรื่องร้องทุกข์ • จ–ศ 08:30–16:30 น.</div></div></div>',
  80,
  1,
  0
)
ON DUPLICATE KEY UPDATE
  response = VALUES(response),
  is_active = 1;
