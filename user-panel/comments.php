<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Yorumlar';

require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Toplam yorum sayısı (kullanıcının içeriklerine yapılan yorumlar)
$total_query = "SELECT COUNT(*) as total 
                FROM comments c 
                INNER JOIN content ct ON c.content_id = ct.id 
                WHERE ct.user_id = $user_id";
$total_result = mysqli_query($conn, $total_query);
$total_comments = mysqli_fetch_assoc($total_result)['total'];

// Sayfalama
$items_per_page = 20;
$pagination = paginate($total_comments, $items_per_page, $page);

// Yorumları getir
$comments_query = "SELECT c.*, ct.title as content_title, ct.id as content_id, u.username as commenter_name 
                   FROM comments c 
                   INNER JOIN content ct ON c.content_id = ct.id 
                   INNER JOIN users u ON c.user_id = u.id 
                   WHERE ct.user_id = $user_id 
                   ORDER BY c.created_at DESC 
                   LIMIT {$pagination['start']}, {$pagination['per_page']}";
$comments_result = mysqli_query($conn, $comments_query);

$comments = [];
while ($row = mysqli_fetch_assoc($comments_result)) {
    $comments[] = $row;
}

// Yorum silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $comment_id = (int)$_GET['delete'];
    
    // Yorumun kullanıcının içeriğine ait olduğunu kontrol et
    $check_query = "SELECT c.id 
                    FROM comments c 
                    INNER JOIN content ct ON c.content_id = ct.id 
                    WHERE c.id = $comment_id AND ct.user_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $delete_query = "DELETE FROM comments WHERE id = $comment_id";
        if (mysqli_query($conn, $delete_query)) {
            $success_message = "Yorum başarıyla silindi.";
        } else {
            $error_message = "Yorum silinirken hata oluştu.";
        }
        
        header('Location: comments.php');
        exit;
    }
}
?>

<div class="comments-management">
    <!-- Sayfa Başlığı -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>💬 Yorumlar</h2>
            <p class="text-muted mb-0">İçeriklerinize yapılan toplam <?php echo $total_comments; ?> yorum</p>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Yorumlar Listesi -->
    <?php if (count($comments) > 0): ?>
        <div class="card">
            <div class="card-body">
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-info">
                                    <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                    <span class="text-muted">•</span>
                                    <span class="text-muted"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <div class="comment-actions">
                                                            <a href="../view.php?id=<?php echo $comment['content_id']; ?>" 
                           class="btn btn-sm btn-outline-primary" target="_blank">
                            🔗 İçeriği Gör
                        </a>
                                    <a href="?delete=<?php echo $comment['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       data-confirm="Bu yorumu silmek istediğinizden emin misiniz?">
                                        🗑️ Sil
                                    </a>
                                </div>
                            </div>
                            
                            <div class="comment-content">
                                <div class="content-title">
                                    📖
                                    <strong><?php echo htmlspecialchars($comment['content_title']); ?></strong>
                                </div>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Sayfalama" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Önceki">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Sonraki">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <div style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.6;">💬</div>
            <h4 class="text-muted">Henüz yorum yok</h4>
            <p class="text-muted mb-4">İçeriklerinize henüz yorum yapılmamış.</p>
            <a href="../upload.php" class="btn btn-primary">
                ➕ İçerik Yükle
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.comment-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.comment-item:hover {
    border-color: #d0d7de;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.comment-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.comment-actions {
    display: flex;
    gap: 0.5rem;
}

.content-title {
    color: var(--primary-color);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.comment-text {
    color: var(--dark-color);
    line-height: 1.6;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

@media (max-width: 768px) {
    .comment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .comment-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 