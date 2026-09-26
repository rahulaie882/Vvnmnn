<?php
// This patch adds btn_style saving — merge into settings.php POST handler

// Inside save_theme block, add:
if (isset($_POST['save_theme'])) {
    setSetting('primary_color',   trim($_POST['primary_color']   ?? '#ff0040'));
    setSetting('secondary_color', trim($_POST['secondary_color'] ?? '#ff6b00'));
    setSetting('panel_name',      trim($_POST['panel_name']      ?? 'BABA PANEL'));
    setSetting('btn_style',       trim($_POST['btn_style']       ?? 'fire'));
    $success = "Theme & Button style saved!";
}
?>
