<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$user = getCurrentUser();
$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        
        // Kullanıcı adı kontrolü
        if (empty($username)) {
            $errors[] = "Kullanıcı adı gereklidir.";
        } elseif (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = "Kullanıcı adı 3-20 karakter arasında olmalıdır.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
        } elseif ($username !== $user['username']) {
            // Kullanıcı adı değiştirilmişse, daha önce alınmış mı kontrol et
            $query = "SELECT id FROM users WHERE username = '$username' AND id != {$user['id']}";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Bu kullanıcı adı zaten kullanılıyor.";
            }
        }
        
        // E-posta kontrolü
        if (empty($email)) {
            $errors[] = "E-posta adresi gereklidir.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Geçerli bir e-posta adresi girin.";
        } elseif ($email !== $user['email']) {
            // E-posta değiştirilmişse, daha önce kullanılmış mı kontrol et
            $query = "SELECT id FROM users WHERE email = '$email' AND id != {$user['id']}";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
            }
        }
        
        // Profil resmi kontrolü
        $avatar = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['avatar'], 'uploads/avatars');
            if ($upload['success']) {
                $avatar = $upload['file_name'];
            } else {
                $errors[] = $upload['message'];
            }
        }
        
        // Hata yoksa profili güncelle
        if (empty($errors)) {
            $query = "UPDATE users SET username = '$username', email = '$email', bio = '$bio', avatar = '$avatar' WHERE id = {$user['id']}";
            
            if (mysqli_query($conn, $query)) {
                $success = true;
                // Kullanıcı bilgilerini güncelle
                $user = getUserById($user['id']);
            } else {
                $errors[] = "Profil güncellenirken bir hata oluştu: " . mysqli_error($conn);
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Mevcut şifre kontrolü
        if (empty($current_password)) {
            $errors[] = "Mevcut şifre gereklidir.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Mevcut şifre yanlış.";
        }
        
        // Yeni şifre kontrolü
        if (empty($new_password)) {
            $errors[] = "Yeni şifre gereklidir.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }
        
        // Şifre onayı kontrolü
        if ($new_password !== $confirm_password) {
            $errors[] = "Yeni şifreler eşleşmiyor.";
        }
        
        // Hata yoksa şifreyi güncelle
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = '$hashed_password' WHERE id = {$user['id']}";
            
            if (mysqli_query($conn, $query)) {
                $success = true;
            } else {
                $errors[] = "Şifre güncellenirken bir hata oluştu: " . mysqli_error($conn);
            }
        }
    }
}

// Kullanıcının içeriklerini getir
$query = "SELECT * FROM content WHERE user_id = {$user['id']} ORDER BY created_at DESC";
$content_result = mysqli_query($conn, $query);

$contents = [];
while ($row = mysqli_fetch_assoc($content_result)) {
    $contents[] = $row;
}

require_once 'includes/header.php';
?>

<style>
/* Profile sayfası için optimize edilmiş stiller */
.profile-page {
    transform: translateZ(0);
    will-change: auto;
}

.profile-header {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    transform: translateZ(0);
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e5e7eb;
    transition: none; /* Titreme önleme */
    transform: translateZ(0);
}

.profile-avatar-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: bold;
    margin: 0 auto;
    position: relative;
    border: 4px solid #e5e7eb;
    transform: translateZ(0);
}

.profile-avatar-placeholder i {
    position: absolute;
    font-size: 4rem;
    opacity: 0.3;
}

.profile-avatar-placeholder span {
    position: relative;
    z-index: 2;
    font-size: 2.5rem;
    font-weight: 800;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.list-group {
    transform: translateZ(0);
}

.list-group-item {
    border: none;
    border-radius: 0.5rem !important;
    margin-bottom: 0.5rem;
    transition: background-color 0.2s ease;
    transform: translateZ(0);
}

.list-group-item:hover {
    background-color: rgba(99, 102, 241, 0.1);
    transform: none; /* Titreme önleme */
}

.list-group-item.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-color: transparent;
    transform: none;
}

.form-container {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transform: translateZ(0);
}

.tab-content {
    transform: translateZ(0);
}

.tab-pane {
    transform: translateZ(0);
}

/* Form elementleri için titreme önleme */
.form-control {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    transform: translateZ(0);
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
    transform: none;
}

