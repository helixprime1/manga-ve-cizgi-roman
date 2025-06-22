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

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$chapter_id = (int)($input['chapter_id'] ?? 0);

if ($chapter_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz bölüm ID']);
    exit;
}

try {
    // Bölümün var olduğunu kontrol et ve dosya bilgilerini al
    $chapter_query = "SELECT id, content_id, chapter_files FROM chapters WHERE id = $chapter_id";
    $chapter_result = mysqli_query($conn, $chapter_query);
    
    if (mysqli_num_rows($chapter_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Bölüm bulunamadı']);
        exit;
    }
    
    $chapter = mysqli_fetch_assoc($chapter_result);
    $content_id = $chapter['content_id'];
    
    // Bölüm dosyalarını sil
    $chapter_files = json_decode($chapter['chapter_files'], true);
    if (is_array($chapter_files)) {
        foreach ($chapter_files as $file) {
            $file_path = '../../uploads/content/' . $file;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Bölümü veritabanından sil
    $delete_query = "DELETE FROM chapters WHERE id = $chapter_id";
    
    if (mysqli_query($conn, $delete_query)) {
        // Ana içeriğin bölüm sayısını güncelle
        $update_count_query = "UPDATE content SET chapter_count = (SELECT COUNT(*) FROM chapters WHERE content_id = $content_id) WHERE id = $content_id";
        mysqli_query($conn, $update_count_query);
        
        echo json_encode([
            'success' => true,
            'message' => 'Bölüm başarıyla silindi'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı silme hatası']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?> 