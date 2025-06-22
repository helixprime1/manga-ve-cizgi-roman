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
$page_title = 'Kullanıcı Moderasyonu';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Filtreler
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$allowed_filters = ['all', 'active', 'banned', 'new'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'all';
}

// Sayfa parametresi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Kullanıcı işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
    
    if (in_array($action, ['ban', 'unban', 'warn', 'toggle_author'])) {
        if ($action === 'ban') {
            // Kullanıcıyı banla
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_banned = 1, banned_reason = ?, banned_by = ?, banned_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sii", $reason, $moderator_user['id'], $user_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $success_message = 'Kullanıcı başarıyla banlandı.';
            }
        } elseif ($action === 'unban') {
            // Banı kaldır
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_banned = 0, banned_reason = NULL, banned_by = NULL, banned_at = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $success_message = 'Kullanıcının banı kaldırıldı.';
            }
        } elseif ($action === 'warn') {
            // Kullanıcıya uyarı gönder
            $message = "Moderatörlerden bir uyarı aldınız." . (!empty($reason) ? " Sebep: $reason" : "");
            $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'warning', 'Moderatör Uyarısı', ?, NOW())");
            mysqli_stmt_bind_param($notif_stmt, "is", $user_id, $message);
            $success = mysqli_stmt_execute($notif_stmt);
            mysqli_stmt_close($notif_stmt);
            
            if ($success) {
                $success_message = 'Kullanıcıya uyarı gönderildi.';
            }
        } elseif ($action === 'toggle_author') {
            // Yazar yetkisini değiştir
            $user_info_stmt = mysqli_prepare($conn, "SELECT is_author FROM users WHERE id = ?");
            mysqli_stmt_bind_param($user_info_stmt, "i", $user_id);
            mysqli_stmt_execute($user_info_stmt);
            $user_info_result = mysqli_stmt_get_result($user_info_stmt);
            $user_info = mysqli_fetch_assoc($user_info_result);
            mysqli_stmt_close($user_info_stmt);
            
            $new_author_status = $user_info['is_author'] ? 0 : 1;
            $approved_at = $new_author_status ? "NOW()" : "NULL";
            
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET is_author = ?, author_approved_at = $approved_at WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "ii", $new_author_status, $user_id);
            $success = mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            if ($success) {
                $action_text = $new_author_status ? 'verildi' : 'kaldırıldı';
                $success_message = "Yazar yetkisi başarıyla $action_text.";
            }
        }
        
        // Log kaydı
        if (isset($success) && $success) {
            $log_message = "Kullanıcı #$user_id $action işlemi yapıldı";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO moderation_logs (moderator_id, action, target_type, target_id, description, created_at) VALUES (?, ?, 'user', ?, ?, NOW())");
            mysqli_stmt_bind_param($log_stmt, "isss", $moderator_user['id'], $action, $user_id, $log_message);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
}

