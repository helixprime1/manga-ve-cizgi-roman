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
$page_title = 'Kategori Yönetimi';

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

// Kategori tablosu yoksa oluştur
$create_categories_table = "
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    color VARCHAR(7) DEFAULT '#6366f1',
    icon VARCHAR(50) DEFAULT 'fas fa-tag',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_categories_table);

// Varsayılan kategorileri ekle
$default_categories = [
    ['Aksiyon', 'aksiyon', 'Aksiyon dolu macera hikayeleri', '#ef4444', 'fas fa-fist-raised'],
    ['Romantik', 'romantik', 'Aşk ve romantik hikayeler', '#ec4899', 'fas fa-heart'],
    ['Komedi', 'komedi', 'Eğlenceli ve komik hikayeler', '#f59e0b', 'fas fa-laugh'],
    ['Drama', 'drama', 'Duygusal ve dramatik hikayeler', '#8b5cf6', 'fas fa-theater-masks'],
    ['Fantastik', 'fantastik', 'Büyü ve fantezi dünyaları', '#10b981', 'fas fa-magic'],
    ['Bilim Kurgu', 'bilim-kurgu', 'Gelecek ve teknoloji hikayeleri', '#06b6d4', 'fas fa-rocket'],
    ['Korku', 'korku', 'Gerilim ve korku hikayeleri', '#1f2937', 'fas fa-ghost'],
    ['Spor', 'spor', 'Spor temalı hikayeler', '#f97316', 'fas fa-running']
];

foreach ($default_categories as $cat) {
    $check_query = "SELECT id FROM categories WHERE slug = '{$cat[1]}'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO categories (name, slug, description, color, icon) VALUES ('{$cat[0]}', '{$cat[1]}', '{$cat[2]}', '{$cat[3]}', '{$cat[4]}')";
        mysqli_query($conn, $insert_query);
    }
}

// Kategori işlemleri
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $color = sanitizeInput($_POST['color'] ?? '#6366f1');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-tag');
        
        if (!empty($name) && !empty($slug)) {
            $insert_query = "INSERT INTO categories (name, slug, description, color, icon) VALUES ('$name', '$slug', '$description', '$color', '$icon')";
            if (mysqli_query($conn, $insert_query)) {
                $message = 'Kategori başarıyla eklendi.';
                $message_type = 'success';
            } else {
                $message = 'Kategori eklenirken bir hata oluştu.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Kategori adı ve slug alanları zorunludur.';
            $message_type = 'warning';
        }
    } elseif ($action === 'edit_category') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $color = sanitizeInput($_POST['color'] ?? '#6366f1');
        $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-tag');
        
        if ($category_id > 0 && !empty($name) && !empty($slug)) {
            $update_query = "UPDATE categories SET name = '$name', slug = '$slug', description = '$description', color = '$color', icon = '$icon' WHERE id = $category_id";
            if (mysqli_query($conn, $update_query)) {
                $message = 'Kategori başarıyla güncellendi.';
                $message_type = 'success';
            } else {
                $message = 'Kategori güncellenirken bir hata oluştu.';
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'toggle_status') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            $toggle_query = "UPDATE categories SET is_active = NOT is_active WHERE id = $category_id";
            if (mysqli_query($conn, $toggle_query)) {
                $message = 'Kategori durumu güncellendi.';
                $message_type = 'success';
            } else {
                $message = 'Durum güncellenirken bir hata oluştu.';
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'delete_category') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            $delete_query = "DELETE FROM categories WHERE id = $category_id";
            if (mysqli_query($conn, $delete_query)) {
                $message = 'Kategori başarıyla silindi.';
                $message_type = 'success';
            } else {
                $message = 'Kategori silinirken bir hata oluştu.';
                $message_type = 'danger';
            }
        }
    }
}

// Kategorileri getir
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

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
                            <h1 class="h3 mb-0">Kategori Yönetimi</h1>
                            <p class="text-muted">İçerik kategorilerini yönetin</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Yeni Kategori
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Categories Grid -->
            <div class="row">
                <?php if (mysqli_num_rows($categories_result) > 0): ?>
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="category-icon rounded-3 d-flex align-items-center justify-content-center me-3" 
                                             style="width: 50px; height: 50px; background-color: <?php echo $category['color']; ?>20; color: <?php echo $category['color']; ?>;">
                                            <i class="<?php echo htmlspecialchars($category['icon']); ?> fa-lg"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($category['slug']); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge <?php echo $category['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $category['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="card-text text-muted mb-3">
                                            <?php echo htmlspecialchars($category['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d.m.Y', strtotime($category['created_at'])); ?>
                                        </small>
                                        
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-toggle-<?php echo $category['is_active'] ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirmAction('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-tags fa-4x text-muted mb-4"></i>
                                <h4 class="text-muted">Kategori Bulunamadı</h4>
                                <p class="text-muted">Henüz kategori eklenmemiş.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fas fa-plus me-2"></i>İlk Kategoriyi Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kategori Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                        <div class="form-text">URL dostu format (örn: aksiyon-macera)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="color" class="form-label">Renk</label>
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="#6366f1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="icon" class="form-label">İkon</label>
                                <select class="form-select" id="icon" name="icon">
                                    <option value="fas fa-tag">Tag</option>
                                    <option value="fas fa-fist-raised">Aksiyon</option>
                                    <option value="fas fa-heart">Romantik</option>
                                    <option value="fas fa-laugh">Komedi</option>
                                    <option value="fas fa-theater-masks">Drama</option>
                                    <option value="fas fa-magic">Fantastik</option>
                                    <option value="fas fa-rocket">Bilim Kurgu</option>
                                    <option value="fas fa-ghost">Korku</option>
                                    <option value="fas fa-running">Spor</option>
                                    <option value="fas fa-book">Kitap</option>
                                    <option value="fas fa-star">Yıldız</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kategori Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kategori Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Kategori Adı</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="edit_slug" name="slug" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_color" class="form-label">Renk</label>
                                <input type="color" class="form-control form-control-color" id="edit_color" name="color">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_icon" class="form-label">İkon</label>
                                <select class="form-select" id="edit_icon" name="icon">
                                    <option value="fas fa-tag">Tag</option>
                                    <option value="fas fa-fist-raised">Aksiyon</option>
                                    <option value="fas fa-heart">Romantik</option>
                                    <option value="fas fa-laugh">Komedi</option>
                                    <option value="fas fa-theater-masks">Drama</option>
                                    <option value="fas fa-magic">Fantastik</option>
                                    <option value="fas fa-rocket">Bilim Kurgu</option>
                                    <option value="fas fa-ghost">Korku</option>
                                    <option value="fas fa-running">Spor</option>
                                    <option value="fas fa-book">Kitap</option>
                                    <option value="fas fa-star">Yıldız</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Slug otomatik oluşturma
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/ğ/g, 'g')
        .replace(/ü/g, 'u')
        .replace(/ş/g, 's')
        .replace(/ı/g, 'i')
        .replace(/ö/g, 'o')
        .replace(/ç/g, 'c')
        .replace(/[^a-z0-9]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('slug').value = slug;
});

function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_slug').value = category.slug;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_color').value = category.color;
    document.getElementById('edit_icon').value = category.icon;
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?> 