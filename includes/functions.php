<?php
require_once 'config.php';

// Hammadde fonksiyonları

function getHammaddeler($filters = []) {
    $db = getDB();
    
    $sql = "SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi,
            pb.sembol as para_sembol, ts.kod as teslimat_kodu,
            t.akreditif_gun, t.satici_tedarik_gun, t.yol_gun, t.depo_kabul_gun
            FROM hammaddeler h
            LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
            LEFT JOIN ulkeler u ON h.mensei_ulke_id = u.id
            LEFT JOIN paketleme_tipleri pt ON h.paketleme_kodu = pt.kod
            LEFT JOIN para_birimleri pb ON h.para_birimi_kodu = pb.kod
            LEFT JOIN teslimat_sekilleri ts ON h.teslimat_sekli_kodu = ts.kod
            LEFT JOIN termin_sureleri t ON h.id = t.hammadde_id
            WHERE h.is_active = 1";
    
    $params = [];
    
    if (!empty($filters['sk']) && $filters['sk'] != 'Tumu') {
        $sql .= " AND h.sk = ?";
        $params[] = $filters['sk'];
    }
    
    if (!empty($filters['tur']) && $filters['tur'] != 'Tumu') {
        $sql .= " AND h.tur_kodu = ?";
        $params[] = $filters['tur'];
    }
    
    if (!empty($filters['arama'])) {
        $sql .= " AND (h.hammadde_ismi LIKE ? OR h.stok_kodu LIKE ? OR h.urun_kodu LIKE ? OR ht.ad LIKE ?)";
        $arama = "%{$filters['arama']}%";
        $params = array_merge($params, [$arama, $arama, $arama, $arama]);
    }
    
    $sql .= " ORDER BY h.hammadde_ismi ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function getHammadde($id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi,
                          pb.sembol as para_sembol, ts.ad as teslimat_adi,
                          t.akreditif_gun, t.satici_tedarik_gun, t.yol_gun, t.depo_kabul_gun
                          FROM hammaddeler h
                          LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
                          LEFT JOIN ulkeler u ON h.mensei_ulke_id = u.id
                          LEFT JOIN paketleme_tipleri pt ON h.paketleme_kodu = pt.kod
                          LEFT JOIN para_birimleri pb ON h.para_birimi_kodu = pb.kod
                          LEFT JOIN teslimat_sekilleri ts ON h.teslimat_sekli_kodu = ts.kod
                          LEFT JOIN termin_sureleri t ON h.id = t.hammadde_id
                          WHERE h.id = ? AND h.is_active = 1");
    $stmt->execute([$id]);
    
    return $stmt->fetch();
}

function getTuketimVerileri($hammadde_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM tuketim_verileri WHERE hammadde_id = ? ORDER BY yil DESC, ay ASC");
    $stmt->execute([$hammadde_id]);
    
    $veriler = [];
    while ($row = $stmt->fetch()) {
        $veriler[$row['yil']][$row['ay']] = $row['miktar_kg'];
    }
    
    return $veriler;
}

function getTuketimOrtalama($hammadde_id, $yil) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT AVG(miktar_kg) as ortalama FROM tuketim_verileri 
                          WHERE hammadde_id = ? AND yil = ? AND miktar_kg IS NOT NULL");
    $stmt->execute([$hammadde_id, $yil]);
    $result = $stmt->fetch();
    
    return $result['ortalama'] ?: 0;
}

function getTuketimYillari() {
    $db = getDB();
    
    $stmt = $db->query("SELECT DISTINCT yil FROM tuketim_verileri WHERE miktar_kg IS NOT NULL AND miktar_kg > 0 ORDER BY yil ASC");
    $yillar = [];
    while ($row = $stmt->fetch()) {
        $yillar[] = (int)$row['yil'];
    }
    
    if (empty($yillar)) {
        $yillar = [date('Y') - 2, date('Y') - 1, date('Y')];
    }
    
    return $yillar;
}

