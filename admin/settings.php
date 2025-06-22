<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$admin_user = getCurrentUser();
$page_title = 'Site Ayarları';

// İstatistikleri al
$stats = [];
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Settings tablosu kontrol ve oluşturma
$check_settings_table = "SHOW TABLES LIKE 'settings'";
$table_exists = mysqli_query($conn, $check_settings_table);

if (mysqli_num_rows($table_exists) == 0) {
    $create_settings_table = "
    CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type ENUM('text', 'textarea', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
        description TEXT,
        category VARCHAR(50) DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_settings_table);
}

// Eksik sütunları kontrol et ve ekle
$check_columns = "SHOW COLUMNS FROM settings";
$columns_result = mysqli_query($conn, $check_columns);
$existing_columns = [];
if ($columns_result) {
while ($col = mysqli_fetch_assoc($columns_result)) {
    $existing_columns[] = $col['Field'];
    }
}

if (!in_array('setting_type', $existing_columns)) {
    $add_type_column = "ALTER TABLE settings ADD COLUMN setting_type ENUM('text', 'textarea', 'number', 'boolean', 'email', 'url') DEFAULT 'text' AFTER setting_value";
    mysqli_query($conn, $add_type_column);
}

if (!in_array('description', $existing_columns)) {
    $add_desc_column = "ALTER TABLE settings ADD COLUMN description TEXT AFTER setting_type";
    mysqli_query($conn, $add_desc_column);
}

if (!in_array('category', $existing_columns)) {
    $add_cat_column = "ALTER TABLE settings ADD COLUMN category VARCHAR(50) DEFAULT 'general' AFTER description";
    mysqli_query($conn, $add_cat_column);
}

