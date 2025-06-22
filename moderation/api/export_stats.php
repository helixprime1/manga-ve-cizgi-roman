<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

$moderator_user = getCurrentUser();

// İstatistikleri al
$general_stats_query = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'moderator') as total_moderators,
    (SELECT COUNT(*) FROM users WHERE is_banned = 1) as banned_users,
    (SELECT COUNT(*) FROM content) as total_content,
    (SELECT COUNT(*) FROM content WHERE status = 'published') as published_content,
    (SELECT COUNT(*) FROM content WHERE status = 'pending') as pending_content,
    (SELECT COUNT(*) FROM content WHERE status = 'rejected') as rejected_content,
    (SELECT COUNT(*) FROM comments) as total_comments,
    (SELECT COUNT(*) FROM comments WHERE is_reported = 1) as reported_comments,
    (SELECT COUNT(*) FROM comments WHERE is_hidden = 1) as hidden_comments,
    (SELECT COUNT(*) FROM reports) as total_reports,
    (SELECT COUNT(*) FROM reports WHERE status = 'resolved') as resolved_reports,
    (SELECT COUNT(*) FROM moderation_logs) as total_moderation_actions";
$general_stats_result = mysqli_query($conn, $general_stats_query);
$general_stats = mysqli_fetch_assoc($general_stats_result);

// En aktif moderatörler
$top_moderators_query = "SELECT u.username, COUNT(*) as actions
    FROM moderation_logs ml
    JOIN users u ON ml.moderator_id = u.id
    WHERE ml.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY ml.moderator_id, u.username
    ORDER BY actions DESC
    LIMIT 10";
$top_moderators_result = mysqli_query($conn, $top_moderators_query);

// Eylem türleri dağılımı
$action_distribution_query = "SELECT action, COUNT(*) as count
    FROM moderation_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action
    ORDER BY count DESC";
$action_distribution_result = mysqli_query($conn, $action_distribution_query);

// HTML raporu oluştur
ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Moderasyon İstatistikleri Raporu</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 5px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 14px; }
        .section { margin-bottom: 30px; }
        .section h3 { border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Moderasyon İstatistikleri Raporu</h1>
        <p>Rapor Tarihi: <?php echo date('d.m.Y H:i'); ?></p>
        <p>Raporu Hazırlayan: <?php echo htmlspecialchars($moderator_user['username']); ?></p>
    </div>

    <div class="section">
        <h3>Genel İstatistikler</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['total_users']); ?></div>
                <div class="stat-label">Toplam Kullanıcı</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['total_content']); ?></div>
                <div class="stat-label">Toplam İçerik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['total_comments']); ?></div>
                <div class="stat-label">Toplam Yorum</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['published_content']); ?></div>
                <div class="stat-label">Yayınlanan İçerik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['pending_content']); ?></div>
                <div class="stat-label">Bekleyen İçerik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['rejected_content']); ?></div>
                <div class="stat-label">Reddedilen İçerik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['banned_users']); ?></div>
                <div class="stat-label">Banlı Kullanıcı</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['reported_comments']); ?></div>
                <div class="stat-label">Bildirilen Yorum</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($general_stats['total_moderation_actions']); ?></div>
                <div class="stat-label">Toplam Moderasyon İşlemi</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>En Aktif Moderatörler (Son 30 Gün)</h3>
        <table>
            <thead>
                <tr>
                    <th>Moderatör</th>
                    <th>İşlem Sayısı</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($moderator = mysqli_fetch_assoc($top_moderators_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($moderator['username']); ?></td>
                        <td><?php echo number_format($moderator['actions']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Eylem Türleri Dağılımı (Son 30 Gün)</h3>
        <table>
            <thead>
                <tr>
                    <th>Eylem</th>
                    <th>Sayı</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($action = mysqli_fetch_assoc($action_distribution_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($action['action']); ?></td>
                        <td><?php echo number_format($action['count']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Bu rapor otomatik olarak oluşturulmuştur.</p>
        <p>Moderasyon Paneli - <?php echo date('Y'); ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// HTML'i dosya olarak indir
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="moderasyon_istatistikleri_' . date('Y-m-d_H-i-s') . '.html"');

echo $html;
exit;
?> 