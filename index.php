<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = t('hero_title');
require_once __DIR__ . '/includes/header.php';

$packages = getPackages();
$topPackages = array_slice($packages, 0, 4);
$destinations = getActiveDestinations();
?>

<section class="hero">
    <div class="container">
        <h1><?= t('hero_title') ?></h1>
        <p><?= t('hero_subtitle') ?></p>
        <a href="<?= BASE_URL ?>/customer/packages.php" class="btn btn-primary btn-lg"><?= t('hero_cta') ?></a>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title"><?= t('feature_curated') ?></h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">&#127759;</div>
                <h3><?= t('feature_curated') ?></h3>
                <p><?= t('feature_curated_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128176;</div>
                <h3><?= t('feature_prices') ?></h3>
                <p><?= t('feature_prices_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128274;</div>
                <h3><?= t('feature_secure') ?></h3>
                <p><?= t('feature_secure_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#11088;</div>
                <h3><?= t('feature_expertise') ?></h3>
                <p><?= t('feature_expertise_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <h2 class="section-title"><?= t('top_packages') ?></h2>
        <div class="packages-grid">
            <?php foreach ($topPackages as $pkg): ?>
            <div class="package-card">
                <div class="package-image">
                    <?php if (!empty($pkg['image_url'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/packages/<?= sanitize($pkg['image_url']) ?>" alt="<?= sanitize($pkg['title']) ?>" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <span class="package-duration"><?= formatDuration($pkg['duration_days']) ?></span>
                    <?php if ($range = formatDateRange($pkg['start_date'] ?? null, $pkg['end_date'] ?? null)): ?>
                        <span class="badge">&#128197; <?= $range ?></span>
                    <?php endif; ?>
                </div>
                <div class="package-body">
                    <h3><?= sanitize($pkg['title']) ?></h3>
                    <p class="package-destination">&#128205; <?= sanitize($pkg['destination']) ?></p>
                    <div class="package-meta">
                        <span class="package-slots"><?= $pkg['remaining_slots'] ?> <?= t('remaining_slots') ?></span>
                    </div>
                    <div class="package-footer">
                        <span class="package-price"><?= formatPrice($pkg['price_per_person']) ?></span>
                        <span class="price-label"><?= t('per_person') ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/customer/package-detail.php?id=<?= $pkg['id'] ?>" class="btn btn-primary btn-block"><?= t('view_details') ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>/customer/packages.php" class="btn btn-outline btn-lg"><?= t('view_all_packages') ?></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title"><?= t('popular_destinations') ?></h2>
        <div class="destinations-grid">
            <?php foreach ($destinations as $dest): ?>
            <div class="dest-card<?= !empty($dest['image']) ? ' has-image' : '' ?>"<?php if (!empty($dest['image'])): ?> style="background-image:url('<?= BASE_URL ?>/uploads/destinations/<?= sanitize($dest['image']) ?>')"<?php endif; ?>>
                <div class="dest-overlay">
                    <h3><?= sanitize($dest['name']) ?></h3>
                    <?php if ($dest['tagline']): ?>
                        <p><?= sanitize($dest['tagline']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
