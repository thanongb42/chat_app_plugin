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
    public function process(string $message, string $userName, int $roomId, string $convId = ''): ?array {
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
                    $this->log($roomId, $message, $userName, $aiReply, 'ai', $result['id'], $latency, $convId);
                    return ['response' => $aiReply, 'type' => 'ai'];
                }
                // AI ล้มเหลว → fallback
                $fallback = "ขออภัยครับ {$userName} ขณะนี้ระบบ AI ไม่พร้อมใช้งาน กรุณาติดต่อเจ้าหน้าที่โดยตรงครับ 🙏";
                $this->log($roomId, $message, $userName, $fallback, 'fallback', null, $latency, $convId);
                return ['response' => $fallback, 'type' => 'fallback'];
            }

            // Pattern reply ธรรมดา
            $reply = str_replace('{name}', $userName, $result['response']);
            $this->log($roomId, $message, $userName, $reply, 'pattern', $result['id'], $latency, $convId);
            $choices = null;
            if (!empty($result['choices'])) {
                $decoded = json_decode($result['choices'], true);
                if (is_array($decoded) && count($decoded)) $choices = $decoded;
            }
            return ['response' => $reply, 'type' => 'pattern', 'choices' => $choices];
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
    // PUBLIC: ตอบเมื่อรับรูปภาพ
    // ══════════════════════════════════════════
    public function processImage(string $imgPath, string $userName, int $roomId, string $convId = ''): ?array {
        if (!$this->isEnabled()) return null;

        $start  = microtime(true);
        $useAI  = ($this->config['image_use_ai'] ?? '0') === '1' && $this->isAIEnabled();

        if ($useAI) {
            $aiReply = $this->callClaudeVision($imgPath, $userName);
            if ($aiReply) {
                $latency = (int)((microtime(true) - $start) * 1000);
                $this->log($roomId, "[IMAGE:$imgPath]", $userName, $aiReply, 'ai', null, $latency, $convId);
                return ['response' => $aiReply, 'type' => 'ai'];
            }
        }

        $tpl   = $this->config['image_reply']
               ?? "ขอบคุณสำหรับรูปภาพนะครับ {name} 📷\nทีมงานจะตรวจสอบและติดต่อกลับโดยเร็วที่สุดครับ 🙏";
        $reply = str_replace('{name}', $userName, $tpl);
        $latency = (int)((microtime(true) - $start) * 1000);
        $this->log($roomId, "[IMAGE:$imgPath]", $userName, $reply, 'pattern', null, $latency, $convId);
        return ['response' => $reply, 'type' => 'pattern'];
    }

    // ══════════════════════════════════════════
    // PUBLIC: ตอบเมื่อรับตำแหน่ง
    // ══════════════════════════════════════════
    public function processLocation(array $loc, string $userName, int $roomId, string $convId = ''): ?array {
        if (!$this->isEnabled()) return null;

        $start   = microtime(true);
        $address = $this->reverseGeocode($loc['lat'], $loc['lng']);

        $tpl   = $this->config['location_reply']
               ?? "รับทราบตำแหน่งแล้วครับ {name} 📍\n{address}\nเจ้าหน้าที่จะเดินทางไปตรวจสอบโดยเร็วครับ 🙏";
        $reply = str_replace(
            ['{name}', '{address}'],
            [$userName, $address ?: "พิกัด {$loc['lat']}, {$loc['lng']}"],
            $tpl
        );
        $latency = (int)((microtime(true) - $start) * 1000);
        $this->log($roomId, "[LOCATION:{$loc['lat']},{$loc['lng']}]", $userName, $reply, 'pattern', null, $latency, $convId);
        return ['response' => $reply, 'type' => 'pattern'];
    }

    // ══════════════════════════════════════════
    // PRIVATE: Reverse geocode ผ่าน Nominatim
    // ══════════════════════════════════════════
    private function reverseGeocode(float $lat, float $lng): string {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=th";
        $ctx = stream_context_create(['http' => [
            'timeout' => 4,
            'header'  => "User-Agent: RungsitChatBot/1.0\r\nAccept-Language: th\r\n",
            'ignore_errors' => true,
        ]]);
        try {
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw) {
                $d = json_decode($raw, true);
                // ดึงเฉพาะส่วนที่อ่านง่าย (ไม่เอา postcode, country)
                $addr = $d['address'] ?? [];
                $parts = array_filter([
                    $addr['road'] ?? $addr['pedestrian'] ?? '',
                    $addr['suburb'] ?? $addr['neighbourhood'] ?? '',
                    $addr['city_district'] ?? $addr['district'] ?? '',
                    $addr['city'] ?? $addr['town'] ?? $addr['county'] ?? '',
                    $addr['state'] ?? '',
                ]);
                return implode(' ', $parts) ?: ($d['display_name'] ?? '');
            }
        } catch (Throwable) {}
        return '';
    }

    // ══════════════════════════════════════════
    // PRIVATE: Claude Vision วิเคราะห์รูปภาพ
    // ══════════════════════════════════════════
    private function callClaudeVision(string $imgPath, string $userName): ?string {
        $apiKey = $this->config['claude_api_key'] ?? '';
        $model  = $this->config['claude_model']   ?? 'claude-sonnet-4-20250514';
        $system = $this->config['ai_system_prompt']
                ?? 'คุณเป็นผู้ช่วยของเทศบาลนครรังสิต ตอบภาษาไทยอย่างสุภาพและกระชับ';

        if (empty($apiKey) || str_starts_with($apiKey, 'sk-ant-api') === false) return null;

        $fullPath = __DIR__ . '/' . $imgPath;
        if (!file_exists($fullPath) || filesize($fullPath) > 5 * 1024 * 1024) return null;

        $mime      = mime_content_type($fullPath) ?: 'image/jpeg';
        $imgBase64 = base64_encode(file_get_contents($fullPath));

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 300,
            'system'     => $system,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image', 'source' => [
                        'type'       => 'base64',
                        'media_type' => $mime,
                        'data'       => $imgBase64,
                    ]],
                    ['type' => 'text', 'text' =>
                        "ประชาชนชื่อ {$userName} ส่งรูปนี้มาเพื่อแจ้งปัญหาหรือแจ้งซ่อม " .
                        "กรุณา: 1) ขอบคุณ 2) บอกว่าเห็นอะไรในรูปสั้นๆ 3) แจ้งขั้นตอนต่อไป " .
                        "ตอบภาษาไทย 2-3 ประโยค กระชับ"
                    ],
                ],
            ]],
        ], JSON_UNESCAPED_UNICODE);

        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" .
                         "x-api-key: {$apiKey}\r\n" .
                         "anthropic-version: 2023-06-01\r\n",
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents('https://api.anthropic.com/v1/messages', false, $ctx);
        if (!$raw) return null;
        $d = json_decode($raw, true);
        return $d['content'][0]['text'] ?? null;
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
        PDO $db, int $roomId, string $botName, string $botColor, string $response,
        int $delayMs = 0, ?string $metadata = null, ?string $conversationId = null
    ): void {
        if ($delayMs > 0) usleep($delayMs * 1000);
        // If response starts with < it's a rich HTML card — render without escaping in widget
        $msgType = (!empty($response) && $response[0] === '<') ? 'rich' : 'text';
        $db->prepare("
            INSERT INTO chat_messages
              (room_id, conversation_id, username, display_name, avatar_color, message, msg_type, metadata)
            VALUES (?, ?, 'chatbot', ?, ?, ?, ?, ?)
        ")->execute([$roomId, $conversationId, $botName, $botColor, $response, $msgType, $metadata]);
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
                SELECT id, pattern, match_type, response, choices, room_id, priority, use_ai
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
    private function log(int $roomId, string $trigger, string $user, string $response,
                         string $type, ?int $patternId, int $latency, string $convId = ''): void {
        try {
            $this->db->prepare("
                INSERT INTO chat_bot_log
                  (room_id, conversation_id, trigger_msg, user_name, bot_response, response_type, pattern_id, latency_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$roomId, $convId ?: null, $trigger, $user, $response, $type, $patternId, $latency]);
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