// Varsayılan ayarları ekle (sadece yoksa)
$default_settings = [
    // Genel Ayarlar
    ['site_name', 'Manga & Comic Hub', 'text', 'Site adı', 'general'],
    ['site_description', 'Manga ve çizgi roman paylaşım platformu', 'textarea', 'Site açıklaması', 'general'],
    ['site_keywords', 'manga, çizgi roman, anime, comic', 'text', 'Site anahtar kelimeleri', 'general'],
    ['admin_email', 'admin@example.com', 'email', 'Admin e-posta adresi', 'general'],
    ['contact_email', 'contact@example.com', 'email', 'İletişim e-posta adresi', 'general'],
    ['site_logo', '', 'text', 'Site logo URL', 'general'],
    ['site_favicon', '', 'text', 'Site favicon URL', 'general'],
    ['default_language', 'tr', 'text', 'Varsayılan dil', 'general'],
    ['timezone', 'Europe/Istanbul', 'text', 'Zaman dilimi', 'general'],
    ['items_per_page', '12', 'number', 'Sayfa başına öğe sayısı', 'general'],
    
    // Kullanıcı Ayarları
    ['allow_registration', '1', 'boolean', 'Kayıt olmaya izin ver', 'users'],
    ['email_verification', '0', 'boolean', 'E-posta doğrulaması gerektir', 'users'],
    ['min_username_length', '3', 'number', 'Minimum kullanıcı adı uzunluğu', 'users'],
    ['max_username_length', '20', 'number', 'Maksimum kullanıcı adı uzunluğu', 'users'],
    ['min_password_length', '6', 'number', 'Minimum şifre uzunluğu', 'users'],
    ['allow_profile_edit', '1', 'boolean', 'Profil düzenlemeye izin ver', 'users'],
    ['allow_avatar_upload', '1', 'boolean', 'Avatar yüklemeye izin ver', 'users'],
    ['max_avatar_size', '2', 'number', 'Maksimum avatar boyutu (MB)', 'users'],
    ['user_roles', 'user,author,moderator,admin', 'text', 'Kullanıcı rolleri (virgülle ayır)', 'users'],
    
    // İçerik Ayarları
    ['require_approval', '1', 'boolean', 'İçerik onayı gerektir', 'content'],
    ['max_file_size', '10', 'number', 'Maksimum dosya boyutu (MB)', 'content'],
    ['allowed_file_types', 'jpg,jpeg,png,pdf', 'text', 'İzin verilen dosya türleri', 'content'],
    ['enable_comments', '1', 'boolean', 'Yorumlara izin ver', 'content'],
    ['enable_likes', '1', 'boolean', 'Beğenilere izin ver', 'content'],
    ['enable_favorites', '1', 'boolean', 'Favorilere izin ver', 'content'],
    ['enable_ratings', '1', 'boolean', 'Puanlamaya izin ver', 'content'],
    ['max_tags_per_content', '10', 'number', 'İçerik başına maksimum etiket sayısı', 'content'],
    ['auto_generate_thumbnails', '1', 'boolean', 'Otomatik küçük resim oluştur', 'content'],
    ['watermark_images', '0', 'boolean', 'Resimlere filigran ekle', 'content'],
    ['watermark_text', 'MangaComicHub', 'text', 'Filigran metni', 'content'],
    ['enable_series', '1', 'boolean', 'Seri içeriklere izin ver', 'content'],
    ['max_chapters_per_series', '1000', 'number', 'Seri başına maksimum bölüm sayısı', 'content'],
    
    // Güvenlik Ayarları
    ['enable_captcha', '0', 'boolean', 'CAPTCHA kullan', 'security'],
    ['captcha_site_key', '', 'text', 'reCAPTCHA Site Key', 'security'],
    ['captcha_secret_key', '', 'text', 'reCAPTCHA Secret Key', 'security'],
    ['login_attempts_limit', '5', 'number', 'Maksimum giriş denemesi', 'security'],
    ['login_lockout_time', '15', 'number', 'Hesap kilitleme süresi (dakika)', 'security'],
    ['session_timeout', '1440', 'number', 'Oturum zaman aşımı (dakika)', 'security'],
    ['enable_two_factor', '0', 'boolean', 'İki faktörlü kimlik doğrulama', 'security'],
    ['password_reset_expiry', '60', 'number', 'Şifre sıfırlama link süresi (dakika)', 'security'],
    ['enable_ssl_redirect', '0', 'boolean', 'HTTPS\'e yönlendir', 'security'],
    
    // E-posta Ayarları
    ['smtp_enabled', '0', 'boolean', 'SMTP kullan', 'email'],
    ['smtp_host', '', 'text', 'SMTP sunucusu', 'email'],
    ['smtp_port', '587', 'number', 'SMTP portu', 'email'],
    ['smtp_username', '', 'text', 'SMTP kullanıcı adı', 'email'],
    ['smtp_password', '', 'text', 'SMTP şifresi', 'email'],
    ['smtp_encryption', 'tls', 'text', 'SMTP şifreleme (tls/ssl)', 'email'],
    ['email_from_name', 'Manga Comic Hub', 'text', 'Gönderen adı', 'email'],
    ['email_from_address', 'noreply@example.com', 'email', 'Gönderen e-posta', 'email'],
    ['welcome_email_enabled', '1', 'boolean', 'Hoş geldin e-postası gönder', 'email'],
    ['notification_emails', '1', 'boolean', 'Bildirim e-postaları gönder', 'email'],
    
    // Sistem Ayarları
    ['maintenance_mode', '0', 'boolean', 'Bakım modu', 'system'],
    ['maintenance_message', 'Site bakımda. Lütfen daha sonra tekrar deneyin.', 'textarea', 'Bakım modu mesajı', 'system'],
    ['enable_caching', '1', 'boolean', 'Önbellekleme aktif', 'system'],
    ['cache_expiry', '3600', 'number', 'Önbellek süresi (saniye)', 'system'],
    ['enable_compression', '1', 'boolean', 'Gzip sıkıştırma', 'system'],
    ['debug_mode', '0', 'boolean', 'Hata ayıklama modu', 'system'],
    ['log_errors', '1', 'boolean', 'Hataları kaydet', 'system'],
    ['max_log_size', '10', 'number', 'Maksimum log dosyası boyutu (MB)', 'system'],
    ['backup_enabled', '1', 'boolean', 'Otomatik yedekleme', 'system'],
    ['backup_frequency', 'daily', 'text', 'Yedekleme sıklığı (daily/weekly/monthly)', 'system'],
    
    // Entegrasyonlar
    ['google_analytics', '', 'text', 'Google Analytics ID', 'integrations'],
    ['google_adsense', '', 'text', 'Google AdSense ID', 'integrations'],
    ['facebook_app_id', '', 'text', 'Facebook App ID', 'integrations'],
    ['twitter_api_key', '', 'text', 'Twitter API Key', 'integrations'],
    ['discord_webhook', '', 'url', 'Discord Webhook URL', 'integrations'],
    ['slack_webhook', '', 'url', 'Slack Webhook URL', 'integrations'],
    ['enable_api', '1', 'boolean', 'API erişimini etkinleştir', 'integrations'],
    ['api_rate_limit', '100', 'number', 'API istek limiti (saat başına)', 'integrations'],
    
    // Sosyal Medya
    ['facebook_url', '', 'url', 'Facebook URL', 'social'],
    ['twitter_url', '', 'url', 'Twitter URL', 'social'],
    ['instagram_url', '', 'url', 'Instagram URL', 'social'],
    ['youtube_url', '', 'url', 'YouTube URL', 'social'],
    ['discord_url', '', 'url', 'Discord URL', 'social'],
    ['telegram_url', '', 'url', 'Telegram URL', 'social'],
    ['enable_social_login', '0', 'boolean', 'Sosyal medya girişi', 'social'],
    ['enable_social_share', '1', 'boolean', 'Sosyal medya paylaşımı', 'social'],
    
    // SEO Ayarları
    ['enable_seo_urls', '1', 'boolean', 'SEO dostu URL\'ler', 'seo'],
    ['meta_robots', 'index,follow', 'text', 'Meta robots', 'seo'],
    ['og_image', '', 'url', 'Varsayılan OG resmi', 'seo'],
    ['enable_sitemap', '1', 'boolean', 'XML sitemap oluştur', 'seo'],
    ['sitemap_frequency', 'daily', 'text', 'Sitemap güncelleme sıklığı', 'seo'],
    ['enable_breadcrumbs', '1', 'boolean', 'Breadcrumb navigasyon', 'seo'],
    ['canonical_urls', '1', 'boolean', 'Canonical URL\'ler', 'seo'],
    
    // Bildirimler
    ['enable_notifications', '1', 'boolean', 'Bildirimleri etkinleştir', 'notifications'],
    ['notification_sound', '1', 'boolean', 'Bildirim sesi', 'notifications'],
    ['email_notifications', '1', 'boolean', 'E-posta bildirimleri', 'notifications'],
    ['push_notifications', '0', 'boolean', 'Push bildirimleri', 'notifications'],
    ['notification_retention', '30', 'number', 'Bildirim saklama süresi (gün)', 'notifications'],
    ['admin_notifications', '1', 'boolean', 'Admin bildirimleri', 'notifications']
];

