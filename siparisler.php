<?php
require_once 'includes/header.php';

$pageTitle = 'Siparisler';

$durum = $_GET['durum'] ?? 'bekleyen';
$siparisler = getSiparisler($durum);
$kurlar = getDovizKurlari();

$pageTitle = $durum === 'bekleyen' ? 'Bekleyen Siparisler' : 'Tamamlanan Siparisler';
?>

<div style="margin-bottom:20px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;">
    <div>
        <div style="font-size:18px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">🛒 Siparisler</div>
        <div style="font-size:13px;color:#64748b;"><?php echo count($siparisler); ?> siparis listeleniyor</div>
    </div>
    
    <div style="display:flex;gap:8px;">
        <a href="?durum=bekleyen" 
            style="padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;
            <?php echo $durum === 'bekleyen' 
                ? 'background:#0d2018;border:1px solid #34d399;color:#34d399;' 
                : 'background:#141820;border:1px solid #1e2430;color:#64748b;'; ?>">
            Bekleyen (<?php echo countAktifSiparisler(); ?>)
        </a>
        <a href="?durum=tamamlanan" 
            style="padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;
            <?php echo $durum === 'tamamlanan' 
                ? 'background:#1d3557;border:1px solid #3b82f6;color:#60a5fa;' 
                : 'background:#141820;border:1px solid #1e2430;color:#64748b;'; ?>">
            Tamamlanan
        </a>
    </div>
</div>

<?php if (empty($siparisler)): ?>
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:48px;text-align:center;">
    <div style="font-size:48;margin-bottom:16px;">📭</div>
    <div style="font-size:16px;color:#64748b;font-weight:700;margin-bottom:8px;">
        <?php echo $durum === 'bekleyen' ? 'Bekleyen Siparis Yok' : 'Tamamlanan Siparis Yok'; ?>
    </div>
    <div style="font-size:13px;color:#475569;">
        <?php echo $durum === 'bekleyen' ? 'Su an icin bekleyen siparis bulunmuyor.' : 'Henüz teslim alinan siparis kaydi yok.'; ?>
    </div>
</div>
<?php else: ?>
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hammadde</th>
                    <th>Tur</th>
                    <th>Siparis No</th>
                    <th>Tarih</th>
                    <th>Miktar (kg)</th>
                    <th>Tedarikci</th>
                    <th>Birim Varis Mal.</th>
                    <th>Durum</th>
                    <th>Islem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siparisler as $s): 
                    $varisMaliyet = hesaplaVarisMaliyeti($s, $kurlar);
                    $miktar = (float)$s['miktar_kg'];
                    $birimMiktar = $s['fiyat_birimi'] === 'ton' ? $miktar / 1000 : $miktar;
                    $toplamBedel = $varisMaliyet * $birimMiktar;
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:#60a5fa;"><?php echo $s['hammadde_ismi']; ?></div>
                        <div style="font-size:11px;color:#64748b;"><?php echo $s['stok_kodu'] ?: '-'; ?></div>
                    </td>
                    <td>
                        <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $s['tur_kodu']; ?></span>
                    </td>
                    <td style="font-weight:700;color:#fbbf24;"><?php echo $s['siparis_no'] ?: '-'; ?></td>
                    <td style="color:#94a3b8;"><?php echo date('d.m.Y', strtotime($s['tarih'])); ?></td>
                    <td style="font-weight:700;color:#34d399;"><?php echo number_format($s['miktar_kg'], 0, ',', '.'); ?></td>
                    <td style="color:#94a3b8;"><?php echo $s['tedarikci'] ?: '-'; ?></td>
                    <td>
                        <?php if ($varisMaliyet > 0): ?>
                        <div style="font-weight:700;color:#a78bfa;">
                            <?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $s['para_birimi_kodu']; ?>/<?php echo $s['fiyat_birimi']; ?>
                        </div>
                        <?php if ($toplamBedel > 0): ?>
                        <div style="font-size:11px;color:#64748b;">
                            ≈ <?php echo number_format($toplamBedel, 0, ',', '.'); ?> <?php echo $s['para_birimi_kodu']; ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($durum === 'bekleyen'): ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#0d2018;color:#34d399;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid #34d39955;">
                            Bekliyor
                        </span>
                        <?php else: ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#1d3557;color:#60a5fa;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid #3b82f655;">
                            Teslim
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($durum === 'bekleyen'): ?>
                        <div style="display:flex;gap:6px;">
                            <button onclick="tamamlaSiparis(<?php echo $s['id']; ?>)" 
                                style="padding:6px 12px;background:#1d3557;border:1px solid #3b82f655;border-radius:6px;color:#60a5fa;font-size:11px;font-weight:700;cursor:pointer;">
                                Tamamla
                            </button>
                            <button onclick="iptalSiparis(<?php echo $s['id']; ?>)" 
                                style="padding:6px 12px;background:#2d1a1a;border:1px solid #ef444455;border-radius:6px;color:#ef4444;font-size:11px;font-weight:700;cursor:pointer;">
                                Iptal
                            </button>
                        </div>
                        <?php else: ?>
                        <button onclick="iptalSiparis(<?php echo $s['id']; ?>)" 
                            style="padding:6px 12px;background:#1e2430;border:1px solid #1e2430;border-radius:6px;color:#64748b;font-size:11px;cursor:pointer;">
                            Temizle
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function tamamlaSiparis(id) {
    if (confirm('Bu siparisi teslim alindi olarak isaretlemek istiyor musunuz?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=tamamla'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        });
    }
}

function iptalSiparis(id) {
    if (confirm('Bu siparisi iptal etmek istiyor musunuz?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=iptal'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>