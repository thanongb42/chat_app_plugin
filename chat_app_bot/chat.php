<?php
// chat.php — Standalone Chat Page
session_start();
$apiUrl = './chat_api.php'; // path ไปยัง API
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PHP Chat</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
/* ─────── RESET & ROOT ─────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:        #0D0F14;
  --surface:   #161923;
  --surface2:  #1E2533;
  --border:    #252D3D;
  --accent:    #00D2C8;
  --accent2:   #7C6AF7;
  --text:      #E8EAF0;
  --text-muted:#7A839A;
  --danger:    #FF5C7C;
  --success:   #00D2C8;
  --msg-own:   #1A2544;
  --msg-other: #1E2533;
  --radius:    14px;
  --font:      'Sarabun', sans-serif;
  --mono:      'Space Mono', monospace;
}

html, body { height: 100%; overflow: hidden; background: var(--bg); color: var(--text); font-family: var(--font); }

/* ─────── LOGIN OVERLAY ─────── */
#loginOverlay {
  position: fixed; inset: 0;
  background: radial-gradient(ellipse at 30% 50%, #1a0a2e 0%, #0D0F14 70%);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
  transition: opacity .4s, visibility .4s;
}
#loginOverlay.hidden { opacity: 0; visibility: hidden; pointer-events: none; }

.login-box {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px;
  padding: 48px 40px;
  width: 380px;
  max-width: 92vw;
  text-align: center;
  box-shadow: 0 0 80px rgba(0,210,200,.08);
  animation: slideUp .5s cubic-bezier(.2,.8,.3,1) both;
}
@keyframes slideUp {
  from { opacity:0; transform: translateY(30px) scale(.96); }
  to   { opacity:1; transform: translateY(0)    scale(1); }
}

.login-logo {
  width: 64px; height: 64px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border-radius: 18px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 28px; margin-bottom: 20px;
  box-shadow: 0 8px 32px rgba(0,210,200,.25);
}
.login-box h1 { font-size: 24px; font-weight: 600; margin-bottom: 6px; }
.login-box p  { color: var(--text-muted); font-size: 14px; margin-bottom: 32px; }

.input-group { position: relative; margin-bottom: 16px; }
.input-group input {
  width: 100%; padding: 14px 18px;
  background: var(--bg); border: 1.5px solid var(--border);
  border-radius: var(--radius); color: var(--text); font-family: var(--font);
  font-size: 15px; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.input-group input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(0,210,200,.12);
}
.input-group input::placeholder { color: var(--text-muted); }

.btn-primary {
  width: 100%; padding: 14px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border: none; border-radius: var(--radius);
  color: #fff; font-family: var(--font); font-size: 16px; font-weight: 600;
  cursor: pointer; transition: opacity .2s, transform .15s;
}
.btn-primary:hover  { opacity: .9; }
.btn-primary:active { transform: scale(.98); }
.error-msg { color: var(--danger); font-size: 13px; margin-top: 10px; min-height: 18px; }

/* ─────── MAIN LAYOUT ─────── */
#app {
  display: grid;
  grid-template-columns: 240px 1fr 200px;
  grid-template-rows: 56px 1fr;
  height: 100vh;
  gap: 0;
}

/* ─────── TOP BAR ─────── */
#topBar {
  grid-column: 1 / -1;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; padding: 0 20px; gap: 14px;
}
.logo-sm {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
}
#topBar h2 { font-size: 16px; font-weight: 600; letter-spacing: .3px; }
.badge {
  background: var(--accent); color: var(--bg);
  font-size: 11px; font-weight: 700; font-family: var(--mono);
  padding: 2px 8px; border-radius: 20px; letter-spacing: .5px;
}
.spacer { flex: 1; }
#currentUser {
  font-size: 13px; color: var(--text-muted);
  display: flex; align-items: center; gap: 8px;
}
.avatar-dot {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: var(--bg);
  flex-shrink: 0;
}
.btn-logout {
  background: none; border: 1px solid var(--border);
  color: var(--text-muted); padding: 6px 14px;
  border-radius: 8px; cursor: pointer; font-size: 13px;
  font-family: var(--font);
  transition: border-color .2s, color .2s;
}
.btn-logout:hover { border-color: var(--danger); color: var(--danger); }

