<?php
/**
 * Session, authentication, CSRF helpers.
 */

require_once __DIR__ . '/paths.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        redirectTo('auth/login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        redirectTo('index.php');
    }
}

function getCurrentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function getCurrentUserName(): ?string
{
    return $_SESSION['user_name'] ?? null;
}

function getCurrentUserEmail(): ?string
{
    return $_SESSION['user_email'] ?? null;
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
}

function redirectAfterLogin(): void
{
    if (!empty($_SESSION['redirect_after_login'])) {
        $target = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $target);
        exit();
    }

    if (isAdmin()) {
        redirectTo('admin/dashboard.php');
    }
    redirectTo('reservations.php');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && hash_equals(csrfToken(), $submitted);
}

function requireCsrf(): void
{
    if (!validateCsrf()) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        redirectBack('index.php');
    }
}

/**
 * Roll back only if a transaction is still open.
 */
function rollbackIfActive(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function sanitizeInput(string $data): string
{
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}
