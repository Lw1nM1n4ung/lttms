<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('my_packages_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$stmt = $pdo->prepare(
    "SELECT p.*, h.name as hotel_name, t.type as transport_type
     FROM packages p
     LEFT JOIN hotels h ON p.hotel_id = h.id
     LEFT JOIN transportation t ON p.transportation_id = t.id
     WHERE p.agent_id = ?
     ORDER BY p.created_at DESC"
);
$stmt->execute([$currentUser['id']]);
$packages = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ? AND agent_id = ?");
        $stmt->execute([(int)$_POST['delete_id'], $currentUser['id']]);
        setFlash('Package deleted.');
        redirect(BASE_URL . '/agent/packages.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('my_packages_title') ?></h2>
        <a href="<?= BASE_URL ?>/agent/add-package.php" class="btn btn-primary">+ <?= t('nav_add_package') ?></a>
    </div>

    <?php if (empty($packages)): ?>
        <div class="empty-state">
            <p><?= t('no_packages_yet') ?></p>
            <a href="<?= BASE_URL ?>/agent/add-package.php" class="btn btn-primary"><?= t('add_new_package') ?></a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('col_title') ?></th>
                    <th><?= t('col_destination') ?></th>
                    <th><?= t('col_price') ?></th>
                    <th><?= t('duration') ?></th>
                    <th><?= t('col_dates') ?></th>
                    <th><?= t('col_slots') ?></th>
                    <th><?= t('col_status') ?></th>
                    <th><?= t('col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($packages as $pkg): ?>
                <tr>
                    <td><?= sanitize($pkg['title']) ?></td>
                    <td><?= sanitize($pkg['destination']) ?></td>
                    <td><?= formatPrice($pkg['price_per_person']) ?></td>
                    <td><?= formatDuration($pkg['duration_days']) ?></td>
                    <td><?= sanitize(formatDateRange($pkg['start_date'] ?? null, $pkg['end_date'] ?? null)) ?></td>
                    <td><?= $pkg['remaining_slots'] ?> / <?= $pkg['max_slots'] ?></td>
                    <td><span class="status-badge status-<?= $pkg['status'] ?>"><?= tStatus($pkg['status']) ?></span></td>
                    <td class="actions-cell">
                        <a href="<?= BASE_URL ?>/agent/edit-package.php?id=<?= $pkg['id'] ?>" class="btn btn-sm btn-outline"><?= t('btn_edit') ?></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete_package') ?>')">
                            <?= csrfField() ?>
                            <input type="hidden" name="delete_id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><?= t('btn_delete') ?></button>
                        </form>
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
