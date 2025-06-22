    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <?php
                    $footer_site_name = getSetting('site_name', SITE_NAME);
                    $footer_site_description = getSetting('site_description', 'Manga ve çizgi roman tutkunları için en iyi platform. Kendi eserlerinizi yükleyin, paylaşın ve diğer sanatçıların çalışmalarını keşfedin.');
                    $footer_site_logo = getSetting('site_logo', '');
                    
                    // Sosyal medya linkleri
                    $facebook_url = getSetting('facebook_url', '');
                    $twitter_url = getSetting('twitter_url', '');
                    $instagram_url = getSetting('instagram_url', '');
                    $youtube_url = getSetting('youtube_url', '');
                    $discord_url = getSetting('discord_url', '');
                    $telegram_url = getSetting('telegram_url', '');
                    ?>
                    <h5>
                        <?php if (!empty($footer_site_logo)): ?>
                            <img src="<?php echo htmlspecialchars($footer_site_logo); ?>" alt="<?php echo htmlspecialchars($footer_site_name); ?>" height="20" class="me-2">
                        <?php else: ?>
                            <i class="fas fa-book-open me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($footer_site_name); ?>
                    </h5>
                    <p class="mb-3"><?php echo htmlspecialchars($footer_site_description); ?></p>
                    <div class="social-links">
                        <?php if (!empty($facebook_url)): ?>
                            <a href="<?php echo htmlspecialchars($facebook_url); ?>" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($twitter_url)): ?>
                            <a href="<?php echo htmlspecialchars($twitter_url); ?>" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($instagram_url)): ?>
                            <a href="<?php echo htmlspecialchars($instagram_url); ?>" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($youtube_url)): ?>
                            <a href="<?php echo htmlspecialchars($youtube_url); ?>" class="social-link" target="_blank"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($discord_url)): ?>
                            <a href="<?php echo htmlspecialchars($discord_url); ?>" class="social-link" target="_blank"><i class="fab fa-discord"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($telegram_url)): ?>
                            <a href="<?php echo htmlspecialchars($telegram_url); ?>" class="social-link" target="_blank"><i class="fab fa-telegram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h5>Hızlı Bağlantılar</h5>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-angle-right me-2"></i>Ana Sayfa</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/category.php?type=manga"><i class="fas fa-angle-right me-2"></i>Mangalar</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/category.php?type=comic"><i class="fas fa-angle-right me-2"></i>Çizgi Romanlar</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/series-info.php"><i class="fas fa-angle-right me-2"></i>Seri Rehberi</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/manga-info.php"><i class="fas fa-angle-right me-2"></i>Manga Rehberi</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/popular.php"><i class="fas fa-angle-right me-2"></i>Popüler İçerikler</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h5>Kullanıcı</h5>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-angle-right me-2"></i>Profilim</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/my-content.php"><i class="fas fa-angle-right me-2"></i>İçeriklerim</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/upload.php"><i class="fas fa-angle-right me-2"></i>İçerik Yükle</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-angle-right me-2"></i>Çıkış Yap</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/login.php"><i class="fas fa-angle-right me-2"></i>Giriş Yap</a></li>
                            <?php if (getSetting('allow_registration', '1') == '1'): ?>
                            <li><a href="<?php echo SITE_URL; ?>/register.php"><i class="fas fa-angle-right me-2"></i>Üye Ol</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/terms.php"><i class="fas fa-angle-right me-2"></i>Kullanım Koşulları</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php"><i class="fas fa-angle-right me-2"></i>Gizlilik Politikası</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h5>İletişim</h5>
                    <ul>
                        <?php 
                        $contact_email = getSetting('contact_email', getSetting('admin_email', 'info@example.com'));
                        ?>
                        <li><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($contact_email); ?></li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Türkiye</li>
                    </ul>
                    <div class="mt-3">
                        <h6>Bültenimize Abone Olun</h6>
                        <form class="d-flex mt-2">
                            <input type="email" class="form-control me-2" placeholder="E-posta adresiniz">
                            <button class="btn btn-primary" type="submit">Abone Ol</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.1);">
            
            <div class="text-center py-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('site_name', SITE_NAME)); ?>. Tüm hakları saklıdır. 
                    <span class="mx-2">|</span>
                    <a href="<?php echo SITE_URL; ?>/terms.php" class="text-white-50">Kullanım Koşulları</a>
                    <span class="mx-2">|</span>
                    <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white-50">Gizlilik</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary" style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 999; border-radius: 50%; width: 50px; height: 50px;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Özel JS -->
    <script src="<?php echo SITE_URL; ?>/js/main.js"></script>
    
    <script>
        // AOS Animasyonları Başlat
        AOS.init({
            duration: 600,
            once: true,
            offset: 80,
            disable: 'mobile' // Mobilde performans için devre dışı
        });
        
        // Optimized Scroll Handler - Tek bir scroll event listener kullan
        let isScrolling = false;
        
        function handleScroll() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    const scrollY = window.pageYOffset;
                    const navbar = document.querySelector('.navbar');
                    const backToTopButton = document.getElementById('backToTop');
                    
                    // Navbar Scroll Effect
                    if (scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                    
                    // Back to Top Button
                    if (scrollY > 300) {
                        backToTopButton.style.display = 'block';
                    } else {
                        backToTopButton.style.display = 'none';
                    }
                    
                    isScrolling = false;
                });
                isScrolling = true;
            }
        }
        
        // Throttled scroll event
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Back to Top Button Click
        const backToTopButton = document.getElementById('backToTop');
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Smooth scroll için CSS scroll-behavior desteği kontrol et
        if (!CSS.supports('scroll-behavior', 'smooth')) {
            // Polyfill veya alternatif smooth scroll implementasyonu
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    </script>
</body>
</html> 