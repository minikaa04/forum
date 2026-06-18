<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$topicId = intval($_POST['topic_id'] ?? 0);
if (!$topicId) { header('Location: index.php'); exit; }

// Check if subscribed
$stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND topic_id = ?");
$stmt->execute([$_SESSION['user_id'], $topicId]);

if ($stmt->fetch()) {
    // Unsubscribe
    $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ? AND topic_id = ?")->execute([$_SESSION['user_id'], $topicId]);
} else {
    // Subscribe
    $pdo->prepare("INSERT INTO subscriptions (user_id, topic_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $topicId]);
}

header("Location: topic.php?id=$topicId");
exit;
?>
