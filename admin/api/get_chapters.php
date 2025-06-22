<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetki hatası']);
    exit;
}

$content_id = (int)($_GET['content_id'] ?? 0);

if ($content_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz içerik ID']);
    exit;
}

try {
    // İçeriğin var olduğunu ve seri olduğunu kontrol et
    $content_query = "SELECT id, title, is_series FROM content WHERE id = $content_id";
    $content_result = mysqli_query($conn, $content_query);
    
    if (mysqli_num_rows($content_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı']);
        exit;
    }
    
    $content = mysqli_fetch_assoc($content_result);
    
    if (!$content['is_series']) {
        echo json_encode(['success' => false, 'message' => 'Bu içerik bir seri değil']);
        exit;
    }
    
    // Bölümleri getir
    $chapters_query = "SELECT * FROM chapters WHERE content_id = $content_id ORDER BY chapter_number ASC";
    $chapters_result = mysqli_query($conn, $chapters_query);
    
    $chapters = [];
    while ($chapter = mysqli_fetch_assoc($chapters_result)) {
        $chapters[] = $chapter;
    }
    
    echo json_encode([
        'success' => true,
        'chapters' => $chapters,
        'content_title' => $content['title']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?> 