<?php
require_once 'includes/header.php';

// Yetki kontrolü
checkPermission('view');

$pageTitle = 'İstatistikler';

$db = getDB();

// Yılları dinamik olarak al
$yillar = getTuketimYillari();
$YILLAR = !empty($yillar) ? array_map('intval', $yillar) : [date('Y')];

// Renkler
$YIL_RENKLER = [
    2023 => ['border' => '#3b82f6', 'text' => '#60a5fa'],
    2024 => ['border' => '#8b5cf6', 'text' => '#a78bfa'],
    2025 => ['border' => '#10b981', 'text' => '#34d399'],
    2026 => ['border' => '#f59e0b', 'text' => '#fbbf24'],
];

// Standart (S) hammaddeleri al
$hammaddeler = $db->query("SELECT * FROM hammaddeler WHERE sk = 'S' AND is_active = 1 ORDER BY hammadde_ismi ASC")->fetchAll();

// Tür listesi
$turler = $db->query("SELECT kod, ad FROM hammadde_turleri WHERE is_active = 1 ORDER BY sira ASC")->fetchAll();

// Tüketim verilerini al
$tuketimVerileri = [];
foreach ($hammaddeler as $h) {
    $veriler = $db->query("SELECT yil, ay, miktar_kg FROM tuketim_verileri WHERE hammadde_id = {$h['id']}")->fetchAll();
    $tuketimVerileri[$h['id']] = [];
    foreach ($veriler as $v) {
        $key = $v['yil'] . '-' . $v['ay'];
        $tuketimVerileri[$h['id']][$key] = $v['miktar_kg'];
    }
}

// Aylar
$AYLAR = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];

// Ortalama hesaplama fonksiyonları
function hesaplaOrt($tuketimData, $yil) {
    global $AYLAR;
    $toplam = 0;
    $sayac = 0;
    foreach ($AYLAR as $index => $ay) {
        $key = $yil . '-' . ($index + 1);
        if (isset($tuketimData[$key]) && $tuketimData[$key] > 0) {
            $toplam += $tuketimData[$key];
            $sayac++;
        }
    }
    return $sayac > 0 ? $toplam / $sayac : 0;
}

function son12AyOrt($tuketimData) {
    global $AYLAR;
    $veriler = [];
    
    // Son 12 ayı topla
    $yil = date('Y');
    $ay = date('n');
    
    for ($i = 0; $i < 12; $i++) {
        $currentAy = $ay - $i;
        $currentYil = $yil;
        if ($currentAy <= 0) {
            $currentAy += 12;
            $currentYil--;
        }
        $key = $currentYil . '-' . $currentAy;
        if (isset($tuketimData[$key]) && $tuketimData[$key] > 0) {
            $veriler[] = $tuketimData[$key];
        }
    }
    
    return count($veriler) > 0 ? array_sum($veriler) / count($veriler) : 0;
}

function son3AyOrt($tuketimData) {
    return son12AyOrt($tuketimData) * 0.25; // Basit yaklaşım
}

// Format fonksiyonu
function N($v, $d = 0) {
    return is_null($v) || $v === false || $v === '' ? '—' : number_format($v, $d, ',', '.');
}

// İstatistik hesaplamaları
$toplamStok = array_sum(array_column($hammaddeler, 'stok_miktari'));
$ihtiyacSay = count(array_filter($hammaddeler, function($m) {
    return $m['stok_miktari'] > 0 && $m['hesaplanan_optimum'] > 0 && $m['stok_miktari'] < ($m['hesaplanan_optimum'] / 2);
}));

// Yıl bazlı toplamlar
$yilToplam = [];
$yilOrtToplamlar = [];
foreach ($YILLAR as $yil) {
    $yilToplam[$yil] = 0;
    $yilOrtToplamlar[$yil] = 0;
    $toplamOrt = 0;
    $sayac = 0;
    
    foreach ($hammaddeler as $h) {
        $ort = hesaplaOrt($tuketimVerileri[$h['id']], $yil);
        if ($ort > 0) {
            $toplamOrt += $ort;
            $sayac++;
        }
        
        // Yıllık toplam
        foreach ($AYLAR as $index => $ay) {
            $key = $yil . '-' . ($index + 1);
            if (isset($tuketimVerileri[$h['id']][$key])) {
                $yilToplam[$yil] += $tuketimVerileri[$h['id']][$key];
            }
        }
    }
    
    $yilOrtToplamlar[$yil] = $sayac > 0 ? $toplamOrt : 0;
}

