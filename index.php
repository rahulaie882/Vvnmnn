<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid Username or Password';
    }
}

$primary = getSetting('primary_color') ?: '#3b82f6';
$secondary = getSetting('secondary_color') ?: '#8b5cf6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BABA PANEL | Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        body {
            background: #05050a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
            top: -100px; left: -100px;
            border-radius: 50%;
        }
        body::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(139,92,246,0.12) 0%, transparent 70%);
            bottom: -80px; right: -80px;
            border-radius: 50%;
        }
        .login-box {
            background: rgba(15,15,25,0.85);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 42px 32px;
            width: 90%;
            max-width: 380px;
            text-align: center;
            border: 1px solid rgba(99,102,241,0.25);
            box-shadow: 0 0 60px rgba(99,102,241,0.1), 0 25px 50px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
        }
        .logo {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, <?php echo $primary; ?>, <?php echo $secondary; ?>);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 22px;
            font-size: 36px;
            box-shadow: 0 0 30px rgba(99,102,241,0.4);
            animation: pulse 2.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(99,102,241,0.3); }
            50% { box-shadow: 0 0 40px rgba(99,102,241,0.5); }
        }
        h1 {
            font-size: 28px; font-weight: 800; margin-bottom: 6px;
            background: linear-gradient(90deg, <?php echo $primary; ?>, <?php echo $secondary; ?>);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 6px; letter-spacing: 1px; }
        .created { color: #64748b; font-size: 12px; margin-bottom: 28px; }
        .created span { color: #f59e0b; }
        .input-group { margin-bottom: 14px; position: relative; text-align: left; }
        .input-group input {
            width: 100%; padding: 14px 16px 14px 46px;
            background: #0a0a12; border: 1px solid rgba(99,102,241,0.2);
            border-radius: 14px; color: white; font-size: 15px; outline: none;
            transition: 0.3s;
        }
        .input-group input:focus { border-color: <?php echo $primary; ?>; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px; }
        .btn {
            width: 100%; padding: 15px;
            background: linear-gradient(90deg, <?php echo $primary; ?>, <?php echo $secondary; ?>);
            border: none; border-radius: 14px; color: white;
            font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 8px 25px rgba(99,102,241,0.3);
            transition: 0.2s;
        }
        .btn:hover { opacity: 0.92; transform: translateY(-1px); }
        .error {
            background: rgba(127,29,29,0.5); color: #fecaca;
            padding: 10px 14px; border-radius: 12px; font-size: 13px; margin-bottom: 16px;
            border: 1px solid rgba(248,113,113,0.3);
        }
        .footer { margin-top: 28px; font-size: 12px; color: #475569; }
        .footer span { color: <?php echo $primary; ?>; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">👑</div>
        <h1>BABA PANEL</h1>
        <div class="subtitle">Administrator Access</div>
        <div class="created">— Created by <span>Baba</span> —</div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i>👤</i>
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
            </div>
            <div class="input-group">
                <i>🔒</i>
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Access Panel →</button>
        </form>

        <div class="footer">© 2026 BABA PANEL • <span>Premium Telegram Bot</span></div>
    </div>
</body>
</html>
