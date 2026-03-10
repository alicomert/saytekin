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
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->prepare("INSERT INTO users (username, password_hash, email, full_name, role, is_active, created_at) 
                         VALUES (?, ?, ?, ?, 'admin', 1, NOW())")
               ->execute([$username, $password_hash, $email ?: $username . '@localhost', $full_name]);
            
            $success = 'Kurulum basariyla tamamlandi! Simdi giris yapabilirsiniz.';
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
    <title>Kurulum - Hammadde Takip</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0f1117; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #e2e8f0; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        
        .setup-container {
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg,#10b981,#34d399);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
        }
        
        .logo-title {
            font-size: 24px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 4px;
        }
        
        .logo-subtitle {
            font-size: 13px;
            color: #64748b;
        }
        
        .setup-card {
            background: #141820;
            border: 1px solid #1e2430;
            border-radius: 16px;
            padding: 32px;
        }
        
        .setup-title {
            font-size: 18px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 24px;
        }
        
        .field-label {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .field-input {
            width: 100%;
            background: #0f1117;
            border: 1px solid #1e2430;
            border-radius: 8px;
            padding: 12px 14px;
            color: #e2e8f0;
            font-size: 14px;
            margin-bottom: 16px;
            transition: border-color 0.2s;
        }
        .field-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }
        .field-input::placeholder { color: #334155; }
        
        .btn-success {
            width: 100%;
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            margin-top: 8px;
        }
        .btn-success:hover { opacity: 0.88; }
        
        .error-message {
            background: #ef444422;
            border: 1px solid #ef444455;
            color: #ef4444;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .success-message {
            background: #10b98122;
            border: 1px solid #10b98155;
            color: #34d399;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <!-- Logo -->
        <div class="logo-section">
            <div class="logo-icon">⚙️</div>
            <div class="logo-title">SİSTEM KURULUMU</div>
            <div class="logo-subtitle">İlk yöneticiyi oluşturun</div>
        </div>
        
        <!-- Setup Form -->
        <div class="setup-card">
            <div class="setup-title">Yönetici Hesabı</div>
            
            <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="">
                <label class="field-label">Ad Soyad *</label>
                <input type="text" name="full_name" required class="field-input" placeholder="Örn: Ahmet Yılmaz">
                
                <label class="field-label">Kullanıcı Adı *</label>
                <input type="text" name="username" required class="field-input" placeholder="Örn: admin">
                
                <label class="field-label">E-posta</label>
                <input type="email" name="email" class="field-input" placeholder="ornek@email.com">
                
                <label class="field-label">Şifre *</label>
                <input type="password" name="password" required class="field-input" placeholder="En az 6 karakter">
                
                <label class="field-label">Şifre Tekrar *</label>
                <input type="password" name="password_confirm" required class="field-input" placeholder="Şifrenizi tekrar girin">
                
                <button type="submit" class="btn-success">Kurulumu Tamamla</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>