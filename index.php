<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// Admin ayarlarından site bilgilerini al
$site_name = getSetting('site_name', 'Manga & Comic Hub');
$site_description = getSetting('site_description', 'Manga ve çizgi roman paylaşım platformu');
$items_per_page = (int)getSetting('items_per_page', 12);
$enable_comments = getSetting('enable_comments', '1');
$enable_likes = getSetting('enable_likes', '1');
$enable_favorites = getSetting('enable_favorites', '1');

$page_title = $site_name;

// İstatistikleri al - Güvenli SQL sorguları
$total_manga_query = "SELECT COUNT(*) as count FROM content WHERE type='manga' AND status='published'";
$total_manga_result = mysqli_query($conn, $total_manga_query);
$total_manga = $total_manga_result ? mysqli_fetch_assoc($total_manga_result)['count'] : 0;

$total_comic_query = "SELECT COUNT(*) as count FROM content WHERE type='comic' AND status='published'";
$total_comic_result = mysqli_query($conn, $total_comic_query);
$total_comic = $total_comic_result ? mysqli_fetch_assoc($total_comic_result)['count'] : 0;

$total_users_query = "SELECT COUNT(*) as count FROM users";
$total_users_result = mysqli_query($conn, $total_users_query);
$total_users = $total_users_result ? mysqli_fetch_assoc($total_users_result)['count'] : 0;

$total_views_query = "SELECT SUM(views) as total FROM content";
$total_views_result = mysqli_query($conn, $total_views_query);
if ($total_views_result) {
    $total_views_row = mysqli_fetch_assoc($total_views_result);
    $total_views = $total_views_row['total'] ?? 0;
} else {
    $total_views = 0;
}

