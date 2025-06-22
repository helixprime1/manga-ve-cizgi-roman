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

$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit;
}

try {
    // Kullanıcı bilgilerini al
    $user = getUserById($user_id);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }
    
    // Kullanıcının içeriklerini al
    $content_query = "SELECT * FROM content WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10";
    $content_result = mysqli_query($conn, $content_query);
    
    // Kullanıcının yorumlarını al
    $comments_query = "SELECT cm.*, c.title as content_title FROM comments cm JOIN content c ON cm.content_id = c.id WHERE cm.user_id = $user_id ORDER BY cm.created_at DESC LIMIT 10";
    $comments_result = mysqli_query($conn, $comments_query);
    
    // İstatistikleri hesapla
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM content WHERE user_id = $user_id) as total_content,
            (SELECT COUNT(*) FROM content WHERE user_id = $user_id AND status = 'published') as published_content,
            (SELECT COUNT(*) FROM content WHERE user_id = $user_id AND status = 'pending') as pending_content,
            (SELECT COUNT(*) FROM comments WHERE user_id = $user_id) as total_comments,
            (SELECT SUM(views) FROM content WHERE user_id = $user_id) as total_views
    ";
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    
    // HTML oluştur
    ob_start();
    ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4 col-md-12 mb-4">
                <!-- Kullanıcı Bilgileri -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body text-center">
                        <div class="user-avatar-large bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?> mb-3">
                            <?php echo $user['role'] === 'admin' ? 'Admin' : 'Kullanıcı'; ?>
                        </span>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-primary h5 mb-0"><?php echo $stats['total_content']; ?></div>
                                    <div class="stat-label small text-muted">İçerik</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-success h5 mb-0"><?php echo $stats['total_comments']; ?></div>
                                    <div class="stat-label small text-muted">Yorum</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <div class="stat-number text-info h5 mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                                    <div class="stat-label small text-muted">Görüntülenme</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hesap Detayları -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Hesap Detayları</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td><span class="text-primary">#<?php echo $user['id']; ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kayıt Tarihi:</strong></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Son Güncelleme:</strong></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user['updated_at'] ?? $user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Durum:</strong></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Aktif
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8 col-md-12">
                <!-- İçerik İstatistikleri -->
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body py-3">
                                <div class="stat-number text-success h4 mb-1"><?php echo $stats['published_content']; ?></div>
                                <div class="stat-label small text-muted">Yayınlanan</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body py-3">
                                <div class="stat-number text-warning h4 mb-1"><?php echo $stats['pending_content']; ?></div>
                                <div class="stat-label small text-muted">Bekleyen</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body py-3">
                                <div class="stat-number text-danger h4 mb-1"><?php echo $stats['total_content'] - $stats['published_content'] - $stats['pending_content']; ?></div>
                                <div class="stat-label small text-muted">Reddedilen</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Son İçerikler -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-0">
                        <h6 class="card-title mb-0"><i class="fas fa-book me-2 text-success"></i>Son İçerikler</h6>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($content_result) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1 me-2">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($content['title']); ?></h6>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-calendar me-1"></i><?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?> - 
                                                    <i class="fas fa-eye me-1"></i><?php echo number_format($content['views']); ?> görüntülenme
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?> mb-1 d-block">
                                                    <i class="fas fa-<?php echo $content['type'] === 'manga' ? 'book' : 'images'; ?> me-1"></i>
                                                    <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                                </span>
                                                <span class="badge <?php 
                                                    echo $content['status'] === 'published' ? 'bg-success' : 
                                                         ($content['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                ?> d-block">
                                                    <i class="fas fa-<?php 
                                                        echo $content['status'] === 'published' ? 'check-circle' : 
                                                             ($content['status'] === 'pending' ? 'clock' : 'times-circle'); 
                                                    ?> me-1"></i>
                                                    <?php 
                                                        echo $content['status'] === 'published' ? 'Yayınlandı' : 
                                                             ($content['status'] === 'pending' ? 'Bekliyor' : 'Reddedildi'); 
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Henüz içerik yok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Son Yorumlar -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h6 class="card-title mb-0"><i class="fas fa-comments me-2 text-warning"></i>Son Yorumlar</h6>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($comments_result) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($comment['content_title']); ?></h6>
                                                <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars(substr($comment['comment'], 0, 100))); ?>...</p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Henüz yorum yok</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    error_log('Get User API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kullanıcı bilgileri yüklenirken bir hata oluştu'
    ]);
}
?> 