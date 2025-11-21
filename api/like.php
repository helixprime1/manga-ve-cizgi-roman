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
$content_check_stmt = $conn->prepare("SELECT id FROM content WHERE id = ? AND status = 'published'");
$content_check_stmt->bind_param('i', $content_id);
$content_check_stmt->execute();
$content_check_result = $content_check_stmt->get_result();
if ($content_check_result->num_rows === 0) {
    $response['message'] = 'İçerik bulunamadı';
    echo json_encode($response);
    exit;
}

// Beğeni durumunu kontrol et
$like_check_stmt = $conn->prepare("SELECT id FROM likes WHERE content_id = ? AND user_id = ?");
$like_check_stmt->bind_param('ii', $content_id, $user_id);
$like_check_stmt->execute();
$like_check = $like_check_stmt->get_result();

if ($like_check->num_rows > 0) {
    // Beğeniyi kaldır
    $delete_like_stmt = $conn->prepare("DELETE FROM likes WHERE content_id = ? AND user_id = ?");
    $delete_like_stmt->bind_param('ii', $content_id, $user_id);
    if ($delete_like_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Beğeni kaldırıldı';
        $response['action'] = 'removed';
    } else {
        $response['message'] = 'Beğeni kaldırılırken bir hata oluştu';
    }
} else {
    // Beğeni ekle
    $created_at = date('Y-m-d H:i:s');
    $insert_like_stmt = $conn->prepare("INSERT INTO likes (content_id, user_id, created_at) VALUES (?, ?, ?)");
    $insert_like_stmt->bind_param('iis', $content_id, $user_id, $created_at);
    if ($insert_like_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Beğenildi';
        $response['action'] = 'added';

        // İçerik sahibine bildirim gönder
        $content_info_stmt = $conn->prepare("SELECT user_id, title FROM content WHERE id = ?");
        $content_info_stmt->bind_param('i', $content_id);
        $content_info_stmt->execute();
        $content = $content_info_stmt->get_result()->fetch_assoc();
        if ($content && $content['user_id'] != $user_id) {
            $username_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $username_stmt->bind_param('i', $user_id);
            $username_stmt->execute();
            $username_result = $username_stmt->get_result()->fetch_assoc();
            $username = $username_result ? $username_result['username'] : '';
            $message = "$username '{$content['title']}' başlıklı içeriğinizi beğendi";
            $link = "view.php?id=$content_id";

            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, ?)");
            $notification_stmt->bind_param('isss', $content['user_id'], $message, $link, $created_at);
            $notification_stmt->execute();
        }
    } else {
        $response['message'] = 'Beğeni eklenirken bir hata oluştu';
    }
}

// Toplam beğeni sayısını al
$like_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE content_id = ?");
$like_count_stmt->bind_param('i', $content_id);
$like_count_stmt->execute();
$like_count_result = $like_count_stmt->get_result();
$like_count = $like_count_result->fetch_assoc()['count'];
$response['likes'] = $like_count;

echo json_encode($response);
?> 