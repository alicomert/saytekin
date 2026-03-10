<?php
require_once 'includes/config.php';

// Eğer zaten kullanıcı varsa login'e yönlendir
$db = getDB();
$userCount = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch()['count'];

if ($userCount > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validasyon
    if (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Kullanici adi, sifre ve ad soyad zorunludur.';
    } elseif (strlen($username) < 3) {
        $error = 'Kullanici adi en az 3 karakter olmalidir.';
    } elseif (strlen($password) < 6) {
        $error = 'Sifre en az 6 karakter olmalidir.';
    } elseif ($password !== $password_confirm) {
        $error = 'Sifreler eslesmiyor.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Gecerli bir e-posta adresi girin.';
    } else {
        // Kullanıcıyı oluştur
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->prepare("INSERT INTO users (username, password_hash, email, full_name, role, is_active, created_at) 
                         VALUES (?, ?, ?, ?, 'admin', 1, NOW())")
               ->execute([$username, $password_hash, $email ?: $username . '@localhost', $full_name]);
            
            $success = 'Kurulum basariyla tamamlandi! Simdi giris yapabilirsiniz.';
            
            // 3 saniye sonra login'e yönlendir
            header('Refresh: 3; URL=login.php');
            
        } catch (PDOException $e) {
            $error = 'Kullanici olusturulurken hata: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - <?php echo SITE_TITLE; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0f1117; color: #e2e8f0; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white">Sistem Kurulumu</h1>
            <p class="text-gray-500 text-sm mt-1">Ilk yoneticiyi olusturun</p>
        </div>
        
        <div class="bg-gray-800 border border-gray-700 rounded-2xl p-8 shadow-2xl">
            <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-6 text-sm">
                <?php echo $success; ?>
            </div>
            <?php elseif ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-6 text-sm">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Ad Soyad *</label>
                        <input type="text" name="full_name" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                            placeholder="Orn: Ahmet Yilmaz">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Kullanici Adi *</label>
                        <input type="text" name="username" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                            placeholder="Orn: admin">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Sifre *</label>
                        <input type="password" name="password" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                            placeholder="******">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Sifre Tekrar *</label>
                        <input type="password" name="password_confirm" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white"
                            placeholder="******">
                    </div>
                </div>
                
                <button type="submit" 
                    class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg">
                    Kurulumu Tamamla
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
