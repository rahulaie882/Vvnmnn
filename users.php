<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Users';

$filter = $_GET['filter'] ?? 'all';

if ($filter === 'premium') {
    $users = $pdo->query("SELECT * FROM users WHERE status='premium' ORDER BY updated_at DESC LIMIT 150")->fetchAll();
} elseif ($filter === 'free') {
    $users = $pdo->query("SELECT * FROM users WHERE status='free' OR status IS NULL ORDER BY joined_at DESC LIMIT 150")->fetchAll();
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY joined_at DESC LIMIT 150")->fetchAll();
}

$total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$premium = $pdo->query("SELECT COUNT(*) FROM users WHERE status='premium'")->fetchColumn();

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= number_format($total) ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="value" style="color:#34d399;"><?= number_format($premium) ?></div>
        <div class="label">Premium</div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h3 style="margin:0;">👥 Users</h3>
        <div style="display:flex;gap:8px;">
            <a href="?filter=all" class="btn btn-sm <?= $filter=='all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="?filter=premium" class="btn btn-sm <?= $filter=='premium' ? 'btn-primary' : 'btn-secondary' ?>">Premium</a>
            <a href="?filter=free" class="btn btn-sm <?= $filter=='free' ? 'btn-primary' : 'btn-secondary' ?>">Free</a>
        </div>
    </div>

    <?php if (empty($users)): ?>
        <p style="color:#64748b;">No users found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Telegram ID</th>
                    <th>Name / Username</th>
                    <th>Plan</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td>
                        <code style="background:#0f0f17;padding:3px 8px;border-radius:6px;font-size:13px;"><?= htmlspecialchars($u['telegram_id']) ?></code>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($u['full_name'] ?: '-') ?></div>
                        <small style="color:#64748b;">@<?= htmlspecialchars($u['username'] ?: 'N/A') ?></small>
                    </td>
                    <td><?= htmlspecialchars($u['plan'] ?: '-') ?></td>
                    <td><?= $u['expiry'] ?: '-' ?></td>
                    <td>
                        <?php if ($u['status'] === 'premium'): ?>
                            <span class="badge badge-green">Premium</span>
                        <?php else: ?>
                            <span class="badge badge-blue">Free</span>
                        <?php endif; ?>
                    </td>
                    <td><?= timeAgo($u['joined_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