// Tür bazlı istatistik
$turStats = [];
foreach ($turler as $tur) {
    $turHammaddeler = array_filter($hammaddeler, function($m) use ($tur) {
        return $m['tur_kodu'] === $tur['kod'];
    });
    
    if (count($turHammaddeler) > 0) {
        // Tüm yıllar için ortalama hesapla
        $yilOrtalamalari = [];
        foreach ($YILLAR as $yil) {
            $yilOrtalamalari[$yil] = 0;
            foreach ($turHammaddeler as $h) {
                $yilOrtalamalari[$yil] += hesaplaOrt($tuketimVerileri[$h['id']], $yil);
            }
        }
        
        $s12 = 0;
        foreach ($turHammaddeler as $h) {
            $s12 += son12AyOrt($tuketimVerileri[$h['id']]);
        }
        
        $turStats[] = array_merge([
            'tur' => $tur['kod'],
            'sayi' => count($turHammaddeler),
            's12' => $s12
        ], $yilOrtalamalari);
    }
}

// Son yıla göre sırala
$sonYil = end($YILLAR);
usort($turStats, function($a, $b) use ($sonYil) {
    $valA = $a[$sonYil] ?? 0;
    $valB = $b[$sonYil] ?? 0;
    return $valB <=> $valA;
});

// Hammadde bazlı istatistik
$hammaddeStats = [];
foreach ($hammaddeler as $h) {
    // Tüm yıllar için ortalama hesapla
    $yilOrtalamalari = [];
    foreach ($YILLAR as $yil) {
        $key = 'o' . substr($yil, -2);
        $yilOrtalamalari[$key] = hesaplaOrt($tuketimVerileri[$h['id']], $yil);
    }
    
    $s12 = son12AyOrt($tuketimVerileri[$h['id']]);
    $s3 = son3AyOrt($tuketimVerileri[$h['id']]);
    
    // Trend hesapla (son iki yıl arası)
    $sonIkiYil = array_slice($YILLAR, -2);
    if (count($sonIkiYil) >= 2) {
        $oncekiYilKey = 'o' . substr($sonIkiYil[0], -2);
        $sonYilKey = 'o' . substr($sonIkiYil[1], -2);
        $trend = $yilOrtalamalari[$oncekiYilKey] > 0 ? 
            (($yilOrtalamalari[$sonYilKey] - $yilOrtalamalari[$oncekiYilKey]) / $yilOrtalamalari[$oncekiYilKey] * 100) : null;
    } else {
        $trend = null;
    }
    
    $hammaddeStats[] = array_merge([
        'id' => $h['id'],
        'hammadde_ismi' => $h['hammadde_ismi'],
        'tur_kodu' => $h['tur_kodu'],
        'tedarikci' => $h['tedarikci'],
        's12' => $s12,
        's3' => $s3,
        'trend' => $trend
    ], $yilOrtalamalari);
}

// Son yıl ortalamasına göre sırala
$sonYilKey = 'o' . substr(end($YILLAR), -2);
usort($hammaddeStats, function($a, $b) use ($sonYilKey) {
    return $b[$sonYilKey] <=> $a[$sonYilKey];
});

$maxSonYil = !empty($hammaddeStats) ? max(array_column($hammaddeStats, $sonYilKey)) : 0;

