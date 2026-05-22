# PHP Chat Application
## ไฟล์ทั้งหมดในชุดนี้

```
chat_app/
├── chat_db.sql          ← รัน SQL นี้ใน phpMyAdmin เพื่อสร้างตาราง
├── chat_config.php      ← แก้ไข DB_HOST, DB_USER, DB_PASS, DB_NAME
├── chat_api.php         ← PHP API Backend
├── chat.php             ← Standalone Chat Page
└── wp-chat-plugin/
    └── wp-chat.php      ← WordPress Plugin
```

---

## ✅ ติดตั้งแบบ Standalone (ไม่ใช้ WordPress)

### 1. อัปโหลดไฟล์
```
public_html/
└── chat/
    ├── chat_config.php
    ├── chat_api.php
    └── chat.php
```

### 2. สร้างฐานข้อมูล
- เปิด phpMyAdmin
- สร้าง Database ชื่อ `chat_app`
- Import `chat_db.sql`

### 3. แก้ chat_config.php
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'chat_app');
```

### 4. เข้าใช้งาน
```
https://yourdomain.com/chat/chat.php
```

---

## ✅ ติดตั้งบน WordPress

### 1. อัปโหลด Plugin
```
wp-content/plugins/
└── php-chat/
    └── wp-chat.php
```

### 2. Activate Plugin
- ไปที่ WordPress Admin → Plugins
- เปิดใช้งาน "PHP Chat"
- ระบบจะสร้างตาราง database อัตโนมัติ

### 3. ใส่ Shortcode ในหน้าที่ต้องการ
```
[php_chat]
```
หรือกำหนด room และความสูง:
```
[php_chat room_id="1" height="600"]
[php_chat room_id="2" height="400"]
```

---

## ⚙️ Features

| Feature | Standalone | WordPress |
|---------|-----------|-----------|
| Real-time polling (2s) | ✅ | ✅ |
| หลายห้องสนทนา | ✅ | ✅ |
| Guest login | ✅ | ✅ |
| WP User integration | — | ✅ |
| Admin dashboard | — | ✅ |
| Online users | ✅ | ✅ |
| System messages | ✅ | ✅ |
| Responsive mobile | ✅ | ✅ |

---

## 🔒 Security Notes

1. **CSRF** — WordPress version ใช้ `wp_nonce` อยู่แล้ว
2. **XSS** — ใช้ `htmlspecialchars()` / `wp_kses()` ทุกที่
3. **SQL Injection** — ใช้ PDO Prepared Statements / `$wpdb->prepare()`
4. **Rate Limiting** — แนะนำเพิ่ม nginx/apache rate limit ที่ endpoint
5. **Session** — ใช้ PHP Session สำหรับ Guest, WP Auth สำหรับ Member

---

## 🛠️ ปรับแต่งเพิ่มเติม

### เปลี่ยนสี Theme
แก้ CSS variables ใน `chat.php`:
```css
:root {
  --accent:  #00D2C8;   /* สีหลัก */
  --accent2: #7C6AF7;   /* สีรอง */
  --bg:      #0D0F14;   /* พื้นหลัง */
}
```

### เพิ่ม Polling ถี่ขึ้น/ช้าลง
```php
// chat_config.php
define('CHAT_POLL_INTERVAL', 1000); // 1 วินาที (เร็วขึ้น แต่ load มากขึ้น)
define('CHAT_POLL_INTERVAL', 5000); // 5 วินาที (ช้าลง ประหยัด server)
```

### Upgrade เป็น WebSocket (Optional)
สำหรับ production ที่มีผู้ใช้เยอะ แนะนำใช้:
- **Ratchet** (PHP WebSocket library)  
- **Pusher** (Hosted WebSocket service)
- **Ably** (Hosted Realtime service)
