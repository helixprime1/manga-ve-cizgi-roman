<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    header('Location: login.php?redirect=edit-content.php');
    exit;
}

// İçerik ID'si kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my-content.php');
    exit;
}

$content_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// İçeriği getir ve kullanıcıya ait olduğunu kontrol et
$query = "SELECT * FROM content WHERE id = $content_id AND user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    header('Location: my-content.php');
    exit;
}

$content = mysqli_fetch_assoc($result);

$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? '');
    $tags = sanitizeInput($_POST['tags'] ?? '');
    
    // Başlık kontrolü
    if (empty($title)) {
        $errors[] = "Başlık gereklidir.";
    } elseif (strlen($title) < 3 || strlen($title) > 200) {
        $errors[] = "Başlık 3-200 karakter arasında olmalıdır.";
    }
    
    // Açıklama kontrolü
    if (empty($description)) {
        $errors[] = "Açıklama gereklidir.";
    } elseif (strlen($description) < 10 || strlen($description) > 2000) {
        $errors[] = "Açıklama 10-2000 karakter arasında olmalıdır.";
    }
    
    // Tür kontrolü
    if (!in_array($type, ['manga', 'comic'])) {
        $errors[] = "Geçerli bir tür seçin.";
    }
    
    // Kapak resmi yüklendi mi kontrol et
    $cover_image = $content['cover_image']; // Mevcut kapak resmini koru
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_file = $_FILES['cover_image'];
        
        // Dosya boyutu kontrolü
        if ($cover_file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = "Kapak resmi çok büyük (maksimum " . (UPLOAD_MAX_SIZE / 1024 / 1024) . "MB).";
        }
        
        // Dosya türü kontrolü
        $cover_extension = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
        $allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($cover_extension, $allowed_image_extensions)) {
            $errors[] = "Kapak resmi için sadece JPG, JPEG, PNG veya GIF dosyaları kabul edilir.";
        }
        
        if (empty($errors)) {
            // Eski kapak resmini sil
            if (file_exists('uploads/covers/' . $content['cover_image'])) {
                unlink('uploads/covers/' . $content['cover_image']);
            }
            
            // Yeni kapak resmini yükle
            $cover_image = time() . '_cover_' . $cover_file['name'];
            if (!move_uploaded_file($cover_file['tmp_name'], 'uploads/covers/' . $cover_image)) {
                $errors[] = "Kapak resmi yüklenirken bir hata oluştu.";
            }
        }
    }
    
    // İçerik dosyası yüklendi mi kontrol et
    $content_file = $content['content_file']; // Mevcut içerik dosyasını koru
    if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['content_file'];
        
        // Dosya boyutu kontrolü
        if ($uploaded_file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = "İçerik dosyası çok büyük (maksimum " . (UPLOAD_MAX_SIZE / 1024 / 1024) . "MB).";
        }
        
        // Dosya türü kontrolü
        $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
            $errors[] = "Sadece " . implode(', ', ALLOWED_EXTENSIONS) . " dosya türleri kabul edilir.";
        }
        
        if (empty($errors)) {
            // Eski içerik dosyasını sil
            if (file_exists('uploads/content/' . $content['content_file'])) {
                unlink('uploads/content/' . $content['content_file']);
            }
            
            // Yeni içerik dosyasını yükle
            $content_file = time() . '_content_' . $uploaded_file['name'];
            if (!move_uploaded_file($uploaded_file['tmp_name'], 'uploads/content/' . $content_file)) {
                $errors[] = "İçerik dosyası yüklenirken bir hata oluştu.";
            }
        }
    }
    
    // Hata yoksa güncelle
    if (empty($errors)) {
        $updated_at = date('Y-m-d H:i:s');
        
        $query = "UPDATE content SET 
                  title = '$title',
                  description = '$description',
                  type = '$type',
                  tags = '$tags',
                  cover_image = '$cover_image',
                  content_file = '$content_file',
                  status = 'pending',
                  updated_at = '$updated_at'
                  WHERE id = $content_id AND user_id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            $success = true;
            // Güncellenmiş içeriği yeniden getir
            $result = mysqli_query($conn, "SELECT * FROM content WHERE id = $content_id");
            $content = mysqli_fetch_assoc($result);
        } else {
            $errors[] = "İçerik güncellenirken bir hata oluştu: " . mysqli_error($conn);
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>İçerik Düzenle</h1>
                <a href="my-content.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    İçerik başarıyla güncellendi! Değişiklikler incelendikten sonra yayınlanacaktır.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Başlık *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($content['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Açıklama *</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="5" required><?php echo htmlspecialchars($content['description']); ?></textarea>
                                    <div class="form-text">İçeriğiniz hakkında kısa bir açıklama yazın.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="type" class="form-label">Tür *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Tür Seçin</option>
                                        <option value="manga" <?php echo $content['type'] === 'manga' ? 'selected' : ''; ?>>Manga</option>
                                        <option value="comic" <?php echo $content['type'] === 'comic' ? 'selected' : ''; ?>>Çizgi Roman</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tags" class="form-label">Etiketler</label>
                                    <input type="text" class="form-control" id="tags" name="tags" 
                                           value="<?php echo htmlspecialchars($content['tags']); ?>">
                                    <div class="form-text">Etiketleri virgülle ayırın (örnek: aksiyon, macera, fantastik)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Mevcut Kapak Resmi</label>
                                    <div class="text-center">
                                        <img src="uploads/covers/<?php echo $content['cover_image']; ?>" 
                                             class="img-fluid rounded mb-2" 
                                             style="max-height: 200px;" 
                                             alt="Mevcut Kapak">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cover_image" class="form-label">Yeni Kapak Resmi</label>
                                    <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                                    <div class="form-text">JPG, JPEG, PNG veya GIF (maksimum <?php echo UPLOAD_MAX_SIZE / 1024 / 1024; ?>MB)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Mevcut İçerik Dosyası</label>
                                    <div class="alert alert-info">
                                        <i class="fas fa-file"></i> <?php echo $content['content_file']; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content_file" class="form-label">Yeni İçerik Dosyası</label>
                                    <input type="file" class="form-control" id="content_file" name="content_file" 
                                           accept=".jpg,.jpeg,.png,.gif,.pdf">
                                    <div class="form-text">
                                        <?php echo implode(', ', ALLOWED_EXTENSIONS); ?> 
                                        (maksimum <?php echo UPLOAD_MAX_SIZE / 1024 / 1024; ?>MB)
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Bilgi</h6>
                                    <ul class="mb-0">
                                        <li>Değişiklikler incelendikten sonra yayınlanacaktır</li>
                                        <li>Sadece değiştirmek istediğiniz dosyaları seçin</li>
                                        <li>Boş bırakılan dosyalar değiştirilmez</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Uyarı</h6>
                                    <ul class="mb-0">
                                        <li>Dosya değiştirirseniz eski dosya silinir</li>
                                        <li>Bu işlem geri alınamaz</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="my-content.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- İçerik Bilgileri -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> İçerik Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Durum:</strong> 
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
                                        $status_text = 'İnceleme Bekliyor';
                                        break;
                                    case 'rejected':
                                        $status_class = 'danger';
                                        $status_text = 'Reddedildi';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </p>
                            <p><strong>Oluşturulma:</strong> <?php echo date('d.m.Y H:i', strtotime($content['created_at'])); ?></p>
                            <?php if ($content['updated_at']): ?>
                                <p><strong>Son Güncelleme:</strong> <?php echo date('d.m.Y H:i', strtotime($content['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Görüntülenme:</strong> <?php echo $content['views']; ?> kez</p>
                            <?php if ($content['status'] === 'published'): ?>
                                <p><strong>Bağlantı:</strong> 
                                    <a href="view.php?id=<?php echo $content['id']; ?>" target="_blank">
                                        İçeriği Görüntüle <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dosya seçimi önizleme
document.getElementById('cover_image').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('img').src = e.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

// Form gönderilmeden önce onay
document.querySelector('form').addEventListener('submit', function(e) {
    const coverFile = document.getElementById('cover_image').files[0];
    const contentFile = document.getElementById('content_file').files[0];
    
    if (coverFile || contentFile) {
        if (!confirm('Dosya değiştirirseniz eski dosyalar silinecektir. Devam etmek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 