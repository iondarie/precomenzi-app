<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_product') {
    try {
        verifyCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $cod = trim((string)($_POST['cod_produs'] ?? ''));
        $nume = trim((string)($_POST['denumire'] ?? ''));
        $multiplier = (int)($_POST['multiplu_cantitate'] ?? 1);
        $activ = !empty($_POST['activ']) ? 1 : 0;

        if ($cod === '' || $nume === '') {
            throw new RuntimeException('Cod produs și denumire sunt obligatorii.');
        }
        if ($multiplier <= 0) {
            throw new RuntimeException('Multiplu cantitate trebuie să fie > 0.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE produse SET cod_produs = :cod, denumire = :nume, multiplu_cantitate = :mult, activ = :activ WHERE id = :id');
            $stmt->execute([':cod' => $cod, ':nume' => $nume, ':mult' => $multiplier, ':activ' => $activ, ':id' => $id]);
            addFlash('success', 'Produsul a fost actualizat.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO produse (cod_produs, denumire, multiplu_cantitate, activ) VALUES (:cod, :nume, :mult, :activ)');
            $stmt->execute([':cod' => $cod, ':nume' => $nume, ':mult' => $multiplier, ':activ' => $activ]);
            addFlash('success', 'Produsul a fost creat.');
        }
    } catch (Throwable $e) {
        addFlash('danger', $e->getMessage());
    }
}

if (isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $pdo->prepare('DELETE FROM produse WHERE id = :id')->execute([':id' => $id]);
    addFlash('success', 'Produsul a fost șters.');
}

$productList = $pdo->query('SELECT * FROM produse ORDER BY activ DESC, denumire ASC')->fetchAll();
$orderList = $pdo->query('SELECT c.*, cl.nume_client, u.nume AS agent_nume FROM comenzi c LEFT JOIN clienti cl ON cl.id = c.client_id LEFT JOIN utilizatori u ON u.id = c.agent_id ORDER BY c.data_comanda DESC LIMIT 50')->fetchAll();

$selectedProduct = null;
if (isset($_GET['edit_product'])) {
    $stmt = $pdo->prepare('SELECT * FROM produse WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_GET['edit_product']]);
    $selectedProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office - Precomenzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Precomenzi Admin</a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-light btn-sm" href="../pdf/report.php">Raport PDF</a>
                <a class="btn btn-light btn-sm" href="../logout.php">Delogare</a>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php renderFlash(); ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="summary-box">
                    <div class="text-muted small">Produse</div>
                    <h3 class="mb-0"><?= count($productList); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-box">
                    <div class="text-muted small">Comenzi</div>
                    <h3 class="mb-0"><?= count($orderList); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-box">
                    <div class="text-muted small">Săptămâna curentă</div>
                    <h5 class="mb-0"><?= currentOrderWindow()['label']; ?></h5>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white"><strong><?= $selectedProduct ? 'Editare produs' : 'Adăugare produs'; ?></strong></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="_token" value="<?= e(csrfToken()); ?>">
                            <input type="hidden" name="action" value="save_product">
                            <input type="hidden" name="id" value="<?= (int)($selectedProduct['id'] ?? 0); ?>">

                            <div class="mb-3">
                                <label class="form-label">Cod produs</label>
                                <input type="text" class="form-control" name="cod_produs" value="<?= e($selectedProduct['cod_produs'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Denumire</label>
                                <input type="text" class="form-control" name="denumire" value="<?= e($selectedProduct['denumire'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Multiplu cantitate</label>
                                <input type="number" class="form-control" min="1" name="multiplu_cantitate" value="<?= (int)($selectedProduct['multiplu_cantitate'] ?? 6); ?>" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="activ" name="activ" <?= (!empty($selectedProduct['activ']) || empty($selectedProduct)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activ">Activ</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Salvează</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong>Catalog produse</strong>
                        <?php if ($selectedProduct): ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">Anulează</a>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Cod</th>
                                <th>Denumire</th>
                                <th>Multiplu</th>
                                <th>Status</th>
                                <th class="text-end">Acțiuni</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($productList as $product): ?>
                                <tr>
                                    <td><?= e($product['cod_produs']); ?></td>
                                    <td><?= e($product['denumire']); ?></td>
                                    <td><?= (int)$product['multiplu_cantitate']; ?></td>
                                    <td>
                                        <?php if ((int)$product['activ'] === 1): ?>
                                            <span class="badge bg-success">Activ</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiv</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="index.php?edit_product=<?= (int)$product['id']; ?>" class="btn btn-sm btn-outline-primary">Editează</a>
                                        <a href="index.php?delete_product=<?= (int)$product['id']; ?>" onclick="return confirm('Ștergi produsul?');" class="btn btn-sm btn-outline-danger">Șterge</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white"><strong>Lista comenzi</strong></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Agent</th>
                        <th>Săptămână</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orderList as $order): ?>
                        <tr>
                            <td><?= (int)$order['id']; ?></td>
                            <td><?= e($order['nume_client'] ?? '-'); ?></td>
                            <td><?= e($order['agent_nume'] ?? '-'); ?></td>
                            <td><?= e($order['saptamana_an']); ?></td>
                            <td>
                                <?php if ($order['status'] === 'finalizata'): ?>
                                    <span class="badge bg-success">Finalizată</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">În lucru</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($order['data_comanda']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
