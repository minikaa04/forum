<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Kullanıcı adı 3-50 karakter olmalıdır.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $password2) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, display_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $username]);

            $newUserId = $pdo->lastInsertId();
            // System notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'system', 'SözlükForum''a hoş geldiniz! Profilinizi tamamlamayı unutmayın.', 'edit_profile.php')");
            $stmt->execute([$newUserId]);

            $success = 'Hesabınız oluşturuldu! Şimdi giriş yapabilirsiniz.';
        }
    }
}

$pageTitle = 'Kayıt Ol';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — SözlükForum</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-fire-flame-curved"></i>
                <h1>SözlükForum</h1>
                <p>Topluluğumuza katılın</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= e($success) ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" value="<?= e($username ?? '') ?>" placeholder="Kullanıcı adınız" required minlength="3" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                    <input type="email" id="email" name="email" value="<?= e($email ?? '') ?>" placeholder="E-posta adresiniz" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                    <input type="password" id="password" name="password" placeholder="En az 6 karakter" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password2"><i class="fas fa-lock"></i> Şifre Tekrar</label>
                    <input type="password" id="password2" name="password2" placeholder="Şifrenizi tekrarlayın" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i> Kayıt Ol
                </button>
            </form>
            <div class="auth-footer">
                Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a>
            </div>
        </div>
    </div>
    <script src="assets/script.js"></script>
</body>
</html>
