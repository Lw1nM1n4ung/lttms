<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('agent_dashboard');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM packages WHERE agent_id = ?");
$stmt->execute([$agentId]);
$totalPackages = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings b JOIN packages p ON b.package_id = p.id WHERE p.agent_id = ?");
$stmt->execute([$agentId]);
$totalBookings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings b JOIN packages p ON b.package_id = p.id WHERE p.agent_id = ? AND b.status = 'pending'");
$stmt->execute([$agentId]);
$pendingBookings = $stmt->fetch()['total'];

$stmt = $pdo->prepare(
    "SELECT b.*, p.title, p.destination, u.full_name as customer_name
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     JOIN users u ON b.customer_id = u.id
     WHERE p.agent_id = ?
     ORDER BY b.created_at DESC LIMIT 5"
);
$stmt->execute([$agentId]);
$recentBookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <h2 class="section-title"><?= t('agent_dashboard') ?></h2>
    <p class="text-muted"><?= sanitize($currentUser['full_name']) ?></p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $totalPackages ?></div>
            <div class="stat-label"><?= t('total_packages') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalBookings ?></div>
            <div class="stat-label"><?= t('total_bookings') ?></div>
        </div>
        <div class="stat-card stat-highlight">
            <div class="stat-number"><?= $pendingBookings ?></div>
            <div class="stat-label"><?= t('pending_bookings') ?></div>
        </div>
    </div>

    <div class="dashboard-actions">
        <a href="<?= BASE_URL ?>/agent/packages.php" class="btn btn-primary"><?= t('nav_my_packages') ?></a>
        <a href="<?= BASE_URL ?>/agent/add-package.php" class="btn btn-outline"><?= t('add_new_package') ?></a>
        <a href="<?= BASE_URL ?>/agent/bookings.php" class="btn btn-outline"><?= t('manage_bookings') ?></a>
        <a href="<?= BASE_URL ?>/agent/settings.php" class="btn btn-outline"><?= t('agent_settings') ?></a>
    </div>

    <h3 class="mt-2"><?= t('recent_bookings') ?></h3>
    <?php if (empty($recentBookings)): ?>
        <p class="text-muted"><?= t('no_booking_data') ?></p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('col_customer') ?></th>
                    <th><?= t('col_package') ?></th>
                    <th><?= t('col_travel_date') ?></th>
                    <th><?= t('col_people') ?></th>
                    <th><?= t('col_total') ?></th>
                    <th><?= t('col_status') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
                <tr>
                    <td><?= sanitize($b['customer_name']) ?></td>
                    <td><?= sanitize($b['title']) ?></td>
                    <td><?= formatDate($b['travel_date']) ?></td>
                    <td><?= $b['num_people'] ?></td>
                    <td><?= formatPrice($b['total_price']) ?></td>
                    <td><span class="status-badge status-<?= $b['status'] ?>"><?= tStatus($b['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
