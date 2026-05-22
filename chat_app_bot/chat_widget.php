<?php
// chat_widget.php — Compact chat UI สำหรับ floating widget
require_once __DIR__ . '/chat_config.php';
header('Content-Type: text/html; charset=utf-8');
session_name(CHAT_SESSION_NAME);
session_start();
$roomId = (int)($_GET['room_id'] ?? 1);
$roomId = max(1, $roomId);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RungsitBot</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f1923;--surface:#182030;--surface2:#1e2a3a;
  --accent:#1565C0;--accent2:#1976D2;--accentL:#42A5F5;
  --text:#e8edf5;--text2:#8fa3bc;--border:#253040;
  --bot:#1a3a5c;--user:#0d2137;--sys:#1a2030;
}
html,body{height:100%;overflow:hidden;font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text)}

/* ─── Header ─── */
#hdr{
  display:flex;align-items:center;gap:10px;
  padding:10px 14px;background:var(--accent);
  border-bottom:1px solid var(--accent2);
  position:sticky;top:0;z-index:10;
}
#hdr .bot-avatar{
  width:34px;height:34px;border-radius:50%;
  background:var(--accentL);display:flex;align-items:center;
  justify-content:center;font-size:18px;flex-shrink:0;
}
#hdr .info{flex:1;min-width:0}
#hdr .info .name{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#hdr .info .sub{font-size:10px;color:rgba(255,255,255,.75);margin-top:1px}
#hdr .online-dot{width:9px;height:9px;border-radius:50%;background:#4caf50;border:2px solid #fff;flex-shrink:0}

/* ─── Room tabs ─── */
#rooms{
  display:flex;gap:0;border-bottom:1px solid var(--border);
  overflow-x:auto;scrollbar-width:none;background:var(--surface);
}
#rooms::-webkit-scrollbar{display:none}
.room-tab{
  padding:7px 14px;font-size:11px;white-space:nowrap;cursor:pointer;
  border-bottom:2px solid transparent;color:var(--text2);transition:.2s;
  flex-shrink:0;
}
.room-tab:hover{color:var(--accentL)}
.room-tab.active{color:var(--accentL);border-bottom-color:var(--accentL);font-weight:600}

/* ─── Login overlay ─── */
#loginOverlay{
  position:absolute;inset:0;background:rgba(15,25,35,.97);
  display:flex;align-items:center;justify-content:center;
  flex-direction:column;gap:14px;padding:24px;z-index:100;
}
#loginOverlay h3{font-size:15px;color:var(--accentL);text-align:center}
#loginOverlay p{font-size:11px;color:var(--text2);text-align:center;line-height:1.5}
#nameInput{
  width:100%;padding:9px 12px;border-radius:8px;
  border:1px solid var(--accent2);background:var(--surface2);
  color:var(--text);font-size:13px;outline:none;
}
#nameInput:focus{border-color:var(--accentL)}
#loginBtn{
  width:100%;padding:9px;border-radius:8px;border:none;
  background:var(--accent);color:#fff;font-size:13px;
  font-weight:600;cursor:pointer;transition:.2s;
}
#loginBtn:hover{background:var(--accent2)}

/* ─── Messages ─── */
#msgArea{
  flex:1;overflow-y:auto;padding:10px;
  display:flex;flex-direction:column;gap:6px;
  scroll-behavior:smooth;
}
#msgArea::-webkit-scrollbar{width:4px}
#msgArea::-webkit-scrollbar-track{background:transparent}
#msgArea::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

