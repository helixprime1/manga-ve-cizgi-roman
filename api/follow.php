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
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'follow':
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        
        // Geçerli kullanıcı ID'si mi kontrol et
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Kendini takip etmeye çalışıyor mu
        if ($target_user_id == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Kendinizi takip edemezsiniz.']);
            exit;
        }
        
        // Hedef kullanıcı var mı kontrol et
        $query = "SELECT id, username FROM users WHERE id = $target_user_id";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
            exit;
        }
        $target_user = mysqli_fetch_assoc($result);
        
        // Zaten takip ediyor mu kontrol et
        $query = "SELECT id FROM follows WHERE follower_id = $user_id AND following_id = $target_user_id";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyı zaten takip ediyorsunuz.']);
            exit;
        }
        
        // Takip et
        $query = "INSERT INTO follows (follower_id, following_id, created_at) VALUES ($user_id, $target_user_id, NOW())";
        
        if (mysqli_query($conn, $query)) {
            // Bildirim oluştur
            $follower_username = $_SESSION['username'];
            $notification_message = "$follower_username sizi takip etmeye başladı.";
            
            $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                      VALUES ($target_user_id, 'follow', 'Yeni Takipçi', '$notification_message', 
                      'profile.php?user={$_SESSION['username']}', NOW())";
            mysqli_query($conn, $query);
            
            // Takipçi sayısını güncelle
            $query = "UPDATE users SET followers_count = (SELECT COUNT(*) FROM follows WHERE following_id = $target_user_id) WHERE id = $target_user_id";
            mysqli_query($conn, $query);
            
            $query = "UPDATE users SET following_count = (SELECT COUNT(*) FROM follows WHERE follower_id = $user_id) WHERE id = $user_id";
            mysqli_query($conn, $query);
            
            echo json_encode([
                'success' => true, 
                'message' => $target_user['username'] . ' kullanıcısını takip ediyorsunuz.',
                'action' => 'followed'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Takip edilirken hata oluştu.']);
        }
        break;
        
    case 'unfollow':
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        
        // Geçerli kullanıcı ID'si mi kontrol et
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Hedef kullanıcı var mı kontrol et
        $query = "SELECT username FROM users WHERE id = $target_user_id";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
            exit;
        }
        $target_user = mysqli_fetch_assoc($result);
        
        // Takip ediyor mu kontrol et
        $query = "SELECT id FROM follows WHERE follower_id = $user_id AND following_id = $target_user_id";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Bu kullanıcıyı takip etmiyorsunuz.']);
            exit;
        }
        
        // Takibi bırak
        $query = "DELETE FROM follows WHERE follower_id = $user_id AND following_id = $target_user_id";
        
        if (mysqli_query($conn, $query)) {
            // Takipçi sayısını güncelle
            $query = "UPDATE users SET followers_count = (SELECT COUNT(*) FROM follows WHERE following_id = $target_user_id) WHERE id = $target_user_id";
            mysqli_query($conn, $query);
            
            $query = "UPDATE users SET following_count = (SELECT COUNT(*) FROM follows WHERE follower_id = $user_id) WHERE id = $user_id";
            mysqli_query($conn, $query);
            
            echo json_encode([
                'success' => true, 
                'message' => $target_user['username'] . ' kullanıcısını takip etmeyi bıraktınız.',
                'action' => 'unfollowed'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Takip bırakılırken hata oluştu.']);
        }
        break;
        
    case 'get_status':
        $target_user_id = (int)($_GET['user_id'] ?? 0);
        
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Takip durumunu kontrol et
        $query = "SELECT id FROM follows WHERE follower_id = $user_id AND following_id = $target_user_id";
        $result = mysqli_query($conn, $query);
        $is_following = mysqli_num_rows($result) > 0;
        
        // Kullanıcı istatistiklerini getir
        $query = "SELECT followers_count, following_count FROM users WHERE id = $target_user_id";
        $result = mysqli_query($conn, $query);
        $stats = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'is_following' => $is_following,
            'followers_count' => (int)($stats['followers_count'] ?? 0),
            'following_count' => (int)($stats['following_count'] ?? 0)
        ]);
        break;
        
    case 'get_followers':
        $target_user_id = (int)($_GET['user_id'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Takipçileri getir
        $query = "SELECT u.id, u.username, u.avatar, u.created_at,
                  (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
                  (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
                  (SELECT COUNT(*) FROM follows WHERE follower_id = $user_id AND following_id = u.id) as is_following
                  FROM follows f 
                  JOIN users u ON f.follower_id = u.id 
                  WHERE f.following_id = $target_user_id 
                  ORDER BY f.created_at DESC 
                  LIMIT $per_page OFFSET $offset";
        $result = mysqli_query($conn, $query);
        
        $followers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $followers[] = [
                'id' => $row['id'],
                'username' => htmlspecialchars($row['username']),
                'avatar' => $row['avatar'],
                'followers_count' => (int)$row['followers_count'],
                'following_count' => (int)$row['following_count'],
                'is_following' => (bool)$row['is_following'],
                'member_since' => date('Y', strtotime($row['created_at']))
            ];
        }
        
        // Toplam takipçi sayısı
        $query = "SELECT COUNT(*) as total FROM follows WHERE following_id = $target_user_id";
        $result = mysqli_query($conn, $query);
        $total = mysqli_fetch_assoc($result)['total'];
        
        echo json_encode([
            'success' => true,
            'followers' => $followers,
            'total' => (int)$total,
            'has_more' => ($offset + $per_page) < $total
        ]);
        break;
        
    case 'get_following':
        $target_user_id = (int)($_GET['user_id'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Takip edilenleri getir
        $query = "SELECT u.id, u.username, u.avatar, u.created_at,
                  (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
                  (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
                  (SELECT COUNT(*) FROM follows WHERE follower_id = $user_id AND following_id = u.id) as is_following
                  FROM follows f 
                  JOIN users u ON f.following_id = u.id 
                  WHERE f.follower_id = $target_user_id 
                  ORDER BY f.created_at DESC 
                  LIMIT $per_page OFFSET $offset";
        $result = mysqli_query($conn, $query);
        
        $following = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $following[] = [
                'id' => $row['id'],
                'username' => htmlspecialchars($row['username']),
                'avatar' => $row['avatar'],
                'followers_count' => (int)$row['followers_count'],
                'following_count' => (int)$row['following_count'],
                'is_following' => (bool)$row['is_following'],
                'member_since' => date('Y', strtotime($row['created_at']))
            ];
        }
        
        // Toplam takip edilen sayısı
        $query = "SELECT COUNT(*) as total FROM follows WHERE follower_id = $target_user_id";
        $result = mysqli_query($conn, $query);
        $total = mysqli_fetch_assoc($result)['total'];
        
        echo json_encode([
            'success' => true,
            'following' => $following,
            'total' => (int)$total,
            'has_more' => ($offset + $per_page) < $total
        ]);
        break;
        
    case 'get_suggestions':
        $limit = (int)($_GET['limit'] ?? 5);
        
        // Takip önerileri getir (popüler kullanıcılar, henüz takip edilmeyenler)
        $query = "SELECT u.id, u.username, u.avatar, u.followers_count,
                  (SELECT COUNT(*) FROM content WHERE user_id = u.id AND status = 'published') as content_count
                  FROM users u 
                  WHERE u.id != $user_id 
                  AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = $user_id)
                  AND u.followers_count > 0
                  ORDER BY u.followers_count DESC, u.created_at DESC 
                  LIMIT $limit";
        $result = mysqli_query($conn, $query);
        
        $suggestions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $suggestions[] = [
                'id' => $row['id'],
                'username' => htmlspecialchars($row['username']),
                'avatar' => $row['avatar'],
                'followers_count' => (int)$row['followers_count'],
                'content_count' => (int)$row['content_count']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        break;
        
    case 'check_mutual':
        $target_user_id = (int)($_GET['user_id'] ?? 0);
        
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID.']);
            exit;
        }
        
        // Karşılıklı takip var mı kontrol et
        $query = "SELECT 
                  (SELECT COUNT(*) FROM follows WHERE follower_id = $user_id AND following_id = $target_user_id) as i_follow,
                  (SELECT COUNT(*) FROM follows WHERE follower_id = $target_user_id AND following_id = $user_id) as follows_me";
        $result = mysqli_query($conn, $query);
        $mutual = mysqli_fetch_assoc($result);
        
        $is_mutual = ($mutual['i_follow'] > 0 && $mutual['follows_me'] > 0);
        
        echo json_encode([
            'success' => true,
            'is_mutual' => $is_mutual,
            'i_follow' => (bool)$mutual['i_follow'],
            'follows_me' => (bool)$mutual['follows_me']
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
}
?> 