<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = t('nav_login');

if (isLoggedIn()) redirect(BASE_URL . '/');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $errors[] = 'Your account has been suspended. Contact admin.';
            } elseif ($user['status'] === 'pending') {
                $errors[] = 'Your account is pending admin approval.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                switch ($user['role']) {
                    case 'admin':  redirect(BASE_URL . '/admin/dashboard.php');
                    case 'agent':  redirect(BASE_URL . '/agent/dashboard.php');
                    default:       redirect(BASE_URL . '/customer/packages.php');
                }
            }
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
<div class="container container-sm">
    <div class="card">
        <h2 class="text-center"><?= t('login_title') ?></h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p><?= sanitize($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username"><?= t('login_username') ?></label>
                <input type="text" name="username" id="username" class="form-control" value="<?= sanitize($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password"><?= t('login_password') ?></label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('login_submit') ?></button>
        </form>

        <p class="text-center mt-1"><?= t('login_no_account') ?> <a href="<?= BASE_URL ?>/register.php"><?= t('login_register_link') ?></a></p>

        <div class="demo-credentials">
            <h4>Demo Accounts</h4>
            <p><strong>Admin:</strong> admin / password</p>
            <p><strong>Agent:</strong> agent1 / password</p>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
