<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-bell me-1"></i> In-App Notification Center
        </div>
        <h1 class="page-title mb-1">My Notifications</h1>
        <p class="text-muted small mb-0">
            Approval escalations, rejections, safety locks, RFID and regulatory alerts delivered to your account
            (every email notification is mirrored here).
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($unread > 0): ?>
            <form action="<?= base_url('notifications/read-all') ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary text-decoration-none">
                    <i class="fa-solid fa-check-double me-1"></i> Mark all as read
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filter tabs -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= base_url('notifications') ?>" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-secondary' ?>">
        All
    </a>
    <a href="<?= base_url('notifications?filter=unread') ?>" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem; color: #f59e0b;"></i> Unread
        <span class="badge bg-dark ms-1"><?= $unread ?></span>
    </a>
</div>

<div class="card-panel">
    <?php if (empty($items)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-bell-slash fa-2x mb-3 d-block text-secondary opacity-50"></i>
            No notifications to show.
        </div>
    <?php else: ?>
        <ul class="list-unstyled mb-0">
            <?php foreach ($items as $n): ?>
                <?php
                    $typeColor = match ($n['type'] ?? 'info') {
                        'danger'  => '#dc2626',
                        'warning' => '#f59e0b',
                        'success' => '#16a34a',
                        default   => '#2563eb',
                    };
                    $isUnread = (int) ($n['is_read'] ?? 0) === 0;
                ?>
                <li class="d-flex align-items-start gap-3 py-3 border-bottom" style="<?= $isUnread ? 'background: rgba(37,99,235,0.03);' : '' ?>">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center mt-1 flex-shrink-0"
                          style="width: 34px; height: 34px; background: <?= $typeColor ?>1a; color: <?= $typeColor ?>;">
                        <i class="fa-solid <?= $isUnread ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                    </span>

                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <?php if ($isUnread): ?>
                                <span class="badge bg-primary" style="font-size: 0.6rem;">NEW</span>
                            <?php endif; ?>
                            <span class="fw-semibold text-dark" style="font-size: 0.88rem;"><?= esc($n['title']) ?></span>
                        </div>
                        <div class="text-muted" style="font-size: 0.8rem;"><?= esc($n['message']) ?></div>
                        <div class="mono text-muted mt-1" style="font-size: 0.68rem;"><?= esc($n['created_at']) ?></div>
                    </div>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <?php if (!empty($n['link'])): ?>
                            <a href="<?= esc($n['link']) ?>" class="btn-icon-xs btn-secondary text-decoration-none" title="Open">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <form action="<?= base_url('notifications/' . $n['id'] . '/read') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-icon-xs btn-secondary" title="Mark as read">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
