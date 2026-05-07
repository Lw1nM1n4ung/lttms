<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('feedback_title');
requireLogin();
requireApproved();

$booking_id = (int)($_GET['booking_id'] ?? 0);
$pdo = getDBConnection();

$stmt = $pdo->prepare(
    "SELECT b.*, p.title, p.destination, p.id as pkg_id
     FROM bookings b JOIN packages p ON b.package_id = p.id
     WHERE b.id = ? AND b.customer_id = ? AND b.status = 'confirmed'"
);
$stmt->execute([$booking_id, $currentUser['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('Booking not found or not eligible for review.');
    redirect(BASE_URL . '/customer/my-bookings.php');
}

$stmt = $pdo->prepare("SELECT id FROM feedback WHERE booking_id = ?");
$stmt->execute([$booking_id]);
if ($stmt->fetch()) {
    setFlash('You have already reviewed this booking.');
    redirect(BASE_URL . '/customer/my-bookings.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) $errors[] = 'Please select a rating between 1 and 5.';
        if (empty($comment)) $errors[] = 'Please write a comment.';

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO feedback (customer_id, package_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$currentUser['id'], $booking['pkg_id'], $booking_id, $rating, $comment]);
            updatePackageRating($booking['pkg_id']);

            setFlash('Thank you for your feedback!');
            redirect(BASE_URL . '/customer/my-bookings.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container container-sm">
    <a href="<?= BASE_URL ?>/customer/my-bookings.php" class="btn btn-outline btn-sm mb-1">&larr; <?= t('back_to_bookings') ?></a>

    <div class="card">
        <h2><?= t('feedback_title') ?></h2>
        <p class="text-muted"><?= sanitize($booking['title']) ?> — <?= sanitize($booking['destination']) ?></p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p><?= sanitize($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="form-group">
                <label><?= t('feedback_rating') ?></label>
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= ($_POST['rating'] ?? 0) == $i ? 'checked' : '' ?>>
                        <label for="star<?= $i ?>">&#9733;</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="comment"><?= t('feedback_comment') ?></label>
                <textarea name="comment" id="comment" class="form-control" rows="5" placeholder="<?= t('feedback_placeholder') ?>"><?= sanitize($_POST['comment'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= t('feedback_submit') ?></button>
        </form>
    </div>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
