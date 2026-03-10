<?php
require_once 'includes/header.php';

$pageTitle = 'Fiyat Karşılaştırma';

$db = getDB();
$hammaddeler = $db->query("SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi,
                            ts.ad as teslimat_adi, pb.sembol as para_sembol
                            FROM hammaddeler h
                            LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
                            LEFT JOIN ulkeler u ON h.mensei_ulke_id = u.id
                            LEFT JOIN paketleme_tipleri pt ON h.paketleme_kodu = pt.kod
                            LEFT JOIN teslimat_sekilleri ts ON h.teslimat_sekli_kodu = ts.kod
                            LEFT JOIN para_birimleri pb ON h.para_birimi_kodu = pb.kod
                            WHERE h.is_active = 1 AND h.birim_fiyat > 0
                            ORDER BY h.hammadde_ismi")
                       ->fetchAll();

$kurlar = getDovizKurlari();

$secili = $_GET['secili'] ?? [];
if (is_string($secili) && $secili) {
    $secili = array_map('intval', explode(',', $secili));
} else {
    $secili = [];
}

$seciliHammaddeler = array_filter($hammaddeler, fn($h) => in_array($h['id'], $secili));
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:16px;">
        <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">⚖️ Fiyat Karşılaştırma</h2>
        <p style="color:#475569;font-size:13px;">Karşılaştırmak istediğiniz hammaddeleri seçin</p>
    </div>

    <!-- Seçim Paneli -->
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:20px;margin-bottom:20px;">
        <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:12px;">
            Hammadde Seç <span id="secim_sayisi" style="margin-left:8px;background:#1e2430;padding:2px 8px;border-radius:4px;color:#f1f5f9;"><?php echo count($secili); ?> seçili</span>
            <?php if (count($secili) > 0): ?>
            <button onclick="secimiTemizle()" style="margin-left:12px;background:transparent;border:none;color:#ef4444;font-size:11px;cursor:pointer;">Temizle</button>
            <?php endif; ?>
        </div>
        
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ($hammaddeler as $h): 
                $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
                $isSecili = in_array($h['id'], $secili);
            ?>
            <button type="button" onclick="toggleSecim(<?php echo $h['id']; ?>, this)" 
                    data-id="<?php echo $h['id']; ?>"
                    class="secim-btn" 
                    style="padding:10px 16px;background:<?php echo $isSecili ? '#1d3557' : '#0f1117'; ?>;border:1px solid <?php echo $isSecili ? '#3b82f6' : '#1e2430'; ?>;border-radius:8px;text-align:left;cursor:pointer;transition:all 0.15s;">
                <div style="font-weight:700;color:<?php echo $isSecili ? '#f1f5f9' : '#94a3b8'; ?>;font-size:13px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo htmlspecialchars($h['hammadde_ismi']); ?>
                </div>
                <div style="font-size:11px;color:<?php echo $isSecili ? '#34d399' : '#475569'; ?>;margin-top:4px;">
                    <?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $h['para_birimi_kodu']; ?>/<?php echo $h['fiyat_birimi']; ?>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($seciliHammaddeler) >= 2): ?>
    <!-- Karşılaştırma Tablosu -->
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="padding:14px 18px;border-bottom:1px solid #1e2430;">
            <span style="font-weight:700;color:#f1f5f9;"><span id="karsilastirma_sayisi"><?php echo count($seciliHammaddeler); ?></span> hammadde karşılaştırılıyor</span>
        </div>
        
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#0f1117;">
                        <th style="text-align:left;color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid #1e2430;">KRİTER</th>
                        <?php foreach ($seciliHammaddeler as $h): ?>
                        <th style="text-align:left;color:#60a5fa;font-weight:700;font-size:13px;padding:12px 16px;border-bottom:1px solid #1e2430;min-width:160px;">
                            <div style="color:#f1f5f9;margin-bottom:4px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($h['hammadde_ismi']); ?>">
                                <?php echo htmlspecialchars($h['hammadde_ismi']); ?>
                            </div>
                            <div style="font-size:11px;color:#64748b;font-weight:400;"><?php echo $h['stok_kodu']; ?></div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Calculate varis maliyetleri for each
                    $varisDegerler = [];
                    foreach ($seciliHammaddeler as $h) {
                        $varisDegerler[$h['id']] = hesaplaVarisMaliyeti($h, $kurlar);
                    }
                    
                    $kriterler = [
                        ['label' => 'Tedarikçi', 'fn' => fn($h) => $h['tedarikci'] ?: '-'],
                        ['label' => 'Menşei Ülke', 'fn' => fn($h) => $h['ulke_adi'] ?: '-'],
                        ['label' => 'Paketleme', 'fn' => fn($h) => $h['paketleme_adi'] ?: '-'],
                        ['label' => 'Teslim Şekli', 'fn' => fn($h) => $h['teslimat_sekli_kodu'] ?: '-'],
                        ['label' => 'Para Birimi', 'fn' => fn($h) => $h['para_birimi_kodu'] ?: '-'],
                        ['label' => 'Fiyat Birimi', 'fn' => fn($h) => '/' . ($h['fiyat_birimi'] ?: 'ton')],
                        ['label' => 'Maliyet Türü', 'fn' => fn($h) => ($h['maliyet_turu'] ?? 'T') === 'G' ? 'G - Gerçekleşen' : 'T - Tahmini', 'renkFn' => fn($h) => ($h['maliyet_turu'] ?? 'T') === 'G' ? '#34d399' : '#fbbf24'],
                        ['label' => 'Birim Fiyat', 'fn' => fn($h) => number_format((float)$h['birim_fiyat'], 2, ',', '.') . ' ' . ($h['para_birimi_kodu'] ?? 'USD'), 'sayisal' => true, 'key' => 'birim_fiyat', 'renkFn' => fn($h, $min, $max) => (float)$h['birim_fiyat'] === $min ? '#34d399' : ((float)$h['birim_fiyat'] === $max ? '#ef4444' : '#f1f5f9')],
                        ['label' => 'Birim Varış Maliyeti', 'fn' => fn($h) => number_format($varisDegerler[$h['id']], 2, ',', '.') . ' ' . ($h['para_birimi_kodu'] ?? 'USD'), 'vurgulu' => true, 'sayisal' => true, 'key' => 'varis', 'renkFn' => fn($h, $min, $max) => $varisDegerler[$h['id']] === $min ? '#34d399' : ($varisDegerler[$h['id']] === $max ? '#ef4444' : '#f1f5f9')],
                        ['label' => 'Mevcut Stok', 'fn' => fn($h) => $h['stok_miktari'] ? number_format($h['stok_miktari'], 0, ',', '.') . ' kg' : '-'],
                        ['label' => 'Toplam Stok Bedeli', 'fn' => fn($h) => $varisDegerler[$h['id']] > 0 ? number_format($varisDegerler[$h['id']] * (($h['fiyat_birimi'] ?? 'ton') === 'ton' ? $h['stok_miktari'] / 1000 : $h['stok_miktari']), 0, ',', '.') . ' ' . ($h['para_birimi_kodu'] ?? 'USD') : '-', 'sayisal' => true],
                    ];
                    
                    foreach ($kriterler as $idx => $kriter):
                        // Calculate min/max for numeric criteria
                        $vals = [];
                        if (($kriter['sayisal'] ?? false) && ($kriter['renkFn'] ?? false)) {
                            foreach ($seciliHammaddeler as $h) {
                                if (isset($kriter['key']) && $kriter['key'] === 'varis') {
                                    $vals[] = $varisDegerler[$h['id']];
                                } elseif (isset($kriter['key'])) {
                                    $vals[] = (float)($h[$kriter['key']] ?? 0);
                                }
                            }
                            $minV = count($vals) ? min($vals) : 0;
                            $maxV = count($vals) ? max($vals) : 0;
                        } else {
                            $minV = $maxV = 0;
                        }
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;<?php echo $idx % 2 === 1 ? 'background:#0f1117;' : ''; ?>">
                        <td style="padding:12px 16px;color:#64748b;font-weight:700;font-size:11px;text-transform:uppercase;white-space:nowrap;"><?php echo $kriter['label']; ?></td>
                        <?php foreach ($seciliHammaddeler as $h): 
                            $val = $kriter['fn']($h);
                            $renk = ($kriter['renkFn'] ?? null) ? $kriter['renkFn']($h, $minV, $maxV) : (($kriter['vurgulu'] ?? false) ? '#34d399' : '#94a3b8');
                            $agirlik = (($kriter['vurgulu'] ?? false) || ($kriter['sayisal'] ?? false)) ? '700' : '400';
                        ?>
                        <td style="padding:12px 16px;color:<?php echo $renk; ?>;font-weight:<?php echo $agirlik; ?>;font-size:<?php echo ($kriter['vurgulu'] ?? false) ? '14px' : '12px'; ?>;">
                            <?php echo $val; ?>
                            <?php if (($kriter['vurgulu'] ?? false) && count($vals) > 0 && ($kriter['key'] ?? '') === 'varis'): ?>
                                <?php if ($varisDegerler[$h['id']] === $minV && $minV !== $maxV): ?>
                                <span style="margin-left:6px;font-size:10px;background:#10b98133;color:#34d399;border-radius:3px;padding:1px 5px;font-weight:700;">EN DÜŞÜK</span>
                                <?php elseif ($varisDegerler[$h['id']] === $maxV && $minV !== $maxV): ?>
                                <span style="margin-left:6px;font-size:10px;background:#ef444433;color:#ef4444;border-radius:3px;padding:1px 5px;font-weight:700;">EN YÜKSEK</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif (count($seciliHammaddeler) === 1): ?>
    <div style="text-align:center;padding:30px;color:#475569;font-size:13px;background:#141820;border:1px solid #1e2430;border-radius:12px;">
        ⚖️ Karşılaştırma için en az 2 hammadde seçin.
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:30px;color:#475569;font-size:13px;background:#141820;border:1px solid #1e2430;border-radius:12px;">
        Yukarıdan hammaddeleri seçerek karşılaştırma başlatın.
    </div>
    <?php endif; ?>
