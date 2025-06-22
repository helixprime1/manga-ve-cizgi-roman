<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-shield-alt fa-2x mb-2 text-white"></i>
        <h4>Moderasyon</h4>
        <small>Panel v2.0</small>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Ana Sayfa">
                <i class="fas fa-tachometer-alt icon"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <li>
            <a href="content.php" data-bs-toggle="tooltip" data-bs-placement="right" title="İçerik Yönetimi">
                <i class="fas fa-file-alt icon"></i>
                <span>İçerik Yönetimi</span>
                <?php if (isset($stats['pending_content']) && $stats['pending_content'] > 0): ?>
                    <span class="badge-notification"><?php echo $stats['pending_content']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li>
            <a href="comments.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Yorum Yönetimi">
                <i class="fas fa-comments icon"></i>
                <span>Yorum Yönetimi</span>
                <?php if (isset($stats['reported_comments']) && $stats['reported_comments'] > 0): ?>
                    <span class="badge-notification"><?php echo $stats['reported_comments']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li>
            <a href="reports.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Bildirimleri İncele">
                <i class="fas fa-flag icon"></i>
                <span>Rapor Yönetimi</span>
            </a>
        </li>
        
        <li>
            <a href="users.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Kullanıcı Moderasyonu">
                <i class="fas fa-users icon"></i>
                <span>Kullanıcı Yönetimi</span>
            </a>
        </li>
        
        <li>
            <a href="author-applications.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Yazar Başvuruları">
                <i class="fas fa-pen-fancy icon"></i>
                <span>Yazar Başvuruları</span>
                <?php 
                // Bekleyen başvuru sayısını al
                $pending_apps_query = "SELECT COUNT(*) as count FROM author_applications WHERE status = 'pending'";
                $pending_apps_result = mysqli_query($conn, $pending_apps_query);
                $pending_apps = $pending_apps_result ? mysqli_fetch_assoc($pending_apps_result)['count'] : 0;
                if ($pending_apps > 0): ?>
                    <span class="badge-notification"><?php echo $pending_apps; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <li>
            <a href="logs.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Moderasyon Logları">
                <i class="fas fa-history icon"></i>
                <span>Aktivite Logları</span>
            </a>
        </li>
        
        <li>
            <a href="statistics.php" data-bs-toggle="tooltip" data-bs-placement="right" title="İstatistikler ve Raporlar">
                <i class="fas fa-chart-bar icon"></i>
                <span>İstatistikler</span>
            </a>
        </li>
        
        <!-- Ayırıcı -->
        <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <a href="profile.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Profil Ayarları">
                <i class="fas fa-user-cog icon"></i>
                <span>Profil</span>
            </a>
        </li>
        
        <li>
            <a href="../index.php" target="_blank" data-bs-toggle="tooltip" data-bs-placement="right" title="Ana Siteyi Aç">
                <i class="fas fa-home icon"></i>
                <span>Ana Site</span>
                <i class="fas fa-external-link-alt ms-auto" style="font-size: 0.8rem; opacity: 0.7;"></i>
            </a>
        </li>
        
        <?php if (isAdmin()): ?>
        <li>
            <a href="../admin/index.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Admin Paneli">
                <i class="fas fa-cog icon"></i>
                <span>Admin Panel</span>
                <i class="fas fa-external-link-alt ms-auto" style="font-size: 0.8rem; opacity: 0.7;"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <li>
            <a href="javascript:void(0)" onclick="confirmLogout()" class="text-danger" 
               data-bs-toggle="tooltip" data-bs-placement="right" title="Güvenli Çıkış">
                <i class="fas fa-sign-out-alt icon"></i>
                <span>Çıkış Yap</span>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Alt Bilgi -->
    <div class="sidebar-footer" style="position: absolute; bottom: 1rem; left: 1rem; right: 1rem; text-align: center; opacity: 0.7;">
        <small>
            <i class="fas fa-user me-1"></i>
            <?php echo htmlspecialchars($moderator_user['username']); ?>
        </small>
        <br>
        <small style="font-size: 0.7rem;">
            <?php echo date('d.m.Y H:i'); ?>
        </small>
    </div>
</div> 