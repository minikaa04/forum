<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$topicId = intval($_POST['topic_id'] ?? 0);
$parentId = intval($_POST['parent_id'] ?? 0) ?: null;
$content = trim($_POST['content'] ?? '');

if (!$topicId || empty($content)) {
    header("Location: topic.php?id=$topicId");
    exit;
}

// Verify topic exists
$stmt = $pdo->prepare("SELECT id FROM topics WHERE id = ?");
$stmt->execute([$topicId]);
if (!$stmt->fetch()) { header('Location: index.php'); exit; }

// Insert comment
$stmt = $pdo->prepare("INSERT INTO comments (topic_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)");
$stmt->execute([$topicId, $_SESSION['user_id'], $parentId, $content]);

// Create notification for parent comment author (if replying)
if ($parentId) {
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$parentId]);
    $parentComment = $stmt->fetch();
    if ($parentComment && $parentComment['user_id'] != $_SESSION['user_id']) {
        $notifContent = ($_SESSION['display_name'] ?? $_SESSION['username']) . ' yorumunuza yanıt verdi';
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'comment_reply', ?, ?)");
        $stmt->execute([$parentComment['user_id'], $notifContent, "topic.php?id=$topicId#comment-" . $pdo->lastInsertId()]);
    }
}

header("Location: topic.php?id=$topicId");
exit;
?>
