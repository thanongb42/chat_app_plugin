<?php
// =============================================
// chat_api.php — REST API Backend
// =============================================
// Endpoints:
//   GET  ?action=messages&room_id=1&last_id=0   → ดึงข้อความ
//   POST ?action=send                             → ส่งข้อความ
//   POST ?action=login                            → เข้าสู่ระบบ
//   POST ?action=logout                           → ออกจากระบบ
//   GET  ?action=rooms                            → ดึงรายการห้อง
//   GET  ?action=online_users&room_id=1           → ผู้ใช้ออนไลน์
//   POST ?action=heartbeat                        → อัปเดตสถานะออนไลน์
// =============================================

require_once __DIR__ . '/chat_config.php';
session_name(CHAT_SESSION_NAME);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // ─────────────────────────────────────
        // LOGIN
        // ─────────────────────────────────────
        case 'login':
            $displayName = sanitizeInput($_POST['display_name'] ?? '');
            if (empty($displayName) || mb_strlen($displayName) > 30) {
                jsonResponse(['success' => false, 'error' => 'ชื่อต้องมี 1-30 ตัวอักษร']);
            }

            // สร้าง username จาก display_name + random
            $username = 'user_' . uniqid();
            $color    = randomAvatarColor();

            $deviceId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['device_id'] ?? '');
            $pdo = getChatDB();
            $stmt = $pdo->prepare("
                INSERT INTO chat_users (username, display_name, avatar_color, device_id, is_online, last_seen)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$username, $displayName, $color, $deviceId ?: null]);
            $userId = $pdo->lastInsertId();

            $_SESSION[CHAT_SESSION_NAME] = [
                'id'           => $userId,
                'username'     => $username,
                'display_name' => $displayName,
                'avatar_color' => $color,
            ];

            // ส่งข้อความ system ว่าเข้าห้อง
            $stmt2 = $pdo->prepare("
                INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
                VALUES (1, 'system', 'ระบบ', '#888888', ?, 'system')
            ");
            $stmt2->execute(["$displayName เข้าร่วมห้องสนทนา 👋"]);

            // สร้าง conversation_id สำหรับ session นี้
            $_SESSION['conversation_id'] = bin2hex(random_bytes(16));

            jsonResponse([
                'success'         => true,
                'user'            => $_SESSION[CHAT_SESSION_NAME],
                'conversation_id' => $_SESSION['conversation_id'],
            ]);

        // ─────────────────────────────────────
        // LOGOUT
        // ─────────────────────────────────────
        case 'logout':
            if (!empty($_SESSION[CHAT_SESSION_NAME])) {
                $user = $_SESSION[CHAT_SESSION_NAME];
                $pdo  = getChatDB();
                $pdo->prepare("UPDATE chat_users SET is_online=0 WHERE id=?")
                    ->execute([$user['id']]);
                $pdo->prepare("
                    INSERT INTO chat_messages (room_id, username, display_name, avatar_color, message, msg_type)
                    VALUES (1, 'system', 'ระบบ', '#888888', ?, 'system')
                ")->execute([$user['display_name'] . " ออกจากห้องสนทนา"]);
            }
            unset($_SESSION[CHAT_SESSION_NAME]);
            session_destroy();
            jsonResponse(['success' => true]);

        // ─────────────────────────────────────
        // CHECK SESSION
        // ─────────────────────────────────────
        case 'check_session':
            if (!empty($_SESSION[CHAT_SESSION_NAME])) {
                if (empty($_SESSION['conversation_id'])) {
                    $_SESSION['conversation_id'] = bin2hex(random_bytes(16));
                }
                jsonResponse([
                    'logged_in'       => true,
                    'user'            => $_SESSION[CHAT_SESSION_NAME],
                    'conversation_id' => $_SESSION['conversation_id'],
                ]);
            }
            jsonResponse(['logged_in' => false]);

        // ─────────────────────────────────────
        // NEW CONVERSATION
        // ─────────────────────────────────────
        case 'new_conversation':
            if (empty($_SESSION[CHAT_SESSION_NAME])) {
                jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            }
            $_SESSION['conversation_id'] = bin2hex(random_bytes(16));
            jsonResponse(['success' => true, 'conversation_id' => $_SESSION['conversation_id']]);

        // ─────────────────────────────────────
        // GET MESSAGES
        // ─────────────────────────────────────
        case 'messages':
            $roomId = (int)($_GET['room_id'] ?? 1);
            $lastId = (int)($_GET['last_id'] ?? 0);
            $limit  = min((int)($_GET['limit'] ?? CHAT_MSG_LIMIT), 100);
            $convId = preg_replace('/[^a-f0-9]/', '', $_GET['conversation_id'] ?? '');

            $pdo = getChatDB();
            $convCond = $convId ? ' AND conversation_id = ?' : '';

            if ($lastId === 0) {
                $params = $convId ? [$roomId, $convId, $limit] : [$roomId, $limit];
                $stmt = $pdo->prepare("
                    SELECT id, username, display_name, avatar_color, message, msg_type, metadata,
                           DATE_FORMAT(created_at, '%H:%i') AS time_str, created_at
                    FROM chat_messages
                    WHERE room_id = ? {$convCond}
                    ORDER BY created_at DESC LIMIT ?
                ");
                $stmt->execute($params);
                $msgs = array_reverse($stmt->fetchAll());
            } else {
                $params = $convId ? [$roomId, $lastId, $convId] : [$roomId, $lastId];
                $stmt = $pdo->prepare("
                    SELECT id, username, display_name, avatar_color, message, msg_type, metadata,
                           DATE_FORMAT(created_at, '%H:%i') AS time_str, created_at
                    FROM chat_messages
                    WHERE room_id = ? AND id > ? {$convCond}
                    ORDER BY created_at ASC LIMIT 50
                ");
                $stmt->execute($params);
                $msgs = $stmt->fetchAll();
            }

            // ─── Extra metadata ───────────────────────────────────
            $adminReadId   = 0;
            $operatorTyping = null;
            $convStatus    = 'open';
            $botEnabled    = true;
            if ($convId) {
                try {
                    $meta = $pdo->prepare("SELECT admin_last_read_id, status, bot_enabled FROM chat_conversation_sessions WHERE conversation_id=?");
                    $meta->execute([$convId]);
                    $row = $meta->fetch();
                    if ($row) {
                        $adminReadId = (int)$row['admin_last_read_id'];
                        $convStatus  = $row['status'];
                        $botEnabled  = (bool)$row['bot_enabled'];
                    }
                } catch (Throwable) {}
            }
            try {
                $tStmt = $pdo->prepare("SELECT display_name FROM chat_typing_status WHERE room_id=? AND is_admin=1 AND updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND) LIMIT 1");
                $tStmt->execute([$roomId]);
                $operatorTyping = $tStmt->fetchColumn() ?: null;
            } catch (Throwable) {}

            jsonResponse([
                'success'         => true,
                'messages'        => $msgs,
                'admin_read_id'   => $adminReadId,
                'operator_typing' => $operatorTyping,
                'conv_status'     => $convStatus,
                'bot_enabled'     => $botEnabled,
            ]);

        // ─────────────────────────────────────
        // SEND MESSAGE
        // ─────────────────────────────────────
        case 'send':
            if (empty($_SESSION[CHAT_SESSION_NAME])) {
                jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            }

            $user    = $_SESSION[CHAT_SESSION_NAME];
            $message = trim($_POST['message'] ?? '');
            $roomId  = (int)($_POST['room_id'] ?? 1);

            if (empty($message)) {
                jsonResponse(['success' => false, 'error' => 'ข้อความว่างเปล่า']);
            }
            if (mb_strlen($message) > CHAT_MAX_MSG_LENGTH) {
                jsonResponse(['success' => false, 'error' => 'ข้อความยาวเกินไป']);
            }

            $pdo    = getChatDB();
            $convId = $_SESSION['conversation_id'] ?? bin2hex(random_bytes(16));

            // ─── Rate limiting: max 15 messages per user per 60 seconds ───
            try {
                $rlStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_rate_limits WHERE user_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
                $rlStmt->execute([$user['id']]);
                if ((int)$rlStmt->fetchColumn() >= 15) {
                    jsonResponse(['success' => false, 'error' => 'ส่งข้อความถี่เกินไป กรุณารอสักครู่']);
                }
                $pdo->prepare("INSERT INTO chat_rate_limits (user_id, ip_address) VALUES (?,?)")
                    ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
                $pdo->exec("DELETE FROM chat_rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            } catch (Throwable) {} // rate limit table might not exist yet

            // ─── Save message ────────────────────────────────────
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (room_id, conversation_id, user_id, username, display_name, avatar_color, message)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $roomId, $convId,
                $user['id'],
                $user['username'],
                $user['display_name'],
                $user['avatar_color'],
                $message,
            ]);
            $newId = $pdo->lastInsertId();

            // ─── Update/Create conversation session ───────────────
            try {
                $pdo->prepare("INSERT INTO chat_conversation_sessions (conversation_id, room_id, user_id, user_name, status, last_message, last_msg_at)
                               VALUES (?,?,?,?,'open',?,NOW())
                               ON DUPLICATE KEY UPDATE last_message=VALUES(last_message), last_msg_at=NOW()")
                    ->execute([$convId, $roomId, $user['id'], $user['display_name'], mb_substr($message, 0, 200)]);
            } catch (Throwable) {}

            // ─── Clear user typing status ─────────────────────────
            try {
                $pdo->prepare("DELETE FROM chat_typing_status WHERE room_id=? AND username=? AND is_admin=0")
                    ->execute([$roomId, $user['username']]);
            } catch (Throwable) {}

            // ─── Check if in operator mode (skip bot) ────────────
            $botEnabled = true;
            try {
                $modeStmt = $pdo->prepare("SELECT bot_enabled FROM chat_conversation_sessions WHERE conversation_id=? LIMIT 1");
                $modeStmt->execute([$convId]);
                $modeRow  = $modeStmt->fetch();
                if ($modeRow && (int)$modeRow['bot_enabled'] === 0) {
                    $botEnabled = false;
                }
            } catch (Throwable) {}

            // ─── BOT + NOTIFICATION ───────────────────────────────
            if ($botEnabled) {
                try {
                    require_once __DIR__ . '/chat_bot_engine.php';
                    require_once __DIR__ . '/notification_engine.php';

                    $bot    = new ChatBotEngine($pdo);
                    $notif  = new NotificationEngine($pdo);
                    $result = $bot->process($message, $user['display_name'], $roomId, $convId);

                    // ดึงชื่อห้อง
                    $roomName = $pdo->prepare("SELECT name FROM chat_rooms WHERE id=?");
                    $roomName->execute([$roomId]);
                    $roomName = $roomName->fetchColumn() ?: "ห้อง #{$roomId}";

                    if ($result) {
                        $metadata = null;
                        if (!empty($result['choices'])) {
                            $metadata = json_encode(
                                ['choices' => $result['choices']],
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            );
                        }
                        ChatBotEngine::insertBotMessage(
                            $pdo, $roomId,
                            $bot->getBotName(),
                            $bot->getBotColor(),
                            $result['response'],
                            $bot->getDelayMs(),
                            $metadata,
                            $convId
                        );
                        if ($result['type'] === 'fallback') {
                            $notif->onBotFallback($message, $user['display_name'], $roomId, $roomName);
                        }
                    } else {
                        $notif->onOffHoursMessage($message, $user['display_name'], $roomId, $roomName);
                    }
                } catch (Throwable $e) {
                    error_log('ChatBot/Notification error: ' . $e->getMessage());
                }
            }
            // ─────────────────────────────────────────────────────

            jsonResponse(['success' => true, 'id' => $newId]);

        // ─────────────────────────────────────
        // ROOMS
        // ─────────────────────────────────────
        case 'rooms':
            $pdo  = getChatDB();
            $stmt = $pdo->query("
                SELECT r.id, r.name, r.description,
                       COUNT(m.id) AS msg_count
                FROM chat_rooms r
                LEFT JOIN chat_messages m ON m.room_id = r.id
                WHERE r.is_public = 1
                GROUP BY r.id
                ORDER BY r.id ASC
            ");
            jsonResponse(['success' => true, 'rooms' => $stmt->fetchAll()]);

        // ─────────────────────────────────────
        // ONLINE USERS
        // ─────────────────────────────────────
        case 'online_users':
            $pdo  = getChatDB();
            // ถือว่าออนไลน์หากส่ง heartbeat ใน 30 วินาทีที่แล้ว
            $stmt = $pdo->query("
                SELECT id, display_name, avatar_color
                FROM chat_users
                WHERE is_online = 1 AND last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                ORDER BY last_seen DESC
                LIMIT 50
            ");
            $users = $stmt->fetchAll();
            jsonResponse(['success' => true, 'users' => $users, 'count' => count($users)]);

        // ─────────────────────────────────────
        // HEARTBEAT (keep alive)
        // ─────────────────────────────────────
        case 'heartbeat':
            if (!empty($_SESSION[CHAT_SESSION_NAME])) {
                $pdo = getChatDB();
                $pdo->prepare("UPDATE chat_users SET is_online=1, last_seen=NOW() WHERE id=?")
                    ->execute([$_SESSION[CHAT_SESSION_NAME]['id']]);
            }
            jsonResponse(['success' => true, 'time' => date('H:i:s')]);

        // ─────────────────────────────────────
        // SEND IMAGE
        // ─────────────────────────────────────
        case 'send_image':
            if (empty($_SESSION[CHAT_SESSION_NAME])) {
                jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            }
            $user   = $_SESSION[CHAT_SESSION_NAME];
            $roomId = (int)($_POST['room_id'] ?? 1);
            $file   = $_FILES['image'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['success' => false, 'error' => 'ไม่พบไฟล์ที่อัปโหลด']);
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                jsonResponse(['success' => false, 'error' => 'ไฟล์ใหญ่เกิน 5MB']);
            }

            // ตรวจ MIME type จากเนื้อไฟล์จริง (ไม่ใช่ extension)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png',
                        'image/gif' => 'gif', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                jsonResponse(['success' => false, 'error' => 'ไฟล์ต้องเป็นรูปภาพ (jpg/png/gif/webp)']);
            }

            $uploadDir = __DIR__ . '/uploads/chat/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                jsonResponse(['success' => false, 'error' => 'บันทึกไฟล์ไม่สำเร็จ']);
            }

            $imgPath = 'uploads/chat/' . $filename;
            $pdo    = getChatDB();
            $convId = $_SESSION['conversation_id'] ?? bin2hex(random_bytes(16));
            $pdo->prepare("
                INSERT INTO chat_messages (room_id, conversation_id, user_id, username, display_name, avatar_color, message, msg_type)
                VALUES (?,?,?,?,?,?,?,'image')
            ")->execute([$roomId, $convId, $user['id'], $user['username'], $user['display_name'], $user['avatar_color'], $imgPath]);

            // Bot ตอบขอบคุณรูปภาพ (+ Claude Vision ถ้าเปิด AI)
            try {
                require_once __DIR__ . '/chat_bot_engine.php';
                $bot    = new ChatBotEngine($pdo);
                $result = $bot->processImage($imgPath, $user['display_name'], $roomId, $convId);
                if ($result) {
                    ChatBotEngine::insertBotMessage(
                        $pdo, $roomId, $bot->getBotName(), $bot->getBotColor(),
                        $result['response'], $bot->getDelayMs(), null, $convId
                    );
                }
            } catch (Throwable $e) { error_log('Bot image error: ' . $e->getMessage()); }

            jsonResponse(['success' => true]);

        // ─────────────────────────────────────
        // SEND LOCATION
        // ─────────────────────────────────────
        case 'send_location':
            if (empty($_SESSION[CHAT_SESSION_NAME])) {
                jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            }
            $user   = $_SESSION[CHAT_SESSION_NAME];
            $roomId = (int)($_POST['room_id'] ?? 1);
            $lat    = (float)($_POST['lat'] ?? 0);
            $lng    = (float)($_POST['lng'] ?? 0);
            $acc    = (float)($_POST['accuracy'] ?? 0);

            if (($lat === 0.0 && $lng === 0.0) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                jsonResponse(['success' => false, 'error' => 'พิกัดไม่ถูกต้อง']);
            }

            $locArr  = ['lat' => round($lat, 7), 'lng' => round($lng, 7), 'acc' => round($acc, 1)];
            $locData = json_encode($locArr, JSON_UNESCAPED_UNICODE);
            $pdo    = getChatDB();
            $convId = $_SESSION['conversation_id'] ?? bin2hex(random_bytes(16));
            $pdo->prepare("
                INSERT INTO chat_messages (room_id, conversation_id, user_id, username, display_name, avatar_color, message, msg_type)
                VALUES (?,?,?,?,?,?,?,'location')
            ")->execute([$roomId, $convId, $user['id'], $user['username'], $user['display_name'], $user['avatar_color'], $locData]);

            // Bot ตอบรับตำแหน่งพร้อมชื่อสถานที่จริง
            try {
                require_once __DIR__ . '/chat_bot_engine.php';
                $bot    = new ChatBotEngine($pdo);
                $result = $bot->processLocation($locArr, $user['display_name'], $roomId, $convId);
                if ($result) {
                    ChatBotEngine::insertBotMessage(
                        $pdo, $roomId, $bot->getBotName(), $bot->getBotColor(),
                        $result['response'], $bot->getDelayMs(), null, $convId
                    );
                }
            } catch (Throwable $e) { error_log('Bot location error: ' . $e->getMessage()); }

            jsonResponse(['success' => true]);

        // ─────────────────────────────────────
        // DEVICE AUTO-LOGIN (คืนชื่อผู้ใช้เดิม)
        // ─────────────────────────────────────
        case 'device_login':
            $deviceId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['device_id'] ?? '');
            if (!$deviceId) jsonResponse(['success' => false, 'reason' => 'no_device']);

            $pdo   = getChatDB();
            $stmt  = $pdo->prepare("SELECT display_name, avatar_color FROM chat_users WHERE device_id=? ORDER BY last_seen DESC LIMIT 1");
            $stmt->execute([$deviceId]);
            $prev  = $stmt->fetch();
            if (!$prev) jsonResponse(['success' => false, 'reason' => 'new_device']);

            // สร้าง session ใหม่ด้วยชื่อเดิม
            $username = 'user_' . uniqid();
            $convId   = bin2hex(random_bytes(16));
            $pdo->prepare("INSERT INTO chat_users (username, display_name, avatar_color, device_id, is_online, last_seen) VALUES (?,?,?,?,1,NOW())")
                ->execute([$username, $prev['display_name'], $prev['avatar_color'], $deviceId]);
            $userId = (int)$pdo->lastInsertId();

            $_SESSION[CHAT_SESSION_NAME] = [
                'id' => $userId, 'username' => $username,
                'display_name' => $prev['display_name'], 'avatar_color' => $prev['avatar_color'],
            ];
            $_SESSION['conversation_id'] = $convId;

            jsonResponse(['success' => true, 'user' => $_SESSION[CHAT_SESSION_NAME], 'conversation_id' => $convId]);

        // ─────────────────────────────────────
        // DEVICE HISTORY (รายการสนทนาเก่า)
        // ─────────────────────────────────────
        case 'device_history':
            $deviceId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['device_id'] ?? '');
            if (!$deviceId) jsonResponse(['success' => true, 'conversations' => []]);

            $pdo  = getChatDB();
            $stmt = $pdo->prepare("
                SELECT
                    cs.conversation_id,
                    cs.room_id,
                    COALESCE(r.name, 'ทั่วไป')                           AS room_name,
                    DATE_FORMAT(cs.created_at,  '%d/%m/%Y %H:%i')       AS started_at,
                    DATE_FORMAT(
                        COALESCE(cs.last_msg_at, cs.created_at),
                        '%d/%m/%Y %H:%i')                                AS last_at,
                    (SELECT COUNT(*) FROM chat_messages cm
                     WHERE cm.conversation_id = cs.conversation_id)      AS msg_count,
                    (SELECT cm2.message FROM chat_messages cm2
                     WHERE cm2.conversation_id = cs.conversation_id
                       AND cm2.username NOT IN ('chatbot','system','admin_staff')
                     ORDER BY cm2.id ASC LIMIT 1)                        AS first_msg
                FROM chat_conversation_sessions cs
                LEFT JOIN chat_rooms r ON r.id = cs.room_id
                WHERE cs.user_id IN (
                    SELECT id FROM chat_users WHERE device_id = ?
                )
                ORDER BY COALESCE(cs.last_msg_at, cs.created_at) DESC
                LIMIT 20
            ");
            $stmt->execute([$deviceId]);
            jsonResponse(['success' => true, 'conversations' => $stmt->fetchAll()]);

        // ─────────────────────────────────────
        // CONVERSATION VIEW (ดูการสนทนาเก่า)
        // ─────────────────────────────────────
        case 'conversation_view':
            $convId   = preg_replace('/[^a-f0-9]/',         '', $_GET['conversation_id'] ?? '');
            $deviceId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['device_id']        ?? '');
            if (!$convId || !$deviceId) jsonResponse(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);

            $pdo = getChatDB();
            // ตรวจว่า device นี้เป็นเจ้าของ conversation จริง
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM chat_conversation_sessions cs
                INNER JOIN chat_users u ON u.id = cs.user_id
                WHERE cs.conversation_id = ? AND u.device_id = ?
            ");
            $chk->execute([$convId, $deviceId]);
            if ((int)$chk->fetchColumn() === 0) jsonResponse(['success' => false, 'error' => 'ไม่พบประวัติ'], 403);

            $stmt = $pdo->prepare("
                SELECT id, username, display_name, avatar_color, message, msg_type, metadata,
                       DATE_FORMAT(created_at,'%H:%i') AS time_str
                FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC
            ");
            $stmt->execute([$convId]);
            jsonResponse(['success' => true, 'messages' => $stmt->fetchAll()]);

        // ─────────────────────────────────────
        // MENU ITEMS (quick reply menu)
        // ─────────────────────────────────────
        case 'menu_items':
            $pdo   = getChatDB();
            $items = $pdo->query("SELECT id, icon, label, message_text FROM chat_menu_items WHERE is_active=1 ORDER BY sort_order ASC, id ASC")->fetchAll();
            jsonResponse(['success' => true, 'items' => $items]);

        case 'widget_config':
            $pdo  = getChatDB();
            $cfg  = $pdo->query("SELECT key_name, value FROM chat_bot_config WHERE key_name IN ('bot_name','bot_color','site_logo','welcome_title','welcome_sub','bot_sub')")->fetchAll(PDO::FETCH_KEY_PAIR);
            jsonResponse(['success' => true, 'config' => $cfg]);

        // ─────────────────────────────────────
        // USER TYPING STATUS
        // ─────────────────────────────────────
        case 'typing':
            if (empty($_SESSION[CHAT_SESSION_NAME])) jsonResponse(['success' => false]);
            $user     = $_SESSION[CHAT_SESSION_NAME];
            $roomId   = (int)($_POST['room_id'] ?? 1);
            $isTyping = (int)($_POST['is_typing'] ?? 0);
            try {
                $pdo = getChatDB();
                if ($isTyping) {
                    $pdo->prepare("INSERT INTO chat_typing_status (room_id, username, display_name, is_admin) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE display_name=?, updated_at=NOW()")
                        ->execute([$roomId, $user['username'], $user['display_name'], $user['display_name']]);
                } else {
                    $pdo->prepare("DELETE FROM chat_typing_status WHERE room_id=? AND username=? AND is_admin=0")
                        ->execute([$roomId, $user['username']]);
                }
            } catch (Throwable) {}
            jsonResponse(['success' => true]);

        // ─────────────────────────────────────
        // OPERATOR PRESENCE
        // ─────────────────────────────────────
        case 'operator_presence':
            try {
                $pdo   = getChatDB();
                $avail = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE is_available=1 AND is_active=1")->fetchColumn();
                jsonResponse(['success' => true, 'available' => $avail > 0, 'count' => $avail]);
            } catch (Throwable) {
                jsonResponse(['success' => true, 'available' => false, 'count' => 0]);
            }

        // ─────────────────────────────────────
        // CSAT RATING SUBMIT
        // ─────────────────────────────────────
        case 'rate_csat':
            if (empty($_SESSION[CHAT_SESSION_NAME])) jsonResponse(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            $user    = $_SESSION[CHAT_SESSION_NAME];
            $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
            $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 500);
            $convId  = preg_replace('/[^a-f0-9]/', '', $_POST['conversation_id'] ?? ($_SESSION['conversation_id'] ?? ''));
            $roomId  = (int)($_POST['room_id'] ?? 1);
            try {
                $pdo = getChatDB();
                $pdo->prepare("INSERT INTO chat_csat_ratings (conversation_id, room_id, user_name, rating, comment) VALUES (?,?,?,?,?)")
                    ->execute([$convId, $roomId, $user['display_name'], $rating, $comment]);
                jsonResponse(['success' => true]);
            } catch (Throwable $e) {
                jsonResponse(['success' => false, 'error' => 'บันทึกไม่ได้']);
            }

        // ─────────────────────────────────────
        // CONVERSATION INFO
        // ─────────────────────────────────────
        case 'conv_info':
            $convId = preg_replace('/[^a-f0-9]/', '', $_GET['conversation_id'] ?? '');
            if (!$convId) jsonResponse(['success' => false]);
            try {
                $pdo  = getChatDB();
                $stmt = $pdo->prepare("SELECT status, bot_enabled, assigned_name FROM chat_conversation_sessions WHERE conversation_id=?");
                $stmt->execute([$convId]);
                $info = $stmt->fetch() ?: ['status' => 'open', 'bot_enabled' => 1, 'assigned_name' => null];
                jsonResponse(['success' => true, 'info' => $info]);
            } catch (Throwable) {
                jsonResponse(['success' => true, 'info' => ['status' => 'open', 'bot_enabled' => 1]]);
            }

        default:
            jsonResponse(['error' => 'Unknown action'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
