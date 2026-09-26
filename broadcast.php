<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Broadcast';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $type = $_POST['type'] ?? 'text';
    $message = trim($_POST['message'] ?? '');
    $file_id = trim($_POST['file_id'] ?? '');

    if (!$message && !$file_id) {
        $error = "Message or File ID required.";
    } else {
        // Get all users
        $users = $pdo->query("SELECT telegram_id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $sent = 0;
        $failed = 0;

        $primary = getSetting('primary_color') ?: '#3b82f6';

        // Inline keyboard with Buy Now
        $keyboard = json_encode([
            'inline_keyboard' => [[
                ['text' => '🛒 Buy Now', 'callback_data' => 'show_plans']
            ]]
        ]);

        foreach ($users as $uid) {
            $params = [
                'chat_id' => $uid,
                'reply_markup' => $keyboard,
                'parse_mode' => 'Markdown'
            ];

            $result = false;
            if ($type === 'text') {
                $params['text'] = $message;
                $result = telegramApi('sendMessage', $params);
            } elseif ($type === 'photo' && $file_id) {
                $params['photo'] = $file_id;
                $params['caption'] = $message;
                $result = telegramApi('sendPhoto', $params);
            } elseif ($type === 'video' && $file_id) {
                $params['video'] = $file_id;
                $params['caption'] = $message;
                $result = telegramApi('sendVideo', $params);
            }

            if ($result && ($result['ok'] ?? false)) {
                $sent++;
            } else {
                $failed++;
            }
            usleep(50000); // 0.05s delay to avoid flood
        }

        // Log broadcast
        $pdo->prepare("INSERT INTO broadcasts (type, content, file_id, caption, status, sent_count) VALUES (?,?,?,?,?,?)")
            ->execute([$type, $message, $file_id, $message, 'completed', $sent]);

        $success = "Broadcast completed! Sent: $sent | Failed: $failed";
    }
}

$recent = $pdo->query("SELECT * FROM broadcasts ORDER BY id DESC LIMIT 10")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card">
    <h3>📢 Send Broadcast</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
        Message ke niche automatically <b>Buy Now</b> button aa jayega.
    </p>

    <form method="POST">
        <label>Broadcast Type</label>
        <select name="type" id="btype" onchange="toggleFile()">
            <option value="text">📝 Text Only</option>
            <option value="photo">🖼️ Photo + Caption</option>
            <option value="video">🎬 Video + Caption</option>
        </select>

        <div id="fileid_box" style="display:none;">
            <label>File ID (Photo/Video)</label>
            <input type="text" name="file_id" placeholder="BAACAgQAAxkBAAI...">
            <small style="display:block;margin-bottom:12px;">Bot ko media bhejo → file_id mil jayega</small>
        </div>

        <label>Message / Caption</label>
        <textarea name="message" rows="5" placeholder="Your broadcast message here..."></textarea>

        <button type="submit" name="send_broadcast" class="btn btn-primary" onclick="return confirm('Send broadcast to all users?')">🚀 Send Broadcast</button>
    </form>
</div>

<script>
function toggleFile() {
    const t = document.getElementById('btype').value;
    document.getElementById('fileid_box').style.display = (t === 'text') ? 'none' : 'block';
}
</script>

<div class="card">
    <h3>📋 Recent Broadcasts</h3>
    <?php if (empty($recent)): ?>
        <p style="color:#64748b;">No broadcasts yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Content</th>
                    <th>Sent</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $b): ?>
                <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td><span class="badge badge-blue"><?= strtoupper($b['type']) ?></span></td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($b['content'] ?: $b['file_id']) ?></td>
                    <td><?= $b['sent_count'] ?></td>
                    <td><?= timeAgo($b['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
