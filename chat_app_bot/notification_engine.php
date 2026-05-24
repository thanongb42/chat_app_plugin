<?php
// notification_engine.php — ระบบแจ้งเตือน Real-time
// Triggers: bot fallback, นอกเวลาราชการ, ข้อความค้างไม่ได้ตอบ
// Channels: Line Notify, Webhook, Email

class NotificationEngine {

    private PDO   $db;
    private array $cfg = [];

    public function __construct(PDO $db) {
        $this->db  = $db;
        $this->loadConfig();
    }

    // ══════════════════════════════════════════════
    // PUBLIC TRIGGERS
    // ══════════════════════════════════════════════

    // เรียกเมื่อ Bot ตอบไม่ได้ (fallback)
    public function onBotFallback(string $userMsg, string $userName, int $roomId, string $roomName): void {
        if (!$this->get('notify_on_fallback', '1')) return;
        if ($this->isCooldown('fallback_' . $roomId)) return;

        $inOffice = $this->isOfficeHours();
        $icon     = $inOffice ? '🆘' : '🌙';
        $status   = $inOffice ? 'Bot ตอบไม่ได้ — รอเจ้าหน้าที่' : 'นอกเวลาราชการ + Bot ตอบไม่ได้';

        $msg = "{$icon} {$status}\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "👤 ผู้ใช้: {$userName}\n"
             . "💬 คำถาม: " . mb_substr($userMsg, 0, 120) . "\n"
             . "🏠 ห้อง: {$roomName}\n"
             . "🕐 เวลา: " . date('d/m/Y H:i') . "\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "🔗 Admin: " . $this->adminUrl();

        $this->dispatch($msg, 'fallback', $roomId, $userName, $userMsg);
    }

    // เรียกเมื่อมีข้อความเข้านอกเวลาราชการ (user ปกติ ไม่ใช่ bot fallback)
    public function onOffHoursMessage(string $userMsg, string $userName, int $roomId, string $roomName): void {
        if (!$this->get('notify_on_offhours', '1')) return;
        if ($this->isOfficeHours()) return;
        if ($this->isCooldown('offhours_' . $roomId)) return;

        [$start, $end] = $this->officeHours();
        $msg = "🌙 มีข้อความนอกเวลาราชการ\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "👤 ผู้ใช้: {$userName}\n"
             . "💬 ข้อความ: " . mb_substr($userMsg, 0, 120) . "\n"
             . "🏠 ห้อง: {$roomName}\n"
             . "🕐 เวลา: " . date('d/m/Y H:i') . "\n"
             . "ℹ️ เวลาทำการ: จ–ศ {$start}–{$end} น.\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "🔗 Admin: " . $this->adminUrl();

        $this->dispatch($msg, 'offhours', $roomId, $userName, $userMsg);
    }

    // เรียกจาก cron — ตรวจหาข้อความที่ค้างไม่ได้รับการตอบ
    public function checkUnanswered(): int {
        $minutes = (int)$this->get('notify_unanswered_min', '10');
        if ($minutes <= 0) return 0;

        // หาข้อความ user ล่าสุดในแต่ละห้องที่ยังไม่มีการตอบจาก admin_staff
        $stmt = $this->db->prepare("
            SELECT m.room_id, r.name AS room_name,
                   m.display_name, m.message, m.created_at,
                   TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) AS wait_min
            FROM chat_messages m
            JOIN chat_rooms r ON r.id = m.room_id
            WHERE m.username NOT IN ('chatbot','admin_staff','system')
              AND m.msg_type = 'text'
              AND m.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
              AND m.id = (
                  SELECT MAX(m2.id) FROM chat_messages m2
                  WHERE m2.room_id = m.room_id
                    AND m2.username NOT IN ('chatbot','admin_staff','system')
                    AND m2.msg_type = 'text'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM chat_messages m3
                  WHERE m3.room_id = m.room_id
                    AND m3.username = 'admin_staff'
                    AND m3.created_at > m.created_at
              )
            HAVING wait_min >= ?
        ");
        $stmt->execute([$minutes]);
        $rows = $stmt->fetchAll();

        $count = 0;
        foreach ($rows as $row) {
            if ($this->isCooldown('unanswered_' . $row['room_id'])) continue;

            $msg = "⏰ ข้อความรอตอบนาน {$row['wait_min']} นาที\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "👤 ผู้ใช้: {$row['display_name']}\n"
                 . "💬 ข้อความ: " . mb_substr($row['message'], 0, 120) . "\n"
                 . "🏠 ห้อง: {$row['room_name']}\n"
                 . "🕐 ส่งเมื่อ: " . date('H:i', strtotime($row['created_at'])) . "\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🔗 Admin: " . $this->adminUrl();

            $this->dispatch($msg, 'unanswered', $row['room_id'], $row['display_name'], $row['message']);
            $count++;
        }
        return $count;
    }

    // ══════════════════════════════════════════════
    // DISPATCH — ส่งไปทุก channel ที่เปิดใช้งาน
    // ══════════════════════════════════════════════
    private function dispatch(string $msg, string $triggerType, int $roomId, string $userName, string $triggerMsg): void {
        $sent = false;

        if ($this->get('line_enabled', '0') === '1' && $this->get('line_notify_token')) {
            $ok = $this->sendLine($msg);
            if ($ok) $sent = true;
        }

        if ($this->get('webhook_enabled', '0') === '1' && $this->get('webhook_url')) {
            $this->sendWebhook($msg, $triggerType, $roomId, $userName, $triggerMsg);
            $sent = true;
        }

        if ($this->get('notify_email_enabled', '0') === '1' && $this->get('notify_email')) {
            $this->sendEmail($msg, $triggerType);
            $sent = true;
        }

        $this->logNotification($triggerType, $roomId, $userName, $triggerMsg, $msg, $sent ? 'sent' : 'skipped');
        $this->setCooldown($triggerType . '_' . $roomId);
    }

