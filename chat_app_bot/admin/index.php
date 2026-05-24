<?php
header('Content-Type: text/html; charset=utf-8');
session_name('rungsit_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
session_start();
if (!empty($_SESSION['is_admin'])) {
    header('Location: ../admin.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — เทศบาลนครรังสิต</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --blue-900:#0d1b3e;--blue-800:#0f2460;--blue-700:#1035a0;
  --blue-600:#1565C0;--blue-500:#1976D2;--blue-400:#42A5F5;
  --blue-300:#90CAF9;--blue-100:#E3F2FD;
  --surface:#0d1117;--surface2:rgba(255,255,255,.06);
  --border:rgba(255,255,255,.12);--text:#e6edf3;--muted:#8b949e;
  --green:#2ea043;--red:#da3633;--orange:#f59e0b;
  --gold:#f59e0b;--gold2:#fbbf24;
}
html,body{height:100%;font-family:'Sarabun',sans-serif;background:var(--surface);color:var(--text);overflow:hidden}

/* ── BACKGROUND ─────────────────────────────── */
.bg{position:fixed;inset:0;z-index:0;overflow:hidden}
.bg-grad{
  position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 20% 50%, rgba(21,101,192,.35) 0%, transparent 60%),
    radial-gradient(ellipse 60% 80% at 80% 20%, rgba(66,165,245,.2) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 60% 80%, rgba(13,27,62,.8) 0%, transparent 60%),
    linear-gradient(135deg, #050c1a 0%, #0d1b3e 40%, #0a1628 100%);
}
.bg-grid{
  position:absolute;inset:0;
  background-image:
    linear-gradient(rgba(66,165,245,.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(66,165,245,.06) 1px, transparent 1px);
  background-size:50px 50px;
  mask-image:radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
}
.orb{position:absolute;border-radius:50%;filter:blur(80px);animation:floatOrb 12s ease-in-out infinite;pointer-events:none}
.orb1{width:500px;height:500px;background:radial-gradient(circle, rgba(21,101,192,.25) 0%, transparent 70%);top:-100px;left:-100px;animation-delay:0s}
.orb2{width:400px;height:400px;background:radial-gradient(circle, rgba(66,165,245,.15) 0%, transparent 70%);bottom:-100px;right:30%;animation-delay:-4s}
.orb3{width:300px;height:300px;background:radial-gradient(circle, rgba(245,158,11,.12) 0%, transparent 70%);top:40%;right:-50px;animation-delay:-8s}
@keyframes floatOrb{
  0%,100%{transform:translate(0,0)}
  33%{transform:translate(30px,-40px)}
  66%{transform:translate(-20px,30px)}
}

/* ── PARTICLES ──────────────────────────────── */
.particles{position:absolute;inset:0}
.particle{
  position:absolute;width:2px;height:2px;border-radius:50%;
  background:rgba(144,202,249,.6);
  animation:rise linear infinite;
}
@keyframes rise{
  0%{transform:translateY(100vh) translateX(0);opacity:0}
  10%{opacity:.8}
  90%{opacity:.4}
  100%{transform:translateY(-100px) translateX(var(--dx));opacity:0}
}

/* ── LAYOUT ─────────────────────────────────── */
#root{position:relative;z-index:1;display:flex;height:100vh;overflow:hidden}
.panel-left{flex:1;display:flex;flex-direction:column;justify-content:center;padding:60px 80px;max-width:680px;overflow:hidden}
.panel-right{width:440px;display:flex;align-items:center;justify-content:center;padding:40px;flex-shrink:0}
@media(max-width:900px){.panel-left{display:none}.panel-right{width:100%;padding:24px}}

/* ── LEFT PANEL ─────────────────────────────── */
.brand{display:flex;align-items:center;gap:16px;margin-bottom:48px}
.brand-logo{
  width:56px;height:56px;border-radius:14px;
  background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
  display:flex;align-items:center;justify-content:center;font-size:28px;
  box-shadow:0 8px 32px rgba(21,101,192,.4);flex-shrink:0;
}
.brand-text h1{font-size:20px;font-weight:800;letter-spacing:-.2px;line-height:1.2}
.brand-text p{font-size:13px;color:var(--blue-400);margin-top:3px;font-weight:500}

.hero-title{font-size:44px;font-weight:800;line-height:1.1;margin-bottom:16px;letter-spacing:-1px}
.hero-title span{
  background:linear-gradient(135deg, var(--blue-400) 0%, #93c5fd 50%, var(--gold2) 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.hero-sub{font-size:16px;color:var(--muted);line-height:1.7;max-width:480px;margin-bottom:48px;font-weight:400}

/* Stats row */
.stats-row{display:flex;gap:32px;margin-bottom:52px}
.stat-item{text-align:center}
.stat-item .num{
  font-family:'Space Mono',monospace;font-size:28px;font-weight:700;color:var(--blue-400);
  animation:countUp .8s ease both;
}
.stat-item .lbl{font-size:11px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.8px}
.stat-sep{width:1px;background:var(--border)}
@keyframes countUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* Feature list */
.features{display:flex;flex-direction:column;gap:18px}
.feat{display:flex;align-items:flex-start;gap:14px;opacity:0;animation:fadeSlide .5s ease both}
.feat:nth-child(1){animation-delay:.1s}
.feat:nth-child(2){animation-delay:.2s}
.feat:nth-child(3){animation-delay:.3s}
.feat:nth-child(4){animation-delay:.4s}
@keyframes fadeSlide{from{opacity:0;transform:translateX(-16px)}to{opacity:1;transform:translateX(0)}}
.feat-icon{
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:18px;
}
.feat-icon.blue{background:rgba(21,101,192,.2);border:1px solid rgba(66,165,245,.25)}
.feat-icon.green{background:rgba(46,160,67,.2);border:1px solid rgba(46,160,67,.25)}
.feat-icon.orange{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.25)}
.feat-icon.purple{background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.25)}
.feat-body h4{font-size:14px;font-weight:700;margin-bottom:3px}
.feat-body p{font-size:12px;color:var(--muted);line-height:1.5}

/* ── RIGHT PANEL / LOGIN CARD ───────────────── */
.login-card{
  background:rgba(13,17,23,.85);
  border:1px solid rgba(255,255,255,.1);
  border-radius:24px;width:100%;max-width:380px;
  padding:40px 36px;
  backdrop-filter:blur(24px);
  box-shadow:
    0 0 0 1px rgba(66,165,245,.08),
    0 32px 80px rgba(0,0,0,.6),
    inset 0 1px 0 rgba(255,255,255,.07);
  animation:cardIn .4s cubic-bezier(.16,1,.3,1) both;
}
@keyframes cardIn{from{opacity:0;transform:translateY(24px) scale(.97)}to{opacity:1;transform:none}}

.card-logo{
  width:64px;height:64px;border-radius:18px;margin:0 auto 20px;
  background:linear-gradient(135deg,rgba(21,101,192,.9) 0%,rgba(66,165,245,.7) 100%);
  display:flex;align-items:center;justify-content:center;font-size:32px;
  box-shadow:0 12px 40px rgba(21,101,192,.4),0 0 0 1px rgba(66,165,245,.3);
}
.card-header{text-align:center;margin-bottom:32px}
.card-header h2{font-size:22px;font-weight:800;margin-bottom:6px;letter-spacing:-.3px}
.card-header p{font-size:13px;color:var(--muted);line-height:1.5}

/* Input fields */
.field-group{margin-bottom:16px}
.field-group label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:7px;text-transform:uppercase;letter-spacing:.8px}
.input-wrap{position:relative}
.input-wrap .icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none;opacity:.5}
.field-group input{
  width:100%;padding:12px 44px 12px 42px;
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.1);
  border-radius:10px;color:var(--text);font-size:14px;font-family:'Sarabun',sans-serif;
  outline:none;transition:all .2s;
}
.field-group input:focus{
  border-color:var(--blue-400);
  background:rgba(66,165,245,.07);
  box-shadow:0 0 0 3px rgba(66,165,245,.12);
}
.field-group input::placeholder{color:rgba(139,148,158,.5)}
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:15px;padding:4px;transition:.2s;line-height:1}
.eye-btn:hover{color:var(--text)}

