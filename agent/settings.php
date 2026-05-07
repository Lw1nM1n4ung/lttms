<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('agent_settings');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

$stmt = $pdo->prepare("SELECT kbz_phone, kbz_name FROM users WHERE id = ?");
$stmt->execute([$agentId]);
$agentData = $stmt->fetch();

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $kbz_phone = trim($_POST['kbz_phone'] ?? '');
        $kbz_name  = trim($_POST['kbz_name'] ?? '');

        if (!empty($kbz_phone) && !preg_match('/^09\s?\d[\d\s]{6,15}$/', $kbz_phone)) {
            $errors[] = t('err_kbz_phone');
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET kbz_phone = ?, kbz_name = ? WHERE id = ?");
            $stmt->execute([$kbz_phone ?: null, $kbz_name ?: null, $agentId]);
            $agentData['kbz_phone'] = $kbz_phone;
            $agentData['kbz_name']  = $kbz_name;
            setFlash(t('settings_saved_msg'));
            redirect(BASE_URL . '/agent/settings.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-sm">
    <div class="page-header">
        <h2><?= t('agent_settings') ?></h2>
        <a href="<?= BASE_URL ?>/agent/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="card">
        <h3>&#128179; <?= t('kbz_pay_settings') ?></h3>
        <p class="text-muted mb-1"><?= t('kbz_pay_desc') ?></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="kbz_phone"><?= t('kbz_phone_label') ?></label>
                <input type="text" name="kbz_phone" id="kbz_phone" class="form-control"
                       value="<?= sanitize($_POST['kbz_phone'] ?? $agentData['kbz_phone'] ?? '') ?>"
                       placeholder="09 XXX XXX XXX">
            </div>

            <div class="form-group">
                <label for="kbz_name"><?= t('kbz_name_label') ?></label>
                <input type="text" name="kbz_name" id="kbz_name" class="form-control"
                       value="<?= sanitize($_POST['kbz_name'] ?? $agentData['kbz_name'] ?? '') ?>"
                       placeholder="<?= sanitize($currentUser['full_name']) ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('save') ?></button>
        </form>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