</div>

<script>
let seciliIds = <?php echo json_encode(array_map('intval', $secili)); ?>;

function toggleSecim(id, btn) {
    const index = seciliIds.indexOf(id);
    
    if (index === -1) {
        seciliIds.push(id);
        btn.style.borderColor = '#3b82f6';
        btn.style.background = '#1d3557';
        btn.querySelector('div:first-child').style.color = '#f1f5f9';
        btn.querySelector('div:last-child').style.color = '#34d399';
    } else {
        seciliIds.splice(index, 1);
        btn.style.borderColor = '#1e2430';
        btn.style.background = '#0f1117';
        btn.querySelector('div:first-child').style.color = '#94a3b8';
        btn.querySelector('div:last-child').style.color = '#475569';
    }
    
    document.getElementById('secim_sayisi').textContent = seciliIds.length + ' seçili';
    guncelleURL();
}

function guncelleURL() {
    const url = new URL(window.location);
    if (seciliIds.length > 0) {
        url.searchParams.set('secili', seciliIds.join(','));
    } else {
        url.searchParams.delete('secili');
    }
    window.history.replaceState({}, '', url);
}

function secimiTemizle() {
    seciliIds = [];
    document.querySelectorAll('.secim-btn').forEach(btn => {
        btn.style.borderColor = '#1e2430';
        btn.style.background = '#0f1117';
        btn.querySelector('div:first-child').style.color = '#94a3b8';
        btn.querySelector('div:last-child').style.color = '#475569';
    });
    document.getElementById('secim_sayisi').textContent = '0 seçili';
    guncelleURL();
    location.reload();
}
</script>

<?php require_once 'includes/footer.php'; ?>
