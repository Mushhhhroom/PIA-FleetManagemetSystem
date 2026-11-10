<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN-F-001 rev1 - <?= esc($ticket['ticket_serial_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            font-size: 9.5pt;
            line-height: 1.35;
        }
        .mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-rep {
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .header-pia {
            font-size: 12pt;
            font-weight: 700;
            margin: 1px 0;
        }
        .header-addr {
            font-size: 7.5pt;
            color: #333;
        }
        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .form-title {
            font-size: 13pt;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-decoration: underline;
        }
        .section-header {
            background-color: #000;
            color: #fff;
            padding: 4px 8px;
            font-weight: 700;
            font-size: 9pt;
            letter-spacing: 0.05em;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        table.tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.tbl th, table.tbl td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 8.5pt;
            vertical-align: top;
        }
        table.tbl th {
            background-color: #f2f2f2;
            text-align: left;
            font-weight: 600;
        }
        .fuel-box {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 15px;
        }
        .sig-block {
            width: 48%;
            border: 1px solid #000;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100px;
        }
        .footer-note {
            margin-top: 12px;
            font-size: 7pt;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 4px;
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
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>

    <!-- Print Control Header (Screen Only) -->
    <div class="no-print" style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px 16px; margin-bottom: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>ADMIN-F-001 rev1 Print Preview</strong> &bull; <?= esc($ticket['ticket_serial_no']) ?>
        </div>
        <div>
            <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 5px 14px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Print / Save PDF
            </button>
            <button onclick="window.close()" style="background: #e2e8f0; color: #334155; border: none; padding: 5px 14px; border-radius: 4px; cursor: pointer; margin-left: 6px;">
                Close
            </button>
        </div>
    </div>

    <!-- Official Government Header -->
    <div class="header">
        <div class="header-rep">Republic of the Philippines</div>
        <div class="header-pia">PHILIPPINE INFORMATION AGENCY</div>
        <div class="header-addr">PIA Bldg., Visayas Avenue, Diliman, Quezon City 1128 &bull; Administrative Division</div>
    </div>

    <!-- Title & QR Block -->
    <div class="title-row">
        <div>
            <div class="form-title">DRIVER'S TRIP TICKET (DTT)</div>
            <div class="mono" style="font-size: 8pt; color: #333;">FORM NO. ADMIN-F-001 rev1</div>
            <div class="mono fw-bold" style="font-size: 10pt; margin-top: 2px;">
                SERIAL NO: <?= esc($ticket['ticket_serial_no']) ?>
            </div>
        </div>
        <div style="text-align: right; display: flex; align-items: center; gap: 10px;">
            <div id="printQr" style="width: 75px; height: 75px;"></div>
            <div class="mono" style="font-size: 7.5pt; text-align: right;">
                VRS Ref: <?= esc($ticket['request_number']) ?><br>
                Status: <?= strtoupper($ticket['status']) ?><br>
                Date: <?= date('M d, Y', strtotime($ticket['created_at'])) ?>
            </div>
        </div>
    </div>

    <!-- SECTION A -->
    <div class="section-header">A. TO BE ACCOMPLISHED BY APPROVING AUTHORITY (TRAVEL AUTHORIZATION)</div>
    <table class="tbl">
        <tr>
            <th style="width: 20%;">Vehicle Plate Number</th>
            <td class="mono fw-bold" style="width: 30%;"><?= esc($ticket['plate_number']) ?> (<?= esc($ticket['make'] . ' ' . $ticket['model']) ?>)</td>
            <th style="width: 20%;">Assigned Driver</th>
            <td style="width: 30%;"><strong><?= esc($ticket['driver_name']) ?></strong> (<?= esc($ticket['driver_code']) ?>)</td>
        </tr>
        <tr>
            <th>Authorized Destination</th>
            <td colspan="3"><strong><?= esc($ticket['authorized_destination']) ?></strong></td>
        </tr>
        <tr>
            <th>Purpose of Travel</th>
            <td colspan="3"><?= esc($ticket['authorized_purpose']) ?></td>
        </tr>
        <tr>
            <th>Authorized Passengers</th>
            <td colspan="3" class="mono" style="font-size: 8pt;"><?= nl2br(esc($ticket['authorized_passengers'])) ?></td>
        </tr>
        <tr>
            <th>Authorized Departure</th>
            <td class="mono"><?= date('M d, Y h:i A', strtotime($ticket['authorized_departure'])) ?></td>
            <th>Authorized Return</th>
            <td class="mono"><?= date('M d, Y h:i A', strtotime($ticket['authorized_return'])) ?></td>
        </tr>
        <tr>
            <th>Approved By</th>
            <td colspan="3">
                <strong><?= esc($ticket['admin_approver_name'] ?? 'Atty. Julius S. De Peralta') ?></strong> - Head, Administrative Division
            </td>
        </tr>
    </table>

    <!-- SECTION B -->
    <div class="section-header">B. TO BE ACCOMPLISHED BY THE DRIVER (TRIP EXECUTION & FUEL LOG)</div>
    <table class="tbl">
        <tr>
            <th colspan="2" style="text-align: center; background: #e6e6e6;">TIME RECORD</th>
            <th colspan="2" style="text-align: center; background: #e6e6e6;">ODOMETER READINGS (KM)</th>
        </tr>
        <tr>
            <th style="width: 25%;">Departure from Garage</th>
            <td class="mono" style="width: 25%;"><?= $ticket['departure_time'] ? date('M d, H:i', strtotime($ticket['departure_time'])) : '____/__/__ __:__' ?></td>
            <th style="width: 25%;">Start Odometer</th>
            <td class="mono" style="width: 25%;"><?= number_format((float)$ticket['start_odometer'], 1) ?> km</td>
        </tr>
        <tr>
            <th>Arrival at Destination</th>
            <td class="mono"><?= $ticket['arrival_dest_time'] ? date('M d, H:i', strtotime($ticket['arrival_dest_time'])) : '____/__/__ __:__' ?></td>
            <th>Destination Odometer</th>
            <td class="mono"><?= number_format((float)$ticket['dest_odometer'], 1) ?> km</td>
        </tr>
        <tr>
            <th>Departure from Destination</th>
            <td class="mono"><?= $ticket['departure_dest_time'] ? date('M d, H:i', strtotime($ticket['departure_dest_time'])) : '____/__/__ __:__' ?></td>
            <th>Return Odometer</th>
            <td class="mono"><?= number_format((float)$ticket['return_odometer'], 1) ?> km</td>
        </tr>
        <tr>
            <th>Arrival at Garage</th>
            <td class="mono"><?= $ticket['arrival_back_time'] ? date('M d, H:i', strtotime($ticket['arrival_back_time'])) : '____/__/__ __:__' ?></td>
            <th>Total Distance Travelled</th>
            <td class="mono fw-bold"><?= number_format((float)$ticket['total_distance_km'], 1) ?> KM</td>
        </tr>
    </table>

    <!-- Fuel Formula Table -->
    <table class="tbl" style="margin-top: 8px;">
        <tr>
            <th colspan="5" style="text-align: center; background: #e6e6e6;">
                GASOLINE / DIESEL CONSUMPTION RECORD (Balance End = Start + Issued + Purchased - Used)
            </th>
        </tr>
        <tr>
            <th style="width: 20%;">1. Balance in Tank (Start)</th>
            <th style="width: 20%;">2. Issued from Stock</th>
            <th style="width: 20%;">3. Purchased on Trip</th>
            <th style="width: 20%;">4. Total Fuel Used</th>
            <th style="width: 20%;">5. Balance in Tank (End)</th>
        </tr>
        <tr class="mono" style="text-align: center; font-size: 9pt;">
            <td><?= number_format((float)$ticket['fuel_balance_start_liters'], 2) ?> L</td>
            <td><?= number_format((float)$ticket['fuel_issued_stock_liters'], 2) ?> L</td>
            <td><?= number_format((float)$ticket['fuel_purchased_liters'], 2) ?> L</td>
            <td style="font-weight: 700;"><?= number_format((float)$ticket['fuel_used_liters'], 2) ?> L</td>
            <td style="font-weight: 700; background: #fafafa;"><?= number_format((float)$ticket['fuel_balance_end_liters'], 2) ?> L</td>
        </tr>
        <tr>
            <td colspan="5" style="font-size: 8pt;">
                Fuel Purchase Cost: <strong>₱<?= number_format((float)$ticket['fuel_purchased_cost'], 2) ?></strong> &bull;
                Efficiency: <strong><?= number_format((float)$ticket['fuel_efficiency_kml'], 2) ?> KM/L</strong>
                <?php if ((int)$ticket['fuel_anomaly_flag'] === 1): ?>
                    <span style="color: #900; font-weight: 700;">(>20% COA Variance Flagged)</span>
                <?php endif; ?>
                &bull; Consumables: Gear Oil: <?= (float)$ticket['gear_oil_liters'] ?>L, Lube Oil: <?= (float)$ticket['lube_oil_liters'] ?>L, Grease: <?= (float)$ticket['grease_units'] ?> units
            </td>
        </tr>
    </table>

    <!-- Dual Certifications -->
    <div class="signatures">
        <div class="sig-block">
            <div style="font-weight: 700; font-size: 8pt; text-transform: uppercase;">
                Driver's Certification:
            </div>
            <div style="font-size: 7.5pt; font-style: italic; color: #333;">
                I hereby certify to the correctness of the above statement of record of travel and fuel consumption.
            </div>
            <div style="text-align: center; border-top: 1px solid #000; padding-top: 4px; margin-top: 25px;">
                <strong><?= esc($ticket['driver_name']) ?></strong><br>
                <span style="font-size: 7.5pt;">Official Driver</span><br>
                <span class="mono" style="font-size: 7pt;"><?= $ticket['driver_certified_at'] ? date('M d, Y h:i A', strtotime($ticket['driver_certified_at'])) : 'Unsigned' ?></span>
            </div>
        </div>

        <div class="sig-block">
            <div style="font-weight: 700; font-size: 8pt; text-transform: uppercase;">
                Passenger / Official Certification:
            </div>
            <div style="font-size: 7.5pt; font-style: italic; color: #333;">
                I hereby certify that I used this vehicle on official business as stated above.
            </div>
            <div style="text-align: center; border-top: 1px solid #000; padding-top: 4px; margin-top: 25px;">
                <strong><?= esc($ticket['passenger_certifier_name'] ?? $ticket['requestor_name']) ?></strong><br>
                <span style="font-size: 7.5pt;">Authorized Passenger / Official</span><br>
                <span class="mono" style="font-size: 7pt;"><?= $ticket['passenger_certified_at'] ? date('M d, Y h:i A', strtotime($ticket['passenger_certified_at'])) : 'Unsigned' ?></span>
            </div>
        </div>
    </div>

    <!-- Statutory Notice -->
    <div class="footer-note">
        This document serves as the official liquidation ticket for audit by the Commission on Audit (COA) under COA Circular No. 75-6. Security gate verification is logged via HMAC-SHA256 encrypted QR checkpoint.
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrEl = document.getElementById('printQr');
        if (qrEl) {
            new QRCode(qrEl, {
                text: <?= json_encode($ticket['qr_crypt_token'] ?: $ticket['ticket_serial_no']) ?>,
                width: 75,
                height: 75,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.L
            });
        }
    });
    </script>
</body>
</html>
