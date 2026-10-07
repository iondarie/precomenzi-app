<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($expected, $token)) {
        throw new RuntimeException('Sesiunea a expirat. Reîncarcă pagina.');
    }
}

function requireAuth(?string $role = null): void
{
    if (empty($_SESSION['user'])) {
        redirect('/login.php');
    }

    if ($role && ($_SESSION['user']['rol'] ?? null) !== $role) {
        addFlash('warning', 'Nu ai permisiunea necesară.');
        redirect('/login.php');
    }
}

function loginUser(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'nume' => $user['nume'],
        'email' => $user['email'],
        'rol' => $user['rol'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}

function userIsAdmin(): bool
{
    return !empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? null) === 'admin';
}

function userIsAgent(): bool
{
    return !empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? null) === 'agent';
}
