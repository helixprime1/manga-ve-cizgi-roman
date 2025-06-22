<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// Admin ayarlarını çek
$site_name = getSetting('site_name', SITE_NAME);
$site_description = getSetting('site_description', 'Manga ve çizgi roman paylaşım platformu');
$allow_registration = getSetting('allow_registration', '1');
$min_username_length = (int)getSetting('min_username_length', '3');
$max_username_length = (int)getSetting('max_username_length', '20');
$min_password_length = (int)getSetting('min_password_length', '6');
$email_verification = getSetting('email_verification', '0');

// Kayıt kapalıysa ana sayfaya yönlendir
if ($allow_registration != '1') {
    header('Location: index.php?error=registration_disabled');
    exit;
}

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Kullanıcı adı kontrolü
    if (empty($username)) {
        $errors[] = "Kullanıcı adı gereklidir.";
    } elseif (strlen($username) < $min_username_length || strlen($username) > $max_username_length) {
        $errors[] = "Kullanıcı adı {$min_username_length}-{$max_username_length} karakter arasında olmalıdır.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.";
    } else {
        // Kullanıcı adı daha önce alınmış mı kontrol et
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Bu kullanıcı adı zaten kullanılıyor.";
        }
        mysqli_stmt_close($stmt);
    }
    
    // E-posta kontrolü
    if (empty($email)) {
        $errors[] = "E-posta adresi gereklidir.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin.";
    } else {
        // E-posta daha önce kullanılmış mı kontrol et
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
        }
        mysqli_stmt_close($stmt);
    }
    
    // Şifre kontrolü
    if (empty($password)) {
        $errors[] = "Şifre gereklidir.";
    } elseif (strlen($password) < $min_password_length) {
        $errors[] = "Şifre en az {$min_password_length} karakter olmalıdır.";
    }
    
    // Şifre onayı kontrolü
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }
    
    // Hata yoksa kullanıcıyı kaydet
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $created_at = date('Y-m-d H:i:s');
        $email_verified = ($email_verification == '1') ? 0 : 1;
        $verification_token = ($email_verification == '1') ? bin2hex(random_bytes(32)) : null;
        
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role, email_verified, verification_token, created_at) VALUES (?, ?, ?, 'user', ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssiss", $username, $email, $hashed_password, $email_verified, $verification_token, $created_at);
        
        if (mysqli_stmt_execute($stmt)) {
            // E-posta doğrulama gerekiyorsa e-posta gönder
            if ($email_verification == '1' && $verification_token) {
                // Burada e-posta gönderme fonksiyonu çağrılabilir
                // sendVerificationEmail($email, $verification_token);
            }
            $success = true;
        } else {
            $errors[] = "Kayıt sırasında bir hata oluştu: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = 'Üye Ol';

// Diğer ayarları çek
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
    <title><?php echo $page_title . ' - ' . htmlspecialchars($site_name); ?></title>
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
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .register-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem;
            text-align: center;
        }
        .register-body {
            padding: 3rem;
        }
        .form-floating label {
            color: var(--text-color);
        }
        .form-floating input:focus ~ label {
            color: var(--primary-color);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background: #f87171;
            width: 33%;
        }
        .strength-medium {
            background: #fbbf24;
            width: 66%;
        }
        .strength-strong {
            background: #34d399;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="register-card mx-auto">
                <?php if ($success): ?>
                    <div class="text-center p-5">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h2 class="mb-4">Kayıt Başarılı!</h2>
                        <?php if ($email_verification == '1'): ?>
                            <p class="lead mb-4">Hesabınız başarıyla oluşturuldu. E-posta adresinize gönderilen doğrulama linkine tıklayarak hesabınızı aktifleştirin.</p>
                            <div class="alert alert-info">
                                <i class="fas fa-envelope me-2"></i>
                                E-posta doğrulama linki <strong><?php echo htmlspecialchars($email ?? ''); ?></strong> adresine gönderildi.
                            </div>
                        <?php else: ?>
                            <p class="lead mb-4">Hesabınız başarıyla oluşturuldu. Şimdi giriş yapabilirsiniz.</p>
                        <?php endif; ?>
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                        </a>
                    </div>
                <?php else: ?>
                    <div class="register-header">
                        <i class="fas fa-book-open fa-3x mb-3"></i>
                        <h2 class="fw-bold"><?php echo htmlspecialchars($site_name); ?>'e Katılın</h2>
                        <p class="mb-0"><?php echo htmlspecialchars($site_description); ?></p>
                    </div>
                    
                    <div class="register-body">
                        <div class="text-center mb-4">
                            <h3>Ücretsiz Hesap Oluştur</h3>
                            <p class="text-muted">Zaten hesabınız var mı? <a href="login.php" class="fw-bold text-decoration-none">Giriş Yap</a></p>
                        </div>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="register.php" id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="username" name="username" 
                                               placeholder="Kullanıcı adı" 
                                               value="<?php echo isset($username) ? $username : ''; ?>" required>
                                        <label for="username"><i class="fas fa-user me-2"></i>Kullanıcı Adı</label>
                                        <div class="form-text"><?php echo $min_username_length; ?>-<?php echo $max_username_length; ?> karakter, harf, rakam ve alt çizgi</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="E-posta" 
                                               value="<?php echo isset($email) ? $email : ''; ?>" required>
                                        <label for="email"><i class="fas fa-envelope me-2"></i>E-posta Adresi</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Şifre" required>
                                        <label for="password"><i class="fas fa-lock me-2"></i>Şifre</label>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <div class="form-text">En az <?php echo $min_password_length; ?> karakter</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                               placeholder="Şifre tekrar" required>
                                        <label for="password_confirm"><i class="fas fa-check me-2"></i>Şifre Tekrar</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    <a href="terms.php" target="_blank" class="text-decoration-none">Kullanım Koşulları</a> ve 
                                    <a href="privacy.php" target="_blank" class="text-decoration-none">Gizlilik Politikası</a>'nı 
                                    okudum ve kabul ediyorum.
                                </label>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Üye Ol
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-muted mb-3">veya</p>
                                <div class="d-flex gap-2 justify-content-center mb-4">
                                    <button type="button" class="btn btn-outline-secondary">
                                        <i class="fab fa-google"></i> Google
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary">
                                        <i class="fab fa-facebook-f"></i> Facebook
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Şifre güvenlik kontrolü
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('passwordStrength');
            const minLength = <?php echo $min_password_length; ?>;
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength';
            } else if (password.length < minLength) {
                strengthBar.className = 'password-strength strength-weak';
            } else if (password.length < (minLength + 4) || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                strengthBar.className = 'password-strength strength-medium';
            } else {
                strengthBar.className = 'password-strength strength-strong';
            }
        });
        
        // Şifre eşleşme kontrolü
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirm').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
            }
        });
    </script>
</body>
</html> 