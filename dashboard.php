<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Dashboard';

$total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$premium_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status='premium'")->fetchColumn();
$pending       = $pdo->query("SELECT COUNT(*) FROM pending_payments WHERE status='pending'")->fetchColumn();
$total_plans   = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved'")->fetchColumn();
$today_earning = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved' AND date(created_at)=date('now')")->fetchColumn();
$today_users   = $pdo->query("SELECT COUNT(*) FROM users WHERE date(joined_at)=date('now')")->fetchColumn();
$month_earning = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved' AND strftime('%Y-%m', created_at)=strftime('%Y-%m', 'now')")->fetchColumn();

// Last 7 days earnings
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $amt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pending_payments WHERE status='approved' AND date(created_at)='$date'")->fetchColumn();
    $days[] = ['date' => date('d M', strtotime($date)), 'amount' => (float)$amt];
}
$max = max(array_column($days, 'amount')) ?: 1;

require_once 'includes/header.php';
?>

<style>
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}
@keyframes glow {
    0%, 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0.3); }
    50% { box-shadow: 0 0 20px 4px rgba(59,130,246,0.15); }
}
.stat-card {
    animation: fadeUp 0.5s ease forwards;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}
.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.1s; }
.stat-card:nth-child(3) { animation-delay: 0.15s; }
.stat-card:nth-child(4) { animation-delay: 0.2s; }
.stat-card:nth-child(5) { animation-delay: 0.25s; }
.stat-card:nth-child(6) { animation-delay: 0.3s; }
.live-dot {
    display: inline-block;
    width: 8px; height: 8px;
    background: #34d399;
    border-radius: 50%;
    margin-right: 6px;
    animation: pulse 1.5s infinite;
}
.bar-fill {
    transition: height 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.card { animation: fadeUp 0.6s ease forwards; }
</style>

<!-- Top Stats -->
<div class="stats-grid">
    <div class="stat-card" style="animation: fadeUp 0.5s ease forwards, glow 3s infinite;">
        <div class="icon">👥</div>
        <div class="value"><?= number_format($total_users) ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="icon">⭐</div>
        <div class="value" style="color:#34d399;"><?= number_format($premium_users) ?></div>
        <div class="label">Premium Users</div>
    </div>
    <div class="stat-card">
        <div class="icon">📦</div>
        <div class="value"><?= number_format($total_plans) ?></div>
        <div class="label">Active Plans</div>
    </div>
    <div class="stat-card">
        <div class="icon">⏳</div>
        <div class="value" style="color:#fbbf24;"><?= number_format($pending) ?></div>
        <div class="label">Pending Payments</div>
    </div>
    <div class="stat-card">
        <div class="icon">💰</div>
        <div class="value" style="color:#34d399;"><?= money($total_revenue) ?></div>
        <div class="label">Total Earning</div>
    </div>
    <div class="stat-card">
        <div class="icon">📅</div>
        <div class="value"><?= money($month_earning) ?></div>
        <div class="label">This Month</div>
    </div>
</div>

<!-- Today + Quick -->
<div class="grid-2">
    <div class="card" style="background:linear-gradient(135deg,#0f172a 0%,#14141f 100%);border-color:#1e293b;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <span class="live-dot"></span>
            <h3 style="margin:0;">Today Live</h3>
        </div>
        <div style="font-size:36px;font-weight:800;color:#34d399;margin-bottom:6px;letter-spacing:-1px;">
            <?= money($today_earning) ?>
        </div>
        <div style="color:#94a3b8;font-size:14px;">
            <?= $today_users ?> new users aaye aaj
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px;">⚡ Quick Actions</h3>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <a href="pending.php" class="btn btn-primary btn-sm">⏳ Pending (<?= $pending ?>)</a>
            <a href="plans.php" class="btn btn-secondary btn-sm">📦 Plans</a>
            <a href="broadcast.php" class="btn btn-secondary btn-sm">📢 Broadcast</a>
            <a href="settings.php" class="btn btn-secondary btn-sm">⚙️ Settings</a>
            <a href="users.php" class="btn btn-secondary btn-sm">👥 Users</a>
        </div>
    </div>
</div>

<!-- Earnings Graph -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h3 style="margin:0;">📈 Last 7 Days Earnings</h3>
        <span class="badge badge-green"><span class="live-dot" style="width:6px;height:6px;"></span> Live</span>
    </div>
    <div style="display:flex;align-items:flex-end;gap:10px;height:180px;padding-top:10px;">
        <?php foreach ($days as $idx => $d): 
            $h = ($d['amount'] / $max) * 130;
        ?>
        <div style="flex:1;text-align:center;">
            <div class="bar-fill" style="background:linear-gradient(180deg,var(--primary),var(--secondary));height:<?= max(6, $h) ?>px;border-radius:8px 8px 0 0;margin-bottom:8px;animation: fadeUp 0.6s ease forwards;animation-delay:<?= 0.1 * $idx ?>s;" title="<?= money($d['amount']) ?>"></div>
            <div style="font-size:11px;color:#64748b;font-weight:500;"><?= $d['date'] ?></div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= $d['amount'] > 0 ? money($d['amount']) : '—' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Bottom Info Boxes -->
<div class="grid-2">
    <div class="card">
        <h3>📊 Summary</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #1e1e2d;">
                <span style="color:#94a3b8;">Total Users</span>
                <strong><?= number_format($total_users) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #1e1e2d;">
                <span style="color:#94a3b8;">Premium</span>
                <strong style="color:#34d399;"><?= number_format($premium_users) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #1e1e2d;">
                <span style="color:#94a3b8;">Plans</span>
                <strong><?= number_format($total_plans) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0;">
                <span style="color:#94a3b8;">Pending</span>
                <strong style="color:#fbbf24;"><?= number_format($pending) ?></strong>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>💡 Tips</h3>
        <ul style="color:#94a3b8;font-size:13px;line-height:1.8;padding-left:18px;">
            <li>Settings → Bot Token daalo → <b>Set Webhook</b> dabao</li>
            <li>Start Message mein video file_id set karo</li>
            <li>Plans add karke price & validity set karo</li>
            <li>Payment mein UPI + QR upload karo</li>
            <li>Panel se jo change karo woh turant bot mein reflect hota hai</li>
        </ul>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
