<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// Arama sorgusu
$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

$items = [];
$total_results = 0;

if (!empty($query)) {
    // Arama yap
    $items = searchContent($query);
    $total_results = count($items);
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Arama Sonuçları</h1>
    
    <div class="mb-4">
        <form action="search.php" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Manga veya çizgi roman ara..." aria-label="Arama" aria-describedby="search-button">
                <button class="btn btn-primary" type="submit" id="search-button">Ara</button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($query)): ?>
        <div class="mb-3">
            <p>"<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" için <?php echo $total_results; ?> sonuç bulundu.</p>
        </div>
        
        <?php if ($total_results > 0): ?>
            <div class="row">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="uploads/covers/<?php echo htmlspecialchars($item['cover_image'], ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 100), ENT_QUOTES, 'UTF-8'); ?>...</p>
                                <span class="badge bg-primary"><?php echo $item['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?></span>
                            </div>
                            <div class="card-footer">
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">Oku</a>
                                <small class="text-muted float-end mt-1">
                                    <i class="fas fa-eye"></i> <?php echo $item['views']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Aramanızla eşleşen sonuç bulunamadı. Lütfen farklı anahtar kelimelerle tekrar deneyin.
            </div>
            
            <div class="mt-4">
                <h3>Öneriler:</h3>
                <ul>
                    <li>Farklı anahtar kelimeler deneyin</li>
                    <li>Daha genel terimler kullanın</li>
                    <li>Yazım hatalarını kontrol edin</li>
                </ul>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            Lütfen arama yapmak için bir anahtar kelime girin.
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?> 