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
$page_title = 'Moderasyon Logları';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Filtreler
$action_filter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'all';
$moderator_filter = isset($_GET['moderator']) ? (int)$_GET['moderator'] : 0;
$target_type_filter = isset($_GET['target_type']) ? sanitizeInput($_GET['target_type']) : 'all';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : 'all';

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// WHERE koşulu oluştur
$where_clauses = [];

if ($action_filter !== 'all') {
    $where_clauses[] = "ml.action = '$action_filter'";
}

if ($moderator_filter > 0) {
    $where_clauses[] = "ml.moderator_id = $moderator_filter";
}

if ($target_type_filter !== 'all') {
    $where_clauses[] = "ml.target_type = '$target_type_filter'";
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_clauses[] = "DATE(ml.created_at) = CURDATE()";
            break;
        case 'week':
            $where_clauses[] = "ml.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_clauses[] = "ml.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = !empty($where_clauses) ? implode(' AND ', $where_clauses) : '1=1';

// Toplam log sayısı
$count_query = "SELECT COUNT(*) as total FROM moderation_logs ml WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];

// Sayfalama
$items_per_page = 25;
$pagination = paginate($total_items, $items_per_page, $page);

// Logları getir
$query = "SELECT ml.*, u.username as moderator_name
          FROM moderation_logs ml 
          JOIN users u ON ml.moderator_id = u.id 
          WHERE $where_clause 
          ORDER BY ml.created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

// Moderatörleri getir (filtre için)
$moderators_query = "SELECT DISTINCT u.id, u.username 
                     FROM users u 
                     JOIN moderation_logs ml ON u.id = ml.moderator_id 
                     WHERE u.role IN ('admin', 'moderator') 
                     ORDER BY u.username";
$moderators_result = mysqli_query($conn, $moderators_query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
        <!-- Başlık ve Filtreler -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Moderasyon Logları</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="exportLogs()">
                    <i class="fas fa-download me-1"></i>Dışa Aktar
                </button>
                <button class="btn btn-outline-info" onclick="refreshLogs()">
                    <i class="fas fa-sync me-1"></i>Yenile
                </button>
            </div>
        </div>

        <!-- Filtre Formu -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Eylem</label>
                        <select name="action" class="form-select">
                            <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                            <option value="approve" <?php echo $action_filter === 'approve' ? 'selected' : ''; ?>>Onay</option>
                            <option value="reject" <?php echo $action_filter === 'reject' ? 'selected' : ''; ?>>Reddet</option>
                            <option value="delete" <?php echo $action_filter === 'delete' ? 'selected' : ''; ?>>Sil</option>
                            <option value="hide" <?php echo $action_filter === 'hide' ? 'selected' : ''; ?>>Gizle</option>
                            <option value="ban" <?php echo $action_filter === 'ban' ? 'selected' : ''; ?>>Ban</option>
                            <option value="unban" <?php echo $action_filter === 'unban' ? 'selected' : ''; ?>>Ban Kaldır</option>
                            <option value="warn" <?php echo $action_filter === 'warn' ? 'selected' : ''; ?>>Uyarı</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Moderatör</label>
                        <select name="moderator" class="form-select">
                            <option value="0">Tümü</option>
                            <?php while ($mod = mysqli_fetch_assoc($moderators_result)): ?>
                                <option value="<?php echo $mod['id']; ?>" 
                                        <?php echo $moderator_filter == $mod['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Hedef Türü</label>
                        <select name="target_type" class="form-select">
                            <option value="all" <?php echo $target_type_filter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                            <option value="content" <?php echo $target_type_filter === 'content' ? 'selected' : ''; ?>>İçerik</option>
                            <option value="comment" <?php echo $target_type_filter === 'comment' ? 'selected' : ''; ?>>Yorum</option>
                            <option value="user" <?php echo $target_type_filter === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                            <option value="report" <?php echo $target_type_filter === 'report' ? 'selected' : ''; ?>>Rapor</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Tarih</label>
                        <select name="date" class="form-select">
                            <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>Filtrele
                        </button>
                        <a href="logs.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Temizle
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Log Listesi -->
        <div class="card">
            <div class="card-body">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tarih/Saat</th>
                                    <th>Moderatör</th>
                                    <th>Eylem</th>
                                    <th>Hedef</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($log = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($log['moderator_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $log['action'] === 'approve' ? 'success' : 
                                                    ($log['action'] === 'reject' ? 'danger' : 
                                                    ($log['action'] === 'delete' ? 'warning' : 
                                                    ($log['action'] === 'ban' ? 'dark' : 'secondary'))); 
                                            ?>">
                                                <?php 
                                                switch($log['action']) {
                                                    case 'approve': echo 'Onaylandı'; break;
                                                    case 'reject': echo 'Reddedildi'; break;
                                                    case 'delete': echo 'Silindi'; break;
                                                    case 'hide': echo 'Gizlendi'; break;
                                                    case 'ban': echo 'Banlandı'; break;
                                                    case 'unban': echo 'Ban Kaldırıldı'; break;
                                                    case 'warn': echo 'Uyarıldı'; break;
                                                    default: echo ucfirst($log['action']); break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php 
                                                switch($log['target_type']) {
                                                    case 'content': echo 'İçerik'; break;
                                                    case 'comment': echo 'Yorum'; break;
                                                    case 'user': echo 'Kullanıcı'; break;
                                                    case 'report': echo 'Rapor'; break;
                                                    default: echo ucfirst($log['target_type']); break;
                                                }
                                                ?>
                                                #<?php echo $log['target_id']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($log['description']); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Sayfalama -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav aria-label="Sayfalama" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Önceki</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Sonraki</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-muted text-center">
                        <small>Toplam <?php echo $total_items; ?> log kaydı bulundu</small>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Log kaydı bulunamadı</h5>
                        <p class="text-muted">Seçilen kriterlere uygun log kaydı bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportLogs() {
    // Mevcut filtrelerle CSV export
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('api/export_logs.php?' + params.toString(), '_blank');
}

function refreshLogs() {
    location.reload();
}

// Auto refresh her 30 saniyede bir
setInterval(function() {
    // Sadece ilk sayfadaysa refresh yap
    if (window.location.search.indexOf('page=') === -1 || window.location.search.indexOf('page=1') !== -1) {
        const currentTime = new Date().getTime();
        const lastRefresh = localStorage.getItem('lastLogRefresh') || 0;
        
        // Son refresh'ten 30 saniye geçmişse
        if (currentTime - lastRefresh > 30000) {
            localStorage.setItem('lastLogRefresh', currentTime);
            
            // Sessizce yeni logları kontrol et
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    // Yeni log varsa sayfayı yenile
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newCount = doc.querySelector('.text-muted small').textContent;
                    const currentCount = document.querySelector('.text-muted small').textContent;
                    
                    if (newCount !== currentCount) {
                        location.reload();
                    }
                })
                .catch(error => console.log('Log refresh error:', error));
        }
    }
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?> 