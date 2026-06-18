<?php
if (!isset($pageTitle)) $pageTitle = 'Forum';
$notifCount = 0;
$msgCount = 0;
if (isLoggedIn()) {
    $notifCount = getUnreadNotificationCount($pdo, $_SESSION['user_id']);
    $msgCount = getUnreadMessageCount($pdo, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Türkiye'nin en interaktif forum ve sözlük platformu">
    <title><?= e($pageTitle) ?> — SözlükForum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo">
                <i class="fas fa-fire-flame-curved"></i>
                <span>SözlükForum</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-house"></i>
                <span>Ana Sayfa</span>
            </a>
            <?php if (isLoggedIn()): ?>
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' && isset($_GET['id']) && $_GET['id'] == $_SESSION['user_id'] ? 'active' : '' ?>">
                <i class="fas fa-user"></i>
                <span>Profilim</span>
            </a>
            <a href="my_topics.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'my_topics.php' ? 'active' : '' ?>">
                <i class="fas fa-pen-to-square"></i>
                <span>Forumlarım</span>
            </a>
            <a href="subscriptions.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'subscriptions.php' ? 'active' : '' ?>">
                <i class="fas fa-bookmark"></i>
                <span>Aboneliklerim</span>
            </a>
            <a href="messages.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i>
                <span>Mesajlar</span>
                <?php if ($msgCount > 0): ?>
                    <span class="badge"><?= $msgCount ?></span>
                <?php endif; ?>
            </a>
            <a href="notifications.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Bildirimler</span>
                <?php if ($notifCount > 0): ?>
                    <span class="badge"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
                <i class="fas fa-shield-halved"></i>
                <span>Yönetim Paneli</span>
            </a>
            <?php endif; ?>
            <div class="nav-divider"></div>
            <a href="create_topic.php" class="nav-item nav-create">
                <i class="fas fa-plus"></i>
                <span>Yeni Başlık Oluştur</span>
            </a>
            <div class="nav-divider"></div>
            <button class="nav-item theme-toggle" onclick="toggleTheme()">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeText">Karanlık Mod</span>
            </button>
            <a href="logout.php" class="nav-item nav-logout">
                <i class="fas fa-right-from-bracket"></i>
                <span>Çıkış Yap</span>
            </a>
            <?php else: ?>
            <a href="login.php" class="nav-item">
                <i class="fas fa-right-to-bracket"></i>
                <span>Giriş Yap</span>
            </a>
            <a href="register.php" class="nav-item">
                <i class="fas fa-user-plus"></i>
                <span>Kayıt Ol</span>
            </a>
            <div class="nav-divider"></div>
            <button class="nav-item theme-toggle" onclick="toggleTheme()">
                <i class="fas fa-moon" id="themeIcon"></i>
                <span id="themeText">Karanlık Mod</span>
            </button>
            <?php endif; ?>
        </nav>
        <?php if (isLoggedIn()): ?>
        <div class="sidebar-user">
            <img src="<?= e(getAvatar($_SESSION['avatar'] ?? '')) ?>" alt="Avatar" class="sidebar-avatar">
            <div class="sidebar-user-info">
                <span class="sidebar-username"><?= e($_SESSION['display_name'] ?? $_SESSION['username']) ?></span>
                <span class="sidebar-role">@<?= e($_SESSION['username']) ?></span>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <!-- Top bar for mobile -->
        <header class="topbar">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Menü">
                <i class="fas fa-bars"></i>
            </button>
            <a href="index.php" class="topbar-logo">
                <i class="fas fa-fire-flame-curved"></i>
                SözlükForum
            </a>
            <div class="topbar-actions">
                <?php if (isLoggedIn()): ?>
                <a href="notifications.php" class="topbar-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="topbar-badge"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="topbar-btn">
                    <i class="fas fa-envelope"></i>
                    <?php if ($msgCount > 0): ?>
                        <span class="topbar-badge"><?= $msgCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
        </header>
        <div class="content-wrapper">
