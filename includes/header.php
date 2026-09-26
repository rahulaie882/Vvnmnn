<?php
if (!defined('BABA_PANEL')) {
    require_once __DIR__ . '/../config.php';
    requireLogin();
}

$current_page = basename($_SERVER['PHP_SELF']);
$primary      = getSetting('primary_color') ?: '#ff0040';
$secondary    = getSetting('secondary_color') ?: '#ff6b00';
$panel_name   = getSetting('panel_name') ?: 'BABA PANEL';
$btn_style    = getSetting('btn_style', 'fire');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($panel_name) ?> | <?= $page_title ?? 'Dashboard' ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap');

:root {
    --primary:   <?= htmlspecialchars($primary) ?>;
    --secondary: <?= htmlspecialchars($secondary) ?>;
    --bg:        #000000;
    --bg2:       #080808;
    --bg3:       #0d0d0d;
    --border:    rgba(255,0,64,0.18);
    --border2:   rgba(255,0,64,0.08);
    --text:      #f0f0f0;
    --muted:     #666;
    --glow:      0 0 24px rgba(255,0,64,0.22);
    --glow2:     0 0 48px rgba(255,0,64,0.10);
}

* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    overflow-x: hidden;
}

/* ── SCANLINE OVERLAY ── */
body::before {
    content:'';
    position:fixed;
    inset:0;
    background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255,0,64,0.015) 2px, rgba(255,0,64,0.015) 4px);
    pointer-events: none;
    z-index: 9999;
}

/* ── SIDEBAR ── */
.sidebar {
    width: 250px;
    background: var(--bg2);
    border-right: 1px solid var(--border);
    height: 100vh;
    position: fixed;
    left: 0; top: 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 30px rgba(255,0,64,0.08);
}

.sidebar-header {
    padding: 22px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(135deg, rgba(255,0,64,0.06), rgba(255,107,0,0.04));
}

.sidebar-logo {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 0 20px rgba(255,0,64,0.4);
    flex-shrink: 0;
}

.sidebar-title {
    font-weight: 800;
    font-size: 15px;
    letter-spacing: 0.5px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1.2;
}
.sidebar-sub { font-size: 11px; color: var(--muted); margin-top: 2px; font-family: 'JetBrains Mono', monospace; }

.nav { flex:1; padding: 10px 8px; overflow-y: auto; }
.nav::-webkit-scrollbar { width: 3px; }
.nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 2px; }

.nav-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 14px 12px 6px;
}

.nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    color: #777;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 2px;
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.nav a:hover {
    background: rgba(255,0,64,0.07);
    color: #ccc;
    border-color: var(--border);
}

.nav a.active {
    background: linear-gradient(90deg, rgba(255,0,64,0.12), rgba(255,107,0,0.06));
    color: var(--primary);
    border-color: rgba(255,0,64,0.3);
    box-shadow: inset 3px 0 0 var(--primary);
}

.nav a .ico { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
.nav .badge {
    margin-left: auto;
    background: var(--primary);
    color: #fff;
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 20px;
    font-weight: 700;
    box-shadow: 0 0 10px rgba(255,0,64,0.5);
    animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.7} }

.nav-divider { height:1px; background: var(--border2); margin: 8px 12px; }

