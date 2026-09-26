<?php
/**
 * BABA PANEL — Webhook v3.0 FIXED
 * All 3 flows fixed:
 *   1. State → DB (not /tmp)
 *   2. Group links → sent on approve
 *   3. Screenshot → panel viewable
 */

require_once __DIR__ . '/../config.php';

$content = file_get_contents("php://input");
$update  = json_decode($content, true);
if (!$update) { http_response_code(200); exit; }

if (!isLicenseValid()) {
    if (isset($update['message']['chat']['id'])) {
        telegramApi('sendMessage', [
            'chat_id'    => $update['message']['chat']['id'],
            'text'       => "⛔ Bot License Expired. Contact admin.",
            'parse_mode' => 'Markdown'
        ]);
    }
    http_response_code(200); exit;
}

// ─── HELPERS ────────────────────────────────────────────────────────

$E = getBtnStyle(); // emoji set from panel setting

function sendMsg($chat_id, $text, $keyboard = null) {
    $p = ['chat_id'=>$chat_id,'text'=>$text,'parse_mode'=>'Markdown','disable_web_page_preview'=>true];
    if ($keyboard) $p['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendMessage', $p);
}

function sendPhoto($chat_id, $file_id, $caption='', $keyboard=null) {
    $p = ['chat_id'=>$chat_id,'photo'=>$file_id,'caption'=>$caption,'parse_mode'=>'Markdown'];
    if ($keyboard) $p['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendPhoto', $p);
}

function sendVideo($chat_id, $file_id, $caption='', $keyboard=null) {
    $p = ['chat_id'=>$chat_id,'video'=>$file_id,'caption'=>$caption,'parse_mode'=>'Markdown'];
    if ($keyboard) $p['reply_markup'] = json_encode($keyboard);
    return telegramApi('sendVideo', $p);
}

function answerCb($cb_id, $text='') {
    telegramApi('answerCallbackQuery', ['callback_query_id'=>$cb_id,'text'=>$text,'show_alert'=>false]);
}

function getPlansKeyboard() {
    global $pdo, $E;
    $plans = $pdo->query("SELECT * FROM plans WHERE status='active' OR status IS NULL ORDER BY sort_order ASC, price ASC")->fetchAll();
    $btns  = [];
    foreach ($plans as $p) {
        $btns[] = [[
            'text'          => "{$E['plan']} {$p['name']} — ₹" . number_format($p['price']),
            'callback_data' => 'plan_' . $p['id']
        ]];
    }
    if (empty($btns)) $btns[] = [['text'=>'No plans available','callback_data'=>'none']];
    return ['inline_keyboard' => $btns];
}

