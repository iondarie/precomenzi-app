<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAuth('admin');

$window = currentOrderWindow();
$stmt = db()->prepare('SELECT p.cod_produs, p.denumire, u.nume AS agent_nume, SUM(cd.cantitate) AS total_bucati FROM comanda_detalii cd JOIN comenzi c ON c.id = cd.comanda_id JOIN produse p ON p.id = cd.produs_id JOIN utilizatori u ON u.id = c.agent_id WHERE c.saptamana_an = :week GROUP BY p.id, c.agent_id ORDER BY p.denumire ASC, u.nume ASC');
$stmt->execute([':week' => $window['week']]);
$rows = $stmt->fetchAll();

$productTotals = [];
$agentTotals = [];
$grandTotal = 0;
$grouped = [];

foreach ($rows as $row) {
    $productCode = (string)$row['cod_produs'];
    $productName = (string)$row['denumire'];
    $agentName = (string)$row['agent_nume'];
    $qty = (int)$row['total_bucati'];

    $productTotals[$productCode]['name'] = $productName;
    $productTotals[$productCode]['total'] = ($productTotals[$productCode]['total'] ?? 0) + $qty;
    $agentTotals[$agentName] = ($agentTotals[$agentName] ?? 0) + $qty;
    $grandTotal += $qty;

    $grouped[$productCode]['name'] = $productName;
    $grouped[$productCode]['items'][$agentName] = ($grouped[$productCode]['items'][$agentName] ?? 0) + $qty;
}

$start = $window['start']->format('d.m.Y');
$end = $window['end']->format('d.m.Y');

$html = '<!DOCTYPE html><html lang="ro"><head><meta charset="UTF-8"><title>Raport precomenzi</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827;}h1,h3{margin:0 0 10px 0;}table{width:100%;border-collapse:collapse;margin-top:12px;}th,td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;}th{background:#f3f4f6;} .total{font-weight:700;} </style></head><body>';
$html .= '<h1>Raport precomenzi săptămânale</h1>';
$html .= '<div><strong>Perioada:</strong> ' . $start . ' - ' . $end . '</div>';
$html .= '<div><strong>Data generării:</strong> ' . (new DateTimeImmutable('now'))->format('d.m.Y H:i') . '</div>';

$html .= '<h3 style="margin-top:20px;">1. Totaluri generale per produs</h3><table><thead><tr><th>Produs</th><th>Cod</th><th>Total</th></tr></thead><tbody>';
foreach ($productTotals as $code => $data) {
    $html .= '<tr><td>' . e($data['name']) . '</td><td>' . e($code) . '</td><td class="total">' . (int)$data['total'] . '</td></tr>';
}
$html .= '<tr class="total"><td colspan="2">Total general</td><td>' . $grandTotal . '</td></tr>';
$html .= '</tbody></table>';

$html .= '<h3 style="margin-top:20px;">2. Produs și agent</h3><table><thead><tr><th>Produs</th><th>Agent</th><th>Cantitate</th></tr></thead><tbody>';
foreach ($grouped as $code => $data) {
    $first = true;
    foreach ($data['items'] as $agentName => $qty) {
        $html .= '<tr>';
        if ($first) {
            $html .= '<td rowspan="' . count($data['items']) . '">' . e($data['name']) . '</td>';
            $first = false;
        }
        $html .= '<td>' . e($agentName) . '</td><td>' . (int)$qty . '</td></tr>';
    }
}
$html .= '</tbody></table>';

$html .= '<h3 style="margin-top:20px;">3. Totaluri generale per agent</h3><table><thead><tr><th>Agent</th><th>Total bucăți</th></tr></thead><tbody>';
foreach ($agentTotals as $agentName => $total) {
    $html .= '<tr><td>' . e($agentName) . '</td><td>' . (int)$total . '</td></tr>';
}
$html .= '</tbody></table></body></html>';

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'dejavusans',
    ]);
    $mpdf->WriteHTML($html);
    $mpdf->Output('raport-precomenzi-' . $window['week'] . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
} catch (Throwable $e) {
    echo '<pre>' . e($e->getMessage()) . '</pre>';
}
