<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum gecersiz.']);
    exit;
}

$hammaddeId = $_POST['hammadde_id'] ?? null;
$miktar = $_POST['miktar'] ?? null;
$siparisNo = $_POST['siparis_no'] ?? '';

if (!$hammaddeId || !$miktar || floatval($miktar) <= 0) {
    echo json_encode(['success' => false, 'error' => 'Hammadde ID ve miktar zorunludur.']);
    exit;
}

$db = getDB();

try {
    // Check if hammadde exists
    $stmt = $db->prepare("SELECT id FROM hammaddeler WHERE id = ?");
    $stmt->execute([$hammaddeId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Hammadde bulunamadi.']);
        exit;
    }
    
    // Check if there's already an active order for this hammadde
    $stmt = $db->prepare("SELECT id FROM siparisler WHERE hammadde_id = ? AND geldi = 0");
    $stmt->execute([$hammaddeId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Bu hammadde icin zaten aktif bir siparis var.']);
        exit;
    }
    
    // Insert new order
    $stmt = $db->prepare("INSERT INTO siparisler (hammadde_id, miktar_kg, siparis_no, tarih, geldi) VALUES (?, ?, ?, CURDATE(), 0)");
    $stmt->execute([$hammaddeId, $miktar, $siparisNo]);
    
    echo json_encode(['success' => true, 'message' => 'Siparis kaydedildi.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Hata: ' . $e->getMessage()]);
}
