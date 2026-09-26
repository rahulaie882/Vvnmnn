<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Pending Payments';

// Approve / Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];

    $stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();

    if ($payment) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE pending_payments SET status='approved' WHERE id=?")->execute([$id]);

            $plan = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $plan->execute([$payment['plan_id']]);
            $planData = $plan->fetch();

            $expiry = date('Y-m-d', strtotime('+' . ($planData['validity'] ?? 30) . ' days'));

            $pdo->prepare("
                INSERT INTO users (telegram_id, username, full_name, plan, plan_id, expiry, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'premium', CURRENT_TIMESTAMP)
                ON CONFLICT(telegram_id) DO UPDATE SET
                    plan       = excluded.plan,
                    plan_id    = excluded.plan_id,
                    expiry     = excluded.expiry,
                    status     = 'premium',
                    updated_at = CURRENT_TIMESTAMP
            ")->execute([
                $payment['user_id'], $payment['username'], $payment['full_name'],
                $payment['plan_name'], $payment['plan_id'], $expiry
            ]);

            // ── APPROVED MESSAGE ──
            $E       = getBtnStyle();
            $caption = "✅ *Payment Approved!*\n\n";
            $caption .= "Plan: *{$payment['plan_name']}*\n";
            $caption .= "Expiry: `{$expiry}`\n\n";
            $caption .= "Niche diye links join karo 👇";

            // FIX 2: Get group links and send them
            $groups = $pdo->query("SELECT * FROM groups WHERE status='Active' ORDER BY id ASC")->fetchAll();
            if (!empty($groups)) {
                $links_text = "\n\n🔗 *Your Premium Links*\n━━━━━━━━━━━━━━━━\n";
                foreach ($groups as $g) {
                    $links_text .= "▸ [{$g['name']}]({$g['link']})\n";
                }
                $caption .= $links_text;
            }

            // Send with approved image if file_id set
            $approved_fid = getSetting('approved_file_id');
            if ($approved_fid) {
                telegramApi('sendPhoto', [
                    'chat_id'    => $payment['user_id'],
                    'photo'      => $approved_fid,
                    'caption'    => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                telegramApi('sendMessage', [
                    'chat_id'    => $payment['user_id'],
                    'text'       => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            }

        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE pending_payments SET status='rejected' WHERE id=?")->execute([$id]);

            $rejected_fid = getSetting('rejected_file_id');
            $caption = "❌ *Payment Rejected*\n\nPlan: *{$payment['plan_name']}*\nAdmin se contact karo ya dobara try karo.";

            if ($rejected_fid) {
                telegramApi('sendPhoto', [
                    'chat_id'    => $payment['user_id'],
                    'photo'      => $rejected_fid,
                    'caption'    => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                telegramApi('sendMessage', [
                    'chat_id'    => $payment['user_id'],
                    'text'       => $caption,
                    'parse_mode' => 'Markdown'
                ]);
            }
        }
    }

    header('Location: pending.php');
    exit;
}

$pending = $pdo->query("SELECT * FROM pending_payments WHERE status='pending' ORDER BY id DESC")->fetchAll();
$all     = $pdo->query("SELECT * FROM pending_payments ORDER BY id DESC LIMIT 50")->fetchAll();

require_once 'includes/header.php';
?>

<div class="card">
    <h3>⏳ Pending Payments (<?= count($pending) ?>)</h3>
    <?php if (empty($pending)): ?>
        <p style="color:#64748b;">No pending payments. 🔥</p>
    <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>User</th><th>Plan</th><th>Amount</th><th>Screenshot</th><th>Time</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <?php
                    // FIX 3: Convert file_id to real Telegram URL for viewing
                    $ss_url = null;
                    if ($p['screenshot']) {
                        $ss_url = getTelegramFileUrl($p['screenshot']);
                    }
                ?>
                <tr>
                    <td><span class="badge badge-yellow">#<?= $p['id'] ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($p['full_name'] ?: $p['username'] ?: 'Unknown') ?></strong><br>
                        <small style="color:#64748b;">@<?= $p['username'] ?: 'N/A' ?> | <code><?= $p['user_id'] ?></code></small>
                    </td>
                    <td><?= htmlspecialchars($p['plan_name']) ?></td>
                    <td><strong><?= money($p['amount']) ?></strong></td>
                    <td>
                        <?php if ($ss_url): ?>
                            <a href="<?= htmlspecialchars($ss_url) ?>" target="_blank" class="btn btn-secondary btn-sm">🖼️ View SS</a>
                        <?php elseif ($p['screenshot']): ?>
                            <span style="color:#64748b;font-size:12px;">Loading...</span>
                        <?php else: ?>
                            <span style="color:#64748b;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= timeAgo($p['created_at']) ?></td>
                    <td>
                        <a href="?action=approve&id=<?= $p['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('✅ Approve karo?')">✅ Approve</a>
                        <a href="?action=reject&id=<?= $p['id'] ?>"  class="btn btn-danger btn-sm"  onclick="return confirm('❌ Reject karo?')">❌ Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📋 Recent Payments (Last 50)</h3>
    <table>
        <thead><tr><th>#</th><th>User</th><th>Plan</th><th>Amount</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
            <?php foreach ($all as $p): ?>
            <tr>
                <td>#<?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['username'] ?: $p['user_id']) ?></td>
                <td><?= htmlspecialchars($p['plan_name']) ?></td>
                <td><?= money($p['amount']) ?></td>
                <td>
                    <?php if ($p['status']=='approved'): ?><span class="badge badge-green">✅ Approved</span>
                    <?php elseif ($p['status']=='rejected'): ?><span class="badge badge-red">❌ Rejected</span>
                    <?php else: ?><span class="badge badge-yellow">⏳ Pending</span>
                    <?php endif; ?>
                </td>
                <td><?= timeAgo($p['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
