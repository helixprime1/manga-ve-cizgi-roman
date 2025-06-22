<?php
/**
 * Güvenlik Fonksiyonları
 */

// Rate limiting
function checkRateLimit($action, $user_id, $max_attempts = 5, $time_window = 300) {
    $key = $action . '_' . $user_id;
    
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [];
    }
    
    $now = time();
    $attempts = &$_SESSION['rate_limits'][$key];
    
    // Eski denemeleri temizle
    $attempts = array_filter($attempts, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Limit aşıldı mı?
    if (count($attempts) >= $max_attempts) {
        return false;
    }
    
    // Yeni denemeyi kaydet
    $attempts[] = $now;
    return true;
}

// IP Adresini güvenli şekilde al
function getUserIP() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Güvenli dosya yükleme kontrolü
function validateFileUpload($file, $allowed_types = [], $max_size = 2097152) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Dosya yükleme hatası.'];
    }
    
    // Dosya boyutu kontrolü
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Dosya çok büyük. Maksimum ' . formatBytes($max_size) . ' olabilir.'];
    }
    
    // MIME type kontrolü
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Geçersiz dosya türü.'];
    }
    
    // Dosya uzantısı kontrolü
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = [];
    
    foreach ($allowed_types as $type) {
        switch ($type) {
            case 'image/jpeg':
                $allowed_extensions[] = 'jpg';
                $allowed_extensions[] = 'jpeg';
                break;
            case 'image/png':
                $allowed_extensions[] = 'png';
                break;
            case 'image/gif':
                $allowed_extensions[] = 'gif';
                break;
            case 'application/pdf':
                $allowed_extensions[] = 'pdf';
                break;
        }
    }
    
    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Geçersiz dosya uzantısı.'];
    }
    
    return ['success' => true];
}

// Bytes formatla
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// XSS koruması için HTML temizle
function cleanHTML($html) {
    // Basit HTML temizleme - production'da HTMLPurifier kullanın
    $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
    return strip_tags($html, $allowed_tags);
}

// Güvenli redirect
function safeRedirect($url, $allowed_domains = []) {
    // Sadece aynı domain veya izin verilen domainlere yönlendir
    $parsed_url = parse_url($url);
    
    if (isset($parsed_url['host'])) {
        $current_host = $_SERVER['HTTP_HOST'];
        
        if ($parsed_url['host'] !== $current_host && !in_array($parsed_url['host'], $allowed_domains)) {
            $url = '/'; // Ana sayfaya yönlendir
        }
    }
    
    header('Location: ' . $url);
    exit;
}

// Session güvenliği
function secureSession() {
    // Session hijacking koruması
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_destroy();
        return false;
    }
    
    // Session regeneration
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    if (time() - $_SESSION['last_regeneration'] > 300) { // 5 dakikada bir
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    return true;
}

// Güvenlik başlıkları ekle
function addSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data:;');
}
?> 