function getSonGirilenAy() {
    $db = getDB();
    
    $stmt = $db->query("SELECT yil, ay FROM tuketim_verileri WHERE miktar_kg IS NOT NULL AND miktar_kg > 0 ORDER BY yil DESC, ay DESC LIMIT 1");
    $row = $stmt->fetch();
    
    if ($row) {
        return [
            'yil' => (int)$row['yil'],
            'ay' => (int)$row['ay']
        ];
    }
    
    return [
        'yil' => date('Y'),
        'ay' => date('n')
    ];
}

function getSonrakiAy($yil, $ay) {
    if ($ay < 12) {
        return ['yil' => $yil, 'ay' => $ay + 1];
    } else {
        return ['yil' => $yil + 1, 'ay' => 1];
    }
}

function getSon12AyOrtalama($hammadde_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri 
                          WHERE hammadde_id = ? AND miktar_kg IS NOT NULL 
                          ORDER BY yil DESC, ay DESC LIMIT 12");
    $stmt->execute([$hammadde_id]);
    
    $toplam = 0;
    $sayac = 0;
    while ($row = $stmt->fetch()) {
        $toplam += $row['miktar_kg'];
        $sayac++;
    }
    
    return $sayac > 0 ? $toplam / $sayac : 0;
}

function getSon3AyOrtalama($hammadde_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri 
                          WHERE hammadde_id = ? AND miktar_kg IS NOT NULL 
                          ORDER BY yil DESC, ay DESC LIMIT 3");
    $stmt->execute([$hammadde_id]);
    
    $toplam = 0;
    $sayac = 0;
    while ($row = $stmt->fetch()) {
        $toplam += $row['miktar_kg'];
        $sayac++;
    }
    
    return $sayac > 0 ? $toplam / $sayac : 0;
}

function saveHammadde($data, $id = null) {
    $db = getDB();
    
    $fields = [
        'sk', 'stok_kodu', 'urun_kodu', 'tur_kodu', 'hammadde_ismi', 'tedarikci',
        'mensei_ulke_id', 'paketleme_kodu', 'stok_miktari', 'hesaplanan_optimum',
        'birim_fiyat', 'para_birimi_kodu', 'fiyat_birimi', 'teslimat_sekli_kodu',
        'maliyet_tipi', 'maliyet_deger', 'maliyet_pb_kodu', 'maliyet_turu', 'alternatif'
    ];
    
    if ($id) {
        $setParts = [];
        $params = [];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "$field = ?";
                $params[] = $data[$field] ?: null;
            }
        }
        $params[] = $id;
        
        $sql = "UPDATE hammaddeler SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $id;
    } else {
        $columns = [];
        $placeholders = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = '?';
                $params[] = $data[$field] ?: null;
            }
        }
        
        $columns[] = 'created_by';
        $placeholders[] = '?';
        $params[] = $_SESSION['user_id'] ?? null;
        
        $sql = "INSERT INTO hammaddeler (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $db->lastInsertId();
    }
}

