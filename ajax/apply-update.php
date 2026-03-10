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

$result = ['success' => false, 'steps' => []];

try {
    // Adım 1: Yedek oluştur
    $result['steps'][] = ['step' => 1, 'name' => 'Yedek oluşturuluyor...', 'status' => 'running'];
    $backupDir = createBackup();
    $result['steps'][0]['status'] = 'completed';
    $result['steps'][0]['detail'] = 'Yedek alındı: ' . basename(dirname($backupDir));
    
    // Adım 2: Güncellemeyi indir
    $result['steps'][] = ['step' => 2, 'name' => 'Güncelleme indiriliyor...', 'status' => 'running'];
    $zipFile = downloadLatestRelease();
    if (!$zipFile) {
        throw new Exception('Güncelleme indirilemedi');
    }
    $result['steps'][1]['status'] = 'completed';
    $result['steps'][1]['detail'] = 'İndirildi: ' . basename($zipFile);
    
    // Adım 3: Dosyaları uygula
    $result['steps'][] = ['step' => 3, 'name' => 'Dosyalar güncelleniyor...', 'status' => 'running'];
    $applyResult = extractAndApplyUpdate($zipFile);
    if (!$applyResult['success']) {
        throw new Exception('Dosya güncellemesi başarısız: ' . implode(', ', $applyResult['errors']));
    }
    $result['steps'][2]['status'] = 'completed';
    $result['steps'][2]['detail'] = count($applyResult['updated_files']) . ' dosya güncellendi';
    
    // Adım 4: Veritabanı migration'ları uygula
    $result['steps'][] = ['step' => 4, 'name' => 'Veritabanı güncelleniyor...', 'status' => 'running'];
    $migrationResult = applyDatabaseMigrations();
    if (!$migrationResult['success']) {
        throw new Exception('Veritabanı güncellemesi başarısız: ' . implode(', ', $migrationResult['errors']));
    }
    $result['steps'][3]['status'] = 'completed';
    $result['steps'][3]['detail'] = count($migrationResult['applied']) . ' migration uygulandı';
    
    // Adım 5: Sürüm bilgisini güncelle
    $result['steps'][] = ['step' => 5, 'name' => 'Sistem bilgileri güncelleniyor...', 'status' => 'running'];
    $latest = getLatestGitHubCommit();
    if ($latest) {
        updateSystemVersion($latest['sha']);
        $result['steps'][4]['status'] = 'completed';
        $result['steps'][4]['detail'] = 'Yeni sürüm: ' . substr($latest['sha'], 0, 7);
    }
    
    $result['success'] = true;
    $result['message'] = 'Sistem başarıyla güncellendi!';
    
} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
    
    // Hata durumunda son adımı işaretle
    if (!empty($result['steps'])) {
        $lastIndex = count($result['steps']) - 1;
        if ($result['steps'][$lastIndex]['status'] === 'running') {
            $result['steps'][$lastIndex]['status'] = 'error';
        }
    }
}

echo json_encode($result);
