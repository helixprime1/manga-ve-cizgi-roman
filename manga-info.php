<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

$page_title = getSetting('manga_info_title', 'Eser ve Manga Rehberi');
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">Eser ve Manga Rehberi</h1>
        <p class="lead text-muted">Bu sayfa user panel içine taşınmıştır.</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center p-5">
                    <i class="fas fa-info-circle fa-5x text-primary mb-4"></i>
                    <h3 class="mb-3">Sayfa Taşındı</h3>
                    <p class="text-muted mb-4">Eser rehberi artık kullanıcı paneli içinde bulunmaktadır.</p>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="user-panel/info.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>Rehbere Git
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=user-panel/info.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yapın
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 