// Save user in DB
function saveUser($user_id, $username, $full_name) {
    global $pdo;
    $pdo->prepare("
        INSERT INTO users (telegram_id, username, full_name, status, joined_at)
        VALUES (?, ?, ?, 'free', CURRENT_TIMESTAMP)
        ON CONFLICT(telegram_id) DO UPDATE SET
            username  = excluded.username,
            full_name = excluded.full_name
    ")->execute([$user_id, $username, $full_name]);
}

// ── FIX 1: Set pending plan in DB (not /tmp) ──────────────────────
function setPendingPlan($user_id, $plan_id) {
    global $pdo;
    $pdo->prepare("UPDATE users SET pending_plan_id = ? WHERE telegram_id = ?")
        ->execute([$plan_id, $user_id]);
}

// ── FIX 1: Get pending plan from DB ───────────────────────────────
function getPendingPlan($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT pending_plan_id FROM users WHERE telegram_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    return $row ? $row['pending_plan_id'] : null;
}

function clearPendingPlan($user_id) {
    global $pdo;
    $pdo->prepare("UPDATE users SET pending_plan_id = NULL WHERE telegram_id = ?")
        ->execute([$user_id]);
}

// ── FIX 2: Get all active group links ─────────────────────────────
function getGroupLinks() {
    global $pdo;
    return $pdo->query("SELECT * FROM groups WHERE status='Active' ORDER BY id ASC")->fetchAll();
}

// ─── CALLBACK HANDLER ───────────────────────────────────────────────

if (isset($update['callback_query'])) {
    $cb      = $update['callback_query'];
    $chat_id = $cb['message']['chat']['id'];
    $user_id = $cb['from']['id'];
    $data    = $cb['data'];
    $cb_id   = $cb['id'];

    answerCb($cb_id);

    // Show plans
    if ($data === 'show_plans') {
        $welcome = getSetting('welcome_message') ?: "Choose a plan:";
        sendMsg($chat_id, $welcome, getPlansKeyboard());
    }

    // Plan selected
    elseif (strpos($data, 'plan_') === 0) {
        $plan_id = intval(str_replace('plan_', '', $data));
        $stmt    = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();

        if (!$plan) { sendMsg($chat_id, "❌ Plan not found."); exit; }

        $upi = getSetting('upi_id') ?: 'Not set';

        $text  = "╔══════════════════╗\n";
        $text .= "    {$E['plan']} *PLAN DETAILS*\n";
        $text .= "╚══════════════════╝\n\n";
        $text .= "📦 *{$plan['name']}*\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "💰 Price » *₹" . number_format($plan['price']) . "*\n";
        $text .= "⏱ Validity » *{$plan['validity']} Days*\n";
        if ($plan['description']) $text .= "📝 Info » _{$plan['description']}_\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "💳 *PAY HERE*\n";
        $text .= "UPI ↠ `{$upi}`\n\n";
        $text .= "1️⃣ UPI se payment karo\n";
        $text .= "2️⃣ Screenshot lo\n";
        $text .= "3️⃣ Niche button dabakar screenshot bhejo";

        $keyboard = ['inline_keyboard' => [
            [['text' => "{$E['paid']} I Have Paid — Send Screenshot", 'callback_data' => 'paid_'.$plan_id]],
            [['text' => "{$E['back']} Back to Plans", 'callback_data' => 'show_plans']]
        ]];

        // Send QR if file_id set
        $qr_fid = getSetting('qr_file_id');
        if ($qr_fid) {
            sendPhoto($chat_id, $qr_fid, $text, $keyboard);
        } else {
            sendMsg($chat_id, $text, $keyboard);
        }
    }

    // User clicked "I Have Paid" — FIX 1: save to DB
    elseif (strpos($data, 'paid_') === 0) {
        $plan_id = intval(str_replace('paid_', '', $data));

        // Ensure user exists in DB
        $stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO users (telegram_id, status) VALUES (?, 'free')")->execute([$user_id]);
        }

        // Save pending plan to DB — no more /tmp files
        setPendingPlan($user_id, $plan_id);

        sendMsg($chat_id,
            "📸 *Screenshot Bhejo*\n\n" .
            "Payment ka screenshot is chat mein send karo.\n" .
            "Admin turant check karega. ✅"
        );
    }

    // How to use
    elseif ($data === 'how_to') {
        $howto = getSetting('howto_video_file_id');
        $txt   = "📖 *How To Buy*\n\n1. Plan choose karo\n2. UPI se pay karo\n3. Screenshot bhejo\n4. Admin approve karega\n5. Group link milega ✅";
        if ($howto) sendVideo($chat_id, $howto, $txt);
        else sendMsg($chat_id, $txt);
    }

    // Report
    elseif ($data === 'report') {
        $admin = getSetting('admin_chat_id');
        sendMsg($chat_id, "🚨 *Issue Report*\n\nApna issue type karke message bhejo. Admin ko forward kar diya jayega.");
    }

    http_response_code(200); exit;
}

// ─── MESSAGE HANDLER ────────────────────────────────────────────────

