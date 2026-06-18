<?php
require_once 'config.php';

$topicId = intval($_GET['id'] ?? 0);
if (!$topicId) { header('Location: index.php'); exit; }

// Get topic
$stmt = $pdo->prepare("SELECT t.*, u.username, u.display_name, u.avatar, u.last_seen
                        FROM topics t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$topicId]);
$topic = $stmt->fetch();
if (!$topic) { header('Location: index.php'); exit; }

// Increment views
$pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?")->execute([$topicId]);

// Check subscription
$isSubscribed = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND topic_id = ?");
    $stmt->execute([$_SESSION['user_id'], $topicId]);
    $isSubscribed = (bool)$stmt->fetch();
}

// Get comments with vote counts
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.display_name, u.avatar, u.last_seen,
    COALESCE(SUM(CASE WHEN cv.vote = 1 THEN 1 ELSE 0 END), 0) as likes,
    COALESCE(SUM(CASE WHEN cv.vote = -1 THEN 1 ELSE 0 END), 0) as dislikes
    FROM comments c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN comment_votes cv ON c.id = cv.comment_id
    WHERE c.topic_id = ? AND c.is_hidden = 0
    GROUP BY c.id
    ORDER BY c.created_at ASC
");
$stmt->execute([$topicId]);
$allComments = $stmt->fetchAll();

// Get current user's votes
$userVotes = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT comment_id, vote FROM comment_votes WHERE user_id = ? AND comment_id IN (SELECT id FROM comments WHERE topic_id = ?)");
    $stmt->execute([$_SESSION['user_id'], $topicId]);
    while ($row = $stmt->fetch()) {
        $userVotes[$row['comment_id']] = $row['vote'];
    }
}

// Organize threaded comments
$comments = [];
$replies = [];
foreach ($allComments as $c) {
    if ($c['parent_id']) {
        $replies[$c['parent_id']][] = $c;
    } else {
        $comments[] = $c;
    }
}

// Comment count
$commentCount = count($allComments);

$pageTitle = $topic['title'];
include 'includes/header.php';

function renderComment($comment, $replies, $userVotes, $topicId, $depth = 0) {
    $maxDepth = 3;
    $isReply = $depth > 0;
    $userVote = $userVotes[$comment['id']] ?? 0;
    ?>
    <div class="comment <?= $isReply ? 'comment-reply' : '' ?>" id="comment-<?= $comment['id'] ?>" style="<?= $depth > 0 ? 'margin-left: ' . min($depth * 24, $maxDepth * 24) . 'px' : '' ?>">
        <div class="comment-header">
            <a href="profile.php?id=<?= $comment['user_id'] ?>" class="comment-author">
                <img src="<?= e(getAvatar($comment['avatar'])) ?>" alt="" class="comment-avatar">
                <div class="comment-author-info">
                    <span class="comment-username"><?= e($comment['display_name'] ?? $comment['username']) ?></span>
                    <span class="comment-status <?= isOnline($comment['last_seen']) ? 'online' : 'offline' ?>">
                        <i class="fas fa-circle"></i>
                    </span>
                </div>
            </a>
            <span class="comment-time"><?= timeAgo($comment['created_at']) ?></span>
        </div>
        <div class="comment-body">
            <?php if ($comment['parent_id'] && isset($comment['parent_username'])): ?>
                <span class="reply-to">@<?= e($comment['parent_username']) ?></span>
            <?php endif; ?>
            <?= nl2br(e($comment['content'])) ?>
        </div>
        <div class="comment-actions">
            <?php if (isLoggedIn()): ?>
            <button class="vote-btn <?= $userVote == 1 ? 'voted' : '' ?>" onclick="vote(<?= $comment['id'] ?>, 1)" title="Beğen">
                <i class="fas fa-thumbs-up"></i>
                <span id="likes-<?= $comment['id'] ?>"><?= $comment['likes'] ?></span>
            </button>
            <button class="vote-btn vote-down <?= $userVote == -1 ? 'voted' : '' ?>" onclick="vote(<?= $comment['id'] ?>, -1)" title="Beğenme">
                <i class="fas fa-thumbs-down"></i>
                <span id="dislikes-<?= $comment['id'] ?>"><?= $comment['dislikes'] ?></span>
            </button>
            <button class="reply-btn" onclick="showReplyForm(<?= $comment['id'] ?>)" title="Yanıtla">
                <i class="fas fa-reply"></i> Yanıtla
            </button>
            <?php else: ?>
            <span class="vote-btn disabled"><i class="fas fa-thumbs-up"></i> <?= $comment['likes'] ?></span>
            <span class="vote-btn disabled"><i class="fas fa-thumbs-down"></i> <?= $comment['dislikes'] ?></span>
            <?php endif; ?>
        </div>
        <!-- Reply form (hidden) -->
        <?php if (isLoggedIn()): ?>
        <div class="reply-form-container" id="reply-form-<?= $comment['id'] ?>" style="display:none;">
            <form method="POST" action="add_comment.php" class="reply-form" onsubmit="return validateForm(this)">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                <textarea name="content" placeholder="Yanıtınızı yazın..." rows="2" required></textarea>
                <div class="reply-form-actions">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="hideReplyForm(<?= $comment['id'] ?>)">İptal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Yanıtla</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <?php
        // Render replies recursively
        if (isset($replies[$comment['id']])) {
            foreach ($replies[$comment['id']] as $reply) {
                renderComment($reply, $replies, $userVotes, $topicId, $depth + 1);
            }
        }
        ?>
    </div>
    <?php
}
?>

