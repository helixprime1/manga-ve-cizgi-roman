<?php
$page_title = 'Yazar Başvurusu';

// Eğer oturum başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isLoggedIn()) {
    header('Location: login.php?redirect=author-application.php');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

// Kullanıcı zaten yazar mı kontrol et
if ($user['is_author'] == 1) {
    header('Location: user-panel/');
    exit;
}

// Bekleyen başvuru var mı kontrol et
$existing_application_stmt = mysqli_prepare($conn, "SELECT * FROM author_applications WHERE user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($existing_application_stmt, "i", $user_id);
mysqli_stmt_execute($existing_application_stmt);
$existing_application_result = mysqli_stmt_get_result($existing_application_stmt);

if (mysqli_num_rows($existing_application_result) > 0) {
    $existing_application = mysqli_fetch_assoc($existing_application_result);
    $has_pending_application = true;
} else {
    $has_pending_application = false;
}

$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_pending_application) {
    // CSRF Token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = "Güvenlik hatası. Lütfen tekrar deneyin.";
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $experience = sanitizeInput($_POST['experience'] ?? '');
        $portfolio_links = sanitizeInput($_POST['portfolio_links'] ?? '');
        $sample_work_description = sanitizeInput($_POST['sample_work_description'] ?? '');
        $why_author = sanitizeInput($_POST['why_author'] ?? '');
        $preferred_genres = sanitizeInput($_POST['preferred_genres'] ?? '');
        $social_media_links = sanitizeInput($_POST['social_media_links'] ?? '');
        
        // Validasyon
        if (empty($full_name)) {
            $errors[] = "Ad Soyad gereklidir.";
        } elseif (strlen($full_name) < 3 || strlen($full_name) > 100) {
            $errors[] = "Ad Soyad 3-100 karakter arasında olmalıdır.";
        }
        
        if (empty($email)) {
            $errors[] = "E-posta gereklidir.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Geçerli bir e-posta adresi girin.";
        }
        
        if (empty($bio)) {
            $errors[] = "Kişisel tanıtım gereklidir.";
        } elseif (strlen($bio) < 50) {
            $errors[] = "Kişisel tanıtım en az 50 karakter olmalıdır.";
        }
        
        if (empty($why_author)) {
            $errors[] = "Yazar olmak isteme nedeniniz gereklidir.";
        } elseif (strlen($why_author) < 50) {
            $errors[] = "Yazar olmak isteme nedeniniz en az 50 karakter olmalıdır.";
        }
        
        // Hata yoksa başvuruyu kaydet
        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO author_applications (user_id, full_name, email, phone, bio, experience, portfolio_links, sample_work_description, why_author, preferred_genres, social_media_links) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issssssssss", $user_id, $full_name, $email, $phone, $bio, $experience, $portfolio_links, $sample_work_description, $why_author, $preferred_genres, $social_media_links);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                $errors[] = "Başvuru kaydedilirken bir hata oluştu: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.application-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.application-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 1rem;
    padding: 3rem 2rem;
    text-align: center;
    margin-bottom: 3rem;
}