if (isset($update['message'])) {
    $msg       = $update['message'];
    $chat_id   = $msg['chat']['id'];
    $user_id   = $msg['from']['id'];
    $username  = $msg['from']['username'] ?? '';
    $full_name = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
    $text      = $msg['text'] ?? '';
    $admin_id  = getSetting('admin_chat_id');

    // ── Admin file_id extractor ──
    if ($admin_id && (string)$user_id === (string)$admin_id) {
        if (isset($msg['video'])) {
            $fid = $msg['video']['file_id'];
            sendMsg($chat_id, "✅ *Video File ID*\n\n`{$fid}`\n\nPanel Settings mein paste karo.");
            http_response_code(200); exit;
        }
        if (isset($msg['photo'])) {
            $photos = $msg['photo'];
            $fid    = end($photos)['file_id'];
            sendMsg($chat_id, "✅ *Photo File ID*\n\n`{$fid}`\n\nPanel Settings mein paste karo.");
            http_response_code(200); exit;
        }
        if (isset($msg['document'])) {
            $fid = $msg['document']['file_id'];
            sendMsg($chat_id, "✅ *Document File ID*\n\n`{$fid}`");
            http_response_code(200); exit;
        }
    }

    // ── /start ──
    if ($text === '/start' || strpos($text, '/start') === 0) {
        saveUser($user_id, $username, $full_name);

        // Log to channel
        $log_ch = getSetting('user_log_channel');
        if ($log_ch) {
            telegramApi('sendMessage', [
                'chat_id'      => $log_ch,
                'text'         => "👤 *New User*\nName: *{$full_name}*\n@" . ($username?:'N/A') . "\nID: `{$user_id}`\n" . date('d M H:i'),
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard'=>[[['text'=>'💬 DM','url'=>"tg://user?id={$user_id}"]]]])
            ]);
        }

        $welcome = getSetting('welcome_message') ?: "🔥 *Welcome!*\n\nChoose a plan:";
        $start_v = getSetting('start_video_file_id');
        $kb      = getPlansKeyboard();

        if ($start_v) sendVideo($chat_id, $start_v, $welcome, $kb);
        else sendMsg($chat_id, $welcome, $kb);

        http_response_code(200); exit;
    }

    // ── Screenshot received — FIX 1 + FIX 2 ──
    if (isset($msg['photo'])) {
        $photos  = $msg['photo'];
        $file_id = end($photos)['file_id'];

        // FIX 1: Get plan from DB — not /tmp
        $plan_id = getPendingPlan($user_id);
        clearPendingPlan($user_id);

        $plan_name = 'Unknown';
        $amount    = 0;

        if ($plan_id) {
            $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch();
            if ($plan) { $plan_name = $plan['name']; $amount = $plan['price']; }
        }

        // Save to pending_payments
        $pdo->prepare("
            INSERT INTO pending_payments (user_id, username, full_name, plan_id, plan_name, amount, screenshot, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ")->execute([$user_id, $username, $full_name, $plan_id, $plan_name, $amount, $file_id]);

        // Notify admin
        if ($admin_id) {
            $notify  = "💳 *New Payment*\n\n";
            $notify .= "👤 *{$full_name}*\n";
            $notify .= "🆔 `{$user_id}`\n";
            $notify .= "@" . ($username?:'N/A') . "\n";
            $notify .= "📦 Plan: *{$plan_name}*\n";
            $notify .= "💰 Amount: *₹" . number_format($amount) . "*\n\n";
            $notify .= "Panel se Approve / Reject karo ↗️";
            sendPhoto($admin_id, $file_id, $notify);
        }

        // Also to proof channel
        $proof_ch = getSetting('payment_proof_channel');
        if ($proof_ch) {
            sendPhoto($proof_ch, $file_id, "From {$full_name} (@{$username}) — {$plan_name} — ₹{$amount}");
        }

        // Reply to user
        sendMsg($chat_id,
            "⏳ *Screenshot Received!*\n\n" .
            "Aapka payment admin ke paas gaya hai.\n" .
            "Thodi der mein approve ho jayega. 🔥\n\n" .
            "_Koi problem ho to admin se contact karo._"
        );

        http_response_code(200); exit;
    }

    // Default
    if ($text && $text !== '/start') {
        sendMsg($chat_id, "Use /start to see plans. 🔥");
    }
}

http_response_code(200);
?>
