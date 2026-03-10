<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$hammadde_id = $_POST['hammadde_id'] ?? null;
$index = $_POST['index'] ?? null;

if (!$hammadde_id || $index === null) {
    echo json_encode(['success' => false, 'error' => 'Eksik parametre']);
    exit;
}

$gecmis = getFiyatGecmisi($hammadde_id);

if (isset($gecmis[$index])) {
    deleteFiyatGecmisi($gecmis[$index]['id']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Kayıt bulunamadı']);
}
