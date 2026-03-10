<?php
session_start();

// Session süresi: 1 hafta (7 gün)
ini_set('session.gc_maxlifetime', 604800);
ini_set('session.cookie_lifetime', 604800);

// Hata raporlama
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'sayteki_db');
define('DB_USER', 'sayteki_db');
define('DB_PASS', 'hvk09sf1u5');
define('DB_CHARSET', 'utf8mb4');

// Uygulama ayarları
define('SITE_TITLE', 'Hammadde Takip Sistemi');
define('SITE_URL', 'http://localhost');
define('BASE_PATH', dirname(__DIR__));

// Kullanılabilir yıllar
define('YILLAR', [2023, 2024, 2025, 2026, 2027]);

// Aylar
define('AYLAR', [
    1 => 'Ocak', 2 => 'Subat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayis', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Agustos', 9 => 'Eylul', 10 => 'Ekim', 11 => 'Kasim', 12 => 'Aralik'
]);

// Veritabanı bağlantısı
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Güvenlik fonksiyonları
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateInput($data, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        default:
            return clean($data);
    }
}

// Oturum kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Format fonksiyonları
function formatNumber($number, $decimals = 0) {
    if ($number === null || $number === '') return '-';
    return number_format((float)$number, $decimals, ',', '.');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d.m.Y H:i', strtotime($datetime));
}

// Para birimi sembolü
function getCurrencySymbol($code) {
    $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'TRY' => '₺', 'CHF' => 'Fr'];
    return $symbols[$code] ?? $code;
}

// Türkçe karakter dönüşümü
function turkishToEnglish($text) {
    $search = ['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'];
    $replace = ['i', 'I', 'g', 'G', 'u', 'U', 's', 'S', 'o', 'O', 'c', 'C'];
    return str_replace($search, $replace, $text);
}

// Bildirim fonksiyonu
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =====================================================
// SİSTEM TABLOLARI OLUŞTURMA
// =====================================================

function initializeSystemTables() {
    $db = getDB();
    
    // System Settings tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Update Logs tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS update_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(50) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Schema Migrations tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Sistem tablolarını başlat
initializeSystemTables();
