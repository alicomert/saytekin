<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;

// Form verileri
$formData = [
    'sk' => 'S',
    'stok_kodu' => '',
    'urun_kodu' => '',
    'tur_kodu' => '',
    'hammadde_ismi' => '',
    'tedarikci' => '',
    'mensei_ulke_id' => '',
    'paketleme_kodu' => '',
    'stok_miktari' => '',
    'hesaplanan_optimum' => '',
    'birim_fiyat' => '',
    'para_birimi_kodu' => 'USD',
    'fiyat_birimi' => 'ton',
    'teslimat_sekli_kodu' => 'CIF',
    'maliyet_tipi' => 'yuzde',
    'maliyet_deger' => '',
    'maliyet_pb_kodu' => '',
    'maliyet_turu' => 'T',
    'akreditif_gun' => '',
    'satici_tedarik_gun' => '',
    'yol_gun' => '',
    'depo_kabul_gun' => ''
];

$tuketimVerileri = [];

// Duzenleme modunda verileri getir
if ($isEdit) {
    $hammadde = getHammadde($id);
    if (!$hammadde) {
        setFlashMessage('Hammadde bulunamadi.', 'error');
        header('Location: index.php');
        exit;
    }
    
    $formData = array_merge($formData, $hammadde);
    $tuketimVerileri = getTuketimVerileri($id);
}

// Dropdown verileri
$turler = getTurler();
$ulkeler = getUlkeler();
$paketlemeler = getPaketlemeTipleri();
$teslimatlar = getTeslimatSekilleri();
$parabirimleri = getParaBirimleri();

$pageTitle = $isEdit ? 'Hammadde Duzenle' : 'Yeni Hammadde';

// Form gonderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Silme islemi
        deleteHammadde($id);
        setFlashMessage('Hammadde silindi.', 'success');
        header('Location: index.php');
        exit;
    }
    
    // Form verilerini al
    $formData['sk'] = $_POST['sk'] ?? 'S';
    $formData['stok_kodu'] = $_POST['stok_kodu'] ?? '';
    $formData['urun_kodu'] = $_POST['urun_kodu'] ?? '';
    $formData['tur_kodu'] = $_POST['tur_kodu'] ?? '';
    $formData['hammadde_ismi'] = $_POST['hammadde_ismi'] ?? '';
    $formData['tedarikci'] = $_POST['tedarikci'] ?? '';
    $formData['mensei_ulke_id'] = $_POST['mensei_ulke_id'] ?: null;
    $formData['paketleme_kodu'] = $_POST['paketleme_kodu'] ?: null;
    $formData['stok_miktari'] = $_POST['stok_miktari'] ?: 0;
    $formData['hesaplanan_optimum'] = $_POST['hesaplanan_optimum'] ?: 0;
    $formData['birim_fiyat'] = $_POST['birim_fiyat'] ?: 0;
    $formData['para_birimi_kodu'] = $_POST['para_birimi_kodu'] ?? 'USD';
    $formData['fiyat_birimi'] = $_POST['fiyat_birimi'] ?? 'ton';
    $formData['teslimat_sekli_kodu'] = $_POST['teslimat_sekli_kodu'] ?? 'CIF';
    $formData['maliyet_tipi'] = $_POST['maliyet_tipi'] ?? 'yuzde';
    $formData['maliyet_deger'] = $_POST['maliyet_deger'] ?: 0;
    $formData['maliyet_pb_kodu'] = $_POST['maliyet_pb_kodu'] ?: null;
    $formData['maliyet_turu'] = $_POST['maliyet_turu'] ?? 'T';
    
    // Validasyon
    $errors = [];
    if ($formData['sk'] !== 'A' && empty($formData['stok_kodu'])) {
        $errors[] = 'Standart ve Kapali hammadde icin stok kodu zorunludur.';
    }
    if (empty($formData['hammadde_ismi'])) {
        $errors[] = 'Hammadde ismi zorunludur.';
    }
    
    if (empty($errors)) {
        // Hammaddeyi kaydet
        $newId = saveHammadde($formData, $id);
        
        // Termin surelerini kaydet
        saveTerminSuresi($newId, [
            'akreditif' => $_POST['akreditif_gun'] ?? 0,
            'satici_tedarik' => $_POST['satici_tedarik_gun'] ?? 0,
            'yol' => $_POST['yol_gun'] ?? 0,
            'depo_kabul' => $_POST['depo_kabul_gun'] ?? 0
        ]);
        
        // Tuketim verilerini kaydet
        if (isset($_POST['tuketim'])) {
            foreach ($_POST['tuketim'] as $yil => $aylar) {
                foreach ($aylar as $ay => $miktar) {
                    if ($miktar !== '') {
                        saveTuketimVerisi($newId, $yil, $ay, $miktar);
                    }
                }
            }
        }
        
        setFlashMessage($isEdit ? 'Hammadde guncellendi.' : 'Hammadde eklendi.', 'success');
        header('Location: index.php');
        exit;
    }
}
?>

