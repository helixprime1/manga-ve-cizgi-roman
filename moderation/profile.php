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
$page_title = 'Profil';

// İstatistikleri al
$stats = [];
$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
$reported_comments_result = mysqli_query($conn, $reported_comments_query);
$stats['reported_comments'] = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

// Moderatör istatistikleri
$moderator_stats_query = "SELECT 
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ?) as total_actions,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND action = 'approve') as approvals,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND action = 'reject') as rejections,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND action = 'delete') as deletions,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND DATE(created_at) = CURDATE()) as today_actions,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_actions,
    (SELECT COUNT(*) FROM moderation_logs WHERE moderator_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_actions";

$moderator_stats_stmt = mysqli_prepare($conn, $moderator_stats_query);
$user_id = $moderator_user['id'];
mysqli_stmt_bind_param($moderator_stats_stmt, "iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($moderator_stats_stmt);
$moderator_stats_result = mysqli_stmt_get_result($moderator_stats_stmt);
$moderator_stats = mysqli_fetch_assoc($moderator_stats_result);

// Son aktiviteler
$recent_activities_query = "SELECT * FROM moderation_logs WHERE moderator_id = ? ORDER BY created_at DESC LIMIT 10";
$recent_activities_stmt = mysqli_prepare($conn, $recent_activities_query);
mysqli_stmt_bind_param($recent_activities_stmt, "i", $user_id);
mysqli_stmt_execute($recent_activities_stmt);
$recent_activities_result = mysqli_stmt_get_result($recent_activities_stmt);

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = sanitizeInput($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // E-posta kontrolü
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }
    
    // Şifre değişikliği kontrolü
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Mevcut şifrenizi giriniz.";
        } elseif (!password_verify($current_password, $moderator_user['password'])) {
            $errors[] = "Mevcut şifre yanlış.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Yeni şifre en az 6 karakter olmalıdır.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Yeni şifreler eşleşmiyor.";
        }
    }
    
    if (empty($errors)) {
        // Profili güncelle
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET email = ?, password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssi", $email, $hashed_password, $user_id);
        } else {
            $update_query = "UPDATE users SET email = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $email, $user_id);
        }
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = "Profil başarıyla güncellendi.";
            // Güncel bilgileri al
            $moderator_user = getCurrentUser();
        } else {
            $error_message = "Profil güncellenirken hata oluştu.";
        }
        
        mysqli_stmt_close($update_stmt);
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-content">
                        <!-- Başlık -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Profil</h4>
                            <div class="d-flex gap-2">
                                <a href="../profile.php" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Genel Profil
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

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Profil Bilgileri -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Profil Bilgileri</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Kullanıcı Adı</label>
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo htmlspecialchars($moderator_user['username']); ?>" 
                                                               readonly>
                                                        <small class="text-muted">Kullanıcı adı değiştirilemez</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Rol</label>
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo ucfirst($moderator_user['role']); ?>" 
                                                               readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">E-posta</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($moderator_user['email']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <hr>
                                            
                                            <h6>Şifre Değiştir (Opsiyonel)</h6>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Mevcut Şifre</label>
                                                <input type="password" class="form-control" name="current_password">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Yeni Şifre</label>
                                                        <input type="password" class="form-control" name="new_password">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                                                        <input type="password" class="form-control" name="confirm_password">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Güncelle
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Moderasyon İstatistikleri -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Moderasyon İstatistiklerim</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <div class="user-avatar-large mx-auto mb-2">
                                                <?php echo strtoupper(substr($moderator_user['username'], 0, 2)); ?>
                                            </div>
                                            <h5><?php echo htmlspecialchars($moderator_user['username']); ?></h5>
                                            <span class="badge bg-warning">Moderatör</span>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row text-center">
                                            <div class="col-6 mb-3">
                                                <h4 class="text-primary"><?php echo number_format($moderator_stats['total_actions']); ?></h4>
                                                <small class="text-muted">Toplam İşlem</small>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h4 class="text-success"><?php echo number_format($moderator_stats['approvals']); ?></h4>
                                                <small class="text-muted">Onay</small>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h4 class="text-danger"><?php echo number_format($moderator_stats['rejections']); ?></h4>
                                                <small class="text-muted">Red</small>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h4 class="text-warning"><?php echo number_format($moderator_stats['deletions']); ?></h4>
                                                <small class="text-muted">Silme</small>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="mb-2">
                                            <strong>Bugün:</strong> 
                                            <span class="float-end"><?php echo $moderator_stats['today_actions']; ?> işlem</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Bu Hafta:</strong> 
                                            <span class="float-end"><?php echo $moderator_stats['week_actions']; ?> işlem</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Bu Ay:</strong> 
                                            <span class="float-end"><?php echo $moderator_stats['month_actions']; ?> işlem</span>
                                        </div>
                                        
                                        <hr>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Katılım: <?php echo date('d.m.Y', strtotime($moderator_user['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Son Aktiviteler -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Son Aktivitelerim</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($recent_activities_result) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarih/Saat</th>
                                                    <th>Eylem</th>
                                                    <th>Hedef</th>
                                                    <th>Açıklama</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($activity = mysqli_fetch_assoc($recent_activities_result)): ?>
                                                    <tr>
                                                        <td>
                                                            <small>
                                                                <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $activity['action'] === 'approve' ? 'success' : 
                                                                    ($activity['action'] === 'reject' ? 'danger' : 
                                                                    ($activity['action'] === 'delete' ? 'warning' : 'secondary')); 
                                                            ?>">
                                                                <?php echo ucfirst($activity['action']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo ucfirst($activity['target_type']); ?> #<?php echo $activity['target_id']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small><?php echo htmlspecialchars($activity['description']); ?></small>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="logs.php?moderator=<?php echo $moderator_user['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-history me-1"></i>Tüm Aktivitelerim
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">Henüz aktivite yok</h6>
                                        <p class="text-muted">İlk moderasyon işleminizi yaptığınızda burada görünecek.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 24px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 