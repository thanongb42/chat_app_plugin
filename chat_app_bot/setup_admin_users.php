<?php
// setup_admin_users.php — ตั้งค่า default admin users
// Run: php setup_admin_users.php
// หรือเปิด browser: http://localhost/chat_app/chat_app_bot/setup_admin_users.php?token=setup2025

define('SETUP_TOKEN', 'setup2025'); // เปลี่ยนก่อนใช้งาน

$isCli     = PHP_SAPI === 'cli';
$isAllowed = $isCli || ($_GET['token'] ?? '') === SETUP_TOKEN;

if (!$isAllowed) {
    http_response_code(403);
    die("403 Forbidden — ต้องระบุ ?token=setup2025\n");
}

require_once __DIR__ . '/chat_config.php';

$users = [
    [
        'username'     => 'admin',
        'display_name' => 'ผู้ดูแลระบบ',
        'password'     => 'Byd@tt03',
        'role'         => 'superadmin',
        'avatar_color' => '#C62828',
    ],
];

try {
    $pdo = getChatDB();

    // สร้างตารางถ้ายังไม่มี
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id`            INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
        `username`      VARCHAR(50)     NOT NULL,
        `display_name`  VARCHAR(100)    NOT NULL,
        `password_hash` VARCHAR(255)    NOT NULL,
        `role`          ENUM('superadmin','staff') NOT NULL DEFAULT 'staff',
        `avatar_color`  VARCHAR(7)      NOT NULL DEFAULT '#1565C0',
        `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
        `last_login`    DATETIME        NULL,
        `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_username` (`username`),
        INDEX `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $results = [];
    foreach ($users as $u) {
        $hash   = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $exists = $pdo->prepare("SELECT id FROM admin_users WHERE username=?");
        $exists->execute([$u['username']]);
        $id = $exists->fetchColumn();

        if ($id) {
            $pdo->prepare("UPDATE admin_users SET display_name=?, password_hash=?, role=?, avatar_color=? WHERE id=?")
                ->execute([$u['display_name'], $hash, $u['role'], $u['avatar_color'], $id]);
            $action = 'updated';
        } else {
            $pdo->prepare("INSERT INTO admin_users (username, display_name, password_hash, role, avatar_color) VALUES (?,?,?,?,?)")
                ->execute([$u['username'], $u['display_name'], $hash, $u['role'], $u['avatar_color']]);
            $action = 'created';
        }

        $ok = password_verify($u['password'], $hash);
        $results[] = [
            'username' => $u['username'],
            'role'     => $u['role'],
            'action'   => $action,
            'verify'   => $ok ? 'PASS' : 'FAIL',
        ];
    }

    if ($isCli) {
        foreach ($results as $r) {
            echo "[{$r['action']}] {$r['username']} ({$r['role']}) — verify: {$r['verify']}\n";
        }
        echo "Done.\n";
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        foreach ($results as $r) {
            echo "[{$r['action']}] {$r['username']} ({$r['role']}) — verify: {$r['verify']}\n";
        }
        echo "Done.\n";
    }

} catch (Throwable $e) {
    $msg = "Error: " . $e->getMessage() . "\n";
    if (!$isCli) header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit(1);
}