?>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <div style="margin-bottom:20px;">
        <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">📊 İstatistikler</h2>
        <p style="color:#475569;font-size:13px;">Yıllara ve hammaddelere göre tüketim analizleri</p>
    </div>

    <!-- Genel özet kartlar -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
        <?php
        $kartlar = [
            ['label' => 'Toplam Hammadde (S)', 'val' => count($hammaddeler) . ' kalem', 'renk' => '#3b82f6', 'bg' => '#1d3557', 'icon' => '📦'],
            ['label' => 'Toplam Stok', 'val' => N($toplamStok) . ' kg', 'renk' => '#10b981', 'bg' => '#0d2018', 'icon' => '🏭'],
            ['label' => 'İhtiyaç Durumunda', 'val' => $ihtiyacSay . ' kalem', 'renk' => '#f59e0b', 'bg' => '#2a1f0a', 'icon' => '⚠️'],
            ['label' => end($YILLAR) . ' Aylık Ort. Toplam', 'val' => N($yilOrtToplamlar[end($YILLAR)] ?? 0), 'renk' => '#8b5cf6', 'bg' => '#1e1535', 'icon' => '📈'],
        ];
        foreach ($kartlar as $k):
        ?>
        <div style="background:<?php echo $k['bg']; ?>;border:1px solid <?php echo $k['renk']; ?>44;border-radius:12px;padding:18px;border-top:3px solid <?php echo $k['renk']; ?>">
            <div style="font-size:22px;margin-bottom:8px;"><?php echo $k['icon']; ?></div>
            <div style="font-size:20px;font-weight:700;color:<?php echo $k['renk']; ?>"><?php echo $k['val']; ?></div>
            <div style="font-size:11px;color:#64748b;margin-top:4px;"><?php echo $k['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Yıllara göre toplam tüketim -->
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:20px;margin-bottom:20px;">
        <div style="font-size:12px;font-weight:700;letter-spacing:0.1em;color:#64748b;margin-bottom:16px;text-transform:uppercase;">Yıllara Göre Toplam Tüketim</div>
        <div style="display:grid;grid-template-columns:repeat(<?php echo min(count($YILLAR), 4); ?>,1fr);gap:16px;">
            <?php
            $maxOrt = max($yilOrtToplamlar) ?: 1;
            foreach ($YILLAR as $index => $yil):
                $renk = $YIL_RENKLER[$yil] ?? ['border' => '#64748b', 'text' => '#94a3b8'];
                $oncekiYil = $YILLAR[$index - 1] ?? null;
                $oncekiOrt = $oncekiYil ? ($yilOrtToplamlar[$oncekiYil] ?? 0) : 0;
                $buOrt = $yilOrtToplamlar[$yil] ?? 0;
                $farkYuzde = $oncekiOrt > 0 ? round(($buOrt - $oncekiOrt) / $oncekiOrt * 100) : null;
                $barYuzde = $maxOrt > 0 ? round($buOrt / $maxOrt * 100) : 0;
            ?>
            <div style="background:#0f1117;border-radius:10px;padding:16px;border:1px solid <?php echo $renk['border']; ?>33">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <div style="font-size:13px;font-weight:700;color:<?php echo $renk['text']; ?>"><?php echo $yil; ?></div>
                    <?php if ($farkYuzde !== null): ?>
                    <span style="font-size:11px;font-weight:700;color:<?php echo $farkYuzde > 0 ? '#34d399' : ($farkYuzde < 0 ? '#ef4444' : '#64748b'); ?>;background:<?php echo $farkYuzde > 0 ? '#10b98122' : ($farkYuzde < 0 ? '#ef444422' : '#1e2430'); ?>;padding:2px 7px;border-radius:4px;">
                        <?php echo $farkYuzde > 0 ? '▲ ' : ($farkYuzde < 0 ? '▼ ' : ''); echo abs($farkYuzde); ?>%
                    </span>
                    <?php endif; ?>
                </div>
                <div style="font-size:20px;font-weight:700;color:#f1f5f9;margin-bottom:2px;"><?php echo N($yilToplam[$yil]); ?></div>
                <div style="font-size:11px;color:#475569;margin-bottom:10px;">
                    Aylık ort: <strong style="color:<?php echo $renk['text']; ?>"><?php echo N($buOrt); ?></strong>
                    <?php if ($farkYuzde !== null): ?>
                    <span style="color:#334155;margin-left:5px;font-size:10px;">vs <?php echo N($oncekiOrt); ?></span>
                    <?php endif; ?>
                </div>
                <div style="height:6px;background:#1e2430;border-radius:3px;">
                    <div style="width:<?php echo $barYuzde; ?>%;height:100%;background:<?php echo $renk['border']; ?>;border-radius:3px;transition:width 0.6s"></div>
                </div>
                <div style="font-size:10px;color:#475569;margin-top:4px;">
                    <?php echo $farkYuzde === null ? 'İlk yıl' : ($farkYuzde === 0 ? 'Önceki yıl ile aynı' : $oncekiYil . ' aylık ort. ile karşılaştırma'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tür bazlı istatistik -->
    <?php if (!empty($turStats)): ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:20px;margin-bottom:20px;">
        <div style="font-size:12px;font-weight:700;letter-spacing:0.1em;color:#64748b;margin-bottom:16px;text-transform:uppercase;">Tür Bazlı Aylık Ortalama Tüketim</div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #1e2430;">
                        <th style="padding:9px 14px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;">Tür</th>
                        <th style="padding:9px 14px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;">Kalem</th>
                        <?php 
                        // Son 2 yılı göster
                        $sonIkiYil = array_slice($YILLAR, -2);
                        foreach ($sonIkiYil as $yil): 
                        ?>
                        <th style="padding:9px 14px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;"><?php echo $yil; ?> Ort./Ay</th>
                        <?php endforeach; ?>
                        <th style="padding:9px 14px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;">Son 12 Ay Ort.</th>
                        <th style="padding:9px 14px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turStats as $t): 
                        // Trend hesapla (son 2 yıl arası)
                        $trend = null;
                        if (count($sonIkiYil) >= 2) {
                            $oncekiYil = $sonIkiYil[0];
                            $sonYil = $sonIkiYil[1];
                            $oncekiDeger = $t[$oncekiYil] ?? 0;
                            $sonDeger = $t[$sonYil] ?? 0;
                            $trend = $oncekiDeger > 0 ? (($sonDeger - $oncekiDeger) / $oncekiDeger * 100) : null;
                        }
                        $yilRenkler = [];
                        if (isset($sonIkiYil[0])) $yilRenkler[$sonIkiYil[0]] = '#a78bfa';
                        if (isset($sonIkiYil[1])) $yilRenkler[$sonIkiYil[1]] = '#34d399';
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;"
                        onMouseEnter="this.style.background='#1a2130'"
                        onMouseLeave="this.style.background='transparent'">
                        <td style="padding:11px 14px;"><span style="background:#1e2430;padding:3px 10px;border-radius:4px;font-size:12px;color:#94a3b8;font-weight:700;"><?php echo htmlspecialchars($t['tur']); ?></span></td>
                        <td style="padding:11px 14px;color:#64748b;font-size:12px;"><?php echo $t['sayi']; ?></td>
                        <?php foreach ($sonIkiYil as $yil): 
                            $yilDeger = $t[$yil] ?? 0;
                            $yilRenk = $yilRenkler[$yil] ?? '#94a3b8';
                        ?>
                        <td style="padding:11px 14px;color:<?php echo $yilRenk; ?>;font-size:13px;font-family:monospace;font-weight:600;"><?php echo N($yilDeger); ?></td>
                        <?php endforeach; ?>
                        <td style="padding:11px 14px;color:#fbbf24;font-size:13px;font-family:monospace;font-weight:600;"><?php echo N($t['s12']); ?></td>
                        <td style="padding:11px 14px;">
                            <?php if ($trend !== null): ?>
                            <span style="color:<?php echo $trend > 0 ? '#34d399' : '#f87171'; ?>;font-weight:700;font-size:12px;">
                                <?php echo $trend > 0 ? '▲' : '▼'; ?> %<?php echo abs(round($trend, 1)); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hammadde bazlı istatistikler -->
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:20px;">
        <div style="font-size:12px;font-weight:700;letter-spacing:0.1em;color:#64748b;margin-bottom:16px;text-transform:uppercase;">Hammadde Bazlı Tüketim İstatistikleri (<?php echo end($YILLAR); ?> tüketimine göre sıralı)</div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:900px;">
                <thead>
                    <tr style="border-bottom:2px solid #1e2430;background:#0d1017;">
                        <th style="padding:10px 12px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Hammadde</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Tür</th>
                        <?php foreach ($YILLAR as $yil): ?>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;"><?php echo $yil; ?> Ort.</th>
                        <?php endforeach; ?>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Son 12 Ay</th>
                        <th style="padding:10px 12px;text-align:right;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Son 3 Ay</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Trend</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Gösterge</th>
                    <th style="padding:10px 12px;text-align:center;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hammaddeStats as $m):
                        $trendRenk = is_null($m['trend']) ? '#64748b' : ($m['trend'] > 10 ? '#34d399' : ($m['trend'] < -10 ? '#f87171' : '#fbbf24'));
                        $barYuzde = $maxSonYil > 0 ? round($m[$sonYilKey] / $maxSonYil * 100) : 0;
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;"
                        onMouseEnter="this.style.background='#1a2130'"
                        onMouseLeave="this.style.background='transparent'">
                        <td style="padding:10px 12px;max-width:200px;">
                            <div style="font-weight:600;color:#f1f5f9;font-size:12px;"><?php echo htmlspecialchars($m['hammadde_ismi']); ?></div>
                            <?php if ($m['tedarikci']): ?>
                            <div style="font-size:10px;color:#475569;">🏭 <?php echo htmlspecialchars($m['tedarikci']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;"><span style="background:#1e2430;padding:2px 7px;border-radius:3px;font-size:10px;color:#94a3b8;"><?php echo $m['tur_kodu']; ?></span></td>
                        <?php foreach ($YILLAR as $yil):
                            $deger = $m['o' . substr($yil, -2)];
                            $renk = $YIL_RENKLER[$yil]['text'] ?? '#94a3b8';
                        ?>
                        <td style="padding:10px 12px;color:<?php echo $renk; ?>;font-size:12px;font-family:monospace;text-align:right;<?php echo $yil == end($YILLAR) ? 'font-weight:700;' : ''; ?>"><?php echo N($deger); ?></td>
                        <?php endforeach; ?>
                        <td style="padding:10px 12px;color:#fbbf24;font-size:12px;font-family:monospace;text-align:right;"><?php echo $m['s12'] > 0 ? N($m['s12']) : '—'; ?></td>
                        <td style="padding:10px 12px;color:#fb923c;font-size:12px;font-family:monospace;text-align:right;"><?php echo $m['s3'] > 0 ? N($m['s3']) : '—'; ?></td>
                        <td style="padding:10px 12px;">
                            <?php if (!is_null($m['trend'])): ?>
                            <span style="color:<?php echo $trendRenk; ?>;font-weight:700;font-size:11px;">
                                <?php echo $m['trend'] > 0 ? '▲' : '▼'; ?> %<?php echo abs(round($m['trend'], 1)); ?>
                            </span>
                            <?php else: ?>
                            <span style="color:#334155;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;min-width:100px;">
                            <div style="height:6px;background:#1e2430;border-radius:3px;">
                                <div style="width:<?php echo $barYuzde; ?>%;height:100%;background:linear-gradient(90deg,#3b82f6,#8b5cf6);border-radius:3px;"></div>
                            </div>
                        </td>
                        <td style="padding:10px 12px;text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <a href="hammadde-detay.php?id=<?php echo $m['id']; ?>" 
                                    style="padding:4px 8px;background:#1e2430;border:1px solid #3b82f655;border-radius:5px;color:#60a5fa;font-size:11px;font-weight:600;text-decoration:none;"
                                    title="Detay">👁</a>
                                <a href="hammadde-form.php?id=<?php echo $m['id']; ?>" 
                                    style="padding:4px 8px;background:#1e2430;border:1px solid #fbbf2466;border-radius:5px;color:#fbbf24;font-size:11px;font-weight:600;text-decoration:none;"
                                    title="Düzenle">✏️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
