<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Bildirimler';

// Mark all as read when viewing
if (isset($_GET['markread'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: notifications.php');
    exit;
}

// Get friend requests (pending, sent to me)
$stmt = $pdo->prepare("SELECT fr.*, u.username, u.display_name, u.avatar FROM friend_requests fr
                        JOIN users u ON fr.sender_id = u.id
                        WHERE fr.receiver_id = ? AND fr.status = 'pending'
                        ORDER BY fr.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$friendRequests = $stmt->fetchAll();

// Get message requests (pending, sent to me)
$stmt = $pdo->prepare("SELECT mr.*, u.username, u.display_name, u.avatar FROM message_requests mr
                        JOIN users u ON mr.sender_id = u.id
                        WHERE mr.receiver_id = ? AND mr.status = 'pending'
                        ORDER BY mr.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$messageRequests = $stmt->fetchAll();

// Get other notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

$unreadCount = getUnreadNotificationCount($pdo, $_SESSION['user_id']);

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <div class="page-header">
            <h1><i class="fas fa-bell"></i> Bildirimler</h1>
            <?php if ($unreadCount > 0): ?>
            <a href="notifications.php?markread=1" class="btn btn-sm btn-secondary">
                <i class="fas fa-check-double"></i> Tümünü Okundu İşaretle
            </a>
            <?php endif; ?>
        </div>

        <!-- Notification tabs -->
        <div class="notif-tabs">
            <button class="notif-tab active" onclick="showNotifSection('all')">
                <i class="fas fa-bell"></i> Tümü
                <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?>
            </button>
            <button class="notif-tab" onclick="showNotifSection('friends')">
                <i class="fas fa-user-plus"></i> Arkadaşlık İstekleri
                <?php if (count($friendRequests) > 0): ?><span class="badge"><?= count($friendRequests) ?></span><?php endif; ?>
            </button>
            <button class="notif-tab" onclick="showNotifSection('messages')">
                <i class="fas fa-envelope"></i> Mesaj İstekleri
                <?php if (count($messageRequests) > 0): ?><span class="badge"><?= count($messageRequests) ?></span><?php endif; ?>
            </button>
        </div>

        <!-- All notifications -->
        <div class="notif-section" id="notif-all">
            <?php if (empty($notifications)): ?>
                <div class="empty-state small">
                    <i class="fas fa-bell-slash"></i>
                    <p>Bildiriminiz bulunmuyor.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                    <div class="notif-icon">
                        <?php
                        $icon = 'bell';
                        if ($notif['type'] === 'comment_reply') $icon = 'reply';
                        if ($notif['type'] === 'friend_request') $icon = 'user-plus';
                        if ($notif['type'] === 'message_request') $icon = 'envelope';
                        if ($notif['type'] === 'system') $icon = 'gear';
                        ?>
                        <i class="fas fa-<?= $icon ?>"></i>
                    </div>
                    <div class="notif-content">
                        <?php if ($notif['link']): ?>
                            <a href="<?= e($notif['link']) ?>"><?= e($notif['content']) ?></a>
                        <?php else: ?>
                            <span><?= e($notif['content']) ?></span>
                        <?php endif; ?>
                        <span class="notif-time"><?= timeAgo($notif['created_at']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Friend requests -->
        <div class="notif-section" id="notif-friends" style="display:none">
            <?php if (empty($friendRequests)): ?>
                <div class="empty-state small">
                    <i class="fas fa-user-plus"></i>
                    <p>Bekleyen arkadaşlık isteğiniz yok.</p>
                </div>
            <?php else: ?>
                <?php foreach ($friendRequests as $fr): ?>
                <div class="notif-item request-item">
                    <a href="profile.php?id=<?= $fr['sender_id'] ?>" class="request-user">
                        <img src="<?= e(getAvatar($fr['avatar'])) ?>" alt="" class="request-avatar">
                        <div>
                            <span class="request-name"><?= e($fr['display_name'] ?? $fr['username']) ?></span>
                            <span class="request-sub">@<?= e($fr['username']) ?></span>
                        </div>
                    </a>
                    <div class="request-actions">
                        <form method="POST" action="friend_request.php" style="display:inline">
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="request_id" value="<?= $fr['id'] ?>">
                            <input type="hidden" name="redirect" value="notifications.php">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Kabul</button>
                        </form>
                        <form method="POST" action="friend_request.php" style="display:inline">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" value="<?= $fr['id'] ?>">
                            <input type="hidden" name="redirect" value="notifications.php">
                            <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reddet</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Message requests -->
        <div class="notif-section" id="notif-messages" style="display:none">
            <?php if (empty($messageRequests)): ?>
                <div class="empty-state small">
                    <i class="fas fa-envelope"></i>
                    <p>Bekleyen mesaj isteğiniz yok.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messageRequests as $mr): ?>
                <div class="notif-item request-item">
                    <a href="profile.php?id=<?= $mr['sender_id'] ?>" class="request-user">
                        <img src="<?= e(getAvatar($mr['avatar'])) ?>" alt="" class="request-avatar">
                        <div>
                            <span class="request-name"><?= e($mr['display_name'] ?? $mr['username']) ?></span>
                            <span class="request-sub">@<?= e($mr['username']) ?> size mesaj göndermek istiyor</span>
                        </div>
                    </a>
                    <div class="request-actions">
                        <form method="POST" action="send_message.php" style="display:inline">
                            <input type="hidden" name="action" value="accept_msg">
                            <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Kabul</button>
                        </form>
                        <form method="POST" action="send_message.php" style="display:inline">
                            <input type="hidden" name="action" value="reject_msg">
                            <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                            <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reddet</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showNotifSection(section) {
    document.querySelectorAll('.notif-section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.notif-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('notif-' + section).style.display = 'block';
    event.target.closest('.notif-tab').classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
