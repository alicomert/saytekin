<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum gecersiz.']);
    exit;
}

$db = getDB();

// İhtiyaç listesi sayısını hesapla
$hammaddeler = getHammaddeler();
$siparisler = $db->query("SELECT * FROM siparisler WHERE geldi = 0")->fetchAll();
$siparisByHammadde = [];
foreach ($siparisler as $s) {
    $siparisByHammadde[$s['hammadde_id']] = $s;
}

$ihtiyacSayi = 0;
foreach ($hammaddeler as $h) {
    $stok = (float)$h['stok_miktari'];
    $opt = (float)$h['hesaplanan_optimum'];
    $sip = $siparisByHammadde[$h['id']] ?? null;
    $sipMiktar = $sip ? (float)$sip['miktar_kg'] : 0;
    $efektifStok = $stok + $sipMiktar;
    
    if ($h['sk'] !== 'K' && $h['sk'] !== 'A' && $opt > 0 && $efektifStok < $opt / 2) {
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