function saveTerminSuresi($hammadde_id, $data) {
    $db = getDB();
    
    $db->prepare("INSERT INTO termin_sureleri (hammadde_id, akreditif_gun, satici_tedarik_gun, yol_gun, depo_kabul_gun)
                   VALUES (?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                   akreditif_gun = VALUES(akreditif_gun),
                   satici_tedarik_gun = VALUES(satici_tedarik_gun),
                   yol_gun = VALUES(yol_gun),
                   depo_kabul_gun = VALUES(depo_kabul_gun)")
               ->execute([
                   $hammadde_id,
                   $data['akreditif'] ?? 0,
                   $data['satici_tedarik'] ?? 0,
                   $data['yol'] ?? 0,
                   $data['depo_kabul'] ?? 0
               ]);
}

function saveTuketimVerisi($hammadde_id, $yil, $ay, $miktar) {
    $db = getDB();
    
    $db->prepare("INSERT INTO tuketim_verileri (hammadde_id, yil, ay, miktar_kg)
                   VALUES (?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE miktar_kg = VALUES(miktar_kg)")
               ->execute([$hammadde_id, $yil, $ay, $miktar]);
}

function deleteHammadde($id) {
    $db = getDB();
    $db->prepare("UPDATE hammaddeler SET is_active = 0 WHERE id = ?")->execute([$id]);
}

// Dropdown verileri
function getTurler() {
    $db = getDB();
    return $db->query("SELECT * FROM hammadde_turleri WHERE is_active = 1 ORDER BY sira")->fetchAll();
}

function getUlkeler() {
    $db = getDB();
    return $db->query("SELECT * FROM ulkeler WHERE is_active = 1 ORDER BY ad")->fetchAll();
}

function getPaketlemeTipleri() {
    $db = getDB();
    return $db->query("SELECT * FROM paketleme_tipleri ORDER BY id")->fetchAll();
}

function getTeslimatSekilleri() {
    $db = getDB();
    return $db->query("SELECT * FROM teslimat_sekilleri WHERE is_active = 1 ORDER BY kod")->fetchAll();
}

function getParaBirimleri() {
    $db = getDB();
    return $db->query("SELECT * FROM para_birimleri ORDER BY kod")->fetchAll();
}

// Stok durumu hesaplama
function getStokDurum($hammadde) {
    $stok = (float)($hammadde['stok_miktari'] ?? 0);
    $optimum = (float)($hammadde['hesaplanan_optimum'] ?? 0);
    $termin = (int)($hammadde['akreditif_gun'] ?? 0) + 
              (int)($hammadde['satici_tedarik_gun'] ?? 0) + 
              (int)($hammadde['yol_gun'] ?? 0) + 
              (int)($hammadde['depo_kabul_gun'] ?? 0);
    
    $gunlukTuketim = getGunlukTuketim($hammadde['id']);
    $kalanGun = $gunlukTuketim > 0 ? round($stok / $gunlukTuketim) : null;
    $oran = ($kalanGun !== null && $termin > 0) ? $kalanGun / $termin : null;
    
    $durum = ['label' => '', 'renk' => '', 'kritik' => false];
    
    if ($oran === null) {
        $durum = ['label' => '', 'renk' => '#94a3b8', 'kritik' => false];
    } elseif ($oran >= 2) {
        $durum = ['label' => '', 'renk' => '#34d399', 'kritik' => false];
    } elseif ($oran >= 1) {
        $durum = ['label' => 'TAKIPTE', 'renk' => '#eab308', 'kritik' => false];
    } elseif ($oran >= 0.5) {
        $durum = ['label' => 'SIPARIS VER', 'renk' => '#f97316', 'kritik' => true];
    } else {
        $durum = ['label' => 'ACIL SIPARIS', 'renk' => '#ef4444', 'kritik' => true];
    }
    
    return array_merge($durum, [
        'kalan_gun' => $kalanGun,
        'oran' => $oran,
        'gunluk_tuketim' => $gunlukTuketim
    ]);
}

function getGunlukTuketim($hammadde_id) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri 
                          WHERE hammadde_id = ? AND miktar_kg IS NOT NULL 
                          ORDER BY yil DESC, ay DESC LIMIT 14");
    $stmt->execute([$hammadde_id]);
    
    $toplam = 0;
    $sayac = 0;
    while ($row = $stmt->fetch()) {
        $toplam += $row['miktar_kg'];
        $sayac++;
    }
    
    return $sayac > 0 ? ($toplam / $sayac) / 30 : 0;
}

// Maliyet hesaplamaları
function hesaplaVarisMaliyeti($hammadde, $kurlar = null) {
    $birimFiyat = (float)($hammadde['birim_fiyat'] ?? 0);
    $maliyetDeger = (float)($hammadde['maliyet_deger'] ?? 0);
    $paraBirimi = $hammadde['para_birimi_kodu'] ?? 'USD';
    $maliyetPB = $hammadde['maliyet_pb_kodu'] ?? $paraBirimi;
    $maliyetTip = $hammadde['maliyet_tipi'] ?? 'yuzde';
    
    if ($maliyetTip == 'yuzde') {
        $maliyet = $birimFiyat * ($maliyetDeger / 100);
    } else {
        if ($maliyetPB == $paraBirimi || !$kurlar) {
            $maliyet = $maliyetDeger;
        } else {
            $kurKey = $maliyetPB . '_' . $paraBirimi;
            $kur = $kurlar[$kurKey] ?? 0;
            $maliyet = $kur > 0 ? $maliyetDeger * $kur : $maliyetDeger;
        }
    }
    
    return $birimFiyat + $maliyet;
}

function hesaplaMaliyet($hammadde, $kurlar = null) {
    $birimFiyat = (float)($hammadde['birim_fiyat'] ?? 0);
    $maliyetDeger = (float)($hammadde['maliyet_deger'] ?? 0);
    $paraBirimi = $hammadde['para_birimi_kodu'] ?? 'USD';
    $maliyetPB = $hammadde['maliyet_pb_kodu'] ?? $paraBirimi;
    $maliyetTip = $hammadde['maliyet_tipi'] ?? 'yuzde';
    
    if ($maliyetTip == 'yuzde') {
        return $birimFiyat * ($maliyetDeger / 100);
    }
    
    if ($maliyetPB == $paraBirimi || !$kurlar) {
        return $maliyetDeger;
    }
    
    $kurKey = $maliyetPB . '_' . $paraBirimi;
    $kur = $kurlar[$kurKey] ?? 0;
    return $kur > 0 ? $maliyetDeger * $kur : $maliyetDeger;
}

// Doviz kurlari - Direkt TCMB'den anlik cek (database'e kaydetmez)
function getDovizKurlari() {
    // TCMB'den cek
    $tcmbUrl = 'https://www.tcmb.gov.tr/kurlar/today.xml';
    
    try {
        // SSL sertifika dogrulamasini devre disi birak
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $xmlContent = @file_get_contents($tcmbUrl, false, $context);
        
        if ($xmlContent === false) {
            throw new Exception('TCMB XML erisilemedi');
        }
        
        // XML parse et
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new Exception('XML parse hatasi');
        }
        
        $tcmbKurlar = [];
        
        // TCMB'den gelen kurlari parse et
        foreach ($xml->Currency as $currency) {
            $kod = (string)$currency['Kod'];
            $forexBuying = (float)$currency->ForexBuying;
            $forexSelling = (float)$currency->ForexSelling;
            
            // Ortalama kur (alis + satis / 2)
            $kur = ($forexBuying + $forexSelling) / 2;
            
            if ($kur > 0) {
                $tcmbKurlar[$kod] = $kur;
            }
        }
        
        // Temel kurlari hesapla (TRY bazli)
        if (isset($tcmbKurlar['USD']) && isset($tcmbKurlar['EUR']) && isset($tcmbKurlar['GBP'])) {
            $usdTry = $tcmbKurlar['USD'];
            $eurTry = $tcmbKurlar['EUR'];
            $gbpTry = $tcmbKurlar['GBP'];
            
            // Capraz kurlar
            $eurUsd = $eurTry / $usdTry;
            $gbpUsd = $gbpTry / $usdTry;
            
            return [
                // TRY Bazli
                'USD_TRY' => $usdTry,
                'EUR_TRY' => $eurTry,
                'GBP_TRY' => $gbpTry,
                // USD bazli
                'EUR_USD' => $eurUsd,
                'GBP_USD' => $gbpUsd,
                'USD_EUR' => 1 / $eurUsd,
                'USD_GBP' => 1 / $gbpUsd,
                // Capraz
                'EUR_GBP' => $eurTry / $gbpTry,
                'GBP_EUR' => $gbpTry / $eurTry,
                // Ters kurlar
                'TRY_USD' => 1 / $usdTry,
                'TRY_EUR' => 1 / $eurTry,
                'TRY_GBP' => 1 / $gbpTry
            ];
        }
        
        throw new Exception('TCMB kurlari eksik');
        
    } catch (Exception $e) {
        // Hata durumunda varsayilan degerler
        return [
            'USD_TRY' => 38.5,
            'EUR_TRY' => 41.2,
            'GBP_TRY' => 48.8,
            'EUR_USD' => 1.07,
            'GBP_USD' => 1.27,
            'USD_EUR' => 0.93,
            'USD_GBP' => 0.79,
            'EUR_GBP' => 0.85,
            'GBP_EUR' => 1.18,
            'TRY_USD' => 0.026,
            'TRY_EUR' => 0.024,
            'TRY_GBP' => 0.021
        ];
    }
}

// Siparis fonksiyonlari
function getSiparisler($durum = 'bekleyen') {
    $db = getDB();
    
    if ($durum === 'bekleyen') {
        $sql = "SELECT s.*, h.hammadde_ismi, h.stok_kodu, h.tur_kodu, h.tedarikci,
                       h.birim_fiyat, h.para_birimi_kodu, h.fiyat_birimi, h.maliyet_tipi, 
                       h.maliyet_deger, h.maliyet_pb_kodu
                FROM siparisler s
                JOIN hammaddeler h ON s.hammadde_id = h.id
                WHERE s.geldi = 0 AND s.verildi = 1 AND h.is_active = 1
                ORDER BY s.tarih DESC";
    } else {
        $sql = "SELECT s.*, h.hammadde_ismi, h.stok_kodu, h.tur_kodu, h.tedarikci
                FROM siparisler s
                JOIN hammaddeler h ON s.hammadde_id = h.id
                WHERE s.geldi = 1 AND h.is_active = 1
                ORDER BY s.teslim_tarihi DESC";
    }
    
    return $db->query($sql)->fetchAll();
}

function countAktifSiparisler() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as sayi FROM siparisler WHERE geldi = 0 AND verildi = 1");
    return $stmt->fetch()['sayi'];
}

// Fiyat gecmisi
function getFiyatGecmisi($hammadde_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT fg.*, pb.sembol 
                          FROM fiyat_gecmisi fg
                          JOIN para_birimleri pb ON fg.para_birimi_kodu = pb.kod
                          WHERE fg.hammadde_id = ?
                          ORDER BY fg.kayit_tarihi DESC");
    $stmt->execute([$hammadde_id]);
    return $stmt->fetchAll();
}

function saveFiyatGecmisi($hammadde_id, $fiyat) {
    $db = getDB();
    
    $stmt = $db->prepare("INSERT INTO fiyat_gecmisi (
        hammadde_id, birim_fiyat, para_birimi_kodu, fiyat_birimi,
        teslimat_sekli_kodu, maliyet_tipi, maliyet_deger, maliyet_pb_kodu, maliyet_turu, kayit_tarihi
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $hammadde_id,
        $fiyat['birim_fiyat'] ?? 0,
        $fiyat['para_birimi_kodu'] ?? 'USD',
        $fiyat['fiyat_birimi'] ?? 'ton',
        $fiyat['teslimat_sekli_kodu'] ?? null,
        $fiyat['maliyet_tipi'] ?? null,
        $fiyat['maliyet_deger'] ?? null,
        $fiyat['maliyet_pb_kodu'] ?? null,
        $fiyat['maliyet_turu'] ?? 'T',
        date('Y-m-d')
    ]);
}

