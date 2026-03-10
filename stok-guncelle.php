<?php
require_once 'includes/header.php';

$pageTitle = 'Stok Guncelleme';

$buYil = date('Y');
$buAy = date('n');
$aylar = [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$ayAd = $aylar[$buAy];

$oncekiAy = $buAy > 1 ? $buAy - 1 : 12;
$oncekiAyYil = $buAy > 1 ? $buYil : $buYil - 1;
$oncekiAyAd = $aylar[$oncekiAy];

$hammaddeler = getHammaddeler(['sk' => 'S']);

usort($hammaddeler, function($a, $b) {
    return strcmp($a['hammadde_ismi'], $b['hammadde_ismi']);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['stok'])) {
        foreach ($_POST['stok'] as $id => $deger) {
            if ($deger !== '') {
                $db = getDB();
                $db->prepare("UPDATE hammaddeler SET stok_miktari = ? WHERE id = ?")
                   ->execute([$deger, $id]);
            }
        }
    }
    
    if (isset($_POST['tuketim'])) {
        foreach ($_POST['tuketim'] as $id => $deger) {
            if ($deger !== '') {
                saveTuketimVerisi($id, $buYil, $buAy, $deger);
            }
        }
    }
    
    setFlashMessage('Stok ve tuketim verileri guncellendi.', 'success');
    header('Location: stok-guncelle.php');
    exit;
}
?>

<div style="margin-bottom:20px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:16px;">
    <div>
        <div style="font-size:18px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">🔄 Stok Guncelleme</div>
        <div style="font-size:13px;color:#64748b;">
            <?php echo count($hammaddeler); ?> standart hammadde · Stok miktari ve <span style="color:#38bdf8;"><?php echo $ayAd . ' ' . $buYil; ?></span> tuketimi guncellenebilir
        </div>
    </div>
    
    <div style="background:#0c1f2e;border:1px solid #38bdf855;border-radius:8px;padding:8px 16px;">
        <span style="color:#38bdf8;font-size:13px;font-weight:700;">Guncelleme ayi: <?php echo $ayAd . ' ' . $buYil; ?></span>
    </div>
</div>

