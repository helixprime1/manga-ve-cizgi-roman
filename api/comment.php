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
        $content_check_stmt = $conn->prepare("SELECT id FROM content WHERE id = ? AND status = 'published'");
        $content_check_stmt->bind_param('i', $content_id);
        $content_check_stmt->execute();
        $content_check = $content_check_stmt->get_result();
        if ($content_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı.']);
            exit;
        }

        // Eğer parent_id varsa, parent yorum var mı kontrol et
        if ($parent_id > 0) {
            $parent_check_stmt = $conn->prepare("SELECT id FROM comments WHERE id = ? AND content_id = ?");
            $parent_check_stmt->bind_param('ii', $parent_id, $content_id);
            $parent_check_stmt->execute();
            $parent_check = $parent_check_stmt->get_result();
            if ($parent_check->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Yanıtlanan yorum bulunamadı.']);
                exit;
            }
        }

        // Spam kontrolü (son 1 dakikada aynı kullanıcıdan 3'ten fazla yorum)
        $spam_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $spam_check_stmt->bind_param('i', $user_id);
        $spam_check_stmt->execute();
        $row = $spam_check_stmt->get_result()->fetch_assoc();
        if ($row['count'] >= 3) {
            echo json_encode(['success' => false, 'message' => 'Çok hızlı yorum yapıyorsunuz. Lütfen bekleyin.']);
            exit;
        }

        // Yorumu ekle
        $insert_comment_stmt = $conn->prepare("INSERT INTO comments (content_id, user_id, parent_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $parent_param = $parent_id > 0 ? $parent_id : null;
        $insert_comment_stmt->bind_param('iiis', $content_id, $user_id, $parent_param, $comment_text);

        if ($insert_comment_stmt->execute()) {
            $comment_id = $insert_comment_stmt->insert_id;

            // Bildirim oluştur (içerik sahibine)
            $content_owner_stmt = $conn->prepare("SELECT user_id FROM content WHERE id = ?");
            $content_owner_stmt->bind_param('i', $content_id);
            $content_owner_stmt->execute();
            $content_owner = $content_owner_stmt->get_result()->fetch_assoc();

            if ($content_owner['user_id'] != $user_id) {
                $username = $_SESSION['username'];
                $notification_message = "$username içeriğinize yorum yaptı: " . mb_substr($comment_text, 0, 50) . "...";

                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, 'comment', ?, ?, ?, NOW())");
                $new_comment_title = 'Yeni Yorum';
                $new_comment_link = "view.php?id=$content_id#comment-$comment_id";
                $notification_stmt->bind_param('isss', $content_owner['user_id'], $new_comment_title, $notification_message, $new_comment_link);
                $notification_stmt->execute();
            }

            // Eğer bir yoruma yanıt ise, o yorumun sahibine bildirim gönder
            if ($parent_id > 0) {
                $parent_owner_stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
                $parent_owner_stmt->bind_param('i', $parent_id);
                $parent_owner_stmt->execute();
                $parent_owner = $parent_owner_stmt->get_result()->fetch_assoc();

                if ($parent_owner['user_id'] != $user_id && $parent_owner['user_id'] != $content_owner['user_id']) {
                    $username = $_SESSION['username'];
                    $notification_message = "$username yorumunuza yanıt verdi: " . mb_substr($comment_text, 0, 50) . "...";

                    $reply_notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, 'comment', ?, ?, ?, NOW())");
                    $reply_title = 'Yoruma Yanıt';
                    $reply_link = "view.php?id=$content_id#comment-$comment_id";
                    $reply_notification_stmt->bind_param('isss', $parent_owner['user_id'], $reply_title, $notification_message, $reply_link);
                    $reply_notification_stmt->execute();
                }
            }

            // Yorum bilgilerini getir
            $comment_stmt = $conn->prepare("SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
            $comment_stmt->bind_param('i', $comment_id);
            $comment_stmt->execute();
            $comment = $comment_stmt->get_result()->fetch_assoc();
            
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
        $comment_owner_stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
        $comment_owner_stmt->bind_param('i', $comment_id);
        $comment_owner_stmt->execute();
        $comment = $comment_owner_stmt->get_result()->fetch_assoc();

        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
            exit;
        }
        
        if ($comment['user_id'] != $user_id && $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Bu yorumu silme yetkiniz yok.']);
            exit;
        }

        // Yorumu sil (cascade ile alt yorumlar da silinir)
        $delete_stmt = $conn->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");
        $delete_stmt->bind_param('ii', $comment_id, $comment_id);

        if ($delete_stmt->execute()) {
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
        $comment_edit_stmt = $conn->prepare("SELECT user_id, created_at FROM comments WHERE id = ?");
        $comment_edit_stmt->bind_param('i', $comment_id);
        $comment_edit_stmt->execute();
        $comment = $comment_edit_stmt->get_result()->fetch_assoc();
        
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
        $update_stmt = $conn->prepare("UPDATE comments SET comment = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param('si', $comment_text, $comment_id);

        if ($update_stmt->execute()) {
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
        $comment_page_stmt = $conn->prepare("SELECT c.*, u.username, u.avatar, (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as reply_count FROM comments c JOIN users u ON c.user_id = u.id WHERE c.content_id = ? AND c.parent_id IS NULL ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
        $comment_page_stmt->bind_param('iii', $content_id, $per_page, $offset);
        $comment_page_stmt->execute();
        $result = $comment_page_stmt->get_result();
        
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
        $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM comments WHERE content_id = ? AND parent_id IS NULL");
        $total_stmt->bind_param('i', $content_id);
        $total_stmt->execute();
        $total = $total_stmt->get_result()->fetch_assoc()['total'];
        
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
        $replies_stmt = $conn->prepare("SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.parent_id = ? ORDER BY c.created_at ASC");
        $replies_stmt->bind_param('i', $parent_id);
        $replies_stmt->execute();
        $result = $replies_stmt->get_result();
        
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