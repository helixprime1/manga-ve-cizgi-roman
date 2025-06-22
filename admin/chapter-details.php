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
$content_id = (int)($_GET['content_id'] ?? 0);

if ($content_id <= 0) {
    header('Location: content.php');
    exit;
}

// İçerik bilgilerini al
$content_query = "SELECT c.*, u.username FROM content c JOIN users u ON c.user_id = u.id WHERE c.id = ?";
$content_stmt = mysqli_prepare($conn, $content_query);
mysqli_stmt_bind_param($content_stmt, "i", $content_id);
mysqli_stmt_execute($content_stmt);
$content_result = mysqli_stmt_get_result($content_stmt);
$content = mysqli_fetch_assoc($content_result);
mysqli_stmt_close($content_stmt);

if (!$content || !$content['is_series']) {
    header('Location: content.php');
    exit;
}

$page_title = 'Bölüm Detayları - ' . $content['title'];

// Bölüm işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $chapter_id = (int)($_POST['chapter_id'] ?? 0);
    
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $message = 'Güvenlik hatası. Lütfen tekrar deneyin.';
        $message_type = 'danger';
    } elseif ($action === 'toggle_status' && $chapter_id > 0) {
        // Bölüm durumunu değiştir
        $chapter_query = "SELECT status FROM chapters WHERE id = ? AND content_id = ?";
        $chapter_stmt = mysqli_prepare($conn, $chapter_query);
        mysqli_stmt_bind_param($chapter_stmt, "ii", $chapter_id, $content_id);
        mysqli_stmt_execute($chapter_stmt);
        $chapter_result = mysqli_stmt_get_result($chapter_stmt);
        $chapter_info = mysqli_fetch_assoc($chapter_result);
        mysqli_stmt_close($chapter_stmt);
        
        if ($chapter_info) {
            $new_status = $chapter_info['status'] === 'published' ? 'draft' : 'published';
            
            $update_stmt = mysqli_prepare($conn, "UPDATE chapters SET status = ? WHERE id = ? AND content_id = ?");
            mysqli_stmt_bind_param($update_stmt, "sii", $new_status, $chapter_id, $content_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $message = 'Bölüm durumu başarıyla güncellendi.';
                $message_type = 'success';
            } else {
                $message = 'Durum güncellenirken bir hata oluştu.';
                $message_type = 'danger';
            }
            mysqli_stmt_close($update_stmt);
        }
    } elseif ($action === 'delete_chapter' && $chapter_id > 0) {
        // Bölümü sil
        $chapter_query = "SELECT chapter_files, chapter_number FROM chapters WHERE id = ? AND content_id = ?";
        $chapter_stmt = mysqli_prepare($conn, $chapter_query);
        mysqli_stmt_bind_param($chapter_stmt, "ii", $chapter_id, $content_id);
        mysqli_stmt_execute($chapter_stmt);
        $chapter_result = mysqli_stmt_get_result($chapter_stmt);
        $chapter_info = mysqli_fetch_assoc($chapter_result);
        mysqli_stmt_close($chapter_stmt);
        
        if ($chapter_info) {
            // Dosyaları sil
            $chapter_files = json_decode($chapter_info['chapter_files'], true);
            if (is_array($chapter_files)) {
                foreach ($chapter_files as $file) {
                    if (file_exists('../uploads/content/' . $file)) {
                        unlink('../uploads/content/' . $file);
                    }
                }
            }
            
            // Bölümü veritabanından sil
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM chapters WHERE id = ? AND content_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $chapter_id, $content_id);
            if (mysqli_stmt_execute($delete_stmt)) {
                $message = 'Bölüm ' . $chapter_info['chapter_number'] . ' başarıyla silindi.';
                $message_type = 'success';
            } else {
                $message = 'Bölüm silinirken bir hata oluştu.';
                $message_type = 'danger';
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// Bölümleri getir
$chapters_query = "SELECT * FROM chapters WHERE content_id = ? ORDER BY chapter_number ASC";
$chapters_stmt = mysqli_prepare($conn, $chapters_query);
mysqli_stmt_bind_param($chapters_stmt, "i", $content_id);
mysqli_stmt_execute($chapters_stmt);
$chapters_result = mysqli_stmt_get_result($chapters_stmt);
$chapters = [];
while ($chapter = mysqli_fetch_assoc($chapters_result)) {
    $chapters[] = $chapter;
}
mysqli_stmt_close($chapters_stmt);

require_once 'includes/header.php';
?>

<style>
.content-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.content-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="25" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="25" cy="75" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.content-header .content-cover {
    width: 80px;
    height: 100px;
    object-fit: cover;
    border-radius: 10px;
    border: 3px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.content-header .content-info {
    position: relative;
    z-index: 2;
}

.chapter-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.chapter-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.chapter-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.chapter-number {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.chapter-preview {
    max-height: 200px;
    overflow-y: auto;
    border: 2px dashed #e9ecef;
    border-radius: 10px;
    padding: 15px;
    background: #f8f9fa;
}

.chapter-preview img {
    max-width: 100px;
    max-height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin: 5px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.chapter-preview img:hover {
    transform: scale(1.05);
}

.stats-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.btn-modern {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-modern:hover::before {
    left: 100%;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-success-modern {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: white;
}

.btn-warning-modern {
    background: linear-gradient(135deg, #fa709a, #fee140);
    color: white;
}

.btn-danger-modern {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
}

.back-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.7rem 1.5rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.no-chapters {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.no-chapters i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Geri Dön Butonu -->
            <div class="mb-3">
                <a href="content.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    İçerik Yönetimine Dön
                </a>
            </div>

            <!-- İçerik Header -->
            <div class="content-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <?php 
                        $cover_path = '../uploads/covers/' . $content['cover_image'];
                        if (file_exists($cover_path)): ?>
                            <img src="<?php echo htmlspecialchars($cover_path); ?>" 
                                 class="content-cover" alt="<?php echo htmlspecialchars($content['title']); ?>">
                        <?php else: ?>
                            <div class="content-cover bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-image text-muted fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col content-info">
                        <h1 class="h3 mb-2"><?php echo htmlspecialchars($content['title']); ?></h1>
                        <p class="mb-2">
                            <i class="fas fa-user me-2"></i>
                            <strong>Yazar:</strong> <?php echo htmlspecialchars($content['username']); ?>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-tag me-2"></i>
                            <strong>Tür:</strong> 
                            <span class="badge bg-light text-dark"><?php echo $content['type'] === 'manga' ? 'Manga' : 'Comic'; ?></span>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Oluşturulma:</strong> <?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <div class="stats-card">
                            <div class="h4 mb-1"><?php echo count($chapters); ?></div>
                            <div class="small">Toplam Bölüm</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Bölümler -->
            <?php if (empty($chapters)): ?>
                <div class="no-chapters">
                    <i class="fas fa-book-open"></i>
                    <h4>Henüz Bölüm Yok</h4>
                    <p>Bu seri için henüz hiç bölüm eklenmemiş.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($chapters as $chapter): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="chapter-card card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="chapter-number me-3">
                                            <?php echo $chapter['chapter_number']; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($chapter['title']); ?></h5>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="badge <?php echo $chapter['status'] === 'published' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo $chapter['status'] === 'published' ? 'Yayında' : 'Taslak'; ?>
                                                </span>
                                                <?php if ($chapter['is_adult']): ?>
                                                    <span class="badge bg-danger">+18</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($chapter['description'])): ?>
                                        <p class="card-text small text-muted mb-3">
                                            <?php echo htmlspecialchars(substr($chapter['description'], 0, 100)); ?>
                                            <?php if (strlen($chapter['description']) > 100): ?>...<?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- İstatistikler -->
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <small class="text-muted d-block">Görüntülenme</small>
                                            <strong><?php echo number_format($chapter['views']); ?></strong>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Sayfa</small>
                                            <strong><?php echo count(json_decode($chapter['chapter_files'], true) ?: []); ?></strong>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Tarih</small>
                                            <strong><?php echo date('d.m.Y', strtotime($chapter['created_at'])); ?></strong>
                                        </div>
                                    </div>

                                    <!-- Sayfa Önizlemesi -->
                                    <?php 
                                    $chapter_files = json_decode($chapter['chapter_files'], true);
                                    if (is_array($chapter_files) && !empty($chapter_files)): 
                                    ?>
                                        <div class="chapter-preview mb-3">
                                            <small class="text-muted d-block mb-2">
                                                <i class="fas fa-images me-1"></i>Sayfa Önizlemesi (<?php echo count($chapter_files); ?> sayfa)
                                            </small>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach (array_slice($chapter_files, 0, 6) as $file): ?>
                                                    <?php $file_path = '../uploads/content/' . $file; ?>
                                                    <?php if (file_exists($file_path)): ?>
                                                        <img src="<?php echo htmlspecialchars($file_path); ?>" 
                                                             alt="Sayfa önizlemesi" class="img-thumbnail">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($chapter_files) > 6): ?>
                                                    <div class="d-flex align-items-center justify-content-center bg-light rounded" 
                                                         style="width: 100px; height: 120px; margin: 5px;">
                                                        <small class="text-muted">+<?php echo count($chapter_files) - 6; ?> daha</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- İşlem Butonları -->
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="../view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $chapter['chapter_number']; ?>" 
                                           target="_blank" class="btn btn-primary-modern btn-modern btn-sm flex-grow-1">
                                            <i class="fas fa-eye me-1"></i>Görüntüle
                                        </a>
                                        
                                        <form method="POST" class="d-inline">
                                            <?php echo getCSRFTokenInput(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                            <button type="submit" class="btn btn-warning-modern btn-modern btn-sm" 
                                                    onclick="return confirm('Bölüm durumunu değiştirmek istediğinizden emin misiniz?')"
                                                    title="Durum Değiştir">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="d-inline">
                                            <?php echo getCSRFTokenInput(); ?>
                                            <input type="hidden" name="action" value="delete_chapter">
                                            <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                            <button type="submit" class="btn btn-danger-modern btn-modern btn-sm" 
                                                    onclick="return confirm('Bölüm <?php echo $chapter['chapter_number']; ?>\'i silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')"
                                                    title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Sayfa yüklendiğinde animasyonları başlat
document.addEventListener('DOMContentLoaded', function() {
    // Chapter kartlarını sırayla görünür yap
    const cards = document.querySelectorAll('.chapter-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Alert otomatik kapatma
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('show')) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 5000);
</script>

<?php require_once 'includes/footer.php'; ?> 