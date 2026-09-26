<?php
define('BABA_PANEL', true);
require_once 'config.php';
requireLogin();
$page_title = 'Payment Settings';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save UPI
    if (isset($_POST['save_upi'])) {
        setSetting('upi_id', trim($_POST['upi_id'] ?? ''));
        $success = "UPI ID saved successfully!";
    }

    // Upload QR
    if (isset($_POST['upload_qr']) && isset($_FILES['qr_file']) && $_FILES['qr_file']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'qr_' . time() . '.' . $ext;
            $target = UPLOAD_DIR . $filename;
            if (move_uploaded_file($_FILES['qr_file']['tmp_name'], $target)) {
                // delete old qr if exists
                $old = getSetting('qr_image');
                if ($old && file_exists(UPLOAD_DIR . $old)) {
                    @unlink(UPLOAD_DIR . $old);
                }
                setSetting('qr_image', $filename);
                $success = "QR Code uploaded successfully!";
            } else {
                $error = "Failed to upload QR image.";
            }
        } else {
            $error = "Only JPG, PNG, WEBP allowed.";
        }
    }

    // Delete QR
    if (isset($_POST['delete_qr'])) {
        $old = getSetting('qr_image');
        if ($old && file_exists(UPLOAD_DIR . $old)) {
            @unlink(UPLOAD_DIR . $old);
        }
        setSetting('qr_image', '');
        $success = "QR Code deleted.";
    }

    // Upload status images
    foreach (['waiting_image', 'approved_image', 'rejected_image'] as $img_key) {
        if (isset($_POST['upload_' . $img_key]) && isset($_FILES[$img_key]) && $_FILES[$img_key]['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES[$img_key]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = $img_key . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$img_key]['tmp_name'], UPLOAD_DIR . $filename)) {
                    $old = getSetting($img_key);
                    if ($old && file_exists(UPLOAD_DIR . $old)) @unlink(UPLOAD_DIR . $old);
                    setSetting($img_key, $filename);
                    $success = ucfirst(str_replace('_', ' ', $img_key)) . " uploaded!";
                }
            }
        }
    }
}

$upi_id     = getSetting('upi_id');
$qr_image   = getSetting('qr_image');
$waiting    = getSetting('waiting_image');
$approved   = getSetting('approved_image');
$rejected   = getSetting('rejected_image');

require_once 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div class="card">
    <h3>💳 UPI ID (One time setup)</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Yahi UPI ID sab plans ke liye use hoga.</p>
    <form method="POST">
        <label>UPI ID</label>
        <input type="text" name="upi_id" value="<?= htmlspecialchars($upi_id) ?>" placeholder="yourname@upi / 9876543210@ybl">
        <button type="submit" name="save_upi" class="btn btn-primary">💾 Save UPI</button>
    </form>
    <?php if ($upi_id): ?>
        <div style="margin-top:14px;padding:12px;background:#065f46;border-radius:10px;color:#a7f3d0;font-size:14px;">
            ✅ Active UPI: <strong><?= htmlspecialchars($upi_id) ?></strong>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>📷 QR Code (One time upload)</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Yahi QR sab plans ke payment page pe dikhega.</p>

    <?php if ($qr_image && file_exists(UPLOAD_DIR . $qr_image)): ?>
        <img src="uploads/<?= htmlspecialchars($qr_image) ?>" class="img-preview" alt="QR">
        <form method="POST" style="margin-top:12px;">
            <button type="submit" name="delete_qr" class="btn btn-danger btn-sm" onclick="return confirm('Delete QR?')">🗑️ Delete QR</button>
        </form>
    <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="qr_file" accept="image/*" required>
            <button type="submit" name="upload_qr" class="btn btn-primary" style="margin-top:10px;">⬆️ Upload QR</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>🖼️ Status Images</h3>
    <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Payment ke different stages pe yeh images user ko bheji jayengi.</p>

    <div class="grid-2">
        <!-- Waiting -->
        <div>
            <label>⏳ Waiting Image</label>
            <?php if ($waiting && file_exists(UPLOAD_DIR . $waiting)): ?>
                <img src="uploads/<?= htmlspecialchars($waiting) ?>" class="img-preview"><br>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="waiting_image" accept="image/*">
                <button type="submit" name="upload_waiting_image" class="btn btn-secondary btn-sm" style="margin-top:8px;">Upload Waiting</button>
            </form>
        </div>

        <!-- Approved -->
        <div>
            <label>✅ Approved Image</label>
            <?php if ($approved && file_exists(UPLOAD_DIR . $approved)): ?>
                <img src="uploads/<?= htmlspecialchars($approved) ?>" class="img-preview"><br>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="approved_image" accept="image/*">
                <button type="submit" name="upload_approved_image" class="btn btn-secondary btn-sm" style="margin-top:8px;">Upload Approved</button>
            </form>
        </div>
    </div>

    <div style="margin-top:20px;">
        <label>❌ Rejected Image</label>
        <?php if ($rejected && file_exists(UPLOAD_DIR . $rejected)): ?>
            <img src="uploads/<?= htmlspecialchars($rejected) ?>" class="img-preview"><br>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="rejected_image" accept="image/*">
            <button type="submit" name="upload_rejected_image" class="btn btn-secondary btn-sm" style="margin-top:8px;">Upload Rejected</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
