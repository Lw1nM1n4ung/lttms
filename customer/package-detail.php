<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$package = getPackageById($id);
if (!$package) {
    setFlash('Package not found.');
    redirect(BASE_URL . '/customer/packages.php');
}

$pageTitle = $package['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <a href="<?= BASE_URL ?>/customer/packages.php" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_packages') ?></a>

    <div class="detail-grid">
        <div class="detail-main">
            <div class="package-image-lg">
                <?php if (!empty($package['image_url'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/packages/<?= sanitize($package['image_url']) ?>" alt="<?= sanitize($package['title']) ?>" onerror="this.style.display='none'">
                <?php endif; ?>
            </div>

            <h1><?= sanitize($package['title']) ?></h1>
            <p class="package-destination-lg">&#128205; <?= sanitize($package['destination']) ?></p>

            <div class="detail-badges">
                <span class="badge">&#128197; <?= formatDuration($package['duration_days']) ?></span>
                <?php if ($range = formatDateRange($package['start_date'] ?? null, $package['end_date'] ?? null)): ?>
                    <span class="badge">&#128197; <?= $range ?></span>
                <?php endif; ?>
                <span class="badge"><?= $package['remaining_slots'] ?> <?= t('slots_available') ?></span>
                <span class="badge"><?= sanitize($package['agent_name']) ?></span>
            </div>

            <div class="detail-section">
                <h2><?= t('pkg_description') ?></h2>
                <p><?= nl2br(sanitize($package['description'])) ?></p>
            </div>

            <?php if ($package['hotel_name']): ?>
            <div class="detail-section">
                <h2>&#127976; <?= t('hotel_info') ?></h2>
                <p><strong><?= sanitize($package['hotel_name']) ?></strong></p>
                <p><?= sanitize($package['hotel_location']) ?></p>
                <p><?= formatPrice($package['price_per_night']) ?> / night</p>
            </div>
            <?php endif; ?>

            <?php if ($package['transport_type']): ?>
            <div class="detail-section">
                <h2>&#128652; <?= t('transport_info') ?></h2>
                <p><strong><?= sanitize($package['transport_type']) ?></strong> — <?= sanitize($package['company_name']) ?></p>
                <p><?= formatPrice($package['transport_price']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-sidebar">
            <div class="booking-card">
                <h2><?= t('book_now') ?></h2>
                <div class="price-display">
                    <span class="price-big"><?= formatPrice($package['price_per_person']) ?></span>
                    <span class="price-label"><?= t('per_person') ?></span>
                </div>

                <?php if (isLoggedIn() && $currentUser['role'] === 'customer'): ?>
                    <?php if ($package['remaining_slots'] > 0): ?>
                    <form method="GET" action="<?= BASE_URL ?>/customer/booking.php">
                        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                        <div class="form-group">
                            <label for="num_people"><?= t('num_people') ?></label>
                            <input type="number" id="num_people" name="num_people" class="form-control"
                                   min="1" max="<?= $package['remaining_slots'] ?>" value="1"
                                   data-price="<?= $package['price_per_person'] ?>">
                        </div>
                        <div class="total-display">
                            <span><?= t('total_price') ?>:</span>
                            <span id="totalPrice"><?= formatPrice($package['price_per_person']) ?></span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg"><?= t('submit_booking') ?></button>
                    </form>
                    <?php else: ?>
                        <p class="text-center text-muted"><?= t('no_packages') ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center"><a href="<?= BASE_URL ?>/login.php"><?= t('login_to_book') ?></a></p>
                <?php endif; ?>

                <div class="payment-info">
                    <h4><?= t('payment_title') ?></h4>
                    <p>&#128179; KBZ Pay</p>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
