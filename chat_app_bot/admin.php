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
$adminRole = $_SESSION['admin_role'] ?? 'superadmin';
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
#topbar h2{font-size:17px;font-weight:700;flex:1;color:var(--text)}
#page-hash{font-size:11px;color:var(--muted);font-family:monospace;background:var(--s2);padding:2px 8px;border-radius:4px;border:1px solid var(--border);cursor:pointer;user-select:all}
#page-hash:hover{color:var(--accentL);border-color:var(--accentL)}
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
/* ── Response Mode Tabs ── */
.resp-tabs{display:flex;gap:4px;margin-bottom:8px;flex-wrap:wrap}
.resp-tab{background:var(--bg);border:1px solid var(--border);color:var(--muted);padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;transition:all .15s;font-family:inherit}
.resp-tab:hover{border-color:var(--accentL);color:var(--text)}
.resp-tab.active{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600}
.resp-panel{display:none}.resp-panel.active{display:block}

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

/* ══ USER MANAGER ════════════════════════════════ */
.user-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0}
.role-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.role-badge.superadmin{background:rgba(198,40,40,.15);color:#ef9a9a;border:1px solid rgba(198,40,40,.3)}
.role-badge.staff{background:rgba(21,101,192,.15);color:#90caf9;border:1px solid rgba(21,101,192,.3)}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px}
.status-dot.on{background:var(--green)}
.status-dot.off{background:var(--muted)}

/* ══ CHAT MONITOR ════════════════════════════════ */
#chat-monitor{display:flex;flex:1;overflow:hidden;gap:0}
#room-list{width:230px;border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;background:var(--s)}
#room-list .rl-head{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--accentL)}
#room-items{flex:1;overflow-y:auto}
.room-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:.15s;display:flex;gap:10px;align-items:flex-start}
.room-item:hover{background:var(--s2)}
.room-item.active{background:rgba(66,165,245,.1);border-left:3px solid var(--accentL)}
.room-item .ri-body{flex:1;min-width:0}
.room-item .ri-name{font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;justify-content:space-between}
.room-item .ri-preview{font-size:11px;color:var(--muted);margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:170px}
.room-item .ri-time{font-size:10px;color:var(--muted);margin-top:2px}
.unread-badge{background:var(--red);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700}
/* reply status indicator */
.reply-dot{flex-shrink:0;margin-top:2px;transition:all .4s}
.reply-dot.done{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 4px rgba(46,160,67,.5);margin-top:5px}
.reply-dot.need{
  display:inline-flex;align-items:center;gap:3px;
  background:rgba(218,54,51,.18);border:1px solid rgba(218,54,51,.5);
  color:#f85149;font-size:10px;font-weight:700;
  padding:2px 7px;border-radius:10px;white-space:nowrap;
  animation:needPulse 2s ease-in-out infinite;
}
.reply-dot.need::before{content:'';width:6px;height:6px;border-radius:50%;background:#f85149;display:inline-block;animation:dotBlink 1s ease-in-out infinite}
@keyframes needPulse{0%,100%{box-shadow:0 0 0 0 rgba(218,54,51,.4)}60%{box-shadow:0 0 0 4px rgba(218,54,51,0)}}
@keyframes dotBlink{0%,100%{opacity:1}50%{opacity:.3}}
.room-item.need-reply{border-left:2px solid rgba(218,54,51,.6)}
.av-sm{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;margin-top:2px;position:relative}
.av-online-dot{position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;border:2px solid var(--s);background:var(--border)}
.av-online-dot.online{background:var(--green)}
.conv-online{font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px}
.conv-online::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--border);flex-shrink:0}
.conv-online.online{color:var(--green)}.conv-online.online::before{background:var(--green);box-shadow:0 0 4px rgba(46,160,67,.6)}
.conv-online.offline{color:var(--muted)}
.s-badge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;white-space:nowrap}
.s-badge.operator{background:rgba(66,165,245,.2);color:var(--accentL)}
.s-badge.resolved{background:rgba(46,160,67,.2);color:var(--green)}

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

