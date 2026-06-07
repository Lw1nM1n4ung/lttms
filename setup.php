<?php
$pageTitle = 'Setup';
$setupMode = true;
require_once __DIR__ . '/config/database.php';

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$messages = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = $_POST['db_pass'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        $messages[] = ['success', 'Database created and seeded successfully!'];
        $messages[] = ['success', 'Tables: users, packages, bookings, destinations'];
        $messages[] = ['success', 'Demo data loaded with real Myanmar destinations and prices.'];
        $success = true;
    } catch (PDOException $e) {
        $messages[] = ['error', 'Database error: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTTMS Setup</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="container container-sm" style="padding-top: 3rem;">
    <div class="card">
        <h1 style="text-align:center; margin-bottom: 0.5rem;">LTTMS Setup</h1>
        <p style="text-align:center; color: var(--gray-500); margin-bottom: 2rem;">Local Tourism and Travel Management System</p>

        <?php foreach ($messages as $msg): ?>
            <div class="alert <?= $msg[0] === 'error' ? 'alert-error' : '' ?>">
                <p><?= htmlspecialchars($msg[1]) ?></p>
            </div>
        <?php endforeach; ?>

        <?php if ($success): ?>
            <div style="text-align: center; margin-top: 1.5rem;">
                <h3>Setup Complete!</h3>
                <p style="margin: 1rem 0;">Demo accounts:</p>
                <div class="demo-credentials">
                    <p><strong>Admin:</strong> admin / password</p>
                    <p><strong>Agent:</strong> agent1 / password</p>
                </div>
                <p style="margin-top: 1rem; color: var(--gray-500); font-size: 0.85rem;">Update config/database.php if your DB credentials differ from defaults.</p>
                <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-lg" style="margin-top: 1.5rem;">Launch LTTMS</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" name="db_host" id="db_host" class="form-control" value="<?= htmlspecialchars($dbHost) ?>">
                </div>
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" name="db_user" id="db_user" class="form-control" value="<?= htmlspecialchars($dbUser) ?>">
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" name="db_pass" id="db_pass" class="form-control" value="">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Install Database</button>
            </form>

            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                <h4>Prerequisites</h4>
                <ul style="margin-left: 1.5rem; color: var(--gray-500); font-size: 0.9rem;">
                    <li>PHP 7.4+ with PDO MySQL extension</li>
                    <li>MySQL 5.7+ or MariaDB 10.3+</li>
                    <li>Web server (Apache or PHP built-in server)</li>
                </ul>
                <h4 style="margin-top: 1rem;">Quick Start</h4>
                <code style="display: block; background: var(--gray-100); padding: 1rem; border-radius: var(--radius); font-size: 0.85rem;">
                    cd /path/to/lttms &amp;&amp; php -S localhost:8000
                </code>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--gray-500);">Then open http://localhost:8000/setup.php</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
