<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Admin ayarlarından site bilgilerini al
$site_name = getSetting('site_name', 'Manga & Comic Hub');
$contact_email = getSetting('contact_email', getSetting('admin_email', 'info@example.com'));

$page_title = 'Kullanım Koşulları';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-2 bg-success text-white">
    <div class="container">
        <div class="text-center">
            <h1 class="h5 mb-1">
                <i class="fas fa-file-contract me-2"></i>Kullanım Koşulları
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
                            <i class="fas fa-handshake me-1"></i>Kabul ve Onay
                        </a>
                        <a href="#section2" class="nav-link py-1 px-2 small" data-section="section2">
                            <i class="fas fa-user-plus me-1"></i>Hesap Kuralları
                        </a>
                        <a href="#section3" class="nav-link py-1 px-2 small" data-section="section3">
                            <i class="fas fa-upload me-1"></i>İçerik Paylaşımı
                        </a>
                        <a href="#section4" class="nav-link py-1 px-2 small" data-section="section4">
                            <i class="fas fa-ban me-1"></i>Yasaklı Davranışlar
                        </a>
                        <a href="#section5" class="nav-link py-1 px-2 small" data-section="section5">
                            <i class="fas fa-copyright me-1"></i>Telif Hakları
                        </a>
                        <a href="#section6" class="nav-link py-1 px-2 small" data-section="section6">
                            <i class="fas fa-gavel me-1"></i>Sorumluluk
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
                    <div class="alert alert-success py-2 mb-2">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($site_name); ?></strong> platformunu kullanarak aşağıdaki kullanım koşullarını kabul etmiş sayılırsınız.
                    </div>
                </div>

                <!-- 1. Kabul ve Onay -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section1">
                        <div class="card-header py-1 bg-success text-white">
                            <h6 class="mb-0 small"><i class="fas fa-handshake me-1"></i>1. Kabul ve Onay</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="small">
                                <div class="mb-2">
                                    <strong><i class="fas fa-check text-success me-1"></i>Kabul Koşulları:</strong><br>
                                    • Siteyi kullanarak koşulları kabul edersiniz<br>
                                    • 13 yaş altı kullanıcılar ebeveyn izni almalı<br>
                                    • Koşulları düzenli olarak kontrol edin<br>
                                    • Değişiklikler derhal yürürlüğe girer
                                </div>
                                <div>
                                    <strong><i class="fas fa-user-check text-primary me-1"></i>Kullanıcı Onayı:</strong><br>
                                    Bu koşulları kabul etmiyorsanız siteyi kullanmayın
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Hesap Kuralları -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section2">
                        <div class="card-header py-1 bg-primary text-white">
                            <h6 class="mb-0 small"><i class="fas fa-user-plus me-1"></i>2. Hesap Kuralları</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-1 small">
                                <div class="col-6">
                                    <strong><i class="fas fa-user text-primary me-1"></i>Hesap Oluşturma:</strong><br>
                                    • Doğru bilgi verin<br>
                                    • Tek hesap açın<br>
                                    • Güçlü şifre kullanın<br>
                                    • E-posta doğrulayın
                                </div>
                                <div class="col-6">
                                    <strong><i class="fas fa-shield text-danger me-1"></i>Güvenlik:</strong><br>
                                    • Şifrenizi paylaşmayın<br>
                                    • Şüpheli aktiviteyi bildirin<br>
                                    • Hesabınızı koruyun<br>
                                    • Çıkış yapmayı unutmayın
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. İçerik Paylaşımı -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section3">
                        <div class="card-header py-1 bg-info text-white">
                            <h6 class="mb-0 small"><i class="fas fa-upload me-1"></i>3. İçerik Paylaşımı</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="small">
                                <div class="mb-2">
                                    <strong><i class="fas fa-check-circle text-success me-1"></i>İzin Verilen:</strong><br>
                                    • Orijinal manga/çizgi roman içeriği<br>
                                    • Telif hakkı size ait olan eserler<br>
                                    • Creative Commons lisanslı içerik<br>
                                    • Fan art (orijinal karakterlere dayalı)
                                </div>
                                <div>
                                    <strong><i class="fas fa-exclamation-triangle text-warning me-1"></i>Dikkat:</strong><br>
                                    Yüklediğiniz içeriğin yasal sorumluluğu size aittir
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Yasaklı Davranışlar -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section4">
                        <div class="card-header py-1 bg-danger text-white">
                            <h6 class="mb-0 small"><i class="fas fa-ban me-1"></i>4. Yasaklı Davranışlar</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-1 small">
                                <div class="col-6">
                                    <strong><i class="fas fa-times text-danger me-1"></i>Kesinlikle Yasak:</strong><br>
                                    • Telif hakkı ihlali<br>
                                    • Spam ve reklam<br>
                                    • Hakaret ve küfür<br>
                                    • Zararlı yazılım
                                </div>
                                <div class="col-6">
                                    <strong><i class="fas fa-user-slash text-warning me-1"></i>Hesap Kapatma:</strong><br>
                                    • Kurallara uymama<br>
                                    • Tekrarlayan ihlaller<br>
                                    • Sahte hesap<br>
                                    • Yasal sorunlar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Telif Hakları -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section5">
                        <div class="card-header py-1 bg-warning text-dark">
                            <h6 class="mb-0 small"><i class="fas fa-copyright me-1"></i>5. Telif Hakları</h6>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="mb-2">
                                <strong><i class="fas fa-shield-alt text-primary me-1"></i>Koruma:</strong><br>
                                Tüm içerikler telif hakkı yasalarıyla korunmaktadır
                            </div>
                            <div class="mb-2">
                                <strong><i class="fas fa-exclamation text-danger me-1"></i>İhlal Bildirimi:</strong><br>
                                DMCA uyarınca telif ihlallerini derhal bildirin
                            </div>
                            <div>
                                <strong><i class="fas fa-trash text-warning me-1"></i>Kaldırma:</strong><br>
                                İhlal tespit edilen içerikler anında kaldırılır
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Sorumluluk -->
                <div class="col-lg-6">
                    <div class="card h-100" id="section6">
                        <div class="card-header py-1 bg-secondary text-white">
                            <h6 class="mb-0 small"><i class="fas fa-gavel me-1"></i>6. Sorumluluk</h6>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="mb-2">
                                <strong><i class="fas fa-user text-info me-1"></i>Kullanıcı Sorumluluğu:</strong><br>
                                • Paylaştığınız içeriğin yasal sorumluluğu<br>
                                • Hesap güvenliğiniz<br>
                                • Kurallara uyum
                            </div>
                            <div>
                                <strong><i class="fas fa-building text-success me-1"></i>Platform Sorumluluğu:</strong><br>
                                • Hizmet sürekliliği (mümkün olduğunca)<br>
                                • Kullanıcı verilerinin korunması<br>
                                • Yasal gerekliliklere uyum
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Önemli Notlar -->
                <div class="col-12">
                    <div class="row g-1">
                        <div class="col-md-4">
                            <div class="alert alert-warning py-1 mb-1 small">
                                <i class="fas fa-exclamation-triangle me-1"></i><strong>Uyarı:</strong> Kuralları ihlal eden hesaplar kapatılır
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info py-1 mb-1 small">
                                <i class="fas fa-balance-scale me-1"></i><strong>Hukuk:</strong> Türkiye Cumhuriyeti yasaları geçerlidir
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success py-1 mb-1 small">
                                <i class="fas fa-handshake me-1"></i><strong>Anlaşmazlık:</strong> Önce dostane çözüm aranır
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ek Bilgiler -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header py-1 bg-dark text-white">
                            <h6 class="mb-0 small"><i class="fas fa-info me-1"></i>Ek Bilgiler</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2 small">
                                <div class="col-md-3">
                                    <strong><i class="fas fa-edit text-primary me-1"></i>Değişiklikler:</strong><br>
                                    Koşullar önceden haber verilmeksizin değiştirilebilir
                                </div>
                                <div class="col-md-3">
                                    <strong><i class="fas fa-globe text-success me-1"></i>Geçerlilik:</strong><br>
                                    Bu koşullar tüm kullanıcılar için geçerlidir
                                </div>
                                <div class="col-md-3">
                                    <strong><i class="fas fa-envelope text-info me-1"></i>İletişim:</strong><br>
                                    Sorularınız için: <?php echo htmlspecialchars($contact_email); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong><i class="fas fa-calendar text-warning me-1"></i>Yürürlük:</strong><br>
                                    Bu koşullar <?php echo date('d.m.Y'); ?> tarihinden itibaren geçerlidir
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aksiyon Butonları -->
                <div class="col-12">
                    <div class="text-center">
                        <a href="index.php" class="btn btn-success btn-sm me-1">
                            <i class="fas fa-check me-1"></i>Kabul Ediyorum
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary btn-sm me-1">
                            <i class="fas fa-question me-1"></i>Soru Sor
                        </a>
                        <a href="privacy.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user-shield me-1"></i>Gizlilik Politikası
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ek İçerik (Admin Panelinden Düzenlenebilir) -->
    <?php 
    $extra_content = getSetting('terms_extra_content');
    if (!empty($extra_content)): 
    ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-1 bg-gradient-success text-white">
                    <h6 class="mb-0 small"><i class="fas fa-plus-circle me-1"></i>Ek Koşullar</h6>
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
    color: #198754;
    transform: translateX(2px);
}

.nav-link.active {
    background-color: #198754;
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