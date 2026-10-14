<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN-F-018 rev2 - <?= esc($request['request_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 11pt;
            line-height: 1.4;
        }
        .mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-rep {
            font-size: 9.5pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .header-pia {
            font-size: 13pt;
            font-weight: 700;
            margin: 2px 0;
        }
        .header-addr {
            font-size: 8.5pt;
            color: #333;
        }
        .form-title-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 15px;
        }
        .form-title {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-decoration: underline;
        }
        .form-code {
            font-size: 8.5pt;
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid #000;
            padding: 3px 8px;
        }
        .meta-table, .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .content-table th, .content-table td {
            border: 1px solid #000;
            padding: 7px 10px;
            vertical-align: top;
            font-size: 10pt;
        }
        .content-table th {
            background-color: #f2f2f2;
            text-align: left;
            font-weight: 600;
            width: 25%;
        }
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .sig-box {
            width: 48%;
            border: 1px solid #000;
            padding: 12px;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-label {
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
        }
        .sig-name {
            font-weight: 700;
            font-size: 11pt;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 40px;
        }
        .sig-title {
            font-size: 8.5pt;
            text-align: center;
            color: #333;
        }
        .footer-note {
            margin-top: 25px;
            font-size: 7.5pt;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            text-align: center;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Header (Screen Only) -->
    <div class="no-print" style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 12px 20px; margin-bottom: 20px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>ADMIN-F-018 rev2 Print Preview</strong> &bull; <?= esc($request['request_number']) ?>
        </div>
        <div>
            <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Print / Save PDF
            </button>
            <button onclick="window.close()" style="background: #e2e8f0; color: #334155; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; margin-left: 6px;">
                Close
            </button>
        </div>
    </div>

    <!-- Official Government Header -->
    <div class="header">
        <div class="header-rep">Republic of the Philippines</div>
        <div class="header-pia">PHILIPPINE INFORMATION AGENCY</div>
        <div class="header-addr">PIA Bldg., Visayas Avenue, Diliman, Quezon City 1128 &bull; Tel. No. 8920-1224</div>
        <div style="font-size: 8pt; font-weight: 600; margin-top: 4px; letter-spacing: 0.05em;">ADMINISTRATIVE DIVISION &bull; MOTORPOOL UNIT</div>
    </div>

    <!-- Form Title & Control Number -->
    <div class="form-title-box">
        <div>
            <div class="form-title">VEHICLE REQUEST SLIP (VRS)</div>
        </div>
        <div style="text-align: right;">
            <div class="form-code">FORM NO. ADMIN-F-018 rev2</div>
            <div class="mono" style="font-size: 9pt; font-weight: 700; margin-top: 4px;">
                CONTROL NO: <?= esc($request['request_number']) ?>
            </div>
        </div>
    </div>

    <!-- Main Content Table -->
    <table class="content-table">
        <tr>
            <th>Date & Time of Request</th>
            <td class="mono"><?= date('F d, Y \a\t h:i A', strtotime($request['created_at'])) ?></td>
        </tr>
        <tr>
            <th>Originating Division / Office</th>
            <td>
                <strong><?= esc($request['office_name'] ?? 'PIA Central Office') ?></strong>
                <span class="mono" style="font-size: 8.5pt;">(<?= esc($request['office_code'] ?? 'CO') ?>)</span>
                &bull; Scope: <?= strtoupper(esc($request['office_scope'])) ?>
            </td>
        </tr>
        <tr>
            <th>Department Information</th>
            <td>
                <?php if (!empty($request['department_name'])): ?>
                    <strong><?= esc($request['department_name']) ?></strong>
                    <span class="mono" style="font-size: 8.5pt;">(<?= esc($request['department_code'] ?? '') ?>)</span>
                    <?php if (!empty($request['department_head'])): ?>
                        &bull; Head: <?= esc($request['department_head']) ?>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: #777;">Not specified</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Requesting Official / Staff</th>
            <td>
                <?= esc($request['requestor_full_name'] ?? $request['requestor_name']) ?>
                <span style="color: #444; font-size: 9pt;">- <?= esc($request['requestor_designation'] ?? 'Staff') ?></span>
            </td>
        </tr>
        <tr>
            <th>Official Destination</th>
            <td><strong><?= esc($request['destination']) ?></strong></td>
        </tr>
        <tr>
            <th>Purpose of Travel / Coverage</th>
            <td style="min-height: 50px;">
                <?= nl2br(esc($request['purpose'])) ?>
            </td>
        </tr>
        <tr>
            <th>Schedule of Deployment</th>
            <td class="mono">
                Departure: <strong><?= date('M d, Y h:i A', strtotime($request['departure_time'])) ?></strong><br>
                Expected Return: <strong><?= date('M d, Y h:i A', strtotime($request['return_time'])) ?></strong>
            </td>
        </tr>
        <tr>
            <th>Passenger Manifest</th>
            <td>
                <div style="margin-bottom: 4px; font-weight: 600; font-size: 9pt;">Total Passengers: <?= esc($request['passenger_count']) ?></div>
                <div class="mono" style="font-size: 9pt; line-height: 1.5;">
                    <?= nl2br(esc($request['passenger_names'])) ?>
                </div>
            </td>
        </tr>
        <tr>
            <th>Vehicle / Driver Assigned</th>
            <td>
                <?php if (!empty($request['plate_number'])): ?>
                    <span class="mono fw-bold"><?= esc($request['plate_number']) ?></span> (<?= esc($request['vehicle_make'] . ' ' . $request['vehicle_model']) ?>) &bull; Driver: <?= esc($request['requested_driver_name'] ?? 'Assigned Pool Driver') ?>
                <?php else: ?>
                    <em>Awaiting motorpool dispatch assignment</em>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ((int)$request['is_rush_request'] === 1): ?>
            <tr>
                <th style="color: #990000;">Emergency Justification (BR-03)</th>
                <td style="color: #990000; font-size: 9pt;">
                    <strong>SAME-DAY DEPLOYMENT:</strong> <?= esc($request['justification_notes'] ?? 'Emergency coverage') ?><br>
                    <span class="mono" style="font-size: 8pt;">Supporting document uploaded and verified on system.</span>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- Signature Boxes -->
    <div class="signature-section">
        <!-- Recommending Approval -->
        <div class="sig-box">
            <div class="sig-label">Recommending Approval (Tier 1):</div>
            <div>
                <div class="sig-name"><?= esc($request['oic_approver_name'] ?? 'Motorpool Dispatcher') ?></div>
                <div class="sig-title"><?= esc($request['oic_approver_designation'] ?? 'Motorpool Dispatcher (Tier 1 Approver)') ?></div>
                <div class="mono" style="font-size: 7.5pt; text-align: center; margin-top: 2px;">
                    Date Signed: <?= $request['oic_action_at'] ? date('M d, Y h:i A', strtotime($request['oic_action_at'])) : '____________________' ?>
                </div>
            </div>
        </div>

        <!-- Final Approval -->
        <div class="sig-box">
            <div class="sig-label">Approved By (Tier 2):</div>
            <div>
                <div class="sig-name"><?= esc($request['admin_approver_name'] ?? 'Atty. Julius S. De Peralta') ?></div>
                <div class="sig-title">Head, Administrative Division</div>
                <div class="mono" style="font-size: 7.5pt; text-align: center; margin-top: 2px;">
                    Date Signed: <?= $request['admin_action_at'] ? date('M d, Y h:i A', strtotime($request['admin_action_at'])) : '____________________' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Statutory Notice -->
    <div class="footer-note">
        In compliance with Civil Service Commission (CSC) and Commission on Audit (COA) Circular No. 75-6. Official vehicles shall strictly be used for authorized state business. Approved VRS is automatically converted to Driver's Trip Ticket Section A (ADMIN-F-001 rev1).
    </div>

</body>
</html>
