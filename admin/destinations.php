<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('manage_destinations');
requireRole('admin');

$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $image = null;

        if (strlen($name) < 2) {
            $errors[] = t('err_dest_name');
        } else {
            if (!empty($_FILES['image']['name'])) {
                $image = handleDestinationImageUpload($_FILES['image']);
                if (!$image) {
                    $errors[] = t('err_dest_image');
                }
            }
            if (empty($errors)) {
                $stmt = $pdo->prepare("INSERT INTO destinations (name, tagline, image, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $tagline, $image, $sortOrder]);
                setFlash(t('dest_added_msg'));
                redirect(BASE_URL . '/admin/destinations.php');
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['dest_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (strlen($name) < 2) {
            $errors[] = t('err_dest_name');
        } else {
            $image = null;
            if (!empty($_FILES['image']['name'])) {
                $image = handleDestinationImageUpload($_FILES['image']);
                if (!$image) {
                    $errors[] = t('err_dest_image');
                }
            }
            if (empty($errors)) {
                if ($image) {
                    $stmt = $pdo->prepare("UPDATE destinations SET name = ?, tagline = ?, image = ?, sort_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $tagline, $image, $sortOrder, $isActive, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE destinations SET name = ?, tagline = ?, sort_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $tagline, $sortOrder, $isActive, $id]);
                }
                setFlash(t('dest_updated_msg'));
                redirect(BASE_URL . '/admin/destinations.php');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['dest_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $delDest = $stmt->fetch();
        if ($delDest && $delDest['image']) {
            @unlink(__DIR__ . '/../uploads/destinations/' . basename($delDest['image']));
        }
        $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        setFlash(t('dest_deleted_msg'));
        redirect(BASE_URL . '/admin/destinations.php');
    }
}

$destinations = $pdo->query("SELECT * FROM destinations ORDER BY sort_order ASC, name ASC")->fetchAll();
$editDest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editDest = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('manage_destinations') ?></h2>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid-2col">
        <div>
            <h3 class="mb-1"><?= $editDest ? t('edit_destination') : t('add_destination') ?></h3>
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="<?= $editDest ? 'update' : 'add' ?>">
                    <?php if ($editDest): ?>
                        <input type="hidden" name="dest_id" value="<?= $editDest['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name"><?= t('dest_name') ?></label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($editDest['name'] ?? $_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tagline"><?= t('dest_tagline') ?></label>
                        <input type="text" name="tagline" id="tagline" class="form-control" value="<?= sanitize($editDest['tagline'] ?? $_POST['tagline'] ?? '') ?>" placeholder="<?= t('dest_tagline_hint') ?>">
                    </div>

                    <div class="form-group">
                        <label for="dest_image"><?= t('dest_photo') ?></label>
                        <?php if ($editDest && $editDest['image']): ?>
                            <div class="current-image-preview mb-1">
                                <img src="<?= BASE_URL ?>/uploads/destinations/<?= sanitize($editDest['image']) ?>" alt="<?= sanitize($editDest['name']) ?>" style="max-width:200px;max-height:120px;border-radius:8px;display:block;">
                            </div>
                            <small class="text-muted"><?= t('dest_image_change_hint') ?></small>
                        <?php else: ?>
                            <small class="text-muted"><?= t('dest_image_hint') ?></small>
                        <?php endif; ?>
                        <input type="file" name="image" id="dest_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="form-group">
                        <label for="sort_order"><?= t('dest_sort_order') ?></label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= (int)($editDest['sort_order'] ?? $_POST['sort_order'] ?? 0) ?>" min="0">
                    </div>

                    <?php if ($editDest): ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?= $editDest['is_active'] ? 'checked' : '' ?>>
                            <?= t('dest_active') ?>
                        </label>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary"><?= $editDest ? t('btn_save') : t('btn_add') ?></button>
                    <?php if ($editDest): ?>
                        <a href="<?= BASE_URL ?>/admin/destinations.php" class="btn btn-outline"><?= t('btn_cancel_edit') ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div>
            <h3 class="mb-1"><?= t('current_destinations') ?> (<?= count($destinations) ?>)</h3>
            <?php if (empty($destinations)): ?>
                <div class="empty-state"><p><?= t('no_destinations') ?></p></div>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= t('dest_photo') ?></th>
                            <th><?= t('dest_name') ?></th>
                            <th><?= t('col_status') ?></th>
                            <th><?= t('col_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($destinations as $d): ?>
                        <tr>
                            <td><?= $d['sort_order'] ?></td>
                            <td>
                                <?php if ($d['image']): ?>
                                    <img src="<?= BASE_URL ?>/uploads/destinations/<?= sanitize($d['image']) ?>" alt="<?= sanitize($d['name']) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= sanitize($d['name']) ?></strong>
                                <?php if ($d['tagline']): ?>
                                    <br><small class="text-muted"><?= sanitize($d['tagline']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['is_active']): ?>
                                    <span class="status-badge status-active"><?= tStatus('active') ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive"><?= tStatus('inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline"><?= t('btn_edit') ?></a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete_dest') ?>')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="dest_id" value="<?= $d['id'] ?>">
                                    <button class="btn btn-sm btn-danger"><?= t('btn_delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
