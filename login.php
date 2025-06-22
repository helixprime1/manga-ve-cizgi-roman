<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Kullanıcı adı kontrolü
    if (empty($username)) {
        $errors[] = "Kullanıcı adı veya e-posta gereklidir.";
    }
    
    // Şifre kontrolü
    if (empty($password)) {
        $errors[] = "Şifre gereklidir.";
    }
    
    // Hata yoksa giriş yap
    if (empty($errors)) {
        // Kullanıcı adı veya e-posta ile kullanıcıyı bul - Prepared statement kullan
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Şifre doğrulama
            if (password_verify($password, $user['password'])) {
                // Oturum başlat
                $_SESSION['user_id'] = $user['id'];
                
                // Yönlendirme - XSS koruması
                if (isset($_GET['redirect'])) {
                    $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
                    // Sadece iç linklere izin ver
                    if (strpos($redirect, 'http') === false && strpos($redirect, '//') === false) {
                        header('Location: ' . $redirect);
                    } else {
                        // Rol bazlı yönlendirme
                        if ($user['role'] === 'admin') {
                            header('Location: admin/index.php');
                        } elseif ($user['role'] === 'moderator') {
                            header('Location: moderation/index.php');
                        } else {
                            header('Location: index.php');
                        }
                    }
                } else {
                    // Rol bazlı yönlendirme
                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } elseif ($user['role'] === 'moderator') {
                        header('Location: moderation/index.php');
                    } else {
                        header('Location: index.php');
                    }
                }
                exit;
            } else {
                $errors[] = "Hatalı şifre.";
            }
        } else {
            $errors[] = "Kullanıcı bulunamadı.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

$page_title = 'Giriş Yap';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
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
    <style>
        body {
            height: 100vh;
            overflow: hidden;
        }
        .login-container {
            height: 100vh;
            display: flex;
        }
        .login-left {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,218.7C672,203,768,149,864,128C960,107,1056,117,1152,138.7C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat;
            background-size: cover;
            animation: wave 10s linear infinite;
        }
        @keyframes wave {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .login-left-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: var(--light-color);
        }
        .login-form-container {
            width: 100%;
            max-width: 400px;
        }
        .form-floating label {
            color: var(--text-color);
        }
        .form-floating input:focus ~ label {
            color: var(--primary-color);
        }
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left col-lg-5">
            <div class="login-left-content">
                <i class="fas fa-book-open fa-4x mb-4"></i>
                <h1 class="display-4 fw-bold mb-4"><?php echo SITE_NAME; ?></h1>
                <p class="lead">Binlerce manga ve çizgi romanı keşfedin, kendi eserlerinizi paylaşın.</p>
                <div class="mt-5">
                    <p class="mb-2">Hesabınız yok mu?</p>
                    <a href="register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Üye Ol
                    </a>
                </div>
            </div>
        </div>
        
        <div class="login-right col-lg-7">
            <div class="login-form-container">
                <div class="text-center mb-5">
                    <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                        <h2 class="fw-bold" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <i class="fas fa-book-open me-2"></i><?php echo SITE_NAME; ?>
                        </h2>
                    </a>
                    <p class="text-muted">Hesabınıza giriş yapın</p>
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
                
                <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . $_GET['redirect'] : ''; ?>">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Kullanıcı adı veya e-posta" 
                               value="<?php echo isset($username) ? $username : ''; ?>" required>
                        <label for="username"><i class="fas fa-user me-2"></i>Kullanıcı Adı veya E-posta</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Şifre" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Şifre</label>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Beni Hatırla
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none">Şifremi Unuttum</a>
                    </div>
                    
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-muted mb-3">veya</p>
                        <div class="d-flex gap-2 justify-content-center mb-4">
                            <button type="button" class="btn btn-outline-secondary">
                                <i class="fab fa-google"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary">
                                <i class="fab fa-twitter"></i>
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Hesabınız yok mu? 
                            <a href="register.php" class="fw-bold text-decoration-none">Üye Ol</a>
                        </p>
                        <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 