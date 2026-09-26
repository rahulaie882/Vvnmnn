<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Settings';

$success = $error = '';
$webhook_result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_btn_style'])) {
        setSetting('btn_style', trim($_POST['btn_style'] ?? 'fire'));
        $success = "Button style saved! Bot restart nahi chahiye.";
    }


    // Bot Config
    if (isset($_POST['save_bot'])) {
        setSetting('bot_token', trim($_POST['bot_token'] ?? ''));
        setSetting('admin_chat_id', trim($_POST['admin_chat_id'] ?? ''));
        setSetting('user_log_channel', trim($_POST['user_log_channel'] ?? ''));
        setSetting('payment_proof_channel', trim($_POST['payment_proof_channel'] ?? ''));
        $success = "Bot configuration saved!";
    }

    // Set Webhook Button
    if (isset($_POST['set_webhook'])) {
        $token = trim($_POST['bot_token'] ?? getSetting('bot_token'));
        $token = preg_replace('/\s+/', '', $token); // remove spaces
        
        if (!$token || strlen($token) < 20) {
            $error = "Bot Token empty / too short. BotFather se pura token copy karo.";
        } else {
            setSetting('bot_token', $token);
            if (!empty($_POST['admin_chat_id'])) {
                setSetting('admin_chat_id', trim($_POST['admin_chat_id']));
            }

            // Build webhook URL
            $host = $_SERVER['HTTP_HOST'] ?? 'babapanel.xo.je';
            $script = str_replace('\\\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $base = rtrim(dirname($script), '/');
            if ($base === '/' || $base === '.' || $base === '') $base = '';
            $webhook_url = 'https://' . $host . $base . '/bot/webhook.php';

            // Helper: call Telegram API
            $tgCall = function($method, $params = []) use ($token) {
                $url = "https://api.telegram.org/bot{$token}/{$method}";
                // Try curl first
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    $opts = [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 25,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                    ];
                    if (!empty($params)) {
                        $opts[CURLOPT_POST] = true;
                        $opts[CURLOPT_POSTFIELDS] = $params;
                    }
                    curl_setopt_array($ch, $opts);
                    $raw = curl_exec($ch);
                    $err = curl_error($ch);
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($raw !== false) return [$raw, $err, $code];
                }
                // Fallback file_get_contents
                $ctx = stream_context_create([
                    'http' => [
                        'method' => !empty($params) ? 'POST' : 'GET',
                        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'content' => !empty($params) ? http_build_query($params) : '',
                        'timeout' => 25,
                        'ignore_errors' => true
                    ],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                $raw = @file_get_contents($url, false, $ctx);
                return [$raw, $raw === false ? 'file_get_contents failed' : '', 0];
            };

            // 1) getMe
            list($me_raw, $me_err, $me_code) = $tgCall('getMe');
            $me = json_decode($me_raw, true);

            if (!$me_raw) {
                $error = "Server se Telegram connect nahi ho paaya (InfinityFree limit). Neeche MANUAL link use karo.";
                $webhook_result = "Connection error: {$me_err} | Manual: https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($webhook_url);
            } elseif (!($me['ok'] ?? false)) {
                $desc = $me['description'] ?? 'Invalid token';
                $error = "Telegram bola: {$desc}. Token check karo (Revoke karke naya lo, pura paste karo).";
                $webhook_result = "Raw: " . substr($me_raw, 0, 150);
            } else {
                $bot_username = $me['result']['username'] ?? 'bot';
                
                // 2) setWebhook
                list($wh_raw, $wh_err, $wh_code) = $tgCall('setWebhook', [
                    'url' => $webhook_url,
                    'drop_pending_updates' => 'true'
                ]);
                $wh = json_decode($wh_raw, true);

                if ($wh && ($wh['ok'] ?? false)) {
                    setSetting('webhook_set', '1');
                    setSetting('webhook_url', $webhook_url);
                    $success = "✅ Webhook SET! Bot: @{$bot_username}";
                    $webhook_result = "URL: {$webhook_url}";
                } else {
                    $desc = $wh['description'] ?? $wh_err ?: 'Unknown';
                    $error = "Webhook fail: {$desc}";
                    $webhook_result = "URL: {$webhook_url} | Raw: " . substr((string)$wh_raw, 0, 180);
                    // Always show manual link
                    $webhook_result .= " | MANUAL: https://api.telegram.org/bot" . substr($token,0,10) . ".../setWebhook?url=" . urlencode($webhook_url);
                }
            }
        }
    }

    // Delete Webhook
    if (isset($_POST['delete_webhook'])) {
        $token = getSetting('bot_token');
        if ($token) {
            $url = "https://api.telegram.org/bot{$token}/deleteWebhook";
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['drop_pending_updates' => true],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);
            if ($result && ($result['ok'] ?? false)) {
                setSetting('webhook_set', '0');
                $success = "Webhook removed.";
            } else {
                $error = "Could not remove webhook.";
            }
        }
    }

    // Theme
    if (isset($_POST['save_theme'])) {
        $primary = trim($_POST['primary_color'] ?? '#3b82f6');
        $secondary = trim($_POST['secondary_color'] ?? '#8b5cf6');
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $primary) && preg_match('/^#[a-fA-F0-9]{6}$/', $secondary)) {
            setSetting('primary_color', $primary);
        setSetting('btn_style', trim($_POST['btn_style'] ?? 'fire'));
            setSetting('secondary_color', $secondary);
            $success = "Theme colours updated! Bot buttons will also use these colours.";
        } else {
            $error = "Invalid colour code. Use hex like #3b82f6";
        }
    }

    // License
    if (isset($_POST['save_license'])) {
        $days = intval($_POST['license_days'] ?? 0);
        if ($days > 0) {
            $expiry = date('Y-m-d', strtotime("+{$days} days"));
            setSetting('license_expiry', $expiry);
            setSetting('license_owner', trim($_POST['license_owner'] ?? 'Client'));
            setSetting('license_note', trim($_POST['license_note'] ?? ''));
            $success = "License set for $days days. Expiry: $expiry";
        } elseif (!empty($_POST['license_lifetime']) && $_POST['license_lifetime'] == '1') {
            setSetting('license_expiry', '');
            $success = "License set to Lifetime (no expiry)";
        } else {
            $expiry = trim($_POST['license_expiry'] ?? '');
            setSetting('license_expiry', $expiry);
            setSetting('license_owner', trim($_POST['license_owner'] ?? ''));
            setSetting('license_note', trim($_POST['license_note'] ?? ''));
            $success = "License updated!";
        }
    }

    // Password
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM admin WHERE username = ?");
        $stmt->execute([$_SESSION['admin_name']]);
        $hash = $stmt->fetchColumn();

        if (password_verify($current, $hash)) {
            if ($new === $confirm && strlen($new) >= 6) {
                $pdo->prepare("UPDATE admin SET password = ? WHERE username = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_name']]);
                $success = "Password changed successfully!";
            } else {
                $error = "New password mismatch or too short (min 6)";
            }
        } else {
            $error = "Current password is wrong";
        }
    }
}

