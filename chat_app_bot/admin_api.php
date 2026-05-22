<?php
// admin_api.php — Admin-only REST API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/chat_config.php';
require_once __DIR__ . '/notification_engine.php';

session_name('rungsit_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
session_start();

define('ADMIN_SK', 'is_admin');

function ok(mixed $data = null): void {
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function auth(): void {
    if (empty($_SESSION[ADMIN_SK])) fail('Unauthorized', 401);
    // Session timeout 8 hours
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
        session_destroy();
        fail('Session หมดอายุ กรุณา Login ใหม่', 401);
    }
}

$action = $_REQUEST['action'] ?? '';

// ══════════════════════════════════════════════════
// AUTH — ไม่ต้อง login ก่อน
// ══════════════════════════════════════════════════
if ($action === 'login') {
    // CSRF validation
    $csrf        = $_POST['csrf_token'] ?? '';
    $sessionCsrf = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrf)) {
        fail('คำขอไม่ถูกต้อง', 403);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        $pdo = getChatDB();
    } catch (Throwable) {
        fail('ไม่สามารถเชื่อมต่อฐานข้อมูลได้', 500);
    }

    // Rate limiting: max 5 failed attempts per IP per 15 minutes
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM admin_login_attempts
         WHERE ip_address=? AND success=0
         AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$ip]);
    $failCount = (int)$stmt->fetchColumn();

    if ($failCount >= 5) {
        $stmt2 = $pdo->prepare(
            "SELECT attempted_at FROM admin_login_attempts
             WHERE ip_address=? AND success=0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
             ORDER BY attempted_at ASC LIMIT 1"
        );
        $stmt2->execute([$ip]);
        $firstFail = $stmt2->fetchColumn();
        $waitSecs  = max(0, (strtotime($firstFail) + 900) - time());
        $waitMins  = (int)ceil($waitSecs / 60);
        fail("บัญชีถูกล็อกชั่วคราว กรุณารอ {$waitMins} นาที", 429);
    }

    $pass   = $_POST['password'] ?? '';
    $stored = $pdo->query("SELECT value FROM chat_bot_config WHERE key_name='admin_password'")->fetchColumn()
              ?: 'admin1234';

    // Verify: bcrypt or plaintext legacy (auto-migrate to bcrypt on first successful login)
    if (str_starts_with($stored, '$2y$')) {
        $isValid = password_verify($pass, $stored);
    } else {
        $isValid = hash_equals($stored, $pass);
        if ($isValid) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES ('admin_password',?) ON DUPLICATE KEY UPDATE value=?")
                ->execute([$hash, $hash]);
        }
    }

    // Log attempt
    $pdo->prepare("INSERT INTO admin_login_attempts (ip_address, success) VALUES (?,?)")
        ->execute([$ip, $isValid ? 1 : 0]);

    // Clean up old records (> 24h) occasionally
    $pdo->exec("DELETE FROM admin_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

    if (!$isValid) {
        $newFails  = $failCount + 1;
        $remaining = max(0, 5 - $newFails);
        $msg = $remaining > 0
            ? "รหัสผ่านไม่ถูกต้อง เหลืออีก {$remaining} ครั้งก่อนถูกล็อก"
            : 'รหัสผ่านไม่ถูกต้อง บัญชีถูกล็อก 15 นาที';
        fail($msg, 401);
    }

    // Successful login
    session_regenerate_id(true);
    $_SESSION[ADMIN_SK]     = true;
    $_SESSION['admin_name'] = $pdo->query("SELECT value FROM chat_bot_config WHERE key_name='admin_name'")->fetchColumn()
                              ?: 'เจ้าหน้าที่';
    $_SESSION['login_ip']   = $ip;
    $_SESSION['login_time'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate token after login

    ok(['name' => $_SESSION['admin_name']]);
}

if ($action === 'logout') { session_destroy(); ok(); }

if ($action === 'check') {
    ok(['logged_in' => !empty($_SESSION[ADMIN_SK]), 'name' => $_SESSION['admin_name'] ?? '']);
}

// ══════════════════════════════════════════════════
// ต้อง Login ก่อนทุก action ด้านล่าง
// ══════════════════════════════════════════════════
auth();
$pdo = getChatDB();

switch ($action) {

// ── DASHBOARD STATS ────────────────────────────────
case 'stats':
    ok([
        'msg_today'      => (int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at)=CURDATE() AND msg_type='text' AND username NOT IN ('chatbot','admin_staff','system')")->fetchColumn(),
        'bot_today'      => (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE() AND response_type='pattern'")->fetchColumn(),
        'ai_today'       => (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE() AND response_type='ai'")->fetchColumn(),
        'fallback_today' => (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE() AND response_type='fallback' AND is_resolved=0")->fetchColumn(),
        'online'         => (int)$pdo->query("SELECT COUNT(*) FROM chat_users WHERE is_online=1 AND last_seen>=DATE_SUB(NOW(),INTERVAL 30 SECOND)")->fetchColumn(),
        'patterns_active'=> (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_patterns WHERE is_active=1")->fetchColumn(),
        'unanswered_total'=> (int)$pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE response_type='fallback'")->fetchColumn(),
        'msg_total'      => (int)$pdo->query("SELECT COUNT(*) FROM chat_messages WHERE msg_type='text' AND username NOT IN ('chatbot','admin_staff','system')")->fetchColumn(),
    ]);

// ── INBOX (rooms + ข้อมูลล่าสุด) ─────────────────
case 'inbox':
    $lastSeen = $_SESSION['last_seen'] ?? [];
    $stmt = $pdo->query("
        SELECT r.id, r.name,
          (SELECT COUNT(*) FROM chat_messages m
           WHERE m.room_id=r.id AND m.msg_type='text'
             AND m.username NOT IN ('chatbot','admin_staff','system')
             AND DATE(m.created_at)=CURDATE()) AS user_msg_today,
          (SELECT m2.message   FROM chat_messages m2 WHERE m2.room_id=r.id ORDER BY m2.id DESC LIMIT 1) AS last_msg,
          (SELECT m3.display_name FROM chat_messages m3 WHERE m3.room_id=r.id ORDER BY m3.id DESC LIMIT 1) AS last_sender,
          (SELECT m4.username  FROM chat_messages m4 WHERE m4.room_id=r.id ORDER BY m4.id DESC LIMIT 1) AS last_username,
          (SELECT DATE_FORMAT(m5.created_at,'%H:%i') FROM chat_messages m5 WHERE m5.room_id=r.id ORDER BY m5.id DESC LIMIT 1) AS last_time,
          (SELECT MAX(m6.id)   FROM chat_messages m6 WHERE m6.room_id=r.id) AS max_id
        FROM chat_rooms r ORDER BY max_id DESC
    ");
    $rooms = $stmt->fetchAll();
    foreach ($rooms as &$room) {
        $seenId = (int)($lastSeen[$room['id']] ?? 0);
        $unread = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE room_id=? AND id>? AND username NOT IN ('chatbot','admin_staff','system')");
        $unread->execute([$room['id'], $seenId]);
        $room['unread'] = (int)$unread->fetchColumn();
    }
    ok($rooms);

// ── ROOM MESSAGES (admin chat view) ───────────────
case 'room_messages':
    $roomId = (int)($_GET['room_id'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);
    if (!$roomId) fail('room_id required');

    if ($lastId === 0) {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar_color, message, msg_type,
                   DATE_FORMAT(created_at,'%H:%i') AS time_str
            FROM chat_messages WHERE room_id=?
            ORDER BY created_at DESC LIMIT 80
        ");
        $stmt->execute([$roomId]);
        $msgs = array_reverse($stmt->fetchAll());
    } else {
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar_color, message, msg_type,
                   DATE_FORMAT(created_at,'%H:%i') AS time_str
            FROM chat_messages WHERE room_id=? AND id>?
            ORDER BY id ASC LIMIT 50
        ");
        $stmt->execute([$roomId, $lastId]);
        $msgs = $stmt->fetchAll();
    }
    if (!empty($msgs)) {
        $_SESSION['last_seen'][$roomId] = max(array_column($msgs, 'id'));
    }
    ok($msgs);

// ── SEND AS ADMIN ─────────────────────────────────
case 'send_admin':
    $roomId  = (int)($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if (!$roomId || !$message) fail('room_id and message required');
    $name = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $pdo->prepare("
        INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
        VALUES (?, 'admin_staff', ?, '#C62828', ?, 'text')
    ")->execute([$roomId, $name, $message]);
    ok(['id' => $pdo->lastInsertId()]);

// ── PATTERNS LIST ──────────────────────────────────
case 'patterns':
    $filter = $_GET['filter'] ?? 'all';
    $where  = match($filter) {
        'active'   => 'WHERE is_active=1',
        'inactive' => 'WHERE is_active=0',
        'ai'       => 'WHERE use_ai=1',
        default    => '',
    };
    $search = trim($_GET['q'] ?? '');
    if ($search) {
        $s = $pdo->quote('%' . $search . '%');
        $where = $where ? "$where AND (pattern LIKE $s OR response LIKE $s)"
                        : "WHERE (pattern LIKE $s OR response LIKE $s)";
    }
    ok($pdo->query("SELECT * FROM chat_bot_patterns $where ORDER BY priority DESC, id")->fetchAll());

// ── PATTERN SAVE (add / edit) ─────────────────────
case 'pattern_save':
    $id        = (int)($_POST['id'] ?? 0);
    $pattern   = trim($_POST['pattern'] ?? '');
    $matchType = $_POST['match_type'] ?? 'regex';
    $response  = $_POST['response'] ?? '';
    $roomId2   = ($_POST['room_id'] ?? '') ?: null;
    $priority  = (int)($_POST['priority'] ?? 50);
    $isActive  = (int)($_POST['is_active'] ?? 1);
    $useAi     = (int)($_POST['use_ai'] ?? 0);
    if (!$pattern) fail('กรุณาระบุ Pattern');
    if ($id > 0) {
        $pdo->prepare("UPDATE chat_bot_patterns SET pattern=?,match_type=?,response=?,room_id=?,priority=?,is_active=?,use_ai=? WHERE id=?")
            ->execute([$pattern,$matchType,$response,$roomId2,$priority,$isActive,$useAi,$id]);
    } else {
        $pdo->prepare("INSERT INTO chat_bot_patterns (pattern,match_type,response,room_id,priority,is_active,use_ai) VALUES (?,?,?,?,?,?,?)")
            ->execute([$pattern,$matchType,$response,$roomId2,$priority,$isActive,$useAi]);
        $id = (int)$pdo->lastInsertId();
    }
    ok(['id' => $id]);

// ── PATTERN DELETE ────────────────────────────────
case 'pattern_delete':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) fail('id required');
    $pdo->prepare("DELETE FROM chat_bot_patterns WHERE id=?")->execute([$id]);
    ok();

// ── PATTERN TOGGLE ────────────────────────────────
case 'pattern_toggle':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) fail('id required');
    $pdo->prepare("UPDATE chat_bot_patterns SET is_active=!is_active WHERE id=?")->execute([$id]);
    $a = (int)$pdo->query("SELECT is_active FROM chat_bot_patterns WHERE id=$id")->fetchColumn();
    ok(['active' => $a]);

// ── UNANSWERED (bot ตอบไม่ได้ / fallback) ─────────
case 'unanswered':
    $typeFilter   = $_GET['type']          ?? 'fallback';
    $showResolved = (int)($_GET['show_resolved'] ?? 0);
    $inClause = match($typeFilter) {
        'ai'  => "('ai')",
        'all' => "('ai','fallback')",
        default => "('fallback')",
    };
    $resolvedCond = $showResolved ? '' : 'AND l.is_resolved=0';
    ok($pdo->query("
        SELECT l.id, l.room_id, l.trigger_msg, l.user_name,
               l.bot_response, l.response_type, l.latency_ms,
               l.is_resolved, l.resolved_by,
               DATE_FORMAT(l.created_at,'%d/%m %H:%i')    AS time_str,
               DATE_FORMAT(l.resolved_at,'%d/%m %H:%i')   AS resolved_time,
               r.name AS room_name
        FROM chat_bot_log l
        LEFT JOIN chat_rooms r ON r.id=l.room_id
        WHERE l.response_type IN $inClause $resolvedCond
        ORDER BY l.is_resolved ASC, l.created_at DESC LIMIT 100
    ")->fetchAll());

// ── BOT LOG (all) ─────────────────────────────────
case 'bot_log':
    $lim = min((int)($_GET['limit'] ?? 50), 200);
    ok($pdo->query("
        SELECT l.*, DATE_FORMAT(l.created_at,'%d/%m %H:%i') AS time_str, r.name AS room_name
        FROM chat_bot_log l LEFT JOIN chat_rooms r ON r.id=l.room_id
        ORDER BY l.created_at DESC LIMIT $lim
    ")->fetchAll());

case 'clear_log':
    $pdo->exec("TRUNCATE TABLE chat_bot_log");
    ok();

// ── ROOMS LIST ────────────────────────────────────
case 'rooms':
    ok($pdo->query("SELECT id, name FROM chat_rooms ORDER BY id")->fetchAll());

// ── BOT CONFIG ────────────────────────────────────
case 'config':
    ok($pdo->query("SELECT key_name, value FROM chat_bot_config")->fetchAll(PDO::FETCH_KEY_PAIR));

case 'config_save':
    $keys = ['bot_name','bot_color','bot_enabled','ai_enabled','ai_provider',
             'claude_api_key','claude_model','openai_api_key','ai_system_prompt',
             'reply_delay_ms'];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        $v = $_POST[$k];
        $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?")
            ->execute([$k,$v,$v]);
    }
    // admin_name saved to config and session
    if (isset($_POST['admin_name']) && trim($_POST['admin_name']) !== '') {
        $adminName = trim($_POST['admin_name']);
        $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES ('admin_name',?) ON DUPLICATE KEY UPDATE value=?")
            ->execute([$adminName, $adminName]);
        $_SESSION['admin_name'] = $adminName;
    }
    // admin_password: always hash with bcrypt (never store plaintext)
    if (!empty($_POST['admin_password'])) {
        $newHash = password_hash($_POST['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES ('admin_password',?) ON DUPLICATE KEY UPDATE value=?")
            ->execute([$newHash, $newHash]);
    }
    ok();

// ── NOTIFICATION STATUS ──────────────────────────
case 'notify_status':
    $notif = new NotificationEngine($pdo);
    ok($notif->getStatus());

// ── NOTIFICATION CONFIG SAVE ─────────────────────
case 'notify_save':
    $keys = [
        'line_enabled','line_notify_token',
        'webhook_enabled','webhook_url','webhook_secret','webhook_api_key',
        'notify_email_enabled','notify_email',
        'notify_on_fallback','notify_on_offhours',
        'notify_unanswered_min','notify_cooldown_min',
        'office_start','office_end','office_days',
        'fb_verify_token',
    ];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $_POST)) continue;
        $v = $_POST[$k];
        $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?")
            ->execute([$k,$v,$v]);
    }
    ok();

// ── SEND TEST NOTIFICATION ────────────────────────
case 'notify_test':
    $channel = $_POST['channel'] ?? 'line';
    $notif   = new NotificationEngine($pdo);
    $notif->onBotFallback(
        'ทดสอบระบบแจ้งเตือน — ' . date('H:i:s'),
        'Admin Test',
        1,
        'ห้องทั่วไป'
    );
    ok(['message' => 'ส่งการแจ้งเตือนทดสอบแล้ว ตรวจสอบ Line / Webhook ครับ']);

// ── NOTIFICATION LOG ──────────────────────────────
case 'notify_log':
    $lim = min((int)($_GET['limit'] ?? 50), 200);
    ok($pdo->query("
        SELECT id, trigger_type, channel, room_id, user_name,
               LEFT(trigger_msg,80) AS trigger_msg,
               LEFT(sent_msg,100)   AS sent_msg,
               status,
               DATE_FORMAT(created_at,'%d/%m %H:%i') AS time_str
        FROM chat_notifications
        ORDER BY created_at DESC LIMIT $lim
    ")->fetchAll());

// ── RESOLVE LOG ENTRY ────────────────────────────
case 'resolve_log':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) fail('id required');
    $resolvedBy = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $pdo->prepare("UPDATE chat_bot_log SET is_resolved=1, resolved_at=NOW(), resolved_by=? WHERE id=?")
        ->execute([$resolvedBy, $id]);
    $remaining = (int)$pdo->query(
        "SELECT COUNT(*) FROM chat_bot_log WHERE DATE(created_at)=CURDATE() AND response_type='fallback' AND is_resolved=0"
    )->fetchColumn();
    ok(['remaining' => $remaining]);

// ── RUN CRON MANUALLY ────────────────────────────
case 'run_cron':
    $notif = new NotificationEngine($pdo);
    $count = $notif->checkUnanswered();
    ok(['unanswered_notified' => $count]);

default:
    fail('Unknown action', 404);
}
