<?php
require_once 'includes/config.php';
session_start();
require_once 'includes/functions.php';
require_once 'includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

// İçerik ID'si kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$content_id = (int)$_GET['id'];
$content = getContentById($content_id);

// İçerik bulunamadıysa veya yayınlanmamışsa (admin değilse)
if (!$content || ($content['status'] !== 'published' && !isAdmin())) {
    header('Location: index.php');
    exit;
}

// İçeriği görüntüleme sayısını artır
$stmt = mysqli_prepare($conn, "UPDATE content SET views = views + 1 WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $content_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// İçerik sahibi bilgilerini al
$author = getUserById($content['user_id']);

// Bölüm bilgilerini al
$current_chapter = 1;
$chapter_id = null;
$chapter_title = '';
$chapter_description = '';

// URL'den bölüm numarası al
if (isset($_GET['chapter']) && is_numeric($_GET['chapter'])) {
    $current_chapter = (int)$_GET['chapter'];
}

// Seri ise bölümleri al
$chapters = [];
if ($content['is_series']) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM chapters WHERE content_id = ? ORDER BY chapter_number ASC");
    mysqli_stmt_bind_param($stmt, "i", $content_id);
    mysqli_stmt_execute($stmt);
    $chapters_result = mysqli_stmt_get_result($stmt);
    
    while ($chapter = mysqli_fetch_assoc($chapters_result)) {
        $chapters[] = $chapter;
    }
    
    // Mevcut bölümü bul
    foreach ($chapters as $chapter) {
        if ($chapter['chapter_number'] == $current_chapter) {
            $chapter_id = $chapter['id'];
            $chapter_title = $chapter['title'];
            $chapter_description = $chapter['description'];
            
            // Bölüm görüntüleme sayısını artır
            $chapter_stmt = mysqli_prepare($conn, "UPDATE chapters SET views = views + 1 WHERE id = ?");
            mysqli_stmt_bind_param($chapter_stmt, "i", $chapter['id']);
            mysqli_stmt_execute($chapter_stmt);
            mysqli_stmt_close($chapter_stmt);
            break;
        }
    }
    mysqli_stmt_close($stmt);
    
    // Bölüm bulunamadıysa ilk bölüme yönlendir
    if (!$chapter_id && !empty($chapters)) {
        header("Location: view.php?id=$content_id&chapter=1");
        exit;
    }
}

// Benzer içerikleri al - Gelişmiş algoritma
$similar_contents = [];

// 1. Aynı etiketlere sahip içerikler (en yüksek öncelik)
if (!empty($content['tags'])) {
    $tags = explode(',', $content['tags']);
    $tag_conditions = [];
    foreach ($tags as $tag) {
        $tag = trim(mysqli_real_escape_string($conn, $tag));
        if (!empty($tag)) {
            $tag_conditions[] = "tags LIKE '%$tag%'";
        }
    }
    
    if (!empty($tag_conditions)) {
        $tag_query = "SELECT *, 'tag_match' as match_type FROM content 
                      WHERE status = 'published' 
                      AND id != $content_id 
                      AND (" . implode(' OR ', $tag_conditions) . ")
                      ORDER BY views DESC LIMIT 3";
        $tag_result = mysqli_query($conn, $tag_query);
        while ($row = mysqli_fetch_assoc($tag_result)) {
            $similar_contents[] = $row;
        }
    }
}

// 2. Aynı yazarın diğer eserleri
$author_query = "SELECT *, 'author_match' as match_type FROM content 
                 WHERE status = 'published' 
                 AND user_id = {$content['user_id']} 
                 AND id != $content_id 
                 ORDER BY views DESC LIMIT 2";
$author_result = mysqli_query($conn, $author_query);
while ($row = mysqli_fetch_assoc($author_result)) {
    // Aynı içeriği tekrar eklememek için kontrol et
    $already_exists = false;
    foreach ($similar_contents as $existing) {
        if ($existing['id'] == $row['id']) {
            $already_exists = true;
            break;
        }
    }
    if (!$already_exists) {
        $similar_contents[] = $row;
    }
}

// 3. Aynı türdeki popüler içerikler
$type_query = "SELECT *, 'type_match' as match_type FROM content 
               WHERE status = 'published' 
               AND type = '{$content['type']}' 
               AND id != $content_id 
               ORDER BY views DESC LIMIT 4";
$type_result = mysqli_query($conn, $type_query);
while ($row = mysqli_fetch_assoc($type_result)) {
    // Aynı içeriği tekrar eklememek için kontrol et
    $already_exists = false;
    foreach ($similar_contents as $existing) {
        if ($existing['id'] == $row['id']) {
            $already_exists = true;
            break;
        }
    }
    if (!$already_exists && count($similar_contents) < 6) {
        $similar_contents[] = $row;
    }
}

