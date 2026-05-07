<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('agent_bookings_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];
        $newStatus = $_POST['new_status'];
        if (in_array($newStatus, ['confirmed', 'cancelled'])) {
            $stmt = $pdo->prepare(
                "UPDATE bookings b JOIN packages p ON b.package_id = p.id
                 SET b.status = ? WHERE b.id = ? AND p.agent_id = ?"
            );
            $stmt->execute([$newStatus, $bookingId, $agentId]);

            if ($newStatus === 'cancelled') {
                $stmt = $pdo->prepare("SELECT package_id, num_people FROM bookings WHERE id = ?");
                $stmt->execute([$bookingId]);
                $bk = $stmt->fetch();
                if ($bk) {
                    $stmt = $pdo->prepare("UPDATE packages SET remaining_slots = remaining_slots + ? WHERE id = ?");
                    $stmt->execute([$bk['num_people'], $bk['package_id']]);
                }
            }

            setFlash("Booking status updated.");
            redirect(BASE_URL . '/agent/bookings.php');
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT b.*, p.title, p.destination, u.full_name as customer_name, u.phone as customer_phone
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        JOIN users u ON b.customer_id = u.id
        WHERE p.agent_id = ?";
$params = [$agentId];

if (in_array($statusFilter, ['pending', 'confirmed', 'cancelled'])) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('agent_bookings_title') ?></h2>
        <a href="<?= BASE_URL ?>/agent/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="filter-tabs">
        <a href="?status=" class="tab <?= $statusFilter === '' ? 'active' : '' ?>"><?= t('filter_all') ?></a>
        <a href="?status=pending" class="tab <?= $statusFilter === 'pending' ? 'active' : '' ?>"><?= tStatus('pending') ?></a>
        <a href="?status=confirmed" class="tab <?= $statusFilter === 'confirmed' ? 'active' : '' ?>"><?= tStatus('confirmed') ?></a>
        <a href="?status=cancelled" class="tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>"><?= tStatus('cancelled') ?></a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state"><p><?= t('no_bookings_found') ?></p></div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= t('col_id') ?></th>
                    <th><?= t('col_customer') ?></th>
                    <th><?= t('col_phone') ?></th>
                    <th><?= t('col_package') ?></th>
                    <th><?= t('col_travel_date') ?></th>
                    <th><?= t('col_people') ?></th>
                    <th><?= t('col_total') ?></th>
                    <th><?= t('col_payment_ref') ?></th>
                    <th><?= t('col_status') ?></th>
                    <th><?= t('col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><?= sanitize($b['customer_name']) ?></td>
                    <td><?= sanitize($b['customer_phone']) ?></td>
                    <td><?= sanitize($b['title']) ?></td>
                    <td><?= formatDate($b['travel_date']) ?></td>
                    <td><?= $b['num_people'] ?></td>
                    <td><?= formatPrice($b['total_price']) ?></td>
                    <td><code><?= sanitize($b['payment_reference']) ?></code></td>
                    <td><span class="status-badge status-<?= $b['status'] ?>"><?= tStatus($b['status']) ?></span></td>
                    <td class="actions-cell">
                        <?php if ($b['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="new_status" value="confirmed">
                                <button type="submit" class="btn btn-sm btn-success"><?= t('btn_confirm') ?></button>
                            </form>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="new_status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('confirm_cancel_booking') ?>')"><?= t('btn_cancel') ?></button>
                            </form>
                        <?php elseif ($b['status'] === 'confirmed'): ?>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <input type="hidden" name="new_status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?= t('confirm_cancel_booking') ?>')"><?= t('btn_cancel') ?></button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
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
