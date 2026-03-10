<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$updateInfo = checkForUpdates();
$db = getDB();

// Son kontrol zamanını kaydet
$stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('last_update_check', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
$stmt->execute([time()]);

// Log kaydı ekle
$logDetails = [
    'available' => $updateInfo['available'],
    'current_sha' => $updateInfo['current_sha'],
    'latest_sha' => $updateInfo['latest_sha'],
    'manual_check' => true,
    'timestamp' => date('Y-m-d H:i:s')
];

$stmt = $db->prepare("INSERT INTO update_logs (action, details, created_at) VALUES ('check', ?, NOW())");
$stmt->execute([json_encode($logDetails)]);

echo json_encode($updateInfo);
