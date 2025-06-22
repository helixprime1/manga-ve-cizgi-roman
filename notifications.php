<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    header('Location: login.php?redirect=notifications.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Bildirim işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $notification_id = (int)$_POST['notification_id'];
                $query = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id AND user_id = $user_id";
                mysqli_query($conn, $query);
                break;
                
            case 'mark_all_read':
                $query = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
                mysqli_query($conn, $query);
                break;
                
            case 'delete':
                $notification_id = (int)$_POST['notification_id'];
                $query = "DELETE FROM notifications WHERE id = $notification_id AND user_id = $user_id";
                mysqli_query($conn, $query);
                break;
                
            case 'delete_all':
                $query = "DELETE FROM notifications WHERE user_id = $user_id";
                mysqli_query($conn, $query);
                break;
        }
        
        header('Location: notifications.php');
        exit;
    }
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Filtre parametresi
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
$allowed_filters = ['all', 'unread', 'read'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'all';
}

// WHERE koşulu
$where_clause = "user_id = $user_id";
if ($filter === 'unread') {
    $where_clause .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $where_clause .= " AND is_read = 1";
}

// Toplam bildirim sayısı
$query = "SELECT COUNT(*) as total FROM notifications WHERE $where_clause";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_notifications = $row['total'];

// Okunmamış bildirim sayısı
$query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = $user_id AND is_read = 0";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$unread_count = $row['unread'];

// Sayfalama bilgileri
$items_per_page = 10;
$pagination = paginate($total_notifications, $items_per_page, $page);

// Bildirimleri getir
$query = "SELECT * FROM notifications 
          WHERE $where_clause 
          ORDER BY created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

$page_title = 'Bildirimler';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #667eea, #764ba2);">
    <div class="container">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
                <i class="fas fa-bell me-3"></i>Bildirimler
            </h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                Size özel tüm bildirimlerinizi burada görüntüleyebilirsiniz
            </p>
            <div class="mt-4" data-aos="fade-up" data-aos-delay="200">
                <span class="badge bg-white text-dark fs-6 px-3 py-2 me-3">
                    <i class="fas fa-envelope me-2"></i><?php echo number_format($total_notifications); ?> Toplam
                </span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger fs-6 px-3 py-2">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo number_format($unread_count); ?> Okunmamış
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <!-- Kontrol Paneli -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Filtrele</h6>
                    <div class="btn-group w-100" role="group">
                        <a href="?filter=all" 
                           class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list me-2"></i>Tümü (<?php echo $total_notifications; ?>)
                        </a>
                        <a href="?filter=unread" 
                           class="btn <?php echo $filter === 'unread' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            <i class="fas fa-envelope me-2"></i>Okunmamış (<?php echo $unread_count; ?>)
                        </a>
                        <a href="?filter=read" 
                           class="btn <?php echo $filter === 'read' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-envelope-open me-2"></i>Okunmuş
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">İşlemler</h6>
                    <div class="d-grid gap-2">
                        <?php if ($unread_count > 0): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check-double me-2"></i>Tümünü Okundu İşaretle
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($total_notifications > 0): ?>
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                                <i class="fas fa-trash me-2"></i>Tümünü Sil
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($notifications) > 0): ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="card mb-3 <?php echo $notification['is_read'] ? '' : 'border-warning'; ?>" data-aos="fade-up">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <?php
                                    $icon = 'fas fa-info-circle text-info';
                                    switch ($notification['type']) {
                                        case 'like':
                                            $icon = 'fas fa-heart text-danger';
                                            break;
                                        case 'comment':
                                            $icon = 'fas fa-comment text-primary';
                                            break;
                                        case 'favorite':
                                            $icon = 'fas fa-bookmark text-warning';
                                            break;
                                        case 'follow':
                                            $icon = 'fas fa-user-plus text-success';
                                            break;
                                        case 'system':
                                            $icon = 'fas fa-cog text-secondary';
                                            break;
                                    }
                                    ?>
                                    <i class="<?php echo $icon; ?> me-3"></i>
                                    <div>
                                        <h6 class="mb-1 <?php echo $notification['is_read'] ? 'text-muted' : 'fw-bold'; ?>">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h6>
                                        <p class="mb-2 <?php echo $notification['is_read'] ? 'text-muted' : ''; ?>">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo timeAgo($notification['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if (!$notification['is_read']): ?>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-check me-2"></i>Okundu İşaretle
                                                </button>
                                            </form>
                                        </li>
                                    <?php endif; ?>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash me-2"></i>Sil
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (!empty($notification['link'])): ?>
                            <div class="mt-3">
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>Görüntüle
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Sayfalama" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" aria-label="Önceki">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($pagination['total_pages'], $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" aria-label="Sonraki">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-bell-slash fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">
                <?php if ($filter === 'unread'): ?>
                    Okunmamış bildiriminiz yok
                <?php elseif ($filter === 'read'): ?>
                    Okunmuş bildiriminiz yok
                <?php else: ?>
                    Henüz bildiriminiz yok
                <?php endif; ?>
            </h3>
            <p class="text-muted mb-4">
                Yeni içerikler yüklediğinizde, beğeni ve yorumlar aldığınızda bildirimler burada görünecek.
            </p>
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Tümünü Sil Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAllModalLabel">Tüm Bildirimleri Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Emin misiniz?</h5>
                    <p class="text-muted">Tüm bildirimleriniz kalıcı olarak silinecek. Bu işlem geri alınamaz.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Tümünü Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto refresh için
setInterval(function() {
    // Sayfa yenilemesi yerine AJAX ile yeni bildirimler kontrol edilebilir
}, 30000); // 30 saniyede bir

// Notification click handler
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        if (!this.classList.contains('read')) {
            // AJAX ile okundu işaretle
            this.classList.add('read');
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 