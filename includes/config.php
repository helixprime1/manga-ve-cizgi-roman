<?php
// Karakter kodlaması ayarları
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Veritabanı bağlantı bilgileri (ortam değişkenleri üzerinden yüklenir)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME') ?: 'manga_comic_db';

$missing_env = [];

if ($db_user === false || $db_user === '') {
    $missing_env[] = 'DB_USER';
}

if ($db_pass === false) {
    // Şifre boş bırakılmak isteniyorsa değişkeni tanımlı şekilde boş string yapın.
    $missing_env[] = 'DB_PASS';
}

if (!empty($missing_env)) {
    $message = 'Eksik ortam değişkenleri: ' . implode(', ', $missing_env) . '. ' .
        'Lütfen veritabanı bilgilerini güvenli bir şekilde ortam değişkenleriyle tanımlayın.';
    die($message);
}

define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);

// Veritabanı bağlantısı
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Bağlantı kontrolü
if (!$conn) {
    die("Veritabanı bağlantısı başarısız: " . mysqli_connect_error());
}

// UTF-8 karakter seti
mysqli_set_charset($conn, "utf8mb4");

// Site ayarları
define('SITE_NAME', 'MangaÇizgiRoman');
define('SITE_URL', 'http://localhost');
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Upload klasörlerini kontrol et ve oluştur
$upload_dirs = [
    'uploads',
    'uploads/content',
    'uploads/covers', 
    'uploads/avatars'
];

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Güvenlik ayarları
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 dakika

// Oturum ayarları (session_start() çağrılmadan önce yapılmalı)
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // HTTPS kullanırken 1 yapın
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
}
?> 