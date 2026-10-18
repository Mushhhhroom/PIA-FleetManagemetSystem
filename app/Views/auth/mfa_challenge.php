<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification | PIA-AFMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #334155;
        }
        .card-shell {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 420px;
            padding: 34px 32px;
        }
        .brand-dot { width: 42px; height: 42px; border-radius: 8px; background: #0f172a; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 16px; }
        h1 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .muted { font-size: 0.85rem; color: #64748b; }
        .code-input {
            letter-spacing: 0.55rem;
            font-family: 'JetBrains Mono', Consolas, monospace;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
        }
        .btn-primary { background: #0f172a; border-color: #0f172a; font-weight: 600; padding: 10px; border-radius: 7px; }
        .btn-primary:hover { background: #1e293b; border-color: #1e293b; }
        .alert { font-size: 0.85rem; border-radius: 7px; }
    </style>
</head>
<body>
    <div class="card-shell">
        <div class="brand-dot"><i class="fa-solid fa-shield-halved"></i></div>
        <h1>Two-Factor Authentication</h1>
        <p class="muted mb-4">
            Your password was verified. Enter the 6-digit time-based code (TOTP) from your
            authenticator app to complete sign-in.
        </p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('info')): ?>
            <div class="alert alert-info"><i class="fa-solid fa-circle-info me-2"></i><?= session()->getFlashdata('info') ?></div>
        <?php endif; ?>

        <form action="<?= base_url('login/mfa') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Authentication Code</label>
                <input type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}"
                       maxlength="6" name="code" class="form-control code-input" placeholder="000000" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-key me-1"></i> Verify &amp; Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="<?= base_url('logout') ?>" class="muted" style="font-size: 0.8rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to sign in
            </a>
        </div>
    </div>
</body>
</html>
