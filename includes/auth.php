<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/lang.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, role, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

function requireApproved(): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['status'] !== 'approved') {
        $_SESSION['flash'] = 'Your account is pending approval.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

try {
    $currentUser = getCurrentUser();
} catch (PDOException $e) {
    $currentUser = null;
}
