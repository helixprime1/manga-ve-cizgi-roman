-- Moderasyon paneli için gerekli veritabanı yapısı

-- Users tablosuna moderatör rolü ekleme (eğer yoksa)
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'moderator') DEFAULT 'user';

-- Content tablosuna moderasyon alanları ekleme (eğer yoksa)
ALTER TABLE content 
ADD COLUMN IF NOT EXISTS moderated_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS moderated_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS moderation_reason TEXT DEFAULT NULL,
ADD FOREIGN KEY IF NOT EXISTS (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Comments tablosuna moderasyon alanları ekleme (eğer yoksa)
ALTER TABLE comments 
ADD COLUMN IF NOT EXISTS is_reported BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS is_hidden BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS moderated_by INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS moderated_at TIMESTAMP NULL DEFAULT NULL,
ADD FOREIGN KEY IF NOT EXISTS (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Moderasyon logları tablosu
CREATE TABLE IF NOT EXISTS moderation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moderator_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    target_type ENUM('content', 'comment', 'user') NOT NULL,
    target_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_moderator_id (moderator_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
);

-- Bildirimler tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Rapor sistemi tablosu
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    target_type ENUM('content', 'comment', 'user') NOT NULL,
    target_id INT NOT NULL,
    reason VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reporter_id (reporter_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- İlk moderatör kullanıcısı oluşturma (username: moderator, password: moderator123)
INSERT IGNORE INTO users (username, email, password, role, full_name, created_at) 
VALUES ('moderator', 'moderator@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator', 'Moderatör', NOW());

-- Bazı örnek moderasyon logları
INSERT IGNORE INTO moderation_logs (moderator_id, action, target_type, target_id, description, created_at)
SELECT 
    (SELECT id FROM users WHERE role = 'moderator' LIMIT 1),
    'setup',
    'content',
    1,
    'Moderasyon sistemi kuruldu',
    NOW()
WHERE EXISTS (SELECT 1 FROM users WHERE role = 'moderator' LIMIT 1); 