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
$status = sanitizeInput($input['status'] ?? '');
$user_id = $_SESSION['user_id'];

// Parametreleri kontrol et
if ($chapter_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz bölüm ID']);
    exit;
}

if (!in_array($status, ['published', 'draft'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz durum']);
    exit;
}

try {
    // Bölümün kullanıcıya ait olduğunu kontrol et
    $check_stmt = mysqli_prepare($conn, "
        SELECT c.id 
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
    
    // Durumu güncelle
    $update_stmt = mysqli_prepare($conn, "UPDATE chapters SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "si", $status, $chapter_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Bölüm durumu başarıyla güncellendi',
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . mysqli_error($conn)]);
    }
    
    mysqli_stmt_close($update_stmt);
    mysqli_stmt_close($check_stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?> 