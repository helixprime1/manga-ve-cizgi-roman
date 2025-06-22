<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - HTML Karakterleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-check-circle"></i> HTML Karakter Testi</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5>✅ Başarılı Test Sonuçları:</h5>
                            <ul class="mb-0">
                                <li><strong>HTML Karakterleri:</strong> &lt; &gt; &amp; &quot; &#39;</li>
                                <li><strong>Türkçe Karakterler:</strong> ç ğ ı İ ö ş ü Ç Ğ Ö Ş Ü</li>
                                <li><strong>Özel Karakterler:</strong> € ® © ™ ± × ÷</li>
                                <li><strong>JavaScript Test:</strong> <span id="jsTest">❌ Yükleniyor...</span></li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Eğer bu karakterler düzgün görünüyorsa:</h6>
                            <p class="mb-2">✅ Karakter kodlaması sorunu çözülmüştür</p>
                            <p class="mb-0">✅ HTML karakterleri düzgün çalışmaktadır</p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="content.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> İçeriklerim Sayfasına Git
                            </a>
                            <a href="content-fixed.php" class="btn btn-success btn-lg">
                                <i class="fas fa-rocket"></i> Alternatif Sayfa (content-fixed.php)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('jsTest').innerHTML = '✅ JavaScript Çalışıyor!';
            console.log('Test başarılı: < > & " \'');
        });
    </script>
</body>
</html> 