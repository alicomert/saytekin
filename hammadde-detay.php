<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$hammadde = getHammadde($id);
if (!$hammadde) {
    setFlashMessage('Hammadde bulunamadi.', 'error');
    header('Location: index.php');
    exit;
}

$tuketimVerileri = getTuketimVerileri($id);
$fiyatGecmisi = getFiyatGecmisi($id);
$durum = getStokDurum($hammadde);
$kurlar = getDovizKurlari();

$pageTitle = $hammadde['hammadde_ismi'];
?>

<div class="animate-fade-in">
    <!-- Ust Bilgi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 rounded text-xs font-bold <?php echo $hammadde['sk'] == 'S' ? 'bg-blue-500/20 text-blue-400' : ($hammadde['sk'] == 'K' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'); ?>">
                    <?php echo $hammadde['sk']; ?> - <?php echo $hammadde['sk'] == 'S' ? 'Standart' : ($hammadde['sk'] == 'K' ? 'Kapali' : 'Alternatif'); ?>
                </span>
                <span class="bg-dark-700 px-2 py-1 rounded text-xs text-gray-400"><?php echo $hammadde['tur_adi'] ?: '-'; ?></span>
            </div>
            <h1 class="text-2xl font-bold text-white"><?php echo $hammadde['hammadde_ismi']; ?></h1>
            <p class="text-gray-500 text-sm mt-1">
                Stok Kodu: <span class="font-mono text-gray-400"><?php echo $hammadde['stok_kodu'] ?: '-'; ?></span> | 
                Urun Kodu: <span class="font-mono text-gray-400"><?php echo $hammadde['urun_kodu'] ?: '-'; ?></span>
            </p>
        </div>
        
        <div class="flex gap-2">
            <a href="index.php" class="px-4 py-2 bg-dark-700 hover:bg-dark-600 text-gray-400 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Listeye Don
            </a>
            <a href="hammadde-form.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 border border-blue-500/50 rounded-lg text-sm font-semibold transition-colors">
                <i class="fas fa-edit mr-2"></i>Duzenle
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sol Kolon - Temel Bilgiler -->
        <div class="space-y-6">
            <!-- Stok Durumu -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">📊 Stok Durumu</h2>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-4 bg-dark-900 rounded-lg">
                        <div class="text-xs text-gray-500 uppercase mb-1">Mevcut Stok</div>
                        <div class="text-2xl font-bold" style="color: <?php echo $durum['renk']; ?>">
                            <?php echo formatNumber($hammadde['stok_miktari'], 0); ?>
                            <span class="text-sm font-normal">kg</span>
                        </div>
                    </div>
                    <div class="text-center p-4 bg-dark-900 rounded-lg">
                        <div class="text-xs text-gray-500 uppercase mb-1">Kalan Gun</div>
                        <div class="text-2xl font-bold" style="color: <?php echo $durum['renk']; ?>">
                            <?php echo $durum['kalan_gun'] !== null ? $durum['kalan_gun'] : '-'; ?>
                            <span class="text-sm font-normal">gun</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Stok/Termin Orani: %<?php echo round(($durum['oran'] ?? 0) * 100); ?></span>
                    <span>Durum: <span style="color: <?php echo $durum['renk']; ?>"><?php echo $durum['label'] ?: 'RAHAT'; ?></span></span>
                </div>
                <div class="h-2 bg-dark-900 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" 
                         style="width: <?php echo min(100, ($durum['oran'] ?? 0) * 50); ?>%; background-color: <?php echo $durum['renk']; ?>">
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-dark-700 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Optimum:</span>
                        <span class="text-white ml-1"><?php echo formatNumber($hammadde['hesaplanan_optimum'], 0); ?> kg</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Gunluk Tuketim:</span>
                        <span class="text-white ml-1"><?php echo formatNumber($durum['gunluk_tuketim'], 1); ?> kg</span>
                    </div>
                </div>
            </div>
            
            <!-- Tedarikci Bilgileri -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">🏢 Tedarikci Bilgileri</h2>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-gray-500 block">Tedarikci</span>
                        <span class="text-white"><?php echo $hammadde['tedarikci'] ?: '-'; ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Mensei Ulke</span>
                        <span class="text-white"><?php echo $hammadde['ulke_adi'] ?: '-'; ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Paketleme</span>
                        <span class="text-white"><?php echo $hammadde['paketleme_adi'] ?: '-'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Termin Sureleri -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">⏱️ Termin Sureleri</h2>
                
                <?php
                $terminToplam = ($hammadde['akreditif_gun'] ?? 0) + ($hammadde['satici_tedarik_gun'] ?? 0) + ($hammadde['yol_gun'] ?? 0) + ($hammadde['depo_kabul_gun'] ?? 0);
                ?>
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500"><span class="text-blue-400">●</span> Akreditif Acma</span>
                        <span class="font-bold text-white"><?php echo $hammadde['akreditif_gun'] ?: 0; ?> gun</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500"><span class="text-purple-400">●</span> Satici Tedarik</span>
                        <span class="font-bold text-white"><?php echo $hammadde['satici_tedarik_gun'] ?: 0; ?> gun</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500"><span class="text-green-400">●</span> Yol (Nakliye)</span>
                        <span class="font-bold text-white"><?php echo $hammadde['yol_gun'] ?: 0; ?> gun</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500"><span class="text-yellow-400">●</span> Depo Kabul</span>
                        <span class="font-bold text-white"><?php echo $hammadde['depo_kabul_gun'] ?: 0; ?> gun</span>
                    </div>
                    <div class="pt-2 mt-2 border-t border-dark-700 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-400">TOPLAM</span>
                        <span class="font-bold text-yellow-400 text-lg"><?php echo $terminToplam; ?> gun</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Orta Kolon - Fiyat Bilgileri -->
        <div class="space-y-6">
            <!-- Fiyat Bilgileri -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">💰 Fiyat Bilgileri</h2>
                
                <?php if ($hammadde['birim_fiyat'] > 0): 
                    $varisMaliyet = hesaplaVarisMaliyeti($hammadde, $kurlar);
                    $maliyet = hesaplaMaliyet($hammadde, $kurlar);
                ?>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-dark-900 rounded-lg">
                        <span class="text-gray-500">Birim Fiyat</span>
                        <span class="font-bold text-blue-400"><?php echo formatNumber($hammadde['birim_fiyat'], 2); ?> <?php echo $hammadde['para_birimi_kodu']; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-dark-900 rounded-lg">
                        <span class="text-gray-500">Fiyat Birimi</span>
                        <span class="font-bold text-white">/<?php echo $hammadde['fiyat_birimi']; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-dark-900 rounded-lg">
                        <span class="text-gray-500">Teslim Sekli</span>
                        <span class="font-bold text-blue-400"><?php echo $hammadde['teslimat_sekli_kodu']; ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-dark-900 rounded-lg">
                        <span class="text-gray-500">Maliyet Turu</span>
                        <span class="font-bold <?php echo $hammadde['maliyet_turu'] === 'G' ? 'text-green-400' : 'text-amber-400'; ?>">
                            <?php echo $hammadde['maliyet_turu'] === 'G' ? 'G - Gerceklesen' : 'T - Tahmini'; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-dark-900 rounded-lg">
                        <span class="text-gray-500">Nakliye/Maliyet</span>
                        <span class="text-purple-400">
                            <?php if ($hammadde['maliyet_tipi'] === 'yuzde'): ?>
                                %<?php echo $hammadde['maliyet_deger']; ?>
                            <?php else: ?>
                                <?php echo formatNumber($maliyet, 2); ?> <?php echo $hammadde['para_birimi_kodu']; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-green-500/10 border border-green-500/30 rounded-lg">
                        <span class="text-gray-400">Birim Varis Maliyeti</span>
                        <span class="font-bold text-green-400 text-xl"><?php echo formatNumber($varisMaliyet, 2); ?> <?php echo $hammadde['para_birimi_kodu']; ?>/<?php echo $hammadde['fiyat_birimi']; ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-tag text-4xl mb-2 opacity-50"></i>
                    <p>Fiyat bilgisi girilmemis</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Fiyat Gecmisi -->
            <?php if (!empty($fiyatGecmisi)): ?>
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">📈 Fiyat Gecmisi</h2>
                
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <?php foreach (array_slice($fiyatGecmisi, 0, 10) as $fg): ?>
                    <div class="flex justify-between items-center p-2 bg-dark-900 rounded text-sm">
                        <span class="text-gray-500"><?php echo formatDate($fg['kayit_tarihi']); ?></span>
                        <span class="font-bold text-blue-400"><?php echo formatNumber($fg['birim_fiyat'], 2); ?> <?php echo $fg['sembol']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sag Kolon - Tuketim Verileri -->
        <div class="lg:col-span-1">
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">📅 Tuketim Verileri</h2>
                
                <?php foreach (YILLAR as $yil): 
                    $yilRenkler = [2023 => 'blue', 2024 => 'purple', 2025 => 'green', 2026 => 'amber', 2027 => 'red'];
                    $renk = $yilRenkler[$yil] ?? 'gray';
                    
                    $yilVeriler = [];
                    $toplam = 0;
                    $sayac = 0;
                    foreach (AYLAR as $ayNo => $ayAd) {
                        $deger = $tuketimVerileri[$yil][$ayNo] ?? null;
                        $yilVeriler[$ayNo] = $deger;
                        if ($deger !== null) {
                            $toplam += $deger;
                            $sayac++;
                        }
                    }
                    $ortalama = $sayac > 0 ? $toplam / $sayac : 0;
                ?>
                <div class="mb-6 last:mb-0">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 rounded-full bg-<?php echo $renk; ?>-400"></span>
                        <span class="font-bold text-<?php echo $renk; ?>-400"><?php echo $yil; ?></span>
                        <span class="text-xs text-gray-500 ml-auto">Ort: <?php echo formatNumber($ortalama, 0); ?> kg</span>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <?php foreach (AYLAR as $ayNo => $ayAd): 
                            $deger = $yilVeriler[$ayNo];
                        ?>
                        <div class="bg-dark-900 rounded p-2 text-center">
                            <div class="text-gray-500 text-[10px] mb-1"><?php echo substr($ayAd, 0, 3); ?></div>
                            <div class="font-mono <?php echo $deger !== null ? 'text-white' : 'text-gray-700'; ?>">
                                <?php echo $deger !== null ? formatNumber($deger, 0) : '-'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-2 pt-2 border-t border-dark-700 flex justify-between text-xs">
                        <span class="text-gray-500">Toplam: <span class="text-<?php echo $renk; ?>-400 font-bold"><?php echo formatNumber($toplam, 0); ?></span> kg</span>
                        <span class="text-gray-500">Girilen: <?php echo $sayac; ?>/12 ay</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
