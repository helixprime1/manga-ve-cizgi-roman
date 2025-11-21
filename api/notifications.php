<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getUserById($user_id);

if (!$current_user) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    exit;
}

$user_role = $_SESSION['role'] ?? ($current_user['role'] ?? '');
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_count':
        // Okunmamış bildirim sayısını getir
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'count' => (int)$row['count']
        ]);
        break;
        
    case 'get_recent':
        $limit = (int)($_GET['limit'] ?? 5);
        
        // Son bildirimleri getir
        $query = "SELECT * FROM notifications 
                  WHERE user_id = $user_id 
                  ORDER BY created_at DESC 
                  LIMIT $limit";
        $result = mysqli_query($conn, $query);
        
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => htmlspecialchars($row['title']),
                'message' => htmlspecialchars($row['message']),
                'link' => $row['link'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => timeAgo($row['created_at']),
                'icon' => getNotificationIcon($row['type'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        break;
        
    case 'mark_read':
        $notification_id = (int)($_POST['notification_id'] ?? 0);
        
        if ($notification_id > 0) {
            $query = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id AND user_id = $user_id";
        } else {
            // Tümünü okundu işaretle
            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
        }
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Bildirimler okundu olarak işaretlendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirimler güncellenirken hata oluştu.']);
        }
        break;
        
    case 'delete':
        $notification_id = (int)($_POST['notification_id'] ?? 0);
        
        if ($notification_id > 0) {
            $query = "DELETE FROM notifications WHERE id = $notification_id AND user_id = $user_id";
        } else {
            // Tümünü sil
            $query = "DELETE FROM notifications WHERE user_id = $user_id";
        }
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Bildirimler başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirimler silinirken hata oluştu.']);
        }
        break;
        
    case 'send':
        // Admin yetkisi gerekli
        if ($user_role !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
            exit;
        }
        
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $type = sanitizeInput($_POST['type'] ?? 'system');
        $title = sanitizeInput($_POST['title'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $link = sanitizeInput($_POST['link'] ?? '');
        
        if (empty($title) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Başlık ve mesaj boş olamaz.']);
            exit;
        }
        
        if ($target_user_id > 0) {
            // Belirli kullanıcıya gönder
            $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                      VALUES ($target_user_id, '$type', '$title', '$message', '$link', NOW())";
        } else {
            // Tüm kullanıcılara gönder
            $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                      SELECT id, '$type', '$title', '$message', '$link', NOW() FROM users";
        }
        
        if (mysqli_query($conn, $query)) {
            $affected_rows = mysqli_affected_rows($conn);
            echo json_encode([
                'success' => true, 
                'message' => "Bildirim $affected_rows kullanıcıya gönderildi."
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bildirim gönderilirken hata oluştu.']);
        }
        break;
        
    case 'get_settings':
        // Kullanıcının bildirim ayarlarını getir
        $query = "SELECT notification_likes, notification_comments, notification_follows, notification_system 
                  FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $query);
        $settings = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'settings' => [
                'likes' => (bool)($settings['notification_likes'] ?? 1),
                'comments' => (bool)($settings['notification_comments'] ?? 1),
                'follows' => (bool)($settings['notification_follows'] ?? 1),
                'system' => (bool)($settings['notification_system'] ?? 1)
            ]
        ]);
        break;
        
    case 'update_settings':
        $likes = (int)($_POST['likes'] ?? 1);
        $comments = (int)($_POST['comments'] ?? 1);
        $follows = (int)($_POST['follows'] ?? 1);
        $system = (int)($_POST['system'] ?? 1);
        
        $query = "UPDATE users SET 
                  notification_likes = $likes,
                  notification_comments = $comments,
                  notification_follows = $follows,
                  notification_system = $system
                  WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Bildirim ayarları güncellendi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ayarlar güncellenirken hata oluştu.']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'like':
            return 'fas fa-heart text-danger';
        case 'comment':
            return 'fas fa-comment text-primary';
        case 'favorite':
            return 'fas fa-bookmark text-warning';
        case 'follow':
            return 'fas fa-user-plus text-success';
        case 'system':
            return 'fas fa-cog text-secondary';
        default:
            return 'fas fa-bell text-info';
    }
}
?> 