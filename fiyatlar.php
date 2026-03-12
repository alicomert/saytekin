<?php
require_once 'includes/header.php';

$pageTitle = 'Fiyat Tablosu';

$db = getDB();
$hammaddeler = $db->query("SELECT h.*, ht.ad as tur_adi, u.ad as ulke_adi, pt.ad as paketleme_adi, pt.kod as paketleme_kodu,
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
if (is_string($secili)) {
    $secili = explode(',', $secili);
}

$PAKETLEME_TIPLERI = [
    'dokme' => ['ad' => 'Dökme', 'aciklama' => 'Tanker, silo veya açık araçla dökme taşıma'],
    'bigbag' => ['ad' => 'Big Bag (FIBC)', 'aciklama' => '500–1500 kg\'lık jumbo torba, vinç ile elleçlenir'],
    'craft' => ['ad' => 'Kraft Kağıt Torba', 'aciklama' => '25–50 kg\'lık çok katlı kağıt ambalaj'],
    'plastik' => ['ad' => 'Plastik Torba', 'aciklama' => '25–50 kg\'lık PE/PP torba'],
    'drum' => ['ad' => 'Varil / Drum', 'aciklama' => 'Metal veya plastik 200 lt / 250 kg varil'],
    'ibc' => ['ad' => 'IBC Konteyner', 'aciklama' => '1000 lt\'lik palet üzeri sıvı konteyner'],
    'teneke' => ['ad' => 'Teneke / Bidon', 'aciklama' => '5–25 kg metal veya plastik ambalaj'],
    'karton' => ['ad' => 'Karton Kutu', 'aciklama' => 'Küçük miktarlar için oluklu mukavva ambalaj'],
    'konteyner' => ['ad' => 'ISO Konteyner', 'aciklama' => '20\' veya 40\' FCL konteyner içinde'],
];
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8;">
        <div>
            <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">💰 Fiyat Tablosu</h2>
            <p style="color:#475569;font-size:13px;">Fiyat bilgisi girilmiş tüm hammaddeler — <?php echo count($hammaddeler); ?> kayıt
                <?php if (count($secili) > 0): ?>
                <span style="margin-left:8px;color:#a78bfa;font-weight:700;">· <?php echo count($secili); ?> seçili</span>
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?php if (count($secili) > 0): ?>
            <button onclick="secimiTemizle()" style="background:#2d1a1a;border:1px solid #ef444444;border-radius:6px;padding:6px 12px;color:#f87171;cursor:pointer;font-size:12px;font-weight:700;">
                ✕ Seçimi Temizle
            </button>
            <?php endif; ?>
            <button onclick="tumunuSec()" style="background:#1a1535;border:1px solid #a78bfa44;border-radius:6px;padding:6px 12px;color:#a78bfa;cursor:pointer;font-size:12px;font-weight:700;">
                <?php echo count($secili) == count($hammaddeler) && count($hammaddeler) > 0 ? '☐ Tümünü Bırak' : '☑ Tümünü Seç'; ?>
            </button>
        </div>
    </div>

    <?php if (empty($hammaddeler)): ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:48px;text-align:center;">
        <div style="font-size:48px;margin-bottom:16px;">🏷️</div>
        <div style="font-size:16px;color:#475569;">Henüz fiyat bilgisi girilmiş hammadde yok.</div>
    </div>
    <?php else: ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                        <th style="padding:10px 13px;width:36px;">
                            <input type="checkbox" id="tumunu_sec_checkbox" 
                                <?php echo count($secili) == count($hammaddeler) && count($hammaddeler) > 0 ? 'checked' : ''; ?>
                                onchange="toggleTumunu(this)" style="cursor:pointer;width:14px;height:14px;">
                        </th>
                        <?php 
                        $sutunlar = ["Hammadde","Stok Kodu","Tedarikçi","Menşei","Paketleme","Teslim Şekli","Para Birimi","Birim","Tür","Birim Fiyat","Maliyet","Varış Maliyeti","Varış (EUR)","Toplam Stok Bedeli","Geçmiş Sayısı"];
                        foreach ($sutunlar as $h): ?>
                        <th style="padding:10px 13px;text-align:left;color:#475569;font-weight:700;white-space:nowrap;letter-spacing:0.05em;font-size:10px;"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hammaddeler as $idx => $h): 
                        $varisMaliyet = hesaplaVarisMaliyeti($h, $kurlar);
                        $maliyet = hesaplaMaliyet($h, $kurlar);
                        $bp = (float)$h['birim_fiyat'];
                        $md = (float)$h['maliyet_deger'];
                        $birim = $h['fiyat_birimi'] ?? 'ton';
                        $stokKg = (float)$h['stok_miktari'];
                        $stokBirimde = $birim === 'ton' ? $stokKg / 1000 : $stokKg;
                        $topBedel = $varisMaliyet * $stokBirimde;
                        $pk = $PAKETLEME_TIPLERI[$h['paketleme_kodu']] ?? null;
                        $tur = $h['maliyet_turu'] ?? 'T';
                        $isSecili = in_array($h['id'], $secili);
                        
                        // EUR varış
                        $eurVaris = 0;
                        if ($kurlar && $varisMaliyet > 0) {
                            $pb2 = $h['para_birimi_kodu'] ?? 'USD';
                            if ($pb2 === 'EUR') {
                                $eurVaris = $varisMaliyet;
                            } else {
                                $k = $kurlar[$pb2 . '_EUR'] ?? 0;
                                $eurVaris = $k > 0 ? $varisMaliyet * $k : 0;
                            }
                        }
                        
                        $fiyatGecmisi = getFiyatGecmisi($h['id']);
                    ?>
                    <tr data-id="<?php echo $h['id']; ?>" style="border-bottom:1px solid #1e2430;cursor:pointer;background:<?php echo $isSecili ? '#1a1535' : 'transparent'; ?>;"
                        onMouseEnter="if(!<?php echo $isSecili ? 'true' : 'false'; ?>)this.style.background='#1a2130'"
                        onMouseLeave="if(!<?php echo $isSecili ? 'true' : 'false'; ?>)this.style.background='transparent'">
                        <td style="padding:10px 13px;" onclick="event.stopPropagation();toggleSecim(<?php echo $h['id']; ?>, this)">
                            <input type="checkbox" class="hammadde-checkbox" value="<?php echo $h['id']; ?>" 
                                <?php echo $isSecili ? 'checked' : ''; ?>
                                onchange="toggleSecim(<?php echo $h['id']; ?>, this)" style="cursor:pointer;width:14px;height:14px;">
                        </td>
                        <td style="padding:10px 13px;font-weight:600;color:#f1f5f9;max-width:200px;cursor:pointer;" onclick="detayGit(<?php echo $h['id']; ?>)">
                            <?php echo htmlspecialchars($h['hammadde_ismi']); ?>
                        </td>
                        <!-- Düzenle Butonu -->
                        <td style="padding:10px 13px;">
                            <a href="hammadde-form.php?id=<?php echo $h['id']; ?>" 
                                style="padding:4px 10px;background:#141820;border:1px solid #fbbf2466;border-radius:6px;color:#fbbf24;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;"
                                onclick="event.stopPropagation();"
                                title="Hammaddeyi Düzenle">
                                ✏️
                            </a>
                        </td>
                        <td style="padding:10px 13px;color:#64748b;font-family:monospace;font-size:11px;"><?php echo $h['stok_kodu']; ?></td>
                        <td style="padding:10px 13px;color:#94a3b8;"><?php echo $h['tedarikci'] ?: '-'; ?></td>
                        <td style="padding:10px 13px;color:#94a3b8;"><?php echo $h['ulke_adi'] ?: '-'; ?></td>
                        <td style="padding:10px 13px;">
                            <?php if ($pk): ?>
                            <span style="background:#1e2430;border-radius:4px;padding:2px 7px;font-size:11px;color:#94a3b8;"><?php echo $pk['ad']; ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td style="padding:10px 13px;">
                            <span style="background:#1a2535;color:#60a5fa;border-radius:4px;padding:2px 7px;font-size:11px;font-weight:700;"><?php echo $h['teslimat_sekli_kodu']; ?></span>
                        </td>
                        <td style="padding:10px 13px;color:#94a3b8;font-weight:600;"><?php echo $h['para_birimi_kodu']; ?></td>
                        <td style="padding:10px 13px;color:#94a3b8;">/<?php echo $birim; ?></td>
                        <td style="padding:10px 13px;">
                            <span style="padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;
                                background:<?php echo $tur=='G'?'#10b98133':'#f59e0b33'; ?>;
                                color:<?php echo $tur=='G'?'#34d399':'#fbbf24'; ?>;
                                border:1px solid <?php echo $tur=='G'?'#10b98155':'#f59e0b55'; ?>;">
                                <?php echo $tur=='G'?'G':'T'; ?>
                            </span>
                        </td>
                        <td style="padding:10px 13px;font-weight:700;color:#60a5fa;"><?php echo number_format($bp, 2, ',', '.'); ?></td>
                        <td style="padding:10px 13px;color:#a78bfa;">
                            <?php echo $h['maliyet_tipi'] === 'yuzde' ? '%' . $md : number_format($maliyet, 2, ',', '.'); ?>
                        </td>
                        <td style="padding:10px 13px;font-weight:700;color:#34d399;"><?php echo number_format($varisMaliyet, 2, ',', '.'); ?></td>
                        <td style="padding:10px 13px;">
                            <?php if ($eurVaris > 0): ?>
                            <span style="font-weight:700;color:#a78bfa;font-size:12px;"><?php echo number_format($eurVaris, 2, ',', '.'); ?> <span style="font-size:10px;font-weight:400;color:#475569;">EUR</span></span>
                            <?php else: ?><span style="color:#334155;">—</span><?php endif; ?>
                        </td>
                        <td style="padding:10px 13px;font-weight:700;color:#fbbf24;">
                            <?php echo $topBedel > 0 ? number_format($topBedel, 0, ',', '.') . ' ' . $h['para_birimi_kodu'] : '-'; ?>
                        </td>
                        <td style="padding:10px 13px;text-align:center;">
                            <?php if (count($fiyatGecmisi) > 0): ?>
                            <span style="background:#1e2430;color:#94a3b8;border-radius:10px;padding:1px 8px;font-size:11px;"><?php echo count($fiyatGecmisi); ?></span>
                            <?php else: ?><span style="color:#334155;">—</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Alt Toplam -->
        <?php 
        $pbToplam = [];
        foreach ($hammaddeler as $h) {
            $varis = hesaplaVarisMaliyeti($h, $kurlar);
            $birim = $h['fiyat_birimi'] ?? 'ton';
            $stok = (float)$h['stok_miktari'];
            $stokB = $birim === 'ton' ? $stok / 1000 : $stok;
            $pb = $h['para_birimi_kodu'] ?? '?';
            if (!isset($pbToplam[$pb])) $pbToplam[$pb] = 0;
            $pbToplam[$pb] += $varis * $stokB;
        }
        
        $pbToplamSec = [];
        if (count($secili) > 0) {
            foreach ($hammaddeler as $h) {
                if (!in_array($h['id'], $secili)) continue;
                $varis = hesaplaVarisMaliyeti($h, $kurlar);
                $birim = $h['fiyat_birimi'] ?? 'ton';
                $stok = (float)$h['stok_miktari'];
                $stokB = $birim === 'ton' ? $stok / 1000 : $stok;
                $pb = $h['para_birimi_kodu'] ?? '?';
                if (!isset($pbToplamSec[$pb])) $pbToplamSec[$pb] = 0;
                $pbToplamSec[$pb] += $varis * $stokB;
            }
        }
        
        $pbListesi = array_unique(array_merge(array_keys($pbToplam), array_keys($pbToplamSec)));
        ?>
        <div style="background:#0d1017;border-top:2px solid #1e2430;padding:14px 18px;">
            <div style="font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
                Toplam Stok Bedeli
            </div>
            <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
                <div>
                    <div style="font-size:10px;color:#64748b;margin-bottom:6px;">📊 Tüm Hammaddeler (<?php echo count($hammaddeler); ?> kayıt)</div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <?php foreach ($pbListesi as $pb): 
                            if (($pbToplam[$pb] ?? 0) > 0):
                        ?>
                        <div style="background:#141820;border-radius:6px;padding:6px 14px;border:1px solid #1e2430;">
                            <span style="color:#fbbf24;font-weight:700;font-size:14px;"><?php echo number_format($pbToplam[$pb], 0, ',', '.'); ?></span>
                            <span style="color:#475569;font-size:11px;margin-left:4px;"><?php echo $pb; ?></span>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php if (count($secili) > 0): ?>
                <div>
                    <div style="font-size:10px;color:#a78bfa;margin-bottom:6px;">✅ Seçili Hammaddeler (<?php echo count($secili); ?> kayıt)</div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <?php foreach ($pbListesi as $pb): 
                            if (($pbToplamSec[$pb] ?? 0) > 0):
                        ?>
                        <div style="background:#1a1535;border-radius:6px;padding:6px 14px;border:1px solid #a78bfa33;">
                            <span style="color:#a78bfa;font-weight:700;font-size:14px;"><?php echo number_format($pbToplamSec[$pb], 0, ',', '.'); ?></span>
                            <span style="color:#475569;font-size:11px;margin-left:4px;"><?php echo $pb; ?></span>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let seciliIds = <?php echo json_encode(array_map('intval', $secili)); ?>;

