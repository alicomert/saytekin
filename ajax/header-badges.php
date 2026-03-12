<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum gecersiz.']);
    exit;
}

$db = getDB();

// İhtiyaç listesi sayısını hesapla - getStokDurum() ile aynı mantık
$hammaddeler = getHammaddeler();

$ihtiyacSayi = 0;
foreach ($hammaddeler as $h) {
    $durum = getStokDurum($h);
    // Sadece TAKIPTE, SIPARIS VER, ACIL SIPARIS olanlar
    if ($h['sk'] !== 'K' && $h['sk'] !== 'A' && !empty($durum['label'])) {
        $ihtiyacSayi++;
    }
}

// Aktif sipariş sayısı
$aktifSiparisler = count($siparisler);

echo json_encode([
    'success' => true,
    'ihtiyacSayi' => $ihtiyacSayi,
    'aktifSiparisler' => $aktifSiparisler
]);
