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

            $pdo = getChatDB();
            $stmt = $pdo->prepare("
                INSERT INTO chat_users (username, display_name, avatar_color, is_online, last_seen)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$username, $displayName, $color]);
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

            jsonResponse(['success' => true, 'user' => $_SESSION[CHAT_SESSION_NAME]]);

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
                jsonResponse(['logged_in' => true, 'user' => $_SESSION[CHAT_SESSION_NAME]]);
            }
            jsonResponse(['logged_in' => false]);

        // ─────────────────────────────────────
        // GET MESSAGES
        // ─────────────────────────────────────
        case 'messages':
            $roomId = (int)($_GET['room_id'] ?? 1);
            $lastId = (int)($_GET['last_id'] ?? 0);
            $limit  = (int)($_GET['limit']   ?? CHAT_MSG_LIMIT);
            $limit  = min($limit, 100);

            $pdo = getChatDB();

            if ($lastId === 0) {
                // โหลดข้อความล่าสุด
                $stmt = $pdo->prepare("
                    SELECT id, username, display_name, avatar_color, message, msg_type,
                           DATE_FORMAT(created_at, '%H:%i') AS time_str,
                           created_at
                    FROM chat_messages
                    WHERE room_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$roomId, $limit]);
                $msgs = array_reverse($stmt->fetchAll());
            } else {
                // Polling — ดึงข้อความใหม่หลัง last_id
                $stmt = $pdo->prepare("
                    SELECT id, username, display_name, avatar_color, message, msg_type,
                           DATE_FORMAT(created_at, '%H:%i') AS time_str,
                           created_at
                    FROM chat_messages
                    WHERE room_id = ? AND id > ?
                    ORDER BY created_at ASC
                    LIMIT 50
                ");
                $stmt->execute([$roomId, $lastId]);
                $msgs = $stmt->fetchAll();
            }

            jsonResponse(['success' => true, 'messages' => $msgs]);

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

            $pdo = getChatDB();
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (room_id, user_id, username, display_name, avatar_color, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $roomId,
                $user['id'],
                $user['username'],
                $user['display_name'],
                $user['avatar_color'],
                $message,
            ]);
            $newId = $pdo->lastInsertId();

            // ─── BOT + NOTIFICATION ───────────────────────────────
            try {
                require_once __DIR__ . '/chat_bot_engine.php';
                require_once __DIR__ . '/notification_engine.php';

                $bot    = new ChatBotEngine($pdo);
                $notif  = new NotificationEngine($pdo);
                $result = $bot->process($message, $user['display_name'], $roomId);

                // ดึงชื่อห้อง
                $roomName = $pdo->prepare("SELECT name FROM chat_rooms WHERE id=?");
                $roomName->execute([$roomId]);
                $roomName = $roomName->fetchColumn() ?: "ห้อง #{$roomId}";

                if ($result) {
                    ChatBotEngine::insertBotMessage(
                        $pdo, $roomId,
                        $bot->getBotName(),
                        $bot->getBotColor(),
                        $result['response'],
                        $bot->getDelayMs()
                    );
                    // Bot fallback → แจ้งเตือน
                    if ($result['type'] === 'fallback') {
                        $notif->onBotFallback($message, $user['display_name'], $roomId, $roomName);
                    }
                } else {
                    // ไม่มี bot reply เลย → แจ้งเตือน off-hours ถ้านอกเวลา
                    $notif->onOffHoursMessage($message, $user['display_name'], $roomId, $roomName);
                }
            } catch (Throwable $e) {
                error_log('ChatBot/Notification error: ' . $e->getMessage());
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

        default:
            jsonResponse(['error' => 'Unknown action'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