// WHERE koşulu oluştur
$where_clauses = ["u.role != 'admin'"]; // Admin'leri listede gösterme
if ($filter === 'active') {
    $where_clauses[] = "u.is_banned = 0";
} elseif ($filter === 'banned') {
    $where_clauses[] = "u.is_banned = 1";
} elseif ($filter === 'new') {
    $where_clauses[] = "u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

if (!empty($search)) {
    $where_clauses[] = "(u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$where_clause = implode(' AND ', $where_clauses);

// Toplam kullanıcı sayısı
$count_query = "SELECT COUNT(*) as total FROM users u WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];

// Sayfalama
$items_per_page = 20;
$pagination = paginate($total_items, $items_per_page, $page);

// Kullanıcıları getir
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM content WHERE user_id = u.id) as content_count,
          (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count,
          (SELECT username FROM users WHERE id = u.banned_by) as banned_by_username
          FROM users u 
          WHERE $where_clause 
          ORDER BY u.created_at DESC 
          LIMIT {$pagination['start']}, {$pagination['per_page']}";
$result = mysqli_query($conn, $query);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
        <!-- Başlık ve Filtreler -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Kullanıcı Moderasyonu</h4>
            <div class="d-flex gap-2">
                <!-- Arama -->
                <form method="GET" class="d-flex">
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                    <input type="text" name="search" class="form-control" placeholder="Kullanıcı ara..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- Filtreler -->
                <div class="btn-group" role="group">
                    <a href="?filter=all&search=<?php echo urlencode($search); ?>" 
                       class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Tümü
                    </a>
                    <a href="?filter=active&search=<?php echo urlencode($search); ?>" 
                       class="btn <?php echo $filter === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">
                        Aktif
                    </a>
                    <a href="?filter=banned&search=<?php echo urlencode($search); ?>" 
                       class="btn <?php echo $filter === 'banned' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        Banlı
                    </a>
                    <a href="?filter=new&search=<?php echo urlencode($search); ?>" 
                       class="btn <?php echo $filter === 'new' ? 'btn-info' : 'btn-outline-info'; ?>">
                        Yeni (7 gün)
                    </a>
                </div>
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

        <!-- Kullanıcı Listesi -->
        <div class="card">
            <div class="card-body">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Rol</th>
                                    <th>Yazar</th>
                                    <th>Aktivite</th>
                                    <th>Durum</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'moderator' ? 'warning' : 
                                                    ($user['role'] === 'admin' ? 'danger' : 'secondary'); 
                                            ?>">
                                                <?php 
                                                switch($user['role']) {
                                                    case 'admin': echo 'Admin'; break;
                                                    case 'moderator': echo 'Moderatör'; break;
                                                    default: echo 'Kullanıcı'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['is_author'] ? 'bg-success' : 'bg-light text-dark'; ?>">
                                                <?php echo $user['is_author'] ? 'Yazar' : 'Normal'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fas fa-book me-1"></i><?php echo $user['content_count']; ?> İçerik<br>
                                                <i class="fas fa-comments me-1"></i><?php echo $user['comment_count']; ?> Yorum
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banlı</span>
                                                <?php if ($user['banned_by_username']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($user['banned_by_username']); ?> tarafından
                                                    </small>
                                                <?php endif; ?>
                                                <?php if ($user['banned_reason']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($user['banned_reason']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                                <br>
                                                <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical btn-group-sm" role="group">
                                                <a href="../profile.php?user=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" target="_blank">
                                                    <i class="fas fa-eye"></i> Profil
                                                </a>
                                                
                                                <button class="btn btn-outline-info btn-sm" 
                                                        onclick="moderateUser(<?php echo $user['id']; ?>, 'toggle_author')"
                                                        title="<?php echo $user['is_author'] ? 'Yazar Yetkisini Kaldır' : 'Yazar Yetkisi Ver'; ?>">
                                                    <i class="fas fa-pen-fancy"></i> 
                                                    <?php echo $user['is_author'] ? 'Yazar-' : 'Yazar+'; ?>
                                                </button>
                                                
                                                <?php if (!$user['is_banned'] && $user['role'] !== 'admin'): ?>
                                                    <button class="btn btn-outline-warning btn-sm" 
                                                            onclick="moderateUser(<?php echo $user['id']; ?>, 'warn')">
                                                        <i class="fas fa-exclamation-triangle"></i> Uyar
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="moderateUser(<?php echo $user['id']; ?>, 'ban')">
                                                        <i class="fas fa-ban"></i> Banla
                                                    </button>
                                                <?php elseif ($user['is_banned']): ?>
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="moderateUser(<?php echo $user['id']; ?>, 'unban')">
                                                        <i class="fas fa-check"></i> Banı Kaldır
                                                    </button>
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
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Önceki</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Sonraki</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Kullanıcı bulunamadı</h5>
                        <p class="text-muted">Arama kriterlerinize uygun kullanıcı bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Moderasyon Modal -->
<div class="modal fade" id="moderationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moderationModalTitle">Kullanıcı Moderasyonu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="moderationForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="moderationUserId">
                    <input type="hidden" name="action" id="moderationAction">
                    
                    <div class="alert alert-warning" id="confirmationMessage">
                        <!-- Onay mesajı buraya gelecek -->
                    </div>
                    
                    <div class="mb-3" id="reasonField">
                        <label for="reason" class="form-label">Sebep</label>
                        <textarea class="form-control" name="reason" id="reason" rows="3" 
                                  placeholder="Moderasyon sebebini belirtiniz..."></textarea>
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

<style>
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}
</style>

<script>
function moderateUser(userId, action) {
    document.getElementById('moderationUserId').value = userId;
    document.getElementById('moderationAction').value = action;
    
    const modal = document.getElementById('moderationModal');
    const title = document.getElementById('moderationModalTitle');
    const submitBtn = document.getElementById('moderationSubmitBtn');
    const confirmationMessage = document.getElementById('confirmationMessage');
    const reasonField = document.getElementById('reasonField');
    
    let actionText = '';
    let buttonClass = 'btn-primary';
    let message = '';
    let showReason = true;
    
    switch(action) {
        case 'warn':
            actionText = 'Uyar';
            buttonClass = 'btn-warning';
            message = 'Bu kullanıcıya uyarı gönderilecektir.';
            break;
        case 'ban':
            actionText = 'Banla';
            buttonClass = 'btn-danger';
            message = 'Bu kullanıcı banlanacak ve sisteme giriş yapamayacaktır.';
            break;
        case 'unban':
            actionText = 'Banı Kaldır';
            buttonClass = 'btn-success';
            message = 'Bu kullanıcının banı kaldırılacaktır.';
            showReason = false;
            break;
        case 'toggle_author':
            actionText = 'Yazar Yetkisini Değiştir';
            buttonClass = 'btn-info';
            message = 'Bu kullanıcının yazar yetkisi değiştirilecektir.';
            showReason = false;
            break;
    }
    
    title.textContent = `Kullanıcıyı ${actionText}`;
    submitBtn.textContent = actionText;
    submitBtn.className = `btn ${buttonClass}`;
    confirmationMessage.textContent = message;
    reasonField.style.display = showReason ? 'block' : 'none';
    
    new bootstrap.Modal(modal).show();
}
</script>

<?php require_once 'includes/footer.php'; ?> 