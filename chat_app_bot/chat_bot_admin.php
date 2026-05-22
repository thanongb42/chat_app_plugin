<?php
// =============================================
// chat_bot_admin.php — Bot Admin Panel
// =============================================
// เข้าใช้งาน: https://yourdomain.com/chat/chat_bot_admin.php
// ควร password-protect ด้วย .htaccess

session_start();

// ─── Simple Password Protection ─────────────
define('ADMIN_PASSWORD', 'admin1234'); // ← เปลี่ยน password ก่อนใช้งาน

if (!isset($_SESSION['bot_admin'])) {
    if ($_POST['admin_pass'] ?? '' === ADMIN_PASSWORD) {
        $_SESSION['bot_admin'] = true;
    } else {
        if (!empty($_POST['admin_pass'])) { $loginError = 'รหัสผ่านไม่ถูกต้อง'; }
        showLoginPage($loginError ?? '');
        exit;
    }
}
if (($_GET['logout'] ?? '') === '1') {
    unset($_SESSION['bot_admin']); header('Location: chat_bot_admin.php'); exit;
}

require_once __DIR__ . '/chat_config.php';
$pdo = getChatDB();

// ─── Handle Actions ──────────────────────────
$msg = '';
$act = $_POST['act'] ?? $_GET['act'] ?? '';

if ($act === 'save_config') {
    foreach (['bot_name','bot_color','bot_enabled','ai_enabled','ai_provider',
              'claude_api_key','claude_model','openai_api_key','ai_system_prompt','reply_delay_ms'] as $k) {
        $v = $_POST[$k] ?? '';
        $pdo->prepare("INSERT INTO chat_bot_config (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?, updated_at=NOW()")
            ->execute([$k, $v, $v]);
    }
    $msg = '✅ บันทึก Config สำเร็จ';
}

if ($act === 'add_pattern') {
    $pdo->prepare("INSERT INTO chat_bot_patterns (pattern,match_type,response,room_id,priority,is_active,use_ai) VALUES (?,?,?,?,?,?,?)")
        ->execute([
            $_POST['pattern'], $_POST['match_type'], $_POST['response'],
            $_POST['room_id'] ?: null, (int)$_POST['priority'],
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['use_ai'])    ? 1 : 0,
        ]);
    $msg = '✅ เพิ่ม Pattern สำเร็จ';
}

if ($act === 'del_pattern' && !empty($_GET['id'])) {
    $pdo->prepare("DELETE FROM chat_bot_patterns WHERE id=?")->execute([(int)$_GET['id']]);
    $msg = '🗑️ ลบ Pattern แล้ว';
}