$bot_token     = getSetting('bot_token');
$admin_chat    = getSetting('admin_chat_id');
$user_log      = getSetting('user_log_channel');
$payment_ch    = getSetting('payment_proof_channel');
$primary       = getSetting('primary_color') ?: '#3b82f6';
$secondary     = getSetting('secondary_color') ?: '#8b5cf6';
$webhook_set   = getSetting('webhook_set') === '1';
$webhook_url   = getSetting('webhook_url');
$license_expiry = getSetting('license_expiry');
$license_owner  = getSetting('license_owner') ?: 'Baba';
$license_note   = getSetting('license_note');
$days_left      = licenseDaysLeft();
$license_ok     = isLicenseValid();

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
<?php if ($webhook_result): ?><div class="alert alert-info"><?= htmlspecialchars($webhook_result) ?></div><?php endif; ?>

<div class="card">
    <h3>🤖 Bot Configuration</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
        Bot Token + Chat ID daalo → <b>Save</b> karo → phir <b>Set Webhook</b> dabao. Bas.
    </p>
    <form method="POST">
        <label>Bot Token *</label>
        <input type="text" name="bot_token" value="<?= htmlspecialchars($bot_token) ?>" placeholder="123456789:AAH..." required>

        <label>Admin Chat ID *</label>
        <input type="text" name="admin_chat_id" value="<?= htmlspecialchars($admin_chat) ?>" placeholder="Your Telegram User ID">

        <label>User Log Channel ID</label>
        <input type="text" name="user_log_channel" value="<?= htmlspecialchars($user_log) ?>" placeholder="-100xxxxxxxxxx (New users yahan notify honge)">

        <label>Payment Proof Channel ID</label>
        <input type="text" name="payment_proof_channel" value="<?= htmlspecialchars($payment_ch) ?>" placeholder="-100xxxxxxxxxx (Payment screenshots backup)">

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
            <button type="submit" name="save_bot" class="btn btn-primary">💾 Save Configuration</button>
            <button type="submit" name="set_webhook" class="btn btn-success">🔗 Set Webhook</button>
            <?php if ($webhook_set): ?>
                <button type="submit" name="delete_webhook" class="btn btn-danger" onclick="return confirm('Webhook hataana hai?')">❌ Remove Webhook</button>
            <?php endif; ?>
        </div>
    </form>

    <div style="margin-top:16px;padding:14px;background:#0f0f17;border-radius:12px;border:1px solid #1e1e2d;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span style="font-size:18px;"><?= $webhook_set ? '✅' : '⚠️' ?></span>
            <strong>Webhook Status:</strong>
            <?php if ($webhook_set): ?>
                <span class="badge badge-green">Active</span>
            <?php else: ?>
                <span class="badge badge-yellow">Not Set</span>
            <?php endif; ?>
        </div>
        <?php if ($webhook_url): ?>
            <div style="font-size:12px;color:#94a3b8;word-break:break-all;">URL: <?= htmlspecialchars($webhook_url) ?></div>
        <?php else: ?>
            <div style="font-size:12px;color:#64748b;">Token daalo → Save → Set Webhook button dabao</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>🎨 Button & Theme Colours</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:14px;">
        Yahan colour change karoge toh <b>Bot ke Plan buttons</b> ka colour bhi change ho jayega.
    </p>
    <form method="POST">
        <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:16px;">
            <div>
                <label>Primary Colour</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="primary_color" value="<?= htmlspecialchars($primary) ?>" style="width:60px;height:40px;padding:0;border:none;cursor:pointer;">
                    <input type="text" value="<?= htmlspecialchars($primary) ?>" style="width:110px;margin:0;" readonly>
                </div>
            </div>
            <div>
                <label>Secondary Colour</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="secondary_color" value="<?= htmlspecialchars($secondary) ?>" style="width:60px;height:40px;padding:0;border:none;cursor:pointer;">
                    <input type="text" value="<?= htmlspecialchars($secondary) ?>" style="width:110px;margin:0;" readonly>
                </div>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label>Quick Presets</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#3b82f6,#8b5cf6)" onclick="setColors('#3b82f6','#8b5cf6')">Blue Purple</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#10b981,#059669)" onclick="setColors('#10b981','#059669')">Green</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#f59e0b,#d97706)" onclick="setColors('#f59e0b','#d97706')">Orange</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#ef4444,#dc2626)" onclick="setColors('#ef4444','#dc2626')">Red</button>
                <button type="button" class="btn btn-sm" style="background:linear-gradient(90deg,#ec4899,#db2777)" onclick="setColors('#ec4899','#db2777')">Pink</button>
            </div>
        </div>

        <div style="margin-bottom:16px;padding:14px;background:#0f0f17;border-radius:12px;border:1px solid #1e1e2d;">
            <label style="margin-bottom:8px;">Preview (Bot Button Style)</label>
            <button type="button" class="btn btn-primary" id="previewBtn">Buy Now • ₹199</button>
        </div>

        <button type="submit" name="save_theme" class="btn btn-primary">💾 Save Colours</button>
    </form>
