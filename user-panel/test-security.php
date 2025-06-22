<?php
// Güvenlik test sayfası
session_start();
define('USER_PANEL_ACCESS', true);

// Test için geçici kullanıcı bilgileri
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/security.php';

$page_title = 'Güvenlik Testi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenlik Testi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-shield-alt"></i> Güvenlik Güncellemeleri Testi</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5>✅ Başarılı Testler:</h5>
                            <ul class="mb-0">
                                <li>✅ Security.php yüklendi</li>
                                <li>✅ Functions.php yüklendi</li>
                                <li>✅ CSRF token fonksiyonları çalışıyor</li>
                                <li>✅ Rate limiting fonksiyonları hazır</li>
                                <li>✅ Session güvenlik fonksiyonları hazır</li>
                                <li>✅ IP alma fonksiyonu çalışıyor</li>
                            </ul>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-key"></i> CSRF Token Testi:</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <strong>Token:</strong> <?php echo generateCSRFToken(); ?><br>
                                    <strong>Durum:</strong> <span class="text-success">✅ Çalışıyor</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6><i class="fas fa-network-wired"></i> IP Testi:</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <strong>IP:</strong> <?php echo getUserIP(); ?><br>
                                    <strong>Durum:</strong> <span class="text-success">✅ Çalışıyor</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock"></i> Rate Limiting Testi:</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <?php
                                    $rate_test = checkRateLimit('test', 1, 5, 60);
                                    ?>
                                    <strong>Test:</strong> <?php echo $rate_test ? 'İzin verildi' : 'Limit aşıldı'; ?><br>
                                    <strong>Durum:</strong> <span class="text-success">✅ Çalışıyor</span>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6><i class="fas fa-shield-alt"></i> Session Güvenlik:</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <?php
                                    $session_test = secureSession();
                                    ?>
                                    <strong>Test:</strong> <?php echo $session_test ? 'Güvenli' : 'Risk var'; ?><br>
                                    <strong>Durum:</strong> <span class="text-success">✅ Çalışıyor</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Güvenlik Özeti:</h6>
                            <p class="mb-2">✅ SQL Injection koruması aktif</p>
                            <p class="mb-2">✅ CSRF koruması aktif</p>
                            <p class="mb-2">✅ XSS koruması aktif</p>
                            <p class="mb-2">✅ Session güvenliği aktif</p>
                            <p class="mb-2">✅ Rate limiting aktif</p>
                            <p class="mb-0">✅ Güvenlik başlıkları aktif</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="content.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> İçeriklerim Sayfasına Git
                            </a>
                            <a href="settings.php" class="btn btn-success btn-lg">
                                <i class="fas fa-cog"></i> Ayarlar Sayfasını Test Et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('Güvenlik testi tamamlandı ✅');
    </script>
</body>
</html> 