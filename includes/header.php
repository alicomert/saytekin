<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage != 'login.php' && $currentPage != 'logout.php' && $currentPage != 'setup.php') {
    requireAuth();
}

$user = getCurrentUser();
$flashMessage = getFlashMessage();
$pageTitle = $pageTitle ?? SITE_TITLE;

// Kritik stok sayisi
$kritikSayi = 0;
if (isLoggedIn()) {
    $hammaddeler = getHammaddeler();
    foreach ($hammaddeler as $h) {
        $durum = getStokDurum($h);
        if ($durum['kritik']) $kritikSayi++;
    }
}

// Aktif siparis sayisi
$aktifSiparisler = 0;
if (isLoggedIn()) {
    $aktifSiparisler = countAktifSiparisler();
}

// Doviz kurlari
$kurlar = getDovizKurlari();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Hammadde Takip</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0f1117; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #e2e8f0; 
            min-height: 100vh;
            font-size: 13px;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #141820; }
        ::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 3px; }
        input, select, textarea { font-family: inherit; }
        input:focus, select:focus, textarea:focus { 
            outline: none; 
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2); 
        }
        @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideIn { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
        
        .btn-primary { 
            background: linear-gradient(135deg,#3b82f6,#6366f1); 
            color:#fff; 
            border:none; 
            border-radius:8px; 
            padding:10px 22px; 
            cursor:pointer; 
            font-weight:700; 
            font-size:13px; 
            transition:opacity 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary:hover { opacity:0.88; }
        
        .btn-secondary { 
            background:#1e2430; 
            color:#94a3b8; 
            border:1px solid #2d3748; 
            border-radius:8px; 
            padding:10px 18px; 
            cursor:pointer; 
            font-size:13px; 
            transition:all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-secondary:hover { background:#252f40; color:#e2e8f0; }
        
        .btn-danger { 
            background:#2d1a1a; 
            color:#ef4444; 
            border:1px solid #ef444433; 
            border-radius:8px; 
            padding:8px 14px; 
            cursor:pointer; 
            font-size:13px; 
            font-weight: 600;
        }
        .btn-danger:hover { background:#3d2020; }
        
        .field-label { 
            display:block; 
            font-size:11px; 
            color:#64748b; 
            margin-bottom:5px; 
            letter-spacing:0.06em; 
            text-transform:uppercase; 
            font-weight:600; 
        }
        .field-input { 
            width:100%; 
            background:#0f1117; 
            border:1px solid #1e2430; 
            border-radius:8px; 
            padding:9px 12px; 
            color:#e2e8f0; 
            font-size:13px; 
            transition:border-color 0.2s; 
        }
        .field-input::placeholder { color:#334155; }
        
        .section-title { 
            font-size:12px; 
            font-weight:700; 
            letter-spacing:0.1em; 
            text-transform:uppercase; 
            color:#64748b; 
            margin-bottom:14px; 
            padding-bottom:8px; 
            border-bottom:1px solid #1e2430; 
        }
        
        .card { 
            background:#141820; 
            border:1px solid #1e2430; 
            border-radius:12px; 
            padding:20px; 
        }
        
        /* Data Table Styles */
        .data-table { width:100%; border-collapse:collapse; min-width:1200px; }
        .data-table th {
            background:#0d1017;
            border-bottom:1px solid #1e2430;
            padding:11px 12px;
            text-align:left;
            font-size:10px;
            color:#475569;
            letter-spacing:0.07em;
            font-weight:700;
            white-space:nowrap;
            text-transform: uppercase;
        }
        .data-table td { 
            padding:11px 12px; 
            border-bottom:1px solid #1e2430;
            font-size: 13px;
        }
        .data-table tr { cursor: default; transition: background 0.15s; }
        .data-table tr:hover { background:#1a2130; }
        
        /* Form Elements */
        .month-input { 
            width:100%; 
            background:#0f1117; 
            border:1px solid #1e2430; 
            border-radius:6px; 
            padding:7px 8px; 
            color:#e2e8f0; 
            font-size:12px; 
            text-align:right; 
        }
        .month-input::placeholder { color:#2d3748; }
        
        .year-header { 
            font-size:13px; 
            font-weight:700; 
            color:#f1f5f9; 
            padding:6px 10px; 
            border-radius:6px; 
            display:inline-block; 
        }
        
        /* Status Colors */
        .status-green { color: #34d399; }
        .status-yellow { color: #eab308; }
        .status-orange { color: #f97316; }
        .status-red { color: #ef4444; }
        .status-gray { color: #94a3b8; }
        
        /* Year Colors */
        .yil-2023 { border-color: #3b82f6; color: #60a5fa; }
        .yil-2024 { border-color: #8b5cf6; color: #a78bfa; }
        .yil-2025 { border-color: #10b981; color: #34d399; }
        .yil-2026 { border-color: #f59e0b; color: #fbbf24; }
        
        /* Nav Button Active States */
        .nav-btn {
            padding: 7px 14px;
            border-radius: 7px;
            border: 1px solid #1e2430;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .nav-btn:hover { border-color: #2d3748; color: #94a3b8; }
        
        .nav-btn.active-liste { border-color: #3b82f6; background: #1d3557; color: #60a5fa; }
        .nav-btn.active-ihtiyac { border-color: #f59e0b; background: #2a1f0a; color: #fbbf24; }
        .nav-btn.active-siparisler { border-color: #34d399; background: #0d2018; color: #34d399; }
        .nav-btn.active-fiyatlar { border-color: #a78bfa; background: #1a1535; color: #a78bfa; }
        .nav-btn.active-karsilastirma { border-color: #10b981; background: #0d2018; color: #10b981; }
        .nav-btn.active-stokguncelle { border-color: #38bdf8; background: #0c1f2e; color: #38bdf8; }
        
        .badge {
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }
        
        /* Flash Messages */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 11px 20px;
            border-radius: 8px;
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            animation: slideIn 0.3s;
            font-size: 14px;
            color: #fff;
        }
        .flash-success { background: #10b981; }
        .flash-error { background: #ef4444; }
        .flash-warning { background: #f59e0b; }
        
        /* Utility */
        .text-muted { color: #64748b; }
        .text-light { color: #94a3b8; }
        .text-white { color: #f1f5f9; }
        .font-bold { font-weight: 700; }
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 12px; }
        .text-base { font-size: 13px; }
        .text-lg { font-size: 15px; }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Döviz Kur Bandı -->
    <?php if ($kurlar): ?>
    <div style="background:#0a0e15;border-bottom:1px solid #1e2430;padding:5px 24px;display:flex;gap:20;font-size:11;overflow-x:auto;">
        <?php
        $kurItems = [
            ['label' => 'USD/TRY', 'val' => $kurlar['USD_TRY'] ?? 0, 'renk' => '#60a5fa'],
            ['label' => 'EUR/TRY', 'val' => $kurlar['EUR_TRY'] ?? 0, 'renk' => '#a78bfa'],
            ['label' => 'GBP/TRY', 'val' => $kurlar['GBP_TRY'] ?? 0, 'renk' => '#34d399'],
            ['label' => 'EUR/USD', 'val' => $kurlar['EUR_USD'] ?? 0, 'renk' => '#fbbf24'],
            ['label' => 'GBP/USD', 'val' => $kurlar['GBP_USD'] ?? 0, 'renk' => '#f97316'],
        ];
        foreach ($kurItems as $k): 
            if ($k['val'] > 0):
        ?>
        <div style="display:flex;gap:5;align-items:center;white-space:nowrap;">
            <span style="color:#475569;"><?php echo $k['label']; ?></span>
            <span style="color:<?php echo $k['renk']; ?>;font-weight:700;"><?php echo number_format($k['val'], 4); ?></span>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
        <div style="margin-left:auto;color:#334155;white-space:nowrap;">🕐 <?php echo date('d.m.Y H:i'); ?></div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div style="border-bottom:1px solid #1e2430;padding:0 28px;display:flex;align-items:center;justify-content:space-between;height:60px;background:#0d1017;position:sticky;top:0;z-index:100;">
        <div style="display:flex;align-items:center;gap:12;">
            <div style="width:34;height:34;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:10;display:flex;align-items:center;justify-content:center;font-size:16;color:#fff;">⬡</div>
            <div>
                <div style="font-size:15;font-weight:700;color:#f1f5f9;letter-spacing:0.03em;">HAMMADDE TAKİP</div>
                <div style="font-size:10;color:#475569;letter-spacing:0.06em;">VERİ GİRİŞ SİSTEMİ</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8;">
            <?php
            $navItems = [
                ['url' => 'index.php', 'key' => 'liste', 'label' => '📋 Tüm Liste', 'page' => 'index'],
                ['url' => 'ihtiyac.php', 'key' => 'ihtiyac', 'label' => '⚠️ İhtiyaç Listesi', 'page' => 'ihtiyac', 'badge' => $kritikSayi],
                ['url' => 'siparisler.php', 'key' => 'siparisler', 'label' => '🛒 Siparişler' . ($aktifSiparisler > 0 ? " ($aktifSiparisler)" : ''), 'page' => 'siparisler'],
                ['url' => 'fiyatlar.php', 'key' => 'fiyatlar', 'label' => '💰 Fiyat Tablosu', 'page' => 'fiyatlar'],
                ['url' => 'karsilastirma.php', 'key' => 'karsilastirma', 'label' => '⚖️ Karşılaştırma', 'page' => 'karsilastirma'],
                ['url' => 'stok-guncelle.php', 'key' => 'stokguncelle', 'label' => '🔄 Stok Güncelleme', 'page' => 'stok-guncelle'],
            ];
            
            foreach ($navItems as $nav):
                $isActive = strpos($_SERVER['PHP_SELF'], $nav['page']) !== false;
                $activeClass = $isActive ? 'active-' . $nav['key'] : '';
            ?>
            <a href="<?php echo $nav['url']; ?>" class="nav-btn <?php echo $activeClass; ?>">
                <?php echo $nav['label']; ?>
                <?php if ($nav['key'] === 'ihtiyac' && $kritikSayi > 0): ?>
                <span class="badge"><?php echo $kritikSayi; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            
            <div style="width:1;height:24;background:#1e2430;margin:0 4px;"></div>
            
            <span style="font-size:12;color:#475569;">
                <?php 
                $totalCount = count(getHammaddeler());
                echo $totalCount . ' kayıt';
                ?>
            </span>
            
            <?php if ($currentPage !== 'hammadde-form.php'): ?>
            <a href="hammadde-form.php" class="btn-primary">+ Yeni Hammadde</a>
            <?php endif; ?>
            
            <?php if ($currentPage === 'hammadde-form.php' || $currentPage === 'hammadde-detay.php'): ?>
            <a href="index.php" class="btn-secondary">← Listeye Dön</a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn-secondary" style="margin-left: 8px;">Çıkış</a>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div id="flash-message" class="flash-message flash-<?php echo $flashMessage['type']; ?>">
        <?php echo $flashMessage['message']; ?>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('flash-message');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.3s';
                setTimeout(() => el.remove(), 300);
            }
        }, 3000);
    </script>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div style="padding:24px 28px;max-width:1400px;margin:0 auto;animation:fadeIn 0.3s;">