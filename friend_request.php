<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'notifications.php';

switch ($action) {
    case 'send':
        $receiverId = intval($_POST['receiver_id'] ?? 0);
        if ($receiverId && $receiverId != $_SESSION['user_id']) {
            // Check if already exists
            $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE
                (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$_SESSION['user_id'], $receiverId, $receiverId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)")
                    ->execute([$_SESSION['user_id'], $receiverId]);
                // Notification
                $name = $_SESSION['display_name'] ?? $_SESSION['username'];
                $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'friend_request', ?, 'notifications.php')")
                    ->execute([$receiverId, "$name size arkadaşlık isteği gönderdi"]);
            }
        }
        break;

    case 'accept':
        $requestId = intval($_POST['request_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?")->execute([$requestId]);
        }
        break;

    case 'reject':
        $requestId = intval($_POST['request_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
        }
        break;
}

header("Location: $redirect");
exit;
?>
