<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$commentId = intval($_POST['comment_id'] ?? 0);
$vote = intval($_POST['vote'] ?? 0);

if (!$commentId || !in_array($vote, [1, -1])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Check if user already voted
$stmt = $pdo->prepare("SELECT vote FROM comment_votes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$commentId, $_SESSION['user_id']]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['vote'] == $vote) {
        // Remove vote (toggle)
        $stmt = $pdo->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$commentId, $_SESSION['user_id']]);
    } else {
        // Change vote
        $stmt = $pdo->prepare("UPDATE comment_votes SET vote = ? WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$vote, $commentId, $_SESSION['user_id']]);
    }
} else {
    // New vote
    $stmt = $pdo->prepare("INSERT INTO comment_votes (comment_id, user_id, vote) VALUES (?, ?, ?)");
    $stmt->execute([$commentId, $_SESSION['user_id'], $vote]);
}

// Get updated counts
$stmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END), 0) as likes,
    COALESCE(SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END), 0) as dislikes
    FROM comment_votes WHERE comment_id = ?");
$stmt->execute([$commentId]);
$counts = $stmt->fetch();

// Get user's current vote
$stmt = $pdo->prepare("SELECT vote FROM comment_votes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$commentId, $_SESSION['user_id']]);
$currentVote = $stmt->fetch();

echo json_encode([
    'likes' => (int)$counts['likes'],
    'dislikes' => (int)$counts['dislikes'],
    'userVote' => $currentVote ? (int)$currentVote['vote'] : 0
]);
?>