// En son eklenen içerikleri al (ayarlardan items_per_page kullan)
$latest_limit = min($items_per_page, 8); // Maksimum 8 öğe göster
$latest_content = mysqli_query($conn, "
    SELECT c.*, u.username 
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.status = 'published' 
    ORDER BY c.created_at DESC 
    LIMIT $latest_limit
");

// Öne çıkan içerikleri al
$featured_limit = min($items_per_page, 8); // Maksimum 8 öğe göster
$featured_content = mysqli_query($conn, "
    SELECT c.*, u.username,
           " . ($enable_likes ? "(SELECT COUNT(*) FROM likes WHERE content_id = c.id)" : "0") . " as like_count,
           " . ($enable_comments ? "(SELECT COUNT(*) FROM comments WHERE content_id = c.id)" : "0") . " as comment_count
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.status = 'published' AND c.featured = 1
    ORDER BY c.views DESC 
    LIMIT $featured_limit
");

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        
        <div class="carousel-inner">
            <!-- Ana Slide -->
            <div class="carousel-item active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="container">
                    <div class="row align-items-center min-vh-75">
                        <div class="col-lg-6">
                            <div class="hero-content text-white">
                                <h1 class="hero-title display-4 fw-bold mb-4" data-aos="fade-up">
                                    <?php echo htmlspecialchars($site_name); ?> Dünyasına Hoş Geldiniz!
                                </h1>
                                <p class="hero-description lead mb-4" data-aos="fade-up" data-aos-delay="100">
                                    <?php echo htmlspecialchars($site_description); ?>
                                </p>
                                <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200">
                                    <?php if (isLoggedIn()): ?>
                                        <?php $user = getCurrentUser(); ?>
                                        <?php if ($user['is_author'] == 1): ?>
                                            <a href="user-panel/" class="btn btn-primary btn-lg me-3">
                                                <i class="fas fa-tachometer-alt me-2"></i>Yazar Paneli
                                            </a>
                                        <?php else: ?>
                                            <a href="author-application.php" class="btn btn-primary btn-lg me-3">
                                                <i class="fas fa-pen-fancy me-2"></i>Yazar Başvurusu
                                            </a>
                                        <?php endif; ?>
                                        <a href="category.php?type=manga" class="btn btn-outline-light btn-lg">
                                            <i class="fas fa-book me-2"></i>Keşfet
                                        </a>
                                    <?php else: ?>
                                        <a href="register.php" class="btn btn-primary btn-lg me-3">
                                            <i class="fas fa-user-plus me-2"></i>Üye Ol
                                        </a>
                                        <a href="login.php" class="btn btn-outline-light btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6" data-aos="fade-left">
                            <div class="hero-image-placeholder d-flex align-items-center justify-content-center" style="height: 400px; background: rgba(255,255,255,0.1); border-radius: 15px;">
                                <i class="fas fa-book-open" style="font-size: 120px; color: rgba(255,255,255,0.3);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Manga Slide -->
            <div class="carousel-item" style="background: linear-gradient(135deg, #6B46C1 0%, #434190 100%);">
                <div class="container">
                    <div class="row align-items-center min-vh-75">
                        <div class="col-lg-6">
                            <div class="hero-content text-white">
                                <h1 class="hero-title display-4 fw-bold mb-4" data-aos="fade-up">
                                    En Yeni Mangalar
                                </h1>
                                <p class="hero-description lead mb-4" data-aos="fade-up" data-aos-delay="100">
                                    Japon kültürünün en güzel örneklerini keşfedin. Aksiyon, macera, romantizm ve daha fazlası...
                                </p>
                                <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200">
                                    <a href="category.php?type=manga" class="btn btn-primary btn-lg">
                                        <i class="fas fa-dragon me-2"></i>Mangaları Keşfet
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6" data-aos="fade-left">
                            <div class="hero-image-placeholder d-flex align-items-center justify-content-center" style="height: 400px; background: rgba(255,255,255,0.1); border-radius: 15px;">
                                <i class="fas fa-dragon" style="font-size: 120px; color: rgba(255,255,255,0.3);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Çizgi Roman Slide -->
            <div class="carousel-item" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);">
                <div class="container">
                    <div class="row align-items-center min-vh-75">
                        <div class="col-lg-6">
                            <div class="hero-content text-white">
                                <h1 class="hero-title display-4 fw-bold mb-4" data-aos="fade-up">
                                    Çizgi Roman Dünyası
                                </h1>
                                <p class="hero-description lead mb-4" data-aos="fade-up" data-aos-delay="100">
                                    Süper kahramanlardan bilim kurguya, western'den tarihi romanlara kadar geniş bir yelpaze...
                                </p>
                                <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200">
                                    <a href="category.php?type=comic" class="btn btn-primary btn-lg">
                                        <i class="fas fa-mask me-2"></i>Çizgi Romanları Keşfet
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6" data-aos="fade-left">
                            <div class="hero-image-placeholder d-flex align-items-center justify-content-center" style="height: 400px; background: rgba(255,255,255,0.1); border-radius: 15px;">
                                <i class="fas fa-mask" style="font-size: 120px; color: rgba(255,255,255,0.3);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Önceki</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sonraki</span>
        </button>
    </div>
</section>

<!-- İstatistikler -->
<section class="stats-section py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-primary-soft mb-3">
                        <i class="fas fa-dragon"></i>
                    </div>
                    <h3 class="stat-number mb-2"><?php echo number_format($total_manga); ?></h3>
                    <p class="stat-label text-muted mb-0">Manga</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-info-soft mb-3">
                        <i class="fas fa-mask"></i>
                    </div>
                    <h3 class="stat-number mb-2"><?php echo number_format($total_comic); ?></h3>
                    <p class="stat-label text-muted mb-0">Çizgi Roman</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-success-soft mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="stat-number mb-2"><?php echo number_format($total_users); ?></h3>
                    <p class="stat-label text-muted mb-0">Kullanıcı</p>
                </div>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-card text-center">
                    <div class="stat-icon bg-warning-soft mb-3">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="stat-number mb-2"><?php echo number_format($total_views); ?></h3>
                    <p class="stat-label text-muted mb-0">Görüntülenme</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Öne Çıkan İçerikler -->
<section class="featured-section py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <h2 class="section-title">Öne Çıkan İçerikler</h2>
            <p class="section-subtitle text-muted">En popüler manga ve çizgi romanları keşfedin</p>
        </div>
        
        <div class="row g-4">
            <?php 
            $delay = 0;
            if ($featured_content && mysqli_num_rows($featured_content) > 0):
                while ($item = mysqli_fetch_assoc($featured_content)): 
            ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $delay += 100; ?>">
                    <div class="content-card h-100">
                        <div class="content-image">
                            <img src="uploads/covers/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 onerror="this.src='assets/images/no-image.svg'">
                            <div class="content-type">
                                <span class="badge <?php echo $item['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                    <?php echo $item['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                </span>
                            </div>
                            <?php if ($item['featured']): ?>
                                <div class="content-featured">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star me-1"></i>Öne Çıkan
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">
                                <?php echo mb_substr(htmlspecialchars($item['description']), 0, 100); ?>...
                            </p>
                            <div class="content-meta">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="author">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['username']); ?>
                                        </small>
                                    </div>
                                    <div class="stats">
                                        <small class="text-muted me-2">
                                            <i class="fas fa-eye me-1"></i><?php echo number_format($item['views']); ?>
                                        </small>
                                        <?php if ($enable_likes): ?>
                                        <small class="text-muted me-2">
                                            <i class="fas fa-heart me-1"></i><?php echo number_format($item['like_count']); ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if ($enable_comments): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-comment me-1"></i><?php echo number_format($item['comment_count']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-book-open me-2"></i>Oku
                            </a>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Henüz öne çıkan içerik bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="category.php?type=manga" class="btn btn-outline-primary btn-lg">
                Tüm İçerikleri Görüntüle <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Kategoriler -->
