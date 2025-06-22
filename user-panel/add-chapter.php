<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Yeni Bölüm Ekle';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$user_id = $user['id'];

// Content ID kontrolü
if (!isset($_GET['content_id']) || !is_numeric($_GET['content_id'])) {
    header('Location: content.php');
    exit;
}

$content_id = (int)$_GET['content_id'];

// İçeriğin kullanıcıya ait olduğunu ve seri olduğunu kontrol et
$content_stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE id = ? AND user_id = ? AND is_series = 1");
mysqli_stmt_bind_param($content_stmt, "ii", $content_id, $user_id);
mysqli_stmt_execute($content_stmt);
$content_result = mysqli_stmt_get_result($content_stmt);

if (mysqli_num_rows($content_result) === 0) {
    header('Location: content.php');
    exit;
}

$content = mysqli_fetch_assoc($content_result);

// Sonraki bölüm numarasını al
$next_chapter_stmt = mysqli_prepare($conn, "SELECT IFNULL(MAX(chapter_number), 0) + 1 as next_chapter FROM chapters WHERE content_id = ?");
mysqli_stmt_bind_param($next_chapter_stmt, "i", $content_id);
mysqli_stmt_execute($next_chapter_stmt);
$next_chapter_result = mysqli_stmt_get_result($next_chapter_stmt);
$next_chapter_data = mysqli_fetch_assoc($next_chapter_result);
$next_chapter_number = $next_chapter_data['next_chapter'];

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
            // Bölüm numarası zaten var mı kontrol et
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM chapters WHERE content_id = ? AND chapter_number = ?");
            mysqli_stmt_bind_param($check_stmt, "ii", $content_id, $chapter_number);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Bu bölüm numarası zaten kullanılıyor.";
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
        
        // İçerik dosyası kontrolü - Resimler veya PDF
        $hasContentImages = isset($_FILES['content_images']) && !empty($_FILES['content_images']['name'][0]);
        $hasPdfFile = isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK;
        
        if (!$hasContentImages && !$hasPdfFile) {
            $errors[] = "En az bir içerik dosyası (resim veya PDF) gereklidir.";
        }
        
        // Resim dosyaları kontrolü
        if ($hasContentImages) {
            foreach ($_FILES['content_images']['error'] as $key => $error) {
                if ($error !== UPLOAD_ERR_OK && $error !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Resim dosyalarından biri yüklenirken hata oluştu.";
                    break;
                }
            }
        }
        
        // PDF dosyası kontrolü
        if ($hasPdfFile && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "PDF dosyası yüklenirken bir hata oluştu.";
        }
        
        // Hata yoksa bölümü kaydet
        if (empty($errors)) {
            $content_files = [];
            $file_content_type = '';
            
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
            
            // Hata yoksa veritabanına kaydet
            if (empty($errors) && !empty($content_files)) {
                $created_at = date('Y-m-d H:i:s');
                $content_files_json = json_encode($content_files);
                
                // Bölümü kaydet
                $stmt = mysqli_prepare($conn, "INSERT INTO chapters (content_id, chapter_number, title, description, chapter_files, file_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iissssss", $content_id, $chapter_number, $chapter_title, $chapter_description, $content_files_json, $file_content_type, $status, $created_at);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Ana içeriğin bölüm sayısını güncelle
                    $update_stmt = mysqli_prepare($conn, "UPDATE content SET chapter_count = (SELECT COUNT(*) FROM chapters WHERE content_id = ?) WHERE id = ?");
                    mysqli_stmt_bind_param($update_stmt, "ii", $content_id, $content_id);
                    mysqli_stmt_execute($update_stmt);
                    
                    // Otomatik olarak is_series değerlerini güncelle
                    updateSeriesStatus($conn);
                    
                    $success = true;
                    mysqli_stmt_close($update_stmt);
                } else {
                    $errors[] = "Bölüm kaydedilirken bir hata oluştu: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } elseif (empty($content_files)) {
                $errors[] = "Hiçbir içerik dosyası yüklenemedi.";
            }
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

.file-upload-area {
    border: 3px dashed var(--border-color);
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: rgba(99, 102, 241, 0.05);
}

.file-upload-area:hover {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.1);
}

.file-upload-area.dragging {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.2);
    transform: scale(1.02);
}

