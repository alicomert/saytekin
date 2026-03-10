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
            padding: 8px 16px;
            border-radius: 7px;
            border: 1px solid #1e2430;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            margin: 0 4px;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .nav-btn:hover { border-color: #2d3748; color: #94a3b8; }
        
        .nav-btn.active-liste { border-color: #3b82f6; background: #1d3557; color: #60a5fa; }
        .nav-btn.active-ihtiyac { border-color: #f59e0b; background: #2a1f0a; color: #fbbf24; }
        .nav-btn.active-siparisler { border-color: #34d399; background: #0d2018; color: #34d399; }
        .nav-btn.active-fiyatlar { border-color: #a78bfa; background: #1a1535; color: #a78bfa; }
        .nav-btn.active-karsilastirma { border-color: #10b981; background: #0d2018; color: #10b981; }
        .nav-btn.active-istatistik { border-color: #8b5cf6; background: #1e1535; color: #a78bfa; }
        .nav-btn.active-stokguncelle { border-color: #38bdf8; background: #0c1f2e; color: #38bdf8; }
        .nav-btn.active-sabit { border-color: #64748b; background: #1e293b; color: #94a3b8; }
        
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
        
        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 240px;
            background: #0d1017;
            border-right: 1px solid #1e2430;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #1e2430;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar-logo-text {
            font-size: 15px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.03em;
        }
        .sidebar-logo-subtext {
            font-size: 10px;
            color: #475569;
            letter-spacing: 0.06em;
        }
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 0;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            margin: 2px 12px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .sidebar-link:hover {
            background: rgba(59,130,246,0.1);
            color: #e2e8f0;
        }
        .sidebar-link.active {
            background: rgba(59,130,246,0.15);
            border-left: 3px solid #3b82f6;
            color: #60a5fa;
            margin-left: 9px;
        }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid #1e2430;
        }
        .main-content {
            flex: 1;
            margin-left: 240px;
            display: flex;
            flex-direction: column;
        }
        .top-header {
            height: 60px;
            background: #0d1017;
            border-bottom: 1px solid #1e2430;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .content-area {
            flex: 1;
            padding: 24px 28px;
            animation: fadeIn 0.3s;
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">⬡</div>
                    <div>
                        <div class="sidebar-logo-text">HAMMADDE TAKİP</div>
                        <div class="sidebar-logo-subtext">VERİ GİRİŞ SİSTEMİ</div>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php
                $navItems = [
                    ['url' => 'index.php', 'key' => 'liste', 'label' => '📋 Tüm Liste', 'page' => 'index'],
                    ['url' => 'ihtiyac.php', 'key' => 'ihtiyac', 'label' => '⚠️ İhtiyaç Listesi', 'page' => 'ihtiyac', 'badge' => $kritikSayi],
                    ['url' => 'siparisler.php', 'key' => 'siparisler', 'label' => '🛒 Siparişler' . ($aktifSiparisler > 0 ? " ($aktifSiparisler)" : ''), 'page' => 'siparisler'],
                    ['url' => 'fiyatlar.php', 'key' => 'fiyatlar', 'label' => '💰 Fiyat Tablosu', 'page' => 'fiyatlar'],
                    ['url' => 'karsilastirma.php', 'key' => 'karsilastirma', 'label' => '⚖️ Karşılaştırma', 'page' => 'karsilastirma'],
                    ['url' => 'istatistik.php', 'key' => 'istatistik', 'label' => '📊 İstatistikler', 'page' => 'istatistik'],
                    ['url' => 'stok-guncelle.php', 'key' => 'stokguncelle', 'label' => '🔄 Stok Güncelleme', 'page' => 'stok-guncelle'],
                ];
                
                if (isAdmin()) {
                    $navItems[] = ['url' => 'sabit-tanimlar.php', 'key' => 'sabit', 'label' => '⚙️ Sabit Tanımlar', 'page' => 'sabit-tanimlar'];
                }
                
                foreach ($navItems as $nav):
                    $isActive = strpos($_SERVER['PHP_SELF'], $nav['page']) !== false;
                    $activeClass = $isActive ? 'active' : '';
                ?>
                <a href="<?php echo $nav['url']; ?>" class="sidebar-link <?php echo $activeClass; ?>">
                    <span><?php echo $nav['label']; ?></span>
                    <?php if ($nav['key'] === 'ihtiyac' && $kritikSayi > 0): ?>
                    <span style="background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:auto;"><?php echo $kritikSayi; ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="btn-secondary" style="width:100%;justify-content:center;">🚪 Çıkış</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div style="display:flex;align-items:center;gap:16px;">
                    <span style="font-size:12px;color:#475569;">
                        <?php 
                        $totalCount = count(getHammaddeler());
                        echo $totalCount . ' kayıt';
                        ?>
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <?php if ($currentPage !== 'hammadde-form.php'): ?>
                    <a href="hammadde-form.php" class="btn-primary">+ Yeni Hammadde</a>
                    <?php endif; ?>
                    
                    <?php if ($currentPage === 'hammadde-form.php' || $currentPage === 'hammadde-detay.php'): ?>
                    <a href="index.php" class="btn-secondary">← Listeye Dön</a>
                    <?php endif; ?>
                    
                    <!-- Döviz Kuru ve Güncelleme -->
                    <?php if ($kurlar): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-left:12px;padding-left:12px;border-left:1px solid #1e2430;">
                        <span style="font-size:11px;color:#64748b;">USD</span>
                        <span style="font-size:13px;color:#60a5fa;font-weight:700;"><?php echo number_format($kurlar['USD_TRY'] ?? 0, 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">

<!-- Güncelleme Modal -->
<div id="update-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#141820;border:1px solid #1e2430;border-radius:12px;padding:24px;width:90%;max-width:500px;max-height:80vh;overflow-y:auto;">
        <h3 style="color:#f1f5f9;margin-bottom:16px;">🔄 Sistem Güncellemesi</h3>
        <div id="update-info" style="background:#0f1117;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;">
            <p style="color:#94a3b8;margin-bottom:8px;">GitHub'dan yeni güncelleme bulundu!</p>
            <p style="color:#64748b;margin:4px 0;">Yazar: <span id="update-author" style="color:#f1f5f9;">-</span></p>
            <p style="color:#64748b;margin:4px 0;">Tarih: <span id="update-date" style="color:#f1f5f9;">-</span></p>
            <p style="color:#64748b;margin:4px 0;">Mesaj: <span id="update-message" style="color:#f1f5f9;">-</span></p>
        </div>
        <div id="update-progress" style="display:none;margin-bottom:16px;">
            <div style="background:#0f1117;border-radius:8px;padding:12px;">
                <div id="progress-steps"></div>
            </div>
        </div>
        <div id="update-actions" style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeUpdateModal()" class="btn-secondary">İptal</button>
            <button onclick="startUpdate()" class="btn-primary">Güncellemeyi Uygula</button>
        </div>
        <div id="update-error" style="display:none;background:#ef444433;border:1px solid #ef444455;color:#ef4444;padding:12px;border-radius:8px;margin-top:16px;font-size:12px;">
            <strong>Hata oluştu!</strong>
            <p id="error-message" style="margin:4px 0 0 0;"></p>
            <button onclick="rollbackUpdate()" style="background:#ef4444;border:none;border-radius:4px;padding:6px 12px;color:#fff;font-size:11px;font-weight:600;cursor:pointer;margin-top:8px;">Geri Al</button>
        </div>
        <div id="update-success" style="display:none;background:#10b98133;border:1px solid #10b98155;color:#10b981;padding:12px;border-radius:8px;margin-top:16px;font-size:12px;">
            ✅ <strong>Güncelleme başarılı!</strong>
            <p style="margin:4px 0 0 0;">Sistem yeni sürüme güncellendi. Sayfa yenileniyor...</p>
        </div>
    </div>
</div>

<script>
let updateData = null;

function showUpdateModal() {
    document.getElementById('update-modal').style.display = 'flex';
    checkUpdateDetails();
}

function closeUpdateModal() {
    document.getElementById('update-modal').style.display = 'none';
    resetUpdateModal();
}

function resetUpdateModal() {
    document.getElementById('update-progress').style.display = 'none';
    document.getElementById('update-actions').style.display = 'flex';
    document.getElementById('update-error').style.display = 'none';
    document.getElementById('update-success').style.display = 'none';
    document.getElementById('progress-steps').innerHTML = '';
}

function checkUpdateDetails() {
    fetch('ajax/check-update.php')
        .then(r => r.json())
        .then(data => {
            updateData = data;
            if (data.available) {
                document.getElementById('update-author').textContent = data.author || 'Bilinmiyor';
                document.getElementById('update-date').textContent = data.date ? new Date(data.date).toLocaleString('tr-TR') : '-';
                document.getElementById('update-message').textContent = data.message || 'Bilinmiyor';
            }
        });
}

function checkUpdateManually() {
    const btn = document.getElementById('btn-check-update');
    if (!btn) return;
    
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<span style="font-size:14px;">⏳</span><span>Kontrol ediliyor...</span>';
    btn.disabled = true;
    
    fetch('ajax/check-update.php')
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
            
            if (data.error) {
                alert('❌ Hata: ' + data.error);
            } else if (data.available) {
                updateData = data;
                showUpdateButton();
                showUpdateModal();
            } else {
                alert('✅ Sistem güncel!\n\nMevcut sürüm: ' + (data.current_sha ? data.current_sha.substring(0, 7) : 'Bilinmiyor') + '\nSon kontrol: ' + new Date().toLocaleString('tr-TR'));
            }
        })
        .catch(err => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
            alert('❌ Bağlantı hatası: ' + err.message);
        });
}

function startUpdate() {
    document.getElementById('update-actions').style.display = 'none';
    document.getElementById('update-progress').style.display = 'block';
    
    fetch('ajax/apply-update.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            updateProgressUI(data.steps);
            
            if (data.success) {
                document.getElementById('update-success').style.display = 'block';
                setTimeout(() => location.reload(), 2000);
            } else {
                document.getElementById('error-message').textContent = data.error || 'Bilinmeyen hata';
                document.getElementById('update-error').style.display = 'block';
            }
        })
        .catch(err => {
            document.getElementById('error-message').textContent = err.message;
            document.getElementById('update-error').style.display = 'block';
        });
}

function updateProgressUI(steps) {
    const container = document.getElementById('progress-steps');
    container.innerHTML = steps.map(s => `
        <div style="display:flex;align-items:center;gap:8px;margin:6px 0;font-size:12px;">
            <span style="color:${s.status === 'completed' ? '#10b981' : s.status === 'error' ? '#ef4444' : '#f59e0b'};">
                ${s.status === 'completed' ? '✓' : s.status === 'error' ? '✕' : '⏳'}
            </span>
            <span style="color:#e2e8f0;">${s.name}</span>
            ${s.detail ? `<span style="color:#64748b;margin-left:auto;font-size:11px;">${s.detail}</span>` : ''}
        </div>
    `).join('');
}

function rollbackUpdate() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Geri alınıyor...';
    
    fetch('ajax/rollback-update.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Sistem önceki sürüme geri yüklendi. Sayfa yenileniyor...');
                location.reload();
            } else {
                alert('Geri alma başarısız: ' + data.error);
                btn.disabled = false;
                btn.textContent = 'Geri Al';
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    checkUpdateOnLoad();
});

function checkUpdateOnLoad() {
    fetch('ajax/check-update.php')
        .then(r => r.json())
        .then(data => {
            if (data.available) {
                showUpdateButton();
            }
        });
}

function showUpdateButton() {
    const btnCheck = document.getElementById('btn-check-update');
    const btnUpdate = document.getElementById('btn-do-update');
    if (btnCheck) btnCheck.style.display = 'none';
    if (btnUpdate) btnUpdate.style.display = 'flex';
}

function hideUpdateButton() {
    const btnCheck = document.getElementById('btn-check-update');
    const btnUpdate = document.getElementById('btn-do-update');
    if (btnCheck) btnCheck.style.display = 'flex';
    if (btnUpdate) btnUpdate.style.display = 'none';
}
</script>
<?php endif; ?>
