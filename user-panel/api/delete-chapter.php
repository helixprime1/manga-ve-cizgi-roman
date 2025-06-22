<?php
header('Content-Type: application/json');

// Yetki kontrolü
require_once '../includes/auth-check.php';

// Yazar olmayan kullanıcılar için ek kontrol
$current_user = getCurrentUser();
if (!$current_user || !$current_user['is_author']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yazar yetkisi gerekli']);
    exit;
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

// JSON verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi']);
    exit;
}

$chapter_id = (int)($input['chapter_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Parametreleri kontrol et
if ($chapter_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz bölüm ID']);
    exit;
}

try {
    // Bölümün kullanıcıya ait olduğunu kontrol et ve dosya bilgilerini al
    $check_stmt = mysqli_prepare($conn, "
        SELECT ch.*, c.id as content_id
        FROM chapters ch 
        JOIN content c ON ch.content_id = c.id 
        WHERE ch.id = ? AND c.user_id = ?
    ");
    mysqli_stmt_bind_param($check_stmt, "ii", $chapter_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Bölüm bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    $chapter = mysqli_fetch_assoc($check_result);
    $content_id = $chapter['content_id'];
    
    // Transaction başlat
    mysqli_autocommit($conn, false);
    
    try {
        // Dosyaları sil
        if (!empty($chapter['chapter_files'])) {
            $chapter_files = json_decode($chapter['chapter_files'], true);
            if (is_array($chapter_files)) {
                foreach ($chapter_files as $file) {
                    $file_path = '../../uploads/content/' . $file;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            } else {
                // Tek dosya
                $file_path = '../../uploads/content/' . $chapter['chapter_files'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
        
        // Bölümü sil
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM chapters WHERE id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $chapter_id);
        
        if (!mysqli_stmt_execute($delete_stmt)) {
            throw new Exception("Bölüm silinirken hata: " . mysqli_error($conn));
        }
        
        // Ana içeriğin bölüm sayısını güncelle
        $update_stmt = mysqli_prepare($conn, "UPDATE content SET chapter_count = (SELECT COUNT(*) FROM chapters WHERE content_id = ?) WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "ii", $content_id, $content_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Bölüm sayısı güncellenirken hata: " . mysqli_error($conn));
        }
        
        // Commit
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bölüm başarıyla silindi'
        ]);
        
        mysqli_stmt_close($delete_stmt);
        mysqli_stmt_close($update_stmt);
        
    } catch (Exception $e) {
        // Rollback
        mysqli_rollback($conn);
        throw $e;
    } finally {
        mysqli_autocommit($conn, true);
    }
    
    mysqli_stmt_close($check_stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?> 