function toggleSecim(id, checkbox) {
    if (checkbox.checked) {
        if (!seciliIds.includes(id)) seciliIds.push(id);
    } else {
        seciliIds = seciliIds.filter(i => i !== id);
    }
    updateRowStyle(id, checkbox.checked);
    guncelleURL();
    location.reload();
}

function updateRowStyle(id, selected) {
    const row = document.querySelector('tr[data-id="' + id + '"]');
    if (row) {
        row.style.background = selected ? '#1a1535' : 'transparent';
    }
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

function tumunuSec() {
    const tumCheckbox = document.getElementById('tumunu_sec_checkbox');
    const checkboxlar = document.querySelectorAll('.hammadde-checkbox');
    
    if (tumCheckbox.checked || seciliIds.length === 0) {
        // Hepsi seçili değilse seç
        checkboxlar.forEach(cb => {
            cb.checked = true;
            const id = parseInt(cb.value);
            if (!seciliIds.includes(id)) seciliIds.push(id);
            updateRowStyle(id, true);
        });
    } else {
        // Hepsi seçiliyse temizle
        seciliIds = [];
        checkboxlar.forEach(cb => {
            cb.checked = false;
            updateRowStyle(parseInt(cb.value), false);
        });
    }
    
    const url = new URL(window.location);
    if (seciliIds.length > 0) {
        url.searchParams.set('secili', seciliIds.join(','));
    } else {
        url.searchParams.delete('secili');
    }
    window.history.replaceState({}, '', url);
    location.reload();
}

function secimiTemizle() {
    seciliIds = [];
    document.querySelectorAll('.hammadde-checkbox').forEach(cb => {
        cb.checked = false;
        updateRowStyle(parseInt(cb.value), false);
    });
    document.getElementById('tumunu_sec_checkbox').checked = false;
    
    const url = new URL(window.location);
    url.searchParams.delete('secili');
    window.history.replaceState({}, '', url);
    location.reload();
}

function toggleTumunu(checkbox) {
    tumunuSec();
}

function detayGit(id) {
    window.location = 'hammadde-detay.php?id=' + id;
}
</script>

<?php require_once 'includes/footer.php'; ?>
