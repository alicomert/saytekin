<?php
require_once 'includes/header.php';

$pageTitle = 'Tum Liste';

// Filtreler
$filters = [
    'sk' => $_GET['sk'] ?? 'Tumu',
    'tur' => $_GET['tur'] ?? 'Tumu',
    'arama' => $_GET['arama'] ?? ''
];

$hammaddeler = getHammaddeler($filters);
$turler = getTurler();

// Sayfa basligi
$pageTitle = 'Tum Liste (' . count($hammaddeler) . ' kayit)';
?>

<!-- Filtreler -->
<div style="display:flex;gap:12;margin-bottom:20;flex-wrap:wrap;align-items:center;">
    <form method="GET" action="" style="display:flex;gap:12;flex:1;flex-wrap:wrap;align-items:center;">
        <input type="text" name="arama" value="<?php echo htmlspecialchars($filters['arama']); ?>" 
            placeholder="🔍 Hammadde, stok kodu veya tur ara..."
            style="flex:1;min-width:220;background:#141820;border:1px solid #1e2430;border-radius:8;padding:9px 14px;color:#e2e8f0;font-size:13px;outline:none;">
        
        <div style="display:flex;gap:6;">
            <?php foreach (['Tumu' => 'Tumu', 'S' => 'S - Standart', 'K' => 'K - Kapali'] as $val => $label): ?>
            <button type="submit" name="sk" value="<?php echo $val; ?>" 
                style="padding:8px 14px;border-radius:6px;border:1px solid <?php echo $filters['sk'] == $val ? '#3b82f6' : '#1e2430'; ?>;font-size:12px;font-weight:600;cursor:pointer;
                background:<?php echo $filters['sk'] == $val ? '#1d3557' : '#141820'; ?>;
                color:<?php echo $filters['sk'] == $val ? '#60a5fa' : '#64748b'; ?>;">
                <?php echo $label; ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <select name="tur" onchange="this.form.submit()" 
            style="background:#141820;border:1px solid #1e2430;border-radius:8;padding:8px 12px;color:#94a3b8;font-size:12px;outline:none;">
            <option value="Tumu">Tum Turler</option>
            <?php foreach ($turler as $tur): ?>
            <option value="<?php echo $tur['kod']; ?>" <?php echo $filters['tur'] == $tur['kod'] ? 'selected' : ''; ?>>
                <?php echo $tur['ad']; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($hammaddeler)): ?>
<!-- Bos durum -->
<div style="text-align:center;padding:80px 0;color:#334155;">
    <div style="font-size:48;margin-bottom:16;">📦</div>
    <div style="font-size:16;margin-bottom:8;color:#475569;">Henuz hammadde eklenmedi</div>
    <div style="font-size:13;color:#334155;margin-bottom:24;">Sag ustteki "Yeni Hammadde" butonuyla baslayin</div>
    <a href="hammadde-form.php" class="btn-primary">+ Ilk Hammaddeyi Ekle</a>