// 4. Eğer hâlâ yeterli içerik yoksa, en popüler içeriklerden ekle
if (count($similar_contents) < 6) {
    $popular_query = "SELECT *, 'popular_match' as match_type FROM content 
                      WHERE status = 'published' 
                      AND id != $content_id 
                      ORDER BY views DESC, created_at DESC LIMIT " . (6 - count($similar_contents));
    $popular_result = mysqli_query($conn, $popular_query);
    while ($row = mysqli_fetch_assoc($popular_result)) {
        // Aynı içeriği tekrar eklememek için kontrol et
        $already_exists = false;
        foreach ($similar_contents as $existing) {
            if ($existing['id'] == $row['id']) {
                $already_exists = true;
                break;
            }
        }
        if (!$already_exists) {
            $similar_contents[] = $row;
        }
    }
}

// Sonuçları 6 ile sınırla
$similar_contents = array_slice($similar_contents, 0, 6);

// Yorumları al
$stmt = mysqli_prepare($conn, "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.content_id = ? ORDER BY c.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $content_id);
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Yorum ekleme işlemi
$comment_error = '';
$comment_success = false;

// GET parametresi ile başarı mesajını kontrol et
if (isset($_GET['comment_success']) && $_GET['comment_success'] == '1') {
    $comment_success = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['comment'])) {
    // CSRF token kontrolü
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $comment_error = "Güvenlik hatası. Lütfen sayfayı yenileyin.";
    } else {
        $comment_text = trim($_POST['comment']);
        
        if (empty($comment_text)) {
            $comment_error = "Yorum alanı boş olamaz.";
        } elseif (strlen($comment_text) > 1000) {
            $comment_error = "Yorum çok uzun (maksimum 1000 karakter).";
        } elseif (strlen($comment_text) < 3) {
            $comment_error = "Yorum çok kısa (minimum 3 karakter).";
        } else {
            $user_id = (int)$_SESSION['user_id'];
            $content_id_safe = (int)$content_id;
            $comment_text_safe = mysqli_real_escape_string($conn, $comment_text);
            $created_at = date('Y-m-d H:i:s');
            
            // Prepared statement kullan
            $stmt = mysqli_prepare($conn, "INSERT INTO comments (content_id, user_id, comment, created_at) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $content_id_safe, $user_id, $comment_text_safe, $created_at);
            
            if (mysqli_stmt_execute($stmt)) {
                // İçerik sahibine bildirim gönder (kendi yorumuna değilse)
                if ($content['user_id'] != $user_id) {
                    $user_stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
                    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $username = mysqli_fetch_assoc($user_result)['username'];
                    mysqli_stmt_close($user_stmt);
                    
                    $message = "$username '{$content['title']}' başlıklı içeriğinize yorum yaptı";
                    $link = "view.php?id=$content_id";
                    
                    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($notif_stmt, "isss", $content['user_id'], $message, $link, $created_at);
                    mysqli_stmt_execute($notif_stmt);
                    mysqli_stmt_close($notif_stmt);
                }
                
                // POST-Redirect-GET pattern - Çift gönderimi önlemek için redirect yap
                $redirect_url = "view.php?id=$content_id";
                if (isset($_GET['chapter'])) {
                    $redirect_url .= "&chapter=" . (int)$_GET['chapter'];
                }
                $redirect_url .= "&comment_success=1";
                
                header("Location: $redirect_url");
                exit;
            } else {
                $comment_error = "Yorum eklenirken bir hata oluştu. Lütfen tekrar deneyin.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Beğeni ve favori durumu
$is_liked = false;
$is_favorited = false;
$like_count = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Beğeni kontrolü
    $like_stmt = mysqli_prepare($conn, "SELECT id FROM likes WHERE content_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($like_stmt, "ii", $content_id, $user_id);
    mysqli_stmt_execute($like_stmt);
    $like_result = mysqli_stmt_get_result($like_stmt);
    $is_liked = mysqli_num_rows($like_result) > 0;
    mysqli_stmt_close($like_stmt);
    
    // Favori kontrolü
    $fav_stmt = mysqli_prepare($conn, "SELECT id FROM favorites WHERE content_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($fav_stmt, "ii", $content_id, $user_id);
    mysqli_stmt_execute($fav_stmt);
    $fav_result = mysqli_stmt_get_result($fav_stmt);
    $is_favorited = mysqli_num_rows($fav_result) > 0;
    mysqli_stmt_close($fav_stmt);
}

// Toplam beğeni sayısı
$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM likes WHERE content_id = ?");
mysqli_stmt_bind_param($count_stmt, "i", $content_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$like_count = mysqli_fetch_assoc($count_result)['count'];
mysqli_stmt_close($count_stmt);

$page_title = $content['title'];
require_once 'includes/header.php';
?>

<style>
.reader-container {
    background: #f8f9fa;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.reader-viewer {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.reader-controls {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.comment-item {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.author-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

#imageViewer {
    min-height: 400px;
    transition: transform 0.3s ease;
}

.reader-page {
    transition: opacity 0.3s ease;
    max-height: 80vh;
    object-fit: contain;
}

#imageViewer .btn {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

#imageViewer:hover .btn {
    opacity: 1;
}

.reader-controls .input-group-text {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

@media (max-width: 768px) {
    #imageViewer .position-absolute.btn {
        position: static !important;
        margin: 0.5rem;
        transform: none !important;
    }
    
    .reader-page {
        max-height: 60vh;
    }
}

.chapter-navigation .btn {
    min-height: 45px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 0.9rem;
}

.chapter-navigation .btn small {
    font-size: 0.75rem;
    opacity: 0.8;
}



.badge-manga {
    background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
    color: white;
}

.badge-comic {
    background: linear-gradient(45deg, #4ecdc4, #6bcf7f);
    color: white;
}

/* Side Panel Styles */
.chapter-sidepanel {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    z-index: 1050;
    transition: right 0.3s ease;
}

.chapter-sidepanel.show {
    right: 0;
}

.sidepanel-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: -1;
}

.chapter-sidepanel.show .sidepanel-overlay {
    opacity: 1;
    visibility: visible;
}

.sidepanel-content {
    background: white;
    height: 100%;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.sidepanel-header {
    padding: 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: between;
    align-items: center;
    background: #f8f9fa;
}

.sidepanel-title {
    margin: 0;
    font-weight: 600;
    color: #333;
    flex-grow: 1;
}

.sidepanel-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

.chapter-item {
    display: block;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.chapter-item:hover {
    background: #f8f9fa;
    color: inherit;
    text-decoration: none;
}

.chapter-item.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.chapter-item.active:hover {
    background: linear-gradient(135deg, #5855eb, #7c3aed);
    color: white;
}

.chapter-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.chapter-number {
    font-weight: 600;
    font-size: 0.9rem;
}

.chapter-views {
    font-size: 0.8rem;
    opacity: 0.8;
}

.chapter-item.active .chapter-views {
    opacity: 0.9;
}

.current-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
    margin-left: 0.5rem;
}

.chapter-title {
    font-weight: 500;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.chapter-description {
    font-size: 0.8rem;
    opacity: 0.7;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.chapter-item.active .chapter-description {
    opacity: 0.8;
}

.chapter-date {
    font-size: 0.75rem;
    opacity: 0.6;
}

.chapter-item.active .chapter-date {
    opacity: 0.7;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .chapter-sidepanel {
        width: 100vw;
        right: -100vw;
    }
    
    .sidepanel-header {
        padding: 1rem;
    }
    
    .chapter-item {
        padding: 0.8rem 1rem;
    }
}
</style>

<div class="container my-5">
    <div class="row">
        <!-- Sol Taraf - İçerik -->
        <div class="col-lg-8">
            <!-- İçerik Başlık ve Bilgiler -->
            <div class="mb-4" data-aos="fade-up">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h1 class="mb-0"><?php echo htmlspecialchars($content['title']); ?></h1>
                        <?php if ($content['is_series'] && !empty($chapter_title)): ?>
                            <h2 class="h4 text-muted mt-2">
                                <i class="fas fa-bookmark me-2"></i>
                                <?php echo htmlspecialchars($chapter_title); ?>
                            </h2>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <span class="badge <?php echo $content['type'] === 'manga' ? 'badge-manga' : 'badge-comic'; ?> fs-6">
                            <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
                        </span>
                        <?php if ($content['is_series']): ?>
                            <br>
                            <span class="badge bg-info fs-6 mt-1">
                                <i class="fas fa-book-open me-1"></i>
                                <?php echo count($chapters); ?> Bölüm
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex align-items-center text-muted mb-3">
                    <img src="<?php echo !empty($author['avatar']) ? SITE_URL . '/uploads/avatars/' . $author['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($author['username']) . '&background=6366f1&color=fff'; ?>" 
                         class="rounded-circle me-2" width="30" height="30" alt="<?php echo htmlspecialchars($author['username']); ?>">
                    <span class="me-3"><?php echo htmlspecialchars($author['username']); ?></span>
                    <span class="me-3"><i class="fas fa-calendar me-1"></i> <?php echo date('d.m.Y', strtotime($content['created_at'])); ?></span>
                    <span class="me-3"><i class="fas fa-eye me-1"></i> <?php echo number_format($content['views']); ?> görüntülenme</span>
                    <span><i class="fas fa-heart me-1"></i> <span id="likeCount"><?php echo number_format($like_count); ?></span> beğeni</span>
                </div>
                
                <?php if (!empty($content['tags'])): ?>
                    <div class="mb-3">
                        <?php foreach (explode(',', $content['tags']) as $tag): ?>
                            <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <p class="lead"><?php echo nl2br(htmlspecialchars($content['description'])); ?></p>
                
                <?php if ($content['is_series'] && !empty($chapter_description)): ?>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Bu Bölümde:</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($chapter_description)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Bölüm Navigasyonu -->
            <?php if ($content['is_series'] && !empty($chapters)): ?>
                <div class="chapter-navigation mb-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <?php 
                                    $prev_chapter = null;
                                    foreach ($chapters as $chapter) {
                                        if ($chapter['chapter_number'] == $current_chapter - 1) {
                                            $prev_chapter = $chapter;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($prev_chapter): ?>
                                        <a href="view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $prev_chapter['chapter_number']; ?>" 
                                           class="btn btn-outline-primary w-100">
                                            <i class="fas fa-chevron-left me-1"></i>
                                            <span style="font-size: 0.8rem;">Önceki</span>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary w-100" disabled>
                                            <i class="fas fa-ban me-1"></i>
                                            <span style="font-size: 0.8rem;">İlk Bölüm</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 text-center">
                                    <button class="btn btn-primary" type="button" 
                                            id="openSidepanel"
                                            style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                        <i class="fas fa-list me-2"></i>
                                        Bölüm <?php echo $current_chapter; ?>: <?php echo htmlspecialchars($chapter_title); ?>
                                    </button>
                                </div>
                                
                                <div class="col-md-3">
                                    <?php 
                                    $next_chapter = null;
                                    foreach ($chapters as $chapter) {
                                        if ($chapter['chapter_number'] == $current_chapter + 1) {
                                            $next_chapter = $chapter;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($next_chapter): ?>
                                        <a href="view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $next_chapter['chapter_number']; ?>" 
                                           class="btn btn-outline-primary w-100">
                                            <span style="font-size: 0.8rem;">Sonraki</span>
                                            <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    <?php else: ?>
                                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $content['user_id']): ?>
                                            <a href="add-chapter.php?content_id=<?php echo $content_id; ?>" 
                                               class="btn btn-success w-100">
                                                <i class="fas fa-plus me-1"></i>
                                                <span style="font-size: 0.8rem;">Yeni Bölüm</span>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary w-100" disabled>
                                                <i class="fas fa-flag-checkered me-1"></i>
                                                <span style="font-size: 0.8rem;">Son Bölüm</span>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Okuyucu -->
            <div class="reader-container" data-aos="fade-up" data-aos-delay="100">
                <div class="reader-viewer" id="readerViewer">
                    <?php
                    // İçerik dosyalarını kontrol et
                    if ($content['is_series'] && $chapter_id) {
                        // Seri ise mevcut bölümün dosyalarını al
                        $current_chapter_data = null;
                        foreach ($chapters as $chapter) {
                            if ($chapter['id'] == $chapter_id) {
                                $current_chapter_data = $chapter;
                                break;
                            }
                        }
                        
                        if ($current_chapter_data) {
                            $content_files = json_decode($current_chapter_data['chapter_files'], true);
                            $content_type = $current_chapter_data['file_type'];
                        } else {
                            $content_files = [];
                            $content_type = 'images';
                        }
                    } else {
                        // Tek eser ise ana dosyaları kullan
                        $content_files = json_decode($content['content_file'], true);
                        $content_type = $content['content_type'] ?? 'pdf';
                    }
                    
                    if (!is_array($content_files)) {
                        $content_files = [$content['content_file']]; // Eski format için uyumluluk
                    }
                    
                    if ($content_type === 'images' && !empty($content_files)): ?>
                        <!-- Çoklu Resim Görüntüleyici -->
                        <div id="imageViewer" class="position-relative">
                            <?php foreach ($content_files as $index => $file): ?>
                                <?php 
                                $file_path = 'uploads/content/' . $file;
                                $display_style = $index === 0 ? 'block' : 'none';
                                ?>
                                <?php if (file_exists($file_path)): ?>
                                    <img src="<?php echo htmlspecialchars($file_path); ?>" 
                                         class="img-fluid w-100 reader-page" 
                                         data-page="<?php echo $index + 1; ?>"
                                         style="display: <?php echo $display_style; ?>;"
                                         alt="Sayfa <?php echo $index + 1; ?>"
                                         onerror="this.style.display='none';">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <!-- Sayfa Navigasyon Okları -->
                            <button class="btn btn-dark btn-sm position-absolute top-50 start-0 translate-middle-y ms-3" 
                                    id="prevPageArrow" style="display: none;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-dark btn-sm position-absolute top-50 end-0 translate-middle-y me-3" 
                                    id="nextPageArrow">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <!-- Sayfa Numarası -->
                            <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                                <span class="badge bg-dark fs-6 px-3 py-2">
                                    <span id="currentPageDisplay">1</span> / <span id="totalPagesDisplay"><?php echo count($content_files); ?></span>
                                </span>
                            </div>
                        </div>
                        
                        <script>
                        // Çoklu resim görüntüleyici
                        let currentPage = 1;
                        const totalPages = <?php echo count($content_files); ?>;
                        
                        function showPage(pageNum) {
                            // Tüm sayfaları gizle
                            document.querySelectorAll('.reader-page').forEach(page => {
                                page.style.display = 'none';
                            });
                            
                            // İstenen sayfayı göster
                            const targetPage = document.querySelector(`[data-page="${pageNum}"]`);
                            if (targetPage) {
                                targetPage.style.display = 'block';
                                currentPage = pageNum;
                                
                                // Sayfa numarasını güncelle
                                document.getElementById('currentPageDisplay').textContent = pageNum;
                                document.getElementById('currentPage').textContent = pageNum;
                                
                                // Okları güncelle
                                document.getElementById('prevPageArrow').style.display = pageNum > 1 ? 'block' : 'none';
                                document.getElementById('nextPageArrow').style.display = pageNum < totalPages ? 'block' : 'none';
                                
                                // Kontrol butonlarını güncelle
                                document.getElementById('prevPage').disabled = pageNum <= 1;
                                document.getElementById('nextPage').disabled = pageNum >= totalPages;
                            }
                        }
                        
                        // Ok tuşları
                        document.getElementById('prevPageArrow').addEventListener('click', () => {
                            if (currentPage > 1) showPage(currentPage - 1);
                        });
                        
                        document.getElementById('nextPageArrow').addEventListener('click', () => {
                            if (currentPage < totalPages) showPage(currentPage + 1);
                        });
                        
                        // Klavye kontrolleri
                        document.addEventListener('keydown', (e) => {
                            if (e.key === 'ArrowLeft' && currentPage > 1) {
                                showPage(currentPage - 1);
                            } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
                                showPage(currentPage + 1);
                            }
                        });
                        
                        // Sayfa güncellemelerini başlat
                        document.getElementById('totalPages').textContent = totalPages;
                        showPage(1);
                        </script>
                        
                    <?php elseif ($content_type === 'pdf' && !empty($content_files[0])): ?>
                        <!-- PDF Görüntüleyici -->
                        <?php 
                        $pdf_path = 'uploads/content/' . $content_files[0];
                        if (file_exists($pdf_path)): ?>
                            <div class="ratio ratio-16x9">
                                <iframe src="<?php echo htmlspecialchars($pdf_path); ?>" 
                                        frameborder="0" 
                                        allowfullscreen></iframe>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center p-5">
                                <i class="fas fa-file-pdf fa-3x mb-3"></i>
                                <h5>PDF Dosyası Bulunamadı</h5>
                                <p>Bu içeriğin PDF dosyası bulunamıyor.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger text-center p-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>İçerik Bulunamadı</h5>
                            <p>Bu içeriğin dosyaları bulunamıyor veya desteklenmeyen bir format.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($content_type === 'images'): ?>
                <div class="reader-controls">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" id="prevPage">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </button>
                        </div>
                        <div class="col text-center">
                            <span class="fw-bold">Sayfa <span id="currentPage">1</span> / <span id="totalPages"><?php echo count($content_files); ?></span></span>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" id="nextPage">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-secondary" id="zoomOut">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button class="btn btn-outline-secondary" id="zoomReset">
                                    <i class="fas fa-compress"></i> 100%
                                </button>
                                <button class="btn btn-outline-secondary" id="zoomIn">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button class="btn btn-outline-secondary" id="fullscreen">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sayfa Atlama -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">Sayfaya Git:</span>
                                <input type="number" class="form-control" id="pageJump" min="1" max="<?php echo count($content_files); ?>" value="1">
                                <button class="btn btn-primary" id="jumpToPage">Git</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-info" id="firstPage">
                                    <i class="fas fa-fast-backward"></i> İlk
                                </button>
                                <button class="btn btn-outline-info" id="lastPage">
                                    <i class="fas fa-fast-forward"></i> Son
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                // Kontrol butonları
                document.getElementById('prevPage').addEventListener('click', () => {
                    if (currentPage > 1) showPage(currentPage - 1);
                });
                
                document.getElementById('nextPage').addEventListener('click', () => {
                    if (currentPage < totalPages) showPage(currentPage + 1);
                });
                
                document.getElementById('firstPage').addEventListener('click', () => {
                    showPage(1);
                });
                
                document.getElementById('lastPage').addEventListener('click', () => {
                    showPage(totalPages);
                });
                
                document.getElementById('jumpToPage').addEventListener('click', () => {
                    const pageNum = parseInt(document.getElementById('pageJump').value);
                    if (pageNum >= 1 && pageNum <= totalPages) {
                        showPage(pageNum);
                    }
                });
                
                // Zoom fonksiyonları
                let zoomLevel = 1;
                document.getElementById('zoomIn').addEventListener('click', () => {
                    zoomLevel = Math.min(zoomLevel + 0.25, 3);
                    applyZoom();
                });
                
                document.getElementById('zoomOut').addEventListener('click', () => {
                    zoomLevel = Math.max(zoomLevel - 0.25, 0.5);
                    applyZoom();
                });
                
                document.getElementById('zoomReset').addEventListener('click', () => {
                    zoomLevel = 1;
                    applyZoom();
                });
                
                function applyZoom() {
                    const viewer = document.getElementById('imageViewer');
                    viewer.style.transform = `scale(${zoomLevel})`;
                    viewer.style.transformOrigin = 'center top';
                    document.getElementById('zoomReset').innerHTML = `<i class="fas fa-compress"></i> ${Math.round(zoomLevel * 100)}%`;
                }
                
                // Fullscreen
                document.getElementById('fullscreen').addEventListener('click', () => {
                    const viewer = document.getElementById('readerViewer');
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        viewer.requestFullscreen();
                    }
                });
                </script>
                <?php endif; ?>
            </div>
            
            <!-- Sosyal Butonlar -->
            <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="btn-group" role="group">
                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-outline-danger <?php echo $is_liked ? 'active' : ''; ?>" 
                                id="likeButton" 
                                data-content-id="<?php echo $content_id; ?>">
                            <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i> Beğen
                        </button>
                        <button class="btn btn-outline-warning <?php echo $is_favorited ? 'active' : ''; ?>" 
                                id="favoriteButton" 
                                data-content-id="<?php echo $content_id; ?>">
                            <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-bookmark"></i> Favorilere Ekle
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=view.php?id=<?php echo $content_id; ?>" class="btn btn-outline-danger">
                            <i class="far fa-heart"></i> Beğen
                        </a>
                        <a href="login.php?redirect=view.php?id=<?php echo $content_id; ?>" class="btn btn-outline-warning">
                            <i class="far fa-bookmark"></i> Favorilere Ekle
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareModal">
                        <i class="fas fa-share-alt"></i> Paylaş
                    </button>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Yazdır
                    </button>
                </div>
            </div>
            
            <!-- Yorumlar -->
            <div class="mt-5" data-aos="fade-up" data-aos-delay="300">
                <h3 class="mb-4">
                    <i class="fas fa-comments me-2"></i>Yorumlar 
                    <span class="badge bg-secondary"><?php echo mysqli_num_rows($comments_result); ?></span>
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php if ($comment_success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Yorumunuz başarıyla eklendi.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($comment_error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $comment_error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="view.php?id=<?php echo $content_id; ?>" id="commentForm">
                                <?php echo getCSRFTokenInput(); ?>
                                <div class="mb-3">
                                    <textarea class="form-control" name="comment" rows="3" 
                                              placeholder="Yorumunuzu yazın..." required 
                                              maxlength="1000" minlength="3"></textarea>
                                    <div class="form-text">Minimum 3, maksimum 1000 karakter</div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="commentSubmitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Yorum Gönder
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        Yorum yapmak için <a href="login.php?redirect=view.php?id=<?php echo $content_id; ?>" class="alert-link">giriş yapın</a> 
                        veya <a href="register.php" class="alert-link">üye olun</a>.
                    </div>
                <?php endif; ?>
                
                <div class="comments-list">
                    <?php if (mysqli_num_rows($comments_result) > 0): ?>
                        <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                            <div class="comment-item">
                                <div class="d-flex">
                                    <img src="<?php echo !empty($comment['avatar']) ? SITE_URL . '/uploads/avatars/' . $comment['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($comment['username']) . '&background=6366f1&color=fff'; ?>" 
                                         class="rounded-circle me-3" width="50" height="50" alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comment-slash fa-3x mb-3"></i>
                            <p>Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sağ Taraf - Sidebar -->
        <div class="col-lg-4">
            <!-- Kapak Resmi -->
            <div class="mb-4 text-center" data-aos="fade-left">
                <?php 
                $cover_image_path = 'uploads/covers/' . $content['cover_image'];
                if (file_exists($cover_image_path)): ?>
                    <img src="<?php echo htmlspecialchars($cover_image_path); ?>" 
                         class="img-fluid rounded shadow" 
                         style="max-height: 500px;" 
                         alt="<?php echo htmlspecialchars($content['title']); ?>"
                         onerror="this.src='assets/images/no-image.svg';">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light rounded shadow" style="height: 300px;">
                        <div class="text-center text-muted">
                            <i class="fas fa-image fa-3x mb-2"></i>
                            <p>Kapak resmi bulunamadı</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Yazar Kartı -->
            <div class="author-card mb-4" data-aos="fade-left" data-aos-delay="100">
                <img src="<?php echo !empty($author['avatar']) ? SITE_URL . '/uploads/avatars/' . $author['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($author['username']) . '&background=6366f1&color=fff'; ?>" 
                     class="rounded-circle mb-3" width="80" height="80" alt="<?php echo htmlspecialchars($author['username']); ?>">
                <h5><?php echo htmlspecialchars($author['username']); ?></h5>
                <?php if (!empty($author['bio'])): ?>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($author['bio']); ?></p>
                <?php endif; ?>
                <?php
                $author_content_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM content WHERE user_id = {$author['id']} AND status = 'published'"))['count'];
                ?>
                <div class="d-flex justify-content-center gap-3 text-muted">
                    <span><i class="fas fa-book me-1"></i> <?php echo $author_content_count; ?> Eser</span>
                    <span><i class="fas fa-calendar me-1"></i> <?php echo date('Y', strtotime($author['created_at'])); ?>'den beri</span>
                </div>
            </div>
            
            <!-- Benzer İçerikler -->
            <div class="card" data-aos="fade-left" data-aos-delay="200" style="max-height: 180px;">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-th me-2"></i>Benzer İçerikler</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 120px; overflow-y: auto;">
                        <?php if (!empty($similar_contents)): ?>
                            <?php foreach ($similar_contents as $similar): ?>
                                <a href="view.php?id=<?php echo $similar['id']; ?>" class="list-group-item list-group-item-action py-2 px-3">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                        $similar_cover_path = 'uploads/covers/' . $similar['cover_image'];
                                        if (file_exists($similar_cover_path)): ?>
                                            <img src="<?php echo htmlspecialchars($similar_cover_path); ?>" 
                                                 class="rounded me-2" width="40" height="40" 
                                                 style="object-fit: cover; flex-shrink: 0;" 
                                                 alt="<?php echo htmlspecialchars($similar['title']); ?>"
                                                 onerror="this.src='assets/images/no-image.svg';">
                                        <?php else: ?>
                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px; min-width: 40px; flex-shrink: 0;">
                                                <i class="fas fa-image text-muted" style="font-size: 12px;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1 min-w-0">
                                            <h6 class="mb-0 text-truncate" style="font-size: 0.85rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars($similar['title']); ?>
                                                <?php 
                                                $match_badge = '';
                                                $match_color = 'secondary';
                                                switch($similar['match_type']) {
                                                    case 'tag_match':
                                                        $match_badge = 'Benzer Etiket';
                                                        $match_color = 'success';
                                                        break;
                                                    case 'author_match':
                                                        $match_badge = 'Aynı Yazar';
                                                        $match_color = 'primary';
                                                        break;
                                                    case 'type_match':
                                                        $match_badge = 'Aynı Tür';
                                                        $match_color = 'info';
                                                        break;
                                                    case 'popular_match':
                                                        $match_badge = 'Popüler';
                                                        $match_color = 'warning';
                                                        break;
                                                }
                                                ?>
                                                <?php if (!empty($match_badge)): ?>
                                                    <span class="badge bg-<?php echo $match_color; ?> ms-1" style="font-size: 0.6rem;">
                                                        <?php echo $match_badge; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                <i class="fas fa-eye me-1"></i> <?php echo number_format($similar['views']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-2 text-center text-muted">
                                <small>Benzer içerik bulunamadı.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chapter Selection Side Panel -->
<div class="chapter-sidepanel" id="chapterSidepanel">
    <div class="sidepanel-overlay" id="sidepanelOverlay"></div>
    <div class="sidepanel-content">
        <div class="sidepanel-header">
            <h5 class="sidepanel-title">
                <i class="fas fa-list me-2"></i>Bölüm Seç
            </h5>
            <button type="button" class="btn-close" id="closeSidepanel" aria-label="Close"></button>
        </div>
        <div class="sidepanel-body">
            <?php foreach ($chapters as $chapter): ?>
                <a href="view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $chapter['chapter_number']; ?>" 
                   class="chapter-item <?php echo $chapter['chapter_number'] == $current_chapter ? 'active' : ''; ?>">
                    <div class="chapter-info">
                        <div class="chapter-header">
                            <span class="chapter-number">
                                <i class="fas fa-bookmark me-1"></i>
                                Bölüm <?php echo $chapter['chapter_number']; ?>
                                <?php if ($chapter['chapter_number'] == $current_chapter): ?>
                                    <span class="current-badge">Şu An</span>
                                <?php endif; ?>
                            </span>
                            <span class="chapter-views">
                                <i class="fas fa-eye me-1"></i><?php echo number_format($chapter['views']); ?>
                            </span>
                        </div>
                        <div class="chapter-title">
                            <?php echo htmlspecialchars($chapter['title']); ?>
                        </div>
                        <?php if (!empty($chapter['description'])): ?>
                            <div class="chapter-description">
                                <?php echo htmlspecialchars(substr($chapter['description'], 0, 80)) . (strlen($chapter['description']) > 80 ? '...' : ''); ?>
                            </div>
                        <?php endif; ?>
                        <div class="chapter-date">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d.m.Y', strtotime($chapter['created_at'])); ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Paylaş</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="shareOn('facebook')">
                        <i class="fab fa-facebook-f me-2"></i>Facebook'ta Paylaş
                    </button>
                    <button class="btn btn-outline-info" onclick="shareOn('twitter')">
                        <i class="fab fa-twitter me-2"></i>Twitter'da Paylaş
                    </button>
                    <button class="btn btn-outline-success" onclick="shareOn('whatsapp')">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp'ta Paylaş
                    </button>
                    <hr>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareUrl" value="<?php echo SITE_URL . '/view.php?id=' . $content_id; ?>" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyShareUrl()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Beğeni ve Favori İşlemleri
<?php if (isLoggedIn()): ?>
document.getElementById('likeButton').addEventListener('click', async function() {
    const contentId = this.dataset.contentId;
    const icon = this.querySelector('i');
    const likeCount = document.getElementById('likeCount');
    const button = this;
    
    // Buton deaktif et
    button.disabled = true;
    
    try {
        const response = await fetch('<?php echo SITE_URL; ?>/api/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ content_id: parseInt(contentId) })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success) {
            button.classList.toggle('active');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            likeCount.textContent = number_format(data.likes);
            
            // Kullanıcı geri bildirimi
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            }
        } else {
            // Hata mesajı göster
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Bir hata oluştu', 'error');
            } else {
                alert(data.message || 'Bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Beğeni hatası:', error);
        if (typeof showNotification === 'function') {
            showNotification('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
        } else {
            alert('Bağlantı hatası. Lütfen tekrar deneyin.');
        }
    } finally {
        // Buton aktif et
        button.disabled = false;
    }
});

document.getElementById('favoriteButton').addEventListener('click', async function() {
    const contentId = this.dataset.contentId;
    const icon = this.querySelector('i');
    const button = this;
    
    // Buton deaktif et
    button.disabled = true;
    
    try {
        const response = await fetch('<?php echo SITE_URL; ?>/api/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ content_id: parseInt(contentId) })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success) {
            button.classList.toggle('active');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            
            // Kullanıcı geri bildirimi
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'success');
            }
        } else {
            // Hata mesajı göster
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Bir hata oluştu', 'error');
            } else {
                alert(data.message || 'Bir hata oluştu');
            }
        }
    } catch (error) {
        console.error('Favori hatası:', error);
        if (typeof showNotification === 'function') {
            showNotification('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
        } else {
            alert('Bağlantı hatası. Lütfen tekrar deneyin.');
        }
    } finally {
        // Buton aktif et
        button.disabled = false;
    }
});

// Yorum formu validasyonu
document.getElementById('commentForm').addEventListener('submit', function(e) {
    const textarea = this.querySelector('textarea[name="comment"]');
    const submitBtn = document.getElementById('commentSubmitBtn');
    const comment = textarea.value.trim();
    
    if (comment.length < 3) {
        e.preventDefault();
        if (typeof showNotification === 'function') {
            showNotification('Yorum en az 3 karakter olmalıdır.', 'error');
        } else {
            alert('Yorum en az 3 karakter olmalıdır.');
        }
        textarea.focus();
        return false;
    }
    
    if (comment.length > 1000) {
        e.preventDefault();
        if (typeof showNotification === 'function') {
            showNotification('Yorum en fazla 1000 karakter olabilir.', 'error');
        } else {
            alert('Yorum en fazla 1000 karakter olabilir.');
        }
        textarea.focus();
        return false;
    }
    
    // Form gönderilirken buton deaktif et ve çift gönderimi önle
    if (submitBtn.disabled) {
        e.preventDefault();
        return false;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';
    
    // 10 saniye sonra buton aktif et (ağ sorunları için)
    setTimeout(function() {
        if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Yorum Gönder';
        }
    }, 10000);
});

// Number format fonksiyonu
function number_format(number) {
    return new Intl.NumberFormat('tr-TR').format(number);
}
<?php endif; ?>

// Paylaşım fonksiyonları
function shareOn(platform) {
    const url = document.getElementById('shareUrl').value;
    const title = '<?php echo addslashes($content['title']); ?>';
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
            break;
    }
    
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

function copyShareUrl() {
    const urlInput = document.getElementById('shareUrl');
    urlInput.select();
    document.execCommand('copy');
    
    if (window.showNotification) {
        showNotification('Link kopyalandı!', 'success');
    }
}

// Zoom kontrolleri
let currentZoom = 100;

document.getElementById('zoomIn').addEventListener('click', function() {
    currentZoom = Math.min(currentZoom + 10, 200);
    updateZoom();
});

document.getElementById('zoomOut').addEventListener('click', function() {
    currentZoom = Math.max(currentZoom - 10, 50);
    updateZoom();
});

document.getElementById('zoomReset').addEventListener('click', function() {
    currentZoom = 100;
    updateZoom();
});

function updateZoom() {
    const viewer = document.querySelector('.reader-viewer img, .reader-viewer iframe');
    if (viewer) {
        viewer.style.transform = `scale(${currentZoom / 100})`;
        viewer.style.transformOrigin = 'center center';
    }
    document.getElementById('zoomReset').textContent = `${currentZoom}%`;
}

// Tam ekran
document.getElementById('fullscreen').addEventListener('click', function() {
    const readerContainer = document.querySelector('.reader-container');
    if (!document.fullscreenElement) {
        readerContainer.requestFullscreen();
        this.innerHTML = '<i class="fas fa-compress"></i>';
    } else {
        document.exitFullscreen();
        this.innerHTML = '<i class="fas fa-expand"></i>';
    }
});

// Side Panel Controls
document.addEventListener('DOMContentLoaded', function() {
    // Başarı mesajı varsa form alanını temizle
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('comment_success') === '1') {
        const commentTextarea = document.querySelector('textarea[name="comment"]');
        if (commentTextarea) {
            commentTextarea.value = '';
        }
        
        // URL'den comment_success parametresini temizle (opsiyonel)
        if (window.history && window.history.replaceState) {
            urlParams.delete('comment_success');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, document.title, newUrl);
        }
    }
    
    const sidepanel = document.getElementById('chapterSidepanel');
    const openBtn = document.getElementById('openSidepanel');
    const closeBtn = document.getElementById('closeSidepanel');
    const overlay = document.getElementById('sidepanelOverlay');
    
    // Açma fonksiyonu
    function openSidepanel() {
        sidepanel.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Mevcut bölümü görünür alanda tut
        setTimeout(() => {
            const activeChapter = sidepanel.querySelector('.chapter-item.active');
            if (activeChapter) {
                activeChapter.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 300);
    }
    
    // Kapama fonksiyonu
    function closeSidepanel() {
        sidepanel.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // Event listeners
    if (openBtn) {
        openBtn.addEventListener('click', openSidepanel);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidepanel);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidepanel);
    }
    
    // ESC tuşu ile kapama
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidepanel.classList.contains('show')) {
            closeSidepanel();
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?> 