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
    // Bölümün var olduğunu kontrol et
    $chapter_query = "SELECT id, status FROM chapters WHERE id = $chapter_id";
    $chapter_result = mysqli_query($conn, $chapter_query);
    
    if (mysqli_num_rows($chapter_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Bölüm bulunamadı']);
        exit;
    }
    
    $chapter = mysqli_fetch_assoc($chapter_result);
    
    // Durumu değiştir
    $new_status = $chapter['status'] === 'published' ? 'draft' : 'published';
    
    $update_query = "UPDATE chapters SET status = '$new_status' WHERE id = $chapter_id";
    
    if (mysqli_query($conn, $update_query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Bölüm durumu başarıyla güncellendi',
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı güncelleme hatası']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?> 