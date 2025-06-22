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
$page_title = 'Yazar Başvuruları';

// author_applications tablosunun var olup olmadığını kontrol et
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'author_applications'");
if (mysqli_num_rows($table_check) == 0) {
    // Tablo yoksa oluştur
    $create_table_sql = "CREATE TABLE IF NOT EXISTS author_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        bio TEXT NOT NULL,
        experience TEXT,
        portfolio_links TEXT,
        sample_work_description TEXT,
        why_author TEXT NOT NULL,
        preferred_genres VARCHAR(255),
        social_media_links TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        reviewed_by INT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_applied_at (applied_at),
        INDEX idx_user_id (user_id)
    )";
    
    if (!mysqli_query($conn, $create_table_sql)) {
        die("Tablo oluşturma hatası: " . mysqli_error($conn));
    }
    
    // Users tablosuna gerekli kolonları ekle
    $add_columns_sql = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_author TINYINT(1) DEFAULT 0 AFTER role",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS author_approved_at TIMESTAMP NULL AFTER is_author"
    ];
    
    foreach ($add_columns_sql as $sql) {
        mysqli_query($conn, $sql); // Hata varsa da devam et
    }
}

// Sayfalama parametreleri
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtreleme parametreleri
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Başvuru durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action = sanitizeInput($_POST['action']);
    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if ($application_id > 0 && in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $reviewed_at = date('Y-m-d H:i:s');
        $reviewed_by = $_SESSION['user_id'];
        
        // Başvuru durumunu güncelle
        $update_stmt = mysqli_prepare($conn, "UPDATE author_applications SET status = ?, admin_notes = ?, reviewed_at = ?, reviewed_by = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "sssii", $new_status, $admin_notes, $reviewed_at, $reviewed_by, $application_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            if ($new_status === 'approved') {
                // Kullanıcıyı yazar yap
                $app_stmt = mysqli_prepare($conn, "SELECT user_id FROM author_applications WHERE id = ?");
                mysqli_stmt_bind_param($app_stmt, "i", $application_id);
                mysqli_stmt_execute($app_stmt);
                $app_result = mysqli_stmt_get_result($app_stmt);
                
                if ($app_row = mysqli_fetch_assoc($app_result)) {
                    $user_update_stmt = mysqli_prepare($conn, "UPDATE users SET is_author = 1, author_approved_at = ? WHERE id = ?");
                    mysqli_stmt_bind_param($user_update_stmt, "si", $reviewed_at, $app_row['user_id']);
                    mysqli_stmt_execute($user_update_stmt);
                    mysqli_stmt_close($user_update_stmt);
                }
                mysqli_stmt_close($app_stmt);
                
                $success_message = "Başvuru onaylandı ve kullanıcı yazar olarak işaretlendi.";
            } else {
                $success_message = "Başvuru reddedildi.";
            }
        } else {
            $error_message = "Güncelleme sırasında hata oluştu: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Toplam başvuru sayısını al
$count_query = "SELECT COUNT(*) as total FROM author_applications a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
$count_params = [];
$count_types = "";

if (!empty($filter_status)) {
    $count_query .= " AND a.status = ?";
    $count_params[] = $filter_status;
    $count_types .= "s";
}

if (!empty($search)) {
    $count_query .= " AND (a.full_name LIKE ? OR u.username LIKE ? OR a.email LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if (!empty($count_params)) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_applications = mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);
    } else {
        $total_applications = 0;
    }
} else {
    $count_result = mysqli_query($conn, $count_query);
    if ($count_result) {
        $total_applications = mysqli_fetch_assoc($count_result)['total'];
    } else {
        $total_applications = 0;
    }
}

$total_pages = ceil($total_applications / $limit);

// Başvuruları al
$applications_query = "SELECT a.*, u.username, u.email as user_email, 
                       (SELECT username FROM users WHERE id = a.reviewed_by) as reviewer_name
                       FROM author_applications a 
                       LEFT JOIN users u ON a.user_id = u.id 
                       WHERE 1=1";

$app_params = [];
$app_types = "";

if (!empty($filter_status)) {
    $applications_query .= " AND a.status = ?";
    $app_params[] = $filter_status;
    $app_types .= "s";
}