<form method="POST" action="">
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#0f1117;border-bottom:2px solid #1e2430;">
                        <th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">#</th>
                        <th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">HAMMADDE</th>
                        <th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">TUR</th>
                        <th style="text-align:right;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">MEVCUT STOK (kg)</th>
                        <th style="text-align:right;color:#38bdf8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;background:#0c1f2e;">YENI STOK (kg)</th>
                        <th style="text-align:right;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;"><?php echo strtoupper($oncekiAyAd); ?> <?php echo $oncekiAyYil; ?> TUK.</th>
                        <th style="text-align:right;color:#38bdf8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;background:#0c1f2e;"><?php echo strtoupper($ayAd); ?> <?php echo $buYil; ?> TUK. GIR</th>
                        <th style="text-align:right;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">ORT. AYLIK</th>
                        <th style="text-align:right;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">OPTIMUM</th>
                        <th style="text-align:center;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px;white-space:nowrap;">DURUM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hammaddeler as $idx => $h): 
                        $stok = (float)$h['stok_miktari'];
                        $optimum = (float)$h['hesaplanan_optimum'];
                        $durum = getStokDurum($h);
                        $son12 = getSon12AyOrtalama($h['id']);
                        
                        $db = getDB();
                        $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri WHERE hammadde_id = ? AND yil = ? AND ay = ?");
                        $stmt->execute([$h['id'], $oncekiAyYil, $oncekiAy]);
                        $oncekiTuketim = $stmt->fetch()['miktar_kg'] ?? 0;
                        
                        $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri WHERE hammadde_id = ? AND yil = ? AND ay = ?");
                        $stmt->execute([$h['id'], $buYil, $buAy]);
                        $buAyTuketim = $stmt->fetch()['miktar_kg'] ?? null;
                        $buAyDolu = $buAyTuketim !== null;
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;<?php echo $buAyDolu ? '' : 'background:#f59e0b0a;'; ?>transition:background 0.15s;" onmouseover="this.style.background='#1a2130'" onmouseout="this.style.background='<?php echo $buAyDolu ? 'transparent' : '#f59e0b0a'; ?>'">
                        <td style="padding:12px;color:#475569;font-size:11px;"><?php echo $idx + 1; ?></td>
                        <td style="padding:12px;">
                            <div style="font-weight:700;color:#f1f5f9;font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo $h['hammadde_ismi']; ?>">
                                <?php echo $h['hammadde_ismi']; ?>
                            </div>
                            <div style="font-size:11px;color:#64748b;"><?php echo $h['urun_kodu'] ?: '-'; ?></div>
                        </td>
                        <td style="padding:12px;">
                            <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $h['tur_adi'] ?: '-'; ?></span>
                        </td>
                        <td style="padding:12px;text-align:right;font-family:monospace;font-weight:700;color:<?php echo $durum['renk']; ?>;">
                            <?php echo number_format($stok, 0, ',', '.'); ?>
                        </td>
                        <td style="padding:8px;background:#0c1f2e;">
                            <input type="number" name="stok[<?php echo $h['id']; ?>]" 
                                placeholder="<?php echo $stok > 0 ? number_format($stok, 0, ',', '.') : '-'; ?>"
                                style="width:100%;min-width:100px;background:#0f1117;border:1px solid #38bdf855;border-radius:6px;padding:8px 12px;text-align:right;color:#38bdf8;font-family:monospace;font-weight:700;font-size:13px;">
                        </td>
                        <td style="padding:12px;text-align:right;font-family:monospace;color:#64748b;">
                            <?php echo $oncekiTuketim > 0 ? number_format($oncekiTuketim, 0, ',', '.') : '-'; ?>
                        </td>
                        <td style="padding:8px;background:#0c1f2e;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php if ($buAyDolu): ?>
                                <span style="color:#34d399;font-family:monospace;font-weight:700;font-size:11px;">✓ <?php echo number_format($buAyTuketim, 0, ',', '.'); ?></span>
                                <?php endif; ?>
                                <input type="number" name="tuketim[<?php echo $h['id']; ?>]" 
                                    placeholder="<?php echo $buAyDolu ? 'duzenle' : 'gir...'; ?>"
                                    style="width:100%;min-width:100px;background:#0f1117;border:1px solid <?php echo $buAyDolu ? '#34d39955' : '#f59e0b55'; ?>;border-radius:6px;padding:8px 12px;text-align:right;font-family:monospace;font-weight:700;font-size:13px;color:<?php echo $buAyDolu ? '#34d399' : '#fbbf24'; ?>;">
                            </div>
                        </td>
                        <td style="padding:12px;text-align:right;font-family:monospace;color:#94a3b8;">
                            <?php echo $son12 > 0 ? number_format($son12, 0, ',', '.') : '-'; ?>
                        </td>
                        <td style="padding:12px;text-align:right;font-family:monospace;font-size:12px;">
                            <?php if ($optimum > 0): ?>
                                <span style="color:#64748b;"><?php echo number_format($optimum, 0, ',', '.'); ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;background:<?php echo $durum['renk']; ?>22;color:<?php echo $durum['renk']; ?>">
                                <?php echo $durum['label'] ?: 'RAHAT'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Alt bilgi -->
        <div style="background:#0f1117;border-top:1px solid #1e2430;padding:16px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
            <div style="font-size:11px;color:#64748b;">
                <span style="color:#fbbf24;font-weight:700;">■</span> Sari arka plan = <?php echo $ayAd . ' ' . $buYil; ?> tuketimi henüz girilmemis
            </div>
            <div style="font-size:11px;color:#64748b;">
                <span style="color:#34d399;font-weight:700;">✓</span> Yesil = tuketim girilmis
            </div>
            <div style="margin-left:auto;">
                <button type="submit" class="btn-primary">Tum Degisiklikleri Kaydet</button>
            </div>
        </div>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>