.msg{display:flex;gap:8px;align-items:flex-end;animation:fadeIn .25s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.msg.own{flex-direction:row-reverse}
.msg.sys{justify-content:center}

.avatar{
  width:26px;height:26px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;flex-shrink:0;color:#fff;
  text-transform:uppercase;
}
.bubble{
  max-width:82%;padding:7px 11px;border-radius:12px;
  font-size:12px;line-height:1.55;word-break:break-word;
  position:relative;
}
.msg.other .bubble{background:var(--surface2);border-radius:3px 12px 12px 12px}
.msg.own   .bubble{background:var(--user);  border-radius:12px 3px 12px 12px}
.msg.bot   .bubble{background:var(--bot);   border-radius:3px 12px 12px 12px;border-left:2px solid var(--accentL)}
.msg.sys   .bubble{background:var(--sys);font-size:10px;color:var(--text2);border-radius:8px;padding:4px 10px;font-style:italic}

.msg-meta{font-size:9px;color:var(--text2);margin-top:2px;text-align:right}
.msg.other .msg-meta,.msg.bot .msg-meta{text-align:left}

.sender-name{font-size:10px;color:var(--accentL);font-weight:600;margin-bottom:2px}

/* ─── Welcome panel ─── */
#welcome-panel{
  flex:1;overflow-y:auto;display:none;flex-direction:column;align-items:center;
  padding:20px 14px 10px;
}
#welcome-panel::-webkit-scrollbar{width:3px}
#welcome-panel::-webkit-scrollbar-thumb{background:var(--border)}
.wp-hero{text-align:center;margin-bottom:18px;padding-top:4px}
.wp-logo{font-size:46px;margin-bottom:10px;filter:drop-shadow(0 2px 8px rgba(66,165,245,.3))}
.wp-title{font-size:17px;font-weight:700;color:var(--text);margin-bottom:5px}
.wp-sub{font-size:12px;color:var(--text2);line-height:1.6}
.wp-list{width:100%;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:12px;background:var(--surface)}
.wp-item{
  display:flex;align-items:center;gap:10px;width:100%;
  padding:12px 14px;border:none;border-bottom:1px solid var(--border);
  background:none;color:var(--text);font-family:inherit;font-size:12px;
  text-align:left;cursor:pointer;transition:.15s;
}
.wp-item:last-child{border-bottom:none}
.wp-item:hover{background:var(--surface2)}
.wp-item:active{background:rgba(66,165,245,.08)}
.wp-icon{font-size:17px;flex-shrink:0}
.wp-text{flex:1;line-height:1.4}
.wp-arrow{
  width:22px;height:22px;border-radius:50%;flex-shrink:0;
  background:var(--surface2);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:11px;color:var(--text2);transition:.15s;font-style:normal;
}
.wp-item:hover .wp-arrow{background:var(--accent);border-color:var(--accent);color:#fff}
.wp-chips{
  display:flex;gap:6px;width:100%;overflow-x:auto;padding:2px 0 4px;
  scrollbar-width:none;flex-wrap:nowrap;
}
.wp-chips::-webkit-scrollbar{display:none}
.wp-chip{
  padding:5px 12px;border-radius:20px;white-space:nowrap;flex-shrink:0;
  border:1px solid var(--border);background:var(--surface2);
  color:var(--text2);font-size:11px;cursor:pointer;
  transition:.15s;font-family:inherit;
}
.wp-chip:hover{border-color:var(--accentL);color:var(--accentL);background:rgba(66,165,245,.08)}
.wp-chip:active{transform:scale(.96)}

/* ─── Attachment buttons ─── */
#imgBtn,#locBtn{
  width:30px;height:30px;border-radius:50%;
  border:1px solid var(--border);background:none;
  color:var(--text2);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;font-size:15px;transition:.2s;padding:0;
}
#imgBtn:hover{color:var(--accentL);border-color:var(--accentL)}
#locBtn:hover{color:#4caf50;border-color:#4caf50}
#imgBtn:disabled,#locBtn:disabled{opacity:.4;cursor:not-allowed}