<div class="animate-fade-in max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">
                <?php echo $isEdit ? '✏️ Hammadde Duzenle' : '➕ Yeni Hammadde Ekle'; ?>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Zorunlu alanlari (*) doldurun.</p>
        </div>
        <a href="index.php" class="text-gray-400 hover:text-white flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>Listeye Don
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6">
        <?php foreach ($errors as $error): ?>
        <div><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" class="space-y-6">
        <!-- Temel Bilgiler -->
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 pb-2 border-b border-dark-700">
                📋 Temel Bilgiler
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- S/K -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        S / K <span class="text-red-500">*</span>
                    </label>
                    <select name="sk" onchange="toggleAlternatif(this.value)"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <option value="S" <?php echo $formData['sk'] == 'S' ? 'selected' : ''; ?>>S - Standart</option>
                        <option value="K" <?php echo $formData['sk'] == 'K' ? 'selected' : ''; ?>>K - Kapali</option>
                        <option value="A" <?php echo $formData['sk'] == 'A' ? 'selected' : ''; ?>>A - Alternatif</option>
                    </select>
                </div>
                
                <!-- Stok Kodu -->
                <div id="stok_kodu_div">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Stok Kodu <span id="stok_kodu_zorunlu" class="text-red-500">*</span>
                    </label>
                    <input type="text" name="stok_kodu" value="<?php echo $formData['stok_kodu']; ?>"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="Orn: 16">
                </div>
                
                <!-- Urun Kodu -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Urun Kodu
                    </label>
                    <input type="text" name="urun_kodu" value="<?php echo $formData['urun_kodu']; ?>"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="Orn: 540004">
                </div>
                
                <!-- Tur -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Tur
                    </label>
                    <select name="tur_kodu"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <option value="">Secin...</option>
                        <?php foreach ($turler as $tur): ?>
                        <option value="<?php echo $tur['kod']; ?>" <?php echo $formData['tur_kodu'] == $tur['kod'] ? 'selected' : ''; ?>>
                            <?php echo $tur['ad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <!-- Hammadde Ismi -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Hammadde Ismi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="hammadde_ismi" value="<?php echo $formData['hammadde_ismi']; ?>" required
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="Orn: KUVARS 45M MATEL">
                </div>
                
                <!-- Tedarikci -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Tedarikci
                    </label>
                    <input type="text" name="tedarikci" value="<?php echo $formData['tedarikci']; ?>"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="Orn: MATEL A.S.">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <!-- Menşei Ülke -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Mensei Ulke
                    </label>
                    <select name="mensei_ulke_id"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <option value="">Secin...</option>
                        <?php foreach ($ulkeler as $ulke): ?>
                        <option value="<?php echo $ulke['id']; ?>" <?php echo $formData['mensei_ulke_id'] == $ulke['id'] ? 'selected' : ''; ?>>
                            <?php echo $ulke['ad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Paketleme -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Paketleme Tipi
                    </label>
                    <select name="paketleme_kodu" onchange="showPaketlemeAciklama(this)"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <option value="">Secin...</option>
                        <?php foreach ($paketlemeler as $p): ?>
                        <option value="<?php echo $p['kod']; ?>" data-aciklama="<?php echo $p['aciklama']; ?>" <?php echo $formData['paketleme_kodu'] == $p['kod'] ? 'selected' : ''; ?>>
                            <?php echo $p['ad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Paketleme Aciklama -->
                <div id="paketleme_aciklama" class="flex items-end">
                    <?php if ($formData['paketleme_kodu']): 
                        $paketleme = array_filter($paketlemeler, fn($p) => $p['kod'] == $formData['paketleme_kodu']);
                        $paketleme = reset($paketleme);
                    ?>
                    <div class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-xs text-gray-500 w-full">
                        <span class="text-blue-400 font-bold"><?php echo $paketleme['ad']; ?></span><br>
                        <?php echo $paketleme['aciklama']; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <!-- Stok Miktarı -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Stok Miktari (kg)
                    </label>
                    <input type="number" name="stok_miktari" value="<?php echo $formData['stok_miktari']; ?>" step="0.01"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="0">
                </div>
                
                <!-- Hesaplanan Optimum -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Hesaplanan Optimum
                    </label>
                    <input type="number" name="hesaplanan_optimum" value="<?php echo $formData['hesaplanan_optimum']; ?>" step="0.01"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500"
                        placeholder="0">
                </div>
            </div>
            
            <!-- Termin Sureleri -->
            <div class="bg-dark-900 border border-dark-700 rounded-lg p-4 mt-4">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                    Termin Suresi (gun cinsinden 4 asama)
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs text-blue-400 font-bold mb-1">1. Akreditif Acma</label>
                        <input type="number" name="akreditif_gun" value="<?php echo $formData['akreditif_gun']; ?>" min="0"
                            class="w-full bg-dark-800 border border-dark-700 rounded-lg px-3 py-2 text-center text-white focus:outline-none focus:border-blue-500"
                            placeholder="gun">
                    </div>
                    <div>
                        <label class="block text-xs text-purple-400 font-bold mb-1">2. Satici Tedarik</label>
                        <input type="number" name="satici_tedarik_gun" value="<?php echo $formData['satici_tedarik_gun']; ?>" min="0"
                            class="w-full bg-dark-800 border border-dark-700 rounded-lg px-3 py-2 text-center text-white focus:outline-none focus:border-purple-500"
                            placeholder="gun">
                    </div>
                    <div>
                        <label class="block text-xs text-green-400 font-bold mb-1">3. Yol (Nakliye)</label>
                        <input type="number" name="yol_gun" value="<?php echo $formData['yol_gun']; ?>" min="0"
                            class="w-full bg-dark-800 border border-dark-700 rounded-lg px-3 py-2 text-center text-white focus:outline-none focus:border-green-500"
                            placeholder="gun">
                    </div>
                    <div>
                        <label class="block text-xs text-yellow-400 font-bold mb-1">4. Depo Kabul</label>
                        <input type="number" name="depo_kabul_gun" value="<?php echo $formData['depo_kabul_gun']; ?>" min="0"
                            class="w-full bg-dark-800 border border-dark-700 rounded-lg px-3 py-2 text-center text-white focus:outline-none focus:border-yellow-500"
                            placeholder="gun">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fiyat Bilgileri -->
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 pb-2 border-b border-dark-700">
                💰 Fiyat & Maliyet Bilgileri
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <!-- Birim Fiyat -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Birim Fiyat
                    </label>
                    <input type="number" name="birim_fiyat" value="<?php echo $formData['birim_fiyat']; ?>" step="0.0001"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500"
                        placeholder="0.00">
                </div>
                
                <!-- Para Birimi -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Para Birimi
                    </label>
                    <select name="para_birimi_kodu"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <?php foreach ($parabirimleri as $pb): ?>
                        <option value="<?php echo $pb['kod']; ?>" <?php echo $formData['para_birimi_kodu'] == $pb['kod'] ? 'selected' : ''; ?>>
                            <?php echo $pb['kod']; ?> (<?php echo $pb['sembol']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Fiyat Birimi -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Fiyat Birimi
                    </label>
                    <div class="flex rounded-lg overflow-hidden border border-dark-700 h-10">
                        <button type="button" onclick="setFiyatBirimi('kg')" id="btn_kg"
                            class="flex-1 font-semibold text-sm transition-all <?php echo $formData['fiyat_birimi'] == 'kg' ? 'bg-blue-500 text-white' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">kg</button>
                        <button type="button" onclick="setFiyatBirimi('ton')" id="btn_ton"
                            class="flex-1 font-semibold text-sm transition-all <?php echo $formData['fiyat_birimi'] == 'ton' ? 'bg-blue-500 text-white' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">ton</button>
                    </div>
                    <input type="hidden" name="fiyat_birimi" id="fiyat_birimi" value="<?php echo $formData['fiyat_birimi']; ?>">
                </div>
                
                <!-- Teslimat Sekli -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Teslim Sekli
                    </label>
                    <select name="teslimat_sekli_kodu" onchange="showTeslimatAciklama(this)"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500">
                        <?php foreach ($teslimatlar as $t): ?>
                        <option value="<?php echo $t['kod']; ?>" data-aciklama="<?php echo $t['aciklama']; ?>" <?php echo $formData['teslimat_sekli_kodu'] == $t['kod'] ? 'selected' : ''; ?>>
                            <?php echo $t['kod']; ?> - <?php echo $t['ad']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Teslimat Aciklama -->
            <div id="teslimat_aciklama" class="mb-4">
                <?php if ($formData['teslimat_sekli_kodu']):
                    $teslimat = array_filter($teslimatlar, fn($t) => $t['kod'] == $formData['teslimat_sekli_kodu']);
                    $teslimat = reset($teslimat);
                ?>
                <div class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-3 text-sm text-gray-400">
                    <span class="text-blue-400 font-bold"><?php echo $teslimat['kod']; ?></span> - <?php echo $teslimat['aciklama']; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Maliyet Tipi -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Maliyet Tipi
                    </label>
                    <div class="flex rounded-lg overflow-hidden border border-dark-700">
                        <button type="button" onclick="setMaliyetTipi('yuzde')" id="btn_yuzde"
                            class="flex-1 py-2.5 text-sm font-semibold transition-all <?php echo $formData['maliyet_tipi'] == 'yuzde' ? 'bg-blue-500 text-white' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">% Oran</button>
                        <button type="button" onclick="setMaliyetTipi('tutar')" id="btn_tutar"
                            class="flex-1 py-2.5 text-sm font-semibold transition-all <?php echo $formData['maliyet_tipi'] == 'tutar' ? 'bg-blue-500 text-white' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">Para Birimi</button>
                    </div>
                    <input type="hidden" name="maliyet_tipi" id="maliyet_tipi" value="<?php echo $formData['maliyet_tipi']; ?>">
                    
                    <!-- Maliyet Turu -->
                    <div class="flex rounded-lg overflow-hidden border border-dark-700 mt-2">
                        <button type="button" onclick="setMaliyetTuru('T')" id="btn_tahmini"
                            class="flex-1 py-2 text-xs font-bold transition-all <?php echo $formData['maliyet_turu'] == 'T' ? 'bg-amber-500/30 text-amber-400' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">T - Tahmini</button>
                        <button type="button" onclick="setMaliyetTuru('G')" id="btn_gercek"
                            class="flex-1 py-2 text-xs font-bold transition-all <?php echo $formData['maliyet_turu'] == 'G' ? 'bg-green-500/30 text-green-400' : 'bg-dark-900 text-gray-500 hover:bg-dark-700'; ?>">G - Gerceklesen</button>
                    </div>
                    <input type="hidden" name="maliyet_turu" id="maliyet_turu" value="<?php echo $formData['maliyet_turu']; ?>">
                </div>
                
                <!-- Maliyet Deger -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2" id="maliyet_label">
                        Maliyet (<?php echo $formData['maliyet_tipi'] == 'yuzde' ? '%' : 'Tutar'; ?>)
                    </label>
                    <div class="flex gap-2" id="maliyet_deger_container">
                        <input type="number" name="maliyet_deger" value="<?php echo $formData['maliyet_deger']; ?>" step="0.0001"
                            class="flex-1 bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-blue-500"
                            placeholder="0.00">
                        <?php if ($formData['maliyet_tipi'] == 'tutar'): ?>
                        <select name="maliyet_pb_kodu" class="w-24 bg-dark-900 border border-dark-700 rounded-lg px-2 py-2.5 text-white text-sm">
                            <?php foreach ($parabirimleri as $pb): ?>
                            <option value="<?php echo $pb['kod']; ?>" <?php echo $formData['maliyet_pb_kodu'] == $pb['kod'] ? 'selected' : ''; ?>><?php echo $pb['kod']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="hidden" name="maliyet_pb_kodu" value="">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Birim Varis Maliyeti (Hesaplanan) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Birim Varis Maliyeti (Otomatik Hesaplanir)
                    </label>
                    <div class="bg-dark-900 border border-green-500/30 rounded-lg px-4 py-2.5">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Toplam:</span>
                            <span class="text-green-400 font-bold text-lg">
                                <?php 
                                $birimFiyat = (float)$formData['birim_fiyat'];
                                $maliyet = (float)$formData['maliyet_deger'];
                                if ($formData['maliyet_tipi'] == 'yuzde') {
                                    $maliyet = $birimFiyat * ($maliyet / 100);
                                }
                                $toplam = $birimFiyat + $maliyet;
                                echo $toplam > 0 ? number_format($toplam, 2, ',', '.') : '-';
                                ?> <?php echo $formData['para_birimi_kodu']; ?>/<?php echo $formData['fiyat_birimi']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tuketim Verileri -->
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider">
                    📅 Yillara & Aylara Gore Tuketim Miktarlari
                </h2>
                <div class="flex gap-2">
                    <?php foreach (YILLAR as $yil): 
                        $yilRenkler = [2023 => 'blue', 2024 => 'purple', 2025 => 'green', 2026 => 'amber', 2027 => 'red'];
                        $renk = $yilRenkler[$yil] ?? 'gray';
                    ?>
                    <button type="button" onclick="showYil(<?php echo $yil; ?>)" id="btn_yil_<?php echo $yil; ?>"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?php echo $yil == 2025 ? "bg-{$renk}-900/30 text-{$renk}-400 border border-{$renk}-500" : 'bg-dark-700 text-gray-500 hover:bg-dark-600'; ?>">
                        <?php echo $yil; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php foreach (YILLAR as $yil): 
                $yilRenkler = [2023 => 'blue', 2024 => 'purple', 2025 => 'green', 2026 => 'amber', 2027 => 'red'];
                $renk = $yilRenkler[$yil] ?? 'gray';
            ?>
            <div id="yil_<?php echo $yil; ?>" class="yil-content <?php echo $yil != 2025 ? 'hidden' : ''; ?>">
                <div class="bg-dark-900/50 border border-<?php echo $renk; ?>-500/20 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 rounded-full bg-<?php echo $renk; ?>-400"></span>
                        <span class="font-bold text-<?php echo $renk; ?>-400"><?php echo $yil; ?> Tuketim Verileri</span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                        <?php foreach (AYLAR as $ayNo => $ayAd): 
                            $miktar = $tuketimVerileri[$yil][$ayNo] ?? '';
                        ?>
                        <div>
                            <label class="block text-xs text-center text-gray-500 font-semibold mb-1"><?php echo $ayAd; ?></label>
                            <input type="number" name="tuketim[<?php echo $yil; ?>][<?php echo $ayNo; ?>]" value="<?php echo $miktar; ?>" step="0.01"
                                class="w-full bg-dark-900 border border-<?php echo $renk; ?>-500/30 rounded-lg px-2 py-2 text-right text-sm text-white focus:outline-none focus:border-<?php echo $renk; ?>-400"
                                placeholder="kg">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php
                    // Yil ozeti hesapla
                    $yilVeriler = [];
                    foreach (AYLAR as $ayNo => $ayAd) {
                        if (!empty($tuketimVerileri[$yil][$ayNo])) {
                            $yilVeriler[] = $tuketimVerileri[$yil][$ayNo];
                        }
                    }
                    $girilenAy = count($yilVeriler);
                    $toplam = array_sum($yilVeriler);
                    $ortalama = $girilenAy > 0 ? $toplam / $girilenAy : 0;
                    $maks = $girilenAy > 0 ? max($yilVeriler) : 0;
                    ?>
                    
                    <div class="flex gap-6 mt-4 pt-4 border-t border-dark-700 text-sm">
                        <div>
                            <span class="text-gray-500">Girilen Ay:</span>
                            <span class="font-bold text-white ml-1"><?php echo $girilenAy; ?> / 12</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Toplam:</span>
                            <span class="font-bold text-<?php echo $renk; ?>-400 ml-1"><?php echo formatNumber($toplam, 0); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Ortalama:</span>
                            <span class="font-bold text-white ml-1"><?php echo formatNumber($ortalama, 0); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">En Yuksek:</span>
                            <span class="font-bold text-white ml-1"><?php echo formatNumber($maks, 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Butonlar -->
        <div class="flex justify-between items-center pt-4">
            <?php if ($isEdit): ?>
            <button type="submit" name="delete" onclick="return confirm('Bu hammaddeyi silmek istediginize emin misiniz?')"
                class="flex items-center gap-2 bg-red-500/10 hover:bg-red-500/20 border border-red-500/50 text-red-400 px-6 py-3 rounded-lg font-bold transition-colors">
                <i class="fas fa-trash"></i>Bu Hammaddeyi Sil
            </button>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
            
            <div class="flex gap-3">
                <a href="index.php" class="px-6 py-3 bg-dark-700 hover:bg-dark-600 text-gray-400 rounded-lg font-semibold transition-colors">
                    Iptal
                </a>
                <button type="submit" class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:opacity-90 text-white px-6 py-3 rounded-lg font-bold transition-opacity">
                    <i class="fas fa-save"></i>
                    <?php echo $isEdit ? 'Guncelle' : 'Kaydet'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Alternatif seciminde stok kodu zorunlulugu
function toggleAlternatif(value) {
    const stokKoduDiv = document.getElementById('stok_kodu_div');
    const stokKoduZorunlu = document.getElementById('stok_kodu_zorunlu');
    const stokKoduInput = document.querySelector('input[name="stok_kodu"]');
    
    if (value === 'A') {
        stokKoduDiv.style.opacity = '0.5';
        stokKoduZorunlu.style.display = 'none';
        stokKoduInput.removeAttribute('required');
    } else {
        stokKoduDiv.style.opacity = '1';
        stokKoduZorunlu.style.display = 'inline';
        stokKoduInput.setAttribute('required', 'required');
    }
}

// Yil goster/gizle
function showYil(yil) {
    // Tum yil content'lerini gizle
    document.querySelectorAll('.yil-content').forEach(el => el.classList.add('hidden'));
    // Secili yili goster
    document.getElementById('yil_' + yil).classList.remove('hidden');
    
    // Buton stillerini guncelle
    const yilRenkler = {2023: 'blue', 2024: 'purple', 2025: 'green', 2026: 'amber', 2027: 'red'};
    document.querySelectorAll('[id^="btn_yil_"]').forEach(btn => {
        btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-dark-700 text-gray-500 hover:bg-dark-600';
    });
    document.getElementById('btn_yil_' + yil).className = 
        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-' + yilRenkler[yil] + '-900/30 text-' + yilRenkler[yil] + '-400 border border-' + yilRenkler[yil] + '-500';
}

// Fiyat birimi secimi
function setFiyatBirimi(birim) {
    document.getElementById('fiyat_birimi').value = birim;
    document.getElementById('btn_kg').className = birim === 'kg' 
        ? 'flex-1 font-semibold text-sm transition-all bg-blue-500 text-white' 
        : 'flex-1 font-semibold text-sm transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
    document.getElementById('btn_ton').className = birim === 'ton' 
        ? 'flex-1 font-semibold text-sm transition-all bg-blue-500 text-white' 
        : 'flex-1 font-semibold text-sm transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
}

// Maliyet tipi secimi
function setMaliyetTipi(tip) {
    document.getElementById('maliyet_tipi').value = tip;
    document.getElementById('btn_yuzde').className = tip === 'yuzde' 
        ? 'flex-1 py-2.5 text-sm font-semibold transition-all bg-blue-500 text-white' 
        : 'flex-1 py-2.5 text-sm font-semibold transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
    document.getElementById('btn_tutar').className = tip === 'tutar' 
        ? 'flex-1 py-2.5 text-sm font-semibold transition-all bg-blue-500 text-white' 
        : 'flex-1 py-2.5 text-sm font-semibold transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
    
    // Label ve input guncelle
    document.getElementById('maliyet_label').textContent = tip === 'yuzde' ? 'Maliyet (%)' : 'Maliyet (Tutar)';
    
    // Para birimi secimi goster/gizle
    const container = document.getElementById('maliyet_deger_container');
    if (tip === 'tutar') {
        const pbSelect = document.createElement('select');
        pbSelect.name = 'maliyet_pb_kodu';
        pbSelect.className = 'w-24 bg-dark-900 border border-dark-700 rounded-lg px-2 py-2.5 text-white text-sm';
        pbSelect.innerHTML = `<?php foreach ($parabirimleri as $pb): ?><option value="<?php echo $pb['kod']; ?>"><?php echo $pb['kod']; ?></option><?php endforeach; ?>`;
        if (!container.querySelector('select')) {
            container.appendChild(pbSelect);
        }
    } else {
        const pbSelect = container.querySelector('select');
        if (pbSelect) pbSelect.remove();
    }
}

// Maliyet turu secimi
function setMaliyetTuru(tur) {
    document.getElementById('maliyet_turu').value = tur;
    document.getElementById('btn_tahmini').className = tur === 'T' 
        ? 'flex-1 py-2 text-xs font-bold transition-all bg-amber-500/30 text-amber-400' 
        : 'flex-1 py-2 text-xs font-bold transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
    document.getElementById('btn_gercek').className = tur === 'G' 
        ? 'flex-1 py-2 text-xs font-bold transition-all bg-green-500/30 text-green-400' 
        : 'flex-1 py-2 text-xs font-bold transition-all bg-dark-900 text-gray-500 hover:bg-dark-700';
}

// Paketleme aciklamasi goster
function showPaketlemeAciklama(select) {
    const option = select.options[select.selectedIndex];
    const aciklama = option.getAttribute('data-aciklama');
    const div = document.getElementById('paketleme_aciklama');
    
    if (aciklama) {
        div.innerHTML = `
            <div class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-2.5 text-xs text-gray-500 w-full">
                <span class="text-blue-400 font-bold">${option.text}</span><br>
                ${aciklama}
            </div>
        `;
    } else {
        div.innerHTML = '';
    }
}

// Teslimat aciklamasi goster
function showTeslimatAciklama(select) {
    const option = select.options[select.selectedIndex];
    const aciklama = option.getAttribute('data-aciklama');
    const kod = option.value;
    const div = document.getElementById('teslimat_aciklama');
    
    if (aciklama) {
        div.innerHTML = `
            <div class="bg-dark-900 border border-dark-700 rounded-lg px-4 py-3 text-sm text-gray-400">
                <span class="text-blue-400 font-bold">${kod}</span> - ${aciklama}
            </div>
        `;
    }
}

// Sayfa yuklenirken calistir
document.addEventListener('DOMContentLoaded', function() {
    toggleAlternatif('<?php echo $formData['sk']; ?>');
});
</script>

<?php require_once 'includes/footer.php'; ?>
