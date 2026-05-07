<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = t('feedback_list_title');
requireRole('agent');
requireApproved();

$pdo = getDBConnection();
$agentId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['reply'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $reply = trim($_POST['reply']);
        $feedbackId = (int)$_POST['feedback_id'];
        if (!empty($reply)) {
            $stmt = $pdo->prepare(
                "UPDATE feedback f JOIN packages p ON f.package_id = p.id
                 SET f.agent_reply = ?, f.replied_at = NOW()
                 WHERE f.id = ? AND p.agent_id = ?"
            );
            $stmt->execute([$reply, $feedbackId, $agentId]);
            setFlash('Reply sent.');
            redirect(BASE_URL . '/agent/feedback.php');
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT f.*, p.title as package_title, p.destination, u.full_name as customer_name
     FROM feedback f
     JOIN packages p ON f.package_id = p.id
     JOIN users u ON f.customer_id = u.id
     WHERE p.agent_id = ?
     ORDER BY f.agent_reply IS NOT NULL, f.created_at DESC"
);
$stmt->execute([$agentId]);
$feedbacks = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
<div class="container">
    <div class="page-header">
        <h2><?= t('feedback_list_title') ?></h2>
        <a href="<?= BASE_URL ?>/agent/dashboard.php" class="btn btn-outline btn-sm">&larr; <?= t('nav_dashboard') ?></a>
    </div>

    <?php if (empty($feedbacks)): ?>
        <div class="empty-state"><p><?= t('no_feedback') ?></p></div>
    <?php else: ?>
        <?php foreach ($feedbacks as $fb): ?>
        <div class="card mb-1">
            <div class="review-header">
                <strong><?= sanitize($fb['customer_name']) ?></strong>
                <span class="review-rating"><?= str_repeat('&#11088;', $fb['rating']) ?><?= str_repeat('&#9734;', 5 - $fb['rating']) ?></span>
                <span class="text-muted"><?= formatDate($fb['created_at']) ?></span>
            </div>
            <p class="text-sm text-muted"><?= t('col_package') ?>: <?= sanitize($fb['package_title']) ?> — <?= sanitize($fb['destination']) ?></p>
            <p><?= nl2br(sanitize($fb['comment'])) ?></p>

            <?php if ($fb['agent_reply']): ?>
                <div class="agent-reply">
                    <strong><?= t('agent_reply') ?> (<?= formatDate($fb['replied_at']) ?>):</strong>
                    <p><?= nl2br(sanitize($fb['agent_reply'])) ?></p>
                </div>
            <?php else: ?>
                <form method="POST" class="reply-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                    <div class="form-group">
                        <textarea name="reply" class="form-control" rows="2" placeholder="<?= t('reply_placeholder') ?>" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><?= t('btn_reply') ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
