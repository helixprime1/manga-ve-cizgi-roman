<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// Tür parametresi kontrolü
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;
if (!in_array($type, ['manga', 'comic'])) {
    header('Location: index.php');
    exit;
}

// Sıralama parametresi
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$allowed_sorts = ['newest', 'popular', 'az', 'za'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'newest';
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Sıralama sorgusu
$order_by = '';
switch ($sort) {
    case 'newest':
        $order_by = 'created_at DESC';
        break;
    case 'popular':
        $order_by = 'views DESC';
        break;
    case 'az':
        $order_by = 'title ASC';
        break;
    case 'za':
        $order_by = 'title DESC';
        break;
}

// Sayfalama için toplam içerik sayısı
$query = "SELECT COUNT(*) as total FROM content WHERE status = 'published' AND type = '$type'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_items = $row['total'];

// Sayfalama bilgileri
$items_per_page = 12;
$pagination = paginate($total_items, $items_per_page, $page);

// İçerikleri getir
$query = "SELECT * FROM content 
          WHERE status = 'published' AND type = '$type' 
          ORDER BY $order_by 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

$page_title = $type === 'manga' ? 'Mangalar' : 'Çizgi Romanlar';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, <?php echo $type === 'manga' ? '#f093fb, #f5576c' : '#4facfe, #00f2fe'; ?>);">
    <div class="container">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
                <i class="fas fa-<?php echo $type === 'manga' ? 'dragon' : 'mask'; ?> me-3"></i>
                <?php echo $type === 'manga' ? 'Mangalar' : 'Çizgi Romanlar'; ?>
            </h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                <?php echo $type === 'manga' ? 'Japon kültürünün en güzel örneklerini keşfedin' : 'Batı tarzı çizgi romanların büyülü dünyası'; ?>
            </p>
            <div class="mt-4" data-aos="fade-up" data-aos-delay="200">
                <span class="badge bg-white text-dark fs-6 px-3 py-2">
                    <i class="fas fa-book me-2"></i><?php echo number_format($total_items); ?> İçerik
                </span>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <!-- Filtreler ve Sıralama -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="d-flex flex-wrap gap-2" data-aos="fade-right">
                <button class="btn btn-outline-primary active" data-filter="all">
                    <i class="fas fa-th me-2"></i>Tümü
                </button>
                <button class="btn btn-outline-primary" data-filter="action">
                    <i class="fas fa-fist-raised me-2"></i>Aksiyon
                </button>
                <button class="btn btn-outline-primary" data-filter="romance">
                    <i class="fas fa-heart me-2"></i>Romantizm
                </button>
                <button class="btn btn-outline-primary" data-filter="fantasy">
                    <i class="fas fa-hat-wizard me-2"></i>Fantastik
                </button>
                <button class="btn btn-outline-primary" data-filter="horror">
                    <i class="fas fa-ghost me-2"></i>Korku
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dropdown" data-aos="fade-left">
                <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-sort me-2"></i>
                    <?php
                    $sort_text = [
                        'newest' => 'En Yeni',
                        'popular' => 'En Popüler',
                        'az' => 'A-Z',
                        'za' => 'Z-A'
                    ];
                    echo $sort_text[$sort];
                    ?>
                </button>
                <ul class="dropdown-menu w-100" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item <?php echo $sort === 'newest' ? 'active' : ''; ?>" href="?type=<?php echo $type; ?>&sort=newest">
                        <i class="fas fa-clock me-2"></i>En Yeni
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'popular' ? 'active' : ''; ?>" href="?type=<?php echo $type; ?>&sort=popular">
                        <i class="fas fa-fire me-2"></i>En Popüler
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'az' ? 'active' : ''; ?>" href="?type=<?php echo $type; ?>&sort=az">
                        <i class="fas fa-sort-alpha-down me-2"></i>A-Z
                    </a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'za' ? 'active' : ''; ?>" href="?type=<?php echo $type; ?>&sort=za">
                        <i class="fas fa-sort-alpha-up me-2"></i>Z-A
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if (count($items) > 0): ?>
        <div class="content-grid" id="contentGrid">
            <?php foreach ($items as $index => $item): ?>
                <div class="content-item" data-aos="fade-up" data-aos-delay="<?php echo ($index % 4) * 100; ?>" data-tags="<?php echo htmlspecialchars($item['tags']); ?>">
                    <div class="card h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="uploads/covers/<?php echo $item['cover_image']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge <?php echo $type === 'manga' ? 'badge-manga' : 'badge-comic'; ?>">
                                    <?php echo $type === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                </span>
                            </div>
                            <?php if ($item['featured']): ?>
                                <div class="position-absolute top-0 start-0 p-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star me-1"></i>Öne Çıkan
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="card-text"><?php echo mb_substr(htmlspecialchars($item['description']), 0, 100); ?>...</p>
                            
                            <?php if (!empty($item['tags'])): ?>
                                <div class="mb-3">
                                    <?php 
                                    $tags = explode(',', $item['tags']);
                                    foreach (array_slice($tags, 0, 3) as $tag): 
                                    ?>
                                        <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i> <?php echo number_format($item['views']); ?>
                                </small>
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
                                        <button type="button" class="btn btn-outline-secondary" data-action="like" data-content-id="<?php echo $item['id']; ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-action="favorite" data-content-id="<?php echo $item['id']; ?>">
                                            <i class="far fa-bookmark"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#shareModal" data-share-url="<?php echo SITE_URL . '/view.php?id=' . $item['id']; ?>">
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
                        <a class="page-link" href="?type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>" aria-label="Önceki">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php 
                    // Sayfa numaralarını göster
                    $start = max(1, $page - 2);
                    $end = min($pagination['total_pages'], $page + 2);
                    
                    if ($start > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=1">1</a>
                        </li>
                        <?php if ($start > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end < $pagination['total_pages']): ?>
                        <?php if ($end < $pagination['total_pages'] - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $pagination['total_pages']; ?>"><?php echo $pagination['total_pages']; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>" aria-label="Sonraki">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">Henüz bu kategoride içerik bulunmamaktadır</h3>
            <p class="text-muted mb-4">İlk içeriği siz yükleyin!</p>
            <?php if (isLoggedIn()): ?>
                <a href="upload.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-cloud-upload-alt me-2"></i>İçerik Yükle
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                </a>
            <?php endif; ?>
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
                        <button class="btn btn-primary" type="button" data-copy="" onclick="copyShareUrl()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Filtreleme
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    const contentItems = document.querySelectorAll('.content-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Aktif butonu güncelle
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // İçerikleri filtrele
            contentItems.forEach(item => {
                if (filter === 'all') {
                    item.style.display = '';
                } else {
                    const tags = item.dataset.tags.toLowerCase();
                    if (tags.includes(filter)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        });
    });
    
    // Share Modal
    const shareModal = document.getElementById('shareModal');
    shareModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const shareUrl = button.getAttribute('data-share-url');
        document.getElementById('shareUrl').value = shareUrl;
    });
});

function shareOn(platform) {
    const url = document.getElementById('shareUrl').value;
    const text = 'Bu içeriğe göz atın!';
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