<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Aboneliklerim';

$stmt = $pdo->prepare("SELECT t.*, u.username, u.display_name, u.avatar,
                        (SELECT COUNT(*) FROM comments WHERE topic_id = t.id AND is_hidden = 0) as comment_count
                        FROM subscriptions s
                        JOIN topics t ON s.topic_id = t.id
                        JOIN users u ON t.user_id = u.id
                        WHERE s.user_id = ?
                        ORDER BY s.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$topics = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <div class="page-header">
            <h1><i class="fas fa-bookmark"></i> Aboneliklerim</h1>
        </div>

        <div class="topic-list">
            <?php if (empty($topics)): ?>
                <div class="empty-state">
                    <i class="fas fa-bookmark"></i>
                    <h3>Henüz bir başlığa abone olmadınız</h3>
                    <p>Beğendiğiniz başlıklara abone olarak onları burada takip edin.</p>
                    <a href="index.php" class="btn btn-primary">Başlıklara Göz At</a>
                </div>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                <a href="topic.php?id=<?= $topic['id'] ?>" class="topic-card">
                    <div class="topic-card-left">
                        <img src="<?= e(getAvatar($topic['avatar'])) ?>" alt="" class="topic-avatar">
                        <div class="topic-info">
                            <h3 class="topic-title"><?= e($topic['title']) ?></h3>
                            <div class="topic-meta">
                                <span class="topic-author"><i class="fas fa-user"></i> <?= e($topic['display_name'] ?? $topic['username']) ?></span>
                                <span class="topic-time"><i class="fas fa-clock"></i> <?= timeAgo($topic['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="topic-card-right">
                        <div class="topic-stat"><i class="fas fa-message"></i> <span><?= $topic['comment_count'] ?></span></div>
                        <div class="topic-stat"><i class="fas fa-eye"></i> <span><?= $topic['views'] ?></span></div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