/* ══ OPERATOR CONTROLS ═══════════════════════════ */
.op-controls{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn-takeover{background:rgba(46,160,67,.15);border:1px solid var(--green);color:#3fb950}
.btn-takeover:hover{background:var(--green);color:#fff}
.btn-release{background:rgba(210,153,34,.1);border:1px solid var(--orange);color:#e3b341}
.btn-release:hover{background:var(--orange);color:#000}
.btn-close-conv{background:rgba(218,54,51,.1);border:1px solid var(--red);color:var(--red)}
.btn-close-conv:hover{background:var(--red);color:#fff}
.conv-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700}
.conv-badge.open{background:rgba(66,165,245,.12);color:var(--accentL);border:1px solid rgba(66,165,245,.3)}
.conv-badge.operator{background:rgba(46,160,67,.12);color:#3fb950;border:1px solid rgba(46,160,67,.3)}
.conv-badge.resolved{background:rgba(210,153,34,.1);color:#e3b341;border:1px solid rgba(210,153,34,.3)}
.conv-badge.closed{background:rgba(139,92,246,.1);color:#a78bfa;border:1px solid rgba(139,92,246,.3)}

/* ══ TYPING INDICATOR ════════════════════════════ */
#user-typing-row{display:none;padding:4px 12px 2px;align-items:center;gap:6px;font-size:11px;color:var(--muted);flex-shrink:0}
.typing-dots{display:inline-flex;gap:3px;align-items:center}
.typing-dots span{width:4px;height:4px;border-radius:50%;background:var(--muted);display:inline-block;animation:tdBounce .9s ease infinite}
.typing-dots span:nth-child(2){animation-delay:.15s}
.typing-dots span:nth-child(3){animation-delay:.3s}
@keyframes tdBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-4px)}}

/* ══ AVAILABILITY TOGGLE ══════════════════════════ */
#avail-toggle-wrap{display:flex;align-items:center;gap:8px;margin-bottom:6px}
#avail-dot{width:8px;height:8px;border-radius:50%;background:var(--muted);flex-shrink:0;transition:.3s}
#avail-dot.on{background:var(--green);box-shadow:0 0 6px rgba(46,160,67,.5)}
#avail-label{font-size:11px;color:var(--muted);flex:1}
#avail-btn{font-size:10px;padding:2px 8px;border-radius:4px;background:none;border:1px solid var(--border);color:var(--muted);cursor:pointer;font-family:inherit;transition:.15s}
#avail-btn:hover{border-color:var(--green);color:var(--green)}
#avail-btn.on{border-color:var(--green);color:var(--green)}

/* ══ CANNED PICKER ═══════════════════════════════ */
#canned-picker-wrap{position:relative}
#canned-picker-btn{width:34px;height:34px;border-radius:6px;border:1px solid var(--border);background:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;transition:.2s}
#canned-picker-btn:hover{border-color:var(--accentL);color:var(--accentL)}
#canned-picker-btn.active{background:rgba(66,165,245,.1);border-color:var(--accentL);color:var(--accentL)}
#canned-picker{position:absolute;bottom:44px;left:0;width:320px;max-height:260px;overflow-y:auto;background:var(--s);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5);display:none;z-index:500}
#canned-picker.open{display:block}
.canned-search{padding:8px 10px;border-bottom:1px solid var(--border)}
.canned-search input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:5px 8px;font-size:12px;outline:none}
.canned-search input:focus{border-color:var(--accentL)}
.canned-item{padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);transition:.12s}
.canned-item:last-child{border:none}
.canned-item:hover{background:var(--s2)}
.canned-item .ci-title{font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px}
.canned-item .ci-short{font-size:10px;color:var(--accentL);margin-right:6px}
.canned-item .ci-preview{font-size:11px;color:var(--muted);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}

/* ══ SEARCH TAB ══════════════════════════════════ */
.search-bar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end}
.search-bar input,.search-bar select{background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:7px 10px;font-size:13px;outline:none}
.search-bar input:focus,.search-bar select:focus{border-color:var(--accentL)}
.search-bar .search-input{flex:1;min-width:180px}
.search-highlight{background:rgba(66,165,245,.25);border-radius:2px;color:var(--accentL)}

/* ══ ANALYTICS TAB ═══════════════════════════════ */
.analytics-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.an-card{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px}
.an-card .an-num{font-size:24px;font-weight:700;margin-bottom:2px}
.an-card .an-lbl{font-size:11px;color:var(--muted)}
.chart-wrap{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:16px}
.chart-wrap h4{font-size:13px;font-weight:700;margin-bottom:12px;color:var(--accentL)}
.chart-bars{display:flex;align-items:flex-end;gap:4px;height:80px}
.chart-bar{flex:1;background:var(--accent);border-radius:3px 3px 0 0;min-width:8px;position:relative;cursor:default;transition:.3s}
.chart-bar:hover{background:var(--accentL)}
.chart-bar-lbl{position:absolute;bottom:-16px;left:50%;transform:translateX(-50%);font-size:8px;color:var(--muted);white-space:nowrap}

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
/* ══ LOGIN DIALOG ════════════════════════════════ */
#ld-bg{position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(6px);animation:ldFadeIn .2s ease}
#ld-box{background:var(--s);border:1px solid var(--border);border-radius:20px;padding:36px 32px;text-align:center;width:310px;box-shadow:0 24px 64px rgba(0,0,0,.6);animation:ldSlideUp .25s ease}
#ld-box.ld-err{border-top:3px solid var(--red)}
#ld-box.ld-ok{border-top:3px solid var(--green)}
#ld-box.ld-lock{border-top:3px solid var(--orange)}
#ld-icon-el{font-size:52px;margin-bottom:14px;line-height:1}
#ld-title-el{font-size:16px;font-weight:700;margin-bottom:8px;color:var(--text)}
#ld-msg-el{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:18px;min-height:18px}
#ld-timer{font-size:36px;font-weight:700;font-family:'Space Mono',monospace;letter-spacing:2px;color:var(--orange);margin-bottom:18px;display:none}
#ld-actions .ld-btn{width:100%;padding:10px;border-radius:8px;border:none;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:.15s}
#ld-actions .ld-btn-ok{background:var(--accent);color:#fff}
#ld-actions .ld-btn-ok:hover{background:#1976D2}
@keyframes ldFadeIn{from{opacity:0}to{opacity:1}}
@keyframes ldSlideUp{from{opacity:0;transform:translateY(16px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}

/* ══ TOAST ═══════════════════════════════════════ */
#toast{position:fixed;bottom:24px;right:24px;z-index:9000;display:flex;flex-direction:column;gap:8px}
.toast-item{background:var(--s2);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:13px;animation:slideIn .25s ease;max-width:300px}
.toast-item.ok{border-left:3px solid var(--green);color:var(--text)}
.toast-item.err{border-left:3px solid var(--red);color:var(--red)}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
</style>
<script src="assets/tinymce/tinymce.min.js"></script>
</head>
<body>

<!-- ══ LOGIN PAGE ══════════════════════════════════════ -->
<div id="login-page" <?= $isAdmin ? 'style="display:none"' : '' ?>>
  <div class="login-box">
    <div class="logo" id="login-logo">🏛️</div>
    <h2>Admin — เทศบาลนครรังสิต</h2>
    <p>กรุณาเข้าสู่ระบบเพื่อจัดการ</p>
    <input type="hidden" id="li-csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div class="login-field">
      <input type="text" id="li-user" placeholder="ชื่อผู้ใช้ (username)"
             autocomplete="username" onkeydown="if(event.key==='Enter')doLogin()">
    </div>
    <div class="login-field">
      <input type="password" id="li-pass" placeholder="รหัสผ่าน"
             autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
      <button type="button" class="eye-btn" onclick="toggleLoginPass()" title="แสดง/ซ่อนรหัสผ่าน">👁</button>
    </div>
    <button id="li-btn" class="login-submit" onclick="doLogin()">เข้าสู่ระบบ →</button>
  </div>
</div>

<!-- ══ LOGIN DIALOG ════════════════════════════════════ -->
<div id="ld-bg" style="display:none" onclick="if(event.target===this&&!_loginLocked)closeLoginDialog()">
  <div id="ld-box">
    <div id="ld-icon-el"></div>
    <div id="ld-title-el"></div>
    <div id="ld-msg-el"></div>
    <div id="ld-timer"></div>
    <div id="ld-actions"></div>
  </div>
</div>

<!-- ══ APP ════════════════════════════════════════════ -->
<div id="app" <?= !$isAdmin ? 'style="display:none"' : '' ?>>

  <!-- Sidebar -->
  <div id="sidebar">
    <div class="sb-head">
      <div class="logo" style="display:flex;align-items:center;gap:8px">
        <span id="sb-logo-emoji" style="font-size:18px;line-height:1">🏛️</span>
        <img id="sb-logo-img" src="" alt="logo" style="display:none;width:22px;height:22px;object-fit:contain;border-radius:4px">
        <span>Admin Panel</span>
      </div>
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
      <div class="nav-item" data-tab="patterns" onclick="switchTab(this)" data-role="superadmin">❓ จัดการ Q&A</div>
      <div class="nav-item" data-tab="menu" onclick="switchTab(this)" data-role="superadmin">📱 เมนูลัด Chat</div>
      <div class="nav-item" data-tab="config" onclick="switchTab(this)" data-role="superadmin">⚙️ ตั้งค่า Bot</div>
      <div class="nav-item" data-tab="log" onclick="switchTab(this)" data-role="superadmin">📋 Bot Log</div>
      <div class="nav-sep" data-role="superadmin"></div>
      <div class="nav-item" data-tab="users" onclick="switchTab(this)" data-role="superadmin">👥 เจ้าหน้าที่</div>
      <div class="nav-sep"></div>
      <div class="nav-item" data-tab="search" onclick="switchTab(this)">🔍 ค้นหาข้อความ</div>
      <div class="nav-item" data-tab="analytics" onclick="switchTab(this)">📈 รายงาน</div>
      <div class="nav-item" data-tab="canned" onclick="switchTab(this)">💬 ข้อความสำเร็จรูป</div>
    </nav>
    <div class="sb-foot">
      <div id="avail-toggle-wrap">
        <div id="avail-dot"></div>
        <span id="avail-label">ออฟไลน์</span>
        <button id="avail-btn" onclick="toggleAvailable()">เปิดรับ</button>
      </div>
      <div class="admin-name" id="sb-name"><?= htmlspecialchars($adminName) ?></div>
      <button class="logout-btn" onclick="doLogout()">ออกจากระบบ</button>
    </div>
  </div>

  <!-- Main -->
  <div id="main">
    <div id="topbar">
      <h2 id="page-title">📊 Dashboard</h2>
      <span id="page-hash" title="คลิกเพื่อ copy URL หน้านี้" onclick="copyPageUrl()">#dashboard</span>
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
              <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>ประเภท</th><th>ห้อง</th><th>รอตอบ</th></tr></thead>
              <tbody id="dash-log-body"><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">กำลังโหลด...</td></tr></tbody>
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
            <div class="rl-head">💬 การสนทนา</div>
            <div id="room-items">
              <div style="padding:20px;color:var(--muted);font-size:12px;text-align:center">กำลังโหลด...</div>
            </div>
          </div>
          <div id="chat-area">
            <div id="chat-empty">
              <span style="font-size:32px">💬</span>
              <span>เลือกการสนทนาเพื่อดูข้อความ</span>
            </div>
            <div id="chat-view">
              <div id="chat-view-head">
                <div style="flex:1;min-width:0">
                  <div class="cvh-name" id="cv-room-name" style="display:flex;align-items:center;gap:8px">
                    <span id="cv-room-name-text">—</span>
                    <span id="conv-online" class="conv-online" style="display:none"></span>
                  </div>
                  <div class="cvh-sub" id="cv-room-sub">—</div>
                </div>
                <div class="op-controls">
                  <span id="conv-status-badge" class="conv-badge open" style="display:none"></span>
                  <button id="btn-takeover" class="btn btn-xs btn-takeover" onclick="takeOver()" style="display:none">🙋 รับสาย</button>
                  <button id="btn-release" class="btn btn-xs btn-release" onclick="releaseConv()" style="display:none">↩️ คืน Bot</button>
                  <button id="btn-close-conv" class="btn btn-xs btn-close-conv" onclick="closeConv()" style="display:none">✅ ปิดสนทนา</button>
                  <button class="btn btn-ghost btn-xs" onclick="exportConv()" id="btn-export" style="display:none" title="Export CSV">⬇️</button>
                  <button class="btn btn-ghost btn-xs" onclick="refreshChat()">🔄</button>
                </div>
              </div>
              <div id="user-typing-row">
                <div class="typing-dots"><span></span><span></span><span></span></div>
                <span id="user-typing-name">กำลังพิมพ์...</span>
              </div>
              <div id="messages"></div>
              <div id="reply-box">
                <div id="canned-picker-wrap">
                  <button id="canned-picker-btn" onclick="toggleCannedPicker()" title="ข้อความสำเร็จรูป">💬</button>
                  <div id="canned-picker">
                    <div class="canned-search"><input id="canned-search-input" placeholder="ค้นหา shortcut หรือหัวเรื่อง..." oninput="filterCannedPicker(this.value)"></div>
                    <div id="canned-picker-list"></div>
                  </div>
                </div>
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
            <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>Bot ตอบ</th><th>ประเภท</th><th style="min-width:180px">จัดการ</th></tr></thead>
            <tbody id="unans-body"><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">กำลังโหลด...</td></tr></tbody>
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

          <!-- Logo upload card -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-title">🖼️ Logo ระบบ</div>
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
              <div id="logo-preview-wrap" style="width:72px;height:72px;border-radius:14px;background:var(--s2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:36px;flex-shrink:0;overflow:hidden">
                <span id="logo-preview-emoji">🏛️</span>
                <img id="logo-preview-img" src="" alt="logo" style="display:none;width:100%;height:100%;object-fit:contain;padding:6px">
              </div>
              <div style="flex:1;min-width:200px">
                <div style="font-size:13px;font-weight:600;margin-bottom:6px">เปลี่ยน Logo</div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:10px">รองรับ PNG, JPG, SVG, WebP — ไม่เกิน 2MB</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <label class="btn btn-primary" style="cursor:pointer">
                    📁 เลือกไฟล์
                    <input type="file" id="logo-file-input" accept=".png,.jpg,.jpeg,.svg,.webp" style="display:none" onchange="uploadLogo(this)">
                  </label>
                  <button id="logo-delete-btn" class="btn btn-red" onclick="deleteLogo()" style="display:none">🗑️ ลบ Logo</button>
                </div>
                <div id="logo-upload-status" style="font-size:12px;margin-top:8px;display:none"></div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-title">⚙️ ตั้งค่า Bot</div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field">
                <label>ชื่อ Bot</label>
                <input type="text" id="cf-bot-name" placeholder="เช่น ChatBot">
              </div>
              <div class="field">
                <label>สี Avatar Bot</label>
                <input type="color" id="cf-bot-color" style="padding:4px;height:40px">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>คำอธิบายใต้ชื่อ Bot <span style="color:var(--muted)">(แถบหัว Widget)</span></label>
                <input type="text" id="cf-bot-sub" placeholder="เช่น ตอบคำถามอัตโนมัติ · โทร 02-xxx-xxxx">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>ข้อความต้อนรับ — หัวเรื่อง</label>
                <input type="text" id="cf-welcome-title" placeholder="สวัสดีครับ 👋">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>ข้อความต้อนรับ — รายละเอียด</label>
                <input type="text" id="cf-welcome-sub" placeholder="ยินดีต้อนรับ มีอะไรให้ช่วยไหมครับ?">
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
            <div class="card-title">🏛️ ข้อมูลองค์กร <span style="font-size:11px;color:var(--muted);font-weight:400">(ใช้ใน Rich Menu และ Bot responses)</span></div>
            <div class="form-row" style="margin-bottom:14px">
              <div class="field" style="grid-column:1/-1">
                <label>ชื่อองค์กร</label>
                <input type="text" id="cf-org-name" placeholder="เช่น เทศบาลนครรังสิต">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>ที่อยู่</label>
                <input type="text" id="cf-org-address" placeholder="เลขที่ ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์">
              </div>
              <div class="field">
                <label>เบอร์โทรหลัก</label>
                <input type="text" id="cf-org-tel" placeholder="02-xxx-xxxx">
              </div>
              <div class="field">
                <label>เบอร์ฉุกเฉิน / 24 ชม.</label>
                <input type="text" id="cf-org-emergency-tel" placeholder="02-xxx-xxxx">
              </div>
              <div class="field" style="grid-column:1/-1">
                <label>เว็บไซต์ (URL เต็ม)</label>
                <input type="url" id="cf-org-website" placeholder="https://www.example.go.th">
              </div>
              <div class="field">
                <label>Line OA</label>
                <input type="text" id="cf-org-line" placeholder="@lineid">
              </div>
              <div class="field">
                <label>Facebook URL หรือชื่อเพจ</label>
                <input type="text" id="cf-org-facebook" placeholder="https://facebook.com/page หรือ ชื่อเพจ">
              </div>
              <div class="field">
                <label>Latitude (พิกัด GPS)</label>
                <input type="number" id="cf-org-lat" step="0.00001" placeholder="เช่น 13.756331">
              </div>
              <div class="field">
                <label>Longitude (พิกัด GPS)</label>
                <input type="number" id="cf-org-lng" step="0.00001" placeholder="เช่น 100.501762">
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
              <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คำถาม</th><th>Bot ตอบ</th><th>ประเภท</th><th>รอตอบ</th><th>ห้อง</th><th>ms</th></tr></thead>
              <tbody id="log-body"><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: USERS                              -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-users">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700">👥 จัดการเจ้าหน้าที่</h3>
            <div style="margin-left:auto;display:flex;gap:8px">
              <button class="btn btn-ghost btn-xs" onclick="loadUsers()">🔄 รีเฟรช</button>
              <button class="btn btn-green" onclick="openUserModal()">+ เพิ่มเจ้าหน้าที่</button>
            </div>
          </div>
          <div class="card" style="padding:0;overflow:hidden">
            <table class="tbl">
              <thead>
                <tr>
                  <th style="width:48px">Avatar</th>
                  <th>ชื่อ / Username</th>
                  <th style="width:110px">บทบาท</th>
                  <th style="width:80px">สถานะ</th>
                  <th style="width:140px">เข้าสู่ระบบล่าสุด</th>
                  <th style="width:130px">จัดการ</th>
                </tr>
              </thead>
              <tbody id="users-body">
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: SEARCH                             -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-search">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <h3 style="font-size:14px;font-weight:700">🔍 ค้นหาข้อความ</h3>
          </div>
          <div class="search-bar">
            <input id="search-q" class="search-input" placeholder="พิมพ์คำค้นหา..." onkeydown="if(event.key==='Enter')loadSearch()">
            <select id="search-room" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:7px 10px;font-size:13px">
              <option value="">— ทุกห้อง —</option>
            </select>
            <input type="date" id="search-date-from" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:7px 10px;font-size:13px">
            <input type="date" id="search-date-to" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:7px 10px;font-size:13px">
            <button class="btn btn-primary" onclick="loadSearch()">🔍 ค้นหา</button>
          </div>
          <div id="search-results" style="font-size:13px;color:var(--muted)">พิมพ์คำค้นหาแล้วกด Enter หรือกด ค้นหา</div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: ANALYTICS                          -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-analytics">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700">📈 รายงาน & วิเคราะห์</h3>
            <select id="analytics-days" onchange="loadAnalytics()" style="background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:13px">
              <option value="7">7 วัน</option>
              <option value="30" selected>30 วัน</option>
              <option value="90">90 วัน</option>
            </select>
            <button class="btn btn-ghost btn-xs" onclick="loadAnalytics()">🔄 รีเฟรช</button>
          </div>
          <div class="analytics-grid" id="analytics-kpi">
            <div class="an-card"><div class="an-num" id="an-resolved">—</div><div class="an-lbl">สนทนาที่ปิดแล้ว</div></div>
            <div class="an-card"><div class="an-num" id="an-response-time">—</div><div class="an-lbl">เวลาตอบเฉลี่ย (วิ)</div></div>
            <div class="an-card"><div class="an-num" id="an-csat">—</div><div class="an-lbl">คะแนน CSAT เฉลี่ย</div></div>
          </div>
          <div class="chart-wrap">
            <h4>📊 ข้อความต่อวัน</h4>
            <div id="chart-daily" class="chart-bars" style="padding-bottom:20px"></div>
          </div>
          <div class="card" style="margin-bottom:16px">
            <div class="card-title">❓ คำถามที่ Bot ตอบไม่ได้บ่อยสุด</div>
            <div id="analytics-top-unanswered" style="font-size:13px;color:var(--muted)">กำลังโหลด...</div>
          </div>
          <div class="card">
            <div class="card-title">⭐ CSAT Ratings ล่าสุด</div>
            <table class="tbl">
              <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>คะแนน</th><th>ความคิดเห็น</th></tr></thead>
              <tbody id="analytics-csat-list"><tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px">กำลังโหลด...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════ -->
      <!-- TAB: CANNED RESPONSES                   -->
      <!-- ════════════════════════════════════════ -->
      <div class="tab-panel" id="tab-canned">
        <div class="scroll-area">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <h3 style="font-size:14px;font-weight:700">💬 ข้อความสำเร็จรูป</h3>
            <div style="margin-left:auto;display:flex;gap:8px">
              <button class="btn btn-ghost btn-xs" onclick="loadCanned()">🔄 รีเฟรช</button>
              <button class="btn btn-green" onclick="openCannedModal()">+ เพิ่มใหม่</button>
            </div>
          </div>
          <div class="card" style="padding:0;overflow:hidden">
            <table class="tbl">
              <thead><tr><th style="width:100px">Shortcut</th><th>หัวเรื่อง</th><th style="width:100px">หมวดหมู่</th><th>ตัวอย่างเนื้อหา</th><th style="width:80px">ลำดับ</th><th style="width:100px">จัดการ</th></tr></thead>
              <tbody id="canned-body"><tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">กำลังโหลด...</td></tr></tbody>
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
          <label>ข้อความตอบกลับ</label>
          <div class="resp-tabs">
            <button type="button" class="resp-tab active" data-mode="text" onclick="setRespMode('text')">📝 ข้อความธรรมดา</button>
            <button type="button" class="resp-tab" data-mode="html" onclick="setRespMode('html')">🎨 HTML (CKEditor)</button>
            <button type="button" class="resp-tab" data-mode="image" onclick="setRespMode('image')">🖼️ รูปภาพ</button>
            <button type="button" class="resp-tab" data-mode="gps" onclick="setRespMode('gps')">📍 พิกัด GPS</button>
          </div>
          <!-- text -->
          <div id="resp-panel-text" class="resp-panel active">
            <textarea id="pm-response" style="min-height:130px" placeholder="สวัสดีครับ {name}! 😊&#10;มีอะไรให้ช่วยไหมครับ?"></textarea>
            <div style="font-size:11px;color:var(--muted);margin-top:4px">ใช้ {name} = ชื่อผู้ใช้ &nbsp;|&nbsp; \n = ขึ้นบรรทัดใหม่</div>
          </div>
          <!-- html -->
          <div id="resp-panel-html" class="resp-panel">
            <textarea id="pm-response-html" style="min-height:200px"></textarea>
          </div>
          <!-- image -->
          <div id="resp-panel-image" class="resp-panel">
            <input type="text" id="pm-img-url" placeholder="URL รูปภาพ เช่น https://example.com/photo.jpg" oninput="updateImgPreview()">
            <div id="pm-img-preview" style="margin-top:8px;text-align:center;display:none">
              <img id="pm-img-preview-img" style="max-width:100%;max-height:180px;border-radius:8px;border:1px solid var(--border)" onerror="this.parentElement.style.display='none'">
            </div>
          </div>
          <!-- gps -->
          <div id="resp-panel-gps" class="resp-panel">
            <div class="form-row" style="margin-bottom:8px">
              <div class="field">
                <label>ชื่อสถานที่</label>
                <input type="text" id="pm-gps-name" placeholder="ศูนย์บริการประชาชน">
              </div>
              <div class="field">
                <label>ที่อยู่ (ไม่บังคับ)</label>
                <input type="text" id="pm-gps-addr" placeholder="เช่น 123 ถ.สุขุมวิท">
              </div>
              <div class="field">
                <label>Latitude</label>
                <input type="number" id="pm-gps-lat" step="0.00001" placeholder="เช่น 13.756331">
              </div>
              <div class="field">
                <label>Longitude</label>
                <input type="number" id="pm-gps-lng" step="0.00001" placeholder="เช่น 100.501762">
              </div>
            </div>
            <button type="button" class="btn btn-ghost" style="width:100%;margin-bottom:8px" onclick="generateGpsHtml()">🗺️ สร้าง HTML อัตโนมัติ</button>
            <textarea id="pm-gps-preview" style="min-height:80px;font-size:11px;font-family:monospace;color:var(--muted)" placeholder="HTML ที่สร้างจะแสดงที่นี่…" readonly></textarea>
          </div>
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

<!-- ══ MODAL: User Add/Edit ═══════════════════════════ -->
<div class="modal-bg" id="user-modal">
  <div class="modal" style="width:460px">
    <div class="modal-head">
      <h3 id="user-modal-title">+ เพิ่มเจ้าหน้าที่</h3>
      <button class="modal-close" onclick="closeUserModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="um-id" value="0">
      <div class="form-row" style="margin-bottom:12px">
        <div class="field">
          <label>Username <span style="color:var(--muted)">(a-z, 0-9, _ เท่านั้น)</span></label>
          <input type="text" id="um-username" placeholder="เช่น somchai_staff" autocomplete="off">
        </div>
        <div class="field">
          <label>ชื่อที่แสดง</label>
          <input type="text" id="um-display" placeholder="เช่น สมชาย ใจดี">
        </div>
        <div class="field">
          <label>บทบาท</label>
          <select id="um-role">
            <option value="staff">👤 Staff — ตอบ Chat เท่านั้น</option>
            <option value="superadmin">👑 Superadmin — จัดการทุกอย่าง</option>
          </select>
        </div>
        <div class="field">
          <label>สี Avatar</label>
          <input type="color" id="um-color" value="#1565C0" style="padding:4px;height:40px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label id="um-pass-label">รหัสผ่าน <span style="color:var(--muted)">(อย่างน้อย 8 ตัว)</span></label>
          <div style="position:relative">
            <input type="password" id="um-password" placeholder="••••••••" autocomplete="new-password"
                   style="width:100%;padding-right:44px">
            <button type="button" class="eye-btn" onclick="toggleUmPass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%)">👁</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeUserModal()">ยกเลิก</button>
      <button class="btn btn-primary" onclick="saveUser()">💾 บันทึก</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: Menu Item Add/Edit ══════════════════════ -->
<div class="modal-bg" id="menu-modal">
  <div class="modal" style="width:560px">
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
          <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <span>🔘 ตัวเลือกย่อย <span style="color:var(--muted);font-weight:400">(ปุ่มให้ผู้ใช้ระบุรายละเอียดต่อ)</span></span>
            <button type="button" class="btn btn-ghost btn-xs" onclick="addMenuChoiceRow()">+ เพิ่มตัวเลือก</button>
          </label>
          <div id="mm-choices-list"></div>
          <div id="mm-choices-empty" style="font-size:11px;color:var(--muted);padding:4px 0">
            ยังไม่มีตัวเลือก — กด "+ เพิ่มตัวเลือก" เพื่อเพิ่มปุ่มให้ผู้ใช้กดต่อ
          </div>
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

<!-- ══ MODAL: Canned Response Add/Edit ════════════════ -->
<div class="modal-bg" id="canned-modal">
  <div class="modal" style="width:560px">
    <div class="modal-head">
      <h3 id="canned-modal-title">+ เพิ่มข้อความสำเร็จรูป</h3>
      <button class="modal-close" onclick="closeCannedModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="cm-id" value="0">
      <div class="form-row" style="margin-bottom:12px">
        <div class="field">
          <label>Shortcut (เช่น /สวัสดี)</label>
          <input type="text" id="cm-shortcut" placeholder="/คำสั่ง">
        </div>
        <div class="field">
          <label>หมวดหมู่</label>
          <input type="text" id="cm-category" placeholder="ทั่วไป">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>หัวเรื่อง</label>
          <input type="text" id="cm-title" placeholder="ชื่อข้อความสำเร็จรูป">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>เนื้อหาข้อความ</label>
          <textarea id="cm-content" style="min-height:120px" placeholder="ข้อความที่จะใส่ลงในช่องตอบ..."></textarea>
        </div>
        <div class="field">
          <label>ลำดับแสดง (น้อย = ก่อน)</label>
          <input type="number" id="cm-order" value="50" min="1" max="999">
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeCannedModal()">ยกเลิก</button>
      <button class="btn btn-primary" onclick="saveCanned()">💾 บันทึก</button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══════════════════════════════════════════ -->
<div id="toast"></div>

<script>
const API = 'admin_api.php';
let currentRoomId = null, chatLastId = 0, chatPollTimer = null;
let inboxPollTimer = null, statsPollTimer = null, totalUnread = 0;
let currentConvId = null, currentConvStatus = 'open', currentBotEnabled = true;
let _adminTypingTimer = null, _cannedAll = [], _isAvailable = false;
let _patternCache = {}, _menuCache = {};
let _respMode = 'text';

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
let _adminRole   = '<?= htmlspecialchars($adminRole, ENT_QUOTES) ?>';

function applyRoleVisibility() {
  document.querySelectorAll('[data-role="superadmin"]').forEach(el => {
    el.style.display = _adminRole === 'superadmin' ? '' : 'none';
  });
}

async function doLogin() {
  if (_loginLocked) return;
  const user = document.getElementById('li-user').value.trim();
  const pass = document.getElementById('li-pass').value;
  const csrf = document.getElementById('li-csrf').value;
  const btn  = document.getElementById('li-btn');

  if (!user) { showLoginDialog('ld-err','⚠️','ข้อมูลไม่ครบ','กรุณากรอก Username'); return; }
  if (!pass) { showLoginDialog('ld-err','⚠️','ข้อมูลไม่ครบ','กรุณากรอกรหัสผ่าน'); return; }

  btn.disabled    = true;
  btn.textContent = 'กำลังตรวจสอบ...';

  const r = await api('login', { username: user, password: pass, csrf_token: csrf });

  btn.disabled    = false;
  btn.textContent = 'เข้าสู่ระบบ →';

  if (r.ok) {
    _adminRole = r.data.role || 'staff';
    showLoginDialog('ld-ok', '✅', 'เข้าสู่ระบบสำเร็จ', `ยินดีต้อนรับ ${r.data.name}`);
    setTimeout(() => {
      closeLoginDialog();
      document.getElementById('login-page').style.display = 'none';
      document.getElementById('app').style.display = '';
      document.getElementById('sb-name').textContent = r.data.name;
      applyRoleVisibility();
      initAdmin();
    }, 1400);
  } else if (r._status === 429) {
    _loginLocked = true;
    btn.disabled  = true;
    showLoginDialog('ld-lock', '🔒', 'บัญชีถูกล็อกชั่วคราว', 'กรอกรหัสผ่านผิดเกิน 5 ครั้ง กรุณารอ:', 15);
  } else {
    showLoginDialog('ld-err', '❌', 'เข้าสู่ระบบไม่สำเร็จ', r.error || 'Username หรือรหัสผ่านไม่ถูกต้อง');
  }
}

function showLoginDialog(type, icon, title, msg, lockMins = 0) {
  const bg      = document.getElementById('ld-bg');
  const box     = document.getElementById('ld-box');
  const timerEl = document.getElementById('ld-timer');
  const actEl   = document.getElementById('ld-actions');

  box.className                                    = type;
  document.getElementById('ld-icon-el').textContent  = icon;
  document.getElementById('ld-title-el').textContent = title;
  document.getElementById('ld-msg-el').textContent   = msg;

  if (lockMins > 0) {
    timerEl.style.display = 'block';
    actEl.innerHTML = '';
    let secs = lockMins * 60;
    const tick = () => {
      if (secs <= 0) {
        _loginLocked = false;
        document.getElementById('li-btn').disabled = false;
        closeLoginDialog();
        return;
      }
      const m = Math.floor(secs / 60), s = secs % 60;
      timerEl.textContent = `${m}:${String(s).padStart(2, '0')}`;
      secs--;
      setTimeout(tick, 1000);
    };
    tick();
  } else if (type === 'ld-ok') {
    timerEl.style.display = 'none';
    actEl.innerHTML = '';
  } else {
    timerEl.style.display = 'none';
    actEl.innerHTML = '<button class="ld-btn ld-btn-ok" onclick="closeLoginDialog()">ลองใหม่อีกครั้ง</button>';
  }

  bg.style.display = 'flex';
}

function closeLoginDialog() {
  document.getElementById('ld-bg').style.display = 'none';
  if (!_loginLocked) {
    document.getElementById('li-pass').value = '';
    document.getElementById('li-pass').focus();
  }
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
const TAB_LABELS = {
  dashboard:  '📊 Dashboard',
  chat:       '💬 Chat Monitor',
  unanswered: '🆘 ต้องการตอบ',
  patterns:   '❓ จัดการ Q&A',
  menu:       '📱 เมนูลัด Chat',
  config:     '⚙️ ตั้งค่า Bot',
  log:        '📋 Bot Log',
  users:      '👥 เจ้าหน้าที่',
  search:     '🔍 ค้นหาข้อความ',
  analytics:  '📈 รายงาน',
  canned:     '💬 ข้อความสำเร็จรูป',
};
function switchTab(el) {
  const tab = el.dataset.tab;
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  const label = TAB_LABELS[tab] || tab;
  document.getElementById('page-title').textContent = label;
  document.getElementById('page-hash').textContent  = '#' + tab;
  document.title = 'Admin — ' + label.replace(/^\S+\s/, '');
  history.replaceState(null, '', '#' + tab);
  onTabSwitch(tab);
}
function switchTabById(tab) {
  switchTab(document.querySelector(`[data-tab="${tab}"]`));
}
function copyPageUrl() {
  const url = location.href.split('#')[0] + '#' + (location.hash.slice(1) || 'dashboard');
  navigator.clipboard?.writeText(url).then(() => toast('Copy URL แล้ว ✅'));
}
function onTabSwitch(tab) {
  if (tab === 'patterns')  loadPatterns();
  if (tab === 'log')       loadLog();
  if (tab === 'unanswered') loadUnanswered();
  if (tab === 'config')    loadConfig();
  if (tab === 'menu')      loadMenuList();
  if (tab === 'chat')      { loadInbox(); if (currentRoomId) startChatPoll(); }
  if (tab === 'users')     loadUsers();
  if (tab === 'search')    loadSearchRooms();
  if (tab === 'analytics') loadAnalytics();
  if (tab === 'canned')    loadCanned();
}

// ══ Init ══════════════════════════════════════════════
function initAdmin() {
  applyRoleVisibility();
  // Hash routing — เปิด tab ตาม URL hash
  const hash = location.hash.slice(1);
  const target = hash && document.querySelector(`[data-tab="${hash}"]`);
  if (target) switchTab(target);
  loadStats();
  loadDashLog();
  loadInbox();
  loadRoomsForModal();
  initAvail();
  api('config').then(r => { if (r.ok && r.data.site_logo) applyLogo(r.data.site_logo); });
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
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">ยังไม่มีกิจกรรม</td></tr>'; return; }
  tbody.innerHTML = r.data.map(l => `
    <tr>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:200px;font-size:12px">${esc(trunc(l.trigger_msg, 50))}</td>
      <td>${badgeHtml(l.response_type)}</td>
      <td style="font-size:12px;color:var(--muted)">${esc(l.room_name || '—')}</td>
      <td>${replyStatusHtml(l)}</td>
    </tr>`).join('');
}

// ══ INBOX (Chat Monitor) ══════════════════════════════
async function loadInbox() {
  const r = await api('inbox');
  if (!r.ok) return;
  const el = document.getElementById('room-items');
  totalUnread = 0;
  if (!r.data.length) { el.innerHTML = '<div style="padding:20px;color:var(--muted);font-size:12px;text-align:center">ไม่มีการสนทนา</div>'; return; }
  el.innerHTML = r.data.map(conv => {
    totalUnread += (conv.unread || 0);
    const needReply  = !!conv.need_reply;
    const isBot      = conv.last_username === 'chatbot';
    const isAdm      = conv.last_username === 'admin_staff';
    const rawPreview = conv.last_msg ? conv.last_msg.replace(/<[^>]*>/g,'').replace(/\n/g,' ') : '—';
    const preview    = trunc(rawPreview, 34);
    const senderIcon = isBot ? '🤖 ' : isAdm ? '👮 ' : '';
    const dotClass   = needReply ? 'need' : 'done';
    const needClass  = needReply ? ' need-reply' : '';
    const dotInner   = needReply ? '⚡' : '';
    const userName   = conv.user_name || 'ผู้ใช้';
    const initLetter = userName[0].toUpperCase();
    const color      = conv.user_color || '#555';
    const statusBadge = conv.status === 'resolved' ? '<span class="s-badge resolved">ปิดแล้ว</span>'
                      : conv.status === 'operator'  ? '<span class="s-badge operator">เจ้าหน้าที่</span>'
                      : '';
    const isActive   = currentConvId === conv.conversation_id;
    const safeConvId = conv.conversation_id.replace(/[^a-f0-9]/g,'');
    const safeName   = esc(userName).replace(/'/g, '&#39;');
    const isOnline   = conv.is_online == 1;
    return `<div class="room-item${isActive ? ' active' : ''}${needClass}" onclick="openConv('${safeConvId}','${safeName}')">
      <div class="av-sm" style="background:${color}">${initLetter}<span class="av-online-dot${isOnline ? ' online' : ''}"></span></div>
      <div class="ri-body">
        <div class="ri-name">
          <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(userName)}</span>
          ${statusBadge}
          ${conv.unread > 0 ? `<span class="unread-badge">${conv.unread}</span>` : ''}
        </div>
        <div class="ri-preview">${senderIcon}${esc(preview)}</div>
        <div class="ri-time" style="display:flex;justify-content:space-between;align-items:center">
          <span>${conv.last_time || ''}</span>
          <span class="reply-dot ${dotClass}">${dotInner}</span>
        </div>
      </div>
    </div>`;
  }).join('');
  if (totalUnread > 0) showBadge('badge-chat', totalUnread);
  else hideBadge('badge-chat');
}

async function openConv(convId, userName) {
  currentConvId = convId;
  currentRoomId = 1;
  chatLastId    = 0;
  currentConvStatus = 'open';
  currentBotEnabled = true;
  document.getElementById('chat-empty').style.display = 'none';
  document.getElementById('chat-view').classList.add('visible');
  document.getElementById('cv-room-name-text').textContent = `💬 ${userName}`;
  const convOnline = document.getElementById('conv-online');
  if (convOnline) convOnline.style.display = 'none';
  document.getElementById('cv-room-sub').textContent  = 'กำลังโหลด...';
  document.getElementById('messages').innerHTML = '';
  document.getElementById('user-typing-row').style.display = 'none';
  document.getElementById('conv-status-badge').style.display = 'none';
  ['btn-takeover','btn-release','btn-close-conv','btn-export'].forEach(id => {
    const el = document.getElementById(id); if (el) el.style.display = 'none';
  });
  document.querySelectorAll('.room-item').forEach(el => el.classList.remove('active'));
  await loadRoomMessages(true);
  loadInbox();
  startChatPoll();
}

async function loadRoomMessages(initial = false) {
  if (!currentConvId && !currentRoomId) return;
  const _params = { room_id: currentRoomId || 1, last_id: chatLastId };
  if (currentConvId) _params.conversation_id = currentConvId;
  const r = await api('room_messages', null, _params);
  if (!r.ok) { if (initial) document.getElementById('cv-room-sub').textContent = 'โหลดไม่สำเร็จ'; return; }

  // New response: { messages, conv_info, user_typing }
  const msgs = Array.isArray(r.data) ? r.data : (r.data.messages || []);
  const convInfo  = r.data?.conv_info || null;
  const userTyping = r.data?.user_typing || null;

  if (convInfo) updateConvHead(convInfo);

  // User typing indicator
  const typingRow  = document.getElementById('user-typing-row');
  const typingName = document.getElementById('user-typing-name');
  if (userTyping) {
    typingName.textContent = `${userTyping} กำลังพิมพ์...`;
    typingRow.style.display = 'flex';
  } else {
    typingRow.style.display = 'none';
  }

  if (!msgs.length) {
    if (initial) document.getElementById('cv-room-sub').textContent = 'ยังไม่มีข้อความ';
    return;
  }
  const area  = document.getElementById('messages');
  const atBot = area.scrollHeight - area.clientHeight - area.scrollTop < 60;
  const prevSender = { username: null };
  msgs.forEach(m => {
    appendAdminMsg(m, prevSender.username);
    prevSender.username = m.username;
    if (m.id > chatLastId) chatLastId = m.id;
  });
  if (initial || atBot) area.scrollTop = area.scrollHeight;
  document.getElementById('cv-room-sub').textContent = `${msgs.length} ข้อความล่าสุด • อัปเดตอัตโนมัติ`;
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
    const name = isBot ? `🤖 ${m.display_name}` : isAdm ? `👮 ${m.display_name}` : m.display_name;
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
    } else if (m.msg_type === 'rich') {
      bubContent = m.message; // trusted bot/admin HTML card
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
  adminTyping(false);
  const r = await api('send_admin', { room_id: currentRoomId, message: msg, conversation_id: currentConvId || '' });
  if (r.ok) { clearInterval(chatPollTimer); await loadRoomMessages(false); startChatPoll(); }
  else toast(r.error || 'ส่งไม่ได้', 'err');
}
document.getElementById('reply-input')?.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAdminMsg(); }
});
document.getElementById('reply-input')?.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  // Typing debounce
  if (!currentRoomId) return;
  adminTyping(true);
  clearTimeout(_adminTypingTimer);
  _adminTypingTimer = setTimeout(() => adminTyping(false), 4000);
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
      : `${l.conversation_id ? `<button class="btn btn-primary btn-xs" onclick="goToRoom('${l.conversation_id}','${esc(l.user_name||'ผู้ใช้').replace(/'/g,"&#39;")}')">💬 ตอบ</button>` : ''}
         <button class="btn btn-green btn-xs" onclick="quickAddPattern('${esc(l.trigger_msg).replace(/'/g,'&#39;')}')">+ Q&A</button>
         <button class="btn btn-xs" style="border:1px solid var(--green);color:var(--green);background:none;border-radius:4px;padding:3px 9px;cursor:pointer;font-size:11px;font-weight:600"
                 onclick="resolveLog(${l.id},this)">✅ ตอบแล้ว</button>`;
    return `
    <tr style="${done ? 'opacity:.45' : ''}">
      <td style="font-size:11px;white-space:nowrap;color:var(--muted)">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:180px;font-size:12px">${esc(trunc(l.trigger_msg, 50))}</td>
      <td style="max-width:160px;font-size:11px;color:var(--muted)">${previewBotResp(l.bot_response, 40)}</td>
      <td>${badgeHtml(l.response_type)}</td>
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

function goToRoom(convId, userName) {
  switchTabById('chat');
  setTimeout(() => openConv(convId, userName || 'ผู้ใช้'), 200);
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
  _menuCache = {};
  r.data.forEach(m => { _menuCache[m.id] = m; });
  tbody.innerHTML = r.data.map(m => {
    const botCell = m.bot_response
      ? `<span style="font-size:11px;color:var(--muted)">${previewBotResp(m.bot_response, 45)}</span>`
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
        <button class="btn btn-ghost btn-xs" onclick="editMenuItemById(${m.id})">✏️</button>
        <button class="btn btn-red btn-xs" onclick="deleteMenuItem(${m.id})">🗑️</button>
      </td>
    </tr>`;
  }).join('');
}

function editMenuItemById(id) { editMenuItem(_menuCache[id]); }

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

// ── Menu Choices ──────────────────────────────────────
let _menuChoices = [];

function addMenuChoiceRow(label = '', message = '') {
  _menuChoices.push({ label, message });
  renderMenuChoicesList();
}
function removeMenuChoiceRow(idx) {
  _menuChoices.splice(idx, 1);
  renderMenuChoicesList();
}
function renderMenuChoicesList() {
  const el      = document.getElementById('mm-choices-list');
  const emptyEl = document.getElementById('mm-choices-empty');
  if (!el) return;
  emptyEl.style.display = _menuChoices.length ? 'none' : 'block';
  const iStyle = 'background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);padding:6px 10px;font-size:12px;outline:none;font-family:inherit';
  el.innerHTML = _menuChoices.map((c, i) => `
    <div style="display:flex;gap:6px;margin-bottom:6px;align-items:center">
      <input value="${esc(c.label)}" placeholder="ชื่อปุ่ม เช่น 🛣️ ถนน"
             oninput="_menuChoices[${i}].label=this.value"
             style="flex:1;${iStyle}">
      <input value="${esc(c.message)}" placeholder="ข้อความที่ส่งเมื่อกด"
             oninput="_menuChoices[${i}].message=this.value"
             style="flex:1.5;${iStyle}">
      <button class="btn btn-red btn-xs" onclick="removeMenuChoiceRow(${i})" style="flex-shrink:0">🗑️</button>
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
  document.getElementById('mm-useai').checked  = !!(data?.use_ai);
  _menuChoices = [];
  try { _menuChoices = JSON.parse(data?.choices || '[]') || []; } catch { _menuChoices = []; }
  renderMenuChoicesList();
  document.getElementById('menu-modal').classList.add('open');
  document.getElementById('mm-label').focus();
}
function editMenuItem(m) { openMenuModal(m); }
function closeMenuModal() { document.getElementById('menu-modal').classList.remove('open'); }

async function saveMenuItem() {
  const validChoices = _menuChoices.filter(c => c.label?.trim() && c.message?.trim());
  const fd = {
    id:           document.getElementById('mm-id').value,
    icon:         document.getElementById('mm-icon').value.trim() || '📋',
    label:        document.getElementById('mm-label').value.trim(),
    message_text: document.getElementById('mm-msg').value.trim(),
    sort_order:   document.getElementById('mm-order').value,
    is_active:    document.getElementById('mm-active').value,
    bot_response: document.getElementById('mm-response').value.trim(),
    use_ai:       document.getElementById('mm-useai').checked ? 1 : 0,
    choices:      JSON.stringify(validChoices),
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
  _patternCache = {};
  r.data.forEach(p => { _patternCache[p.id] = p; });
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
        ${p.use_ai ? '<span class="badge badge-ai">🤖 AI</span>' : previewBotResp(p.response, 70)}
      </td>
      <td style="font-size:12px;text-align:center">${p.priority}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick="editPatternById(${p.id})">✏️ แก้ไข</button>
        <button class="btn btn-red btn-xs" onclick="deletePattern(${p.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

function editPatternById(id) { editPattern(_patternCache[id]); }

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

// ── Response mode helpers ────────────────────────────────────
function setRespMode(mode) {
  _respMode = mode;
  document.querySelectorAll('.resp-tab').forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
  document.querySelectorAll('.resp-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('resp-panel-' + mode).classList.add('active');
  if (mode === 'html') initTinyMCE();
}

function initTinyMCE() {
  if (tinymce.get('pm-response-html')) return;
  tinymce.init({
    selector: '#pm-response-html',
    license_key: 'gpl',
    height: 260,
    menubar: false,
    branding: false,
    plugins: 'lists link image code',
    toolbar: 'bold italic underline strikethrough | removeformat | bullist numlist | alignleft aligncenter | forecolor backcolor | fontsize | link image | code',
    skin: 'oxide-dark',
    content_css: 'dark',
    content_style: 'body { font-family: Sarabun, sans-serif; font-size: 14px; }',
    images_upload_url: 'admin_api.php?action=tinymce_upload',
    images_upload_credentials: true,
    automatic_uploads: true,
    file_picker_types: 'image',
  });
}

function detectRespMode(resp) {
  if (!resp) return 'text';
  const t = resp.trimStart();
  if (/^<img\b/i.test(t)) return 'image';
  if (t[0] === '<') return 'html';
  return 'text';
}

function getRespValue() {
  if (_respMode === 'text') return document.getElementById('pm-response').value;
  if (_respMode === 'html') {
    const ed = tinymce.get('pm-response-html');
    return ed ? ed.getContent() : document.getElementById('pm-response-html').value;
  }
  if (_respMode === 'image') {
    const url = document.getElementById('pm-img-url').value.trim();
    return url ? `<img src="${url}" alt="รูปภาพ" style="max-width:100%;border-radius:8px;display:block">` : '';
  }
  if (_respMode === 'gps') return document.getElementById('pm-gps-preview').value;
  return '';
}

function setRespValue(resp) {
  const mode = detectRespMode(resp);
  if (mode === 'text') {
    document.getElementById('pm-response').value = resp || '';
  } else if (mode === 'html') {
    const ed = tinymce.get('pm-response-html');
    if (ed) ed.setContent(resp || '');
    else document.getElementById('pm-response-html').value = resp || '';
  } else if (mode === 'image') {
    const m = (resp || '').match(/src="([^"]+)"/);
    document.getElementById('pm-img-url').value = m ? m[1] : '';
    updateImgPreview();
  }
  setRespMode(mode);
}

function updateImgPreview() {
  const url = document.getElementById('pm-img-url').value.trim();
  const wrap = document.getElementById('pm-img-preview');
  const img  = document.getElementById('pm-img-preview-img');
  wrap.style.display = url ? 'block' : 'none';
  if (url) img.src = url;
}

function generateGpsHtml() {
  const name = document.getElementById('pm-gps-name').value.trim();
  const addr = document.getElementById('pm-gps-addr').value.trim();
  const lat  = parseFloat(document.getElementById('pm-gps-lat').value);
  const lng  = parseFloat(document.getElementById('pm-gps-lng').value);
  if (!name || isNaN(lat) || isNaN(lng)) { toast('กรุณาระบุชื่อสถานที่ และพิกัด Lat/Lng', 'err'); return; }
  const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
  const addrHtml = addr ? `<div class="rich-meta">${addr}</div>` : '';
  const html = `<div class="rich-card"><div style="font-size:12px;font-weight:700;margin-bottom:10px;color:#42A5F5">📍 ${name}</div><div class="rich-section" style="border-left:3px solid #2ea043">${addrHtml}<a href="${mapsUrl}" target="_blank" class="rich-nav-btn">🗺️ นำทาง Google Maps</a></div></div>`;
  document.getElementById('pm-gps-preview').value = html;
  toast('สร้าง HTML สำเร็จ ✅');
}

function openPatternModal(data = null) {
  document.getElementById('modal-title').textContent = data ? '✏️ แก้ไข Pattern' : '+ เพิ่ม Pattern ใหม่';
  document.getElementById('pm-id').value       = data?.id || 0;
  document.getElementById('pm-pattern').value  = data?.pattern || '';
  document.getElementById('pm-match').value    = data?.match_type || 'regex';
  document.getElementById('pm-priority').value = data?.priority ?? 50;
  document.getElementById('pm-room').value     = data?.room_id || '';
  document.getElementById('pm-active').value   = data?.is_active ?? 1;
  document.getElementById('pm-useai').checked  = !!(data?.use_ai);
  _modalChoices = [];
  try { _modalChoices = JSON.parse(data?.choices || '[]') || []; } catch { _modalChoices = []; }
  renderChoicesList();
  setRespValue(data?.response || '');
  document.getElementById('pat-modal').classList.add('open');
  document.getElementById('pm-pattern').focus();
}
function editPattern(p) { openPatternModal(p); }
function closeModal() {
  document.getElementById('pat-modal').classList.remove('open');
  const ed = tinymce.get('pm-response-html');
  if (ed) { try { ed.remove(); } catch(e){} }
  setRespMode('text');
}

async function savePattern() {
  const validChoices = _modalChoices.filter(c => c.label?.trim() && c.message?.trim());
  const fd = {
    id:         document.getElementById('pm-id').value,
    pattern:    document.getElementById('pm-pattern').value.trim(),
    match_type: document.getElementById('pm-match').value,
    response:   getRespValue(),
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

// ══ LOGO ══════════════════════════════════════════════
function applyLogo(url) {
  const hasLogo = !!url;
  const fullUrl = url ? url + '?v=' + Date.now() : '';

  // Sidebar
  document.getElementById('sb-logo-emoji').style.display = hasLogo ? 'none' : '';
  const sbImg = document.getElementById('sb-logo-img');
  sbImg.style.display = hasLogo ? 'inline' : 'none';
  if (hasLogo) sbImg.src = fullUrl;

  // Login page logo
  const loginLogo = document.getElementById('login-logo');
  if (loginLogo) loginLogo.innerHTML = hasLogo
    ? `<img src="${fullUrl}" style="width:48px;height:48px;object-fit:contain;border-radius:10px">`
    : '🏛️';

  // Config preview
  document.getElementById('logo-preview-emoji').style.display = hasLogo ? 'none' : '';
  const previewImg = document.getElementById('logo-preview-img');
  previewImg.style.display = hasLogo ? 'block' : 'none';
  if (hasLogo) previewImg.src = fullUrl;
  document.getElementById('logo-delete-btn').style.display = hasLogo ? '' : 'none';
}

async function uploadLogo(input) {
  const file = input.files[0];
  if (!file) return;
  const status = document.getElementById('logo-upload-status');
  status.style.display = 'block';
  status.style.color = 'var(--muted)';
  status.textContent = '⏳ กำลังอัปโหลด...';

  const fd = new FormData();
  fd.append('logo', file);
  try {
    const res = await fetch(`${API}?action=logo_upload`, { method:'POST', credentials:'same-origin', body: fd });
    const r = await res.json();
    if (r.ok) {
      applyLogo(r.data.url);
      status.style.color = 'var(--green)';
      status.textContent = '✅ อัปโหลดสำเร็จ';
      toast('เปลี่ยน Logo เรียบร้อยแล้ว ✅');
    } else {
      status.style.color = 'var(--red)';
      status.textContent = '❌ ' + (r.error || 'อัปโหลดไม่สำเร็จ');
    }
  } catch {
    status.style.color = 'var(--red)';
    status.textContent = '❌ เกิดข้อผิดพลาด';
  }
  input.value = '';
}

async function deleteLogo() {
  if (!confirm('ลบ Logo และใช้ค่าเริ่มต้น (🏛️) แทน?')) return;
  const r = await api('logo_delete', {});
  if (r.ok) { applyLogo(''); toast('ลบ Logo แล้ว'); }
  else toast(r.error || 'เกิดข้อผิดพลาด', 'err');
}

// ══ CONFIG ════════════════════════════════════════════
async function loadConfig() {
  const r = await api('config');
  if (!r.ok) return;
  const c = r.data;
  applyLogo(c.site_logo || '');
  document.getElementById('cf-welcome-title').value    = c.welcome_title    || '';
  document.getElementById('cf-welcome-sub').value      = c.welcome_sub      || '';
  document.getElementById('cf-bot-name').value         = c.bot_name         || '';
  document.getElementById('cf-bot-sub').value          = c.bot_sub          || '';
  document.getElementById('cf-org-name').value         = c.org_name         || '';
  document.getElementById('cf-org-address').value      = c.org_address      || '';
  document.getElementById('cf-org-tel').value          = c.org_tel          || '';
  document.getElementById('cf-org-emergency-tel').value= c.org_emergency_tel|| '';
  document.getElementById('cf-org-website').value      = c.org_website      || '';
  document.getElementById('cf-org-line').value         = c.org_line         || '';
  document.getElementById('cf-org-facebook').value     = c.org_facebook     || '';
  document.getElementById('cf-org-lat').value          = c.org_lat          || '';
  document.getElementById('cf-org-lng').value          = c.org_lng          || '';
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
    bot_name:          document.getElementById('cf-bot-name').value,
    bot_sub:           document.getElementById('cf-bot-sub').value,
    org_name:          document.getElementById('cf-org-name').value,
    org_address:       document.getElementById('cf-org-address').value,
    org_tel:           document.getElementById('cf-org-tel').value,
    org_emergency_tel: document.getElementById('cf-org-emergency-tel').value,
    org_website:       document.getElementById('cf-org-website').value,
    org_line:          document.getElementById('cf-org-line').value,
    org_facebook:      document.getElementById('cf-org-facebook').value,
    org_lat:           document.getElementById('cf-org-lat').value,
    org_lng:           document.getElementById('cf-org-lng').value,
    bot_color:       document.getElementById('cf-bot-color').value,
    bot_enabled:     document.getElementById('cf-bot-enabled').value,
    welcome_title:   document.getElementById('cf-welcome-title').value,
    welcome_sub:     document.getElementById('cf-welcome-sub').value,
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
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px">ยังไม่มี Log</td></tr>'; return; }
  tbody.innerHTML = r.data.map(l => `
    <tr>
      <td style="font-size:11px;color:var(--muted);white-space:nowrap">${esc(l.time_str)}</td>
      <td style="font-size:12px">${esc(l.user_name)}</td>
      <td style="max-width:180px;font-size:12px">${esc(trunc(l.trigger_msg,50))}</td>
      <td style="max-width:200px;font-size:12px;color:var(--muted)">${l.bot_response ? previewBotResp(l.bot_response, 60) : '<em style="color:var(--red)">ไม่มีคำตอบ</em>'}</td>
      <td>${badgeHtml(l.response_type)}</td>
      <td>${replyStatusHtml(l)}</td>
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
function stripHtml(s) { return String(s||'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim(); }
function relativeTime(isoStr) {
  if (!isoStr) return '';
  const diff = Math.floor((Date.now() - new Date(isoStr).getTime()) / 1000);
  if (diff < 60)   return 'เมื่อกี้';
  if (diff < 3600) return `${Math.floor(diff/60)} นาทีที่แล้ว`;
  if (diff < 86400)return `${Math.floor(diff/3600)} ชม.ที่แล้ว`;
  return `${Math.floor(diff/86400)} วันที่แล้ว`;
}
function previewBotResp(s, n = 50) {
  if (!s) return '<em>ไม่มี</em>';
  const isHtml = s.trimStart()[0] === '<';
  const text = isHtml ? stripHtml(s) : s;
  return (isHtml ? '<span style="color:var(--purple);font-size:10px;margin-right:4px">🎨 Rich</span>' : '') + esc(trunc(text, n));
}

function badgeHtml(type) {
  const map = { pattern:'badge-pattern', ai:'badge-ai', fallback:'badge-fallback' };
  const cls = map[type] || 'badge-pattern';
  return `<span class="badge ${cls}">${type}</span>`;
}

function replyStatusHtml(l) {
  if (l.response_type !== 'fallback') return '<span style="color:var(--muted);font-size:11px">—</span>';
  if (parseInt(l.is_resolved)) {
    return `<span class="badge" style="background:rgba(46,160,67,.12);color:#3fb950;font-size:10px">✅ ตอบแล้ว</span>`;
  }
  return `<span class="badge" style="background:rgba(218,54,51,.15);color:#f85149;border:1px solid rgba(218,54,51,.4);font-size:10px;animation:needPulse 2s ease-in-out infinite">⚡ รอตอบ</span>`;
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

// ══ USERS MANAGEMENT ══════════════════════════════════
let _userCache = {};
async function loadUsers() {
  const r = await api('users_list');
  const tbody = document.getElementById('users-body');
  if (!r.ok) { tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:20px">${esc(r.error)}</td></tr>`; return; }
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">ยังไม่มีเจ้าหน้าที่</td></tr>'; return; }
  _userCache = {};
  r.data.forEach(u => { _userCache[u.id] = u; });
  tbody.innerHTML = r.data.map(u => {
    const initials = u.display_name.charAt(0).toUpperCase();
    const roleBadge = u.role === 'superadmin'
      ? '<span class="role-badge superadmin">👑 Superadmin</span>'
      : '<span class="role-badge staff">👤 Staff</span>';
    const statusHtml = u.is_active
      ? '<span class="status-dot on"></span>เปิดใช้งาน'
      : '<span class="status-dot off"></span><span style="color:var(--muted)">ปิดใช้งาน</span>';
    const toggleLabel = u.is_active ? '🔒 ปิด' : '🔓 เปิด';
    return `<tr>
      <td><div class="user-avatar" style="background:${esc(u.avatar_color)}">${esc(initials)}</div></td>
      <td>
        <div style="font-weight:600">${esc(u.display_name)}</div>
        <div style="font-size:11px;color:var(--muted)">@${esc(u.username)}</div>
      </td>
      <td>${roleBadge}</td>
      <td style="font-size:12px">${statusHtml}</td>
      <td style="font-size:11px;color:var(--muted)">${esc(u.last_login || '—')}</td>
      <td>
        <div style="display:flex;gap:5px;flex-wrap:wrap">
          <button class="btn btn-ghost btn-xs" onclick="openUserModal(_userCache[${u.id}])">✏️ แก้ไข</button>
          <button class="btn btn-ghost btn-xs" onclick="toggleUser(${u.id})">${toggleLabel}</button>
          <button class="btn btn-red btn-xs" onclick="deleteUser(${u.id},'${esc(u.display_name).replace(/'/g,"\\'")}')">🗑️</button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function openUserModal(data = null) {
  const isEdit = !!data;
  document.getElementById('user-modal-title').textContent = isEdit ? '✏️ แก้ไขเจ้าหน้าที่' : '+ เพิ่มเจ้าหน้าที่';
  document.getElementById('um-id').value        = data?.id || 0;
  document.getElementById('um-username').value  = data?.username || '';
  document.getElementById('um-display').value   = data?.display_name || '';
  document.getElementById('um-role').value      = data?.role || 'staff';
  document.getElementById('um-color').value     = data?.avatar_color || '#1565C0';
  document.getElementById('um-password').value  = '';
  document.getElementById('um-pass-label').innerHTML = isEdit
    ? 'รหัสผ่านใหม่ <span style="color:var(--muted)">(เว้นว่างถ้าไม่เปลี่ยน)</span>'
    : 'รหัสผ่าน <span style="color:var(--muted)">(อย่างน้อย 8 ตัว)</span>';
  document.getElementById('user-modal').classList.add('open');
  document.getElementById('um-username').focus();
}
function closeUserModal() { document.getElementById('user-modal').classList.remove('open'); }

function toggleUmPass() {
  const inp = document.getElementById('um-password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

async function saveUser() {
  const fd = {
    id:           document.getElementById('um-id').value,
    username:     document.getElementById('um-username').value.trim(),
    display_name: document.getElementById('um-display').value.trim(),
    role:         document.getElementById('um-role').value,
    avatar_color: document.getElementById('um-color').value,
    password:     document.getElementById('um-password').value,
  };
  if (!fd.username || !fd.display_name) { toast('กรุณากรอก Username และชื่อ', 'err'); return; }
  const r = await api('users_save', fd);
  if (r.ok) { toast('บันทึกสำเร็จ ✅'); closeUserModal(); loadUsers(); }
  else toast(r.error || 'บันทึกไม่ได้', 'err');
}

async function toggleUser(id) {
  const r = await api('users_toggle', { id });
  if (r.ok) { toast(r.data.is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว'); loadUsers(); }
  else toast(r.error || 'เกิดข้อผิดพลาด', 'err');
}

async function deleteUser(id, name) {
  if (!confirm(`ลบบัญชี "${name}" ออก?\nการกระทำนี้ไม่สามารถย้อนกลับได้`)) return;
  const r = await api('users_delete', { id });
  if (r.ok) { toast('ลบบัญชีแล้ว'); loadUsers(); }
  else toast(r.error || 'ลบไม่ได้', 'err');
}

// ══ OPERATOR CONTROLS ═════════════════════════════════
function updateConvHead(convInfo) {
  if (!convInfo) return;
  currentConvId      = convInfo.conversation_id || currentConvId;
  currentConvStatus  = convInfo.status || 'open';
  currentBotEnabled  = convInfo.bot_enabled == 1;
  // Online status badge in header
  const onlineEl = document.getElementById('conv-online');
  if (onlineEl && convInfo.last_seen !== undefined) {
    const isOn = convInfo.is_online == 1;
    onlineEl.className = `conv-online ${isOn ? 'online' : 'offline'}`;
    onlineEl.textContent = isOn ? 'Online' : relativeTime(convInfo.last_seen);
    onlineEl.style.display = '';
  }
  const badge = document.getElementById('conv-status-badge');
  const btnTO = document.getElementById('btn-takeover');
  const btnRL = document.getElementById('btn-release');
  const btnCC = document.getElementById('btn-close-conv');
  const btnEX = document.getElementById('btn-export');
  if (!badge) return;
  const labels = { open:'🤖 Bot', operator:'👮 เจ้าหน้าที่', resolved:'✅ ปิดแล้ว', closed:'🔒 ปิด' };
  badge.textContent = labels[currentConvStatus] || currentConvStatus;
  badge.className   = `conv-badge ${currentConvStatus}`;
  badge.style.display = '';
  const isResolved = currentConvStatus === 'resolved' || currentConvStatus === 'closed';
  btnTO.style.display  = (!isResolved && currentBotEnabled) ? '' : 'none';
  btnRL.style.display  = (!isResolved && !currentBotEnabled) ? '' : 'none';
  btnCC.style.display  = !isResolved ? '' : 'none';
  if (btnEX) btnEX.style.display = '';
  // Disable reply when resolved
  const ri = document.getElementById('reply-input');
  const rs = document.getElementById('reply-send');
  if (ri) ri.disabled = isResolved;
  if (rs) rs.disabled = isResolved;
}

async function takeOver() {
  if (!currentConvId) { toast('ยังไม่มีการสนทนา', 'err'); return; }
  const r = await api('take_over', { room_id: currentRoomId || 1, conversation_id: currentConvId });
  if (r.ok) { toast('รับสายแล้ว ✅'); await loadRoomMessages(false); }
  else toast(r.error || 'ไม่สำเร็จ', 'err');
}

async function releaseConv() {
  if (!currentConvId) return;
  if (!confirm('คืนการสนทนาให้ Bot?')) return;
  const r = await api('release_conv', { room_id: currentRoomId || 1, conversation_id: currentConvId });
  if (r.ok) { toast('คืนให้ Bot แล้ว'); await loadRoomMessages(false); }
  else toast(r.error || 'ไม่สำเร็จ', 'err');
}

async function closeConv() {
  if (!currentConvId) return;
  if (!confirm('ปิดการสนทนานี้?')) return;
  const r = await api('close_conv', { room_id: currentRoomId || 1, conversation_id: currentConvId });
  if (r.ok) { toast('ปิดการสนทนาแล้ว ✅'); await loadRoomMessages(false); }
  else toast(r.error || 'ไม่สำเร็จ', 'err');
}

async function exportConv() {
  if (!currentConvId) return;
  window.location.href = `${API}?action=export_conv&conversation_id=${currentConvId}`;
}

async function adminTyping(isTyping) {
  if (!currentRoomId) return;
  try { await api('admin_typing', { room_id: currentRoomId, is_typing: isTyping ? 1 : 0 }); } catch {}
}

// ══ AVAILABILITY TOGGLE ═══════════════════════════════
async function initAvail() {
  const r = await api('operator_list');
  if (!r.ok) return;
  const adminId = <?= (int)($_SESSION['admin_id'] ?? 0) ?>;
  const me = (r.data || []).find(u => u.id === adminId);
  if (me) setAvailUI(!!me.is_available);
}

function setAvailUI(on) {
  _isAvailable = on;
  const dot = document.getElementById('avail-dot');
  const lbl = document.getElementById('avail-label');
  const btn = document.getElementById('avail-btn');
  if (!dot) return;
  dot.className = on ? 'on' : '';
  lbl.textContent = on ? 'พร้อมรับ Chat' : 'ออฟไลน์';
  btn.textContent = on ? 'เปลี่ยนเป็นออฟไลน์' : 'เปิดรับ Chat';
  btn.className   = on ? 'on' : '';
}

async function toggleAvailable() {
  const r = await api('set_available', { is_available: _isAvailable ? 0 : 1 });
  if (r.ok) { setAvailUI(!!r.data.is_available); toast(r.data.is_available ? 'พร้อมรับ Chat แล้ว ✅' : 'เปลี่ยนเป็นออฟไลน์แล้ว'); }
  else toast(r.error || 'ไม่สำเร็จ', 'err');
}

// ══ CANNED PICKER ═════════════════════════════════════
async function loadCannedPicker() {
  if (_cannedAll.length) return;
  const r = await api('canned_list');
  if (r.ok) _cannedAll = r.data || [];
  renderCannedPickerList(_cannedAll);
}

function renderCannedPickerList(items) {
  const el = document.getElementById('canned-picker-list');
  if (!el) return;
  if (!items.length) { el.innerHTML = '<div style="padding:12px;color:var(--muted);font-size:12px;text-align:center">ไม่พบข้อความสำเร็จรูป</div>'; return; }
  el.innerHTML = items.map(c => `
    <div class="canned-item" onclick="insertCanned(${JSON.stringify(c.content)})">
      <div class="ci-title"><span class="ci-short">${esc(c.shortcut)}</span>${esc(c.title)}</div>
      <div class="ci-preview">${esc(c.content.replace(/\n/g,' '))}</div>
    </div>`).join('');
}

function filterCannedPicker(q) {
  q = q.toLowerCase();
  const filtered = q ? _cannedAll.filter(c =>
    c.shortcut.toLowerCase().includes(q) ||
    c.title.toLowerCase().includes(q) ||
    c.content.toLowerCase().includes(q)
  ) : _cannedAll;
  renderCannedPickerList(filtered);
}

function toggleCannedPicker() {
  const picker = document.getElementById('canned-picker');
  const btn    = document.getElementById('canned-picker-btn');
  const isOpen = picker.classList.contains('open');
  if (!isOpen) {
    _cannedAll = []; // refresh each open
    loadCannedPicker();
    document.getElementById('canned-search-input').value = '';
  }
  picker.classList.toggle('open', !isOpen);
  btn.classList.toggle('active', !isOpen);
}

function insertCanned(content) {
  const inp = document.getElementById('reply-input');
  inp.value = content;
  inp.style.height = 'auto';
  inp.style.height = Math.min(inp.scrollHeight, 100) + 'px';
  inp.focus();
  document.getElementById('canned-picker').classList.remove('open');
  document.getElementById('canned-picker-btn').classList.remove('active');
}

// Close picker when clicking outside
document.addEventListener('click', e => {
  const wrap = document.getElementById('canned-picker-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('canned-picker')?.classList.remove('open');
    document.getElementById('canned-picker-btn')?.classList.remove('active');
  }
});

// ══ CANNED RESPONSES CRUD ═════════════════════════════
async function loadCanned() {
  const r = await api('canned_list');
  const tbody = document.getElementById('canned-body');
  if (!tbody) return;
  if (!r.ok) { tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red);padding:20px">${esc(r.error)}</td></tr>`; return; }
  if (!r.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">ยังไม่มีข้อความสำเร็จรูป</td></tr>'; return; }
  tbody.innerHTML = r.data.map(c => `
    <tr>
      <td style="font-family:monospace;color:var(--accentL);font-size:12px">${esc(c.shortcut)}</td>
      <td style="font-size:13px;font-weight:600">${esc(c.title)}</td>
      <td style="font-size:11px;color:var(--muted)">${esc(c.category)}</td>
      <td style="font-size:12px;color:var(--muted);max-width:260px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${esc(c.content)}</td>
      <td style="text-align:center;font-size:12px">${c.sort_order}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick='openCannedModal(${JSON.stringify(c)})'>✏️</button>
        <button class="btn btn-red btn-xs" onclick="deleteCanned(${c.id})">🗑️</button>
      </td>
    </tr>`).join('');
  _cannedAll = r.data;
}

function openCannedModal(data = null) {
  document.getElementById('canned-modal-title').textContent = data ? '✏️ แก้ไขข้อความสำเร็จรูป' : '+ เพิ่มข้อความสำเร็จรูป';
  document.getElementById('cm-id').value       = data?.id || 0;
  document.getElementById('cm-shortcut').value = data?.shortcut || '';
  document.getElementById('cm-category').value = data?.category || 'ทั่วไป';
  document.getElementById('cm-title').value    = data?.title || '';
  document.getElementById('cm-content').value  = data?.content || '';
  document.getElementById('cm-order').value    = data?.sort_order ?? 50;
  document.getElementById('canned-modal').classList.add('open');
  document.getElementById('cm-title').focus();
}
function closeCannedModal() { document.getElementById('canned-modal').classList.remove('open'); }

async function saveCanned() {
  const fd = {
    id:         document.getElementById('cm-id').value,
    shortcut:   document.getElementById('cm-shortcut').value.trim(),
    title:      document.getElementById('cm-title').value.trim(),
    content:    document.getElementById('cm-content').value,
    category:   document.getElementById('cm-category').value.trim() || 'ทั่วไป',
    sort_order: document.getElementById('cm-order').value,
  };
  if (!fd.title || !fd.content) { toast('กรุณากรอกหัวเรื่องและเนื้อหา', 'err'); return; }
  const r = await api('canned_save', fd);
  if (r.ok) { toast('บันทึกแล้ว ✅'); closeCannedModal(); loadCanned(); }
  else toast(r.error || 'บันทึกไม่ได้', 'err');
}

async function deleteCanned(id) {
  if (!confirm('ลบข้อความสำเร็จรูปนี้?')) return;
  const r = await api('canned_delete', { id });
  if (r.ok) { toast('ลบแล้ว'); loadCanned(); }
  else toast(r.error, 'err');
}

document.getElementById('canned-modal')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeCannedModal();
});

// ══ SEARCH ════════════════════════════════════════════
async function loadSearchRooms() {
  const sel = document.getElementById('search-room');
  if (!sel || sel.options.length > 1) return;
  const r = await api('rooms');
  if (!r.ok) return;
  r.data.forEach(room => {
    const o = document.createElement('option');
    o.value = room.id; o.textContent = room.name;
    sel.appendChild(o);
  });
}

async function loadSearch() {
  const q      = document.getElementById('search-q')?.value.trim();
  const roomId = document.getElementById('search-room')?.value || '';
  const from   = document.getElementById('search-date-from')?.value || '';
  const to     = document.getElementById('search-date-to')?.value || '';
  const el     = document.getElementById('search-results');
  if (!q) { el.textContent = 'กรุณาระบุคำค้นหา'; return; }
  el.innerHTML = '<div style="color:var(--muted)">กำลังค้นหา...</div>';
  const r = await api('search_messages', null, { q, room_id: roomId, date_from: from, date_to: to });
  if (!r.ok) { el.innerHTML = `<div style="color:var(--red)">${esc(r.error)}</div>`; return; }
  if (!r.data.length) { el.innerHTML = '<div style="color:var(--muted);padding:20px 0">ไม่พบผลลัพธ์</div>'; return; }
  const highlighted = s => esc(s).replace(new RegExp(esc(q).replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'gi'), m => `<mark class="search-highlight">${m}</mark>`);
  el.innerHTML = `<div style="font-size:12px;color:var(--muted);margin-bottom:12px">พบ ${r.data.length} ผลลัพธ์</div>` +
    r.data.map(m => `
    <div class="card" style="margin-bottom:10px;padding:12px 14px">
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600">${esc(m.display_name || m.username)}</span>
        <span style="font-size:11px;color:var(--muted)">${esc(m.room_name||'')}</span>
        <span style="font-size:10px;color:var(--muted);margin-left:auto">${esc(m.time_str||'')}</span>
      </div>
      <div style="font-size:13px;line-height:1.5">${highlighted(m.message)}</div>
    </div>`).join('');
}

// ══ ANALYTICS ═════════════════════════════════════════
async function loadAnalytics() {
  const days = document.getElementById('analytics-days')?.value || 30;
  const r = await api('analytics', null, { days });
  if (!r.ok) { toast(r.error || 'โหลดไม่ได้', 'err'); return; }
  const d = r.data;

  document.getElementById('an-resolved').textContent      = d.resolved ?? '—';
  document.getElementById('an-response-time').textContent = d.avg_response_sec ? Math.round(d.avg_response_sec) + 'วิ' : '—';
  document.getElementById('an-csat').textContent          = d.csat_avg ? parseFloat(d.csat_avg).toFixed(1) + ' ⭐' : '—';

  // Daily chart
  const chartEl = document.getElementById('chart-daily');
  if (chartEl && d.daily?.length) {
    const maxVal = Math.max(...d.daily.map(x => x.user_msgs || 0), 1);
    chartEl.innerHTML = d.daily.map(x => {
      const cnt = x.user_msgs || 0;
      const h = Math.max(4, Math.round((cnt / maxVal) * 80));
      return `<div class="chart-bar" style="height:${h}px" title="${x.day}: ${cnt} ข้อความ">
        <div class="chart-bar-lbl">${x.day.slice(5)}</div>
      </div>`;
    }).join('');
  }

  // Top unanswered
  const tuEl = document.getElementById('analytics-top-unanswered');
  if (tuEl) {
    tuEl.innerHTML = (d.top_unanswered || []).length
      ? d.top_unanswered.map((u,i) => `<div style="display:flex;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);font-size:13px">
          <span style="color:var(--muted);width:20px">${i+1}.</span>
          <span style="flex:1">${esc(u.trigger_msg)}</span>
          <span style="color:var(--muted);font-size:11px">${u.cnt} ครั้ง</span>
        </div>`).join('')
      : '<div style="color:var(--muted);padding:10px 0">ไม่มีข้อมูล</div>';
  }

  // CSAT list
  const csatEl = document.getElementById('analytics-csat-list');
  if (csatEl) {
    const list = await api('csat_list', null, { days });
    csatEl.innerHTML = (list.ok && list.data.length)
      ? list.data.slice(0,20).map(c => `<tr>
          <td style="font-size:11px;color:var(--muted)">${esc(c.time_str||'')}</td>
          <td style="font-size:12px">${esc(c.user_name||'ไม่ระบุ')}</td>
          <td style="font-size:14px">${'⭐'.repeat(c.rating)}</td>
          <td style="font-size:12px;color:var(--muted)">${c.comment ? esc(c.comment) : '<em>—</em>'}</td>
        </tr>`).join('')
      : '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:16px">ยังไม่มีการให้คะแนน</td></tr>';
  }
}
</script>
</body>
</html>
