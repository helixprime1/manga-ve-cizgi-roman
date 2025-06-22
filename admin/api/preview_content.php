<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$content_id = (int)($_GET['id'] ?? 0);

if ($content_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz içerik ID']);
    exit;
}

try {
    // İçerik bilgilerini al
    $content = getContentById($content_id);
    
    if (!$content) {
        echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı']);
        exit;
    }
    
    // İçerik sahibi bilgilerini al
    $author = getUserById($content['user_id']);
    
    // HTML oluştur
    ob_start();
    ?>
    
    <div class="row">
        <div class="col-md-4">
            <!-- Kapak Resmi -->
            <div class="text-center mb-3">
                <?php 
                $cover_image_path = '../../uploads/covers/' . $content['cover_image'];
                if (file_exists($cover_image_path)): ?>
                    <img src="<?php echo htmlspecialchars($cover_image_path); ?>" 
                         class="img-fluid rounded shadow" 
                         style="max-height: 400px;" 
                         alt="<?php echo htmlspecialchars($content['title']); ?>">
                <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- İçerik Bilgileri -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">İçerik Bilgileri</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?php echo $content['id']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tür:</strong></td>
                            <td>
                                <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                    <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Durum:</strong></td>
                            <td>
                                <span class="badge bg-warning">Bekliyor</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Yüklenme:</strong></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Görüntülenme:</strong></td>
                            <td><?php echo number_format($content['views']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Yazar Bilgileri -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Yazar Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                            <?php echo strtoupper(substr($author['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($author['username']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($author['email']); ?></small>
                        </div>
                    </div>
                    <small class="text-muted">
                        Kayıt: <?php echo date('d.m.Y', strtotime($author['created_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Başlık ve Açıklama -->
            <div class="mb-4">
                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
            </div>
            
            <!-- Etiketler -->
            <?php if (!empty($content['tags'])): ?>
                <div class="mb-4">
                    <h6>Etiketler:</h6>
                    <?php foreach (explode(',', $content['tags']) as $tag): ?>
                        <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tag)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- İçerik Dosyası -->
            <div class="mb-4">
                <h6>İçerik Dosyası:</h6>
                <?php
                $content_file_path = '../../uploads/content/' . $content['content_file'];
                $file_extension = strtolower(pathinfo($content['content_file'], PATHINFO_EXTENSION));
                
                if (file_exists($content_file_path)):
                    if ($file_extension === 'pdf'): ?>
                        <div class="border rounded p-3 bg-light">
                            <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                            <p class="mb-0">PDF Dosyası: <?php echo htmlspecialchars($content['content_file']); ?></p>
                            <a href="<?php echo htmlspecialchars($content_file_path); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-external-link-alt me-1"></i>Dosyayı Aç
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <img src="<?php echo htmlspecialchars($content_file_path); ?>" 
                                 class="img-fluid rounded border" 
                                 style="max-height: 500px;"
                                 alt="İçerik">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        İçerik dosyası bulunamadı: <?php echo htmlspecialchars($content['content_file']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- İşlem Butonları -->
            <div class="d-flex gap-2">
                <form method="POST" action="../pending.php" class="d-inline">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Bu içeriği onaylamak istediğinizden emin misiniz?')">
                        <i class="fas fa-check me-2"></i>Onayla
                    </button>
                </form>
                
                <button type="button" class="btn btn-warning" 
                        onclick="parent.showRejectModal(<?php echo $content['id']; ?>); parent.bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();">
                    <i class="fas fa-times me-2"></i>Reddet
                </button>
                
                <form method="POST" action="../pending.php" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Bu içeriği kalıcı olarak silmek istediğinizden emin misiniz?')">
                        <i class="fas fa-trash me-2"></i>Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    error_log('Preview Content API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İçerik önizlenirken bir hata oluştu'
    ]);
}
?>