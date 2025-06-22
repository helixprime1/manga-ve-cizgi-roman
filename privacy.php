<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Admin ayarlarından site bilgilerini al
$site_name = getSetting('site_name', 'Manga & Comic Hub');
$contact_email = getSetting('contact_email', getSetting('admin_email', 'info@example.com'));

$page_title = 'Gizlilik Politikası';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-2 bg-primary text-white">
    <div class="container">
        <div class="text-center">
            <h1 class="h5 mb-1">
                <i class="fas fa-shield-alt me-2"></i>Gizlilik Politikası
            </h1>
            <p class="small mb-0">Son Güncelleme: <?php echo date('d.m.Y'); ?></p>
        </div>
    </div>
</section>

<div class="container-fluid py-2">
    <div class="row g-2">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card h-100">
                <div class="card-header py-1 bg-light">
                    <h6 class="mb-0 small"><i class="fas fa-list me-1"></i>İçindekiler</h6>
                </div>
                <div class="card-body p-2">
                    <nav class="nav flex-column">
                        <a href="#section1" class="nav-link py-1 px-2 small active" data-section="section1">
                            <i class="fas fa-database me-1"></i>Toplanan Veriler
                        </a>
                        <a href="#section2" class="nav-link py-1 px-2 small" data-section="section2">
                            <i class="fas fa-cogs me-1"></i>Kullanım Amaçları
                        </a>
                        <a href="#section3" class="nav-link py-1 px-2 small" data-section="section3">
                            <i class="fas fa-cookie-bite me-1"></i>Çerezler
                        </a>
                        <a href="#section4" class="nav-link py-1 px-2 small" data-section="section4">
                            <i class="fas fa-shield-alt me-1"></i>Güvenlik
                        </a>
                        <a href="#section5" class="nav-link py-1 px-2 small" data-section="section5">
                            <i class="fas fa-user-check me-1"></i>Haklarınız
                        </a>
                        <a href="#section6" class="nav-link py-1 px-2 small" data-section="section6">
                            <i class="fas fa-envelope me-1"></i>İletişim
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Ana İçerik -->
        <div class="col-md-9 col-lg-10">
            <div class="row g-2">
                <!-- Giriş Bilgisi -->
                <div class="col-12">
                    <div class="alert alert-info py-2 mb-2">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($site_name); ?></strong> platformunda kişisel verilerinizin nasıl işlendiğini açıklayan KVKK ve GDPR uyumlu gizlilik politikası.
                    </div>
                </div>

                <!-- 1. Toplanan Veriler -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section1">
                        <div class="card-header py-1 bg-primary text-white">
                            <h6 class="mb-0 small"><i class="fas fa-database me-1"></i>1. Toplanan Veriler</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-1 small">
                                <div class="col-6">
                                    <strong><i class="fas fa-user text-primary me-1"></i>Hesap:</strong><br>
                                    • Kullanıcı adı<br>
                                    • E-posta adresi<br>
                                    • Şifre (şifreli)<br>
                                    • Profil fotoğrafı
                                </div>
                                <div class="col-6">
                                    <strong><i class="fas fa-chart-line text-success me-1"></i>Kullanım:</strong><br>
                                    • IP adresi<br>
                                    • Tarayıcı bilgisi<br>
                                    • Sayfa ziyaretleri<br>
                                    • Beğeni/yorumlar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Kullanım Amaçları -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section2">
                        <div class="card-header py-1 bg-success text-white">
                            <h6 class="mb-0 small"><i class="fas fa-cogs me-1"></i>2. Kullanım Amaçları</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-1 small">
                                <div class="col-6">
                                    <strong><i class="fas fa-check text-success me-1"></i>Hizmetler:</strong><br>
                                    • Hesap yönetimi<br>
                                    • Kimlik doğrulama<br>
                                    • İçerik paylaşımı<br>
                                    • Kişiselleştirme
                                </div>
                                <div class="col-6">
                                    <strong><i class="fas fa-shield text-danger me-1"></i>Güvenlik:</strong><br>
                                    • Spam koruması<br>
                                    • Güvenlik izleme<br>
                                    • Performans analizi<br>
                                    • Hata tespiti
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Çerezler -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section3">
                        <div class="card-header py-1 bg-warning text-dark">
                            <h6 class="mb-0 small"><i class="fas fa-cookie-bite me-1"></i>3. Çerezler</h6>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="mb-1">
                                <strong><i class="fas fa-exclamation-circle text-danger me-1"></i>Gerekli Çerezler:</strong>
                                <span class="badge bg-danger ms-1">Zorunlu</span><br>
                                Oturum yönetimi ve güvenlik için gerekli
                            </div>
                            <div>
                                <strong><i class="fas fa-chart-bar text-info me-1"></i>Analitik Çerezler:</strong>
                                <span class="badge bg-warning ms-1">İsteğe Bağlı</span><br>
                                Google Analytics ile site performansı analizi
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Güvenlik -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section4">
                        <div class="card-header py-1 bg-danger text-white">
                            <h6 class="mb-0 small"><i class="fas fa-shield-alt me-1"></i>4. Veri Güvenliği</h6>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="mb-1">
                                <strong><i class="fas fa-lock text-primary me-1"></i>Teknik Güvenlik:</strong><br>
                                SSL şifreleme, güvenli veritabanı, firewall koruması
                            </div>
                            <div>
                                <strong><i class="fas fa-users-cog text-success me-1"></i>İdari Güvenlik:</strong><br>
                                Erişim kontrolü, düzenli denetim, veri yedekleme
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Kullanıcı Hakları -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section5">
                        <div class="card-header py-1 bg-info text-white">
                            <h6 class="mb-0 small"><i class="fas fa-user-check me-1"></i>5. KVKK/GDPR Hakları</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-1 small">
                                <div class="col-6">
                                    <i class="fas fa-eye text-primary me-1"></i><strong>Erişim</strong><br>
                                    <i class="fas fa-edit text-success me-1"></i><strong>Düzeltme</strong><br>
                                    <i class="fas fa-trash text-danger me-1"></i><strong>Silme</strong>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-download text-info me-1"></i><strong>Taşınabilirlik</strong><br>
                                    <i class="fas fa-ban text-warning me-1"></i><strong>İtiraz</strong><br>
                                    <i class="fas fa-pause text-secondary me-1"></i><strong>Sınırlama</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. İletişim -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section6">
                        <div class="card-header py-1 bg-success text-white">
                            <h6 class="mb-0 small"><i class="fas fa-envelope me-1"></i>6. İletişim</h6>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="mb-1">
                                <strong><i class="fas fa-envelope text-primary me-1"></i>E-posta:</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($contact_email); ?>
                                </a>
                            </div>
                            <div>
                                <strong><i class="fas fa-clock text-info me-1"></i>Yanıt Süresi:</strong><br>
                                3-30 gün (talep türüne göre)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Önemli Notlar -->
                <div class="col-12">
                    <div class="row g-1">
                        <div class="col-md-4">
                            <div class="alert alert-info py-1 mb-1 small">
                                <i class="fas fa-shield me-1"></i><strong>Güvence:</strong> Verileriniz satılmaz veya paylaşılmaz
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning py-1 mb-1 small">
                                <i class="fas fa-external-link me-1"></i><strong>3. Taraf:</strong> Sadece Google Analytics kullanılır
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success py-1 mb-1 small">
                                <i class="fas fa-bell me-1"></i><strong>Değişiklik:</strong> 30 gün önceden bildirilir
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aksiyon Butonları -->
                <div class="col-12">
                    <div class="text-center">
                        <a href="index.php" class="btn btn-success btn-sm me-1">
                            <i class="fas fa-check me-1"></i>Anladım
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-sm me-1">
                            <i class="fas fa-question me-1"></i>Soru Sor
                        </a>
                        <a href="terms.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file-contract me-1"></i>Kullanım Koşulları
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ek İçerik (Admin Panelinden Düzenlenebilir) -->
    <?php 
    $extra_content = getSetting('privacy_extra_content');
    if (!empty($extra_content)): 
    ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-1 bg-gradient-primary text-white">
                    <h6 class="mb-0 small"><i class="fas fa-plus-circle me-1"></i>Ek Gizlilik Bilgileri</h6>
                </div>
                <div class="card-body p-2">
                    <?php echo $extra_content; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Kompakt tasarım için özel CSS */