<div class="page-layout">
    <div class="content-main">
        <!-- Topic header -->
        <div class="topic-header-card">
            <div class="topic-header-top">
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Geri</a>
                <?php if (isLoggedIn()): ?>
                <div class="topic-header-actions">
                    <form method="POST" action="subscribe.php" style="display:inline">
                        <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                        <button type="submit" class="btn btn-sm <?= $isSubscribed ? 'btn-secondary' : 'btn-outline' ?>">
                            <i class="fas fa-<?= $isSubscribed ? 'bookmark' : 'bookmark' ?>"></i>
                            <?= $isSubscribed ? 'Abonelikten Çık' : 'Abone Ol' ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <h1 class="topic-page-title"><?= e($topic['title']) ?></h1>
            <?php if ($topic['description']): ?>
                <p class="topic-description"><?= nl2br(e($topic['description'])) ?></p>
            <?php endif; ?>
            <div class="topic-header-meta">
                <a href="profile.php?id=<?= $topic['user_id'] ?>" class="topic-header-author">
                    <img src="<?= e(getAvatar($topic['avatar'])) ?>" alt="" class="small-avatar">
                    <?= e($topic['display_name'] ?? $topic['username']) ?>
                </a>
                <span><i class="fas fa-clock"></i> <?= timeAgo($topic['created_at']) ?></span>
                <span><i class="fas fa-eye"></i> <?= $topic['views'] + 1 ?> görüntülenme</span>
                <span><i class="fas fa-message"></i> <?= $commentCount ?> yorum</span>
            </div>
        </div>

        <!-- Comments -->
        <div class="comments-section">
            <h2 class="comments-title">
                <i class="fas fa-comments"></i> Yorumlar (<?= $commentCount ?>)
            </h2>

            <?php if (empty($comments)): ?>
                <div class="empty-state small">
                    <i class="fas fa-comment-dots"></i>
                    <p>Henüz yorum yok. İlk yorumu siz yapın!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <?php renderComment($comment, $replies, $userVotes, $topicId); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add comment form -->
        <?php if (isLoggedIn()): ?>
        <div class="add-comment-card">
            <h3><i class="fas fa-pen"></i> Görüşünüzü Yazın</h3>
            <form method="POST" action="add_comment.php" class="comment-form" onsubmit="return validateForm(this)">
                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                <textarea name="content" placeholder="Bu konu hakkında ne düşünüyorsunuz?" rows="4" required></textarea>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Yorum Ekle
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="add-comment-card login-prompt">
            <p>Yorum yapabilmek için <a href="login.php">giriş yapın</a> veya <a href="register.php">kayıt olun</a>.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="content-sidebar">
        <div class="sidebar-card">
            <h3 class="sidebar-card-title"><i class="fas fa-chart-simple"></i> Başlık Bilgileri</h3>
            <div class="sidebar-stats">
                <div class="stat-item">
                    <span class="stat-label">Oluşturan</span>
                    <a href="profile.php?id=<?= $topic['user_id'] ?>" class="stat-value"><?= e($topic['display_name'] ?? $topic['username']) ?></a>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Tarih</span>
                    <span class="stat-value"><?= date('d.m.Y H:i', strtotime($topic['created_at'])) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Görüntülenme</span>
                    <span class="stat-value"><?= $topic['views'] + 1 ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Yorum</span>
                    <span class="stat-value"><?= $commentCount ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