/* ─────── SIDEBAR (Rooms) ─────── */
#roomList {
  background: var(--surface);
  border-right: 1px solid var(--border);
  overflow-y: auto; padding: 16px 0;
}
#roomList h3 {
  font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
  color: var(--text-muted); padding: 0 16px 10px; text-transform: uppercase;
  font-family: var(--mono);
}
.room-item {
  padding: 10px 16px; cursor: pointer;
  display: flex; align-items: center; gap: 10px;
  border-left: 3px solid transparent;
  transition: background .15s, border-color .15s;
  font-size: 14px;
}
.room-item:hover    { background: rgba(255,255,255,.04); }
.room-item.active   { background: rgba(0,210,200,.06); border-left-color: var(--accent); }
.room-item.active span { color: var(--accent); }
.room-hash { color: var(--text-muted); font-family: var(--mono); font-size: 13px; }

/* ─────── CHAT MAIN ─────── */
#chatMain {
  display: flex; flex-direction: column; overflow: hidden;
}
#chatHeader {
  padding: 12px 20px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 10px;
  flex-shrink: 0;
}
#chatHeader .room-name { font-weight: 600; font-size: 15px; }
#chatHeader .room-desc { font-size: 12px; color: var(--text-muted); margin-left: 4px; }
#onlineCount {
  margin-left: auto; font-size: 12px; color: var(--text-muted);
  display: flex; align-items: center; gap: 5px;
}
.dot-green { width: 7px; height: 7px; background: var(--success); border-radius: 50%; }

/* ─────── MESSAGES ─────── */
#messages {
  flex: 1; overflow-y: auto; padding: 20px;
  display: flex; flex-direction: column; gap: 4px;
  scroll-behavior: smooth;
}
#messages::-webkit-scrollbar { width: 4px; }
#messages::-webkit-scrollbar-track { background: transparent; }
#messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.msg-group { display: flex; flex-direction: column; gap: 2px; }

.msg-item {
  display: flex; align-items: flex-end; gap: 8px;
  animation: fadeIn .25s ease both;
  max-width: 75%;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.msg-item.own { align-self: flex-end; flex-direction: row-reverse; }
.msg-item.other { align-self: flex-start; }
.msg-item.system-msg { align-self: center; max-width: 100%; }

.msg-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
  color: var(--bg); margin-bottom: 2px;
}
.no-avatar { visibility: hidden; }

.msg-bubble-wrap { display: flex; flex-direction: column; gap: 1px; }
.msg-name {
  font-size: 11px; color: var(--text-muted);
  padding: 0 4px; margin-bottom: 2px;
}
.own .msg-name { text-align: right; }

.msg-bubble {
  padding: 9px 14px;
  border-radius: 18px;
  font-size: 14.5px; line-height: 1.5;
  word-break: break-word;
  position: relative;
}
.own   .msg-bubble { background: var(--msg-own);  border-bottom-right-radius: 4px; color: var(--text); border: 1px solid #2a3a5e; }
.other .msg-bubble { background: var(--msg-other); border-bottom-left-radius: 4px; border: 1px solid var(--border); }
.msg-time {
  font-size: 10px; color: var(--text-muted);
  padding: 0 4px; font-family: var(--mono);
  align-self: flex-end;
}

.system-bubble {
  background: rgba(255,255,255,.03);
  border: 1px solid var(--border);
  padding: 6px 16px; border-radius: 20px;
  font-size: 12px; color: var(--text-muted); text-align: center;
  font-style: italic;
}

/* ─────── DATE DIVIDER ─────── */
.date-divider {
  text-align: center; position: relative; margin: 12px 0;
}
.date-divider::before {
  content: ''; position: absolute; left: 0; right: 0; top: 50%;
  border-top: 1px solid var(--border);
}
.date-divider span {
  position: relative; background: var(--bg);
  padding: 0 12px; font-size: 11px;
  color: var(--text-muted); font-family: var(--mono);
}

/* ─────── INPUT BOX ─────── */
#inputArea {
  padding: 16px 20px;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}
#inputWrap {
  display: flex; gap: 10px; align-items: flex-end;
  background: var(--surface2); border: 1.5px solid var(--border);
  border-radius: 16px; padding: 8px 8px 8px 16px;
  transition: border-color .2s;
}
#inputWrap:focus-within { border-color: var(--accent); }

