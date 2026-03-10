<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Yetkisiz erişim']);
    exit;
}

$updateInfo = checkForUpdates();

// Son kontrol zamanını kaydet
if ($updateInfo['available']) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('last_update_check', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([time(), time()]);
}

echo json_encode($updateInfo);