</div>

<script>
function setColors(p, s) {
    document.querySelector('input[name="primary_color"]').value = p;
    document.querySelector('input[name="secondary_color"]').value = s;
    updatePreview();
}
function updatePreview() {
    const p = document.querySelector('input[name="primary_color"]').value;
    const s = document.querySelector('input[name="secondary_color"]').value;
    document.getElementById('previewBtn').style.background = `linear-gradient(90deg, ${p}, ${s})`;
}
document.querySelector('input[name="primary_color"]').addEventListener('input', updatePreview);
document.querySelector('input[name="secondary_color"]').addEventListener('input', updatePreview);
</script>


<div class="card">
    <h3>🔐 License / Share Bot</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:14px;">
        Kisi ko bot do toh yahan days set karo. Expiry ke baad uska bot automatically band ho jayega.
    </p>
    
    <div style="margin-bottom:16px;padding:14px;background:#0f0f17;border-radius:12px;border:1px solid #1e1e2d;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <strong>Status:</strong>
            <?php if ($license_ok): ?>
                <span class="badge badge-green">Active</span>
            <?php else: ?>
                <span class="badge badge-red">Expired</span>
            <?php endif; ?>
        </div>
        <?php if ($days_left < 0): ?>
            <div style="color:#94a3b8;font-size:13px;">Lifetime: Lifetime (no expiry)</div>
        <?php elseif ($days_left > 0): ?>
            <div style="color:#94a3b8;font-size:13px;">Days left: <b style="color:#34d399;">{$days_left}</b> • Expiry: <?php echo htmlspecialchars($license_expiry); ?></div>
        <?php else: ?>
            <div style="color:#f87171;font-size:13px;">Expired on: <?php echo htmlspecialchars($license_expiry); ?></div>
        <?php endif; ?>
        <?php if ($license_owner): ?>
            <div style="color:#64748b;font-size:12px;margin-top:4px;">Owner: <?php echo htmlspecialchars($license_owner); ?></div>
        <?php endif; ?>
    </div>

    <form method="POST">
        <label>Quick Set Days</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
            <button type="submit" name="save_license" class="btn btn-secondary btn-sm" onclick="document.getElementById('lic_days').value=7;document.getElementById('lic_life').value=0;">7 Days</button>
            <button type="submit" name="save_license" class="btn btn-secondary btn-sm" onclick="document.getElementById('lic_days').value=30;document.getElementById('lic_life').value=0;">30 Days</button>
            <button type="submit" name="save_license" class="btn btn-secondary btn-sm" onclick="document.getElementById('lic_days').value=90;document.getElementById('lic_life').value=0;">90 Days</button>
            <button type="submit" name="save_license" class="btn btn-secondary btn-sm" onclick="document.getElementById('lic_days').value=365;document.getElementById('lic_life').value=0;">1 Year</button>
            <button type="submit" name="save_license" class="btn btn-success btn-sm" onclick="document.getElementById('lic_days').value=0;document.getElementById('lic_life').value=1;">Lifetime</button>
        </div>
        <input type="hidden" name="license_days" id="lic_days" value="0">
        <input type="hidden" name="license_lifetime" id="lic_life" value="0">

        <label>Custom Expiry Date (YYYY-MM-DD)</label>
        <input type="date" name="license_expiry" value="<?php echo htmlspecialchars($license_expiry); ?>">

        <label>Client / Owner Name</label>
        <input type="text" name="license_owner" value="<?php echo htmlspecialchars($license_owner); ?>" placeholder="Client name">

        <label>Note (optional)</label>
        <input type="text" name="license_note" value="<?php echo htmlspecialchars($license_note); ?>" placeholder="e.g. Sold to Rahul">

        <button type="submit" name="save_license" class="btn btn-primary">💾 Save License</button>
    </form>
