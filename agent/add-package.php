<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('add_package_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

$stmt = $pdo->prepare("SELECT id, name, location FROM hotels WHERE agent_id = ?");
$stmt->execute([$agentId]);
$hotels = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, type, company_name FROM transportation WHERE agent_id = ?");
$stmt->execute([$agentId]);
$transports = $stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $destination  = trim($_POST['destination'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $duration     = (int)($_POST['duration_days'] ?? 0);
        $price        = (float)($_POST['price_per_person'] ?? 0);
        $max_slots    = (int)($_POST['max_slots'] ?? 20);
        $hotel_id     = !empty($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : null;
        $transport_id = !empty($_POST['transportation_id']) ? (int)$_POST['transportation_id'] : null;
        $start_date  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date    = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if (empty($title)) $errors[] = 'Package title is required.';
        if (empty($destination)) $errors[] = 'Destination is required.';
        if ($duration < 1) $errors[] = 'Duration must be at least 1 day.';
        if ($price < 1000) $errors[] = 'Price must be at least 1,000 MMK.';
        if ($max_slots < 1) $errors[] = 'Must have at least 1 slot.';
        if ($start_date && $end_date && $end_date < $start_date) $errors[] = t('err_pkg_end_before_start');

        $imageFile = null;
        if (!empty($_FILES['package_image']) && $_FILES['package_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageFile = handlePackageImageUpload($_FILES['package_image']);
            if (!$imageFile) {
                $errors[] = t('err_pkg_image');
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO packages (agent_id, title, destination, description, duration_days, start_date, end_date, price_per_person, max_slots, remaining_slots, hotel_id, transportation_id, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$agentId, $title, $destination, $description, $duration, $start_date, $end_date, $price, $max_slots, $max_slots, $hotel_id, $transport_id, $imageFile]);
            setFlash('Package created successfully!');
            redirect(BASE_URL . '/agent/packages.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-md">
    <a href="<?= BASE_URL ?>/agent/packages.php" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_my_packages') ?></a>

    <div class="card">
        <h2><?= t('add_package_title') ?></h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="package_image"><?= t('pkg_image') ?></label>
                <input type="file" name="package_image" id="package_image" class="form-control file-input" accept="image/jpeg,image/png,image/webp">
                <small><?= t('pkg_image_hint') ?></small>
            </div>

            <div class="form-group">
                <label for="title"><?= t('pkg_title') ?></label>
                <input type="text" name="title" id="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="destination"><?= t('pkg_destination') ?></label>
                <input type="text" name="destination" id="destination" class="form-control" value="<?= sanitize($_POST['destination'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="description"><?= t('pkg_description') ?></label>
                <textarea name="description" id="description" class="form-control" rows="5"><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="duration_days"><?= t('pkg_duration') ?></label>
                    <input type="number" name="duration_days" id="duration_days" class="form-control" min="1" value="<?= sanitize($_POST['duration_days'] ?? '3') ?>" required>
                </div>
                <div class="form-group">
                    <label for="price_per_person"><?= t('pkg_price') ?></label>
                    <input type="number" name="price_per_person" id="price_per_person" class="form-control" min="1000" step="1000" value="<?= sanitize($_POST['price_per_person'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="max_slots"><?= t('pkg_max_slots') ?></label>
                    <input type="number" name="max_slots" id="max_slots" class="form-control" min="1" value="<?= sanitize($_POST['max_slots'] ?? '20') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date"><?= t('start_date') ?></label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="<?= sanitize($_POST['start_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="end_date"><?= t('end_date') ?></label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= sanitize($_POST['end_date'] ?? '') ?>">
                </div>
            </div>
            <small><?= t('pkg_date_hint') ?></small>

            <div class="form-row">
                <div class="form-group">
                    <label for="hotel_id"><?= t('pkg_hotel') ?></label>
                    <select name="hotel_id" id="hotel_id" class="form-control">
                        <option value="">— <?= t('pkg_none') ?> —</option>
                        <?php foreach ($hotels as $h): ?>
                            <option value="<?= $h['id'] ?>" <?= ($_POST['hotel_id'] ?? '') == $h['id'] ? 'selected' : '' ?>>
                                <?= sanitize($h['name']) ?> (<?= sanitize($h['location']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transportation_id"><?= t('pkg_transport') ?></label>
                    <select name="transportation_id" id="transportation_id" class="form-control">
                        <option value="">— <?= t('pkg_none') ?> —</option>
                        <?php foreach ($transports as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($_POST['transportation_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                <?= sanitize($t['type']) ?> — <?= sanitize($t['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('pkg_save') ?></button>
        </form>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
