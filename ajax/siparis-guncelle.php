<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum gecersiz.']);
    exit;
}

$id = $_POST['id'] ?? null;
$hammaddeId = $_POST['hammadde_id'] ?? null;
$islem = $_POST['islem'] ?? null;

if ((!$id && !$hammaddeId) || !$islem) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

$db = getDB();

try {
    if ($islem === 'tamamla') {
        // ID veya hammadde_id ile tamamla
        if ($id) {
            $db->prepare("UPDATE siparisler SET geldi = 1, teslim_tarihi = CURDATE() WHERE id = ?")
               ->execute([$id]);
        } else {
            $db->prepare("UPDATE siparisler SET geldi = 1, teslim_tarihi = CURDATE() WHERE hammadde_id = ? AND geldi = 0")
               ->execute([$hammaddeId]);
        }
        echo json_encode(['success' => true, 'message' => 'Siparis tamamlandi.']);
    } elseif ($islem === 'iptal') {
        // ID veya hammadde_id ile iptal
        if ($id) {
            $db->prepare("DELETE FROM siparisler WHERE id = ?")
               ->execute([$id]);
        } else {
            $db->prepare("DELETE FROM siparisler WHERE hammadde_id = ? AND geldi = 0")
               ->execute([$hammaddeId]);
        }
        echo json_encode(['success' => true, 'message' => 'Siparis silindi.']);
    } elseif ($islem === 'sil') {
        // Kalıcı sil
        if ($id) {
            $db->prepare("DELETE FROM siparisler WHERE id = ?")
               ->execute([$id]);
        } else {
            $db->prepare("DELETE FROM siparisler WHERE hammadde_id = ?")
               ->execute([$hammaddeId]);
        }
        echo json_encode(['success' => true, 'message' => 'Siparis kalici olarak silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gecersiz islem.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
