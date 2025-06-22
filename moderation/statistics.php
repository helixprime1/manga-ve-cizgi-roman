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
$page_title = 'İstatistikler';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Genel istatistikler
$general_stats_query = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'moderator') as total_moderators,
    (SELECT COUNT(*) FROM users WHERE is_banned = 1) as banned_users,
    (SELECT COUNT(*) FROM content) as total_content,
    (SELECT COUNT(*) FROM content WHERE status = 'published') as published_content,
    (SELECT COUNT(*) FROM content WHERE status = 'rejected') as rejected_content,
    (SELECT COUNT(*) FROM comments) as total_comments,
    (SELECT COUNT(*) FROM comments WHERE is_hidden = 1) as hidden_comments,
    (SELECT COUNT(*) FROM reports) as total_reports,
    (SELECT COUNT(*) FROM reports WHERE status = 'resolved') as resolved_reports,
    (SELECT COUNT(*) FROM moderation_logs) as total_moderation_actions";
$general_stats_result = mysqli_query($conn, $general_stats_query);
$general_stats = mysqli_fetch_assoc($general_stats_result);

// Aylık moderasyon aktivitesi (son 12 ay)
$monthly_activity_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as actions
    FROM moderation_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$monthly_activity_result = mysqli_query($conn, $monthly_activity_query);
$monthly_activity = [];
while ($row = mysqli_fetch_assoc($monthly_activity_result)) {
    $monthly_activity[] = $row;
}

// En aktif moderatörler
$top_moderators_query = "SELECT u.username, COUNT(*) as actions
    FROM moderation_logs ml
    JOIN users u ON ml.moderator_id = u.id
    WHERE ml.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY ml.moderator_id, u.username
    ORDER BY actions DESC
    LIMIT 10";
$top_moderators_result = mysqli_query($conn, $top_moderators_query);
$top_moderators = [];
while ($row = mysqli_fetch_assoc($top_moderators_result)) {
    $top_moderators[] = $row;
}

// Eylem türleri dağılımı
$action_distribution_query = "SELECT action, COUNT(*) as count
    FROM moderation_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action
    ORDER BY count DESC";
$action_distribution_result = mysqli_query($conn, $action_distribution_query);
$action_distribution = [];
while ($row = mysqli_fetch_assoc($action_distribution_result)) {
    $action_distribution[] = $row;
}

// Hedef türleri dağılımı
$target_distribution_query = "SELECT target_type, COUNT(*) as count
    FROM moderation_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY target_type
    ORDER BY count DESC";
$target_distribution_result = mysqli_query($conn, $target_distribution_query);
$target_distribution = [];
while ($row = mysqli_fetch_assoc($target_distribution_result)) {
    $target_distribution[] = $row;
}

// Haftalık trend
$weekly_trend_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as actions
    FROM moderation_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date";
$weekly_trend_result = mysqli_query($conn, $weekly_trend_query);
$weekly_trend = [];
while ($row = mysqli_fetch_assoc($weekly_trend_result)) {
    $weekly_trend[] = $row;
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
        <!-- Başlık -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Moderasyon İstatistikleri</h4>
            <div class="btn-group" role="group">
                <button class="btn btn-outline-primary" onclick="exportStats()">
                    <i class="fas fa-download me-1"></i>Rapor İndir
                </button>
                <button class="btn btn-outline-info" onclick="refreshStats()">
                    <i class="fas fa-sync me-1"></i>Yenile
                </button>
            </div>
        </div>

        <!-- Genel İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users fa-2x me-3"></i>
                            <div>
                                <h3 class="mb-0"><?php echo number_format($general_stats['total_users']); ?></h3>
                                <p class="mb-0">Toplam Kullanıcı</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-book fa-2x me-3"></i>
                            <div>
                                <h3 class="mb-0"><?php echo number_format($general_stats['total_content']); ?></h3>
                                <p class="mb-0">Toplam İçerik</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-comments fa-2x me-3"></i>
                            <div>
                                <h3 class="mb-0"><?php echo number_format($general_stats['total_comments']); ?></h3>
                                <p class="mb-0">Toplam Yorum</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt fa-2x me-3"></i>
                            <div>
                                <h3 class="mb-0"><?php echo number_format($general_stats['total_moderation_actions']); ?></h3>
                                <p class="mb-0">Moderasyon İşlemi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detaylı İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">İçerik Durumu</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="contentStatusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Moderasyon Eylemleri (Son 30 Gün)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="actionDistributionChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Haftalık Trend -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Haftalık Moderasyon Aktivitesi</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyTrendChart" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Aktif Moderatörler ve Detaylar -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">En Aktif Moderatörler (Son 30 Gün)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($top_moderators)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Moderatör</th>
                                            <th>İşlem Sayısı</th>
                                            <th>Grafik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $max_actions = $top_moderators[0]['actions'];
                                        foreach ($top_moderators as $moderator): 
                                            $percentage = ($moderator['actions'] / $max_actions) * 100;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($moderator['username']); ?></td>
                                                <td><?php echo $moderator['actions']; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%"
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Son 30 günde moderasyon aktivitesi bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sistem Özeti</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-success"><?php echo number_format($general_stats['published_content']); ?></h4>
                                <small class="text-muted">Yayınlanan İçerik</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-danger"><?php echo number_format($general_stats['rejected_content']); ?></h4>
                                <small class="text-muted">Reddedilen İçerik</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-warning"><?php echo number_format($general_stats['banned_users']); ?></h4>
                                <small class="text-muted">Banlı Kullanıcı</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-info"><?php echo number_format($general_stats['hidden_comments']); ?></h4>
                                <small class="text-muted">Gizli Yorum</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-primary"><?php echo number_format($general_stats['total_reports']); ?></h4>
                                <small class="text-muted">Toplam Rapor</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success"><?php echo number_format($general_stats['resolved_reports']); ?></h4>
                                <small class="text-muted">Çözülen Rapor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js ile grafikleri oluştur
document.addEventListener('DOMContentLoaded', function() {
    // İçerik durumu grafiği
    const contentCtx = document.getElementById('contentStatusChart').getContext('2d');
    new Chart(contentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Yayınlanan', 'Bekleyen', 'Reddedilen'],
            datasets: [{
                data: [
                    <?php echo $general_stats['published_content']; ?>,
                    <?php echo $stats['pending_content']; ?>,
                    <?php echo $general_stats['rejected_content']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Eylem dağılımı grafiği
    const actionCtx = document.getElementById('actionDistributionChart').getContext('2d');
    new Chart(actionCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($a) { return "'" . $a['action'] . "'"; }, $action_distribution)); ?>],
            datasets: [{
                label: 'İşlem Sayısı',
                data: [<?php echo implode(',', array_map(function($a) { return $a['count']; }, $action_distribution)); ?>],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Haftalık trend grafiği
    const trendCtx = document.getElementById('weeklyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($w) { return "'" . date('d.m', strtotime($w['date'])) . "'"; }, $weekly_trend)); ?>],
            datasets: [{
                label: 'Günlük İşlemler',
                data: [<?php echo implode(',', array_map(function($w) { return $w['actions']; }, $weekly_trend)); ?>],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

function exportStats() {
    window.open('api/export_stats.php', '_blank');
}

function refreshStats() {
    location.reload();
}
</script>

<?php require_once 'includes/footer.php'; ?> 