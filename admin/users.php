<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('manage_users');
requireRole('admin');

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId === $currentUser['id']) {
        setFlash('You cannot modify your own account.');
        redirect(BASE_URL . '/admin/users.php');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
                $stmt->execute([$userId]);
                setFlash('User approved.');
                break;
            case 'suspend':
                $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$userId]);
                setFlash('User suspended.');
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                $stmt->execute([$userId, $currentUser['id']]);
                setFlash('User deleted.');
                break;
        }
        redirect(BASE_URL . '/admin/users.php');
    }
}

$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (in_array($roleFilter, ['customer', 'agent', 'admin'])) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
if (in_array($statusFilter, ['pending', 'approved', 'suspended'])) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('manage_users') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="filter-tabs">
        <a href="?role=&status=<?= sanitize($statusFilter) ?>" class="tab <?= $roleFilter === '' ? 'active' : '' ?>"><?= t('all_roles') ?></a>
        <a href="?role=customer&status=<?= sanitize($statusFilter) ?>" class="tab <?= $roleFilter === 'customer' ? 'active' : '' ?>"><?= t('customers') ?></a>
        <a href="?role=agent&status=<?= sanitize($statusFilter) ?>" class="tab <?= $roleFilter === 'agent' ? 'active' : '' ?>"><?= t('agents') ?></a>
        <a href="?role=admin&status=<?= sanitize($statusFilter) ?>" class="tab <?= $roleFilter === 'admin' ? 'active' : '' ?>">Admin</a>
    </div>
    <div class="filter-tabs mb-1">
        <a href="?role=<?= sanitize($roleFilter) ?>&status=" class="tab <?= $statusFilter === '' ? 'active' : '' ?>"><?= t('all_status') ?></a>
        <a href="?role=<?= sanitize($roleFilter) ?>&status=pending" class="tab <?= $statusFilter === 'pending' ? 'active' : '' ?>"><?= tStatus('pending') ?></a>
        <a href="?role=<?= sanitize($roleFilter) ?>&status=approved" class="tab <?= $statusFilter === 'approved' ? 'active' : '' ?>"><?= tStatus('approved') ?></a>
        <a href="?role=<?= sanitize($roleFilter) ?>&status=suspended" class="tab <?= $statusFilter === 'suspended' ? 'active' : '' ?>"><?= tStatus('suspended') ?></a>
    </div>

    <p class="results-count"><?= count($users) ?> <?= t('users_count') ?></p>

    <div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= t('col_name') ?></th>
                <th><?= t('col_username') ?></th>
                <th><?= t('col_email') ?></th>
                <th><?= t('col_phone') ?></th>
                <th><?= t('col_role') ?></th>
                <th><?= t('col_location') ?></th>
                <th><?= t('col_status') ?></th>
                <th><?= t('col_joined') ?></th>
                <th><?= t('col_nrc') ?></th>
                <th><?= t('col_actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= sanitize($u['full_name']) ?></td>
                <td><?= sanitize($u['username']) ?></td>
                <td><?= sanitize($u['email']) ?></td>
                <td><?= sanitize($u['phone'] ?? '—') ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><?= $u['agent_location'] ? sanitize($u['agent_location']) : '<span class="text-muted">—</span>' ?></td>
                <td><span class="status-badge status-<?= $u['status'] ?>"><?= tStatus($u['status']) ?></span></td>
                <td><?= formatDate($u['created_at']) ?></td>
                <td>
                    <?php if ($u['nrc_number']): ?>
                        <a href="<?= BASE_URL ?>/admin/user-detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline"><?= t('btn_view_nrc') ?></a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <?php if ($u['id'] !== $currentUser['id']): ?>
                        <?php if ($u['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn btn-sm btn-success"><?= t('btn_approve') ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if ($u['status'] !== 'suspended'): ?>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button class="btn btn-sm btn-outline"><?= t('btn_suspend') ?></button>
                            </form>
                        <?php elseif ($u['status'] === 'suspended'): ?>
                            <form method="POST" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn btn-sm btn-success"><?= t('btn_reactivate') ?></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete_user') ?>')">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-sm btn-danger"><?= t('btn_delete') ?></button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted"><?= t('label_you') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
