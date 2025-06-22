<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenir']);
    exit;
}

try {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'user');
    $is_author = isset($_POST['is_author']) ? 1 : 0;
    
    // Validasyon
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Kullanıcı adı gereklidir';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Kullanıcı adı en az 3 karakter olmalıdır';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir';
    }
    
    if (empty($email)) {
        $errors[] = 'E-posta adresi gereklidir';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz';
    }
    
    if (empty($password)) {
        $errors[] = 'Şifre gereklidir';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır';
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = 'Geçersiz rol seçimi';
    }
    
    // Kullanıcı adı kontrolü
    if (empty($errors)) {
        $check_username = "SELECT id FROM users WHERE username = '$username'";
        $username_result = mysqli_query($conn, $check_username);
        if (mysqli_num_rows($username_result) > 0) {
            $errors[] = 'Bu kullanıcı adı zaten kullanılıyor';
        }
    }
    
    // E-posta kontrolü
    if (empty($errors)) {
        $check_email = "SELECT id FROM users WHERE email = '$email'";
        $email_result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($email_result) > 0) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode('<br>', $errors)
        ]);
        exit;
    }
    
    // Şifreyi hash'le
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcıyı ekle
    $author_approved_at = $is_author ? "NOW()" : "NULL";
    $insert_query = "INSERT INTO users (username, email, password, role, is_author, author_approved_at, created_at) VALUES ('$username', '$email', '$hashed_password', '$role', $is_author, $author_approved_at, NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        $user_id = mysqli_insert_id($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Kullanıcı başarıyla eklendi',
            'user_id' => $user_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kullanıcı eklenirken bir hata oluştu: ' . mysqli_error($conn)
        ]);
    }
    
} catch (Exception $e) {
    error_log('Add User API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu'
    ]);
}
?> 