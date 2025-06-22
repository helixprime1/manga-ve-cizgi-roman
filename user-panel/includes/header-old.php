<?php
// Güvenlik kontrolü
if (!defined('USER_PANEL_ACCESS')) {
    define('USER_PANEL_ACCESS', true);
}

// Yetki kontrolü - auth-check.php zaten dahil edilmiş olmalı
// Eğer auth-check.php dahil edilmemişse, dahil et
if (!function_exists('getCurrentUser') || !isset($current_user)) {
    require_once 'auth-check.php';
}

// Kullanıcı bilgilerini kontrol et
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    // Session bilgileri eksikse, veritabanından al
    $user = getCurrentUser();
    if ($user) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
    } else {
        // Kullanıcı bulunamazsa çıkış yap
        session_destroy();
        header('Location: ../login.php?redirect=user-panel/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>İçerik Yönetim Paneli</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/panel.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-user-circle me-2"></i>Panel</h3>
            <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?></p>
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="content.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'content.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder-open"></i>
                <span>İçeriklerim</span>
            </a>
            
            <a href="upload.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>">
                <i class="fas fa-upload"></i>
                <span>İçerik Yükle</span>
            </a>
            
            <a href="info.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'info.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i>
                <span>Eser Rehberi</span>
            </a>
            
            <a href="comments.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i>
                <span>Yorumlar</span>
            </a>
            
            <a href="notifications.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i>
                <span>Bildirimler</span>
            </a>
            
            <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Ayarlar</span>
            </a>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 1rem 0;">
            
            <a href="../index.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Ana Siteye Dön</span>
            </a>
            
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
            </div>
            
            <div class="navbar-right">
                <a href="../notifications.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-bell"></i>
                </a>
                
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'K', 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content"> 