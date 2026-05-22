-- update_facebook.sql — อัปเดต Facebook page URL จริง
USE `chat_app`;

-- ─── ID 36: ติดต่อ/โทร/Line/Facebook ────────────────────────────
UPDATE `chat_bot_patterns` SET `response` =
'📞 ช่องทางติดต่อ — เทศบาลนครรังสิต:\n\n☎️ โทรศัพท์: 0 2567 6000\n💬 Line OA: @rangsitcity\n📘 Facebook: https://web.facebook.com/rangsitcity2016\n🌐 www.rangsitcity.go.th\n📧 info@rangsitcity.go.th\n📠 Fax: 0 2567 6000 ต่อ 131\n\n📞 เบอร์ต่อภายใน:\n   • ต่อ 151 — RSSC\n   • ต่อ 111/1307 — ประชาสัมพันธ์\n   • ต่อ 600 — สำนักปลัด\n   • ต่อ 300 — สำนักคลัง\n   • ต่อ 900 — สำนักช่าง\n   • ต่อ 700 — กองสาธารณสุขฯ\n\n🕐 จ.–ศ. 08:30–16:30 น.\n🚨 ฉุกเฉิน 24 ชม.: RCC 0 2567 3388'
WHERE `id` = 36;

-- ─── AI System Prompt ────────────────────────────────────────────
UPDATE `chat_bot_config`
SET `value` = 'คุณคือ RungsitBot ผู้ช่วยอัจฉริยะของเว็บไซต์เทศบาลนครรังสิต ที่อยู่เลขที่ 151 ถนนรังสิต-ปทุมธานี ตำบลประชาธิปัตย์ อำเภอธัญบุรี จังหวัดปทุมธานี 12130 โทร 0 2567 6000 Line OA: @rangsitcity Facebook: https://web.facebook.com/rangsitcity2016 เว็บไซต์ www.rangsitcity.go.th ตอบคำถามเป็นภาษาไทย สุภาพ กระชับ ไม่เกิน 5 ประโยค ถ้าไม่รู้คำตอบให้แนะนำให้โทร 0 2567 6000 หรือ Line OA: @rangsitcity'
WHERE `key_name` = 'ai_system_prompt';
