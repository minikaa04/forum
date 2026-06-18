<?php
require_once 'config.php';
$pageTitle = 'Ana Sayfa';

// Search
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search) {
    $where = "WHERE t.title LIKE ?";
    $params[] = "%$search%";
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM topics t $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalTopics = $stmt->fetchColumn();
$totalPages = ceil($totalTopics / $perPage);

$sql = "SELECT t.*, u.username, u.display_name, u.avatar, u.last_seen,
        (SELECT COUNT(*) FROM comments WHERE topic_id = t.id AND is_hidden = 0) as comment_count
        FROM topics t
        JOIN users u ON t.user_id = u.id
        $where
        ORDER BY t.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topics = $stmt->fetchAll();

// Popular topics (sidebar)
$stmt = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count FROM topics t ORDER BY comment_count DESC, t.views DESC LIMIT 5");
$popularTopics = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <!-- Search bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <i class="fas fa-search"></i>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Başlık ara..." class="search-input">
                <button type="submit" class="btn btn-sm">Ara</button>
            </form>
        </div>

        <?php if ($search): ?>
            <div class="search-result-info">
                "<strong><?= e($search) ?></strong>" için <?= $totalTopics ?> sonuç bulundu.
                <a href="index.php">Temizle</a>
            </div>
        <?php endif; ?>

        <!-- Topic list -->
        <div class="topic-list">
            <?php if (empty($topics)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Henüz başlık yok</h3>
                    <p>İlk başlığı oluşturan siz olun!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="create_topic.php" class="btn btn-primary">Yeni Başlık Oluştur</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                <a href="topic.php?id=<?= $topic['id'] ?>" class="topic-card">
                    <div class="topic-card-left">
                        <img src="<?= e(getAvatar($topic['avatar'])) ?>" alt="Avatar" class="topic-avatar">
                        <div class="topic-info">
                            <h3 class="topic-title"><?= e($topic['title']) ?></h3>
                            <div class="topic-meta">
                                <span class="topic-author">
                                    <i class="fas fa-user"></i> <?= e($topic['display_name'] ?? $topic['username']) ?>
                                </span>
                                <span class="topic-time">
                                    <i class="fas fa-clock"></i> <?= timeAgo($topic['created_at']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="topic-card-right">
                        <div class="topic-stat">
                            <i class="fas fa-message"></i>
                            <span><?= $topic['comment_count'] ?></span>
                        </div>
                        <div class="topic-stat">
                            <i class="fas fa-eye"></i>
                            <span><?= $topic['views'] ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right sidebar -->
    <div class="content-sidebar">
        <?php if (isLoggedIn()): ?>
        <div class="sidebar-card">
            <a href="create_topic.php" class="btn btn-primary btn-full">
                <i class="fas fa-plus"></i> Yeni Başlık Oluştur
            </a>
        </div>
        <?php endif; ?>

        <div class="sidebar-card">
            <h3 class="sidebar-card-title"><i class="fas fa-fire"></i> Popüler Başlıklar</h3>
            <?php foreach ($popularTopics as $pt): ?>
            <a href="topic.php?id=<?= $pt['id'] ?>" class="sidebar-topic">
                <span class="sidebar-topic-title"><?= e($pt['title']) ?></span>
                <span class="sidebar-topic-count"><?= $pt['comment_count'] ?> yorum</span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-card">
            <h3 class="sidebar-card-title"><i class="fas fa-info-circle"></i> Hakkında</h3>
            <p class="sidebar-about">SözlükForum, fikirlerin paylaşıldığı ve tartışıldığı interaktif bir forumdur. Her konuda özgürce düşüncelerinizi paylaşın!</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
