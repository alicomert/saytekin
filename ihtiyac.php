<?php
require_once 'includes/header.php';

$pageTitle = 'Ihtiyac Listesi';

// Tum hammaddeleri al ve kritik olanlari filtrele
$hammaddeler = getHammaddeler();
$kritikListe = [];

foreach ($hammaddeler as $h) {
    $durum = getStokDurum($h);
    if ($durum['kritik'] || ($durum['oran'] !== null && $durum['oran'] < 2)) {
        $h['durum'] = $durum;
        $kritikListe[] = $h;
    }
}

// Siralama: En kritikler once
usort($kritikListe, function($a, $b) {
    return $a['durum']['oran'] <=> $b['durum']['oran'];
});
?>

<div style="margin-bottom:20px;">
    <div style="font-size:18px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">⚠️ İhtiyaç Listesi</div>
    <div style="font-size:13px;color:#64748b;">Kritik stok seviyesindeki hammaddeler - Toplam <?php echo count($kritikListe); ?> kayıt</div>
</div>

<?php if (empty($kritikListe)): ?>
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:48px;text-align:center;">
    <div style="font-size:48;margin-bottom:16px;">✅</div>
    <div style="font-size:16px;color:#34d399;font-weight:700;margin-bottom:8px;">Tum Hammaddeler Normal Seviyede</div>
    <div style="font-size:13px;color:#64748b;">Su an icin acil siparis gerektiren hammadde bulunmuyor.</div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($kritikListe as $h): 
        $durum = $h['durum'];
        $son12 = getSon12AyOrtalama($h['id']);
        $gunluk = $son12 / 30;
        $termin = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
        $ihtiyac = ceil($gunluk * $termin * 1.5);
        
        $borderColor = $durum['oran'] < 0.5 ? '#ef444455' : ($durum['oran'] < 1 ? '#f9731655' : '#eab30855');
        $bgColor = $durum['oran'] < 0.5 ? '#ef44440a' : ($durum['oran'] < 1 ? '#f973160a' : '#eab3080a');
    ?>
    <div style="background:#141820;border:1px solid <?php echo $borderColor; ?>;border-radius:12px;padding:20px;background-color:<?php echo $bgColor; ?>;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
            <div style="flex:1;min-width:280px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <?php if ($h['sk'] == 'S'): ?>
                    <span style="background:#3b82f622;color:#60a5fa;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">S</span>
                    <?php elseif ($h['sk'] == 'K'): ?>
                    <span style="background:#ef444422;color:#ef4444;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">K</span>
                    <?php else: ?>
                    <span style="background:#eab30822;color:#eab308;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">A</span>
                    <?php endif; ?>
                    <span style="color:#64748b;font-size:12px;font-family:monospace;"><?php echo $h['stok_kodu'] ?: '-'; ?></span>
                    <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $h['tur_adi']; ?></span>
                </div>
                <div style="font-size:16px;font-weight:700;color:#f1f5f9;margin-bottom:4px;"><?php echo $h['hammadde_ismi']; ?></div>
                <?php if ($h['tedarikci']): ?>
                <div style="font-size:12px;color:#64748b;">🏢 <?php echo $h['tedarikci']; ?></div>
                <?php endif; ?>
            </div>
            
            <div style="display:flex;flex-wrap:wrap;gap:24px;">
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.05em;">Mevcut Stok</div>
                    <div style="font-size:20px;font-weight:700;color:<?php echo $durum['renk']; ?>">
                        <?php echo number_format($h['stok_miktari'], 0, ',', '.'); ?>
                        <span style="font-size:12px;font-weight:400;color:#64748b;">kg</span>
                    </div>
                </div>
                
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.05em;">Kalan Gun</div>
                    <div style="font-size:20px;font-weight:700;color:<?php echo $durum['renk']; ?>">
                        <?php echo $durum['kalan_gun'] !== null ? $durum['kalan_gun'] : '-'; ?>
                        <span style="font-size:12px;font-weight:400;color:#64748b;">gun</span>
                    </div>
                </div>
                
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.05em;">Termin</div>
                    <div style="font-size:20px;font-weight:700;color:#fbbf24;">
                        <?php echo $termin; ?>
                        <span style="font-size:12px;font-weight:400;color:#64748b;">gun</span>
                    </div>
                </div>
                
                <div style="text-align:center;">
                    <div style="font-size:10px;color:#64748b;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.05em;">Onerilen Siparis</div>
                    <div style="font-size:20px;font-weight:700;color:#60a5fa;">
                        <?php echo number_format($ihtiyac, 0, ',', '.'); ?>
                        <span style="font-size:12px;font-weight:400;color:#64748b;">kg</span>
                    </div>
                </div>
                
                <div style="display:flex;align-items:center;">
                    <span style="padding:6px 12px;border-radius:6px;font-size:12px;font-weight:700;
                        <?php echo $durum['oran'] < 0.5 ? 'background:#ef4444;color:#fff;' : ($durum['oran'] < 1 ? 'background:#f97316;color:#fff;' : 'background:#eab308;color:#0f1117;'); ?>">
                        <?php echo $durum['label']; ?>
                    </span>
                </div>
            </div>
            
            <div style="display:flex;gap:8px;">
                <a href="hammadde-detay.php?id=<?php echo $h['id']; ?>" 
                    style="width:32px;height:32px;background:#1e2430;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:12px;"
                    title="Detay">👁</a>
                <a href="hammadde-form.php?id=<?php echo $h['id']; ?>" 
                    style="width:32px;height:32px;background:#1e2430;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;font-size:12px;"
                    title="Duzenle">✏️</a>
            </div>
        </div>
        
        <!-- Progress bar -->
        <div style="margin-top:16px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:4px;">
                <span>Stok/Termin Orani: %<?php echo round(($durum['oran'] ?? 0) * 100); ?></span>
                <span>Optimum: <?php echo number_format($h['hesaplanan_optimum'], 0, ',', '.'); ?> kg</span>
            </div>
            <div style="height:6px;background:#0f1117;border-radius:3px;overflow:hidden;">
                <div style="height:100%;border-radius:3px;transition:width 0.5s;width:<?php echo min(100, ($durum['oran'] ?? 0) * 50); ?>%;background-color:<?php echo $durum['renk']; ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>