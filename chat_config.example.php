<?php
// =============================================
// chat_config.example.php
// คัดลอกไฟล์นี้เป็น chat_config.php แล้วกรอกค่าจริง
// =============================================

define('DB_HOST',     'localhost');
define('DB_USER',     'root');          // ← เปลี่ยนเป็น DB user ของคุณ
define('DB_PASS',     '');              // ← เปลี่ยนเป็น DB password ของคุณ
define('DB_NAME',     'chat_app');      // ← เปลี่ยนชื่อ database ถ้าต้องการ
define('DB_CHARSET',  'utf8mb4');

define('CHAT_MAX_MSG_LENGTH', 1000);
define('CHAT_POLL_INTERVAL',  2000);
define('CHAT_MSG_LIMIT',      50);
define('CHAT_SESSION_NAME',   'chat_user');

function getChatDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function sanitizeInput(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function randomAvatarColor(): string {
    $colors = ['#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7',
               '#DDA0DD','#98D8C8','#F7DC6F','#BB8FCE','#85C1E9'];
    return $colors[array_rand($colors)];
}