<section class="py-5">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Kategoriler</h2>
            <p class="section-subtitle">İlgi alanınıza göre içerikleri keşfedin</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4" data-aos="fade-right">
                <div class="category-card h-100">
                    <div class="category-icon">
                        <i class="fas fa-dragon"></i>
                    </div>
                    <h3>Manga</h3>
                    <p class="mb-4">Japon kültürünün en güzel örneklerinden biri olan mangaları keşfedin. Aksiyon, romantizm, fantastik ve daha fazlası...</p>
                    <a href="category.php?type=manga" class="btn btn-primary">
                        Mangaları Keşfet <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-6 mb-4" data-aos="fade-left">
                <div class="category-card h-100">
                    <div class="category-icon">
                        <i class="fas fa-mask"></i>
                    </div>
                    <h3>Çizgi Roman</h3>
                    <p class="mb-4">Süper kahramanlardan bilim kurguya, western'den tarihi romanlara kadar geniş bir yelpazede çizgi romanlar...</p>
                    <a href="category.php?type=comic" class="btn btn-primary">
                        Çizgi Romanları Keşfet <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Son Eklenenler -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Son Eklenenler</h2>
            <p class="section-subtitle">En yeni manga ve çizgi romanları kaçırmayın</p>
        </div>
        
        <div class="row g-4">
            <?php 
            // Son eklenen içerikler için yeni sorgu (ayarlardan limit al)
            $latest_items_limit = min($items_per_page / 2, 4); // Maksimum 4 öğe göster
            $latest_items = mysqli_query($conn, "
                SELECT c.*, u.username 
                FROM content c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.status = 'published' 
                ORDER BY c.created_at DESC 
                LIMIT $latest_items_limit
            ");
            
            $index = 0;
            if ($latest_items && mysqli_num_rows($latest_items) > 0):
                while ($item = mysqli_fetch_assoc($latest_items)): 
            ?>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                    <div class="card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="uploads/covers/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 onerror="this.src='assets/images/no-image.svg'">
                            <div class="position-absolute top-0 start-0 p-2">
                                <span class="badge bg-success">
                                    <i class="fas fa-sparkles me-1"></i>Yeni
                                </span>
                            </div>
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge <?php echo $item['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                    <?php echo $item['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">
                                <?php echo mb_substr(htmlspecialchars($item['description']), 0, 100); ?>...
                            </p>
                            <div class="content-meta">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['username']); ?>
                                </small>
                                <small class="text-muted ms-3">
                                    <i class="fas fa-eye me-1"></i><?php echo number_format($item['views']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-book-open me-2"></i>Detayları Gör
                            </a>
                        </div>
                    </div>
                </div>
            <?php 
                    $index++;
                endwhile;
            else:
            ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Henüz yeni içerik bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="category.php" class="btn btn-outline-primary btn-lg">
                Tüm Son Eklenenler <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container" data-aos="fade-up">
        <h2 class="mb-4">Hemen Başlayın!</h2>
        <p class="lead mb-4">Kendi manga ve çizgi romanlarınızı yükleyin, binlerce okuyucuya ulaşın.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">
                <i class="fas fa-user-plus me-2"></i>Ücretsiz Üye Ol
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </a>
        <?php else: ?>
            <?php $user = getCurrentUser(); ?>
            <?php if ($user['is_author'] == 1): ?>
                <a href="user-panel/upload.php" class="btn btn-light btn-lg">
                    <i class="fas fa-cloud-upload-alt me-2"></i>İçerik Yükle
                </a>
            <?php else: ?>
                <a href="author-application.php" class="btn btn-light btn-lg">
                    <i class="fas fa-pen-fancy me-2"></i>Yazar Başvurusu
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?> 