<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

$success_message = '';
$error_message = '';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validasyon
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($message) < 10) {
        $error_message = 'Mesajınız en az 10 karakter olmalıdır.';
    } else {
        // Veritabanına kaydet
        $query = "INSERT INTO contact_messages (name, email, subject, message, created_at) 
                  VALUES ('$name', '$email', '$subject', '$message', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $success_message = 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.';
            
            // Admin bildirimi oluştur
            $admin_query = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                           SELECT id, 'system', 'Yeni İletişim Mesajı', 
                           'Yeni bir iletişim mesajı alındı: $subject', NOW() 
                           FROM users WHERE role = 'admin'";
            mysqli_query($conn, $admin_query);
            
            // Formu temizle
            $name = $email = $subject = $message = '';
        } else {
            $error_message = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}

$page_title = 'İletişim';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #667eea, #764ba2);">
    <div class="container">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
                <i class="fas fa-envelope me-3"></i>İletişim
            </h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                Bizimle iletişime geçin, sorularınızı ve önerilerinizi paylaşın
            </p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <!-- İletişim Formu -->
        <div class="col-lg-8">
            <div class="card shadow-lg" data-aos="fade-right">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-paper-plane me-2"></i>Mesaj Gönder
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="contactForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Ad Soyad *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Konu *</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Konu seçin...</option>
                                <option value="Genel Soru" <?php echo (isset($subject) && $subject === 'Genel Soru') ? 'selected' : ''; ?>>Genel Soru</option>
                                <option value="Teknik Destek" <?php echo (isset($subject) && $subject === 'Teknik Destek') ? 'selected' : ''; ?>>Teknik Destek</option>
                                <option value="İçerik Sorunu" <?php echo (isset($subject) && $subject === 'İçerik Sorunu') ? 'selected' : ''; ?>>İçerik Sorunu</option>
                                <option value="Hesap Sorunu" <?php echo (isset($subject) && $subject === 'Hesap Sorunu') ? 'selected' : ''; ?>>Hesap Sorunu</option>
                                <option value="Öneri" <?php echo (isset($subject) && $subject === 'Öneri') ? 'selected' : ''; ?>>Öneri</option>
                                <option value="Şikayet" <?php echo (isset($subject) && $subject === 'Şikayet') ? 'selected' : ''; ?>>Şikayet</option>
                                <option value="Diğer" <?php echo (isset($subject) && $subject === 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Mesajınız *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Mesajınızı buraya yazın..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <div class="form-text">Minimum 10 karakter</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Mesajı Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- İletişim Bilgileri -->
        <div class="col-lg-4">
            <div class="card shadow-lg mb-4" data-aos="fade-left" data-aos-delay="100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>İletişim Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="fas fa-envelope text-primary me-2"></i>E-posta
                        </h6>
                        <p class="text-muted mb-0">info@mangasite.com</p>
                        <p class="text-muted">destek@mangasite.com</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="fas fa-phone text-success me-2"></i>Telefon
                        </h6>
                        <p class="text-muted mb-0">+90 (212) 555 0123</p>
                        <p class="text-muted">Hafta içi 09:00 - 18:00</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>Adres
                        </h6>
                        <p class="text-muted">
                            Manga Sitesi<br>
                            Beşiktaş/İstanbul<br>
                            Türkiye
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">
                            <i class="fas fa-clock text-warning me-2"></i>Çalışma Saatleri
                        </h6>
                        <p class="text-muted mb-1">Pazartesi - Cuma: 09:00 - 18:00</p>
                        <p class="text-muted mb-1">Cumartesi: 10:00 - 16:00</p>
                        <p class="text-muted">Pazar: Kapalı</p>
                    </div>
                </div>
            </div>
            
            <!-- Sosyal Medya -->
            <div class="card shadow-lg" data-aos="fade-left" data-aos-delay="200">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-share-alt me-2"></i>Sosyal Medya
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fab fa-facebook-f me-2"></i>Facebook
                        </a>
                        <a href="#" class="btn btn-outline-info">
                            <i class="fab fa-twitter me-2"></i>Twitter
                        </a>
                        <a href="#" class="btn btn-outline-danger">
                            <i class="fab fa-instagram me-2"></i>Instagram
                        </a>
                        <a href="#" class="btn btn-outline-success">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </a>
                        <a href="#" class="btn btn-outline-secondary">
                            <i class="fab fa-telegram me-2"></i>Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SSS Bölümü -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Sıkça Sorulan Sorular
                    </h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    Siteye nasıl içerik yükleyebilirim?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Siteye içerik yüklemek için önce kayıt olmanız gerekir. Kayıt olduktan sonra "İçerik Yükle" bölümünden manga veya çizgi romanınızı yükleyebilirsiniz. Yüklediğiniz içerik moderasyon sürecinden geçtikten sonra yayınlanır.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    İçeriğim neden yayınlanmıyor?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yüklediğiniz içerik site kurallarına uygunluk açısından incelenir. Telif hakkı ihlali, uygunsuz içerik veya teknik sorunlar nedeniyle içeriğiniz reddedilebilir. Detaylı bilgi için bizimle iletişime geçebilirsiniz.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    Hesabımı nasıl silebilirim?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Hesabınızı silmek için profil ayarlarından "Hesabı Sil" seçeneğini kullanabilir veya bizimle iletişime geçebilirsiniz. Hesap silme işlemi geri alınamaz ve tüm verileriniz kalıcı olarak silinir.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                    Mobil uygulama var mı?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Şu anda mobil uygulamamız bulunmamaktadır, ancak sitemiz mobil cihazlarda mükemmel çalışacak şekilde optimize edilmiştir. Mobil tarayıcınızdan rahatlıkla siteyi kullanabilirsiniz.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validasyonu
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const message = document.getElementById('message').value;
    if (message.length < 10) {
        e.preventDefault();
        alert('Mesajınız en az 10 karakter olmalıdır.');
        return false;
    }
});

// Karakter sayacı
document.getElementById('message').addEventListener('input', function() {
    const length = this.value.length;
    const formText = this.nextElementSibling;
    formText.textContent = `${length}/1000 karakter (minimum 10)`;
    
    if (length < 10) {
        formText.className = 'form-text text-danger';
    } else {
        formText.className = 'form-text text-success';
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 