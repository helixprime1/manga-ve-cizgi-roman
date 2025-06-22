<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$admin_user = getCurrentUser();
$page_title = 'Sayfa Yönetimi';

// İstatistikleri al (sidebar için gerekli)
$stats = [];
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Varsayılan sayfa ayarlarını ekle (sadece yoksa)
$default_page_settings = [
    ['about_mission', 'Manga ve çizgi roman tutkunlarını bir araya getirerek, kaliteli içeriklerin paylaşıldığı, etkileşimli ve kullanıcı dostu bir platform sunmak.', 'textarea', 'Hakkımızda - Misyonumuz', 'pages'],
    ['about_vision', 'Türkiye\'nin en büyük ve en güvenilir manga-çizgi roman paylaşım platformu olmak.', 'textarea', 'Hakkımızda - Vizyonumuz', 'pages'],
    ['about_story', '2024 yılında manga ve çizgi roman tutkusu ile başlayan yolculuğumuz... Bir grup manga ve çizgi roman severin "Keşke tüm sevdiğimiz içerikleri tek bir yerde bulabilsek" hayaliyle başlayan projemiz, bugün binlerce kullanıcının güvenle kullandığı bir platforma dönüştü.', 'textarea', 'Hakkımızda - Hikayemiz', 'pages'],
    ['about_extra_content', '', 'textarea', 'Hakkımızda - Ek İçerik (HTML)', 'pages'],
    ['terms_extra_content', '', 'textarea', 'Kullanım Koşulları - Ek İçerik (HTML)', 'pages'],
    ['privacy_extra_content', '', 'textarea', 'Gizlilik Politikası - Ek İçerik (HTML)', 'pages'],
    
    // About sayfası ek içerikleri
    ['about_features_title', 'Neden Bizi Seçmelisiniz?', 'text', 'Özellikler Bölüm Başlığı', 'pages'],
    ['about_features_security_title', 'Güvenli Platform', 'text', 'Güvenlik Özellik Başlığı', 'pages'],
    ['about_features_security_desc', 'Verileriniz ve gizliliğiniz bizim için çok önemli. En son güvenlik teknolojilerini kullanıyoruz.', 'textarea', 'Güvenlik Özellik Açıklaması', 'pages'],
    ['about_features_speed_title', 'Hızlı ve Stabil', 'text', 'Hız Özellik Başlığı', 'pages'],
    ['about_features_speed_desc', 'Optimize edilmiş altyapımızla hızlı yükleme süreleri ve kesintisiz okuma deneyimi sunuyoruz.', 'textarea', 'Hız Özellik Açıklaması', 'pages'],
    ['about_features_mobile_title', 'Mobil Uyumlu', 'text', 'Mobil Özellik Başlığı', 'pages'],
    ['about_features_mobile_desc', 'Tüm cihazlarda mükemmel çalışan responsive tasarımımızla her yerden erişim sağlayın.', 'textarea', 'Mobil Özellik Açıklaması', 'pages'],
    ['about_features_community_title', 'Aktif Topluluk', 'text', 'Topluluk Özellik Başlığı', 'pages'],
    ['about_features_community_desc', 'Binlerce aktif kullanıcımızla yorumlar, beğeniler ve etkileşimlerle zengin bir deneyim yaşayın.', 'textarea', 'Topluluk Özellik Açıklaması', 'pages'],
    ['about_features_search_title', 'Gelişmiş Arama', 'text', 'Arama Özellik Başlığı', 'pages'],
    ['about_features_search_desc', 'Güçlü arama ve filtreleme seçenekleriyle aradığınız içeriği kolayca bulun.', 'textarea', 'Arama Özellik Açıklaması', 'pages'],
    ['about_features_support_title', '7/24 Destek', 'text', 'Destek Özellik Başlığı', 'pages'],
    ['about_features_support_desc', 'Sorularınız ve sorunlarınız için her zaman ulaşabileceğiniz destek ekibimiz var.', 'textarea', 'Destek Özellik Açıklaması', 'pages'],
    
    // Ekip bölümü
    ['about_team_title', 'Ekibimiz', 'text', 'Ekip Bölüm Başlığı', 'pages'],
    ['about_team_member1_name', 'Zeki Kurt', 'text', 'Ekip Üyesi 1 Adı', 'pages'],
    ['about_team_member1_role', 'Kurucu & CEO', 'text', 'Ekip Üyesi 1 Pozisyonu', 'pages'],
    ['about_team_member1_desc', 'Manga tutkunu ve teknoloji uzmanı', 'text', 'Ekip Üyesi 1 Açıklaması', 'pages'],
    ['about_team_member2_name', 'Ayşe Demir', 'text', 'Ekip Üyesi 2 Adı', 'pages'],
    ['about_team_member2_role', 'İçerik Editörü', 'text', 'Ekip Üyesi 2 Pozisyonu', 'pages'],
    ['about_team_member2_desc', 'Çizgi roman uzmanı ve editör', 'text', 'Ekip Üyesi 2 Açıklaması', 'pages'],
    ['about_team_member3_name', 'HelixPrime', 'text', 'Ekip Üyesi 3 Adı', 'pages'],
    ['about_team_member3_role', 'Geliştirici', 'text', 'Ekip Üyesi 3 Pozisyonu', 'pages'],
    ['about_team_member3_desc', 'Full-stack developer', 'text', 'Ekip Üyesi 3 Açıklaması', 'pages'],
    ['about_team_member4_name', 'Fatma Özkan', 'text', 'Ekip Üyesi 4 Adı', 'pages'],
    ['about_team_member4_role', 'Topluluk Yöneticisi', 'text', 'Ekip Üyesi 4 Pozisyonu', 'pages'],
    ['about_team_member4_desc', 'Kullanıcı deneyimi uzmanı', 'text', 'Ekip Üyesi 4 Açıklaması', 'pages'],
    
    // İletişim CTA
    ['about_contact_title', 'Bizimle İletişime Geçin', 'text', 'İletişim CTA Başlığı', 'pages'],
    ['about_contact_desc', 'Sorularınız, önerileriniz veya işbirliği teklifleriniz için bize ulaşın', 'textarea', 'İletişim CTA Açıklaması', 'pages'],
    
    // Seri Bilgileri Sayfası
    ['series_info_title', 'Bölümler ve Seri Hakkında', 'text', 'Seri Bilgileri - Sayfa Başlığı', 'pages'],
    ['series_info_subtitle', 'Manga ve çizgi roman serilerinin nasıl çalıştığını öğrenin', 'text', 'Seri Bilgileri - Alt Başlık', 'pages'],
    ['series_what_title', 'Seri Nedir?', 'text', 'Seri Nedir - Başlık', 'pages'],
    ['series_what_content', 'Seri, birden fazla bölümden oluşan manga veya çizgi roman eserleridir. Her bölüm hikayenin bir parçasını anlatır ve okuyucular bölümleri sırayla takip ederek tam hikayeyi deneyimler.', 'textarea', 'Seri Nedir - İçerik', 'pages'],
    ['chapter_system_title', 'Bölüm Sistemi Nasıl Çalışır?', 'text', 'Bölüm Sistemi - Başlık', 'pages'],
    ['chapter_system_content', 'Her seri, numaralandırılmış bölümlerden oluşur. Okuyucular istediği bölümden başlayabilir, ancak hikayeyi tam anlamak için sırayla okumaları önerilir.', 'textarea', 'Bölüm Sistemi - İçerik', 'pages'],
    ['content_types_title', 'İçerik Türleri', 'text', 'İçerik Türleri - Başlık', 'pages'],
    ['content_types_content', 'Platformumuzda iki ana içerik türü bulunmaktadır:', 'text', 'İçerik Türleri - İçerik', 'pages'],
    ['manga_description', 'Japon tarzı çizgi romanlar. Genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir.', 'textarea', 'Manga Açıklaması', 'pages'],
    ['comic_description', 'Batı tarzı çizgi romanlar. Renkli çizimler ve soldan sağa okuma düzeni ile karakterize edilir.', 'textarea', 'Çizgi Roman Açıklaması', 'pages'],
    ['how_to_follow_title', 'Serileri Nasıl Takip Ederim?', 'text', 'Nasıl Takip Edilir - Başlık', 'pages'],
    ['how_to_follow_content', 'Sevdiğiniz serileri takip etmek için çeşitli özelliklerimizi kullanabilirsiniz.', 'textarea', 'Nasıl Takip Edilir - İçerik', 'pages'],
    ['for_authors_title', 'Yazarlar İçin Rehber', 'text', 'Yazarlar İçin - Başlık', 'pages'],
    ['for_authors_content', 'Kendi serinizi oluşturmak ve yönetmek için ipuçları.', 'textarea', 'Yazarlar İçin - İçerik', 'pages'],
    
    // Manga Bilgi Sayfası
    ['manga_info_title', 'Eser ve Manga Rehberi', 'text', 'Manga Bilgi - Sayfa Başlığı', 'pages'],
    ['manga_info_subtitle', 'Manga ve çizgi roman eserlerini nasıl keşfedeceğinizi ve okuyacağınızı öğrenin', 'text', 'Manga Bilgi - Alt Başlık', 'pages'],
    ['discover_content_title', 'Eser Nasıl Keşfedilir?', 'text', 'Eser Keşfetme - Başlık', 'pages'],
    ['discover_content_text', 'Platformumuzda binlerce manga ve çizgi roman eseri bulunmaktadır. İstediğiniz türde içerikleri keşfetmek için çeşitli yöntemler kullanabilirsiniz.', 'textarea', 'Eser Keşfetme - İçerik', 'pages'],
    ['how_to_read_title', 'Okuma Nasıl Başlanır?', 'text', 'Okuma Başlangıcı - Başlık', 'pages'],
    ['how_to_read_text', 'Bir eseri okumaya başlamak çok kolay! Aşağıdaki adımları takip ederek hemen okumaya başlayabilirsiniz.', 'textarea', 'Okuma Başlangıcı - İçerik', 'pages'],
    ['content_types_text', 'Platformumuzda iki ana eser türü bulunmaktadır:', 'text', 'Eser Türleri - İçerik', 'pages'],
    ['manga_info_description', 'Japon kültürünün ürünü olan manga eserleri, genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir. Çeşitli türlerde hikayeler sunar.', 'textarea', 'Manga Bilgi Açıklaması', 'pages'],
    ['comic_info_description', 'Batı kültürünün ürünü olan çizgi romanlar, soldan sağa okunur ve genellikle renkli çizimlerle sunulur. Süper kahramanlardan komedi türüne kadar geniş yelpaze.', 'textarea', 'Çizgi Roman Bilgi Açıklaması', 'pages'],
    ['demo_reading_title', 'Demo: Okuma Deneyimi', 'text', 'Demo Okuma - Başlık', 'pages'],
    ['demo_reading_text', 'Aşağıdaki örneklere tıklayarak okuma deneyimini test edebilirsiniz:', 'text', 'Demo Okuma - İçerik', 'pages'],
    ['reading_tips_title', 'Okuma İpuçları', 'text', 'Okuma İpuçları - Başlık', 'pages'],
    ['reading_tips_text', 'Daha iyi bir okuma deneyimi için aşağıdaki ipuçlarını takip edebilirsiniz.', 'textarea', 'Okuma İpuçları - İçerik', 'pages']
];

