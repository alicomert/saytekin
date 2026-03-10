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

$aylar = ["Ocak","Şubat","Mart","Nisan","Mayıs","Haziran","Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık"];
$yillar = [2023, 2024, 2025, 2026];
?>

<!-- Ust Bilgi -->
<div style="margin-bottom:20px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px;">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <?php if ($hammadde['sk'] == 'S'): ?>
            <span style="background:#3b82f622;color:#60a5fa;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">S - Standart</span>
            <?php elseif ($hammadde['sk'] == 'K'): ?>
            <span style="background:#ef444422;color:#ef4444;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">K - Kapali</span>
            <?php else: ?>
            <span style="background:#eab30822;color:#eab308;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;">A - Alternatif</span>
            <?php endif; ?>
            <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $hammadde['tur_adi'] ?: '-'; ?></span>
        </div>
        <div style="font-size:20px;font-weight:700;color:#f1f5f9;"><?php echo $hammadde['hammadde_ismi']; ?></div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">
            Stok Kodu: <span style="font-family:monospace;color:#94a3b8;"><?php echo $hammadde['stok_kodu'] ?: '-'; ?></span> | 
            Urun Kodu: <span style="font-family:monospace;color:#94a3b8;"><?php echo $hammadde['urun_kodu'] ?: '-'; ?></span>
        </div>
    </div>
    
    <div style="display:flex;gap:8px;">
        <a href="index.php" class="btn-secondary">← Listeye Don</a>
        <a href="hammadde-form.php?id=<?php echo $id; ?>" class="btn-primary">✏️ Duzenle</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
    <!-- Sol Kolon - Temel Bilgiler -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <!-- Stok Durumu -->
        <div class="card">
            <div class="section-title">📊 Stok Durumu</div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div style="text-align:center;padding:16px;background:#0f1117;border-radius:8px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Mevcut Stok</div>
                    <div style="font-size:24px;font-weight:700;color:<?php echo $durum['renk']; ?>">
                        <?php echo number_format($hammadde['stok_miktari'], 0, ',', '.'); ?>
                        <span style="font-size:13px;font-weight:400;">kg</span>
                    </div>
                </div>
                <div style="text-align:center;padding:16px;background:#0f1117;border-radius:8px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Kalan Gun</div>
                    <div style="font-size:24px;font-weight:700;color:<?php echo $durum['renk']; ?>">
                        <?php echo $durum['kalan_gun'] !== null ? $durum['kalan_gun'] : '-'; ?>
                        <span style="font-size:13px;font-weight:400;">gun</span>
                    </div>
                </div>
            </div>
            
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:4px;">
                <span>Stok/Termin Orani: %<?php echo round(($durum['oran'] ?? 0) * 100); ?></span>
                <span>Durum: <span style="color:<?php echo $durum['renk']; ?>"><?php echo $durum['label'] ?: 'RAHAT'; ?></span></span>
            </div>
            <div style="height:6px;background:#0f1117;border-radius:3px;overflow:hidden;">
                <div style="height:100%;border-radius:3px;width:<?php echo min(100, ($durum['oran'] ?? 0) * 50); ?>%;background-color:<?php echo $durum['renk']; ?>"></div>
            </div>
            
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #1e2430;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                <div>
                    <span style="color:#64748b;">Optimum:</span>
                    <span style="color:#f1f5f9;margin-left:4px;"><?php echo number_format($hammadde['hesaplanan_optimum'], 0, ',', '.'); ?> kg</span>
                </div>
                <div>
                    <span style="color:#64748b;">Gunluk Tuketim:</span>
                    <span style="color:#f1f5f9;margin-left:4px;"><?php echo number_format($durum['gunluk_tuketim'], 1, ',', '.'); ?> kg</span>
                </div>
            </div>
        </div>
        
        <!-- Tedarikci Bilgileri -->
        <div class="card">
            <div class="section-title">🏢 Tedarikci Bilgileri</div>
            
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div>
                    <span style="font-size:11px;color:#64748b;display:block;">Tedarikci</span>
                    <span style="color:#f1f5f9;"><?php echo $hammadde['tedarikci'] ?: '-'; ?></span>
                </div>
                <div>
                    <span style="font-size:11px;color:#64748b;display:block;">Mensei Ulke</span>
                    <span style="color:#f1f5f9;"><?php echo $hammadde['ulke_adi'] ?: '-'; ?></span>
                </div>
                <div>
                    <span style="font-size:11px;color:#64748b;display:block;">Paketleme</span>
                    <span style="color:#f1f5f9;"><?php echo $hammadde['paketleme_adi'] ?: '-'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Termin Sureleri -->
        <div class="card">
            <div class="section-title">⏱️ Termin Sureleri</div>
            
            <?php
            $terminToplam = ($hammadde['akreditif_gun'] ?? 0) + ($hammadde['satici_tedarik_gun'] ?? 0) + ($hammadde['yol_gun'] ?? 0) + ($hammadde['depo_kabul_gun'] ?? 0);
            ?>
            
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#64748b;"><span style="color:#3b82f6;">●</span> Akreditif Acma</span>
                    <span style="font-weight:700;color:#f1f5f9;"><?php echo $hammadde['akreditif_gun'] ?: 0; ?> gun</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#64748b;"><span style="color:#8b5cf6;">●</span> Satici Tedarik</span>
                    <span style="font-weight:700;color:#f1f5f9;"><?php echo $hammadde['satici_tedarik_gun'] ?: 0; ?> gun</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#64748b;"><span style="color:#10b981;">●</span> Yol (Nakliye)</span>
                    <span style="font-weight:700;color:#f1f5f9;"><?php echo $hammadde['yol_gun'] ?: 0; ?> gun</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#64748b;"><span style="color:#f59e0b;">●</span> Depo Kabul</span>
                    <span style="font-weight:700;color:#f1f5f9;"><?php echo $hammadde['depo_kabul_gun'] ?: 0; ?> gun</span>
                </div>
                <div style="padding-top:8px;margin-top:8px;border-top:1px solid #1e2430;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:11px;font-weight:700;color:#64748b;">TOPLAM</span>
                    <span style="font-weight:700;color:#fbbf24;font-size:18px;"><?php echo $terminToplam; ?> gun</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orta Kolon - Fiyat Bilgileri -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <!-- Fiyat Bilgileri -->
        <div class="card">
            <div class="section-title">💰 Fiyat Bilgileri</div>
            
            <?php if ($hammadde['birim_fiyat'] > 0): 
                $varisMaliyet = hesaplaVarisMaliyeti($hammadde, $kurlar);
                $maliyet = hesaplaMaliyet($hammadde, $kurlar);
            ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#0f1117;border-radius:6px;">
                    <span style="color:#64748b;">Birim Fiyat</span>
                    <span style="font-weight:700;color:#60a5fa;"><?php echo number_format($hammadde['birim_fiyat'], 2, ',', '.'); ?> <?php echo $hammadde['para_birimi_kodu']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#0f1117;border-radius:6px;">
                    <span style="color:#64748b;">Fiyat Birimi</span>
                    <span style="font-weight:700;color:#f1f5f9;">/<?php echo $hammadde['fiyat_birimi']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#0f1117;border-radius:6px;">
                    <span style="color:#64748b;">Teslim Sekli</span>
                    <span style="font-weight:700;color:#60a5fa;"><?php echo $hammadde['teslimat_sekli_kodu']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#0f1117;border-radius:6px;">
                    <span style="color:#64748b;">Maliyet Turu</span>
                    <span style="font-weight:700;<?php echo $hammadde['maliyet_turu'] === 'G' ? 'color:#34d399;' : 'color:#fbbf24;' ?>">
                        <?php echo $hammadde['maliyet_turu'] === 'G' ? 'G - Gerceklesen' : 'T - Tahmini'; ?>
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#0f1117;border-radius:6px;">
                    <span style="color:#64748b;">Nakliye/Maliyet</span>
                    <span style="color:#a78bfa;">
                        <?php if ($hammadde['maliyet_tipi'] === 'yuzde'): ?>
                            %<?php echo $hammadde['maliyet_deger']; ?>
                        <?php else: ?>
                            <?php echo number_format($maliyet, 2, ',', '.'); ?> <?php echo $hammadde['para_birimi_kodu']; ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#10b9810a;border:1px solid #10b98144;border-radius:6px;">
                    <span style="color:#94a3b8;">Birim Varis Maliyeti</span>
                    <span style="font-weight:700;color:#34d399;font-size:18px;"><?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $hammadde['para_birimi_kodu']; ?>/<?php echo $hammadde['fiyat_birimi']; ?></span>
                </div>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:32px;color:#64748b;">
                <div style="font-size:32px;margin-bottom:8px;opacity:0.5;">🏷️</div>
                <div>Fiyat bilgisi girilmemis</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Fiyat Gecmisi -->
        <?php if (!empty($fiyatGecmisi)): ?>
        <div class="card">
            <div class="section-title">📈 Fiyat Gecmisi</div>
            
            <div style="display:flex;flex-direction:column;gap:6px;max-height:240px;overflow-y:auto;">
                <?php foreach (array_slice($fiyatGecmisi, 0, 10) as $fg): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:#0f1117;border-radius:6px;font-size:13px;">
                    <span style="color:#64748b;"><?php echo date('d.m.Y', strtotime($fg['kayit_tarihi'])); ?></span>
                    <span style="font-weight:700;color:#60a5fa;"><?php echo number_format($fg['birim_fiyat'], 2, ',', '.'); ?> <?php echo $fg['sembol']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sag Kolon - Tuketim Verileri -->
    <div>
        <div class="card">
            <div class="section-title">📅 Tuketim Verileri</div>
            
            <?php 
            $yilRenkler = [
                2023 => ['border' => '#3b82f6', 'text' => '#60a5fa'],
                2024 => ['border' => '#8b5cf6', 'text' => '#a78bfa'],
                2025 => ['border' => '#10b981', 'text' => '#34d399'],
                2026 => ['border' => '#f59e0b', 'text' => '#fbbf24']
            ];
            
            foreach ($yillar as $yil): 
                $renk = $yilRenkler[$yil];
                
                $yilVeriler = [];
                $toplam = 0;
                $sayac = 0;
                foreach ($aylar as $idx => $ayAd) {
                    $ayNo = $idx + 1;
                    $deger = $tuketimVerileri[$yil][$ayNo] ?? null;
                    $yilVeriler[$ayNo] = $deger;
                    if ($deger !== null) {
                        $toplam += $deger;
                        $sayac++;
                    }
                }
                $ortalama = $sayac > 0 ? $toplam / $sayac : 0;
            ?>
            <div style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $renk['border']; ?>"></span>
                    <span style="font-weight:700;color:<?php echo $renk['text']; ?>"><?php echo $yil; ?></span>
                    <span style="font-size:11px;color:#64748b;margin-left:auto;">Ort: <?php echo number_format($ortalama, 0, ',', '.'); ?> kg</span>
                </div>
                
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;font-size:11px;">
                    <?php foreach ($aylar as $idx => $ayAd): 
                        $ayNo = $idx + 1;
                        $deger = $yilVeriler[$ayNo];
                    ?>
                    <div style="background:#0f1117;border-radius:6px;padding:6px;text-align:center;">
                        <div style="color:#64748b;font-size:10px;margin-bottom:2px;"><?php echo substr($ayAd, 0, 3); ?></div>
                        <div style="font-family:monospace;<?php echo $deger !== null ? 'color:#f1f5f9;' : 'color:#374151;' ?>">
                            <?php echo $deger !== null ? number_format($deger, 0, ',', '.') : '-'; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #1e2430;display:flex;justify-content:space-between;font-size:11px;">
                    <span style="color:#64748b;">Toplam: <span style="color:<?php echo $renk['text']; ?>;font-weight:700;"><?php echo number_format($toplam, 0, ',', '.'); ?></span> kg</span>
                    <span style="color:#64748b;">Girilen: <?php echo $sayac; ?>/12 ay</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>