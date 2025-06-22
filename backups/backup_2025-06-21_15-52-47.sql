-- MySQL Database Backup
-- Generated on: 2025-06-21 15:52:47
-- Database: manga_comic_db


-- Table structure for table `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6366f1',
  `icon` varchar(50) DEFAULT 'fas fa-tag',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES ('1', 'Aksiyon', 'aksiyon', 'Aksiyon dolu macera hikayeleri', '#ef4444', 'fas fa-fist-raised', '1', '2025-06-21 15:56:17', '2025-06-21 16:23:18'),
('2', 'Romantik', 'romantik', 'Aşk ve romantik hikayeler', '#ec4899', 'fas fa-heart', '1', '2025-06-21 15:56:17', '2025-06-21 16:23:23'),
('3', 'Komedi', 'komedi', 'Eğlenceli ve komik hikayeler', '#f59e0b', 'fas fa-laugh', '1', '2025-06-21 15:56:17', '2025-06-21 15:56:17'),
('4', 'Drama', 'drama', 'Duygusal ve dramatik hikayeler', '#8b5cf6', 'fas fa-theater-masks', '1', '2025-06-21 15:56:17', '2025-06-21 15:56:17'),
('5', 'Fantastik', 'fantastik', 'Büyü ve fantezi dünyaları', '#10b981', 'fas fa-magic', '1', '2025-06-21 15:56:17', '2025-06-21 16:23:20'),
('6', 'Bilim Kurgu', 'bilim-kurgu', 'Gelecek ve teknoloji hikayeleri', '#06b6d4', 'fas fa-rocket', '1', '2025-06-21 15:56:17', '2025-06-21 15:56:17'),
('7', 'Korku', 'korku', 'Gerilim ve korku hikayeleri', '#1f2937', 'fas fa-ghost', '1', '2025-06-21 15:56:17', '2025-06-21 15:56:17'),
('8', 'Spor', 'spor', 'Spor temalı hikayeler', '#f97316', 'fas fa-running', '1', '2025-06-21 15:56:17', '2025-06-21 15:56:17');


-- Table structure for table `chapters`
DROP TABLE IF EXISTS `chapters`;
CREATE TABLE `chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `chapter_files` text NOT NULL,
  `file_type` enum('images','pdf') DEFAULT 'images',
  `views` int(11) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_chapter` (`content_id`,`chapter_number`),
  CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `chapters`
INSERT INTO `chapters` VALUES ('6', '8', '1', 'flatya işgali', '', '[\"CeUYclYYBSQzuHS.png\",\"qynBQDkyffhiyau.png\",\"r4eNXTT2qG1d8XP.png\",\"7F8Po0omvrZvQhp.png\"]', 'images', '33', 'published', '2025-06-21 12:53:00', '2025-06-21 16:23:54');


-- Table structure for table `comments`
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `content_id` (`content_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `content`
DROP TABLE IF EXISTS `content`;
CREATE TABLE `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `type` enum('manga','comic') NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) NOT NULL,
  `content_file` varchar(255) NOT NULL,
  `content_type` varchar(20) DEFAULT 'pdf',
  `status` enum('pending','published','rejected') DEFAULT 'pending',
  `featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `chapter_count` int(11) DEFAULT 1,
  `is_series` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `content_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `content`
INSERT INTO `content` VALUES ('8', '3', '4.Tümen', 'deneme deneme', 'manga', 'aksiyon', 'IlSYgwJeieJnmDt.png', '[\"CeUYclYYBSQzuHS.png\",\"qynBQDkyffhiyau.png\",\"r4eNXTT2qG1d8XP.png\",\"7F8Po0omvrZvQhp.png\"]', 'images', 'published', '0', '33', '1', '1', '2025-06-21 12:53:00', '2025-06-21 16:23:54');


-- Table structure for table `favorites`
DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id` (`content_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `likes`
DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id` (`content_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` VALUES ('2', '3', 'deneme', '0', '', '2025-06-21 00:53:19');


-- Table structure for table `settings`
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','number','boolean','email','url') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` VALUES ('1', 'site_name', 'HepStudio', 'text', 'Site adı', 'general', '2025-06-21 03:02:45', '2025-06-21 14:44:33'),
('2', 'site_description', 'Manga ve çizgi roman paylaşım platformu', 'textarea', 'Site açıklaması', 'general', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('3', 'site_keywords', 'manga, çizgi roman, anime, comic', 'text', 'Site anahtar kelimeleri', 'general', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('4', 'admin_email', 'admin@example.com', 'email', 'Admin e-posta adresi', 'general', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('5', 'items_per_page', '12', 'number', 'Sayfa başına öğe sayısı', 'general', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('6', 'allow_registration', '1', 'boolean', 'Kayıt olmaya izin ver', 'users', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('7', 'require_approval', '1', 'boolean', 'İçerik onayı gerektir', 'content', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('8', 'max_file_size', '10', 'number', 'Maksimum dosya boyutu (MB)', 'content', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('9', 'allowed_file_types', 'jpg,jpeg,png,pdf', 'text', 'İzin verilen dosya türleri', 'content', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('10', 'enable_comments', '1', 'boolean', 'Yorumlara izin ver', 'content', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('11', 'enable_likes', '1', 'boolean', 'Beğenilere izin ver', 'content', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('12', 'maintenance_mode', '1', 'boolean', 'Bakım modu', 'system', '2025-06-21 03:02:45', '2025-06-21 14:45:30'),
('13', 'google_analytics', '', 'text', 'Google Analytics ID', 'integrations', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('14', 'facebook_url', '', 'url', 'Facebook URL', 'social', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('15', 'twitter_url', '', 'url', 'Twitter URL', 'social', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('16', 'instagram_url', '', 'url', 'Instagram URL', 'social', '2025-06-21 03:02:45', '2025-06-21 03:02:45'),
('17', 'contact_email', 'contact@example.com', 'email', 'İletişim e-posta adresi', 'general', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('18', 'site_logo', '', 'text', 'Site logo URL', 'general', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('19', 'site_favicon', '', 'text', 'Site favicon URL', 'general', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('20', 'default_language', 'tr', 'text', 'Varsayılan dil', 'general', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('21', 'timezone', 'Europe/Istanbul', 'text', 'Zaman dilimi', 'general', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('22', 'email_verification', '0', 'boolean', 'E-posta doğrulaması gerektir', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('23', 'min_username_length', '3', 'number', 'Minimum kullanıcı adı uzunluğu', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('24', 'max_username_length', '20', 'number', 'Maksimum kullanıcı adı uzunluğu', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('25', 'min_password_length', '6', 'number', 'Minimum şifre uzunluğu', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('26', 'allow_profile_edit', '1', 'boolean', 'Profil düzenlemeye izin ver', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('27', 'allow_avatar_upload', '1', 'boolean', 'Avatar yüklemeye izin ver', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('28', 'max_avatar_size', '2', 'number', 'Maksimum avatar boyutu (MB)', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('29', 'user_roles', 'user,author,moderator,admin', 'text', 'Kullanıcı rolleri (virgülle ayır)', 'users', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('30', 'enable_favorites', '1', 'boolean', 'Favorilere izin ver', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('31', 'enable_ratings', '1', 'boolean', 'Puanlamaya izin ver', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('32', 'max_tags_per_content', '10', 'number', 'İçerik başına maksimum etiket sayısı', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('33', 'auto_generate_thumbnails', '1', 'boolean', 'Otomatik küçük resim oluştur', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('34', 'watermark_images', '0', 'boolean', 'Resimlere filigran ekle', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('35', 'watermark_text', 'MangaComicHub', 'text', 'Filigran metni', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('36', 'enable_series', '1', 'boolean', 'Seri içeriklere izin ver', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('37', 'max_chapters_per_series', '1000', 'number', 'Seri başına maksimum bölüm sayısı', 'content', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('38', 'enable_captcha', '0', 'boolean', 'CAPTCHA kullan', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('39', 'captcha_site_key', '', 'text', 'reCAPTCHA Site Key', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('40', 'captcha_secret_key', '', 'text', 'reCAPTCHA Secret Key', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('41', 'login_attempts_limit', '5', 'number', 'Maksimum giriş denemesi', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('42', 'login_lockout_time', '15', 'number', 'Hesap kilitleme süresi (dakika)', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('43', 'session_timeout', '1440', 'number', 'Oturum zaman aşımı (dakika)', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('44', 'enable_two_factor', '0', 'boolean', 'İki faktörlü kimlik doğrulama', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('45', 'password_reset_expiry', '60', 'number', 'Şifre sıfırlama link süresi (dakika)', 'security', '2025-06-21 14:28:55', '2025-06-21 14:28:55'),
('46', 'enable_ssl_redirect', '0', 'boolean', 'HTTPS\'e yönlendir', 'security', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('47', 'smtp_enabled', '0', 'boolean', 'SMTP kullan', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('48', 'smtp_host', '', 'text', 'SMTP sunucusu', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('49', 'smtp_port', '587', 'number', 'SMTP portu', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('50', 'smtp_username', '', 'text', 'SMTP kullanıcı adı', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('51', 'smtp_password', '', 'text', 'SMTP şifresi', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('52', 'smtp_encryption', 'tls', 'text', 'SMTP şifreleme (tls/ssl)', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('53', 'email_from_name', 'Manga Comic Hub', 'text', 'Gönderen adı', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('54', 'email_from_address', 'noreply@example.com', 'email', 'Gönderen e-posta', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('55', 'welcome_email_enabled', '1', 'boolean', 'Hoş geldin e-postası gönder', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('56', 'notification_emails', '1', 'boolean', 'Bildirim e-postaları gönder', 'email', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('57', 'maintenance_message', 'Site bakımda. Lütfen daha sonra tekrar deneyin.', 'textarea', 'Bakım modu mesajı', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('58', 'enable_caching', '1', 'boolean', 'Önbellekleme aktif', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('59', 'cache_expiry', '3600', 'number', 'Önbellek süresi (saniye)', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('60', 'enable_compression', '1', 'boolean', 'Gzip sıkıştırma', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('61', 'debug_mode', '0', 'boolean', 'Hata ayıklama modu', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('62', 'log_errors', '1', 'boolean', 'Hataları kaydet', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('63', 'max_log_size', '10', 'number', 'Maksimum log dosyası boyutu (MB)', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('64', 'backup_enabled', '1', 'boolean', 'Otomatik yedekleme', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('65', 'backup_frequency', 'daily', 'text', 'Yedekleme sıklığı (daily/weekly/monthly)', 'system', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('66', 'google_adsense', '', 'text', 'Google AdSense ID', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('67', 'facebook_app_id', '', 'text', 'Facebook App ID', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('68', 'twitter_api_key', '', 'text', 'Twitter API Key', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('69', 'discord_webhook', '', 'url', 'Discord Webhook URL', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('70', 'slack_webhook', '', 'url', 'Slack Webhook URL', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('71', 'enable_api', '1', 'boolean', 'API erişimini etkinleştir', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('72', 'api_rate_limit', '100', 'number', 'API istek limiti (saat başına)', 'integrations', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('73', 'youtube_url', '', 'url', 'YouTube URL', 'social', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('74', 'discord_url', '', 'url', 'Discord URL', 'social', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('75', 'telegram_url', '', 'url', 'Telegram URL', 'social', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('76', 'enable_social_login', '0', 'boolean', 'Sosyal medya girişi', 'social', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('77', 'enable_social_share', '1', 'boolean', 'Sosyal medya paylaşımı', 'social', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('78', 'enable_seo_urls', '1', 'boolean', 'SEO dostu URL\'ler', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('79', 'meta_robots', 'index,follow', 'text', 'Meta robots', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('80', 'og_image', '', 'url', 'Varsayılan OG resmi', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('81', 'enable_sitemap', '1', 'boolean', 'XML sitemap oluştur', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('82', 'sitemap_frequency', 'daily', 'text', 'Sitemap güncelleme sıklığı', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('83', 'enable_breadcrumbs', '1', 'boolean', 'Breadcrumb navigasyon', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('84', 'canonical_urls', '1', 'boolean', 'Canonical URL\'ler', 'seo', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('85', 'enable_notifications', '1', 'boolean', 'Bildirimleri etkinleştir', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('86', 'notification_sound', '1', 'boolean', 'Bildirim sesi', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('87', 'email_notifications', '1', 'boolean', 'E-posta bildirimleri', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('88', 'push_notifications', '0', 'boolean', 'Push bildirimleri', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('89', 'notification_retention', '30', 'number', 'Bildirim saklama süresi (gün)', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('90', 'admin_notifications', '1', 'boolean', 'Admin bildirimleri', 'notifications', '2025-06-21 14:33:53', '2025-06-21 14:33:53'),
('91', 'about_mission', 'Manga ve çizgi roman tutkunlarını bir araya getirerek, kaliteli içeriklerin paylaşıldığı, etkileşimli ve kullanıcı dostu bir platform sunmak.', 'textarea', 'Hakkımızda - Misyonumuz', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('92', 'about_vision', 'Türkiye\'nin en büyük ve en güvenilir manga-çizgi roman paylaşım platformu olmak.', 'textarea', 'Hakkımızda - Vizyonumuz', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('93', 'about_story', '2024 yılında manga ve çizgi roman tutkusu ile başlayan yolculuğumuz... Bir grup manga ve çizgi roman severin \"Keşke tüm sevdiğimiz içerikleri tek bir yerde bulabilsek\" hayaliyle başlayan projemiz, bugün binlerce kullanıcının güvenle kullandığı bir platforma dönüştü.', 'textarea', 'Hakkımızda - Hikayemiz', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('94', 'about_extra_content', '', 'textarea', 'Hakkımızda - Ek İçerik (HTML)', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('95', 'terms_extra_content', '', 'textarea', 'Kullanım Koşulları - Ek İçerik (HTML)', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('96', 'privacy_extra_content', '', 'textarea', 'Gizlilik Politikası - Ek İçerik (HTML)', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('97', 'contact_address', '', 'textarea', 'İletişim - Adres', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('98', 'contact_phone', '', 'text', 'İletişim - Telefon', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('99', 'contact_hours', 'Pazartesi - Cuma: 09:00 - 18:00', 'text', 'İletişim - Çalışma Saatleri', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('100', 'footer_text', 'Tüm hakları saklıdır.', 'text', 'Footer Metni', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('101', 'footer_links', '', 'textarea', 'Footer Linkleri (JSON format)', 'pages', '2025-06-21 15:39:55', '2025-06-21 15:39:55'),
('102', 'about_features_title', 'Neden Bizi Seçmelisiniz?', 'text', 'Özellikler Bölüm Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('103', 'about_features_security_title', 'Güvenli Platform', 'text', 'Güvenlik Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('104', 'about_features_security_desc', 'Verileriniz ve gizliliğiniz bizim için çok önemli. En son güvenlik teknolojilerini kullanıyoruz.', 'textarea', 'Güvenlik Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('105', 'about_features_speed_title', 'Hızlı ve Stabil', 'text', 'Hız Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('106', 'about_features_speed_desc', 'Optimize edilmiş altyapımızla hızlı yükleme süreleri ve kesintisiz okuma deneyimi sunuyoruz.', 'textarea', 'Hız Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('107', 'about_features_mobile_title', 'Mobil Uyumlu', 'text', 'Mobil Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('108', 'about_features_mobile_desc', 'Tüm cihazlarda mükemmel çalışan responsive tasarımımızla her yerden erişim sağlayın.', 'textarea', 'Mobil Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('109', 'about_features_community_title', 'Aktif Topluluk', 'text', 'Topluluk Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('110', 'about_features_community_desc', 'Binlerce aktif kullanıcımızla yorumlar, beğeniler ve etkileşimlerle zengin bir deneyim yaşayın.', 'textarea', 'Topluluk Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('111', 'about_features_search_title', 'Gelişmiş Arama', 'text', 'Arama Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('112', 'about_features_search_desc', 'Güçlü arama ve filtreleme seçenekleriyle aradığınız içeriği kolayca bulun.', 'textarea', 'Arama Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('113', 'about_features_support_title', '7/24 Destek', 'text', 'Destek Özellik Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('114', 'about_features_support_desc', 'Sorularınız ve sorunlarınız için her zaman ulaşabileceğiniz destek ekibimiz var.', 'textarea', 'Destek Özellik Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('115', 'about_team_title', 'Ekibimiz', 'text', 'Ekip Bölüm Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('116', 'about_team_member1_name', 'Zeki Kurt', 'text', 'Ekip Üyesi 1 Adı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('117', 'about_team_member1_role', 'Kurucu & CEO', 'text', 'Ekip Üyesi 1 Pozisyonu', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('118', 'about_team_member1_desc', 'Manga tutkunu ve teknoloji uzmanı', 'text', 'Ekip Üyesi 1 Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('119', 'about_team_member2_name', 'Ayşe Demir', 'text', 'Ekip Üyesi 2 Adı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('120', 'about_team_member2_role', 'İçerik Editörü', 'text', 'Ekip Üyesi 2 Pozisyonu', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('121', 'about_team_member2_desc', 'Çizgi roman uzmanı ve editör', 'text', 'Ekip Üyesi 2 Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('122', 'about_team_member3_name', 'HelixPrime', 'text', 'Ekip Üyesi 3 Adı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('123', 'about_team_member3_role', 'Geliştirici', 'text', 'Ekip Üyesi 3 Pozisyonu', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('124', 'about_team_member3_desc', 'Full-stack developer', 'text', 'Ekip Üyesi 3 Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('125', 'about_team_member4_name', 'Fatma Özkan', 'text', 'Ekip Üyesi 4 Adı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('126', 'about_team_member4_role', 'Topluluk Yöneticisi', 'text', 'Ekip Üyesi 4 Pozisyonu', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('127', 'about_team_member4_desc', 'Kullanıcı deneyimi uzmanı', 'text', 'Ekip Üyesi 4 Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('128', 'about_contact_title', 'Bizimle İletişime Geçin', 'text', 'İletişim CTA Başlığı', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('129', 'about_contact_desc', 'Sorularınız, önerileriniz veya işbirliği teklifleriniz için bize ulaşın', 'textarea', 'İletişim CTA Açıklaması', 'pages', '2025-06-21 15:55:58', '2025-06-21 15:55:58'),
('130', 'series_info_title', 'Bölümler ve Seri Hakkında', 'text', 'Seri Bilgileri - Sayfa Başlığı', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('131', 'series_info_subtitle', 'Manga ve çizgi roman serilerinin nasıl çalıştığını öğrenin', 'text', 'Seri Bilgileri - Alt Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('132', 'series_what_title', 'Seri Nedir?', 'text', 'Seri Nedir - Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('133', 'series_what_content', 'Seri, birden fazla bölümden oluşan manga veya çizgi roman eserleridir. Her bölüm hikayenin bir parçasını anlatır ve okuyucular bölümleri sırayla takip ederek tam hikayeyi deneyimler.', 'textarea', 'Seri Nedir - İçerik', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('134', 'chapter_system_title', 'Bölüm Sistemi Nasıl Çalışır?', 'text', 'Bölüm Sistemi - Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('135', 'chapter_system_content', 'Her seri, numaralandırılmış bölümlerden oluşur. Okuyucular istediği bölümden başlayabilir, ancak hikayeyi tam anlamak için sırayla okumaları önerilir.', 'textarea', 'Bölüm Sistemi - İçerik', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('136', 'content_types_title', 'İçerik Türleri', 'text', 'İçerik Türleri - Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('137', 'content_types_content', 'Platformumuzda iki ana içerik türü bulunmaktadır:', 'text', 'İçerik Türleri - İçerik', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('138', 'manga_description', 'Japon tarzı çizgi romanlar. Genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir.', 'textarea', 'Manga Açıklaması', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('139', 'comic_description', 'Batı tarzı çizgi romanlar. Renkli çizimler ve soldan sağa okuma düzeni ile karakterize edilir.', 'textarea', 'Çizgi Roman Açıklaması', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('140', 'how_to_follow_title', 'Serileri Nasıl Takip Ederim?', 'text', 'Nasıl Takip Edilir - Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('141', 'how_to_follow_content', 'Sevdiğiniz serileri takip etmek için çeşitli özelliklerimizi kullanabilirsiniz.', 'textarea', 'Nasıl Takip Edilir - İçerik', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('142', 'for_authors_title', 'Yazarlar İçin Rehber', 'text', 'Yazarlar İçin - Başlık', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('143', 'for_authors_content', 'Kendi serinizi oluşturmak ve yönetmek için ipuçları.', 'textarea', 'Yazarlar İçin - İçerik', 'pages', '2025-06-21 16:09:09', '2025-06-21 16:09:09'),
('144', 'manga_info_title', 'Eser ve Manga Rehberi', 'text', 'Manga Bilgi - Sayfa Başlığı', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('145', 'manga_info_subtitle', 'Manga ve çizgi roman eserlerini nasıl keşfedeceğinizi ve okuyacağınızı öğrenin', 'text', 'Manga Bilgi - Alt Başlık', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('146', 'discover_content_title', 'Eser Nasıl Keşfedilir?', 'text', 'Eser Keşfetme - Başlık', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('147', 'discover_content_text', 'Platformumuzda binlerce manga ve çizgi roman eseri bulunmaktadır. İstediğiniz türde içerikleri keşfetmek için çeşitli yöntemler kullanabilirsiniz.', 'textarea', 'Eser Keşfetme - İçerik', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('148', 'how_to_read_title', 'Okuma Nasıl Başlanır?', 'text', 'Okuma Başlangıcı - Başlık', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('149', 'how_to_read_text', 'Bir eseri okumaya başlamak çok kolay! Aşağıdaki adımları takip ederek hemen okumaya başlayabilirsiniz.', 'textarea', 'Okuma Başlangıcı - İçerik', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('150', 'content_types_text', 'Platformumuzda iki ana eser türü bulunmaktadır:', 'text', 'Eser Türleri - İçerik', 'pages', '2025-06-21 16:22:26', '2025-06-21 16:22:26'),
('151', 'manga_info_description', 'Japon kültürünün ürünü olan manga eserleri, genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir. Çeşitli türlerde hikayeler sunar.', 'textarea', 'Manga Bilgi Açıklaması', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27'),
('152', 'comic_info_description', 'Batı kültürünün ürünü olan çizgi romanlar, soldan sağa okunur ve genellikle renkli çizimlerle sunulur. Süper kahramanlardan komedi türüne kadar geniş yelpaze.', 'textarea', 'Çizgi Roman Bilgi Açıklaması', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27'),
('153', 'demo_reading_title', 'Demo: Okuma Deneyimi', 'text', 'Demo Okuma - Başlık', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27'),
('154', 'demo_reading_text', 'Aşağıdaki örneklere tıklayarak okuma deneyimini test edebilirsiniz:', 'text', 'Demo Okuma - İçerik', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27'),
('155', 'reading_tips_title', 'Okuma İpuçları', 'text', 'Okuma İpuçları - Başlık', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27'),
('156', 'reading_tips_text', 'Daha iyi bir okuma deneyimi için aşağıdaki ipuçlarını takip edebilirsiniz.', 'textarea', 'Okuma İpuçları - İçerik', 'pages', '2025-06-21 16:22:27', '2025-06-21 16:22:27');


-- Table structure for table `system_logs`
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `system_logs`
INSERT INTO `system_logs` VALUES ('1', '3', 'clear_logs', '30 günden eski loglar temizlendi (0 kayıt)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 02:59:55'),
('2', '3', 'clear_logs', '30 günden eski loglar temizlendi (0 kayıt)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 03:00:59'),
('3', '3', 'clear_logs', '30 günden eski loglar temizlendi (0 kayıt)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 03:01:04'),
('4', '3', 'clear_logs', '30 günden eski loglar temizlendi (0 kayıt)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 14:47:14');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('3', 'helixprime', 'zekikurt134@gmail.com', '$2y$10$1m0MWF/TZqlCvyIz5eY9deaKPnxUoPFxUoVIbr3b/SHeZzfSM.d7u', 'LO0iEbZ24OLwPAd.png', '', 'admin', '2025-06-17 22:48:28', '2025-06-21 13:49:26');

