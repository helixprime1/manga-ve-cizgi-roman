<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';
session_start();
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdminOrModerator()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
    exit;
}

$application_id = (int)($_GET['id'] ?? 0);

if ($application_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz başvuru ID']);
    exit;
}

try {
    // Başvuru detaylarını al
    $stmt = mysqli_prepare($conn, "
        SELECT a.*, u.username, u.email as user_email, u.created_at as user_joined,
               (SELECT username FROM users WHERE id = a.reviewed_by) as reviewer_name
        FROM author_applications a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($application = mysqli_fetch_assoc($result)) {
        $status_badges = [
            'pending' => '<span class="badge bg-warning">Beklemede</span>',
            'approved' => '<span class="badge bg-success">Onaylandı</span>',
            'rejected' => '<span class="badge bg-danger">Reddedildi</span>'
        ];
        
        $html = '
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Kişisel Bilgiler</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Ad Soyad:</strong></td>
                        <td>' . htmlspecialchars($application['full_name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Kullanıcı Adı:</strong></td>
                        <td>@' . htmlspecialchars($application['username']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>E-posta:</strong></td>
                        <td>' . htmlspecialchars($application['email']) . '</td>
                    </tr>';
        
        if (!empty($application['phone'])) {
            $html .= '
                    <tr>
                        <td><strong>Telefon:</strong></td>
                        <td>' . htmlspecialchars($application['phone']) . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr>
                        <td><strong>Üyelik Tarihi:</strong></td>
                        <td>' . date('d.m.Y', strtotime($application['user_joined'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Başvuru Tarihi:</strong></td>
                        <td>' . date('d.m.Y H:i', strtotime($application['applied_at'])) . '</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Başvuru Durumu</h6>
                <div class="mb-3">
                    ' . $status_badges[$application['status']] . '
                </div>';
        
        if ($application['status'] !== 'pending' && !empty($application['reviewer_name'])) {
            $html .= '
                <p><strong>İnceleme:</strong><br>
                ' . htmlspecialchars($application['reviewer_name']) . ' tarafından<br>
                ' . date('d.m.Y H:i', strtotime($application['reviewed_at'])) . '</p>';
        }
        
        if (!empty($application['admin_notes'])) {
            $html .= '
                <p><strong>Yönetici Notları:</strong><br>
                <em>' . nl2br(htmlspecialchars($application['admin_notes'])) . '</em></p>';
        }
        
        // Moderatör için uyarı
        if (!isAdmin()) {
            $html .= '
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Not:</strong> Moderatör olarak sadece başvuruları görüntüleyebilirsiniz. 
                    Onay/Red işlemleri admin yetkisi gerektirir.
                </div>';
        }
        
        $html .= '
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary">Kişisel Tanıtım</h6>
                <p>' . nl2br(htmlspecialchars($application['bio'])) . '</p>
            </div>
        </div>';
        
        if (!empty($application['experience'])) {
            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary">Deneyimler</h6>
                    <p>' . nl2br(htmlspecialchars($application['experience'])) . '</p>
                </div>
            </div>';
        }
        
        $html .= '
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary">Neden Yazar Olmak İstiyor?</h6>
                <p>' . nl2br(htmlspecialchars($application['why_author'])) . '</p>
            </div>
        </div>';
        
        if (!empty($application['preferred_genres'])) {
            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary">Tercih Edilen Türler</h6>
                    <p>' . htmlspecialchars($application['preferred_genres']) . '</p>
                </div>
            </div>';
        }
        
        if (!empty($application['portfolio_links'])) {
            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary">Portföy Linkleri</h6>
                    <div>';
            
            $links = explode("\n", $application['portfolio_links']);
            foreach ($links as $link) {
                $link = trim($link);
                if (!empty($link)) {
                    $html .= '<a href="' . htmlspecialchars($link) . '" target="_blank" class="btn btn-outline-primary btn-sm me-2 mb-2">
                        <i class="fas fa-external-link-alt me-1"></i>Link
                    </a>';
                }
            }
            
            $html .= '
                    </div>
                </div>
            </div>';
        }
        
        if (!empty($application['sample_work_description'])) {
            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary">Örnek Çalışma Açıklaması</h6>
                    <p>' . nl2br(htmlspecialchars($application['sample_work_description'])) . '</p>
                </div>
            </div>';
        }
        
        if (!empty($application['social_media_links'])) {
            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary">Sosyal Medya Hesapları</h6>
                    <div>';
            
            $social_links = explode("\n", $application['social_media_links']);
            foreach ($social_links as $link) {
                $link = trim($link);
                if (!empty($link)) {
                    $html .= '<a href="' . htmlspecialchars($link) . '" target="_blank" class="btn btn-outline-info btn-sm me-2 mb-2">
                        <i class="fas fa-share-alt me-1"></i>Sosyal Medya
                    </a>';
                }
            }
            
            $html .= '
                    </div>
                </div>
            </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Başvuru bulunamadı']);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
?> 