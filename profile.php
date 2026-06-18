<?php
require_once 'config.php';

$userId = intval($_GET['id'] ?? 0);
if (!$userId) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: index.php'); exit; }

$isOwn = isLoggedIn() && $_SESSION['user_id'] == $userId;

// Get user's topics
$stmt = $pdo->prepare("SELECT t.*, (SELECT COUNT(*) FROM comments WHERE topic_id = t.id AND is_hidden = 0) as comment_count
                        FROM topics t WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$userTopics = $stmt->fetchAll();

// Get user's comment count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$userId]);
$commentCount = $stmt->fetchColumn();

// Get user's topic count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE user_id = ?");
$stmt->execute([$userId]);
$topicCount = $stmt->fetchColumn();

// Friend status check
$friendStatus = null;
$friendRequestId = null;
if (isLoggedIn() && !$isOwn) {
    $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE
        (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']]);
    $friendReq = $stmt->fetch();
    if ($friendReq) {
        $friendStatus = $friendReq['status'];
        $friendRequestId = $friendReq['id'];
        if ($friendReq['sender_id'] == $userId && $friendReq['status'] == 'pending') {
            $friendStatus = 'incoming';
        }
    }
}

// Message request status
$msgStatus = null;
if (isLoggedIn() && !$isOwn) {
    $stmt = $pdo->prepare("SELECT * FROM message_requests WHERE
        (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']]);
    $msgReq = $stmt->fetch();
    if ($msgReq) {
        $msgStatus = $msgReq['status'];
        if ($msgReq['sender_id'] == $userId && $msgReq['status'] == 'pending') {
            $msgStatus = 'incoming_msg';
        }
    }
}

$pageTitle = ($user['display_name'] ?? $user['username']) . ' - Profil';
include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-info-section">
                <div class="profile-avatar-wrap">
                    <img src="<?= e(getAvatar($user['avatar'])) ?>" alt="Avatar" class="profile-avatar">
                    <span class="profile-status-dot <?= isOnline($user['last_seen']) ? 'online' : 'offline' ?>"></span>
                </div>
                <div class="profile-details">
                    <h1 class="profile-name"><?= e($user['display_name'] ?? $user['username']) ?></h1>
                    <span class="profile-username">@<?= e($user['username']) ?></span>
                    <span class="profile-online-status">
                        <?= isOnline($user['last_seen']) ? '<i class="fas fa-circle online-dot"></i> Çevrimiçi' : '<i class="fas fa-circle offline-dot"></i> Son görülme: ' . timeAgo($user['last_seen']) ?>
                    </span>
                </div>
                <div class="profile-actions">
                    <?php if ($isOwn): ?>
                        <a href="edit_profile.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-pen"></i> Profili Düzenle
                        </a>
                    <?php else: ?>
                        <?php if (isLoggedIn()): ?>
                            <!-- Friend Request -->
                            <?php if ($friendStatus === 'accepted'): ?>
                                <span class="btn btn-sm btn-success"><i class="fas fa-user-check"></i> Arkadaş</span>
                            <?php elseif ($friendStatus === 'pending'): ?>
                                <span class="btn btn-sm btn-secondary"><i class="fas fa-clock"></i> İstek Gönderildi</span>
                            <?php elseif ($friendStatus === 'incoming'): ?>
                                <form method="POST" action="friend_request.php" style="display:inline">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="request_id" value="<?= $friendRequestId ?>">
                                    <input type="hidden" name="redirect" value="profile.php?id=<?= $userId ?>">
                                    <button class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Kabul Et</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="friend_request.php" style="display:inline">
                                    <input type="hidden" name="action" value="send">
                                    <input type="hidden" name="receiver_id" value="<?= $userId ?>">
                                    <input type="hidden" name="redirect" value="profile.php?id=<?= $userId ?>">
                                    <button class="btn btn-sm btn-outline"><i class="fas fa-user-plus"></i> Arkadaş Ekle</button>
                                </form>
                            <?php endif; ?>

                            <!-- Message Request -->
                            <?php if ($msgStatus === 'accepted'): ?>
                                <a href="messages.php?user=<?= $userId ?>" class="btn btn-sm btn-primary"><i class="fas fa-envelope"></i> Mesaj Gönder</a>
                            <?php elseif ($msgStatus === 'pending'): ?>
                                <span class="btn btn-sm btn-secondary"><i class="fas fa-clock"></i> Mesaj İsteği Gönderildi</span>
                            <?php elseif ($msgStatus === 'incoming_msg'): ?>
                                <a href="notifications.php" class="btn btn-sm btn-primary"><i class="fas fa-envelope"></i> Mesaj İsteğini Gör</a>
                            <?php else: ?>
                                <form method="POST" action="send_message.php" style="display:inline">
                                    <input type="hidden" name="action" value="request">
                                    <input type="hidden" name="receiver_id" value="<?= $userId ?>">
                                    <button class="btn btn-sm btn-outline"><i class="fas fa-envelope"></i> Mesaj İsteği Gönder</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($user['bio']): ?>
            <div class="profile-bio">
                <p><?= nl2br(e($user['bio'])) ?></p>
            </div>
            <?php endif; ?>
            <div class="profile-stats">
                <div class="profile-stat">
                    <span class="stat-num"><?= $topicCount ?></span>
                    <span class="stat-label">Başlık</span>
                </div>
                <div class="profile-stat">
                    <span class="stat-num"><?= $commentCount ?></span>
                    <span class="stat-label">Yorum</span>
                </div>
                <div class="profile-stat">
                    <span class="stat-num"><?= date('d.m.Y', strtotime($user['created_at'])) ?></span>
                    <span class="stat-label">Katılım</span>
                </div>
            </div>
        </div>

        <!-- User's topics -->
        <div class="section-header">
            <h2><i class="fas fa-pen-to-square"></i> Oluşturduğu Başlıklar</h2>
        </div>
        <div class="topic-list">
            <?php if (empty($userTopics)): ?>
                <div class="empty-state small">
                    <p>Henüz başlık oluşturmamış.</p>
                </div>
            <?php else: ?>
                <?php foreach ($userTopics as $topic): ?>
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
