<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAuth('agent');

$pdo = db();
$user = $_SESSION['user'];
$window = currentOrderWindow();
$currentWeek = $window['week'];

$clients = $pdo->prepare('SELECT * FROM clienti WHERE agent_id = :agent_id ORDER BY nume_client ASC');
$clients->execute([':agent_id' => $user['id']]);
$clients = $clients->fetchAll();

$products = $pdo->query('SELECT * FROM produse WHERE activ = 1 ORDER BY denumire ASC')->fetchAll();

$existingOrders = $pdo->prepare('SELECT c.id, c.client_id, c.data_comanda, cl.nume_client FROM comenzi c LEFT JOIN clienti cl ON cl.id = c.client_id WHERE c.agent_id = :agent_id AND c.saptamana_an = :week ORDER BY c.data_comanda DESC');
$existingOrders->execute([':agent_id' => $user['id'], ':week' => $currentWeek]);
$existingOrders = $existingOrders->fetchAll();

$editableOrder = null;
if (isset($_GET['edit_order'])) {
    $stmt = $pdo->prepare('SELECT * FROM comenzi WHERE id = :id AND agent_id = :agent_id LIMIT 1');
    $stmt->execute([':id' => (int)$_GET['edit_order'], ':agent_id' => $user['id']]);
    $editableOrder = $stmt->fetch();

    if ($editableOrder) {
        $det = $pdo->prepare('SELECT * FROM comanda_detalii WHERE comanda_id = :comanda_id');
        $det->execute([':comanda_id' => $editableOrder['id']]);
        $editableOrder['details'] = $det->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        if (!isOrderWindowOpen(new DateTimeImmutable('now'))) {
            throw new RuntimeException('Fereastra de precomandă este închisă. Se acceptă doar Miercuri 00:00 - Marți 12:00.');
        }

        $clientId = (int)($_POST['client_id'] ?? 0);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $selected = $_POST['products'] ?? [];

        if ($clientId <= 0) {
            throw new RuntimeException('Selectează un client valid.');
        }

        $items = [];
        foreach ($selected as $productId => $qty) {
            $productId = (int)$productId;
            $qty = (int)$qty;
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $prod = $pdo->prepare('SELECT * FROM produse WHERE id = :id AND activ = 1 LIMIT 1');
            $prod->execute([':id' => $productId]);
            $prod = $prod->fetch();
            if (!$prod) {
                throw new RuntimeException('Produs invalid.');
            }

            if (!isValidQuantityForProduct($qty, (int)$prod['multiplu_cantitate'])) {
                throw new RuntimeException('Cantitatea pentru ' . $prod['denumire'] . ' trebuie să fie multiplu de ' . $prod['multiplu_cantitate'] . '.');
            }

            $items[] = ['product_id' => $productId, 'quantity' => $qty];
        }

        if (empty($items)) {
            throw new RuntimeException('Nu ai selectat niciun produs valid.');
        }

        $pdo->beginTransaction();

        if ($orderId > 0) {
            $check = $pdo->prepare('SELECT id FROM comenzi WHERE id = :id AND agent_id = :agent_id LIMIT 1');
            $check->execute([':id' => $orderId, ':agent_id' => $user['id']]);
            if (!$check->fetch()) {
                throw new RuntimeException('Comanda nu există sau nu îți aparține.');
            }

            $stmt = $pdo->prepare('UPDATE comenzi SET client_id = :client_id, data_comanda = NOW(), saptamana_an = :week WHERE id = :id');
            $stmt->execute([':client_id' => $clientId, ':week' => $currentWeek, ':id' => $orderId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO comenzi (client_id, agent_id, data_comanda, saptamana_an, status) VALUES (:client_id, :agent_id, NOW(), :week, "in_lucru")');
            $stmt->execute([':client_id' => $clientId, ':agent_id' => $user['id'], ':week' => $currentWeek]);
            $orderId = (int)$pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM comanda_detalii WHERE comanda_id = :order_id')->execute([':order_id' => $orderId]);

        foreach ($items as $item) {
            $ins = $pdo->prepare('INSERT INTO comanda_detalii (comanda_id, produs_id, cantitate) VALUES (:order_id, :product_id, :qty)');
            $ins->execute([':order_id' => $orderId, ':product_id' => $item['product_id'], ':qty' => $item['quantity']]);
        }

        $pdo->commit();
        addFlash('success', 'Precomanda a fost salvată.');
        redirect('/agent/index.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        addFlash('danger', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precomenzi Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Agent Precomenzi</a>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <span class="text-light small"><?= e($user['nume']); ?></span>
                <a class="btn btn-light btn-sm" href="../logout.php">Delogare</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php renderFlash(); ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Săptămâna de comandă</h4>
                        <div class="text-muted">Perioada: <?= e($window['label']); ?></div>
                    </div>
                    <div>
                        <?php if (isOrderWindowOpen(new DateTimeImmutable('now'))): ?>
                            <span class="badge bg-success px-3 py-2">Fereastră activă</span>
                        <?php else: ?>
                            <span class="badge bg-danger px-3 py-2">Fereastră închisă</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><strong><?= $editableOrder ? 'Modificare precomandă' : 'Introducere precomandă'; ?></strong></div>
            <div class="card-body">
                <?php if (!isOrderWindowOpen(new DateTimeImmutable('now'))): ?>
                    <div class="alert alert-warning">Comenzile nu pot fi create sau modificate în afara intervalului Miercuri 00:00 - Marți 12:00.</div>
                <?php else: ?>
                    <form id="agent-order-form" method="post">
                        <input type="hidden" name="_token" value="<?= e(csrfToken()); ?>">
                        <input type="hidden" name="order_id" value="<?= (int)($editableOrder['id'] ?? 0); ?>">

                        <div class="mb-4">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">-- Selectează client --</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= (int)$client['id']; ?>" <?= (!empty($editableOrder) && (int)$editableOrder['client_id'] === (int)$client['id']) ? 'selected' : ''; ?>><?= e($client['nume_client']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Multiplu</th>
                                    <th>Cantitate</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $value = 0;
                                    if (!empty($editableOrder['details'])) {
                                        foreach ($editableOrder['details'] as $detail) {
                                            if ((int)$detail['produs_id'] === (int)$product['id']) {
                                                $value = (int)$detail['cantitate'];
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= e($product['denumire']); ?></td>
                                        <td><?= (int)$product['multiplu_cantitate']; ?></td>
                                        <td>
                                            <input class="form-control" type="number" min="0" step="1" name="products[<?= (int)$product['id']; ?>]" value="<?= $value; ?>" data-multiplier="<?= (int)$product['multiplu_cantitate']; ?>" placeholder="0">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Salvează</button>
                            <?php if ($editableOrder): ?>
                                <a href="index.php" class="btn btn-outline-secondary">Renunță</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white"><strong>Precomenzile mele</strong></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Data</th>
                        <th>Acțiune</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($existingOrders)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nicio precomandă înregistrată pentru săptămâna curentă.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($existingOrders as $order): ?>
                            <tr>
                                <td><?= (int)$order['id']; ?></td>
                                <td><?= e($order['nume_client'] ?? '-'); ?></td>
                                <td><?= e($order['data_comanda']); ?></td>
                                <td><a href="index.php?edit_order=<?= (int)$order['id']; ?>" class="btn btn-sm btn-outline-primary">Editează</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/app.js"></script>
</body>
</html>
