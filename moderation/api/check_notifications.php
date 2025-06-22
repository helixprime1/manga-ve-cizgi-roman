<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

try {
    // Bekleyen işlem sayısını hesapla
    $pending_content_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
    $pending_content_result = mysqli_query($conn, $pending_content_query);
    $pending_content = $pending_content_result ? mysqli_fetch_assoc($pending_content_result)['total'] : 0;

    $reported_comments_query = "SELECT COUNT(*) as total FROM comments WHERE is_reported = 1";
    $reported_comments_result = mysqli_query($conn, $reported_comments_query);
    $reported_comments = $reported_comments_result ? mysqli_fetch_assoc($reported_comments_result)['total'] : 0;

    $pending_reports_query = "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'";
    $pending_reports_result = mysqli_query($conn, $pending_reports_query);
    $pending_reports = $pending_reports_result ? mysqli_fetch_assoc($pending_reports_result)['total'] : 0;

    $total_pending = $pending_content + $reported_comments + $pending_reports;
    
    // Son kontrol zamanını session'da sakla
    $last_check = isset($_SESSION['last_notification_check']) ? $_SESSION['last_notification_check'] : 0;
    $current_time = time();
    
    // Yeni bildirim var mı kontrol et (son 30 saniyede)
    $hasNew = false;
    if ($current_time - $last_check > 30) {
        // Son 30 saniyede yeni işlem var mı kontrol et
        $new_activity_query = "SELECT COUNT(*) as total FROM (
            SELECT created_at FROM content WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
            UNION ALL
            SELECT created_at FROM comments WHERE is_reported = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
            UNION ALL
            SELECT created_at FROM reports WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
        ) as new_items";
        
        $new_activity_result = mysqli_query($conn, $new_activity_query);
        $new_activity = $new_activity_result ? mysqli_fetch_assoc($new_activity_result)['total'] : 0;
        
        $hasNew = $new_activity > 0;
        $_SESSION['last_notification_check'] = $current_time;
    }

    echo json_encode([
        'success' => true,
        'count' => $total_pending,
        'hasNew' => $hasNew,
        'breakdown' => [
            'pending_content' => $pending_content,
            'reported_comments' => $reported_comments,
            'pending_reports' => $pending_reports
        ],
        'timestamp' => $current_time
    ]);

} catch (Exception $e) {
    error_log("Notification check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bildirim kontrolü sırasında hata oluştu'
    ]);
}
?> 