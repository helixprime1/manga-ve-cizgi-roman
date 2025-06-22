<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// JSON response için header
header('Content-Type: application/json');

// Arama sorgusu
$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

$results = [];

if (!empty($query) && strlen($query) >= 2) {
    // Arama yap
    $sql = "SELECT id, title, type, cover_image, views 
            FROM content 
            WHERE status = 'published' 
            AND (title LIKE '%$query%' OR description LIKE '%$query%' OR tags LIKE '%$query%') 
            ORDER BY 
                CASE 
                    WHEN title LIKE '$query%' THEN 1
                    WHEN title LIKE '%$query%' THEN 2
                    ELSE 3
                END,
                views DESC 
            LIMIT 10";
    
    $result = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'type' => $row['type'],
            'cover_image' => $row['cover_image'],
            'views' => $row['views']
        ];
    }
}

echo json_encode($results);
?> 