/* ─── Image preview bar ─── */
#img-preview-bar{
  display:none;align-items:center;gap:8px;
  padding:8px 10px;background:var(--surface2);
  border-top:1px solid var(--border);flex-shrink:0;
}
#img-preview-thumb{height:52px;width:52px;border-radius:6px;object-fit:cover;flex-shrink:0;border:1px solid var(--border)}
.pbar-info{flex:1;min-width:0}
.pbar-name{font-size:11px;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pbar-size{font-size:10px;color:var(--text2)}
.pbar-cancel{padding:4px 10px;border-radius:6px;font-size:11px;font-family:inherit;cursor:pointer;border:1px solid var(--border);background:none;color:var(--text2);transition:.15s}
.pbar-send{padding:4px 12px;border-radius:6px;font-size:11px;font-family:inherit;cursor:pointer;border:none;background:var(--accent);color:#fff;transition:.15s}
.pbar-cancel:hover{border-color:var(--red);color:var(--red)}
.pbar-send:hover{filter:brightness(1.1)}
.pbar-send:disabled{opacity:.5;cursor:not-allowed}

/* ─── Location card ─── */
.loc-card{display:block;text-decoration:none;padding:8px 10px;background:rgba(76,175,80,.08);border:1px solid rgba(76,175,80,.3);border-radius:8px;color:inherit;transition:.15s}
.loc-card:hover{background:rgba(76,175,80,.14)}
.loc-title{font-weight:700;font-size:12px;color:#4caf50;margin-bottom:4px}
.loc-coords{font-size:10px;color:var(--text2);line-height:1.7}
.loc-link{font-size:11px;color:var(--accentL);margin-top:5px;display:block}

/* ─── Map picker modal ─── */
#map-modal{
  position:absolute;inset:0;z-index:200;
  background:var(--bg);display:none;flex-direction:column;
}
#map-modal.open{display:flex}
#map-hdr{
  display:flex;align-items:center;padding:10px 12px;
  background:var(--surface);border-bottom:1px solid var(--border);
  flex-shrink:0;gap:10px;
}
#map-hdr .map-title{flex:1;font-size:13px;font-weight:700;color:var(--accentL)}
#map-close{width:28px;height:28px;border-radius:50%;border:1px solid var(--border);
  background:none;color:var(--text2);cursor:pointer;font-size:15px;line-height:1;
  display:flex;align-items:center;justify-content:center}
#map-close:hover{border-color:var(--red);color:var(--red)}
#map-hint{padding:4px 12px;font-size:10px;color:var(--text2);
  background:rgba(66,165,245,.06);border-bottom:1px solid var(--border);
  text-align:center;flex-shrink:0}
#map-container{flex:1;min-height:0}
#map-footer{
  padding:10px 12px;background:var(--surface);
  border-top:1px solid var(--border);flex-shrink:0;
  display:flex;align-items:center;gap:8px;
}
#map-addr-wrap{flex:1;display:flex;align-items:flex-start;gap:6px;min-width:0}
#map-addr-wrap .pin-ico{font-size:16px;flex-shrink:0;margin-top:1px}
#map-address{font-size:11px;color:var(--text2);line-height:1.45;
  overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
#map-gps-btn{
  padding:6px 10px;border-radius:6px;font-size:11px;font-family:inherit;
  cursor:pointer;border:1px solid var(--border);background:none;color:var(--text2);
  flex-shrink:0;white-space:nowrap;transition:.15s;
}
#map-gps-btn:hover{border-color:var(--accentL);color:var(--accentL)}
#map-send-btn{
  padding:8px 14px;border-radius:8px;border:none;
  background:var(--accent);color:#fff;font-size:12px;font-weight:600;
  cursor:pointer;flex-shrink:0;white-space:nowrap;transition:.15s;
}
#map-send-btn:hover{background:var(--accent2)}
#map-send-btn:disabled{opacity:.5;cursor:not-allowed}

/* ─── Choice buttons (inline after bot bubble) ─── */
.choices-wrap{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}
.choice-btn{
  padding:5px 12px;border-radius:16px;font-size:11px;cursor:pointer;
  background:rgba(66,165,245,.1);border:1px solid rgba(66,165,245,.35);
  color:var(--accentL);transition:.15s;font-family:inherit;white-space:nowrap;
}
.choice-btn:hover{background:var(--accentL);color:#0f1923;border-color:var(--accentL)}
.choice-btn:active{transform:scale(.95)}

/* ─── Menu panel ─── */
#menuPanel{
  overflow:hidden;max-height:0;
  transition:max-height .28s ease;
  background:var(--surface);
  border-top:1px solid transparent;
}
#menuPanel.open{max-height:300px;border-top-color:var(--border)}
.menu-inner{padding:10px 10px 8px}
.menu-title{font-size:10px;color:var(--text2);font-weight:600;margin-bottom:8px;letter-spacing:.4px;text-transform:uppercase}
.menu-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}
.menu-item{
  display:flex;flex-direction:column;align-items:center;gap:4px;
  padding:10px 4px 8px;border-radius:10px;cursor:pointer;
  background:var(--surface2);border:1px solid var(--border);
  transition:.15s;text-align:center;user-select:none;
}
.menu-item:hover{border-color:var(--accentL);background:rgba(66,165,245,.1)}
.menu-item:active{transform:scale(.95)}
.menu-icon{font-size:22px;line-height:1}
.menu-label{font-size:10px;color:var(--text);line-height:1.3;font-weight:500}

