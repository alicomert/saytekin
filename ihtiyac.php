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

<div class="animate-fade-in">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">⚠️ Ihtiyac Listesi</h1>
            <p class="text-gray-500 text-sm mt-1">
                Kritik stok seviyesindeki hammaddeler - Toplam <?php echo count($kritikListe); ?> kayit
            </p>
        </div>
    </div>
    
    <?php if (empty($kritikListe)): ?>
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-12 text-center">
        <div class="text-6xl mb-4">✅</div>
        <div class="text-xl text-green-400 font-bold mb-2">Tum Hammaddeler Normal Seviyede</div>
        <div class="text-gray-500 text-sm">Su an icin acil siparis gerektiren hammadde bulunmuyor.</div>
    </div>
    <?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($kritikListe as $h): 
            $durum = $h['durum'];
            $son12 = getSon12AyOrtalama($h['id']);
            $gunluk = $son12 / 30;
            $termin = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
            $ihtiyac = ceil($gunluk * $termin * 1.5); // 1.5x guvenlik payi
        ?>
        <div class="bg-dark-800 border rounded-xl p-6 <?php echo $durum['oran'] < 0.5 ? 'border-red-500/50 bg-red-500/5' : ($durum['oran'] < 1 ? 'border-orange-500/50 bg-orange-500/5' : 'border-yellow-500/50 bg-yellow-500/5'); ?>">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-1 rounded text-xs font-bold <?php echo $h['sk'] == 'S' ? 'bg-blue-500/20 text-blue-400' : 'bg-yellow-500/20 text-yellow-400'; ?>">
                            <?php echo $h['sk']; ?>
                        </span>
                        <span class="text-gray-500 text-sm font-mono"><?php echo $h['stok_kodu'] ?: '-'; ?></span>
                        <span class="bg-dark-700 px-2 py-0.5 rounded text-xs text-gray-400"><?php echo $h['tur_adi']; ?></span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1"><?php echo $h['hammadde_ismi']; ?></h3>
                    <?php if ($h['tedarikci']): ?>
                    <p class="text-gray-500 text-sm"><i class="fas fa-building mr-1"></i><?php echo $h['tedarikci']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="flex flex-wrap gap-6 lg:gap-8">
                    <div class="text-center">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Mevcut Stok</div>
                        <div class="text-2xl font-bold" style="color: <?php echo $durum['renk']; ?>">
                            <?php echo formatNumber($h['stok_miktari'], 0); ?>
                            <span class="text-sm font-normal text-gray-500">kg</span>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Kalan Gun</div>
                        <div class="text-2xl font-bold" style="color: <?php echo $durum['renk']; ?>">
                            <?php echo $durum['kalan_gun'] !== null ? $durum['kalan_gun'] : '-'; ?>
                            <span class="text-sm font-normal text-gray-500">gun</span>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Termin</div>
                        <div class="text-2xl font-bold text-yellow-400">
                            <?php echo $termin; ?>
                            <span class="text-sm font-normal text-gray-500">gun</span>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Onerilen Siparis</div>
                        <div class="text-2xl font-bold text-blue-400">
                            <?php echo formatNumber($ihtiyac, 0); ?>
                            <span class="text-sm font-normal text-gray-500">kg</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <span class="px-4 py-2 rounded-lg font-bold text-sm <?php echo $durum['oran'] < 0.5 ? 'bg-red-500 text-white' : ($durum['oran'] < 1 ? 'bg-orange-500 text-white' : 'bg-yellow-500 text-dark-900'); ?>">
                            <?php echo $durum['label']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="hammadde-detay.php?id=<?php echo $h['id']; ?>" 
                        class="w-10 h-10 bg-dark-700 hover:bg-dark-600 rounded-lg flex items-center justify-center text-gray-400 transition-colors" title="Detay">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="hammadde-form.php?id=<?php echo $h['id']; ?>" 
                        class="w-10 h-10 bg-dark-700 hover:bg-dark-600 rounded-lg flex items-center justify-center text-gray-400 transition-colors" title="Duzenle">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            
            <!-- Progress bar -->
            <div class="mt-4">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Stok/Termin Orani: %<?php echo round(($durum['oran'] ?? 0) * 100); ?></span>
                    <span>Optimum: <?php echo formatNumber($h['hesaplanan_optimum'], 0); ?> kg</span>
                </div>
                <div class="h-2 bg-dark-900 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" 
                         style="width: <?php echo min(100, ($durum['oran'] ?? 0) * 50); ?>%; background-color: <?php echo $durum['renk']; ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
