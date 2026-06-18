<?php
require_once 'config.php';
requireLogin();

$pageTitle = 'Profili Düzenle';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($displayName)) {
        $error = 'Görünen ad boş olamaz.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta girin.';
    } else {
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = 'Bu e-posta zaten kullanılıyor.';
        } else {
            $avatarPath = $user['avatar'];

            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['avatar']['type'], $allowed)) {
                    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $target = 'uploads/' . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                        // Delete old avatar
                        if ($avatarPath && file_exists($avatarPath) && strpos($avatarPath, 'uploads/') === 0) {
                            unlink($avatarPath);
                        }
                        $avatarPath = $target;
                    }
                } else {
                    $error = 'Sadece JPEG, PNG, GIF ve WebP formatları desteklenir.';
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE users SET display_name = ?, bio = ?, email = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$displayName, $bio, $email, $avatarPath, $_SESSION['user_id']]);
                $_SESSION['display_name'] = $displayName;
                $_SESSION['avatar'] = $avatarPath;
                $user['display_name'] = $displayName;
                $user['bio'] = $bio;
                $user['email'] = $email;
                $user['avatar'] = $avatarPath;
                $success = 'Profiliniz güncellendi!';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main">
        <div class="page-header">
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Profile Dön</a>
            <h1><i class="fas fa-user-pen"></i> Profili Düzenle</h1>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="create-form">
                <div class="form-group avatar-upload">
                    <label>Profil Fotoğrafı</label>
                    <div class="avatar-preview-wrap">
                        <img src="<?= e(getAvatar($user['avatar'])) ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
                        <label for="avatar" class="avatar-upload-btn">
                            <i class="fas fa-camera"></i>
                            <span>Fotoğraf Seç</span>
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)" style="display:none">
                    </div>
                </div>
                <div class="form-group">
                    <label for="display_name"><i class="fas fa-user"></i> Görünen Ad</label>
                    <input type="text" id="display_name" name="display_name" value="<?= e($user['display_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> E-posta</label>
                    <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="bio"><i class="fas fa-align-left"></i> Hakkımda</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Kendinizden bahsedin..."><?= e($user['bio'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
