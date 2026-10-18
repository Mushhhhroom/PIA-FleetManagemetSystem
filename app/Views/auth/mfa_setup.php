<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            NFR-2 &bull; Account Security &bull; TOTP Multi-Factor Authentication
        </div>
        <h1 class="page-title mb-1">Multi-Factor Authentication Setup</h1>
        <p class="text-muted small mb-0">
            Add a time-based one-time password (TOTP) step to your sign-in, as required by the BRD technology stack
            (Argon2id + TOTP MFA) and RA 10173 data privacy safeguards.
        </p>
    </div>
    <a href="<?= base_url('/') ?>" class="btn btn-secondary text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Portal
    </a>
</div>

<?php if ($enabled): ?>
    <div class="alert border-0 rounded-2 p-3 mb-4" style="background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important;">
        <i class="fa-solid fa-circle-check me-2"></i>
        <strong>MFA is ENABLED.</strong> You will be asked for a 6-digit code each time you sign in.
    </div>

    <div class="card-panel" style="max-width: 640px;">
        <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-toggle-on me-2 text-success"></i> Current Protection</h2>
        <p class="text-muted small">
            Your account currently requires a rotating 6-digit code from your authenticator app in addition to your password.
        </p>
        <form action="<?= base_url('mfa/disable') ?>" method="POST" onsubmit="return confirm('Disable multi-factor authentication? Your account will be protected by password only.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger fw-semibold">
                <i class="fa-solid fa-toggle-off me-1"></i> Disable MFA
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-panel">
                <h2 class="h6 fw-bold mb-3"><i class="fa-solid fa-mobile-screen me-2" style="color: var(--primary-accent);"></i> Enroll an Authenticator App</h2>

                <ol class="small mb-4" style="line-height: 1.9;">
                    <li>Install an authenticator app (Google Authenticator, Microsoft Authenticator, Authy, 1Password…).</li>
                    <li>Enter the setup key below manually, or scan the QR code shown on the right.</li>
                    <li>Enter the 6-digit code it generates to confirm and enable MFA.</li>
                </ol>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Setup Key (Base32 secret)</label>
                    <div class="input-group">
                        <input type="text" class="form-control mono" id="totpSecret" value="<?= esc($secret) ?>" readonly style="font-family: Consolas, monospace; letter-spacing: 0.08em;">
                        <button type="button" class="btn btn-outline-secondary" onclick="copySecret()">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                    <div class="form-text">Keep this key secret — it is only shown during enrollment.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Provisioning URI</label>
                    <input type="text" class="form-control mono" value="<?= esc($uri) ?>" readonly
                           style="font-family: Consolas, monospace; font-size: 0.75rem;">
                </div>

                <form action="<?= base_url('mfa/enable') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="secret" value="<?= esc($secret) ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Confirm 6-digit code</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   name="code" class="form-control text-center" placeholder="000000" required
                                   style="letter-spacing: 0.4rem; font-weight: 600;">
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-shield-halved me-1"></i> Enable MFA
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-panel text-center">
                <h2 class="h6 fw-bold mb-3">QR Code</h2>
                <div id="qrcode" class="d-inline-block p-2 border rounded-2 bg-white"></div>
                <p class="text-muted small mt-3 mb-0">
                    Scan with your authenticator app, or use the setup key on the left if scanning is unavailable.
                </p>
            </div>

            <div class="card-panel mt-4">
                <h2 class="h6 fw-bold mb-2"><i class="fa-solid fa-circle-info me-2" style="color: var(--primary-accent);"></i> How it works</h2>
                <p class="text-muted small mb-0">
                    After your password is accepted, the sign-in flow pauses on a verification screen until you enter
                    the current 6-digit code. Codes rotate every 30 seconds and a ±1 step clock drift is tolerated.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    function copySecret() {
        const field = document.getElementById('totpSecret');
        field.select();
        navigator.clipboard ? navigator.clipboard.writeText(field.value) : document.execCommand('copy');
    }
    <?php if (!$enabled): ?>
    (function () {
        const el = document.getElementById('qrcode');
        if (window.QRCode && el) {
            new QRCode(el, { text: <?= json_encode($uri) ?>, width: 190, height: 190 });
        } else if (el) {
            el.innerHTML = '<div class="text-muted small p-4">QR library unavailable — use the setup key.</div>';
        }
    })();
    <?php endif; ?>
</script>
<?= $this->endSection() ?>
