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
<title>ChatBot</title>
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
#new-chat-btn{
  width:30px;height:30px;border-radius:8px;border:1px solid rgba(255,255,255,.25);
  background:rgba(255,255,255,.12);color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:15px;flex-shrink:0;transition:.2s;padding:0;
}
#new-chat-btn:hover{background:rgba(255,255,255,.25);border-color:rgba(255,255,255,.5)}
#new-chat-btn:active{transform:scale(.93)}
#new-chat-btn title{display:none}


/* ─── Login overlay ─── */
#loginOverlay{
  position:absolute;inset:0;background:rgba(13,17,23,.97);
  display:flex;flex-direction:column;justify-content:center;
  padding:20px 18px;z-index:100;gap:0;
}
.lo-header{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.lo-av{width:40px;height:40px;border-radius:50%;background:var(--accent);
  display:flex;align-items:center;justify-content:center;
  font-size:20px;flex-shrink:0;box-shadow:0 0 0 3px rgba(21,101,192,.3)}
.lo-bot-name{font-size:13px;font-weight:700;color:var(--text)}
.lo-bot-sub{font-size:10px;color:var(--text2)}
.lo-bubble{background:var(--surface);border:1px solid var(--border);
  border-radius:4px 14px 14px 14px;padding:11px 14px;
  font-size:13px;color:var(--text);line-height:1.6;margin-bottom:6px}
.lo-bubble-hint{font-size:10px;color:var(--muted);margin-bottom:16px;padding-left:2px}
.lo-anon-chips{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.lo-anon-chip{padding:4px 11px;border-radius:20px;border:1px solid var(--border);
  background:var(--surface2);color:var(--text2);font-size:11px;cursor:pointer;transition:.15s}
.lo-anon-chip:hover{border-color:var(--accentL);color:var(--accentL)}
#nameInput{
  width:100%;padding:10px 13px;border-radius:10px;
  border:1px solid var(--border);background:var(--surface2);
  color:var(--text);font-size:13px;outline:none;margin-bottom:10px;
}
#nameInput:focus{border-color:var(--accentL);box-shadow:0 0 0 2px rgba(66,165,245,.15)}
#loginBtn{
  width:100%;padding:10px;border-radius:10px;border:none;
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

.msg{display:flex;gap:8px;align-items:flex-end;width:100%;animation:fadeIn .25s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.msg.own{justify-content:flex-end}
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
/* ─── Rich HTML card inside bubble ─── */
.bubble a{color:var(--accentL);text-decoration:none}
.bubble a:hover{text-decoration:underline}
.rich-card{font-size:11px;line-height:1.65;color:var(--text)}
.rich-section{background:rgba(255,255,255,.05);border-radius:8px;padding:8px 10px;margin-bottom:8px}
.rich-section:last-child{margin-bottom:0}
.rich-section-title{font-weight:700;margin-bottom:4px}
.rich-meta{color:var(--text2);font-size:10px;margin-top:2px}
.rich-nav-btn{display:inline-flex;align-items:center;gap:4px;background:var(--accent);color:#fff;padding:5px 10px;border-radius:6px;text-decoration:none;font-size:10px;font-weight:600;margin-top:6px}
.rich-nav-btn:hover{background:var(--accentL);text-decoration:none;color:#fff}

.msg-meta{font-size:9px;color:var(--text2);margin-top:2px;text-align:right}
.msg.other .msg-meta,.msg.bot .msg-meta{text-align:left}

.sender-name{font-size:10px;color:var(--accentL);font-weight:600;margin-bottom:2px}

/* ─── Welcome panel (scrollable area) ─── */
#welcome-panel{
  flex:1;overflow-y:auto;display:none;flex-direction:column;
  padding:20px 14px 12px;
  /* ไม่ใช้ align-items:center — ทำให้ overflow-y ไม่ทำงาน */
}
#welcome-panel::-webkit-scrollbar{width:3px}
#welcome-panel::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.wp-hero{text-align:center;margin-bottom:18px;padding-top:4px}
.wp-logo{font-size:46px;margin-bottom:10px;filter:drop-shadow(0 2px 8px rgba(66,165,245,.3))}
.wp-title{font-size:17px;font-weight:700;color:var(--text);margin-bottom:5px}
.wp-sub{font-size:12px;color:var(--text2);line-height:1.6}

/* ─── Chip bar (pinned above inputBar, ไม่ scroll ตาม welcome panel) ─── */
#wp-chips-bar{
  display:none;flex-shrink:0;
  padding:8px 12px 6px;background:var(--surface);
  border-top:1px solid var(--border);
}
.wp-chips{
  display:flex;gap:6px;overflow-x:auto;padding:2px 0;
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

/* ─── History panel ─── */
#hist-panel,#hist-conv-panel{
  position:absolute;inset:0;z-index:190;
  background:var(--bg);display:none;flex-direction:column;
}
#hist-panel.open,#hist-conv-panel.open{display:flex}
.hist-hdr{
  display:flex;align-items:center;gap:10px;padding:10px 12px;
  background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0;
}
.hist-hdr-title{flex:1;font-size:13px;font-weight:700;color:var(--accentL)}
.hist-back{width:28px;height:28px;border-radius:50%;border:1px solid var(--border);
  background:none;color:var(--text2);cursor:pointer;font-size:16px;
  display:flex;align-items:center;justify-content:center}
.hist-back:hover{border-color:var(--accentL);color:var(--accentL)}
#hist-list{flex:1;overflow-y:auto;padding:8px 0}
#hist-list::-webkit-scrollbar{width:3px}
#hist-list::-webkit-scrollbar-thumb{background:var(--border)}
.hist-item{
  display:flex;align-items:center;gap:10px;
  padding:12px 14px;border-bottom:1px solid var(--border);
  cursor:pointer;transition:.15s;
}
.hist-item:hover{background:var(--surface2)}
.hist-item:active{background:rgba(66,165,245,.08)}
.hist-item-body{flex:1;min-width:0}
.hist-date{font-size:10px;color:var(--text2);margin-bottom:3px}
.hist-preview{font-size:12px;color:var(--text);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.hist-meta{font-size:10px;color:var(--text2);margin-top:2px}
.hist-arrow{color:var(--text2);font-size:14px;flex-shrink:0}
.hist-empty{text-align:center;padding:40px 20px;color:var(--text2);font-size:13px;line-height:1.8}
#hist-footer{padding:12px 14px;border-top:1px solid var(--border);flex-shrink:0}
.hist-del-btn{
  width:100%;padding:9px;border-radius:8px;border:1px solid var(--red);
  background:none;color:var(--red);font-size:12px;cursor:pointer;
  font-family:inherit;transition:.15s;
}
.hist-del-btn:hover{background:var(--red);color:#fff}
#hist-conv-msgs{flex:1;overflow-y:auto;padding:10px;display:flex;flex-direction:column;gap:6px}
#hist-conv-msgs::-webkit-scrollbar{width:4px}
#hist-conv-msgs::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.hist-readonly-bar{
  padding:6px 12px;background:rgba(210,153,34,.08);border-top:1px solid rgba(210,153,34,.2);
  font-size:10px;color:var(--orange);text-align:center;flex-shrink:0;
}

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
  height:34px;padding:0 10px;border-radius:17px;
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

/* ─── Typing indicator ─── */
#typing-indicator{display:none;padding:0 10px 6px;flex-shrink:0;pointer-events:none}
.typing-wrap{display:inline-flex;align-items:center;gap:7px;background:var(--bot);padding:6px 12px;border-radius:10px;font-size:11px;color:var(--text2);border-left:2px solid var(--accentL)}
.typing-dots{display:flex;gap:3px;align-items:center}
.typing-dots span{width:5px;height:5px;border-radius:50%;background:var(--accentL);display:inline-block;animation:typingBounce .9s ease infinite}
.typing-dots span:nth-child(2){animation-delay:.15s}
.typing-dots span:nth-child(3){animation-delay:.3s}
@keyframes typingBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

/* ─── Read receipts ─── */
.read-receipt{font-size:9px;margin-left:3px;vertical-align:middle}
.read-receipt.read{color:var(--accentL)}
.read-receipt.sent{color:var(--text2)}

/* ─── Operator status badge ─── */
#op-badge{display:none;font-size:9px;padding:1px 6px;border-radius:8px;font-weight:600;margin-left:4px;vertical-align:middle}
#op-badge.online{background:rgba(46,160,67,.25);color:#4caf50;border:1px solid rgba(76,175,80,.4)}
#op-badge.bot-only{background:rgba(66,165,245,.15);color:var(--accentL);border:1px solid rgba(66,165,245,.3)}

/* ─── Conversation status bar ─── */
#conv-status-bar{display:none;padding:5px 12px;font-size:11px;text-align:center;flex-shrink:0}
#conv-status-bar.operator{background:rgba(46,160,67,.1);color:#4caf50;border-bottom:1px solid rgba(76,175,80,.2)}
#conv-status-bar.resolved{background:rgba(210,153,34,.1);color:#e3b341;border-bottom:1px solid rgba(210,153,34,.2)}

/* ─── CSAT Modal ─── */
#csat-overlay{position:absolute;inset:0;background:rgba(15,25,35,.95);z-index:300;display:none;flex-direction:column;align-items:center;justify-content:center;padding:24px}
#csat-overlay.open{display:flex}
.csat-box{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px 20px;width:100%;max-width:300px;text-align:center}
.csat-title{font-size:15px;font-weight:700;margin-bottom:6px;color:var(--text)}
.csat-sub{font-size:12px;color:var(--text2);margin-bottom:18px;line-height:1.5}
.csat-stars{display:flex;justify-content:center;gap:8px;margin-bottom:16px}
.csat-star{font-size:28px;cursor:pointer;transition:.15s;opacity:.35;filter:grayscale(1)}
.csat-star.active,.csat-star:hover~.csat-star,.csat-stars:hover .csat-star:hover{opacity:1;filter:none}
.csat-stars:hover .csat-star{opacity:.35;filter:grayscale(1)}
.csat-stars:hover .csat-star:hover,.csat-stars:hover .csat-star:hover~.csat-star{opacity:.35;filter:grayscale(1)}
.csat-stars .csat-star:hover,.csat-stars .csat-star:hover~*{opacity:1!important;filter:none!important}
.csat-comment{width:100%;margin-bottom:14px;padding:8px 10px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:12px;font-family:inherit;resize:none;min-height:60px;outline:none}
.csat-comment:focus{border-color:var(--accentL)}
.csat-submit{width:100%;padding:9px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:.15s;margin-bottom:8px}
.csat-submit:hover{background:var(--accent2)}
.csat-skip{background:none;border:none;color:var(--text2);font-size:11px;cursor:pointer;font-family:inherit;text-decoration:underline}
.csat-skip:hover{color:var(--text)}
</style>
</head>
<body>
<div id="app">

  <!-- Login overlay -->
  <div id="loginOverlay">
    <div class="lo-header">
      <div class="lo-av">🤖</div>
      <div>
        <div class="lo-bot-name">น้องรังสิตา</div>
        <div class="lo-bot-sub">เทศบาลนครรังสิต · ออนไลน์</div>
      </div>
    </div>
    <div class="lo-bubble">
      สวัสดีค่ะ 😊 ยินดีต้อนรับสู่บริการออนไลน์เทศบาลนครรังสิต<br>
      <strong>ให้หนูเรียกคุณพี่ว่าอะไรดีคะ?</strong>
    </div>
    <div class="lo-bubble-hint">ระบุชื่อจริง ชื่อเล่น หรือนิรนามก็ได้ค่ะ</div>
    <div class="lo-anon-chips">
      <span class="lo-anon-chip" onclick="pickAnon('นิรนาม')">นิรนาม</span>
      <span class="lo-anon-chip" onclick="pickAnon('ผู้ใช้งาน')">ผู้ใช้งาน</span>
      <span class="lo-anon-chip" onclick="pickAnon('บุคคลทั่วไป')">บุคคลทั่วไป</span>
      <span class="lo-anon-chip" onclick="pickAnon('ไม่ระบุชื่อ')">ไม่ระบุชื่อ</span>
    </div>
    <input id="nameInput" type="text" placeholder="พิมพ์ชื่อที่นี่..." maxlength="50" autocomplete="off">
    <button id="loginBtn">เริ่มสนทนาเลย →</button>
  </div>

  <!-- Header -->
  <div id="hdr">
    <div class="bot-avatar" id="hdr-avatar">🏛️</div>
    <div class="info">
      <div class="name" id="hdr-name">ChatBot</div>
      <div class="sub" id="hdr-sub">ตอบคำถามอัตโนมัติ<span id="op-badge"></span></div>
    </div>
    <button id="hist-btn" onclick="openHistory()" title="ประวัติการสนทนา" style="width:30px;height:30px;border-radius:8px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;transition:.2s;padding:0">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
    </button>
    <button id="new-chat-btn" onclick="newChat()" title="เริ่มสนทนาใหม่">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
      </svg>
    </button>
    <div class="online-dot" title="ออนไลน์"></div>
  </div>



  <!-- Welcome panel — suggestion list (scrollable) -->
  <div id="welcome-panel">
    <div class="wp-hero">
      <div class="wp-logo" id="wp-logo">🏛️</div>
      <div class="wp-title" id="wp-title">สวัสดีครับ 👋</div>
      <div class="wp-sub" id="wp-sub">ยินดีต้อนรับสู่บริการออนไลน์<br>เทศบาลนครรังสิต</div>
    </div>
  </div>

  <!-- Chip bar — pinned, always above inputBar (not inside scroll area) -->
  <div id="wp-chips-bar">
    <div class="wp-chips" id="wp-chips"></div>
  </div>

  <!-- Conversation status bar (operator took over / resolved) -->
  <div id="conv-status-bar"></div>

  <!-- Messages -->
  <div id="msgArea"></div>

  <!-- Typing indicator -->
  <div id="typing-indicator">
    <div class="typing-wrap">
      <div class="typing-dots"><span></span><span></span><span></span></div>
      <span id="typing-who">เจ้าหน้าที่กำลังพิมพ์...</span>
    </div>
  </div>

  <!-- CSAT overlay -->
  <div id="csat-overlay">
    <div class="csat-box">
      <div class="csat-title">⭐ ความพึงพอใจในการให้บริการ</div>
      <div class="csat-sub">กรุณาให้คะแนนการสนทนาครั้งนี้<br>เพื่อพัฒนาคุณภาพการให้บริการ</div>
      <div class="csat-stars" id="csat-stars">
        <span class="csat-star" data-v="1">⭐</span>
        <span class="csat-star" data-v="2">⭐</span>
        <span class="csat-star" data-v="3">⭐</span>
        <span class="csat-star" data-v="4">⭐</span>
        <span class="csat-star" data-v="5">⭐</span>
      </div>
      <textarea id="csat-comment" class="csat-comment" placeholder="ความคิดเห็นเพิ่มเติม (ไม่บังคับ)"></textarea>
      <button class="csat-submit" id="csat-submit-btn" onclick="submitCsat()">ส่งคะแนน</button>
      <button class="csat-skip" onclick="closeCsat()">ข้ามขั้นตอนนี้</button>
    </div>
  </div>

  <!-- Quick menu panel (slides up above input bar) -->
  <div id="menuPanel">
    <div class="menu-inner">
      <div class="menu-title">⚡ เลือกหัวข้อที่ต้องการถาม</div>
      <div class="menu-grid" id="menuGrid"></div>
    </div>
  </div>

  <!-- History list panel -->
  <div id="hist-panel">
    <div class="hist-hdr">
      <button class="hist-back" onclick="closeHistory()">←</button>
      <div class="hist-hdr-title">📜 ประวัติการสนทนา</div>
    </div>
    <div id="hist-list"></div>
    <div id="hist-footer">
      <button class="hist-del-btn" onclick="clearDeviceHistory()">🗑️ ลบประวัติออกจากเครื่องนี้</button>
    </div>
  </div>

  <!-- Single conversation view panel -->
  <div id="hist-conv-panel">
    <div class="hist-hdr">
      <button class="hist-back" onclick="closeConvView()">←</button>
      <div class="hist-hdr-title" id="hist-conv-title">กำลังโหลด...</div>
    </div>
    <div id="hist-conv-msgs"></div>
    <div class="hist-readonly-bar">📖 โหมดอ่านอย่างเดียว — ข้อความในอดีต</div>
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
    <button id="menuBtn" title="เมนูลัด">เมนู</button>
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
let conversationId = '', atBottom = true;
let adminReadId = 0, _csatRating = 0, _typingTimer = null, _lastConvStatus = 'open';

// ─── Login ───────────────────────────────────────
document.getElementById('loginBtn').addEventListener('click', doLogin);
document.getElementById('nameInput').addEventListener('keydown', e => { if(e.key==='Enter') doLogin(); });

function pickAnon(name) {
  document.getElementById('nameInput').value = name;
  document.getElementById('nameInput').focus();
}

async function doLogin() {
  const name = document.getElementById('nameInput').value.trim();
  if (!name) { document.getElementById('nameInput').focus(); return; }
  const r = await api('login', { display_name: name, device_id: deviceId });
  if (r.success) {
    currentUser    = r.user;
    conversationId = r.conversation_id || '';
    document.getElementById('loginOverlay').style.display = 'none';
    startChat();
  }
}

// ─── New Chat ─────────────────────────────────────
async function newChat() {
  clearImgPreview();

  // สร้าง conversation_id ใหม่ฝั่ง server (session update)
  const r = await api('new_conversation');
  if (r.success) conversationId = r.conversation_id;

  document.getElementById('msgArea').innerHTML = '';
  document.getElementById('msgInput').value    = '';
  lastId = 0;
  showWelcome();
  clearInterval(pollTimer);
  pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
}

// ─── Welcome panel show/hide ──────────────────────
function showWelcome() {
  document.getElementById('welcome-panel').style.display  = 'flex';
  document.getElementById('wp-chips-bar').style.display  = 'block';
  document.getElementById('msgArea').style.display        = 'none';
}
function hideWelcome() {
  document.getElementById('welcome-panel').style.display  = 'none';
  document.getElementById('wp-chips-bar').style.display  = 'none';
  document.getElementById('msgArea').style.display        = '';
}

// ─── Init ─────────────────────────────────────────
async function startChat() {
  showWelcome();
  await loadMenuItems();
  switchRoom(currentRoom);
  heartTimer = setInterval(() => api('heartbeat', {}), 20000);
}

async function loadWelcomeMsg() {
  try {
    const r = await fetch(`${API}?action=widget_config`);
    const d = await r.json();
    if (!d.success) return;
    const c = d.config || {};

    // Logo
    if (c.site_logo) {
      const src = c.site_logo + '?v=' + Date.now();
      document.getElementById('hdr-avatar').innerHTML =
        `<img src="${src}" alt="logo" style="width:28px;height:28px;object-fit:contain;border-radius:6px">`;
      document.getElementById('wp-logo').innerHTML =
        `<img src="${src}" alt="logo" style="width:56px;height:56px;object-fit:contain;border-radius:14px;filter:drop-shadow(0 2px 12px rgba(66,165,245,.35))">`;
    }

    // Bot name + subtitle
    if (c.bot_name) {
      document.getElementById('hdr-name').textContent = c.bot_name;
    }
    if (c.bot_sub !== undefined) {
      const badge = document.getElementById('op-badge').outerHTML;
      document.getElementById('hdr-sub').innerHTML = (c.bot_sub || 'ตอบคำถามอัตโนมัติ') + badge;
    }

    // Welcome text
    if (c.welcome_title) {
      document.getElementById('wp-title').textContent = c.welcome_title;
    }
    if (c.welcome_sub) {
      document.getElementById('wp-sub').innerHTML = c.welcome_sub.replace(/\n/g, '<br>');
    }
  } catch {}
}

function switchRoom(id) {
  currentRoom = id;
  lastId = 0;
  document.getElementById('msgArea').innerHTML = '';
  showWelcome();
  clearInterval(pollTimer);
  pollMessages();
  pollTimer = setInterval(pollMessages, <?= CHAT_POLL_INTERVAL ?>);
}

// ─── Polling ──────────────────────────────────────
async function pollMessages() {
  const r = await api('messages', null, { room_id: currentRoom, last_id: lastId, conversation_id: conversationId, limit: 40 });
  // Handle operator typing indicator
  const typingEl = document.getElementById('typing-indicator');
  if (r.operator_typing) {
    document.getElementById('typing-who').textContent = r.operator_typing + ' กำลังพิมพ์...';
    typingEl.style.display = 'block';
  } else {
    typingEl.style.display = 'none';
  }
  // Update admin read id for receipts
  if ((r.admin_read_id || 0) > adminReadId) {
    adminReadId = r.admin_read_id;
    updateReadReceipts();
  }
  // Handle conversation status changes
  if (r.conv_status && r.conv_status !== _lastConvStatus) {
    _lastConvStatus = r.conv_status;
    updateConvStatusBar(r.conv_status, r.bot_enabled);
    if (r.conv_status === 'resolved') {
      setTimeout(showCsatModal, 1500);
    }
  }
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
    const av = `<div class="avatar" style="background:${m.avatar_color};color:${contrastColor(m.avatar_color)}">${(m.display_name||'?')[0]}</div>`;
    const senderRow = !isOwn ? `<div class="sender-name">${esc(isBot ? `🤖 ${m.display_name}` : m.display_name)}</div>` : '';

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
    } else if (m.msg_type === 'rich' && (isBot || m.username === 'admin_staff')) {
      // Rich HTML card — trusted source only, never for user messages
      bubbleContent = m.message;
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

    const receiptHtml = isOwn
      ? `<span class="read-receipt ${m.id <= adminReadId ? 'read' : 'sent'}" data-msg-id="${m.id}" title="${m.id <= adminReadId ? 'อ่านแล้ว' : 'ส่งแล้ว'}">${m.id <= adminReadId ? '✓✓' : '✓'}</span>`
      : '';
    wrap.innerHTML = (isOwn ? '' : av) +
      `<div>
        ${senderRow}
        <div class="bubble">${bubbleContent}</div>
        ${choicesHtml}
        <div class="msg-meta">${m.time_str||''}${receiptHtml}</div>
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
function contrastColor(hex) {
  const h = (hex||'#888').replace('#','');
  const r = parseInt(h.slice(0,2),16), g = parseInt(h.slice(2,4),16), b = parseInt(h.slice(4,6),16);
  return (0.299*r + 0.587*g + 0.114*b) > 140 ? '#1a1a1a' : '#ffffff';
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
    return;
  }

  // menu grid (panel เมนูลัด)
  document.getElementById('menuGrid').innerHTML = r.items.map(item =>
    `<div class="menu-item" data-msg="${esc(item.message_text)}" onclick="sendMenuMsg(this.dataset.msg)">
       <span class="menu-icon">${esc(item.icon)}</span>
       <span class="menu-label">${esc(item.label)}</span>
     </div>`
  ).join('');

  // Welcome panel — chip row (horizontal scroll)
  document.getElementById('wp-chips').innerHTML = r.items.map(item =>
    `<button class="wp-chip" data-msg="${esc(item.message_text)}" onclick="sendMenuMsg(this.dataset.msg)">
       ${esc(item.icon)} ${esc(item.label)}
     </button>`
  ).join('');

  // Auto-open after items loaded
  menuOpen = true;
  document.getElementById('menuPanel').classList.add('open');
  const btn = document.getElementById('menuBtn');
  btn.classList.add('active');
  btn.textContent = 'ซ่อนเมนู';
}

function toggleMenu() {
  menuOpen = !menuOpen;
  document.getElementById('menuPanel').classList.toggle('open', menuOpen);
  const btn = document.getElementById('menuBtn');
  btn.classList.toggle('active', menuOpen);
  btn.textContent = menuOpen ? 'ซ่อนเมนู' : 'เมนู';
}

async function sendMenuMsg(text) {
  const input = document.getElementById('msgInput');
  input.value = text;
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 80) + 'px';
  await sendMessage();
}

document.getElementById('menuBtn').addEventListener('click', toggleMenu);

// ─── Device ID management ─────────────────────────
let deviceId = '';

function getOrCreateDeviceId() {
  let id = localStorage.getItem('rungsit_device_id');
  if (!id) {
    id = (crypto.randomUUID?.() || (Date.now().toString(36) + Math.random().toString(36).slice(2))).replace(/-/g,'');
    localStorage.setItem('rungsit_device_id', id);
    document.cookie = `rungsit_device=${id}; max-age=${365*24*3600}; path=/; SameSite=Lax`;
  }
  return id;
}

// ─── Typing — send status to server (debounced) ──
document.getElementById('msgInput').addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 80) + 'px';
  if (!currentUser) return;
  api('typing', { room_id: currentRoom, is_typing: 1 });
  clearTimeout(_typingTimer);
  _typingTimer = setTimeout(() => {
    if (currentUser) api('typing', { room_id: currentRoom, is_typing: 0 });
  }, 4000);
});

// ─── Update all read receipts in DOM ─────────────
function updateReadReceipts() {
  document.querySelectorAll('.read-receipt[data-msg-id]').forEach(el => {
    const mid = parseInt(el.dataset.msgId);
    if (mid <= adminReadId) {
      el.textContent = '✓✓';
      el.className = 'read-receipt read';
      el.title = 'อ่านแล้ว';
    }
  });
}

// ─── Conversation status bar ──────────────────────
function updateConvStatusBar(status, botEnabled) {
  const bar = document.getElementById('conv-status-bar');
  if (status === 'operator') {
    bar.className = 'operator';
    bar.textContent = '🙋 เจ้าหน้าที่รับสายแล้ว';
    bar.style.display = 'block';
  } else if (status === 'resolved') {
    bar.className = 'resolved';
    bar.textContent = '✅ การสนทนาสิ้นสุดแล้ว ขอบคุณที่ใช้บริการ';
    bar.style.display = 'block';
    // Disable input
    document.getElementById('msgInput').disabled = true;
    document.getElementById('sendBtn').disabled = true;
    document.getElementById('imgBtn').disabled = true;
    document.getElementById('locBtn').disabled = true;
  } else {
    bar.style.display = 'none';
  }
}

// ─── Operator presence ────────────────────────────
async function checkOperatorPresence() {
  try {
    const r = await api('operator_presence');
    const badge = document.getElementById('op-badge');
    if (r.available) {
      badge.textContent = '🟢 มีเจ้าหน้าที่';
      badge.className = 'online';
      badge.style.display = 'inline';
    } else {
      badge.style.display = 'none';
    }
  } catch {}
}

// ─── CSAT ─────────────────────────────────────────
let _csatShown = false;

function showCsatModal() {
  if (_csatShown) return;
  _csatShown = true;
  _csatRating = 0;
  document.getElementById('csat-stars').querySelectorAll('.csat-star').forEach(s => s.classList.remove('active'));
  document.getElementById('csat-comment').value = '';
  document.getElementById('csat-overlay').classList.add('open');
}

function closeCsat() {
  document.getElementById('csat-overlay').classList.remove('open');
}

async function submitCsat() {
  if (_csatRating === 0) {
    document.getElementById('csat-stars').style.animation = 'none';
    setTimeout(() => document.getElementById('csat-stars').style.animation = '', 100);
    return;
  }
  const btn = document.getElementById('csat-submit-btn');
  btn.disabled = true;
  btn.textContent = '⏳ กำลังส่ง...';
  const r = await api('rate_csat', {
    rating: _csatRating,
    comment: document.getElementById('csat-comment').value,
    conversation_id: conversationId,
    room_id: currentRoom,
  });
  closeCsat();
  if (r.success) {
    // Show thank-you message in chat
    const div = document.createElement('div');
    div.className = 'msg sys';
    div.innerHTML = '<div class="bubble">⭐ ขอบคุณสำหรับคะแนน ' + _csatRating + '/5 ดาวครับ 🙏</div>';
    document.getElementById('msgArea').appendChild(div);
  }
}

// Star rating handler
document.getElementById('csat-stars').addEventListener('click', e => {
  const star = e.target.closest('.csat-star');
  if (!star) return;
  _csatRating = parseInt(star.dataset.v);
  document.getElementById('csat-stars').querySelectorAll('.csat-star').forEach((s, i) => {
    s.classList.toggle('active', i < _csatRating);
    s.style.opacity = i < _csatRating ? '1' : '0.35';
    s.style.filter = i < _csatRating ? 'none' : 'grayscale(1)';
  });
});

// ─── Init on page load ────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
  deviceId = getOrCreateDeviceId();
  loadWelcomeMsg();
  checkOperatorPresence();
  setInterval(checkOperatorPresence, 30000);

  // 1. Check existing PHP session (page refresh)
  const sess = await api('check_session');
  if (sess.logged_in) {
    currentUser    = sess.user;
    conversationId = sess.conversation_id || '';
    document.getElementById('loginOverlay').style.display = 'none';
    startChat();
    return;
  }

  // 2. Device auto-login (returning user, same device)
  const dev = await api('device_login', { device_id: deviceId });
  if (dev.success) {
    currentUser    = dev.user;
    conversationId = dev.conversation_id || '';
    document.getElementById('loginOverlay').style.display = 'none';
    startChat();
    return;
  }

  // 3. New user → show login overlay
  document.getElementById('nameInput').focus();
});

// ─── History panel ────────────────────────────────
async function openHistory() {
  if (!deviceId) return;
  document.getElementById('hist-panel').classList.add('open');
  document.getElementById('hist-list').innerHTML =
    '<div class="hist-empty">⏳ กำลังโหลดประวัติ...</div>';

  const r = await api('device_history', null, { device_id: deviceId });
  const list = document.getElementById('hist-list');

  if (!r.success || !r.conversations?.length) {
    list.innerHTML = '<div class="hist-empty">📭 ยังไม่มีประวัติการสนทนา<br><span style="font-size:11px">เริ่มสนทนาเพื่อบันทึกประวัติ</span></div>';
    return;
  }

  list.innerHTML = r.conversations.map(c => `
    <div class="hist-item" onclick="viewConversation('${esc(c.conversation_id)}','${esc(c.started_at)}')">
      <div class="hist-item-body">
        <div class="hist-date">📅 ${esc(c.started_at)}</div>
        <div class="hist-preview">${esc(trunc(c.first_msg || '(ไม่มีข้อความ)', 50))}</div>
        <div class="hist-meta">💬 ${esc(c.room_name)} · ${c.msg_count} ข้อความ · ล่าสุด ${esc(c.last_at)}</div>
      </div>
      <span class="hist-arrow">›</span>
    </div>`).join('');
}

function closeHistory() {
  document.getElementById('hist-panel').classList.remove('open');
}

function trunc(s, n) { s = String(s||''); return s.length > n ? s.slice(0,n)+'…' : s; }

async function viewConversation(convId, dateLabel) {
  document.getElementById('hist-conv-panel').classList.add('open');
  document.getElementById('hist-conv-title').textContent = dateLabel;
  const msgEl = document.getElementById('hist-conv-msgs');
  msgEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text2);font-size:12px">⏳ กำลังโหลด...</div>';

  const r = await api('conversation_view', null, { conversation_id: convId, device_id: deviceId });
  if (!r.success) { msgEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--red);font-size:12px">ไม่พบข้อมูล</div>'; return; }

  msgEl.innerHTML = '';
  let prevSender = null;
  r.messages.forEach(m => {
    const isOwn = !['chatbot','system','admin_staff'].includes(m.username) &&
                  m.username !== 'chatbot';
    const isBot = m.username === 'chatbot';
    const isSys = m.msg_type === 'system';

    const div = document.createElement('div');
    div.className = 'msg ' + (isSys ? 'sys' : isBot ? 'bot' : isOwn ? 'own' : 'other');

    let bubbleContent = m.msg_type === 'image'
      ? `<img src="${esc(m.message)}" alt="รูป" style="max-width:200px;border-radius:8px;display:block">`
      : m.msg_type === 'location'
        ? (() => { try { const loc=JSON.parse(m.message); return `📍 <a href="https://www.google.com/maps?q=${loc.lat},${loc.lng}" target="_blank" style="color:var(--accentL)">ดูตำแหน่ง</a>`; } catch{return '📍 ตำแหน่ง';} })()
        : formatMsg(m.message);

    if (isSys) {
      div.innerHTML = `<div class="bubble">${bubbleContent}</div>`;
    } else {
      const av  = `<div class="avatar" style="background:${m.avatar_color}">${(m.display_name||'?')[0]}</div>`;
      const snm = m.username !== prevSender && !isOwn ? `<div class="sender-name">${esc(isBot?`🤖 ${m.display_name}`:m.display_name)}</div>` : '';
      div.innerHTML = (isOwn?'':av) + `<div>${snm}<div class="bubble">${bubbleContent}</div><div class="msg-meta">${m.time_str||''}</div></div>` + (isOwn?av:'');
    }
    msgEl.appendChild(div);
    prevSender = m.username;
  });
  msgEl.scrollTop = msgEl.scrollHeight;
}

function closeConvView() {
  document.getElementById('hist-conv-panel').classList.remove('open');
}

function clearDeviceHistory() {
  if (!confirm('ลบประวัติการสนทนาออกจากเครื่องนี้?\n(ข้อมูลยังอยู่ที่ server เจ้าหน้าที่ยังเห็นได้)')) return;
  localStorage.removeItem('rungsit_device_id');
  document.cookie = 'rungsit_device=; max-age=0; path=/';
  deviceId = '';
  closeHistory();
  alert('ลบประวัติแล้ว\nหากเปิด chat ใหม่จะเริ่มต้นเป็นผู้ใช้ใหม่');
  location.reload();
}
</script>
</body>
</html>
