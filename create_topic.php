<?php
require_once 'config.php';
requireLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $error = 'Başlık boş olamaz.';
    } elseif (strlen($title) > 255) {
        $error = 'Başlık çok uzun (max 255 karakter).';
    } else {
        $stmt = $pdo->prepare("INSERT INTO topics (title, description, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $_SESSION['user_id']]);
        $topicId = $pdo->lastInsertId();

        // Auto-subscribe creator
        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, topic_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $topicId]);

        header("Location: topic.php?id=$topicId");
        exit;
    }
}

$pageTitle = 'Yeni Başlık Oluştur';
include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <div class="page-header">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Geri</a>
            <h1><i class="fas fa-plus-circle"></i> Yeni Başlık Oluştur</h1>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="create-form" onsubmit="return validateForm(this)">
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Başlık</label>
                    <input type="text" id="title" name="title" value="<?= e($title ?? '') ?>" placeholder="Tartışma başlığını yazın..." required maxlength="255">
                </div>
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Açıklama (isteğe bağlı)</label>
                    <textarea id="description" name="description" placeholder="Konu hakkında detaylı açıklama yazın..." rows="5"><?= e($description ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Başlığı Oluştur
                </button>
            </form>
        </div>
    </div>
    <div class="content-sidebar">
        <div class="sidebar-card">
            <h3 class="sidebar-card-title"><i class="fas fa-lightbulb"></i> İpuçları</h3>
            <ul class="tips-list">
                <li>Başlığınız konuyu açık ve net bir şekilde ifade etmeli</li>
                <li>Açıklama kısmında detaylı bilgi verebilirsiniz</li>
                <li>Saygılı ve yapıcı bir dil kullanın</li>
                <li>Daha önce açılmış benzer başlıkları kontrol edin</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
