# CLAUDE.md — เทศบาลนครรังสิต Chat Bot

## Project Overview
Chat bot สำหรับเว็บไซต์เทศบาลนครรังสิต — PHP + MySQL + Vanilla JS บน XAMPP/Windows
ไม่ใช้ Framework ไม่ใช้ npm ไม่ใช้ composer — ทุกอย่าง built-in PHP

---

## Architecture

```
chat_app_bot/
├── chat_config.php          # DB config + helper functions
├── chat_api.php             # User REST API  (session: chat_user)
├── chat_bot_engine.php      # Bot pattern matching + AI fallback
├── notification_engine.php  # Line Notify + Webhook + office-hours check
├── webhook.php              # Inbound webhook จาก external apps
├── admin_api.php            # Admin REST API (session: rungsit_admin)
├── admin.php                # Admin Panel UI
├── chat_widget.php          # Floating chat UI (iframe)
├── demo_wp.php              # Mock WordPress page
└── chat_bot_admin.php       # Legacy bot admin (patterns/config/log)
```

### Request Flow
```
ประชาชน → chat_widget.php (iframe)
         → chat_api.php?action=send
         → chat_bot_engine.php::process()
              ├─ pattern match → reply
              ├─ AI fallback   → Claude/OpenAI API
              └─ no match      → notification_engine.php → Line Notify
```

---

## Engineering Standards

### PHP Rules
- PDO prepared statements ทุก query — ห้าม string interpolation ใน SQL
- `session_name()` ก่อน `session_start()` เสมอ
- `header('Content-Type: ...; charset=utf-8')` ทุกไฟล์
- `json_encode(..., JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)`
- ไม่ `htmlspecialchars()` ก่อน INSERT — escape เฉพาะตอน output
- Error logging ด้วย `error_log()` ไม่ echo error สู่ user

### Database Rules
```bash
# ทุก SQL import ต้องระบุ charset
mysql -u root --default-character-set=utf8mb4 chat_app < file.sql

# Thai ใน -e flag จะพัง — เขียนเป็นไฟล์เสมอ
mysql -u root --default-character-set=utf8mb4 chat_app < update_something.sql
```
- charset: `utf8mb4` collation: `utf8mb4_unicode_ci` ทุกตาราง
- Foreign key ใช้ `ON DELETE CASCADE` หรือ `SET NULL` อย่างชัดเจน
- Index ทุก column ที่ query บ่อย (room_id, created_at, response_type)

### JavaScript Rules
- Vanilla JS ไม่มี jQuery/React
- `esc(s)` ก่อน innerHTML ทุกครั้ง
- `formatMsg(txt)` = `esc(txt).replace(/\n/g,'<br>')`
- Polling ด้วย `setInterval` — ต้อง `clearInterval` ก่อน switch room
- `credentials: 'same-origin'` ทุก fetch call

### Session Architecture
| Session Name | ใช้ใน | Key ใน $_SESSION |
|---|---|---|
| `chat_user` | chat_api.php, chat_widget.php | `chat_user` |
| `rungsit_admin` | admin.php, admin_api.php | `is_admin` |

---

## Design Standards

### Color Palette (Municipality Blue)
```css
--accent:   #1565C0   /* Primary blue */
--accentL:  #42A5F5   /* Light blue */
--bg:       #0d1117   /* Dark background */
--surface:  #161b22   /* Card surface */
--border:   #30363d   /* Borders */
--text:     #e6edf3   /* Primary text */
--muted:    #8b949e   /* Secondary text */
--green:    #2ea043   /* Success */
--red:      #da3633   /* Danger/Alert */
--orange:   #d29922   /* Warning */
```

### Typography
- **UI Font**: Segoe UI, Tahoma, sans-serif
- **Thai Font**: Sarabun (admin), Segoe UI fallback (widget)
- **Code/Numbers**: Space Mono

### Component Patterns
- **Floating widget**: right: 24px, bottom: 24px, z-index: 9000
- **Chat bubble**: user = ซ้าย/surface2, admin = ขวา/dark-blue, bot = ซ้าย/blue-tint
- **Admin badge แดง**: แสดงเมื่อมี unread/unanswered
- **Toast**: bottom-right, auto-dismiss 3s
- **Modal**: backdrop blur, max-width 680px, border-radius 12px

