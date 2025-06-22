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
$page_title = 'Kullanıcı Yönetimi';

// İstatistikleri al
$stats = [];
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Kullanıcı işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'delete_user' && $user_id > 0 && $user_id !== $admin_user['id']) {
        // Kullanıcının içeriklerini sil
        $delete_content_query = "DELETE FROM content WHERE user_id = $user_id";
        mysqli_query($conn, $delete_content_query);
        
        // Kullanıcının yorumlarını sil
        $delete_comments_query = "DELETE FROM comments WHERE user_id = $user_id";
        mysqli_query($conn, $delete_comments_query);
        
        // Kullanıcının beğenilerini sil
        $delete_likes_query = "DELETE FROM likes WHERE user_id = $user_id";
        mysqli_query($conn, $delete_likes_query);
        
        // Kullanıcının favorilerini sil
        $delete_favorites_query = "DELETE FROM favorites WHERE user_id = $user_id";
        mysqli_query($conn, $delete_favorites_query);
        
        // Kullanıcıyı sil
        $delete_user_query = "DELETE FROM users WHERE id = $user_id";
        if (mysqli_query($conn, $delete_user_query)) {
            $message = 'Kullanıcı başarıyla silindi.';
            $message_type = 'success';
        } else {
            $message = 'Kullanıcı silinirken bir hata oluştu.';
            $message_type = 'danger';
        }
    } elseif ($action === 'toggle_role' && $user_id > 0 && $user_id !== $admin_user['id']) {
        $user_info = getUserById($user_id);
        $new_role = $user_info['role'] === 'admin' ? 'user' : 'admin';
        
        $update_query = "UPDATE users SET role = '$new_role' WHERE id = $user_id";
        if (mysqli_query($conn, $update_query)) {
            $message = 'Kullanıcı rolü başarıyla güncellendi.';
            $message_type = 'success';
        } else {
            $message = 'Rol güncellenirken bir hata oluştu.';
            $message_type = 'danger';
        }
    } elseif ($action === 'toggle_author' && $user_id > 0) {
        $user_info = getUserById($user_id);
        $new_author_status = $user_info['is_author'] ? 0 : 1;
        $approved_at = $new_author_status ? date('Y-m-d H:i:s') : null;
        
        $update_query = "UPDATE users SET is_author = $new_author_status, author_approved_at = " . 
                       ($approved_at ? "'$approved_at'" : "NULL") . " WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $action_text = $new_author_status ? 'verildi' : 'kaldırıldı';
            $message = "Yazar yetkisi başarıyla $action_text.";
            $message_type = 'success';
        } else {
            $message = 'Yazar yetkisi güncellenirken bir hata oluştu.';
            $message_type = 'danger';
        }
    }
}

// Kullanıcıları getir
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
if (!empty($search)) {
    $search_safe = sanitizeInput($search);
    $where_conditions[] = "(username LIKE '%$search_safe%' OR email LIKE '%$search_safe%')";
}

if (!empty($role_filter)) {
    $role_safe = sanitizeInput($role_filter);
    $where_conditions[] = "role = '$role_safe'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$order_clause = "ORDER BY $sort $order";

$users_query = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM content WHERE user_id = u.id) as content_count,
           (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
    FROM users u 
    $where_clause 
    $order_clause
";

$users_result = mysqli_query($conn, $users_query);

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Kullanıcı Yönetimi</h1>
                            <p class="text-muted">Tüm kullanıcıları görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-2"></i>Yeni Kullanıcı
                            </button>
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

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Arama</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Kullanıcı adı veya e-posta..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Rol</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">Tüm Roller</option>
                                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sıralama</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Kayıt Tarihi</option>
                                <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Kullanıcı Adı</option>
                                <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>E-posta</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filtrele
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Kullanıcılar 
                        <span class="badge bg-primary"><?php echo mysqli_num_rows($users_result); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 data-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>Yazar</th>
                                    <th>İçerik</th>
                                    <th>Yorum</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
                                                        <?php if ($user['id'] === $admin_user['id']): ?>
                                                            <small class="text-primary">Siz</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'Kullanıcı'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $user['is_author'] ? 'bg-success' : 'bg-light text-dark'; ?>">
                                                    <?php echo $user['is_author'] ? 'Yazar' : 'Normal'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo number_format($user['content_count']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo number_format($user['comment_count']); ?></span>
                                            </td>
                                            <td>
                                                <small><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($user['id'] !== $admin_user['id']): ?>
                                                    <div class="btn-group" role="group">
                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="Detayları Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAction('Yazar yetkisini değiştirmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="toggle_author">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $user['is_author'] ? 'btn-success' : 'btn-outline-success'; ?>" 
                                                    title="<?php echo $user['is_author'] ? 'Yazar Yetkisini Kaldır' : 'Yazar Yetkisi Ver'; ?>">
                                                <i class="fas fa-pen-fancy"></i>
                                            </button>
                                        </form>
                                        <?php if ($user['role'] !== 'admin' || $admin_user['role'] === 'admin'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAction('Kullanıcı rolünü değiştirmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="toggle_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Rolü Değiştir">
                                                <i class="fas fa-user-cog"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirmAction('Bu kullanıcıyı ve tüm verilerini silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Kullanıcıyı Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Kullanıcı bulunamadı</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/add_user.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user">Kullanıcı</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_author" name="is_author" value="1">
                            <label class="form-check-label" for="is_author">
                                Yazar yetkisi ver
                            </label>
                            <div class="form-text">Kullanıcı içerik yükleyebilir ve yönetebilir</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kullanıcı Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmAction(message) {
    return confirm(message);
}
</script>

<?php require_once 'includes/footer.php'; ?> 