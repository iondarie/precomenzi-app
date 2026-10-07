<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['rol'] ?? 'guest';
    redirect($role === 'admin' ? '/admin/index.php' : '/agent/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Te rugăm să completezi emailul și parola.');
        }

        $stmt = db()->prepare('SELECT * FROM utilizatori WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['parola_hash'])) {
            throw new RuntimeException('Date de autentificare incorecte.');
        }

        loginUser($user);
        redirect($_SESSION['user']['rol'] === 'admin' ? '/admin/index.php' : '/agent/index.php');
    } catch (Throwable $e) {
        addFlash('danger', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Precomenzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow border-0" style="width:min(100%, 440px);">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Precomenzi</h2>
                    <p class="text-muted mb-0">Autentificare</p>
                </div>

                <?php renderFlash(); ?>

                <form method="post" novalidate>
                    <input type="hidden" name="_token" value="<?= e(csrfToken()); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Parola</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Autentificare</button>
                </form>

                <div class="mt-4 small text-muted">
                    <strong>Demo:</strong>
                    <div>admin@precomenzi.ro / password</div>
                    <div>agent1@precomenzi.ro / password</div>
                    <div>agent2@precomenzi.ro / password</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
