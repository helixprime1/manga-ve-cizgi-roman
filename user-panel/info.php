<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Eser Rehberi';

require_once 'includes/header.php';

$page_title = getSetting('manga_info_title', 'Eser ve Manga Rehberi');
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    border-radius: 15px;
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

.step-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.step-card:hover {
    transform: translateX(5px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.15);
}

.step-number {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 1rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.feature-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    cursor: pointer;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
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

.demo-section {
    background: #f8f9fa;
    border-radius: 1rem;
    padding: 2rem;
    margin: 2rem 0;
}

.demo-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.demo-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.demo-cover {
    width: 60px;
    height: 80px;
    background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 1rem;
    }
    
    .info-card {
        padding: 1.5rem;
    }
    
    .navigation-sidebar {
        position: static;
        margin-bottom: 2rem;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('manga_info_title', 'Eser ve Manga Rehberi')); ?></h1>
            <p class="lead"><?php echo htmlspecialchars(getSetting('manga_info_subtitle', 'Manga ve çizgi roman eserlerini nasıl keşfedeceğinizi ve okuyacağınızı öğrenin')); ?></p>
        </div>
        <div class="col-lg-4 text-center">
            <div style="font-size: 4rem; opacity: 0.3;">📖</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Ana İçerik -->
    <div class="col-lg-8">
        <!-- Eser Keşfetme -->
        <div class="info-card" id="discover-content">
            <div class="info-icon">
                🔍
            </div>
            <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('discover_content_title', 'Eser Nasıl Keşfedilir?')); ?></h2>
            <p><?php echo nl2br(htmlspecialchars(getSetting('discover_content_text', 'Platformumuzda binlerce manga ve çizgi roman eseri bulunmaktadır. İstediğiniz türde içerikleri keşfetmek için çeşitli yöntemler kullanabilirsiniz.'))); ?></p>
            
            <div class="feature-grid">
                <div class="feature-card" onclick="window.location.href='<?php echo SITE_URL; ?>'">
                    <div class="feature-icon">
                        🏠
                    </div>
                    <h5>Ana Sayfa</h5>
                    <p class="small text-muted">Öne çıkan ve popüler eserleri ana sayfada keşfedin</p>
                </div>
                <div class="feature-card" onclick="showCategoryMenu()">
                    <div class="feature-icon">
                        📂
                    </div>
                    <h5>Kategoriler</h5>
                    <p class="small text-muted">Manga veya çizgi roman kategorilerini inceleyin</p>
                </div>
                <div class="feature-card" onclick="focusSearchBox()">
                    <div class="feature-icon">
                        🔍
                    </div>
                    <h5>Arama</h5>
                    <p class="small text-muted">İsim veya etiketlerle arama yapın</p>
                </div>
                <div class="feature-card" onclick="window.location.href='<?php echo SITE_URL; ?>/popular.php'">
                    <div class="feature-icon">
                        🔥
                    </div>
                    <h5>Popüler</h5>
                    <p class="small text-muted">En çok okunan eserleri keşfedin</p>
                </div>
            </div>
        </div>

        <!-- Okuma Nasıl Başlanır -->
        <div class="info-card" id="how-to-read">
            <div class="info-icon">
                ▶️
            </div>
            <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('how_to_read_title', 'Okuma Nasıl Başlanır?')); ?></h2>
            <p><?php echo nl2br(htmlspecialchars(getSetting('how_to_read_text', 'Bir eseri okumaya başlamak çok kolay! Aşağıdaki adımları takip ederek hemen okumaya başlayabilirsiniz.'))); ?></p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5>Eser Seçin</h5>
                        <p class="small">İlginizi çeken bir manga veya çizgi roman bulun</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5>Detay Sayfasına Gidin</h5>
                        <p class="small">Eserin kapağına tıklayarak detay sayfasını açın</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5>Okumaya Başlayın</h5>
                        <p class="small">"Oku" butonuna tıklayarak okumaya başlayın</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h5>Bölümler Arası Geçiş</h5>
                        <p class="small">Seri eserlerde bölümler arası kolayca geçiş yapın</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eser Türleri -->
        <div class="info-card" id="content-types">
            <div class="info-icon">
                📚
            </div>
            <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('content_types_title', 'Eser Türleri')); ?></h2>
            <p><?php echo htmlspecialchars(getSetting('content_types_text', 'Platformumuzda iki ana eser türü bulunmaktadır:')); ?></p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="d-flex align-items-center mb-3">
                            <span class="type-badge type-manga me-3">Manga</span>
                            <h4 class="h5 mb-0">Manga Eserleri</h4>
                        </div>
                        <p><?php echo htmlspecialchars(getSetting('manga_info_description', 'Japon kültürünün ürünü olan manga eserleri, genellikle sağdan sola okunur ve siyah-beyaz çizimlerle karakterize edilir. Çeşitli türlerde hikayeler sunar.')); ?></p>
                        <div class="mt-3">
                            <h6 class="fw-bold">Özellikler:</h6>
                            <ul class="list-unstyled">
                                <li><span class="text-success me-2">✅</span> Sağdan sola okuma</li>
                                <li><span class="text-success me-2">✅</span> Siyah-beyaz çizimler</li>
                                <li><span class="text-success me-2">✅</span> Çeşitli türler</li>
                                <li><span class="text-success me-2">✅</span> Seri halinde</li>
                            </ul>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/category.php?type=manga" class="btn btn-outline-danger btn-sm">
                            🐉 Mangaları Keşfet
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="d-flex align-items-center mb-3">
                            <span class="type-badge type-comic me-3">Çizgi Roman</span>
                            <h4 class="h5 mb-0">Çizgi Roman Eserleri</h4>
                        </div>
                        <p><?php echo htmlspecialchars(getSetting('comic_info_description', 'Batı kültürünün ürünü olan çizgi romanlar, soldan sağa okunur ve genellikle renkli çizimlerle sunulur. Süper kahramanlardan komedi türüne kadar geniş yelpaze.')); ?></p>
                        <div class="mt-3">
                            <h6 class="fw-bold">Özellikler:</h6>
                            <ul class="list-unstyled">
                                <li><span class="text-success me-2">✅</span> Soldan sağa okuma</li>
                                <li><span class="text-success me-2">✅</span> Renkli çizimler</li>
                                <li><span class="text-success me-2">✅</span> Çeşitli türler</li>
                                <li><span class="text-success me-2">✅</span> Tek sayı veya seri</li>
                            </ul>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/category.php?type=comic" class="btn btn-outline-info btn-sm">
                            🎭 Çizgi Romanları Keşfet
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demo Okuma Deneyimi -->
        <div class="demo-section" id="demo-reading">
            <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('demo_reading_title', 'Demo: Okuma Deneyimi')); ?></h2>
            <p><?php echo htmlspecialchars(getSetting('demo_reading_text', 'Aşağıdaki örneklere tıklayarak okuma deneyimini test edebilirsiniz:')); ?></p>
            
            <?php
            // Demo için gerçek içerikleri al
            $demo_query = "SELECT * FROM content WHERE status = 'published' ORDER BY views DESC LIMIT 2";
            $demo_result = mysqli_query($conn, $demo_query);
            $demo_contents = [];
            while ($row = mysqli_fetch_assoc($demo_result)) {
                $demo_contents[] = $row;
            }
            
            // Eğer gerçek içerik yoksa örnek veri kullan
            if (empty($demo_contents)) {
                $demo_contents = [
                    [
                        'id' => 0,
                        'title' => 'Örnek Manga Serisi',
                        'type' => 'manga',
                        'is_series' => 1,
                        'description' => 'Bu bir örnek manga serisidir. Okuma deneyimini test etmek için kullanabilirsiniz.'
                    ],
                    [
                        'id' => 0,
                        'title' => 'Örnek Çizgi Roman',
                        'type' => 'comic',
                        'is_series' => 0,
                        'description' => 'Bu bir örnek çizgi romandır. Tek sayılık eser örneğidir.'
                    ]
                ];
            }
            ?>
            
            <div class="row">
                <?php foreach ($demo_contents as $demo): ?>
                    <div class="col-md-6">
                        <div class="demo-card" onclick="startReading(<?php echo $demo['id']; ?>, '<?php echo $demo['type']; ?>', <?php echo $demo['is_series']; ?>)">
                            <div class="d-flex align-items-center">
                                <div class="demo-cover me-3">
                                    📖
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($demo['title']); ?></h6>
                                    <p class="small text-muted mb-1"><?php echo htmlspecialchars(substr($demo['description'], 0, 60)) . '...'; ?></p>
                                    <div class="d-flex align-items-center">
                                        <span class="badge <?php echo $demo['type'] === 'manga' ? 'type-manga' : 'type-comic'; ?> me-2">
                                            <?php echo $demo['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                                        </span>
                                        <?php if ($demo['is_series']): ?>
                                            <span class="badge bg-info">Seri</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark">Tek Eser</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <span style="font-size: 2rem; color: #0d6efd;">▶️</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Okuma İpuçları -->
        <div class="info-card" id="reading-tips">
            <div class="info-icon">
                💡
            </div>
            <h2 class="h3 fw-bold mb-3"><?php echo htmlspecialchars(getSetting('reading_tips_title', 'Okuma İpuçları')); ?></h2>
            <p><?php echo nl2br(htmlspecialchars(getSetting('reading_tips_text', 'Daha iyi bir okuma deneyimi için aşağıdaki ipuçlarını takip edebilirsiniz.'))); ?></p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5><span class="text-primary me-2">🔖</span>Favorilere Ekleyin</h5>
                    <p>Beğendiğiniz eserleri favorilerinize ekleyerek daha sonra kolayca bulabilirsiniz.</p>
                </div>
                <div class="col-md-6">
                    <h5><span class="text-success me-2">📱</span>Mobil Uyumlu</h5>
                    <p>Tüm cihazlarda rahatlıkla okuyabilirsiniz. Mobil deneyim optimize edilmiştir.</p>
                </div>
                <div class="col-md-6">
                    <h5><span class="text-warning me-2">🔍</span>Tam Ekran Modu</h5>
                    <p>Daha iyi odaklanma için tam ekran modunu kullanabilirsiniz.</p>
                </div>
                <div class="col-md-6">
                    <h5><span class="text-info me-2">⌨️</span>Klavye Kısayolları</h5>
                    <p>Ok tuşları ile sayfa geçişi yapabilir, ESC ile menüyü açabilirsiniz.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Yan Panel -->
    <div class="col-lg-4">
        <div class="navigation-sidebar">
            <h4 class="fw-bold mb-3">İçindekiler</h4>
            <nav>
                <a href="#discover-content" class="nav-link">Eser Keşfetme</a>
                <a href="#how-to-read" class="nav-link">Okuma Nasıl Başlanır</a>
                <a href="#content-types" class="nav-link">Eser Türleri</a>
                <a href="#demo-reading" class="nav-link">Demo Okuma</a>
                <a href="#reading-tips" class="nav-link">Okuma İpuçları</a>
            </nav>
        </div>

        <!-- Hızlı Linkler -->
        <div class="info-card mt-4">
            <h5 class="fw-bold mb-3">Hızlı Linkler</h5>
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/category.php?type=manga" class="btn btn-outline-primary">
                    🐉 Mangaları Keşfet
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?type=comic" class="btn btn-outline-info">
                    🎭 Çizgi Romanları Keşfet
                </a>
                <a href="<?php echo SITE_URL; ?>/popular.php" class="btn btn-outline-danger">
                    🔥 Popüler İçerikler
                </a>
                <a href="<?php echo SITE_URL; ?>/trending.php" class="btn btn-outline-warning">
                    📈 Trend İçerikler
                </a>
            </div>
        </div>

        <!-- İstatistikler -->
        <?php
        // Platform istatistikleri
        $total_manga = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE type = 'manga' AND status = 'published'"))['count'];
        $total_comic = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE type = 'comic' AND status = 'published'"))['count'];
        $total_chapters = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM chapters"))['count'];
        ?>
        
        <div class="feature-grid mt-4">
            <div class="feature-card">
                <div class="feature-icon">
                    🐉
                </div>
                <h5><?php echo number_format($total_manga); ?></h5>
                <p class="small text-muted">Manga Eseri</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    🎭
                </div>
                <h5><?php echo number_format($total_comic); ?></h5>
                <p class="small text-muted">Çizgi Roman</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    📖
                </div>
                <h5><?php echo number_format($total_chapters); ?></h5>
                <p class="small text-muted">Toplam Bölüm</p>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll navigation
document.addEventListener('DOMContentLoaded', function() {
    // Navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.info-card, .demo-section');
    
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

// Demo okuma fonksiyonu
function startReading(contentId, type, isSeries) {
    if (contentId > 0) {
        // Gerçek içerik varsa direkt yönlendir
        window.location.href = `../view.php?id=${contentId}`;
    } else {
        // Demo için uyarı göster
        alert(`Bu bir demo örneğidir. Gerçek ${type === 'manga' ? 'manga' : 'çizgi roman'} okumak için platformumuzdaki ${isSeries ? 'seri' : 'tek'} eserleri keşfedin!`);
        window.location.href = `../category.php?type=${type}`;
    }
}

// Kategori menüsünü göster
function showCategoryMenu() {
    window.location.href = '<?php echo SITE_URL; ?>';
}

// Arama kutusuna odaklan
function focusSearchBox() {
    window.location.href = '<?php echo SITE_URL; ?>/search.php';
}
</script>

<?php require_once 'includes/footer.php'; ?>