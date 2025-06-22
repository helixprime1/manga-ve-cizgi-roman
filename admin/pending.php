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
$page_title = 'Bekleyen İçerikler';

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

// İçerik işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $content_id = (int)($_POST['content_id'] ?? 0);
    
    if ($action === 'approve' && $content_id > 0) {
        $update_query = "UPDATE content SET status = 'published' WHERE id = $content_id";
        if (mysqli_query($conn, $update_query)) {
            $message = 'İçerik başarıyla onaylandı.';
            $message_type = 'success';
        } else {
            $message = 'İçerik onaylanırken bir hata oluştu.';
            $message_type = 'danger';
        }
    } elseif ($action === 'reject' && $content_id > 0) {
        $reject_reason = sanitizeInput($_POST['reject_reason'] ?? '');
        $update_query = "UPDATE content SET status = 'rejected', reject_reason = '$reject_reason' WHERE id = $content_id";
        if (mysqli_query($conn, $update_query)) {
            $message = 'İçerik reddedildi.';
            $message_type = 'warning';
        } else {
            $message = 'İçerik reddedilirken bir hata oluştu.';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete' && $content_id > 0) {
        // İçeriği ve ilgili dosyaları sil
        $content_info = getContentById($content_id);
        if ($content_info) {
            // Dosyaları sil
            if (file_exists('../uploads/content/' . $content_info['content_file'])) {
                unlink('../uploads/content/' . $content_info['content_file']);
            }
            if (file_exists('../uploads/covers/' . $content_info['cover_image'])) {
                unlink('../uploads/covers/' . $content_info['cover_image']);
            }
            
            // Veritabanından sil
            $delete_query = "DELETE FROM content WHERE id = $content_id";
            if (mysqli_query($conn, $delete_query)) {
                $message = 'İçerik başarıyla silindi.';
                $message_type = 'success';
            } else {
                $message = 'İçerik silinirken bir hata oluştu.';
                $message_type = 'danger';
            }
        }
    }
}

// Bekleyen içerikleri getir
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = ["status = 'pending'"];
if (!empty($search)) {
    $search_safe = sanitizeInput($search);
    $where_conditions[] = "(title LIKE '%$search_safe%' OR description LIKE '%$search_safe%' OR tags LIKE '%$search_safe%')";
}

if (!empty($type_filter)) {
    $type_safe = sanitizeInput($type_filter);
    $where_conditions[] = "type = '$type_safe'";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
$order_clause = "ORDER BY $sort $order";

$content_query = "
    SELECT c.*, u.username, u.email
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    $where_clause 
    $order_clause
";

$content_result = mysqli_query($conn, $content_query);

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
                            <h1 class="h3 mb-0">Bekleyen İçerikler</h1>
                            <p class="text-muted">Onay bekleyen içerikleri görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <span class="badge bg-warning fs-6">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo mysqli_num_rows($content_result); ?> Bekleyen
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Arama</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Başlık, açıklama veya etiket..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="form-label">Tür</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tüm Türler</option>
                                <option value="manga" <?php echo $type_filter === 'manga' ? 'selected' : ''; ?>>Manga</option>
                                <option value="comic" <?php echo $type_filter === 'comic' ? 'selected' : ''; ?>>Çizgi Roman</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sıralama</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Tarih</option>
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Başlık</option>
                                <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>Görüntülenme</option>
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

            <!-- Content Grid -->
            <?php if (mysqli_num_rows($content_result) > 0): ?>
                <div class="row">
                    <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="position-relative">
                                    <?php 
                                    $cover_image_path = '../uploads/covers/' . $content['cover_image'];
                                    if (file_exists($cover_image_path)): ?>
                                        <img src="<?php echo htmlspecialchars($cover_image_path); ?>" 
                                             class="card-img-top" style="height: 250px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($content['title']); ?>">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="position-absolute top-0 start-0 p-2">
                                        <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                            <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                        </span>
                                    </div>
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Bekliyor
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo mb_substr(htmlspecialchars($content['description']), 0, 100); ?>...
                                    </p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($content['username']); ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar me-1"></i><?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-eye me-1"></i><?php echo number_format($content['views']); ?>
                                        </small>
                                    </div>
                                    
                                    <?php if (!empty($content['tags'])): ?>
                                        <div class="mb-3">
                                            <?php foreach (explode(',', $content['tags']) as $tag): ?>
                                                <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tag)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-white border-0">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button class="btn btn-outline-primary btn-sm w-100" 
                                                    onclick="previewContent(<?php echo $content['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>Önizle
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <div class="dropdown w-100">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog me-1"></i>İşlemler
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-success" 
                                                                    onclick="return confirmAction('Bu içeriği onaylamak istediğinizden emin misiniz?')">
                                                                <i class="fas fa-check me-2"></i>Onayla
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-warning" 
                                                                onclick="showRejectModal(<?php echo $content['id']; ?>)">
                                                            <i class="fas fa-times me-2"></i>Reddet
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                    onclick="return confirmAction('Bu içeriği kalıcı olarak silmek istediğinizden emin misiniz?')">
                                                                <i class="fas fa-trash me-2"></i>Sil
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clock fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">Bekleyen İçerik Yok</h4>
                        <p class="text-muted">Şu anda onay bekleyen içerik bulunmuyor.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İçeriği Reddet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="content_id" id="rejectContentId">
                    
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Red Sebebi</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" 
                                  rows="4" placeholder="İçeriğin neden reddedildiğini açıklayın..." required></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu işlem geri alınamaz. İçerik sahibine red sebebi bildirilecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">İçeriği Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İçerik Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showRejectModal(contentId) {
    document.getElementById('rejectContentId').value = contentId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function previewContent(contentId) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewDiv = document.getElementById('previewContent');
    
    previewDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Yükleniyor...</p></div>';
    modal.show();
    
    fetch(`api/preview_content.php?id=${contentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                previewDiv.innerHTML = data.html;
            } else {
                previewDiv.innerHTML = '<div class="alert alert-danger">İçerik önizlenemiyor.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            previewDiv.innerHTML = '<div class="alert alert-danger">Bir hata oluştu.</div>';
        });
}
</script>

<?php require_once 'includes/footer.php'; ?> 