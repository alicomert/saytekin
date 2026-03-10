<?php
require_once 'includes/header.php';

$pageTitle = 'Siparisler';

$durum = $_GET['durum'] ?? 'bekleyen';
$siparisler = getSiparisler($durum);
$kurlar = getDovizKurlari();

// Sayfa basligini guncelle
$pageTitle = $durum === 'bekleyen' ? 'Bekleyen Siparisler' : 'Tamamlanan Siparisler';
?>

<div class="animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">🛒 Siparisler</h1>
            <p class="text-gray-500 text-sm mt-1">
                <?php echo count($siparisler); ?> siparis listeleniyor
            </p>
        </div>
        
        <div class="flex gap-2">
            <a href="?durum=bekleyen" 
                class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?php echo $durum === 'bekleyen' ? 'bg-green-500/20 text-green-400 border border-green-500' : 'bg-dark-700 text-gray-500 hover:bg-dark-600'; ?>">
                <i class="fas fa-clock mr-2"></i>Bekleyen (<?php echo countAktifSiparisler(); ?>)
            </a>
            <a href="?durum=tamamlanan" 
                class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?php echo $durum === 'tamamlanan' ? 'bg-blue-500/20 text-blue-400 border border-blue-500' : 'bg-dark-700 text-gray-500 hover:bg-dark-600'; ?>">
                <i class="fas fa-check mr-2"></i>Tamamlanan
            </a>
        </div>
    </div>
    
    <?php if (empty($siparisler)): ?>
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <div class="text-xl text-gray-400 font-bold mb-2">
            <?php echo $durum === 'bekleyen' ? 'Bekleyen Siparis Yok' : 'Tamamlanan Siparis Yok'; ?>
        </div>
        <div class="text-gray-500 text-sm">
            <?php echo $durum === 'bekleyen' ? 'Su an icin bekleyen siparis bulunmuyor.' : 'Henüz teslim alinan siparis kaydi yok.'; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table text-sm">
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
                    <tr class="group">
                        <td>
                            <div class="font-bold text-blue-400"><?php echo $s['hammadde_ismi']; ?></div>
                            <div class="text-xs text-gray-500"><?php echo $s['stok_kodu'] ?: '-'; ?></div>
                        </td>
                        <td>
                            <span class="bg-dark-700 px-2 py-1 rounded text-xs text-gray-400"><?php echo $s['tur_kodu']; ?></span>
                        </td>
                        <td class="font-bold text-amber-400"><?php echo $s['siparis_no'] ?: '-'; ?></td>
                        <td class="text-gray-400"><?php echo formatDate($s['tarih']); ?></td>
                        <td class="font-bold text-green-400"><?php echo formatNumber($s['miktar_kg'], 0); ?></td>
                        <td class="text-gray-400"><?php echo $s['tedarikci'] ?: '-'; ?></td>
                        <td>
                            <?php if ($varisMaliyet > 0): ?>
                            <div class="font-bold text-purple-400">
                                <?php echo formatNumber($varisMaliyet, 2); ?> <?php echo $s['para_birimi_kodu']; ?>/<?php echo $s['fiyat_birimi']; ?>
                            </div>
                            <?php if ($toplamBedel > 0): ?>
                            <div class="text-xs text-gray-500">
                                ≈ <?php echo formatNumber($toplamBedel, 0); ?> <?php echo $s['para_birimi_kodu']; ?>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($durum === 'bekleyen'): ?>
                            <span class="inline-flex items-center gap-1 bg-green-500/10 text-green-400 px-2 py-1 rounded text-xs font-bold">
                                <i class="fas fa-hourglass-half"></i>Bekliyor
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 bg-blue-500/10 text-blue-400 px-2 py-1 rounded text-xs font-bold">
                                <i class="fas fa-check-circle"></i>Teslim
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($durum === 'bekleyen'): ?>
                            <div class="flex gap-2">
                                <button onclick="tamamlaSiparis(<?php echo $s['id']; ?>)" 
                                    class="px-3 py-1.5 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 border border-blue-500/50 rounded-lg text-xs font-bold transition-colors">
                                    <i class="fas fa-check mr-1"></i>Tamam
                                </button>
                                <button onclick="iptalSiparis(<?php echo $s['id']; ?>)" 
                                    class="px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/50 rounded-lg text-xs font-bold transition-colors">
                                    <i class="fas fa-times mr-1"></i>Iptal
                                </button>
                            </div>
                            <?php else: ?>
                            <button onclick="iptalSiparis(<?php echo $s['id']; ?>)" 
                                class="px-3 py-1.5 bg-dark-700 hover:bg-dark-600 text-gray-400 rounded-lg text-xs transition-colors">
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
</div>

<script>
function tamamlaSiparis(id) {
    if (confirm('Bu siparisi teslim alindi olarak isaretlemek istiyor musunuz?')) {
        // AJAX ile guncelle
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
