<?php
header('Content-Type: text/html; charset=utf-8');
session_name('rungsit_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$isAdmin   = !empty($_SESSION['is_admin']);
$adminName = $_SESSION['admin_name'] ?? '';
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — เทศบาลนครรังสิต</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d1117;--s:#161b22;--s2:#21262d;--s3:#30363d;
  --border:#30363d;--accent:#1565C0;--accentL:#42A5F5;
  --green:#2ea043;--red:#da3633;--orange:#d29922;--purple:#8b5cf6;
  --text:#e6edf3;--muted:#8b949e;--r:8px;
}
html,body{height:100%;overflow:hidden;font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);font-size:14px}

/* ══ LAYOUT ══════════════════════════════════════ */
#app{display:flex;height:100vh}
#sidebar{width:220px;background:var(--s);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0}
#main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* ══ SIDEBAR ═════════════════════════════════════ */
.sb-head{padding:16px;border-bottom:1px solid var(--border)}
.sb-head .logo{font-size:15px;font-weight:700;color:var(--accentL)}
.sb-head .sub{font-size:11px;color:var(--muted);margin-top:2px}
.sb-nav{flex:1;padding:8px 0;overflow-y:auto}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 16px;cursor:pointer;color:var(--muted);font-size:13px;border-left:3px solid transparent;transition:.15s;position:relative}
.nav-item:hover{color:var(--text);background:var(--s2)}
.nav-item.active{color:var(--accentL);border-left-color:var(--accentL);background:rgba(66,165,245,.08);font-weight:600}
.nav-item .badge{margin-left:auto;background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700}
.nav-sep{height:1px;background:var(--border);margin:6px 12px}
.sb-foot{padding:12px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.sb-foot .admin-name{color:var(--text);font-weight:600;margin-bottom:4px}
.logout-btn{color:var(--red);cursor:pointer;font-size:12px;background:none;border:none;padding:0}
.logout-btn:hover{text-decoration:underline}

/* ══ TOP BAR ═════════════════════════════════════ */
#topbar{background:var(--s);border-bottom:1px solid var(--border);padding:10px 20px;display:flex;align-items:center;gap:12px;flex-shrink:0}
#topbar h2{font-size:15px;font-weight:700;flex:1}
.refresh-info{font-size:11px;color:var(--muted)}
.online-dot{width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;margin-right:4px}

/* ══ CONTENT ═════════════════════════════════════ */
#content{flex:1;overflow:hidden;display:flex;flex-direction:column}
.tab-panel{display:none;flex:1;overflow:hidden;flex-direction:column}
.tab-panel.active{display:flex}
.scroll-area{flex:1;overflow-y:auto;padding:20px}
.scroll-area::-webkit-scrollbar{width:6px}
.scroll-area::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}

/* ══ STAT CARDS ══════════════════════════════════ */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat-card{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:16px 18px;border-top:3px solid}
.stat-num{font-size:28px;font-weight:700;line-height:1}
.stat-lbl{font-size:11px;color:var(--muted);margin-top:4px}
.stat-sub{font-size:10px;color:var(--muted);margin-top:2px}

/* ══ CARDS ═══════════════════════════════════════ */
.card{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:18px;margin-bottom:16px}
.card-title{font-size:14px;font-weight:700;margin-bottom:14px;color:var(--accentL)}

/* ══ TABLE ═══════════════════════════════════════ */
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;padding:9px 12px;background:var(--s2);color:var(--muted);font-size:11px;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap}
.tbl td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.tbl tr:last-child td{border:none}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.tbl .inactive td{opacity:.4}

