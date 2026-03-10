<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek']);
    exit;
}

$backupDir = getLastBackupDir();

if (!$backupDir) {
    echo json_encode(['success' => false, 'error' => 'Geri yüklenecek yedek bulunamadı']);
    exit;
}

$result = rollbackUpdate($backupDir);

if ($result['success']) {
    // Log kaydı
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO update_logs (action, details, created_at) VALUES ('rollback', ?, NOW())");
    $stmt->execute([json_encode(['backup_dir' => $backupDir, 'files_restored' => count($result['restored_files'])])]);
    
    $result['message'] = 'Sistem önceki sürüme geri yüklendi. ' . count($result['restored_files']) . ' dosya geri yüklendi.';
}

echo json_encode($result);
