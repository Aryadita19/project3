<?php
declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Halaman internal wajib login. */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('/login.php');
    }
    return $user;
}

/** Authorization di SERVER: bukan sekadar menyembunyikan menu. */
function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $pageTitle = '403 Forbidden';
        require __DIR__ . '/../views/header.php';
        echo '<div class="card"><h2>403 - Akses Ditolak</h2>'
           . '<p>Role Anda (<b>' . e($user['role']) . '</b>) tidak diizinkan membuka halaman ini.</p>'
           . '<a class="btn" href="' . e(url('/dashboard.php')) . '">Kembali ke Dashboard</a></div>';
        require __DIR__ . '/../views/footer.php';
        exit;
    }
    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Token CSRF tidak valid. Muat ulang halaman.');
    }
}

/* ---------- Flash message ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
