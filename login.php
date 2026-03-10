<?php
require_once 'includes/config.php';

// Zaten giris yapmismi kontrol et
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Kullanici var mi kontrol et - yoksa setup'a yonlendir
$db = getDB();
$userCount = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch()['count'];

if ($userCount == 0) {
    header('Location: setup.php');
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Kullanici adi veya sifre hatali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giris - Hammadde Takip</title>
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
        
        .login-container {
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
            background: linear-gradient(135deg,#3b82f6,#6366f1);
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
        
        .login-card {
            background: #141820;
            border: 1px solid #1e2430;
            border-radius: 16px;
            padding: 32px;
        }
        
        .login-title {
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }
        .field-input::placeholder { color: #334155; }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            margin-top: 8px;
        }
        .btn-primary:hover { opacity: 0.88; }
        
        .error-message {
            background: #ef444422;
            border: 1px solid #ef444455;
            color: #ef4444;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .footer-text {
            text-align: center;
            color: #475569;
            font-size: 11px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo -->
        <div class="logo-section">
            <div class="logo-icon">⬡</div>
            <div class="logo-title">HAMMADDE TAKİP</div>
            <div class="logo-subtitle">VERİ GİRİŞ SİSTEMİ</div>
        </div>
        
        <!-- Login Form -->
        <div class="login-card">
            <div class="login-title">Giriş Yap</div>
            
            <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <label class="field-label">Kullanıcı Adı</label>
                <input type="text" name="username" required autofocus class="field-input" placeholder="Kullanıcı adınızı girin">
                
                <label class="field-label">Şifre</label>
                <input type="password" name="password" required class="field-input" placeholder="Şifrenizi girin">
                
                <button type="submit" class="btn-primary">Giriş Yap</button>
            </form>
        </div>
        
        <p class="footer-text">&copy; <?php echo date('Y'); ?> Hammadde Takip Sistemi</p>
    </div>
</body>
</html>