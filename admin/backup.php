<?php
require_once '../includes/config.php';
session_start();
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$admin_user = getCurrentUser();
$page_title = 'Veritabanı Yedekleme';

// İstatistikleri al
$stats = [];
$user_query = "SELECT COUNT(*) as total FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;

$content_query = "SELECT COUNT(*) as total FROM content";
$content_result = mysqli_query($conn, $content_query);
$stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;

$pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;

$published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
$published_result = mysqli_query($conn, $published_query);
$stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;

// Yedekleme işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_backup') {
        $backup_dir = '../backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = $backup_dir . $backup_filename;
        
        // Veritabanı yedekleme
        $tables = [];
        $result = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        
        $sql_dump = "-- MySQL Database Backup\n";
        $sql_dump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Database: " . DB_NAME . "\n\n";
        
        foreach ($tables as $table) {
            // Tablo yapısını al
            $create_table = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $create_row = mysqli_fetch_row($create_table);
            
            $sql_dump .= "\n-- Table structure for table `$table`\n";
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_dump .= $create_row[1] . ";\n\n";
            
            // Veriyi al
            $data_result = mysqli_query($conn, "SELECT * FROM `$table`");
            if (mysqli_num_rows($data_result) > 0) {
                $sql_dump .= "-- Dumping data for table `$table`\n";
                $sql_dump .= "INSERT INTO `$table` VALUES ";
                
                $first = true;
                while ($data_row = mysqli_fetch_row($data_result)) {
                    if (!$first) {
                        $sql_dump .= ",\n";
                    }
                    $sql_dump .= "(";
                    for ($i = 0; $i < count($data_row); $i++) {
                        if ($i > 0) $sql_dump .= ", ";
                        $sql_dump .= $data_row[$i] === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $data_row[$i]) . "'";
                    }
                    $sql_dump .= ")";
                    $first = false;
                }
                $sql_dump .= ";\n\n";
            }
        }
        
        if (file_put_contents($backup_path, $sql_dump)) {
            $message = "Yedekleme başarıyla oluşturuldu: $backup_filename";
            $message_type = 'success';
        } else {
            $message = 'Yedekleme oluşturulurken bir hata oluştu.';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete_backup') {
        $backup_file = sanitizeInput($_POST['backup_file'] ?? '');
        $backup_path = '../backups/' . $backup_file;
        
        if (file_exists($backup_path) && unlink($backup_path)) {
            $message = 'Yedek dosya başarıyla silindi.';
            $message_type = 'success';
        } else {
            $message = 'Yedek dosya silinirken bir hata oluştu.';
            $message_type = 'danger';
        }
    }
}

// Mevcut yedekleri listele
$backup_dir = '../backups/';
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'filename' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    // Tarihe göre sırala (en yeni önce)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Veritabanı Yedekleme</h1>
                            <p class="text-muted">Veritabanınızı yedekleyin ve geri yükleyin</p>
                        </div>
                        <div>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="create_backup">
                                <button type="submit" class="btn btn-success" onclick="return confirmAction('Yeni yedek oluşturmak istediğinizden emin misiniz?')">
                                    <i class="fas fa-download me-2"></i>Yeni Yedek Oluştur
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Backup Info -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-info"></i>Yedekleme Bilgileri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>Önemli Notlar:</h6>
                                <ul class="mb-0">
                                    <li>Yedeklemeler otomatik olarak <code>/backups/</code> klasörüne kaydedilir</li>
                                    <li>Yedeklemeler veritabanının tam bir kopyasını içerir</li>
                                    <li>Büyük veritabanları için yedekleme işlemi zaman alabilir</li>
                                    <li>Düzenli yedekleme almayı unutmayın</li>
                                    <li>Yedek dosyalarını güvenli bir yerde saklayın</li>
                                </ul>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="stat-item">
                                        <div class="stat-number text-primary"><?php echo count($backups); ?></div>
                                        <div class="stat-label">Toplam Yedek</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-item">
                                        <div class="stat-number text-success"><?php echo $stats['total_users']; ?></div>
                                        <div class="stat-label">Kullanıcı</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-item">
                                        <div class="stat-number text-info"><?php echo $stats['total_content']; ?></div>
                                        <div class="stat-label">İçerik</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-item">
                                        <div class="stat-number text-warning">
                                            <?php 
                                            $total_size = 0;
                                            foreach ($backups as $backup) {
                                                $total_size += $backup['size'];
                                            }
                                            echo number_format($total_size / 1024 / 1024, 1);
                                            ?>
                                        </div>
                                        <div class="stat-label">MB Toplam</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2 text-warning"></i>Son Yedek
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($backups)): ?>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-file-archive fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo $backups[0]['filename']; ?></div>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', $backups[0]['date']); ?> - 
                                            <?php echo number_format($backups[0]['size'] / 1024, 1); ?> KB
                                        </small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <p class="mb-0">Henüz yedek oluşturulmamış</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-archive me-2"></i>Yedek Dosyaları
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($backups)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dosya Adı</th>
                                        <th>Tarih</th>
                                        <th>Boyut</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-archive text-success me-2"></i>
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($backup['filename']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold"><?php echo date('d.m.Y H:i:s', $backup['date']); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo timeAgo($backup['date']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo number_format($backup['size'] / 1024, 1); ?> KB</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../backups/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Bu yedek dosyasını silmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="action" value="delete_backup">
                                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-archive fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">Yedek Dosyası Bulunamadı</h4>
                            <p class="text-muted">Henüz hiç yedek oluşturulmamış.</p>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="create_backup">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>İlk Yedeği Oluştur
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-item {
    padding: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}
</style>

<?php 
// Zaman farkı hesaplama fonksiyonu
function timeAgo($time) {
    $time_difference = time() - $time;
    
    if ($time_difference < 1) {
        return 'az önce';
    }
    
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'yıl',
        30 * 24 * 60 * 60 => 'ay',
        24 * 60 * 60 => 'gün',
        60 * 60 => 'saat',
        60 => 'dakika',
        1 => 'saniye'
    );
    
    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ' önce';
        }
    }
}

require_once 'includes/footer.php'; 
?> 