.application-form {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-banner {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.success-banner {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 1rem;
    padding: 3rem 2rem;
    text-align: center;
}

.requirements-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.requirement-item:last-child {
    margin-bottom: 0;
}

.requirement-icon {
    color: var(--primary-color);
    margin-right: 0.75rem;
    font-size: 1.1rem;
}
</style>

<div class="container">
    <div class="application-container">
        <?php if ($success): ?>
            <div class="success-banner" data-aos="fade-up">
                <i class="fas fa-check-circle fa-5x mb-4"></i>
                <h2 class="mb-4">Başvurunuz Alındı!</h2>
                <p class="lead mb-4">
                    Yazar başvurunuz başarıyla gönderildi. Başvurunuz admin ekibimiz tarafından incelenecek ve 
                    en kısa sürede size geri dönüş yapılacaktır.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="index.php" class="btn btn-light btn-lg">
                        <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                    </a>
                    <a href="profile.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user me-2"></i>Profilim
                    </a>
                </div>
            </div>
        <?php elseif ($has_pending_application): ?>
            <div class="status-banner" data-aos="fade-up">
                <i class="fas fa-clock fa-4x mb-4"></i>
                <h2 class="mb-4">Başvurunuz İnceleniyor</h2>
                <p class="lead mb-4">
                    <?php echo date('d.m.Y H:i', strtotime($existing_application['applied_at'])); ?> tarihinde 
                    yaptığınız yazar başvurunuz halen incelenmektedir.
                </p>
                <p class="mb-4">
                    Başvurunuzun durumu hakkında bilgi almak için admin ekibimizle iletişime geçebilirsiniz.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="index.php" class="btn btn-light btn-lg">
                        <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>İletişim
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="application-header" data-aos="fade-up">
                <h1 class="display-5 fw-bold mb-3">Yazar Başvurusu</h1>
                <p class="lead mb-0">
                    Platformumuzda yazar olmak için başvuru yapın ve kendi eserlerinizi paylaşmaya başlayın
                </p>
            </div>

            <!-- Gereksinimler -->
            <div class="requirements-card" data-aos="fade-up" data-aos-delay="100">
                <h4 class="mb-3">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Yazar Olmak İçin Gereksinimler
                </h4>
                <div class="requirement-item">
                    <i class="fas fa-check requirement-icon"></i>
                    <span>Orijinal ve kaliteli içerik üretme yeteneği</span>
                </div>
                <div class="requirement-item">
                    <i class="fas fa-check requirement-icon"></i>
                    <span>Manga veya çizgi roman konusunda deneyim</span>
                </div>
                <div class="requirement-item">
                    <i class="fas fa-check requirement-icon"></i>
                    <span>Düzenli içerik yayınlama taahhüdü</span>
                </div>
                <div class="requirement-item">
                    <i class="fas fa-check requirement-icon"></i>
                    <span>Telif hakları konusunda bilinçli olma</span>
                </div>
                <div class="requirement-item">
                    <i class="fas fa-check requirement-icon"></i>
                    <span>Topluluk kurallarına uyma</span>
                </div>
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

            <form method="POST" class="application-form" data-aos="fade-up" data-aos-delay="200">
                <?php echo getCSRFTokenInput(); ?>
                
                <!-- Kişisel Bilgiler -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>Kişisel Bilgiler
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label fw-bold">Ad Soyad *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-bold">E-posta *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">Telefon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" 
                               placeholder="+90 5XX XXX XX XX">
                        <div class="form-text">İletişim için kullanılacak (opsiyonel)</div>
                    </div>
                </div>

                <!-- Hakkınızda -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-pen"></i>Hakkınızda
                    </h3>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label fw-bold">Kişisel Tanıtım *</label>
                        <textarea class="form-control" id="bio" name="bio" rows="5" required 
                                  placeholder="Kendinizi tanıtın, ilgi alanlarınız, hobileriniz..."><?php echo isset($bio) ? htmlspecialchars($bio) : ''; ?></textarea>
                        <div class="form-text">En az 50 karakter</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="experience" class="form-label fw-bold">Deneyimleriniz</label>
                        <textarea class="form-control" id="experience" name="experience" rows="4" 
                                  placeholder="Manga/çizgi roman alanındaki deneyimleriniz, eğitimleriniz..."><?php echo isset($experience) ? htmlspecialchars($experience) : ''; ?></textarea>
                        <div class="form-text">Önceki çalışmalarınız, eğitimleriniz, sertifikalarınız (opsiyonel)</div>
                    </div>
                </div>

                <!-- Eserleriniz -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-palette"></i>Eserleriniz ve Portföy
                    </h3>
                    
                    <div class="mb-3">
                        <label for="portfolio_links" class="form-label fw-bold">Portföy Linkleri</label>
                        <textarea class="form-control" id="portfolio_links" name="portfolio_links" rows="3" 
                                  placeholder="Önceki çalışmalarınızın linklerini paylaşın (DeviantArt, ArtStation, kişisel site vb.)"><?php echo isset($portfolio_links) ? htmlspecialchars($portfolio_links) : ''; ?></textarea>
                        <div class="form-text">Her satıra bir link yazın (opsiyonel)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sample_work_description" class="form-label fw-bold">Örnek Çalışma Açıklaması</label>
                        <textarea class="form-control" id="sample_work_description" name="sample_work_description" rows="4" 
                                  placeholder="En beğendiğiniz çalışmanızı anlatın, hangi teknikler kullandınız, ne kadar sürdü..."><?php echo isset($sample_work_description) ? htmlspecialchars($sample_work_description) : ''; ?></textarea>
                        <div class="form-text">Çalışma sürecinizi ve tekniklerinizi anlatın (opsiyonel)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="preferred_genres" class="form-label fw-bold">Tercih Ettiğiniz Türler</label>
                        <input type="text" class="form-control" id="preferred_genres" name="preferred_genres" 
                               value="<?php echo isset($preferred_genres) ? htmlspecialchars($preferred_genres) : ''; ?>" 
                               placeholder="Aksiyon, Macera, Romantik, Komedi, Fantastik...">
                        <div class="form-text">Virgülle ayırın (opsiyonel)</div>
                    </div>
                </div>

                <!-- Motivasyon -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-heart"></i>Motivasyon
                    </h3>
                    
                    <div class="mb-3">
                        <label for="why_author" class="form-label fw-bold">Neden Yazar Olmak İstiyorsunuz? *</label>
                        <textarea class="form-control" id="why_author" name="why_author" rows="5" required 
                                  placeholder="Platformumuzda yazar olmak istemenizin nedenlerini açıklayın..."><?php echo isset($why_author) ? htmlspecialchars($why_author) : ''; ?></textarea>
                        <div class="form-text">En az 50 karakter - Motivasyonunuzu ve hedeflerinizi anlatın</div>
                    </div>
                </div>

                <!-- Sosyal Medya -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-share-alt"></i>Sosyal Medya
                    </h3>
                    
                    <div class="mb-3">
                        <label for="social_media_links" class="form-label fw-bold">Sosyal Medya Hesaplarınız</label>
                        <textarea class="form-control" id="social_media_links" name="social_media_links" rows="3" 
                                  placeholder="Instagram, Twitter, Facebook, YouTube vb. hesaplarınızın linklerini paylaşın"><?php echo isset($social_media_links) ? htmlspecialchars($social_media_links) : ''; ?></textarea>
                        <div class="form-text">Her satıra bir link yazın (opsiyonel)</div>
                    </div>
                </div>

                <!-- Onay ve Gönder -->
                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        Verdiğim bilgilerin doğru olduğunu, <a href="terms.php" target="_blank">kullanım şartlarını</a> 
                        ve <a href="privacy.php" target="_blank">gizlilik politikasını</a> kabul ettiğimi onaylıyorum.
                    </label>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-paper-plane me-2"></i>Başvuruyu Gönder
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Form validasyonu
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.application-form');
    const textareas = form.querySelectorAll('textarea[required]');
    
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            const minLength = this.id === 'bio' || this.id === 'why_author' ? 50 : 0;
            const currentLength = this.value.length;
            
            // Karakter sayacı ekle
            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter form-text mt-1';
                this.parentNode.appendChild(counter);
            }
            
            if (minLength > 0) {
                counter.textContent = `${currentLength}/${minLength} karakter (minimum)`;
                counter.className = `char-counter form-text mt-1 ${currentLength >= minLength ? 'text-success' : 'text-warning'}`;
            }
        });
        
        // İlk yüklemede de çalıştır
        textarea.dispatchEvent(new Event('input'));
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 