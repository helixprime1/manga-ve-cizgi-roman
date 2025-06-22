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
$page_title = 'Sistem Logları';

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

// Log tablosu yoksa oluştur
$create_logs_table = "
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
mysqli_query($conn, $create_logs_table);

// Log temizleme işlemi
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_logs') {
        $days = (int)($_POST['days'] ?? 30);
        $delete_query = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)";
        
        if (mysqli_query($conn, $delete_query)) {
            $affected_rows = mysqli_affected_rows($conn);
            $message = "$affected_rows eski log kaydı silindi.";
            $message_type = 'success';
            
            // Bu işlemi logla
            addSystemLog('clear_logs', "$days günden eski loglar temizlendi ($affected_rows kayıt)");
        } else {
            $message = 'Log temizlenirken bir hata oluştu.';
            $message_type = 'danger';
        }
    }
}

// Logları getir
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';
$limit = (int)($_GET['limit'] ?? 50);

$where_conditions = [];
if (!empty($search)) {
    $search_safe = sanitizeInput($search);
    $where_conditions[] = "(l.action LIKE '%$search_safe%' OR l.description LIKE '%$search_safe%')";
}

if (!empty($action_filter)) {
    $action_safe = sanitizeInput($action_filter);
    $where_conditions[] = "l.action = '$action_safe'";
}

if (!empty($user_filter)) {
    $user_safe = (int)$user_filter;
    $where_conditions[] = "l.user_id = $user_safe";
}

if (!empty($date_filter)) {
    $date_safe = sanitizeInput($date_filter);
    $where_conditions[] = "DATE(l.created_at) = '$date_safe'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$logs_query = "
    SELECT l.*, u.username, u.email
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.id
    $where_clause
    ORDER BY l.created_at DESC
    LIMIT $limit
";

$logs_result = mysqli_query($conn, $logs_query);

// Kullanıcı listesi (filtre için)
$users_query = "SELECT id, username FROM users ORDER BY username";
$users_result = mysqli_query($conn, $users_query);

// Aksiyon listesi
$actions_query = "SELECT DISTINCT action FROM system_logs ORDER BY action";
$actions_result = mysqli_query($conn, $actions_query);

// Log istatistikleri
$log_stats_query = "
    SELECT 
        COUNT(*) as total_logs,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as today_logs,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_logs,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_logs
    FROM system_logs
";
$log_stats_result = mysqli_query($conn, $log_stats_query);
$log_stats = mysqli_fetch_assoc($log_stats_result);

// Sistem log fonksiyonu
function addSystemLog($action, $description = '', $user_id = null) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = sanitizeInput($_SERVER['HTTP_USER_AGENT'] ?? '');
    $description = sanitizeInput($description);
    
    $insert_query = "INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES ";
    $insert_query .= "(" . ($user_id ? $user_id : 'NULL') . ", '$action', '$description', '$ip_address', '$user_agent')";
    
    mysqli_query($conn, $insert_query);
}

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
                            <h1 class="h3 mb-0">Sistem Logları</h1>
                            <p class="text-muted">Sistem aktivitelerini görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                <i class="fas fa-trash me-2"></i>Logları Temizle
                            </button>
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

            <!-- Log Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-primary"><?php echo number_format($log_stats['total_logs']); ?></div>
                            <div class="stat-label">Toplam Log</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-success"><?php echo number_format($log_stats['today_logs']); ?></div>
                            <div class="stat-label">Bugün</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-info"><?php echo number_format($log_stats['week_logs']); ?></div>
                            <div class="stat-label">Bu Hafta</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-warning"><?php echo number_format($log_stats['month_logs']); ?></div>
                            <div class="stat-label">Bu Ay</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Arama</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Aksiyon veya açıklama..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="action" class="form-label">Aksiyon</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">Tüm Aksiyonlar</option>
                                <?php while ($action = mysqli_fetch_assoc($actions_result)): ?>
                                    <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                            <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="user" class="form-label">Kullanıcı</label>
                            <select class="form-select" id="user" name="user">
                                <option value="">Tüm Kullanıcılar</option>
                                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="limit" class="form-label">Limit</label>
                            <select class="form-select" id="limit" name="limit">
                                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                                <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Sistem Logları 
                        <span class="badge bg-info"><?php echo mysqli_num_rows($logs_result); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>Aksiyon</th>
                                    <th>Açıklama</th>
                                    <th>IP Adresi</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($logs_result) > 0): ?>
                                    <?php while ($log = mysqli_fetch_assoc($logs_result)): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <?php if ($log['username']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($log['username']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($log['email']); ?></small>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Sistem</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($log['action']); ?></span>
                                            </td>
                                            <td>
                                                <div style="max-width: 300px;">
                                                    <?php echo htmlspecialchars($log['description']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></div>
                                                <small class="text-muted"><?php echo timeAgo(strtotime($log['created_at'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Log kaydı bulunamadı</p>
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

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logları Temizle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="clear_logs">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu işlem geri alınamaz. Seçilen günden eski tüm loglar silinecektir.
                    </div>
                    
                    <div class="mb-3">
                        <label for="days" class="form-label">Kaç günden eski loglar silinsin?</label>
                        <select class="form-select" id="days" name="days" required>
                            <option value="7">7 gün</option>
                            <option value="30" selected>30 gün</option>
                            <option value="60">60 gün</option>
                            <option value="90">90 gün</option>
                            <option value="180">180 gün</option>
                            <option value="365">1 yıl</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning" onclick="return confirmAction('Eski logları silmek istediğinizden emin misiniz?')">
                        Logları Temizle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}
</style>

<?php 
// Zaman farkı hesaplama fonksiyonu
function timeAgo($time) {
    $time_difference = time() - $time;
    
    if ($time_difference < 1) {
        return 'az önce';
    }
    
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'yıl',
        30 * 24 * 60 * 60 => 'ay',
        24 * 60 * 60 => 'gün',
        60 * 60 => 'saat',
        60 => 'dakika',
        1 => 'saniye'
    );
    
    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ' önce';
        }
    }
}

require_once 'includes/footer.php'; 
?> 