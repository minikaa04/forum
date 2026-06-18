<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Forumlarım';

$stmt = $pdo->prepare("SELECT t.*, (SELECT COUNT(*) FROM comments WHERE topic_id = t.id AND is_hidden = 0) as comment_count
                        FROM topics t WHERE t.user_id = ? ORDER BY t.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$topics = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <div class="page-header">
            <h1><i class="fas fa-pen-to-square"></i> Forumlarım</h1>
            <a href="create_topic.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Yeni Başlık
            </a>
        </div>

        <div class="topic-list">
            <?php if (empty($topics)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>Henüz bir başlık oluşturmadınız</h3>
                    <a href="create_topic.php" class="btn btn-primary">İlk Başlığınızı Oluşturun</a>
                </div>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                <a href="topic.php?id=<?= $topic['id'] ?>" class="topic-card">
                    <div class="topic-card-left">
                        <div class="topic-info">
                            <h3 class="topic-title"><?= e($topic['title']) ?></h3>
                            <div class="topic-meta">
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
