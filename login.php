<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Tüm alanları doldurun.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_blocked']) {
                $error = 'Hesabınız engellenmiştir. Yöneticiyle iletişime geçin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
                $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}

$pageTitle = 'Giriş Yap';
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
                <p>Fikirlerinizi paylaşın, tartışmalara katılın</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Kullanıcı Adı veya E-posta</label>
                    <input type="text" id="username" name="username" value="<?= e($username ?? '') ?>" placeholder="Kullanıcı adınız veya e-posta" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Şifre</label>
                    <input type="password" id="password" name="password" placeholder="Şifreniz" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-right-to-bracket"></i> Giriş Yap
                </button>
            </form>
            <div class="auth-footer">
                Hesabınız yok mu? <a href="register.php">Kayıt Ol</a>
            </div>
        </div>
    </div>
    <script src="assets/script.js"></script>
</body>
</html>