if (!empty($search)) {
    $applications_query .= " AND (a.full_name LIKE ? OR u.username LIKE ? OR a.email LIKE ?)";
    $search_param = "%$search%";
    $app_params[] = $search_param;
    $app_params[] = $search_param;
    $app_params[] = $search_param;
    $app_types .= "sss";
}

$applications_query .= " ORDER BY a.applied_at DESC LIMIT ? OFFSET ?";
$app_params[] = $limit;
$app_params[] = $offset;
$app_types .= "ii";

if (!empty($app_params)) {
    $applications_stmt = mysqli_prepare($conn, $applications_query);
    if ($applications_stmt) {
        mysqli_stmt_bind_param($applications_stmt, $app_types, ...$app_params);
        mysqli_stmt_execute($applications_stmt);
        $applications_result = mysqli_stmt_get_result($applications_stmt);
    } else {
        // Hata durumunda boş sonuç oluştur
        $applications_result = mysqli_query($conn, "SELECT * FROM author_applications WHERE 1=0");
    }
} else {
    $applications_result = mysqli_query($conn, $applications_query);
    if (!$applications_result) {
        // Hata durumunda boş sonuç oluştur
        $applications_result = mysqli_query($conn, "SELECT * FROM author_applications WHERE 1=0");
    }
}

// İstatistikler
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM author_applications";
$stats_result = mysqli_query($conn, $stats_query);
if ($stats_result) {
    $stats = mysqli_fetch_assoc($stats_result);
} else {
    // Varsayılan değerler
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
}