</div>
<?php else: ?>
<!-- Tablo -->
<div style="background:#141820;border:1px solid #1e2430;border-radius:12;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%;border-collapse:collapse;min-width:1200;">
            <thead>
                <tr style="background:#0d1017;border-bottom:1px solid #1e2430;">
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">S/K</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Stok Kodu</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Urun Kodu</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Tur</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Hammadde Ismi / Tedarikci</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Stok Miktari</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Optimum</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Termin</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">2023 Ort.</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">2024 Ort.</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">2025 Ort.</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Son 12 Ay</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Son 3 Ay</th>
                    <th style="padding:11px 12px;text-align:left;font-size:10;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;">Islem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hammaddeler as $h): 
                    $durum = getStokDurum($h);
                    
                    // Ortalamalar - SQL fonksiyonlarini kullan
                    $ort23 = getTuketimOrtalama($h['id'], 2023);
                    $ort24 = getTuketimOrtalama($h['id'], 2024);
                    $ort25 = getTuketimOrtalama($h['id'], 2025);
                    $s12 = getSon12AyOrtalama($h['id']);
                    $s3 = getSon3AyOrtalama($h['id']);
                    
                    // Termin toplam
                    $terminToplam = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
                    
                    // Stok durum renk
                    $stokRenk = $durum['renk'] ?? '#94a3b8';
                    $stokEtiket = $durum['label'] ?? '';
                ?>
                <tr style="border-bottom:1px solid #1e2430;cursor:default;"
                    onmouseenter="this.style.background='#1a2130'"
                    onmouseleave="this.style.background='transparent'">
                    <td style="padding:11px 12px;">
                        <?php if ($h['sk'] == 'S'): ?>
                        <span style="background:#3b82f622;color:#60a5fa;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">S</span>
                        <?php elseif ($h['sk'] == 'K'): ?>
                        <span style="background:#ef444422;color:#ef4444;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">K</span>
                        <?php else: ?>
                        <span style="background:#eab30822;color:#eab308;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">A</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:11px 12px;color:#94a3b8;"><?php echo $h['stok_kodu'] ?: '-'; ?></td>
                    <td style="padding:11px 12px;color:#64748b;"><?php echo $h['urun_kodu'] ?: '-'; ?></td>
                    <td style="padding:11px 12px;">
                        <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $h['tur_adi'] ?: '-'; ?></span>
                    </td>
                    <td style="padding:11px 12px;">
                        <div style="font-weight:700;color:#f1f5f9;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($h['hammadde_ismi']); ?>">
                            <?php echo $h['hammadde_ismi']; ?>
                        </div>
                    </td>
                    <td style="padding:11px 12px;">
                        <span style="color:<?php echo $stokRenk; ?>;font-weight:700;">
                            <?php echo number_format($h['stok_miktari'], 0, ',', '.'); ?>
                        </span>
                        <?php if ($stokEtiket): ?>
                        <span style="margin-left:4px;font-size:10px;padding:1px 4px;border-radius:3px;font-weight:700;background:<?php echo $stokRenk; ?>22;color:<?php echo $stokRenk; ?>;">
                            <?php echo $stokEtiket; ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:11px 12px;color:#94a3b8;">
                        <?php if ($h['hesaplanan_optimum']): ?>
                            <?php echo number_format($h['hesaplanan_optimum'], 0, ',', '.'); ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:center;">
                        <?php if ($terminToplam > 0): ?>
                        <span style="color:#fbbf24;font-weight:700;"><?php echo $terminToplam; ?> gun</span>
                        <?php else: ?><span style="color:#475569;">-</span><?php endif; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:right;font-family:monospace;color:#60a5fa;">
                        <?php echo $ort23 > 0 ? number_format($ort23, 0, ',', '.') : '-'; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:right;font-family:monospace;color:#a78bfa;">
                        <?php echo $ort24 > 0 ? number_format($ort24, 0, ',', '.') : '-'; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:right;font-family:monospace;color:#34d399;">
                        <?php echo $ort25 > 0 ? number_format($ort25, 0, ',', '.') : '-'; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:right;font-family:monospace;color:#fbbf24;font-weight:700;">
                        <?php echo $s12 > 0 ? number_format($s12, 0, ',', '.') : '-'; ?>
                    </td>
                    <td style="padding:11px 12px;text-align:right;font-family:monospace;color:#f97316;font-weight:700;">
                        <?php echo $s3 > 0 ? number_format($s3, 0, ',', '.') : '-'; ?>
                    </td>
                    <td style="padding:11px 12px;">
                        <div style="display:flex;gap:8;">
                            <a href="hammadde-detay.php?id=<?php echo $h['id']; ?>" 
                                style="width:28;height:28;background:#1e2430;border-radius:6;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:12px;"
                                title="Detay">👁</a>
                            <a href="hammadde-form.php?id=<?php echo $h['id']; ?>" 
                                style="width:28;height:28;background:#1e2430;border-radius:6;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:12px;"
                                title="Duzenle">✏️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>