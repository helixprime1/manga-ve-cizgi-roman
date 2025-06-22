<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Sıralama parametresi
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'views';
$allowed_sorts = ['views', 'likes', 'comments', 'recent'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'views';
}

// Tür filtresi
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';
$allowed_types = ['all', 'manga', 'comic'];
if (!in_array($type_filter, $allowed_types)) {
    $type_filter = 'all';
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// WHERE koşulu
$where_clause = "c.status = 'published'";
if ($type_filter !== 'all') {
    $where_clause .= " AND c.type = '$type_filter'";
}

// Sıralama sorgusu
$order_by = '';
switch ($sort) {
    case 'views':
        $order_by = 'c.views DESC';
        break;
    case 'likes':
        $order_by = 'like_count DESC';
        break;
    case 'comments':
        $order_by = 'comment_count DESC';
        break;
    case 'recent':
        $order_by = 'c.created_at DESC';
        break;
}

// Toplam içerik sayısı
$query = "SELECT COUNT(*) as total FROM content c WHERE $where_clause";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_items = $row['total'];

// Sayfalama bilgileri
$items_per_page = 12;
$pagination = paginate($total_items, $items_per_page, $page);

// İçerikleri getir
$query = "SELECT c.*, u.username,
          (SELECT COUNT(*) FROM likes WHERE content_id = c.id) as like_count,
          (SELECT COUNT(*) FROM comments WHERE content_id = c.id) as comment_count,
          (SELECT COUNT(*) FROM favorites WHERE content_id = c.id) as favorite_count
          FROM content c 
          JOIN users u ON c.user_id = u.id 
          WHERE $where_clause 
          ORDER BY $order_by 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

$page_title = 'Popüler İçerikler';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #ff6b6b, #feca57);">
    <div class="container">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
                <i class="fas fa-fire me-3"></i>Popüler İçerikler
            </h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                En çok beğenilen ve görüntülenen manga ve çizgi romanları keşfedin
            </p>
            <div class="mt-4" data-aos="fade-up" data-aos-delay="200">
                <span class="badge bg-white text-dark fs-6 px-3 py-2">
                    <i class="fas fa-chart-line me-2"></i><?php echo number_format($total_items); ?> Popüler İçerik
                </span>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <!-- Filtreler ve Sıralama -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Tür Filtresi</h6>
                    <div class="btn-group w-100" role="group">
                        <a href="?type=all&sort=<?php echo $sort; ?>" 
                           class="btn <?php echo $type_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-th me-2"></i>Tümü
                        </a>
                        <a href="?type=manga&sort=<?php echo $sort; ?>" 
                           class="btn <?php echo $type_filter === 'manga' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-dragon me-2"></i>Manga
                        </a>
                        <a href="?type=comic&sort=<?php echo $sort; ?>" 
                           class="btn <?php echo $type_filter === 'comic' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-mask me-2"></i>Çizgi Roman
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Sıralama</h6>
                    <div class="btn-group w-100" role="group">
                        <a href="?type=<?php echo $type_filter; ?>&sort=views" 
                           class="btn <?php echo $sort === 'views' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="fas fa-eye me-1"></i>Görüntülenme
                        </a>
                        <a href="?type=<?php echo $type_filter; ?>&sort=likes" 
                           class="btn <?php echo $sort === 'likes' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="fas fa-heart me-1"></i>Beğeni
                        </a>
                        <a href="?type=<?php echo $type_filter; ?>&sort=comments" 
                           class="btn <?php echo $sort === 'comments' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="fas fa-comment me-1"></i>Yorum
                        </a>
                        <a href="?type=<?php echo $type_filter; ?>&sort=recent" 
                           class="btn <?php echo $sort === 'recent' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                            <i class="fas fa-clock me-1"></i>Tarih
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($items) > 0): ?>
        <div class="content-grid" id="contentGrid">
            <?php foreach ($items as $index => $item): ?>
                <div class="content-item" data-aos="fade-up" data-aos-delay="<?php echo ($index % 4) * 100; ?>">
                    <div class="card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="uploads/covers/<?php echo $item['cover_image']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 onerror="this.src='assets/images/no-image.svg'">
                            
                            <!-- Popülerlik Badge'i -->
                            <div class="position-absolute top-0 start-0 p-2">
                                <?php 
                                $rank = $index + 1 + ($page - 1) * $items_per_page;
                                $badge_class = 'bg-warning';
                                if ($rank <= 3) $badge_class = 'bg-danger';
                                elseif ($rank <= 10) $badge_class = 'bg-warning';
                                else $badge_class = 'bg-info';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="fas fa-trophy me-1"></i>#<?php echo $rank; ?>
                                </span>
                            </div>
                            
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge <?php echo $item['type'] === 'manga' ? 'badge-manga' : 'badge-comic'; ?>">
                                    <?php echo $item['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="card-text"><?php echo mb_substr(htmlspecialchars($item['description']), 0, 100); ?>...</p>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>Yazar: <?php echo htmlspecialchars($item['username']); ?>
                                </small>
                            </div>
                            
                            <!-- İstatistikler -->
                            <div class="row text-center mb-3">
                                <div class="col-3">
                                    <div class="text-primary">
                                        <i class="fas fa-eye"></i>
                                        <div class="small fw-bold"><?php echo number_format($item['views']); ?></div>
                                        <div class="small text-muted">Görüntülenme</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-danger">
                                        <i class="fas fa-heart"></i>
                                        <div class="small fw-bold"><?php echo number_format($item['like_count']); ?></div>
                                        <div class="small text-muted">Beğeni</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-info">
                                        <i class="fas fa-comment"></i>
                                        <div class="small fw-bold"><?php echo number_format($item['comment_count']); ?></div>
                                        <div class="small text-muted">Yorum</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-warning">
                                        <i class="fas fa-bookmark"></i>
                                        <div class="small fw-bold"><?php echo number_format($item['favorite_count']); ?></div>
                                        <div class="small text-muted">Favori</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i> <?php echo date('d.m.Y', strtotime($item['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-book-open me-2"></i>Oku
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                data-action="like" data-content-id="<?php echo $item['id']; ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                data-action="favorite" data-content-id="<?php echo $item['id']; ?>">
                                            <i class="far fa-bookmark"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#shareModal" 
                                                data-share-url="<?php echo SITE_URL . '/view.php?id=' . $item['id']; ?>">
                                            <i class="fas fa-share-alt"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Sayfalama" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?type=<?php echo $type_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>" aria-label="Önceki">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($pagination['total_pages'], $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?type=<?php echo $type_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?type=<?php echo $type_filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>" aria-label="Sonraki">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-chart-line fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">Popüler içerik bulunamadı</h3>
            <p class="text-muted mb-4">Seçtiğiniz kriterlere uygun popüler içerik bulunmamaktadır.</p>
            <a href="category.php?type=manga" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-dragon me-2"></i>Mangaları Keşfet
            </a>
            <a href="category.php?type=comic" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-mask me-2"></i>Çizgi Romanları Keşfet
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Paylaş</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="shareOn('facebook')">
                        <i class="fab fa-facebook-f me-2"></i>Facebook'ta Paylaş
                    </button>
                    <button class="btn btn-outline-info" onclick="shareOn('twitter')">
                        <i class="fab fa-twitter me-2"></i>Twitter'da Paylaş
                    </button>
                    <button class="btn btn-outline-success" onclick="shareOn('whatsapp')">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp'ta Paylaş
                    </button>
                    <hr>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareUrl" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyShareUrl()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Share Modal
document.addEventListener('DOMContentLoaded', function() {
    const shareModal = document.getElementById('shareModal');
    shareModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const shareUrl = button.getAttribute('data-share-url');
        document.getElementById('shareUrl').value = shareUrl;
    });
});

function shareOn(platform) {
    const url = document.getElementById('shareUrl').value;
    const text = 'Bu popüler içeriğe göz atın!';
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
            break;
    }
    
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

function copyShareUrl() {
    const urlInput = document.getElementById('shareUrl');
    urlInput.select();
    document.execCommand('copy');
    
    const copyButton = event.target.closest('button');
    const originalHtml = copyButton.innerHTML;
    copyButton.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => {
        copyButton.innerHTML = originalHtml;
    }, 2000);
}
</script>

<?php
require_once 'includes/footer.php';
?> 