.submit-btn{
  width:100%;padding:13px;border-radius:10px;border:none;
  background:linear-gradient(135deg,var(--blue-600) 0%,var(--blue-500) 100%);
  color:#fff;font-size:15px;font-weight:700;font-family:'Sarabun',sans-serif;
  cursor:pointer;transition:all .2s;margin-top:8px;
  box-shadow:0 4px 20px rgba(21,101,192,.4);
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.submit-btn:hover:not(:disabled){
  background:linear-gradient(135deg,var(--blue-500) 0%,var(--blue-400) 100%);
  box-shadow:0 8px 28px rgba(21,101,192,.5);
  transform:translateY(-1px);
}
.submit-btn:active:not(:disabled){transform:translateY(0);box-shadow:0 4px 14px rgba(21,101,192,.4)}
.submit-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.submit-btn .arrow{transition:transform .2s}
.submit-btn:hover:not(:disabled) .arrow{transform:translateX(3px)}

/* Status messages */
.status-msg{
  margin-top:14px;padding:10px 14px;border-radius:8px;
  font-size:13px;text-align:center;display:none;
  animation:fadeIn .2s ease;
}
@keyframes fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.status-msg.err{background:rgba(218,54,51,.12);border:1px solid rgba(218,54,51,.3);color:#f85149}
.status-msg.ok{background:rgba(46,160,67,.12);border:1px solid rgba(46,160,67,.3);color:#3fb950}
.status-msg.lock{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:#f59e0b}

/* Lock timer */
.lock-timer{
  font-family:'Space Mono',monospace;font-size:32px;font-weight:700;
  color:var(--gold);text-align:center;margin:10px 0;display:none;
  animation:pulse 1s ease-in-out infinite;
}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}

.card-footer{margin-top:24px;text-align:center}
.card-footer a{font-size:12px;color:var(--muted);text-decoration:none;transition:.2s}
.card-footer a:hover{color:var(--blue-400)}

/* ── DIALOG ──────────────────────────────────── */
#dialog-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  backdrop-filter:blur(8px);z-index:1000;display:none;
  align-items:center;justify-content:center;animation:fadeIn .2s ease;
}
#dialog-bg.open{display:flex}
#dialog-box{
  background:#161b22;border-radius:20px;padding:36px 32px;
  text-align:center;width:300px;
  animation:cardIn .25s cubic-bezier(.16,1,.3,1);
  box-shadow:0 24px 64px rgba(0,0,0,.7);
}
#dialog-box.type-ok{border-top:3px solid var(--green)}
#dialog-box.type-err{border-top:3px solid var(--red)}
#dialog-box.type-lock{border-top:3px solid var(--gold)}
#d-icon{font-size:48px;margin-bottom:12px;line-height:1}
#d-title{font-size:17px;font-weight:800;margin-bottom:8px}
#d-msg{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:18px}
#d-timer{font-family:'Space Mono',monospace;font-size:36px;font-weight:700;color:var(--gold);margin-bottom:16px;display:none}
.d-btn{
  width:100%;padding:11px;border-radius:9px;border:none;
  font-size:14px;font-weight:700;font-family:'Sarabun',sans-serif;
  cursor:pointer;transition:.2s;
}
.d-btn-ok{background:var(--blue-600);color:#fff}
.d-btn-ok:hover{background:var(--blue-500)}
</style>
</head>
<body>

<!-- Background -->
<div class="bg">
  <div class="bg-grad"></div>
  <div class="bg-grid"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
  <div class="particles" id="particles"></div>
</div>

<!-- Root -->
<div id="root">

  <!-- LEFT PANEL -->
  <div class="panel-left">
    <div class="brand">
      <div class="brand-logo">🏛️</div>
      <div class="brand-text">
        <h1>เทศบาลนครรังสิต</h1>
        <p>Rangsit Municipality</p>
      </div>
    </div>

    <div class="hero-title">
      ระบบจัดการ<br>
      <span>Chat Bot</span><br>
      อัจฉริยะ
    </div>
    <div class="hero-sub">
      บริหารจัดการการสนทนากับประชาชนได้อย่างมีประสิทธิภาพ<br>
      ตอบคำถามอัตโนมัติ — ติดตามสถานะ — ควบคุมทุกอย่างจากที่เดียว
    </div>

    <div class="stats-row">
      <div class="stat-item">
        <div class="num" id="stat-patterns">—</div>
        <div class="lbl">Q&A Patterns</div>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-item">
        <div class="num" id="stat-msg">—</div>
        <div class="lbl">ข้อความวันนี้</div>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-item">
        <div class="num" id="stat-online">—</div>
        <div class="lbl">ออนไลน์ขณะนี้</div>
      </div>
    </div>

    <div class="features">
      <div class="feat">
        <div class="feat-icon blue">💬</div>
        <div class="feat-body">
          <h4>Chat Monitor แบบ Real-time</h4>
          <p>ติดตามทุกการสนทนา ตอบกลับเป็นเจ้าหน้าที่ได้ทันที</p>
        </div>
      </div>
      <div class="feat">
        <div class="feat-icon green">🤖</div>
        <div class="feat-body">
          <h4>Bot AI อัจฉริยะ</h4>
          <p>ตอบอัตโนมัติด้วย Pattern matching + Claude AI</p>
        </div>
      </div>
      <div class="feat">
        <div class="feat-icon orange">📊</div>
        <div class="feat-body">
          <h4>Dashboard & Analytics</h4>
          <p>วิเคราะห์สถิติ ดูรายงาน และติดตาม KPI ได้ทันที</p>
        </div>
      </div>
      <div class="feat">
        <div class="feat-icon purple">👥</div>
        <div class="feat-body">
          <h4>จัดการเจ้าหน้าที่หลายคน</h4>
          <p>ระบบ Superadmin / Staff พร้อม Role-based access</p>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="panel-right">
    <div class="login-card">
      <div class="card-logo">🏛️</div>
      <div class="card-header">
        <h2>เข้าสู่ระบบ</h2>
        <p>Admin Panel เทศบาลนครรังสิต</p>
      </div>

      <input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <div class="field-group">
        <label>ชื่อผู้ใช้</label>
        <div class="input-wrap">
          <span class="icon">👤</span>
          <input type="text" id="f-user" placeholder="username" autocomplete="username"
                 onkeydown="if(event.key==='Enter')doLogin()">
        </div>
      </div>

      <div class="field-group">
        <label>รหัสผ่าน</label>
        <div class="input-wrap">
          <span class="icon">🔑</span>
          <input type="password" id="f-pass" placeholder="••••••••" autocomplete="current-password"
                 onkeydown="if(event.key==='Enter')doLogin()">
          <button type="button" class="eye-btn" onclick="togglePass()" title="แสดง/ซ่อน">👁</button>
        </div>
      </div>

      <button id="submit-btn" class="submit-btn" onclick="doLogin()">
        <span id="btn-text">เข้าสู่ระบบ</span>
        <span class="arrow">→</span>
      </button>

      <div class="status-msg" id="status-msg"></div>
      <div class="lock-timer" id="lock-timer"></div>

      <div class="card-footer">
        <a href="../demo_wp.php" target="_blank">🌐 ดูหน้าเว็บสาธิต</a>
      </div>
    </div>
  </div>

</div>

<!-- Dialog -->
<div id="dialog-bg" onclick="if(event.target===this&&!_locked)closeDialog()">
  <div id="dialog-box">
    <div id="d-icon"></div>
    <div id="d-title"></div>
    <div id="d-msg"></div>
    <div id="d-timer"></div>
    <div id="d-actions"></div>
  </div>
</div>

<script>
// ── Particles ────────────────────────────────────────
(function(){
  const c = document.getElementById('particles');
  for (let i = 0; i < 28; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    const left = Math.random() * 100;
    const dur  = 8 + Math.random() * 14;
    const del  = Math.random() * -15;
    const dx   = (Math.random() - .5) * 120;
    p.style.cssText = `left:${left}%;bottom:0;width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;--dx:${dx}px;animation-duration:${dur}s;animation-delay:${del}s;opacity:${.3+Math.random()*.7}`;
    c.appendChild(p);
  }
})();

// ── Public stats (no auth needed) ────────────────────
(async function loadPublicStats() {
  try {
    const r = await fetch('../admin_api.php?action=public_stats');
    if (!r.ok) return;
    const d = await r.json();
    if (!d.ok) return;
    animateNum('stat-patterns', d.data.patterns_active ?? 0);
    animateNum('stat-msg',      d.data.msg_today       ?? 0);
    animateNum('stat-online',   d.data.online          ?? 0);
    if (d.data.site_logo) applyLogo(d.data.site_logo);
  } catch {}
})();

function applyLogo(url) {
  const src = '../' + url + '?v=' + Date.now();
  // card logo
  const card = document.querySelector('.card-logo');
  if (card) card.innerHTML = `<img src="${src}" alt="logo" style="width:44px;height:44px;object-fit:contain;border-radius:10px">`;
  // brand logo
  const brand = document.querySelector('.brand-logo');
  if (brand) brand.innerHTML = `<img src="${src}" alt="logo" style="width:36px;height:36px;object-fit:contain;border-radius:8px">`;
}

function animateNum(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  const dur = 1000, start = performance.now();
  const tick = (now) => {
    const t = Math.min((now - start) / dur, 1);
    const ease = 1 - Math.pow(1 - t, 3);
    el.textContent = Math.round(ease * target);
    if (t < 1) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}

// ── Login ─────────────────────────────────────────────
let _locked = false;

async function doLogin() {
  if (_locked) return;
  const user = document.getElementById('f-user').value.trim();
  const pass = document.getElementById('f-pass').value;
  const csrf = document.getElementById('csrf').value;
  const btn  = document.getElementById('submit-btn');
  const txt  = document.getElementById('btn-text');

  if (!user) { showStatus('err', '⚠️ กรุณากรอก Username'); return; }
  if (!pass) { showStatus('err', '⚠️ กรุณากรอกรหัสผ่าน'); return; }

  btn.disabled = true;
  txt.textContent = 'กำลังตรวจสอบ...';
  hideStatus();

  const fd = new FormData();
  fd.append('username', user);
  fd.append('password', pass);
  fd.append('csrf_token', csrf);

  let r;
  try {
    const res = await fetch('../admin_api.php?action=login', { method:'POST', credentials:'same-origin', body: fd });
    r = await res.json();
    r._status = res.status;
  } catch {
    r = { ok: false, error: 'ไม่สามารถเชื่อมต่อได้', _status: 0 };
  }

  btn.disabled = false;
  txt.textContent = 'เข้าสู่ระบบ';

  if (r.ok) {
    showDialog('type-ok', '✅', 'เข้าสู่ระบบสำเร็จ', `ยินดีต้อนรับ ${r.data.name}`);
    setTimeout(() => { window.location.href = '../admin.php'; }, 1200);
  } else if (r._status === 429) {
    _locked = true;
    btn.disabled = true;
    showDialog('type-lock', '🔒', 'บัญชีถูกล็อก', 'กรอกรหัสผ่านผิดเกิน 5 ครั้ง กรุณารอ:', 15);
  } else {
    showDialog('type-err', '❌', 'เข้าสู่ระบบไม่สำเร็จ', r.error || 'Username หรือรหัสผ่านไม่ถูกต้อง');
  }
}

function togglePass() {
  const i = document.getElementById('f-pass');
  i.type = i.type === 'password' ? 'text' : 'password';
}

function showStatus(type, msg) {
  const el = document.getElementById('status-msg');
  el.textContent = msg;
  el.className = 'status-msg ' + type;
  el.style.display = 'block';
}
function hideStatus() {
  document.getElementById('status-msg').style.display = 'none';
}

// ── Dialog ────────────────────────────────────────────
function showDialog(type, icon, title, msg, lockMins = 0) {
  const bg  = document.getElementById('dialog-bg');
  const box = document.getElementById('dialog-box');
  const tim = document.getElementById('d-timer');
  const act = document.getElementById('d-actions');
  box.className = `type-${type.replace('type-','')}`;
  document.getElementById('d-icon').textContent  = icon;
  document.getElementById('d-title').textContent = title;
  document.getElementById('d-msg').textContent   = msg;

  if (lockMins > 0) {
    tim.style.display = 'block';
    act.innerHTML = '';
    let secs = lockMins * 60;
    const tick = () => {
      if (secs <= 0) { _locked = false; document.getElementById('submit-btn').disabled = false; closeDialog(); return; }
      const m = Math.floor(secs/60), s = secs % 60;
      tim.textContent = `${m}:${String(s).padStart(2,'0')}`;
      secs--;
      setTimeout(tick, 1000);
    };
    tick();
  } else if (type === 'type-ok') {
    tim.style.display = 'none';
    act.innerHTML = '';
  } else {
    tim.style.display = 'none';
    act.innerHTML = '<button class="d-btn d-btn-ok" onclick="closeDialog()">ลองใหม่อีกครั้ง</button>';
  }
  bg.classList.add('open');
}
function closeDialog() {
  document.getElementById('dialog-bg').classList.remove('open');
  if (!_locked) {
    document.getElementById('f-pass').value = '';
    document.getElementById('f-pass').focus();
    hideStatus();
  }
}

// Auto-focus
document.getElementById('f-user').focus();
</script>
</body>
</html>