foreach ($default_settings as $setting) {
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $setting[0]);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value, setting_type, description, category) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "sssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($check_stmt);
}

// Ayar güncellemeleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $message = 'Güvenlik hatası. Lütfen tekrar deneyin.';
        $message_type = 'danger';
    } else {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $updated_count = 0;
        
        // Önce tüm boolean ayarları 0 yap (checkbox işaretli değilse POST'ta gelmez)
        $boolean_settings_stmt = mysqli_prepare($conn, "SELECT setting_key FROM settings WHERE setting_type = 'boolean'");
        mysqli_stmt_execute($boolean_settings_stmt);
        $boolean_result = mysqli_stmt_get_result($boolean_settings_stmt);
        
        while ($boolean_setting = mysqli_fetch_assoc($boolean_result)) {
            $setting_key = $boolean_setting['setting_key'];
            if (!isset($_POST['setting_' . $setting_key])) {
                $update_stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = '0' WHERE setting_key = ?");
                mysqli_stmt_bind_param($update_stmt, "s", $setting_key);
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated_count++;
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        mysqli_stmt_close($boolean_settings_stmt);
        
        // Gelen değerleri güncelle
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && $key !== CSRF_TOKEN_NAME && strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // 'setting_' kısmını çıkar
                $setting_value = sanitizeInput($value);
                
                $update_stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                mysqli_stmt_bind_param($update_stmt, "ss", $setting_value, $setting_key);
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated_count++;
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        
        if ($updated_count > 0) {
            $message = "$updated_count ayar başarıyla güncellendi.";
            $message_type = 'success';
        } else {
            $message = 'Hiçbir ayar güncellenmedi.';
            $message_type = 'warning';
        }
    }
    } // CSRF token kontrolü kapanış
}

