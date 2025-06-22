<?php
// Bakım modu kontrolü
function checkMaintenanceMode() {
    global $conn;
    
    // Admin ise bakım modunu atla
    if (isLoggedIn() && isAdmin()) {
        return false;
    }
    
    $maintenance_mode = getSetting('maintenance_mode', '0');
    
    if ($maintenance_mode == '1') {
        $maintenance_message = getSetting('maintenance_message', 'Site bakımda. Lütfen daha sonra tekrar deneyin.');
        $site_name = getSetting('site_name', 'Manga & Comic Hub');
        
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bakım Modu - <?php echo htmlspecialchars($site_name); ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Inter', sans-serif;
                }
                .maintenance-card {
                    background: white;
                    border-radius: 20px;
                    padding: 3rem;
                    text-align: center;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    max-width: 500px;
                }
                .maintenance-icon {
                    font-size: 4rem;
                    color: #6c757d;
                    margin-bottom: 1.5rem;
                }
                .maintenance-title {
                    font-size: 2rem;
                    font-weight: 700;
                    color: #343a40;
                    margin-bottom: 1rem;
                }
                .maintenance-message {
                    color: #6c757d;
                    font-size: 1.1rem;
                    margin-bottom: 2rem;
                    line-height: 1.6;
                }
                .btn-home {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    padding: 0.75rem 2rem;
                    border-radius: 50px;
                    color: white;
                    font-weight: 600;
                    text-decoration: none;
                    display: inline-block;
                    transition: transform 0.3s ease;
                }
                .btn-home:hover {
                    transform: translateY(-2px);
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-card">
                <div class="maintenance-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h1 class="maintenance-title">Bakım Modu</h1>
                <p class="maintenance-message">
                    <?php echo htmlspecialchars($maintenance_message); ?>
                </p>
                <a href="<?php echo SITE_URL; ?>" class="btn-home me-2">
                    <i class="fas fa-home me-2"></i>Ana Sayfa
                </a>
                <a href="<?php echo SITE_URL; ?>/admin" class="btn-home" style="background: #dc3545;">
                    <i class="fas fa-user-shield me-2"></i>Admin Girişi
                </a>
                <div class="mt-4">
                    <small class="text-muted">
                        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>
                    </small>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?> 