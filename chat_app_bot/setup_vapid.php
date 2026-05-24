<?php
// setup_vapid.php — รันครั้งเดียวเพื่อ generate VAPID keys และเก็บใน DB
// เรียกผ่าน browser: http://localhost/chat_app/chat_app_bot/setup_vapid.php
// *** ลบหรือ protect ไฟล์นี้หลังใช้งาน ***

require_once __DIR__ . '/chat_config.php';
require_once __DIR__ . '/web_push.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = getChatDB();

// ตรวจสอบว่ามี key อยู่แล้วไหม
$existing = $pdo->query("SELECT value FROM chat_bot_config WHERE key_name='vapid_public_key'")->fetchColumn();
if ($existing) {
    echo "VAPID keys already exist.\n";
    echo "Public Key: " . $existing . "\n";
    echo "ถ้าต้องการ regenerate ให้ลบ key_name='vapid_public_key' จาก chat_bot_config ก่อน\n";
    exit;
}

// Generate
$keys = webpush_generate_vapid();

// บันทึกลง DB
$upsert = $pdo->prepare("INSERT INTO chat_bot_config (key_name, value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
$upsert->execute(['vapid_public_key',  $keys['public']]);
$upsert->execute(['vapid_private_pem', $keys['private_pem']]);
$upsert->execute(['vapid_subject',     'mailto:admin@chatbot.local']);

echo "✅ VAPID keys generated and saved.\n";
echo "Public Key (ใส่ใน applicationServerKey): " . $keys['public'] . "\n";
echo "\n*** กรุณาลบหรือ block ไฟล์นี้หลังใช้งาน ***\n";
