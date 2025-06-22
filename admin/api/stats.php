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

try {
    // İstatistikleri al
    $stats = [];
    
    // Toplam kullanıcı sayısı
    $user_query = "SELECT COUNT(*) as total FROM users";
    $user_result = mysqli_query($conn, $user_query);
    $stats['total_users'] = $user_result ? mysqli_fetch_assoc($user_result)['total'] : 0;
    
    // Toplam içerik sayısı
    $content_query = "SELECT COUNT(*) as total FROM content";
    $content_result = mysqli_query($conn, $content_query);
    $stats['total_content'] = $content_result ? mysqli_fetch_assoc($content_result)['total'] : 0;
    
    // Bekleyen içerikler
    $pending_query = "SELECT COUNT(*) as total FROM content WHERE status = 'pending'";
    $pending_result = mysqli_query($conn, $pending_query);
    $stats['pending_content'] = $pending_result ? mysqli_fetch_assoc($pending_result)['total'] : 0;
    
    // Yayınlanan içerikler
    $published_query = "SELECT COUNT(*) as total FROM content WHERE status = 'published'";
    $published_result = mysqli_query($conn, $published_query);
    $stats['published_content'] = $published_result ? mysqli_fetch_assoc($published_result)['total'] : 0;
    
    // Toplam görüntülenme
    $views_query = "SELECT SUM(views) as total FROM content";
    $views_result = mysqli_query($conn, $views_query);
    $views_row = $views_result ? mysqli_fetch_assoc($views_result) : null;
    $stats['total_views'] = $views_row && $views_row['total'] ? $views_row['total'] : 0;
    
    // Bu ayki yeni kullanıcılar
    $month_users_query = "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $month_users_result = mysqli_query($conn, $month_users_query);
    $stats['month_users'] = $month_users_result ? mysqli_fetch_assoc($month_users_result)['total'] : 0;
    
    // Bu ayki yeni içerikler
    $month_content_query = "SELECT COUNT(*) as total FROM content WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $month_content_result = mysqli_query($conn, $month_content_query);
    $stats['month_content'] = $month_content_result ? mysqli_fetch_assoc($month_content_result)['total'] : 0;
    
    // Manga vs Çizgi Roman oranı
    $manga_query = "SELECT COUNT(*) as total FROM content WHERE type = 'manga' AND status = 'published'";
    $manga_result = mysqli_query($conn, $manga_query);
    $stats['manga_count'] = $manga_result ? mysqli_fetch_assoc($manga_result)['total'] : 0;
    
    $comic_query = "SELECT COUNT(*) as total FROM content WHERE type = 'comic' AND status = 'published'";
    $comic_result = mysqli_query($conn, $comic_query);
    $stats['comic_count'] = $comic_result ? mysqli_fetch_assoc($comic_result)['total'] : 0;
    
    // En aktif kullanıcılar (içerik sayısına göre)
    $active_users_query = "
        SELECT u.username, COUNT(c.id) as content_count 
        FROM users u 
        LEFT JOIN content c ON u.id = c.user_id 
        GROUP BY u.id 
        ORDER BY content_count DESC 
        LIMIT 5
    ";
    $active_users_result = mysqli_query($conn, $active_users_query);
    $stats['active_users'] = [];
    while ($user = mysqli_fetch_assoc($active_users_result)) {
        $stats['active_users'][] = $user;
    }
    
    // Son 7 günün istatistikleri
    $daily_stats_query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM content 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $daily_stats_result = mysqli_query($conn, $daily_stats_query);
    $stats['daily_content'] = [];
    while ($day = mysqli_fetch_assoc($daily_stats_result)) {
        $stats['daily_content'][] = $day;
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log('Stats API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İstatistikler yüklenirken bir hata oluştu'
    ]);
}
?> 