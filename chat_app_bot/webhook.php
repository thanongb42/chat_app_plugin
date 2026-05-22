<?php
// webhook.php — รับ Webhook จาก external apps (n8n, Make, Zapier, Line Bot)
// และส่งออก event ไปยัง external systems
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/chat_config.php';
require_once __DIR__ . '/notification_engine.php';

$pdo     = getChatDB();
$notif   = new NotificationEngine($pdo);
$secret  = $notif->get('webhook_secret', '');

// ── Verify Signature ─────────────────────────────────────────────
$body = file_get_contents('php://input');
if ($secret) {
    $sig      = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
    if (!hash_equals($expected, $sig)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
        exit;
    }
}

$data   = json_decode($body, true) ?? [];
$action = $data['action'] ?? $_GET['action'] ?? '';

function ok(mixed $d = null): void {
    echo json_encode(['ok' => true, 'data' => $d], JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $m, int $c = 400): void {
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

// ── API Key Auth (สำหรับ GET requests จาก external) ───────────────
$apiKey    = $notif->get('webhook_api_key', '');
$reqApiKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey && $action !== '' && $reqApiKey !== $apiKey) {
    // ยกเว้น inbound webhook ที่ verify signature แล้ว
    if ($_SERVER['REQUEST_METHOD'] === 'GET') fail('Unauthorized', 401);
}

switch ($action) {

    // ── รับข้อมูลจาก Line Bot (Messaging API webhook) ─────────────
    case 'line_bot':
        $events = $data['events'] ?? [];
        foreach ($events as $event) {
            if ($event['type'] !== 'message' || $event['message']['type'] !== 'text') continue;
            $text   = $event['message']['text'] ?? '';
            $userId = $event['source']['userId'] ?? 'line_user';

            // บันทึกข้อความเข้า room 1 (ห้องทั่วไป)
            $pdo->prepare("
                INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
                VALUES (1, ?, 'Line User', '#06c755', ?, 'text')
            ")->execute(['line_' . substr($userId, 0, 8), $text]);
        }
        ok(['events_processed' => count($events)]);

    // ── รับข้อมูลจาก Facebook Messenger ──────────────────────────
    // (Verify token สำหรับ FB webhook setup)
    case 'facebook':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $verifyToken = $notif->get('fb_verify_token', 'rangsitcity_verify');
            if (($_GET['hub_verify_token'] ?? '') === $verifyToken) {
                echo $_GET['hub_challenge'] ?? '';
                exit;
            }
            fail('Invalid verify token', 403);
        }
        $entries = $data['entry'] ?? [];
        foreach ($entries as $entry) {
            foreach ($entry['messaging'] ?? [] as $msg) {
                $text = $msg['message']['text'] ?? '';
                if (!$text) continue;
                $sender = $msg['sender']['id'] ?? 'fb_user';
                $pdo->prepare("
                    INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
                    VALUES (1, ?, 'Facebook User', '#1565C0', ?, 'text')
                ")->execute(['fb_' . substr($sender, 0, 8), $text]);
            }
        }
        ok();

    // ── ส่งข้อความลงห้อง (จาก n8n/Make/Zapier) ───────────────────
    case 'send_message':
        $roomId  = (int)($data['room_id'] ?? 1);
        $message = trim($data['message'] ?? '');
        $sender  = $data['sender_name'] ?? 'External System';
        if (!$message) fail('message required');

        $pdo->prepare("
            INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
            VALUES (?, 'webhook_bot', ?, '#8b5cf6', ?, 'text')
        ")->execute([$roomId, $sender, $message]);
        ok(['inserted' => true]);

    // ── ดึง stats (สำหรับ dashboard ภายนอก) ──────────────────────
    case 'get_stats':
        ok([
            'messages_today'  => (int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at)=CURDATE() AND msg_type='text'")->fetchColumn(),
            'bot_responses'   => (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
            'fallback_today'  => (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE() AND response_type='fallback'")->fetchColumn(),
            'online_users'    => (int)$pdo->query("SELECT COUNT(*) FROM chat_users WHERE is_online=1 AND last_seen>=DATE_SUB(NOW(),INTERVAL 30 SECOND)")->fetchColumn(),
            'is_office_hours' => $notif->isOfficeHours(),
            'generated_at'    => date('c'),
        ]);

    // ── เพิ่ม pattern ใหม่ (จาก automation) ──────────────────────
    case 'add_pattern':
        $pattern  = trim($data['pattern'] ?? '');
        $response = trim($data['response'] ?? '');
        if (!$pattern || !$response) fail('pattern and response required');

        $pdo->prepare("
            INSERT INTO chat_bot_patterns (pattern, match_type, response, priority, is_active, use_ai)
            VALUES (?, ?, ?, ?, 1, 0)
        ")->execute([
            $pattern,
            $data['match_type'] ?? 'regex',
            $response,
            (int)($data['priority'] ?? 50),
        ]);
        ok(['id' => $pdo->lastInsertId()]);

    // ── Ping / Health check ───────────────────────────────────────
    case 'ping':
    case 'health':
        ok([
            'status'       => 'ok',
            'time'         => date('c'),
            'office_hours' => $notif->isOfficeHours(),
            'db'           => 'connected',
        ]);

    // ── Test notification (ทดสอบส่ง Line) ────────────────────────
    case 'test_notify':
        $notif->onBotFallback(
            'ทดสอบระบบแจ้งเตือน',
            'Admin Test',
            1,
            'ห้องทั่วไป'
        );
        ok(['message' => 'Notification triggered — ตรวจสอบ Line / Webhook']);

    default:
        // Inbound webhook ทั่วไป — log แล้วตอบ OK
        if ($body) {
            error_log('[Webhook] Unknown action: ' . $action . ' | Body: ' . substr($body, 0, 200));
        }
        ok(['received' => true, 'action' => $action ?: 'none']);
}
