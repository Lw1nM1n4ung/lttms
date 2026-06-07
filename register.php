<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = t('nav_register');

if (isLoggedIn()) redirect(BASE_URL . '/');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $role      = $_POST['role'] ?? 'customer';
        $agent_location = trim($_POST['agent_location'] ?? '');
        $nrc_number = trim($_POST['nrc_number'] ?? '');

        if (strlen($username) < 3) $errors[] = t('err_username_short');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('err_email_invalid');
        if (strlen($full_name) < 2) $errors[] = t('err_fullname_short');
        if (strlen($password) < 6) $errors[] = t('err_password_short');
        if ($password !== $confirm) $errors[] = t('err_password_mismatch');
        if (!in_array($role, ['customer', 'agent'])) $errors[] = t('err_invalid_role');
        if ($role === 'agent' && !in_array($agent_location, getMyanmarLocations())) $errors[] = t('err_agent_location');

        $nrc_front = null;
        $nrc_back = null;

        if ($role === 'agent') {
            if (empty($nrc_number)) $errors[] = t('err_nrc_required');

            if (empty($_FILES['nrc_front']['name'])) {
                $errors[] = t('err_nrc_front_required');
            }
            if (empty($_FILES['nrc_back']['name'])) {
                $errors[] = t('err_nrc_back_required');
            }

            if (empty($errors) && !empty($_FILES['nrc_front']['name'])) {
                $nrc_front = handleNRCUpload($_FILES['nrc_front']);
                if (!$nrc_front) $errors[] = t('err_nrc_front_invalid');
            }
            if (empty($errors) && !empty($_FILES['nrc_back']['name'])) {
                $nrc_back = handleNRCUpload($_FILES['nrc_back']);
                if (!$nrc_back) $errors[] = t('err_nrc_back_invalid');
            }
        }

        if (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = t('err_user_exists');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $loc = ($role === 'agent') ? $agent_location : null;
                $status = ($role === 'agent') ? 'pending' : 'approved';
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password, full_name, phone, nrc_number, nrc_front_photo, nrc_back_photo, role, agent_location, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$username, $email, $hashedPassword, $full_name, $phone, $nrc_number, $nrc_front, $nrc_back, $role, $loc, $status]);
                setFlash(t('register_pending_msg'));
                redirect(BASE_URL . '/login.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
<div class="container container-md">
    <div class="card">
        <h2 class="text-center"><?= t('register_title') ?></h2>
        <p class="text-center text-muted mb-1"><?= t('register_approval_note') ?></p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p><?= sanitize($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="role"><?= t('register_role') ?>:</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="customer" <?= ($_POST['role'] ?? '') === 'customer' ? 'selected' : '' ?>><?= t('register_customer') ?></option>
                    <option value="agent" <?= ($_POST['role'] ?? '') === 'agent' ? 'selected' : '' ?>><?= t('register_agent') ?></option>
                </select>
            </div>

            <div class="form-group" id="locationGroup" style="display:none">
                <label for="agent_location"><?= t('agent_location_label') ?></label>
                <select name="agent_location" id="agent_location" class="form-control">
                    <option value=""><?= t('select_location') ?></option>
                    <?php foreach (getMyanmarLocations() as $loc): ?>
                        <option value="<?= sanitize($loc) ?>" <?= ($_POST['agent_location'] ?? '') === $loc ? 'selected' : '' ?>><?= sanitize($loc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="full_name"><?= t('register_full_name') ?></label>
                <input type="text" name="full_name" id="full_name" class="form-control" value="<?= sanitize($_POST['full_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="username"><?= t('register_username') ?></label>
                    <input type="text" name="username" id="username" class="form-control" value="<?= sanitize($_POST['username'] ?? '') ?>" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="email"><?= t('register_email') ?></label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone"><?= t('register_phone') ?></label>
                <input type="tel" name="phone" id="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>" placeholder="09xxxxxxxxx">
            </div>

            <fieldset class="nrc-fieldset" id="nrcFieldset" style="display:none">
                <legend><?= t('nrc_section_title') ?></legend>

                <div class="form-group">
                    <label for="nrc_number"><?= t('nrc_number_label') ?></label>
                    <input type="text" name="nrc_number" id="nrc_number" class="form-control" value="<?= sanitize($_POST['nrc_number'] ?? '') ?>" placeholder="<?= t('nrc_placeholder') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nrc_front"><?= t('nrc_front') ?></label>
                        <input type="file" name="nrc_front" id="nrc_front" class="form-control file-input" accept="image/jpeg,image/png,image/webp" required>
                        <small><?= t('nrc_file_hint') ?></small>
                    </div>
                    <div class="form-group">
                        <label for="nrc_back"><?= t('nrc_back') ?></label>
                        <input type="file" name="nrc_back" id="nrc_back" class="form-control file-input" accept="image/jpeg,image/png,image/webp" required>
                        <small><?= t('nrc_file_hint') ?></small>
                    </div>
                </div>
            </fieldset>

            <div class="form-row">
                <div class="form-group">
                    <label for="password"><?= t('register_password') ?></label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?= t('register_confirm_password') ?></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('register_submit') ?></button>
        </form>

        <p class="text-center mt-1"><?= t('register_has_account') ?> <a href="<?= BASE_URL ?>/login.php"><?= t('register_login_link') ?></a></p>
    </div>
</div>
</section>

<script>
(function() {
    var role = document.getElementById('role');
    var locGroup = document.getElementById('locationGroup');
    var locSelect = document.getElementById('agent_location');
    var nrcFieldset = document.getElementById('nrcFieldset');
    var nrcNumber = document.getElementById('nrc_number');
    var nrcFront = document.getElementById('nrc_front');
    var nrcBack = document.getElementById('nrc_back');
    function toggle() {
        var isAgent = role.value === 'agent';
        locGroup.style.display = isAgent ? '' : 'none';
        locSelect.required = isAgent;
        nrcFieldset.style.display = isAgent ? '' : 'none';
        nrcNumber.required = isAgent;
        nrcFront.required = isAgent;
        nrcBack.required = isAgent;
    }
    role.addEventListener('change', toggle);
    toggle();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
