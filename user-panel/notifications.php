<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Bildirimler';

require_once 'includes/auth-check.php';

$user_id = $_SESSION['user_id'];

// Bildirimler tablosunun varlığını kontrol et
$table_check_stmt = mysqli_prepare($conn, "SHOW TABLES LIKE 'notifications'");
mysqli_stmt_execute($table_check_stmt);
$table_check = mysqli_stmt_get_result($table_check_stmt);

if (mysqli_num_rows($table_check) == 0) {
    echo "<div class='alert alert-warning'>Bildirimler tablosu bulunamadı. Lütfen admin ile iletişime geçin.</div>";
    exit;
}

// Okunmamış bildirim sayısını al
$unread_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
mysqli_stmt_execute($unread_stmt);
$unread_result = mysqli_stmt_get_result($unread_stmt);
$unread_count = mysqli_fetch_assoc($unread_result)['count'];

// Bildirimleri al
$notifications_stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);

// Bildirim işlemleri
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    
    // CSRF koruması için referer kontrolü
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
        $update_stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
    
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['mark_all_read'])) {
    // CSRF koruması için referer kontrolü
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
        $update_all_stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        mysqli_stmt_bind_param($update_all_stmt, "i", $user_id);
        mysqli_stmt_execute($update_all_stmt);
        mysqli_stmt_close($update_all_stmt);
    }
    
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = (int)$_GET['delete'];
    
    // CSRF koruması için referer kontrolü ve yetki kontrolü
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
        $delete_stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    }
    
    header('Location: notifications.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="notifications-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🔔 Bildirimler</h2>
            <p class="text-muted mb-0">
                <?php if ($unread_count > 0): ?>
                    <?php echo $unread_count; ?> okunmamış bildiriminiz var
                <?php else: ?>
                    Tüm bildirimler okundu
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($unread_count > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-outline-primary" 
               onclick="return confirm('Tüm bildirimleri okundu olarak işaretlemek istediğinizden emin misiniz?')">
                ✅ Tümünü Okundu İşaretle
            </a>
        <?php endif; ?>
    </div>

    <div class="notifications-list">
        <?php if (mysqli_num_rows($notifications_result) > 0): ?>
            <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-content">
                        <div class="notification-header">
                            <h6><?php echo htmlspecialchars($notification['title']); ?></h6>
                            <span class="notification-time">
                                <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                    </div>
                    
                    <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                            <a href="?mark_read=<?php echo $notification['id']; ?>" 
                               class="btn btn-sm btn-outline-success" title="Okundu işaretle">
                                ✅
                            </a>
                        <?php endif; ?>
                        
                        <a href="?delete=<?php echo $notification['id']; ?>" 
                           class="btn btn-sm btn-outline-danger" title="Sil"
                           onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?')">
                            🗑️
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state text-center py-5">
                <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.6;">🔕</div>
                <h4>Henüz bildirim yok</h4>
                <p class="text-muted">Yeni bildirimler burada görünecek.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.3s ease;
}

.notification-item.unread {
    border-left: 4px solid #007bff;
    background: #f8f9ff;
}

.notification-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.notification-header h6 {
    margin: 0;
    color: #333;
}

.notification-time {
    font-size: 0.8rem;
    color: #666;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: 1rem;
}

.empty-state {
    background: white;
    border-radius: 8px;
    padding: 3rem;
}
</style>

<?php require_once 'includes/footer.php'; ?> 