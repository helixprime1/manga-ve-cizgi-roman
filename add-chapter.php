<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isLoggedIn()) {
    header('Location: login.php?redirect=add-chapter.php');
    exit;
}

$user = getCurrentUser();
$errors = [];
$success = false;

// İçerik ID'si kontrolü
if (!isset($_GET['content_id']) || !is_numeric($_GET['content_id'])) {
    header('Location: my-content.php');
    exit;
}

$content_id = (int)$_GET['content_id'];

// İçeriğin sahibi mi ve seri mi kontrol et
$content_query = "SELECT * FROM content WHERE id = $content_id AND user_id = {$user['id']} AND is_series = 1";
$content_result = mysqli_query($conn, $content_query);

if (mysqli_num_rows($content_result) === 0) {
    header('Location: my-content.php');
    exit;
}

$content = mysqli_fetch_assoc($content_result);

// Mevcut bölümleri al
$chapters_query = "SELECT * FROM chapters WHERE content_id = $content_id ORDER BY chapter_number ASC";
$chapters_result = mysqli_query($conn, $chapters_query);
$chapters = [];
while ($chapter = mysqli_fetch_assoc($chapters_result)) {
    $chapters[] = $chapter;
}

$next_chapter_number = count($chapters) + 1;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter_number = (int)($_POST['chapter_number'] ?? $next_chapter_number);
    $chapter_title = sanitizeInput($_POST['chapter_title'] ?? '');
    $chapter_description = sanitizeInput($_POST['chapter_description'] ?? '');
    
    // Bölüm numarası kontrolü
    if ($chapter_number < 1) {
        $errors[] = "Bölüm numarası 1'den küçük olamaz.";
    }
    
    // Bu bölüm numarası zaten var mı kontrol et
    $existing_chapter = mysqli_query($conn, "SELECT id FROM chapters WHERE content_id = $content_id AND chapter_number = $chapter_number");
    if (mysqli_num_rows($existing_chapter) > 0) {
        $errors[] = "Bu bölüm numarası zaten mevcut.";
    }
    
    // Bölüm başlığı kontrolü
    if (empty($chapter_title)) {
        $chapter_title = "Bölüm $chapter_number";
    }
    
    // Dosya kontrolü
    $hasContentImages = isset($_FILES['content_images']) && !empty($_FILES['content_images']['name'][0]);
    $hasPdfFile = isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK;
    
    if (!$hasContentImages && !$hasPdfFile) {
        $errors[] = "En az bir içerik dosyası (resim veya PDF) gereklidir.";
    }
    
    // Dosya yükleme işlemi
    if (empty($errors)) {
        $content_files = [];
        $file_type = '';
        
        // Resim dosyaları yükle
        if ($hasContentImages) {
            $file_type = 'images';
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
                    
                    $imageUpload = uploadFile($imageFile, 'uploads/content');
                    
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
            $file_type = 'pdf';
            $pdfUpload = uploadFile($_FILES['pdf_file'], 'uploads/content');
            
            if ($pdfUpload['success']) {
                $content_files[] = $pdfUpload['file_name'];
            } else {
                $errors[] = "PDF yüklenirken hata: " . $pdfUpload['message'];
            }
        }
        
        // Bölümü veritabanına kaydet
        if (empty($errors) && !empty($content_files)) {
            $created_at = date('Y-m-d H:i:s');
            $content_files_json = json_encode($content_files);
            
            $chapter_query = "INSERT INTO chapters (content_id, chapter_number, title, description, chapter_files, file_type, status, created_at) 
                             VALUES ($content_id, $chapter_number, '$chapter_title', '$chapter_description', '$content_files_json', '$file_type', 'published', '$created_at')";
            
            if (mysqli_query($conn, $chapter_query)) {
                // Otomatik olarak is_series değerlerini güncelle
                updateSeriesStatus($conn);
                
                $success = true;
            } else {
                $errors[] = "Bölüm kaydedilirken bir hata oluştu: " . mysqli_error($conn);
            }
        }
    }
}

$page_title = $content['title'] . ' - Yeni Bölüm Ekle';
require_once 'includes/header.php';
?>

<style>
.chapter-list {
    max-height: 400px;
    overflow-y: auto;
}

.chapter-item {
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: white;
    transition: all 0.3s ease;
}

