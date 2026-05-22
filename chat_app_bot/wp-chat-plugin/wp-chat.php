<?php
/**
 * Plugin Name:  PHP Chat
 * Description:  ห้องสนทนา Real-time บน WordPress — ใช้ Shortcode [php_chat] หรือ [php_chat room_id="2"]
 * Version:      1.0.0
 * Author:       Your Name
 * Text Domain:  php-chat
 */

// ── Security ──────────────────────────────────────────────────
if (!defined('ABSPATH')) { die; }

// ── Constants ─────────────────────────────────────────────────
define('PHPCHAT_VERSION',  '1.0.0');
define('PHPCHAT_DIR',      plugin_dir_path(__FILE__));
define('PHPCHAT_URL',      plugin_dir_url(__FILE__));

// ── DB Config (ใช้ WordPress database) ────────────────────────
// หรือจะกำหนด DB แยกต่างหากก็ได้ ดูที่ chat_config.php
define('PHPCHAT_API_PATH', PHPCHAT_DIR . 'chat_api.php');

// ══════════════════════════════════════════════════════════════
// ACTIVATION HOOK — สร้างตารางอัตโนมัติเมื่อ Activate Plugin
// ══════════════════════════════════════════════════════════════
register_activation_hook(__FILE__, 'phpchat_install');

function phpchat_install() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $sql_rooms = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}chat_rooms` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(100) NOT NULL,
        `description` VARCHAR(255) DEFAULT '',
        `is_public`   TINYINT(1) DEFAULT 1,
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    $sql_messages = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}chat_messages` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `room_id`      INT NOT NULL DEFAULT 1,
        `user_id`      BIGINT UNSIGNED DEFAULT NULL,
        `username`     VARCHAR(60) NOT NULL,
        `display_name` VARCHAR(100) NOT NULL,
        `avatar_color` VARCHAR(7) DEFAULT '#4ECDC4',
        `message`      TEXT NOT NULL,
        `msg_type`     ENUM('text','system') DEFAULT 'text',
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_room_created` (`room_id`, `created_at`)
    ) $charset;";

    $sql_users = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}chat_online` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`      BIGINT UNSIGNED NOT NULL,
        `display_name` VARCHAR(100) NOT NULL,
        `avatar_color` VARCHAR(7) DEFAULT '#4ECDC4',
        `last_seen`    DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_user` (`user_id`)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_rooms);
    dbDelta($sql_messages);
    dbDelta($sql_users);

    // สร้างห้องเริ่มต้น
    $wpdb->query("INSERT IGNORE INTO `{$wpdb->prefix}chat_rooms` (id, name, description)
        VALUES (1,'ห้องทั่วไป','ห้องสนทนาสาธารณะ'),
               (2,'ประกาศ','ห้องประกาศข่าวสาร'),
               (3,'ถามตอบ','ห้องถามตอบ')");
}

// ══════════════════════════════════════════════════════════════
// DEACTIVATION HOOK
// ══════════════════════════════════════════════════════════════
register_deactivation_hook(__FILE__, function() {
    // ไม่ลบข้อมูล — ลบเฉพาะตอน uninstall
});

// ══════════════════════════════════════════════════════════════
// AJAX HANDLERS (WordPress AJAX API)
// ══════════════════════════════════════════════════════════════
add_action('wp_ajax_phpchat',        'phpchat_ajax_handler');
add_action('wp_ajax_nopriv_phpchat', 'phpchat_ajax_handler');

