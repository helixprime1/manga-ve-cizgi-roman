<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'İçerik Yükle';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = "Güvenlik hatası. Lütfen tekrar deneyin.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '');
        $content_type = sanitizeInput($_POST['content_type'] ?? '');
        $tags = sanitizeInput($_POST['tags'] ?? '');
    
    // Bölüm bilgileri (seri ise)
    $chapter_number = $content_type === 'series' ? (int)($_POST['chapter_number'] ?? 1) : 1;
    $chapter_title = sanitizeInput($_POST['chapter_title'] ?? '');
    $chapter_description = sanitizeInput($_POST['chapter_description'] ?? '');
    
    // Başlık kontrolü
    if (empty($title)) {
        $errors[] = "Başlık gereklidir.";
    } elseif (strlen($title) < 3 || strlen($title) > 100) {
        $errors[] = "Başlık 3-100 karakter arasında olmalıdır.";
    }
    
    // Açıklama kontrolü
    if (empty($description)) {
        $errors[] = "Açıklama gereklidir.";
    } elseif (strlen($description) < 10) {
        $errors[] = "Açıklama en az 10 karakter olmalıdır.";
    }
    
    // Tür kontrolü
    if (empty($type) || !in_array($type, ['manga', 'comic'])) {
        $errors[] = "Geçerli bir tür seçin.";
    }
    
    // İçerik türü kontrolü
    if (empty($content_type) || !in_array($content_type, ['single', 'series'])) {
        $errors[] = "Geçerli bir içerik türü seçin.";
    }
    
    // Kapak resmi kontrolü
    if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Kapak resmi gereklidir.";
    } elseif ($_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Kapak resmi yüklenirken bir hata oluştu.";
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
    
    // Hata yoksa içeriği kaydet
    if (empty($errors)) {
        // Kapak resmi yükle
        $coverUpload = uploadFile($_FILES['cover_image'], '../uploads/covers');
        
        if (!$coverUpload['success']) {
            $errors[] = $coverUpload['message'];
        } else {
            $cover_image = $coverUpload['file_name'];
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
                $user_id = $user['id'];
                $created_at = date('Y-m-d H:i:s');
                $content_files_json = json_encode($content_files);
                $is_series = $content_type === 'series' ? 1 : 0;
                
                // Ana içeriği kaydet - Prepared statement kullan
                $stmt = mysqli_prepare($conn, "INSERT INTO content (user_id, title, description, type, tags, cover_image, content_file, content_type, is_series, chapter_count, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', ?)");
                mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $title, $description, $type, $tags, $cover_image, $content_files_json, $file_content_type, $is_series, $created_at);
                
                if (mysqli_stmt_execute($stmt)) {
                    $content_id = mysqli_insert_id($conn);
                    
                    // Bölüm bilgilerini kaydet
                    $final_chapter_title = !empty($chapter_title) ? $chapter_title : "Bölüm $chapter_number";
                    $chapter_status = 'published'; // İlk bölüm direkt yayınlanır
                    
                    $chapter_stmt = mysqli_prepare($conn, "INSERT INTO chapters (content_id, chapter_number, title, description, chapter_files, file_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($chapter_stmt, "iissssss", $content_id, $chapter_number, $final_chapter_title, $chapter_description, $content_files_json, $file_content_type, $chapter_status, $created_at);
                    
                    if (mysqli_stmt_execute($chapter_stmt)) {
                        // Otomatik olarak is_series değerlerini güncelle
                        updateSeriesStatus($conn);
                        $success = true;
                        mysqli_stmt_close($chapter_stmt);
                    } else {
                        $errors[] = "Bölüm kaydedilirken bir hata oluştu: " . mysqli_error($conn);
                        // Ana içeriği de sil
                        $delete_stmt = mysqli_prepare($conn, "DELETE FROM content WHERE id = ?");
                        mysqli_stmt_bind_param($delete_stmt, "i", $content_id);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                        mysqli_stmt_close($chapter_stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "İçerik kaydedilirken bir hata oluştu: " . mysqli_error($conn);
                }
            } elseif (empty($content_files)) {
                $errors[] = "Hiçbir içerik dosyası yüklenemedi.";
            }
        }
    }
    } // CSRF token kontrolü kapanış
}