/* ─── Input bar ─── */
#inputBar{
  display:flex;gap:6px;padding:8px 10px;
  border-top:1px solid var(--border);background:var(--surface);
}
#menuBtn{
  width:34px;height:34px;border-radius:50%;
  border:1px solid var(--border);background:var(--surface2);
  color:var(--accentL);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;font-size:17px;transition:.2s;
}
#menuBtn:hover,#menuBtn.active{background:var(--accent);border-color:var(--accent);color:#fff}
#msgInput{
  flex:1;padding:8px 10px;border-radius:20px;
  border:1px solid var(--border);background:var(--surface2);
  color:var(--text);font-size:12px;outline:none;
  resize:none;max-height:80px;line-height:1.4;
  font-family:inherit;
}
#msgInput:focus{border-color:var(--accent2)}
#sendBtn{
  width:34px;height:34px;border-radius:50%;border:none;
  background:var(--accent);color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:.2s;
}
#sendBtn:hover{background:var(--accent2)}
#sendBtn svg{width:16px;height:16px}

/* ─── Layout wrapper ─── */
#app{display:flex;flex-direction:column;height:100%;position:relative}
</style>
</head>
<body>
<div id="app">

  <!-- Login overlay -->
  <div id="loginOverlay">
    <div style="font-size:32px">🏛️</div>
    <h3>เทศบาลนครรังสิต<br>บริการออนไลน์</h3>
    <p>กรุณาระบุชื่อของท่าน<br>เพื่อเริ่มพูดคุยกับเจ้าหน้าที่</p>
    <input id="nameInput" type="text" placeholder="ชื่อ-นามสกุล หรือชื่อเล่น" maxlength="50" autocomplete="off">
    <button id="loginBtn">เริ่มสนทนา →</button>
  </div>

  <!-- Header -->
  <div id="hdr">
    <div class="bot-avatar">🏛️</div>
    <div class="info">
      <div class="name">RungsitBot — เทศบาลนครรังสิต</div>
      <div class="sub">ตอบคำถามอัตโนมัติ · โทร 0 2567 6000</div>
    </div>
    <div class="online-dot" title="ออนไลน์"></div>
  </div>

  <!-- Room tabs -->
  <div id="rooms">
    <div class="room-tab active" data-id="1">💬 ถามตอบทั่วไป</div>
    <div class="room-tab" data-id="2">📢 ประกาศ</div>
    <div class="room-tab" data-id="3">❓ ถาม-ตอบ</div>
  </div>

  <!-- Welcome panel — shown before any messages -->
  <div id="welcome-panel">
    <div class="wp-hero">
      <div class="wp-logo">🏛️</div>
      <div class="wp-title">สวัสดีครับ 👋</div>
      <div class="wp-sub">ยินดีต้อนรับสู่บริการออนไลน์<br>เทศบาลนครรังสิต</div>
    </div>
    <div class="wp-list" id="wp-list">
      <div style="text-align:center;padding:20px;color:var(--text2);font-size:12px">กำลังโหลด...</div>
    </div>
    <div class="wp-chips" id="wp-chips"></div>
  </div>

  <!-- Messages -->
  <div id="msgArea"></div>

  <!-- Quick menu panel (slides up above input bar) -->
  <div id="menuPanel">
    <div class="menu-inner">
      <div class="menu-title">⚡ เลือกหัวข้อที่ต้องการถาม</div>
      <div class="menu-grid" id="menuGrid"></div>
    </div>
  </div>

  <!-- Map picker modal (full overlay) -->
  <div id="map-modal">
    <div id="map-hdr">
      <button id="map-close" onclick="closeMapModal()">✕</button>
      <div class="map-title">📍 เลือกตำแหน่งที่ต้องการส่ง</div>
      <button id="map-gps-btn" onclick="centerOnGPS()">🎯 GPS ฉัน</button>
    </div>
    <div id="map-hint">แตะแผนที่หรือลากหมุด 📌 เพื่อเลือกตำแหน่ง</div>
    <div id="map-container"></div>
    <div id="map-footer">
      <div id="map-addr-wrap">
        <span class="pin-ico">📍</span>
        <div id="map-address">กำลังโหลดแผนที่...</div>
      </div>
      <button id="map-send-btn" onclick="sendMapLocation()" disabled>📤 ส่งตำแหน่งนี้</button>
    </div>
  </div>

  <!-- Image preview bar -->
  <div id="img-preview-bar">
    <img id="img-preview-thumb" alt="">
    <div class="pbar-info">
      <div class="pbar-name" id="img-pname"></div>
      <div class="pbar-size" id="img-psize"></div>
    </div>
    <button class="pbar-cancel" onclick="clearImgPreview()">✕</button>
    <button class="pbar-send" id="pbar-send-btn" onclick="confirmSendImage()">📤 ส่ง</button>
  </div>

  <!-- Input -->
  <div id="inputBar">
    <input type="file" id="fileInput" accept="image/*" capture="environment" style="display:none">
    <button id="menuBtn" title="เมนูลัด">☰</button>
    <button id="imgBtn" title="แนบรูปภาพ">📷</button>
    <button id="locBtn" title="ส่งตำแหน่ง">📍</button>
    <textarea id="msgInput" rows="1" placeholder="พิมพ์คำถามที่นี่... (Enter = ส่ง)"></textarea>
    <button id="sendBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="22" y1="2" x2="11" y2="13"/>
        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
    </button>
  </div>
