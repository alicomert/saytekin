<?php
require_once 'includes/header.php';

$pageTitle = 'Fiyat Tablosu';

$db = getDB();
$hammaddeler = $db->query("SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi,
                            pb.sembol as para_sembol, ts.ad as teslimat_adi
                            FROM hammaddeler h
                            LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
                            LEFT JOIN ulkeler u ON h.mensei_ulke_id = u.id
                            LEFT JOIN paketleme_tipleri pt ON h.paketleme_kodu = pt.kod
                            LEFT JOIN para_birimleri pb ON h.para_birimi_kodu = pb.kod
                            LEFT JOIN teslimat_sekilleri ts ON h.teslimat_sekli_kodu = ts.kod
                            WHERE h.is_active = 1 AND h.birim_fiyat > 0
                            ORDER BY h.hammadde_ismi")
                       ->fetchAll();

$kurlar = getDovizKurlari();
$secili = $_GET['secili'] ?? [];
?>

<div style="margin-bottom:20px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;">
    <div>
        <div style="font-size:18px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">💰 Fiyat Tablosu</div>
        <div style="font-size:13px;color:#64748b;">Fiyat bilgisi girilmis tum hammaddeler - <?php echo count($hammaddeler); ?> kayit</div>
    </div>
    
    <div style="display:flex;gap:8px;">
        <button onclick="tumunuSec()" style="padding:8px 16px;background:#1a1535;border:1px solid #a78bfa55;border-radius:8px;color:#a78bfa;font-size:12px;font-weight:700;cursor:pointer;">
            Tumunu Sec
        </button>
        <button onclick="secimiTemizle()" style="padding:8px 16px;background:#141820;border:1px solid #1e2430;border-radius:8px;color:#64748b;font-size:12px;font-weight:700;cursor:pointer;">
            Secimi Temizle
        </button>
    </div>
</div>

<?php if (empty($hammaddeler)): ?>
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:48px;text-align:center;">
    <div style="font-size:48;margin-bottom:16px;">💵</div>
    <div style="font-size:16px;color:#64748b;font-weight:700;margin-bottom:8px;">Fiyat Bilgisi Yok</div>
    <div style="font-size:13px;color:#475569;">Henüz fiyat bilgisi girilmis hammadde bulunmuyor.</div>
</div>
<?php else: ?>
<div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="tumunu_sec_checkbox" onchange="toggleTumunu(this)" style="accent-color:#3b82f6;">
                    </th>
                    <th>Hammadde</th>
                    <th>Stok Kodu</th>
                    <th>Tedarikci</th>
                    <th>Mensei</th>
                    <th>Paketleme</th>
                    <th>Teslim Sekli</th>
                    <th>Para Birimi</th>
                    <th>Maliyet Turu</th>
                    <th>Birim Fiyat</th>
                    <th>Maliyet</th>
                    <th>Varis Maliyeti</th>
                    <th>Toplam Stok Bedeli</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hammaddeler as $h): 
                    $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
                    $maliyet = hesaplaMaliyet($h, $kurlar);
                    $birimMiktar = $h['fiyat_birimi'] === 'ton' ? ($h['stok_miktari'] ?? 0) / 1000 : ($h['stok_miktari'] ?? 0);
                    $stokBedeli = $varisMaliyet * $birimMiktar;
                ?>
                <tr data-id="<?php echo $h['id']; ?>" style="<?php echo in_array($h['id'], $secili) ? 'background:#a78bfa0a;' : ''; ?>">
                    <td>
                        <input type="checkbox" class="hammadde-checkbox" value="<?php echo $h['id']; ?>" 
                               <?php echo in_array($h['id'], $secili) ? 'checked' : ''; ?>
                               onchange="toggleSecim(<?php echo $h['id']; ?>, this)" style="accent-color:#3b82f6;">
                    </td>
                    <td>
                        <div style="font-weight:700;color:#f1f5f9;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo $h['hammadde_ismi']; ?>">
                            <?php echo $h['hammadde_ismi']; ?>
                        </div>
                    </td>
                    <td style="font-family:monospace;color:#94a3b8;"><?php echo $h['stok_kodu'] ?: '-'; ?></td>
                    <td style="color:#94a3b8;"><?php echo $h['tedarikci'] ?: '-'; ?></td>
                    <td style="color:#94a3b8;"><?php echo $h['ulke_adi'] ?: '-'; ?></td>
                    <td>
                        <?php if ($h['paketleme_adi']): ?>
                        <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $h['paketleme_adi']; ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td>
                        <span style="background:#3b82f622;color:#60a5fa;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;"><?php echo $h['teslimat_sekli_kodu']; ?></span>
                    </td>
                    <td style="font-weight:700;color:#94a3b8;"><?php echo $h['para_birimi_kodu']; ?></td>
                    <td>
                        <?php if ($h['maliyet_turu'] === 'G'): ?>
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background-color:rgba(16,185,129,0.13);color:#34d399;">G</span>
                        <?php else: ?>
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background-color:rgba(245,158,11,0.13);color:#fbbf24;">T</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;color:#60a5fa;"><?php echo number_format($h['birim_fiyat'], 2, ',', '.'); ?></td>
                    <td style="color:#a78bfa;">
                        <?php if ($h['maliyet_tipi'] === 'yuzde'): ?>
                            %<?php echo $h['maliyet_deger']; ?>
                        <?php else: ?>
                            <?php echo number_format($maliyet, 2, ',', '.'); ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;color:#34d399;"><?php echo number_format($varisMaliyet, 2, ',', '.'); ?></td>
                    <td style="font-weight:700;color:#fbbf24;">
                        <?php echo $stokBedeli > 0 ? number_format($stokBedeli, 0, ',', '.') . ' ' . $h['para_birimi_kodu'] : '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Alt Toplam -->
    <div style="background:#0f1117;border-top:1px solid #1e2430;padding:16px;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;">Toplam Stok Bedeli</div>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <?php
            $pbToplam = [];
            foreach ($hammaddeler as $h) {
                $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
                $birimMiktar = $h['fiyat_birimi'] === 'ton' ? ($h['stok_miktari'] ?? 0) / 1000 : ($h['stok_miktari'] ?? 0);
                $pb = $h['para_birimi_kodu'];
                if (!isset($pbToplam[$pb])) $pbToplam[$pb] = 0;
                $pbToplam[$pb] += $varisMaliyet * $birimMiktar;
            }
            
            foreach ($pbToplam as $pb => $toplam):
                if ($toplam > 0):
            ?>
            <div style="background:#141820;border:1px solid #1e2430;border-radius:8px;padding:8px 16px;">
                <span style="color:#fbbf24;font-weight:700;font-size:16px;"><?php echo number_format($toplam, 0, ',', '.'); ?></span>
                <span style="color:#64748b;font-size:12px;margin-left:4px;"><?php echo $pb; ?></span>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let seciliIds = [];

function toggleSecim(id, checkbox) {
    if (checkbox.checked) {
        if (!seciliIds.includes(id)) seciliIds.push(id);
    } else {
        seciliIds = seciliIds.filter(i => i !== id);
    }
    updateRowStyle(id, checkbox.checked);
}

function updateRowStyle(id, selected) {
    const row = document.querySelector('tr[data-id="' + id + '"]');
    if (row) {
        row.style.background = selected ? '#a78bfa0a' : 'transparent';
    }
}

function tumunuSec() {
    document.querySelectorAll('.hammadde-checkbox').forEach(cb => {
        cb.checked = true;
        const id = parseInt(cb.value);
        if (!seciliIds.includes(id)) seciliIds.push(id);
        updateRowStyle(id, true);
    });
    document.getElementById('tumunu_sec_checkbox').checked = true;
}

function secimiTemizle() {
    document.querySelectorAll('.hammadde-checkbox').forEach(cb => {
        cb.checked = false;
        updateRowStyle(parseInt(cb.value), false);
    });
    seciliIds = [];
    document.getElementById('tumunu_sec_checkbox').checked = false;
}

function toggleTumunu(checkbox) {
    if (checkbox.checked) {
        tumunuSec();
    } else {
        secimiTemizle();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>