.container-fluid {
    max-width: 1400px;
}

.nav-link {
    color: #495057;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
    transform: translateX(2px);
}

.nav-link.active {
    background-color: #0d6efd;
    color: white !important;
    font-weight: 600;
}

.nav-link.active i {
    color: white;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transition: box-shadow 0.15s ease-in-out;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}

html {
    scroll-behavior: smooth;
}

.alert {
    border: none;
    border-left: 3px solid;
}

.alert-info {
    border-left-color: #0dcaf0;
    background-color: #cff4fc;
}

.alert-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
}

.alert-success {
    border-left-color: #198754;
    background-color: #d1e7dd;
}

/* Responsive optimizasyonlar */
@media (max-width: 768px) {
    .container-fluid {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    .card-body {
        padding: 0.5rem !important;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .row.g-1 > * {
        padding: 0.125rem;
    }
}

@media (max-width: 576px) {
    .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dinamik navigasyon
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    const sections = document.querySelectorAll('[id^="section"]');
    
    // Intersection Observer
    const observerOptions = {
        root: null,
        rootMargin: '-20% 0px -70% 0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const sectionId = entry.target.id;
                
                // Tüm nav linklerden active kaldır
                navLinks.forEach(link => link.classList.remove('active'));
                
                // İlgili nav link'e active ekle
                const activeLink = document.querySelector(`[data-section="${sectionId}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });
    }, observerOptions);
    
    // Bölümleri gözlemle
    sections.forEach(section => observer.observe(section));
    
    // Nav link tıklama eventi
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-section');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // URL güncelle
                history.replaceState(null, null, `#${targetId}`);
            }
        });
    });
    
    // Sayfa yüklendiğinde hash kontrolü
    if (window.location.hash) {
        const hashTarget = window.location.hash.substring(1);
        const targetElement = document.getElementById(hashTarget);
        
        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 