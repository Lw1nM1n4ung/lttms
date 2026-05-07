<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('hotels_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid form submission.');
        redirect(BASE_URL . '/agent/hotels.php');
    }

    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ? AND agent_id = ?");
        $stmt->execute([(int)$_POST['delete_id'], $agentId]);
        setFlash('Hotel deleted.');
        redirect(BASE_URL . '/agent/hotels.php');
    }

    $name        = trim($_POST['name'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rating      = (float)($_POST['rating'] ?? 0);
    $price       = (float)($_POST['price_per_night'] ?? 0);

    if (!empty($name) && !empty($location) && $price > 0) {
        if (isset($_POST['edit_id']) && $_POST['edit_id']) {
            $stmt = $pdo->prepare("UPDATE hotels SET name=?, location=?, description=?, rating=?, price_per_night=? WHERE id=? AND agent_id=?");
            $stmt->execute([$name, $location, $description, $rating, $price, (int)$_POST['edit_id'], $agentId]);
            setFlash('Hotel updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO hotels (agent_id, name, location, description, rating, price_per_night) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$agentId, $name, $location, $description, $rating, $price]);
            setFlash('Hotel added.');
        }
        redirect(BASE_URL . '/agent/hotels.php');
    }
}

$stmt = $pdo->prepare("SELECT * FROM hotels WHERE agent_id = ? ORDER BY created_at DESC");
$stmt->execute([$agentId]);
$hotels = $stmt->fetchAll();

$editHotel = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ? AND agent_id = ?");
    $stmt->execute([(int)$_GET['edit'], $agentId]);
    $editHotel = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('hotels_title') ?></h2>
        <a href="<?= BASE_URL ?>/agent/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <div class="grid-2col">
        <div>
            <div class="card">
                <h3><?= $editHotel ? t('edit_hotel') : t('add_hotel') ?></h3>
                <form method="POST">
                    <?= csrfField() ?>
                    <?php if ($editHotel): ?>
                        <input type="hidden" name="edit_id" value="<?= $editHotel['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name"><?= t('hotel_name_label') ?></label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= sanitize($editHotel['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="location"><?= t('hotel_location') ?></label>
                        <input type="text" name="location" id="location" class="form-control" value="<?= sanitize($editHotel['location'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description"><?= t('hotel_description') ?></label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?= sanitize($editHotel['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rating"><?= t('hotel_rating_label') ?> (0-5)</label>
                            <input type="number" name="rating" id="rating" class="form-control" min="0" max="5" step="0.1" value="<?= $editHotel['rating'] ?? '0' ?>">
                        </div>
                        <div class="form-group">
                            <label for="price_per_night"><?= t('col_price') ?> (MMK)</label>
                            <input type="number" name="price_per_night" id="price_per_night" class="form-control" min="1000" step="1000" value="<?= $editHotel['price_per_night'] ?? '' ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= $editHotel ? t('hotel_update') : t('hotel_save') ?></button>
                    <?php if ($editHotel): ?>
                        <a href="<?= BASE_URL ?>/agent/hotels.php" class="btn btn-outline btn-block"><?= t('btn_cancel') ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div>
            <h3><?= t('nav_hotels') ?> (<?= count($hotels) ?>)</h3>
            <?php if (empty($hotels)): ?>
                <p class="text-muted"><?= t('no_hotels') ?></p>
            <?php else: ?>
                <?php foreach ($hotels as $h): ?>
                <div class="card card-compact">
                    <h4><?= sanitize($h['name']) ?></h4>
                    <p class="text-muted">&#128205; <?= sanitize($h['location']) ?></p>
                    <p>&#11088; <?= number_format($h['rating'], 1) ?> &mdash; <?= formatPrice($h['price_per_night']) ?>/night</p>
                    <div class="actions-cell">
                        <a href="?edit=<?= $h['id'] ?>" class="btn btn-sm btn-outline"><?= t('btn_edit') ?></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('<?= t('confirm_delete_hotel') ?>')">
                            <?= csrfField() ?>
                            <input type="hidden" name="delete_id" value="<?= $h['id'] ?>">
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
