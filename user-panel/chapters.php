<?php
define('USER_PANEL_ACCESS', true);
$page_title = 'Bölüm Yönetimi';

// Yetki kontrolü
require_once 'includes/auth-check.php';
require_once '../includes/maintenance_check.php';

// Bakım modu kontrolü
checkMaintenanceMode();

$user = getCurrentUser();
$user_id = $user['id'];

// Content ID kontrolü
if (!isset($_GET['content_id']) || !is_numeric($_GET['content_id'])) {
    header('Location: content.php');
    exit;
}

$content_id = (int)$_GET['content_id'];

// İçeriğin kullanıcıya ait olduğunu ve seri olduğunu kontrol et
$content_stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE id = ? AND user_id = ? AND is_series = 1");
mysqli_stmt_bind_param($content_stmt, "ii", $content_id, $user_id);
mysqli_stmt_execute($content_stmt);
$content_result = mysqli_stmt_get_result($content_stmt);

if (mysqli_num_rows($content_result) === 0) {
    header('Location: content.php');
    exit;
}

$content = mysqli_fetch_assoc($content_result);

// Bölümleri al
$chapters_stmt = mysqli_prepare($conn, "SELECT * FROM chapters WHERE content_id = ? ORDER BY chapter_number ASC");
mysqli_stmt_bind_param($chapters_stmt, "i", $content_id);
mysqli_stmt_execute($chapters_stmt);
$chapters_result = mysqli_stmt_get_result($chapters_stmt);

