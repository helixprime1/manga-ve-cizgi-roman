<?php
session_start();
define('USER_PANEL_ACCESS', true);

// Basit yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/functions.php';

$user = getCurrentUser();
$user_id = $user['id'];

// Basit içerik sorgulama
$content_query = "SELECT * FROM content WHERE user_id = ? ORDER BY created_at DESC";
$content_stmt = mysqli_prepare($conn, $content_query);
mysqli_stmt_bind_param($content_stmt, "i", $user_id);
mysqli_stmt_execute($content_stmt);
$content_result = mysqli_stmt_get_result($content_stmt);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İçeriklerim - Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-dark text-white p-3">
                <h5><i class="fas fa-user"></i> Panel</h5>
                <div class="nav flex-column">
                    <a href="index.php" class="nav-link text-white">Dashboard</a>
                    <a href="content-fixed.php" class="nav-link text-warning">İçeriklerim</a>
                    <a href="upload.php" class="nav-link text-white">Yükle</a>
                    <a href="../index.php" class="nav-link text-white">Ana Site</a>
                </div>
            </div>
            
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>İçeriklerim</h2>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni İçerik
                    </a>
                </div>
                
                <div class="row">
                    <?php if (mysqli_num_rows($content_result) > 0): ?>
                        <?php while ($content = mysqli_fetch_assoc($content_result)): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <img src="../uploads/covers/<?php echo htmlspecialchars($content['cover_image']); ?>" 
                                         class="card-img-top" style="height: 200px; object-fit: cover;"
                                         onerror="this.src='../assets/images/no-image.svg'">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                        <p class="card-text">
                                            <span class="badge bg-<?php echo $content['status'] == 'published' ? 'success' : ($content['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($content['status']); ?>
                                            </span>
                                        </p>
                                        <div class="btn-group w-100">
                                            <a href="../view.php?id=<?php echo $content['id']; ?>" class="btn btn-sm btn-outline-primary">Görüntüle</a>
                                            <a href="../edit-content.php?id=<?php echo $content['id']; ?>" class="btn btn-sm btn-outline-warning">Düzenle</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <h4><i class="fas fa-info-circle"></i> Henüz İçerik Yok</h4>
                                <p>İlk içeriğinizi yüklemek için yukarıdaki "Yeni İçerik" butonuna tıklayın.</p>
                                <a href="upload.php" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> İçerik Yükle
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('Sayfa yüklendi - HTML karakterleri test: < > & " \'');
    </script>
</body>
</html> 