</div>

<script>
const API = 'chat_api.php';
let currentRoom = 1, lastId = 0, pollTimer, heartTimer, currentUser = null;
let atBottom = true;

// ─── Login ───────────────────────────────────────
document.getElementById('loginBtn').addEventListener('click', doLogin);
document.getElementById('nameInput').addEventListener('keydown', e => { if(e.key==='Enter') doLogin(); });

async function doLogin() {
  const name = document.getElementById('nameInput').value.trim();
  if (!name) { document.getElementById('nameInput').focus(); return; }
  const r = await api('login', { display_name: name });
  if (r.success) {
    currentUser = r.user;
    document.getElementById('loginOverlay').style.display = 'none';
    startChat();
  }
}

// ─── Welcome panel show/hide ──────────────────────
function showWelcome() {
  document.getElementById('welcome-panel').style.display = 'flex';
  document.getElementById('msgArea').style.display       = 'none';
}
function hideWelcome() {
  document.getElementById('welcome-panel').style.display = 'none';
  document.getElementById('msgArea').style.display       = '';
}

// ─── Init ─────────────────────────────────────────
async function startChat() {
  showWelcome();
  await loadMenuItems();
  switchRoom(currentRoom);
  heartTimer = setInterval(() => api('heartbeat', {}), 20000);
}

async function checkSession() {
  const r = await api('check_session');
  if (r.logged_in && !currentUser) {
    currentUser = r.user;
    document.getElementById('loginOverlay').style.display = 'none';
    startChat();
  }
}

async function loadWelcomeMsg() {
  // ดึง welcome message จาก room 1
}

// ─── Room switch ──────────────────────────────────
document.querySelectorAll('.room-tab').forEach(tab => {
  tab.addEventListener('click', () => switchRoom(parseInt(tab.dataset.id)));
});

function switchRoom(id) {
  currentRoom = id;
  lastId = 0;
  document.querySelectorAll('.room-tab').forEach(t => t.classList.toggle('active', parseInt(t.dataset.id) === id));
  document.getElementById('msgArea').innerHTML = '';
  showWelcome();
  clearInterval(pollTimer);
  pollMessages();
  pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
}

