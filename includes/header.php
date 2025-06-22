<?php
// Bakım modu kontrolü
require_once __DIR__ . '/maintenance_check.php';
checkMaintenanceMode();

// Admin ayarlarından site bilgilerini al
$site_name = getSetting('site_name', SITE_NAME);
$site_description = getSetting('site_description', 'Manga ve çizgi roman paylaşım platformu');
$site_keywords = getSetting('site_keywords', 'manga, çizgi roman, anime, comic');
$site_favicon = getSetting('site_favicon', '');
$google_analytics = getSetting('google_analytics', '');
$default_language = getSetting('default_language', 'tr');
?>
<!DOCTYPE html>
<html lang="<?php echo $default_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_keywords); ?>">
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Özel CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    
    <?php if (!empty($google_analytics)): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($google_analytics); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($google_analytics); ?>');
    </script>
    <?php endif; ?>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                    <?php 
                    $site_logo = getSetting('site_logo', '');
                    if (!empty($site_logo)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" height="30" class="me-2">
                    <?php else: ?>
                        <i class="fas fa-book-open me-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($site_name); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-home me-2"></i>Ana Sayfa
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-th-large me-2"></i>Kategoriler
                            </a>
                            <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/category.php?type=manga">
                                        <i class="fas fa-dragon me-2 text-primary"></i>Manga
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/category.php?type=comic">
                                        <i class="fas fa-mask me-2 text-info"></i>Çizgi Roman
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/popular.php">
                                        <i class="fas fa-fire me-2 text-danger"></i>Popüler
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/trending.php">
                                        <i class="fas fa-trending-up me-2 text-warning"></i>Trend
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/series-info.php">
                                <i class="fas fa-book-open me-2"></i>Seri Rehberi
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/user-panel/info.php">
                                <i class="fas fa-book-reader me-2"></i>Manga Rehberi
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/manga-info.php">
                                <i class="fas fa-book-reader me-2"></i>Manga Rehberi
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Arama Kutusu -->
                    <form class="d-flex me-3" action="<?php echo SITE_URL; ?>/search.php" method="GET">
                        <div class="search-box">
                            <input class="search-input" type="search" name="q" placeholder="Ara..." aria-label="Search">
                            <button class="search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Kullanıcı Menüsü -->
                    <div class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <?php $user = getCurrentUser(); ?>
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/avatars/<?php echo $user['avatar']; ?>" 
                                             class="user-avatar me-2" alt="Avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder me-2">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="d-none d-lg-inline"><?php echo $user['username']; ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userDropdown">
                                    <li class="dropdown-header">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/avatars/<?php echo $user['avatar']; ?>" 
                                                     class="user-avatar me-2" alt="Avatar">
                                            <?php else: ?>
                                                <div class="user-avatar-placeholder me-2">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo $user['username']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $user['email']; ?></small>
                                            </div>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                            <i class="fas fa-user-circle me-2"></i> Profilim
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/user-panel/">
                                            <i class="fas fa-tachometer-alt me-2"></i> İçerik Paneli
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/favorites.php">
                                            <i class="fas fa-heart me-2"></i> Favorilerim
                                        </a>
                                    </li>
                                    <?php if (isAdmin()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/admin/">
                                                <i class="fas fa-cog me-2"></i> Yönetim Paneli
                                            </a>
                                        </li>
                                    <?php elseif (isModerator()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-warning" href="<?php echo SITE_URL; ?>/moderation/">
                                                <i class="fas fa-shield-alt me-2"></i> Moderasyon Paneli
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="btn btn-outline-primary me-2" href="<?php echo SITE_URL; ?>/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Giriş Yap
                            </a>
                            <?php if (getSetting('allow_registration', '1') == '1'): ?>
                            <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/register.php">
                                <i class="fas fa-user-plus me-1"></i> Üye Ol
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="main-content"> 