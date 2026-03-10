<?php
require_once 'includes/header.php';

$pageTitle = 'Stok Guncelleme';

// Gunun tarihi
$buYil = date('Y');
$buAy = date('n');
$ayAd = AYLAR[$buAy];

// Onceki ay
$oncekiAy = $buAy > 1 ? $buAy - 1 : 12;
$oncekiAyYil = $buAy > 1 ? $buYil : $buYil - 1;
$oncekiAyAd = AYLAR[$oncekiAy];

// Sadece S (Standart) hammaddeler
$hammaddeler = getHammaddeler(['sk' => 'S']);

// Siralama
usort($hammaddeler, function($a, $b) {
    return strcmp($a['hammadde_ismi'], $b['hammadde_ismi']);
});

// Form gonderildi
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

<div class="animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">🔄 Stok Guncelleme</h1>
            <p class="text-gray-500 text-sm mt-1">
                <?php echo count($hammaddeler); ?> standart hammadde · Stok miktari ve <span class="text-cyan-400"><?php echo $ayAd . ' ' . $buYil; ?></span> tuketimi guncellenebilir
            </p>
        </div>
        
        <div class="bg-cyan-500/10 border border-cyan-500/30 rounded-lg px-4 py-2">
            <span class="text-cyan-400 text-sm font-bold">📅 Guncelleme ayi: <?php echo $ayAd . ' ' . $buYil; ?></span>
        </div>
    </div>
    
    <form method="POST" action="">
        <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-dark-900 border-b-2 border-dark-700">
                            <th class="text-left text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">#</th>
                            <th class="text-left text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">HAMMADDE</th>
                            <th class="text-left text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">TUR</th>
                            <th class="text-right text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">MEVCUT STOK (kg)</th>
                            <th class="text-right text-cyan-400 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap bg-cyan-500/5">📦 YENI STOK (kg)</th>
                            <th class="text-right text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap"><?php echo strtoupper($oncekiAyAd); ?> <?php echo $oncekiAyYil; ?> TUK.</th>
                            <th class="text-right text-cyan-400 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap bg-cyan-500/5">🗓 <?php echo strtoupper($ayAd); ?> <?php echo $buYil; ?> TUK. GIR</th>
                            <th class="text-right text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">ORT. AYLIK</th>
                            <th class="text-right text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">OPTIMUM</th>
                            <th class="text-center text-gray-500 font-bold text-xs uppercase tracking-wider p-3 whitespace-nowrap">DURUM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hammaddeler as $idx => $h): 
                            $stok = (float)$h['stok_miktari'];
                            $optimum = (float)$h['hesaplanan_optimum'];
                            $durum = getStokDurum($h);
                            $son12 = getSon12AyOrtalama($h['id']);
                            
                            // Onceki ay tuketimi
                            $db = getDB();
                            $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri 
                                                  WHERE hammadde_id = ? AND yil = ? AND ay = ?");
                            $stmt->execute([$h['id'], $oncekiAyYil, $oncekiAy]);
                            $oncekiTuketim = $stmt->fetch()['miktar_kg'] ?? 0;
                            
                            // Bu ay tuketimi
                            $stmt = $db->prepare("SELECT miktar_kg FROM tuketim_verileri 
                                                  WHERE hammadde_id = ? AND yil = ? AND ay = ?");
                            $stmt->execute([$h['id'], $buYil, $buAy]);
                            $buAyTuketim = $stmt->fetch()['miktar_kg'] ?? null;
                            $buAyDolu = $buAyTuketim !== null;
                        ?>
                        <tr class="border-b border-dark-700 <?php echo $buAyDolu ? '' : 'bg-amber-500/5'; ?> hover:bg-dark-700/50">
                            <td class="p-3 text-gray-600 text-xs"><?php echo $idx + 1; ?></td>
                            <td class="p-3">
                                <div class="font-bold text-white text-sm max-w-[200px] truncate" title="<?php echo $h['hammadde_ismi']; ?>">
                                    <?php echo $h['hammadde_ismi']; ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo $h['urun_kodu'] ?: '-'; ?></div>
                            </td>
                            <td class="p-3">
                                <span class="bg-dark-700 px-2 py-0.5 rounded text-xs text-gray-400"><?php echo $h['tur_adi'] ?: '-'; ?></span>
                            </td>
                            <td class="p-3 text-right font-mono font-bold" style="color: <?php echo $durum['renk']; ?>">
                                <?php echo formatNumber($stok, 0); ?>
                            </td>
                            <td class="p-2 bg-cyan-500/5">
                                <input type="number" name="stok[<?php echo $h['id']; ?>]" 
                                    placeholder="<?php echo $stok > 0 ? formatNumber($stok, 0) : '-'; ?>"
                                    class="w-full min-w-[100px] bg-dark-900 border border-cyan-500/30 rounded-lg px-3 py-2 text-right text-cyan-400 font-mono font-bold text-sm focus:outline-none focus:border-cyan-400">
                            </td>
                            <td class="p-3 text-right font-mono text-gray-500">
                                <?php echo $oncekiTuketim > 0 ? formatNumber($oncekiTuketim, 0) : '-'; ?>
                            </td>
                            <td class="p-2 bg-cyan-500/5">
                                <div class="flex items-center gap-2">
                                    <?php if ($buAyDolu): ?>
                                    <span class="text-green-400 font-mono font-bold text-xs">✓ <?php echo formatNumber($buAyTuketim, 0); ?></span>
                                    <?php endif; ?>
                                    <input type="number" name="tuketim[<?php echo $h['id']; ?>]" 
                                        placeholder="<?php echo $buAyDolu ? 'duzenle' : 'gir...'; ?>"
                                        class="w-full min-w-[100px] bg-dark-900 border rounded-lg px-3 py-2 text-right font-mono font-bold text-sm focus:outline-none <?php echo $buAyDolu ? 'border-green-500/30 text-green-400' : 'border-amber-500/30 text-amber-400'; ?>">
                                </div>
                            </td>
                            <td class="p-3 text-right font-mono text-gray-400">
                                <?php echo $son12 > 0 ? formatNumber($son12, 0) : '-'; ?>
                            </td>
                            <td class="p-3 text-right font-mono text-sm">
                                <?php if ($optimum > 0): ?>
                                    <span class="text-gray-500"><?php echo formatNumber($optimum, 0); ?></span>
                                    <div class="text-xs text-gray-600">(<?php echo formatNumber($optimum / 2, 0); ?>)</div>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold" 
                                      style="background: <?php echo $durum['renk']; ?>22; color: <?php echo $durum['renk']; ?>">
                                    <?php echo $durum['label'] ?: 'RAHAT'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Alt bilgi -->
            <div class="bg-dark-900 border-t border-dark-700 p-4 flex flex-wrap gap-6 items-center">
                <div class="text-xs text-gray-500">
                    <span class="text-amber-400 font-bold">■</span> Sari arka plan = <?php echo $ayAd . ' ' . $buYil; ?> tuketimi henüz girilmemis
                </div>
                <div class="text-xs text-gray-500">
                    <span class="text-green-400 font-bold">✓</span> Yesil = tuketim girilmis
                </div>
                <div class="ml-auto">
                    <button type="submit" class="flex items-center gap-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:opacity-90 text-white px-6 py-3 rounded-lg font-bold transition-opacity">
                        <i class="fas fa-save"></i>Tum Degisiklikleri Kaydet
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