function deleteFiyatGecmisi($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM fiyat_gecmisi WHERE id = ?");
    $stmt->execute([$id]);
}

// =====================================================
// ROL SİSTEMİ
// =====================================================

function getCurrentUserRole() {
    if (!isLoggedIn()) return null;
    return $_SESSION['user_role'] ?? 'user';
}

function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

function isManager() {
    $role = getCurrentUserRole();
    return $role === 'admin' || $role === 'manager';
}

function canEdit() {
    return isManager();
}

function canDelete() {
    return isAdmin();
}

function checkPermission($action = 'view') {
    // Public access kontrolü
    $db = getDB();
    $publicAccess = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'public_access'")->fetchColumn() ?? '0';
    
    if ($publicAccess === '1' && $action === 'view' && !isLoggedIn()) {
        return true; // Public access aktifse ve sadece görüntüleme yapılacaksa izin ver
    }
    
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    switch ($action) {
        case 'view':
            return true; // Herkes görüntüleyebilir
        case 'edit':
        case 'add':
            if (!canEdit()) {
                setFlashMessage('error', 'Bu işlem için yetkiniz yok.');
                header('Location: index.php');
                exit;
            }
            return true;
        case 'delete':
        case 'admin':
            if (!isAdmin()) {
                setFlashMessage('error', 'Bu işlem için admin yetkisi gerekiyor.');
                header('Location: index.php');
                exit;
            }
            return true;
        default:
            return false;
    }
}