#msgInput {
  flex: 1; background: none; border: none; outline: none;
  color: var(--text); font-family: var(--font); font-size: 15px;
  resize: none; max-height: 120px; line-height: 1.5;
  padding: 4px 0;
}
#msgInput::placeholder { color: var(--text-muted); }

#sendBtn {
  width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: opacity .2s, transform .15s; color: white;
}
#sendBtn:hover  { opacity: .9; }
#sendBtn:active { transform: scale(.9); }
#sendBtn svg    { width: 18px; height: 18px; fill: white; }

/* ─────── RIGHT PANEL (Online Users) ─────── */
#userPanel {
  background: var(--surface);
  border-left: 1px solid var(--border);
  overflow-y: auto; padding: 16px 0;
}
#userPanel h3 {
  font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
  color: var(--text-muted); padding: 0 16px 10px; text-transform: uppercase;
  font-family: var(--mono);
}
.online-user {
  padding: 8px 14px; display: flex; align-items: center; gap: 8px;
  font-size: 13px;
}
.online-user .avatar-dot { width: 32px; height: 32px; font-size: 13px; }
.online-indicator { width: 8px; height: 8px; background: var(--success); border-radius: 50%; flex-shrink: 0; }
.online-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ─────── SCROLLBAR ─────── */
#roomList::-webkit-scrollbar,
#userPanel::-webkit-scrollbar { width: 3px; }
#roomList::-webkit-scrollbar-thumb,
#userPanel::-webkit-scrollbar-thumb { background: var(--border); }

/* ─────── RESPONSIVE ─────── */
@media (max-width: 768px) {
  #app { grid-template-columns: 1fr; grid-template-rows: 56px 1fr; }
  #roomList, #userPanel { display: none; }
}
</style>
</head>
<body>

<!-- ─────── LOGIN OVERLAY ─────── -->
<div id="loginOverlay">
  <div class="login-box">
    <div class="login-logo">💬</div>
    <h1>PHP Chat</h1>
    <p>ป้อนชื่อที่ต้องการแสดงในห้องสนทนา</p>
    <div class="input-group">
      <input type="text" id="nameInput" placeholder="ชื่อของคุณ (เช่น John Doe)" maxlength="30" autocomplete="off">
    </div>
    <button class="btn-primary" id="loginBtn" onclick="doLogin()">เข้าสู่ห้องสนทนา →</button>
    <div class="error-msg" id="loginError"></div>
  </div>
</div>

<!-- ─────── MAIN APP ─────── -->
<div id="app">
  <!-- Top Bar -->
  <div id="topBar">
    <div class="logo-sm">💬</div>
    <h2>PHP Chat</h2>
    <span class="badge">LIVE</span>
    <div class="spacer"></div>
    <div id="currentUser">
      <div class="avatar-dot" id="myAvatar"></div>
      <span id="myName">—</span>
    </div>
    <button class="btn-logout" onclick="doLogout()">ออก</button>
  </div>

  <!-- Room List -->
  <div id="roomList">
    <h3>ห้องสนทนา</h3>
    <div id="roomItems">
      <div class="room-item active" data-id="1">
        <span class="room-hash">#</span>
        <span>ห้องทั่วไป</span>
      </div>
    </div>
  </div>

  <!-- Chat Main -->
  <div id="chatMain">
    <div id="chatHeader">
      <span class="room-hash" style="color:var(--accent);font-size:16px;">#</span>
      <span class="room-name" id="currentRoomName">ห้องทั่วไป</span>
      <span class="room-desc" id="currentRoomDesc"></span>
      <div id="onlineCount">
        <div class="dot-green"></div>
        <span id="onlineNum">0</span> ออนไลน์
      </div>
    </div>

    <div id="messages"></div>

    <div id="inputArea">
      <div id="inputWrap">
        <textarea id="msgInput" placeholder="พิมพ์ข้อความ... (Enter ส่ง, Shift+Enter ขึ้นบรรทัด)" rows="1"></textarea>
        <button id="sendBtn" onclick="sendMessage()">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Online Users -->
  <div id="userPanel">
    <h3>ออนไลน์</h3>
    <div id="onlineUserList"></div>
  </div>
</div>

<script>
// ═══════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════
const API      = '<?= $apiUrl ?>';
const POLL_MS  = 2000;

let currentUser  = null;
let currentRoom  = 1;
let lastMsgId    = 0;
let pollTimer    = null;
let heartTimer   = null;
let isAtBottom   = true;