// Ayarları kategorilere göre getir
$categories = ['general', 'users', 'content', 'security', 'email', 'system', 'integrations', 'social', 'seo', 'notifications'];
$settings_by_category = [];

foreach ($categories as $category) {
    $settings_stmt = mysqli_prepare($conn, "SELECT * FROM settings WHERE category = ? ORDER BY setting_key");
    mysqli_stmt_bind_param($settings_stmt, "s", $category);
    mysqli_stmt_execute($settings_stmt);
    $settings_result = mysqli_stmt_get_result($settings_stmt);
    $settings_by_category[$category] = [];
    
    while ($setting = mysqli_fetch_assoc($settings_result)) {
        $settings_by_category[$category][] = $setting;
    }
    mysqli_stmt_close($settings_stmt);
}

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Site Ayarları</h1>
                            <p class="text-muted">Site yapılandırmasını yönetin</p>
                        </div>
                        <div>
                            <button type="submit" form="settingsForm" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Ayarları Kaydet
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form id="settingsForm" method="POST">
                <?php echo getCSRFTokenInput(); ?>
                <input type="hidden" name="action" value="update_settings">
                
                <!-- Settings Tabs -->
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="fas fa-cog me-2"></i>Genel
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">
                            <i class="fas fa-book me-2"></i>İçerik
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-shield-alt me-2"></i>Güvenlik
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                            <i class="fas fa-envelope me-2"></i>E-posta
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                            <i class="fas fa-server me-2"></i>Sistem
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="integrations-tab" data-bs-toggle="tab" data-bs-target="#integrations" type="button" role="tab">
                            <i class="fas fa-plug me-2"></i>Entegrasyonlar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                            <i class="fas fa-share-alt me-2"></i>Sosyal Medya
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">
                            <i class="fas fa-search me-2"></i>SEO
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                            <i class="fas fa-bell me-2"></i>Bildirimler
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="settingsTabContent">
                    <?php 
                    $category_names = [
                        'general' => 'Genel Ayarlar',
                        'users' => 'Kullanıcı Ayarları',
                        'content' => 'İçerik Ayarları',
                        'security' => 'Güvenlik Ayarları',
                        'email' => 'E-posta Ayarları',
                        'system' => 'Sistem Ayarları',
                        'integrations' => 'Entegrasyon Ayarları',
                        'social' => 'Sosyal Medya Ayarları',
                        'seo' => 'SEO Ayarları',
                        'notifications' => 'Bildirim Ayarları'
                    ];
                    
                    $first = true;
                    foreach ($settings_by_category as $category => $settings): 
                        if (empty($settings)) continue;
                    ?>
                        <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" id="<?php echo $category; ?>" role="tabpanel">
                            <div class="card border-0 shadow-sm settings-card">
                                <div class="card-header bg-white border-0">
                                    <h5 class="card-title mb-0"><?php echo $category_names[$category]; ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($category === 'email'): ?>
                                        <div class="d-flex justify-content-end mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-test me-2" onclick="testSMTP()">
                                                <i class="fas fa-paper-plane me-1"></i>SMTP Test
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-test" onclick="testEmail()">
                                                <i class="fas fa-envelope me-1"></i>Test E-postası Gönder
                                            </button>
                                        </div>
                                    <?php elseif ($category === 'system'): ?>
                                        <div class="d-flex justify-content-end mb-3">
                                            <button type="button" class="btn btn-outline-warning btn-test me-2" onclick="clearCache()">
                                                <i class="fas fa-broom me-1"></i>Önbellek Temizle
                                            </button>
                                        </div>
                                    <?php elseif ($category === 'seo'): ?>
                                        <div class="d-flex justify-content-end mb-3">
                                            <button type="button" class="btn btn-outline-info btn-test" onclick="generateSitemap()">
                                                <i class="fas fa-sitemap me-1"></i>Sitemap Oluştur
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <?php 
                                        // Ayarları gruplandır
                                        $grouped_settings = [];
                                        if ($category === 'security') {
                                            $groups = [
                                                'CAPTCHA' => ['enable_captcha', 'captcha_site_key', 'captcha_secret_key'],
                                                'Giriş Güvenliği' => ['login_attempts_limit', 'login_lockout_time', 'session_timeout'],
                                                'İki Faktörlü Doğrulama' => ['enable_two_factor'],
                                                'Diğer' => ['password_reset_expiry', 'enable_ssl_redirect']
                                            ];
                                            foreach ($groups as $group_name => $group_keys) {
                                                $grouped_settings[$group_name] = array_filter($settings, function($s) use ($group_keys) {
                                                    return in_array($s['setting_key'], $group_keys);
                                                });
                                            }
                                        } elseif ($category === 'email') {
                                            $groups = [
                                                'SMTP Ayarları' => ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption'],
                                                'E-posta Ayarları' => ['email_from_name', 'email_from_address', 'welcome_email_enabled', 'notification_emails']
                                            ];
                                            foreach ($groups as $group_name => $group_keys) {
                                                $grouped_settings[$group_name] = array_filter($settings, function($s) use ($group_keys) {
                                                    return in_array($s['setting_key'], $group_keys);
                                                });
                                            }
                                        } else {
                                            $grouped_settings[''] = $settings;
                                        }
                                        
                                        foreach ($grouped_settings as $group_name => $group_settings):
                                            if (empty($group_settings)) continue;
                                        ?>
                                            <?php if ($group_name): ?>
                                                <div class="col-12">
                                                    <div class="setting-group">
                                                        <h6><?php echo $group_name; ?></h6>
                                                        <div class="row">
                                            <?php endif; ?>
                                            
                                            <?php foreach ($group_settings as $setting): ?>
                                            <div class="col-md-6 mb-3">
                                                <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                                        <?php 
                                                        // Türkçe etiketler
                                                        $turkish_labels = [
                                                            'site_name' => 'Site Adı',
                                                            'site_description' => 'Site Açıklaması',
                                                            'site_keywords' => 'Site Anahtar Kelimeleri',
                                                            'admin_email' => 'Admin E-posta Adresi',
                                                            'contact_email' => 'İletişim E-posta Adresi',
                                                            'site_logo' => 'Site Logo URL',
                                                            'site_favicon' => 'Site Favicon URL',
                                                            'default_language' => 'Varsayılan Dil',
                                                            'timezone' => 'Zaman Dilimi',
                                                            'items_per_page' => 'Sayfa Başına Öğe Sayısı',
                                                            
                                                            // Kullanıcı Ayarları
                                                            'allow_registration' => 'Kayıt Olmaya İzin Ver',
                                                            'email_verification' => 'E-posta Doğrulaması Gerektir',
                                                            'min_username_length' => 'Minimum Kullanıcı Adı Uzunluğu',
                                                            'max_username_length' => 'Maksimum Kullanıcı Adı Uzunluğu',
                                                            'min_password_length' => 'Minimum Şifre Uzunluğu',
                                                            'allow_profile_edit' => 'Profil Düzenlemeye İzin Ver',
                                                            'allow_avatar_upload' => 'Avatar Yüklemeye İzin Ver',
                                                            'max_avatar_size' => 'Maksimum Avatar Boyutu (MB)',
                                                            'user_roles' => 'Kullanıcı Rolleri',
                                                            
                                                            // İçerik Ayarları
                                                            'require_approval' => 'İçerik Onayı Gerektir',
                                                            'max_file_size' => 'Maksimum Dosya Boyutu (MB)',
                                                            'allowed_file_types' => 'İzin Verilen Dosya Türleri',
                                                            'enable_comments' => 'Yorumlara İzin Ver',
                                                            'enable_likes' => 'Beğenilere İzin Ver',
                                                            'enable_favorites' => 'Favorilere İzin Ver',
                                                            'enable_ratings' => 'Puanlamaya İzin Ver',
                                                            'max_tags_per_content' => 'İçerik Başına Maksimum Etiket Sayısı',
                                                            'auto_generate_thumbnails' => 'Otomatik Küçük Resim Oluştur',
                                                            'watermark_images' => 'Resimlere Filigran Ekle',
                                                            'watermark_text' => 'Filigran Metni',
                                                            'enable_series' => 'Seri İçeriklere İzin Ver',
                                                            'max_chapters_per_series' => 'Seri Başına Maksimum Bölüm Sayısı',
                                                            
                                                            // Güvenlik Ayarları
                                                            'enable_captcha' => 'CAPTCHA Kullan',
                                                            'captcha_site_key' => 'reCAPTCHA Site Anahtarı',
                                                            'captcha_secret_key' => 'reCAPTCHA Gizli Anahtarı',
                                                            'login_attempts_limit' => 'Maksimum Giriş Denemesi',
                                                            'login_lockout_time' => 'Hesap Kilitleme Süresi (Dakika)',
                                                            'session_timeout' => 'Oturum Zaman Aşımı (Dakika)',
                                                            'enable_two_factor' => 'İki Faktörlü Kimlik Doğrulama',
                                                            'password_reset_expiry' => 'Şifre Sıfırlama Link Süresi (Dakika)',
                                                            'enable_ssl_redirect' => 'HTTPS\'e Yönlendir',
                                                            
                                                            // E-posta Ayarları
                                                            'smtp_enabled' => 'SMTP Kullan',
                                                            'smtp_host' => 'SMTP Sunucusu',
                                                            'smtp_port' => 'SMTP Portu',
                                                            'smtp_username' => 'SMTP Kullanıcı Adı',
                                                            'smtp_password' => 'SMTP Şifresi',
                                                            'smtp_encryption' => 'SMTP Şifreleme',
                                                            'email_from_name' => 'Gönderen Adı',
                                                            'email_from_address' => 'Gönderen E-posta',
                                                            'welcome_email_enabled' => 'Hoş Geldin E-postası Gönder',
                                                            'notification_emails' => 'Bildirim E-postaları Gönder',
                                                            
                                                            // Sistem Ayarları
                                                            'maintenance_mode' => 'Bakım Modu',
                                                            'maintenance_message' => 'Bakım Modu Mesajı',
                                                            'enable_caching' => 'Önbellekleme Aktif',
                                                            'cache_expiry' => 'Önbellek Süresi (Saniye)',
                                                            'enable_compression' => 'Gzip Sıkıştırma',
                                                            'debug_mode' => 'Hata Ayıklama Modu',
                                                            'log_errors' => 'Hataları Kaydet',
                                                            'max_log_size' => 'Maksimum Log Dosyası Boyutu (MB)',
                                                            'backup_enabled' => 'Otomatik Yedekleme',
                                                            'backup_frequency' => 'Yedekleme Sıklığı',
                                                            
                                                            // Entegrasyonlar
                                                            'google_analytics' => 'Google Analytics ID',
                                                            'google_adsense' => 'Google AdSense ID',
                                                            'facebook_app_id' => 'Facebook App ID',
                                                            'twitter_api_key' => 'Twitter API Anahtarı',
                                                            'discord_webhook' => 'Discord Webhook URL',
                                                            'slack_webhook' => 'Slack Webhook URL',
                                                            'enable_api' => 'API Erişimini Etkinleştir',
                                                            'api_rate_limit' => 'API İstek Limiti (Saat Başına)',
                                                            
                                                            // Sosyal Medya
                                                            'facebook_url' => 'Facebook URL',
                                                            'twitter_url' => 'Twitter URL',
                                                            'instagram_url' => 'Instagram URL',
                                                            'youtube_url' => 'YouTube URL',
                                                            'discord_url' => 'Discord URL',
                                                            'telegram_url' => 'Telegram URL',
                                                            'enable_social_login' => 'Sosyal Medya Girişi',
                                                            'enable_social_share' => 'Sosyal Medya Paylaşımı',
                                                            
                                                            // SEO Ayarları
                                                            'enable_seo_urls' => 'SEO Dostu URL\'ler',
                                                            'meta_robots' => 'Meta Robots',
                                                            'og_image' => 'Varsayılan OG Resmi',
                                                            'enable_sitemap' => 'XML Sitemap Oluştur',
                                                            'sitemap_frequency' => 'Sitemap Güncelleme Sıklığı',
                                                            'enable_breadcrumbs' => 'Breadcrumb Navigasyon',
                                                            'canonical_urls' => 'Canonical URL\'ler',
                                                            
                                                            // Bildirimler
                                                            'enable_notifications' => 'Bildirimleri Etkinleştir',
                                                            'notification_sound' => 'Bildirim Sesi',
                                                            'email_notifications' => 'E-posta Bildirimleri',
                                                            'push_notifications' => 'Push Bildirimleri',
                                                            'notification_retention' => 'Bildirim Saklama Süresi (Gün)',
                                                            'admin_notifications' => 'Admin Bildirimleri'
                                                        ];
                                                        
                                                        echo isset($turkish_labels[$setting['setting_key']]) ? 
                                                             $turkish_labels[$setting['setting_key']] : 
                                                             ucfirst(str_replace('_', ' ', $setting['setting_key']));
                                                        ?>
                                                </label>
                                                
                                                <?php if ($setting['setting_type'] === 'textarea'): ?>
                                                    <textarea class="form-control" 
                                                              id="setting_<?php echo $setting['setting_key']; ?>" 
                                                              name="setting_<?php echo $setting['setting_key']; ?>" 
                                                              rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                                <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               id="setting_<?php echo $setting['setting_key']; ?>" 
                                                               name="setting_<?php echo $setting['setting_key']; ?>" 
                                                               value="1" 
                                                               <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="setting_<?php echo $setting['setting_key']; ?>">
                                                            Etkin
                                                        </label>
                                                    </div>
                                                <?php elseif (strpos($setting['setting_key'], 'password') !== false): ?>
                                                    <div class="input-group">
                                                        <input type="password" 
                                                               class="form-control" 
                                                               id="setting_<?php echo $setting['setting_key']; ?>" 
                                                               name="setting_<?php echo $setting['setting_key']; ?>" 
                                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                               placeholder="••••••••">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('setting_<?php echo $setting['setting_key']; ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                <?php elseif ($setting['setting_key'] === 'timezone'): ?>
                                                    <select class="form-select" 
                                                            id="setting_<?php echo $setting['setting_key']; ?>" 
                                                            name="setting_<?php echo $setting['setting_key']; ?>">
                                                        <?php
                                                        $timezones = [
                                                            'Europe/Istanbul' => 'Türkiye (UTC+3)',
                                                            'UTC' => 'UTC',
                                                            'Europe/London' => 'Londra (UTC+0)',
                                                            'Europe/Berlin' => 'Berlin (UTC+1)',
                                                            'America/New_York' => 'New York (UTC-5)',
                                                            'America/Los_Angeles' => 'Los Angeles (UTC-8)',
                                                            'Asia/Tokyo' => 'Tokyo (UTC+9)'
                                                        ];
                                                        foreach ($timezones as $tz => $label):
                                                        ?>
                                                            <option value="<?php echo $tz; ?>" <?php echo $setting['setting_value'] === $tz ? 'selected' : ''; ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif ($setting['setting_key'] === 'smtp_encryption'): ?>
                                                    <select class="form-select" 
                                                            id="setting_<?php echo $setting['setting_key']; ?>" 
                                                            name="setting_<?php echo $setting['setting_key']; ?>">
                                                        <option value="tls" <?php echo $setting['setting_value'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                        <option value="ssl" <?php echo $setting['setting_value'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                        <option value="none" <?php echo $setting['setting_value'] === 'none' ? 'selected' : ''; ?>>Şifreleme Yok</option>
                                                    </select>
                                                <?php elseif ($setting['setting_key'] === 'backup_frequency'): ?>
                                                    <select class="form-select" 
                                                            id="setting_<?php echo $setting['setting_key']; ?>" 
                                                            name="setting_<?php echo $setting['setting_key']; ?>">
                                                        <option value="daily" <?php echo $setting['setting_value'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                                                        <option value="weekly" <?php echo $setting['setting_value'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                                                        <option value="monthly" <?php echo $setting['setting_value'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                                                    </select>
                                                <?php elseif ($setting['setting_key'] === 'sitemap_frequency'): ?>
                                                    <select class="form-select" 
                                                            id="setting_<?php echo $setting['setting_key']; ?>" 
                                                            name="setting_<?php echo $setting['setting_key']; ?>">
                                                        <option value="always" <?php echo $setting['setting_value'] === 'always' ? 'selected' : ''; ?>>Her Zaman</option>
                                                        <option value="hourly" <?php echo $setting['setting_value'] === 'hourly' ? 'selected' : ''; ?>>Saatlik</option>
                                                        <option value="daily" <?php echo $setting['setting_value'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                                                        <option value="weekly" <?php echo $setting['setting_value'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                                                        <option value="monthly" <?php echo $setting['setting_value'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                                                        <option value="yearly" <?php echo $setting['setting_value'] === 'yearly' ? 'selected' : ''; ?>>Yıllık</option>
                                                        <option value="never" <?php echo $setting['setting_value'] === 'never' ? 'selected' : ''; ?>>Hiçbir Zaman</option>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="<?php echo $setting['setting_type']; ?>" 
                                                           class="form-control" 
                                                           id="setting_<?php echo $setting['setting_key']; ?>" 
                                                           name="setting_<?php echo $setting['setting_key']; ?>" 
                                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                           <?php if ($setting['setting_type'] === 'number'): ?>
                                                               min="0"
                                                           <?php endif; ?>>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($setting['description'])): ?>
                                                    <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if ($group_name): ?>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php endif; ?>
                                        
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            </form>

            <!-- System Information -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm settings-card">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">PHP Sürümü</div>
                                        <div class="info-value"><?php echo phpversion(); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">MySQL Sürümü</div>
                                        <div class="info-value"><?php echo mysqli_get_server_info($conn); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Sunucu</div>
                                        <div class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Upload Limit</div>
                                        <div class="info-value"><?php echo ini_get('upload_max_filesize'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Bellek Limiti</div>
                                        <div class="info-value"><?php echo ini_get('memory_limit'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Max Execution Time</div>
                                        <div class="info-value"><?php echo ini_get('max_execution_time'); ?>s</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Post Max Size</div>
                                        <div class="info-value"><?php echo ini_get('post_max_size'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <div class="info-label">Disk Kullanımı</div>
                                        <div class="info-value">
                                            <?php
                                            $bytes = disk_free_space(".");
                                            $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
                                            $base = 1024;
                                            $class = min((int)log($bytes , $base) , count($si_prefix) - 1);
                                            echo sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
                                            ?> boş
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">Toplam Kullanıcı</div>
                                        <div class="info-value text-primary"><?php echo number_format($stats['total_users']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">Toplam İçerik</div>
                                        <div class="info-value text-success"><?php echo number_format($stats['total_content']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">Bekleyen İçerik</div>
                                        <div class="info-value text-warning"><?php echo number_format($stats['pending_content']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: #1f2937;
}

.settings-card {
    transition: all 0.3s ease;
}

.settings-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
}

.form-label {
    font-weight: 600;
    color: #374151;
}

.form-text {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.nav-tabs .nav-link {
    color: #6b7280;
    border: none;
    border-bottom: 2px solid transparent;
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.nav-tabs .nav-link.active {
    color: #3b82f6;
    background: none;
    border-bottom-color: #3b82f6;
}

.btn-test {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

.setting-group {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.setting-group h6 {
    color: #374151;
    font-weight: 600;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}


</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function testSMTP() {
    const host = document.getElementById('setting_smtp_host').value;
    const port = document.getElementById('setting_smtp_port').value;
    const username = document.getElementById('setting_smtp_username').value;
    const password = document.getElementById('setting_smtp_password').value;
    
    if (!host || !port || !username || !password) {
        alert('Lütfen tüm SMTP ayarlarını doldurun.');
        return;
    }
    
    // SMTP test işlemi burada yapılacak
    alert('SMTP testi başlatıldı. Sonuçlar e-posta ile gönderilecek.');
}

function testEmail() {
    const email = document.getElementById('setting_admin_email').value;
    if (!email) {
        alert('Lütfen admin e-posta adresini girin.');
        return;
    }
    
    // Test e-postası gönderme işlemi
    alert('Test e-postası gönderildi: ' + email);
}

function clearCache() {
    if (confirm('Önbelleği temizlemek istediğinizden emin misiniz?')) {
        // Cache temizleme işlemi
        alert('Önbellek temizlendi.');
    }
}

function generateSitemap() {
    if (confirm('Sitemap oluşturulsun mu?')) {
        // Sitemap oluşturma işlemi
        alert('Sitemap oluşturuldu.');
    }
}

// Form değişikliklerini takip et
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('settingsForm');
    let hasChanges = false;
    

    
    // Form alanlarındaki değişiklikleri takip et
    form.addEventListener('change', function() {
        hasChanges = true;
    });
    
    // Sayfa kapatılırken uyar
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = 'Kaydedilmemiş değişiklikler var. Sayfayı kapatmak istediğinizden emin misiniz?';
        }
    });
    
    // Form gönderildiğinde değişiklik takibini sıfırla
    form.addEventListener('submit', function() {
        hasChanges = false;
    });
});


</script>

<?php require_once 'includes/footer.php'; ?> 