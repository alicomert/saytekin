<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum gecersiz.']);
    exit;
}

$db = getDB();

// Hammaddeleri çek
$hammaddeler = getHammaddeler();

// Siparişleri çek
$siparisler = $db->query("SELECT * FROM siparisler WHERE geldi = 0")->fetchAll();
$siparisByHammadde = [];
foreach ($siparisler as $s) {
    $siparisByHammadde[$s['hammadde_id']] = $s;
}

// Tüketim verilerini çek
$tuketimTumu = [];
foreach ($hammaddeler as $h) {
    $tuketimTumu[$h['id']] = getTuketimVerileri($h['id']);
}

$ihtiyacListe = [];
$ozet = [
    'toplam' => 0,
    'siparisVerilmis' => 0,
    'acil' => 0,
    'siparisVer' => 0,
    'takipte' => 0
];

foreach ($hammaddeler as $h) {
    $stok = (float)$h['stok_miktari'];
    $opt = (float)$h['hesaplanan_optimum'];
    
    // Sipariş verileri
    $sip = $siparisByHammadde[$h['id']] ?? null;
    $sipMiktar = $sip ? (float)$sip['miktar_kg'] : 0;
    $efektifStok = $stok + $sipMiktar;
    
    // Sadece K ve A olmayan, optimumu olan ve efektif stok < optimum/2 olanları göster
    if ($h['sk'] !== 'K' && $h['sk'] !== 'A' && $opt > 0 && $efektifStok < $opt / 2) {
        $optEfektif = $opt / 2;
        $eksik = $optEfektif - $efektifStok;
        
        // Termin süresi
        $terminGun = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
        
        // Stok durum bilgisi (index.php ile aynı mantık)
        $durum = getStokDurum($h);
        $stokDurumLabel = $durum['label'] ?? '';
        
        // Tüketim hesapla
        $tuk = $tuketimTumu[$h['id']];
        $t2025 = [];
        $t2026 = [];
        for ($i = 1; $i <= 12; $i++) {
            $t2025[] = $tuk[2025][$i] ?? 0;
            $t2026[] = $tuk[2026][$i] ?? 0;
        }
        $tumTuk = array_merge($t2025, $t2026);
        $aylikOrt = count($tumTuk) > 0 ? array_sum($tumTuk) / 12 : 0;
        $gunlukTuk = $aylikOrt / 30;
        $kalanGun = $gunlukTuk > 0 ? round($efektifStok / $gunlukTuk) : null;
        
        $oran = $kalanGun !== null && $terminGun > 0 ? $kalanGun / $terminGun : ($kalanGun !== null ? 99 : null);
        
        $item = [
            'id' => (int)$h['id'],
            'sk' => $h['sk'],
            'stok_kodu' => $h['stok_kodu'],
            'tur_adi' => $h['tur_adi'] ?? '-',
            'hammadde_ismi' => $h['hammadde_ismi'],
            'tedarikci' => $h['tedarikci'],
            'stok_durum_label' => $stokDurumLabel,
            'stok' => $stok,
            'opt' => $opt,
            'siparis_verildi' => $sip ? true : false,
            'siparis_geldi' => $sip ? (bool)$sip['geldi'] : false,
            'siparis_miktar' => $sipMiktar,
            'siparis_no' => $sip ? $sip['siparis_no'] : '',
            'siparis_tarih' => $sip ? date('d.m.Y', strtotime($sip['tarih'])) : '',
            'termin_gun' => $terminGun,
            'aylik_ort' => $aylikOrt,
            'kalan_gun' => $kalanGun,
            'eksik' => $eksik,
            'oran' => $oran
        ];
        
        $ihtiyacListe[] = $item;
        
        // Özet istatistikleri güncelle
        $ozet['toplam']++;
        if ($item['siparis_verildi'] && !$item['siparis_geldi']) {
            $ozet['siparisVerilmis']++;
        }
        
        if ($oran !== null) {
            if ($oran < 0.5) $ozet['acil']++;
            elseif ($oran < 1) $ozet['siparisVer']++;
            else $ozet['takipte']++;
        } else {
            $ozet['takipte']++;
        }
    }
}

// Oran'a göre sırala (düşük oran = acil = önce)
usort($ihtiyacListe, function($a, $b) {
    if ($a['oran'] === null) return 1;
    if ($b['oran'] === null) return -1;
    return $a['oran'] <=> $b['oran'];
});

echo json_encode([
    'success' => true,
    'data' => $ihtiyacListe,
    'ozet' => $ozet,
    'timestamp' => date('Y-m-d H:i:s')
]);