// ═══════════════════════════════════════
// INIT
// ═══════════════════════════════════════
window.addEventListener('DOMContentLoaded', async () => {
  // ตรวจสอบ session เดิม
  try {
    const r = await api('check_session');
    if (r.logged_in) {
      currentUser = r.user;
      showApp();
    }
  } catch(e) {}

  // Enter key login
  document.getElementById('nameInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') doLogin();
  });

  // Auto-resize textarea
  const ta = document.getElementById('msgInput');
  ta.addEventListener('input', () => {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
  });
  ta.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });

  // Track scroll position
  const msgs = document.getElementById('messages');
  msgs.addEventListener('scroll', () => {
    isAtBottom = msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 50;
  });
});

// ═══════════════════════════════════════
// LOGIN / LOGOUT
// ═══════════════════════════════════════
async function doLogin() {
  const name = document.getElementById('nameInput').value.trim();
  if (!name) { setLoginError('กรุณากรอกชื่อ'); return; }
  if (name.length > 30) { setLoginError('ชื่อยาวเกิน 30 ตัวอักษร'); return; }

  document.getElementById('loginBtn').disabled = true;
  document.getElementById('loginBtn').textContent = 'กำลังเข้าสู่ระบบ...';

  try {
    const r = await api('login', { display_name: name });
    if (r.success) {
      currentUser = r.user;
      showApp();
    } else {
      setLoginError(r.error || 'เกิดข้อผิดพลาด');
    }
  } catch(e) {
    setLoginError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์');
  } finally {
    document.getElementById('loginBtn').disabled = false;
    document.getElementById('loginBtn').textContent = 'เข้าสู่ห้องสนทนา →';
  }
}

async function doLogout() {
  clearTimers();
  await api('logout').catch(() => {});
  currentUser = null;
  lastMsgId   = 0;
  document.getElementById('messages').innerHTML = '';
  document.getElementById('loginOverlay').classList.remove('hidden');
}

function setLoginError(msg) {
  document.getElementById('loginError').textContent = msg;
}

// ═══════════════════════════════════════
// SHOW APP
// ═══════════════════════════════════════
async function showApp() {
  document.getElementById('loginOverlay').classList.add('hidden');

  // แสดงชื่อผู้ใช้
  document.getElementById('myName').textContent = currentUser.display_name;
  const av = document.getElementById('myAvatar');
  av.style.background = currentUser.avatar_color;
  av.textContent = currentUser.display_name.charAt(0).toUpperCase();

  // โหลดห้อง
  await loadRooms();

  // โหลดข้อความแรก
  await loadMessages();

  // เริ่ม polling
  startPolling();

  // Focus input
  document.getElementById('msgInput').focus();
}

// ═══════════════════════════════════════
// ROOMS
// ═══════════════════════════════════════
async function loadRooms() {
  try {
    const r = await api('rooms');
    const container = document.getElementById('roomItems');
    container.innerHTML = '';
    r.rooms.forEach(room => {
      const el = document.createElement('div');
      el.className = 'room-item' + (room.id == currentRoom ? ' active' : '');
      el.dataset.id = room.id;
      el.innerHTML = `<span class="room-hash">#</span><span>${room.name}</span>`;
      el.onclick = () => switchRoom(room.id, room.name, room.description || '');
      container.appendChild(el);
    });
  } catch(e) {}
}

async function switchRoom(id, name, desc) {
  currentRoom = id; lastMsgId = 0;
  document.getElementById('currentRoomName').textContent = name;
  document.getElementById('currentRoomDesc').textContent = desc ? '— ' + desc : '';
  document.getElementById('messages').innerHTML = '';
  document.querySelectorAll('.room-item').forEach(el => {
    el.classList.toggle('active', el.dataset.id == id);
  });
  await loadMessages();
}

// ═══════════════════════════════════════
// MESSAGES
// ═══════════════════════════════════════
async function loadMessages() {
  try {
    const r = await api('messages', null, { room_id: currentRoom, last_id: 0 });
    if (r.messages && r.messages.length) {
      const box = document.getElementById('messages');
      box.innerHTML = '';
      r.messages.forEach(m => appendMessage(m));
      lastMsgId = r.messages[r.messages.length - 1].id;
      scrollToBottom(true);
    }
  } catch(e) {}
}

