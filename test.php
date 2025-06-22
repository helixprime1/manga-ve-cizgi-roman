<?php
echo "PHP çalışıyor!<br>";
echo "PHP Versiyonu: " . phpversion() . "<br>";
echo "Tarih: " . date('Y-m-d H:i:s') . "<br>";

// Database bağlantısını test et
try {
    require_once 'includes/config.php';
    echo "Veritabanı bağlantısı başarılı!<br>";
    
    // Basit bir sorgu test et
    $result = mysqli_query($conn, "SELECT 1 as test");
    if ($result) {
        echo "Veritabanı sorgusu başarılı!<br>";
    } else {
        echo "Veritabanı sorgusu başarısız: " . mysqli_error($conn) . "<br>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "<br>";
}
?> 