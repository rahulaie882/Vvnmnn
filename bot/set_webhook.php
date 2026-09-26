<?php
/**
 * Manual Webhook Helper - works even if panel curl fails
 */
require_once __DIR__ . '/../config.php';
requireLogin();

$token = getSetting('bot_token');
$host = $_SERVER['HTTP_HOST'] ?? 'babapanel.xo.je';
$script = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
$base = rtrim($script, '/');
if ($base === '/' || $base === '.') $base = '';
$webhook_url = 'https://' . $host . $base . '/bot/webhook.php';

$manual = $token ? "https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($webhook_url) : '';
$getme = $token ? "https://api.telegram.org/bot{$token}/getMe" : '';
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set Webhook</title>
<style>
body{font-family:sans-serif;background:#0a0a0f;color:#e2e8f0;padding:20px;max-width:600px;margin:0 auto}
.card{background:#14141f;border:1px solid #1e1e2d;border-radius:16px;padding:20px;margin-bottom:16px}
a.btn{display:inline-block;padding:14px 20px;background:linear-gradient(90deg,#3b82f6,#8b5cf6);color:#fff;text-decoration:none;border-radius:12px;font-weight:600;margin:8px 0}
code{background:#0f0f17;padding:8px;border-radius:8px;display:block;word-break:break-all;font-size:12px;margin:10px 0}
.ok{color:#34d399}.err{color:#f87171}
</style>
</head>
<body>
<h2>🔗 Webhook Helper</h2>

<div class="card">
<h3>1. Token check</h3>
<?php if (!$token): ?>
<p class="err">Token empty. Settings mein pehle token save karo.</p>
<?php else: ?>
<p>Token saved: <code><?= htmlspecialchars(substr($token,0,12)) ?>...<?= htmlspecialchars(substr($token,-6)) ?></code></p>
<a class="btn" href="<?= htmlspecialchars($getme) ?>" target="_blank">▶ Test Token (getMe)</a>
<p style="font-size:13px;color:#94a3b8;margin-top:8px">Ispe click karo. Agar <b>ok:true</b> + bot username aaye toh token sahi hai.</p>
<?php endif; ?>
</div>

<div class="card">
<h3>2. Set Webhook</h3>
<p style="font-size:13px;color:#94a3b8">Webhook URL:</p>
<code><?= htmlspecialchars($webhook_url) ?></code>
<?php if ($manual): ?>
<a class="btn" href="<?= htmlspecialchars($manual) ?>" target="_blank">▶ SET WEBHOOK (click here)</a>
<p style="font-size:13px;color:#94a3b8;margin-top:8px">Success: <span class="ok">{"ok":true,"result":true}</span></p>
<?php endif; ?>
</div>

<div class="card">
<a href="../settings.php" style="color:#60a5fa">← Back to Settings</a>
</div>
</body>
</html>
