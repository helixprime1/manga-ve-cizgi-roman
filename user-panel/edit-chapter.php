<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Bölüm Düzenle';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$user_id = $user['id'];

// Chapter ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: content.php');
    exit;
}

$chapter_id = (int)$_GET['id'];

// Bölümün kullanıcıya ait olduğunu kontrol et
$chapter_stmt = mysqli_prepare($conn, "
    SELECT c.*, ch.* 
    FROM chapters ch 
    INNER JOIN content c ON ch.content_id = c.id 
    WHERE ch.id = ? AND c.user_id = ?
");
mysqli_stmt_bind_param($chapter_stmt, "ii", $chapter_id, $user_id);
mysqli_stmt_execute($chapter_stmt);
$chapter_result = mysqli_stmt_get_result($chapter_stmt);

if (mysqli_num_rows($chapter_result) === 0) {
    header('Location: content.php');
    exit;
}

$chapter = mysqli_fetch_assoc($chapter_result);
$content_id = $chapter['content_id'];

$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = "Güvenlik hatası. Lütfen tekrar deneyin.";
    } else {
        $chapter_number = (int)($_POST['chapter_number'] ?? 0);
        $chapter_title = sanitizeInput($_POST['chapter_title'] ?? '');
        $chapter_description = sanitizeInput($_POST['chapter_description'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'draft');
        
        // Bölüm numarası kontrolü
        if ($chapter_number <= 0) {
            $errors[] = "Geçerli bir bölüm numarası girin.";
        } else {
            // Bölüm numarası başka bir bölümde kullanılıyor mu kontrol et
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM chapters WHERE content_id = ? AND chapter_number = ? AND id != ?");
            mysqli_stmt_bind_param($check_stmt, "iii", $content_id, $chapter_number, $chapter_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Bu bölüm numarası zaten başka bir bölümde kullanılıyor.";
            }
        }
        
        // Başlık kontrolü
        if (empty($chapter_title)) {
            $errors[] = "Bölüm başlığı gereklidir.";
        } elseif (strlen($chapter_title) < 3 || strlen($chapter_title) > 100) {
            $errors[] = "Bölüm başlığı 3-100 karakter arasında olmalıdır.";
        }
        
        // Durum kontrolü
        if (!in_array($status, ['draft', 'published'])) {
            $errors[] = "Geçerli bir durum seçin.";
        }
        
        // Yeni dosya yüklendi mi kontrol et
        $hasContentImages = isset($_FILES['content_images']) && !empty($_FILES['content_images']['name'][0]);
        $hasPdfFile = isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK;
        
        $content_files = [];
        $file_content_type = $chapter['file_type']; // Mevcut dosya türünü koru
        
        // Yeni dosya yüklendiyse işle
        if ($hasContentImages || $hasPdfFile) {
            // Eski dosyaları sil
            $old_files = json_decode($chapter['chapter_files'], true);
            if (is_array($old_files)) {
                foreach ($old_files as $old_file) {
                    $file_path = '../uploads/content/' . $old_file;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
            
            // Resim dosyaları yükle
            if ($hasContentImages) {
                $file_content_type = 'images';
                $imageCount = count($_FILES['content_images']['name']);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    if ($_FILES['content_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $imageFile = [
                            'name' => $_FILES['content_images']['name'][$i],
                            'type' => $_FILES['content_images']['type'][$i],
                            'tmp_name' => $_FILES['content_images']['tmp_name'][$i],
                            'error' => $_FILES['content_images']['error'][$i],
                            'size' => $_FILES['content_images']['size'][$i]
                        ];
                        
                        $imageUpload = uploadFile($imageFile, '../uploads/content');
                        
                        if ($imageUpload['success']) {
                            $content_files[] = $imageUpload['file_name'];
                        } else {
                            $errors[] = "Resim yüklenirken hata: " . $imageUpload['message'];
                        }
                    }
                }
            }
            // PDF dosyası yükle
            elseif ($hasPdfFile) {
                $file_content_type = 'pdf';
                $pdfUpload = uploadFile($_FILES['pdf_file'], '../uploads/content');
                
                if ($pdfUpload['success']) {
                    $content_files[] = $pdfUpload['file_name'];
                } else {
                    $errors[] = "PDF yüklenirken hata: " . $pdfUpload['message'];
                }
            }
        } else {
            // Yeni dosya yüklenmedi, eski dosyaları koru
            $content_files = json_decode($chapter['chapter_files'], true);
            if (!is_array($content_files)) {
                $content_files = [$chapter['chapter_files']];
            }
        }
        
        // Hata yoksa bölümü güncelle
        if (empty($errors) && !empty($content_files)) {
            $updated_at = date('Y-m-d H:i:s');
            $content_files_json = json_encode($content_files);
            
            // Bölümü güncelle
            $stmt = mysqli_prepare($conn, "UPDATE chapters SET chapter_number = ?, title = ?, description = ?, chapter_files = ?, file_type = ?, status = ?, updated_at = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "issssssi", $chapter_number, $chapter_title, $chapter_description, $content_files_json, $file_content_type, $status, $updated_at, $chapter_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
                // Güncellenmiş bilgileri al
                $chapter['chapter_number'] = $chapter_number;
                $chapter['title'] = $chapter_title;
                $chapter['status'] = $status;
            } else {
                $errors[] = "Bölüm güncellenirken bir hata oluştu: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } elseif (empty($content_files)) {
            $errors[] = "En az bir içerik dosyası gereklidir.";
        }
    } // CSRF token kontrolü kapanış
}

require_once 'includes/header.php';
?>

<style>
.content-info-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.form-section {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chapter-number-input {
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border: 2px solid var(--border-color);
}

.chapter-number-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
}

.status-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.status-option {
    border: 2px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.status-option:hover {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.05);
}

.status-option.selected {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.1);
}

.status-option input[type="radio"] {
    display: none;
}

.status-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.current-files {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.current-file-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    border: 1px solid #dee2e6;
}

.current-file-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.25rem;
    margin-right: 1rem;
}
</style>

