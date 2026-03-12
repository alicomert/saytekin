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

$YIL_RENKLER = [
    2023 => ['border' => '#3b82f6', 'text' => '#60a5fa', 'bg' => '#1a2535'],
    2024 => ['border' => '#8b5cf6', 'text' => '#a78bfa', 'bg' => '#1a2535'],
    2025 => ['border' => '#10b981', 'text' => '#34d399', 'bg' => '#1a2535'],
    2026 => ['border' => '#f59e0b', 'text' => '#fbbf24', 'bg' => '#1a2535'],
];

$aktifYil = isset($_GET['yil']) ? (int)$_GET['yil'] : 2025;
$detayTab = $_GET['tab'] ?? 'genel';

$terminToplam = ($hammadde['akreditif_gun'] ?? 0) + ($hammadde['satici_tedarik_gun'] ?? 0) + ($hammadde['yol_gun'] ?? 0) + ($hammadde['depo_kabul_gun'] ?? 0);

$varisMaliyet = hesaplaVarisMaliyeti($hammadde, $kurlar);
$maliyet = hesaplaMaliyet($hammadde, $kurlar);

$stokKg = (float)$hammadde['stok_miktari'];
$birim = $hammadde['fiyat_birimi'] ?? 'ton';
$stokBirimde = $birim === 'ton' ? $stokKg / 1000 : $stokKg;
$toplamBedel = $varisMaliyet * $stokBirimde;

$s12Veriler = [];
foreach ($aylar as $idx => $ay) {
    $deger = $tuketimVerileri[2025][$idx + 1] ?? 0;
    // 0 değerli aylar da dahil ediliyor (sadece null/boş olmayanlar)
    $s12Veriler[] = ['etiket' => substr($ay, 0, 3) . ' \'25', 'deger' => $deger];
}
foreach (['Ocak', 'Şubat'] as $ay) {
    $deger = $tuketimVerileri[2026][array_search($ay, $aylar) + 1] ?? 0;
    $s12Veriler[] = ['etiket' => substr($ay, 0, 3) . ' \'26', 'deger' => $deger];
}
$s12Veriler = array_slice($s12Veriler, -12);
$topS12 = array_sum(array_column($s12Veriler, 'deger'));
// Her zaman 12 ay baz alınarak hesaplama yapılıyor
$gunlukTuketim = count($s12Veriler) > 0 ? $topS12 / (12 * 30) : 0;

$optKgGirilen = (float)$hammadde['hesaplanan_optimum'];
$optKg = $optKgGirilen / 2;
$kalanGun = $gunlukTuketim > 0 ? round($stokKg / $gunlukTuketim) : null;
$optimumGun = $gunlukTuketim > 0 ? ($stokKg > $optKg ? round(($stokKg - $optKg) / $gunlukTuketim) : 0) : null;

function tarihHesapla($gun) {
    if ($gun === null) return null;
    $d = new DateTime();
    $d->modify("+{$gun} days");
    // Türkçe ay isimleri
    $aylarIng = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $aylarTr = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $ay = (int)$d->format('n') - 1; // 0-11 arası
    return $d->format('d') . ' ' . $aylarTr[$ay] . ' ' . $d->format('Y');
}

$tukenmeTarih = tarihHesapla($kalanGun);
$optimumTarih = tarihHesapla($optimumGun);

$minMiktar = $gunlukTuketim > 0 && $terminToplam > 0 ? round($gunlukTuketim * $terminToplam) : 0;
$optMiktar = $gunlukTuketim > 0 && $terminToplam > 0 ? round($gunlukTuketim * $terminToplam * 2) : 0;
$maksMiktar = $minMiktar + $optMiktar;

