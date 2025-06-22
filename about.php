<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// İstatistikler
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM content WHERE status = 'published') as total_content,
    (SELECT COUNT(*) FROM content WHERE type = 'manga' AND status = 'published') as total_manga,
    (SELECT COUNT(*) FROM content WHERE type = 'comic' AND status = 'published') as total_comics,
    (SELECT SUM(views) FROM content WHERE status = 'published') as total_views,
    (SELECT COUNT(*) FROM comments) as total_comments";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Admin ayarlarından site bilgilerini al
$site_name = getSetting('site_name', 'Manga & Comic Hub');
$site_description = getSetting('site_description', 'Manga ve çizgi roman paylaşım platformu');
$contact_email = getSetting('contact_email', getSetting('admin_email', 'info@example.com'));

$page_title = 'Hakkımızda';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #667eea, #764ba2);">
    <div class="container">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">
                <i class="fas fa-info-circle me-3"></i>Hakkımızda
            </h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                <?php echo htmlspecialchars($site_description); ?>
            </p>
        </div>
    </div>
</section>

<div class="container my-5">
    <!-- Misyon ve Vizyon -->
    <div class="row mb-5">
        <div class="col-lg-6" data-aos="fade-right">
            <div class="card h-100 shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-bullseye me-2"></i>Misyonumuz
                    </h3>
                </div>
                <div class="card-body">
                                                <p class="lead">
                                <?php echo nl2br(htmlspecialchars(getSetting('about_mission', 'Manga ve çizgi roman tutkunlarını bir araya getirerek, kaliteli içeriklerin paylaşıldığı, etkileşimli ve kullanıcı dostu bir platform sunmak.'))); ?>
                            </p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6" data-aos="fade-left">
            <div class="card h-100 shadow-lg">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Vizyonumuz
                    </h3>
                </div>
                <div class="card-body">
                    <p class="lead">
                        <?php echo nl2br(htmlspecialchars(getSetting('about_vision', 'Türkiye\'nin en büyük ve en güvenilir manga-çizgi roman paylaşım platformu olmak.'))); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0 text-center">
                        <i class="fas fa-chart-bar me-2"></i>Rakamlarla Biz
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_users']); ?></h3>
                                <p class="text-muted">Kayıtlı Kullanıcı</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-book fa-3x text-success mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_content']); ?></h3>
                                <p class="text-muted">Toplam İçerik</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-dragon fa-3x text-danger mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_manga']); ?></h3>
                                <p class="text-muted">Manga</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-mask fa-3x text-info mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_comics']); ?></h3>
                                <p class="text-muted">Çizgi Roman</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-eye fa-3x text-warning mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_views']); ?></h3>
                                <p class="text-muted">Toplam Görüntülenme</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-4">
                            <div class="stat-item">
                                <i class="fas fa-comments fa-3x text-secondary mb-3"></i>
                                <h3 class="fw-bold"><?php echo number_format($stats['total_comments']); ?></h3>
                                <p class="text-muted">Yorum</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hikayemiz -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-history me-2"></i>Hikayemiz
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div>
                                <?php echo nl2br(htmlspecialchars(getSetting('about_story', '2024 yılında manga ve çizgi roman tutkusu ile başlayan yolculuğumuz... Bir grup manga ve çizgi roman severin "Keşke tüm sevdiğimiz içerikleri tek bir yerde bulabilsek" hayaliyle başlayan projemiz, bugün binlerce kullanıcının güvenle kullandığı bir platforma dönüştü.'))); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6>2024 - Başlangıç</h6>
                                        <p class="small">Platform kuruldu</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6>İlk 100 Kullanıcı</h6>
                                        <p class="small">Topluluk büyümeye başladı</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <h6>1000+ İçerik</h6>
                                        <p class="small">Zengin içerik arşivi</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6>Şimdi</h6>
                                        <p class="small">Sürekli gelişim</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Özellikler -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-dark text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-star me-2"></i><?php echo htmlspecialchars(getSetting('about_features_title', 'Neden Bizi Seçmelisiniz?')); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_security_title', 'Güvenli Platform')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_security_desc', 'Verileriniz ve gizliliğiniz bizim için çok önemli. En son güvenlik teknolojilerini kullanıyoruz.'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_speed_title', 'Hızlı ve Stabil')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_speed_desc', 'Optimize edilmiş altyapımızla hızlı yükleme süreleri ve kesintisiz okuma deneyimi sunuyoruz.'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-mobile-alt fa-3x text-warning mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_mobile_title', 'Mobil Uyumlu')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_mobile_desc', 'Tüm cihazlarda mükemmel çalışan responsive tasarımımızla her yerden erişim sağlayın.'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-users fa-3x text-info mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_community_title', 'Aktif Topluluk')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_community_desc', 'Binlerce aktif kullanıcımızla yorumlar, beğeniler ve etkileşimlerle zengin bir deneyim yaşayın.'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-search fa-3x text-danger mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_search_title', 'Gelişmiş Arama')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_search_desc', 'Güçlü arama ve filtreleme seçenekleriyle aradığınız içeriği kolayca bulun.'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="feature-item text-center">
                                <i class="fas fa-headset fa-3x text-secondary mb-3"></i>
                                <h5><?php echo htmlspecialchars(getSetting('about_features_support_title', '7/24 Destek')); ?></h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars(getSetting('about_features_support_desc', 'Sorularınız ve sorunlarınız için her zaman ulaşabileceğiniz destek ekibimiz var.'))); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ekip -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-gradient-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i><?php echo htmlspecialchars(getSetting('about_team_title', 'Ekibimiz')); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="team-member text-center">
                                <div class="member-avatar mb-3">
                                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                                </div>
                                <h5><?php echo htmlspecialchars(getSetting('about_team_member1_name', 'Zeki Kurt')); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars(getSetting('about_team_member1_role', 'Kurucu & CEO')); ?></p>
                                <p class="small"><?php echo htmlspecialchars(getSetting('about_team_member1_desc', 'Manga tutkunu ve teknoloji uzmanı')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="team-member text-center">
                                <div class="member-avatar mb-3">
                                    <i class="fas fa-user-circle fa-5x text-success"></i>
                                </div>
                                <h5><?php echo htmlspecialchars(getSetting('about_team_member2_name', 'Ayşe Demir')); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars(getSetting('about_team_member2_role', 'İçerik Editörü')); ?></p>
                                <p class="small"><?php echo htmlspecialchars(getSetting('about_team_member2_desc', 'Çizgi roman uzmanı ve editör')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="team-member text-center">
                                <div class="member-avatar mb-3">
                                    <i class="fas fa-user-circle fa-5x text-warning"></i>
                                </div>
                                <h5><?php echo htmlspecialchars(getSetting('about_team_member3_name', 'HelixPrime')); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars(getSetting('about_team_member3_role', 'Geliştirici')); ?></p>
                                <p class="small"><?php echo htmlspecialchars(getSetting('about_team_member3_desc', 'Full-stack developer')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="team-member text-center">
                                <div class="member-avatar mb-3">
                                    <i class="fas fa-user-circle fa-5x text-info"></i>
                                </div>
                                <h5><?php echo htmlspecialchars(getSetting('about_team_member4_name', 'Fatma Özkan')); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars(getSetting('about_team_member4_role', 'Topluluk Yöneticisi')); ?></p>
                                <p class="small"><?php echo htmlspecialchars(getSetting('about_team_member4_desc', 'Kullanıcı deneyimi uzmanı')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İletişim CTA -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg text-center" data-aos="fade-up">
                <div class="card-body bg-gradient-primary text-white">
                    <h3 class="mb-3">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars(getSetting('about_contact_title', 'Bizimle İletişime Geçin')); ?>
                    </h3>
                    <p class="lead mb-4">
                        <?php echo nl2br(htmlspecialchars(getSetting('about_contact_desc', 'Sorularınız, önerileriniz veya işbirliği teklifleriniz için bize ulaşın'))); ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="contact.php" class="btn btn-light btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>İletişim Formu
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-envelope me-2"></i>E-posta Gönder
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ek İçerik (Admin Panelinden Düzenlenebilir) -->
    <?php 
    $extra_content = getSetting('about_extra_content');
    if (!empty($extra_content)): 
    ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg" data-aos="fade-up">
                <div class="card-header bg-gradient-info text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Ek Bilgiler
                    </h3>
                </div>
                <div class="card-body">
                    <?php echo $extra_content; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.feature-item:hover {
    transform: translateY(-5px);
    transition: transform 0.3s ease;
}

.team-member:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>

<?php
require_once 'includes/footer.php';
?> 