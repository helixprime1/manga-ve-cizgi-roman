<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

try {
    $content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$content_id) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz içerik ID']);
        exit;
    }

    // İçerik sorgusu
    $query = "SELECT c.*, u.username, u.email 
              FROM content c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Veritabanı sorgu hatası: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Sorgu çalıştırma hatası: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $content = mysqli_fetch_assoc($result);

    if (!$content) {
        echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı']);
        exit;
    }

    // HTML oluştur
    ob_start();
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle me-2"></i>İçerik Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Başlık:</strong></td>
                            <td><?php echo htmlspecialchars($content['title']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Yazar:</strong></td>
                            <td><?php echo htmlspecialchars($content['username']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>E-posta:</strong></td>
                            <td><?php echo htmlspecialchars($content['email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tür:</strong></td>
                            <td><?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?></td>
                        </tr>
                        <?php if (!empty($content['category'])): ?>
                        <tr>
                            <td><strong>Kategori:</strong></td>
                            <td><?php echo htmlspecialchars($content['category']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Durum:</strong></td>
                            <td>
                                <span class="badge bg-<?php echo $content['status'] === 'published' ? 'success' : ($content['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php 
                                    switch($content['status']) {
                                        case 'published': echo 'Yayınlanan'; break;
                                        case 'pending': echo 'Bekleyen'; break;
                                        case 'rejected': echo 'Reddedilen'; break;
                                        default: echo ucfirst($content['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Yükleme Tarihi:</strong></td>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($content['created_at'])); ?></td>
                        </tr>
                        <?php if (!empty($content['moderated_at'])): ?>
                        <tr>
                            <td><strong>Moderasyon Tarihi:</strong></td>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($content['moderated_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <?php if (isset($content['is_series']) && $content['is_series']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-layer-group me-2"></i>Bu içerik bir seri
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($content['is_mature']) && $content['is_mature']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>+18 İçerik
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-image me-2"></i>Kapak Resmi</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($content['cover_image'])): ?>
                        <img src="../../uploads/covers/<?php echo htmlspecialchars($content['cover_image']); ?>" 
                             class="img-fluid rounded" 
                             style="max-height: 300px; object-fit: cover;"
                             alt="Kapak"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div style="display: none;" class="text-muted">
                            <i class="fas fa-image fa-3x"></i>
                            <p>Kapak resmi yüklenemedi</p>
                            <small>Dosya: <?php echo htmlspecialchars($content['cover_image']); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">
                            <i class="fas fa-image fa-3x"></i>
                            <p>Kapak resmi yok</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-align-left me-2"></i>Açıklama</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($content['description'] ?? 'Açıklama bulunmuyor.')); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($content['tags'])): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-tags me-2"></i>Etiketler</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $tags = explode(',', $content['tags']);
                    foreach ($tags as $tag): 
                        $tag = trim($tag);
                        if (!empty($tag)):
                    ?>
                        <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($tag); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($content['moderation_reason'])): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Moderasyon Sebebi</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($content['moderation_reason'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <div class="btn-group" role="group">
            <?php if ($content['status'] === 'published'): ?>
                <a href="../../view.php?id=<?php echo $content['id']; ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Sitede Görüntüle
                </a>
            <?php endif; ?>
            
            <button class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i>Kapat
            </button>
        </div>
    </div>

    <?php
    $html = ob_get_clean();
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true, 
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log("Preview content error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?> 