<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    header('Location: ../login.php');
    exit;
}

$moderator_user = getCurrentUser();
$page_title = 'Yorum Yönetimi';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Filtreler
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
$allowed_filters = ['all', 'reported', 'recent'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'all';
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Yorum işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $comment_id = (int)$_POST['comment_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'delete', 'hide'])) {
        if ($action === 'delete') {
            // Yorumu sil
            $stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $comment_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $success_message = 'Yorum başarıyla silindi.';
            } else {
                $error_message = 'Yorum silinirken hata oluştu.';
            }
        } elseif ($action === 'approve') {
            // Bildirimi kaldır
            $stmt = mysqli_prepare($conn, "UPDATE comments SET is_reported = 0, moderated_by = ?, moderated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $moderator_user['id'], $comment_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $success_message = 'Yorum onaylandı, bildirim kaldırıldı.';
            } else {
                $error_message = 'İşlem sırasında hata oluştu.';
            }
        } elseif ($action === 'hide') {
            // Yorumu gizle
            $stmt = mysqli_prepare($conn, "UPDATE comments SET is_hidden = 1, moderated_by = ?, moderated_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $moderator_user['id'], $comment_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $success_message = 'Yorum gizlendi.';
            } else {
                $error_message = 'İşlem sırasında hata oluştu.';
            }
        }
        
        // Log kaydı
        if (isset($success) && $success) {
            $log_message = "Yorum #$comment_id $action işlemi yapıldı";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, description, created_at) VALUES (?, ?, 'comment', ?, ?, NOW())");
            mysqli_stmt_bind_param($log_stmt, "isss", $moderator_user['id'], $action, $comment_id, $log_message);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
}

// WHERE koşulu oluştur
$where_clause = "1=1";
if ($filter === 'reported') {
    $where_clause .= " AND c.is_reported = 1";
} elseif ($filter === 'recent') {
    $where_clause .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

// Toplam yorum sayısı
$count_query = "SELECT COUNT(*) as total FROM comments c WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];

// Sayfalama
$items_per_page = 20;
$pagination = paginate($total_items, $items_per_page, $page);

// Yorumları getir
$query = "SELECT c.*, u.username, u.email, cnt.title as content_title,
          (SELECT username FROM users WHERE id = c.moderated_by) as moderated_by_username
          FROM comments c 
          JOIN users u ON c.user_id = u.id 
          JOIN content cnt ON c.content_id = cnt.id
          WHERE $where_clause 
          ORDER BY c.created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
                <!-- Başlık ve Filtreler -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Yorum Yönetimi</h4>
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Tümü (<?php echo $total_items; ?>)
                        </a>
                        <a href="?filter=reported" class="btn <?php echo $filter === 'reported' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            Bildirilenler (<?php echo $stats['reported_comments']; ?>)
                        </a>
                        <a href="?filter=recent" class="btn <?php echo $filter === 'recent' ? 'btn-info' : 'btn-outline-info'; ?>">
                            Son 7 Gün
                        </a>
                    </div>
                </div>

                <!-- Başarı/Hata Mesajları -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Yorum Listesi -->
                <div class="card">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($comment = mysqli_fetch_assoc($result)): ?>
                                <div class="card mb-3 <?php echo $comment['is_reported'] ? 'border-danger' : ''; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                            <span class="text-muted ms-2">
                                                                <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                                            </span>
                                                            
                                                            <?php if ($comment['is_reported']): ?>
                                                                <span class="badge bg-danger ms-2">
                                                                    <i class="fas fa-flag"></i> Bildirildi
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($comment['is_hidden']): ?>
                                                                <span class="badge bg-secondary ms-2">
                                                                    <i class="fas fa-eye-slash"></i> Gizli
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                        
                                                        <div class="text-muted small">
                                                            <i class="fas fa-book me-1"></i>
                                                            <strong>İçerik:</strong> 
                                                            <a href="../view.php?id=<?php echo $comment['content_id']; ?>" target="_blank">
                                                                <?php echo htmlspecialchars($comment['content_title']); ?>
                                                            </a>
                                                        </div>
                                                        
                                                        <?php if ($comment['moderated_at']): ?>
                                                            <div class="text-muted small mt-1">
                                                                <i class="fas fa-shield-alt me-1"></i>
                                                                <strong>Moderasyon:</strong> 
                                                                <?php echo date('d.m.Y H:i', strtotime($comment['moderated_at'])); ?>
                                                                <?php if ($comment['moderated_by_username']): ?>
                                                                    - <?php echo htmlspecialchars($comment['moderated_by_username']); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <?php if ($comment['is_reported']): ?>
                                                        <button class="btn btn-outline-success btn-sm" 
                                                                onclick="moderateComment(<?php echo $comment['id']; ?>, 'approve')">
                                                            <i class="fas fa-check"></i> Onayla
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$comment['is_hidden']): ?>
                                                        <button class="btn btn-outline-warning btn-sm" 
                                                                onclick="moderateComment(<?php echo $comment['id']; ?>, 'hide')">
                                                            <i class="fas fa-eye-slash"></i> Gizle
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="moderateComment(<?php echo $comment['id']; ?>, 'delete')">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </button>
                                                    
                                                    <a href="../view.php?id=<?php echo $comment['content_id']; ?>#comment-<?php echo $comment['id']; ?>" 
                                                       class="btn btn-outline-info btn-sm" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i> Sitede Gör
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            
                            <!-- Sayfalama -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Sayfalama" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">Önceki</a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Yorum bulunamadı</h5>
                                <p class="text-muted">Seçilen kriterlere uygun yorum bulunmamaktadır.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Moderasyon Modal -->
<div class="modal fade" id="moderationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moderationModalTitle">Yorum Moderasyonu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="moderationForm">
                <div class="modal-body">
                    <input type="hidden" name="comment_id" id="moderationCommentId">
                    <input type="hidden" name="action" id="moderationAction">
                    
                    <div class="alert alert-warning" id="confirmationMessage">
                        <!-- Onay mesajı buraya gelecek -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="moderationSubmitBtn">Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function moderateComment(commentId, action) {
    document.getElementById('moderationCommentId').value = commentId;
    document.getElementById('moderationAction').value = action;
    
    const modal = document.getElementById('moderationModal');
    const title = document.getElementById('moderationModalTitle');
    const submitBtn = document.getElementById('moderationSubmitBtn');
    
    if (action === 'approve') {
        title.textContent = 'Yorumu Onayla';
        submitBtn.textContent = 'Onayla';
        submitBtn.className = 'btn btn-success';
    } else if (action === 'hide') {
        title.textContent = 'Yorumu Gizle';
        submitBtn.textContent = 'Gizle';
        submitBtn.className = 'btn btn-warning';
    } else {
        title.textContent = 'Yorumu Sil';
        submitBtn.textContent = 'Sil';
        submitBtn.className = 'btn btn-danger';
    }
    
    new bootstrap.Modal(modal).show();
}

function viewComment(commentId) {
    // İçerik detaylarını göster
    alert('Yorum detayları: #' + commentId);
}
</script>

<?php require_once 'includes/footer.php'; ?> 