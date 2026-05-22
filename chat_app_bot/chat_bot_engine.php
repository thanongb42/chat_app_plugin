<?php
// =============================================
// chat_bot_engine.php — Bot Engine
// =============================================
// ใช้งาน:
//   require_once 'chat_bot_engine.php';
//   $bot = new ChatBotEngine($pdo);
//   $reply = $bot->process($message, $userName, $roomId);
//   if ($reply) { /* บันทึกลงฐานข้อมูล */ }
// =============================================

class ChatBotEngine {

    private PDO    $db;
    private array  $config   = [];
    private array  $patterns = [];

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->loadConfig();
        $this->loadPatterns();
    }

    // ══════════════════════════════════════════
    // PUBLIC: ประมวลผลข้อความ → return bot reply
    // ══════════════════════════════════════════
    public function process(string $message, string $userName, int $roomId): ?array {
        if (!$this->isEnabled()) return null;

        // ข้ามข้อความจาก bot เอง
        if (str_starts_with($message, '[BOT]')) return null;

        $start = microtime(true);

        // 1. ลอง Pattern Match ก่อน
        $result = $this->matchPattern($message, $userName, $roomId);

        if ($result) {
            $latency = (int)((microtime(true) - $start) * 1000);

            if ($result['use_ai']) {
                // Pattern บอกให้ใช้ AI
                $aiReply = $this->callAI($message, $userName);
                if ($aiReply) {
                    $this->log($roomId, $message, $userName, $aiReply, 'ai', $result['id'], $latency);
                    return ['response' => $aiReply, 'type' => 'ai'];
                }
                // AI ล้มเหลว → fallback
                $fallback = "ขออภัยครับ {$userName} ขณะนี้ระบบ AI ไม่พร้อมใช้งาน กรุณาติดต่อเจ้าหน้าที่โดยตรงครับ 🙏";
                $this->log($roomId, $message, $userName, $fallback, 'fallback', null, $latency);
                return ['response' => $fallback, 'type' => 'fallback'];
            }

            // Pattern reply ธรรมดา
            $reply = str_replace('{name}', $userName, $result['response']);
            $this->log($roomId, $message, $userName, $reply, 'pattern', $result['id'], $latency);
            return ['response' => $reply, 'type' => 'pattern'];
        }

        return null;
    }

    // ══════════════════════════════════════════
    // PATTERN MATCHING
    // ══════════════════════════════════════════
    private function matchPattern(string $message, string $userName, int $roomId): ?array {
        $msg = mb_strtolower(trim($message));

        foreach ($this->patterns as $p) {
            // กรองตาม room_id
            if ($p['room_id'] !== null && (int)$p['room_id'] !== $roomId) continue;

            $matched = false;
            $pattern = $p['pattern'];

            switch ($p['match_type']) {
                case 'keyword':
                    $matched = ($msg === mb_strtolower($pattern));
                    break;
                case 'contains':
                    $matched = str_contains($msg, mb_strtolower($pattern));
                    break;
                case 'starts':
                    $matched = str_starts_with($msg, mb_strtolower($pattern));
                    break;
                case 'ends':
                    $matched = str_ends_with($msg, mb_strtolower($pattern));
                    break;
                case 'regex':
                    $safePattern = '/' . str_replace('/', '\/', $pattern) . '/ui';
                    $matched = @preg_match($safePattern, $msg) === 1;
                    break;
            }

            if ($matched) return $p;
        }
        return null;
    }

    // ══════════════════════════════════════════
    // CALL AI (Claude / OpenAI)
    // ══════════════════════════════════════════
    private function callAI(string $message, string $userName): ?string {
        if (!$this->isAIEnabled()) return null;

        $provider = $this->config['ai_provider'] ?? 'claude';

        return match($provider) {
            'openai' => $this->callOpenAI($message, $userName),
            default  => $this->callClaude($message, $userName),
        };
    }

    // ─── Claude API ──────────────────────────
    private function callClaude(string $message, string $userName): ?string {
        $apiKey = $this->config['claude_api_key'] ?? '';
        $model  = $this->config['claude_model']   ?? 'claude-sonnet-4-20250514';
        $system = $this->config['ai_system_prompt'] ?? 'ตอบคำถามภาษาไทยอย่างสุภาพ กระชับ';

        if (empty($apiKey) || $apiKey === 'YOUR_API_KEY_HERE') return null;

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 300,
            'system'     => $system,
            'messages'   => [
                ['role' => 'user', 'content' => "ผู้ใช้ชื่อ {$userName} ถามว่า: {$message}"]
            ],
        ]);

        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ]),
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents('https://api.anthropic.com/v1/messages', false, $ctx);
        if (!$raw) return null;

        $data = json_decode($raw, true);
        return $data['content'][0]['text'] ?? null;
    }

    // ─── OpenAI API ──────────────────────────
    private function callOpenAI(string $message, string $userName): ?string {
        $apiKey = $this->config['openai_api_key'] ?? '';
        $system = $this->config['ai_system_prompt'] ?? 'ตอบคำถามภาษาไทยอย่างสุภาพ กระชับ';

        if (empty($apiKey)) return null;

        $payload = json_encode([
            'model'      => 'gpt-4o-mini',
            'max_tokens' => 300,
            'messages'   => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => "ผู้ใช้ชื่อ {$userName} ถามว่า: {$message}"],
            ],
        ]);

        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]),
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);

        $raw  = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
        if (!$raw) return null;

        $data = json_decode($raw, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    // ══════════════════════════════════════════
    // BOT ส่งข้อความลงฐานข้อมูล (static helper)
    // ══════════════════════════════════════════
    public static function insertBotMessage(
        PDO $db, int $roomId, string $botName, string $botColor, string $response, int $delayMs = 0
    ): void {
        if ($delayMs > 0) {
            // หน่วง execution โดยไม่ block process (ใช้ background job จริงๆ ควรใช้ queue)
            // ที่นี่เราใช้ usleep สำหรับ demo
            usleep($delayMs * 1000);
        }
        $stmt = $db->prepare("
            INSERT INTO chat_messages
              (room_id, username, display_name, avatar_color, message, msg_type)
            VALUES (?, 'chatbot', ?, ?, ?, 'text')
        ");
        $stmt->execute([$roomId, $botName, $botColor, $response]);
    }

    // ══════════════════════════════════════════
    // LOAD CONFIG & PATTERNS
    // ══════════════════════════════════════════
    private function loadConfig(): void {
        try {
            $rows = $this->db->query("SELECT key_name, value FROM chat_bot_config")->fetchAll(PDO::FETCH_KEY_PAIR);
            $this->config = $rows ?: [];
        } catch (Throwable) {
            $this->config = [];
        }
    }

    private function loadPatterns(): void {
        try {
            $stmt = $this->db->query("
                SELECT id, pattern, match_type, response, room_id, priority, use_ai
                FROM chat_bot_patterns
                WHERE is_active = 1
                ORDER BY priority DESC
            ");
            $this->patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            $this->patterns = [];
        }
    }

    // ══════════════════════════════════════════
    // LOG
    // ══════════════════════════════════════════
    private function log(int $roomId, string $trigger, string $user, string $response,
                         string $type, ?int $patternId, int $latency): void {
        try {
            $this->db->prepare("
                INSERT INTO chat_bot_log
                  (room_id, trigger_msg, user_name, bot_response, response_type, pattern_id, latency_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$roomId, $trigger, $user, $response, $type, $patternId, $latency]);
        } catch (Throwable) {}
    }

    // ══════════════════════════════════════════
    // GETTERS
    // ══════════════════════════════════════════
    public function isEnabled(): bool   { return ($this->config['bot_enabled'] ?? '1') === '1'; }
    public function isAIEnabled(): bool { return ($this->config['ai_enabled']  ?? '1') === '1'; }
    public function getBotName(): string  { return $this->config['bot_name']  ?? 'ChatBot 🤖'; }
    public function getBotColor(): string { return $this->config['bot_color'] ?? '#7C6AF7'; }
    public function getDelayMs(): int     { return (int)($this->config['reply_delay_ms'] ?? 800); }
    public function getConfig(): array    { return $this->config; }
}
