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
    case 'add':
        $content_id = (int)($_POST['content_id'] ?? 0);
        $comment_text = sanitizeInput($_POST['comment'] ?? '');
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        
        // Validasyon
        if (empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Yorum boş olamaz.']);
            exit;
        }
        
        if (strlen($comment_text) < 3) {
            echo json_encode(['success' => false, 'message' => 'Yorum en az 3 karakter olmalıdır.']);
            exit;
        }
        
        if (strlen($comment_text) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Yorum en fazla 1000 karakter olabilir.']);
            exit;
        }
        
        // İçerik var mı kontrol et
        $query = "SELECT id FROM content WHERE id = $content_id AND status = 'published'";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı.']);
            exit;
        }
        
        // Eğer parent_id varsa, parent yorum var mı kontrol et
        if ($parent_id > 0) {
            $query = "SELECT id FROM comments WHERE id = $parent_id AND content_id = $content_id";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) === 0) {
                echo json_encode(['success' => false, 'message' => 'Yanıtlanan yorum bulunamadı.']);
                exit;
            }
        }
        
        // Spam kontrolü (son 1 dakikada aynı kullanıcıdan 3'ten fazla yorum)
        $query = "SELECT COUNT(*) as count FROM comments 
                  WHERE user_id = $user_id AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        if ($row['count'] >= 3) {
            echo json_encode(['success' => false, 'message' => 'Çok hızlı yorum yapıyorsunuz. Lütfen bekleyin.']);
            exit;
        }
        
        // Yorumu ekle
        $query = "INSERT INTO comments (content_id, user_id, parent_id, comment, created_at) 
                  VALUES ($content_id, $user_id, " . ($parent_id ?: 'NULL') . ", '$comment_text', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $comment_id = mysqli_insert_id($conn);
            
            // Bildirim oluştur (içerik sahibine)
            $query = "SELECT user_id FROM content WHERE id = $content_id";
            $result = mysqli_query($conn, $query);
            $content_owner = mysqli_fetch_assoc($result);
            
            if ($content_owner['user_id'] != $user_id) {
                $username = $_SESSION['username'];
                $notification_message = "$username içeriğinize yorum yaptı: " . mb_substr($comment_text, 0, 50) . "...";
                
                $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                          VALUES ({$content_owner['user_id']}, 'comment', 'Yeni Yorum', '$notification_message', 
                          'view.php?id=$content_id#comment-$comment_id', NOW())";
                mysqli_query($conn, $query);
            }
            
            // Eğer bir yoruma yanıt ise, o yorumun sahibine bildirim gönder
            if ($parent_id > 0) {
                $query = "SELECT user_id FROM comments WHERE id = $parent_id";
                $result = mysqli_query($conn, $query);
                $parent_owner = mysqli_fetch_assoc($result);
                
                if ($parent_owner['user_id'] != $user_id && $parent_owner['user_id'] != $content_owner['user_id']) {
                    $username = $_SESSION['username'];
                    $notification_message = "$username yorumunuza yanıt verdi: " . mb_substr($comment_text, 0, 50) . "...";
                    
                    $query = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                              VALUES ({$parent_owner['user_id']}, 'comment', 'Yoruma Yanıt', '$notification_message', 
                              'view.php?id=$content_id#comment-$comment_id', NOW())";
                    mysqli_query($conn, $query);
                }
            }
            
            // Yorum bilgilerini getir
            $query = "SELECT c.*, u.username, u.avatar 
                      FROM comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.id = $comment_id";
            $result = mysqli_query($conn, $query);
            $comment = mysqli_fetch_assoc($result);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Yorum başarıyla eklendi.',
                'comment' => [
                    'id' => $comment['id'],
                    'comment' => htmlspecialchars($comment['comment']),
                    'username' => htmlspecialchars($comment['username']),
                    'avatar' => $comment['avatar'],
                    'created_at' => timeAgo($comment['created_at']),
                    'parent_id' => $comment['parent_id']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Yorum eklenirken hata oluştu.']);
        }
        break;
        
    case 'delete':
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        
        // Yorumun sahibi mi veya admin mi kontrol et
        $query = "SELECT user_id FROM comments WHERE id = $comment_id";
        $result = mysqli_query($conn, $query);
        $comment = mysqli_fetch_assoc($result);
        
        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            exit;
        }
        
        if ($comment['user_id'] != $user_id && $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
            exit;
        }
        
        // Yorumu sil (cascade ile alt yorumlar da silinir)
        $query = "DELETE FROM comments WHERE id = $comment_id OR parent_id = $comment_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Yorum başarıyla silindi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Yorum silinirken hata oluştu.']);
        }
        break;
        
    case 'edit':
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $comment_text = sanitizeInput($_POST['comment'] ?? '');
        
        // Validasyon
        if (empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Yorum boş olamaz.']);
            exit;
        }
        
        if (strlen($comment_text) < 3) {
            echo json_encode(['success' => false, 'message' => 'Yorum en az 3 karakter olmalıdır.']);
            exit;
        }
        
        if (strlen($comment_text) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Yorum en fazla 1000 karakter olabilir.']);
            exit;
        }
        
        // Yorumun sahibi mi kontrol et
        $query = "SELECT user_id, created_at FROM comments WHERE id = $comment_id";
        $result = mysqli_query($conn, $query);
        $comment = mysqli_fetch_assoc($result);
        
        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            exit;
        }
        
        if ($comment['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Bu yorumu düzenleme yetkiniz yok.']);
            exit;
        }
        
        // 15 dakika sonra düzenleme yapılamaz
        $created_time = strtotime($comment['created_at']);
        $current_time = time();
        if (($current_time - $created_time) > 900) { // 15 dakika = 900 saniye
            echo json_encode(['success' => false, 'message' => 'Yorum 15 dakika sonra düzenlenemez.']);
            exit;
        }
        
        // Yorumu güncelle
        $query = "UPDATE comments SET comment = '$comment_text', updated_at = NOW() WHERE id = $comment_id";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Yorum başarıyla güncellendi.',
                'comment' => htmlspecialchars($comment_text)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Yorum güncellenirken hata oluştu.']);
        }
        break;
        
    case 'load':
        $content_id = (int)($_GET['content_id'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        // Yorumları getir (sadece ana yorumlar, yanıtlar ayrı)
        $query = "SELECT c.*, u.username, u.avatar,
                  (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as reply_count
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.content_id = $content_id AND c.parent_id IS NULL
                  ORDER BY c.created_at DESC 
                  LIMIT $per_page OFFSET $offset";
        $result = mysqli_query($conn, $query);
        
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = [
                'id' => $row['id'],
                'comment' => htmlspecialchars($row['comment']),
                'username' => htmlspecialchars($row['username']),
                'avatar' => $row['avatar'],
                'created_at' => timeAgo($row['created_at']),
                'reply_count' => $row['reply_count'],
                'can_edit' => ($row['user_id'] == $user_id && (time() - strtotime($row['created_at'])) < 900),
                'can_delete' => ($row['user_id'] == $user_id || $_SESSION['role'] === 'admin')
            ];
        }
        
        // Toplam yorum sayısı
        $query = "SELECT COUNT(*) as total FROM comments WHERE content_id = $content_id AND parent_id IS NULL";
        $result = mysqli_query($conn, $query);
        $total = mysqli_fetch_assoc($result)['total'];
        
        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'total' => $total,
            'has_more' => ($offset + $per_page) < $total
        ]);
        break;
        
    case 'load_replies':
        $parent_id = (int)($_GET['parent_id'] ?? 0);
        
        // Yanıtları getir
        $query = "SELECT c.*, u.username, u.avatar
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.parent_id = $parent_id
                  ORDER BY c.created_at ASC";
        $result = mysqli_query($conn, $query);
        
        $replies = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $replies[] = [
                'id' => $row['id'],
                'comment' => htmlspecialchars($row['comment']),
                'username' => htmlspecialchars($row['username']),
                'avatar' => $row['avatar'],
                'created_at' => timeAgo($row['created_at']),
                'can_edit' => ($row['user_id'] == $user_id && (time() - strtotime($row['created_at'])) < 900),
                'can_delete' => ($row['user_id'] == $user_id || $_SESSION['role'] === 'admin')
            ];
        }
        
        echo json_encode([
            'success' => true,
            'replies' => $replies
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
}
?> 