// ─── Polling ──────────────────────────────────────
async function pollMessages() {
  const r = await api('messages', null, { room_id: currentRoom, last_id: lastId, limit: 40 });
  if (!r.messages?.length) return;
  hideWelcome();
  const area = document.getElementById('msgArea');
  const wasBottom = area.scrollHeight - area.clientHeight - area.scrollTop < 60;
  r.messages.forEach(m => { appendMessage(m); if(m.id > lastId) lastId = m.id; });
  if (wasBottom || lastId === r.messages.at(-1)?.id) area.scrollTop = area.scrollHeight;
}

// ─── Render message ───────────────────────────────
function appendMessage(m) {
  const me = currentUser?.username;
  const isOwn = m.username === me;
  const isBot = m.username === 'chatbot';
  const isSys = m.msg_type === 'system';

  const wrap = document.createElement('div');
  wrap.className = 'msg ' + (isSys ? 'sys' : isBot ? 'bot' : isOwn ? 'own' : 'other');

  if (isSys) {
    wrap.innerHTML = `<div class="bubble">${m.message}</div>`;
  } else {
    const av = `<div class="avatar" style="background:${m.avatar_color}">${(m.display_name||'?')[0]}</div>`;
    const senderRow = !isOwn ? `<div class="sender-name">${esc(isBot ? '🤖 RungsitBot' : m.display_name)}</div>` : '';

    // render bubble content ตาม msg_type
    let bubbleContent;
    if (m.msg_type === 'image') {
      const src = esc(m.message);
      bubbleContent = `<img src="${src}" alt="รูปภาพ" loading="lazy"
        style="max-width:220px;max-height:200px;border-radius:8px;display:block;cursor:zoom-in"
        onclick="window.open('${src}','_blank')">`;
    } else if (m.msg_type === 'location') {
      try {
        const loc = JSON.parse(m.message);
        const mapUrl = `https://www.google.com/maps?q=${loc.lat},${loc.lng}&z=17`;
        bubbleContent = `<a href="${mapUrl}" target="_blank" class="loc-card">
          <div class="loc-title">📍 ตำแหน่งของฉัน</div>
          <div class="loc-coords">
            ละติจูด: ${loc.lat.toFixed(6)}<br>
            ลองจิจูด: ${loc.lng.toFixed(6)}<br>
            ความแม่นยำ: ±${Math.round(loc.acc || 0)} เมตร
          </div>
          <span class="loc-link">🗺️ เปิด Google Maps →</span>
        </a>`;
      } catch { bubbleContent = '📍 ตำแหน่ง (ข้อมูลผิดพลาด)'; }
    } else {
      bubbleContent = formatMsg(m.message);
    }

    // choices ใต้ bot message
    let choicesHtml = '';
    if (isBot && m.metadata) {
      try {
        const meta = JSON.parse(m.metadata);
        if (Array.isArray(meta?.choices) && meta.choices.length) {
          choicesHtml = `<div class="choices-wrap">${
            meta.choices.map(c =>
              `<button class="choice-btn" data-msg="${esc(c.message)}" onclick="sendMenuMsg(this.dataset.msg)">${esc(c.label)}</button>`
            ).join('')
          }</div>`;
        }
      } catch {}
    }

    wrap.innerHTML = (isOwn ? '' : av) +
      `<div>
        ${senderRow}
        <div class="bubble">${bubbleContent}</div>
        ${choicesHtml}
        <div class="msg-meta">${m.time_str||''}</div>
      </div>` +
      (isOwn ? av : '');
  }
  document.getElementById('msgArea').appendChild(wrap);
}

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatMsg(txt) {
  // escape HTML แล้วแปลง newline → <br>
  return esc(txt).replace(/\n/g,'<br>');
}

// ─── Send message ─────────────────────────────────
async function sendMessage() {
  if (!currentUser) return;
  const input = document.getElementById('msgInput');
  const msg = input.value.trim();
  if (!msg) return;
  input.value = '';
  input.style.height = 'auto';
  await api('send', { message: msg, room_id: currentRoom });
  clearInterval(pollTimer);
  pollMessages();
  pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
}

