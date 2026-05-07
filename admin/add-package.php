<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('admin_add_package');
requireRole('admin');

$pdo = getDBConnection();

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'agent' AND status = 'approved' ORDER BY full_name");
$agents = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, name, location, agent_id FROM hotels ORDER BY name");
$allHotels = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, type, company_name, agent_id FROM transportation ORDER BY type");
$allTransports = $stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $agent_id     = (int)($_POST['agent_id'] ?? 0);
        $title        = trim($_POST['title'] ?? '');
        $destination   = trim($_POST['destination'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $duration      = (int)($_POST['duration_days'] ?? 0);
        $price         = (float)($_POST['price_per_person'] ?? 0);
        $max_slots     = (int)($_POST['max_slots'] ?? 20);
        $hotel_id      = !empty($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : null;
        $transport_id  = !empty($_POST['transportation_id']) ? (int)$_POST['transportation_id'] : null;
        $status        = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($agent_id < 1) $errors[] = t('err_select_agent');
        if (empty($title)) $errors[] = t('err_pkg_title');
        if (empty($destination)) $errors[] = t('err_pkg_destination');
        if ($duration < 1) $errors[] = t('err_pkg_duration');
        if ($price < 1000) $errors[] = t('err_pkg_price');
        if ($max_slots < 1) $errors[] = t('err_pkg_slots');

        $imageFile = null;
        if (!empty($_FILES['package_image']) && $_FILES['package_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageFile = handlePackageImageUpload($_FILES['package_image']);
            if (!$imageFile) {
                $errors[] = t('err_pkg_image');
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO packages (agent_id, title, destination, description, duration_days, price_per_person, max_slots, remaining_slots, hotel_id, transportation_id, status, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$agent_id, $title, $destination, $description, $duration, $price, $max_slots, $max_slots, $hotel_id, $transport_id, $status, $imageFile]);
            setFlash(t('package_created_msg'));
            redirect(BASE_URL . '/admin/packages.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-md">
    <a href="<?= BASE_URL ?>/admin/packages.php" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_admin_packages') ?></a>

    <div class="card">
        <h2><?= t('admin_add_package') ?></h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($agents)): ?>
            <div class="alert alert-error"><p><?= t('no_approved_agents') ?></p></div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="package_image"><?= t('pkg_image') ?></label>
                <input type="file" name="package_image" id="package_image" class="form-control file-input" accept="image/jpeg,image/png,image/webp">
                <small><?= t('pkg_image_hint') ?></small>
            </div>

            <div class="form-group">
                <label for="agent_id"><?= t('assign_to_agent') ?></label>
                <select name="agent_id" id="agent_id" class="form-control" required>
                    <option value="">— <?= t('select_agent') ?> —</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($_POST['agent_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                            <?= sanitize($a['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                    <label for="hotel_id"><?= t('pkg_hotel') ?></label>
                    <select name="hotel_id" id="hotel_id" class="form-control">
                        <option value="">— <?= t('pkg_none') ?> —</option>
                        <?php foreach ($allHotels as $h): ?>
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
                        <?php foreach ($allTransports as $tr): ?>
                            <option value="<?= $tr['id'] ?>" <?= ($_POST['transportation_id'] ?? '') == $tr['id'] ? 'selected' : '' ?>>
                                <?= sanitize($tr['type']) ?> — <?= sanitize($tr['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="status"><?= t('pkg_status') ?></label>
                <select name="status" id="status" class="form-control">
                    <option value="active"><?= tStatus('active') ?></option>
                    <option value="inactive"><?= tStatus('inactive') ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('pkg_save') ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