.btn {
    transform: translateZ(0);
    transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

/* Tablo için optimizasyon */
.table {
    transform: translateZ(0);
}

.table tbody tr {
    transition: background-color 0.2s ease;
    transform: translateZ(0);
}

.table tbody tr:hover {
    background-color: rgba(99, 102, 241, 0.05);
    transform: none;
}

/* Alert animasyonlarını disable et */
.alert {
    transform: translateZ(0);
    animation: none;
}

/* Responsive optimizasyonlar */
@media (max-width: 768px) {
    .profile-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .form-container {
        padding: 1.5rem;
    }
    
    .profile-avatar,
    .profile-avatar-placeholder {
        width: 120px;
        height: 120px;
    }
    
    .profile-avatar-placeholder {
        font-size: 2rem;
    }
    
    .profile-avatar-placeholder i {
        font-size: 2.5rem;
    }
    
    .profile-avatar-placeholder span {
        font-size: 1.8rem;
    }
}

/* Smooth scroll optimizasyonu */
.tab-content .tab-pane {
    scroll-behavior: auto;
}

/* Hardware acceleration */
.container,
.row,
.col-md-4,
.col-md-8 {
    transform: translateZ(0);
}
</style>

<div class="container mt-4 profile-page">
    <div class="row">
        <div class="col-md-4">
            <div class="profile-header text-center">
                <?php if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])): ?>
                    <img src="uploads/avatars/<?php echo $user['avatar']; ?>" 
                         class="profile-avatar mb-3" alt="<?php echo $user['username']; ?>">
                <?php else: ?>
                    <div class="profile-avatar-placeholder mb-3">
                        <i class="fas fa-user"></i>
                        <span><?php echo strtoupper(substr($user['username'], 0, 2)); ?></span>
                    </div>
                <?php endif; ?>
                <h3><?php echo $user['username']; ?></h3>
                <p class="text-muted">Üyelik Tarihi: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                <?php if (!empty($user['bio'])): ?>
                    <p><?php echo nl2br($user['bio']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="list-group mb-4">
                <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profil Bilgileri</a>
                <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">Şifre Değiştir</a>
                <a href="#contents" class="list-group-item list-group-item-action" data-bs-toggle="list">İçeriklerim</a>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="profile">
                    <div class="form-container">
                        <h2 class="mb-4">Profil Bilgileri</h2>
                        
                        <?php if ($success && isset($_POST['action']) && $_POST['action'] === 'update_profile'): ?>
                            <div class="alert alert-success" role="alert">
                                Profil bilgileriniz başarıyla güncellendi.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'update_profile'): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Hakkımda</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="avatar" class="form-label">Profil Resmi</label>
                                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                                <div class="form-text">JPG, JPEG, PNG veya GIF. Maksimum 2MB.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </form>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="password">
                    <div class="form-container">
                        <h2 class="mb-4">Şifre Değiştir</h2>
                        
                        <?php if ($success && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                            <div class="alert alert-success" role="alert">
                                Şifreniz başarıyla değiştirildi.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">En az 6 karakter olmalıdır.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Yeni Şifre Tekrar</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
                        </form>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="contents">
                    <div class="form-container">
                        <h2 class="mb-4">İçeriklerim</h2>
                        
                        <?php if (count($contents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Tür</th>
                                            <th>Durum</th>
                                            <th>Görüntülenme</th>
                                            <th>Tarih</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contents as $item): ?>
                                            <tr>
                                                <td><?php echo $item['title']; ?></td>
                                                <td><?php echo $item['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?></td>
                                                <td>
                                                    <?php if ($item['status'] === 'published'): ?>
                                                        <span class="badge bg-success">Yayında</span>
                                                    <?php elseif ($item['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">İncelemede</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Reddedildi</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $item['views']; ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($item['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($item['status'] === 'published'): ?>
                                                        <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Görüntüle</a>
                                                    <?php endif; ?>
                                                    <a href="edit-content.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Düzenle</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <a href="upload.php" class="btn btn-primary">Yeni İçerik Yükle</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                Henüz içerik yüklemediniz.
                            </div>
                            <a href="upload.php" class="btn btn-primary">İçerik Yükle</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?> 