// =====================================================
// GİTHUB GÜNCELLEME SİSTEMİ
// =====================================================

function getCurrentCommitSHA() {
    $db = getDB();
    return $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'last_commit_sha'")->fetchColumn();
}

function getLatestGitHubCommit() {
    $repo = 'alicomert/saytekin';
    $url = "https://api.github.com/repos/{$repo}/commits/main";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Hammadde-Takip-Updater');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

function checkForUpdates() {
    $latest = getLatestGitHubCommit();
    if (!$latest) return ['available' => false, 'error' => 'GitHub bağlantısı kurulamadı'];
    
    $currentSHA = getCurrentCommitSHA();
    $latestSHA = $latest['sha'] ?? null;
    
    return [
        'available' => $latestSHA !== $currentSHA,
        'current_sha' => $currentSHA,
        'latest_sha' => $latestSHA,
        'message' => $latest['commit']['message'] ?? 'Bilinmiyor',
        'date' => $latest['commit']['committer']['date'] ?? null,
        'author' => $latest['commit']['author']['name'] ?? 'Bilinmiyor'
    ];
}

function downloadLatestRelease() {
    $repo = 'alicomert/saytekin';
    $zipUrl = "https://github.com/{$repo}/archive/refs/heads/main.zip";
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $zipFile = $uploadDir . 'update_' . time() . '.zip';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Hammadde-Takip-Updater');
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data) {
        file_put_contents($zipFile, $data);
        return $zipFile;
    }
    
    return false;
}

