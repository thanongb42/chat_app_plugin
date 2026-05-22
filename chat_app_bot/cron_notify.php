<?php
// cron_notify.php — รันทุก 5 นาที เพื่อตรวจสอบข้อความที่ยังไม่ได้รับการตอบ
// ตั้งค่า Cron (Linux): */5 * * * * php /path/to/cron_notify.php
// ตั้งค่า Windows Task Scheduler: ทุก 5 นาที รัน php.exe cron_notify.php

define('CRON_MODE', true);
require_once __DIR__ . '/chat_config.php';
require_once __DIR__ . '/notification_engine.php';

$start = microtime(true);
$pdo   = getChatDB();
$notif = new NotificationEngine($pdo);

$count = $notif->checkUnanswered();

$elapsed = round((microtime(true) - $start) * 1000);
$log     = date('Y-m-d H:i:s') . " | cron_notify | unanswered_notified={$count} | {$elapsed}ms";
error_log($log);

if (php_sapi_name() === 'cli') {
    echo $log . PHP_EOL;
}
