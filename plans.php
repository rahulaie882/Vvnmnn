<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Plans';

$success = $error = '';
$edit_plan = null;

// Edit load
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_plan = $stmt->fetch();
}

// Add / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $validity = intval($_POST['validity'] ?? 30);
    $description = trim($_POST['description'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    if ($name && $price > 0) {
        if ($id > 0) {
            $pdo->prepare("UPDATE plans SET name=?, price=?, validity=?, description=? WHERE id=?")
                ->execute([$name, $price, $validity, $description, $id]);
            $success = "Plan updated successfully!";
        } else {
            $pdo->prepare("INSERT INTO plans (name, price, validity, description) VALUES (?,?,?,?)")
                ->execute([$name, $price, $validity, $description]);
            $success = "Plan added successfully!";
        }
        $edit_plan = null;
    } else {
        $error = "Name and Price are required.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM plans WHERE id = ?")->execute([intval($_GET['delete'])]);
    header('Location: plans.php');
    exit;
}

$plans = $pdo->query("SELECT * FROM plans ORDER BY sort_order ASC, id ASC")->fetchAll();

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card">
    <h3><?= $edit_plan ? '✏️ Edit Plan' : '➕ Add New Plan' ?></h3>
    <form method="POST">
        <?php if ($edit_plan): ?>
            <input type="hidden" name="id" value="<?= $edit_plan['id'] ?>">
        <?php endif; ?>

        <div class="grid-2">
            <div>
                <label>Plan Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit_plan['name'] ?? '') ?>" placeholder="e.g. 1 Month Premium" required>
            </div>
            <div>
                <label>Price (₹) *</label>
                <input type="number" name="price" value="<?= $edit_plan['price'] ?? '' ?>" step="1" min="1" placeholder="199" required>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <label>Validity (Days) *</label>
                <input type="number" name="validity" value="<?= $edit_plan['validity'] ?? 30 ?>" min="1" required>
            </div>
            <div>
                <label>Status</label>
                <input type="text" value="Active" disabled style="opacity:0.6;">
            </div>
        </div>

        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Short description about this plan..."><?= htmlspecialchars($edit_plan['description'] ?? '') ?></textarea>

        <div style="display:flex;gap:10px;margin-top:8px;">
            <button type="submit" class="btn btn-primary"><?= $edit_plan ? '💾 Update Plan' : '+ Add Plan' ?></button>
            <?php if ($edit_plan): ?>
                <a href="plans.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>📦 All Plans (<?= count($plans) ?>)</h3>

    <?php if (empty($plans)): ?>
        <p style="color:#64748b;">No plans added yet. Add your first plan above.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Validity</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= money($p['price']) ?></td>
                    <td><?= $p['validity'] ?> days</td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['description'] ?: '-') ?></td>
                    <td>
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this plan?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
