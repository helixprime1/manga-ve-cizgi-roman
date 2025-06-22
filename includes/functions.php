<?php
// Ayar değeri alma fonksiyonu
function getSetting($key, $default = '') {
    global $conn;
    static $settings_cache = [];
    
    // Cache'den kontrol et
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $settings_cache[$key] = $row['setting_value'];
            mysqli_stmt_close($stmt);
            return $row['setting_value'];
        }
        mysqli_stmt_close($stmt);
    }
    
    $settings_cache[$key] = $default;
    return $default;
}

// Tüm ayarları getir
function getAllSettings() {
    global $conn;
    static $all_settings = null;
    
    if ($all_settings !== null) {
        return $all_settings;
    }
    
    $all_settings = [];
    $query = "SELECT setting_key, setting_value FROM settings";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $all_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $all_settings;
}

// Güvenlik fonksiyonları
function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// CSRF Token fonksiyonları
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function getCSRFTokenInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Kullanıcı işlemleri
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserById($id) {
    global $conn;
    $id = (int)$id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return getUserById($_SESSION['user_id']);
    }
    return false;
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Moderatör kontrolü
function isModerator() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && $user['role'] === 'moderator';
}

// Admin veya moderatör kontrolü
function isAdminOrModerator() {
    return isAdmin() || isModerator();
}

// İçerik işlemleri
function getFeaturedContent($limit = 6) {
    global $conn;
    $limit = (int)$limit;
    $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE status = 'published' AND featured = 1 ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $items;
}

function getLatestContent($type = null, $limit = 10) {
    global $conn;
    $limit = (int)$limit;
    
    if ($type) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE status = 'published' AND type = ? ORDER BY created_at DESC LIMIT ?");
        mysqli_stmt_bind_param($stmt, "si", $type, $limit);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE status = 'published' ORDER BY created_at DESC LIMIT ?");
        mysqli_stmt_bind_param($stmt, "i", $limit);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $items;
}

function getContentById($id) {
    global $conn;
    $id = (int)$id;
    $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $content = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $content;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function getContentByUser($userId, $limit = 10) {
    global $conn;
    $userId = (int)$userId;
    $limit = (int)$limit;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $items;
}

function searchContent($keyword) {
    global $conn;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE status = 'published' AND (title LIKE ? OR description LIKE ? OR tags LIKE ?) ORDER BY created_at DESC LIMIT 20");
    $search_term = '%' . $keyword . '%';
    mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $items;
}

// Dosya işlemleri
function uploadFile($file, $targetDir) {
    // Dosya güvenlik kontrolleri
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Geçersiz dosya yükleme.'];
    }
    
    // Dosya uzantısını kontrol et
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı.'];
    }
    
    // Dosya boyutunu kontrol et
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum ' . (UPLOAD_MAX_SIZE / 1048576) . 'MB olabilir.'];
    }
    
    // MIME type kontrolü (ek güvenlik)
    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf'
    ];
    
    if (isset($allowedMimeTypes[$fileExtension])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mimeType !== $allowedMimeTypes[$fileExtension]) {
            return ['success' => false, 'message' => 'Dosya türü uyumsuz.'];
        }
    }
    
    // Hedef dizini oluştur
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return ['success' => false, 'message' => 'Hedef dizin oluşturulamadı.'];
        }
    }
    
    // Benzersiz dosya adı oluştur
    $fileName = generateRandomString(15) . '.' . $fileExtension;
    $targetFile = $targetDir . '/' . $fileName;
    
    // Dosyayı yükle
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Dosya izinlerini ayarla
        chmod($targetFile, 0644);
        return ['success' => true, 'file_name' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Dosya yüklenirken bir hata oluştu.'];
    }
}

// Sayfalama fonksiyonu
function paginate($total, $perPage = 10, $page = 1) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    
    $start = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'start' => $start
    ];
}

// Seri durumunu otomatik güncelleme fonksiyonu
function updateSeriesStatus($conn) {
    // Chapters tablosuna bakarak hangi content'lerin seri olduğunu belirle
    $chapter_counts_query = "SELECT content_id, COUNT(*) as chapter_count FROM chapters GROUP BY content_id";
    $chapter_counts_result = mysqli_query($conn, $chapter_counts_query);
    
    $series_contents = [];
    while ($row = mysqli_fetch_assoc($chapter_counts_result)) {
        $series_contents[$row['content_id']] = $row['chapter_count'];
    }
    
    // Tüm content'leri güncelle
    $all_content_query = "SELECT id FROM content";
    $all_content_result = mysqli_query($conn, $all_content_query);
    
    while ($content = mysqli_fetch_assoc($all_content_result)) {
        $content_id = $content['id'];
        
        if (isset($series_contents[$content_id])) {
            // Bu content'in bölümleri var, seri olarak işaretle
            $chapter_count = $series_contents[$content_id];
            $update_query = "UPDATE content SET is_series = 1, chapter_count = $chapter_count WHERE id = $content_id";
        } else {
            // Bu content'in bölümü yok, tek eser olarak işaretle
            $update_query = "UPDATE content SET is_series = 0, chapter_count = 1 WHERE id = $content_id";
        }
        
        mysqli_query($conn, $update_query);
    }
}
?> 