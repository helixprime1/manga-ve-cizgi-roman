<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Ayarlar';

require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$user = getCurrentUser();

$success_message = '';
$error_message = '';

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $error_message = "Güvenlik hatası. Lütfen tekrar deneyin.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $bio = sanitizeInput($_POST['bio']);
        
        // Kullanıcı adı kontrolü
        if (strlen($username) < 3) {
            $error_message = "Kullanıcı adı en az 3 karakter olmalıdır.";
        } elseif (strlen($username) > 50) {
            $error_message = "Kullanıcı adı en fazla 50 karakter olabilir.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error_message = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
        } else {
            // Başka kullanıcı tarafından kullanılıp kullanılmadığını kontrol et
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($check_stmt, "si", $username, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error_message = "Bu kullanıcı adı zaten kullanılıyor.";
            } else {
                // Email kontrolü
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = "Geçerli bir email adresi giriniz.";
                } elseif (strlen($email) > 100) {
                    $error_message = "Email adresi çok uzun.";
                } else {
                    // Başka kullanıcı tarafından kullanılıp kullanılmadığını kontrol et
                    $check_email_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
                    mysqli_stmt_bind_param($check_email_stmt, "si", $email, $user_id);
                    mysqli_stmt_execute($check_email_stmt);
                    $check_email_result = mysqli_stmt_get_result($check_email_stmt);
                    
                    if (mysqli_num_rows($check_email_result) > 0) {
                        $error_message = "Bu email adresi zaten kullanılıyor.";
                    } else {
                        // Bio uzunluk kontrolü
                        if (strlen($bio) > 500) {
                            $error_message = "Biyografi en fazla 500 karakter olabilir.";
                        } else {
                            // Avatar yükleme
                            $avatar_filename = $user['avatar'];
                            
                            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                                // Dosya güvenlik kontrolü
                                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                                $file_type = $_FILES['avatar']['type'];
                                $file_size = $_FILES['avatar']['size'];
                                
                                if (!in_array($file_type, $allowed_types)) {
                                    $error_message = "Sadece JPG, PNG ve GIF formatları kabul edilir.";
                                } elseif ($file_size > 2 * 1024 * 1024) { // 2MB
                                    $error_message = "Avatar dosyası en fazla 2MB olabilir.";
                                } else {
                                    $upload_result = uploadFile($_FILES['avatar'], '../uploads/avatars');
                                    
                                    if ($upload_result['success']) {
                                        // Eski avatarı sil
                                        if (!empty($user['avatar']) && file_exists('../uploads/avatars/' . $user['avatar'])) {
                                            unlink('../uploads/avatars/' . $user['avatar']);
                                        }
                                        $avatar_filename = $upload_result['file_name'];
                                    } else {
                                        $error_message = $upload_result['message'];
                                    }
                                }
                            }
                            
                            if (empty($error_message)) {
                                // Profili güncelle - Güvenli prepared statement
                                $update_stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ?, bio = ?, avatar = ?, updated_at = NOW() WHERE id = ?");
                                mysqli_stmt_bind_param($update_stmt, "ssssi", $username, $email, $bio, $avatar_filename, $user_id);
                                
                                if (mysqli_stmt_execute($update_stmt)) {
                                    $_SESSION['username'] = $username;
                                    $success_message = "Profil başarıyla güncellendi.";
                                    $user = getCurrentUser(); // Güncellenmiş bilgileri al
                                } else {
                                    $error_message = "Profil güncellenirken hata oluştu.";
                                }
                                mysqli_stmt_close($update_stmt);
                            }
                        }
                    }
                    mysqli_stmt_close($check_email_stmt);
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    }
}

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $error_message = "Güvenlik hatası. Lütfen tekrar deneyin.";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Rate limiting kontrolü
        $rate_limit_key = 'password_change_' . $user_id;
        if (isset($_SESSION[$rate_limit_key]) && $_SESSION[$rate_limit_key] > time() - 300) { // 5 dakika
            $error_message = "Şifre değiştirme işlemi için 5 dakika beklemelisiniz.";
        } else {
            // Mevcut şifreyi kontrol et
            if (!password_verify($current_password, $user['password'])) {
                $error_message = "Mevcut şifre yanlış.";
                $_SESSION[$rate_limit_key] = time();
            } elseif (strlen($new_password) < 8) {
                $error_message = "Yeni şifre en az 8 karakter olmalıdır.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
                $error_message = "Yeni şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Yeni şifreler uyuşmuyor.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = "Şifre başarıyla değiştirildi.";
                    unset($_SESSION[$rate_limit_key]); // Rate limit sıfırla
                } else {
                    $error_message = "Şifre değiştirilirken hata oluştu.";
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }
}

