<?php
session_start();

define('DB_FILE', __DIR__ . '/database.sqlite');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

try {
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE,
    password TEXT
);
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    validity INTEGER NOT NULL DEFAULT 30,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    link TEXT,
    description TEXT,
    status TEXT DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);
CREATE TABLE IF NOT EXISTS pending_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT,
    username TEXT,
    full_name TEXT,
    plan_id INTEGER,
    plan_name TEXT,
    amount REAL,
    screenshot TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id TEXT UNIQUE,
    username TEXT,
    full_name TEXT,
    plan TEXT,
    plan_id INTEGER,
    expiry DATE,
    status TEXT DEFAULT 'free',
    pending_plan_id INTEGER DEFAULT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS broadcasts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT,
    content TEXT,
    file_id TEXT,
    caption TEXT,
    status TEXT DEFAULT 'pending',
    sent_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// Add pending_plan_id column if old DB exists without it
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN pending_plan_id INTEGER DEFAULT NULL");
} catch (Exception $e) {}

// Default admin
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)")
        ->execute(['baba', password_hash('baba123', PASSWORD_DEFAULT)]);
}

$defaults = [
    'bot_token'             => '',
    'admin_chat_id'         => '',
    'webhook_set'           => '0',
    'webhook_url'           => '',
    'user_log_channel'      => '',
    'payment_proof_channel' => '',
    'start_video_file_id'   => '',
    'welcome_message'       => "🔥 *Welcome!*\n\nChoose a plan below:",
    'upi_id'                => '',
    'qr_image'              => '',
    'qr_file_id'            => '',
    'waiting_image'         => '',
    'approved_image'        => '',
    'approved_file_id'      => '',
    'rejected_image'        => '',
    'rejected_file_id'      => '',
    'howto_video_file_id'   => '',
    // BUTTON STYLE — Panel se choose karo
    'btn_style'             => 'fire',   // fire | red | green | blue | gold | skull
    // Panel theme
    'primary_color'         => '#ff0040',
    'secondary_color'       => '#ff6b00',
    'panel_name'            => 'BABA PANEL',
    'created_by'            => 'Baba',
    'license_expiry'        => '',
    'license_owner'         => 'Baba',
    'license_note'          => '',
];

foreach ($defaults as $k => $v) {
    $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")->execute([$k, $v]);
}

// ==================== HELPERS ====================

function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ($val !== false && $val !== null) ? $val : $default;
}

function setSetting($key, $value) {
    global $pdo;
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)")->execute([$key, $value]);
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) { header('Location: index.php'); exit; }
}

function money($amount) {
    return '₹' . number_format((float)$amount, 0);
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hrs ago';
    return date('d M Y', strtotime($datetime));
}

function isLicenseValid() {
    $expiry = getSetting('license_expiry');
    if (!$expiry || trim($expiry) === '') return true;
    return strtotime($expiry) >= strtotime(date('Y-m-d'));
}

function licenseDaysLeft() {
    $expiry = getSetting('license_expiry');
    if (!$expiry || trim($expiry) === '') return 9999;
    $days = (strtotime($expiry) - strtotime(date('Y-m-d'))) / 86400;
    return max(0, (int)$days);
}

// Bot style emojis — panel se choose hoga
function getBtnStyle() {
    $styles = [
        'fire'  => ['plan'=>'🔥','paid'=>'💸','back'=>'◀️','how'=>'📖','report'=>'🚨','start'=>'🔥'],
        'red'   => ['plan'=>'🔴','paid'=>'✅','back'=>'◀️','how'=>'📋','report'=>'⚠️','start'=>'🔴'],
        'green' => ['plan'=>'🟢','paid'=>'💰','back'=>'⬅️','how'=>'📗','report'=>'🚩','start'=>'🟢'],
        'blue'  => ['plan'=>'🔵','paid'=>'💎','back'=>'◀️','how'=>'📘','report'=>'📣','start'=>'🔵'],
        'gold'  => ['plan'=>'⭐','paid'=>'💳','back'=>'◀️','how'=>'📜','report'=>'📢','start'=>'⭐'],
        'skull' => ['plan'=>'💀','paid'=>'🩸','back'=>'◀️','how'=>'📖','report'=>'☠️','start'=>'💀'],
    ];
    $s = getSetting('btn_style', 'fire');
    return $styles[$s] ?? $styles['fire'];
}

function telegramApi($method, $params = []) {
    $token = getSetting('bot_token');
    if (!$token) return false;
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Get viewable URL from telegram file_id
function getTelegramFileUrl($file_id) {
    $token = getSetting('bot_token');
    if (!$token || !$file_id) return null;
    $res = telegramApi('getFile', ['file_id' => $file_id]);
    if (!empty($res['result']['file_path'])) {
        return "https://api.telegram.org/file/bot{$token}/" . $res['result']['file_path'];
    }
    return null;
}
?>
