<?php
require_once 'includes/header.php';

$pageTitle = 'Siparişler';

$sipTab = $_GET['tab'] ?? 'aktif';

$db = getDB();
$siparisler = [];

if ($sipTab === 'aktif') {
    $siparisler = $db->query("SELECT s.*, h.hammadde_ismi, h.stok_kodu, h.tur_kodu, h.tedarikci, h.para_birimi_kodu, h.fiyat_birimi, h.birim_fiyat, h.maliyet_tipi, h.maliyet_deger, h.teslimat_sekli_kodu
        FROM siparisler s
        JOIN hammaddeler h ON s.hammadde_id = h.id
        WHERE s.is_active = 1 AND s.geldi = 0
        ORDER BY s.tarih DESC")
        ->fetchAll();
} else {
    $siparisler = $db->query("SELECT s.*, h.hammadde_ismi, h.stok_kodu, h.tur_kodu, h.tedarikci, h.para_birimi_kodu, h.fiyat_birimi, h.birim_fiyat, h.maliyet_tipi, h.maliyet_deger, h.teslimat_sekli_kodu
        FROM siparisler s
        JOIN hammaddeler h ON s.hammadde_id = h.id
        WHERE s.is_active = 1 AND s.geldi = 1
        ORDER BY s.tarih DESC")
        ->fetchAll();
}

$kurlar = getDovizKurlari();
$toplamAktif = $db->query("SELECT COUNT(*) as c FROM siparisler WHERE is_active = 1 AND geldi = 0")->fetch()['c'] ?? 0;
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8;">
        <div>
            <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">🛒 Siparişler</h2>
            <p style="color:#475569;font-size:13px;"><?php echo count($siparisler); ?> sipariş listeleniyor</p>
        </div>
        <div style="display:flex;gap:6px;">
            <?php 
            $tabs = [
                ['k' => 'aktif', 'l' => '⏳ Bekleyen (' . $toplamAktif . ')', 'renk' => '#34d399', 'bg' => '#0d2018'],
                ['k' => 'kapali', 'l' => '📦 Teslim Alınan', 'renk' => '#60a5fa', 'bg' => '#1d3557']
            ];
            foreach ($tabs as $t):
            ?>
            <a href="?tab=<?php echo $t['k']; ?>" style="padding:7px 14px;border-radius:7px;border:1px solid;cursor:pointer;font-size:12px;font-weight:700;text-decoration:none;
                border-color:<?php echo $sipTab===$t['k']?$t['renk']:'#1e2430'; ?>;
                background:<?php echo $sipTab===$t['k']?$t['bg']:'#141820'; ?>;
                color:<?php echo $sipTab===$t['k']?$t['renk']:'#64748b'; ?>;">
                <?php echo $t['l']; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($siparisler)): ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:48px;text-align:center;">
        <div style="font-size:48px;margin-bottom:16px;">📭</div>
        <div style="font-size:16px;color:#64748b;font-weight:700;margin-bottom:8px;">
            <?php echo $sipTab === 'aktif' ? 'Bekleyen Sipariş Yok' : 'Teslim Alınan Sipariş Yok'; ?>
        </div>
        <div style="font-size:13px;color:#475569;">
            <?php echo $sipTab === 'aktif' ? 'Su an için bekleyen sipariş bulunmuyor.' : 'Henüz teslim alınan sipariş kaydı yok.'; ?>
        </div>
    </div>
    <?php else: ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                        <?php 
                        $basliklar = ["Hammadde","Tür","Sipariş No","Sipariş Tarihi","Miktar (kg)","Tedarikçi","Birim Varış Mal.","Durum","İşlem"];
                        foreach ($basliklar as $h): ?>
                        <th style="padding:10px 13px;text-align:left;color:#475569;font-weight:700;white-space:nowrap;font-size:10px;letter-spacing:0.05em;"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siparisler as $s): 
                        $varisMaliyet = hesaplaVarisMaliyeti($s, $kurlar);
                        $miktar = (float)$s['miktar_kg'];
                        $birimMiktar = $s['fiyat_birimi'] === 'ton' ? $miktar / 1000 : $miktar;
                        $toplamBedel = $varisMaliyet * $birimMiktar;
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;"
                        onMouseEnter="this.style.background='#1a2130'"
                        onMouseLeave="this.style.background='transparent'">
                        
                        <!-- Hammadde -->
                        <td style="padding:12px 13px;">
                            <div style="font-weight:700;color:#60a5fa;font-size:13px;cursor:pointer;text-decoration:underline;text-decoration-style:dotted;text-underline-offset:3;" 
                                onclick="window.location='hammadde-detay.php?id=<?php echo $s['hammadde_id']; ?>'">
                                <?php echo htmlspecialchars($s['hammadde_ismi']); ?>
                            </div>
                            <div style="font-size:10px;color:#475569;margin-top:2px;"><?php echo $s['stok_kodu']; ?></div>
                        </td>
                        
                        <!-- Tür -->
                        <td style="padding:12px 13px;">
                            <span style="background:#1e2430;border-radius:4px;padding:2px 7px;font-size:10px;color:#94a3b8;"><?php echo $s['tur_kodu']; ?></span>
                        </td>
                        
                        <!-- Sipariş No -->
                        <td style="padding:12px 13px;font-weight:700;color:#fbbf24;"><?php echo $s['siparis_no'] ?: '-'; ?></td>
                        
                        <!-- Tarih -->
                        <td style="padding:12px 13px;color:#64748b;"><?php echo date('d.m.Y', strtotime($s['tarih'])); ?></td>
                        
                        <!-- Miktar -->
                        <td style="padding:12px 13px;font-weight:700;color:#34d399;"><?php echo number_format($s['miktar_kg'], 0, ',', '.'); ?></td>
                        
                        <!-- Tedarikçi -->
                        <td style="padding:12px 13px;color:#94a3b8;"><?php echo $s['tedarikci'] ?: '-'; ?></td>
                        
                        <!-- Birim Varış Maliyeti -->
                        <td style="padding:12px 13px;color:#a78bfa;">
                            <?php if ($varisMaliyet > 0): ?>
                            <div style="font-weight:700;">
                                <?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $s['para_birimi_kodu']; ?>/<?php echo $s['fiyat_birimi']; ?>
                            </div>
                            <?php if ($toplamBedel > 0): ?>
                            <div style="font-size:11px;color:#475569;margin-top:2px;">
                                ≈ <?php echo number_format($toplamBedel, 0, ',', '.'); ?> <?php echo $s['para_birimi_kodu']; ?>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        
                        <!-- Durum -->
                        <td style="padding:12px 13px;">
                            <?php if ($sipTab === 'aktif'): ?>
                            <span style="display:inline-flex;align-items:center;gap:4px;background:#0d2018;color:#34d399;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid #34d39955;">
                                ⏳ Bekliyor
                            </span>
                            <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:4px;background:#1d3557;color:#60a5fa;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;border:1px solid #3b82f655;">
                                📦 Teslim
                            </span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- İşlem -->
                        <td style="padding:12px 13px;">
                            <?php if ($sipTab === 'aktif'): ?>
                            <div style="display:flex;gap:6px;">
                                <button onclick="tamamlaSiparis(<?php echo $s['id']; ?>)" 
                                    style="padding:6px 12px;background:#1d3557;border:1px solid #3b82f655;border-radius:6px;color:#60a5fa;font-size:11px;font-weight:700;cursor:pointer;">
                                    ✅ Tamam
                                </button>
                                <button onclick="iptalSiparis(<?php echo $s['id']; ?>)" 
                                    style="padding:6px 12px;background:#2d1a1a;border:1px solid #ef444455;border-radius:6px;color:#ef4444;font-size:11px;font-weight:700;cursor:pointer;">
                                    ✕ İptal
                                </button>
                            </div>
                            <?php else: ?>
                            <button onclick="temizleSiparis(<?php echo $s['id']; ?>)" 
                                style="padding:6px 12px;background:#1e2430;border:1px solid #1e2430;border-radius:6px;color:#64748b;font-size:11px;cursor:pointer;">
                                ✕ Temizle
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
</div>

<script>
function tamamlaSiparis(id) {
    if (confirm('Bu siparisi teslim alındı olarak işaretlemek istiyor musunuz?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=tamamla'
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
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
        .then(data => { if (data.success) location.reload(); });
    }
}

function temizleSiparis(id) {
    if (confirm('Bu sipariş kaydını tamamen silmek istiyor musunuz?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=sil'
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
