<?php
require_once 'includes/header.php';

$pageTitle = 'İhtiyaç Listesi';

$db = getDB();

// Siparişleri al
$siparisler = $db->query("SELECT * FROM siparisler WHERE geldi = 0")->fetchAll();
$siparisByHammadde = [];
foreach ($siparisler as $s) {
    $siparisByHammadde[$s['hammadde_id']] = $s;
}

$hammaddeler = getHammaddeler();
$tuketimTumu = [];
foreach ($hammaddeler as $h) {
    $tuketimTumu[$h['id']] = getTuketimVerileri($h['id']);
}

$ihtiyacListe = [];
foreach ($hammaddeler as $h) {
    $stok = (float)$h['stok_miktari'];
    $opt = (float)$h['hesaplanan_optimum'];
    
    // Get siparis data from the joined data
    $sip = $siparisByHammadde[$h['id']] ?? null;
    $sipMiktar = $sip ? (float)$sip['miktar_kg'] : 0;
    $efektifStok = $stok + $sipMiktar;
    
    if ($h['sk'] !== 'K' && $h['sk'] !== 'A' && $opt > 0 && $efektifStok < $opt / 2) {
        $optEfektif = $opt / 2;
        $eksik = $optEfektif - $efektifStok;
        
        $terminGun = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
        
        $tuk = $tuketimTumu[$h['id']];
        $t2025 = [];
        $t2026 = [];
        for ($i = 1; $i <= 12; $i++) {
            // 0 değerli aylar da dahil ediliyor
            $t2025[] = $tuk[2025][$i] ?? 0;
            $t2026[] = $tuk[2026][$i] ?? 0;
        }
        $tumTuk = array_merge($t2025, $t2026);
        // Her zaman 12 ay baz alınarak ortalama hesapla
        $aylikOrt = count($tumTuk) > 0 ? array_sum($tumTuk) / 12 : 0;
        $gunlukTuk = $aylikOrt / 30;
        $kalanGun = $gunlukTuk > 0 ? round($efektifStok / $gunlukTuk) : null;
        
        $oran = $kalanGun !== null && $terminGun > 0 ? $kalanGun / $terminGun : ($kalanGun !== null ? 99 : null);
        
        $ihtiyacListe[] = [
            'id' => $h['id'],
            'sk' => $h['sk'],
            'stok_kodu' => $h['stok_kodu'],
            'tur_adi' => $h['tur_adi'],
            'hammadde_ismi' => $h['hammadde_ismi'],
            'tedarikci' => $h['tedarikci'],
            'stok' => $stok,
            'opt' => $opt,
            'siparis_verildi' => $sip ? true : false,
            'siparis_geldi' => $sip ? $sip['geldi'] : false,
            'siparis_miktar' => $sip ? $sip['miktar_kg'] : '',
            'siparis_no' => $sip ? $sip['siparis_no'] : '',
            'siparis_tarih' => $sip ? date('d.m.Y', strtotime($sip['tarih'])) : '',
            'termin_gun' => $terminGun,
            'aylik_ort' => $aylikOrt,
            'kalan_gun' => $kalanGun,
            'eksik' => $eksik,
            'oran' => $oran
        ];
    }
}

usort($ihtiyacListe, function($a, $b) {
    if ($a['oran'] === null) return 1;
    if ($b['oran'] === null) return -1;
    return $a['oran'] - $b['oran'];
});

$acil = array_filter($ihtiyacListe, fn($m) => $m['oran'] !== null && $m['oran'] < 0.5);
$siparisVer = array_filter($ihtiyacListe, fn($m) => $m['oran'] !== null && $m['oran'] >= 0.5 && $m['oran'] < 1);
$takipte = array_filter($ihtiyacListe, fn($m) => $m['oran'] === null || $m['oran'] >= 1);

