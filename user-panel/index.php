<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Dashboard';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$user_id = $user['id'];

// Eğer user_id yoksa hata ver
if (!$user_id) {
    echo "<div class='alert alert-danger'>Kullanıcı ID'si bulunamadı. Lütfen tekrar giriş yapın.</div>";
    echo "<a href='../logout.php' class='btn btn-primary'>Çıkış Yap ve Tekrar Giriş Yap</a>";
    exit;
}

// İstatistikleri güvenli şekilde al
$stats_stmt = mysqli_prepare($conn, "
    SELECT 
    COUNT(*) as total_content,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_content,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_content,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_content,
    SUM(views) as total_views,
    SUM(CASE WHEN type = 'manga' THEN 1 ELSE 0 END) as manga_count,
    SUM(CASE WHEN type = 'comic' THEN 1 ELSE 0 END) as comic_count
    FROM content 
    WHERE user_id = ?
");
mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Beğeni sayılarını ayrı olarak al (likes tablosundan)
$likes_stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) as total_likes
    FROM likes l
    INNER JOIN content c ON l.content_id = c.id
    WHERE c.user_id = ?
");
mysqli_stmt_bind_param($likes_stmt, "i", $user_id);
mysqli_stmt_execute($likes_stmt);
$likes_result = mysqli_stmt_get_result($likes_stmt);
$likes_data = mysqli_fetch_assoc($likes_result);
$stats['total_likes'] = $likes_data['total_likes'] ?? 0;

// Son yüklenen içerikleri al
$recent_stmt = mysqli_prepare($conn, "
    SELECT id, title, type, status, views, created_at, cover_image
    FROM content 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
mysqli_stmt_bind_param($recent_stmt, "i", $user_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);
$recent_content = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    // Her içerik için beğeni sayısını al
    $content_likes_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as likes FROM likes WHERE content_id = ?");
    mysqli_stmt_bind_param($content_likes_stmt, "i", $row['id']);
    mysqli_stmt_execute($content_likes_stmt);
    $content_likes_result = mysqli_stmt_get_result($content_likes_stmt);
    $content_likes_data = mysqli_fetch_assoc($content_likes_result);
    $row['likes'] = $content_likes_data['likes'] ?? 0;
    mysqli_stmt_close($content_likes_stmt);
    
    $recent_content[] = $row;
}