    // ══════════════════════════════════════════════
    // CHANNELS
    // ══════════════════════════════════════════════

    // Line Notify
    private function sendLine(string $msg): bool {
        $token = $this->get('line_notify_token', '');
        if (!$token) return false;

        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Bearer {$token}",
            'content'       => 'message=' . urlencode("\n" . $msg),
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);

        $result = @file_get_contents('https://notify-api.line.me/api/notify', false, $ctx);
        if ($result === false) return false;
        $data = json_decode($result, true);
        return ($data['status'] ?? 0) === 200;
    }

    // Webhook (POST JSON ไปที่ URL ภายนอก)
    private function sendWebhook(string $msg, string $type, int $roomId, string $userName, string $triggerMsg): void {
        $url    = $this->get('webhook_url', '');
        $secret = $this->get('webhook_secret', '');
        if (!$url) return;

        $payload = json_encode([
            'event'       => 'chat_notification',
            'type'        => $type,
            'message'     => $msg,
            'room_id'     => $roomId,
            'user'        => $userName,
            'trigger_msg' => $triggerMsg,
            'timestamp'   => date('c'),
            'admin_url'   => $this->adminUrl(),
        ], JSON_UNESCAPED_UNICODE);

        $sig = $secret ? hash_hmac('sha256', $payload, $secret) : '';

        $headers = "Content-Type: application/json\r\n";
        if ($sig) $headers .= "X-Webhook-Signature: sha256={$sig}\r\n";

        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => $headers,
            'content'       => $payload,
            'timeout'       => 8,
            'ignore_errors' => true,
        ]]);
        @file_get_contents($url, false, $ctx);
    }

    // Email (ผ่าน PHP mail())
    private function sendEmail(string $msg, string $type): void {
        $to      = $this->get('notify_email', '');
        $subject = match($type) {
            'fallback'   => '[เทศบาล] 🆘 Bot ตอบไม่ได้ — ต้องการเจ้าหน้าที่',
            'offhours'   => '[เทศบาล] 🌙 มีข้อความนอกเวลาราชการ',
            'unanswered' => '[เทศบาล] ⏰ ข้อความรอตอบนานเกินกำหนด',
            default      => '[เทศบาล] แจ้งเตือนจาก Chat Bot',
        };
        $fromEmail = $this->get('notify_from_email', 'noreply@chatbot.local');
        $headers = "Content-Type: text/plain; charset=UTF-8\r\nFrom: $fromEmail";
        @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $msg, $headers);
    }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    public function isOfficeHours(): bool {
        $dow  = (int)date('N');
        $mins = (int)date('G') * 60 + (int)date('i');

        $days = array_map('intval', explode(',', $this->get('office_days', '1,2,3,4,5')));
        if (!in_array($dow, $days)) return false;

        [$sh, $sm] = array_map('intval', explode(':', $this->get('office_start', '08:30')));
        [$eh, $em] = array_map('intval', explode(':', $this->get('office_end',   '16:30')));

        return $mins >= ($sh * 60 + $sm) && $mins <= ($eh * 60 + $em);
    }

    private function officeHours(): array {
        return [$this->get('office_start', '08:30'), $this->get('office_end', '16:30')];
    }

    private function isCooldown(string $key): bool {
        $cooldown = (int)$this->get('notify_cooldown_min', '5');
        if ($cooldown <= 0) return false;
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM chat_notifications
            WHERE JSON_UNQUOTE(JSON_EXTRACT(sent_msg,'$')) IS NOT NULL
               OR trigger_type = ?
            HAVING COUNT(*) > 0
        ");
        // ใช้ key ตรงๆ ใน sent_msg เพื่อ cooldown per-room
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM chat_notifications
            WHERE trigger_type LIKE ?
              AND room_id = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
              AND status = 'sent'
        ");
        [$type, $roomId] = explode('_', $key, 2) + ['', 0];
        $stmt->execute([$type, (int)$roomId, $cooldown]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function setCooldown(string $key): void {
        // cooldown บันทึกผ่าน log ปกติ — ไม่ต้องเก็บแยก
    }

    private function adminUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$proto}://{$host}/chat_app/chat_app_bot/admin.php";
    }

    private function logNotification(string $type, int $roomId, string $user, string $trigger, string $sent, string $status): void {
        try {
            $this->db->prepare("
                INSERT INTO chat_notifications (trigger_type, room_id, user_name, trigger_msg, sent_msg, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$type, $roomId, $user, $trigger, $sent, $status]);
        } catch (Throwable) {}
    }

    private function loadConfig(): void {
        try {
            $this->cfg = $this->db->query("SELECT key_name, value FROM chat_bot_config")
                                   ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable) {
            $this->cfg = [];
        }
    }

    public function get(string $key, string $default = ''): string {
        return $this->cfg[$key] ?? $default;
    }

    // ตรวจสอบ config ที่จำเป็น — ใช้ใน admin
    public function getStatus(): array {
        return [
            'line_enabled'    => $this->get('line_enabled') === '1',
            'line_configured' => !empty($this->get('line_notify_token')),
            'webhook_enabled' => $this->get('webhook_enabled') === '1',
            'webhook_url'     => $this->get('webhook_url'),
            'email_enabled'   => $this->get('notify_email_enabled') === '1',
            'office_hours'    => $this->isOfficeHours(),
            'office_start'    => $this->get('office_start', '08:30'),
            'office_end'      => $this->get('office_end', '16:30'),
        ];
    }
}
