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
            return $sonuc;
        }
    }
    
    // TCMB'den cek
    $tcmbUrl = 'https://www.tcmb.gov.tr/kurlar/today.xml';
    
    try {
        // SSL sertifika dogrulamasini devre disi birak (TCMB sertifika sorunu olabilir)
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
        
        $kurlar = [];
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
            $gbpEur = $gbpTry / $eurTry;
            
            $kurlar = [
                // TRY bazli
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
            
            // Veritabanina kaydet
            $tarih = date('Y-m-d');
            foreach ($kurlar as $key => $kur) {
                $parts = explode('_', $key);
                try {
                    $db->prepare("INSERT INTO doviz_kurlari (kaynak_pb, hedef_pb, kur, tarih) 
                                  VALUES (?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE 
                                  kur = VALUES(kur), 
                                  created_at = NOW()")
                       ->execute([$parts[0], $parts[1], $kur, $tarih]);
                } catch (Exception $e) {
                    // Hata durumunda devam et
                }
            }
            
            return $kurlar;
        }
        
        throw new Exception('TCMB kurlari eksik');
        
    } catch (Exception $e) {
        // Hata durumunda cache'ten veya varsayilan degerlerden don
        $stmt = $db->query("SELECT * FROM doviz_kurlari ORDER BY tarih DESC, created_at DESC LIMIT 20");
        $cached = $stmt->fetchAll();
        
        if (count($cached) >= 3) {
            $sonuc = [];
            foreach ($cached as $k) {
                $key = $k['kaynak_pb'] . '_' . $k['hedef_pb'];
                if (!isset($sonuc[$key])) {
                    $sonuc[$key] = (float)$k['kur'];
                }
            }
            return $sonuc;
        }
        
        // Varsayilan degerler (son bilinen degerler)
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

// TCMB kur guncelleme fonksiyonu (manuel cagirma icin)
function guncelleDovizKurlari() {
    return getDovizKurlari(true);
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
