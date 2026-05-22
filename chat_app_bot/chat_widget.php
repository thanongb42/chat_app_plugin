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

/* ─── Input bar ─── */
#inputBar{
  display:flex;gap:6px;padding:8px 10px;
  border-top:1px solid var(--border);background:var(--surface);
}
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

  <!-- Messages -->
  <div id="msgArea"></div>

  <!-- Input -->
  <div id="inputBar">
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

// ─── Init ─────────────────────────────────────────
async function startChat() {
  await checkSession();
  await loadWelcomeMsg();
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
  clearInterval(pollTimer);
  pollMessages();
  pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
}

// ─── Polling ──────────────────────────────────────
async function pollMessages() {
  const r = await api('messages', null, { room_id: currentRoom, last_id: lastId, limit: 40 });
  if (!r.messages?.length) return;
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
    wrap.innerHTML = (isOwn ? '' : av) +
      `<div>
        ${senderRow}
        <div class="bubble">${formatMsg(m.message)}</div>
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

// ─── Check session on load ───────────────────────
window.addEventListener('DOMContentLoaded', checkSession);
</script>
</body>
</html>
