<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

$page_title = getSetting('series_info_title', 'Bölümler ve Seri Hakkında');
require_once 'includes/header.php';
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
}

.info-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.info-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.chapter-types {
    background: #f8f9fa;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.type-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 500;
    margin: 0.25rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.type-manga {
    background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
    color: white;
}

.type-comic {
    background: linear-gradient(45deg, #4ecdc4, #6bcf7f);
    color: white;
}

.type-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}

.navigation-sidebar {
    position: sticky;
    top: 2rem;
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    height: fit-content;
}

.nav-link {
    color: #666;
    text-decoration: none;
    padding: 0.5rem 0;
    display: block;
    border-left: 3px solid transparent;
    padding-left: 1rem;
    transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
    color: #667eea;
    border-left-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    text-decoration: none;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    display: block;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }
    
    .info-card {
        padding: 1.5rem;
    }
    
    .navigation-sidebar {
        position: static;
        margin-bottom: 2rem;
    }
}
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('series_info_title', 'Bölümler ve Seri Hakkında')); ?></h1>
                <p class="lead"><?php echo htmlspecialchars(getSetting('series_info_subtitle', 'Manga ve çizgi roman serilerinin nasıl çalıştığını öğrenin')); ?></p>
            </div>
            <div class="col-lg-4 text-center">
                <i class="fas fa-book-open" style="font-size: 5rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Ana İçerik -->
        <div class="col-lg-8">
            <!-- Seri Nedir? -->
            <div class="info-card" id="what-is-series">
                <div class="info-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('series_what_title', 'Seri Nedir?')); ?></h2>
                <p><?php echo nl2br(htmlspecialchars(getSetting('series_what_content', 'Seri, birden fazla bölümden oluşan manga veya çizgi roman eserleridir. Her bölüm hikayenin bir parçasını anlatır ve okuyucular bölümleri sırayla takip ederek tam hikayeyi deneyimler.'))); ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4 class="h5 fw-bold text-primary">Seri Avantajları:</h4>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Uzun hikayeler anlatabilme</li>
                            <li><i class="fas fa-check text-success me-2"></i> Karakter gelişimi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Okuyucu bağlılığı</li>
                            <li><i class="fas fa-check text-success me-2"></i> Düzenli içerik</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4 class="h5 fw-bold text-primary">Tek Eser Avantajları:</h4>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Hızlı tamamlama</li>
                            <li><i class="fas fa-check text-success me-2"></i> Bağımsız hikaye</li>
                            <li><i class="fas fa-check text-success me-2"></i> Kolay takip</li>
                            <li><i class="fas fa-check text-success me-2"></i> Anında memnuniyet</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bölüm Sistemi -->
            <div class="info-card" id="chapter-system">
                <div class="info-icon">
                    <i class="fas fa-list-ol"></i>
                </div>
                <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('chapter_system_title', 'Bölüm Sistemi Nasıl Çalışır?')); ?></h2>
                <p><?php echo nl2br(htmlspecialchars(getSetting('chapter_system_content', 'Her seri, numaralandırılmış bölümlerden oluşur. Okuyucular istediği bölümden başlayabilir, ancak hikayeyi tam anlamak için sırayla okumaları önerilir.'))); ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-4 text-center">
                        <div class="info-icon mx-auto">
                            <i class="fas fa-play"></i>
                        </div>
                        <h5>Başlangıç</h5>
                        <p class="small text-muted">İlk bölümden hikayeye başlayın</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-icon mx-auto">
                            <i class="fas fa-forward"></i>
                        </div>
                        <h5>Devam</h5>
                        <p class="small text-muted">Bölümleri sırayla takip edin</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-icon mx-auto">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <h5>Takip</h5>
                        <p class="small text-muted">Kaldığınız yeri kaydedin</p>
                    </div>
                </div>
            </div>

            <!-- İçerik Türleri -->
            <div class="chapter-types" id="content-types">
                <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('content_types_title', 'İçerik Türleri')); ?></h2>
                <p><?php echo htmlspecialchars(getSetting('content_types_content', 'Platformumuzda iki ana içerik türü bulunmaktadır:')); ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="d-flex align-items-center mb-3">
                                <span class="type-badge type-manga me-3">Manga</span>
                                <h4 class="h5 mb-0">Manga Serileri</h4>
                            </div>
                            <p><?php echo htmlspecialchars(getSetting('manga_description', 'Japon tarzı çizgi romanlar. Genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir.')); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="d-flex align-items-center mb-3">
                                <span class="type-badge type-comic me-3">Çizgi Roman</span>
                                <h4 class="h5 mb-0">Çizgi Roman Serileri</h4>
                            </div>
                            <p><?php echo htmlspecialchars(getSetting('comic_description', 'Batı tarzı çizgi romanlar. Renkli çizimler ve soldan sağa okuma düzeni ile karakterize edilir.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nasıl Takip Edilir -->
            <div class="info-card" id="how-to-follow">
                <div class="info-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('how_to_follow_title', 'Serileri Nasıl Takip Ederim?')); ?></h2>
                <p><?php echo nl2br(htmlspecialchars(getSetting('how_to_follow_content', 'Sevdiğiniz serileri takip etmek için çeşitli özelliklerimizi kullanabilirsiniz.'))); ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-star text-warning me-2"></i>Favorilere Ekle</h5>
                        <p>Sevdiğiniz serileri favorilerinize ekleyerek hızlıca erişin.</p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-bell text-info me-2"></i>Bildirimler</h5>
                        <p>Yeni bölümler yayınlandığında anında haberdar olun.</p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-thumbs-up text-success me-2"></i>Beğeni</h5>
                        <p>Beğendiğiniz bölümleri işaretleyerek yazarlara destek verin.</p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-comment text-primary me-2"></i>Yorum</h5>
                        <p>Diğer okuyucularla deneyimlerinizi paylaşın.</p>
                    </div>
                </div>
            </div>

            <!-- Yazarlar İçin -->
            <div class="info-card" id="for-authors">
                <div class="info-icon">
                    <i class="fas fa-pen-nib"></i>
                </div>
                <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('for_authors_title', 'Yazarlar İçin Rehber')); ?></h2>
                <p><?php echo nl2br(htmlspecialchars(getSetting('for_authors_content', 'Kendi serinizi oluşturmak ve yönetmek için ipuçları.'))); ?></p>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <h6 class="fw-bold">1. Planlama</h6>
                        <p class="small">Hikayenizi önceden planlayın ve bölüm sayısını belirleyin.</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold">2. Düzenli Yayın</h6>
                        <p class="small">Okuyucularınızı kaybetmemek için düzenli aralıklarla yayın yapın.</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="fw-bold">3. Etkileşim</h6>
                        <p class="small">Yorumlara cevap vererek okuyucularınızla bağ kurun.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yan Panel -->
        <div class="col-lg-4">
            <div class="navigation-sidebar">
                <h4 class="fw-bold mb-3">İçindekiler</h4>
                <nav>
                    <a href="#what-is-series" class="nav-link">Seri Nedir?</a>
                    <a href="#chapter-system" class="nav-link">Bölüm Sistemi</a>
                    <a href="#content-types" class="nav-link">İçerik Türleri</a>
                    <a href="#how-to-follow" class="nav-link">Nasıl Takip Edilir</a>
                    <a href="#for-authors" class="nav-link">Yazarlar İçin</a>
                </nav>
            </div>

            <!-- İstatistikler -->
            <?php
            // Platform istatistikleri
            $total_series = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE is_series = 1 AND status = 'published'"))['count'];
            $total_chapters = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM chapters"))['count'];
            $total_single = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE is_series = 0 AND status = 'published'"))['count'];
            ?>
            
            <div class="stats-grid mt-4">
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($total_series); ?></span>
                    <span class="stat-label">Aktif Seri</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($total_chapters); ?></span>
                    <span class="stat-label">Toplam Bölüm</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($total_single); ?></span>
                    <span class="stat-label">Tek Eser</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll navigation
document.addEventListener('DOMContentLoaded', function() {
    // Navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.info-card, .chapter-types');
    
    // Smooth scroll
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update active link
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Intersection Observer for active section highlighting
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        },
        {
            threshold: 0.3,
            rootMargin: '-100px 0px -100px 0px'
        }
    );
    
    sections.forEach(section => {
        observer.observe(section);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 