require_once 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Yazar Başvuruları</h1>
            <p class="text-muted">Kullanıcıların yazar olmak için yaptığı başvuruları yönetin</p>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['total']; ?></h4>
                    <small class="text-muted">Toplam Başvuru</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['pending']; ?></h4>
                    <small class="text-muted">Beklemede</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['approved']; ?></h4>
                    <small class="text-muted">Onaylanan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                    <h4 class="mb-1"><?php echo $stats['rejected']; ?></h4>
                    <small class="text-muted">Reddedilen</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreleme -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3 mb-3">
                    <label for="search" class="form-label">Ara</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Ad, kullanıcı adı, e-posta..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tümü</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Onaylanan</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Reddedilen</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Filtrele
                    </button>
                    <a href="author-applications.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Temizle
                    </a>
                </div>
                <div class="col-md-2 mb-3 text-end">
                    <small class="text-muted"><?php echo $total_applications; ?> sonuç</small>
                </div>
            </form>
        </div>
    </div>

    <!-- Başvuru Listesi -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($applications_result && mysqli_num_rows($applications_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Başvuran</th>
                                <th>İletişim</th>
                                <th>Başvuru Tarihi</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($application = mysqli_fetch_assoc($applications_result)): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($application['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($application['username'] ?? 'Bilinmiyor'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div><?php echo htmlspecialchars($application['email']); ?></div>
                                            <?php if (!empty($application['phone'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($application['phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('d.m.Y H:i', strtotime($application['applied_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($application['status']) {
                                            case 'pending':
                                                $status_class = 'warning';
                                                $status_text = 'Beklemede';
                                                break;
                                            case 'approved':
                                                $status_class = 'success';
                                                $status_text = 'Onaylandı';
                                                break;
                                            case 'rejected':
                                                $status_class = 'danger';
                                                $status_text = 'Reddedildi';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <?php if ($application['status'] !== 'pending' && !empty($application['reviewer_name'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($application['reviewer_name']); ?> tarafından
                                                <?php echo date('d.m.Y', strtotime($application['reviewed_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="viewApplication(<?php echo $application['id']; ?>)">
                                            <i class="fas fa-eye"></i> Detay
                                        </button>
                                        <?php if ($application['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-success btn-sm ms-1" 
                                                    onclick="approveApplication(<?php echo $application['id']; ?>)">
                                                <i class="fas fa-check"></i> Onayla
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm ms-1" 
                                                    onclick="rejectApplication(<?php echo $application['id']; ?>)">
                                                <i class="fas fa-times"></i> Reddet
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($application['user_id'])): ?>
                                            <?php
                                            // Kullanıcının mevcut yazar durumunu kontrol et
                                            $user_check_stmt = mysqli_prepare($conn, "SELECT is_author FROM users WHERE id = ?");
                                            mysqli_stmt_bind_param($user_check_stmt, "i", $application['user_id']);
                                            mysqli_stmt_execute($user_check_stmt);
                                            $user_check_result = mysqli_stmt_get_result($user_check_stmt);
                                            $user_check = mysqli_fetch_assoc($user_check_result);
                                            mysqli_stmt_close($user_check_stmt);
                                            ?>
                                            <button type="button" class="btn btn-outline-info btn-sm ms-1" 
                                                    onclick="toggleAuthorStatus(<?php echo $application['user_id']; ?>, <?php echo $user_check['is_author'] ? 'false' : 'true'; ?>)"
                                                    title="<?php echo $user_check['is_author'] ? 'Yazar Yetkisini Kaldır' : 'Yazar Yetkisi Ver'; ?>">
                                                <i class="fas fa-pen-fancy"></i> 
                                                <?php echo $user_check['is_author'] ? 'Yetki-' : 'Yetki+'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Başvuru sayfalama" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-5x text-muted mb-4"></i>
                    <h4 class="text-muted">Başvuru Bulunamadı</h4>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($filter_status)): ?>
                            Arama kriterlerinize uygun başvuru bulunamadı.
                        <?php else: ?>
                            Henüz hiç yazar başvurusu yapılmamış.
                        <?php endif; ?>
                    </p>
                    <div class="mt-4">
                        <a href="../author-application.php" class="btn btn-primary" target="_blank">
                            <i class="fas fa-pen-fancy me-2"></i>Test Başvurusu Yap
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Başvuru Detay Modal -->
<div class="modal fade" id="applicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Başvuru Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="applicationDetails">
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Onaylama/Reddetme Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalTitle">Başvuruyu Onayla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="application_id" id="actionApplicationId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Yönetici Notları</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Bu karar hakkında not ekleyin (opsiyonel)"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="actionMessage">Bu işlem geri alınamaz.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn" id="actionButton">Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewApplication(applicationId) {
    const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
    const details = document.getElementById('applicationDetails');
    
    // Loading göster
    details.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    `;
    modal.show();
    
    // AJAX ile başvuru detaylarını getir
    fetch(`api/get-application-details.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                details.innerHTML = data.html;
            } else {
                details.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Hata</h6>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            details.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Hata</h6>
                    <p>Başvuru detayları yüklenirken bir hata oluştu.</p>
                </div>
            `;
        });
}

function approveApplication(applicationId) {
    showActionModal(applicationId, 'approve', 'Başvuruyu Onayla', 
                   'Bu başvuruyu onaylamak istediğinizden emin misiniz? Kullanıcı yazar olarak işaretlenecek.',
                   'btn-success', 'Onayla');
}

function rejectApplication(applicationId) {
    showActionModal(applicationId, 'reject', 'Başvuruyu Reddet', 
                   'Bu başvuruyu reddetmek istediğinizden emin misiniz?',
                   'btn-danger', 'Reddet');
}

function showActionModal(applicationId, action, title, message, buttonClass, buttonText) {
    document.getElementById('actionApplicationId').value = applicationId;
    document.getElementById('actionType').value = action;
    document.getElementById('actionModalTitle').textContent = title;
    document.getElementById('actionMessage').textContent = message;
    
    const button = document.getElementById('actionButton');
    button.className = `btn ${buttonClass}`;
    button.textContent = buttonText;
    
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
}

function toggleAuthorStatus(userId, giveAuthor) {
    const action = giveAuthor === 'true' ? 'ver' : 'kaldır';
    const message = `Bu kullanıcının yazar yetkisini ${action}mak istediğinizden emin misiniz?`;
    
    if (confirm(message)) {
        // Form oluştur ve gönder
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'users.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_author';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

        </div> <!-- container-fluid -->
    </div> <!-- admin-content -->
</div> <!-- admin-wrapper -->

<?php require_once 'includes/footer.php'; ?> 