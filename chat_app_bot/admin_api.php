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
function authSuper(): void {
    auth();
    if (($_SESSION['admin_role'] ?? '') !== 'superadmin') fail('สิทธิ์ไม่เพียงพอ', 403);
}

$action = $_REQUEST['action'] ?? '';

// ══════════════════════════════════════════════════
// PUBLIC — ไม่ต้อง login (stats สำหรับ landing page)
// ══════════════════════════════════════════════════
if ($action === 'public_stats') {
    try {
        $pdo2 = getChatDB();
        $logoVal = $pdo2->query("SELECT value FROM chat_bot_config WHERE key_name='site_logo'")->fetchColumn();
        ok([
            'patterns_active' => (int)$pdo2->query("SELECT COUNT(*) FROM chat_bot_patterns WHERE is_active=1")->fetchColumn(),
            'msg_today'       => (int)$pdo2->query("SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at)=CURDATE() AND msg_type='text' AND username NOT IN ('chatbot','admin_staff','system')")->fetchColumn(),
            'online'          => (int)$pdo2->query("SELECT COUNT(*) FROM chat_users WHERE is_online=1 AND last_seen>=DATE_SUB(NOW(),INTERVAL 30 SECOND)")->fetchColumn(),
            'site_logo'       => $logoVal ?: '',
        ]);
    } catch (Throwable) {
        ok(['patterns_active' => 0, 'msg_today' => 0, 'online' => 0]);
    }
}

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

    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    // ลองหาจาก admin_users ก่อน
    $user = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username=? AND is_active=1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch() ?: null;
    } catch (Throwable) { $user = null; }

    if ($user) {
        $isValid = password_verify($pass, $user['password_hash']);
    } else {
        // Legacy fallback: single-password ใน chat_bot_config
        $stored = $pdo->query("SELECT value FROM chat_bot_config WHERE key_name='admin_password'")->fetchColumn()
                  ?: 'admin1234';
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
    }

    // Log attempt
    $pdo->prepare("INSERT INTO admin_login_attempts (ip_address, success) VALUES (?,?)")
        ->execute([$ip, $isValid ? 1 : 0]);

    // Clean up old records (> 24h) occasionally
    $pdo->exec("DELETE FROM admin_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

    if (!$isValid) {
        $newFails  = $failCount + 1;
        $remaining = max(0, 5 - $newFails);
        if ($remaining <= 0) {
            fail('บัญชีถูกล็อกชั่วคราว กรุณารอ 15 นาที', 429);
        }
        fail("รหัสผ่านไม่ถูกต้อง เหลืออีก {$remaining} ครั้ง", 401);
    }

    // Successful login
    session_regenerate_id(true);
    $_SESSION[ADMIN_SK]          = true;
    $_SESSION['admin_id']        = $user ? (int)$user['id'] : 0;
    $_SESSION['admin_role']      = $user ? $user['role'] : 'superadmin';
    $_SESSION['admin_username']  = $user ? $user['username'] : ($username ?: 'admin');
    $_SESSION['admin_name']      = $user
        ? $user['display_name']
        : ($pdo->query("SELECT value FROM chat_bot_config WHERE key_name='admin_name'")->fetchColumn() ?: 'เจ้าหน้าที่');
    $_SESSION['login_ip']        = $ip;
    $_SESSION['login_time']      = time();
    $_SESSION['csrf_token']      = bin2hex(random_bytes(32));

    if ($user) {
        $pdo->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    }

    ok(['name' => $_SESSION['admin_name'], 'role' => $_SESSION['admin_role']]);
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

// ── INBOX (per-conversation) ───────────────────────
case 'inbox':
    $filter = $_GET['filter'] ?? 'all';
    $stmt = $pdo->query("
        SELECT
            m.conversation_id,
            MAX(m.id) AS max_id,
            (SELECT m2.display_name FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id
               AND m2.username NOT IN ('chatbot','admin_staff','system')
             ORDER BY m2.id ASC LIMIT 1) AS user_name,
            (SELECT m2.avatar_color FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id
               AND m2.username NOT IN ('chatbot','admin_staff','system')
             ORDER BY m2.id ASC LIMIT 1) AS user_color,
            (SELECT m2.message FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id
             ORDER BY m2.id DESC LIMIT 1) AS last_msg,
            (SELECT m2.username FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id
             ORDER BY m2.id DESC LIMIT 1) AS last_username,
            (SELECT DATE_FORMAT(m2.created_at,'%d/%m %H:%i') FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id
             ORDER BY m2.id DESC LIMIT 1) AS last_time,
            COALESCE((SELECT cs.status FROM chat_conversation_sessions cs
                      WHERE cs.conversation_id = m.conversation_id), 'open') AS status,
            COALESCE((SELECT cs.bot_enabled FROM chat_conversation_sessions cs
                      WHERE cs.conversation_id = m.conversation_id), 1) AS bot_enabled,
            COALESCE((SELECT cs.admin_last_read_id FROM chat_conversation_sessions cs
                      WHERE cs.conversation_id = m.conversation_id), 0) AS admin_read_id,
            (SELECT m2.username NOT IN ('admin_staff','chatbot','system')
             FROM chat_messages m2
             WHERE m2.conversation_id = m.conversation_id AND m2.msg_type = 'text'
             ORDER BY m2.id DESC LIMIT 1) AS need_reply,
            (SELECT cu.is_online FROM chat_users cu
             WHERE cu.username = (
                 SELECT m2.username FROM chat_messages m2
                 WHERE m2.conversation_id = m.conversation_id
                   AND m2.username NOT IN ('chatbot','admin_staff','system')
                 ORDER BY m2.id ASC LIMIT 1) LIMIT 1) AS is_online,
            (SELECT DATE_FORMAT(cu.last_seen,'%Y-%m-%dT%H:%i:%s') FROM chat_users cu
             WHERE cu.username = (
                 SELECT m2.username FROM chat_messages m2
                 WHERE m2.conversation_id = m.conversation_id
                   AND m2.username NOT IN ('chatbot','admin_staff','system')
                 ORDER BY m2.id ASC LIMIT 1) LIMIT 1) AS last_seen
        FROM chat_messages m
        WHERE m.conversation_id IS NOT NULL AND m.conversation_id != ''
        GROUP BY m.conversation_id
        ORDER BY max_id DESC
        LIMIT 200
    ");
    $convs = $stmt->fetchAll();
    // Filter by status
    if ($filter !== 'all') {
        $convs = array_values(array_filter($convs, fn($c) => match($filter) {
            'need_reply' => $c['need_reply'] && $c['status'] !== 'resolved',
            'operator'   => $c['status'] === 'operator',
            'resolved'   => $c['status'] === 'resolved',
            default      => true,
        }));
    }
    // Unread count per conversation
    foreach ($convs as &$conv) {
        $readId = (int)$conv['admin_read_id'];
        $u = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id=? AND id>? AND username NOT IN ('chatbot','admin_staff','system')");
        $u->execute([$conv['conversation_id'], $readId]);
        $conv['unread'] = (int)$u->fetchColumn();
    }
    ok($convs);

// ── ROOM MESSAGES (admin chat view) ───────────────
case 'room_messages':
    $roomId = (int)($_GET['room_id'] ?? 1);
    $lastId = (int)($_GET['last_id'] ?? 0);
    $convId = preg_replace('/[^a-f0-9]/', '', $_GET['conversation_id'] ?? '');
    $convCond = $convId ? ' AND conversation_id = ?' : '';

    if ($lastId === 0) {
        $params = $convId ? [$roomId, $convId] : [$roomId];
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar_color, message, msg_type, metadata,
                   conversation_id, DATE_FORMAT(created_at,'%H:%i') AS time_str
            FROM chat_messages WHERE room_id=? $convCond
            ORDER BY created_at DESC LIMIT 80
        ");
        $stmt->execute($params);
        $msgs = array_reverse($stmt->fetchAll());
    } else {
        $params = $convId ? [$roomId, $lastId, $convId] : [$roomId, $lastId];
        $stmt = $pdo->prepare("
            SELECT id, username, display_name, avatar_color, message, msg_type, metadata,
                   conversation_id, DATE_FORMAT(created_at,'%H:%i') AS time_str
            FROM chat_messages WHERE room_id=? AND id>? $convCond
            ORDER BY id ASC LIMIT 50
        ");
        $stmt->execute($params);
        $msgs = $stmt->fetchAll();
    }
    // Mark as read + update conv_info for this specific conversation
    $convInfo = null;
    if ($convId && !empty($msgs)) {
        $maxId = max(array_column($msgs, 'id'));
        try {
            $pdo->prepare("UPDATE chat_conversation_sessions SET admin_last_read_id=? WHERE conversation_id=? AND admin_last_read_id<?")
                ->execute([$maxId, $convId, $maxId]);
        } catch(Throwable) {}
    }
    try {
        $q = $convId
            ? "SELECT conversation_id, status, bot_enabled FROM chat_conversation_sessions WHERE conversation_id=? LIMIT 1"
            : "SELECT conversation_id, status, bot_enabled FROM chat_conversation_sessions WHERE room_id=? ORDER BY id DESC LIMIT 1";
        $ciStmt = $pdo->prepare($q);
        $ciStmt->execute([$convId ?: $roomId]);
        $convInfo = $ciStmt->fetch() ?: null;
    } catch(Throwable) {}
    // Attach user online status to convInfo
    if ($convId) {
        try {
            $onStmt = $pdo->prepare("
                SELECT cu.is_online, DATE_FORMAT(cu.last_seen,'%Y-%m-%dT%H:%i:%s') AS last_seen
                FROM chat_users cu
                WHERE cu.username = (
                    SELECT m2.username FROM chat_messages m2
                    WHERE m2.conversation_id = ? AND m2.username NOT IN ('chatbot','admin_staff','system')
                    ORDER BY m2.id ASC LIMIT 1
                ) LIMIT 1
            ");
            $onStmt->execute([$convId]);
            $uStatus = $onStmt->fetch();
            if ($uStatus) {
                $convInfo = $convInfo ?: [];
                $convInfo['is_online'] = (int)$uStatus['is_online'];
                $convInfo['last_seen'] = $uStatus['last_seen'];
            }
        } catch(Throwable) {}
    }
    $userTyping = null;
    try {
        $q2 = $convId
            ? "SELECT display_name FROM chat_typing_status WHERE room_id=? AND is_admin=0 AND updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1"
            : "SELECT display_name FROM chat_typing_status WHERE room_id=? AND is_admin=0 AND updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1";
        $tStmt = $pdo->prepare($q2);
        $tStmt->execute([$roomId]);
        $userTyping = $tStmt->fetchColumn() ?: null;
    } catch(Throwable) {}
    ok(['messages' => $msgs, 'conv_info' => $convInfo, 'user_typing' => $userTyping]);

// ── SEND AS ADMIN ─────────────────────────────────
case 'send_admin':
    $roomId  = (int)($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $convId  = preg_replace('/[^a-f0-9]/', '', $_POST['conversation_id'] ?? '');
    if (!$roomId || !$message) fail('room_id and message required');
    $name    = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($convId) {
        $pdo->prepare("INSERT INTO chat_messages (room_id, conversation_id, username, display_name, avatar_color, message, msg_type) VALUES (?, ?, 'admin_staff', ?, '#C62828', ?, 'text')")
            ->execute([$roomId, $convId, $name, $message]);
    } else {
        $pdo->prepare("INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type) VALUES (?, 'admin_staff', ?, '#C62828', ?, 'text')")
            ->execute([$roomId, $name, $message]);
    }
    $newId = (int)$pdo->lastInsertId();
    // Mark admin as having read up to this point and switch session to operator mode
    if ($convId) {
        $pdo->prepare("UPDATE chat_conversation_sessions SET admin_last_read_id=?, status='operator', bot_enabled=0 WHERE conversation_id=?")
            ->execute([$newId, $convId]);
    }
    // Clear admin typing status
    try {
        $adminUsername = 'admin_' . $adminId;
        $pdo->prepare("DELETE FROM chat_typing_status WHERE room_id=? AND username=? AND is_admin=1")
            ->execute([$roomId, $adminUsername]);
    } catch(Throwable) {}
    ok(['id' => $newId]);

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
    $choicesRaw = $_POST['choices'] ?? '[]';
    $choices    = (json_decode($choicesRaw) !== null) ? $choicesRaw : '[]';
    if (!$pattern) fail('กรุณาระบุ Pattern');
    if ($id > 0) {
        $pdo->prepare("UPDATE chat_bot_patterns SET pattern=?,match_type=?,response=?,choices=?,room_id=?,priority=?,is_active=?,use_ai=? WHERE id=?")
            ->execute([$pattern,$matchType,$response,$choices,$roomId2,$priority,$isActive,$useAi,$id]);
    } else {
        $pdo->prepare("INSERT INTO chat_bot_patterns (pattern,match_type,response,choices,room_id,priority,is_active,use_ai) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$pattern,$matchType,$response,$choices,$roomId2,$priority,$isActive,$useAi]);
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
        SELECT l.id, l.room_id, l.conversation_id, l.trigger_msg, l.user_name,
               l.bot_response, l.response_type, l.latency_ms,
               l.is_resolved, l.resolved_by,
               DATE_FORMAT(l.created_at,'%d/%m %H:%i')    AS time_str,
               DATE_FORMAT(l.resolved_at,'%d/%m %H:%i')   AS resolved_time
        FROM chat_bot_log l
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
    $keys = ['bot_name','bot_sub','bot_color','bot_enabled','ai_enabled','ai_provider',
             'claude_api_key','claude_model','openai_api_key','ai_system_prompt',
             'reply_delay_ms','welcome_title','welcome_sub',
             'image_reply','image_use_ai','location_reply','notify_from_email',
             'org_name','org_address','org_tel','org_emergency_tel',
             'org_website','org_line','org_facebook','org_lat','org_lng'];
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

// ── MENU ITEMS (quick reply menu) ────────────────
case 'menu_list':
    ok($pdo->query("
        SELECT m.*,
          (SELECT p.response FROM chat_bot_patterns p
           WHERE p.pattern = m.message_text AND p.match_type = 'contains' LIMIT 1) AS bot_response,
          (SELECT p.choices FROM chat_bot_patterns p
           WHERE p.pattern = m.message_text AND p.match_type = 'contains' LIMIT 1) AS choices,
          (SELECT p.use_ai FROM chat_bot_patterns p
           WHERE p.pattern = m.message_text AND p.match_type = 'contains' LIMIT 1) AS use_ai
        FROM chat_menu_items m
        ORDER BY m.sort_order ASC, m.id ASC
    ")->fetchAll());

case 'menu_save':
    $mid         = (int)($_POST['id']          ?? 0);
    $icon        = trim($_POST['icon']         ?? '📋');
    $label       = trim($_POST['label']        ?? '');
    $msgText     = trim($_POST['message_text'] ?? '');
    $order       = (int)($_POST['sort_order']  ?? 50);
    $active      = (int)($_POST['is_active']   ?? 1);
    $botResponse = trim($_POST['bot_response'] ?? '');
    $useAi       = (int)($_POST['use_ai']      ?? 0);
    $choicesRaw  = $_POST['choices'] ?? '[]';
    $choices     = (json_decode($choicesRaw) !== null) ? $choicesRaw : '[]';

    if (!$label || !$msgText) fail('label และ message_text ต้องระบุ');

    if ($mid > 0) {
        $pdo->prepare("UPDATE chat_menu_items SET icon=?,label=?,message_text=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$icon, $label, $msgText, $order, $active, $mid]);
    } else {
        $pdo->prepare("INSERT INTO chat_menu_items (icon,label,message_text,sort_order,is_active) VALUES (?,?,?,?,?)")
            ->execute([$icon, $label, $msgText, $order, $active]);
        $mid = (int)$pdo->lastInsertId();
    }

    // Auto-sync bot pattern: สร้าง/อัปเดต pattern พร้อม choices
    if ($botResponse !== '' || $useAi || $choices !== '[]') {
        $stmt = $pdo->prepare("SELECT id FROM chat_bot_patterns WHERE pattern=? AND match_type='contains' LIMIT 1");
        $stmt->execute([$msgText]);
        $patternId = $stmt->fetchColumn();
        if ($patternId) {
            $pdo->prepare("UPDATE chat_bot_patterns SET response=?, use_ai=?, is_active=?, priority=60, choices=? WHERE id=?")
                ->execute([$botResponse, $useAi, $active, $choices, $patternId]);
        } else {
            $pdo->prepare("INSERT INTO chat_bot_patterns (pattern,match_type,response,priority,is_active,use_ai,choices) VALUES (?,?,?,60,?,?,?)")
                ->execute([$msgText, 'contains', $botResponse, $active, $useAi, $choices]);
        }
    }

    ok(['id' => $mid]);

case 'menu_delete':
    $mid = (int)($_POST['id'] ?? 0);
    if (!$mid) fail('id required');
    $pdo->prepare("DELETE FROM chat_menu_items WHERE id=?")->execute([$mid]);
    ok();

case 'menu_toggle':
    $mid = (int)($_POST['id'] ?? 0);
    if (!$mid) fail('id required');
    $pdo->prepare("UPDATE chat_menu_items SET is_active=!is_active WHERE id=?")->execute([$mid]);
    $a = (int)$pdo->query("SELECT is_active FROM chat_menu_items WHERE id=$mid")->fetchColumn();
    ok(['active' => $a]);

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

// ── TINYMCE IMAGE UPLOAD ──────────────────────────
case 'tinymce_upload':
    auth();
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) fail('ไม่พบไฟล์');
    $file = $_FILES['file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png','jpg','jpeg','gif','webp'])) fail('รองรับเฉพาะ PNG, JPG, GIF, WebP');
    if ($file['size'] > 5 * 1024 * 1024) fail('ไฟล์ต้องไม่เกิน 5MB');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/png','image/jpeg','image/gif','image/webp'])) fail('ไฟล์ไม่ใช่รูปภาพ');
    $dir  = __DIR__ . '/uploads/editor/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = md5(uniqid('', true)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) fail('อัปโหลดไม่สำเร็จ');
    // TinyMCE expects { location: "url" }
    echo json_encode(['location' => 'uploads/editor/' . $name], JSON_UNESCAPED_SLASHES);
    exit;

// ── LOGO UPLOAD ───────────────────────────────────
case 'logo_upload':
    authSuper();
    if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) fail('ไม่พบไฟล์ที่อัปโหลด');
    $file = $_FILES['logo'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png','jpg','jpeg','gif','svg','webp'])) fail('รองรับเฉพาะ PNG, JPG, SVG, WebP เท่านั้น');
    if ($file['size'] > 2 * 1024 * 1024) fail('ไฟล์ต้องไม่เกิน 2MB');
    $dir = __DIR__ . '/uploads/logo/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    foreach (glob($dir . 'site_logo.*') ?: [] as $old) @unlink($old);
    $dest = $dir . 'site_logo.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dest)) fail('อัปโหลดไฟล์ไม่สำเร็จ');
    $url = 'uploads/logo/site_logo.' . $ext;
    $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES ('site_logo',?) ON DUPLICATE KEY UPDATE value=?")
        ->execute([$url, $url]);
    ok(['url' => $url]);

case 'logo_delete':
    authSuper();
    foreach (glob(__DIR__ . '/uploads/logo/site_logo.*') ?: [] as $f) @unlink($f);
    $pdo->exec("DELETE FROM chat_bot_config WHERE key_name='site_logo'");
    ok();

// ══════════════════════════════════════════════════
// USERS MANAGEMENT (superadmin เท่านั้น)
// ══════════════════════════════════════════════════

case 'users_list':
    authSuper();
    ok($pdo->query("
        SELECT id, username, display_name, role, avatar_color, is_active,
               DATE_FORMAT(last_login,'%d/%m/%Y %H:%i') AS last_login,
               DATE_FORMAT(created_at,'%d/%m/%Y') AS created_at
        FROM admin_users ORDER BY role DESC, created_at ASC
    ")->fetchAll());

case 'users_save':
    authSuper();
    $uid         = (int)($_POST['id']           ?? 0);
    $uname       = trim($_POST['username']      ?? '');
    $displayName = trim($_POST['display_name']  ?? '');
    $role        = in_array($_POST['role'] ?? '', ['superadmin','staff']) ? $_POST['role'] : 'staff';
    $avatarColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['avatar_color'] ?? '') ? $_POST['avatar_color'] : '#1565C0';
    $password    = $_POST['password'] ?? '';

    if (!$uname || !$displayName) fail('กรุณากรอก username และชื่อ');
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $uname)) fail('Username ต้องเป็น a-z, 0-9, _ และ 3–30 ตัว');

    if ($uid > 0) {
        // ป้องกัน superadmin คนสุดท้ายถูก demote เป็น staff
        if ($role !== 'superadmin') {
            $currentRole = $pdo->prepare("SELECT role FROM admin_users WHERE id=?");
            $currentRole->execute([$uid]);
            $oldRole = $currentRole->fetchColumn();
            if ($oldRole === 'superadmin') {
                $superCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role='superadmin' AND is_active=1")->fetchColumn();
                if ($superCount <= 1) fail('ไม่สามารถลดสิทธิ์ได้ — ต้องมี Superadmin อย่างน้อย 1 คน');
            }
        }
        if ($password !== '') {
            if (mb_strlen($password) < 8) fail('รหัสผ่านต้องอย่างน้อย 8 ตัวอักษร');
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE admin_users SET username=?,display_name=?,password_hash=?,role=?,avatar_color=? WHERE id=?")
                ->execute([$uname, $displayName, $hash, $role, $avatarColor, $uid]);
        } else {
            $pdo->prepare("UPDATE admin_users SET username=?,display_name=?,role=?,avatar_color=? WHERE id=?")
                ->execute([$uname, $displayName, $role, $avatarColor, $uid]);
        }
    } else {
        if (mb_strlen($password) < 8) fail('รหัสผ่านต้องอย่างน้อย 8 ตัวอักษร');
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $pdo->prepare("INSERT INTO admin_users (username,display_name,password_hash,role,avatar_color) VALUES (?,?,?,?,?)")
                ->execute([$uname, $displayName, $hash, $role, $avatarColor]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') fail('Username นี้มีอยู่แล้ว');
            throw $e;
        }
        $uid = (int)$pdo->lastInsertId();
    }
    ok(['id' => $uid]);

case 'users_toggle':
    authSuper();
    $uid = (int)($_POST['id'] ?? 0);
    if (!$uid) fail('id required');
    if ($uid === (int)($_SESSION['admin_id'] ?? 0)) fail('ไม่สามารถปิดใช้งานบัญชีตัวเองได้');
    // ป้องกัน disable superadmin คนสุดท้าย
    $targetRole = $pdo->prepare("SELECT role FROM admin_users WHERE id=?");
    $targetRole->execute([$uid]);
    if ($targetRole->fetchColumn() === 'superadmin') {
        $superCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role='superadmin' AND is_active=1")->fetchColumn();
        if ($superCount <= 1) fail('ไม่สามารถปิดใช้งานได้ — ต้องมี Superadmin ที่ active อย่างน้อย 1 คน');
    }
    $pdo->prepare("UPDATE admin_users SET is_active = NOT is_active WHERE id=?")->execute([$uid]);
    $active = (bool)$pdo->query("SELECT is_active FROM admin_users WHERE id=$uid")->fetchColumn();
    ok(['is_active' => $active]);

case 'users_delete':
    authSuper();
    $uid = (int)($_POST['id'] ?? 0);
    if (!$uid) fail('id required');
    if ($uid === (int)($_SESSION['admin_id'] ?? 0)) fail('ไม่สามารถลบบัญชีตัวเองได้');
    // ป้องกัน delete superadmin คนสุดท้าย
    $targetRole2 = $pdo->prepare("SELECT role FROM admin_users WHERE id=?");
    $targetRole2->execute([$uid]);
    if ($targetRole2->fetchColumn() === 'superadmin') {
        $superCount2 = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role='superadmin'")->fetchColumn();
        if ($superCount2 <= 1) fail('ไม่สามารถลบได้ — ต้องมี Superadmin อย่างน้อย 1 คน');
    }
    $pdo->prepare("DELETE FROM admin_users WHERE id=?")->execute([$uid]);
    ok();

// ── OPERATOR: TAKE OVER ───────────────────────────
case 'take_over':
    $roomId = (int)($_POST['room_id'] ?? 1);
    $convId = preg_replace('/[^a-f0-9]/', '', $_POST['conversation_id'] ?? '');
    if (!$convId && !$roomId) fail('conversation_id or room_id required');
    $adminName = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $adminId   = (int)($_SESSION['admin_id'] ?? 0);
    // If no specific conv_id, find the latest conversation in this room
    if (!$convId) {
        $r = $pdo->prepare("SELECT conversation_id FROM chat_conversation_sessions WHERE room_id=? ORDER BY last_msg_at DESC LIMIT 1");
        $r->execute([$roomId]);
        $convId = $r->fetchColumn() ?: '';
    }
    if (!$convId) fail('ไม่พบการสนทนาที่ active ในห้องนี้');
    $pdo->prepare("UPDATE chat_conversation_sessions SET status='operator', bot_enabled=0, assigned_to=?, assigned_name=? WHERE conversation_id=?")
        ->execute([$adminId, $adminName, $convId]);
    // System message
    try {
        $pdo->prepare("INSERT INTO chat_messages (room_id, conversation_id, username, display_name, avatar_color, message, msg_type) VALUES (?,?,'system','ระบบ','#888888',?,'system')")
            ->execute([$roomId, $convId, "🙋 {$adminName} รับสายแล้ว กรุณาพิมพ์ข้อความได้เลยครับ"]);
    } catch(Throwable) {}
    ok(['conversation_id' => $convId, 'operator' => $adminName]);

// ── OPERATOR: RELEASE (คืนให้ Bot) ─────────────────
case 'release_conv':
    $convId = preg_replace('/[^a-f0-9]/', '', $_POST['conversation_id'] ?? '');
    $roomId = (int)($_POST['room_id'] ?? 0);
    if (!$convId) fail('conversation_id required');
    $adminName = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $pdo->prepare("UPDATE chat_conversation_sessions SET status='open', bot_enabled=1, assigned_to=NULL, assigned_name=NULL WHERE conversation_id=?")
        ->execute([$convId]);
    try {
        $pdo->prepare("INSERT INTO chat_messages (room_id, conversation_id, username, display_name, avatar_color, message, msg_type) VALUES (?,?,'system','ระบบ','#888888',?,'system')")
            ->execute([$roomId ?: 1, $convId, "🤖 โอนกลับให้ Bot ดูแลแล้ว"]);
    } catch(Throwable) {}
    ok(['conversation_id' => $convId]);

// ── OPERATOR: CLOSE CONVERSATION ──────────────────
case 'close_conv':
    $convId = preg_replace('/[^a-f0-9]/', '', $_POST['conversation_id'] ?? '');
    $roomId = (int)($_POST['room_id'] ?? 0);
    if (!$convId) fail('conversation_id required');
    $adminName = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $pdo->prepare("UPDATE chat_conversation_sessions SET status='resolved', bot_enabled=0, resolved_at=NOW(), resolved_by=? WHERE conversation_id=?")
        ->execute([$adminName, $convId]);
    try {
        $pdo->prepare("INSERT INTO chat_messages (room_id, conversation_id, username, display_name, avatar_color, message, msg_type) VALUES (?,?,'system','ระบบ','#888888',?,'system')")
            ->execute([$roomId ?: 1, $convId, "✅ เจ้าหน้าที่ปิดการสนทนาแล้ว ขอบคุณที่ใช้บริการครับ"]);
    } catch(Throwable) {}
    ok(['conversation_id' => $convId]);

// ── ADMIN TYPING STATUS ───────────────────────────
case 'admin_typing':
    $roomId   = (int)($_POST['room_id'] ?? 0);
    $isTyping = (int)($_POST['is_typing'] ?? 0);
    if (!$roomId) fail('room_id required');
    $adminId   = (int)($_SESSION['admin_id'] ?? 0);
    $adminName = $_SESSION['admin_name'] ?? 'เจ้าหน้าที่';
    $username  = 'admin_' . $adminId;
    try {
        if ($isTyping) {
            $pdo->prepare("INSERT INTO chat_typing_status (room_id, username, display_name, is_admin) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE display_name=?, updated_at=NOW()")
                ->execute([$roomId, $username, $adminName, $adminName]);
        } else {
            $pdo->prepare("DELETE FROM chat_typing_status WHERE room_id=? AND username=? AND is_admin=1")
                ->execute([$roomId, $username]);
        }
    } catch(Throwable) {}
    ok();

// ── GET USER TYPING STATUS ────────────────────────
case 'get_user_typing':
    $roomId = (int)($_GET['room_id'] ?? 0);
    if (!$roomId) ok(['typing' => null]);
    try {
        $stmt = $pdo->prepare("SELECT display_name FROM chat_typing_status WHERE room_id=? AND is_admin=0 AND updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1");
        $stmt->execute([$roomId]);
        $who = $stmt->fetchColumn() ?: null;
    } catch(Throwable) { $who = null; }
    ok(['typing' => $who]);

// ── SET OPERATOR AVAILABILITY ─────────────────────
case 'set_available':
    $avail   = (int)($_POST['is_available'] ?? 0);
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    if (!$adminId) fail('ต้องล็อกอิน');
    try {
        $pdo->prepare("UPDATE admin_users SET is_available=?, last_active=NOW() WHERE id=?")
            ->execute([$avail ? 1 : 0, $adminId]);
    } catch(Throwable $e) { fail('ตาราง admin_users ยังไม่ถูก migrate: ' . $e->getMessage()); }
    $_SESSION['admin_available'] = (bool)$avail;
    ok(['is_available' => (bool)$avail]);

// ── CANNED RESPONSES ──────────────────────────────
case 'canned_list':
    try {
        $rows = $pdo->query("SELECT * FROM chat_canned_responses ORDER BY category ASC, sort_order ASC, id ASC")->fetchAll();
    } catch(Throwable) { $rows = []; }
    ok($rows);

case 'canned_save':
    $cid      = (int)($_POST['id']         ?? 0);
    $shortcut = trim($_POST['shortcut']    ?? '');
    $title    = trim($_POST['title']       ?? '');
    $content  = trim($_POST['content']     ?? '');
    $category = trim($_POST['category']    ?? 'ทั่วไป');
    $order    = (int)($_POST['sort_order'] ?? 50);
    $active   = (int)($_POST['is_active']  ?? 1);
    if (!$title || !$content) fail('กรุณากรอก Title และ Content');
    if ($cid > 0) {
        $pdo->prepare("UPDATE chat_canned_responses SET shortcut=?,title=?,content=?,category=?,sort_order=?,is_active=? WHERE id=?")
            ->execute([$shortcut, $title, $content, $category, $order, $active, $cid]);
    } else {
        $pdo->prepare("INSERT INTO chat_canned_responses (shortcut,title,content,category,sort_order,is_active) VALUES (?,?,?,?,?,?)")
            ->execute([$shortcut, $title, $content, $category, $order, $active]);
        $cid = (int)$pdo->lastInsertId();
    }
    ok(['id' => $cid]);

case 'canned_delete':
    $cid = (int)($_POST['id'] ?? 0);
    if (!$cid) fail('id required');
    $pdo->prepare("DELETE FROM chat_canned_responses WHERE id=?")->execute([$cid]);
    ok();

// ── SEARCH MESSAGES ───────────────────────────────
case 'search_messages':
    $q       = trim($_GET['q'] ?? '');
    $roomFlt = (int)($_GET['room_id'] ?? 0);
    $dateFr  = trim($_GET['date_from'] ?? '');
    $dateTo  = trim($_GET['date_to'] ?? '');
    $lim     = min((int)($_GET['limit'] ?? 50), 200);
    if (mb_strlen($q) < 2) fail('กรุณาใส่คำค้นหาอย่างน้อย 2 ตัวอักษร');
    $where = "WHERE m.message LIKE ? AND m.msg_type IN ('text','image')";
    $params = ['%' . $q . '%'];
    if ($roomFlt > 0) { $where .= ' AND m.room_id=?'; $params[] = $roomFlt; }
    if ($dateFr) { $where .= ' AND DATE(m.created_at)>=?'; $params[] = $dateFr; }
    if ($dateTo) { $where .= ' AND DATE(m.created_at)<=?'; $params[] = $dateTo; }
    $params[] = $lim;
    $stmt = $pdo->prepare("
        SELECT m.id, m.room_id, m.username, m.display_name, m.message, m.msg_type,
               m.conversation_id,
               DATE_FORMAT(m.created_at,'%d/%m/%Y %H:%i') AS time_str,
               r.name AS room_name
        FROM chat_messages m
        LEFT JOIN chat_rooms r ON r.id=m.room_id
        $where ORDER BY m.created_at DESC LIMIT ?
    ");
    $stmt->execute($params);
    ok($stmt->fetchAll());

// ── EXPORT CONVERSATION ───────────────────────────
case 'export_conv':
    $convId = preg_replace('/[^a-f0-9]/', '', $_GET['conversation_id'] ?? '');
    $roomId = (int)($_GET['room_id'] ?? 0);
    if (!$convId && !$roomId) fail('conversation_id หรือ room_id required');
    $where  = $convId ? 'WHERE conversation_id=?' : 'WHERE room_id=?';
    $param  = $convId ?: $roomId;
    $stmt   = $pdo->prepare("
        SELECT display_name, message, msg_type, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') AS created_at
        FROM chat_messages $where ORDER BY created_at ASC
    ");
    $stmt->execute([$param]);
    $rows = $stmt->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="chat_export_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "เวลา,ผู้ส่ง,ประเภท,ข้อความ\n";
    foreach ($rows as $r) {
        $msg = str_replace(['"', "\n", "\r"], ['"\"', ' ', ''], $r['message']);
        echo '"' . $r['created_at'] . '","' . $r['display_name'] . '","' . $r['msg_type'] . '","' . $msg . '"' . "\n";
    }
    exit;

// ── ANALYTICS (7-day trends + response stats) ─────
case 'analytics':
    $period = (int)($_GET['days'] ?? 7);
    $period = min(max($period, 7), 90);
    // Daily message counts
    $daily = $pdo->query("
        SELECT DATE(created_at) AS day,
               COUNT(*) AS total,
               SUM(username NOT IN ('chatbot','admin_staff','system') AND msg_type='text') AS user_msgs,
               SUM(username='chatbot') AS bot_msgs,
               SUM(username='admin_staff') AS admin_msgs
        FROM chat_messages
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ")->fetchAll();
    // Avg response time (time from user msg to first admin/bot reply in same conv)
    $avgResp = (float)($pdo->query("
        SELECT AVG(latency_ms)/1000
        FROM chat_bot_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
    ")->fetchColumn() ?: 0);
    // CSAT average
    $csatAvg = 0; $csatCount = 0;
    try {
        $cRow = $pdo->query("SELECT AVG(rating), COUNT(*) FROM chat_csat_ratings WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)")->fetch(PDO::FETCH_NUM);
        $csatAvg   = round((float)($cRow[0] ?? 0), 1);
        $csatCount = (int)($cRow[1] ?? 0);
    } catch(Throwable) {}
    // Top unanswered patterns
    $topUnans = $pdo->query("
        SELECT trigger_msg, COUNT(*) AS cnt
        FROM chat_bot_log
        WHERE response_type='fallback' AND created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
        GROUP BY trigger_msg ORDER BY cnt DESC LIMIT 10
    ")->fetchAll();
    // Resolved conversations
    $resolved = (int)$pdo->query("SELECT COUNT(*) FROM chat_conversation_sessions WHERE status='resolved' AND resolved_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)")->fetchColumn();
    ok(['daily' => $daily, 'avg_response_sec' => round($avgResp, 2), 'csat_avg' => $csatAvg, 'csat_count' => $csatCount, 'top_unanswered' => $topUnans, 'resolved' => $resolved, 'period' => $period]);

// ── CSAT LIST ──────────────────────────────────────
case 'csat_list':
    $lim = min((int)($_GET['limit'] ?? 50), 200);
    try {
        ok($pdo->query("SELECT id, conversation_id, room_id, user_name, rating, LEFT(comment,200) AS comment, DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS time_str FROM chat_csat_ratings ORDER BY created_at DESC LIMIT {$lim}")->fetchAll());
    } catch(Throwable) { ok([]); }

// ── OPERATOR PRESENCE (list online admins) ─────────
case 'operator_list':
    try {
        $rows = $pdo->query("SELECT id, display_name, avatar_color, is_available, DATE_FORMAT(last_active,'%H:%i') AS last_active_str FROM admin_users WHERE is_active=1 ORDER BY is_available DESC, last_active DESC")->fetchAll();
    } catch(Throwable) { $rows = []; }
    ok($rows);

default:
    fail('Unknown action', 404);
}