// En popüler içerikleri al (beğeni sayısına göre)
$popular_stmt = mysqli_prepare($conn, "
    SELECT c.id, c.title, c.type, c.views, c.created_at, c.cover_image,
           COUNT(l.id) as likes
    FROM content c 
    LEFT JOIN likes l ON c.id = l.content_id
    WHERE c.user_id = ? AND c.status = 'published'
    GROUP BY c.id
    ORDER BY likes DESC, c.views DESC 
    LIMIT 5
");
mysqli_stmt_bind_param($popular_stmt, "i", $user_id);
mysqli_stmt_execute($popular_stmt);
$popular_result = mysqli_stmt_get_result($popular_stmt);
$popular_content = [];
while ($row = mysqli_fetch_assoc($popular_result)) {
    $popular_content[] = $row;
}

// Aylık istatistikler - Güvenli şekilde
$monthly_stats = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_stmt = mysqli_prepare($conn, "
        SELECT SUM(views) as views, COUNT(*) as content_count
        FROM content 
        WHERE user_id = ? 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    mysqli_stmt_bind_param($monthly_stmt, "is", $user_id, $month);
    mysqli_stmt_execute($monthly_stmt);
    $monthly_result = mysqli_stmt_get_result($monthly_stmt);
    $monthly_data = mysqli_fetch_assoc($monthly_result);
    $monthly_stats[] = [
        'month' => $month,
        'month_name' => date('M Y', strtotime($month . '-01')),
        'views' => $monthly_data['views'] ?? 0,
        'content_count' => $monthly_data['content_count'] ?? 0
    ];
    mysqli_stmt_close($monthly_stmt);
}

require_once 'includes/header.php';
?>

<div class="dashboard-content">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>📊 İçerik Yönetim Paneli</h1>
                <p>Hoş geldin, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?>! İçeriklerini yönet ve istatistiklerini takip et.</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="../upload.php" class="btn btn-primary">
                        ➕ Yeni İçerik
                    </a>
                    <a href="content.php" class="btn btn-outline-primary">
                        📋 Tüm İçerikler
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stats-card">
                <div class="stats-icon">
                    📚
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo $stats['total_content'] ?? 0; ?></div>
                    <div class="stats-label">Toplam İçerik</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card stats-success">
                <div class="stats-icon">
                    ✅
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo $stats['published_content'] ?? 0; ?></div>
                    <div class="stats-label">Yayınlanan</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card stats-warning">
                <div class="stats-icon">
                    ⏳
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo $stats['pending_content'] ?? 0; ?></div>
                    <div class="stats-label">Beklemede</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card stats-info">
                <div class="stats-icon">
                    👁️
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                    <div class="stats-label">Görüntülenme</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card stats-danger">
                <div class="stats-icon">
                    ❤️
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo number_format($stats['total_likes'] ?? 0); ?></div>
                    <div class="stats-label">Beğeni</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card stats-dark">
                <div class="stats-icon">
                    ❌
                </div>
                <div class="stats-info">
                    <div class="stats-number"><?php echo $stats['rejected_content'] ?? 0; ?></div>
                    <div class="stats-label">Reddedilen</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hızlı İşlemler -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">⚡ Hızlı İşlemler</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="upload.php" class="quick-action-btn">
                        📤
                        <span>İçerik Yükle</span>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="content.php" class="quick-action-btn">
                        📁
                        <span>İçeriklerim</span>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="settings.php" class="quick-action-btn">
                        ⚙️
                        <span>Ayarlar</span>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="comments.php" class="quick-action-btn">
                        💬
                        <span>Yorumlar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Aylık İstatistikler -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📅 Son 6 Aylık Özet</h5>
                    <div class="monthly-stats-text">
                        <?php
                        $total_monthly_views = array_sum(array_column($monthly_stats, 'views'));
                        $total_monthly_content = array_sum(array_column($monthly_stats, 'content_count'));
                        $avg_monthly_views = $total_monthly_content > 0 ? round($total_monthly_views / $total_monthly_content, 1) : 0;
                        ?>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="monthly-stat-item">
                                    <div class="stat-number text-primary"><?php echo number_format($total_monthly_views); ?></div>
                                    <div class="stat-label">Toplam Görüntülenme</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="monthly-stat-item">
                                    <div class="stat-number text-success"><?php echo $total_monthly_content; ?></div>
                                    <div class="stat-label">Yeni İçerik</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="monthly-stat-item">
                                    <div class="stat-number text-info"><?php echo $avg_monthly_views; ?></div>
                                    <div class="stat-label">Ortalama/İçerik</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="monthly-breakdown">
                            <h6 class="mb-3">Aylık Detay:</h6>
                            <?php foreach (array_slice($monthly_stats, -3) as $month): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-medium"><?php echo $month['month_name']; ?></span>
                                    <div class="text-end">
                                                                <span class="badge bg-light text-dark me-2">
                            👁️ <?php echo number_format($month['views']); ?>
                        </span>
                        <span class="badge bg-primary">
                            ➕ <?php echo $month['content_count']; ?>
                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- En Popüler İçerikler -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">🔥 En Popüler İçerikler</h5>
                    <?php if (count($popular_content) > 0): ?>
                        <div class="popular-content-list">
                            <?php foreach ($popular_content as $content): ?>
                                <div class="popular-item">
                                    <img src="../uploads/covers/<?php echo htmlspecialchars($content['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($content['title']); ?>"
                                         onerror="this.src='../assets/images/no-image.svg'">
                                    <div class="popular-info">
                                        <div class="popular-title"><?php echo htmlspecialchars($content['title']); ?></div>
                                                                <div class="popular-stats">
                            <span>👁️ <?php echo number_format($content['views']); ?></span>
                            <span>❤️ <?php echo number_format($content['likes'] ?? 0); ?></span>
                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                                            <div class="text-center py-4">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📈</div>
                        <p class="text-muted">Henüz yayınlanan içerik yok</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Yüklenen İçerikler -->
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">🕒 Son Yüklenen İçerikler</h5>
            <?php if (count($recent_content) > 0): ?>
                <div class="row">
                    <?php foreach ($recent_content as $content): ?>
                        <div class="col-md-6 mb-3">
                            <div class="recent-content-card">
                                <div class="row g-0">
                                    <div class="col-4">
                                        <img src="../uploads/covers/<?php echo htmlspecialchars($content['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($content['title']); ?>"
                                             class="recent-image"
                                             onerror="this.src='../assets/images/no-image.svg'">
                                    </div>
                                    <div class="col-8">
                                        <div class="recent-body">
                                            <h6 class="recent-title"><?php echo htmlspecialchars($content['title']); ?></h6>
                                            <p class="recent-status">
                                                <span class="badge badge-<?php echo $content['status']; ?>">
                                                    <?php 
                                                    switch($content['status']) {
                                                        case 'published': echo 'Yayınlanan'; break;
                                                        case 'pending': echo 'Beklemede'; break;
                                                        case 'rejected': echo 'Reddedilen'; break;
                                                    }
                                                    ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                📅 <?php echo date('d.m.Y', strtotime($content['created_at'])); ?>
                                                <br>
                                                👁️ <?php echo number_format($content['views']); ?> 
                                                ❤️ <?php echo number_format($content['likes'] ?? 0); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size: 5rem; margin-bottom: 2rem;">📚</div>
                    <h4 class="text-muted">Henüz içerik yüklememişsiniz</h4>
                    <p class="text-muted mb-4">İlk manga veya çizgi romanınızı yükleyerek başlayın!</p>
                    <a href="../upload.php" class="btn btn-primary btn-lg">
                        ➕ İlk İçeriğinizi Yükleyin
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Sayfa yüklendiğinde animasyonlar
document.addEventListener('DOMContentLoaded', function() {
    // Stats kartlarına animasyon
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Monthly stats animasyonu
    const monthlyItems = document.querySelectorAll('.monthly-stat-item');
    monthlyItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, (index * 100) + 500);
    });
});
</script>

<style>
.monthly-stat-item {
    padding: 1rem 0;
}

.monthly-stat-item .stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.monthly-stat-item .stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.monthly-breakdown {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

.monthly-breakdown .badge {
    font-size: 0.75rem;
}

.monthly-breakdown .fw-medium {
    color: #495057;
}
</style>

<?php require_once 'includes/footer.php'; ?> 