document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('msgInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
document.getElementById('msgInput').addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 80) + 'px';
});

// ─── API helper ───────────────────────────────────
async function api(action, post=null, get=null) {
  let url = API + '?action=' + action;
  if (get) url += '&' + new URLSearchParams(get);
  const opts = { method: post ? 'POST' : 'GET', credentials: 'same-origin' };
  if (post) { const fd = new FormData(); Object.entries(post).forEach(([k,v]) => fd.append(k,v)); opts.body = fd; }
  try { const r = await fetch(url, opts); return await r.json(); }
  catch { return {}; }
}

// ─── Image attachment ─────────────────────────────
let _pendingFile = null;

document.getElementById('imgBtn').addEventListener('click', () => {
  if (!currentUser) return;
  document.getElementById('fileInput').click();
});

document.getElementById('fileInput').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    alert('ไฟล์ใหญ่เกิน 5MB กรุณาเลือกรูปที่เล็กกว่า');
    this.value = ''; return;
  }
  _pendingFile = file;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('img-preview-thumb').src = e.target.result;
    document.getElementById('img-pname').textContent = file.name;
    document.getElementById('img-psize').textContent = (file.size / 1024).toFixed(0) + ' KB';
    document.getElementById('img-preview-bar').style.display = 'flex';
    if (menuOpen) toggleMenu();
  };
  reader.readAsDataURL(file);
});

function clearImgPreview() {
  _pendingFile = null;
  document.getElementById('fileInput').value = '';
  document.getElementById('img-preview-bar').style.display = 'none';
  const btn = document.getElementById('pbar-send-btn');
  btn.disabled = false; btn.textContent = '📤 ส่ง';
}

async function confirmSendImage() {
  if (!_pendingFile || !currentUser) return;
  const btn = document.getElementById('pbar-send-btn');
  btn.disabled = true; btn.textContent = '⏳';
  const fd = new FormData();
  fd.append('image', _pendingFile);
  fd.append('room_id', currentRoom);
  try {
    const resp = await fetch(API + '?action=send_image', { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await resp.json();
    if (data.success) {
      clearImgPreview();
      clearInterval(pollTimer);
      await pollMessages();
      pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
    } else {
      alert(data.error || 'อัปโหลดไม่สำเร็จ');
      btn.disabled = false; btn.textContent = '📤 ส่ง';
    }
  } catch { alert('เกิดข้อผิดพลาด กรุณาลองใหม่'); btn.disabled = false; btn.textContent = '📤 ส่ง'; }
}

// ─── Location map picker ───────────────────────────
let _map = null, _marker = null, _mapCoords = null, _addrTimer = null;
const MAP_DEFAULT = [14.0167, 100.7333]; // เทศบาลนครรังสิต

document.getElementById('locBtn').addEventListener('click', () => {
  if (!currentUser) return;
  openMapModal();
});

async function openMapModal() {
  document.getElementById('map-modal').classList.add('open');
  if (menuOpen) toggleMenu();

  // Lazy-load Leaflet from CDN
  if (!window.L) {
    await new Promise(resolve => {
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(css);
      const js = document.createElement('script');
      js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      js.onload = resolve;
      document.head.appendChild(js);
    });
    // Fix default icon paths
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
      iconUrl:       'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      shadowUrl:     'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });
  }

  // Init map once
  if (!_map) {
    _map = L.map('map-container', { zoomControl: true }).setView(MAP_DEFAULT, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(_map);
    _marker = L.marker(MAP_DEFAULT, { draggable: true }).addTo(_map);
    _marker.on('dragend', e => updatePin(e.target.getLatLng()));
    _map.on('click', e => { _marker.setLatLng(e.latlng); updatePin(e.latlng); });
  }

  setTimeout(() => _map.invalidateSize(), 50);
  centerOnGPS();
}

function closeMapModal() {
  document.getElementById('map-modal').classList.remove('open');
}

function updatePin(latlng) {
  _mapCoords = { lat: latlng.lat, lng: latlng.lng };
  document.getElementById('map-send-btn').disabled = false;
  document.getElementById('map-address').textContent =
    `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
  clearTimeout(_addrTimer);
  _addrTimer = setTimeout(() => reverseGeocode(latlng.lat, latlng.lng), 800);
}

async function reverseGeocode(lat, lng) {
  try {
    const r = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=th`,
      { headers: { 'Accept-Language': 'th' } }
    );
    const d = await r.json();
    if (d.display_name) document.getElementById('map-address').textContent = d.display_name;
  } catch {}
}

