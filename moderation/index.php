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
$page_title = 'Moderasyon Paneli';

// İstatistikleri al
$stats = [];

// Bekleyen içerikler
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

// Bildirilen yorumlar
$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Bekleyen raporlar
$pending_reports_query = "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'";
$pending_reports_result = mysqli_query($conn, $pending_reports_query);
$stats['pending_reports'] = $pending_reports_result ? mysqli_fetch_assoc($pending_reports_result)['total'] : 0;

// Toplam kullanıcılar
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users_result = mysqli_query($conn, $total_users_query);
$stats['total_users'] = $total_users_result ? mysqli_fetch_assoc($total_users_result)['total'] : 0;

// Toplam içerikler
$total_content_query = "SELECT COUNT(*) as total FROM content";
$total_content_result = mysqli_query($conn, $total_content_query);
$stats['total_content'] = $total_content_result ? mysqli_fetch_assoc($total_content_result)['total'] : 0;

// Bugünkü aktivite
$today_activity_query = "SELECT 
    (SELECT COUNT(*) FROM content WHERE DATE(created_at) = CURDATE()) as today_content,
    (SELECT COUNT(*) FROM comments WHERE DATE(created_at) = CURDATE()) as today_comments,
    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as today_users";
$today_activity_result = mysqli_query($conn, $today_activity_query);
$today_activity = $today_activity_result ? mysqli_fetch_assoc($today_activity_result) : ['today_content' => 0, 'today_comments' => 0, 'today_users' => 0];

// Son moderasyon aktiviteleri
$recent_logs_query = "SELECT ml.*, u.username as moderator_name 
                      FROM moderation_logs ml 
                      JOIN users u ON ml.moderator_id = u.id 
                      ORDER BY ml.created_at DESC 
                      LIMIT 10";
$recent_logs_result = mysqli_query($conn, $recent_logs_query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
                <!-- Hoşgeldin Mesajı -->
                <div class="welcome-section mb-4">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="mb-1">Hoşgeldin, <?php echo htmlspecialchars($moderator_user['username']); ?>!</h4>
                                    <p class="mb-0">Moderasyon paneline hoşgeldin. Bugün <?php echo date('d.m.Y'); ?></p>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shield-alt fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                            <i class="fas fa-clock text-warning fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-1"><?php echo $stats['pending_content']; ?></h3>
                                        <p class="text-muted mb-0">Bekleyen İçerik</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                            <i class="fas fa-flag text-danger fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-1"><?php echo $stats['reported_comments']; ?></h3>
                                        <p class="text-muted mb-0">Bildirilen Yorum</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                            <i class="fas fa-exclamation-triangle text-info fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-1"><?php echo $stats['pending_reports']; ?></h3>
                                        <p class="text-muted mb-0">Bekleyen Rapor</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                            <i class="fas fa-users text-success fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h3 class="mb-1"><?php echo $stats['total_users']; ?></h3>
                                        <p class="text-muted mb-0">Toplam Kullanıcı</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hızlı Eylemler -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Hızlı Eylemler
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="content.php?status=pending" class="btn btn-outline-warning">
                                        <i class="fas fa-clock me-2"></i>Bekleyen İçerikleri İncele
                                        <?php if ($stats['pending_content'] > 0): ?>
                                            <span class="badge bg-warning"><?php echo $stats['pending_content']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="comments.php?filter=reported" class="btn btn-outline-danger">
                                        <i class="fas fa-flag me-2"></i>Bildirilen Yorumları İncele
                                        <?php if ($stats['reported_comments'] > 0): ?>
                                            <span class="badge bg-danger"><?php echo $stats['reported_comments']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="reports.php?status=pending" class="btn btn-outline-info">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Bekleyen Raporları İncele
                                        <?php if ($stats['pending_reports'] > 0): ?>
                                            <span class="badge bg-info"><?php echo $stats['pending_reports']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Bugünkü Aktivite
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-primary"><?php echo $today_activity['today_content']; ?></h4>
                                            <small class="text-muted">Yeni İçerik</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-success"><?php echo $today_activity['today_comments']; ?></h4>
                                            <small class="text-muted">Yeni Yorum</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info"><?php echo $today_activity['today_users']; ?></h4>
                                        <small class="text-muted">Yeni Üye</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Moderasyon Aktiviteleri -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Son Moderasyon Aktiviteleri
                        </h5>
                        <a href="logs.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Tümünü Gör
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($recent_logs_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Moderatör</th>
                                            <th>Eylem</th>
                                            <th>Hedef</th>
                                            <th>Açıklama</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = mysqli_fetch_assoc($recent_logs_result)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($log['moderator_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $log['action'] === 'approve' ? 'success' : 
                                                            ($log['action'] === 'reject' ? 'danger' : 
                                                            ($log['action'] === 'delete' ? 'warning' : 'info')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($log['target_type']); ?> #<?php echo $log['target_id']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($log['description']); ?></small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Henüz moderasyon aktivitesi yok</h6>
                                <p class="text-muted">İlk moderasyon işleminizi yaptığınızda burada görünecek.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 