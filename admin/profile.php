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
$page_title = 'Profil Ayarları';

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

// Profil güncelleme işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Kullanıcı adı kontrolü
        if (empty($username)) {
            $errors[] = 'Kullanıcı adı gereklidir';
        } elseif ($username !== $admin_user['username']) {
            $check_username = "SELECT id FROM users WHERE username = '$username' AND id != {$admin_user['id']}";
            $username_result = mysqli_query($conn, $check_username);
            if (mysqli_num_rows($username_result) > 0) {
                $errors[] = 'Bu kullanıcı adı zaten kullanılıyor';
            }
        }
        
        // E-posta kontrolü
        if (empty($email)) {
            $errors[] = 'E-posta adresi gereklidir';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi giriniz';
        } elseif ($email !== $admin_user['email']) {
            $check_email = "SELECT id FROM users WHERE email = '$email' AND id != {$admin_user['id']}";
            $email_result = mysqli_query($conn, $check_email);
            if (mysqli_num_rows($email_result) > 0) {
                $errors[] = 'Bu e-posta adresi zaten kullanılıyor';
            }
        }
        
        // Şifre kontrolü
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = 'Mevcut şifrenizi giriniz';
            } elseif (!password_verify($current_password, $admin_user['password'])) {
                $errors[] = 'Mevcut şifre hatalı';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'Yeni şifre en az 6 karakter olmalıdır';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'Şifre onayı eşleşmiyor';
            }
        }
        
        if (empty($errors)) {
            $update_fields = [];
            $update_fields[] = "username = '$username'";
            $update_fields[] = "email = '$email'";
            
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_fields[] = "password = '$hashed_password'";
            }
            
            $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = {$admin_user['id']}";
            
            if (mysqli_query($conn, $update_query)) {
                $message = 'Profil başarıyla güncellendi.';
                $message_type = 'success';
                
                // Session'ı güncelle
                $_SESSION['username'] = $username;
                $admin_user['username'] = $username;
                $admin_user['email'] = $email;
            } else {
                $message = 'Profil güncellenirken bir hata oluştu.';
                $message_type = 'danger';
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'danger';
        }
    }
}

// Admin aktivite istatistikleri
$admin_stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users_month,
        (SELECT COUNT(*) FROM content WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_content_month,
        (SELECT COUNT(*) FROM content WHERE status = 'pending') as pending_approvals,
        (SELECT COUNT(*) FROM system_logs WHERE user_id = {$admin_user['id']} AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as admin_actions_week
";
$admin_stats_result = mysqli_query($conn, $admin_stats_query);
$admin_stats = mysqli_fetch_assoc($admin_stats_result);

// Son admin aktiviteleri
$recent_activities_query = "
    SELECT * FROM system_logs 
    WHERE user_id = {$admin_user['id']} 
    ORDER BY created_at DESC 
    LIMIT 10
";
$recent_activities = mysqli_query($conn, $recent_activities_query);

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
                            <h1 class="h3 mb-0">Profil Ayarları</h1>
                            <p class="text-muted">Hesap bilgilerinizi yönetin</p>
                        </div>
                        <div>
                            <span class="badge bg-danger fs-6">
                                <i class="fas fa-shield-alt me-1"></i>Administrator
                            </span>
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
                <!-- Profil Bilgileri -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Profil Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">E-posta Adresi</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Şifre Değiştir</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="new_password" class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="confirm_password" class="form-label">Şifre Onayı</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Profil Özeti -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($admin_user['username']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($admin_user['email']); ?></p>
                            <span class="badge bg-danger mb-3">Administrator</span>
                            
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number text-primary"><?php echo $admin_user['id']; ?></div>
                                        <div class="stat-label">Kullanıcı ID</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number text-success"><?php echo $admin_stats['admin_actions_week']; ?></div>
                                        <div class="stat-label">Bu Hafta</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hesap Detayları -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white border-0">
                            <h6 class="card-title mb-0">Hesap Detayları</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Kayıt Tarihi:</strong></td>
                                    <td><?php echo date('d.m.Y', strtotime($admin_user['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Son Giriş:</strong></td>
                                    <td><?php echo date('d.m.Y H:i'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Rol:</strong></td>
                                    <td><span class="badge bg-danger">Admin</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Durum:</strong></td>
                                    <td><span class="badge bg-success">Aktif</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin İstatistikleri -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-primary"><?php echo $admin_stats['new_users_month']; ?></div>
                            <div class="stat-label">Bu Ay Yeni Kullanıcı</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-success"><?php echo $admin_stats['new_content_month']; ?></div>
                            <div class="stat-label">Bu Ay Yeni İçerik</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-warning"><?php echo $admin_stats['pending_approvals']; ?></div>
                            <div class="stat-label">Bekleyen Onay</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="stat-number text-info"><?php echo $admin_stats['admin_actions_week']; ?></div>
                            <div class="stat-label">Bu Hafta Aktivite</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Son Aktiviteler -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Son Aktivitelerim
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_activities) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($activity = mysqli_fetch_assoc($recent_activities)): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($activity['action']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></small>
                                            <br>
                                            <code style="font-size: 0.75rem;"><?php echo htmlspecialchars($activity['ip_address']); ?></code>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-history fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Henüz aktivite yok</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-item {
    padding: 0.5rem;
}

.avatar-lg {
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    border: 3px solid rgba(255, 255, 255, 0.2);
}
</style>

<?php require_once 'includes/footer.php'; ?> 