<?php
// Karakter kodlaması ayarları
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Çevre değişkenleri desteği
$autoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

if (!function_exists('env_or_default')) {
    function env_or_default(string $key, $default = null)
    {
        $value = getenv($key);

        return $value !== false && $value !== '' ? $value : $default;
    }
}

// Veritabanı bağlantı bilgileri (ortam değişkeni yoksa hatırlatıcı varsayılanlar kullanılır)
define('DB_HOST', env_or_default('DB_HOST', 'localhost'));
define('DB_USER', env_or_default('DB_USER', 'root'));
define('DB_PASS', env_or_default('DB_PASS', ''));
define('DB_NAME', env_or_default('DB_NAME', 'manga_comic_db'));

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
define('SITE_URL', env_or_default('SITE_URL', 'http://localhost'));
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
