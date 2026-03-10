<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum gecersiz.']);
    exit;
}

$id = $_POST['id'] ?? null;
$islem = $_POST['islem'] ?? null;

if (!$id || !$islem) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

$db = getDB();

try {
    if ($islem === 'tamamla') {
        $db->prepare("UPDATE siparisler SET geldi = 1, teslim_tarihi = CURDATE() WHERE id = ?")
           ->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Siparis tamamlandi.']);
    } elseif ($islem === 'iptal') {
        $db->prepare("DELETE FROM siparisler WHERE id = ?")
           ->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Siparis silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gecersiz islem.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
