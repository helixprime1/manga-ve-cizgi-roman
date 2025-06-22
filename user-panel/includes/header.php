<?php
if (!defined('USER_PANEL_ACCESS')) {
    define('USER_PANEL_ACCESS', true);
}

if (!function_exists('getCurrentUser') || !isset($current_user)) {
    require_once 'auth-check.php';
}

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    $user = getCurrentUser();
    if ($user) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
    } else {
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/panel.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>👤 Panel</h3>
            <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?></p>
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📊</span>
                <span>Dashboard</span>
            </a>
            
            <a href="content.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'content.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📁</span>
                <span>İçeriklerim</span>
            </a>
            
            <a href="upload.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📤</span>
                <span>İçerik Yükle</span>
            </a>
            
            <a href="info.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'info.php' ? 'active' : ''; ?>">
                <span class="menu-icon">ℹ️</span>
                <span>Eser Rehberi</span>
            </a>
            
            <a href="comments.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>">
                <span class="menu-icon">💬</span>
                <span>Yorumlar</span>
            </a>
            
            <a href="notifications.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <span class="menu-icon">🔔</span>
                <span>Bildirimler</span>
            </a>
            
            <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <span class="menu-icon">⚙️</span>
                <span>Ayarlar</span>
            </a>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 1rem 0;">
            
            <a href="../index.php" class="menu-item">
                <span class="menu-icon">🏠</span>
                <span>Ana Siteye Dön</span>
            </a>
            
            <a href="../logout.php" class="menu-item">
                <span class="menu-icon">🚪</span>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    ☰
                </button>
                <h4><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
            </div>
            
            <div class="navbar-right">
                <a href="../notifications.php" class="btn btn-outline-primary btn-sm">
                    🔔
                </a>
                
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'K', 0, 1)); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-content"> 