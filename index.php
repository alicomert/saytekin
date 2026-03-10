<?php
require_once 'includes/header.php';

$pageTitle = 'Tum Liste';

// Filtreler
$filters = [
    'sk' => $_GET['sk'] ?? 'Tumu',
    'tur' => $_GET['tur'] ?? 'Tumu',
    'arama' => $_GET['arama'] ?? ''
];

$hammaddeler = getHammaddeler($filters);
$turler = getTurler();

// Sayfa basligi
$pageTitle = 'Tum Liste (' . count($hammaddeler) . ' kayit)';
?>

<div class="animate-fade-in">
    <!-- Filtreler -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-4 mb-6">
        <form method="GET" action="" class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="arama" value="<?php echo $filters['arama']; ?>" 
                    placeholder="Hammadde, stok kodu veya tur ara..."
                    class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-blue-500">
            </div>
            
            <select name="sk" onchange="this.form.submit()" 
                class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-gray-400 text-sm focus:outline-none focus:border-blue-500">
                <option value="Tumu" <?php echo $filters['sk'] == 'Tumu' ? 'selected' : ''; ?>>Tumu</option>
                <option value="S" <?php echo $filters['sk'] == 'S' ? 'selected' : ''; ?>>S - Standart</option>
                <option value="K" <?php echo $filters['sk'] == 'K' ? 'selected' : ''; ?>>K - Kapali</option>
                <option value="A" <?php echo $filters['sk'] == 'A' ? 'selected' : ''; ?>>A - Alternatif</option>
            </select>
            
            <select name="tur" onchange="this.form.submit()" 
                class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-gray-400 text-sm focus:outline-none focus:border-blue-500">
                <option value="Tumu">Tum Turler</option>
                <?php foreach ($turler as $tur): ?>
                <option value="<?php echo $tur['kod']; ?>" <?php echo $filters['tur'] == $tur['kod'] ? 'selected' : ''; ?>>
                    <?php echo $tur['ad']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <?php if (empty($hammaddeler)): ?>
    <!-- Bos durum -->
    <div class="text-center py-20">
        <div class="text-6xl mb-4">📦</div>
        <div class="text-xl text-gray-400 mb-2">Henuz hammadde eklenmedi</div>
        <div class="text-sm text-gray-600 mb-6">Yeni hammadde ekleyerek baslayin</div>
        <a href="hammadde-form.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:opacity-90 text-white px-6 py-3 rounded-lg font-bold">
            <i class="fas fa-plus"></i>Ilk Hammaddeyi Ekle
        </a>
    </div>
    <?php else: ?>
    <!-- Tablo -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full data-table text-sm">
                <thead>
                    <tr>
                        <th>S/K</th>
                        <th>Stok Kodu</th>
                        <th>Urun Kodu</th>
                        <th>Tur</th>
                        <th>Hammadde</th>
                        <th>Stok</th>
                        <th>Optimum</th>
                        <th>Termin</th>
                        <th>2023 Ort.</th>
                        <th>2024 Ort.</th>
                        <th>2025 Ort.</th>
                        <th>Son 12 Ay</th>
                        <th>Son 3 Ay</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hammaddeler as $h): 
                        $durum = getStokDurum($h);
                        $ort23 = getTuketimOrtalama($h['id'], 2023);
                        $ort24 = getTuketimOrtalama($h['id'], 2024);
                        $ort25 = getTuketimOrtalama($h['id'], 2025);
                        $son12 = getSon12AyOrtalama($h['id']);
                        $son3 = getSon3AyOrtalama($h['id']);
                        $terminToplam = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
                    ?>
                    <tr class="group">
                        <td>
                            <span class="px-2 py-1 rounded text-xs font-bold <?php echo $h['sk'] == 'S' ? 'bg-blue-500/20 text-blue-400' : ($h['sk'] == 'K' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'); ?>">
                                <?php echo $h['sk']; ?>
                            </span>
                        </td>
                        <td class="font-mono text-gray-400"><?php echo $h['stok_kodu'] ?: '-'; ?></td>
                        <td class="font-mono text-gray-500"><?php echo $h['urun_kodu'] ?: '-'; ?></td>
                        <td>
                            <span class="bg-dark-700 px-2 py-1 rounded text-xs text-gray-400"><?php echo $h['tur_adi'] ?: '-'; ?></span>
                        </td>
                        <td>
                            <div class="font-semibold text-white max-w-xs truncate" title="<?php echo $h['hammadde_ismi']; ?>">
                                <?php echo $h['hammadde_ismi']; ?>
                            </div>
                        </td>
                        <td>
                            <span class="font-bold" style="color: <?php echo $durum['renk']; ?>">
                                <?php echo formatNumber($h['stok_miktari']); ?>
                            </span>
                            <?php if ($durum['label']): ?>
                            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded font-bold" style="background: <?php echo $durum['renk']; ?>22; color: <?php echo $durum['renk']; ?>">
                                <?php echo $durum['label']; ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-gray-400">
                            <?php if ($h['hesaplanan_optimum']): ?>
                                <?php echo formatNumber($h['hesaplanan_optimum']); ?>
                                <span class="text-xs text-gray-600 ml-1">(<?php echo formatNumber($h['hesaplanan_optimum'] / 2); ?>)</span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($terminToplam > 0): ?>
                            <span class="text-yellow-400 font-bold"><?php echo $terminToplam; ?> gun</span>
                            <?php else: ?><span class="text-gray-600">-</span><?php endif; ?>
                        </td>
                        <td class="text-right font-mono text-blue-400"><?php echo $ort23 > 0 ? formatNumber($ort23, 0) : '-'; ?></td>
                        <td class="text-right font-mono text-purple-400"><?php echo $ort24 > 0 ? formatNumber($ort24, 0) : '-'; ?></td>
                        <td class="text-right font-mono text-green-400"><?php echo $ort25 > 0 ? formatNumber($ort25, 0) : '-'; ?></td>
                        <td class="text-right font-mono text-amber-400 font-bold"><?php echo $son12 > 0 ? formatNumber($son12, 0) : '-'; ?></td>
                        <td class="text-right font-mono text-orange-400 font-bold"><?php echo $son3 > 0 ? formatNumber($son3, 0) : '-'; ?></td>
                        <td>
                            <div class="flex gap-2">
                                <a href="hammadde-detay.php?id=<?php echo $h['id']; ?>" 
                                    class="w-8 h-8 bg-dark-700 hover:bg-dark-600 rounded flex items-center justify-center text-gray-400 transition-colors" title="Detay">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="hammadde-form.php?id=<?php echo $h['id']; ?>" 
                                    class="w-8 h-8 bg-dark-700 hover:bg-dark-600 rounded flex items-center justify-center text-gray-400 transition-colors" title="Duzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
