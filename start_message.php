<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Start Message';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_start'])) {
        setSetting('start_video_file_id', trim($_POST['start_video_file_id'] ?? ''));
        setSetting('welcome_message', trim($_POST['welcome_message'] ?? ''));
        setSetting('howto_video_file_id', trim($_POST['howto_video_file_id'] ?? ''));
        $success = "Start message settings saved successfully!";
    }
}

$start_video = getSetting('start_video_file_id');
$welcome_msg = getSetting('welcome_message');
$howto_video = getSetting('howto_video_file_id');

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card">
    <h3>🎬 Start Video + Welcome Message</h3>
    <p style="color:#64748b;margin-bottom:18px;font-size:13px;">
        Jab user <b>/start</b> karega toh pehle yeh video + message dikhega, uske baad Plans buttons.
    </p>

    <form method="POST">
        <label>Start Video File ID</label>
        <input type="text" name="start_video_file_id" value="<?= htmlspecialchars($start_video) ?>" placeholder="BAACAgQAAxkBAAI...">
        <small style="display:block;margin-bottom:14px;">Bot ko koi video forward karo → bot file_id de dega. Wahi yahan paste karo.</small>

        <label>Welcome Message (Description)</label>
        <textarea name="welcome_message" rows="5" placeholder="Welcome message here..."><?= htmlspecialchars($welcome_msg) ?></textarea>
        <small style="display:block;margin-bottom:14px;">Supports Telegram formatting: *bold*, _italic_, `code`</small>

        <label>How to Use Video File ID (Optional)</label>
        <input type="text" name="howto_video_file_id" value="<?= htmlspecialchars($howto_video) ?>" placeholder="Optional how-to-use video file_id">
        <small style="display:block;margin-bottom:18px;">Agar set kiya toh users ko alag se how-to-use video mil sakti hai.</small>

        <button type="submit" name="save_start" class="btn btn-primary">💾 Save Start Settings</button>
    </form>
</div>

<div class="card">
    <h3>📌 How to get File ID?</h3>
    <ol style="color:#94a3b8;font-size:14px;padding-left:20px;line-height:1.8;">
        <li>Apne bot ko koi bhi <b>Video</b> forward karo ya bhejo</li>
        <li>Bot automatically uska <code>file_id</code> reply karega</li>
        <li>Us file_id ko copy karke yahan paste kar do</li>
    </ol>
</div>

<?php require_once 'includes/footer.php'; ?>
