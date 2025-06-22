<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Moderasyon Paneli</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS (isteğe bağlı) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Özel Moderasyon CSS -->
    <link rel="stylesheet" href="assets/css/moderation.css">
</head>
<body>
    <!-- Top Navbar -->
    <nav class="top-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-link mobile-toggle d-md-none" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 text-gradient"><?php echo isset($page_title) ? $page_title : 'Moderasyon Paneli'; ?></h5>
        </div>
        
        <div class="user-menu">
            <!-- Bildirimler -->
            <div class="dropdown">
                <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown" 
                        title="Bekleyen İşlemler">
                    <i class="fas fa-bell"></i>
                    <?php
                    // Bekleyen işlem sayısını hesapla
                    $pending_count = 0;
                    if (isset($stats)) {
                        $pending_count = ($stats['pending_content'] ?? 0) + ($stats['reported_comments'] ?? 0);
                    }
                    if ($pending_count > 0):
                    ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $pending_count; ?>
                            <span class="visually-hidden">bekleyen işlem</span>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-custom">
                    <li><h6 class="dropdown-header"><i class="fas fa-tasks me-2"></i>Bekleyen İşlemler</h6></li>
                    <?php if (isset($stats['pending_content']) && $stats['pending_content'] > 0): ?>
                        <li>
                            <a class="dropdown-item" href="content.php?status=pending">
                                <i class="fas fa-clock me-2 text-warning"></i>
                                <?php echo $stats['pending_content']; ?> Bekleyen İçerik
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($stats['reported_comments']) && $stats['reported_comments'] > 0): ?>
                        <li>
                            <a class="dropdown-item" href="comments.php?filter=reported">
                                <i class="fas fa-flag me-2 text-danger"></i>
                                <?php echo $stats['reported_comments']; ?> Bildirilen Yorum
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($pending_count == 0): ?>
                        <li><span class="dropdown-item-text text-muted">
                            <i class="fas fa-check-circle me-2 text-success"></i>Bekleyen işlem yok
                        </span></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Hızlı Eylemler -->
            <div class="dropdown">
                <button class="btn btn-link" type="button" data-bs-toggle="dropdown" title="Hızlı Eylemler">
                    <i class="fas fa-bolt"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-custom">
                    <li><h6 class="dropdown-header"><i class="fas fa-bolt me-2"></i>Hızlı Eylemler</h6></li>
                    <li><a class="dropdown-item" href="content.php?status=pending">
                        <i class="fas fa-file-alt me-2"></i>İçerik Yönetimi
                    </a></li>
                    <li><a class="dropdown-item" href="comments.php?filter=reported">
                        <i class="fas fa-comments me-2"></i>Yorum Moderasyonu
                    </a></li>
                    <li><a class="dropdown-item" href="users.php?filter=new">
                        <i class="fas fa-users me-2"></i>Yeni Kullanıcılar
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="statistics.php">
                        <i class="fas fa-chart-bar me-2"></i>İstatistikler
                    </a></li>
                </ul>
            </div>
            
            <!-- Kullanıcı Menüsü -->
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($moderator_user['username'], 0, 2)); ?>
                    </div>
                    <span class="ms-2 d-none d-md-inline"><?php echo htmlspecialchars($moderator_user['username']); ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-custom">
                    <li><h6 class="dropdown-header">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($moderator_user['username']); ?>
                    </h6></li>
                    <li><a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user-cog me-2"></i>Profil Ayarları
                    </a></li>
                    <li><a class="dropdown-item" href="logs.php?moderator=<?php echo $moderator_user['id']; ?>">
                        <i class="fas fa-history me-2"></i>Aktivitelerim
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../index.php" target="_blank">
                        <i class="fas fa-home me-2"></i>Ana Site
                    </a></li>
                    <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="../admin/index.php">
                            <i class="fas fa-cog me-2"></i>Admin Panel
                        </a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Aktif sayfa menü öğesini işaretle
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const menuLinks = document.querySelectorAll('.sidebar-menu a');
            
            menuLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    link.classList.add('active');
                }
            });
            
            // Tooltip'leri etkinleştir
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 