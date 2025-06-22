<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Otomatik olarak is_series değerlerini güncelle
updateSeriesStatus($conn);

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$admin_user = getCurrentUser();
$page_title = 'İçerik Yönetimi';

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

$chapters_query = "SELECT COUNT(*) as total FROM chapters";
$chapters_result = mysqli_query($conn, $chapters_query);
$stats['total_chapters'] = $chapters_result ? mysqli_fetch_assoc($chapters_result)['total'] : 0;

$series_query = "SELECT COUNT(*) as total FROM content WHERE is_series = 1";
$series_result = mysqli_query($conn, $series_query);
$stats['total_series'] = $series_result ? mysqli_fetch_assoc($series_result)['total'] : 0;

// İçerik işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $content_id = (int)($_POST['content_id'] ?? 0);
    
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $message = 'Güvenlik hatası. Lütfen tekrar deneyin.';
        $message_type = 'danger';
    } elseif ($action === 'delete_content' && $content_id > 0) {
        // İçeriği ve ilgili dosyaları sil
        $content_info = getContentById($content_id);
        if ($content_info) {
            // Eğer seri ise bölüm dosyalarını da sil
            if ($content_info['is_series']) {
                $stmt = mysqli_prepare($conn, "SELECT chapter_files FROM chapters WHERE content_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $content_id);
                mysqli_stmt_execute($stmt);
                $chapters_result = mysqli_stmt_get_result($stmt);
                
                while ($chapter = mysqli_fetch_assoc($chapters_result)) {
                    $chapter_files = json_decode($chapter['chapter_files'], true);
                    if (is_array($chapter_files)) {
                        foreach ($chapter_files as $file) {
                            if (file_exists('../uploads/content/' . $file)) {
                                unlink('../uploads/content/' . $file);
                            }
                        }
                    }
                }
                mysqli_stmt_close($stmt);
                
                // Bölümleri sil
                $delete_stmt = mysqli_prepare($conn, "DELETE FROM chapters WHERE content_id = ?");
                mysqli_stmt_bind_param($delete_stmt, "i", $content_id);
                mysqli_stmt_execute($delete_stmt);
                mysqli_stmt_close($delete_stmt);
            } else {
                // Tek eser dosyalarını sil
                $content_files = json_decode($content_info['content_file'], true);
                if (is_array($content_files)) {
                    foreach ($content_files as $file) {
                        if (file_exists('../uploads/content/' . $file)) {
                            unlink('../uploads/content/' . $file);
                        }
                    }
                } else {
                    if (file_exists('../uploads/content/' . $content_info['content_file'])) {
                        unlink('../uploads/content/' . $content_info['content_file']);
                    }
                }
            }
            
            // Kapak resmini sil
            if (file_exists('../uploads/covers/' . $content_info['cover_image'])) {
                unlink('../uploads/covers/' . $content_info['cover_image']);
            }
            
            // İlgili yorumları sil
            $comments_stmt = mysqli_prepare($conn, "DELETE FROM comments WHERE content_id = ?");
            mysqli_stmt_bind_param($comments_stmt, "i", $content_id);
            mysqli_stmt_execute($comments_stmt);
            mysqli_stmt_close($comments_stmt);
            
            // İlgili beğenileri sil
            $likes_stmt = mysqli_prepare($conn, "DELETE FROM likes WHERE content_id = ?");
            mysqli_stmt_bind_param($likes_stmt, "i", $content_id);
            mysqli_stmt_execute($likes_stmt);
            mysqli_stmt_close($likes_stmt);
            
            // İlgili favorileri sil
            $favorites_stmt = mysqli_prepare($conn, "DELETE FROM favorites WHERE content_id = ?");
            mysqli_stmt_bind_param($favorites_stmt, "i", $content_id);
            mysqli_stmt_execute($favorites_stmt);
            mysqli_stmt_close($favorites_stmt);
            
            // İçeriği sil
            $content_stmt = mysqli_prepare($conn, "DELETE FROM content WHERE id = ?");
            mysqli_stmt_bind_param($content_stmt, "i", $content_id);
            if (mysqli_stmt_execute($content_stmt)) {
                $message = 'İçerik ve tüm bölümleri başarıyla silindi.';
                $message_type = 'success';
            } else {
                $message = 'İçerik silinirken bir hata oluştu.';
                $message_type = 'danger';
            }
            mysqli_stmt_close($content_stmt);
        }
    } elseif ($action === 'toggle_status' && $content_id > 0) {
        $content_info = getContentById($content_id);
        $new_status = $content_info['status'] === 'published' ? 'pending' : 'published';
        
        $status_stmt = mysqli_prepare($conn, "UPDATE content SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($status_stmt, "si", $new_status, $content_id);
        if (mysqli_stmt_execute($status_stmt)) {
            $message = 'İçerik durumu başarıyla güncellendi.';
            $message_type = 'success';
        } else {
            $message = 'Durum güncellenirken bir hata oluştu.';
            $message_type = 'danger';
        }
        mysqli_stmt_close($status_stmt);
    }
}

// İçerikleri getir
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$series_filter = $_GET['series'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
if (!empty($search)) {
    $search_safe = sanitizeInput($search);
    $where_conditions[] = "(title LIKE '%$search_safe%' OR description LIKE '%$search_safe%' OR tags LIKE '%$search_safe%')";
}

if (!empty($status_filter)) {
    $status_safe = sanitizeInput($status_filter);
    $where_conditions[] = "status = '$status_safe'";
}

if (!empty($type_filter)) {
    $type_safe = sanitizeInput($type_filter);
    $where_conditions[] = "type = '$type_safe'";
}

if ($series_filter !== '') {
    $series_safe = (int)$series_filter;
    $where_conditions[] = "is_series = $series_safe";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$order_clause = "ORDER BY $sort $order";

$content_query = "
    SELECT c.*, u.username, u.email,
           (SELECT COUNT(*) FROM comments WHERE content_id = c.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE content_id = c.id) as like_count,
           (SELECT COUNT(*) FROM chapters WHERE content_id = c.id) as chapter_count
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    $where_clause 
    $order_clause
";

$content_result = mysqli_query($conn, $content_query);

require_once 'includes/header.php';
?>

<style>
/* Kompakt tablo stilleri */
.table-compact {
    font-size: 0.875rem;
}

.table-compact th,
.table-compact td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

.table-compact .btn {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
}

.table-compact .badge {
    font-size: 0.65rem;
    padding: 0.25rem 0.4rem;
}

.content-title {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.content-author {
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-group-compact .btn {
    padding: 0.15rem 0.3rem;
    margin: 0 1px;
}

@media (max-width: 1200px) {
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .content-title {
        max-width: 150px;
    }
    
    .content-author {
        max-width: 100px;
    }
}

.filter-compact .col-md-3,
.filter-compact .col-md-2 {
    margin-bottom: 0.5rem;
}

.filter-compact .form-label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.filter-compact .form-control,
.filter-compact .form-select {
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h4 mb-0">İçerik Yönetimi</h1>
                            <p class="text-muted small mb-0">Tüm içerikleri görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <span class="badge bg-primary">
                                <i class="fas fa-book me-1"></i>
                                <?php echo mysqli_num_rows($content_result); ?> İçerik
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

            <!-- Kompakt Filtreler -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 filter-compact">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Arama</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                   placeholder="Başlık, açıklama, etiket..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Durum</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="">Tümü</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Yayında</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Bekliyor</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Red</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Tür</label>
                            <select class="form-select form-select-sm" id="type" name="type">
                                <option value="">Tümü</option>
                                <option value="manga" <?php echo $type_filter === 'manga' ? 'selected' : ''; ?>>Manga</option>
                                <option value="comic" <?php echo $type_filter === 'comic' ? 'selected' : ''; ?>>Comic</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="series" class="form-label">İçerik</label>
                            <select class="form-select form-select-sm" id="series" name="series">
                                <option value="">Tümü</option>
                                <option value="1" <?php echo ($_GET['series'] ?? '') === '1' ? 'selected' : ''; ?>>Seri</option>
                                <option value="0" <?php echo ($_GET['series'] ?? '') === '0' ? 'selected' : ''; ?>>Tek</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sıralama</label>
                            <select class="form-select form-select-sm" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Tarih</option>
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Başlık</option>
                                <option value="views" <?php echo $sort === 'views' ? 'selected' : ''; ?>>Görüntülenme</option>
                                <option value="like_count" <?php echo $sort === 'like_count' ? 'selected' : ''; ?>>Beğeni</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kompakt İçerik Tablosu -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-book me-2"></i>İçerikler
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-compact">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 60px;">Kapak</th>
                                    <th style="width: 250px;">Başlık & Yazar</th>
                                    <th style="width: 80px;">Tür</th>
                                    <th style="width: 100px;">Durum</th>
                                    <th style="width: 80px;">İstatistik</th>
                                    <th style="width: 80px;">Tarih</th>
                                    <th style="width: 120px;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($content_result) > 0): ?>
                                    <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">#<?php echo $content['id']; ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $cover_image_path = '../uploads/covers/' . $content['cover_image'];
                                                if (file_exists($cover_image_path)): ?>
                                                    <img src="<?php echo htmlspecialchars($cover_image_path); ?>" 
                                                         class="rounded" style="width: 35px; height: 45px; object-fit: cover;"
                                                         alt="<?php echo htmlspecialchars($content['title']); ?>">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 35px; height: 45px;">
                                                        <i class="fas fa-image text-muted small"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="content-title fw-semibold" title="<?php echo htmlspecialchars($content['title']); ?>">
                                                    <?php echo htmlspecialchars($content['title']); ?>
                                                </div>
                                                <div class="content-author text-muted small" title="<?php echo htmlspecialchars($content['username']); ?>">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($content['username']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                                        <?php echo $content['type'] === 'manga' ? 'Manga' : 'Comic'; ?>
                                                    </span>
                                                    <?php if ($content['is_series']): ?>
                                                        <span class="badge bg-success">
                                                            Seri (<?php echo $content['chapter_count']; ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tek</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $content['status'] === 'published' ? 'bg-success' : 
                                                         ($content['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                ?>">
                                                    <?php 
                                                        echo $content['status'] === 'published' ? 'Yayında' : 
                                                             ($content['status'] === 'pending' ? 'Bekliyor' : 'Red'); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <small><i class="fas fa-eye me-1"></i><?php echo number_format($content['views']); ?></small>
                                                    <small><i class="fas fa-heart me-1"></i><?php echo number_format($content['like_count']); ?></small>
                                                    <small><i class="fas fa-comment me-1"></i><?php echo number_format($content['comment_count']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo date('d.m.y', strtotime($content['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-compact d-flex flex-wrap gap-1">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="window.open('../view.php?id=<?php echo $content['id']; ?>', '_blank')"
                                                            title="Görüntüle">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                                                        <?php if ($content['is_series']): ?>
                                        <a href="chapter-details.php?content_id=<?php echo $content['id']; ?>" 
                                           class="btn btn-outline-success btn-sm" title="Bölümler">
                                            <i class="fas fa-book-open"></i>
                                        </a>
                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Durum değiştirilsin mi?')">
                                                        <?php echo getCSRFTokenInput(); ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-warning btn-sm" title="Durum">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Silmek istediğinizden emin misiniz?')">
                                                        <?php echo getCSRFTokenInput(); ?>
                                                        <input type="hidden" name="action" value="delete_content">
                                                        <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-book fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">İçerik bulunamadı</p>
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



<script>
function confirmAction(message) {
    return confirm(message);
}
</script>

<?php require_once 'includes/footer.php'; ?>