// İstatistikler
$stats_stmt = mysqli_prepare($conn, "SELECT 
    COUNT(*) as total_chapters,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_chapters,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_chapters,
    MAX(chapter_number) as last_chapter
    FROM chapters WHERE content_id = ?");
mysqli_stmt_bind_param($stats_stmt, "i", $content_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$chapter_stats = mysqli_fetch_assoc($stats_result);

require_once 'includes/header.php';
?>

<style>
.chapter-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.chapter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.chapter-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
}

.chapter-number {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 700;
    font-size: 0.9rem;
}

.chapter-status {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-published {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-draft {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.chapter-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.chapter-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.chapter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-chapter {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.content-info-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.add-chapter-card {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border: 2px dashed var(--border-color);
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.add-chapter-card:hover {
    border-color: var(--primary-color);
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="dashboard-content">
    <!-- Geri Dön Butonu -->
    <div class="mb-4">
                    <a href="content.php" class="btn btn-outline-secondary">
                ⬅️ İçeriklerime Dön
            </a>
    </div>

    <!-- İçerik Bilgileri -->
    <div class="content-info-card" data-aos="fade-up">
        <h1 class="display-6 fw-bold mb-3"><?php echo htmlspecialchars($content['title']); ?></h1>
        <p class="lead mb-3"><?php echo htmlspecialchars($content['description']); ?></p>
        <div class="d-flex justify-content-center gap-4">
            <div>
                📅
                <?php echo date('d.m.Y', strtotime($content['created_at'])); ?>
            </div>
            <div>
                🏷️
                <?php echo $content['type'] === 'manga' ? 'Manga' : 'Çizgi Roman'; ?>
            </div>
            <div>
                👁️
                <?php echo number_format($content['views']); ?> Görüntülenme
            </div>
        </div>
    </div>

    <!-- İstatistikler -->
    <div class="stats-grid" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card">
            <div class="stat-number text-primary"><?php echo $chapter_stats['total_chapters']; ?></div>
            <div class="stat-label">Toplam Bölüm</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-success"><?php echo $chapter_stats['published_chapters']; ?></div>
            <div class="stat-label">Yayınlanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-secondary"><?php echo $chapter_stats['draft_chapters']; ?></div>
            <div class="stat-label">Taslak</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-info"><?php echo $chapter_stats['last_chapter'] ?? 0; ?></div>
            <div class="stat-label">Son Bölüm</div>
        </div>
    </div>

    <!-- Yeni Bölüm Ekle -->
    <div class="add-chapter-card" data-aos="fade-up" data-aos-delay="200">
        <div style="font-size: 3rem; margin-bottom: 1rem;" class="text-primary">➕</div>
        <h3 class="mb-3">Yeni Bölüm Ekle</h3>
        <p class="text-muted mb-4">Serinize yeni bir bölüm ekleyin</p>
        <a href="add-chapter.php?content_id=<?php echo $content_id; ?>" class="btn btn-primary btn-lg">
            ➕ Bölüm <?php echo ($chapter_stats['last_chapter'] ?? 0) + 1; ?> Ekle
        </a>
    </div>

    <!-- Bölüm Listesi -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            📚 Bölümler
        </h2>
    </div>

    <?php if (mysqli_num_rows($chapters_result) > 0): ?>
        <div data-aos="fade-up" data-aos-delay="300">
            <?php while ($chapter = mysqli_fetch_assoc($chapters_result)): ?>
                <div class="chapter-card">
                    <div class="chapter-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="chapter-number">
                                Bölüm <?php echo $chapter['chapter_number']; ?>
                            </div>
                            <div class="chapter-status status-<?php echo $chapter['status']; ?>">
                                <?php echo $chapter['status'] === 'published' ? 'Yayınlandı' : 'Taslak'; ?>
                            </div>
                        </div>
                        <div class="text-muted">
                            📅
                            <?php echo date('d.m.Y', strtotime($chapter['created_at'])); ?>
                        </div>
                    </div>
                    
                    <h3 class="chapter-title">
                        <?php echo htmlspecialchars($chapter['title']); ?>
                    </h3>
                    
                    <?php if (!empty($chapter['description'])): ?>
                        <p class="text-muted mb-3">
                            <?php echo htmlspecialchars($chapter['description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="chapter-meta">
                        <div>
                            📄
                            <?php 
                            $chapter_files = json_decode($chapter['chapter_files'], true);
                            if (is_array($chapter_files)) {
                                echo count($chapter_files) . ' dosya';
                            } else {
                                echo '1 dosya';
                            }
                            ?>
                        </div>
                        <div>
                            📁
                            <?php echo $chapter['file_type'] === 'images' ? 'Resimler' : 'PDF'; ?>
                        </div>
                        <?php if ($chapter['status'] === 'published'): ?>
                            <div>
                                👁️
                                <?php echo number_format($chapter['views'] ?? 0); ?> görüntülenme
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chapter-actions">
                        <?php if ($chapter['status'] === 'published'): ?>
                            <a href="../view.php?id=<?php echo $content_id; ?>&chapter=<?php echo $chapter['chapter_number']; ?>" 
                               class="btn-chapter btn btn-outline-primary" target="_blank">
                                👁️ Görüntüle
                            </a>
                        <?php endif; ?>
                        
                        <a href="edit-chapter.php?id=<?php echo $chapter['id']; ?>" 
                           class="btn-chapter btn btn-outline-warning">
                            ✏️ Düzenle
                        </a>
                        
                        <?php if ($chapter['status'] === 'draft'): ?>
                            <button onclick="publishChapter(<?php echo $chapter['id']; ?>)" 
                                    class="btn-chapter btn btn-outline-success">
                                📤 Yayınla
                            </button>
                        <?php else: ?>
                            <button onclick="unpublishChapter(<?php echo $chapter['id']; ?>)" 
                                    class="btn-chapter btn btn-outline-secondary">
                                📦 Yayından Kaldır
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="deleteChapter(<?php echo $chapter['id']; ?>, '<?php echo addslashes($chapter['title']); ?>')" 
                                class="btn-chapter btn btn-outline-danger">
                            🗑️ Sil
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" data-aos="fade-up">
            <div class="empty-icon">
                📖
            </div>
            <h3 class="mb-3">Henüz Bölüm Yok</h3>
            <p class="mb-4">Bu seri için henüz hiç bölüm eklenmemiş. İlk bölümü ekleyerek başlayın!</p>
            <a href="add-chapter.php?content_id=<?php echo $content_id; ?>" class="btn btn-primary btn-lg">
                ➕ İlk Bölümü Ekle
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function publishChapter(chapterId) {
    if (confirm('Bu bölümü yayınlamak istediğinizden emin misiniz?')) {
        updateChapterStatus(chapterId, 'published');
    }
}

function unpublishChapter(chapterId) {
    if (confirm('Bu bölümü yayından kaldırmak istediğinizden emin misiniz?')) {
        updateChapterStatus(chapterId, 'draft');
    }
}

function updateChapterStatus(chapterId, status) {
    fetch('api/update-chapter-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            chapter_id: chapterId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

function deleteChapter(chapterId, chapterTitle) {
    if (confirm(`"${chapterTitle}" bölümünü silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) {
        fetch('api/delete-chapter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chapter_id: chapterId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 