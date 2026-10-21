<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; PIA Motorpool Fleet Management System</title>
    <!-- Inter & JetBrains Mono Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --bg-canvas: #f8fafc;
            --border-subtle: #e2e8f0;
            --border-strong: #cbd5e1;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --primary: #1e3a8a;
            --primary-accent: #2563eb;
            --primary-hover: #1d4ed8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-canvas);
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(37, 99, 235, 0.05), transparent 40%),
                radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 100% 100%, 24px 24px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-sans);
            color: var(--text-body);
            padding: 32px 16px;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        .mono {
            font-family: var(--font-mono);
        }

        .login-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
            position: relative;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0A2540, #1e3a8a);
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(10, 37, 64, 0.2);
        }

        .brand-title {
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-heading);
            line-height: 1.15;
        }

        .brand-subtitle {
            font-size: 0.70rem;
            font-family: var(--font-mono);
            color: var(--text-muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .page-heading {
            font-size: 1.22rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .page-subtext {
            font-size: 0.83rem;
            color: var(--text-muted);
            margin-bottom: 22px;
            line-height: 1.45;
        }

        .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 6px;
            letter-spacing: -0.01em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .input-group-corporate {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-corporate .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.88rem;
            pointer-events: none;
            z-index: 10;
        }

        .form-control-corporate {
            width: 100%;
            padding: 9px 38px 9px 36px;
            font-size: 0.88rem;
            font-family: var(--font-sans);
            color: var(--text-heading);
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .form-control-corporate:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Action buttons inside inputs (Clear & Show/Hide Password) */
        .input-action-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 4px 6px;
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            z-index: 10;
            border-radius: 4px;
            transition: color 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-action-btn:hover {
            color: var(--text-heading);
        }

        .btn-corporate-primary {
            width: 100%;
            padding: 10px 16px;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: #ffffff;
            background: #0f172a;
            border: 1px solid #0f172a;
            border-radius: 8px;
            transition: all 0.15s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-corporate-primary:hover {
            background: #1e293b;
            border-color: #1e293b;
            color: #ffffff;
        }

        /* Google SSO Button */
        .btn-google-sso {
            width: 100%;
            padding: 9px 16px;
            font-size: 0.84rem;
            font-weight: 500;
            color: #334155;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            transition: all 0.15s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 10px;
        }

        .btn-google-sso:hover {
            background: #f8fafc;
            border-color: var(--border-strong);
            color: #0f172a;
        }

        .quick-role-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 7px 10px;
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: 6px;
            font-size: 0.78rem;
            color: var(--text-body);
            text-align: left;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .quick-role-btn:hover {
            background: #ffffff;
            border-color: var(--border-strong);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            color: var(--text-heading);
        }

        .role-chip {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 4px;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            color: var(--text-muted);
            white-space: nowrap;
        }

        .alert-corporate {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-corporate-danger {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
        }

        .alert-corporate-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
        }

        .footer-note {
            text-align: center;
            margin-top: 22px;
            font-size: 0.72rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Brand Header -->
        <div class="brand-header">
            <div class="brand-icon">
                <i class="fa-solid fa-landmark"></i>
            </div>
            <div>
                <div class="brand-title">PIA <span style="color: #2563eb; font-weight: 700;">Motorpool</span></div>
                <div class="brand-subtitle">Republic of the Philippines</div>
            </div>
        </div>

        <div class="page-heading">Fleet Management System</div>
        <div class="page-subtext">Sign in to access official vehicle requests, approvals, dispatch, and gate security.</div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-corporate alert-corporate-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                <div><?= esc(session()->getFlashdata('error')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert-corporate alert-corporate-success" role="alert">
                <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                <div><?= esc(session()->getFlashdata('success')) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('login') ?>" method="POST" id="loginForm">
            <?= csrf_field() ?>

            <!-- Work Email with Clear Button -->
            <div class="mb-3">
                <label class="form-label" for="email">
                    <span>Government Work Email</span>
                    <span class="mono text-muted" style="font-size: 0.70rem;">user@pia.gov.ph</span>
                </label>
                <div class="input-group-corporate">
                    <i class="fa-regular fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" class="form-control-corporate mono" placeholder="user@pia.gov.ph" required value="admin@pia.gov.ph" autocomplete="email">
                    <button type="button" class="input-action-btn" id="clearEmailBtn" title="Clear input" aria-label="Clear email">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <!-- Password with Show/Hide Toggle -->
            <div class="mb-4">
                <label class="form-label" for="password">
                    <span>Account Password</span>
                    <span class="mono text-muted" style="font-size: 0.70rem;">Encrypted &bull; NFR-2</span>
                </label>
                <div class="input-group-corporate">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-control-corporate" placeholder="••••••••" required value="Admin_PIA2026!" autocomplete="current-password">
                    <button type="button" class="input-action-btn" id="togglePasswordBtn" title="Show or hide password" aria-label="Toggle password visibility">
                        <i class="fa-regular fa-eye" id="passwordEyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Primary Submit -->
            <button type="submit" class="btn-corporate-primary">
                <span>Sign In to System</span>
                <i class="fa-solid fa-arrow-right" style="font-size: 0.8rem;"></i>
            </button>

            <!-- Google Workspace SSO (OAuth Ready) -->
            <button type="button" class="btn-google-sso" id="googleSsoBtn" onclick="handleGoogleSso()" title="Google Account Single Sign-On (GovSuite)">
                <svg width="16" height="16" viewBox="0 0 24 24" style="flex-shrink: 0;">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google Account</span>
            </button>
        </form>

        <!-- Fast Role Profiles (All 7 Government RBAC Roles) -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="mono" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); font-weight: 600;">1-Click Role Profiles (Demonstration)</span>
                <span class="badge bg-light text-secondary border mono" style="font-size: 0.62rem;">7 RBAC ROLES</span>
            </div>

            <div class="d-flex flex-column gap-1" style="max-height: 200px; overflow-y: auto;">
                <button type="button" class="quick-role-btn" onclick="setCreds('admin@pia.gov.ph', 'Admin_PIA2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-shield-halved text-primary" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Super Admin</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; admin@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Admin</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('requestor@pia.gov.ph', 'Requestor_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-file-signature" style="color: #8b5cf6; font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Requestor (Staff)</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; requestor@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Staff</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('dispatcher@pia.gov.ph', 'Dispatch_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-truck-fast text-success" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Motorpool Dispatcher</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; dispatcher@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Dispatch</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('admin.head@pia.gov.ph', 'AdminHead_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-stamp text-info" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Tier 2: Atty. De Peralta</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; admin.head@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Admin Head</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('guard@pia.gov.ph', 'Guard_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-qrcode text-secondary" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Gate Security Guard</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; guard@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Guard</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('auditor@pia.gov.ph', 'Auditor_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-scale-balanced text-danger" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">COA Resident Auditor</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; auditor@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">COA</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('driver.santos@pia.gov.ph', 'Driver_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-compass text-primary" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Official Driver (R. Santos)</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; driver.santos@pia.gov.ph</span>
                    </div>
                    <span class="role-chip">Driver</span>
                </button>

                <button type="button" class="quick-role-btn" onclick="setCreds('maintenance@fleet.com', 'Maintenance_2026!')">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fa-solid fa-screwdriver-wrench text-warning" style="font-size: 0.8rem; width: 14px;"></i>
                        <span class="fw-semibold small">Motorpool Mechanic</span>
                        <span class="text-muted small text-truncate" style="font-size: 0.7rem;">&bull; maintenance@fleet.com</span>
                    </div>
                    <span class="role-chip">Mechanic</span>
                </button>
            </div>
        </div>

        <div class="footer-note">
            <span class="mono">Philippine Information Agency &bull; Motorpool FMS v2.2</span>
        </div>
    </div>

    <script>
        // 1. Fast Role Profile Setter
        function setCreds(email, pwd) {
            const emailInput = document.getElementById('email');
            const pwdInput = document.getElementById('password');
            emailInput.value = email;
            pwdInput.value = pwd;
            checkClearBtnState();
        }

        // 2. Clear Email Button Functionality
        const emailInput = document.getElementById('email');
        const clearEmailBtn = document.getElementById('clearEmailBtn');

        function checkClearBtnState() {
            if (clearEmailBtn) {
                clearEmailBtn.style.display = emailInput.value.length > 0 ? 'flex' : 'none';
            }
        }

        if (clearEmailBtn && emailInput) {
            clearEmailBtn.addEventListener('click', function () {
                emailInput.value = '';
                emailInput.focus();
                checkClearBtnState();
            });
            emailInput.addEventListener('input', checkClearBtnState);
            checkClearBtnState(); // initial evaluation
        }

        // 3. Show / Hide Password Toggle Functionality
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const eyeIcon = document.getElementById('passwordEyeIcon');

        if (togglePasswordBtn && passwordInput && eyeIcon) {
            togglePasswordBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.className = isPassword ? 'fa-regular fa-eye-slash text-primary' : 'fa-regular fa-eye';
                togglePasswordBtn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
            });
        }

        // 4. Google Account OAuth Hook (Pre-configured placeholder)
        function handleGoogleSso() {
            alert('Google Workspace OAuth: Single Sign-On endpoint configured. To link agency credentials, provide GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in the government .env configuration.');
        }
    </script>
</body>
</html>
