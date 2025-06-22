<?php
// User Panel Yetki Kontrolü

// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'security.php';

// Güvenlik başlıklarını ekle
addSecurityHeaders();

// Session güvenlik kontrolü
if (!secureSession()) {
    header('Location: ../login.php?error=session_expired');
    exit;
}

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=user-panel/');
    exit;
}

// Yazar yetkisi kontrolü
$current_user = getCurrentUser();
if (!$current_user || !$current_user['is_author']) {
    showAccessDeniedPage();
}

// CSRF token tanımla
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', 'csrf_token');
}

// Rate limiting kontrolü
$user_ip = getUserIP();
$rate_limit_key = 'page_access_' . $current_user['id'] . '_' . $user_ip;

if (!checkRateLimit('page_access', $current_user['id'], 100, 60)) { // dakikada 100 istek
    http_response_code(429);
    die('Rate limit exceeded. Please try again later.');
}

function showAccessDeniedPage() {
    echo '<!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erişim Engellendi - ' . SITE_NAME . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .access-denied-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 20px;
                color: white;
                position: relative;
                overflow: hidden;
            }
            .access-denied-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.1\"%3E%3Ccircle cx=\"30\" cy=\"30\" r=\"2\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
                opacity: 0.5;
            }
            .access-denied-content {
                position: relative;
                z-index: 2;
            }
            .btn-gradient {
                background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
                border: none;
                transition: all 0.3s ease;
            }
            .btn-gradient:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-6">
                    <div class="card access-denied-card shadow-lg">
                        <div class="card-body text-center p-5 access-denied-content">
                            <div class="mb-4">
                                <i class="fas fa-user-lock fa-4x text-white"></i>
                            </div>
                            <h2 class="mb-3 fw-bold">Yazar Paneli</h2>
                            <h5 class="mb-3 opacity-75">Erişim Yetkisi Gerekli</h5>
                            <p class="mb-4 opacity-75">Bu panele erişebilmek için yazar yetkisine sahip olmanız gerekiyor. Yazar başvurunuzu yaparak içerik oluşturma ve yönetme yetkisi alabilirsiniz.</p>
                            
                            <div class="d-grid gap-3 mb-4">
                                <a href="../author-application.php" class="btn btn-gradient btn-lg">
                                    <i class="fas fa-pen-fancy me-2"></i>Yazar Başvurusu Yap
                                </a>
                                <a href="../index.php" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                                </a>
                            </div>
                            
                            <div class="border-top border-white border-opacity-25 pt-4 mt-4">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <i class="fas fa-upload fa-2x mb-2 opacity-75"></i>
                                        <div class="small">İçerik Yükle</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-chart-line fa-2x mb-2 opacity-75"></i>
                                        <div class="small">İstatistik Takibi</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-users fa-2x mb-2 opacity-75"></i>
                                        <div class="small">Okuyucu Etkileşimi</div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="opacity-75">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Yazar başvurunuz admin onayından sonra aktif olacaktır.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
    exit;
}
?> 