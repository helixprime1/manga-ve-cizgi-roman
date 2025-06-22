<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// JSON response için header
header('Content-Type: application/json');

// Response array
$response = ['success' => false, 'message' => ''];

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

// Favori durumunu kontrol et
$fav_check = mysqli_query($conn, "SELECT id FROM favorites WHERE content_id = $content_id AND user_id = $user_id");

if (mysqli_num_rows($fav_check) > 0) {
    // Favorilerden kaldır
    $query = "DELETE FROM favorites WHERE content_id = $content_id AND user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        $response['success'] = true;
        $response['message'] = 'Favorilerden kaldırıldı';
        $response['action'] = 'removed';
    } else {
        $response['message'] = 'Favorilerden kaldırılırken bir hata oluştu';
    }
} else {
    // Favorilere ekle
    $created_at = date('Y-m-d H:i:s');
    $query = "INSERT INTO favorites (content_id, user_id, created_at) VALUES ($content_id, $user_id, '$created_at')";
    if (mysqli_query($conn, $query)) {
        $response['success'] = true;
        $response['message'] = 'Favorilere eklendi';
        $response['action'] = 'added';
    } else {
        $response['message'] = 'Favorilere eklenirken bir hata oluştu';
    }
}

echo json_encode($response);
?> 