.logout { padding: 12px 8px; border-top: 1px solid var(--border); }
.logout a { color: #ff4444 !important; font-size: 13px; }
.logout a:hover { background: rgba(255,60,60,0.08) !important; }

/* ── MAIN ── */
.main { margin-left: 250px; flex:1; min-height: 100vh; }

.topbar {
    background: var(--bg2);
    border-bottom: 1px solid var(--border);
    padding: 14px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky; top:0; z-index:50;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
}
.topbar-title { font-size: 17px; font-weight: 700; color: #ddd; }
.topbar-right { display:flex; align-items:center; gap:14px; }

.status-dot {
    width:8px; height:8px;
    background: #00ff88;
    border-radius: 50%;
    box-shadow: 0 0 10px #00ff88;
    animation: pulse 2s infinite;
}

.admin-badge {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    box-shadow: 0 0 14px rgba(255,0,64,0.4);
}

.content { padding: 22px; }

/* ── CARDS ── */
.card {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
    box-shadow: var(--glow2);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.card:hover { border-color: rgba(255,0,64,0.3); box-shadow: var(--glow); }
.card h3 { margin-bottom: 16px; font-size: 15px; font-weight: 700; color: #ddd; }

/* ── STATS ── */
.stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:20px; }
.stat-card {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
    position: relative;
    overflow: hidden;
    transition: all 0.2s;
}
.stat-card::before {
    content:'';
    position: absolute;
    top:0; left:0; right:0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}
.stat-card:hover { box-shadow: var(--glow); border-color: rgba(255,0,64,0.3); transform: translateY(-1px); }
.stat-card .icon { font-size: 24px; margin-bottom: 10px; }
.stat-card .value { font-size: 26px; font-weight: 800; background: linear-gradient(90deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px; }
.stat-card .label { font-size: 12px; color: var(--muted); font-weight: 500; }

/* ── BUTTONS ── */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-radius: 8px; border: none;
    cursor: pointer; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.18s;
    font-family: 'Inter', sans-serif;
}
.btn-primary {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: #fff;
    box-shadow: 0 0 16px rgba(255,0,64,0.3);
}
.btn-primary:hover { box-shadow: 0 0 24px rgba(255,0,64,0.5); transform: translateY(-1px); }
.btn-danger { background: rgba(255,60,60,0.12); color: #ff6b6b; border: 1px solid rgba(255,60,60,0.25); }
.btn-danger:hover { background: rgba(255,60,60,0.2); }
.btn-success { background: rgba(0,255,130,0.08); color: #00ff88; border: 1px solid rgba(0,255,130,0.2); }
.btn-success:hover { background: rgba(0,255,130,0.14); }
.btn-secondary { background: rgba(255,255,255,0.05); color: #999; border: 1px solid rgba(255,255,255,0.08); }
.btn-secondary:hover { background: rgba(255,255,255,0.08); color: #ccc; }
.btn-sm { padding: 5px 11px; font-size: 12px; }

/* ── FORMS ── */
input, textarea, select {
    width: 100%; padding: 11px 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border);
    border-radius: 8px; color: #ddd; font-size: 13.5px;
    outline: none; margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
    transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus, textarea:focus, select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255,0,64,0.1);
}
input[type="color"] { padding: 4px; height: 42px; cursor: pointer; }
label { display:block; font-size: 12px; color: var(--muted); margin-bottom: 5px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
small { color: var(--muted); font-size: 11px; display:block; margin-top: -8px; margin-bottom: 10px; }

/* ── TABLE ── */
table { width:100%; border-collapse: collapse; }
th, td { padding: 11px 10px; text-align:left; border-bottom: 1px solid var(--border2); font-size: 13px; }
th { color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
tr:hover td { background: rgba(255,0,64,0.02); }

/* ── BADGES ── */
.badge { display:inline-flex; align-items:center; gap:4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-green  { background: rgba(0,255,130,0.1);  color: #00ff88; border:1px solid rgba(0,255,130,0.2); }
.badge-red    { background: rgba(255,60,60,0.1);  color: #ff6b6b; border:1px solid rgba(255,60,60,0.2); }
.badge-yellow { background: rgba(255,200,0,0.1);  color: #ffd700; border:1px solid rgba(255,200,0,0.2); }
.badge-blue   { background: rgba(0,150,255,0.1);  color: #60a5fa; border:1px solid rgba(0,150,255,0.2); }

/* ── ALERTS ── */
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: 500; border: 1px solid; }
.alert-success { background: rgba(0,255,130,0.07); color: #00ff88; border-color: rgba(0,255,130,0.2); }
.alert-error   { background: rgba(255,60,60,0.07); color: #ff6b6b; border-color: rgba(255,60,60,0.2); }
.alert-info    { background: rgba(0,150,255,0.07); color: #60a5fa; border-color: rgba(0,150,255,0.2); }

/* ── GRID ── */
.grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
.grid-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; }

/* ── BUTTON STYLE PICKER ── */
.style-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin-bottom:16px; }
.style-card {
    background: rgba(255,255,255,0.03);
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 14px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.style-card input[type=radio] { position:absolute; opacity:0; }
.style-card:has(input:checked),
.style-card.selected { border-color: var(--primary); background: rgba(255,0,64,0.08); box-shadow: 0 0 14px rgba(255,0,64,0.2); }
.style-card .s-icon { font-size: 26px; display:block; margin-bottom:6px; }
.style-card .s-name { font-size: 12px; font-weight: 600; color: #ccc; }
.style-card .s-ex   { font-size: 10px; color: var(--muted); margin-top:2px; }

/* ── CODE ── */
code { font-family: 'JetBrains Mono', monospace; background: rgba(255,0,64,0.08); padding: 2px 7px; border-radius: 4px; font-size: 12px; color: #ff6b6b; border: 1px solid rgba(255,0,64,0.2); }

/* ── SCROLL ── */
::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: rgba(255,0,64,0.3); border-radius:3px; }
::-webkit-scrollbar-thumb:hover { background: var(--primary); }

/* ── RESPONSIVE ── */
@media (max-width:768px) {
    .sidebar { width:60px; }
    .sidebar-title, .sidebar-sub, .nav a span, .nav-label, .nav-divider { display:none; }
    .main { margin-left:60px; }
    .nav a { justify-content:center; padding:12px; }
    .grid-2, .grid-3 { grid-template-columns:1fr; }
    .style-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">💀</div>
        <div>
            <div class="sidebar-title"><?= htmlspecialchars($panel_name) ?></div>
            <div class="sidebar-sub">v3.0 — FIXED</div>
        </div>
    </div>

    <div class="nav">
        <div class="nav-label">Main</div>
        <a href="dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>">
            <span class="ico">📊</span><span>Dashboard</span>
        </a>
        <a href="start_message.php" class="<?= $current_page=='start_message.php'?'active':'' ?>">
            <span class="ico">🎬</span><span>Start Message</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Shop</div>
        <a href="plans.php" class="<?= $current_page=='plans.php'?'active':'' ?>">
            <span class="ico">📦</span><span>Plans</span>
        </a>
        <a href="groups.php" class="<?= $current_page=='groups.php'?'active':'' ?>">
            <span class="ico">🔗</span><span>Groups</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Payments</div>
        <a href="pending.php" class="<?= $current_page=='pending.php'?'active':'' ?>">
            <span class="ico">⏳</span>
            <span>Pending</span>
            <?php
            $pc = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();
            if ($pc > 0) echo "<span class='badge'>$pc</span>";
            ?>
        </a>
        <a href="payment.php" class="<?= $current_page=='payment.php'?'active':'' ?>">
            <span class="ico">💳</span><span>Payment Setup</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">Users</div>
        <a href="users.php" class="<?= $current_page=='users.php'?'active':'' ?>">
            <span class="ico">👥</span><span>Users</span>
        </a>
        <a href="broadcast.php" class="<?= $current_page=='broadcast.php'?'active':'' ?>">
            <span class="ico">📢</span><span>Broadcast</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-label">System</div>
        <a href="settings.php" class="<?= $current_page=='settings.php'?'active':'' ?>">
            <span class="ico">⚙️</span><span>Settings</span>
        </a>
        <a href="backup.php" class="<?= $current_page=='backup.php'?'active':'' ?>">
            <span class="ico">💾</span><span>Backup</span>
        </a>
    </div>

    <div class="logout">
        <a href="logout.php" class="nav" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:#ff4444;text-decoration:none;border-radius:8px;font-size:13px;">
            <span class="ico">🚪</span><span>Logout</span>
        </a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
        <div class="topbar-right">
            <div class="status-dot" title="System Online"></div>
            <span style="font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace;"><?= date('H:i') ?></span>
            <div class="admin-badge"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'B', 0, 1)) ?></div>
        </div>
    </div>
    <div class="content">