$toplamAktifSiparisler = count($siparisler);
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
.btn-secondary { background:#1e2430; color:#94a3b8; border:1px solid #2d3748; border-radius:8px; padding:10px 18px; cursor:pointer; font-size:13px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:20px;">
        <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">⚠️ İhtiyaç Listesi</h2>
        <p style="color:#475569;font-size:13px;">Stok miktarı hesaplanan optimumun altında kalan hammaddeler</p>
    </div>

    <!-- Özet Kartlar -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;">
        <?php 
        $kartlar = [
            ['label' => 'Toplam İhtiyaç', 'val' => count($ihtiyacListe) . ' kalem', 'renk' => '#f59e0b', 'bg' => '#2a1f0a', 'icon' => '📦'],
            ['label' => 'Siparişi Verilmiş', 'val' => $toplamAktifSiparisler . ' kalem', 'renk' => '#34d399', 'bg' => '#0d2018', 'icon' => '✅'],
            ['label' => 'Acil Sipariş (<%50)', 'val' => count($acil) . ' kalem', 'renk' => '#ef4444', 'bg' => '#2d1a1a', 'icon' => '🚨'],
            ['label' => 'Sipariş Ver (50-100%)', 'val' => count($siparisVer) . ' kalem', 'renk' => '#f97316', 'bg' => '#2a1a0a', 'icon' => '📦'],
            ['label' => 'Takipte (>100%)', 'val' => count($takipte) . ' kalem', 'renk' => '#eab308', 'bg' => '#1f1e0a', 'icon' => '👁'],
        ];
        foreach ($kartlar as $k):
        ?>
        <div style="background:<?php echo $k['bg']; ?>;border:1px solid <?php echo $k['renk']; ?>44;border-radius:12px;padding:18px;border-top:3px solid <?php echo $k['renk']; ?>;">
            <div style="font-size:22px;margin-bottom:8px;"><?php echo $k['icon']; ?></div>
            <div style="font-size:22px;font-weight:700;color:<?php echo $k['renk']; ?>;"><?php echo $k['val']; ?></div>
            <div style="font-size:11px;color:#64748b;margin-top:4px;"><?php echo $k['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($ihtiyacListe)): ?>
    <div style="text-align:center;padding:60px;color:#334155;">
        <div style="font-size:48px;margin-bottom:12px;">✅</div>
        <div style="font-size:16px;color:#475569;">Tüm hammaddeler yeterli stok seviyesinde!</div>
    </div>
    <?php else: ?>
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                        <?php 
                        $basliklar = ["Öncelik","S/K","Stok Kodu","Hammadde İsmi","Tür","Mevcut Stok","Optimum","Gereken Miktar","Stok Ömrü / termin","Aylık ort. Tük.","Tahmini Tükenme","Termin","Sipariş Durumu","İşlem"];
                        foreach ($basliklar as $h): ?>
                        <th style="padding:11px 13px;text-align:left;font-size:10px;color:#475569;letter-spacing:0.07em;font-weight:700;white-space:nowrap;"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ihtiyacListe as $m): 
                        $oncelik = $m['oran'] === null 
                            ? ['renk' => '#475569', 'bg' => '#141820', 'etiket' => 'VERİ YOK', 'icon' => '—']
                            : ($m['oran'] < 0.5 
                                ? ['renk' => '#ef4444', 'bg' => '#2d1a1a', 'etiket' => 'ACİL SİPARİŞ', 'icon' => '🚨']
                                : ($m['oran'] < 1 
                                    ? ['renk' => '#f97316', 'bg' => '#2a1a0a', 'etiket' => 'SİPARİŞ VER', 'icon' => '📦']
                                    : ($m['oran'] < 2 
                                        ? ['renk' => '#eab308', 'bg' => '#1f1e0a', 'etiket' => 'TAKİPTE', 'icon' => '👁']
                                        : ['renk' => '#34d399', 'bg' => '#0d2018', 'etiket' => 'RAHAT', 'icon' => '✅'])));
                        
                        $yuzde = $m['oran'] !== null ? min(100, round($m['oran'] * 50)) : 0;
                        
                        $kalanText = $m['kalan_gun'] !== null 
                            ? ($m['kalan_gun'] <= 0 ? 'Tükendi!' : ($m['kalan_gun'] < 30 ? $m['kalan_gun'] . ' gün' : '~' . round($m['kalan_gun'] / 30) . ' ay'))
                            : '—';
                        
                        $kalanRenk = $m['kalan_gun'] !== null
                            ? ($m['kalan_gun'] <= 7 ? '#ef4444' : ($m['kalan_gun'] <= 30 ? '#f97316' : ($m['kalan_gun'] <= 90 ? '#eab308' : '#94a3b8')))
                            : '#64748b';
                    ?>
                    <tr style="border-bottom:1px solid #1e2430;"
                        onMouseEnter="this.style.background='#1a2130'"
                        onMouseLeave="this.style.background='transparent'">
                        
                        <!-- Öncelik -->
                        <td style="padding:12px 13px;">
                            <span style="background:<?php echo $oncelik['bg']; ?>;color:<?php echo $oncelik['renk']; ?>;padding:3px 9px;border-radius:5px;font-size:11px;font-weight:700;border:1px solid <?php echo $oncelik['renk']; ?>44;white-space:nowrap;">
                                <?php echo $oncelik['icon'] . ' ' . $oncelik['etiket']; ?>
                            </span>
                        </td>
                        
                        <!-- S/K -->
                        <td style="padding:12px 13px;">
                            <span style="padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;
                                background:<?php echo $m['sk']=='S'?'#1d3557':($m['sk']=='A'?'#221a05':'#2d1a1a'); ?>;
                                color:<?php echo $m['sk']=='S'?'#60a5fa':($m['sk']=='A'?'#fbbf24':'#f87171'); ?>;">
                                <?php echo $m['sk']; ?>
                            </span>
                        </td>
                        
                        <!-- Stok Kodu -->
                        <td style="padding:12px 13px;color:#94a3b8;font-size:12px;font-family:monospace;"><?php echo $m['stok_kodu']; ?></td>
                        
                        <!-- Hammadde -->
                        <td style="padding:12px 13px;font-weight:600;color:#60a5fa;font-size:13px;max-width:220px;cursor:pointer;text-decoration:underline;text-decoration-style:dotted;text-underline-offset:3;" 
                            onclick="window.location='hammadde-detay.php?id=<?php echo $m['id']; ?>'">
                            <?php echo htmlspecialchars($m['hammadde_ismi']); ?>
                        </td>
                        
                        <!-- Tür -->
                        <td style="padding:12px 13px;">
                            <span style="background:#1e2430;padding:2px 8px;border-radius:4px;font-size:11px;color:#94a3b8;"><?php echo $m['tur_adi']; ?></span>
                        </td>
                        
                        <!-- Mevcut Stok -->
                        <td style="padding:12px 13px;font-weight:700;color:#f87171;font-size:13px;"><?php echo number_format($m['stok'], 0, ',', '.'); ?></td>
                        
                        <!-- Optimum -->
                        <td style="padding:12px 13px;color:#64748b;font-size:12px;"><?php echo number_format($m['opt'], 0, ',', '.'); ?></td>
                        
                        <!-- Gereken Miktar -->
                        <td style="padding:12px 13px;font-weight:700;color:<?php echo $oncelik['renk']; ?>;font-size:13px;"><?php echo number_format($m['eksik'], 0, ',', '.'); ?></td>
                        
                        <!-- Stok Ömrü / Termin -->
                        <td style="padding:12px 13px;min-width:120px;">
                            <div style="margin-bottom:4px;display:flex;justify-content:space-between;align-items:center;gap:6px;">
                                <span style="font-size:11px;color:<?php echo $oncelik['renk']; ?>;font-weight:700;">%<?php echo $yuzde; ?></span>
                                <span style="font-size:9px;color:<?php echo $oncelik['renk']; ?>;font-weight:700;opacity:0.8;">
                                    <?php echo $m['oran']>=1?'✓ RAHAT':($m['oran']>=0.75?'👁 TAKİP':($m['oran']>=0.25?'📦 SİP.VER':'🚨 ACİL')); ?>
                                </span>
                            </div>
                            <div style="height:8px;background:#1e2430;border-radius:4px;overflow:hidden;">
                                <div style="width:<?php echo $yuzde; ?>%;height:100%;border-radius:4px;<?php echo ($m['oran']===null || $m['oran']<0.5)?'background:linear-gradient(90deg,#ef4444,#f97316)':($m['oran']<1?'background:linear-gradient(90deg,#f97316,#eab308)':'background:linear-gradient(90deg,#eab308,#84cc16)'); ?>;transition:width 0.5s"></div>
                            </div>
                        </td>
                        
                        <!-- Aylık Ort -->
                        <td style="padding:12px 13px;color:#94a3b8;font-size:12px;"><?php echo $m['aylik_ort'] > 0 ? number_format(round($m['aylik_ort']), 0, ',', '.') : '—'; ?></td>
                        
                        <!-- Tahmini Tükenme -->
                        <td style="padding:12px 13px;font-weight:700;color:<?php echo $kalanRenk; ?>;font-size:13px;white-space:nowrap;"><?php echo $kalanText; ?></td>
                        
                        <!-- Termin -->
                        <td style="padding:12px 13px;color:#64748b;font-size:12px;"><?php echo $m['termin_gun'] > 0 ? $m['termin_gun'] . ' gün' : '—'; ?></td>
                        
                        <!-- Sipariş Durumu -->
                        <td style="padding:10px 13px;min-width:220px;">
                            <?php if ($m['siparis_verildi'] && !$m['siparis_geldi']): ?>
                            <div style="background:#0d2018;border:1px solid #10b98155;border-radius:8px;padding:8px 10px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5;">
                                    <span style="font-size:11px;color:#34d399;font-weight:700;">✅ Sipariş Verildi</span>
                                    <button onclick="siparisIptal(<?php echo $m['id']; ?>)" style="background:#2d1a1a;border:none;border-radius:4px;color:#f87171;font-size:10px;padding:2px 6px;cursor:pointer;">✕ İptal</button>
                                </div>
                                <?php if($m['siparis_no']): ?>
                                <div style="font-size:11px;color:#fbbf24;font-weight:700;margin-bottom:3;">📋 <?php echo $m['siparis_no']; ?></div>
                                <?php endif; ?>
                                <div style="font-size:11px;color:#94a3b8;margin-bottom:2px;">
                                    Miktar: <strong style="color:#34d399;"><?php echo number_format($m['siparis_miktar'], 0, ',', '.'); ?> kg</strong>
                                </div>
                                <?php if($m['siparis_tarih']): ?>
                                <div style="font-size:10px;color:#475569;">📅 <?php echo $m['siparis_tarih']; ?></div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div id="siparis-form-<?php echo $m['id']; ?>" style="display:none;background:#0d1a2d;border:1px solid #3b82f655;border-radius:8px;padding:10px;">
                                <div style="font-size:11px;color:#60a5fa;font-weight:700;margin-bottom:6px;">🛒 Sipariş Bilgisi</div>
                                <div style="margin-bottom:6px;">
                                    <div style="font-size:10px;color:#475569;margin-bottom:3px;">Sipariş No</div>
                                    <input type="text" id="sip-no-<?php echo $m['id']; ?>" placeholder="ör: PO-2025-001"
                                        style="width:100%;background:#141820;border:1px solid #1e2430;border-radius:5px;padding:5px 8px;color:#f1f5f9;font-size:12px;box-sizing:border-box;">
                                </div>
                                <div style="margin-bottom:8px;">
                                    <div style="font-size:10px;color:#475569;margin-bottom:3px;">Miktar (kg)</div>
                                    <input type="number" id="sip-miktar-<?php echo $m['id']; ?>" value="<?php echo max(0, round(($m['opt'] * 2) - $m['stok'])); ?>" min="0"
                                        style="width:100%;background:#141820;border:1px solid #1e2430;border-radius:5px;padding:5px 8px;color:#34d399;font-weight:700;font-size:13px;box-sizing:border-box;">
                                </div>
                                <div style="display:flex;gap:6;">
                                    <button onclick="siparisKaydet(<?php echo $m['id']; ?>)" style="flex:1;background:#10b981;border:none;border-radius:6px;padding:7px;color:#fff;cursor:pointer;font-size:12px;font-weight:700;">
                                        ✅ Kaydet
                                    </button>
                                    <button onclick="siparisFormKapat(<?php echo $m['id']; ?>)" style="background:#1e2430;border:none;border-radius:6px;padding:7px 12px;cursor:pointer;color:#64748b;font-size:12px;">İptal</button>
                                </div>
                            </div>
                            <button id="siparis-btn-<?php echo $m['id']; ?>" onclick="siparisFormAc(<?php echo $m['id']; ?>)"
                                style="background:#1a2535;border:1px solid #3b82f655;border-radius:7px;padding:7px 12px;cursor:pointer;color:#60a5fa;font-size:11px;font-weight:700;width:100%;text-align:center;">
                                🛒 Sipariş Verildi İşaretle
                            </button>
                            <?php endif; ?>
                        </td>
                        
                        <!-- İşlem -->
                        <td style="padding:12px 13px;">
                            <div style="display:flex;gap:6px;">
                                <button onclick="window.location='hammadde-detay.php?id=<?php echo $m['id']; ?>'" title="Detay" style="background:#1e2430;border:none;border-radius:6px;padding:5px 9px;cursor:pointer;font-size:13px;">👁</button>
                                <button onclick="window.location='hammadde-form.php?id=<?php echo $m['id']; ?>'" title="Düzenle" style="background:#1e2430;border:none;border-radius:6px;padding:5px 9px;cursor:pointer;font-size:13px;">✏️</button>
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

<script>
function siparisFormAc(id) {
    document.getElementById('siparis-form-' + id).style.display = 'block';
    document.getElementById('siparis-btn-' + id).style.display = 'none';
}

function siparisFormKapat(id) {
    document.getElementById('siparis-form-' + id).style.display = 'none';
    document.getElementById('siparis-btn-' + id).style.display = 'block';
}

function siparisKaydet(id) {
    const sipNo = document.getElementById('sip-no-' + id).value;
    const miktar = document.getElementById('sip-miktar-' + id).value;
    
    // Validation
    if (!miktar || parseFloat(miktar) <= 0) {
        alert('Lütfen geçerli bir miktar girin.');
        return;
    }
    
    fetch('ajax/siparis-ver.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'hammadde_id=' + id + '&miktar=' + miktar + '&siparis_no=' + encodeURIComponent(sipNo)
    })
    .then(r => {
        if (!r.ok) {
            throw new Error('HTTP Hata: ' + r.status);
        }
        const contentType = r.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return r.text().then(text => {
                throw new Error('Sunucu JSON yerine HTML döndürdü. Hata mesajı: ' + text.substring(0, 200));
            });
        }
        return r.json();
    })
    .then(data => { 
        if (data.success) {
            location.reload(); 
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    })
    .catch(err => {
        console.error('Sipariş kaydetme hatası:', err);
        alert('Bağlantı hatası: ' + err.message);
    });
}

function siparisIptal(id) {
    if (!confirm('Sipariş iptal edilsin mi?')) return;
    
    fetch('ajax/siparis-guncelle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&islem=iptal'
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); });
}
</script>

<?php require_once 'includes/footer.php'; ?>
