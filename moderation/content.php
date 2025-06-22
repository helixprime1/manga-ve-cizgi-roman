<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    header('Location: ../login.php');
    exit;
}

$moderator_user = getCurrentUser();
$page_title = 'İçerik Yönetimi';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Durum filtresi
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$allowed_statuses = ['all', 'pending', 'published', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'all';
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// İçerik onaylama/reddetme işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $content_id = (int)$_POST['content_id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
    
    if (in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'published' : 'rejected';
        
        // İçeriği güncelle
        $stmt = mysqli_prepare($conn, "UPDATE content SET status = ?, moderated_by = ?, moderated_at = NOW(), moderation_reason = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sisi", $new_status, $moderator_user['id'], $reason, $content_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Kullanıcıya bildirim gönder
            $content_query = "SELECT user_id, title FROM content WHERE id = ?";
            $content_stmt = mysqli_prepare($conn, $content_query);
            mysqli_stmt_bind_param($content_stmt, "i", $content_id);
            mysqli_stmt_execute($content_stmt);
            $content_result = mysqli_stmt_get_result($content_stmt);
            $content_data = mysqli_fetch_assoc($content_result);
            
            if ($content_data) {
                $message = $action === 'approve' ? 
                    "'{$content_data['title']}' başlıklı içeriğiniz onaylandı ve yayınlandı." :
                    "'{$content_data['title']}' başlıklı içeriğiniz reddedildi." . (!empty($reason) ? " Sebep: $reason" : "");
                
                $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'moderation', 'İçerik Moderasyonu', ?, NOW())");
                mysqli_stmt_bind_param($notif_stmt, "is", $content_data['user_id'], $message);
                mysqli_stmt_execute($notif_stmt);
                mysqli_stmt_close($notif_stmt);
            }
            
            // Log kaydı
            $log_message = "İçerik #$content_id " . ($action === 'approve' ? 'onaylandı' : 'reddedildi');
            $log_stmt = mysqli_prepare($conn, "INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, description, created_at) VALUES (?, ?, 'content', ?, ?, NOW())");
            mysqli_stmt_bind_param($log_stmt, "isss", $moderator_user['id'], $action, $content_id, $log_message);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            mysqli_stmt_close($content_stmt);
            $success_message = $action === 'approve' ? 'İçerik başarıyla onaylandı.' : 'İçerik başarıyla reddedildi.';
        } else {
            $error_message = 'İşlem sırasında bir hata oluştu.';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// WHERE koşulu oluştur
$where_clause = "1=1";
if ($status_filter !== 'all') {
    $where_clause .= " AND c.status = '$status_filter'";
}

// Toplam içerik sayısı
$count_query = "SELECT COUNT(*) as total FROM content c WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];

// Sayfalama
$items_per_page = 20;
$pagination = paginate($total_items, $items_per_page, $page);

// İçerikleri getir
$query = "SELECT c.*, u.username, u.email,
          (SELECT username FROM users WHERE id = c.moderated_by) as moderated_by_username
          FROM content c 
          JOIN users u ON c.user_id = u.id 
          WHERE $where_clause 
          ORDER BY c.created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
                <!-- Başlık ve Filtreler -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>İçerik Yönetimi</h4>
                    <div class="btn-group" role="group">
                        <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Tümü (<?php echo $total_items; ?>)
                        </a>
                        <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            Bekleyen (<?php echo $stats['pending_content']; ?>)
                        </a>
                        <a href="?status=published" class="btn <?php echo $status_filter === 'published' ? 'btn-success' : 'btn-outline-success'; ?>">
                            Yayınlanan
                        </a>
                        <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            Reddedilen
                        </a>
                    </div>
                </div>

                <!-- Başarı/Hata Mesajları -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- İçerik Listesi -->
                <div class="card">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kapak</th>
                                            <th>Başlık</th>
                                            <th>Yazar</th>
                                            <th>Tür</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($content = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td>
                                                    <img src="../uploads/covers/<?php echo $content['cover_image']; ?>" 
                                                         class="img-thumbnail" 
                                                         style="width: 50px; height: 60px; object-fit: cover;" 
                                                         alt="Kapak"
                                                         onerror="this.src='../assets/images/no-image.svg';">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($content['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($content['description'], 0, 100)) . '...'; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($content['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($content['email']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $content['type'] === 'manga' ? 'bg-primary' : 'bg-info'; ?>">
                                                        <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                                    </span>
                                    <?php if ($content['is_series']): ?>
                                        <br><span class="badge bg-info">Seri</span>
                                    <?php endif; ?>
                                    <?php if (isset($content['is_mature']) && $content['is_mature']): ?>
                                        <br><span class="badge bg-warning text-dark">+18</span>
                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($content['status']) {
                                                        case 'published':
                                                            $status_class = 'success';
                                                            $status_text = 'Yayınlanan';
                                                            break;
                                                        case 'pending':
                                                            $status_class = 'warning';
                                                            $status_text = 'Bekleyen';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'danger';
                                                            $status_text = 'Reddedilen';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    <?php if (!empty($content['moderated_by_username'])): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($content['moderated_by_username']); ?> tarafından
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo date('d.m.Y', strtotime($content['created_at'])); ?>
                                                        <br>
                                                        <?php echo date('H:i', strtotime($content['created_at'])); ?>
                                                    </small>
                                                    <?php if ($content['moderated_at']): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Moderasyon: <?php echo date('d.m.Y H:i', strtotime($content['moderated_at'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <button class="btn btn-outline-primary btn-sm" 
                                                                onclick="viewContent(<?php echo $content['id']; ?>)">
                                                            <i class="fas fa-eye"></i> Görüntüle
                                                        </button>
                                                        
                                                        <?php if ($content['status'] === 'pending'): ?>
                                                            <button class="btn btn-outline-success btn-sm" 
                                                                    onclick="moderateContent(<?php echo $content['id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i> Onayla
                                                            </button>
                                                            <button class="btn btn-outline-danger btn-sm" 
                                                                    onclick="moderateContent(<?php echo $content['id']; ?>, 'reject')">
                                                                <i class="fas fa-times"></i> Reddet
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($content['status'] === 'published'): ?>
                                                            <a href="../view.php?id=<?php echo $content['id']; ?>" 
                                                               class="btn btn-outline-info btn-sm" target="_blank">
                                                                <i class="fas fa-external-link-alt"></i> Sitede Gör
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Sayfalama -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                                <nav aria-label="Sayfalama" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">Önceki</a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">İçerik bulunamadı</h5>
                                <p class="text-muted">Seçilen kriterlere uygun içerik bulunmamaktadır.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </div>
</div>

<!-- Moderasyon Modal -->
<div class="modal fade" id="moderationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moderationModalTitle">İçerik Moderasyonu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="moderationForm">
                <div class="modal-body">
                    <input type="hidden" name="content_id" id="moderationContentId">
                    <input type="hidden" name="action" id="moderationAction">
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Sebep (Opsiyonel)</label>
                        <textarea class="form-control" name="reason" id="reason" rows="3" 
                                  placeholder="Moderasyon sebebini belirtebilirsiniz..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Bu işlem kullanıcıya bildirim olarak gönderilecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="moderationSubmitBtn">Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- İçerik Görüntüleme Modal -->
<div class="modal fade" id="viewContentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İçerik Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contentPreview">
                <!-- İçerik buraya yüklenecek -->
            </div>
        </div>
    </div>
</div>

<script>
function moderateContent(contentId, action) {
    document.getElementById('moderationContentId').value = contentId;
    document.getElementById('moderationAction').value = action;
    
    const modal = document.getElementById('moderationModal');
    const title = document.getElementById('moderationModalTitle');
    const submitBtn = document.getElementById('moderationSubmitBtn');
    
    if (action === 'approve') {
        title.textContent = 'İçeriği Onayla';
        submitBtn.textContent = 'Onayla';
        submitBtn.className = 'btn btn-success';
    } else {
        title.textContent = 'İçeriği Reddet';
        submitBtn.textContent = 'Reddet';
        submitBtn.className = 'btn btn-danger';
    }
    
    new bootstrap.Modal(modal).show();
}

function viewContent(contentId) {
    const modal = new bootstrap.Modal(document.getElementById('viewContentModal'));
    const preview = document.getElementById('contentPreview');
    
    // Loading göster
    preview.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-3 text-muted">İçerik yükleniyor...</p>
        </div>
    `;
    modal.show();
    
    // AJAX ile içerik detaylarını getir
    fetch(`api/preview_content.php?id=${contentId}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                preview.innerHTML = data.html;
            } else {
                preview.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Hata</h6>
                        <p>${data.message || 'İçerik yüklenemedi.'}</p>
                        ${data.error ? `<small class="text-muted">Detay: ${data.error}</small>` : ''}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            preview.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Bağlantı Hatası</h6>
                    <p>Sunucuya bağlanırken bir hata oluştu.</p>
                    <small class="text-muted">Detay: ${error.message}</small>
                    <hr>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewContent(${contentId})">
                        <i class="fas fa-redo me-1"></i>Tekrar Dene
                    </button>
                </div>
            `;
        });
}
</script>

<?php require_once 'includes/footer.php'; ?> 