foreach ($default_page_settings as $setting) {
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $setting[0]);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value, setting_type, description, category) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "sssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($check_stmt);
}

// Ayar güncellemeleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_pages') {
        $updated_count = 0;
        
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // 'setting_' kısmını çıkar
                $setting_value = sanitizeInput($value);
                
                $update_stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                mysqli_stmt_bind_param($update_stmt, "ss", $setting_value, $setting_key);
                if (mysqli_stmt_execute($update_stmt)) {
                    $updated_count++;
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        
        if ($updated_count > 0) {
            $message = "$updated_count sayfa içeriği başarıyla güncellendi.";
            $message_type = 'success';
        } else {
            $message = 'Hiçbir içerik güncellenmedi.';
            $message_type = 'warning';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0 d-flex align-items-center">
                                <i class="fas fa-file-edit me-3 text-primary"></i>
                                Sayfa Yönetimi
                            </h1>
                            <p class="text-muted mb-0">Hakkımızda, Kullanım Koşulları ve Gizlilik Politikası sayfalarını düzenleyin</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="../about.php" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-info-circle me-1"></i>Hakkımızda
                            </a>
                            <a href="../terms.php" target="_blank" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-contract me-1"></i>Kullanım Koşulları
                            </a>
                            <a href="../privacy.php" target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-user-shield me-1"></i>Gizlilik Politikası
                            </a>
                            <a href="../series-info.php" target="_blank" class="btn btn-outline-purple btn-sm">
                                <i class="fas fa-book-open me-1"></i>Seri Bilgileri
                            </a>
                            <a href="../manga-info.php" target="_blank" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-book-reader me-1"></i>Manga Rehberi
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form id="pagesForm" method="POST">
                <input type="hidden" name="action" value="update_pages">
                
                <!-- Hakkımızda Sayfası -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Hakkımızda Sayfası
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_about_mission" class="form-label">Misyonumuz</label>
                                    <textarea class="form-control" 
                                              id="setting_about_mission" 
                                              name="setting_about_mission" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('about_mission', 'Manga ve çizgi roman tutkunlarını bir araya getirerek, kaliteli içeriklerin paylaşıldığı, etkileşimli ve kullanıcı dostu bir platform sunmak.')); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_about_vision" class="form-label">Vizyonumuz</label>
                                    <textarea class="form-control" 
                                              id="setting_about_vision" 
                                              name="setting_about_vision" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('about_vision', 'Türkiye\'nin en büyük ve en güvenilir manga-çizgi roman paylaşım platformu olmak.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="setting_about_story" class="form-label">Hikayemiz</label>
                            <textarea class="form-control" 
                                      id="setting_about_story" 
                                      name="setting_about_story" 
                                      rows="6"><?php echo htmlspecialchars(getSetting('about_story', '2024 yılında manga ve çizgi roman tutkusu ile başlayan yolculuğumuz...')); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="setting_about_extra_content" class="form-label">Ek İçerik (HTML)</label>
                            <textarea class="form-control" 
                                      id="setting_about_extra_content" 
                                      name="setting_about_extra_content" 
                                      rows="8"><?php echo htmlspecialchars(getSetting('about_extra_content')); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Özellikler Bölümü -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-star text-warning me-2"></i>
                            Özellikler Bölümü
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="setting_about_features_title" class="form-label">Bölüm Başlığı</label>
                            <input type="text" class="form-control" 
                                   id="setting_about_features_title" 
                                   name="setting_about_features_title" 
                                   value="<?php echo htmlspecialchars(getSetting('about_features_title', 'Neden Bizi Seçmelisiniz?')); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success">Güvenlik Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_security_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_security_title" 
                                           name="setting_about_features_security_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_security_title', 'Güvenli Platform')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_security_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_security_desc" 
                                              name="setting_about_features_security_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_security_desc', 'Verileriniz ve gizliliğiniz bizim için çok önemli. En son güvenlik teknolojilerini kullanıyoruz.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-primary">Hız Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_speed_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_speed_title" 
                                           name="setting_about_features_speed_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_speed_title', 'Hızlı ve Stabil')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_speed_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_speed_desc" 
                                              name="setting_about_features_speed_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_speed_desc', 'Optimize edilmiş altyapımızla hızlı yükleme süreleri ve kesintisiz okuma deneyimi sunuyoruz.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">Mobil Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_mobile_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_mobile_title" 
                                           name="setting_about_features_mobile_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_mobile_title', 'Mobil Uyumlu')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_mobile_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_mobile_desc" 
                                              name="setting_about_features_mobile_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_mobile_desc', 'Tüm cihazlarda mükemmel çalışan responsive tasarımımızla her yerden erişim sağlayın.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-info">Topluluk Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_community_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_community_title" 
                                           name="setting_about_features_community_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_community_title', 'Aktif Topluluk')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_community_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_community_desc" 
                                              name="setting_about_features_community_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_community_desc', 'Binlerce aktif kullanıcımızla yorumlar, beğeniler ve etkileşimlerle zengin bir deneyim yaşayın.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger">Arama Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_search_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_search_title" 
                                           name="setting_about_features_search_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_search_title', 'Gelişmiş Arama')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_search_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_search_desc" 
                                              name="setting_about_features_search_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_search_desc', 'Güçlü arama ve filtreleme seçenekleriyle aradığınız içeriği kolayca bulun.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-secondary">Destek Özelliği</h6>
                                <div class="mb-3">
                                    <label for="setting_about_features_support_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_features_support_title" 
                                           name="setting_about_features_support_title" 
                                           value="<?php echo htmlspecialchars(getSetting('about_features_support_title', '7/24 Destek')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_features_support_desc" class="form-label">Açıklama</label>
                                    <textarea class="form-control" 
                                              id="setting_about_features_support_desc" 
                                              name="setting_about_features_support_desc" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('about_features_support_desc', 'Sorularınız ve sorunlarınız için her zaman ulaşabileceğiniz destek ekibimiz var.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ekip Bölümü -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users-cog text-purple me-2"></i>
                            Ekip Bölümü
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="setting_about_team_title" class="form-label">Bölüm Başlığı</label>
                            <input type="text" class="form-control" 
                                   id="setting_about_team_title" 
                                   name="setting_about_team_title" 
                                   value="<?php echo htmlspecialchars(getSetting('about_team_title', 'Ekibimiz')); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Ekip Üyesi 1</h6>
                                <div class="mb-2">
                                    <label for="setting_about_team_member1_name" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member1_name" 
                                           name="setting_about_team_member1_name" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member1_name', 'Zeki Kurt')); ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="setting_about_team_member1_role" class="form-label">Pozisyon</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member1_role" 
                                           name="setting_about_team_member1_role" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member1_role', 'Kurucu & CEO')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_team_member1_desc" class="form-label">Açıklama</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member1_desc" 
                                           name="setting_about_team_member1_desc" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member1_desc', 'Manga tutkunu ve teknoloji uzmanı')); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-success">Ekip Üyesi 2</h6>
                                <div class="mb-2">
                                    <label for="setting_about_team_member2_name" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member2_name" 
                                           name="setting_about_team_member2_name" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member2_name', 'Ayşe Demir')); ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="setting_about_team_member2_role" class="form-label">Pozisyon</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member2_role" 
                                           name="setting_about_team_member2_role" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member2_role', 'İçerik Editörü')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_team_member2_desc" class="form-label">Açıklama</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member2_desc" 
                                           name="setting_about_team_member2_desc" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member2_desc', 'Çizgi roman uzmanı ve editör')); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">Ekip Üyesi 3</h6>
                                <div class="mb-2">
                                    <label for="setting_about_team_member3_name" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member3_name" 
                                           name="setting_about_team_member3_name" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member3_name', 'HelixPrime')); ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="setting_about_team_member3_role" class="form-label">Pozisyon</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member3_role" 
                                           name="setting_about_team_member3_role" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member3_role', 'Geliştirici')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_team_member3_desc" class="form-label">Açıklama</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member3_desc" 
                                           name="setting_about_team_member3_desc" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member3_desc', 'Full-stack developer')); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-info">Ekip Üyesi 4</h6>
                                <div class="mb-2">
                                    <label for="setting_about_team_member4_name" class="form-label">Ad Soyad</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member4_name" 
                                           name="setting_about_team_member4_name" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member4_name', 'Fatma Özkan')); ?>">
                                </div>
                                <div class="mb-2">
                                    <label for="setting_about_team_member4_role" class="form-label">Pozisyon</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member4_role" 
                                           name="setting_about_team_member4_role" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member4_role', 'Topluluk Yöneticisi')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_about_team_member4_desc" class="form-label">Açıklama</label>
                                    <input type="text" class="form-control" 
                                           id="setting_about_team_member4_desc" 
                                           name="setting_about_team_member4_desc" 
                                           value="<?php echo htmlspecialchars(getSetting('about_team_member4_desc', 'Kullanıcı deneyimi uzmanı')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- İletişim CTA Bölümü -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            İletişim CTA Bölümü
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="setting_about_contact_title" class="form-label">Başlık</label>
                            <input type="text" class="form-control" 
                                   id="setting_about_contact_title" 
                                   name="setting_about_contact_title" 
                                   value="<?php echo htmlspecialchars(getSetting('about_contact_title', 'Bizimle İletişime Geçin')); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="setting_about_contact_desc" class="form-label">Açıklama</label>
                            <textarea class="form-control" 
                                      id="setting_about_contact_desc" 
                                      name="setting_about_contact_desc" 
                                      rows="3"><?php echo htmlspecialchars(getSetting('about_contact_desc', 'Sorularınız, önerileriniz veya işbirliği teklifleriniz için bize ulaşın')); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Kullanım Koşulları -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-contract text-success me-2"></i>
                            Kullanım Koşulları
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="setting_terms_extra_content" class="form-label">Ek Kullanım Koşulları</label>
                            <textarea class="form-control" 
                                      id="setting_terms_extra_content" 
                                      name="setting_terms_extra_content" 
                                      rows="10"><?php echo htmlspecialchars(getSetting('terms_extra_content')); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Gizlilik Politikası -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-shield text-info me-2"></i>
                            Gizlilik Politikası
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="setting_privacy_extra_content" class="form-label">Ek Gizlilik Bilgileri</label>
                            <textarea class="form-control" 
                                      id="setting_privacy_extra_content" 
                                      name="setting_privacy_extra_content" 
                                      rows="10"><?php echo htmlspecialchars(getSetting('privacy_extra_content')); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Seri Bilgileri Sayfası -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book-open text-purple me-2"></i>
                            Seri Bilgileri Sayfası
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_series_info_title" class="form-label">Sayfa Başlığı</label>
                                    <input type="text" class="form-control" 
                                           id="setting_series_info_title" 
                                           name="setting_series_info_title" 
                                           value="<?php echo htmlspecialchars(getSetting('series_info_title', 'Bölümler ve Seri Hakkında')); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_series_info_subtitle" class="form-label">Alt Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_series_info_subtitle" 
                                           name="setting_series_info_subtitle" 
                                           value="<?php echo htmlspecialchars(getSetting('series_info_subtitle', 'Manga ve çizgi roman serilerinin nasıl çalıştığını öğrenin')); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Seri Nedir? Bölümü</h6>
                                <div class="mb-3">
                                    <label for="setting_series_what_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_series_what_title" 
                                           name="setting_series_what_title" 
                                           value="<?php echo htmlspecialchars(getSetting('series_what_title', 'Seri Nedir?')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_series_what_content" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_series_what_content" 
                                              name="setting_series_what_content" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('series_what_content', 'Seri, birden fazla bölümden oluşan manga veya çizgi roman eserleridir. Her bölüm hikayenin bir parçasını anlatır ve okuyucular bölümleri sırayla takip ederek tam hikayeyi deneyimler.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-success">Bölüm Sistemi</h6>
                                <div class="mb-3">
                                    <label for="setting_chapter_system_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_chapter_system_title" 
                                           name="setting_chapter_system_title" 
                                           value="<?php echo htmlspecialchars(getSetting('chapter_system_title', 'Bölüm Sistemi Nasıl Çalışır?')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_chapter_system_content" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_chapter_system_content" 
                                              name="setting_chapter_system_content" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('chapter_system_content', 'Her seri, numaralandırılmış bölümlerden oluşur. Okuyucular istediği bölümden başlayabilir, ancak hikayeyi tam anlamak için sırayla okumaları önerilir.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">İçerik Türleri</h6>
                                <div class="mb-3">
                                    <label for="setting_content_types_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_content_types_title" 
                                           name="setting_content_types_title" 
                                           value="<?php echo htmlspecialchars(getSetting('content_types_title', 'İçerik Türleri')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_content_types_content" class="form-label">İçerik</label>
                                    <input type="text" class="form-control" 
                                           id="setting_content_types_content" 
                                           name="setting_content_types_content" 
                                           value="<?php echo htmlspecialchars(getSetting('content_types_content', 'Platformumuzda iki ana içerik türü bulunmaktadır:')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_manga_description" class="form-label">Manga Açıklaması</label>
                                    <textarea class="form-control" 
                                              id="setting_manga_description" 
                                              name="setting_manga_description" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('manga_description', 'Japon tarzı çizgi romanlar. Genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir.')); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="setting_comic_description" class="form-label">Çizgi Roman Açıklaması</label>
                                    <textarea class="form-control" 
                                              id="setting_comic_description" 
                                              name="setting_comic_description" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('comic_description', 'Batı tarzı çizgi romanlar. Renkli çizimler ve soldan sağa okuma düzeni ile karakterize edilir.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-info">Takip Etme</h6>
                                <div class="mb-3">
                                    <label for="setting_how_to_follow_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_how_to_follow_title" 
                                           name="setting_how_to_follow_title" 
                                           value="<?php echo htmlspecialchars(getSetting('how_to_follow_title', 'Serileri Nasıl Takip Ederim?')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_how_to_follow_content" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_how_to_follow_content" 
                                              name="setting_how_to_follow_content" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('how_to_follow_content', 'Sevdiğiniz serileri takip etmek için çeşitli özelliklerimizi kullanabilirsiniz.')); ?></textarea>
                                </div>
                                
                                <h6 class="text-danger">Yazarlar İçin</h6>
                                <div class="mb-3">
                                    <label for="setting_for_authors_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_for_authors_title" 
                                           name="setting_for_authors_title" 
                                           value="<?php echo htmlspecialchars(getSetting('for_authors_title', 'Yazarlar İçin Rehber')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_for_authors_content" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_for_authors_content" 
                                              name="setting_for_authors_content" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('for_authors_content', 'Kendi serinizi oluşturmak ve yönetmek için ipuçları.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Manga Bilgi Sayfası -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-book-reader text-success me-2"></i>
                            Manga Bilgi Sayfası
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_manga_info_title" class="form-label">Sayfa Başlığı</label>
                                    <input type="text" class="form-control" 
                                           id="setting_manga_info_title" 
                                           name="setting_manga_info_title" 
                                           value="<?php echo htmlspecialchars(getSetting('manga_info_title', 'Eser ve Manga Rehberi')); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="setting_manga_info_subtitle" class="form-label">Alt Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_manga_info_subtitle" 
                                           name="setting_manga_info_subtitle" 
                                           value="<?php echo htmlspecialchars(getSetting('manga_info_subtitle', 'Manga ve çizgi roman eserlerini nasıl keşfedeceğinizi ve okuyacağınızı öğrenin')); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Eser Keşfetme Bölümü</h6>
                                <div class="mb-3">
                                    <label for="setting_discover_content_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_discover_content_title" 
                                           name="setting_discover_content_title" 
                                           value="<?php echo htmlspecialchars(getSetting('discover_content_title', 'Eser Nasıl Keşfedilir?')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_discover_content_text" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_discover_content_text" 
                                              name="setting_discover_content_text" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('discover_content_text', 'Platformumuzda binlerce manga ve çizgi roman eseri bulunmaktadır. İstediğiniz türde içerikleri keşfetmek için çeşitli yöntemler kullanabilirsiniz.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-success">Okuma Başlangıcı</h6>
                                <div class="mb-3">
                                    <label for="setting_how_to_read_title" class="form-label">Başlık</label>
                                    <input type="text" class="form-control" 
                                           id="setting_how_to_read_title" 
                                           name="setting_how_to_read_title" 
                                           value="<?php echo htmlspecialchars(getSetting('how_to_read_title', 'Okuma Nasıl Başlanır?')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_how_to_read_text" class="form-label">İçerik</label>
                                    <textarea class="form-control" 
                                              id="setting_how_to_read_text" 
                                              name="setting_how_to_read_text" 
                                              rows="4"><?php echo htmlspecialchars(getSetting('how_to_read_text', 'Bir eseri okumaya başlamak çok kolay! Aşağıdaki adımları takip ederek hemen okumaya başlayabilirsiniz.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">Eser Türleri</h6>
                                <div class="mb-3">
                                    <label for="setting_content_types_text" class="form-label">Giriş Metni</label>
                                    <input type="text" class="form-control" 
                                           id="setting_content_types_text" 
                                           name="setting_content_types_text" 
                                           value="<?php echo htmlspecialchars(getSetting('content_types_text', 'Platformumuzda iki ana eser türü bulunmaktadır:')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_manga_info_description" class="form-label">Manga Açıklaması</label>
                                    <textarea class="form-control" 
                                              id="setting_manga_info_description" 
                                              name="setting_manga_info_description" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('manga_info_description', 'Japon kültürünün ürünü olan manga eserleri, genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir. Çeşitli türlerde hikayeler sunar.')); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="setting_comic_info_description" class="form-label">Çizgi Roman Açıklaması</label>
                                    <textarea class="form-control" 
                                              id="setting_comic_info_description" 
                                              name="setting_comic_info_description" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('comic_info_description', 'Batı kültürünün ürünü olan çizgi romanlar, soldan sağa okunur ve genellikle renkli çizimlerle sunulur. Süper kahramanlardan komedi türüne kadar geniş yelpaze.')); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-info">Demo ve İpuçları</h6>
                                <div class="mb-3">
                                    <label for="setting_demo_reading_title" class="form-label">Demo Başlığı</label>
                                    <input type="text" class="form-control" 
                                           id="setting_demo_reading_title" 
                                           name="setting_demo_reading_title" 
                                           value="<?php echo htmlspecialchars(getSetting('demo_reading_title', 'Demo: Okuma Deneyimi')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_demo_reading_text" class="form-label">Demo İçeriği</label>
                                    <input type="text" class="form-control" 
                                           id="setting_demo_reading_text" 
                                           name="setting_demo_reading_text" 
                                           value="<?php echo htmlspecialchars(getSetting('demo_reading_text', 'Aşağıdaki örneklere tıklayarak okuma deneyimini test edebilirsiniz:')); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_reading_tips_title" class="form-label">İpuçları Başlığı</label>
                                    <input type="text" class="form-control" 
                                           id="setting_reading_tips_title" 
                                           name="setting_reading_tips_title" 
                                           value="<?php echo htmlspecialchars(getSetting('reading_tips_title', 'Okuma İpuçları')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="setting_reading_tips_text" class="form-label">İpuçları İçeriği</label>
                                    <textarea class="form-control" 
                                              id="setting_reading_tips_text" 
                                              name="setting_reading_tips_text" 
                                              rows="3"><?php echo htmlspecialchars(getSetting('reading_tips_text', 'Daha iyi bir okuma deneyimi için aşağıdaki ipuçlarını takip edebilirsiniz.')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kaydet Butonu -->
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 