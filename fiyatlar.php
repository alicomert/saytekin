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

<div class="animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">💰 Fiyat Tablosu</h1>
            <p class="text-gray-500 text-sm mt-1">
                Fiyat bilgisi girilmis tum hammaddeler - <?php echo count($hammaddeler); ?> kayit
            </p>
        </div>
        
        <div class="flex gap-2">
            <button onclick="tumunuSec()" class="px-4 py-2 bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 border border-purple-500/50 rounded-lg text-sm font-bold transition-colors">
                <i class="fas fa-check-square mr-2"></i>Tumunu Sec
            </button>
            <button onclick="secimiTemizle()" class="px-4 py-2 bg-dark-700 hover:bg-dark-600 text-gray-400 rounded-lg text-sm font-bold transition-colors">
                <i class="fas fa-times mr-2"></i>Secimi Temizle
            </button>
        </div>
    </div>
    
    <?php if (empty($hammaddeler)): ?>
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-12 text-center">
        <div class="text-6xl mb-4">💵</div>
        <div class="text-xl text-gray-400 font-bold mb-2">Fiyat Bilgisi Yok</div>
        <div class="text-gray-500 text-sm">Henüz fiyat bilgisi girilmis hammadde bulunmuyor.</div>
    </div>
    <?php else: ?>
    <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table text-sm">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="tumunu_sec_checkbox" onchange="toggleTumunu(this)" class="rounded bg-dark-700 border-dark-600">
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
                    <tr class="group <?php echo in_array($h['id'], $secili) ? 'bg-purple-500/10' : ''; ?>" data-id="<?php echo $h['id']; ?>">
                        <td>
                            <input type="checkbox" class="hammadde-checkbox rounded bg-dark-700 border-dark-600" 
                                   value="<?php echo $h['id']; ?>" 
                                   <?php echo in_array($h['id'], $secili) ? 'checked' : ''; ?>
                                   onchange="toggleSecim(<?php echo $h['id']; ?>, this)">
                        </td>
                        <td>
                            <div class="font-bold text-white max-w-xs truncate" title="<?php echo $h['hammadde_ismi']; ?>">
                                <?php echo $h['hammadde_ismi']; ?>
                            </div>
                        </td>
                        <td class="font-mono text-gray-400"><?php echo $h['stok_kodu'] ?: '-'; ?></td>
                        <td class="text-gray-400"><?php echo $h['tedarikci'] ?: '-'; ?></td>
                        <td class="text-gray-400"><?php echo $h['ulke_adi'] ?: '-'; ?></td>
                        <td>
                            <?php if ($h['paketleme_adi']): ?>
                            <span class="bg-dark-700 px-2 py-0.5 rounded text-xs text-gray-400"><?php echo $h['paketleme_adi']; ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <span class="bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded text-xs font-bold"><?php echo $h['teslimat_sekli_kodu']; ?></span>
                        </td>
                        <td class="font-bold text-gray-400"><?php echo $h['para_birimi_kodu']; ?></td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-xs font-bold <?php echo $h['maliyet_turu'] === 'G' ? 'bg-green-500/20 text-green-400' : 'bg-amber-500/20 text-amber-400'; ?>">
                                <?php echo $h['maliyet_turu'] === 'G' ? 'G' : 'T'; ?>
                            </span>
                        </td>
                        <td class="font-bold text-blue-400"><?php echo formatNumber($h['birim_fiyat'], 2); ?></td>
                        <td class="text-purple-400">
                            <?php if ($h['maliyet_tipi'] === 'yuzde'): ?>
                                %<?php echo $h['maliyet_deger']; ?>
                            <?php else: ?>
                                <?php echo formatNumber($maliyet, 2); ?>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold text-green-400"><?php echo formatNumber($varisMaliyet, 2); ?></td>
                        <td class="font-bold text-amber-400">
                            <?php echo $stokBedeli > 0 ? formatNumber($stokBedeli, 0) . ' ' . $h['para_birimi_kodu'] : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Alt Toplam -->
        <div class="bg-dark-900 border-t border-dark-700 p-4">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Toplam Stok Bedeli</div>
            <div class="flex flex-wrap gap-4">
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
                <div class="bg-dark-800 border border-dark-700 rounded-lg px-4 py-2">
                    <span class="text-amber-400 font-bold text-lg"><?php echo formatNumber($toplam, 0); ?></span>
                    <span class="text-gray-500 text-sm ml-1"><?php echo $pb; ?></span>
                </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

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
        if (selected) {
            row.classList.add('bg-purple-500/10');
        } else {
            row.classList.remove('bg-purple-500/10');
        }
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
