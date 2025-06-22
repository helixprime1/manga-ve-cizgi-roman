<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// JSON response için header
header('Content-Type: application/json');

// Response array
$response = ['success' => false, 'message' => '', 'likes' => 0];

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    $response['message'] = 'Giriş yapmanız gerekiyor';
    echo json_encode($response);
    exit;
}

// POST verisi kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek';
    echo json_encode($response);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);

// JSON parse hatası kontrolü
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Geçersiz JSON verisi';
    echo json_encode($response);
    exit;
}

$content_id = isset($input['content_id']) ? (int)$input['content_id'] : 0;

if ($content_id <= 0) {
    $response['message'] = 'Geçersiz içerik ID';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// İçerik var mı kontrol et
$content_check = mysqli_query($conn, "SELECT id FROM content WHERE id = $content_id AND status = 'published'");
if (mysqli_num_rows($content_check) === 0) {
    $response['message'] = 'İçerik bulunamadı';
    echo json_encode($response);
    exit;
}

// Beğeni durumunu kontrol et
$like_check = mysqli_query($conn, "SELECT id FROM likes WHERE content_id = $content_id AND user_id = $user_id");

if (mysqli_num_rows($like_check) > 0) {
    // Beğeniyi kaldır
    $query = "DELETE FROM likes WHERE content_id = $content_id AND user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        $response['success'] = true;
        $response['message'] = 'Beğeni kaldırıldı';
        $response['action'] = 'removed';
    } else {
        $response['message'] = 'Beğeni kaldırılırken bir hata oluştu';
    }
} else {
    // Beğeni ekle
    $created_at = date('Y-m-d H:i:s');
    $query = "INSERT INTO likes (content_id, user_id, created_at) VALUES ($content_id, $user_id, '$created_at')";
    if (mysqli_query($conn, $query)) {
        $response['success'] = true;
        $response['message'] = 'Beğenildi';
        $response['action'] = 'added';
        
        // İçerik sahibine bildirim gönder
        $content = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id, title FROM content WHERE id = $content_id"));
        if ($content['user_id'] != $user_id) {
            $username = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id = $user_id"))['username'];
            $message = "$username '{$content['title']}' başlıklı içeriğinizi beğendi";
            $link = "view.php?id=$content_id";
            
            $query = "INSERT INTO notifications (user_id, message, link, created_at) 
                      VALUES ({$content['user_id']}, '$message', '$link', '$created_at')";
            mysqli_query($conn, $query);
        }
    } else {
        $response['message'] = 'Beğeni eklenirken bir hata oluştu';
    }
}

// Toplam beğeni sayısını al
$like_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM likes WHERE content_id = $content_id"))['count'];
$response['likes'] = $like_count;

echo json_encode($response);
?> 