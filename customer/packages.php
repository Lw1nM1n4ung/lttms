<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('packages_title');
require_once __DIR__ . '/../includes/header.php';

$filters = [
    'destination' => $_GET['destination'] ?? '',
    'min_price'   => $_GET['min_price'] ?? '',
    'max_price'   => $_GET['max_price'] ?? '',
    'min_rating'  => $_GET['min_rating'] ?? '',
];
$packages = getPackages($filters);
?>

<section class="section">
<div class="container">
    <h2 class="section-title"><?= t('packages_title') ?></h2>

    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label><?= t('col_destination') ?></label>
            <input type="text" name="destination" class="form-control" value="<?= sanitize($filters['destination']) ?>" placeholder="e.g. Bagan, Inle Lake">
        </div>
        <div class="filter-group">
            <label>Min (MMK)</label>
            <input type="number" name="min_price" class="form-control" value="<?= sanitize($filters['min_price']) ?>" step="10000">
        </div>
        <div class="filter-group">
            <label>Max (MMK)</label>
            <input type="number" name="max_price" class="form-control" value="<?= sanitize($filters['max_price']) ?>" step="10000">
        </div>
        <div class="filter-group">
            <label><?= t('filter_min_rating') ?></label>
            <select name="min_rating" class="form-control">
                <option value=""><?= t('filter_all_ratings') ?></option>
                <option value="3" <?= $filters['min_rating'] === '3' ? 'selected' : '' ?>>3+ <?= t('stars_up') ?></option>
                <option value="4" <?= $filters['min_rating'] === '4' ? 'selected' : '' ?>>4+ <?= t('stars_up') ?></option>
                <option value="4.5" <?= $filters['min_rating'] === '4.5' ? 'selected' : '' ?>>4.5+ <?= t('stars_up') ?></option>
            </select>
        </div>
        <div class="filter-group filter-actions">
            <button type="submit" class="btn btn-primary"><?= t('filter_button') ?></button>
            <a href="<?= BASE_URL ?>/customer/packages.php" class="btn btn-outline">Clear</a>
        </div>
    </form>

    <p class="results-count"><?= count($packages) ?> package(s)</p>

    <?php if (empty($packages)): ?>
        <div class="empty-state">
            <p><?= t('no_packages') ?></p>
        </div>
    <?php else: ?>
        <div class="packages-grid">
        <?php foreach ($packages as $pkg): ?>
            <div class="package-card">
                <div class="package-image">
                    <?php if (!empty($pkg['image_url'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/packages/<?= sanitize($pkg['image_url']) ?>" alt="<?= sanitize($pkg['title']) ?>" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <span class="package-duration"><?= $pkg['duration_days'] ?> <?= t('days') ?></span>
                </div>
                <div class="package-body">
                    <h3><?= sanitize($pkg['title']) ?></h3>
                    <p class="package-destination">&#128205; <?= sanitize($pkg['destination']) ?></p>
                    <p class="package-desc"><?= sanitize(substr($pkg['description'], 0, 120)) ?>...</p>
                    <div class="package-meta">
                        <span class="package-rating"><?= renderStars((float)$pkg['rating_avg']) ?> <?= number_format($pkg['rating_avg'], 1) ?></span>
                        <span class="package-slots"><?= $pkg['remaining_slots'] ?> <?= t('remaining_slots') ?></span>
                    </div>
                    <?php if ($pkg['hotel_name']): ?>
                        <p class="package-hotel">&#127976; <?= sanitize($pkg['hotel_name']) ?></p>
                    <?php endif; ?>
                    <?php if ($pkg['transport_type']): ?>
                        <p class="package-transport">&#128652; <?= sanitize($pkg['transport_type']) ?></p>
                    <?php endif; ?>
                    <div class="package-footer">
                        <span class="package-price"><?= formatPrice($pkg['price_per_person']) ?></span>
                        <span class="price-label"><?= t('per_person') ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/customer/package-detail.php?id=<?= $pkg['id'] ?>" class="btn btn-primary btn-block"><?= t('view_details') ?></a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
