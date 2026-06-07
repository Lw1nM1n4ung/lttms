<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('my_bookings_title');
requireLogin();
requireApproved();

$pdo = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT b.*, p.title, p.destination, p.duration_days, p.image_url
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     WHERE b.customer_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute([$currentUser['id']]);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <h2 class="section-title"><?= t('my_bookings_title') ?></h2>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <p><?= t('no_bookings') ?></p>
            <a href="<?= BASE_URL ?>/customer/packages.php" class="btn btn-primary"><?= t('browse_packages_cta') ?></a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('col_package') ?></th>
                    <th><?= t('col_destination') ?></th>
                    <th><?= t('col_travel_date') ?></th>
                    <th><?= t('col_people') ?></th>
                    <th><?= t('col_total') ?></th>
                    <th><?= t('col_status') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= sanitize($b['title']) ?></td>
                    <td><?= sanitize($b['destination']) ?></td>
                    <td><?= formatDate($b['travel_date']) ?></td>
                    <td><?= $b['num_people'] ?></td>
                    <td><?= formatPrice($b['total_price']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $b['status'] ?>">
                            <?= tStatus($b['status']) ?>
                        </span>
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