### Responsive Breakpoint
- Mobile (<768px): ซ่อน sidebar, chat panel เต็มจอ
- Desktop: 3-column (sidebar 220px | content | chat 230px)

---

## Security Standards

### Input Validation
```php
// ทุก user input ผ่าน sanitize ก่อน
$msg = trim($_POST['message'] ?? '');
if (mb_strlen($msg) > CHAT_MAX_MSG_LENGTH) fail('ยาวเกินไป');
// เก็บ raw ใน DB — escape เฉพาะตอน output ใน JS ด้วย esc()
```

### Admin Protection
- Admin session แยกจาก user session ชัดเจน
- `auth()` function ตรวจสอบก่อนทุก admin_api action
- Password เก็บใน `chat_bot_config` (ควร hash ใน production)
- ไม่ expose API Key ใน client-side code

### Rate Limiting (ควร implement)
```php
// ใน chat_api.php action=send
$ip = $_SERVER['REMOTE_ADDR'];
// ตรวจสอบ request count ต่อ IP ต่อ minute
// ใช้ APCu หรือ Redis ถ้ามี
```

### CSRF Protection (Admin)
- Admin form ควรมี CSRF token
- ปัจจุบัน rely on session — ควรเพิ่ม nonce ใน production

### Webhook Security
- ทุก inbound webhook ต้องมี signature verification
- ใช้ `hash_hmac('sha256', $body, $secret)` เปรียบเทียบ header
- IP whitelist สำหรับ trusted sources

### Headers ที่ควรเพิ่มใน production (.htaccess)
```apache
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### File ที่ต้อง protect ด้วย .htaccess
```apache
<Files "chat_config.php">
    Require all denied
</Files>
<Files "*.sql">
    Require all denied
</Files>
```

---

## Notification System

### Trigger Conditions
1. **Bot Fallback** — pattern ไม่ match, ส่งให้ AI แต่ AI ล้มเหลว
2. **นอกเวลาราชการ** — จ-ศ 08:30–16:30 (วันหยุดปิดทั้งวัน)
3. **ไม่มี admin ตอบ** — ข้อความค้างนาน > N นาที (cron check)

### Channels
| Channel | Config Key | หมายเหตุ |
|---------|-----------|---------|
| Line Notify | `line_notify_token` | https://notify-bot.line.me |
| Webhook | `webhook_url` + `webhook_secret` | POST JSON |
| Email | `notify_email` | ผ่าน mail() |

### Office Hours Logic
```php
$dow  = (int)date('N');          // 1=จันทร์ 7=อาทิตย์
$mins = (int)date('G')*60 + (int)date('i');
$isWorkday  = $dow <= 5;
$isWorkTime = $mins >= (8*60+30) && $mins <= (16*60+30);
$inOffice   = $isWorkday && $isWorkTime;
```

---

## External Integration

### Line Notify (ส่งออก)
```
POST https://notify-api.line.me/api/notify
Header: Authorization: Bearer {token}
Body:   message=ข้อความ
```

### Webhook Inbound (รับเข้า)
```
POST /webhook.php
Header: X-Webhook-Secret: {secret}
Body:   JSON { action, data }
```

### Supported Actions (inbound)
- `send_message` — ส่งข้อความลงห้อง
- `get_stats` — ดึงสถิติ
- `add_pattern` — เพิ่ม Q&A pattern

---

## Deployment Checklist
- [ ] เปลี่ยน admin password จาก `admin1234`
- [ ] ตั้งค่า Line Notify token
- [ ] เพิ่ม .htaccess protect *.sql และ chat_config.php
- [ ] ตั้งค่า Claude/OpenAI API key สำหรับ AI fallback
- [ ] ทดสอบ notification ส่งถึง Line group
- [ ] ตั้ง cron job รัน cron_notify.php ทุก 5 นาที
- [ ] เปิด HTTPS บน production server
