<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('admin_packages_title');
requireRole('admin');

$pdo = getDBConnection();

$statusFilter = $_GET['status'] ?? '';
$agentFilter = $_GET['agent'] ?? '';

$sql = "SELECT p.*, u.full_name as agent_name, h.name as hotel_name, t.type as transport_type,
               (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.id) as booking_count
        FROM packages p
        JOIN users u ON p.agent_id = u.id
        LEFT JOIN hotels h ON p.hotel_id = h.id
        LEFT JOIN transportation t ON p.transportation_id = t.id
        WHERE 1=1";
$params = [];

if (in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}
if (!empty($agentFilter)) {
    $sql .= " AND p.agent_id = ?";
    $params[] = (int)$agentFilter;
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'agent' ORDER BY full_name");
$agents = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('admin_packages_title') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="filter-tabs">
        <a href="?status=&agent=<?= sanitize($agentFilter) ?>" class="tab <?= $statusFilter === '' ? 'active' : '' ?>"><?= t('filter_all') ?></a>
        <a href="?status=active&agent=<?= sanitize($agentFilter) ?>" class="tab <?= $statusFilter === 'active' ? 'active' : '' ?>"><?= tStatus('active') ?></a>
        <a href="?status=inactive&agent=<?= sanitize($agentFilter) ?>" class="tab <?= $statusFilter === 'inactive' ? 'active' : '' ?>"><?= tStatus('inactive') ?></a>
    </div>
    <div class="filter-tabs mb-1">
        <a href="?status=<?= sanitize($statusFilter) ?>&agent=" class="tab <?= $agentFilter === '' ? 'active' : '' ?>"><?= t('all_agents') ?></a>
        <?php foreach ($agents as $a): ?>
            <a href="?status=<?= sanitize($statusFilter) ?>&agent=<?= $a['id'] ?>" class="tab <?= $agentFilter == $a['id'] ? 'active' : '' ?>"><?= sanitize($a['full_name']) ?></a>
        <?php endforeach; ?>
    </div>

    <p class="results-count"><?= count($packages) ?> <?= t('packages_count') ?></p>

    <?php if (empty($packages)): ?>
        <div class="empty-state"><p><?= t('no_packages_yet') ?></p></div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= t('col_title') ?></th>
                    <th><?= t('col_destination') ?></th>
                    <th><?= t('col_agent') ?></th>
                    <th><?= t('col_price') ?></th>
                    <th><?= t('duration') ?></th>
                    <th><?= t('col_slots') ?></th>
                    <th><?= t('col_bookings') ?></th>
                    <th><?= t('col_rating_avg') ?></th>
                    <th><?= t('col_status') ?></th>
                    <th><?= t('col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($packages as $pkg): ?>
                <tr>
                    <td><?= $pkg['id'] ?></td>
                    <td><?= sanitize($pkg['title']) ?></td>
                    <td><?= sanitize($pkg['destination']) ?></td>
                    <td><?= sanitize($pkg['agent_name']) ?></td>
                    <td><?= formatPrice($pkg['price_per_person']) ?></td>
                    <td><?= $pkg['duration_days'] ?> <?= t('days') ?></td>
                    <td><?= $pkg['remaining_slots'] ?>/<?= $pkg['max_slots'] ?></td>
                    <td><?= $pkg['booking_count'] ?></td>
                    <td>&#11088; <?= number_format($pkg['rating_avg'], 1) ?></td>
                    <td><span class="status-badge status-<?= $pkg['status'] ?>"><?= tStatus($pkg['status']) ?></span></td>
                    <td class="actions-cell">
                        <a href="<?= BASE_URL ?>/customer/package-detail.php?id=<?= $pkg['id'] ?>" class="btn btn-sm btn-outline" target="_blank">&#128065; <?= t('btn_view') ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