function centerOnGPS() {
  const gpsBtn = document.getElementById('map-gps-btn');
  gpsBtn.textContent = '⏳'; gpsBtn.disabled = true;
  if (!navigator.geolocation) {
    gpsBtn.textContent = '🎯 GPS ฉัน'; gpsBtn.disabled = false; return;
  }
  navigator.geolocation.getCurrentPosition(
    pos => {
      gpsBtn.textContent = '🎯 GPS ฉัน'; gpsBtn.disabled = false;
      const ll = L.latLng(pos.coords.latitude, pos.coords.longitude);
      _map.setView(ll, 17);
      _marker.setLatLng(ll);
      updatePin(ll);
    },
    () => { gpsBtn.textContent = '🎯 GPS ฉัน'; gpsBtn.disabled = false; },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

async function sendMapLocation() {
  if (!_mapCoords) return;
  const btn = document.getElementById('map-send-btn');
  btn.disabled = true; btn.textContent = '⏳';
  const r = await api('send_location', { lat: _mapCoords.lat, lng: _mapCoords.lng, accuracy: 0, room_id: currentRoom });
  btn.disabled = false; btn.textContent = '📤 ส่งตำแหน่งนี้';
  if (r.success) {
    closeMapModal();
    clearInterval(pollTimer);
    await pollMessages();
    pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
  }
}

// ─── Quick Menu ──────────────────────────────────
let menuOpen = false;

async function loadMenuItems() {
  const r = await api('menu_items');

  if (!r.items?.length) {
    document.getElementById('menuBtn').style.display = 'none';
    document.getElementById('wp-list').innerHTML =
      '<div style="text-align:center;padding:20px;color:var(--text2);font-size:12px">ยังไม่มีเมนูลัด</div>';
    return;
  }

  // ☰ grid menu (เดิม)
  document.getElementById('menuGrid').innerHTML = r.items.map(item =>
    `<div class="menu-item" data-msg="${esc(item.message_text)}" onclick="sendMenuMsg(this.dataset.msg)">
       <span class="menu-icon">${esc(item.icon)}</span>
       <span class="menu-label">${esc(item.label)}</span>
     </div>`
  ).join('');

  // Welcome panel — suggestion list (↗ arrows)
  document.getElementById('wp-list').innerHTML = r.items.map(item =>
    `<button class="wp-item" data-msg="${esc(item.message_text)}" onclick="sendMenuMsg(this.dataset.msg)">
       <span class="wp-icon">${esc(item.icon)}</span>
       <span class="wp-text">${esc(item.label)}</span>
       <i class="wp-arrow">↗</i>
     </button>`
  ).join('');

  // Welcome panel — chip row (horizontal scroll)
  document.getElementById('wp-chips').innerHTML = r.items.map(item =>
    `<button class="wp-chip" data-msg="${esc(item.message_text)}" onclick="sendMenuMsg(this.dataset.msg)">
       ${esc(item.icon)} ${esc(item.label)}
     </button>`
  ).join('');
}

function toggleMenu() {
  menuOpen = !menuOpen;
  document.getElementById('menuPanel').classList.toggle('open', menuOpen);
  document.getElementById('menuBtn').classList.toggle('active', menuOpen);
}

async function sendMenuMsg(text) {
  if (menuOpen) toggleMenu();
  const input = document.getElementById('msgInput');
  input.value = text;
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 80) + 'px';
  await sendMessage();
}

document.getElementById('menuBtn').addEventListener('click', toggleMenu);
document.getElementById('msgArea').addEventListener('click', () => { if (menuOpen) toggleMenu(); });

// ─── Check session on load ───────────────────────
window.addEventListener('DOMContentLoaded', checkSession);
</script>
</body>
</html>
