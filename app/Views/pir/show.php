<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 5 &bull; Mechanic Workflow &bull; FR-5.3
        </div>
        <h1 class="page-title mb-1">PIR Ticket <?= esc($pir['pir_number']) ?></h1>
        <p class="text-muted small mb-0">Pre-inspection findings and post-repair evaluation are both mandatory before release.</p>
    </div>
    <a href="<?= base_url('pir') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Queue
    </a>
</div>

<?php
    $statusBadge = match ($pir['status']) {
        'pending'          => 'bg-secondary',
        'in_inspection'    => 'bg-info',
        'in_repair'        => 'bg-primary',
        'ready_for_release'=> 'bg-warning text-dark',
        'released'         => 'bg-success',
        default            => 'bg-secondary',
    };
?>

<!-- Ticket Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Status</div>
            <div class="kpi-value" style="font-size: 1rem;"><span class="badge <?= $statusBadge ?>"><?= esc(str_replace('_', ' ', ucfirst($pir['status']))) ?></span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Vehicle</div>
            <div class="kpi-value" style="font-size: 1rem;"><?= esc($pir['plate_number'] ?? 'N/A') ?></div>
            <div class="kpi-sub"><?= esc(trim(($pir['make'] ?? '') . ' ' . ($pir['model'] ?? '') . ' ' . ($pir['year'] ?? ''))) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Vehicle State</div>
            <div class="kpi-value" style="font-size: 1rem;">
                <span class="badge <?= in_array($pir['vehicle_status'], ['active'], true) ? 'bg-success' : 'bg-danger' ?>">
                    <?= esc(str_replace('_', ' ', ucfirst($pir['vehicle_status'] ?? 'unknown'))) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Source / Mechanic</div>
            <div class="kpi-value" style="font-size: 1rem;"><?= esc(ucfirst($pir['source'])) ?></div>
            <div class="kpi-sub"><?= esc($pir['mechanic_name'] ?? 'Unassigned') ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Defect + Findings -->
    <div class="col-lg-7">
        <div class="card-panel mb-4">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i> Defect Description</h2>
            <p class="mb-0" style="font-size: 0.88rem;"><?= nl2br(esc($pir['defect_description'])) ?></p>
            <div class="text-muted mt-3" style="font-size: 0.75rem;">
                Filed <?= esc($pir['created_at']) ?>
                <?= $pir['released_at'] ? ' &bull; Released ' . esc($pir['released_at']) : '' ?>
            </div>
        </div>

        <div class="card-panel mb-4">
            <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-magnifying-glass me-2" style="color: var(--primary-accent);"></i> Recorded Findings</h2>
            <div class="mb-3">
                <div class="kpi-label">Pre-Inspection Findings</div>
                <div style="font-size: 0.85rem;"><?= $pir['pre_inspection_findings'] ? nl2br(esc($pir['pre_inspection_findings'])) : '<span class="text-muted fst-italic">Not yet recorded.</span>' ?></div>
            </div>
            <div class="mb-3">
                <div class="kpi-label">Post-Repair Evaluation</div>
                <div style="font-size: 0.85rem;"><?= $pir['post_repair_evaluation'] ? nl2br(esc($pir['post_repair_evaluation'])) : '<span class="text-muted fst-italic">Not yet recorded.</span>' ?></div>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-2">
                    <div class="kpi-label">Parts Replaced</div>
                    <div style="font-size: 0.85rem;"><?= esc($pir['parts_replaced'] ?? '—') ?></div>
                </div>
                <div class="col-sm-6 mb-2">
                    <div class="kpi-label">Repair Cost</div>
                    <div class="mono" style="font-size: 0.85rem;">₱<?= number_format((float) ($pir['repair_cost'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow Actions -->
    <div class="col-lg-5">
        <?php if ($pir['status'] === 'released'): ?>
            <div class="card-panel">
                <div class="text-center py-4">
                    <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                    <h3 class="h6 fw-bold">Ticket Closed</h3>
                    <p class="text-muted small mb-0">
                        Released <?= esc($pir['released_at'] ?? '—') ?>.
                        The vehicle has been restored to <strong>Available</strong> status.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="card-panel">
                <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-list-check me-2" style="color: var(--primary-accent);"></i> FR-5.3 Workflow</h2>
                <form action="<?= base_url('pir/' . $pir['id']) ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Pre-Inspection Findings <span class="text-danger">*</span>
                        </label>
                        <textarea name="pre_inspection_findings" rows="3" class="form-control" placeholder="Diagnosis, measurements, suspected cause..."><?= esc($pir['pre_inspection_findings'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">
                            Post-Repair Evaluation <span class="text-muted">(mandatory before release)</span>
                        </label>
                        <textarea name="post_repair_evaluation" rows="3" class="form-control" placeholder="Road-test results, verification of repair, safety re-check..."><?= esc($pir['post_repair_evaluation'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label small fw-semibold text-dark">Parts Replaced</label>
                            <input type="text" name="parts_replaced" class="form-control" value="<?= esc($pir['parts_replaced'] ?? '') ?>" placeholder="e.g. brake pads, alternator belt">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label small fw-semibold text-dark">Repair Cost (₱)</label>
                            <input type="number" step="0.01" min="0" name="repair_cost" class="form-control" value="<?= esc($pir['repair_cost'] ?? '0.00') ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if ($pir['status'] === 'pending'): ?>
                            <button type="submit" name="action" value="inspect" class="btn btn-primary">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Start Inspection (record findings)
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($pir['status'], ['pending', 'in_inspection'], true)): ?>
                            <button type="submit" name="action" value="repair" class="btn btn-secondary">
                                <i class="fa-solid fa-gear me-1"></i> Begin Repair
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($pir['status'], ['in_inspection', 'in_repair'], true)): ?>
                            <button type="submit" name="action" value="ready" class="btn btn-outline-warning">
                                <i class="fa-solid fa-flag-checkered me-1"></i> Mark Ready for Release (post-repair eval required)
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($pir['status'], ['ready_for_release', 'in_repair'], true)): ?>
                            <button type="submit" name="action" value="release" class="btn btn-success"
                                    onclick="return confirm('Restore this vehicle to AVAILABLE status? Both evaluations are mandatory (FR-5.3).');">
                                <i class="fa-solid fa-unlock me-1"></i> Release Vehicle to Available Status
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
