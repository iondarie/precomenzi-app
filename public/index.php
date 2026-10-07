<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['rol'] ?? 'guest';
    header('Location: ' . APP_BASE_PATH . ($role === 'admin' ? '/admin/index.php' : '/agent/index.php'));
    exit;
}

header('Location: ' . APP_BASE_PATH . '/login.php');
exit;
