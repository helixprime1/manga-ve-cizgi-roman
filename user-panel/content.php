<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'İçeriklerim';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$user_id = $user['id'];

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filtreleme parametreleri
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Toplam içerik sayısını al
$count_query = "SELECT COUNT(*) as total FROM content WHERE user_id = ?";
$count_params = [$user_id];
$count_types = "i";

// Filtreleme koşulları ekle
if (!empty($filter_type)) {
    $count_query .= " AND type = ?";
    $count_params[] = $filter_type;
    $count_types .= "s";
}

if (!empty($filter_status)) {
    $count_query .= " AND status = ?";
    $count_params[] = $filter_status;
    $count_types .= "s";
}

if (!empty($search)) {
    $count_query .= " AND (title LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "ss";
}

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_content = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_content / $limit);

// İçerikleri al
$content_query = "SELECT c.*, 
                  (SELECT COUNT(*) FROM chapters ch WHERE ch.content_id = c.id) as chapter_count,
                  (SELECT COUNT(*) FROM likes l WHERE l.content_id = c.id) as like_count,
                  (SELECT COUNT(*) FROM comments cm WHERE cm.content_id = c.id) as comment_count,
                  (SELECT COUNT(*) FROM favorites f WHERE f.content_id = c.id) as favorite_count
                  FROM content c 
                  WHERE c.user_id = ?";

$content_params = [$user_id];
$content_types = "i";

// Filtreleme koşulları ekle
if (!empty($filter_type)) {
    $content_query .= " AND c.type = ?";
    $content_params[] = $filter_type;
    $content_types .= "s";
}

if (!empty($filter_status)) {
    $content_query .= " AND c.status = ?";
    $content_params[] = $filter_status;
    $content_types .= "s";
}

if (!empty($search)) {
    $content_query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search%";
    $content_params[] = $search_param;
    $content_params[] = $search_param;
    $content_types .= "ss";
}

$content_query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$content_params[] = $limit;
$content_params[] = $offset;
$content_types .= "ii";

$content_stmt = mysqli_prepare($conn, $content_query);
mysqli_stmt_bind_param($content_stmt, $content_types, ...$content_params);
mysqli_stmt_execute($content_stmt);
$content_result = mysqli_stmt_get_result($content_stmt);

// İstatistikler
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN type = 'manga' THEN 1 ELSE 0 END) as manga_count,
                SUM(CASE WHEN type = 'comic' THEN 1 ELSE 0 END) as comic_count,
                SUM(CASE WHEN is_series = 1 THEN 1 ELSE 0 END) as series_count
                FROM content WHERE user_id = ?";

$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Header'ı include et
require_once 'includes/header.php';
?>

<style>
.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.content-card {
    background: white;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
}

.content-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.content-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.content-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.content-card:hover .content-image img {
    transform: scale(1.05);
}

