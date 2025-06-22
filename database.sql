-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS manga_comic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE manga_comic_db;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- İçerik tablosu
CREATE TABLE IF NOT EXISTS content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('manga', 'comic') NOT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) NOT NULL,
    content_file VARCHAR(255) NOT NULL,
    status ENUM('pending', 'published', 'rejected') DEFAULT 'pending',
    featured TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Yorumlar tablosu
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Beğeniler tablosu
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY (content_id, user_id),
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Favoriler tablosu
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY (content_id, user_id),
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bildirimler tablosu
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Site ayarları tablosu
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Örnek veriler

-- Admin kullanıcısı
INSERT INTO users (username, email, password, role, created_at) VALUES 
('admin', 'admin@example.com', '$2y$10$9jTMCwNxOYs/YhT9oZQSAeQyRSY9VL7p.ZUwc.JxD0FTbiRWR5UPO', 'admin', NOW());
-- Şifre: admin123

-- Normal kullanıcı
INSERT INTO users (username, email, password, role, created_at) VALUES 
('kullanici', 'kullanici@example.com', '$2y$10$QlZMoDmGYY9SQB4Nz1DQWe9.UYLehz2jE7MG3fYjI1hVOqFYX.UEK', 'user', NOW());
-- Şifre: 123456

-- Site ayarları
INSERT INTO settings (setting_key, setting_value, created_at) VALUES
('site_title', 'MangaÇizgiRoman', NOW()),
('site_description', 'Manga ve çizgi roman paylaşım platformu', NOW()),
('site_email', 'info@mangacizgiroman.com', NOW()),
('registration_enabled', '1', NOW()),
('auto_approve_content', '0', NOW()),
('max_upload_size', '10485760', NOW()),
('allowed_extensions', 'jpg,jpeg,png,gif,pdf', NOW()),
('meta_keywords', '', NOW()),
('meta_description', '', NOW()),
('google_analytics', '', NOW()),
('smtp_host', '', NOW()),
('smtp_port', '', NOW()),
('smtp_username', '', NOW()),
('smtp_password', '', NOW()),
('smtp_encryption', '', NOW()),
('facebook_url', '', NOW()),
('twitter_url', '', NOW()),
('instagram_url', '', NOW()),
('youtube_url', '', NOW()),
('maintenance_mode', '0', NOW()); 