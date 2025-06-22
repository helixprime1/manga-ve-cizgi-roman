<?php
require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

// Moderatör kontrolü
if (!isLoggedIn() || !isAdminOrModerator()) {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

// Filtreler
$action_filter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'all';
$moderator_filter = isset($_GET['moderator']) ? (int)$_GET['moderator'] : 0;
$target_type_filter = isset($_GET['target_type']) ? sanitizeInput($_GET['target_type']) : 'all';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : 'all';

// WHERE koşulu oluştur
$where_clauses = [];

if ($action_filter !== 'all') {
    $where_clauses[] = "ml.action = '$action_filter'";
}

if ($moderator_filter > 0) {
    $where_clauses[] = "ml.moderator_id = $moderator_filter";
}

if ($target_type_filter !== 'all') {
    $where_clauses[] = "ml.target_type = '$target_type_filter'";
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_clauses[] = "DATE(ml.created_at) = CURDATE()";
            break;
        case 'week':
            $where_clauses[] = "ml.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_clauses[] = "ml.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = !empty($where_clauses) ? implode(' AND ', $where_clauses) : '1=1';

// Logları getir
$query = "SELECT ml.*, u.username as moderator_name
          FROM moderation_logs ml 
          JOIN users u ON ml.moderator_id = u.id 
          WHERE $where_clause 
          ORDER BY ml.created_at DESC";
$result = mysqli_query($conn, $query);

// CSV başlıkları
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="moderasyon_loglari_' . date('Y-m-d_H-i-s') . '.csv"');

// UTF-8 BOM ekle (Excel için)
echo "\xEF\xBB\xBF";

// CSV başlıkları
$headers = ['ID', 'Tarih', 'Saat', 'Moderatör', 'Eylem', 'Hedef Türü', 'Hedef ID', 'Açıklama'];
echo implode(',', $headers) . "\n";

// Verileri yaz
while ($log = mysqli_fetch_assoc($result)) {
    $row = [
        $log['id'],
        date('d.m.Y', strtotime($log['created_at'])),
        date('H:i:s', strtotime($log['created_at'])),
        '"' . str_replace('"', '""', $log['moderator_name']) . '"',
        '"' . str_replace('"', '""', $log['action']) . '"',
        '"' . str_replace('"', '""', $log['target_type']) . '"',
        $log['target_id'],
        '"' . str_replace('"', '""', $log['description']) . '"'
    ];
    echo implode(',', $row) . "\n";
}

exit;
?> 