.status-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-published {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.status-rejected {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.content-info {
    padding: 1.25rem;
}

.content-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.content-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.content-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.content-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    flex: 1;
    padding: 0.5rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.filter-section {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 fw-bold mb-2">İçeriklerim</h1>
            <p class="text-muted mb-0">Yüklediğiniz manga ve çizgi romanları yönetin</p>
        </div>
        <a href="upload.php" class="btn btn-primary btn-lg">
            ➕ Yeni İçerik Yükle
        </a>
    </div>

    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Toplam İçerik</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-success"><?php echo $stats['published']; ?></div>
            <div class="stat-label">Yayınlanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Beklemede</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-danger"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Reddedilen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-info"><?php echo $stats['manga_count']; ?></div>
            <div class="stat-label">Manga</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-secondary"><?php echo $stats['comic_count']; ?></div>
            <div class="stat-label">Çizgi Roman</div>
        </div>
    </div>

    <!-- Filtreleme -->
    <div class="filter-section">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3 mb-3">
                <label for="search" class="form-label fw-bold">Ara</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Başlık veya açıklama..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label for="type" class="form-label fw-bold">Tür</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Tümü</option>
                    <option value="manga" <?php echo $filter_type === 'manga' ? 'selected' : ''; ?>>Manga</option>
                    <option value="comic" <?php echo $filter_type === 'comic' ? 'selected' : ''; ?>>Çizgi Roman</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label for="status" class="form-label fw-bold">Durum</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tümü</option>
                    <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Yayınlanan</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                    <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <button type="submit" class="btn btn-primary me-2">
                    🔍 Filtrele
                </button>
                <a href="content.php" class="btn btn-outline-secondary">
                    ❌ Temizle
                </a>
            </div>
            <div class="col-md-2 mb-3 text-end">
                <small class="text-muted"><?php echo $total_content; ?> sonuç bulundu</small>
            </div>
        </form>
    </div>

    <!-- İçerik Listesi -->
    <?php if (mysqli_num_rows($content_result) > 0): ?>
        <div class="content-grid">
            <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                <div class="content-card">
                    <div class="content-image">
                        <img src="../uploads/covers/<?php echo htmlspecialchars($content['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($content['title']); ?>"
                             onerror="this.src='../assets/images/no-image.svg'">
                        <div class="status-badge status-<?php echo $content['status']; ?>">
                            <?php 
                            $status_text = [
                                'published' => 'Yayınlandı',
                                'pending' => 'Beklemede',
                                'rejected' => 'Reddedildi'
                            ];
                            echo $status_text[$content['status']] ?? $content['status'];
                            ?>
                        </div>
                    </div>
                    
                    <div class="content-info">
                        <h3 class="content-title">
                            <?php echo htmlspecialchars($content['title']); ?>
                        </h3>
                        
                        <div class="content-meta">
                            <span class="badge bg-primary">
                                <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                            </span>
                            <span class="text-muted">
                                <?php echo date('d.m.Y', strtotime($content['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="content-stats">
                            <div class="stat-item">
                                <span class="text-danger">❤️</span>
                                <span><?php echo $content['like_count']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="text-primary">💬</span>
                                <span><?php echo $content['comment_count']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="text-warning">⭐</span>
                                <span><?php echo $content['favorite_count']; ?></span>
                            </div>
                            <?php if ($content['is_series']): ?>
                                <div class="stat-item">
                                    <span class="text-info">📖</span>
                                    <span><?php echo $content['chapter_count']; ?> Bölüm</span>
                                </div>
                            <?php endif; ?>
                        </div>
                            
                        <div class="content-actions">
                            <a href="../view.php?id=<?php echo $content['id']; ?>" 
                               class="btn-action btn btn-outline-primary" target="_blank">
                                👁️ Görüntüle
                            </a>
                            <a href="../edit-content.php?id=<?php echo $content['id']; ?>" 
                               class="btn-action btn btn-outline-warning">
                                ✏️ Düzenle
                            </a>
                            <?php if ($content['is_series']): ?>
                                <a href="chapters.php?content_id=<?php echo $content['id']; ?>" 
                                   class="btn-action btn btn-outline-info">
                                    📚 Bölümler
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Sayfalama -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="İçerik sayfalama" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                ◀️
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&type=<?php echo urlencode($filter_type); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                ▶️
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                📚
            </div>
            <h3 class="mb-3">Henüz İçerik Yok</h3>
            <p class="mb-4">
                <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                    Arama kriterlerinize uygun içerik bulunamadı.
                <?php else: ?>
                    Henüz hiç içerik yüklememişsiniz. İlk manga veya çizgi romanınızı yükleyin!
                <?php endif; ?>
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="upload.php" class="btn btn-primary btn-lg">
                    ➕ İlk İçeriğimi Yükle
                </a>
                <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)): ?>
                    <a href="content.php" class="btn btn-outline-secondary btn-lg">
                        ❌ Filtreleri Temizle
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Kartlara hover efekti
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.content-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 