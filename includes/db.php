<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        'utf8mb4'
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function addFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function renderFlash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $flash) {
        echo '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }

    unset($_SESSION['flash']);
}

function redirect(string $path): never
{
    header('Location: ' . APP_BASE_PATH . $path);
    exit;
}

function currentOrderWindow(): array
{
    $now = new DateTimeImmutable('now');
    $weekday = (int)$now->format('N');

    $daysBack = 0;
    if ($weekday < 3) {
        $daysBack = $weekday + 4;
    } else {
        $daysBack = $weekday - 3;
    }

    $start = (new DateTimeImmutable($now->format('Y-m-d 00:00:00')))->modify('-' . $daysBack . ' days');
    $end = $start->modify('+6 days 12 hours');

    return [
        'start' => $start,
        'end' => $end,
        'label' => $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y'),
        'week' => $start->format('o-\WW'),
    ];
}

function isOrderWindowOpen(?DateTimeImmutable $now = null): bool
{
    $now ??= new DateTimeImmutable('now');
    $window = currentOrderWindow();

    return $now >= $window['start'] && $now <= $window['end'];
}

function isoWeekLabel(?DateTimeImmutable $date = null): string
{
    $date ??= new DateTimeImmutable('now');
    return $date->format('o-\WW');
}

function isValidQuantityForProduct(int $quantity, int $multiplier): bool
{
    if ($quantity <= 0 || $multiplier <= 0) {
        return false;
    }

    return $quantity % $multiplier === 0;
}
