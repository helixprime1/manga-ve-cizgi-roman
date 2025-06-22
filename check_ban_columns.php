<?php
require_once 'includes/config.php';

// Users tablosundaki kolonları kontrol et
$query = "DESCRIBE users";
$result = mysqli_query($conn, $query);

echo "<h3>Users Tablosu Kolonları:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$existing_columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
    $existing_columns[] = $row['Field'];
}
echo "</table>";

// Ban kolonları var mı kontrol et
$ban_columns = ['is_banned', 'banned_reason', 'banned_by', 'banned_at'];

echo "<h3>Ban Kolonları Durumu:</h3>";
foreach ($ban_columns as $column) {
    if (in_array($column, $existing_columns)) {
        echo "<p style='color: green;'>✓ $column kolonu mevcut</p>";
    } else {
        echo "<p style='color: red;'>✗ $column kolonu eksik</p>";
        
        // Eksik kolonları ekle
        $alter_query = "";
        switch ($column) {
            case 'is_banned':
                $alter_query = "ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0";
                break;
            case 'banned_reason':
                $alter_query = "ALTER TABLE users ADD COLUMN banned_reason TEXT NULL";
                break;
            case 'banned_by':
                $alter_query = "ALTER TABLE users ADD COLUMN banned_by INT NULL";
                break;
            case 'banned_at':
                $alter_query = "ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL";
                break;
        }
        
        if ($alter_query && mysqli_query($conn, $alter_query)) {
            echo "<p style='color: blue;'>→ $column kolonu eklendi</p>";
        } else {
            echo "<p style='color: red;'>→ $column kolonu eklenemedi: " . mysqli_error($conn) . "</p>";
        }
    }
}

echo "<h3>İşlem Tamamlandı!</h3>";
echo "<p><a href='admin/user-details.php?id=1'>Kullanıcı Detayları Test</a></p>";
?> 