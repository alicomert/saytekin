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

// Yil renkleri
$YIL_RENKLER = [
    2023 => ['bg' => '#1a2535', 'border' => '#3b82f6', 'text' => '#60a5fa', 'dot' => '#3b82f6'],
    2024 => ['bg' => '#1a2535', 'border' => '#8b5cf6', 'text' => '#a78bfa', 'dot' => '#8b5cf6'],
    2025 => ['bg' => '#1a2535', 'border' => '#10b981', 'text' => '#34d399', 'dot' => '#10b981'],
    2026 => ['bg' => '#1a2535', 'border' => '#f59e0b', 'text' => '#fbbf24', 'dot' => '#f59e0b'],
];

$Aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
?>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0f1117; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; min-height: 100vh; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #141820; }
::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 3px; }
input, select { font-family: inherit; }
input:focus, select:focus { outline: none; border-color: #3b82f6 !important; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
@keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; transition:opacity 0.2s; }
.btn-primary:hover { opacity:0.88; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; transition:all 0.2s; }
.btn-secondary:hover { background:#252f40; color:#e2e8f0; }
.btn-danger { background:#2d1a1a; color:#ef4444; border:1px solid #ef444433; border-radius:8px; padding:8px 14px; cursor:pointer; font-size:13px; }
.btn-danger:hover { background:#3d2020; }
.field-label { display:block; font-size:11px; color:#64748b; margin-bottom:5px; letter-spacing:0.06em; text-transform:uppercase; font-weight:600; }
.field-input { width:100%; background:#0f1117; border:1px solid #1e2430; border-radius:8px; padding:9px 12px; color:#e2e8f0; font-size:13px; transition:border-color 0.2s; }
.field-input::placeholder { color:#334155; }
.section-title { font-size:12px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:#64748b; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #1e2430; }
.card { background:#141820; border:1px solid #1e2430; border-radius:12px; padding:20px; }
.month-input { width:100%; background:#0f1117; border:1px solid #1e2430; border-radius:6px; padding:7px 8px; color:#e2e8f0; font-size:12px; text-align:right; }
.month-input::placeholder { color:#2d3748; }
.year-header { font-size:13px; font-weight:700; color:#f1f5f9; margin-bottom:10px; padding:6px 10px; border-radius:6px; display:inline-block; }
.form-container { max-width: 1400px; margin: 0 auto; padding: 24px 28px; animation: fadeIn 0.3s; }
.form-header { margin-bottom: 20px; }
.form-header h1 { font-size: 20px; font-weight: 700; color: #f1f5f9; }
.form-header p { color: #475569; font-size: 13px; margin-top: 4px; }
.toggle-btn { flex: 1; border: none; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.15s; }
.toggle-btn.active { background: #3b82f6; color: #fff; }
.toggle-btn:not(.active) { background: #141820; color: #64748b; }
.toggle-btn:not(.active):hover { background: #1e2430; }
.year-tab { padding: 8px 18px; border-radius: 8px; border: 2px solid; cursor: pointer; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
.year-tab.active { background: #1a2535; }
.year-tab:not(.active) { background: transparent; border-color: #1e2430 !important; color: #4b5563 !important; }
.year-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
</style>

<div class="form-container">
    <div class="form-header">
        <h1><?php echo $isEdit ? '✏️ Hammadde Düzenle' : '➕ Yeni Hammadde Ekle'; ?></h1>
        <p>Zorunlu alanları (*) doldurun, tüketim verilerini yıl sekmelerinden girin.</p>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div style="background: #2d1a1a; border: 1px solid #ef444433; color: #ef4444; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
        <?php foreach ($errors as $error): ?>
        <div style="font-size: 13px;"><span style="margin-right: 6px;">⚠️</span><?php echo $error; ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Temel Bilgiler -->
        <div class="card" style="margin-bottom: 16px;">
            <div class="section-title">📋 Temel Bilgiler</div>
            
            <div style="display: grid; grid-template-columns: 80px 1fr 1fr 1fr 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                <!-- S/K -->
                <div>
                    <label class="field-label">S / K <span style="color:#ef4444;margin-left:3px;">*</span></label>
                    <select name="sk" class="field-input" onchange="toggleAlternatif(this.value)" style="padding: 9px 8px;">
                        <option value="S" <?php echo $formData['sk'] == 'S' ? 'selected' : ''; ?>>S — Standart</option>
                        <option value="K" <?php echo $formData['sk'] == 'K' ? 'selected' : ''; ?>>K — Kapalı</option>
                        <option value="A" <?php echo $formData['sk'] == 'A' ? 'selected' : ''; ?>>A — Alternatif</option>
                    </select>
                </div>
                
                <!-- Stok Kodu -->
                <div id="stok_kodu_div">
                    <label class="field-label">Stok Kodu <span id="stok_kodu_zorunlu" style="color:#ef4444;margin-left:3px;">*</span></label>
                    <input type="text" name="stok_kodu" value="<?php echo htmlspecialchars($formData['stok_kodu']); ?>" 
                        class="field-input" placeholder="ör: 16">
                </div>
                
                <!-- Urun Kodu -->
                <div>
                    <label class="field-label">Ürün Kodu</label>
                    <input type="text" name="urun_kodu" value="<?php echo htmlspecialchars($formData['urun_kodu']); ?>" 
                        class="field-input" placeholder="ör: 540004">
                </div>
                
                <!-- Tur -->
                <div>
                    <label class="field-label">Tür</label>
                    <select name="tur_kodu" class="field-input">
                        <option value="">-- Seçin --</option>
                        <?php foreach ($turler as $tur): ?>
                        <option value="<?php echo $tur['kod']; ?>" <?php echo $formData['tur_kodu'] == $tur['kod'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tur['ad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Hammadde Ismi -->
                <div>
                    <label class="field-label">Hammadde İsmi <span style="color:#ef4444;margin-left:3px;">*</span></label>
                    <input type="text" name="hammadde_ismi" value="<?php echo htmlspecialchars($formData['hammadde_ismi']); ?>" required
                        class="field-input" placeholder="ör: KUVARS 45M MATEL – ALM.">
                </div>
                
                <!-- Tedarikci -->
                <div>
                    <label class="field-label">Tedarikçi</label>
                    <input type="text" name="tedarikci" value="<?php echo htmlspecialchars($formData['tedarikci']); ?>" 
                        class="field-input" placeholder="ör: MATEL A.Ş.">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <!-- Menşei Ülke -->
                <div>
                    <label class="field-label">Menşei Ülke</label>
                    <select name="mensei_ulke_id" class="field-input">
                        <option value="">-- Ülke Seçin --</option>
                        <?php foreach ($ulkeler as $ulke): ?>
                        <option value="<?php echo $ulke['id']; ?>" <?php echo $formData['mensei_ulke_id'] == $ulke['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ulke['ad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Paketleme -->
                <div>
                    <label class="field-label">Paketleme Tipi</label>
                    <select name="paketleme_kodu" class="field-input" onchange="showPaketlemeAciklama(this)">
                        <option value="">-- Seçin --</option>
                        <?php foreach ($paketlemeler as $p): ?>
                        <option value="<?php echo $p['kod']; ?>" data-aciklama="<?php echo htmlspecialchars($p['aciklama']); ?>" 
                            <?php echo $formData['paketleme_kodu'] == $p['kod'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['ad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Paketleme Aciklama -->
                <div id="paketleme_aciklama" style="display: flex; align-items: flex-end;">
                    <?php if ($formData['paketleme_kodu']): 
                        $paketleme = array_filter($paketlemeler, fn($p) => $p['kod'] == $formData['paketleme_kodu']);
                        $paketleme = reset($paketleme);
                    ?>
                    <div style="background: #0f1117; border: 1px solid #1e2430; border-radius: 8px; padding: 9px 12px; font-size: 11px; color: #64748b; line-height: 1.5; width: 100%;">
                        <span style="color: #60a5fa; font-weight: 700;"><?php echo htmlspecialchars($paketleme['ad']); ?></span><br>
                        <?php echo htmlspecialchars($paketleme['aciklama']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <!-- Stok Miktarı -->
                <div>
                    <label class="field-label">Stok Miktarı (kg)</label>
                    <input type="number" name="stok_miktari" value="<?php echo $formData['stok_miktari']; ?>" step="0.01"
                        class="field-input" placeholder="0">
                </div>
                
                <!-- Hesaplanan Optimum -->
                <div>
                    <label class="field-label">Hesaplanan Optimum</label>
                    <input type="number" name="hesaplanan_optimum" value="<?php echo $formData['hesaplanan_optimum']; ?>" step="0.01"
                        class="field-input" placeholder="0">
                </div>
            </div>
            
            <!-- Termin Süresi - 4 parça -->
            <div style="background: #0f1117; border: 1px solid #1e2430; border-radius: 10px; padding: 16px; margin-top: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 12px;">
                    ⏱ Termin Süresi (gün cinsinden 4 aşama)
                    <?php 
                    $toplamGun = (int)$formData['akreditif_gun'] + (int)$formData['satici_tedarik_gun'] + (int)$formData['yol_gun'] + (int)$formData['depo_kabul_gun'];
                    if ($toplamGun > 0): 
                    ?>
                    <span style="margin-left: 10px; color: #fbbf24; font-weight: 700;">Toplam: <?php echo $toplamGun; ?> gün</span>
                    <?php endif; ?>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 10px; color: #3b82f6; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.06em;">🏦 1. Akreditif Açma</label>
                        <input type="number" name="akreditif_gun" value="<?php echo $formData['akreditif_gun']; ?>" min="0"
                            class="field-input" placeholder="gün" style="text-align: center;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 10px; color: #8b5cf6; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.06em;">🏭 2. Satıcı Tedarik</label>
                        <input type="number" name="satici_tedarik_gun" value="<?php echo $formData['satici_tedarik_gun']; ?>" min="0"
                            class="field-input" placeholder="gün" style="text-align: center;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 10px; color: #10b981; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.06em;">🚢 3. Yol (Nakliye)</label>
                        <input type="number" name="yol_gun" value="<?php echo $formData['yol_gun']; ?>" min="0"
                            class="field-input" placeholder="gün" style="text-align: center;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 10px; color: #f59e0b; margin-bottom: 5px; font-weight: 700; letter-spacing: 0.06em;">🏗️ 4. Depo Kabul</label>
                        <input type="number" name="depo_kabul_gun" value="<?php echo $formData['depo_kabul_gun']; ?>" min="0"
                            class="field-input" placeholder="gün" style="text-align: center;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fiyat Bilgileri -->
        <div class="card" style="margin-bottom: 16px;">
            <div class="section-title">💰 Fiyat & Maliyet Bilgileri</div>
            
            <div style="display: grid; grid-template-columns: 1fr 120px 80px 1fr; gap: 14px; margin-bottom: 14px;">
                <!-- Birim Fiyat -->
                <div>
                    <label class="field-label">Hammadde Birim Fiyatı</label>
                    <input type="number" name="birim_fiyat" value="<?php echo $formData['birim_fiyat']; ?>" step="0.0001"
                        class="field-input" placeholder="0.00">
                </div>
                
                <!-- Para Birimi -->
                <div>
                    <label class="field-label">Para Birimi</label>
                    <select name="para_birimi_kodu" class="field-input">
                        <?php foreach ($parabirimleri as $pb): ?>
                        <option value="<?php echo $pb['kod']; ?>" <?php echo $formData['para_birimi_kodu'] == $pb['kod'] ? 'selected' : ''; ?>>
                            <?php echo $pb['kod']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Fiyat Birimi -->
                <div>
                    <label class="field-label">Fiyat/Birim</label>
                    <div style="display: flex; gap: 0; border-radius: 8px; overflow: hidden; border: 1px solid #1e2430; height: 40px;">
                        <button type="button" onclick="setFiyatBirimi('kg')" id="btn_kg"
                            class="toggle-btn <?php echo $formData['fiyat_birimi'] == 'kg' ? 'active' : ''; ?>">kg</button>
                        <button type="button" onclick="setFiyatBirimi('ton')" id="btn_ton"
                            class="toggle-btn <?php echo $formData['fiyat_birimi'] == 'ton' ? 'active' : ''; ?>">ton</button>
                    </div>
                    <input type="hidden" name="fiyat_birimi" id="fiyat_birimi" value="<?php echo $formData['fiyat_birimi']; ?>">
                </div>
                
                <!-- Teslimat Sekli -->
                <div>
                    <label class="field-label">Teslim Şekli</label>
                    <select name="teslimat_sekli_kodu" class="field-input" onchange="showTeslimatAciklama(this)">
                        <?php foreach ($teslimatlar as $t): ?>
                        <option value="<?php echo $t['kod']; ?>" data-aciklama="<?php echo htmlspecialchars($t['aciklama']); ?>" 
                            <?php echo $formData['teslimat_sekli_kodu'] == $t['kod'] ? 'selected' : ''; ?>>
                            <?php echo $t['kod']; ?> — <?php echo htmlspecialchars($t['ad']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Teslimat Aciklama -->
            <div id="teslimat_aciklama" style="margin-bottom: 14px;">
                <?php if ($formData['teslimat_sekli_kodu']):
                    $teslimat = array_filter($teslimatlar, fn($t) => $t['kod'] == $formData['teslimat_sekli_kodu']);
                    $teslimat = reset($teslimat);
                ?>
                <div style="background: #0f1117; border: 1px solid #1e2430; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b; line-height: 1.6;">
                    <span style="color: #3b82f6; font-weight: 700;"><?php echo $teslimat['kod']; ?></span> — <?php echo htmlspecialchars($teslimat['aciklama']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 180px 1fr 1fr; gap: 14px; align-items: end;">
                <!-- Maliyet Tipi -->
                <div>
                    <label class="field-label">Maliyet Tipi</label>
                    <div style="display: flex; gap: 0; border-radius: 8px; overflow: hidden; border: 1px solid #1e2430; margin-bottom: 6px;">
                        <button type="button" onclick="setMaliyetTipi('yuzde')" id="btn_yuzde"
                            class="toggle-btn <?php echo $formData['maliyet_tipi'] == 'yuzde' ? 'active' : ''; ?>">% Oran</button>
                        <button type="button" onclick="setMaliyetTipi('tutar')" id="btn_tutar"
                            class="toggle-btn <?php echo $formData['maliyet_tipi'] == 'tutar' ? 'active' : ''; ?>">Para Birimi</button>
                    </div>
                    <input type="hidden" name="maliyet_tipi" id="maliyet_tipi" value="<?php echo $formData['maliyet_tipi']; ?>">
                    
                    <div style="display: flex; gap: 0; border-radius: 6px; overflow: hidden; border: 1px solid #1e2430;">
                        <button type="button" onclick="setMaliyetTuru('T')" id="btn_tahmini"
                            style="flex: 1; padding: 6px 8px; border: none; cursor: pointer; font-size: 11px; font-weight: 700; transition: all 0.15s; border-right: 1px solid #1e2430; background: <?php echo $formData['maliyet_turu'] == 'T' ? '#f59e0b33' : '#141820'; ?>; color: <?php echo $formData['maliyet_turu'] == 'T' ? '#f59e0b' : '#64748b'; ?>;">
                            T — Tahmini
                        </button>
                        <button type="button" onclick="setMaliyetTuru('G')" id="btn_gercek"
                            style="flex: 1; padding: 6px 8px; border: none; cursor: pointer; font-size: 11px; font-weight: 700; transition: all 0.15s; background: <?php echo $formData['maliyet_turu'] == 'G' ? '#10b98133' : '#141820'; ?>; color: <?php echo $formData['maliyet_turu'] == 'G' ? '#34d399' : '#64748b'; ?>;">
                            G — Gerçekleşen
                        </button>
                    </div>
                    <input type="hidden" name="maliyet_turu" id="maliyet_turu" value="<?php echo $formData['maliyet_turu']; ?>">
                </div>
                
                <!-- Maliyet Deger -->
                <div>
                    <label class="field-label" id="maliyet_label">
                        <?php echo $formData['maliyet_tipi'] == 'yuzde' ? 'Hammadde Maliyeti (% — birim fiyat üzerinden)' : 'Hammadde Maliyeti (Tutar)'; ?>
                    </label>
                    <div id="maliyet_deger_container" style="display: flex; gap: 6;">
                        <input type="number" name="maliyet_deger" value="<?php echo $formData['maliyet_deger']; ?>" step="0.0001"
                            class="field-input" placeholder="<?php echo $formData['maliyet_tipi'] == 'yuzde' ? '%0.00' : '0.00'; ?>" style="flex: 2;">
                        <?php if ($formData['maliyet_tipi'] == 'tutar'): ?>
                        <select name="maliyet_pb_kodu" class="field-input" style="flex: 1;">
                            <?php foreach ($parabirimleri as $pb): ?>
                            <option value="<?php echo $pb['kod']; ?>" <?php echo $formData['maliyet_pb_kodu'] == $pb['kod'] ? 'selected' : ''; ?>><?php echo $pb['kod']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="hidden" name="maliyet_pb_kodu" value="">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Birim Varis Maliyeti -->
                <div>
                    <label class="field-label">Birim Varış Maliyeti (<?php echo $formData['fiyat_birimi'] == 'kg' ? '/kg' : '/ton'; ?>)</label>
                    <div style="background: #0f1117; border: 1px solid #10b98144; border-radius: 8px; padding: 9px 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4;">
                            <?php 
                            $birimFiyat = (float)$formData['birim_fiyat'];
                            $maliyet = (float)$formData['maliyet_deger'];
                            $maliyetDeger = $formData['maliyet_tipi'] == 'yuzde' ? $birimFiyat * ($maliyet / 100) : $maliyet;
                            $toplam = $birimFiyat + $maliyetDeger;
                            ?>
                            <span style="font-size: 11px; color: #475569;">
                                <?php echo $birimFiyat > 0 ? number_format($birimFiyat, 2, ',', '.') . ' ' . $formData['para_birimi_kodu'] . ' + ' . number_format($maliyetDeger, 2, ',', '.') . ' ' . $formData['para_birimi_kodu'] : '—'; ?>
                            </span>
                            <span style="color: #34d399; font-weight: 700; font-size: 15px;">
                                <?php echo $toplam > 0 ? number_format($toplam, 2, ',', '.') . ' ' . $formData['para_birimi_kodu'] . '/' . $formData['fiyat_birimi'] : '—'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tuketim Tablosu -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <div class="section-title" style="margin-bottom: 0;">📅 Yıllara & Aylara Göre Tüketim Miktarları</div>
            </div>
            
            <!-- Yıl Sekmeleri -->
            <div style="display: flex; gap: 8; margin-bottom: 18px; margin-top: 16px;">
                <?php 
                $aktifYil = 2025;
                foreach (YILLAR as $yil): 
                    $renk = $YIL_RENKLER[$yil];
                    $isActive = $yil == $aktifYil;
                    
                    // Yıl ortalamasını hesapla
                    $yilVeriler = [];
                    foreach (AYLAR as $ayNo => $ayAd) {
                        if (!empty($tuketimVerileri[$yil][$ayNo])) {
                            $yilVeriler[] = $tuketimVerileri[$yil][$ayNo];
                        }
                    }
                    $ortalama = count($yilVeriler) > 0 ? array_sum($yilVeriler) / count($yilVeriler) : 0;
                ?>
                <button type="button" onclick="showYil(<?php echo $yil; ?>)" id="btn_yil_<?php echo $yil; ?>"
                    class="year-tab <?php echo $isActive ? 'active' : ''; ?>"
                    style="border-color: <?php echo $renk['border']; ?>; background: <?php echo $isActive ? $renk['bg'] : 'transparent'; ?>; color: <?php echo $isActive ? $renk['text'] : '#4b5563'; ?>;">
                    <span class="year-dot" style="background: <?php echo $isActive ? $renk['dot'] : '#374151'; ?>"></span>
                    <?php echo $yil; ?>
                    <?php if ($ortalama > 0): ?>
                    <span style="font-size: 11px; opacity: 0.75;">ort: <?php echo number_format($ortalama, 0, ',', '.'); ?></span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
            
            <?php foreach (YILLAR as $yil): 
                $renk = $YIL_RENKLER[$yil];
                $isActive = $yil == $aktifYil;
            ?>
            <div id="yil_<?php echo $yil; ?>" class="yil-content" style="<?php echo $isActive ? '' : 'display: none;'; ?>">
                <div style="display: inline-block; padding: 4px 12px; border-radius: 6px; margin-bottom: 14px; background: <?php echo $renk['bg']; ?>; border: 1px solid <?php echo $renk['border']; ?>33;">
                    <span class="year-header" style="color: <?php echo $renk['text']; ?>;"><?php echo $yil; ?> Tüketim Verileri</span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;">
                    <?php foreach (AYLAR as $ayNo => $ayAd): 
                        $miktar = $tuketimVerileri[$yil][$ayNo] ?? '';
                    ?>
                    <div>
                        <label style="display: block; font-size: 11px; color: #4b5563; margin-bottom: 4px; text-align: center; font-weight: 600;"><?php echo $ayAd; ?></label>
                        <input type="number" name="tuketim[<?php echo $yil; ?>][<?php echo $ayNo; ?>]" value="<?php echo $miktar; ?>" step="0.01"
                            class="month-input" placeholder="—" style="border-color: <?php echo $renk['border']; ?>44;">
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Yıl özeti -->
                <div style="margin-top: 16px; background: #0f1117; border-radius: 8px; padding: 12px 16px; display: flex; gap: 24; flex-wrap: wrap;">
                    <?php
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
                    <div>
                        <span style="font-size: 11px; color: #4b5563;">Girilen Ay</span>
                        <div style="font-weight: 700; color: #f1f5f9;"><?php echo $girilenAy; ?> / 12</div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #4b5563;">Toplam</span>
                        <div style="font-weight: 700; color: <?php echo $renk['text']; ?>"><?php echo number_format($toplam, 0, ',', '.'); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #4b5563;">Ortalama</span>
                        <div style="font-weight: 700; color: #f1f5f9;"><?php echo number_format($ortalama, 0, ',', '.'); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #4b5563;">En Yüksek</span>
                        <div style="font-weight: 700; color: #f1f5f9;"><?php echo number_format($maks, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Butonlar -->
        <div style="display: flex; gap: 10px; margin-top: 16px; justify-content: space-between; align-items: center;">
            <?php if ($isEdit): ?>
            <button type="submit" name="delete" onclick="return confirm('Bu hammaddeyi silmek istediğinize emin misiniz?')"
                class="btn-danger" style="padding: 9px 18px; font-weight: 700; display: flex; align-items: center; gap: 7;">
                🗑️ Bu Hammaddeyi Sil
            </button>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center;">İptal</a>
                <button type="submit" class="btn-primary">
                    <?php echo $isEdit ? '✏️ Güncelle' : '💾 Kaydet'; ?>
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
    const yilRenkler = {
        2023: {bg:'#1a2535', border:'#3b82f6', text:'#60a5fa', dot:'#3b82f6'},
        2024: {bg:'#1a2535', border:'#8b5cf6', text:'#a78bfa', dot:'#8b5cf6'},
        2025: {bg:'#1a2535', border:'#10b981', text:'#34d399', dot:'#10b981'},
        2026: {bg:'#1a2535', border:'#f59e0b', text:'#fbbf24', dot:'#f59e0b'}
    };
    
    // Tum yil content'lerini gizle
    document.querySelectorAll('.yil-content').forEach(el => el.style.display = 'none');
    // Secili yili goster
    document.getElementById('yil_' + yil).style.display = 'block';
    
    // Buton stillerini guncelle
    document.querySelectorAll('[id^="btn_yil_"]').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.borderColor = '#1e2430';
        btn.style.color = '#4b5563';
        btn.querySelector('.year-dot').style.background = '#374151';
    });
    
    const activeBtn = document.getElementById('btn_yil_' + yil);
    activeBtn.classList.add('active');
    activeBtn.style.background = yilRenkler[yil].bg;
    activeBtn.style.borderColor = yilRenkler[yil].border;
    activeBtn.style.color = yilRenkler[yil].text;
    activeBtn.querySelector('.year-dot').style.background = yilRenkler[yil].dot;
}

// Fiyat birimi secimi
function setFiyatBirimi(birim) {
    document.getElementById('fiyat_birimi').value = birim;
    document.getElementById('btn_kg').className = birim === 'kg' 
        ? 'toggle-btn active' 
        : 'toggle-btn';
    document.getElementById('btn_ton').className = birim === 'ton' 
        ? 'toggle-btn active' 
        : 'toggle-btn';
}

// Maliyet tipi secimi
function setMaliyetTipi(tip) {
    document.getElementById('maliyet_tipi').value = tip;
    document.getElementById('btn_yuzde').className = tip === 'yuzde' 
        ? 'toggle-btn active' 
        : 'toggle-btn';
    document.getElementById('btn_tutar').className = tip === 'tutar' 
        ? 'toggle-btn active' 
        : 'toggle-btn';
    
    // Label guncelle
    document.getElementById('maliyet_label').textContent = tip === 'yuzde' 
        ? 'Hammadde Maliyeti (% — birim fiyat üzerinden)' 
        : 'Hammadde Maliyeti (Tutar)';
    
    // Para birimi secimi goster/gizle
    const container = document.getElementById('maliyet_deger_container');
    const input = container.querySelector('input[name="maliyet_deger"]');
    
    if (tip === 'tutar') {
        input.placeholder = '0.00';
        input.style.flex = '2';
        if (!container.querySelector('select')) {
            const pbSelect = document.createElement('select');
            pbSelect.name = 'maliyet_pb_kodu';
            pbSelect.className = 'field-input';
            pbSelect.style.flex = '1';
            pbSelect.innerHTML = `<?php foreach ($parabirimleri as $pb): ?><option value="<?php echo $pb['kod']; ?>"><?php echo $pb['kod']; ?></option><?php endforeach; ?>`;
            container.appendChild(pbSelect);
        }
    } else {
        input.placeholder = '%0.00';
        input.style.flex = '1';
        const pbSelect = container.querySelector('select');
        if (pbSelect) pbSelect.remove();
    }
}

// Maliyet turu secimi
function setMaliyetTuru(tur) {
    document.getElementById('maliyet_turu').value = tur;
    const btnTahmini = document.getElementById('btn_tahmini');
    const btnGercek = document.getElementById('btn_gercek');
    
    if (tur === 'T') {
        btnTahmini.style.background = '#f59e0b33';
        btnTahmini.style.color = '#f59e0b';
        btnGercek.style.background = '#141820';
        btnGercek.style.color = '#64748b';
    } else {
        btnTahmini.style.background = '#141820';
        btnTahmini.style.color = '#64748b';
        btnGercek.style.background = '#10b98133';
        btnGercek.style.color = '#34d399';
    }
}

// Paketleme aciklamasi goster
function showPaketlemeAciklama(select) {
    const option = select.options[select.selectedIndex];
    const aciklama = option.getAttribute('data-aciklama');
    const div = document.getElementById('paketleme_aciklama');
    
    if (aciklama) {
        div.innerHTML = `
            <div style="background: #0f1117; border: 1px solid #1e2430; border-radius: 8px; padding: 9px 12px; font-size: 11px; color: #64748b; line-height: 1.5; width: 100%;">
                <span style="color: #60a5fa; font-weight: 700;">${option.text}</span><br>
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
            <div style="background: #0f1117; border: 1px solid #1e2430; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b; line-height: 1.6;">
                <span style="color: #3b82f6; font-weight: 700;">${kod}</span> — ${aciklama}
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
