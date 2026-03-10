<?php
require_once 'includes/header.php';

$pageTitle = 'Stok Güncelleme';

$buYil = date('Y');
$buAy = date('n');
$aylar = [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$ayAd = $aylar[$buAy];

$oncekiAy = $buAy > 1 ? $buAy - 1 : 12;
$oncekiAyYil = $buAy > 1 ? $buYil : $buYil - 1;
$oncekiAyAd = $aylar[$oncekiAy];

$db = getDB();
$hammaddeler = $db->query("SELECT h.*, ht.ad as tur_adi 
    FROM hammaddeler h 
    LEFT JOIN hammadde_turleri ht ON h.tur_kodu = ht.kod
    WHERE h.is_active = 1 AND h.sk = 'S'
    ORDER BY h.hammadde_ismi")
    ->fetchAll();

// Get tuketim verileri
$tuketimTumu = [];
foreach ($hammaddeler as $h) {
    $tuketimTumu[$h['id']] = getTuketimVerileri($h['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['stok'])) {
        foreach ($_POST['stok'] as $id => $deger) {
            if ($deger !== '') {
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
    
    setFlashMessage('Stok ve tüketim verileri güncellendi.', 'success');
    header('Location: stok-guncelle.php');
    exit;
}

$YIL_RENKLER = [
    2023 => ['border' => '#3b82f6', 'text' => '#60a5fa', 'bg' => '#1a2535'],
    2024 => ['border' => '#8b5cf6', 'text' => '#a78bfa', 'bg' => '#1a2535'],
    2025 => ['border' => '#10b981', 'text' => '#34d399', 'bg' => '#1a2535'],
    2026 => ['border' => '#f59e0b', 'text' => '#fbbf24', 'bg' => '#1a2535'],
];
?>

<style>
.btn-primary { background: linear-gradient(135deg,#3b82f6,#6366f1); color:#fff; border:none; border-radius:8px; padding:10px 22px; cursor:pointer; font-weight:700; font-size:14px; }
</style>

<div style="padding: 24px 28px; max-width: 1400px; margin: 0 auto;">
    <!-- Header -->
    <div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:8;">
        <div>
            <h2 style="font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:4px;">🔄 Stok Güncelleme</h2>
            <p style="color:#475569;font-size:13px;">
                <?php echo count($hammaddeler); ?> standart hammadde · Stok miktarı ve <strong style="color:#38bdf8;"><?php echo $ayAd . ' ' . $buYil; ?></strong> tüketimi güncellenebilir
            </p>
        </div>
        <div style="background:#0c1f2e;border:1px solid #38bdf855;border-radius:8px;padding:8px 16px;">
            <span style="color:#38bdf8;font-size:13px;font-weight:700;">📅 Güncelleme ayı: <?php echo $ayAd . ' ' . $buYil; ?></span>
        </div>
    </div>

    <form method="POST" action="">
        <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#0d1017;border-bottom:2px solid #1e2430;">
                            <th style="padding:11px 14px;text-align:left;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">#</th>
                            <th style="padding:11px 14px;text-align:left;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">HAMMADDE</th>
                            <th style="padding:11px 14px;text-align:left;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">TÜR</th>
                            <th style="padding:11px 14px;text-align:right;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">MEVCUT STOK (kg)</th>
                            <th style="padding:11px 14px;text-align:right;color:#38bdf8;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;background:#0c1f2e;">📦 YENİ STOK (kg)</th>
                            <th style="padding:11px 14px;text-align:right;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;"><?php echo strtoupper($oncekiAyAd); ?> <?php echo $oncekiAyYil; ?> TÜK.</th>
                            <th style="padding:11px 14px;text-align:right;color:#38bdf8;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;background:#0c1f2e;">🗓 <?php echo strtoupper($ayAd); ?> <?php echo $buYil; ?> TÜK. GİR</th>
                            <th style="padding:11px 14px;text-align:right;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">ORT. AYLIK</th>
                            <th style="padding:11px 14px;text-align:right;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">OPTİMUM</th>
                            <th style="padding:11px 14px;text-align:center;color:#475569;font-weight:700;font-size:10px;letter-spacing:0.06em;white-space:nowrap;">DURUM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hammaddeler as $idx => $h): 
                            $stok = (float)$h['stok_miktari'];
                            $opt = (float)$h['hesaplanan_optimum'];
                            
                            // Termin sureleri
                            $terminGun = ($h['akreditif_gun'] ?? 0) + ($h['satici_tedarik_gun'] ?? 0) + ($h['yol_gun'] ?? 0) + ($h['depo_kabul_gun'] ?? 0);
                            
                            // Son 12 ay ortalama
                            $son12 = getSon12AyOrtalama($h['id']);
                            
                            // Onceki ay tuketim
                            $oncekiTuketim = $tuketimTumu[$h['id']][$oncekiAyYil][$oncekiAy] ?? 0;
                            
                            // Bu ay tuketim
                            $buAyTuketim = $tuketimTumu[$h['id']][$buYil][$buAy] ?? null;
                            $buAyDolu = $buAyTuketim !== null && $buAyTuketim > 0;
                            
                            // Durum hesapla
                            $durum = getStokDurum($h);
                            
                            // Ortalama aylik (son 12 ay)
                            $ortAylik = $son12;
                        ?>
                        <tr style="border-bottom:1px solid #1e2430;<?php echo $buAyDolu ? '' : 'background:#f59e0b0a'; ?>;transition:background 0.15s;" 
                            onMouseEnter="this.style.background=<?php echo $buAyDolu ? "'#1a2130'" : "'#1a1505'"; ?>" 
                            onMouseLeave="this.style.background=<?php echo $buAyDolu ? "'transparent'" : "'#f59e0b0a'"; ?>">

                            <!-- Sira -->
                            <td style="padding:10px 14px;color:#334155;font-size:11px;"><?php echo $idx + 1; ?></td>

                            <!-- Hammadde -->
                            <td style="padding:10px 14px;max-width:220px;">
                                <div style="font-weight:700;color:#f1f5f9;font-size:13px;"><?php echo htmlspecialchars($h['hammadde_ismi']); ?></div>
                                <div style="font-size:10px;color:#475569;margin-top:1px;"><?php echo $h['urun_kodu'] ?: '-'; ?></div>
                            </td>

                            <!-- Tur -->
                            <td style="padding:10px 14px;">
                                <span style="background:#1e2430;border-radius:4px;padding:2px 7px;font-size:10px;color:#94a3b8;"><?php echo $h['tur_adi'] ?: '-'; ?></span>
                            </td>

                            <!-- Mevcut Stok -->
                            <td style="padding:10px 14px;text-align:right;font-weight:700;color:<?php echo $durum['renk']; ?>;font-family:monospace;font-size:13px;">
                                <?php echo number_format($stok, 0, ',', '.'); ?>
                            </td>

                            <!-- Yeni Stok -->
                            <td style="padding:6px 8px;background:#0c1f2e;">
                                <input type="number" name="stok[<?php echo $h['id']; ?>]" 
                                    placeholder="<?php echo $stok > 0 ? number_format($stok, 0, ',', '.') : '-'; ?>"
                                    style="width:100%;min-width:110px;background:#0a1520;border:1px solid #38bdf844;border-radius:6px;padding:6px 10px;color:#38bdf8;font-size:13px;font-weight:700;text-align:right;outline:none;font-family:monospace;">
                            </td>

                            <!-- Onceki Ay Tuketim -->
                            <td style="padding:10px 14px;text-align:right;color:#475569;font-family:monospace;font-size:12px;">
                                <?php echo $oncekiTuketim > 0 ? number_format($oncekiTuketim, 0, ',', '.') : '-'; ?>
                            </td>

                            <!-- Bu Ay Tuketim -->
                            <td style="padding:6px 8px;background:#0c1f2e;">
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <?php if ($buAyDolu): ?>
                                    <span style="color:#34d399;font-size:11px;font-weight:700;white-space:nowrap;font-family:monospace;">✓ <?php echo number_format($buAyTuketim, 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                    <input type="number" name="tuketim[<?php echo $h['id']; ?>]" 
                                        placeholder="<?php echo $buAyDolu ? 'düzenle' : 'gir...'; ?>"
                                        style="width:100%;min-width:100px;background:#0a1520;border:1px solid <?php echo $buAyDolu ? '#34d39955' : '#f59e0b55'; ?>;border-radius:6px;padding:6px 10px;text-align:right;font-size:13px;font-weight:700;color:<?php echo $buAyDolu ? '#34d399' : '#fbbf24'; ?>;outline:none;font-family:monospace;">
                                </div>
                            </td>

                            <!-- Ort Aylik -->
                            <td style="padding:10px 14px;text-align:right;color:#94a3b8;font-family:monospace;font-size:12px;">
                                <?php echo $ortAylik > 0 ? number_format($ortAylik, 0, ',', '.') : '-'; ?>
                            </td>

                            <!-- Optimum -->
                            <td style="padding:10px 14px;text-align:right;font-family:monospace;font-size:12px;">
                                <?php if ($opt > 0): ?>
                                    <span style="color:#64748b;"><?php echo number_format($opt, 0, ',', '.'); ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>

                            <!-- Durum -->
                            <td style="padding:10px 14px;text-align:center;">
                                <span style="padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;background:<?php echo $durum['renk']; ?>22;color:<?php echo $durum['renk']; ?>;border:1px solid <?php echo $durum['renk']; ?>44;">
                                    <?php echo $durum['label'] ?: 'RAHAT'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Alt Bilgi -->
            <div style="background:#0f1117;border-top:1px solid #1e2430;padding:12px 18px;display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
                <div style="font-size:11px;color:#475569;">
                    <span style="color:#fbbf24;font-weight:700;">■</span> Sarı arka plan = <?php echo $ayAd . ' ' . $buYil; ?> tüketimi henüz girilmemiş
                </div>
                <div style="font-size:11px;color:#475569;">
                    <span style="color:#34d399;font-weight:700;">✓</span> Yeşil = tüketim girilmiş
                </div>
                <div style="margin-left:auto;">
                    <button type="submit" class="btn-primary">Tüm Değişiklikleri Kaydet</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