require_once 'includes/header.php';
?>

<style>
.upload-container {
    max-width: 900px;
    margin: 0 auto;
}

.drag-drop-area {
    border: 3px dashed var(--border-color);
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: rgba(99, 102, 241, 0.05);
}

.drag-drop-area:hover {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.1);
}

.drag-drop-area.dragging {
    border-color: var(--primary-color);
    background: rgba(99, 102, 241, 0.2);
    transform: scale(1.02);
}

.file-preview {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.file-preview:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.file-preview img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-right: 1rem;
    border: 2px solid var(--border-color);
}

#contentPreview {
    max-height: 400px;
    overflow-y: auto;
}

#contentPreview::-webkit-scrollbar {
    width: 8px;
}

#contentPreview::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#contentPreview::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 10px;
}

.multi-image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.image-sortable {
    cursor: move;
}

.image-sortable:hover {
    opacity: 0.8;
}

.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3rem;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: var(--border-color);
    z-index: -1;
}

.step:last-child::after {
    display: none;
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--border-color);
    color: var(--text-color);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.step.completed .step-number {
    background: var(--secondary-color);
    color: white;
}
</style>

<div class="dashboard-content">
    <div class="upload-container">
        <?php if ($success): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <div style="font-size: 5rem; margin-bottom: 2rem;" class="text-success">✅</div>
                <h2 class="mb-4">İçerik Başarıyla Yüklendi!</h2>
                <p class="lead mb-4">İçeriğiniz incelendikten sonra yayınlanacaktır.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="content.php" class="btn btn-primary btn-lg">
                        📁 İçeriklerim
                    </a>
                    <a href="upload.php" class="btn btn-outline-primary btn-lg">
                        ➕ Yeni İçerik Yükle
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mb-5" data-aos="fade-down">
                <h1 class="display-5 fw-bold mb-3">İçerik Yükle</h1>
                <p class="lead text-muted">Manga veya çizgi romanınızı kolayca yükleyin ve paylaşın</p>
            </div>
            
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
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm" data-aos="fade-up" data-aos-delay="100">
                <?php echo getCSRFTokenInput(); ?>
                
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4">
                            ℹ️ Temel Bilgiler
                        </h4>
                        
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Başlık *</label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                   placeholder="Örn: One Piece Chapter 1000" 
                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                            <div class="form-text">İçeriğiniz için açıklayıcı bir başlık girin (3-100 karakter)</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Açıklama *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" 
                                      placeholder="İçeriğiniz hakkında detaylı bir açıklama yazın..." 
                                      required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <div class="form-text">En az 10 karakter</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="type" class="form-label fw-bold">Tür *</label>
                                <select class="form-select form-select-lg" id="type" name="type" required>
                                    <option value="">Seçiniz</option>
                                    <option value="manga" <?php echo (isset($type) && $type === 'manga') ? 'selected' : ''; ?>>
                                        🐉 Manga
                                    </option>
                                    <option value="comic" <?php echo (isset($type) && $type === 'comic') ? 'selected' : ''; ?>>
                                        🎭 Çizgi Roman
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="content_type" class="form-label fw-bold">İçerik Türü *</label>
                                <select class="form-select form-select-lg" id="content_type" name="content_type" required>
                                    <option value="">Seçiniz</option>
                                    <option value="single" <?php echo (isset($content_type) && $content_type === 'single') ? 'selected' : ''; ?>>
                                        📖 Tek Eser
                                    </option>
                                    <option value="series" <?php echo (isset($content_type) && $content_type === 'series') ? 'selected' : ''; ?>>
                                        📚 Seri (Bölümlü)
                                    </option>
                                </select>
                                <div class="form-text">Tek eser mi yoksa bölümlü seri mi?</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="tags" class="form-label fw-bold">Etiketler</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   placeholder="aksiyon, macera, fantastik" 
                                   value="<?php echo isset($tags) ? htmlspecialchars($tags) : ''; ?>">
                            <div class="form-text">Virgülle ayırın</div>
                        </div>
                        
                        <!-- Bölüm Bilgileri (Seri seçilirse görünür) -->
                        <div id="chapterInfo" class="d-none">
                            <div class="alert alert-info">
                                ℹ️ Seri seçtiniz. İlk bölümü yükleyebilir, sonraki bölümleri daha sonra ekleyebilirsiniz.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="chapter_number" class="form-label fw-bold">Bölüm Numarası</label>
                                    <input type="number" class="form-control" id="chapter_number" name="chapter_number" 
                                           value="1" min="1" readonly>
                                    <div class="form-text">İlk bölüm olarak 1 otomatik atandı</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="chapter_title" class="form-label fw-bold">Bölüm Başlığı</label>
                                    <input type="text" class="form-control" id="chapter_title" name="chapter_title" 
                                           placeholder="Örn: Başlangıç, İlk Karşılaşma" 
                                           value="<?php echo isset($chapter_title) ? htmlspecialchars($chapter_title) : ''; ?>">
                                    <div class="form-text">Bölüm için özel başlık (opsiyonel)</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="chapter_description" class="form-label fw-bold">Bölüm Açıklaması</label>
                                <textarea class="form-control" id="chapter_description" name="chapter_description" rows="3" 
                                          placeholder="Bu bölümde neler oluyor? (opsiyonel)"><?php echo isset($chapter_description) ? htmlspecialchars($chapter_description) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4">
                            📁 Dosya Yükleme
                        </h4>
                        
                        <div class="mb-4">
                            <label for="cover_image" class="form-label fw-bold">Kapak Resmi *</label>
                            <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*" required>
                            <div class="form-text">JPG, JPEG, PNG, GIF - Maksimum 10MB</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="content_images" class="form-label fw-bold">İçerik Resimleri *</label>
                            <input type="file" class="form-control" id="content_images" name="content_images[]" accept="image/*" multiple required>
                            <div class="form-text">JPG, JPEG, PNG, GIF - Her biri maksimum 10MB</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="pdf_file" class="form-label fw-bold">Veya PDF Dosyası</label>
                            <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                            <div class="form-text">PDF Dosyası - Maksimum 50MB</div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Bu içeriğin telif haklarına sahip olduğumu veya paylaşma hakkım olduğunu onaylıyorum.
                            </label>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                ☁️ Yükle
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="../js/upload-functions.js"></script>
<script>
// İçerik türü değişikliği
function toggleChapterInfo() {
    const contentType = document.getElementById('content_type').value;
    const chapterInfo = document.getElementById('chapterInfo');
    
    if (contentType === 'series') {
        chapterInfo.classList.remove('d-none');
        document.getElementById('chapter_title').required = false; // Opsiyonel
        document.getElementById('chapter_description').required = false; // Opsiyonel
        document.getElementById('chapter_number').required = true;
    } else {
        chapterInfo.classList.add('d-none');
        document.getElementById('chapter_title').required = false;
        document.getElementById('chapter_description').required = false;
        document.getElementById('chapter_number').required = false;
    }
}

// PDF seçildiğinde resim alanını temizle
function clearImagesWhenPdfSelected() {
    const pdfInput = document.getElementById('pdf_file');
    const imageInput = document.getElementById('content_images');
    
    pdfInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            imageInput.value = '';
            imageInput.required = false;
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
    clearImagesWhenPdfSelected();
    
    // İçerik türü değişikliğini dinle
    document.getElementById('content_type').addEventListener('change', toggleChapterInfo);
    
    // Sayfa yüklendiğinde mevcut seçimi kontrol et
    toggleChapterInfo();
});
</script>

<?php require_once 'includes/footer.php'; ?>