.chapter-item:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transform: translateY(-2px);
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
</style>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <?php if ($success): ?>
                <div class="text-center py-5" data-aos="fade-up">
                    <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                    <h2 class="mb-4">Bölüm Başarıyla Eklendi!</h2>
                    <p class="lead mb-4">Yeni bölümünüz yayınlandı.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="view.php?id=<?php echo $content_id; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-eye me-2"></i>Eseri Görüntüle
                        </a>
                        <a href="add-chapter.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Başka Bölüm Ekle
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-4" data-aos="fade-down">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="my-content.php">İçeriklerim</a></li>
                            <li class="breadcrumb-item"><a href="view.php?id=<?php echo $content_id; ?>"><?php echo htmlspecialchars($content['title']); ?></a></li>
                            <li class="breadcrumb-item active">Yeni Bölüm</li>
                        </ol>
                    </nav>
                    
                    <h1 class="display-6 fw-bold mb-3">Yeni Bölüm Ekle</h1>
                    <p class="lead text-muted"><?php echo htmlspecialchars($content['title']); ?> serisine yeni bölüm ekleyin</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" data-aos="fade-up" data-aos-delay="100">
                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">
                                <i class="fas fa-info-circle me-2"></i>Bölüm Bilgileri
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="chapter_number" class="form-label fw-bold">Bölüm Numarası *</label>
                                    <input type="number" class="form-control form-control-lg" id="chapter_number" 
                                           name="chapter_number" value="<?php echo $next_chapter_number; ?>" min="1" required>
                                    <div class="form-text">Önerilen: <?php echo $next_chapter_number; ?></div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="chapter_title" class="form-label fw-bold">Bölüm Başlığı</label>
                                    <input type="text" class="form-control form-control-lg" id="chapter_title" 
                                           name="chapter_title" placeholder="Örn: Karanlık Sır, Son Savaş"
                                           value="<?php echo isset($chapter_title) ? htmlspecialchars($chapter_title) : ''; ?>">
                                    <div class="form-text">Boş bırakılırsa "Bölüm X" olarak kaydedilir</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="chapter_description" class="form-label fw-bold">Bölüm Açıklaması</label>
                                <textarea class="form-control" id="chapter_description" name="chapter_description" rows="4" 
                                          placeholder="Bu bölümde neler oluyor? (opsiyonel)"><?php echo isset($chapter_description) ? htmlspecialchars($chapter_description) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">
                                <i class="fas fa-file-upload me-2"></i>Dosya Yükleme
                            </h4>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Bölüm Resimleri *</label>
                                <div class="drag-drop-area" id="contentDropArea">
                                    <i class="fas fa-images fa-3x text-success mb-3"></i>
                                    <p class="mb-2">Birden fazla resim seçin veya buraya sürükleyin</p>
                                    <p class="text-muted small">JPG, JPEG, PNG, GIF - Her biri maksimum 10MB</p>
                                    <input type="file" class="d-none" id="content-images" name="content_images[]" accept="image/*" multiple required>
                                </div>
                                <div id="contentPreview" class="mt-3"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Veya PDF Dosyası</label>
                                <div class="drag-drop-area" id="pdfDropArea">
                                    <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                    <p class="mb-2">PDF dosyasını buraya sürükleyin veya <span class="text-primary">tıklayın</span></p>
                                    <p class="text-muted small">PDF Dosyası - Maksimum 50MB</p>
                                    <input type="file" class="d-none" id="pdf-file" name="pdf_file" accept=".pdf">
                                </div>
                                <div id="pdfPreview"></div>
                            </div>
                            
                            <div class="text-end">
                                <a href="view.php?id=<?php echo $content_id; ?>" class="btn btn-outline-secondary btn-lg me-2">
                                    <i class="fas fa-arrow-left me-2"></i>İptal
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-plus me-2"></i>Bölüm Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar - Mevcut Bölümler -->
        <div class="col-lg-4">
            <div class="card" data-aos="fade-left">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Mevcut Bölümler 
                        <span class="badge bg-primary"><?php echo count($chapters); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="chapter-list">
                        <?php if (!empty($chapters)): ?>
                            <?php foreach ($chapters as $chapter): ?>
                                <div class="chapter-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-secondary me-2"><?php echo $chapter['chapter_number']; ?></span>
                                                <?php echo htmlspecialchars($chapter['title']); ?>
                                            </h6>
                                            <?php if (!empty($chapter['description'])): ?>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($chapter['description'], 0, 100)); ?>...</p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i><?php echo number_format($chapter['views']); ?> görüntülenme
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $chapter['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo $chapter['status'] === 'published' ? 'Yayında' : 'Taslak'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 text-center text-muted">
                                Henüz bölüm eklenmemiş.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/upload-functions.js"></script>
<script>
// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    setupMultiFileDragDrop('contentDropArea', 'content-images', 'contentPreview');
    setupSingleFileDragDrop('pdfDropArea', 'pdf-file', 'pdfPreview');
    
    // PDF seçildiğinde resim alanını temizle
    document.getElementById('pdf-file').addEventListener('change', function() {
        if (this.files.length > 0) {
            document.getElementById('content-images').value = '';
            document.getElementById('content-images').required = false;
            document.getElementById('contentPreview').innerHTML = '';
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 