$TESLIMAT_SEKILLERI = [
    'EXW' => 'Ex Works', 'FCA' => 'Free Carrier', 'CPT' => 'Carriage Paid To',
    'CIP' => 'Carriage & Insurance Paid', 'DAP' => 'Delivered at Place',
    'DPU' => 'Delivered at Place Unloaded', 'DDP' => 'Delivered Duty Paid',
    'FAS' => 'Free Alongside Ship', 'FOB' => 'Free on Board',
    'CFR' => 'Cost & Freight', 'CIF' => 'Cost, Insurance & Freight',
];

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
.card { background: #141820; border: 1px solid #1e2430; border-radius: 12px; padding: 20px; }
.section-title { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #1e2430; }
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <span style="padding:3px 10px;border-radius:5px;font-size:12px;font-weight:700;
                    background:<?php echo $hammadde['sk']=='S'?'#1d3557':($hammadde['sk']=='A'?'#221a05':'#2d1a1a'); ?>;
                    color:<?php echo $hammadde['sk']=='S'?'#60a5fa':($hammadde['sk']=='A'?'#fbbf24':'#f87171'); ?>;">
                    <?php echo $hammadde['sk']=='S'?'S — Standart':($hammadde['sk']=='A'?'A — Alternatif':'K — Kapalı'); ?>
                </span>
                <span style="background:#1e2430;padding:3px 10px;border-radius:4px;font-size:12px;color:#94a3b8;"><?php echo $hammadde['tur_adi'] ?? '-'; ?></span>
            </div>
            <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin:0;"><?php echo htmlspecialchars($hammadde['hammadde_ismi']); ?></h2>
            <p style="color:#475569;font-size:13px;margin-top:2px;">Stok: <?php echo $hammadde['stok_kodu'] ?? '-'; ?> · Ürün: <?php echo $hammadde['urun_kodu'] ?? '-'; ?></p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <?php 
            $fiGec = !empty($fiyatGecmisi) ? ' (' . count($fiyatGecmisi) . ')' : '';
            ?>
            <button onclick="setTab('genel')" style="padding:8px 16px;border-radius:7px;border:1px solid;font-size:12px;font-weight:700;cursor:pointer;
                border-color:<?php echo $detayTab==='genel'?'#3b82f6':'#1e2430'; ?>;
                background:<?php echo $detayTab==='genel'?'#1d3557':'transparent'; ?>;
                color:<?php echo $detayTab==='genel'?'#60a5fa':'#64748b'; ?>;">
                📋 Genel Bilgiler
            </button>
            <button onclick="setTab('fiyatGecmisi')" style="padding:8px 16px;border-radius:7px;border:1px solid;font-size:12px;font-weight:700;cursor:pointer;
                border-color:<?php echo $detayTab==='fiyatGecmisi'?'#3b82f6':'#1e2430'; ?>;
                background:<?php echo $detayTab==='fiyatGecmisi'?'#1d3557':'transparent'; ?>;
                color:<?php echo $detayTab==='fiyatGecmisi'?'#60a5fa':'#64748b'; ?>;">
                📋 Fiyat Geçmişi<?php echo $fiGec; ?>
            </button>
            <a href="hammadde-form.php?id=<?php echo $id; ?>" class="btn-primary">✏️ Düzenle</a>
        </div>
    </div>

    <?php if ($detayTab === 'genel'): ?>
    <!-- Özet Kartlar -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;">
        <?php 
        $sp = $hammadde['siparis_verildi'] ?? false;
        $spGeldi = $hammadde['siparis_geldi'] ?? false;
        $spNo = $hammadde['siparis_no'] ?? '';
        $spMiktar = $hammadde['siparis_miktar'] ?? 0;
        $spTarih = $hammadde['siparis_tarih'] ?? '';
        
        $siparisVal = !$sp ? '📋 Bekliyor' : ($spGeldi ? '📦 Geldi' : '⏳ ' . ($spNo ? $spNo . ': ' : '') . number_format($spMiktar, 0, ',', '.') . ' kg');
        $siparisRenk = $spGeldi ? '#60a5fa' : ($sp ? '#34d399' : '#94a3b8');
        ?>
        <div class="card">
            <div style="font-size:11px;color:#475569;margin-bottom:6;letter-spacing:0.06em;text-transform:uppercase;">Stok Miktarı</div>
            <div style="font-size:22px;font-weight:700;color:#34d399;">
                <?php echo $hammadde['stok_miktari'] ? number_format($hammadde['stok_miktari'], 0, ',', '.') . ' kg' : '—'; ?>
            </div>
        </div>
        <div class="card">
            <div style="font-size:11px;color:#475569;margin-bottom:6;letter-spacing:0.06em;text-transform:uppercase;">Hesaplanan Optimum</div>
            <div style="font-size:22px;font-weight:700;color:#60a5fa;">
                <?php echo $hammadde['hesaplanan_optimum'] ? number_format($hammadde['hesaplanan_optimum'], 0, ',', '.') . ' kg' : '—'; ?>
                <?php if ($hammadde['hesaplanan_optimum']): ?>
                <span style="font-size:13px;color:#475569;margin-left:8;font-weight:400;">(<?php echo number_format($optKg, 0, ',', '.'); ?>)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div style="font-size:11px;color:#475569;margin-bottom:6;letter-spacing:0.06em;text-transform:uppercase;">Toplam Termin</div>
            <div style="font-size:22px;font-weight:700;color:#fbbf24;">
                <?php echo $terminToplam > 0 ? $terminToplam . ' gün' : '—'; ?>
            </div>
        </div>
        <div class="card">
            <div style="font-size:11px;color:#475569;margin-bottom:6;letter-spacing:0.06em;text-transform:uppercase;">Sipariş Durumu</div>
            <div style="font-size:22px;font-weight:700;color:<?php echo $siparisRenk; ?>;">
                <?php echo $siparisVal; ?>
            </div>
        </div>
    </div>

    <!-- Sipariş Detay Kartı -->
    <?php if ($sp && !$spGeldi): ?>
    <div style="background:linear-gradient(135deg,#0d2018,#0f1117);border:1px solid #10b98155;border-radius:10px;padding:12px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">⏳ Bekleyen Sipariş</div>
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                <?php if($spNo): ?><span style="color:#fbbf24;font-weight:700;font-size:14px;">📋 <?php echo $spNo; ?></span><?php endif; ?>
                <span style="color:#34d399;font-weight:700;font-size:14px;"><?php echo number_format($spMiktar, 0, ',', '.'); ?> kg</span>
                <?php if($spTarih): ?><span style="color:#475569;font-size:12px;">📅 <?php echo $spTarih; ?></span><?php endif; ?>
            </div>
        </div>
        <button onclick="siparisGeldi(<?php echo $id; ?>)" style="background:#1d3557;border:1px solid #3b82f6;border-radius:8px;padding:9px 18px;color:#60a5fa;cursor:pointer;font-weight:700;font-size:12px;">
            📦 Sipariş Geldi — Kapat
        </button>
    </div>
    <?php elseif ($spGeldi): ?>
    <div style="background:#0a0e15;border:1px solid #3b82f633;border-radius:10px;padding:10px 18px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
        <div style="color:#60a5fa;font-size:13px;">
            📦 Sipariş teslim alındı
            <?php if($spNo): ?><span style="color:#475569;margin-left:10;font-size:12px;"><?php echo $spNo; ?></span><?php endif; ?>
            <?php if($spTarih): ?><span style="color:#334155;margin-left:10;font-size:11px;">📅 <?php echo $spTarih; ?></span><?php endif; ?>
        </div>
        <button onclick="siparisTemizle(<?php echo $id; ?>)" style="background:transparent;border:1px solid #334155;border-radius:6px;padding:5px 12px;color:#475569;cursor:pointer;font-size:11px;">
            ✕ Temizle
        </button>
    </div>
    <?php endif; ?>

    <!-- Termin & Fiyat -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <!-- Termin -->
        <div class="card">
            <div class="section-title">⏱ Termin Süresi Dağılımı</div>
            <?php if ($terminToplam > 0): ?>
            <?php 
            $adimlar = [
                ['key' => 'akreditif_gun', 'label' => 'Akreditif Açma', 'icon' => '🏦', 'renk' => '#3b82f6'],
                ['key' => 'satici_tedarik_gun', 'label' => 'Satıcı Tedarik', 'icon' => '🏭', 'renk' => '#8b5cf6'],
                ['key' => 'yol_gun', 'label' => 'Yol (Nakliye)', 'icon' => '🚢', 'renk' => '#10b981'],
                ['key' => 'depo_kabul_gun', 'label' => 'Depo Kabul', 'icon' => '🏗️', 'renk' => '#f59e0b'],
            ];
            ?>
            <div>
                <?php foreach ($adimlar as $a): 
                    $v = (int)($hammadde[$a['key']] ?? 0);
                    $yuzde = $terminToplam > 0 ? round($v / $terminToplam * 100) : 0;
                    if ($v > 0):
                ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4;">
                        <span style="font-size:12px;color:#94a3b8;"><?php echo $a['icon']; ?> <?php echo $a['label']; ?></span>
                        <span style="font-size:12px;font-weight:700;color:<?php echo $a['renk']; ?>;"><?php echo $v; ?> gün</span>
                    </div>
                    <div style="height:5;background:#1e2430;border-radius:3;">
                        <div style="width:<?php echo $yuzde; ?>%;height:100%;background:<?php echo $a['renk']; ?>;border-radius:3;"></div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
                <div style="border-top:1px solid #1e2430;padding-top:8;margin-top:4;display:flex;justify-content:space-between;">
                    <span style="font-size:12px;color:#64748b;">Toplam Termin</span>
                    <span style="font-size:14px;font-weight:700;color:#fbbf24;"><?php echo $terminToplam; ?> gün</span>
                </div>
            </div>
            <?php else: ?>
            <div style="color:#475569;font-size:13px;">Termin detayı girilmemiş.</div>
            <?php endif; ?>
        </div>
        
        <!-- Fiyat -->
        <div class="card">
            <div class="section-title">💰 Fiyat & Maliyet</div>
            <?php if ($hammadde['birim_fiyat'] > 0): 
                $bp = (float)$hammadde['birim_fiyat'];
                $md = (float)$hammadde['maliyet_deger'];
                $pb = $hammadde['para_birimi_kodu'] ?? 'USD';
                $birim = $hammadde['fiyat_birimi'] ?? 'ton';
                $teslimat = $hammadde['teslimat_sekli_kodu'] ?? 'CIF';
            ?>
            <div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10;margin-bottom:10;">
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">Birim Fiyat (<?php echo '/'.$birim; ?>)</div>
                        <div style="font-size:13px;font-weight:700;color:#60a5fa;"><?php echo number_format($bp, 2, ',', '.'); ?> <?php echo $pb; ?></div>
                    </div>
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">Nakliye/Maliyet<?php echo $hammadde['maliyet_tipi']=='yuzde'?' (%)':''; ?></div>
                        <div style="font-size:13px;font-weight:700;color:#a78bfa;">
                            <?php echo $hammadde['maliyet_tipi']=='yuzde' ? '%'.$md : number_format($maliyet, 2, ',', '.').' '.$pb; ?>
                        </div>
                    </div>
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">Teslim Şekli</div>
                        <div style="font-size:13px;font-weight:700;color:#94a3b8;"><?php echo $teslimat; ?></div>
                    </div>
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">Birim Varış Maliyeti</div>
                        <div style="font-size:13px;font-weight:700;color:#34d399;"><?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $pb; ?>/<?php echo $birim; ?></div>
                    </div>
                </div>
                
                <!-- Toplam Stok Bedeli -->
                <div style="background:linear-gradient(135deg,#0d2018,#0f1117);border:1px solid #10b98155;border-radius:10px;padding:12px 14px;margin-bottom:10;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3;">📦 Toplam Stok Bedeli</div>
                        <div style="font-size:11px;color:#475569;"><?php echo number_format($stokKg, 0, ',', '.'); ?> kg × <?php echo number_format($varisMaliyet, 2, ',', '.'); ?> <?php echo $pb; ?>/<?php echo $birim; ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:20px;font-weight:700;color:#34d399;"><?php echo number_format($toplamBedel, 0, ',', '.'); ?> <?php echo $pb; ?></div>
                    </div>
                </div>
                
                <!-- Paketleme & Menşei -->
                <?php if ($hammadde['paketleme_kodu'] || $hammadde['ulke_adi'] || $hammadde['tedarikci']): ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8;margin-top:10;">
                    <?php if ($hammadde['paketleme_kodu']): 
                        $pk = $PAKETLEME_TIPLERI[$hammadde['paketleme_kodu']] ?? null;
                        if ($pk):
                    ?>
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">📦 Paketleme</div>
                        <div style="font-size:13px;font-weight:700;color:#60a5fa;"><?php echo $pk['ad']; ?></div>
                        <div style="font-size:11px;color:#475569;margin-top:3;"><?php echo $pk['aciklama']; ?></div>
                    </div>
                    <?php endif; endif; ?>
                    <?php if ($hammadde['ulke_adi'] || $hammadde['tedarikci']): ?>
                    <div style="background:#0f1117;border-radius:8;padding:10px 12px;">
                        <div style="font-size:10px;color:#475569;margin-bottom:4;text-transform:uppercase;">🌍 Menşei Ülke</div>
                        <?php if ($hammadde['ulke_adi']): ?>
                        <div style="font-size:13px;font-weight:700;color:#34d399;"><?php echo $hammadde['ulke_adi']; ?></div>
                        <?php endif; ?>
                        <?php if ($hammadde['tedarikci']): ?>
                        <div style="font-size:12px;color:#fbbf24;margin-top:5;display:flex;align-items:center;gap:4;">
                            <span style="font-size:10;">🏭</span> <?php echo $hammadde['tedarikci']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="color:#475569;font-size:13px;">Fiyat bilgisi girilmemiş.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Optimum Miktar Analizi -->
    <?php 
    $minYarisi = null;
    $optYarisi = null;
    $maksYarisi = null;
    
    // Türkçe ay isimleri
    $aylarTr = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    
    if ($gunlukTuketim > 0 && $terminToplam > 0 && $stokKg > 0) {
        $minYarisiKg = round($minMiktar / 2);
        $optYarisiKg = round($optMiktar / 2);
        $maksYarisiKg = round($maksMiktar / 2);
        
        if ($stokKg > $minYarisiKg) {
            $minGun = round(($stokKg - $minYarisiKg) / $gunlukTuketim);
            $minTarih = new DateTime();
            $minTarih->modify("+{$minGun} days");
            $minAy = (int)$minTarih->format('n') - 1;
            $minYarisi = [
                'tarih' => $minTarih->format('d') . ' ' . $aylarTr[$minAy] . ' ' . $minTarih->format('Y'),
                'gun' => $minGun,
                'kalanStok' => $minYarisiKg,
                'kalanGun' => round($minYarisiKg / $gunlukTuketim)
            ];
        }
        
        if ($stokKg > $optYarisiKg) {
            $optGun = round(($stokKg - $optYarisiKg) / $gunlukTuketim);
            $optTarih = new DateTime();
            $optTarih->modify("+{$optGun} days");
            $optAy = (int)$optTarih->format('n') - 1;
            $optYarisi = [
                'tarih' => $optTarih->format('d') . ' ' . $aylarTr[$optAy] . ' ' . $optTarih->format('Y'),
                'gun' => $optGun,
                'kalanStok' => $optYarisiKg,
                'kalanGun' => round($optYarisiKg / $gunlukTuketim)
            ];
        }
        
        if ($stokKg > $maksYarisiKg) {
            $maksGun = round(($stokKg - $maksYarisiKg) / $gunlukTuketim);
            $maksTarih = new DateTime();
            $maksTarih->modify("+{$maksGun} days");
            $maksAy = (int)$maksTarih->format('n') - 1;
            $maksYarisi = [
                'tarih' => $maksTarih->format('d') . ' ' . $aylarTr[$maksAy] . ' ' . $maksTarih->format('Y'),
                'gun' => $maksGun,
                'kalanStok' => $maksYarisiKg,
                'kalanGun' => round($maksYarisiKg / $gunlukTuketim)
            ];
        }
    }
    ?>
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div class="section-title" style="margin-bottom:0;">📐 Optimum Miktar Analizi</div>
            <?php if ($terminToplam > 0): ?>
            <span style="font-size:11px;color:#475569;background:#1e2430;border-radius:5px;padding:3px 10px;">
                Termin: <?php echo $terminToplam; ?> gün · Günlük tük: <?php echo number_format($gunlukTuketim, 1, ',', '.'); ?> kg
            </span>
            <?php else: ?>
            <span style="font-size:11px;color:#ef4444;background:#2d1a1a;border-radius:5px;padding:3px 10px;">⚠️ Termin süresi girilmemiş</span>
            <?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12;">
            <?php 
            $kartlar = [
                ['baslik' => '📉 Minimum Miktar', 'aciklama' => 'Günlük tük. × Termin', 'miktar' => $minMiktar, 'renk' => '#f97316', 'yarim' => $minYarisi, 'yarimKg' => round($minMiktar/2)],
                ['baslik' => '🎯 Optimum Miktar', 'aciklama' => 'Günlük tük. × Termin × 2', 'miktar' => $optMiktar, 'renk' => '#34d399', 'yarim' => $optYarisi, 'yarimKg' => round($optMiktar/2)],
                ['baslik' => '📈 Maksimum Miktar', 'aciklama' => 'Minimum + Optimum', 'miktar' => $maksMiktar, 'renk' => '#a78bfa', 'yarim' => $maksYarisi, 'yarimKg' => round($maksMiktar/2)],
            ];
            foreach ($kartlar as $k):
            ?>
            <div style="background:#0f1117;border-radius:10;padding:14px 16px;border-left:3px solid <?php echo $k['renk']; ?>;border-top:1px solid <?php echo $k['renk']; ?>22;">
                <div style="font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6;"><?php echo $k['baslik']; ?></div>
                <div style="font-size:22px;font-weight:700;color:<?php echo $k['renk']; ?>;margin-bottom:3;">
                    <?php echo $terminToplam > 0 && $k['miktar'] > 0 ? number_format($k['miktar'], 0, ',', '.').' kg' : '—'; ?>
                </div>
                <div style="font-size:10px;color:#64748b;margin-bottom:10;"><?php echo $k['aciklama']; ?></div>
                <div style="border-top:<?php echo $k['renk']; ?>22 1px solid;padding-top:8;">
                    <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4;">
                        Yarısına (<?php echo number_format($k['yarimKg'], 0, ',', '.'); ?> kg'a) ne zaman?
                    </div>
                    <?php if ($k['yarim']): ?>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:<?php echo $k['renk']; ?>;"><?php echo $k['yarim']['tarih']; ?></div>
                        <div style="font-size:10px;color:#475569;margin-top:1;">~<?php echo $k['yarim']['gun']; ?> gün sonra</div>
                        <div style="margin-top:8;border-top:<?php echo $k['renk']; ?>11 1px solid;padding-top:6;display:flex;gap:10;">
                            <div>
                                <div style="font-size:9px;color:#475569;text-transform:uppercase;">O tarihte stok</div>
                                <div style="font-size:11px;font-weight:700;color:#94a3b8;margin-top:2;"><?php echo number_format($k['yarim']['kalanStok'], 0, ',', '.'); ?> kg</div>
                            </div>
                            <div>
                                <div style="font-size:9px;color:#475569;text-transform:uppercase;">Stok ömrü</div>
                                <div style="font-size:11px;font-weight:700;margin-top:2;
                                    color:<?php echo $k['yarim']['kalanGun']<=30?'#ef4444':($k['yarim']['kalanGun']<=90?'#f97316':'#34d399'); ?>;">
                                    ~<?php echo $k['yarim']['kalanGun']; ?> gün
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($stokKg <= $k['yarimKg']): ?>
                    <div style="font-size:11px;color:#ef4444;font-weight:700;">Stok zaten bu seviyenin altında</div>
                    <?php else: ?>
                    <div style="font-size:10px;color:#334155;">Hesaplanamadı</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tüketim Analizi -->
    <?php 
    $maxDeger = count($s12Veriler) > 0 ? max(array_column($s12Veriler, 'deger')) : 1;
    ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="section-title">📊 Tüketim Analizi (Son 12 Ay)</div>
        
        <!-- 5 Kart -->
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;">
            <div style="background:#0f1117;border-radius:10;padding:12px 14px;border-left:3px solid #3b82f6;">
                <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5;">🕐 Günlük Tüketim</div>
                <div style="font-size:17px;font-weight:700;color:#60a5fa;">
                    <?php echo $gunlukTuketim > 0 ? number_format($gunlukTuketim, 1, ',', '.').' kg' : '—'; ?>
                </div>
                <div style="font-size:10px;color:#475569;margin-top:2;">Son 12 ay ortalaması</div>
            </div>
            <div style="background:#0f1117;border-radius:10;padding:12px 14px;border-left:3px solid <?php echo $kalanGun!==null && $kalanGun<=30?'#ef4444':($kalanGun!==null && $kalanGun<=90?'#f59e0b':'#10b981'); ?>;">
                <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5;">⏳ Tam Tükenme</div>
                <div style="font-size:17px;font-weight:700;color:<?php echo $kalanGun!==null?($kalanGun<=30?'#ef4444':($kalanGun<=90?'#fbbf24':'#34d399')):'#475569'; ?>;">
                    <?php echo $kalanGun !== null ? $kalanGun . ' gün' : '—'; ?>
                </div>
                <div style="font-size:10px;color:#475569;margin-top:2;"><?php echo $kalanGun!==null && $kalanGun<=0?'Stok tükendi!':($kalanGun!==null && $kalanGun<=30?'⚠️ Acil!':''); ?></div>
            </div>
            <div style="background:#0f1117;border-radius:10;padding:12px 14px;border-left:3px solid #8b5cf6;">
                <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5;">📅 Tükenme Tarihi</div>
                <div style="font-size:13px;font-weight:700;color:#a78bfa;line-height:1.3;"><?php echo $tukenmeTarih ?? '—'; ?></div>
            </div>
            <div style="background:#0f1117;border-radius:10;padding:12px 14px;border-left:3px solid <?php echo $optimumGun!==null && $optimumGun<=30?'#f59e0b':'#10b981'; ?>;">
                <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5;">🎯 Optimuma Düşme</div>
                <div style="font-size:17px;font-weight:700;color:<?php echo $optimumGun!==null?($optimumGun<=30?'#fbbf24':'#34d399'):'#475569'; ?>;">
                    <?php echo $optimumGun !== null ? $optimumGun . ' gün' : ($stokKg <= $optKg ? '0 gün' : '—'); ?>
                </div>
                <div style="font-size:10px;color:#475569;margin-top:2;"><?php echo $optKgGirilen>0 ? 'Opt: '.number_format($optKg, 0, ',', '.').' kg' : ''; ?></div>
            </div>
            <div style="background:#0f1117;border-radius:10;padding:12px 14px;border-left:3px solid #f59e0b;">
                <div style="font-size:9px;color:#475569;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5;">📅 Optimum Tarihi</div>
                <div style="font-size:13px;font-weight:700;color:#fbbf24;line-height:1.3;"><?php echo $optimumTarih ?? ($stokKg <= $optKg ? date('d F Y') : '—'); ?></div>
            </div>
        </div>
        
        <!-- Bar Grafik -->
        <?php if (count($s12Veriler) > 0): ?>
        <div>
            <div style="font-size:11px;color:#475569;margin-bottom:10;font-weight:600;">Aylık Tüketim — Son <?php echo count($s12Veriler); ?> Ay</div>
            <div style="position:relative;height:120px;border-bottom:1px solid #1e2430;display:flex;align-items:flex-end;gap:3;padding:20px 2px 0;">
                <?php foreach ($s12Veriler as $i => $x): 
                    $px = round($x['deger'] / $maxDeger * 100);
                    $barH = max(round($px * 100 / 100), 2);
                    $renk = $i >= count($s12Veriler) - 2 ? '#fbbf24' : ($i >= count($s12Veriler) - 4 ? '#10b981' : '#3b82f6');
                ?>
                <div title="<?php echo $x['etiket'] . ': ' . number_format($x['deger'], 0, ',', '.') . ' kg'; ?>"
                    style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:1;cursor:default;">
                    <div style="font-size:7px;color:#64748b;text-align:center;width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:1;">
                        <?php echo $x['deger'] >= 1000 ? ($x['deger']/1000).'k' : number_format($x['deger'], 0, ',', '.'); ?>
                    </div>
                    <div style="width:75%;background:<?php echo $renk; ?>;border-radius:2px 2px 0 0;height:<?php echo $barH; ?>%;transition:height 0.4s;opacity:0.9;"></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:3;padding:4px 2px;">
                <?php foreach ($s12Veriler as $x): ?>
                <div style="flex:1;font-size:7px;color:#475569;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo $x['etiket']; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Yıllık Tüketim Grafiği -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div class="section-title" style="margin-bottom:0;">📅 Yıllık Tüketim Grafiği</div>
            <div style="display:flex;gap:6;">
                <?php foreach ($yillar as $y): 
                    $isActive = $y == $aktifYil;
                    $rk = $YIL_RENKLER[$y];
                ?>
                <button onclick="window.location='?id=<?php echo $id; ?>&tab=genel&yil=<?php echo $y; ?>'" style="
                    padding:5px 12px;border-radius:6px;border:1px solid;
                    border-color:<?php echo $isActive ? $rk['border'] : '#1e2430'; ?>;
                    background:<?php echo $isActive ? $rk['bg'] : 'transparent'; ?>;
                    color:<?php echo $isActive ? $rk['text'] : '#64748b'; ?>;
                    cursor:pointer;font-size:12px;font-weight:700;">
                    <?php echo $y; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php 
        $rk = $YIL_RENKLER[$aktifYil];
        $yilVeriler = [];
        foreach ($aylar as $idx => $ayAd) {
            $deger = $tuketimVerileri[$aktifYil][$idx + 1] ?? 0;
            $yilVeriler[] = ['ay' => $ayAd, 'deger' => $deger];
        }
        $maxY = count($yilVeriler) > 0 ? max(array_column($yilVeriler, 'deger')) : 1;
        $topY = array_sum(array_column($yilVeriler, 'deger'));
        // Her zaman 12 ay baz alınarak ortalama hesapla (0 değerli aylar da dahil)
        $ortY = $topY / 12;
        ?>
        
        <!-- Özet -->
        <div style="display:flex;gap:20;margin-bottom:16px;padding:10px 14px;background:#0f1117;border-radius:8;">
            <div><span style="font-size:11px;color:#475569;">Toplam: </span><span style="font-size:13px;font-weight:700;color:<?php echo $rk['text']; ?>;"><?php echo $topY > 0 ? number_format($topY, 0, ',', '.').' kg' : '—'; ?></span></div>
            <div><span style="font-size:11px;color:#475569;">Aylık ort: </span><span style="font-size:13px;font-weight:700;color:<?php echo $rk['text']; ?>;"><?php echo $ortY > 0 ? number_format($ortY, 0, ',', '.').' kg' : '—'; ?></span></div>
            <div><span style="font-size:11px;color:#475569;">Hesaplanan ay: </span><span style="font-size:13px;font-weight:700;color:<?php echo $rk['text']; ?>;">12</span></div>
        </div>
        
        <!-- Bar Grafik -->
        <div style="display:flex;align-items:flex-end;gap:5;height:110px;padding:0 2px;border-bottom:1px solid #1e2430;margin-bottom:4;">
            <?php foreach ($yilVeriler as $x): 
                $yuzde = $maxY > 0 ? max(round($x['deger'] / $maxY * 100), $x['deger'] > 0 ? 3 : 0) : 0;
            ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:2;">
                <?php if($x['deger'] > 0): ?><div style="font-size:8px;color:<?php echo $rk['text']; ?>;text-align:center;white-space:nowrap;"><?php echo $x['deger'] >= 1000 ? ($x['deger']/1000).'k' : number_format($x['deger'], 0, ',', '.'); ?></div><?php endif; ?>
                <div style="width:75%;background:<?php echo $x['deger'] > 0 ? $rk['border'] : '#1a2130'; ?>;border-radius:3px 3px 0 0;height:<?php echo $yuzde; ?>%;min-height:<?php echo $x['deger'] > 0 ? 3 : 0; ?>px;transition:height 0.5s;"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:5;padding:3px 2px;margin-bottom:12px;">
            <?php foreach ($yilVeriler as $x): ?>
            <div style="flex:1;font-size:9px;color:#475569;text-align:center;"><?php echo substr($x['ay'], 0, 3); ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Tablo -->
        <div style="overflow-x:auto;margin-top:12px;">
            <table style="width:100%;border-collapse:collapse;min-width:700px;">
                <thead>
                    <tr style="background:#0d1017;border-bottom:1px solid #1e2430;">
                        <th style="padding:8px 12px;text-align:left;font-size:10px;color:#475569;font-weight:700;">YIL</th>
                        <?php foreach ($aylar as $a): ?><th style="padding:8px 8px;text-align:right;font-size:10px;color:#475569;font-weight:700;"><?php echo substr($a, 0, 3); ?></th><?php endforeach; ?>
                        <th style="padding:8px 12px;text-align:right;font-size:10px;color:#475569;font-weight:700;">ORT.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yillar as $y): 
                        $rk2 = $YIL_RENKLER[$y];
                        $vals = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $v = $tuketimVerileri[$y][$i] ?? 0;
                            // 0 değerli aylar da dahil - boş olmayan tüm aylar
                            $vals[] = $v;
                        }
                        // Her zaman 12 ay baz alınarak ortalama hesapla
                        $ort = count($vals) > 0 ? round(array_sum($vals) / 12) : 0;
                        $isActive = $y == $aktifYil;
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;background:<?php echo $isActive ? $rk2['bg'].'44' : 'transparent'; ?>;">
                        <td style="padding:9px 12px;"><span style="font-weight:700;color:<?php echo $rk2['text']; ?>;font-size:13px;"><?php echo $y; ?></span></td>
                        <?php for ($i = 1; $i <= 12; $i++): 
                            $v = $tuketimVerileri[$y][$i] ?? 0;
                        ?>
                        <td style="padding:9px 8px;text-align:right;font-size:11px;color:<?php echo $v ? $rk2['text'] : '#2d3748'; ?>;font-family:monospace;"><?php echo $v ? number_format($v, 0, ',', '.') : '-'; ?></td>
                        <?php endfor; ?>
                        <td style="padding:9px 12px;text-align:right;font-weight:700;color:<?php echo $rk2['text']; ?>;font-size:12px;"><?php echo $ort > 0 ? number_format($ort, 0, ',', '.') : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($detayTab === 'fiyatGecmisi'): ?>
    <!-- Fiyat Geçmişi -->
    <?php if (!empty($fiyatGecmisi)): ?>
    <div class="card">
        <div style="font-size:13px;color:#94a3b8;margin-bottom:16px;">
            Toplam <strong style="color:#f1f5f9;"><?php echo count($fiyatGecmisi); ?></strong> geçmiş fiyat kaydı — en yeniden eskiye sıralı
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                        <?php foreach (['Kayıt Tarihi','Tür','Birim Fiyat','Para Birimi','Birim','Teslim Şekli','Maliyet','Varış Maliyeti','Mevcut ile Fark',''] as $h): ?>
                        <th style="padding:9px 12px;text-align:left;color:#475569;font-weight:700;white-space:nowrap;font-size:10px;"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $reversedGecmis = array_reverse($fiyatGecmisi);
                    $bp = (float)$hammadde['birim_fiyat'];
                    $md = (float)$hammadde['maliyet_deger'];
                    $aktifMal = $hammadde['maliyet_tipi'] == 'yuzde' ? $bp * ($md / 100) : $md;
                    $aktifVaris = $bp + $aktifMal;
                    
                    foreach ($reversedGecmis as $i => $g): 
                        $gbp = (float)$g['birim_fiyat'];
                        $gmd = (float)$g['maliyet_deger'];
                        $gmal = $g['maliyet_tipi'] == 'yuzde' ? $gbp * ($gmd / 100) : $gmd;
                        $gvaris = $gbp + $gmal;
                        $farkYuzde = $gvaris > 0 && $aktifVaris > 0 ? round(($aktifVaris - $gvaris) / $gvaris * 100) : null;
                        $tur = $g['maliyet_turu'] ?? 'T';
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;background:<?php echo $i === 0 ? '#0f1117' : 'transparent'; ?>;">
                        <td style="padding:10px 12px;color:#64748b;white-space:nowrap;"><?php echo date('d.m.Y', strtotime($g['kayit_tarihi'])); ?></td>
                        <td style="padding:10px 12px;">
                            <span style="padding:3px 8px;border-radius:4px;font-size:11px;font-weight:700;
                                background:<?php echo $tur=='G'?'#10b98133':'#f59e0b33'; ?>;
                                color:<?php echo $tur=='G'?'#34d399':'#fbbf24'; ?>;
                                border:1px solid <?php echo $tur=='G'?'#10b98155':'#f59e0b55'; ?>;">
                                <?php echo $tur=='G'?'G — Gerçek':'T — Tahmini'; ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px;font-weight:700;color:#60a5fa;"><?php echo number_format($gbp, 2, ',', '.'); ?></td>
                        <td style="padding:10px 12px;color:#94a3b8;"><?php echo $g['para_birimi_kodu'] ?? '-'; ?></td>
                        <td style="padding:10px 12px;color:#94a3b8;">/<?php echo $g['fiyat_birimi'] ?? 'ton'; ?></td>
                        <td style="padding:10px 12px;"><span style="background:#1a2535;color:#60a5fa;border-radius:4px;padding:2px 7px;font-size:11px;"><?php echo $g['teslimat_sekli_kodu'] ?? '-'; ?></span></td>
                        <td style="padding:10px 12px;color:#a78bfa;"><?php echo $g['maliyet_tipi']=='yuzde' ? '%'.$gmd.' = '.number_format($gmal, 2, ',', '.') : number_format($gmal, 2, ',', '.'); ?></td>
                        <td style="padding:10px 12px;">
                            <span style="font-weight:700;color:#34d399;font-size:14px;"><?php echo number_format($gvaris, 2, ',', '.'); ?> <?php echo $g['para_birimi_kodu'] ?? ''; ?></span>
                        </td>
                        <td style="padding:10px 12px;">
                            <?php if ($farkYuzde !== null): ?>
                            <span style="font-weight:700;font-size:12px;color:<?php echo $farkYuzde > 0 ? '#ef4444' : '#34d399'; ?>;
                                background:<?php echo $farkYuzde > 0 ? '#ef444422' : '#10b98122'; ?>;
                                padding:3px 8px;border-radius:4px;">
                                <?php echo $farkYuzde > 0 ? '▲ ' : '▼ '; echo abs($farkYuzde); ?>%
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;">
                            <button onclick="silFiyatGecmisi(<?php echo $id; ?>, <?php echo count($fiyatGecmisi) - 1 - $i; ?>)" 
                                style="background:#2d1a1a;border:1px solid #ef444433;border-radius:5px;color:#ef4444;font-size:12px;padding:4px 9px;cursor:pointer;font-weight:700;">
                                ✕ Sil
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center;padding:40px;color:#475569;">
        <div style="font-size:32px;margin-bottom:10px;">📋</div>
        Bu hammadde için henüz fiyat geçmişi kaydı yok.<br>
        <span style="font-size:12px;">Fiyat bilgisini güncelleyerek kaydettiğinizde eski fiyat buraya eklenir.</span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function setTab(tab) {
    window.location = '?id=<?php echo $id; ?>&tab=' + tab + '&yil=<?php echo $aktifYil; ?>';
}

function siparisGeldi(id) {
    if (confirm('Sipariş geldi olarak işaretlensin mi?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=tamamla'
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}

function siparisTemizle(id) {
    if (confirm('Sipariş bilgileri temizlensin mi?')) {
        fetch('ajax/siparis-guncelle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&islem=iptal'
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}

function silFiyatGecmisi(id, idx) {
    if (confirm('Bu fiyat kaydı silinsin mi?')) {
        fetch('ajax/fiyat-gecmisi-sil.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'hammadde_id=' + id + '&index=' + idx
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
