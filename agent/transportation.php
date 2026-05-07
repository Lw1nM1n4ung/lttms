<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('transport_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid form submission.');
        redirect(BASE_URL . '/agent/transportation.php');
    }

    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM transportation WHERE id = ? AND agent_id = ?");
        $stmt->execute([(int)$_POST['delete_id'], $agentId]);
        setFlash(t('transport_deleted_msg'));
        redirect(BASE_URL . '/agent/transportation.php');
    }

    $type         = trim($_POST['type'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = (float)($_POST['price'] ?? 0);

    $errors = [];
    if (empty($type)) $errors[] = t('err_transport_type');
    if (empty($company_name)) $errors[] = t('err_transport_company');
    if ($price < 1000) $errors[] = t('err_transport_price');

    if (empty($errors)) {
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            $stmt = $pdo->prepare("UPDATE transportation SET type=?, company_name=?, description=?, price=? WHERE id=? AND agent_id=?");
            $stmt->execute([$type, $company_name, $description, $price, (int)$_POST['edit_id'], $agentId]);
            setFlash(t('transport_updated_msg'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO transportation (agent_id, type, company_name, description, price) VALUES (?,?,?,?,?)");
            $stmt->execute([$agentId, $type, $company_name, $description, $price]);
            setFlash(t('transport_added_msg'));
        }
        redirect(BASE_URL . '/agent/transportation.php');
    }
}

$stmt = $pdo->prepare("SELECT * FROM transportation WHERE agent_id = ? ORDER BY type, company_name");
$stmt->execute([$agentId]);
$transports = $stmt->fetchAll();

$editTransport = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM transportation WHERE id = ? AND agent_id = ?");
    $stmt->execute([(int)$_GET['edit'], $agentId]);
    $editTransport = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('transport_title') ?></h2>
        <a href="<?= BASE_URL ?>/agent/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="grid-2col">
        <div>
            <div class="card">
                <h3><?= $editTransport ? t('edit_transport') : t('add_transport') ?></h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <?php if ($editTransport): ?>
                        <input type="hidden" name="edit_id" value="<?= $editTransport['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="type"><?= t('transport_type_label') ?></label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="">— <?= t('select_type') ?> —</option>
                            <?php
                            $types = ['VIP Bus', 'Express Bus', 'Domestic Flight', 'Private Car', 'Shared Minivan', 'Boat', 'Train'];
                            foreach ($types as $tp):
                                $selected = ($editTransport['type'] ?? ($_POST['type'] ?? '')) === $tp ? 'selected' : '';
                            ?>
                                <option value="<?= $tp ?>" <?= $selected ?>><?= $tp ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="company_name"><?= t('transport_company') ?></label>
                        <input type="text" name="company_name" id="company_name" class="form-control" value="<?= sanitize($editTransport['company_name'] ?? ($_POST['company_name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description"><?= t('transport_description') ?></label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?= sanitize($editTransport['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price"><?= t('transport_price') ?></label>
                        <input type="number" name="price" id="price" class="form-control" min="1000" step="1000" value="<?= (int)($editTransport['price'] ?? ($_POST['price'] ?? 0)) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= $editTransport ? t('transport_update') : t('transport_save') ?></button>
                    <?php if ($editTransport): ?>
                        <a href="<?= BASE_URL ?>/agent/transportation.php" class="btn btn-outline btn-block"><?= t('btn_cancel') ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div>
            <h3><?= t('nav_transport') ?> (<?= count($transports) ?>)</h3>
            <?php if (empty($transports)): ?>
                <p class="text-muted"><?= t('no_transports') ?></p>
            <?php else: ?>
                <?php foreach ($transports as $tr): ?>
                <div class="card card-compact">
                    <h4><?= sanitize($tr['type']) ?></h4>
                    <p class="text-muted"><?= sanitize($tr['company_name']) ?></p>
                    <?php if ($tr['description']): ?>
                        <p class="text-sm"><?= sanitize(substr($tr['description'], 0, 100)) ?></p>
                    <?php endif; ?>
                    <p><?= formatPrice($tr['price']) ?></p>
                    <div class="actions-cell">
                        <a href="?edit=<?= $tr['id'] ?>" class="btn btn-sm btn-outline"><?= t('btn_edit') ?></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete_transport') ?>')">
                            <?= csrfField() ?>
                            <input type="hidden" name="delete_id" value="<?= $tr['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><?= t('btn_delete') ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