function phpchat_ajax_handler() {
    global $wpdb;
    $action  = sanitize_text_field($_REQUEST['chat_action'] ?? '');
    $room_id = (int)($_REQUEST['room_id'] ?? 1);

    switch ($action) {

        // ─── CHECK SESSION ────────────────────────────
        case 'check_session':
            if (is_user_logged_in()) {
                $user  = wp_get_current_user();
                $color = get_user_meta($user->ID, 'phpchat_color', true) ?: phpchat_random_color();
                update_user_meta($user->ID, 'phpchat_color', $color);
                wp_send_json(['logged_in' => true, 'user' => [
                    'id'           => $user->ID,
                    'username'     => $user->user_login,
                    'display_name' => $user->display_name,
                    'avatar_color' => $color,
                ]]);
            }
            // Guest mode — ใช้ session
            @session_start();
            if (!empty($_SESSION['phpchat_user'])) {
                wp_send_json(['logged_in' => true, 'user' => $_SESSION['phpchat_user']]);
            }
            wp_send_json(['logged_in' => false]);
            break;

        // ─── LOGIN (Guest) ────────────────────────────
        case 'login':
            $display_name = sanitize_text_field($_POST['display_name'] ?? '');
            if (empty($display_name) || mb_strlen($display_name) > 30) {
                wp_send_json(['success' => false, 'error' => 'ชื่อต้องมี 1-30 ตัวอักษร']);
            }
            @session_start();
            $user = [
                'id'           => 'guest_' . uniqid(),
                'username'     => 'guest_' . substr(md5(uniqid()), 0, 8),
                'display_name' => $display_name,
                'avatar_color' => phpchat_random_color(),
            ];
            $_SESSION['phpchat_user'] = $user;

            $wpdb->insert("{$wpdb->prefix}chat_messages", [
                'room_id'      => 1,
                'username'     => 'system',
                'display_name' => 'ระบบ',
                'avatar_color' => '#888888',
                'message'      => "$display_name เข้าร่วมห้องสนทนา 👋",
                'msg_type'     => 'system',
            ]);
            wp_send_json(['success' => true, 'user' => $user]);
            break;

        // ─── LOGOUT ───────────────────────────────────
        case 'logout':
            @session_start();
            if (!empty($_SESSION['phpchat_user'])) {
                $wpdb->insert("{$wpdb->prefix}chat_messages", [
                    'room_id'      => 1,
                    'username'     => 'system',
                    'display_name' => 'ระบบ',
                    'avatar_color' => '#888888',
                    'message'      => $_SESSION['phpchat_user']['display_name'] . " ออกจากห้องสนทนา",
                    'msg_type'     => 'system',
                ]);
            }
            unset($_SESSION['phpchat_user']);
            wp_send_json(['success' => true]);
            break;

        // ─── GET MESSAGES ─────────────────────────────
        case 'messages':
            $last_id = (int)($_GET['last_id'] ?? 0);
            $limit   = 50;
            if ($last_id === 0) {
                $msgs = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, username, display_name, avatar_color, message, msg_type,
                            DATE_FORMAT(created_at,'%%H:%%i') AS time_str
                     FROM {$wpdb->prefix}chat_messages
                     WHERE room_id = %d
                     ORDER BY created_at DESC LIMIT %d",
                    $room_id, $limit
                ), ARRAY_A);
                $msgs = array_reverse($msgs);
            } else {
                $msgs = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, username, display_name, avatar_color, message, msg_type,
                            DATE_FORMAT(created_at,'%%H:%%i') AS time_str
                     FROM {$wpdb->prefix}chat_messages
                     WHERE room_id = %d AND id > %d
                     ORDER BY created_at ASC LIMIT 50",
                    $room_id, $last_id
                ), ARRAY_A);
            }
            wp_send_json(['success' => true, 'messages' => $msgs]);
            break;

        // ─── SEND MESSAGE ─────────────────────────────
        case 'send':
            // ดึงผู้ใช้จาก WP หรือ Session
            $user = phpchat_get_current_user_data();
            if (!$user) {
                wp_send_json(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบ'], 401);
            }
            $message = trim(sanitize_text_field($_POST['message'] ?? ''));
            if (empty($message) || mb_strlen($message) > 1000) {
                wp_send_json(['success' => false, 'error' => 'ข้อความไม่ถูกต้อง']);
            }
            $wpdb->insert("{$wpdb->prefix}chat_messages", [
                'room_id'      => $room_id,
                'user_id'      => is_numeric($user['id']) ? $user['id'] : null,
                'username'     => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_color' => $user['avatar_color'],
                'message'      => wp_kses($message, []),
            ]);
            wp_send_json(['success' => true, 'id' => $wpdb->insert_id]);
            break;

        // ─── ROOMS ────────────────────────────────────
        case 'rooms':
            $rooms = $wpdb->get_results(
                "SELECT id, name, description FROM {$wpdb->prefix}chat_rooms WHERE is_public=1 ORDER BY id",
                ARRAY_A
            );
            wp_send_json(['success' => true, 'rooms' => $rooms]);
            break;

        // ─── HEARTBEAT ────────────────────────────────
        case 'heartbeat':
            $user = phpchat_get_current_user_data();
            if ($user) {
                $wpdb->replace("{$wpdb->prefix}chat_online", [
                    'user_id'      => crc32($user['username']),
                    'display_name' => $user['display_name'],
                    'avatar_color' => $user['avatar_color'],
                    'last_seen'    => current_time('mysql'),
                ]);
            }
            wp_send_json(['success' => true]);
            break;

        // ─── ONLINE USERS ─────────────────────────────
        case 'online_users':
            $users = $wpdb->get_results(
                "SELECT display_name, avatar_color FROM {$wpdb->prefix}chat_online
                 WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
                 ORDER BY last_seen DESC LIMIT 50",
                ARRAY_A
            );
            wp_send_json(['success' => true, 'users' => $users, 'count' => count($users)]);
            break;

        default:
            wp_send_json(['error' => 'Unknown action'], 400);
    }
}

