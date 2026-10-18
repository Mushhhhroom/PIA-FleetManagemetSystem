<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 6 &bull; Tollway RFID &amp; Fleet Expense Management
        </div>
        <h1 class="page-title mb-1">AutoSweep &amp; EasyTrip RFID Balances</h1>
        <p class="text-muted small mb-0">
            FR-6.1 card registry &bull; FR-6.2 reload &amp; toll entry &bull; FR-6.3 automated ₱500 low-balance alerts to the Motorpool Head.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($lowBalance)): ?>
            <a href="<?= base_url('rfid?filter=low') ?>" class="btn btn-sm btn-danger">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= count($lowBalance) ?> Low-Balance Card(s)
            </a>
        <?php endif; ?>
        <?php if ($lowOnly): ?>
            <a href="<?= base_url('rfid') ?>" class="btn btn-sm btn-secondary">Show All Cards</a>
        <?php endif; ?>
    </div>
</div>

<?php $cardsToShow = $lowOnly ? array_values(array_filter($cards, fn($c) => (float) $c['balance'] < (float) $c['low_balance_threshold'])) : $cards; ?>

<!-- KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Registered Cards</div>
            <div class="kpi-value mono"><?= count($cards) ?></div>
            <div class="kpi-sub">AutoSweep + EasyTrip across the fleet</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Aggregate Balance</div>
            <div class="kpi-value mono" style="color: #166534;">₱<?= number_format(array_sum(array_map(fn($c) => (float) $c['balance'], $cards)), 2) ?></div>
            <div class="kpi-sub">Total prepaid toll value on hand</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Below ₱500 Threshold</div>
            <div class="kpi-value mono" style="color: <?= empty($lowBalance) ? '#166534' : '#991b1b' ?>;"><?= count($lowBalance) ?></div>
            <div class="kpi-sub">FR-6.3 motorpool head alerts dispatched</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- FR-6.1 Card Registry -->
    <div class="col-lg-7">
        <div class="card-panel">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-id-card me-2" style="color: var(--primary-accent);"></i> RFID Card Registry &amp; Balances</h2>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Card Number</th>
                            <th>Vehicle</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cardsToShow)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No RFID cards match this filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cardsToShow as $c): ?>
                                <?php $isLow = (float) $c['balance'] < (float) $c['low_balance_threshold']; ?>
                                <tr style="<?= $isLow ? 'background:#fef2f2;' : '' ?>">
                                    <td>
                                        <span class="badge <?= $c['provider'] === 'autosweep' ? 'bg-primary' : 'bg-success' ?>">
                                            <?= $c['provider'] === 'autosweep' ? 'AutoSweep' : 'EasyTrip' ?>
                                        </span>
                                    </td>
                                    <td class="mono fw-semibold" style="font-size: 0.8rem;"><?= esc($c['card_number']) ?></td>
                                    <td>
                                        <span class="mono"><?= esc($c['plate_number'] ?? 'N/A') ?></span>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= esc(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?></div>
                                    </td>
                                    <td>
                                        <span class="mono fw-bold" style="color: <?= $isLow ? '#991b1b' : '#166534' ?>;">
                                            ₱<?= number_format((float) $c['balance'], 2) ?>
                                        </span>
                                        <?php if ($isLow): ?>
                                            <div class="badge bg-danger mt-1">LOW BALANCE (FR-6.3)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= esc(ucfirst($c['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FR-6.2 Ledger -->
        <div class="card-panel mt-4">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-receipt me-2" style="color: var(--primary-accent);"></i> Reload &amp; Toll Ledger (FR-6.2)</h2>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Card</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ledger)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No RFID transactions recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ledger as $t): ?>
                                <tr>
                                    <td class="mono text-muted" style="font-size: 0.75rem;"><?= esc($t['created_at']) ?></td>
                                    <td class="mono" style="font-size: 0.78rem;"><?= esc($t['card_number'] ?? '—') ?></td>
                                    <td class="mono" style="font-size: 0.78rem;"><?= esc($t['plate_number'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge <?= $t['type'] === 'reload' ? 'bg-success' : ($t['type'] === 'toll' ? 'bg-primary' : 'bg-secondary') ?>">
                                            <?= esc(ucfirst($t['type'])) ?>
                                        </span>
                                    </td>
                                    <td class="mono" style="color: <?= $t['amount'] < 0 ? '#991b1b' : '#166534' ?>;">
                                        <?= $t['amount'] < 0 ? '-' : '' ?>₱<?= number_format(abs((float) $t['amount']), 2) ?>
                                    </td>
                                    <td class="mono">₱<?= number_format((float) $t['balance_after'], 2) ?></td>
                                    <td style="font-size: 0.78rem;"><?= esc($t['recorded_by_name'] ?? 'System') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="col-lg-5">
        <!-- FR-6.2: Reload Entry -->
        <div class="card-panel mb-4">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-plus me-2" style="color: var(--primary-accent);"></i> Log RFID Reload (FR-6.2)</h2>
            <form action="<?= base_url('rfid/reload') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">RFID Card</label>
                    <select name="rfid_card_id" class="form-select" required>
                        <option value="">— Select card —</option>
                        <?php foreach ($cards as $c): ?>
                            <option value="<?= (int) $c['id'] ?>">
                                <?= esc(($c['provider'] === 'autosweep' ? 'AutoSweep' : 'EasyTrip') . ' • ' . $c['card_number'] . ' • ' . ($c['plate_number'] ?? '?') . ' • ₱' . number_format((float) $c['balance'], 2)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Reload Amount (₱)</label>
                    <input type="number" step="0.01" min="1" name="amount" class="form-control" required placeholder="e.g. 1000.00">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="OR/official receipt reference">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Record Reload
                </button>
            </form>
        </div>

        <!-- FR-6.1: Register Card -->
        <div class="card-panel">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-id-card-clip me-2" style="color: var(--primary-accent);"></i> Register RFID Card (FR-6.1)</h2>
            <form action="<?= base_url('rfid/cards') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Vehicle</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">— Select vehicle —</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"><?= esc($v['plate_number'] . ' — ' . trim($v['make'] . ' ' . $v['model'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Tollway Provider</label>
                    <select name="provider" class="form-select" required>
                        <option value="autosweep">AutoSweep</option>
                        <option value="easytrip">EasyTrip</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-sm-7">
                        <label class="form-label small fw-semibold text-dark">Card Number</label>
                        <input type="text" name="card_number" class="form-control" required placeholder="e.g. 1234-5678-9012">
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small fw-semibold text-dark">Opening Balance (₱)</label>
                        <input type="number" step="0.01" min="0" name="balance" class="form-control" value="0.00">
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="fa-solid fa-plus me-1"></i> Register Card
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
