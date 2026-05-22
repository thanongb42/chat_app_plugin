<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>เทศบาลนครรังสิต — เว็บไซต์ทางการ</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f0f4f8;color:#333;font-size:14px}

/* ─── Top Bar ─── */
.top-bar{background:#0d47a1;color:#fff;padding:6px 0;font-size:12px}
.top-bar .inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;justify-content:space-between;align-items:center}
.top-bar a{color:rgba(255,255,255,.8);text-decoration:none;margin-left:14px}
.top-bar a:hover{color:#fff}

/* ─── Header ─── */
header{background:#1565C0;padding:0}
header .inner{max-width:1200px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;gap:18px}
.logo-box{display:flex;align-items:center;gap:14px}
.logo-seal{width:68px;height:68px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:36px;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.25)}
.logo-text .th{font-size:22px;font-weight:700;color:#fff;line-height:1.2}
.logo-text .en{font-size:11px;color:rgba(255,255,255,.75);letter-spacing:.5px;margin-top:2px}
.logo-text .province{font-size:12px;color:#BBDEFB;margin-top:2px}
header .contact-quick{margin-left:auto;text-align:right;color:#BBDEFB;font-size:12px;line-height:1.8}
header .contact-quick strong{color:#fff;font-size:14px}

/* ─── Navigation ─── */
nav{background:#0d47a1;border-top:1px solid rgba(255,255,255,.15)}
nav .inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;gap:0;overflow-x:auto}
nav a{
  display:block;padding:13px 18px;color:rgba(255,255,255,.85);
  text-decoration:none;font-size:13px;font-weight:500;
  white-space:nowrap;border-bottom:3px solid transparent;transition:.2s;
}
nav a:hover,nav a.active{color:#fff;border-bottom-color:#42A5F5;background:rgba(255,255,255,.07)}

/* ─── Hero banner ─── */
.hero{
  background:linear-gradient(135deg,#1565C0 0%,#0d47a1 50%,#1a237e 100%);
  color:#fff;padding:50px 20px;text-align:center;position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero .inner{max-width:1200px;margin:0 auto;position:relative}
.hero h1{font-size:28px;font-weight:700;margin-bottom:10px;text-shadow:0 1px 4px rgba(0,0,0,.3)}
.hero p{font-size:15px;color:#BBDEFB;max-width:600px;margin:0 auto 24px;line-height:1.6}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-hero{
  padding:11px 24px;border-radius:25px;font-size:13px;font-weight:600;
  cursor:pointer;text-decoration:none;transition:.2s;border:2px solid transparent;
}
.btn-hero.primary{background:#fff;color:#1565C0}
.btn-hero.primary:hover{background:#E3F2FD}
.btn-hero.outline{border-color:rgba(255,255,255,.6);color:#fff}
.btn-hero.outline:hover{background:rgba(255,255,255,.12)}

/* ─── Alert bar ─── */
.alert-bar{background:#E3F2FD;border-bottom:2px solid #1565C0;padding:10px 20px}
.alert-bar .inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:10px;font-size:13px}
.alert-bar .tag{background:#1565C0;color:#fff;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.alert-bar .marquee-text{overflow:hidden;white-space:nowrap;flex:1;color:#1565C0;font-weight:500}

/* ─── Main layout ─── */
.main-wrap{max-width:1200px;margin:28px auto;padding:0 20px;display:grid;grid-template-columns:1fr 300px;gap:24px}
@media(max-width:900px){.main-wrap{grid-template-columns:1fr}}

/* ─── Section title ─── */
.section-title{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-title h2{font-size:17px;font-weight:700;color:#0d47a1}
.section-title .line{flex:1;height:2px;background:linear-gradient(90deg,#1565C0,transparent)}

/* ─── e-Service grid ─── */
.eservice-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px}
@media(max-width:700px){.eservice-grid{grid-template-columns:repeat(2,1fr)}}
.eservice-card{
  background:#fff;border-radius:12px;padding:16px 10px;text-align:center;
  cursor:pointer;transition:.2s;border:2px solid transparent;text-decoration:none;color:#333;
  box-shadow:0 1px 4px rgba(0,0,0,.08);
}
.eservice-card:hover{border-color:#1565C0;box-shadow:0 4px 14px rgba(21,101,192,.15);transform:translateY(-2px)}
.eservice-card .icon{font-size:30px;margin-bottom:8px}
.eservice-card .label{font-size:11px;font-weight:600;color:#0d47a1;line-height:1.4}

/* ─── News cards ─── */
.news-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:28px}
@media(max-width:600px){.news-grid{grid-template-columns:1fr}}
.news-card{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);display:flex;flex-direction:column}
.news-img{height:140px;display:flex;align-items:center;justify-content:center;font-size:48px}
.news-body{padding:14px;flex:1}
.news-badge{display:inline-block;background:#E3F2FD;color:#1565C0;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;margin-bottom:6px}
.news-title{font-size:13px;font-weight:700;color:#1a237e;line-height:1.45;margin-bottom:6px}
.news-date{font-size:11px;color:#888}

/* ─── Sidebar ─── */
.sidebar{}
.widget{background:#fff;border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.widget-title{font-size:13px;font-weight:700;color:#0d47a1;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #E3F2FD}

.contact-list li{list-style:none;padding:7px 0;border-bottom:1px solid #f0f4f8;font-size:12px;display:flex;gap:8px;align-items:flex-start}
.contact-list li:last-child{border:none}
.contact-list .icon{font-size:16px;flex-shrink:0;margin-top:1px}

.quick-links a{display:flex;align-items:center;gap:8px;padding:7px 0;text-decoration:none;color:#333;font-size:12px;border-bottom:1px solid #f0f4f8;transition:.15s}
.quick-links a:last-child{border:none}
.quick-links a:hover{color:#1565C0;padding-left:4px}
.quick-links a::before{content:'›';color:#1565C0;font-weight:700}

.calendar-widget{text-align:center}
.calendar-widget .today{font-size:42px;font-weight:700;color:#1565C0;line-height:1}
.calendar-widget .month{font-size:13px;color:#888;margin-top:2px}
.calendar-widget .year{font-size:13px;color:#aaa}

/* ─── Footer ─── */
footer{background:#0d47a1;color:#BBDEFB;padding:32px 20px 16px;margin-top:32px}
footer .inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr;gap:28px}
@media(max-width:700px){footer .inner{grid-template-columns:1fr}}
footer h4{color:#fff;font-size:13px;font-weight:700;margin-bottom:12px}
footer p,footer li{font-size:12px;line-height:1.8;list-style:none}
footer a{color:#BBDEFB;text-decoration:none}
footer a:hover{color:#fff}
.footer-bottom{max-width:1200px;margin:20px auto 0;padding-top:14px;border-top:1px solid rgba(255,255,255,.15);font-size:11px;text-align:center;color:rgba(255,255,255,.5)}

/* ─── Floating chat widget ─── */
#chatFab{
  position:fixed;right:24px;bottom:24px;z-index:9000;
  width:58px;height:58px;border-radius:50%;
  background:#1565C0;color:#fff;border:none;cursor:pointer;
  box-shadow:0 4px 18px rgba(21,101,192,.5);
  display:flex;align-items:center;justify-content:center;
  transition:.3s;font-size:26px;
}
#chatFab:hover{background:#0d47a1;transform:scale(1.08);box-shadow:0 6px 24px rgba(21,101,192,.6)}
#chatFab .badge{
  position:absolute;top:-3px;right:-3px;
  background:#f44336;color:#fff;border-radius:50%;
  width:18px;height:18px;font-size:10px;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  border:2px solid #fff;animation:pulse 2s infinite;
}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}

#chatPanel{
  position:fixed;right:24px;bottom:92px;z-index:8999;
  width:360px;height:520px;
  border-radius:16px;overflow:hidden;
  box-shadow:0 8px 40px rgba(0,0,0,.3);
  display:none;flex-direction:column;
  border:1px solid rgba(21,101,192,.3);
  animation:slideUp .25s ease;
}
#chatPanel.open{display:flex}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

#chatPanel iframe{width:100%;height:100%;border:none;display:block}

/* ─── Chat panel top bar ─── */
#chatPanelBar{
  background:#1565C0;color:#fff;padding:9px 14px;
  display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
  font-size:12px;font-weight:600;
}
#chatPanelBar .close-btn{
  background:rgba(255,255,255,.2);border:none;color:#fff;
  width:22px;height:22px;border-radius:50%;cursor:pointer;
  display:flex;align-items:center;justify-content:center;font-size:14px;
  transition:.2s;
}
#chatPanelBar .close-btn:hover{background:rgba(255,255,255,.35)}

@media(max-width:500px){
  #chatPanel{right:8px;bottom:80px;width:calc(100vw - 16px);height:75vh}
}
</style>
</head>
<body>

<!-- Top bar -->
<div class="top-bar">
  <div class="inner">
    <span>📅 พุธที่ 22 พฤษภาคม 2568</span>
    <div>
      <a href="#">ผู้พิการ</a>
      <a href="#">ภาษาอังกฤษ</a>
      <a href="#">แผนผังเว็บ</a>
      <a href="#">ติดต่อเรา</a>
    </div>
  </div>
</div>

<!-- Header -->
<header>
  <div class="inner">
    <div class="logo-box">
      <div class="logo-seal">🏛️</div>
      <div class="logo-text">
        <div class="th">เทศบาลนครรังสิต</div>
        <div class="en">RANGSIT CITY MUNICIPALITY</div>
        <div class="province">จังหวัดปทุมธานี</div>
      </div>
    </div>
    <div class="contact-quick">
      <div>📞 โทร. <strong>0 2567 6000</strong></div>
      <div>💬 Line OA: <strong>@rangsitcity</strong></div>
      <div>🌐 www.rangsitcity.go.th</div>
    </div>
  </div>
</header>

<!-- Navigation -->
<nav>
  <div class="inner">
    <a href="#" class="active">หน้าหลัก</a>
    <a href="#">เกี่ยวกับเทศบาล</a>
    <a href="#">ข่าวสารและกิจกรรม</a>
    <a href="#">บริการประชาชน</a>
    <a href="#">งานทะเบียน</a>
    <a href="#">ชำระภาษี</a>
    <a href="#">ก่อสร้างและผังเมือง</a>
    <a href="#">สาธารณสุข</a>
    <a href="#">ดาวน์โหลดแบบฟอร์ม</a>
    <a href="#">ติดต่อเรา</a>
  </div>
</nav>

<!-- Alert bar -->
<div class="alert-bar">
  <div class="inner">
    <span class="tag">📢 ประกาศ</span>
    <div class="marquee-text">
      &nbsp;&nbsp;&nbsp;กำหนดชำระภาษีที่ดินและสิ่งปลูกสร้าง ประจำปี 2568 ภายในเดือนเมษายน 2568 ·
      เปิดรับลงทะเบียนเบี้ยยังชีพผู้สูงอายุ ประจำปีงบประมาณ 2569 ตั้งแต่บัดนี้เป็นต้นไป ·
      สายด่วนฉุกเฉิน RCC โทร. 0 2567 3388 ตลอด 24 ชั่วโมง
    </div>
  </div>
</div>

<!-- Hero -->
<div class="hero">
  <div class="inner">
    <h1>🏛️ ยินดีต้อนรับสู่เทศบาลนครรังสิต</h1>
    <p>ให้บริการประชาชนด้วยใจ พัฒนาเมืองอย่างยั่งยืน<br>เมืองน่าอยู่ ประชาชนมีสุข</p>
    <div class="hero-btns">
      <a href="#" class="btn-hero primary">📋 บริการออนไลน์</a>
      <a href="#" class="btn-hero outline">📞 ติดต่อเจ้าหน้าที่</a>
      <a href="#" class="btn-hero outline">🗺️ แผนที่สำนักงาน</a>
    </div>
  </div>
</div>

<!-- Main -->
<div class="main-wrap">
  <main>

    <!-- e-Services -->
    <div class="section-title">
      <h2>🖥️ บริการออนไลน์</h2>
      <div class="line"></div>
    </div>
    <div class="eservice-grid">
      <a href="#" class="eservice-card"><div class="icon">🪪</div><div class="label">งานทะเบียน<br>และบัตรประชาชน</div></a>
      <a href="#" class="eservice-card"><div class="icon">🏗️</div><div class="label">ใบอนุญาต<br>ก่อสร้าง</div></a>
      <a href="#" class="eservice-card"><div class="icon">💳</div><div class="label">ชำระ<br>ภาษี</div></a>
      <a href="#" class="eservice-card"><div class="icon">♿</div><div class="label">เบี้ยยังชีพ<br>ผู้สูงอายุ/พิการ</div></a>
      <a href="#" class="eservice-card"><div class="icon">🍽️</div><div class="label">ใบอนุญาต<br>ร้านอาหาร</div></a>
      <a href="#" class="eservice-card"><div class="icon">🏪</div><div class="label">ทะเบียน<br>พาณิชย์</div></a>
      <a href="#" class="eservice-card"><div class="icon">📄</div><div class="label">ดาวน์โหลด<br>แบบฟอร์ม</div></a>
      <a href="#" class="eservice-card"><div class="icon">📣</div><div class="label">ร้องเรียน<br>ออนไลน์</div></a>
    </div>

    <!-- News -->
    <div class="section-title">
      <h2>📰 ข่าวสารและประชาสัมพันธ์</h2>
      <div class="line"></div>
    </div>
    <div class="news-grid">
      <div class="news-card">
        <div class="news-img" style="background:#E3F2FD">🌳</div>
        <div class="news-body">
          <span class="news-badge">กิจกรรม</span>
          <div class="news-title">เทศบาลนครรังสิตจัดกิจกรรมปลูกต้นไม้เฉลิมพระเกียรติ ประจำปี 2568</div>
          <div class="news-date">📅 20 พฤษภาคม 2568</div>
        </div>
      </div>
      <div class="news-card">
        <div class="news-img" style="background:#FFF3E0">💰</div>
        <div class="news-body">
          <span class="news-badge">ประกาศ</span>
          <div class="news-title">แจ้งเตือนกำหนดชำระภาษีที่ดินและสิ่งปลูกสร้าง ประจำปี 2568</div>
          <div class="news-date">📅 15 พฤษภาคม 2568</div>
        </div>
      </div>
      <div class="news-card">
        <div class="news-img" style="background:#E8F5E9">🏥</div>
        <div class="news-body">
          <span class="news-badge">สาธารณสุข</span>
          <div class="news-title">เปิดคลินิกตรวจสุขภาพฟรีประชาชนในเขตเทศบาล วันที่ 1 มิถุนายน 2568</div>
          <div class="news-date">📅 12 พฤษภาคม 2568</div>
        </div>
      </div>
      <div class="news-card">
        <div class="news-img" style="background:#F3E5F5">👴</div>
        <div class="news-body">
          <span class="news-badge">สวัสดิการ</span>
          <div class="news-title">รับลงทะเบียนเบี้ยยังชีพผู้สูงอายุ ปีงบประมาณ 2569 ตั้งแต่บัดนี้เป็นต้นไป</div>
          <div class="news-date">📅 10 พฤษภาคม 2568</div>
        </div>
      </div>
    </div>

  </main>

  <!-- Sidebar -->
  <aside class="sidebar">

    <!-- Calendar -->
    <div class="widget">
      <div class="widget-title">📅 วันที่ปัจจุบัน</div>
      <div class="calendar-widget">
        <div class="today" id="calDay">22</div>
        <div class="month" id="calMonth">พฤษภาคม 2568</div>
        <div style="margin-top:8px;font-size:11px;color:#1565C0">วันจันทร์–ศุกร์ 08:30–16:30 น.</div>
      </div>
    </div>

    <!-- Contact -->
    <div class="widget">
      <div class="widget-title">📞 ติดต่อเทศบาล</div>
      <ul class="contact-list">
        <li><span class="icon">☎️</span><div><strong>โทรกลาง:</strong><br>0 2567 6000</div></li>
        <li><span class="icon">🆘</span><div><strong>RCC ฉุกเฉิน:</strong><br>0 2567 3388 (24 ชม.)</div></li>
        <li><span class="icon">🚑</span><div><strong>กู้ชีพ:</strong><br>0 2567 4944</div></li>
        <li><span class="icon">📠</span><div><strong>Fax:</strong><br>0 2567 6000 ต่อ 131</div></li>
        <li><span class="icon">💬</span><div><strong>Line OA:</strong><br><a href="https://line.me/R/ti/p/@rangsitcity" style="color:#06c755;font-weight:700">@rangsitcity</a></div></li>
        <li><span class="icon">🌐</span><div><a href="http://www.rangsitcity.go.th" style="color:#1565C0">www.rangsitcity.go.th</a></div></li>
        <li><span class="icon">📍</span><div>เลขที่ 151 ถ.รังสิต-ปทุมธานี<br>ต.ประชาธิปัตย์ อ.ธัญบุรี ปทุมธานี 12130</div></li>
      </ul>
    </div>

    <!-- Quick links -->
    <div class="widget">
      <div class="widget-title">🔗 ลิงก์ด่วน</div>
      <div class="quick-links">
        <a href="#">ดาวน์โหลดแบบฟอร์มทะเบียน</a>
        <a href="#">ขอใบอนุญาตก่อสร้างออนไลน์</a>
        <a href="#">ชำระภาษีออนไลน์</a>
        <a href="#">ตรวจสอบสิทธิ์เบี้ยยังชีพ</a>
        <a href="#">ร้องเรียน/แจ้งเหตุ</a>
        <a href="#">แผนที่สำนักงาน</a>
        <a href="chat_bot_admin.php" target="_blank">⚙️ Bot Admin Panel</a>
      </div>
    </div>

  </aside>
</div>

<!-- Footer -->
<footer>
  <div class="inner">
    <div>
      <h4>🏛️ เทศบาลนครรังสิต</h4>
      <p>เลขที่ 151 ถนนรังสิต-ปทุมธานี<br>
      ตำบลประชาธิปัตย์ อำเภอธัญบุรี<br>
      จังหวัดปทุมธานี 12130</p>
      <p style="margin-top:10px">📞 0 2567 6000 &nbsp;|&nbsp; 📠 Fax: 0 2567 6000 ต่อ 131</p>
      <p>💬 Line OA: <strong style="color:#06c755">@rangsitcity</strong></p>
      <p>📧 info@rangsitcity.go.th</p>
    </div>
    <div>
      <h4>🔗 เมนูหลัก</h4>
      <ul>
        <li><a href="#">หน้าหลัก</a></li>
        <li><a href="#">เกี่ยวกับเทศบาล</a></li>
        <li><a href="#">ข่าวสาร</a></li>
        <li><a href="#">บริการออนไลน์</a></li>
        <li><a href="#">ติดต่อเรา</a></li>
      </ul>
    </div>
    <div>
      <h4>📱 ช่องทางอื่น</h4>
      <ul>
        <li><a href="https://web.facebook.com/rangsitcity2016" target="_blank">📘 Facebook ทางการ</a></li>
        <li><a href="https://line.me/R/ti/p/@rangsitcity" target="_blank">💬 Line OA: @rangsitcity</a></li>
        <li><a href="#">▶️ YouTube</a></li>
        <li><a href="#">🆘 RCC: 0 2567 3388</a></li>
        <li><a href="#">🚑 กู้ชีพ: 0 2567 4944</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">© 2568 เทศบาลนครรังสิต &nbsp;|&nbsp; Powered by WordPress &nbsp;|&nbsp; พัฒนาโดยฝ่ายเทคโนโลยีสารสนเทศ</div>
</footer>

<!-- ─── Floating Chat Widget ─── -->
<button id="chatFab" onclick="toggleChat()" title="พูดคุยกับ RungsitBot">
  💬
  <span class="badge" id="chatBadge">1</span>
</button>

<div id="chatPanel">
  <div id="chatPanelBar">
    <span>🤖 RungsitBot — บริการออนไลน์เทศบาลนครรังสิต</span>
    <button class="close-btn" onclick="toggleChat()">✕</button>
  </div>
  <iframe id="chatFrame" src="" title="RungsitBot Chat"></iframe>
</div>

<script>
let chatOpen = false;
let chatLoaded = false;

function toggleChat() {
  chatOpen = !chatOpen;
  const panel = document.getElementById('chatPanel');
  const fab   = document.getElementById('chatFab');
  const badge = document.getElementById('chatBadge');

  if (chatOpen) {
    panel.classList.add('open');
    fab.innerHTML = '✕';
    fab.style.background = '#d32f2f';
    badge.style.display = 'none';
    if (!chatLoaded) {
      document.getElementById('chatFrame').src = 'chat_widget.php';
      chatLoaded = true;
    }
  } else {
    panel.classList.remove('open');
    fab.innerHTML = '💬<span class="badge" id="chatBadge" style="display:none">1</span>';
    fab.style.background = '#1565C0';
  }
}

// Update date
const now = new Date();
const thMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
document.getElementById('calDay').textContent  = now.getDate();
document.getElementById('calMonth').textContent = thMonths[now.getMonth()] + ' ' + (now.getFullYear() + 543);
</script>
</body>
</html>