<div class="dashboard-content">
    <!-- Geri Dön Butonu -->
    <div class="mb-4">
        <a href="chapters.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-secondary">
            ⬅️ Bölümlere Dön
        </a>
    </div>

    <!-- İçerik Bilgileri -->
    <div class="content-info-banner" data-aos="fade-up">
        <h1 class="display-6 fw-bold mb-3"><?php echo htmlspecialchars($chapter['title']); ?></h1>
        <p class="lead mb-0">Bölüm <?php echo $chapter['chapter_number']; ?> Düzenleniyor</p>
    </div>

    <?php if ($success): ?>
        <div class="text-center py-5" data-aos="fade-up">
            <div style="font-size: 5rem; margin-bottom: 2rem;" class="text-success">✅</div>
            <h2 class="mb-4">Bölüm Başarıyla Güncellendi!</h2>
            <p class="lead mb-4">
                Bölüm <?php echo $chapter['chapter_number']; ?> başarıyla güncellendi.
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="chapters.php?content_id=<?php echo $content_id; ?>" class="btn btn-primary btn-lg">
                    📚 Bölümleri Görüntüle
                </a>
                <?php if ($chapter['status'] === 'published'): ?>
                    <a href="../view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $chapter['chapter_number']; ?>" class="btn btn-outline-primary btn-lg" target="_blank">
                        👁️ Bölümü Görüntüle
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                ⚠️
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="chapterForm" data-aos="fade-up" data-aos-delay="100">
            <?php echo getCSRFTokenInput(); ?>
            
            <!-- Temel Bilgiler -->
            <div class="form-section">
                <h3 class="section-title">
                    ℹ️ Bölüm Bilgileri
                </h3>
                
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <label for="chapter_number" class="form-label fw-bold">Bölüm Numarası *</label>
                        <input type="number" class="form-control chapter-number-input" id="chapter_number" name="chapter_number" 
                               value="<?php echo $chapter['chapter_number']; ?>" min="1" required>
                        <div class="form-text">Bölüm sırasını değiştirebilirsiniz</div>
                    </div>
                    
                    <div class="col-md-9 mb-4">
                        <label for="chapter_title" class="form-label fw-bold">Bölüm Başlığı *</label>
                        <input type="text" class="form-control form-control-lg" id="chapter_title" name="chapter_title" 
                               placeholder="Örn: Yeni Bir Başlangıç, Büyük Savaş" 
                               value="<?php echo htmlspecialchars($chapter['title']); ?>" required>
                        <div class="form-text">Bölüm için açıklayıcı bir başlık girin (3-100 karakter)</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="chapter_description" class="form-label fw-bold">Bölüm Açıklaması</label>
                    <textarea class="form-control" id="chapter_description" name="chapter_description" rows="4" 
                              placeholder="Bu bölümde neler oluyor? (opsiyonel)"><?php echo htmlspecialchars($chapter['description']); ?></textarea>
                    <div class="form-text">Bölüm hakkında kısa bir açıklama yazın</div>
                </div>
            </div>

            <!-- Mevcut Dosyalar -->
            <div class="form-section">
                <h3 class="section-title">
                    📄 Mevcut Dosyalar
                </h3>
                
                <div class="current-files">
                    <h6 class="mb-3">Şu anki bölüm dosyaları:</h6>
                    <?php
                    $current_files = json_decode($chapter['chapter_files'], true);
                    if (!is_array($current_files)) {
                        $current_files = [$chapter['chapter_files']];
                    }
                    
                    foreach ($current_files as $index => $file):
                    ?>
                        <div class="current-file-item">
                            <?php if ($chapter['file_type'] === 'images'): ?>
                                <img src="../uploads/content/<?php echo htmlspecialchars($file); ?>" 
                                     alt="Sayfa <?php echo $index + 1; ?>"
                                     onerror="this.src='../assets/images/no-image.svg'">
                                <div>
                                    <strong>Sayfa <?php echo $index + 1; ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($file); ?></small>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center">
                                    <div style="font-size: 3rem; margin-right: 1rem;">📄</div>
                                    <div>
                                        <strong>PDF Dosyası</strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($file); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-warning">
                    ⚠️ <strong>Dikkat:</strong> Yeni dosya yüklerseniz, mevcut dosyalar silinecektir. 
                    Dosyaları değiştirmek istemiyorsanız aşağıdaki alanları boş bırakın.
                </div>
            </div>

            <!-- Dosya Yükleme (Opsiyonel) -->
            <div class="form-section">
                <h3 class="section-title">
                    📁 Yeni Dosyalar (Opsiyonel)
                </h3>
                
                <div class="mb-4">
                    <label for="content_images" class="form-label fw-bold">Yeni İçerik Resimleri</label>
                    <input type="file" class="form-control" id="content_images" name="content_images[]" accept="image/*" multiple>
                    <div class="form-text">JPG, JPEG, PNG, GIF - Her biri maksimum 10MB - Sıralı şekilde yükleyin</div>
                </div>
                
                <div class="mb-4">
                    <label for="pdf_file" class="form-label fw-bold">Veya Yeni PDF Dosyası</label>
                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                    <div class="form-text">PDF Dosyası - Maksimum 50MB</div>
                </div>
                
                <div class="alert alert-info">
                    ℹ️ <strong>İpucu:</strong> Resimler sıralı şekilde yüklenir. Dosya adlarına göre sıralanır. 
                    Sayfa sırasına dikkat edin (örn: 01.jpg, 02.jpg, 03.jpg).
                </div>
            </div>

            <!-- Yayın Durumu -->
            <div class="form-section">
                <h3 class="section-title">
                    👁️ Yayın Durumu
                </h3>
                
                <div class="status-options">
                    <label class="status-option <?php echo $chapter['status'] === 'draft' ? 'selected' : ''; ?>" for="status_draft">
                        <input type="radio" id="status_draft" name="status" value="draft" <?php echo $chapter['status'] === 'draft' ? 'checked' : ''; ?>>
                        <div class="status-icon text-secondary">
                            💾
                        </div>
                        <h5>Taslak</h5>
                        <p class="text-muted mb-0">Bölüm henüz yayınlanmaz</p>
                    </label>
                    
                    <label class="status-option <?php echo $chapter['status'] === 'published' ? 'selected' : ''; ?>" for="status_published">
                        <input type="radio" id="status_published" name="status" value="published" <?php echo $chapter['status'] === 'published' ? 'checked' : ''; ?>>
                        <div class="status-icon text-success">
                            🌐
                        </div>
                        <h5>Yayınlandı</h5>
                        <p class="text-muted mb-0">Bölüm okuyucular tarafından görülebilir</p>
                    </label>
                </div>
            </div>

            <!-- Gönder Butonu -->
            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    💾 Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// Durum seçenekleri
document.addEventListener('DOMContentLoaded', function() {
    const statusOptions = document.querySelectorAll('.status-option');
    const statusRadios = document.querySelectorAll('input[name="status"]');
    
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Tüm seçenekleri temizle
            statusOptions.forEach(opt => opt.classList.remove('selected'));
            // Seçili olanı işaretle
            this.classList.add('selected');
        });
    });
});

// PDF seçildiğinde resim alanını temizle ve vice versa
function setupFileInputs() {
    const pdfInput = document.getElementById('pdf_file');
    const imageInput = document.getElementById('content_images');
    
    pdfInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            imageInput.value = '';
        }
    });
    
    imageInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            pdfInput.value = '';
        }
    });
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    setupFileInputs();
});
</script>

<?php require_once 'includes/footer.php'; ?> 