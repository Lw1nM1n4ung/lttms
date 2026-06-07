<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireApproved();

if ($currentUser['role'] !== 'customer') redirect(BASE_URL . '/');

$package_id = (int)($_GET['package_id'] ?? $_POST['package_id'] ?? 0);
$num_people = (int)($_GET['num_people'] ?? $_POST['num_people'] ?? 1);

$package = getPackageById($package_id);
if (!$package) {
    setFlash('Package not found.');
    redirect(BASE_URL . '/customer/packages.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $num_people  = (int)($_POST['num_people'] ?? 1);
        $payment_ref = trim($_POST['payment_reference'] ?? '');

        if ($num_people < 1 || $num_people > $package['remaining_slots']) {
            $errors[] = 'Invalid number of people. Maximum available: ' . $package['remaining_slots'];
        }
        if (empty($payment_ref)) {
            $errors[] = 'Please enter your KBZ Pay transaction reference.';
        }

        if (empty($errors)) {
            $total_price = $package['price_per_person'] * $num_people;
            $pdo = getDBConnection();

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO bookings (customer_id, package_id, num_people, total_price, booking_date, payment_method, payment_reference)
                 VALUES (?, ?, ?, ?, CURDATE(), 'KBZ Pay', ?)"
            );
            $stmt->execute([$currentUser['id'], $package_id, $num_people, $total_price, $payment_ref]);

            $stmt = $pdo->prepare("UPDATE packages SET remaining_slots = remaining_slots - ? WHERE id = ? AND remaining_slots >= ?");
            $stmt->execute([$num_people, $package_id, $num_people]);

            $pdo->commit();

            setFlash('Booking submitted successfully!');
            redirect(BASE_URL . '/customer/my-bookings.php');
        }
    }
}

$total_price = $package['price_per_person'] * $num_people;
$pageTitle = t('booking_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-md">
    <a href="<?= BASE_URL ?>/customer/package-detail.php?id=<?= $package['id'] ?>" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_package') ?></a>

    <div class="card">
        <h2><?= t('booking_title') ?></h2>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p><?= sanitize($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="booking-summary">
            <h3><?= sanitize($package['title']) ?></h3>
            <p>&#128205; <?= sanitize($package['destination']) ?> &mdash; <?= formatDuration($package['duration_days']) ?></p>
            <?php if ($range = formatDateRange($package['start_date'] ?? null, $package['end_date'] ?? null)): ?>
                <p>&#128197; <?= $range ?></p>
            <?php endif; ?>
            <?php if ($package['hotel_name']): ?>
                <p>&#127976; <?= sanitize($package['hotel_name']) ?></p>
            <?php endif; ?>
            <?php if ($package['transport_type']): ?>
                <p>&#128652; <?= sanitize($package['transport_type']) ?> (<?= sanitize($package['company_name']) ?>)</p>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="package_id" value="<?= $package['id'] ?>">

            <div class="form-group">
                <label for="num_people"><?= t('num_people') ?></label>
                <input type="number" name="num_people" id="num_people" class="form-control"
                       min="1" max="<?= $package['remaining_slots'] ?>" value="<?= $num_people ?>"
                       data-price="<?= $package['price_per_person'] ?>" required>
            </div>

            <div class="price-breakdown">
                <div class="price-row">
                    <span><?= t('price_per_person') ?>:</span>
                    <span><?= formatPrice($package['price_per_person']) ?></span>
                </div>
                <div class="price-row">
                    <span><?= t('num_people') ?>:</span>
                    <span id="displayPeople"><?= $num_people ?></span>
                </div>
                <div class="price-row price-total">
                    <span><?= t('total_price') ?>:</span>
                    <span id="bookingTotal"><?= formatPrice($total_price) ?></span>
                </div>
            </div>

            <div class="payment-section">
                <h3>&#128179; <?= t('payment_title') ?></h3>
                <div class="payment-instructions">
                    <p><strong><?= t('payment_transfer') ?></strong></p>
                    <?php if (!empty($package['agent_kbz_phone'])): ?>
                        <p><?= t('payment_account') ?>: <strong><?= sanitize($package['agent_kbz_phone']) ?></strong></p>
                        <p><?= t('payment_name') ?>: <strong><?= sanitize($package['agent_kbz_name'] ?: $package['agent_name']) ?></strong></p>
                    <?php else: ?>
                        <p class="text-muted"><?= t('agent_no_kbz') ?></p>
                    <?php endif; ?>
                    <p><?= t('payment_instruction') ?></p>
                </div>

                <div class="form-group">
                    <label for="payment_reference"><?= t('payment_ref') ?></label>
                    <input type="text" name="payment_reference" id="payment_reference" class="form-control"
                           placeholder="<?= t('payment_ref_placeholder') ?>" value="<?= sanitize($_POST['payment_reference'] ?? '') ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg"><?= t('submit_booking') ?></button>
            <p class="text-center text-sm mt-1"><?= t('booking_pending_note') ?></p>
        </form>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