</div>

<div class="card">
    <h3>🔑 Change Password</h3>
    <form method="POST">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
        <label>New Password (min 6)</label>
        <input type="password" name="new_password" required>
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
        <button type="submit" name="change_password" class="btn btn-primary">🔒 Change Password</button>
    </form>
</div>

<div class="card">
    <h3>ℹ️ Panel Info</h3>
    <p style="color:#94a3b8;font-size:14px;line-height:1.7;">
        <strong>BABA PANEL</strong><br>
        Created by <span style="color:#f59e0b;">Baba</span><br>
        Version: 2.1 Final<br>
        © 2026 Premium Telegram Bot Panel
    </p>
</div>



<!-- ═══ BUTTON STYLE PICKER ═══ -->
<div class="card">
    <h3>🎨 Bot Button Style — Panel Se Choose Karo</h3>
    <p style="color:var(--muted);font-size:13px;margin-bottom:18px;">
        Telegram bot ke buttons ka style choose karo. Har style alag emojis use karta hai.
    </p>
    <form method="POST">
        <div class="style-grid">
            <?php $cur = getSetting('btn_style','fire'); ?>
            <label class="style-card <?= $cur=='fire'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="fire" <?= $cur=='fire'?'checked':'' ?>>
                <span class="s-icon">🔥</span>
                <span class="s-name">FIRE</span>
                <span class="s-ex">🔥 Plan • 💸 Paid</span>
            </label>
            <label class="style-card <?= $cur=='red'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="red" <?= $cur=='red'?'checked':'' ?>>
                <span class="s-icon">🔴</span>
                <span class="s-name">RED</span>
                <span class="s-ex">🔴 Plan • ✅ Paid</span>
            </label>
            <label class="style-card <?= $cur=='green'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="green" <?= $cur=='green'?'checked':'' ?>>
                <span class="s-icon">🟢</span>
                <span class="s-name">GREEN</span>
                <span class="s-ex">🟢 Plan • 💰 Paid</span>
            </label>
            <label class="style-card <?= $cur=='blue'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="blue" <?= $cur=='blue'?'checked':'' ?>>
                <span class="s-icon">🔵</span>
                <span class="s-name">BLUE</span>
                <span class="s-ex">🔵 Plan • 💎 Paid</span>
            </label>
            <label class="style-card <?= $cur=='gold'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="gold" <?= $cur=='gold'?'checked':'' ?>>
                <span class="s-icon">⭐</span>
                <span class="s-name">GOLD</span>
                <span class="s-ex">⭐ Plan • 💳 Paid</span>
            </label>
            <label class="style-card <?= $cur=='skull'?'selected':'' ?>">
                <input type="radio" name="btn_style" value="skull" <?= $cur=='skull'?'checked':'' ?>>
                <span class="s-icon">💀</span>
                <span class="s-name">SKULL</span>
                <span class="s-ex">💀 Plan • 🩸 Paid</span>
            </label>
        </div>
        <button type="submit" name="save_btn_style" class="btn btn-primary">💾 Save Button Style</button>
    </form>
</div>


<?php require_once 'includes/footer.php'; ?>
