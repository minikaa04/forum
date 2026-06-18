<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: messages.php'); exit; }

$action = $_POST['action'] ?? '';
$receiverId = intval($_POST['receiver_id'] ?? 0);

if ($action === 'request' && $receiverId && $receiverId != $_SESSION['user_id']) {
    // Send message request
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
    header("Location: " . ($_POST['redirect'] ?? "profile.php?id=$receiverId"));
    exit;
}

if ($action === 'send' && $receiverId) {
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        header("Location: messages.php?user=$receiverId");
        exit;
    }

    // Verify approved
    $stmt = $pdo->prepare("SELECT id FROM message_requests WHERE
        ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted'");
    $stmt->execute([$_SESSION['user_id'], $receiverId, $receiverId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)")
            ->execute([$_SESSION['user_id'], $receiverId, $content]);
    }
    header("Location: messages.php?user=$receiverId");
    exit;
}

if ($action === 'accept_msg') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM message_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE message_requests SET status = 'accepted' WHERE id = ?")->execute([$requestId]);
    }
    header("Location: notifications.php");
    exit;
}

if ($action === 'reject_msg') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM message_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE message_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
    }
    header("Location: notifications.php");
    exit;
}

header("Location: messages.php");
exit;
?>
