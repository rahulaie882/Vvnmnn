<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Groups / Channels';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $name = trim($_POST['name'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name && $link) {
        $pdo->prepare("INSERT INTO groups (name, link, description) VALUES (?,?,?)")
            ->execute([$name, $link, $desc]);
        $success = "Group/Channel added successfully!";
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM groups WHERE id = ?")->execute([intval($_GET['delete'])]);
    header('Location: groups.php');
    exit;
}

$groups = $pdo->query("SELECT * FROM groups ORDER BY id DESC")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div class="card">
    <h3>➕ Add Group / Channel</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Premium users ko yeh links milenge.</p>
    <form method="POST">
        <label>Name *</label>
        <input type="text" name="name" placeholder="Premium Channel" required>
        
        <label>Invite Link *</label>
        <input type="text" name="link" placeholder="https://t.me/+xxxx or https://t.me/yourchannel" required>
        
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Optional description"></textarea>
        
        <button type="submit" name="add_group" class="btn btn-primary">+ Add</button>
    </form>
</div>

<div class="card">
    <h3>🔗 All Groups (<?= count($groups) ?>)</h3>
    
    <?php if (empty($groups)): ?>
        <p style="color:#64748b;">No groups added yet.</p>
    <?php else: ?>
        <?php foreach ($groups as $g): ?>
        <div style="background:#0f0f17;border:1px solid #1e1e2d;border-radius:12px;padding:16px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div>
                <div style="font-weight:600;margin-bottom:4px;"><?= htmlspecialchars($g['name']) ?></div>
                <div style="font-size:13px;color:#60a5fa;word-break:break-all;"><?= htmlspecialchars($g['link']) ?></div>
                <?php if ($g['description']): ?>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= htmlspecialchars($g['description']) ?></div>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="badge badge-green"><?= $g['status'] ?></span>
                <a href="?delete=<?= $g['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