.preview-container {
    max-height: 400px;
    overflow-y: auto;
    margin-top: 1rem;
}

.file-preview {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
}

.file-preview img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-right: 1rem;
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
        <h1 class="display-6 fw-bold mb-3"><?php echo htmlspecialchars($content['title']); ?></h1>
        <p class="lead mb-0">Yeni Bölüm Ekleniyor</p>
    </div>

    <?php if ($success): ?>
        <div class="text-center py-5" data-aos="fade-up">
            <div style="font-size: 5rem; margin-bottom: 2rem;" class="text-success">✅</div>
            <h2 class="mb-4">Bölüm Başarıyla Eklendi!</h2>
            <p class="lead mb-4">
                Bölüm <?php echo $chapter_number; ?> başarıyla <?php echo $status === 'published' ? 'yayınlandı' : 'taslak olarak kaydedildi'; ?>.
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="chapters.php?content_id=<?php echo $content_id; ?>" class="btn btn-primary btn-lg">
                    📚 Bölümleri Görüntüle
                </a>
                <a href="add-chapter.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-primary btn-lg">
                    ➕ Yeni Bölüm Ekle
                </a>
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
                               value="<?php echo $next_chapter_number; ?>" min="1" required>
                        <div class="form-text">Otomatik olarak sonraki numara atandı</div>
                    </div>
                    
                    <div class="col-md-9 mb-4">
                        <label for="chapter_title" class="form-label fw-bold">Bölüm Başlığı *</label>
                        <input type="text" class="form-control form-control-lg" id="chapter_title" name="chapter_title" 
                               placeholder="Örn: Yeni Bir Başlangıç, Büyük Savaş" 
                               value="<?php echo isset($chapter_title) ? htmlspecialchars($chapter_title) : ''; ?>" required>
                        <div class="form-text">Bölüm için açıklayıcı bir başlık girin (3-100 karakter)</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="chapter_description" class="form-label fw-bold">Bölüm Açıklaması</label>
                    <textarea class="form-control" id="chapter_description" name="chapter_description" rows="4" 
                              placeholder="Bu bölümde neler oluyor? (opsiyonel)"><?php echo isset($chapter_description) ? htmlspecialchars($chapter_description) : ''; ?></textarea>
                    <div class="form-text">Bölüm hakkında kısa bir açıklama yazın</div>
                </div>
            </div>

            <!-- Dosya Yükleme -->
            <div class="form-section">
                <h3 class="section-title">
                    📁 İçerik Dosyaları
                </h3>
                
                <div class="mb-4">
                    <label for="content_images" class="form-label fw-bold">İçerik Resimleri *</label>
                    <input type="file" class="form-control" id="content_images" name="content_images[]" accept="image/*" multiple required>
                    <div class="form-text">JPG, JPEG, PNG, GIF - Her biri maksimum 10MB - Sıralı şekilde yükleyin</div>
                </div>
                
                <div class="mb-4">
                    <label for="pdf_file" class="form-label fw-bold">Veya PDF Dosyası</label>
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
                    <label class="status-option" for="status_draft">
                        <input type="radio" id="status_draft" name="status" value="draft" checked>
                        <div class="status-icon text-secondary">
                            💾
                        </div>
                        <h5>Taslak Olarak Kaydet</h5>
                        <p class="text-muted mb-0">Bölüm henüz yayınlanmaz, daha sonra yayınlayabilirsiniz</p>
                    </label>
                    
                    <label class="status-option" for="status_published">
                        <input type="radio" id="status_published" name="status" value="published">
                        <div class="status-icon text-success">
                            🌐
                        </div>
                        <h5>Hemen Yayınla</h5>
                        <p class="text-muted mb-0">Bölüm hemen yayınlanır ve okuyucular görebilir</p>
                    </label>
                </div>
            </div>

            <!-- Gönder Butonu -->
            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    ➕ Bölümü Ekle
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
    
    // Varsayılan seçimi işaretle
    document.querySelector('input[name="status"]:checked').closest('.status-option').classList.add('selected');
});

// PDF seçildiğinde resim alanını temizle ve vice versa
function setupFileInputs() {
    const pdfInput = document.getElementById('pdf_file');
    const imageInput = document.getElementById('content_images');
    
    pdfInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            imageInput.value = '';
            imageInput.required = false;
        } else {
            imageInput.required = true;
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