<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 5 &bull; Mechanic Workflow &bull; FR-5.3
        </div>
        <h1 class="page-title mb-1">Pre-Repair Inspection (PIR) Mechanic Queue</h1>
        <p class="text-muted small mb-0">
            Automated tickets from BLOWBAGETS failures and roadside breakdowns.
            Record pre-inspection findings and post-repair evaluations before restoring a vehicle to Available status.
        </p>
    </div>
    <a href="<?= base_url('safety') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-clipboard-check me-1"></i> Safety Registry
    </a>
</div>

<!-- Status Filters -->
<ul class="nav nav-pills mb-4 gap-2">
    <?php
        $filters = [
            ''        => 'Open Queue',
            'pending' => 'Pending',
            'in_inspection' => 'In Inspection',
            'in_repair' => 'In Repair',
            'ready_for_release' => 'Ready for Release',
            'released' => 'Released',
        ];
    ?>
    <?php foreach ($filters as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link py-1 px-3 small <?= ($activeFilter === ($key ?: 'open') || ($key === '' && $activeFilter === 'open')) ? 'active' : '' ?>"
               href="<?= base_url('pir' . ($key ? '?status=' . $key : '')) ?>">
                <?= $label ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Queue Table -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>PIR Number</th>
                    <th>Vehicle</th>
                    <th>Source</th>
                    <th>Defect Description</th>
                    <th>Status</th>
                    <th>Mechanic</th>
                    <th>Vehicle State</th>
                    <th>Date Filed</th>
                    <th class="text-end" style="width: 1%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($queue)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-screwdriver-wrench fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No PIR tickets in this queue. All vehicles are serviceable.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queue as $p): ?>
                        <?php
                            $statusBadge = match ($p['status']) {
                                'pending'          => 'bg-secondary',
                                'in_inspection'    => 'bg-info',
                                'in_repair'        => 'bg-primary',
                                'ready_for_release'=> 'bg-warning text-dark',
                                'released'         => 'bg-success',
                                default            => 'bg-secondary',
                            };
                            $vehicleBadge = match ($p['vehicle_status']) {
                                'active'             => 'bg-success',
                                'under_maintenance'  => 'bg-warning text-dark',
                                'disabled_breakdown' => 'bg-danger',
                                'maintenance'        => 'bg-warning text-dark',
                                'out_of_service'     => 'bg-danger',
                                default              => 'bg-secondary',
                            };
                        ?>
                        <tr>
                            <td>
                                <span class="mono fw-semibold" style="font-size: 0.8rem; color: #0f172a;"><?= esc($p['pir_number']) ?></span>
                            </td>
                            <td>
                                <span class="mono fw-semibold"><?= esc($p['plate_number'] ?? 'N/A') ?></span>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= esc(trim(($p['make'] ?? '') . ' ' . ($p['model'] ?? ''))) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= esc(ucfirst($p['source'])) ?></span>
                            </td>
                            <td style="font-size: 0.8rem; max-width: 320px;">
                                <?= esc(mb_substr($p['defect_description'], 0, 140)) ?><?= strlen($p['defect_description']) > 140 ? '…' : '' ?>
                            </td>
                            <td><span class="badge <?= $statusBadge ?>"><?= esc(str_replace('_', ' ', ucfirst($p['status']))) ?></span></td>
                            <td style="font-size: 0.8rem;"><?= esc($p['mechanic_name'] ?? 'Unassigned') ?></td>
                            <td><span class="badge <?= $vehicleBadge ?>"><?= esc(str_replace('_', ' ', ucfirst($p['vehicle_status'] ?? 'unknown'))) ?></span></td>
                            <td class="mono text-muted" style="font-size: 0.75rem;"><?= esc($p['created_at']) ?></td>
                            <td class="table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('pir/' . $p['id']) ?>" class="btn-icon-sm btn-outline-secondary" title="View Ticket">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