if ($act === 'toggle_pattern' && !empty($_GET['id'])) {
    $pdo->prepare("UPDATE chat_bot_patterns SET is_active = !is_active WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: chat_bot_admin.php?tab=patterns'); exit;
}

if ($act === 'clear_log') {
    $pdo->exec("TRUNCATE TABLE chat_bot_log");
    $msg = '🗑️ ล้าง Log แล้ว';
}

// ─── Fetch Data ───────────────────────────────
$patterns = $pdo->query("SELECT * FROM chat_bot_patterns ORDER BY priority DESC, id")->fetchAll();
$configRaw = $pdo->query("SELECT key_name, value FROM chat_bot_config")->fetchAll(PDO::FETCH_KEY_PAIR);
$logs      = $pdo->query("SELECT * FROM chat_bot_log ORDER BY created_at DESC LIMIT 50")->fetchAll();
$rooms     = $pdo->query("SELECT id, name FROM chat_rooms ORDER BY id")->fetchAll();
$stats     = [
    'total'   => $pdo->query("SELECT COUNT(*) FROM chat_bot_log")->fetchColumn(),
    'pattern' => $pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE response_type='pattern'")->fetchColumn(),
    'ai'      => $pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE response_type='ai'")->fetchColumn(),
    'fallback'=> $pdo->query("SELECT COUNT(*) FROM chat_bot_log WHERE response_type='fallback'")->fetchColumn(),
];
$activeTab = $_GET['tab'] ?? 'patterns';

// ─── Helper ───────────────────────────────────
function cfg(array $c, string $k, string $def = ''): string {
    return htmlspecialchars($c[$k] ?? $def, ENT_QUOTES, 'UTF-8');
}

// ─────────────────────────────────────────────
function showLoginPage(string $err = ''): void { ?>
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bot Admin Login</title>
<style>
body{background:#0D0F14;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;font-family:'Segoe UI',sans-serif;}
.box{background:#161923;border:1px solid #252D3D;border-radius:16px;padding:40px;width:320px;text-align:center;color:#E8EAF0;}
h2{margin:0 0 8px;color:#7C6AF7;}p{color:#7A839A;font-size:14px;margin-bottom:24px;}
input{width:100%;padding:12px 16px;background:#0D0F14;border:1.5px solid #252D3D;border-radius:10px;color:#E8EAF0;font-size:15px;outline:none;box-sizing:border-box;margin-bottom:12px;}
button{width:100%;padding:12px;background:linear-gradient(135deg,#00D2C8,#7C6AF7);border:none;border-radius:10px;color:#fff;font-size:15px;cursor:pointer;}
.err{color:#FF5C7C;font-size:13px;margin-top:8px;}
</style></head><body>
<div class="box">
<h2>🤖 Bot Admin</h2>
<p>เข้าสู่ระบบจัดการ ChatBot</p>
<form method="POST">
<input type="password" name="admin_pass" placeholder="รหัสผ่าน" autofocus>
<button type="submit">เข้าสู่ระบบ</button>
<?php if($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
</form></div></body></html>
<?php } ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bot Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&family=Space+Mono&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0D0F14;--s:#161923;--s2:#1E2533;--border:#252D3D;--accent:#7C6AF7;--teal:#00D2C8;--text:#E8EAF0;--muted:#7A839A;--danger:#FF5C7C;--success:#00D2C8;--r:10px}
body{background:var(--bg);color:var(--text);font-family:'Sarabun',sans-serif;min-height:100vh}
a{color:var(--accent);text-decoration:none}

/* ─── Layout ─── */
.topbar{background:var(--s);border-bottom:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;gap:14px}
.topbar h1{font-size:18px;font-weight:600;flex:1}
.topbar a{font-size:13px;color:var(--muted);padding:7px 14px;border:1px solid var(--border);border-radius:8px}
.topbar a:hover{border-color:var(--danger);color:var(--danger)}
.container{max-width:1100px;margin:0 auto;padding:28px 24px}

/* ─── Stats ─── */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
.stat-card{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px}
.stat-card .num{font-size:28px;font-weight:700;font-family:'Space Mono'}
.stat-card .lbl{font-size:12px;color:var(--muted);margin-top:4px}

/* ─── Tabs ─── */
.tabs{display:flex;gap:2px;margin-bottom:20px;background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:4px}
.tab-btn{flex:1;text-align:center;padding:9px;border-radius:8px;cursor:pointer;font-size:14px;font-family:'Sarabun',sans-serif;background:none;border:none;color:var(--muted);transition:.15s}
.tab-btn.active{background:var(--accent);color:#fff;font-weight:600}
.tab-content{display:none}.tab-content.active{display:block}

/* ─── Card ─── */
.card{background:var(--s);border:1px solid var(--border);border-radius:var(--r);padding:22px;margin-bottom:20px}
.card h3{font-size:15px;font-weight:600;margin-bottom:16px;color:var(--teal)}

/* ─── Form ─── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-grid.full{grid-template-columns:1fr}
.field{display:flex;flex-direction:column;gap:6px}
.field label{font-size:12px;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:.8px}
.field input,.field select,.field textarea{background:var(--bg);border:1.5px solid var(--border);border-radius:8px;color:var(--text);padding:9px 13px;font-family:'Sarabun',sans-serif;font-size:14px;outline:none;transition:.2s}
.field input:focus,.field select,.field textarea:focus{border-color:var(--accent)}
.field textarea{resize:vertical;min-height:80px}
select option{background:var(--s)}

/* ─── Buttons ─── */
.btn{padding:9px 20px;border-radius:8px;cursor:pointer;font-family:'Sarabun',sans-serif;font-size:14px;border:none;transition:.15s}
.btn-primary{background:linear-gradient(135deg,var(--teal),var(--accent));color:#fff;font-weight:600}
.btn-danger{background:none;border:1px solid var(--danger);color:var(--danger)}
.btn-danger:hover{background:var(--danger);color:#fff}
.btn-sm{padding:5px 12px;font-size:12px;border-radius:6px}
.btn-toggle{background:none;border:1px solid var(--border);color:var(--muted)}

/* ─── Table ─── */
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;padding:10px 12px;background:var(--s2);color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--border)}
.tbl td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.tbl tr:last-child td{border:none}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.badge-pat{background:rgba(0,210,200,.12);color:var(--teal);padding:2px 8px;border-radius:20px;font-size:11px;font-family:'Space Mono'}
.badge-ai{background:rgba(124,106,247,.15);color:var(--accent);padding:2px 8px;border-radius:20px;font-size:11px;font-family:'Space Mono'}
.badge-fb{background:rgba(255,92,124,.12);color:var(--danger);padding:2px 8px;border-radius:20px;font-size:11px;font-family:'Space Mono'}
.dot-on{width:8px;height:8px;background:var(--success);border-radius:50%;display:inline-block}
.dot-off{width:8px;height:8px;background:var(--muted);border-radius:50%;display:inline-block}
.msg-flash{padding:12px 18px;border-radius:8px;margin-bottom:18px;background:rgba(0,210,200,.1);border:1px solid rgba(0,210,200,.3);color:var(--teal)}
.toggle-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--accent)}
.row-inactive td{opacity:.45}
code{background:var(--s2);padding:2px 7px;border-radius:4px;font-size:12px;font-family:'Space Mono'}
.api-hint{font-size:12px;color:var(--muted);margin-top:6px}
</style>
</head>
<body>
<div class="topbar">
  <h1>🤖 Bot Admin Panel</h1>
  <a href="chat.php">🔗 Chat</a>
  <a href="?logout=1">ออกจากระบบ</a>
</div>

<div class="container">
<?php if ($msg): ?>
<div class="msg-flash"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ─── Stats ─── -->
<div class="stats">
  <?php foreach ([
    ['ตอบทั้งหมด',   $stats['total'],   '#00D2C8'],
    ['Pattern Match', $stats['pattern'], '#7C6AF7'],
    ['AI ตอบ',        $stats['ai'],      '#45B7D1'],
    ['Fallback',      $stats['fallback'],'#FF5C7C'],
  ] as [$label,$val,$color]): ?>
  <div class="stat-card" style="border-top:3px solid <?= $color ?>">
    <div class="num" style="color:<?= $color ?>"><?= (int)$val ?></div>
    <div class="lbl"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── Tabs ─── -->
<div class="tabs">
  <?php foreach (['patterns'=>'📋 Patterns','config'=>'⚙️ Config','log'=>'📊 Log'] as $k=>$lbl): ?>
  <button class="tab-btn <?= $activeTab===$k?'active':'' ?>"
          onclick="switchTab('<?= $k ?>')"><?= $lbl ?></button>
  <?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════ -->
<!-- TAB: PATTERNS                           -->
<!-- ════════════════════════════════════════ -->
<div class="tab-content <?= $activeTab==='patterns'?'active':'' ?>" id="tab-patterns">

  <!-- Add Pattern Form -->
  <div class="card">
    <h3>+ เพิ่ม Pattern ใหม่</h3>
    <form method="POST">
    <input type="hidden" name="act" value="add_pattern">
    <div class="form-grid">
      <div class="field">
        <label>Pattern / Keyword</label>
        <input type="text" name="pattern" placeholder="เช่น ราคา|price|เท่าไร" required>
      </div>
      <div class="field">
        <label>Match Type</label>
        <select name="match_type">
          <option value="regex">Regex (รองรับ | สำหรับ หรือ)</option>
          <option value="contains">Contains (มีคำนี้ในข้อความ)</option>
          <option value="keyword">Keyword (ตรงทั้งหมด)</option>
          <option value="starts">Starts With</option>
          <option value="ends">Ends With</option>
        </select>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label>ข้อความตอบกลับ (ใช้ {name} แทนชื่อผู้ใช้)</label>
        <textarea name="response" placeholder="สวัสดีครับ {name}! มีอะไรให้ช่วยไหมครับ 😊"></textarea>
      </div>
      <div class="field">
        <label>ห้อง (ว่าง = ทุกห้อง)</label>
        <select name="room_id">
          <option value="">— ทุกห้อง —</option>
          <?php foreach($rooms as $r): ?>
          <option value="<?= $r['id'] ?>">#<?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Priority (สูง = ทำงานก่อน)</label>
        <input type="number" name="priority" value="50" min="0" max="999">
      </div>
    </div>
    <div style="display:flex;gap:20px;margin-top:14px;align-items:center">
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
        <input type="checkbox" name="is_active" class="toggle-row" checked>
        เปิดใช้งาน
      </label>
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
        <input type="checkbox" name="use_ai" class="toggle-row">
        <span style="color:var(--accent)">🤖 ส่งให้ AI ตอบแทน</span>
      </label>
      <button type="submit" class="btn btn-primary" style="margin-left:auto">+ เพิ่ม Pattern</button>
    </div>
    </form>
  </div>

  <!-- Pattern List -->
  <div class="card">
    <h3>📋 รายการ Pattern ทั้งหมด (<?= count($patterns) ?>)</h3>
    <table class="tbl">
      <tr>
        <th>สถานะ</th><th>Pattern</th><th>Type</th>
        <th>ตอบกลับ</th><th>ห้อง</th><th>Priority</th><th>จัดการ</th>
      </tr>
      <?php foreach($patterns as $p): ?>
      <tr class="<?= $p['is_active']?'':'row-inactive' ?>">
        <td><span class="<?= $p['is_active']?'dot-on':'dot-off' ?>"></span></td>
        <td><code><?= htmlspecialchars($p['pattern']) ?></code></td>
        <td><span class="badge-pat"><?= $p['match_type'] ?></span></td>
        <td>
          <?php if($p['use_ai']): ?>
            <span class="badge-ai">🤖 AI</span>
          <?php else: ?>
            <span style="font-size:13px;color:var(--muted)"><?= mb_strimwidth(htmlspecialchars($p['response']),0,60,'…') ?></span>
          <?php endif; ?>
        </td>
        <td><?= $p['room_id'] ? '#'.$p['room_id'] : '<span style="color:var(--muted)">ทุกห้อง</span>' ?></td>
        <td><code><?= $p['priority'] ?></code></td>
        <td style="white-space:nowrap">
          <a href="?act=toggle_pattern&id=<?= $p['id'] ?>" class="btn btn-sm btn-toggle">
            <?= $p['is_active']?'ปิด':'เปิด' ?>
          </a>
          <a href="?act=del_pattern&id=<?= $p['id'] ?>"
             onclick="return confirm('ลบ Pattern นี้?')"
             class="btn btn-sm btn-danger">ลบ</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<!-- ════════════════════════════════════════ -->
<!-- TAB: CONFIG                             -->
<!-- ════════════════════════════════════════ -->
<div class="tab-content <?= $activeTab==='config'?'active':'' ?>" id="tab-config">
  <div class="card">
    <h3>⚙️ ตั้งค่า Bot</h3>
    <form method="POST">
    <input type="hidden" name="act" value="save_config">
    <div class="form-grid">
      <div class="field">
        <label>ชื่อ Bot</label>
        <input type="text" name="bot_name" value="<?= cfg($configRaw,'bot_name','ChatBot 🤖') ?>">
      </div>
      <div class="field">
        <label>สี Avatar Bot</label>
        <input type="color" name="bot_color" value="<?= cfg($configRaw,'bot_color','#7C6AF7') ?>"
               style="padding:4px;height:42px">
      </div>
      <div class="field">
        <label>เปิด/ปิด Bot</label>
        <select name="bot_enabled">
          <option value="1" <?= cfg($configRaw,'bot_enabled')!=='0'?'selected':'' ?>>✅ เปิดใช้งาน</option>
          <option value="0" <?= cfg($configRaw,'bot_enabled')==='0'?'selected':'' ?>>❌ ปิดใช้งาน</option>
        </select>
      </div>
      <div class="field">
        <label>หน่วงเวลาตอบ (ms)</label>
        <input type="number" name="reply_delay_ms" value="<?= cfg($configRaw,'reply_delay_ms','800') ?>" min="0" max="5000">
      </div>
    </div>

    <hr style="border-color:var(--border);margin:20px 0">
    <h3 style="margin-bottom:16px">🤖 AI Integration</h3>
    <div class="form-grid">
      <div class="field">
        <label>เปิด/ปิด AI Fallback</label>
        <select name="ai_enabled">
          <option value="1" <?= cfg($configRaw,'ai_enabled')!=='0'?'selected':'' ?>>✅ เปิดใช้งาน</option>
          <option value="0" <?= cfg($configRaw,'ai_enabled')==='0'?'selected':'' ?>>❌ ปิดใช้งาน</option>
        </select>
      </div>
      <div class="field">
        <label>AI Provider</label>
        <select name="ai_provider">
          <option value="claude" <?= cfg($configRaw,'ai_provider')==='claude'?'selected':'' ?>>Claude (Anthropic)</option>
          <option value="openai" <?= cfg($configRaw,'ai_provider')==='openai'?'selected':'' ?>>OpenAI (GPT)</option>
        </select>
      </div>

      <!-- Claude -->
      <div class="field">
        <label>Claude API Key</label>
        <input type="text" name="claude_api_key"
               value="<?= cfg($configRaw,'claude_api_key') ?>"
               placeholder="sk-ant-api03-...">
        <div class="api-hint">รับ API Key ที่ <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></div>
      </div>
      <div class="field">
        <label>Claude Model</label>
        <select name="claude_model">
          <?php foreach(['claude-sonnet-4-20250514'=>'Claude Sonnet 4 (แนะนำ)',
                         'claude-haiku-4-5-20251001'=>'Claude Haiku 4.5 (เร็ว/ถูก)'] as $m=>$lbl): ?>
          <option value="<?= $m ?>" <?= cfg($configRaw,'claude_model')===$m?'selected':'' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- OpenAI -->
      <div class="field" style="grid-column:1/-1">
        <label>OpenAI API Key (ถ้าเลือก OpenAI)</label>
        <input type="text" name="openai_api_key"
               value="<?= cfg($configRaw,'openai_api_key') ?>"
               placeholder="sk-...">
      </div>

      <!-- System Prompt -->
      <div class="field" style="grid-column:1/-1">
        <label>AI System Prompt (บุคลิก Bot)</label>
        <textarea name="ai_system_prompt" style="min-height:100px"><?= cfg($configRaw,'ai_system_prompt','ตอบคำถามภาษาไทยอย่างสุภาพ กระชับ ไม่เกิน 3 ประโยค') ?></textarea>
        <div class="api-hint">💡 เช่น "คุณคือ AI ผู้ช่วยของร้านกาแฟ Cherry Cafe ตอบคำถามเกี่ยวกับเมนูและบริการอย่างสุภาพ"</div>
      </div>
    </div>
    <div style="margin-top:18px">
      <button type="submit" class="btn btn-primary">💾 บันทึกการตั้งค่า</button>
    </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════ -->
<!-- TAB: LOG                                -->
<!-- ════════════════════════════════════════ -->
<div class="tab-content <?= $activeTab==='log'?'active':'' ?>" id="tab-log">
  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h3 style="margin:0">📊 Bot Response Log (50 ล่าสุด)</h3>
      <form method="POST" style="margin:0">
        <input type="hidden" name="act" value="clear_log">
        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('ล้าง Log ทั้งหมด?')">
          🗑️ ล้าง Log
        </button>
      </form>
    </div>
    <table class="tbl">
      <tr><th>เวลา</th><th>ผู้ใช้</th><th>ข้อความที่ถาม</th><th>Bot ตอบ</th><th>ประเภท</th><th>Latency</th></tr>
      <?php if(empty($logs)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">ยังไม่มี Log</td></tr>
      <?php else: foreach($logs as $l): ?>
      <tr>
        <td style="white-space:nowrap;font-size:12px;font-family:'Space Mono';color:var(--muted)">
          <?= date('d/m H:i', strtotime($l['created_at'])) ?>
        </td>
        <td style="font-size:13px"><?= htmlspecialchars($l['user_name']) ?></td>
        <td style="max-width:200px;font-size:13px"><?= htmlspecialchars(mb_strimwidth($l['trigger_msg'],0,60,'…')) ?></td>
        <td style="max-width:250px;font-size:13px"><?= htmlspecialchars(mb_strimwidth($l['bot_response'],0,80,'…')) ?></td>
        <td>
          <?php if($l['response_type']==='pattern'): ?>
            <span class="badge-pat">pattern</span>
          <?php elseif($l['response_type']==='ai'): ?>
            <span class="badge-ai">AI</span>
          <?php else: ?>
            <span class="badge-fb">fallback</span>
          <?php endif; ?>
        </td>
        <td style="font-family:'Space Mono';font-size:12px;color:var(--muted)"><?= $l['latency_ms'] ?>ms</td>
      </tr>
      <?php endforeach; endif; ?>
    </table>
  </div>
</div>

</div><!-- /container -->

<script>
function switchTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
