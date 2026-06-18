<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Mesajlar';

// Handle message request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $receiverId = intval($_POST['receiver_id'] ?? 0);

    if ($action === 'request' && $receiverId) {
        // Check if request already exists
        $stmt = $pdo->prepare("SELECT id FROM message_requests WHERE
            (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $stmt->execute([$_SESSION['user_id'], $receiverId, $receiverId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO message_requests (sender_id, receiver_id) VALUES (?, ?)")
                ->execute([$_SESSION['user_id'], $receiverId]);
            $name = $_SESSION['display_name'] ?? $_SESSION['username'];
            $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'message_request', ?, 'notifications.php')")
                ->execute([$receiverId, "$name size mesaj isteği gönderdi"]);
        }
    }
}

// Get conversations (accepted message requests)
$stmt = $pdo->prepare("
    SELECT mr.*, 
           CASE WHEN mr.sender_id = ? THEN mr.receiver_id ELSE mr.sender_id END as other_id
    FROM message_requests mr
    WHERE (mr.sender_id = ? OR mr.receiver_id = ?) AND mr.status = 'accepted'
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$requests = $stmt->fetchAll();

$conversations = [];
foreach ($requests as $req) {
    $otherId = $req['other_id'];
    // Get other user info
    $stmt = $pdo->prepare("SELECT id, username, display_name, avatar, last_seen FROM users WHERE id = ?");
    $stmt->execute([$otherId]);
    $otherUser = $stmt->fetch();
    if (!$otherUser) continue;

    // Get last message
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE
        (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $otherId, $otherId, $_SESSION['user_id']]);
    $lastMsg = $stmt->fetch();

    // Unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$otherId, $_SESSION['user_id']]);
    $unread = $stmt->fetchColumn();

    $conversations[] = [
        'user' => $otherUser,
        'last_message' => $lastMsg,
        'unread' => $unread
    ];
}

// Active chat
$chatUserId = intval($_GET['user'] ?? 0);
$chatUser = null;
$chatMessages = [];

if ($chatUserId) {
    // Verify approved message request
    $stmt = $pdo->prepare("SELECT id FROM message_requests WHERE
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted'");
    $stmt->execute([$_SESSION['user_id'], $chatUserId, $chatUserId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT id, username, display_name, avatar, last_seen FROM users WHERE id = ?");
        $stmt->execute([$chatUserId]);
        $chatUser = $stmt->fetch();

        if ($chatUser) {
            // Mark messages as read
            $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")
                ->execute([$chatUserId, $_SESSION['user_id']]);

            // Get messages
            $stmt = $pdo->prepare("SELECT m.*, u.username, u.display_name, u.avatar FROM messages m
                                   JOIN users u ON m.sender_id = u.id
                                   WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                                   ORDER BY m.created_at ASC");
            $stmt->execute([$_SESSION['user_id'], $chatUserId, $chatUserId, $_SESSION['user_id']]);
            $chatMessages = $stmt->fetchAll();
        }
    }
}

include 'includes/header.php';
?>

<div class="messages-layout">
    <!-- Conversations list -->
    <div class="conversations-panel <?= $chatUser ? 'hide-mobile' : '' ?>" id="conversationsPanel">
        <div class="panel-header">
            <h2><i class="fas fa-envelope"></i> Mesajlar</h2>
        </div>
        <div class="conversations-list">
            <?php if (empty($conversations)): ?>
                <div class="empty-state small">
                    <i class="fas fa-envelope-open"></i>
                    <p>Henüz mesajınız yok</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="messages.php?user=<?= $conv['user']['id'] ?>" class="conversation-item <?= $chatUserId == $conv['user']['id'] ? 'active' : '' ?>">
                    <div class="conv-avatar-wrap">
                        <img src="<?= e(getAvatar($conv['user']['avatar'])) ?>" alt="" class="conv-avatar">
                        <span class="conv-status <?= isOnline($conv['user']['last_seen']) ? 'online' : 'offline' ?>"></span>
                    </div>
                    <div class="conv-info">
                        <div class="conv-top">
                            <span class="conv-name"><?= e($conv['user']['display_name'] ?? $conv['user']['username']) ?></span>
                            <?php if ($conv['last_message']): ?>
                                <span class="conv-time"><?= timeAgo($conv['last_message']['created_at']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="conv-preview">
                            <?php if ($conv['last_message']): ?>
                                <?php if ($conv['last_message']['sender_id'] == $_SESSION['user_id']): ?>
                                    <span class="conv-you">Siz: </span>
                                <?php endif; ?>
                                <?= e(mb_substr($conv['last_message']['content'], 0, 40)) ?><?= mb_strlen($conv['last_message']['content']) > 40 ? '...' : '' ?>
                            <?php else: ?>
                                <em>Mesaj yok</em>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($conv['unread'] > 0): ?>
                        <span class="conv-badge"><?= $conv['unread'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat panel -->
    <div class="chat-panel <?= !$chatUser ? 'hide-mobile' : '' ?>" id="chatPanel">
        <?php if ($chatUser): ?>
        <div class="chat-header">
            <a href="messages.php" class="back-btn-mobile"><i class="fas fa-arrow-left"></i></a>
            <a href="profile.php?id=<?= $chatUser['id'] ?>" class="chat-user-info">
                <img src="<?= e(getAvatar($chatUser['avatar'])) ?>" alt="" class="chat-avatar">
                <div>
                    <span class="chat-username"><?= e($chatUser['display_name'] ?? $chatUser['username']) ?></span>
                    <span class="chat-status"><?= isOnline($chatUser['last_seen']) ? 'Çevrimiçi' : 'Çevrimdışı' ?></span>
                </div>
            </a>
        </div>
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($chatMessages as $msg): ?>
            <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received' ?>">
                <div class="message-bubble">
                    <p><?= nl2br(e($msg['content'])) ?></p>
                    <span class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST" action="send_message.php" class="chat-input-form" onsubmit="return validateForm(this)">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="receiver_id" value="<?= $chatUser['id'] ?>">
            <textarea name="content" placeholder="Mesajınızı yazın..." rows="1" required id="messageInput"></textarea>
            <button type="submit" class="btn btn-primary chat-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
        <?php else: ?>
        <div class="chat-empty">
            <i class="fas fa-comments"></i>
            <h3>Bir sohbet seçin</h3>
            <p>Sol taraftaki sohbetlerden birini seçerek mesajlaşmaya başlayın.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll to bottom of chat
const chatMsgs = document.getElementById('chatMessages');
if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;

// Auto-resize textarea
const msgInput = document.getElementById('messageInput');
if (msgInput) {
    msgInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
