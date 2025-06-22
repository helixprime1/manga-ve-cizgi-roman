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
$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    header('Location: users.php');
    exit;
}

// Kullanıcı bilgilerini al
$user = getUserById($user_id);

if (!$user) {
    $_SESSION['error'] = 'Kullanıcı bulunamadı.';
    header('Location: users.php');
    exit;
}

$page_title = 'Kullanıcı Detayları - ' . htmlspecialchars($user['username']);

// İşlemler
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_user' && $user_id !== $admin_user['id']) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? '');
        
        if (!empty($username) && !empty($email) && in_array($role, ['user', 'admin', 'moderator'])) {
            // Username ve email kontrolü
            $check_query = "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $user_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $message = 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.';
                $message_type = 'danger';
            } else {
                $update_query = "UPDATE users SET username = '$username', email = '$email', role = '$role' WHERE id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $message = 'Kullanıcı bilgileri başarıyla güncellendi.';
                    $message_type = 'success';
                    $user = getUserById($user_id); // Güncel bilgileri al
                } else {
                    $message = 'Güncelleme sırasında bir hata oluştu.';
                    $message_type = 'danger';
                }
            }
        } else {
            $message = 'Lütfen tüm alanları doğru şekilde doldurun.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'ban_user' && $user_id !== $admin_user['id']) {
        $ban_reason = sanitizeInput($_POST['ban_reason'] ?? '');
        
        if (!empty($ban_reason)) {
            $update_query = "UPDATE users SET is_banned = 1, banned_reason = '$ban_reason', banned_by = {$admin_user['id']}, banned_at = NOW() WHERE id = $user_id";
            if (mysqli_query($conn, $update_query)) {
                $message = 'Kullanıcı başarıyla banlandı.';
                $message_type = 'success';
                $user = getUserById($user_id);
            } else {
                $message = 'Ban işlemi sırasında bir hata oluştu.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Ban sebebi gereklidir.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'unban_user' && $user_id !== $admin_user['id']) {
        $update_query = "UPDATE users SET is_banned = 0, banned_reason = NULL, banned_by = NULL, banned_at = NULL WHERE id = $user_id";
        if (mysqli_query($conn, $update_query)) {
            $message = 'Kullanıcının banı başarıyla kaldırıldı.';
            $message_type = 'success';
            $user = getUserById($user_id);
        } else {
            $message = 'Ban kaldırma işlemi sırasında bir hata oluştu.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'reset_password' && $user_id !== $admin_user['id']) {
        $new_password = $_POST['new_password'] ?? '';
        
        if (strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            if (mysqli_query($conn, $update_query)) {
                $message = 'Şifre başarıyla sıfırlandı.';
                $message_type = 'success';
            } else {
                $message = 'Şifre sıfırlama sırasında bir hata oluştu.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Şifre en az 6 karakter olmalıdır.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'delete_user' && $user_id !== $admin_user['id']) {
        // Kullanıcının tüm verilerini sil
        $delete_content = "DELETE FROM content WHERE user_id = $user_id";
        $delete_comments = "DELETE FROM comments WHERE user_id = $user_id";
        $delete_likes = "DELETE FROM likes WHERE user_id = $user_id";
        $delete_favorites = "DELETE FROM favorites WHERE user_id = $user_id";
        $delete_user = "DELETE FROM users WHERE id = $user_id";
        
        mysqli_query($conn, $delete_content);
        mysqli_query($conn, $delete_comments);
        mysqli_query($conn, $delete_likes);
        mysqli_query($conn, $delete_favorites);
        
        if (mysqli_query($conn, $delete_user)) {
            $_SESSION['success'] = 'Kullanıcı ve tüm verileri başarıyla silindi.';
            header('Location: users.php');
            exit;
        } else {
            $message = 'Kullanıcı silinirken bir hata oluştu.';
            $message_type = 'danger';
        }
    }
}

// İstatistikleri al
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM content WHERE user_id = $user_id) as total_content,
        (SELECT COUNT(*) FROM content WHERE user_id = $user_id AND status = 'published') as published_content,
        (SELECT COUNT(*) FROM content WHERE user_id = $user_id AND status = 'pending') as pending_content,
        (SELECT COUNT(*) FROM comments WHERE user_id = $user_id) as total_comments,
        (SELECT SUM(views) FROM content WHERE user_id = $user_id) as total_views,
        (SELECT COUNT(*) FROM likes WHERE user_id = $user_id) as total_likes,
        (SELECT COUNT(*) FROM favorites WHERE user_id = $user_id) as total_favorites
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Kullanıcının içeriklerini al
$content_query = "SELECT * FROM content WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10";
$content_result = mysqli_query($conn, $content_query);

// Kullanıcının yorumlarını al
$comments_query = "SELECT cm.*, c.title as content_title FROM comments cm LEFT JOIN content c ON cm.content_id = c.id WHERE cm.user_id = $user_id ORDER BY cm.created_at DESC LIMIT 10";
$comments_result = mysqli_query($conn, $comments_query);

// Ban bilgilerini al (eğer banlıysa)
$ban_info = null;
if ($user['is_banned']) {
    $ban_query = "SELECT u.username as banned_by_username FROM users u WHERE u.id = {$user['banned_by']}";
    $ban_result = mysqli_query($conn, $ban_query);
    $ban_info = mysqli_fetch_assoc($ban_result);
}

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
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="users.php">Kullanıcılar</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($user['username']); ?></li>
                                </ol>
                            </nav>
                            <h1 class="h3 mb-0">Kullanıcı Detayları</h1>
                            <p class="text-muted">Kullanıcı bilgilerini görüntüleyin ve yönetin</p>
                        </div>
                        <div>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Geri Dön
                            </a>
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

            <div class="row">
                <!-- Sol Kolon - Kullanıcı Bilgileri -->
                <div class="col-lg-4 mb-4">
                    <!-- Kullanıcı Profili -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center">
                            <div class="user-avatar-large bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                            <h4 class="card-title mb-1"><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                            
                            <!-- Rol Badge -->
                            <span class="badge <?php 
                                echo $user['role'] === 'admin' ? 'bg-danger' : 
                                     ($user['role'] === 'moderator' ? 'bg-warning' : 'bg-secondary'); 
                            ?> mb-3 fs-6">
                                <i class="fas fa-<?php 
                                    echo $user['role'] === 'admin' ? 'crown' : 
                                         ($user['role'] === 'moderator' ? 'shield-alt' : 'user'); 
                                ?> me-1"></i>
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            
                            <!-- Ban Durumu -->
                            <?php if ($user['is_banned']): ?>
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-ban me-2"></i>
                                    <strong>Banlı Kullanıcı</strong>
                                    <hr>
                                    <small>
                                        <strong>Sebep:</strong> <?php echo htmlspecialchars($user['banned_reason']); ?><br>
                                        <strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($user['banned_at'])); ?><br>
                                        <?php if ($ban_info): ?>
                                            <strong>Banlayan:</strong> <?php echo htmlspecialchars($ban_info['banned_by_username']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <!-- İstatistikler -->
                            <div class="row text-center mt-4">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-primary h4 mb-0"><?php echo $stats['total_content']; ?></div>
                                        <div class="stat-label small text-muted">İçerik</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-success h4 mb-0"><?php echo $stats['total_comments']; ?></div>
                                        <div class="stat-label small text-muted">Yorum</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number text-info h4 mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                                        <div class="stat-label small text-muted">Görüntülenme</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hesap Detayları -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Hesap Detayları
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
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
                                        <?php if ($user['is_banned']): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-ban me-1"></i>Banlı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Hızlı İşlemler -->
                    <?php if ($user_id !== $admin_user['id']): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-cogs me-2 text-warning"></i>Hızlı İşlemler
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                        <i class="fas fa-edit me-2"></i>Bilgileri Düzenle
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                        <i class="fas fa-key me-2"></i>Şifre Sıfırla
                                    </button>
                                    <?php if ($user['is_banned']): ?>
                                        <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#unbanUserModal">
                                            <i class="fas fa-unlock me-2"></i>Banı Kaldır
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#banUserModal">
                                            <i class="fas fa-ban me-2"></i>Kullanıcıyı Banla
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                        <i class="fas fa-trash me-2"></i>Kullanıcıyı Sil
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sağ Kolon - İçerikler ve Aktiviteler -->
                <div class="col-lg-8">
                    <!-- İçerik İstatistikleri -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <div class="stat-number text-success h3 mb-1"><?php echo $stats['published_content']; ?></div>
                                    <div class="stat-label small text-muted">Yayınlanan</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <div class="stat-number text-warning h3 mb-1"><?php echo $stats['pending_content']; ?></div>
                                    <div class="stat-label small text-muted">Bekleyen</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <div class="stat-number text-info h3 mb-1"><?php echo $stats['total_likes']; ?></div>
                                    <div class="stat-label small text-muted">Beğeni</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <div class="stat-number text-purple h3 mb-1"><?php echo $stats['total_favorites']; ?></div>
                                    <div class="stat-label small text-muted">Favori</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Son İçerikler -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-book me-2 text-success"></i>Son İçerikler
                                <span class="badge bg-primary"><?php echo mysqli_num_rows($content_result); ?></span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($content_result) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1 me-3">
                                                    <h6 class="mb-1">
                                                        <a href="../view.php?id=<?php echo $content['id']; ?>" target="_blank" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($content['title']); ?>
                                                        </a>
                                                    </h6>
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
                                <?php if ($stats['total_content'] > 10): ?>
                                    <div class="text-center mt-3">
                                        <a href="content.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-primary btn-sm">
                                            Tüm İçerikleri Görüntüle (<?php echo $stats['total_content']; ?>)
                                        </a>
                                    </div>
                                <?php endif; ?>
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
                            <h6 class="card-title mb-0">
                                <i class="fas fa-comments me-2 text-warning"></i>Son Yorumlar
                                <span class="badge bg-primary"><?php echo mysqli_num_rows($comments_result); ?></span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($comments_result) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?php if ($comment['content_title']): ?>
                                                            <a href="../view.php?id=<?php echo $comment['content_id']; ?>" target="_blank" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($comment['content_title']); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Silinmiş İçerik</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars(substr($comment['comment'], 0, 150))); ?>...</p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php if ($stats['total_comments'] > 10): ?>
                                    <div class="text-center mt-3">
                                        <a href="comments.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-primary btn-sm">
                                            Tüm Yorumları Görüntüle (<?php echo $stats['total_comments']; ?>)
                                        </a>
                                    </div>
                                <?php endif; ?>
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
    </div>
</div>

<?php if ($user_id !== $admin_user['id']): ?>
<!-- Bilgileri Düzenle Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcı Bilgilerini Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                            <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>>Moderatör</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Şifre Sıfırla Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Şifre Sıfırla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu işlem kullanıcının şifresini kalıcı olarak değiştirecektir.
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                        <div class="form-text">Şifre en az 6 karakter olmalıdır.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">Şifreyi Sıfırla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ban Modal -->
<div class="modal fade" id="banUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcıyı Banla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="ban_user">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-ban me-2"></i>
                        Bu kullanıcı banlandığında sisteme giriş yapamayacaktır.
                    </div>
                    <div class="mb-3">
                        <label for="ban_reason" class="form-label">Ban Sebebi</label>
                        <textarea class="form-control" id="ban_reason" name="ban_reason" rows="3" required placeholder="Ban sebebini açıklayın..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Kullanıcıyı Banla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unban Modal -->
<div class="modal fade" id="unbanUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Banı Kaldır</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="unban_user">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-unlock me-2"></i>
                        Bu kullanıcının banı kaldırılacak ve sisteme tekrar giriş yapabilecektir.
                    </div>
                    <p>Bu işlemi onaylıyor musunuz?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Banı Kaldır</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kullanıcıyı Sil Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcıyı Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>DİKKAT!</strong> Bu işlem geri alınamaz!
                    </div>
                    <p>Bu kullanıcı ve aşağıdaki tüm verileri kalıcı olarak silinecektir:</p>
                    <ul>
                        <li>Tüm içerikleri (<?php echo $stats['total_content']; ?> adet)</li>
                        <li>Tüm yorumları (<?php echo $stats['total_comments']; ?> adet)</li>
                        <li>Tüm beğenileri ve favorileri</li>
                        <li>Hesap bilgileri</li>
                    </ul>
                    <p><strong>Bu işlemi onaylıyor musunuz?</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Evet, Sil</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 