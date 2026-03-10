<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage != 'login.php' && $currentPage != 'logout.php') {
    requireAuth();
}

$user = getCurrentUser();
$flashMessage = getFlashMessage();
$pageTitle = $pageTitle ?? SITE_TITLE;

// Kritik stok sayisi
$kritikSayi = 0;
if (isLoggedIn()) {
    $db = getDB();
    $hammaddeler = getHammaddeler();
    foreach ($hammaddeler as $h) {
        $durum = getStokDurum($h);
        if ($durum['kritik']) $kritikSayi++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_TITLE; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: { 900: '#0f1117', 800: '#141820', 700: '#1e2430', 600: '#2d3748' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f1117; color: #e2e8f0; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #141820; }
        ::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3d4a5c; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .data-table th {
            background-color: #0d1017; color: #64748b; font-size: 0.75rem;
            font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
            padding: 0.75rem 1rem; text-align: left; white-space: nowrap;
        }
        .data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #1e2430; }
        .data-table tr:hover td { background-color: #1a2130; }
    </style>
</head>
<body class="min-h-screen font-sans">
    <?php if (isLoggedIn()): ?>
    <!-- Header -->
    <header class="bg-dark-900 border-b border-dark-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-white tracking-wide">HAMMADDE TAKIP</div>
                        <div class="text-xs text-gray-500 tracking-wider">VERI GIRIS SISTEMI</div>
                    </div>
                </a>
                
                <!-- Navigation -->
                <nav class="hidden lg:flex items-center gap-1">
                    <a href="index.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo strpos($_SERVER['PHP_SELF'], 'index') !== false ? 'bg-blue-900/30 text-blue-400 border border-blue-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-list mr-2"></i>Tum Liste
                    </a>
                    <a href="ihtiyac.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all relative <?php echo strpos($_SERVER['PHP_SELF'], 'ihtiyac') !== false ? 'bg-yellow-900/30 text-yellow-400 border border-yellow-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Ihtiyac
                        <?php if ($kritikSayi > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center"><?php echo $kritikSayi; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="siparisler.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo strpos($_SERVER['PHP_SELF'], 'siparisler') !== false ? 'bg-green-900/30 text-green-400 border border-green-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-shopping-cart mr-2"></i>Siparisler
                    </a>
                    <a href="fiyatlar.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo strpos($_SERVER['PHP_SELF'], 'fiyatlar') !== false ? 'bg-purple-900/30 text-purple-400 border border-purple-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-dollar-sign mr-2"></i>Fiyatlar
                    </a>
                    <a href="karsilastirma.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo strpos($_SERVER['PHP_SELF'], 'karsilastirma') !== false ? 'bg-emerald-900/30 text-emerald-400 border border-emerald-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-balance-scale mr-2"></i>Karsilastirma
                    </a>
                    <a href="stok-guncelle.php" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?php echo strpos($_SERVER['PHP_SELF'], 'stok-guncelle') !== false ? 'bg-cyan-900/30 text-cyan-400 border border-cyan-500' : 'text-gray-500 hover:bg-dark-700'; ?>">
                        <i class="fas fa-sync mr-2"></i>Stok Guncelle
                    </a>
                </nav>
                
                <!-- User Menu -->
                <div class="flex items-center gap-4">
                    <a href="hammadde-form.php" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-bold transition-opacity">
                        <i class="fas fa-plus"></i>
                        <span>Yeni Hammadde</span>
                    </a>
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span class="hidden md:inline text-sm"><?php echo $user['full_name'] ?? 'Kullanici'; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 top-full mt-2 w-48 bg-dark-800 border border-dark-700 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <a href="logout.php" class="block px-4 py-3 text-sm text-gray-400 hover:text-white hover:bg-dark-700 rounded-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i>Cikis Yap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div id="flash-message" class="fixed top-20 right-6 z-50 animate-slide-in">
        <div class="px-6 py-4 rounded-lg shadow-2xl text-white font-bold <?php echo $flashMessage['type'] === 'success' ? 'bg-green-500' : ($flashMessage['type'] === 'error' ? 'bg-red-500' : 'bg-yellow-500'); ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('flash-message').style.opacity = '0';
            setTimeout(() => document.getElementById('flash-message').remove(), 300);
        }, 3000);
    </script>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-6">