// İstatistikleri güvenli şekilde al
$content_count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM content WHERE user_id = ?");
mysqli_stmt_bind_param($content_count_stmt, "i", $user_id);
mysqli_stmt_execute($content_count_stmt);
$content_count_result = mysqli_stmt_get_result($content_count_stmt);
$content_count = mysqli_fetch_assoc($content_count_result)['count'];

$views_stmt = mysqli_prepare($conn, "SELECT SUM(views) as total_views FROM content WHERE user_id = ?");
mysqli_stmt_bind_param($views_stmt, "i", $user_id);
mysqli_stmt_execute($views_stmt);
$views_result = mysqli_stmt_get_result($views_stmt);
$total_views = mysqli_fetch_assoc($views_result)['total_views'] ?? 0;
?>

<div class="settings-page">
    <!-- Sayfa Başlığı -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>⚙️ Hesap Ayarları</h2>
            <p class="text-muted mb-0">Profil bilgilerinizi ve hesap ayarlarınızı yönetin</p>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ⚠️ <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profil Ayarları -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">👤 Profil Bilgileri</h5>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generateCSRFToken(); ?>">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="avatar-upload">
                                    <div class="avatar-preview">
                                        <?php if (!empty($user['avatar'])): ?>
                                            <img src="../uploads/avatars/<?php echo $user['avatar']; ?>" 
                                                 alt="Avatar" id="avatarPreview">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" id="avatarPreview">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="avatar-upload-btn">
                                        <input type="file" name="avatar" id="avatarInput" accept="image/*">
                                        <label for="avatarInput" class="btn btn-outline-primary btn-sm">
                                            📷 Değiştir
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Adresi</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Hakkında</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" 
                                              placeholder="Kendiniz hakkında kısa bir açıklama yazın..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                💾 Profili Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hesap Bilgileri -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">ℹ️ Hesap Bilgileri</h5>
                    
                    <div class="account-info">
                        <div class="info-item">
                            <label>Üyelik Tarihi</label>
                            <span><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Hesap Türü</label>
                            <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <label>Toplam İçerik</label>
                            <span><?php echo $content_count; ?> İçerik</span>
                        </div>
                        
                        <div class="info-item">
                            <label>Toplam Görüntülenme</label>
                            <span><?php echo number_format($total_views); ?> Görüntülenme</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Şifre Değiştirme -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">🔒 Şifre Değiştir</h5>
                    
                    <form method="POST">
                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generateCSRFToken(); ?>">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning">
                            🔑 Şifreyi Değiştir
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hesap İstatistikleri -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📊 Hesap İstatistikleri</h5>
                    
                    <?php
                    // İstatistikleri al
                    $stats_query = "
                        SELECT 
                            COUNT(*) as total_content,
                            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_content,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_content,
                            SUM(views) as total_views,
                            SUM(likes) as total_likes
                        FROM content 
                        WHERE user_id = $user_id
                    ";
                    $stats_result = mysqli_query($conn, $stats_query);
                    $stats = mysqli_fetch_assoc($stats_result);
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_content'] ?? 0; ?></div>
                            <div class="stat-label">Toplam İçerik</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['published_content'] ?? 0; ?></div>
                            <div class="stat-label">Yayınlanan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                            <div class="stat-label">Görüntülenme</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['total_likes'] ?? 0); ?></div>
                            <div class="stat-label">Beğeni</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-upload {
    position: relative;
    display: inline-block;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #e9ecef;
    margin-bottom: 1rem;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
}

.avatar-upload-btn input[type="file"] {
    display: none;
}

.account-info .info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.account-info .info-item:last-child {
    border-bottom: none;
}

.account-info .info-item label {
    font-weight: 500;
    color: #6c757d;
    margin: 0;
}

.account-info .info-item span {
    font-weight: 600;
    color: var(--dark-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<script>
// Avatar önizleme
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Avatar Preview">`;
        };
        reader.readAsDataURL(file);
    }
});

// Şifre doğrulama
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Şifreler uyuşmuyor');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 