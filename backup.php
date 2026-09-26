<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Backup & Restore';

// Export
if (isset($_GET['export'])) {
    $data = [
        'plans'     => $pdo->query("SELECT * FROM plans")->fetchAll(),
        'groups'    => $pdo->query("SELECT * FROM groups")->fetchAll(),
        'settings'  => $pdo->query("SELECT * FROM settings")->fetchAll(),
        'exported_at' => date('Y-m-d H:i:s'),
        'version'   => '2.0'
    ];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="baba_panel_backup_'.date('Y-m-d').'.json"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn() ?></div>
        <div class="label">PLANS</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn() ?></div>
        <div class="label">GROUPS</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?></div>
        <div class="label">USERS</div>
    </div>
    <div class="stat-card">
        <div class="value" style="color:#34d399;">ON</div>
        <div class="label">SYSTEM</div>
    </div>
</div>

<div class="card">
    <h3>⬇️ Download Backup</h3>
    <p style="color:#64748b;margin-bottom:14px;">Full setup export (Plans, Settings, Groups). Users & payments alag rehte hain.</p>
    <a href="?export=1" class="btn btn-primary">📥 Export Backup (JSON)</a>
</div>

<div class="card">
    <h3>ℹ️ Important Note</h3>
    <p style="color:#94a3b8;font-size:14px;line-height:1.7;">
        • Database file <code>database.sqlite</code> ko bhi backup kar lena chahiye (cPanel File Manager se)<br>
        • Agar bot delete ho jaye toh sirf naya <b>Bot Token</b> Settings mein daalo — baaki sab data wahi rahega<br>
        • Uploads folder (QR + images) ko bhi backup mein include karo
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>
