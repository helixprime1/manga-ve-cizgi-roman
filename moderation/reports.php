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
$page_title = 'Rapor Yönetimi';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Filtreler
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'pending';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';

$allowed_statuses = ['all', 'pending', 'reviewed', 'resolved', 'dismissed'];
$allowed_types = ['all', 'content', 'comment', 'user'];

if (!in_array($status_filter, $allowed_statuses)) $status_filter = 'pending';
if (!in_array($type_filter, $allowed_types)) $type_filter = 'all';

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Rapor işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $response = isset($_POST['response']) ? sanitizeInput($_POST['response']) : '';
    
    if (in_array($action, ['reviewed', 'resolved', 'dismissed'])) {
        $stmt = mysqli_prepare($conn, "UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $action, $moderator_user['id'], $report_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log kaydı
            $log_message = "Rapor #$report_id $action olarak işaretlendi";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, description, created_at) VALUES (?, ?, 'report', ?, ?, NOW())");
            mysqli_stmt_bind_param($log_stmt, "isss", $moderator_user['id'], $action, $report_id, $log_message);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            $success_message = 'Rapor başarıyla güncellendi.';
        } else {
            $error_message = 'İşlem sırasında bir hata oluştu.';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// WHERE koşulu oluştur
$where_clauses = [];
if ($status_filter !== 'all') {
    $where_clauses[] = "r.status = '$status_filter'";
}
if ($type_filter !== 'all') {
    $where_clauses[] = "r.target_type = '$type_filter'";
}

$where_clause = !empty($where_clauses) ? implode(' AND ', $where_clauses) : '1=1';

// Toplam rapor sayısı
$count_query = "SELECT COUNT(*) as total FROM reports r WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];

// Sayfalama
$items_per_page = 15;
$pagination = paginate($total_items, $items_per_page, $page);

// Raporları getir
$query = "SELECT r.*, 
          reporter.username as reporter_username,
          reviewer.username as reviewer_username,
          CASE 
            WHEN r.target_type = 'content' THEN (SELECT title FROM content WHERE id = r.target_id)
            WHEN r.target_type = 'comment' THEN (SELECT CONCAT('Yorum: ', LEFT(comment, 50), '...') FROM comments WHERE id = r.target_id)
            WHEN r.target_type = 'user' THEN (SELECT username FROM users WHERE id = r.target_id)
          END as target_title
          FROM reports r 
          JOIN users reporter ON r.reporter_id = reporter.id 
          LEFT JOIN users reviewer ON r.reviewed_by = reviewer.id
          WHERE $where_clause 
          ORDER BY r.created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
                <!-- Başlık ve Filtreler -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Rapor Yönetimi</h4>
                    <div class="d-flex gap-2">
                        <!-- Durum Filtresi -->
                        <div class="btn-group" role="group">
                            <a href="?status=all&type=<?php echo $type_filter; ?>" 
                               class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Tümü
                            </a>
                            <a href="?status=pending&type=<?php echo $type_filter; ?>" 
                               class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                Bekleyen
                            </a>
                            <a href="?status=reviewed&type=<?php echo $type_filter; ?>" 
                               class="btn <?php echo $status_filter === 'reviewed' ? 'btn-info' : 'btn-outline-info'; ?>">
                                İncelenen
                            </a>
                            <a href="?status=resolved&type=<?php echo $type_filter; ?>" 
                               class="btn <?php echo $status_filter === 'resolved' ? 'btn-success' : 'btn-outline-success'; ?>">
                                Çözülen
                            </a>
                            <a href="?status=dismissed&type=<?php echo $type_filter; ?>" 
                               class="btn <?php echo $status_filter === 'dismissed' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                                Reddedilen
                            </a>
                        </div>
                        
                        <!-- Tür Filtresi -->
                        <div class="btn-group" role="group">
                            <a href="?status=<?php echo $status_filter; ?>&type=all" 
                               class="btn <?php echo $type_filter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                                Tüm Türler
                            </a>
                            <a href="?status=<?php echo $status_filter; ?>&type=content" 
                               class="btn <?php echo $type_filter === 'content' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                                İçerik
                            </a>
                            <a href="?status=<?php echo $status_filter; ?>&type=comment" 
                               class="btn <?php echo $type_filter === 'comment' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                                Yorum
                            </a>
                            <a href="?status=<?php echo $status_filter; ?>&type=user" 
                               class="btn <?php echo $type_filter === 'user' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                                Kullanıcı
                            </a>
                        </div>
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

                <!-- Rapor Listesi -->
                <div class="card">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($report = mysqli_fetch_assoc($result)): ?>
                                <div class="card mb-3 <?php echo $report['status'] === 'pending' ? 'border-warning' : ''; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-start">
                                                    <div class="me-3">
                                                        <?php
                                                        $icon = '';
                                                        $color = '';
                                                        switch($report['target_type']) {
                                                            case 'content':
                                                                $icon = 'fas fa-book';
                                                                $color = 'text-primary';
                                                                break;
                                                            case 'comment':
                                                                $icon = 'fas fa-comment';
                                                                $color = 'text-info';
                                                                break;
                                                            case 'user':
                                                                $icon = 'fas fa-user';
                                                                $color = 'text-secondary';
                                                                break;
                                                        }
                                                        ?>
                                                        <i class="<?php echo $icon . ' ' . $color; ?> fa-2x"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h6 class="mb-0">
                                                                <?php echo ucfirst($report['target_type']); ?> Raporu
                                                            </h6>
                                                            <span class="badge bg-<?php 
                                                                echo $report['status'] === 'pending' ? 'warning' : 
                                                                    ($report['status'] === 'resolved' ? 'success' : 
                                                                    ($report['status'] === 'dismissed' ? 'secondary' : 'info')); 
                                                            ?> ms-2">
                                                                <?php 
                                                                switch($report['status']) {
                                                                    case 'pending': echo 'Bekleyen'; break;
                                                                    case 'reviewed': echo 'İncelenen'; break;
                                                                    case 'resolved': echo 'Çözülen'; break;
                                                                    case 'dismissed': echo 'Reddedilen'; break;
                                                                }
                                                                ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <p class="mb-2">
                                                            <strong>Hedef:</strong> <?php echo htmlspecialchars($report['target_title']); ?>
                                                        </p>
                                                        
                                                        <p class="mb-2">
                                                            <strong>Sebep:</strong> <?php echo htmlspecialchars($report['reason']); ?>
                                                        </p>
                                                        
                                                        <?php if (!empty($report['description'])): ?>
                                                            <p class="mb-2">
                                                                <strong>Açıklama:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        
                                                        <div class="text-muted small">
                                                            <i class="fas fa-user me-1"></i>
                                                            <strong>Raporlayan:</strong> <?php echo htmlspecialchars($report['reporter_username']); ?>
                                                            <span class="ms-3">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                <?php echo date('d.m.Y H:i', strtotime($report['created_at'])); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <?php if ($report['reviewed_at']): ?>
                                                            <div class="text-muted small mt-1">
                                                                <i class="fas fa-shield-alt me-1"></i>
                                                                <strong>İnceleme:</strong> 
                                                                <?php echo date('d.m.Y H:i', strtotime($report['reviewed_at'])); ?>
                                                                <?php if ($report['reviewer_username']): ?>
                                                                    - <?php echo htmlspecialchars($report['reviewer_username']); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <?php if ($report['status'] === 'pending'): ?>
                                                        <button class="btn btn-outline-info btn-sm" 
                                                                onclick="updateReportStatus(<?php echo $report['id']; ?>, 'reviewed')">
                                                            <i class="fas fa-eye"></i> İncele
                                                        </button>
                                                        <button class="btn btn-outline-success btn-sm" 
                                                                onclick="updateReportStatus(<?php echo $report['id']; ?>, 'resolved')">
                                                            <i class="fas fa-check"></i> Çöz
                                                        </button>
                                                        <button class="btn btn-outline-secondary btn-sm" 
                                                                onclick="updateReportStatus(<?php echo $report['id']; ?>, 'dismissed')">
                                                            <i class="fas fa-times"></i> Reddet
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            onclick="viewTarget('<?php echo $report['target_type']; ?>', <?php echo $report['target_id']; ?>)">
                                                        <i class="fas fa-external-link-alt"></i> Hedefi Gör
                                                    </button>
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
                                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&page=<?php echo $page - 1; ?>">Önceki</a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&page=<?php echo $page + 1; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Rapor bulunamadı</h5>
                                <p class="text-muted">Seçilen kriterlere uygun rapor bulunmamaktadır.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Durum Güncelleme Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalTitle">Rapor Durumunu Güncelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="statusForm">
                <div class="modal-body">
                    <input type="hidden" name="report_id" id="statusReportId">
                    <input type="hidden" name="action" id="statusAction">
                    
                    <div class="alert alert-info" id="statusMessage">
                        <!-- Durum mesajı buraya gelecek -->
                    </div>
                    
                    <div class="mb-3">
                        <label for="response" class="form-label">Yanıt (Opsiyonel)</label>
                        <textarea class="form-control" name="response" id="response" rows="3" 
                                  placeholder="Bu rapor hakkında notunuzu yazabilirsiniz..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="statusSubmitBtn">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateReportStatus(reportId, status) {
    document.getElementById('statusReportId').value = reportId;
    document.getElementById('statusAction').value = status;
    
    const modal = document.getElementById('statusModal');
    const title = document.getElementById('statusModalTitle');
    const submitBtn = document.getElementById('statusSubmitBtn');
    const message = document.getElementById('statusMessage');
    
    let statusText = '';
    let buttonClass = 'btn-primary';
    let messageText = '';
    
    switch(status) {
        case 'reviewed':
            statusText = 'İncele';
            buttonClass = 'btn-info';
            messageText = 'Bu rapor "İncelenen" olarak işaretlenecektir.';
            break;
        case 'resolved':
            statusText = 'Çöz';
            buttonClass = 'btn-success';
            messageText = 'Bu rapor "Çözülen" olarak işaretlenecektir.';
            break;
        case 'dismissed':
            statusText = 'Reddet';
            buttonClass = 'btn-secondary';
            messageText = 'Bu rapor "Reddedilen" olarak işaretlenecektir.';
            break;
    }
    
    title.textContent = `Raporu ${statusText}`;
    submitBtn.textContent = statusText;
    submitBtn.className = `btn ${buttonClass}`;
    message.textContent = messageText;
    
    new bootstrap.Modal(modal).show();
}

function viewTarget(type, id) {
    let url = '';
    switch(type) {
        case 'content':
            url = `../view.php?id=${id}`;
            break;
        case 'comment':
            url = `../view.php?comment=${id}`;
            break;
        case 'user':
            url = `../profile.php?user=${id}`;
            break;
    }
    
    if (url) {
        window.open(url, '_blank');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 