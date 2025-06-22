<div class="admin-sidebar" id="adminSidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="fas fa-shield-alt me-2"></i>
            <span class="brand-text">Admin Panel</span>
        </a>
    </div>

    <!-- User Info -->
    <div class="px-3 py-2 border-bottom border-white border-opacity-10">
        <div class="d-flex align-items-center">
            <div class="avatar-sm bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center me-2">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <div class="fw-semibold"><?php echo htmlspecialchars($admin_user['username']); ?></div>
                <small class="text-white text-opacity-75">Administrator</small>
            </div>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Kullanıcılar</span>
                    <?php if (isset($stats) && $stats['total_users'] > 0): ?>
                        <span class="badge bg-primary ms-auto"><?php echo $stats['total_users']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="content.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'content.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">İçerikler</span>
                    <?php if (isset($stats) && $stats['total_content'] > 0): ?>
                        <span class="badge bg-success ms-auto"><?php echo $stats['total_content']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="pending.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pending.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span class="nav-text">Bekleyen İçerikler</span>
                    <?php if (isset($stats) && $stats['pending_content'] > 0): ?>
                        <span class="badge bg-warning ms-auto"><?php echo $stats['pending_content']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Raporlar</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="comments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span class="nav-text">Yorumlar</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="author-applications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'author-applications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-pen-fancy"></i>
                    <span class="nav-text">Yazar Başvuruları</span>
                    <?php 
                    // Bekleyen başvuru sayısını al
                    $pending_apps_query = "SELECT COUNT(*) as count FROM author_applications WHERE status = 'pending'";
                    $pending_apps_result = mysqli_query($conn, $pending_apps_query);
                    $pending_apps = $pending_apps_result ? mysqli_fetch_assoc($pending_apps_result)['count'] : 0;
                    if ($pending_apps > 0): ?>
                        <span class="badge bg-warning ms-auto"><?php echo $pending_apps; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span class="nav-text">Kategoriler</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="pages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-edit"></i>
                    <span class="nav-text">Sayfa Yönetimi</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Ayarlar</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="backup.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i>
                    <span class="nav-text">Yedekleme</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="logs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-text">Sistem Logları</span>
                </a>
            </li>
        </ul>
        
        <!-- Alt Menü -->
        <div class="sidebar-footer mt-auto">
            <hr class="border-white border-opacity-10">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="nav-text">Siteyi Görüntüle</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span class="nav-text">Profilim</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Çıkış Yap</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div> 