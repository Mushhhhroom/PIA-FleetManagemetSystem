<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COA Form B — <?= esc(date('F Y', strtotime($month . '-01'))) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #0f172a; margin: 24px; background: #fff; }
        h1 { font-size: 16px; margin: 0; text-align: center; text-transform: uppercase; letter-spacing: 0.04em; }
        h2 { font-size: 13px; margin: 4px 0 0; text-align: center; font-weight: 600; }
        .sub { text-align: center; font-size: 11px; color: #475569; margin-bottom: 4px; }
        .agency { text-align: center; font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .meta { display: flex; justify-content: space-between; margin: 14px 0 8px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #94a3b8; padding: 4px 5px; vertical-align: top; }
        th { background: #e2e8f0; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; }
        td { font-size: 10.5px; }
        .empty { text-align: center; color: #64748b; padding: 24px; }
        .totals { margin-top: 8px; font-size: 11px; display: flex; gap: 24px; }
        .totals span strong { font-family: Consolas, monospace; }
        .sig { display: flex; justify-content: space-between; margin-top: 48px; gap: 40px; }
        .sig div { flex: 1; text-align: center; border-top: 1px solid #0f172a; padding-top: 4px; font-size: 11px; }
        .actions { margin: 16px 0; text-align: center; }
        .actions button, .actions a { font-size: 12px; padding: 6px 14px; margin: 0 4px; cursor: pointer; border: 1px solid #334155; background: #f1f5f9; border-radius: 4px; text-decoration: none; color: #0f172a; }
        @media print { .actions { display: none; } body { margin: 8mm; } @page { landscape: letter; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <a href="<?= base_url('compliance?month=' . urlencode($month)) ?>">← Back to Compliance Portal</a>
    </div>

    <div class="agency">Republic of the Philippines</div>
    <h1>Philippine Information Agency</h1>
    <h2>Monthly Report of Official Travel — COA Form B</h2>
    <div class="sub">For the month of <strong><?= esc(date('F Y', strtotime($month . '-01'))) ?></strong> &bull; Generated <?= esc(date('M d, Y h:i A')) ?></div>

    <div class="meta">
        <div><strong>Form Reference:</strong> COA Form B (Fleet Management)</div>
        <div><strong>Prepared by:</strong> <?= esc(session()->get('user_name') ?? 'System Administrator') ?></div>
    </div>

    <?php
        $totalDistance = 0.0;
        $totalFuel     = 0.0;
        $totalToll     = 0.0;
    ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Ticket No.</th>
                <th>Driver Details</th>
                <th>License No.</th>
                <th>Vehicle / Plate</th>
                <th>Destination Address</th>
                <th>Passengers</th>
                <th>Departure</th>
                <th>Return</th>
                <th>Distance (km)</th>
                <th>Fuel (L)</th>
                <th>Toll (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="12" class="empty">No official travel records for this period.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $r): ?>
                    <?php
                        $totalDistance += (float) $r['total_distance_km'];
                        $totalFuel     += (float) $r['fuel_used_liters'];
                        $totalToll     += (float) ($r['toll_expense'] ?? 0);
                        $driver = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                        $dest = !empty($r['authorized_destination']) ? $r['authorized_destination'] : ($r['destination'] ?? '—');
                        $passengers = !empty($r['authorized_passengers']) ? $r['authorized_passengers'] : ($r['passenger_names'] ?: '—');
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($r['ticket_serial_no']) ?></td>
                        <td><?= esc($driver ?: ($r['requestor_name'] ?? '—')) ?></td>
                        <td><?= esc($r['license_number'] ?? '—') ?></td>
                        <td><?= esc(trim(($r['vehicle_code'] ?? '') . ' / ' . ($r['plate_number'] ?? ''))) ?></td>
                        <td><?= esc($dest) ?></td>
                        <td><?= esc($passengers) ?></td>
                        <td><?= esc($r['departure_time'] ?? $r['authorized_departure'] ?? '—') ?></td>
                        <td><?= esc($r['arrival_back_time'] ?? $r['authorized_return'] ?? '—') ?></td>
                        <td style="text-align:right;"><?= number_format((float) $r['total_distance_km'], 2) ?></td>
                        <td style="text-align:right;"><?= number_format((float) $r['fuel_used_liters'], 2) ?></td>
                        <td style="text-align:right;"><?= number_format((float) ($r['toll_expense'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9" style="text-align:right;">TOTALS (<?= count($rows) ?> trip record(s))</th>
                <th style="text-align:right;"><?= number_format($totalDistance, 2) ?></th>
                <th style="text-align:right;"><?= number_format($totalFuel, 2) ?></th>
                <th style="text-align:right;"><?= number_format($totalToll, 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="sig">
        <div>Prepared by<br><strong>Fleet Management Officer</strong></div>
        <div>Reviewed by<br><strong>Administrative Division Chief</strong></div>
        <div>Approved by<br><strong>Agency Head / Authorized Official</strong></div>
    </div>
</body>
</html>
