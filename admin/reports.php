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
$page_title = 'Raporlar ve İstatistikler';

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

// Detaylı istatistikler
$monthly_stats = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_query = "
        SELECT 
            COUNT(CASE WHEN table_name = 'users' THEN 1 END) as users,
            COUNT(CASE WHEN table_name = 'content' THEN 1 END) as content
        FROM (
            SELECT 'users' as table_name, created_at FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
            UNION ALL
            SELECT 'content' as table_name, created_at FROM content WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
        ) as combined
    ";
    $monthly_result = mysqli_query($conn, $monthly_query);
    $monthly_data = mysqli_fetch_assoc($monthly_result);
    
    $monthly_stats[] = [
        'month' => $month,
        'month_name' => date('M Y', strtotime($month . '-01')),
        'users' => $monthly_data['users'] ?? 0,
        'content' => $monthly_data['content'] ?? 0
    ];
}

// En popüler içerikler
$popular_content_query = "
    SELECT c.title, c.views, c.type, u.username
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.status = 'published'
    ORDER BY c.views DESC 
    LIMIT 10
";
$popular_content = mysqli_query($conn, $popular_content_query);

// En aktif kullanıcılar
$active_users_query = "
    SELECT u.username, u.email, 
           COUNT(c.id) as content_count,
           SUM(c.views) as total_views
    FROM users u 
    LEFT JOIN content c ON u.id = c.user_id 
    GROUP BY u.id 
    ORDER BY content_count DESC, total_views DESC
    LIMIT 10
";
$active_users = mysqli_query($conn, $active_users_query);

// Tür dağılımı
$type_distribution_query = "
    SELECT 
        type,
        COUNT(*) as count,
        SUM(views) as total_views
    FROM content 
    WHERE status = 'published'
    GROUP BY type
";
$type_distribution = mysqli_query($conn, $type_distribution_query);

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
                            <h1 class="h3 mb-0">Raporlar ve İstatistikler</h1>
                            <p class="text-muted">Detaylı sistem raporlarını görüntüleyin</p>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Raporu Yazdır
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Özet İstatistikler -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary text-white rounded-3 me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                                    <div class="stat-label">Toplam Kullanıcı</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success text-white rounded-3 me-3">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo number_format($stats['total_content']); ?></div>
                                    <div class="stat-label">Toplam İçerik</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info text-white rounded-3 me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo number_format($stats['published_content']); ?></div>
                                    <div class="stat-label">Yayınlanan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning text-white rounded-3 me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <div class="stat-number"><?php echo number_format($stats['pending_content']); ?></div>
                                    <div class="stat-label">Bekleyen</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafikler -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2 text-primary"></i>Aylık Büyüme Trendi
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-pie-chart me-2 text-success"></i>İçerik Türü Dağılımı
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="typeChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablolar -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-fire me-2 text-danger"></i>En Popüler İçerikler
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Tür</th>
                                            <th>Yazar</th>
                                            <th>Görüntülenme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($popular_content) > 0): ?>
                                            <?php while ($content = mysqli_fetch_assoc($popular_content)): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($content['title']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                                            <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($content['username']); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo number_format($content['views']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-3 text-muted">Veri bulunamadı</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-star me-2 text-warning"></i>En Aktif Kullanıcılar
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>İçerik Sayısı</th>
                                            <th>Toplam Görüntülenme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($active_users) > 0): ?>
                                            <?php while ($user = mysqli_fetch_assoc($active_users)): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo number_format($user['content_count']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo number_format($user['total_views'] ?? 0); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">Veri bulunamadı</td>
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
    </div>
</div>

<script>
// Aylık trend grafiği
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_stats, 'month_name')); ?>,
        datasets: [{
            label: 'Yeni Kullanıcılar',
            data: <?php echo json_encode(array_column($monthly_stats, 'users')); ?>,
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4
        }, {
            label: 'Yeni İçerikler',
            data: <?php echo json_encode(array_column($monthly_stats, 'content')); ?>,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Tür dağılımı grafiği
const typeCtx = document.getElementById('typeChart').getContext('2d');
<?php
$type_labels = [];
$type_data = [];
$type_colors = [];
while ($type = mysqli_fetch_assoc($type_distribution)) {
    $type_labels[] = $type['type'] === 'manga' ? 'Manga' : 'Çizgi Roman';
    $type_data[] = $type['count'];
    $type_colors[] = $type['type'] === 'manga' ? 'rgb(99, 102, 241)' : 'rgb(59, 130, 246)';
}
?>
const typeChart = new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($type_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($type_data); ?>,
            backgroundColor: <?php echo json_encode($type_colors); ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 