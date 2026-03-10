<?php
require_once 'includes/config.php';

// Zaten giris yapmismi kontrol et
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanici adi ve sifre gereklidir.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Session baslat - 1 hafta (604800 saniye)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Son giris zamanini guncelle
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Kullanici adi veya sifre hatali.';
        }
    }
}

$pageTitle = 'Giris';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_TITLE; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: { 900: '#0f1117', 800: '#141820', 700: '#1e2430' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f1117; color: #e2e8f0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-4 shadow-2xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Hammadde Takip</h1>
            <p class="text-gray-500 text-sm mt-1">Veri Giris Sistemi</p>
        </div>
        
        <!-- Login Form -->
        <div class="bg-dark-800 border border-dark-700 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-bold text-white mb-6">Giris Yap</h2>
            
            <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6 text-sm">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Kullanici Adi</label>
                        <input type="text" name="username" required autofocus
                            class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-colors"
                            placeholder="Kullanici adinizi girin">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Sifre</label>
                        <input type="password" name="password" required
                            class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-blue-500 transition-colors"
                            placeholder="Sifrenizi girin">
                    </div>
                </div>
                
                <button type="submit" 
                    class="w-full mt-6 bg-gradient-to-r from-blue-500 to-indigo-600 hover:opacity-90 text-white font-bold py-3 px-4 rounded-lg transition-opacity">
                    Giris Yap
                </button>
            </form>
            
            <div class="mt-6 pt-6 border-t border-dark-700 text-center">
                <p class="text-xs text-gray-500">
                    Varsayilan: admin / admin123
                </p>
            </div>
        </div>
        
        <p class="text-center text-gray-600 text-xs mt-6">
            &copy; <?php echo date('Y'); ?> Hammadde Takip Sistemi
        </p>
    </div>
</body>
</html>
