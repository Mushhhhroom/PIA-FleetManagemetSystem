<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 5 &bull; Pre-Trip Safety &bull; FR-5.1
        </div>
        <h1 class="page-title mb-1">Mandatory BLOWBAGETS Pre-Trip Safety Checklist</h1>
        <p class="text-muted small mb-0">
            Every item must pass before trip activation. A single failure locks the vehicle to
            <strong>Under Maintenance</strong> and routes a Pre-Repair Inspection Report (PIR) to the mechanic queue (FR-5.2).
        </p>
    </div>
    <a href="<?= base_url('tickets/' . $ticket['id']) ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Trip Ticket
    </a>
</div>

<!-- Trip Ticket Summary -->
<div class="card-panel mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="kpi-label">Trip Ticket</div>
            <div class="kpi-value mono" style="font-size: 1rem;"><?= esc($ticket['ticket_serial_no']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="kpi-label">Vehicle</div>
            <div class="kpi-value" style="font-size: 1rem;">
                <?= esc(($vehicle['plate_number'] ?? 'N/A')) ?>
                <span class="text-muted" style="font-size: 0.8rem;">
                    <?= esc(trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''))) ?>
                </span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-label">Destination</div>
            <div class="kpi-value" style="font-size: 1rem;"><?= esc($ticket['authorized_destination'] ?? '—') ?></div>
        </div>
        <div class="col-md-3">
            <div class="kpi-label">Current Vehicle State</div>
            <div class="kpi-value" style="font-size: 1rem;">
                <span class="badge <?= in_array($vehicle['status'] ?? '', ['active', 'in_transit'], true) ? 'bg-success' : 'bg-danger' ?>">
                    <?= esc(ucfirst(str_replace('_', ' ', $vehicle['status'] ?? 'unknown'))) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($vehicle['status']) && in_array($vehicle['status'], ['under_maintenance', 'disabled_breakdown', 'out_of_service', 'maintenance'], true)): ?>
    <div class="alert alert-danger border-0 rounded-2 p-3 mb-4" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
        <i class="fa-solid fa-lock me-2"></i>
        <strong>VEHICLE LOCKED (FR-5.2).</strong> This vehicle is currently in
        <strong><?= esc(ucfirst(str_replace('_', ' ', $vehicle['status']))) ?></strong> status and cannot be dispatched.
        A mechanic must release it through the PIR workflow (FR-5.3).
    </div>
<?php endif; ?>

<!-- BLOWBAGETS Checklist Form -->
<div class="card-panel">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 mb-0 fw-bold">
            <i class="fa-solid fa-clipboard-check me-2" style="color: var(--primary-accent);"></i>
            Pre-Trip Inspection Items
        </h2>
        <span class="badge bg-light text-dark border p-2">10 Items &bull; All must PASS</span>
    </div>

    <form action="<?= base_url('safety/' . $ticket['id']) ?>" method="POST">
        <?= csrf_field() ?>

        <div class="row g-3">
            <?php foreach ($items as $code => $label): ?>
                <div class="col-md-6">
                    <div class="border rounded-2 p-3 d-flex align-items-center justify-content-between gap-3" style="background: var(--bg-secondary, #f8fafc);">
                        <div>
                            <div class="fw-semibold" style="font-size: 0.88rem; color: #0f172a;">
                                <i class="fa-solid fa-circle-check me-1 text-secondary"></i> <?= esc($label) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">Inspect before every departure</div>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="check[<?= esc($code) ?>]" id="pass_<?= esc($code) ?>" value="pass" required>
                            <label class="btn btn-outline-success" for="pass_<?= esc($code) ?>">Pass</label>

                            <input type="radio" class="btn-check" name="check[<?= esc($code) ?>]" id="fail_<?= esc($code) ?>" value="fail">
                            <label class="btn btn-outline-danger" for="fail_<?= esc($code) ?>">Fail</label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <label class="form-label small fw-semibold text-dark">Driver Remarks (optional)</label>
            <textarea name="remarks" rows="2" class="form-control" placeholder="Note any observations, defects or corrective actions taken..."><?= set_value('remarks') ?></textarea>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-4 gap-3 flex-wrap">
            <div class="text-muted" style="font-size: 0.75rem; max-width: 640px;">
                <i class="fa-solid fa-shield-halved me-1"></i>
                By submitting, you certify that the vehicle was physically inspected.
                A failed item automatically cancels this trip, locks the vehicle state to
                <strong>Under Maintenance</strong>, and creates a PIR ticket for the mechanic queue.
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check me-1"></i> Submit Safety Check
            </button>
        </div>
    </form>
</div>

<!-- Previous Checks -->
<?php if (!empty($history)): ?>
    <div class="card-panel mt-4">
        <h2 class="h6 mb-3 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Previous Checks for This Ticket</h2>
        <div class="table-responsive">
            <table class="table-minimal">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Result</th>
                        <th>Failed Items</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="mono" style="font-size: 0.78rem;"><?= esc($h['created_at']) ?></td>
                            <td>
                                <span class="badge <?= $h['result'] === 'pass' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= strtoupper($h['result']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem;"><?= esc($h['failed_items'] ? (implode(', ', (array) json_decode($h['failed_items'], true)) ?: $h['failed_items']) : '—') ?></td>
                            <td class="text-muted" style="font-size: 0.8rem;"><?= esc($h['remarks'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
