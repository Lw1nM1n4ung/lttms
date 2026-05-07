<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('user_detail_title');
requireRole('admin');

$pdo = getDBConnection();
$userId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('User not found.');
    redirect(BASE_URL . '/admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if ($userId === $currentUser['id']) {
        setFlash('You cannot modify your own account.');
        redirect(BASE_URL . '/admin/user-detail.php?id=' . $userId);
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $stmt->execute([$userId]);
        setFlash(t('user_approved_msg'));
    } elseif ($action === 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$userId]);
        setFlash(t('user_suspended_msg'));
    }
    redirect(BASE_URL . '/admin/user-detail.php?id=' . $userId);
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-md">
    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_users') ?></a>

    <div class="card">
        <div class="user-detail-header">
            <div>
                <h2><?= sanitize($user['full_name']) ?></h2>
                <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                <span class="status-badge status-<?= $user['status'] ?>"><?= tStatus($user['status']) ?></span>
            </div>
            <?php if ($userId !== $currentUser['id']): ?>
            <div class="user-detail-actions">
                <?php if ($user['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-success"><?= t('btn_approve') ?></button>
                    </form>
                <?php elseif ($user['status'] === 'suspended'): ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-success"><?= t('btn_reactivate') ?></button>
                    </form>
                <?php else: ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="suspend">
                        <button class="btn btn-outline"><?= t('btn_suspend') ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="user-info-grid">
            <div class="info-item">
                <label><?= t('col_username') ?></label>
                <span><?= sanitize($user['username']) ?></span>
            </div>
            <div class="info-item">
                <label><?= t('col_email') ?></label>
                <span><?= sanitize($user['email']) ?></span>
            </div>
            <div class="info-item">
                <label><?= t('col_phone') ?></label>
                <span><?= sanitize($user['phone'] ?? '—') ?></span>
            </div>
            <div class="info-item">
                <label><?= t('nrc_number_label') ?></label>
                <span><?= sanitize($user['nrc_number'] ?? '—') ?></span>
            </div>
            <div class="info-item">
                <label><?= t('col_joined') ?></label>
                <span><?= formatDate($user['created_at']) ?></span>
            </div>
        </div>

        <?php if ($user['nrc_front_photo'] || $user['nrc_back_photo']): ?>
        <h3 class="mt-2"><?= t('nrc_documents') ?></h3>
        <div class="nrc-preview-grid">
            <?php if ($user['nrc_front_photo']): ?>
            <div class="nrc-preview-card">
                <label><?= t('nrc_front') ?></label>
                <img src="<?= BASE_URL ?>/admin/view-nrc.php?file=<?= urlencode($user['nrc_front_photo']) ?>" alt="NRC Front">
            </div>
            <?php endif; ?>
            <?php if ($user['nrc_back_photo']): ?>
            <div class="nrc-preview-card">
                <label><?= t('nrc_back') ?></label>
                <img src="<?= BASE_URL ?>/admin/view-nrc.php?file=<?= urlencode($user['nrc_back_photo']) ?>" alt="NRC Back">
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-muted mt-2"><?= t('no_nrc_uploaded') ?></p>
        <?php endif; ?>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