/* ══ BADGE ═══════════════════════════════════════ */
.badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600}
.badge-pattern{background:rgba(46,160,67,.15);color:#3fb950}
.badge-ai{background:rgba(139,92,246,.15);color:#a78bfa}
.badge-fallback{background:rgba(218,54,51,.15);color:#f85149}
.badge-regex{background:rgba(66,165,245,.12);color:var(--accentL)}
.badge-contains{background:rgba(210,153,34,.12);color:#e3b341}

/* ══ FORM ════════════════════════════════════════ */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-row.full{grid-template-columns:1fr}
.field{display:flex;flex-direction:column;gap:5px}
.field label{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.6px}
.field input,.field select,.field textarea{
  background:var(--bg);border:1px solid var(--border);border-radius:6px;
  color:var(--text);padding:8px 11px;font-family:inherit;font-size:13px;
  outline:none;transition:.15s;
}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--accentL)}
.field textarea{resize:vertical;min-height:90px;line-height:1.5}
select option{background:var(--s2)}

/* ══ BUTTONS ═════════════════════════════════════ */
.btn{padding:7px 16px;border-radius:6px;cursor:pointer;font-family:inherit;font-size:13px;border:none;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:#1976D2}
.btn-green{background:var(--green);color:#fff}.btn-green:hover{filter:brightness(1.1)}
.btn-red{background:none;border:1px solid var(--red);color:var(--red)}.btn-red:hover{background:var(--red);color:#fff}
.btn-ghost{background:none;border:1px solid var(--border);color:var(--muted)}.btn-ghost:hover{border-color:var(--text);color:var(--text)}
.btn-xs{padding:3px 9px;font-size:11px;border-radius:4px}
.btn-orange{background:none;border:1px solid var(--orange);color:var(--orange)}.btn-orange:hover{background:var(--orange);color:#000}

/* ══ MODAL ═══════════════════════════════════════ */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;display:none;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{background:var(--s);border:1px solid var(--border);border-radius:12px;width:680px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column}
.modal-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:15px;font-weight:700}
.modal-close{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;line-height:1}
.modal-close:hover{color:var(--text)}
.modal-body{padding:20px;overflow-y:auto;flex:1}
.modal-foot{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}

/* ══ CHAT MONITOR ════════════════════════════════ */
#chat-monitor{display:flex;flex:1;overflow:hidden;gap:0}
#room-list{width:230px;border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;background:var(--s)}
#room-list .rl-head{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--accentL)}
#room-items{flex:1;overflow-y:auto}
.room-item{padding:12px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:.15s}
.room-item:hover{background:var(--s2)}
.room-item.active{background:rgba(66,165,245,.1);border-left:3px solid var(--accentL)}
.room-item .ri-name{font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;justify-content:space-between}
.room-item .ri-preview{font-size:11px;color:var(--muted);margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:180px}
.room-item .ri-time{font-size:10px;color:var(--muted)}
.unread-badge{background:var(--red);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700}

#chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
#chat-empty{flex:1;display:flex;align-items:center;justify-content:center;color:var(--muted);flex-direction:column;gap:10px;font-size:13px}
#chat-view{flex:1;display:none;flex-direction:column;overflow:hidden}
#chat-view.visible{display:flex}

#chat-view-head{padding:12px 16px;border-bottom:1px solid var(--border);background:var(--s);display:flex;align-items:center;gap:10px;flex-shrink:0}
#chat-view-head .cvh-name{font-size:14px;font-weight:700;flex:1}
#chat-view-head .cvh-sub{font-size:11px;color:var(--muted)}

#messages{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:6px}
#messages::-webkit-scrollbar{width:4px}
#messages::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}

.msg-row{display:flex;gap:8px;align-items:flex-end;animation:fadeIn .2s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.msg-row.user{flex-direction:row}
.msg-row.bot{flex-direction:row;opacity:.85}
.msg-row.admin{flex-direction:row-reverse}
.msg-row.sys{justify-content:center}

.av{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.bub{max-width:75%;padding:7px 11px;border-radius:12px;font-size:13px;line-height:1.55;word-break:break-word}
.msg-row.user  .bub{background:var(--s2);border-radius:3px 12px 12px 12px}
.msg-row.admin .bub{background:#1a3a5c;border-radius:12px 3px 12px 12px;color:#90CAF9}
.msg-row.bot   .bub{background:rgba(21,101,192,.15);border-left:2px solid var(--accentL);border-radius:3px 12px 12px 12px}
.msg-row.sys   .bub{background:var(--s2);font-size:11px;color:var(--muted);border-radius:8px;padding:4px 10px;font-style:italic}
.sender{font-size:10px;color:var(--muted);margin-bottom:2px}
.ts{font-size:9px;color:var(--muted);margin-top:2px;text-align:right}
.msg-row.user .ts{text-align:left}

#reply-box{padding:10px 14px;border-top:1px solid var(--border);background:var(--s);display:flex;gap:8px;align-items:flex-end;flex-shrink:0}
#reply-input{flex:1;background:var(--bg);border:1px solid var(--border);border-radius:20px;color:var(--text);padding:8px 14px;font-family:inherit;font-size:13px;outline:none;resize:none;max-height:100px;line-height:1.4}
#reply-input:focus{border-color:var(--accentL)}
#reply-send{width:36px;height:36px;border-radius:50%;border:none;background:var(--accent);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s}
#reply-send:hover{background:#1976D2}
#reply-send svg{width:15px;height:15px}

/* ══ ALERT STRIP ═════════════════════════════════ */
.alert{padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:14px}
.alert-warn{background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.3);color:#e3b341}
.alert-ok{background:rgba(46,160,67,.1);border:1px solid rgba(46,160,67,.3);color:#3fb950}

/* ══ LOGIN PAGE ══════════════════════════════════ */
#login-page{position:fixed;inset:0;background:var(--bg);display:flex;align-items:center;justify-content:center;z-index:9999}
.login-box{background:var(--s);border:1px solid var(--border);border-radius:16px;padding:36px;width:380px;text-align:center}
.login-box .logo{font-size:40px;margin-bottom:12px}
.login-box h2{font-size:18px;font-weight:700;margin-bottom:6px}
.login-box p{font-size:13px;color:var(--muted);margin-bottom:24px}
.login-field{position:relative;margin-bottom:12px}
.login-field input{width:100%;padding:10px 44px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;font-family:inherit;box-sizing:border-box}
.login-field input:focus{border-color:var(--accentL)}
.login-field .eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:16px;padding:0;line-height:1}
.login-field .eye-btn:hover{color:var(--text)}
.login-submit{width:100%;padding:10px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:.15s;margin-top:4px}
.login-submit:hover:not(:disabled){background:#1976D2}
.login-submit:disabled{opacity:.5;cursor:not-allowed}
.login-err{color:var(--red);font-size:13px;margin-top:10px;min-height:18px}
.login-lock{color:var(--orange);font-size:12px;margin-top:6px;padding:8px 12px;background:rgba(210,153,34,.08);border:1px solid rgba(210,153,34,.2);border-radius:6px;display:none}

/* ══ TOAST ═══════════════════════════════════════ */
#toast{position:fixed;bottom:24px;right:24px;z-index:9000;display:flex;flex-direction:column;gap:8px}
.toast-item{background:var(--s2);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:13px;animation:slideIn .25s ease;max-width:300px}
.toast-item.ok{border-left:3px solid var(--green);color:var(--text)}
.toast-item.err{border-left:3px solid var(--red);color:var(--red)}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
</style>
</head>
<body>

<!-- ══ LOGIN PAGE ══════════════════════════════════════ -->
<div id="login-page" <?= $isAdmin ? 'style="display:none"' : '' ?>>
  <div class="login-box">
    <div class="logo">🏛️</div>
    <h2>Admin — เทศบาลนครรังสิต</h2>
    <p>กรุณาเข้าสู่ระบบเพื่อจัดการ</p>
    <input type="hidden" id="li-csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div class="login-field">
      <input type="password" id="li-pass" placeholder="รหัสผ่าน"
             autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
      <button type="button" class="eye-btn" onclick="toggleLoginPass()" title="แสดง/ซ่อนรหัสผ่าน">👁</button>
    </div>
    <button id="li-btn" class="login-submit" onclick="doLogin()">เข้าสู่ระบบ →</button>
    <div class="login-err" id="li-err"></div>
    <div class="login-lock" id="li-lock"></div>
  </div>
</div>

<!-- ══ APP ════════════════════════════════════════════ -->
<div id="app" <?= !$isAdmin ? 'style="display:none"' : '' ?>>

  <!-- Sidebar -->
  <div id="sidebar">
    <div class="sb-head">
      <div class="logo">🏛️ Admin Panel</div>
      <div class="sub">เทศบาลนครรังสิต</div>
    </div>
    <nav class="sb-nav">
      <div class="nav-item active" data-tab="dashboard" onclick="switchTab(this)">📊 Dashboard</div>
      <div class="nav-item" data-tab="chat" onclick="switchTab(this)">
        💬 Chat Monitor
        <span class="badge" id="badge-chat" style="display:none">0</span>
      </div>
      <div class="nav-item" data-tab="unanswered" onclick="switchTab(this)">
        🆘 ต้องการตอบ
        <span class="badge" id="badge-unanswered" style="display:none">0</span>
      </div>
      <div class="nav-sep"></div>
      <div class="nav-item" data-tab="patterns" onclick="switchTab(this)">❓ จัดการ Q&A</div>
      <div class="nav-item" data-tab="menu" onclick="switchTab(this)">📱 เมนูลัด Chat</div>
      <div class="nav-item" data-tab="config" onclick="switchTab(this)">⚙️ ตั้งค่า Bot</div>
      <div class="nav-item" data-tab="log" onclick="switchTab(this)">📋 Bot Log</div>
    </nav>
    <div class="sb-foot">
      <div class="admin-name" id="sb-name"><?= htmlspecialchars($adminName) ?></div>
      <button class="logout-btn" onclick="doLogout()">ออกจากระบบ</button>
    </div>
  </div>

  <!-- Main -->
  <div id="main">
    <div id="topbar">
      <h2 id="page-title">📊 Dashboard</h2>
      <span class="refresh-info"><span class="online-dot"></span>อัปเดตอัตโนมัติทุก 5 วิ</span>
      <a href="demo_wp.php" target="_blank" class="btn btn-ghost btn-xs">🌐 หน้าเว็บ</a>
    </div>

    <div id="content">

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: DASHBOARD                          -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel active" id="tab-dashboard">
        <div class="scroll-area">
          <div class="stats-grid" id="stats-grid">
            <div class="stat-card" style="border-top-color:#42A5F5">
              <div class="stat-num" style="color:#42A5F5" id="s-msg">—</div>
              <div class="stat-lbl">ข้อความวันนี้</div>
              <div class="stat-sub" id="s-msg-total">รวมทั้งหมด: —</div>
            </div>
            <div class="stat-card" style="border-top-color:#2ea043">
              <div class="stat-num" style="color:#2ea043" id="s-bot">—</div>
              <div class="stat-lbl">Bot ตอบอัตโนมัติ (วันนี้)</div>
              <div class="stat-sub" id="s-patterns">Pattern ใช้งาน: —</div>
            </div>
            <div class="stat-card" style="border-top-color:#d29922">
              <div class="stat-num" style="color:#d29922" id="s-ai">—</div>
              <div class="stat-lbl">ส่งให้ AI (วันนี้)</div>
              <div class="stat-sub" id="s-fallback">Fallback รวม: —</div>
            </div>
            <div class="stat-card" style="border-top-color:#da3633">
              <div class="stat-num" style="color:#da3633" id="s-unanswered">—</div>
              <div class="stat-lbl">รอเจ้าหน้าที่ตอบ</div>
              <div class="stat-sub" id="s-online">ออนไลน์ขณะนี้: — คน</div>
            </div>
          </div>

          <div class="card">
            <div class="card-title">⚡ เข้าถึงด่วน</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <button class="btn btn-primary" onclick="switchTabById('chat')">💬 เปิด Chat Monitor</button>
              <button class="btn btn-orange" onclick="switchTabById('unanswered')">🆘 ดูคำถามที่ยังไม่ตอบ</button>
              <button class="btn btn-green" onclick="openPatternModal()">+ เพิ่ม Q&A ใหม่</button>
              <a href="chat_bot_admin.php" target="_blank" class="btn btn-ghost">⚙️ Bot Admin เดิม</a>
            </div>
          </div>

          <div class="card">
            <div class="card-title">🕐 กิจกรรมล่าสุด (Bot Log)</div>
            <table class="tbl" id="dash-log">
              <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>ประเภท</th><th>ห้อง</th></tr></thead>
              <tbody id="dash-log-body"><tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">กำลังโหลด...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: CHAT MONITOR                       -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-chat">
        <div id="chat-monitor">
          <div id="room-list">
            <div class="rl-head">💬 ห้องสนทนา</div>
            <div id="room-items">
              <div style="padding:20px;color:var(--muted);font-size:12px;text-align:center">กำลังโหลด...</div>
            </div>
          </div>
          <div id="chat-area">
            <div id="chat-empty">
              <span style="font-size:32px">💬</span>
              <span>เลือกห้องสนทนาเพื่อดูข้อความ</span>
            </div>
            <div id="chat-view">
              <div id="chat-view-head">
                <div>
                  <div class="cvh-name" id="cv-room-name">—</div>
                  <div class="cvh-sub" id="cv-room-sub">—</div>
                </div>
                <button class="btn btn-ghost btn-xs" onclick="refreshChat()">🔄 รีเฟรช</button>
              </div>
              <div id="messages"></div>
              <div id="reply-box">
                <textarea id="reply-input" rows="1" placeholder="พิมพ์ข้อความตอบกลับ... (Enter = ส่ง, Shift+Enter = บรรทัดใหม่)"></textarea>
                <button id="reply-send" onclick="sendAdminMsg()">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: UNANSWERED                         -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-unanswered">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700;color:var(--red)">🆘 คำถามที่ Bot ตอบไม่ได้ (รอเจ้าหน้าที่)</h3>
            <div style="margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--muted);cursor:pointer;white-space:nowrap">
                <input type="checkbox" id="unans-show-resolved" onchange="loadUnanswered()" style="accent-color:var(--accentL)">
                แสดงที่ตอบแล้วด้วย
              </label>
              <select id="unans-filter" onchange="loadUnanswered()" class="btn btn-ghost" style="padding:5px 10px">
                <option value="fallback">Fallback เท่านั้น</option>
                <option value="ai">AI ตอบแทน</option>
                <option value="all">ทั้งหมด</option>
              </select>
              <button class="btn btn-ghost btn-xs" onclick="loadUnanswered()">🔄 รีเฟรช</button>
            </div>
          </div>
          <div class="alert" id="unans-note" style="display:none"></div>
          <table class="tbl">
            <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>Bot ตอบ</th><th>ประเภท</th><th>ห้อง</th><th style="min-width:180px">จัดการ</th></tr></thead>
            <tbody id="unans-body"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px">กำลังโหลด...</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: PATTERNS (Q&A)                     -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-patterns">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700">❓ จัดการ Q&A / Bot Patterns</h3>
            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
              <input id="pat-search" type="text" placeholder="ค้นหา pattern..." style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:13px;outline:none;width:180px" oninput="loadPatterns()">
              <select id="pat-filter" onchange="loadPatterns()" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:13px">
                <option value="all">ทุกสถานะ</option>
                <option value="active">เปิดใช้งาน</option>
                <option value="inactive">ปิดใช้งาน</option>
                <option value="ai">ส่ง AI</option>
              </select>
              <button class="btn btn-green" onclick="openPatternModal()">+ เพิ่ม Pattern ใหม่</button>
            </div>
          </div>
          <div class="card" style="padding:0;overflow:hidden">
            <table class="tbl">
              <thead>
                <tr>
                  <th style="width:40px">On</th>
                  <th>Pattern / Keyword</th>
                  <th style="width:90px">ประเภท</th>
                  <th>ข้อความตอบ</th>
                  <th style="width:60px">Priority</th>
                  <th style="width:120px">จัดการ</th>
                </tr>
              </thead>
              <tbody id="pat-body">
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: MENU (quick reply)                 -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-menu">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700">📱 เมนูลัด Chat (LINE-style)</h3>
            <div style="font-size:12px;color:var(--muted)">ประชาชนกดปุ่ม ☰ แล้วเลือกหัวข้อได้เลย</div>
            <div style="margin-left:auto">
              <button class="btn btn-green" onclick="openMenuModal()">+ เพิ่มเมนูใหม่</button>
            </div>
          </div>

          <!-- Preview -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-title" style="margin-bottom:10px">👁 ตัวอย่างหน้าตา</div>
            <div style="background:#0f1923;border-radius:10px;padding:10px;max-width:280px">
              <div style="font-size:10px;color:#8fa3bc;font-weight:600;margin-bottom:8px;letter-spacing:.4px;text-transform:uppercase">⚡ เลือกหัวข้อที่ต้องการถาม</div>
              <div id="menu-preview" style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px"></div>
            </div>
          </div>

          <div class="card" style="padding:0;overflow:hidden">
            <table class="tbl">
              <thead>
                <tr>
                  <th style="width:40px">On</th>
                  <th style="width:50px">Icon</th>
                  <th>Label</th>
                  <th>ข้อความที่ส่ง</th>
                  <th>Bot ตอบกลับ</th>
                  <th style="width:60px">ลำดับ</th>
                  <th style="width:100px">จัดการ</th>
                </tr>
              </thead>
              <tbody id="menu-body">
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: CONFIG                             -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-config">
        <div class="scroll-area">
          <div class="card">
            <div class="card-title">⚙️ ตั้งค่า Bot</div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field">
                <label>ชื่อ Bot</label>
                <input type="text" id="cf-bot-name">
              </div>
              <div class="field">
                <label>สี Avatar Bot</label>
                <input type="color" id="cf-bot-color" style="padding:4px;height:40px">
              </div>
              <div class="field">
                <label>สถานะ Bot</label>
                <select id="cf-bot-enabled">
                  <option value="1">✅ เปิดใช้งาน</option>
                  <option value="0">❌ ปิดใช้งาน</option>
                </select>
              </div>
              <div class="field">
                <label>หน่วงเวลาตอบ (ms)</label>
                <input type="number" id="cf-delay" min="0" max="5000">
              </div>
            </div>

            <hr style="border-color:var(--border);margin:18px 0">
            <div class="card-title">🤖 AI Integration</div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field">
                <label>AI Fallback</label>
                <select id="cf-ai-enabled">
                  <option value="1">✅ เปิดใช้งาน</option>
                  <option value="0">❌ ปิดใช้งาน</option>
                </select>
              </div>
              <div class="field">
                <label>AI Provider</label>
                <select id="cf-ai-provider">
                  <option value="claude">Claude (Anthropic)</option>
                  <option value="openai">OpenAI (GPT)</option>
                </select>
              </div>
              <div class="field">
                <label>Claude API Key</label>
                <input type="text" id="cf-claude-key" placeholder="sk-ant-api03-...">
              </div>
              <div class="field">
                <label>Claude Model</label>
                <select id="cf-claude-model">
                  <option value="claude-sonnet-4-20250514">Claude Sonnet 4 (แนะนำ)</option>
                  <option value="claude-haiku-4-5-20251001">Claude Haiku 4.5 (เร็ว)</option>
                </select>
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>OpenAI API Key</label>
                <input type="text" id="cf-openai-key" placeholder="sk-...">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>AI System Prompt (บุคลิก Bot)</label>
                <textarea id="cf-prompt" style="min-height:110px"></textarea>
              </div>
            </div>

            <hr style="border-color:var(--border);margin:18px 0">
            <div class="card-title">📷 ตอบกลับรูปภาพและตำแหน่ง</div>
            <div class="alert alert-warn" style="margin-bottom:14px;font-size:12px">
              ใช้ <code style="background:rgba(255,255,255,.1);padding:1px 5px;border-radius:3px">{name}</code> = ชื่อผู้ใช้ &nbsp;|&nbsp;
              <code style="background:rgba(255,255,255,.1);padding:1px 5px;border-radius:3px">{address}</code> = ชื่อที่อยู่ (location เท่านั้น)
            </div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;justify-content:space-between">
                  <span>📷 ข้อความตอบกลับเมื่อรับรูปภาพ</span>
                  <label style="display:flex;align-items:center;gap:6px;font-weight:400;text-transform:none;letter-spacing:0;cursor:pointer">
                    <input type="checkbox" id="cf-image-ai" style="accent-color:var(--accentL)">
                    <span style="font-size:11px;color:var(--muted)">🤖 ให้ Claude วิเคราะห์รูปแทน (ต้องตั้ง API Key)</span>
                  </label>
                </label>
                <textarea id="cf-image-reply" style="min-height:80px"
                  placeholder="ขอบคุณสำหรับรูปภาพนะครับ {name} 📷&#10;ทีมงานจะตรวจสอบและติดต่อกลับโดยเร็วที่สุดครับ 🙏"></textarea>
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>📍 ข้อความตอบกลับเมื่อรับตำแหน่ง</label>
                <textarea id="cf-location-reply" style="min-height:80px"
                  placeholder="รับทราบตำแหน่งแล้วครับ {name} 📍&#10;{address}&#10;เจ้าหน้าที่จะเดินทางไปตรวจสอบโดยเร็วครับ 🙏"></textarea>
              </div>
            </div>

            <hr style="border-color:var(--border);margin:18px 0">
            <div class="card-title">🔐 ความปลอดภัย Admin</div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field">
                <label>ชื่อเจ้าหน้าที่ (แสดงในแชท)</label>
                <input type="text" id="cf-admin-name">
              </div>
              <div class="field">
                <label>เปลี่ยนรหัสผ่าน Admin</label>
                <input type="password" id="cf-admin-pass" placeholder="(เว้นว่างถ้าไม่เปลี่ยน)">
              </div>
            </div>

            <button class="btn btn-primary" onclick="saveConfig()">💾 บันทึกการตั้งค่า</button>
            <span id="cfg-saved" style="color:var(--green);font-size:13px;margin-left:10px;display:none">✅ บันทึกแล้ว</span>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: LOG                                -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-log">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <h3 style="font-size:14px;font-weight:700">📋 Bot Response Log</h3>
            <div style="margin-left:auto;display:flex;gap:8px">
              <select id="log-limit" onchange="loadLog()" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:13px">
                <option value="50">50 ล่าสุด</option>
                <option value="100">100 ล่าสุด</option>
                <option value="200">200 ล่าสุด</option>
              </select>
              <button class="btn btn-ghost btn-xs" onclick="loadLog()">🔄 รีเฟรช</button>
              <button class="btn btn-red btn-xs" onclick="clearLog()">🗑️ ล้าง Log</button>
            </div>
          </div>
          <div class="card" style="padding:0;overflow:hidden">
            <table class="tbl">
              <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>Bot ตอบ</th><th>ประเภท</th><th>ห้อง</th><th>ms</th></tr></thead>
              <tbody id="log-body"><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /app -->

<!-- ══ MODAL: Pattern Add/Edit ════════════════════════ -->
<div class="modal-bg" id="pat-modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modal-title">+ เพิ่ม Pattern ใหม่</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="pm-id" value="0">
      <div class="form-row" style="margin-bottom:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Pattern / Keyword <span style="color:var(--muted)">(regex รองรับ | สำหรับ หรือ)</span></label>
          <input type="text" id="pm-pattern" placeholder="เช่น งานทะเบียน|ทะเบียนราษฎร์">
        </div>
        <div class="field">
          <label>Match Type</label>
          <select id="pm-match">
            <option value="regex">regex — รองรับ | (หรือ) และ .* (ทุกตัว)</option>
            <option value="contains">contains — ข้อความมีคำนี้อยู่</option>
            <option value="keyword">keyword — ตรงทั้งประโยค</option>
            <option value="starts">starts — ขึ้นต้นด้วย</option>
            <option value="ends">ends — ลงท้ายด้วย</option>
          </select>
        </div>
        <div class="field">
          <label>Priority (สูง = ทำงานก่อน)</label>
          <input type="number" id="pm-priority" value="50" min="0" max="999">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>ข้อความตอบกลับ <span style="color:var(--muted)">(ใช้ {name} = ชื่อผู้ใช้, \n = ขึ้นบรรทัดใหม่)</span></label>
          <textarea id="pm-response" style="min-height:130px" placeholder="สวัสดีครับ {name}! 😊&#10;มีอะไรให้ช่วยไหมครับ?"></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span>🔘 ตัวเลือกย่อย <span style="color:var(--muted);font-weight:400">(ปุ่มที่แสดงใต้คำตอบ — กดแล้วส่งข้อความต่อ)</span></span>
            <button type="button" class="btn btn-ghost btn-xs" onclick="addChoiceRow()">+ เพิ่มตัวเลือก</button>
          </label>
          <div id="pm-choices-list"></div>
          <div id="pm-choices-empty" style="font-size:11px;color:var(--muted);padding:6px 0;display:none">
            ยังไม่มีตัวเลือก — กด "+ เพิ่มตัวเลือก" เพื่อเพิ่มปุ่มให้ผู้ใช้เลือกต่อ
          </div>
        </div>

        <div class="field">
          <label>ห้อง (ว่าง = ทุกห้อง)</label>
          <select id="pm-room">
            <option value="">— ทุกห้อง —</option>
          </select>
        </div>
        <div class="field">
          <label>สถานะ</label>
          <select id="pm-active">
            <option value="1">✅ เปิดใช้งาน</option>
            <option value="0">❌ ปิดใช้งาน</option>
          </select>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="pm-useai" style="width:16px;height:16px;accent-color:var(--accentL)">
            <span>🤖 ส่งให้ AI ตอบแทน (ไม่ใช้ข้อความด้านบน)</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal()">ยกเลิก</button>
      <button class="btn btn-primary" onclick="savePattern()">💾 บันทึก Pattern</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: Menu Item Add/Edit ══════════════════════ -->
<div class="modal-bg" id="menu-modal">
  <div class="modal" style="width:420px">
    <div class="modal-head">
      <h3 id="menu-modal-title">+ เพิ่มเมนูใหม่</h3>
      <button class="modal-close" onclick="closeMenuModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="mm-id" value="0">
      <div class="form-row" style="margin-bottom:12px">
        <div class="field">
          <label>Icon (Emoji)</label>
          <input type="text" id="mm-icon" placeholder="📋" maxlength="5" style="font-size:22px;text-align:center">
        </div>
        <div class="field">
          <label>ลำดับแสดง (น้อย = ก่อน)</label>
          <input type="number" id="mm-order" value="50" min="1" max="999">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Label (ชื่อปุ่ม — ไม่เกิน 10 ตัวอักษร)</label>
          <input type="text" id="mm-label" placeholder="งานทะเบียนราษฎร์" maxlength="20">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>ข้อความที่ส่งเมื่อกด</label>
          <input type="text" id="mm-msg" placeholder="งานทะเบียนราษฎร์" maxlength="200">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>🤖 Bot ตอบกลับ <span style="color:var(--muted);font-weight:400">(พิมพ์คำตอบ หรือเว้นว่างถ้าจะให้ AI ตอบ)</span></label>
          <textarea id="mm-response" style="min-height:90px" placeholder="สวัสดีครับ! สำหรับงานทะเบียนราษฎร์ ท่านสามารถติดต่อ...&#10;(เว้นว่างและติ๊ก 'ให้ AI ตอบ' ด้านล่าง)"></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" id="mm-useai" style="width:16px;height:16px;accent-color:var(--accentL)">
            <span>🤖 ให้ AI (Claude/OpenAI) ตอบแทนเมื่อเลือกเมนูนี้</span>
          </label>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>สถานะ</label>
          <select id="mm-active">
            <option value="1">✅ เปิดใช้งาน</option>
            <option value="0">❌ ปิดใช้งาน</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeMenuModal()">ยกเลิก</button>
      <button class="btn btn-primary" onclick="saveMenuItem()">💾 บันทึก</button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══════════════════════════════════════════ -->
<div id="toast"></div>

<script>
const API = 'admin_api.php';
let currentRoomId = null, chatLastId = 0, chatPollTimer = null;
let inboxPollTimer = null, statsPollTimer = null, totalUnread = 0;

// ══ API Helper ════════════════════════════════════════
async function api(action, post = null, get = null) {
  let url = `${API}?action=${action}`;
  if (get) url += '&' + new URLSearchParams(get);
  const opts = { method: post ? 'POST' : 'GET', credentials: 'same-origin' };
  if (post) {
    const fd = new FormData();
    Object.entries(post).forEach(([k, v]) => fd.append(k, v));
    opts.body = fd;
  }
  try {
    const r = await fetch(url, opts);
    const data = await r.json();
    data._status = r.status;
    return data;
  } catch { return { ok: false, error: 'Network error', _status: 0 }; }
}

// ══ Login / Logout ════════════════════════════════════
let _loginLocked = false;

async function doLogin() {
  if (_loginLocked) return;
  const pass = document.getElementById('li-pass').value;
  const csrf = document.getElementById('li-csrf').value;
  const errEl  = document.getElementById('li-err');
  const lockEl = document.getElementById('li-lock');
  const btn    = document.getElementById('li-btn');

  if (!pass) { errEl.textContent = 'กรุณากรอกรหัสผ่าน'; return; }

  btn.disabled    = true;
  btn.textContent = 'กำลังตรวจสอบ...';
  errEl.textContent = '';

  const r = await api('login', { password: pass, csrf_token: csrf });

  btn.disabled    = false;
  btn.textContent = 'เข้าสู่ระบบ →';

  if (r.ok) {
    document.getElementById('login-page').style.display = 'none';
    document.getElementById('app').style.display = '';
    document.getElementById('sb-name').textContent = r.data.name;
    initAdmin();
  } else {
    errEl.textContent = r.error || 'ไม่สำเร็จ';
    if (r._status === 429 || String(r.error).includes('ล็อก')) {
      _loginLocked = true;
      btn.disabled = true;
      lockEl.style.display = 'block';
      startLockCountdown(15);
    }
  }
}

function startLockCountdown(mins) {
  const lockEl = document.getElementById('li-lock');
  const btn    = document.getElementById('li-btn');
  let secs = mins * 60;
  const tick = () => {
    if (secs <= 0) {
      _loginLocked = false;
      btn.disabled = false;
      lockEl.style.display = 'none';
      document.getElementById('li-err').textContent = '';
      return;
    }
    const m = Math.floor(secs / 60), s = secs % 60;
    lockEl.textContent = `🔒 ถูกล็อกชั่วคราว — รอ ${m}:${String(s).padStart(2,'0')} นาที`;
    secs--;
    setTimeout(tick, 1000);
  };
  tick();
}

function toggleLoginPass() {
  const inp = document.getElementById('li-pass');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

async function doLogout() {
  await api('logout');
  location.reload();
}

// ══ Tab Switching ═════════════════════════════════════
function switchTab(el) {
  const tab = el.dataset.tab;
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  document.getElementById('page-title').textContent = el.textContent.replace(/\d+$/, '').trim();
  onTabSwitch(tab);
}
function switchTabById(tab) {
  switchTab(document.querySelector(`[data-tab="${tab}"]`));
}
function onTabSwitch(tab) {
  if (tab === 'patterns')  loadPatterns();
  if (tab === 'log')       loadLog();
  if (tab === 'unanswered') loadUnanswered();
  if (tab === 'config')    loadConfig();
  if (tab === 'menu')      loadMenuList();
  if (tab === 'chat')      { loadInbox(); if (currentRoomId) startChatPoll(); }
}

// ══ Init ══════════════════════════════════════════════
function initAdmin() {
  loadStats();
  loadDashLog();
  loadInbox();
  loadRoomsForModal();
  statsPollTimer = setInterval(() => { loadStats(); loadDashLog(); }, 10000);
  inboxPollTimer = setInterval(loadInbox, 5000);
}

// ══ STATS ═════════════════════════════════════════════
async function loadStats() {
  const r = await api('stats');
  if (!r.ok) return;
  const d = r.data;
  document.getElementById('s-msg').textContent       = d.msg_today;
  document.getElementById('s-msg-total').textContent = `รวมทั้งหมด: ${d.msg_total}`;
  document.getElementById('s-bot').textContent       = d.bot_today;
  document.getElementById('s-patterns').textContent  = `Pattern ใช้งาน: ${d.patterns_active}`;
  document.getElementById('s-ai').textContent        = d.ai_today;
  document.getElementById('s-fallback').textContent  = `Fallback รวม: ${d.unanswered_total}`;
  document.getElementById('s-unanswered').textContent= d.fallback_today;
  document.getElementById('s-online').textContent    = `ออนไลน์ขณะนี้: ${d.online} คน`;
  if (d.fallback_today > 0) {
    showBadge('badge-unanswered', d.fallback_today);
  }
}

async function loadDashLog() {
  const r = await api('bot_log', null, { limit: 10 });
  if (!r.ok) return;
  const tbody = document.getElementById('dash-log-body');
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">ยังไม่มีกิจกรรม</td></tr>'; return; }
  tbody.innerHTML = r.data.map(l => `
    <tr>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:200px;font-size:12px">${esc(trunc(l.trigger_msg, 50))}</td>
      <td>${badgeHtml(l.response_type)}</td>
      <td style="font-size:12px;color:var(--muted)">${esc(l.room_name || '—')}</td>
    </tr>`).join('');
}

// ══ INBOX (Chat Monitor) ══════════════════════════════
async function loadInbox() {
  const r = await api('inbox');
  if (!r.ok) return;
  const el = document.getElementById('room-items');
  totalUnread = 0;
  if (!r.data.length) { el.innerHTML = '<div style="padding:20px;color:var(--muted);font-size:12px;text-align:center">ไม่มีห้อง</div>'; return; }
  el.innerHTML = r.data.map(room => {
    totalUnread += (room.unread || 0);
    const isBot = room.last_username === 'chatbot';
    const isAdmin = room.last_username === 'admin_staff';
    const preview = room.last_msg ? trunc(room.last_msg.replace(/\n/g, ' '), 38) : '—';
    const senderIcon = isBot ? '🤖 ' : isAdmin ? '👮 ' : '';
    return `<div class="room-item${currentRoomId == room.id ? ' active' : ''}" onclick="openRoom(${room.id},'${esc(room.name)}')">
      <div class="ri-name">
        <span>💬 ${esc(room.name)}</span>
        ${room.unread > 0 ? `<span class="unread-badge">${room.unread}</span>` : ''}
      </div>
      <div class="ri-preview">${senderIcon}${esc(preview)}</div>
      <div class="ri-time">${room.last_time || ''} · วันนี้: ${room.user_msg_today} ข้อ</div>
    </div>`;
  }).join('');
  if (totalUnread > 0) showBadge('badge-chat', totalUnread);
  else hideBadge('badge-chat');
}

async function openRoom(roomId, roomName) {
  currentRoomId = roomId;
  chatLastId    = 0;
  document.getElementById('chat-empty').style.display = 'none';
  document.getElementById('chat-view').classList.add('visible');
  document.getElementById('cv-room-name').textContent = `💬 ${roomName}`;
  document.getElementById('cv-room-sub').textContent  = 'กำลังโหลด...';
  document.getElementById('messages').innerHTML = '';
  document.querySelectorAll('.room-item').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.room-item').forEach(el => { if (el.onclick.toString().includes(roomId)) el.classList.add('active'); });
  loadInbox();
  await loadRoomMessages(true);
  startChatPoll();
}

async function loadRoomMessages(initial = false) {
  if (!currentRoomId) return;
  const r = await api('room_messages', null, { room_id: currentRoomId, last_id: chatLastId });
  if (!r.ok || !r.data.length) {
    if (initial) document.getElementById('cv-room-sub').textContent = 'ยังไม่มีข้อความ';
    return;
  }
  const area  = document.getElementById('messages');
  const atBot = area.scrollHeight - area.clientHeight - area.scrollTop < 60;
  const prevSender = { username: null };
  r.data.forEach(m => {
    appendAdminMsg(m, prevSender.username);
    prevSender.username = m.username;
    if (m.id > chatLastId) chatLastId = m.id;
  });
  if (initial || atBot) area.scrollTop = area.scrollHeight;
  const count = r.data.length;
  document.getElementById('cv-room-sub').textContent = `${count} ข้อความล่าสุด • อัปเดตอัตโนมัติ`;
}

function appendAdminMsg(m, prevSender) {
  const area   = document.getElementById('messages');
  const isBot  = m.username === 'chatbot';
  const isAdm  = m.username === 'admin_staff';
  const isSys  = m.msg_type === 'system';
  const type   = isSys ? 'sys' : isBot ? 'bot' : isAdm ? 'admin' : 'user';
  const color  = m.avatar_color || '#555';
  const init   = (m.display_name || '?')[0].toUpperCase();
  const showSender = m.username !== prevSender;

  const div = document.createElement('div');
  div.className = 'msg-row ' + type;

  if (isSys) {
    div.innerHTML = `<div class="bub">${esc(m.message)}</div>`;
  } else {
    const av   = `<div class="av" style="background:${color}">${init}</div>`;
    const name = isBot ? '🤖 RungsitBot' : isAdm ? `👮 ${m.display_name}` : m.display_name;
    const sRow = showSender ? `<div class="sender">${esc(name)}</div>` : '';

    let bubContent;
    if (m.msg_type === 'image') {
      const src = esc(m.message);
      bubContent = `<img src="${src}" alt="รูปภาพ" loading="lazy"
        style="max-width:200px;border-radius:6px;display:block;cursor:zoom-in"
        onclick="window.open('${src}','_blank')">`;
    } else if (m.msg_type === 'location') {
      try {
        const loc = JSON.parse(m.message);
        const mapUrl = `https://www.google.com/maps?q=${loc.lat},${loc.lng}&z=17`;
        bubContent = `📍 <a href="${mapUrl}" target="_blank" style="color:var(--accentL)">ดูตำแหน่งบน Maps</a>
          <div style="font-size:10px;color:var(--muted);margin-top:2px">${loc.lat.toFixed(5)}, ${loc.lng.toFixed(5)} · ±${Math.round(loc.acc||0)}ม.</div>`;
      } catch { bubContent = '📍 ตำแหน่ง'; }
    } else {
      bubContent = formatMsg(m.message);
    }

    div.innerHTML = (isAdm ? '' : av) +
      `<div><div class="bub">${bubContent}</div><div class="ts">${m.time_str||''}</div></div>` +
      (isAdm ? av : '');
    if (showSender) {
      const wrapper = document.createElement('div');
      wrapper.innerHTML = `<div class="sender" style="padding-left:${isAdm?'0':'32px'};text-align:${isAdm?'right':'left'}">${esc(name)}</div>`;
      area.appendChild(wrapper.firstElementChild);
    }
  }
  area.appendChild(div);
}

function startChatPoll() {
  clearInterval(chatPollTimer);
  chatPollTimer = setInterval(() => loadRoomMessages(false), 3000);
}
async function refreshChat() { chatLastId = 0; document.getElementById('messages').innerHTML = ''; await loadRoomMessages(true); }

// ══ SEND ADMIN MESSAGE ════════════════════════════════
async function sendAdminMsg() {
  const inp = document.getElementById('reply-input');
  const msg = inp.value.trim();
  if (!msg || !currentRoomId) return;
  inp.value = ''; inp.style.height = 'auto';
  const r = await api('send_admin', { room_id: currentRoomId, message: msg });
  if (r.ok) { clearInterval(chatPollTimer); await loadRoomMessages(false); startChatPoll(); }
  else toast(r.error || 'ส่งไม่ได้', 'err');
}
document.getElementById('reply-input')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAdminMsg(); }
});
document.getElementById('reply-input')?.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// ══ UNANSWERED ════════════════════════════════════════
async function loadUnanswered() {
  const type         = document.getElementById('unans-filter')?.value || 'fallback';
  const showResolved = document.getElementById('unans-show-resolved')?.checked ? 1 : 0;
  const r    = await api('unanswered', null, { type, show_resolved: showResolved });
  const note = document.getElementById('unans-note');
  if (!r.ok) { toast(r.error, 'err'); return; }
  const tbody = document.getElementById('unans-body');

  if (!r.data.length) {
    note.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">✅ ไม่มีคำถามที่รอตอบ</td></tr>`;
    return;
  }

  const pending  = r.data.filter(l => l.is_resolved == 0).length;
  const resolved = r.data.length - pending;
  note.style.display = 'block';
  if (pending > 0) {
    note.className   = 'alert alert-warn';
    note.textContent = showResolved
      ? `⚠️ รอตอบ ${pending} รายการ · ตอบแล้ว ${resolved} รายการ`
      : `⚠️ พบ ${pending} คำถามที่ Bot ตอบไม่ได้ — เจ้าหน้าที่ควรเข้าไปตอบในห้องสนทนา`;
  } else {
    note.className   = 'alert alert-ok';
    note.textContent = `✅ ตอบครบทุกรายการแล้ว (${resolved} รายการ)`;
  }

  tbody.innerHTML = r.data.map(l => {
    const done = l.is_resolved == 1;
    const resolvedLabel = done
      ? `<span class="badge badge-pattern">✅ ตอบแล้ว</span>${l.resolved_by ? `<br><span style="font-size:10px;color:var(--muted)">โดย ${esc(l.resolved_by)}</span>` : ''}`
      : `<button class="btn btn-primary btn-xs" onclick="goToRoom(${l.room_id},'${esc(l.room_name)}')">💬 ตอบ</button>
         <button class="btn btn-green btn-xs" onclick="quickAddPattern('${esc(l.trigger_msg).replace(/'/g,'&#39;')}')">+ Q&A</button>
         <button class="btn btn-xs" style="border:1px solid var(--green);color:var(--green);background:none;border-radius:4px;padding:3px 9px;cursor:pointer;font-size:11px;font-weight:600"
                 onclick="resolveLog(${l.id},this)">✅ ตอบแล้ว</button>`;
    return `
    <tr style="${done ? 'opacity:.45' : ''}">
      <td style="font-size:11px;white-space:nowrap;color:var(--muted)">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:180px;font-size:12px">${esc(trunc(l.trigger_msg, 50))}</td>
      <td style="max-width:160px;font-size:11px;color:var(--muted)">${l.bot_response ? esc(trunc(l.bot_response, 40)) : '<em>ไม่มี</em>'}</td>
      <td>${badgeHtml(l.response_type)}</td>
      <td style="font-size:12px">${esc(l.room_name || '—')}</td>
      <td style="white-space:nowrap">${resolvedLabel}</td>
    </tr>`;
  }).join('');
}

async function resolveLog(id, btn) {
  btn.disabled    = true;
  btn.textContent = '...';
  const r = await api('resolve_log', { id });
  if (r.ok) {
    toast('บันทึกแล้ว ✅');
    if (r.data.remaining > 0) showBadge('badge-unanswered', r.data.remaining);
    else hideBadge('badge-unanswered');
    loadUnanswered();
  } else {
    btn.disabled    = false;
    btn.textContent = '✅ ตอบแล้ว';
    toast(r.error || 'เกิดข้อผิดพลาด', 'err');
  }
}

function goToRoom(roomId, roomName) {
  switchTabById('chat');
  setTimeout(() => openRoom(roomId, roomName || 'ห้อง'), 200);
}
function quickAddPattern(trigger) {
  openPatternModal();
  setTimeout(() => { document.getElementById('pm-pattern').value = trigger; }, 100);
}

// ══ MENU ITEMS ════════════════════════════════════════
async function loadMenuList() {
  const r = await api('menu_list');
  if (!r.ok) return;
  renderMenuPreview(r.data.filter(m => m.is_active == 1));
  const tbody = document.getElementById('menu-body');
  if (!r.data.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">ยังไม่มีเมนู — กด + เพิ่มเมนูใหม่</td></tr>';
    return;
  }
  tbody.innerHTML = r.data.map(m => {
    const botCell = m.bot_response
      ? `<span style="font-size:11px;color:var(--muted)">${esc(trunc(m.bot_response,45))}</span>`
      : `<span style="font-size:10px;color:var(--red);opacity:.6">⚠️ ยังไม่มีคำตอบ</span>`;
    return `
    <tr class="${m.is_active ? '' : 'inactive'}">
      <td>
        <button class="btn btn-xs ${m.is_active ? 'btn-green' : 'btn-ghost'}" onclick="toggleMenuItem(${m.id},this)">
          ${m.is_active ? '✅' : '⭕'}
        </button>
      </td>
      <td style="font-size:22px;text-align:center">${esc(m.icon)}</td>
      <td style="font-size:13px;font-weight:600">${esc(m.label)}</td>
      <td style="font-size:12px;color:var(--accentL)">${esc(m.message_text)}</td>
      <td>${botCell}</td>
      <td style="font-size:12px;text-align:center">${m.sort_order}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick='editMenuItem(${JSON.stringify(m)})'>✏️</button>
        <button class="btn btn-red btn-xs" onclick="deleteMenuItem(${m.id})">🗑️</button>
      </td>
    </tr>`;
  }).join('');
}

function renderMenuPreview(items) {
  const el = document.getElementById('menu-preview');
  if (!el) return;
  if (!items.length) { el.innerHTML = '<div style="font-size:11px;color:#8fa3bc;grid-column:1/-1">ยังไม่มีเมนู</div>'; return; }
  el.innerHTML = items.slice(0,9).map(m => `
    <div style="display:flex;flex-direction:column;align-items:center;gap:3px;padding:8px 4px 6px;border-radius:8px;background:#1e2a3a;border:1px solid #253040;text-align:center">
      <span style="font-size:20px;line-height:1">${esc(m.icon)}</span>
      <span style="font-size:9px;color:#e8edf5;font-weight:500;line-height:1.3">${esc(m.label)}</span>
    </div>`).join('');
}

function openMenuModal(data = null) {
  document.getElementById('menu-modal-title').textContent = data ? '✏️ แก้ไขเมนู' : '+ เพิ่มเมนูใหม่';
  document.getElementById('mm-id').value       = data?.id           || 0;
  document.getElementById('mm-icon').value     = data?.icon         || '📋';
  document.getElementById('mm-label').value    = data?.label        || '';
  document.getElementById('mm-msg').value      = data?.message_text || '';
  document.getElementById('mm-order').value    = data?.sort_order   ?? 50;
  document.getElementById('mm-active').value   = data?.is_active    ?? 1;
  document.getElementById('mm-response').value = data?.bot_response || '';
  document.getElementById('mm-useai').checked  = false;
  document.getElementById('menu-modal').classList.add('open');
  document.getElementById('mm-label').focus();
}
function editMenuItem(m) { openMenuModal(m); }
function closeMenuModal() { document.getElementById('menu-modal').classList.remove('open'); }

async function saveMenuItem() {
  const fd = {
    id:           document.getElementById('mm-id').value,
    icon:         document.getElementById('mm-icon').value.trim() || '📋',
    label:        document.getElementById('mm-label').value.trim(),
    message_text: document.getElementById('mm-msg').value.trim(),
    sort_order:   document.getElementById('mm-order').value,
    is_active:    document.getElementById('mm-active').value,
    bot_response: document.getElementById('mm-response').value.trim(),
    use_ai:       document.getElementById('mm-useai').checked ? 1 : 0,
  };
  if (!fd.label || !fd.message_text) { toast('กรุณากรอก Label และข้อความ', 'err'); return; }
  const r = await api('menu_save', fd);
  if (r.ok) { toast('บันทึกสำเร็จ ✅'); closeMenuModal(); loadMenuList(); }
  else toast(r.error || 'บันทึกไม่ได้', 'err');
}

async function deleteMenuItem(id) {
  if (!confirm('ลบเมนูนี้?')) return;
  const r = await api('menu_delete', { id });
  if (r.ok) { toast('ลบแล้ว'); loadMenuList(); }
  else toast(r.error, 'err');
}

async function toggleMenuItem(id, btn) {
  const r = await api('menu_toggle', { id });
  if (r.ok) { btn.textContent = r.data.active ? '✅' : '⭕'; btn.className = `btn btn-xs ${r.data.active ? 'btn-green' : 'btn-ghost'}`; loadMenuList(); }
  else toast(r.error, 'err');
}

document.getElementById('menu-modal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeMenuModal();
});

// ══ PATTERNS ══════════════════════════════════════════
async function loadPatterns() {
  const q      = document.getElementById('pat-search')?.value || '';
  const filter = document.getElementById('pat-filter')?.value || 'all';
  const r = await api('patterns', null, { filter, q });
  if (!r.ok) return;
  const tbody = document.getElementById('pat-body');
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">ไม่พบ Pattern</td></tr>'; return; }
  tbody.innerHTML = r.data.map(p => `
    <tr class="${p.is_active ? '' : 'inactive'}">
      <td>
        <button class="btn btn-xs ${p.is_active ? 'btn-green' : 'btn-ghost'}" onclick="togglePattern(${p.id},this)" title="${p.is_active?'คลิกเพื่อปิด':'คลิกเพื่อเปิด'}">
          ${p.is_active ? '✅' : '⭕'}
        </button>
      </td>
      <td style="max-width:200px;font-size:12px;font-family:monospace;color:var(--accentL);word-break:break-all">${esc(trunc(p.pattern,60))}</td>
      <td><span class="badge badge-${p.match_type === 'regex' ? 'regex' : 'contains'}">${p.match_type}</span></td>
      <td style="max-width:260px;font-size:12px;color:var(--muted)">
        ${p.use_ai ? '<span class="badge badge-ai">🤖 AI</span>' : esc(trunc(p.response,70))}
      </td>
      <td style="font-size:12px;text-align:center">${p.priority}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick='editPattern(${JSON.stringify(p)})'>✏️ แก้ไข</button>
        <button class="btn btn-red btn-xs" onclick="deletePattern(${p.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

async function togglePattern(id, btn) {
  const r = await api('pattern_toggle', { id });
  if (r.ok) { btn.textContent = r.data.active ? '✅' : '⭕'; btn.className = `btn btn-xs ${r.data.active ? 'btn-green' : 'btn-ghost'}`; loadPatterns(); }
  else toast(r.error, 'err');
}

async function deletePattern(id) {
  if (!confirm('ลบ Pattern นี้?')) return;
  const r = await api('pattern_delete', { id });
  if (r.ok) { toast('ลบแล้ว'); loadPatterns(); } else toast(r.error, 'err');
}

// ── Modal Pattern ─────────────────────────────────────
let _modalChoices = [];

function addChoiceRow(label = '', message = '') {
  _modalChoices.push({ label, message });
  renderChoicesList();
}
function removeChoiceRow(idx) {
  _modalChoices.splice(idx, 1);
  renderChoicesList();
}
function renderChoicesList() {
  const el      = document.getElementById('pm-choices-list');
  const emptyEl = document.getElementById('pm-choices-empty');
  if (!el) return;
  emptyEl.style.display = _modalChoices.length ? 'none' : 'block';
  const iStyle = 'background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:12px;outline:none;font-family:inherit';
  el.innerHTML = _modalChoices.map((c, i) => `
    <div style="display:flex;gap:6px;margin-bottom:6px;align-items:center">
      <input value="${esc(c.label)}" placeholder="ชื่อปุ่ม เช่น 🛣️ ถนน"
             oninput="_modalChoices[${i}].label=this.value"
             style="flex:1;${iStyle}">
      <input value="${esc(c.message)}" placeholder="ข้อความที่ส่งเมื่อกด"
             oninput="_modalChoices[${i}].message=this.value"
             style="flex:1.5;${iStyle}">
      <button class="btn btn-red btn-xs" onclick="removeChoiceRow(${i})" style="flex-shrink:0">🗑️</button>
    </div>`).join('');
}

function openPatternModal(data = null) {
  document.getElementById('modal-title').textContent = data ? '✏️ แก้ไข Pattern' : '+ เพิ่ม Pattern ใหม่';
  document.getElementById('pm-id').value       = data?.id || 0;
  document.getElementById('pm-pattern').value  = data?.pattern || '';
  document.getElementById('pm-match').value    = data?.match_type || 'regex';
  document.getElementById('pm-response').value = data?.response || '';
  document.getElementById('pm-priority').value = data?.priority ?? 50;
  document.getElementById('pm-room').value     = data?.room_id || '';
  document.getElementById('pm-active').value   = data?.is_active ?? 1;
  document.getElementById('pm-useai').checked  = !!(data?.use_ai);
  _modalChoices = [];
  try { _modalChoices = JSON.parse(data?.choices || '[]') || []; } catch { _modalChoices = []; }
  renderChoicesList();
  document.getElementById('pat-modal').classList.add('open');
  document.getElementById('pm-pattern').focus();
}
function editPattern(p) { openPatternModal(p); }
function closeModal()   { document.getElementById('pat-modal').classList.remove('open'); }

async function savePattern() {
  const validChoices = _modalChoices.filter(c => c.label?.trim() && c.message?.trim());
  const fd = {
    id:         document.getElementById('pm-id').value,
    pattern:    document.getElementById('pm-pattern').value.trim(),
    match_type: document.getElementById('pm-match').value,
    response:   document.getElementById('pm-response').value,
    priority:   document.getElementById('pm-priority').value,
    room_id:    document.getElementById('pm-room').value,
    is_active:  document.getElementById('pm-active').value,
    use_ai:     document.getElementById('pm-useai').checked ? 1 : 0,
    choices:    JSON.stringify(validChoices),
  };
  if (!fd.pattern) { toast('กรุณาระบุ Pattern', 'err'); return; }
  const r = await api('pattern_save', fd);
  if (r.ok) { toast('บันทึกสำเร็จ ✅'); closeModal(); loadPatterns(); }
  else toast(r.error || 'บันทึกไม่ได้', 'err');
}

async function loadRoomsForModal() {
  const r = await api('rooms');
  if (!r.ok) return;
  const sel = document.getElementById('pm-room');
  r.data.forEach(room => {
    const o = document.createElement('option');
    o.value = room.id; o.textContent = room.name;
    sel.appendChild(o);
  });
}

// ══ CONFIG ════════════════════════════════════════════
async function loadConfig() {
  const r = await api('config');
  if (!r.ok) return;
  const c = r.data;
  document.getElementById('cf-bot-name').value      = c.bot_name || '';
  document.getElementById('cf-bot-color').value     = c.bot_color || '#1565C0';
  document.getElementById('cf-bot-enabled').value   = c.bot_enabled ?? '1';
  document.getElementById('cf-delay').value         = c.reply_delay_ms || '800';
  document.getElementById('cf-ai-enabled').value    = c.ai_enabled ?? '1';
  document.getElementById('cf-ai-provider').value   = c.ai_provider || 'claude';
  document.getElementById('cf-claude-key').value    = c.claude_api_key || '';
  document.getElementById('cf-claude-model').value  = c.claude_model || 'claude-sonnet-4-20250514';
  document.getElementById('cf-openai-key').value    = c.openai_api_key || '';
  document.getElementById('cf-prompt').value        = c.ai_system_prompt || '';
  document.getElementById('cf-image-reply').value   = c.image_reply || '';
  document.getElementById('cf-image-ai').checked    = c.image_use_ai === '1';
  document.getElementById('cf-location-reply').value = c.location_reply || '';
  document.getElementById('cf-admin-name').value    = document.getElementById('sb-name').textContent;
}

async function saveConfig() {
  const pass = document.getElementById('cf-admin-pass').value;
  const fd = {
    bot_name:        document.getElementById('cf-bot-name').value,
    bot_color:       document.getElementById('cf-bot-color').value,
    bot_enabled:     document.getElementById('cf-bot-enabled').value,
    reply_delay_ms:  document.getElementById('cf-delay').value,
    ai_enabled:      document.getElementById('cf-ai-enabled').value,
    ai_provider:     document.getElementById('cf-ai-provider').value,
    claude_api_key:  document.getElementById('cf-claude-key').value,
    claude_model:    document.getElementById('cf-claude-model').value,
    openai_api_key:  document.getElementById('cf-openai-key').value,
    ai_system_prompt:document.getElementById('cf-prompt').value,
    image_reply:     document.getElementById('cf-image-reply').value,
    image_use_ai:    document.getElementById('cf-image-ai').checked ? '1' : '0',
    location_reply:  document.getElementById('cf-location-reply').value,
    admin_name:      document.getElementById('cf-admin-name').value,
  };
  if (pass) fd.admin_password = pass;
  const r = await api('config_save', fd);
  if (r.ok) {
    document.getElementById('sb-name').textContent = fd.admin_name;
    const saved = document.getElementById('cfg-saved');
    saved.style.display = 'inline';
    setTimeout(() => saved.style.display = 'none', 2500);
    toast('บันทึกการตั้งค่าแล้ว ✅');
  } else toast(r.error, 'err');
}

// ══ LOG ═══════════════════════════════════════════════
async function loadLog() {
  const lim = document.getElementById('log-limit')?.value || 50;
  const r = await api('bot_log', null, { limit: lim });
  if (!r.ok) return;
  const tbody = document.getElementById('log-body');
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">ยังไม่มี Log</td></tr>'; return; }
  tbody.innerHTML = r.data.map(l => `
    <tr>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:180px;font-size:12px">${esc(trunc(l.trigger_msg,50))}</td>
      <td style="max-width:200px;font-size:12px;color:var(--muted)">${l.bot_response ? esc(trunc(l.bot_response,60)) : '<em style="color:var(--red)">ไม่มีคำตอบ</em>'}</td>
      <td>${badgeHtml(l.response_type)}</td>
      <td style="font-size:12px">${esc(l.room_name||'—')}</td>
      <td style="font-size:11px;color:var(--muted)">${l.latency_ms}ms</td>
    </tr>`).join('');
}

async function clearLog() {
  if (!confirm('ล้าง Bot Log ทั้งหมด?')) return;
  const r = await api('clear_log', {});
  if (r.ok) { toast('ล้าง Log แล้ว'); loadLog(); } else toast(r.error, 'err');
}

// ══ Utilities ═════════════════════════════════════════
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function formatMsg(txt) { return esc(txt).replace(/\n/g,'<br>'); }
function trunc(s, n) { s = String(s||''); return s.length > n ? s.slice(0,n)+'…' : s; }

function badgeHtml(type) {
  const map = { pattern:'badge-pattern', ai:'badge-ai', fallback:'badge-fallback' };
  const cls = map[type] || 'badge-pattern';
  return `<span class="badge ${cls}">${type}</span>`;
}

function showBadge(id, n) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = n; el.style.display = 'inline';
}
function hideBadge(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

function toast(msg, type = 'ok') {
  const el = document.createElement('div');
  el.className = `toast-item ${type}`;
  el.textContent = msg;
  document.getElementById('toast').appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

// ══ Close modal on bg click ═══════════════════════════
document.getElementById('pat-modal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ══ Boot ══════════════════════════════════════════════
<?php if ($isAdmin): ?>
initAdmin();
<?php else: ?>
document.getElementById('li-pass')?.focus();
<?php endif; ?>
</script>
</body>
</html>
