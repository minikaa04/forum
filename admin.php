<?php
require_once 'config.php';
requireAdmin();

$pageTitle = 'Yönetim Paneli';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'block_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid && $uid != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?")->execute([$uid]);
        }
    }
    if ($action === 'unblock_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?")->execute([$uid]);
    }
    if ($action === 'hide_comment') {
        $cid = intval($_POST['comment_id'] ?? 0);
        $pdo->prepare("UPDATE comments SET is_hidden = 1 WHERE id = ?")->execute([$cid]);
    }
    if ($action === 'show_comment') {
        $cid = intval($_POST['comment_id'] ?? 0);
        $pdo->prepare("UPDATE comments SET is_hidden = 0 WHERE id = ?")->execute([$cid]);
    }
    if ($action === 'delete_comment') {
        $cid = intval($_POST['comment_id'] ?? 0);
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$cid]);
    }
    if ($action === 'delete_topic') {
        $tid = intval($_POST['topic_id'] ?? 0);
        $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$tid]);
    }

    header('Location: admin.php?tab=' . ($_POST['tab'] ?? 'dashboard'));
    exit;
}

$tab = $_GET['tab'] ?? 'dashboard';

// Dashboard stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTopics = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$onlineUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();

// Users list
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Recent topics
$topics = $pdo->query("SELECT t.*, u.username, u.display_name FROM topics t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 20")->fetchAll();

// Recent comments
$recentComments = $pdo->query("SELECT c.*, u.username, u.display_name, t.title as topic_title FROM comments c JOIN users u ON c.user_id = u.id JOIN topics t ON c.topic_id = t.id ORDER BY c.created_at DESC LIMIT 20")->fetchAll();

include 'includes/header.php';
?>

<div class="page-layout">
    <div class="content-main admin-page">
        <div class="page-header">
            <h1><i class="fas fa-shield-halved"></i> Yönetim Paneli</h1>
        </div>

        <!-- Admin tabs -->
        <div class="admin-tabs">
            <a href="admin.php?tab=dashboard" class="admin-tab <?= $tab === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Genel Bakış
            </a>
            <a href="admin.php?tab=users" class="admin-tab <?= $tab === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Kullanıcılar
            </a>
            <a href="admin.php?tab=topics" class="admin-tab <?= $tab === 'topics' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Başlıklar
            </a>
            <a href="admin.php?tab=comments" class="admin-tab <?= $tab === 'comments' ? 'active' : '' ?>">
                <i class="fas fa-message"></i> Yorumlar
            </a>
        </div>

        <?php if ($tab === 'dashboard'): ?>
        <!-- Dashboard -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalUsers ?></span>
                    <span class="stat-label">Toplam Kullanıcı</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-comments"></i>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalTopics ?></span>
                    <span class="stat-label">Toplam Başlık</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-message"></i>
                <div class="stat-info">
                    <span class="stat-number"><?= $totalComments ?></span>
                    <span class="stat-label">Toplam Yorum</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-circle online-dot"></i>
                <div class="stat-info">
                    <span class="stat-number"><?= $onlineUsers ?></span>
                    <span class="stat-label">Çevrimiçi</span>
                </div>
            </div>
        </div>

        <!-- Recent activity -->
        <h3 class="section-title"><i class="fas fa-clock"></i> Son Yorumlar</h3>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Kullanıcı</th><th>Başlık</th><th>Yorum</th><th>Tarih</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($recentComments, 0, 10) as $c): ?>
                    <tr>
                        <td><?= e($c['display_name'] ?? $c['username']) ?></td>
                        <td><a href="topic.php?id=<?= $c['topic_id'] ?>"><?= e(mb_substr($c['topic_title'], 0, 30)) ?>...</a></td>
                        <td><?= e(mb_substr($c['content'], 0, 50)) ?>...</td>
                        <td><?= timeAgo($c['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($tab === 'users'): ?>
        <!-- Users management -->
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Avatar</th><th>Kullanıcı</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son Görülme</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr class="<?= $u['is_blocked'] ? 'blocked-row' : '' ?>">
                        <td><img src="<?= e(getAvatar($u['avatar'])) ?>" alt="" class="table-avatar"></td>
                        <td><a href="profile.php?id=<?= $u['id'] ?>"><?= e($u['display_name'] ?? $u['username']) ?></a></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Yönetici' : 'Kullanıcı' ?></span></td>
                        <td><?= $u['is_blocked'] ? '<span class="status-blocked">Engelli</span>' : '<span class="status-active">Aktif</span>' ?></td>
                        <td><?= timeAgo($u['last_seen']) ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="tab" value="users">
                                <?php if ($u['is_blocked']): ?>
                                    <input type="hidden" name="action" value="unblock_user">
                                    <button class="btn btn-sm btn-success" title="Engeli Kaldır"><i class="fas fa-unlock"></i></button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="block_user">
                                    <button class="btn btn-sm btn-danger" title="Engelle"><i class="fas fa-ban"></i></button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($tab === 'topics'): ?>
        <!-- Topics management -->
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Başlık</th><th>Oluşturan</th><th>Görüntülenme</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach ($topics as $t): ?>
                    <tr>
                        <td><a href="topic.php?id=<?= $t['id'] ?>"><?= e($t['title']) ?></a></td>
                        <td><?= e($t['display_name'] ?? $t['username']) ?></td>
                        <td><?= $t['views'] ?></td>
                        <td><?= timeAgo($t['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Bu başlığı silmek istediğinize emin misiniz?')">
                                <input type="hidden" name="action" value="delete_topic">
                                <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="tab" value="topics">
                                <button class="btn btn-sm btn-danger" title="Sil"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($tab === 'comments'): ?>
        <!-- Comments management -->
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Kullanıcı</th><th>Başlık</th><th>Yorum</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach ($recentComments as $c): ?>
                    <tr class="<?= $c['is_hidden'] ? 'hidden-row' : '' ?>">
                        <td><?= e($c['display_name'] ?? $c['username']) ?></td>
                        <td><a href="topic.php?id=<?= $c['topic_id'] ?>"><?= e(mb_substr($c['topic_title'], 0, 20)) ?></a></td>
                        <td><?= e(mb_substr($c['content'], 0, 60)) ?></td>
                        <td><?= $c['is_hidden'] ? '<span class="status-blocked">Gizli</span>' : '<span class="status-active">Görünür</span>' ?></td>
                        <td><?= timeAgo($c['created_at']) ?></td>
                        <td class="action-cell">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="tab" value="comments">
                                <?php if ($c['is_hidden']): ?>
                                    <input type="hidden" name="action" value="show_comment">
                                    <button class="btn btn-sm btn-success" title="Göster"><i class="fas fa-eye"></i></button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="hide_comment">
                                    <button class="btn btn-sm btn-warning" title="Gizle"><i class="fas fa-eye-slash"></i></button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="tab" value="comments">
                                <button class="btn btn-sm btn-danger" title="Sil"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