function createBackup() {
    // Zaman limitini 5 dakikaya çıkar
    set_time_limit(300);
    
    $backupDir = __DIR__ . '/../backups/' . date('Y-m-d_H-i-s') . '/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Hariç tutulacak klasörler
    $excludeDirs = ['backups', 'uploads', 'update_temp', 'vendor', 'node_modules', '.git', 'cache'];
    
    // PHP dosyalarını yedekle
    $sourceDir = __DIR__ . '/../';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $backedUpFiles = [];
    foreach ($iterator as $file) {
        // Klasör kontrolü - hariç tutulan klasörlerdeki dosyaları atla
        $relativePath = str_replace($sourceDir, '', $file->getPathname());
        $shouldExclude = false;
        foreach ($excludeDirs as $exclude) {
            if (strpos($relativePath, $exclude . '/') === 0 || strpos($relativePath, $exclude . '\\') === 0) {
                $shouldExclude = true;
                break;
            }
        }
        if ($shouldExclude) {
            continue;
        }
        
        if ($file->isFile() && $file->getExtension() === 'php') {
            $targetPath = $backupDir . $relativePath;
            $targetDir = dirname($targetPath);
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            copy($file->getPathname(), $targetPath);
            $backedUpFiles[] = $relativePath;
        }
    }
    
    // Yedek bilgisini kaydet
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO update_logs (action, details, created_at) VALUES ('backup', ?, NOW())");
    $stmt->execute([json_encode(['files' => $backedUpFiles, 'backup_dir' => $backupDir])]);
    
    return $backupDir;
}

function extractAndApplyUpdate($zipFile) {
    // Zaman limitini 5 dakikaya çıkar
    set_time_limit(300);
    
    $extractDir = __DIR__ . '/../update_temp/';
    
    // Eski temp dizini varsa temizle
    if (is_dir($extractDir)) {
        removeDirectory($extractDir);
    }
    mkdir($extractDir, 0755, true);
    
    // Zip'i aç
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($extractDir);
        $zip->close();
    } else {
        return ['success' => false, 'error' => 'ZIP dosyası açılamadı'];
    }
    
    // Çıkarılan klasörü bul
    $extractedFolders = glob($extractDir . '*', GLOB_ONLYDIR);
    if (empty($extractedFolders)) {
        return ['success' => false, 'error' => 'ZIP içeriği bulunamadı'];
    }
    
    $sourceFolder = $extractedFolders[0];
    $targetFolder = __DIR__ . '/../';
    
    // Dosyaları kopyala (config.php ve uploads/ hariç)
    $errors = [];
    $updatedFiles = [];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $relativePath = str_replace($sourceFolder . '/', '', $file->getPathname());
        
        // Config.php ve uploads klasörünü atla
        if (strpos($relativePath, 'includes/config.php') !== false ||
            strpos($relativePath, 'uploads/') === 0 ||
            strpos($relativePath, 'backups/') === 0) {
            continue;
        }
        
        $targetPath = $targetFolder . $relativePath;
        
        if ($file->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            if (!is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0755, true);
            }
            
            if (copy($file->getPathname(), $targetPath)) {
                $updatedFiles[] = $relativePath;
            } else {
                $errors[] = $relativePath;
            }
        }
    }
    
    // Temp dizinini temizle
    removeDirectory($extractDir);
    unlink($zipFile);
    
    return [
        'success' => empty($errors),
        'updated_files' => $updatedFiles,
        'errors' => $errors
    ];
}