// ══════════════════════════════════════════════════════════════
// SHORTCODE [php_chat] หรือ [php_chat room_id="2" height="500"]
// ══════════════════════════════════════════════════════════════
add_shortcode('php_chat', 'phpchat_shortcode');

function phpchat_shortcode($atts) {
    $atts = shortcode_atts([
        'room_id' => 1,
        'height'  => 520,
        'width'   => '100%',
    ], $atts, 'php_chat');

    $room_id = (int)$atts['room_id'];
    $height  = (int)$atts['height'];
    $width   = esc_attr($atts['width']);
    $ajax_url = admin_url('admin-ajax.php');
    $nonce    = wp_create_nonce('phpchat_nonce');

    ob_start();
    ?>
    <div id="phpchat-app-<?= $room_id ?>" class="phpchat-embed"
         data-room="<?= $room_id ?>" data-ajax="<?= esc_url($ajax_url) ?>"
         data-nonce="<?= $nonce ?>"
         style="width:<?= $width ?>;height:<?= $height ?>px;">
        <!-- สร้าง UI ผ่าน JavaScript -->
    </div>
    <?php
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════
// ENQUEUE SCRIPTS & STYLES
// ══════════════════════════════════════════════════════════════
add_action('wp_enqueue_scripts', 'phpchat_enqueue');

function phpchat_enqueue() {
    global $post;
    // โหลดเฉพาะหน้าที่มี shortcode
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'php_chat')) return;

    wp_enqueue_style(
        'phpchat-style',
        PHPCHAT_URL . 'assets/chat.css',
        [], PHPCHAT_VERSION
    );
    wp_enqueue_script(
        'phpchat-script',
        PHPCHAT_URL . 'assets/chat.js',
        [], PHPCHAT_VERSION, true
    );
    wp_localize_script('phpchat-script', 'PhpChatConfig', [
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('phpchat_nonce'),
        'poll_ms'      => 2000,
        'is_logged_in' => is_user_logged_in() ? 1 : 0,
        'wp_user'      => is_user_logged_in() ? [
            'id'           => get_current_user_id(),
            'display_name' => wp_get_current_user()->display_name,
        ] : null,
    ]);
}

// ══════════════════════════════════════════════════════════════
// ADMIN MENU
// ══════════════════════════════════════════════════════════════
add_action('admin_menu', function() {
    add_menu_page(
        'PHP Chat', 'PHP Chat', 'manage_options',
        'php-chat', 'phpchat_admin_page',
        'dashicons-format-chat', 30
    );
});

function phpchat_admin_page() {
    global $wpdb;
    $msg_count  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chat_messages");
    $room_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chat_rooms");
    $online     = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chat_online WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 SECOND)");
    ?>
    <div class="wrap">
        <h1>💬 PHP Chat Dashboard</h1>
        <div style="display:flex;gap:20px;margin:20px 0;">
            <?php foreach ([
                ['ข้อความทั้งหมด', $msg_count,  '#0073aa'],
                ['ห้องสนทนา',      $room_count, '#00a32a'],
                ['ออนไลน์ตอนนี้',  $online,     '#d63638'],
            ] as [$label, $val, $color]): ?>
            <div style="background:#fff;padding:20px 30px;border-left:4px solid <?= $color ?>;border-radius:4px;min-width:150px;box-shadow:0 1px 3px rgba(0,0,0,.1);">
                <div style="font-size:32px;font-weight:700;color:<?= $color ?>"><?= (int)$val ?></div>
                <div style="color:#666;margin-top:4px;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <hr>
        <h2>วิธีใช้งาน</h2>
        <p>เพิ่ม shortcode นี้ในหน้าหรือโพสต์ที่ต้องการ:</p>
        <code style="background:#f0f0f0;padding:10px 16px;display:inline-block;border-radius:4px;font-size:15px;">
            [php_chat]
        </code>
        &nbsp;&nbsp;
        <code style="background:#f0f0f0;padding:10px 16px;display:inline-block;border-radius:4px;font-size:15px;">
            [php_chat room_id="2" height="600"]
        </code>
    </div>
    <?php
}

// ══════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════
function phpchat_get_current_user_data(): ?array {
    if (is_user_logged_in()) {
        $user  = wp_get_current_user();
        $color = get_user_meta($user->ID, 'phpchat_color', true) ?: phpchat_random_color();
        return [
            'id'           => $user->ID,
            'username'     => $user->user_login,
            'display_name' => $user->display_name,
            'avatar_color' => $color,
        ];
    }
    @session_start();
    return $_SESSION['phpchat_user'] ?? null;
}

function phpchat_random_color(): string {
    $colors = ['#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7',
               '#DDA0DD','#98D8C8','#F7DC6F','#BB8FCE','#85C1E9'];
    return $colors[array_rand($colors)];
}
