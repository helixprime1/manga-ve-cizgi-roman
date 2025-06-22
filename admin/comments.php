<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$admin_user = getCurrentUser();
$page_title = 'Yorum Yönetimi';

// İstatistikleri al
$stats = [];
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Yorum işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    
    if ($action === 'delete_comment' && $comment_id > 0) {
        $delete_query = "DELETE FROM comments WHERE id = $comment_id";
        if (mysqli_query($conn, $delete_query)) {
            $message = 'Yorum başarıyla silindi.';
            $message_type = 'success';
        } else {
            $message = 'Yorum silinirken bir hata oluştu.';
            $message_type = 'danger';
        }
    }
}

// Yorumları getir
$search = $_GET['search'] ?? '';
$content_filter = $_GET['content'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
if (!empty($search)) {
    $search_safe = sanitizeInput($search);
    $where_conditions[] = "(cm.comment LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%')";
}

if (!empty($content_filter)) {
    $content_safe = (int)$content_filter;
    $where_conditions[] = "cm.content_id = $content_safe";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$order_clause = "ORDER BY cm.$sort $order";

$comments_query = "
    SELECT cm.*, u.username, u.email, c.title as content_title
    FROM comments cm
    JOIN users u ON cm.user_id = u.id
    JOIN content c ON cm.content_id = c.id
    $where_clause 
    $order_clause
";

$comments_result = mysqli_query($conn, $comments_query);

// İçerik listesi (filtre için)
$content_list_query = "SELECT id, title FROM content ORDER BY title";
$content_list = mysqli_query($conn, $content_list_query);

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Yorum Yönetimi</h1>
                            <p class="text-muted">Tüm yorumları görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <span class="badge bg-info fs-6">
                                <i class="fas fa-comments me-1"></i>
                                <?php echo mysqli_num_rows($comments_result); ?> Yorum
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Arama</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Yorum içeriği veya kullanıcı adı..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="content" class="form-label">İçerik</label>
                            <select class="form-select" id="content" name="content">
                                <option value="">Tüm İçerikler</option>
                                <?php while ($content = mysqli_fetch_assoc($content_list)): ?>
                                    <option value="<?php echo $content['id']; ?>" 
                                            <?php echo $content_filter == $content['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($content['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sıralama</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Tarih</option>
                                <option value="comment" <?php echo $sort === 'comment' ? 'selected' : ''; ?>>İçerik</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filtrele
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Comments Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments me-2"></i>Yorumlar
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>İçerik</th>
                                    <th>Yorum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($comments_result) > 0): ?>
                                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                        <tr>
                                            <td><?php echo $comment['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($comment['username']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($comment['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($comment['content_title']); ?></div>
                                                <small class="text-muted">ID: <?php echo $comment['content_id']; ?></small>
                                            </td>
                                            <td>
                                                <div class="comment-text" style="max-width: 300px;">
                                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="window.open('../view.php?id=<?php echo $comment['content_id']; ?>#comment-<?php echo $comment['id']; ?>', '_blank')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Bu yorumu silmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="action" value="delete_comment">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Yorum bulunamadı</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 