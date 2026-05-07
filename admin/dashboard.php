<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('admin_dashboard');
requireRole('admin');

$stats = getDashboardStats();
$pdo = getDBConnection();

$stmt = $pdo->query(
    "SELECT b.*, p.title, p.destination, u.full_name as customer_name
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     JOIN users u ON b.customer_id = u.id
     ORDER BY b.created_at DESC LIMIT 10"
);
$recentBookings = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$pendingUsers = $stmt->fetch()['total'];

$stmt = $pdo->query(
    "SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count, SUM(total_price) as revenue
     FROM bookings WHERE status = 'confirmed'
     GROUP BY month ORDER BY month DESC LIMIT 6"
);
$monthlyStats = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <h2 class="section-title"><?= t('admin_dashboard') ?></h2>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_bookings'] ?></div>
            <div class="stat-label"><?= t('total_bookings') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= formatPrice($stats['total_revenue']) ?></div>
            <div class="stat-label"><?= t('total_revenue') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_customers'] ?></div>
            <div class="stat-label"><?= t('customers') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_agents'] ?></div>
            <div class="stat-label"><?= t('agents') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number">&#11088; <?= $stats['avg_rating'] ?></div>
            <div class="stat-label"><?= t('avg_rating') ?></div>
        </div>
        <?php if ($pendingUsers > 0): ?>
        <div class="stat-card stat-highlight">
            <div class="stat-number"><?= $pendingUsers ?></div>
            <div class="stat-label"><?= t('pending_approvals') ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-actions">
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-primary"><?= t('manage_users') ?></a>
        <a href="<?= BASE_URL ?>/admin/packages.php" class="btn btn-outline"><?= t('nav_view_packages') ?></a>
        <a href="<?= BASE_URL ?>/admin/destinations.php" class="btn btn-outline"><?= t('manage_destinations') ?></a>
    </div>

    <div class="grid-2col mt-2">
        <div>
            <h3><?= t('popular_destinations') ?></h3>
            <?php if (empty($stats['popular_destinations'])): ?>
                <p class="text-muted"><?= t('no_booking_data') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table">
                    <thead><tr><th><?= t('col_destination') ?></th><th><?= t('col_bookings') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['popular_destinations'] as $d): ?>
                        <tr>
                            <td><?= sanitize($d['destination']) ?></td>
                            <td><?= $d['count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h3><?= t('monthly_revenue') ?></h3>
            <?php if (empty($monthlyStats)): ?>
                <p class="text-muted"><?= t('no_confirmed_data') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table">
                    <thead><tr><th><?= t('col_month') ?></th><th><?= t('col_bookings') ?></th><th><?= t('col_revenue') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($monthlyStats as $m): ?>
                        <tr>
                            <td><?= $m['month'] ?></td>
                            <td><?= $m['count'] ?></td>
                            <td><?= formatPrice($m['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="mt-2"><?= t('recent_bookings') ?></h3>
    <?php if (empty($recentBookings)): ?>
        <p class="text-muted"><?= t('no_booking_data') ?></p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('col_id') ?></th>
                    <th><?= t('col_customer') ?></th>
                    <th><?= t('col_package') ?></th>
                    <th><?= t('col_date') ?></th>
                    <th><?= t('col_total') ?></th>
                    <th><?= t('col_status') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= sanitize($b['customer_name']) ?></td>
                    <td><?= sanitize($b['title']) ?></td>
                    <td><?= formatDate($b['travel_date']) ?></td>
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
