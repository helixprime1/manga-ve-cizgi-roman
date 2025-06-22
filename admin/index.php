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
$page_title = 'Admin Dashboard';

// İstatistikleri al
$stats = [];

// Kullanıcı sayısı
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

// İçerik sayısı
$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

// Bekleyen içerik sayısı
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

// Yayında olan içerik sayısı
$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Toplam bölüm sayısı
$chapters_query = "SELECT COUNT(*) as total FROM chapters";
$chapters_result = mysqli_query($conn, $chapters_query);
$stats['total_chapters'] = $chapters_result ? mysqli_fetch_assoc($chapters_result)['total'] : 0;

// Seri sayısı
$series_query = "SELECT COUNT(*) as total FROM content WHERE is_series = 1";
$series_result = mysqli_query($conn, $series_query);
$stats['total_series'] = $series_result ? mysqli_fetch_assoc($series_result)['total'] : 0;

// Yorum sayısı
$comments_query = "SELECT COUNT(*) as total FROM comments";
$comments_result = mysqli_query($conn, $comments_query);
$stats['total_comments'] = $comments_result ? mysqli_fetch_assoc($comments_result)['total'] : 0;

// Bugünkü yeni içerikler
$today_query = "SELECT COUNT(*) as total FROM content WHERE DATE(created_at) = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$stats['today_content'] = $today_result ? mysqli_fetch_assoc($today_result)['total'] : 0;

// Son eklenen içerikler (5 adet)
$recent_content_query = "
    SELECT c.*, u.username 
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC 
    LIMIT 5
";
$recent_content_result = mysqli_query($conn, $recent_content_query);

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
                            <h1 class="h4 mb-0">Admin Dashboard</h1>
                            <p class="text-muted small mb-0">Sistem genel durumu ve istatistikleri</p>
                        </div>
                        <div>
                            <span class="badge bg-success">
                                <i class="fas fa-circle me-1"></i>
                                Sistem Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Toplam Kullanıcı</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_users']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Toplam İçerik</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_content']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book-open fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Toplam Seri</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_series']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-list fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Toplam Bölüm</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_chapters']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Yayında</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['published_content']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Bekleyen</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['pending_content']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-comments fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Toplam Yorum</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['total_comments']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Bugün Eklenen</div>
                                    <div class="h4 mb-0"><?php echo number_format($stats['today_content']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Son Eklenen İçerikler
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($recent_content_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 60px;">Kapak</th>
                                                <th>Başlık</th>
                                                <th style="width: 120px;">Yazar</th>
                                                <th style="width: 80px;">Tür</th>
                                                <th style="width: 100px;">Durum</th>
                                                <th style="width: 120px;">Tarih</th>
                                                <th style="width: 100px;">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($content = mysqli_fetch_assoc($recent_content_result)): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        $cover_path = '../uploads/covers/' . $content['cover_image'];
                                                        if (file_exists($cover_path)): ?>
                                                            <img src="<?php echo htmlspecialchars($cover_path); ?>" 
                                                                 class="rounded" style="width: 40px; height: 50px; object-fit: cover;"
                                                                 alt="<?php echo htmlspecialchars($content['title']); ?>">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                                 style="width: 40px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($content['title']); ?></div>
                                                        <?php if ($content['is_series']): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-book-open me-1"></i>Seri
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($content['username']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                                            <?php echo $content['type'] === 'manga' ? 'Manga' : 'Comic'; ?>
                                                        </span>
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
                                                        <small><?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="../view.php?id=<?php echo $content['id']; ?>" 
                                                               target="_blank" class="btn btn-outline-primary btn-sm" title="Görüntüle">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="content.php" class="btn btn-outline-secondary btn-sm" title="Düzenle">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Henüz içerik eklenmemiş.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 