async function pollMessages() {
  try {
    const r = await api('messages', null, { room_id: currentRoom, last_id: lastMsgId });
    if (r.messages && r.messages.length) {
      r.messages.forEach(m => appendMessage(m));
      lastMsgId = r.messages[r.messages.length - 1].id;
      if (isAtBottom) scrollToBottom();
    }
    // อัปเดต online users
    updateOnlineUsers();
  } catch(e) {}
}

function appendMessage(m) {
  const box = document.getElementById('messages');
  const isOwn    = m.username === currentUser?.username;
  const isSystem = m.msg_type === 'system';

  if (isSystem) {
    const el = document.createElement('div');
    el.className = 'msg-item system-msg';
    el.innerHTML = `<div class="system-bubble">${m.message}</div>`;
    box.appendChild(el);
    return;
  }

  // ตรวจดูว่า message ก่อนหน้าเป็นคนเดียวกันไหม
  const prev = box.lastElementChild;
  const prevUser = prev?.dataset?.user;
  const showName  = prevUser !== m.username;
  const showAvatar = showName;

  const el = document.createElement('div');
  el.className = `msg-item ${isOwn ? 'own' : 'other'}`;
  el.dataset.user = m.username;

  const initials = m.display_name.charAt(0).toUpperCase();
  el.innerHTML = `
    <div class="msg-avatar ${showAvatar ? '' : 'no-avatar'}"
         style="background:${m.avatar_color}">${initials}</div>
    <div class="msg-bubble-wrap">
      ${showName && !isOwn ? `<div class="msg-name">${m.display_name}</div>` : ''}
      <div style="display:flex;align-items:flex-end;gap:5px;${isOwn ? 'flex-direction:row-reverse;' : ''}">
        <div class="msg-bubble">${m.message}</div>
        <div class="msg-time">${m.time_str}</div>
      </div>
    </div>
  `;
  box.appendChild(el);
}

// ═══════════════════════════════════════
// SEND MESSAGE
// ═══════════════════════════════════════
async function sendMessage() {
  const input = document.getElementById('msgInput');
  const msg   = input.value.trim();
  if (!msg || !currentUser) return;

  input.value = '';
  input.style.height = 'auto';

  try {
    await api('send', { message: msg, room_id: currentRoom });
    await pollMessages();
  } catch(e) {
    console.error('Send error', e);
  }
}

// ═══════════════════════════════════════
// ONLINE USERS
// ═══════════════════════════════════════
async function updateOnlineUsers() {
  try {
    const r = await api('online_users');
    document.getElementById('onlineNum').textContent = r.count;
    const ul = document.getElementById('onlineUserList');
    ul.innerHTML = '';
    r.users.forEach(u => {
      const el = document.createElement('div');
      el.className = 'online-user';
      el.innerHTML = `
        <div class="avatar-dot" style="background:${u.avatar_color};color:#0D0F14;font-weight:700;">
          ${u.display_name.charAt(0).toUpperCase()}
        </div>
        <div class="online-indicator"></div>
        <div class="online-name">${u.display_name}</div>
      `;
      ul.appendChild(el);
    });
  } catch(e) {}
}

// ═══════════════════════════════════════
// POLLING & HEARTBEAT
// ═══════════════════════════════════════
function startPolling() {
  clearTimers();
  pollTimer  = setInterval(pollMessages,       POLL_MS);
  heartTimer = setInterval(() => api('heartbeat'), 20000);
  updateOnlineUsers();
}
function clearTimers() {
  clearInterval(pollTimer);
  clearInterval(heartTimer);
}

// ═══════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════
async function api(action, postData = null, getParams = null) {
  let url = API + '?action=' + action;
  if (getParams) {
    Object.keys(getParams).forEach(k => url += `&${k}=${getParams[k]}`);
  }
  const opts = { method: postData ? 'POST' : 'GET', credentials: 'include' };
  if (postData) {
    const fd = new FormData();
    Object.keys(postData).forEach(k => fd.append(k, postData[k]));
    opts.body = fd;
  }
  const res = await fetch(url, opts);
  return res.json();
}

function scrollToBottom(instant = false) {
  const msgs = document.getElementById('messages');
  msgs.scrollTo({ top: msgs.scrollHeight, behavior: instant ? 'auto' : 'smooth' });
}
</script>
</body>
</html>