function applyDatabaseMigrations() {
    $db = getDB();
    
    // Migration tablosu yoksa oluştur (prepare/execute kullanarak)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Migration tablosu oluşturulamadı: ' . $e->getMessage()]];
    }
    
    // Database klasöründeki migration dosyalarını kontrol et
    $migrationDir = __DIR__ . '/../Database/';
    if (!is_dir($migrationDir)) {
        return ['success' => true, 'message' => 'Migration dizini bulunamadı', 'applied' => []];
    }
    
    $files = glob($migrationDir . '*.sql');
    sort($files);
    
    $applied = [];
    $errors = [];
    
    // Önce uygulanmış migration'ları al
    $appliedMigrations = [];
    try {
        $result = $db->query("SELECT filename FROM schema_migrations");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $appliedMigrations[] = $row['filename'];
        }
    } catch (PDOException $e) {
        // Tablo boş olabilir, devam et
    }
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Daha önce uygulanmış mı kontrol et
        if (in_array($filename, $appliedMigrations)) {
            continue;
        }
        
        // SQL'i oku ve çalıştır
        $sql = file_get_contents($file);
        
        try {
            // Transaction başlat
            $db->beginTransaction();
            
            // SQL'i çalıştır (multi-statement)
            $db->exec($sql);
            
            // Migration kaydını ekle
            $stmt = $db->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
            $stmt->execute([$filename]);
            
            // Transaction commit
            $db->commit();
            
            $applied[] = $filename;
        } catch (Exception $e) {
            // Rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = $filename . ': ' . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'applied' => $applied,
        'errors' => $errors
    ];
}

function updateSystemVersion($commitSHA) {
    $db = getDB();
    $stmt1 = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('last_commit_sha', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt1->execute([$commitSHA, $commitSHA]);
    $stmt1->closeCursor();
    
    // Log kaydı
    $stmt2 = $db->prepare("INSERT INTO update_logs (action, details, created_at) VALUES ('update', ?, NOW())");
    $stmt2->execute([json_encode(['sha' => $commitSHA, 'date' => date('Y-m-d H:i:s')])]);
    $stmt2->closeCursor();
}

function rollbackUpdate($backupDir) {
    if (!is_dir($backupDir)) {
        return ['success' => false, 'error' => 'Yedek dizini bulunamadı'];
    }
    
    $sourceDir = $backupDir;
    $targetDir = __DIR__ . '/../';
    
    $restoredFiles = [];
    $errors = [];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($sourceDir, '', $file->getPathname());
            $targetPath = $targetDir . $relativePath;
            
            if (!is_dir(dirname($targetPath))) {
                mkdir(dirname($targetPath), 0755, true);
            }
            
            if (copy($file->getPathname(), $targetPath)) {
                $restoredFiles[] = $relativePath;
            } else {
                $errors[] = $relativePath;
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'restored_files' => $restoredFiles,
        'errors' => $errors
    ];
}

function removeDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

function getLastBackupDir() {
    $backupBase = __DIR__ . '/../backups/';
    if (!is_dir($backupBase)) return null;
    
    $dirs = glob($backupBase . '*', GLOB_ONLYDIR);
    if (empty($dirs)) return null;
    
    rsort($dirs);
    return $dirs[0] . '/';
}
