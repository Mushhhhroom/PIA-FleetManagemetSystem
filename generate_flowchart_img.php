<?php
// Generates exact flowchart matching Screenshot 2 (Screenshot 2026-10-14 144548.png)
// High-resolution 2500 x 1350 pixels (Dark Theme)

$width = 2500;
$height = 1350;
$im = imagecreatetruecolor($width, $height);
imagealphablending($im, true);
imagesavealpha($im, true);

// Colors (Dark Theme matching Screenshot 2)
$bg = imagecolorallocate($im, 18, 18, 20); // #121214 dark background
imagefilledrectangle($im, 0, 0, $width, $height, $bg);

$cNodeBg      = imagecolorallocate($im, 24, 24, 28);      // #18181c node interior
$cNodeBorder  = imagecolorallocate($im, 225, 230, 235);  // crisp white border
$cWhite       = imagecolorallocate($im, 255, 255, 255);
$cMutedText   = imagecolorallocate($im, 205, 210, 220);
$cLine        = imagecolorallocate($im, 215, 220, 230);  // connector lines

// Role badge background colors
$cRedBg       = imagecolorallocate($im, 225, 29, 72);    // #e11d48 red
$cGreenBg     = imagecolorallocate($im, 34, 197, 94);    // #22c55e green
$cCyanBg      = imagecolorallocate($im, 14, 165, 233);   // #0ea5e9 cyan/blue
$cPinkBg      = imagecolorallocate($im, 236, 72, 153);   // #ec4899 magenta
$cBlueBg      = imagecolorallocate($im, 37, 99, 235);    // #2563eb royal blue
$cYellowBg    = imagecolorallocate($im, 234, 179, 8);    // #eab308 yellow
$cBlackText   = imagecolorallocate($im, 10, 10, 12);     // dark text on light badge

// Fonts
$fontBold = 'C:\\Windows\\Fonts\\arialbd.ttf';
$fontRegular = 'C:\\Windows\\Fonts\\arial.ttf';
$fontMono = 'C:\\Windows\\Fonts\\consola.ttf';

if (!file_exists($fontBold)) {
    $fontBold = 'C:\\Windows\\Fonts\\segoeui.ttf';
    $fontRegular = 'C:\\Windows\\Fonts\\segoeui.ttf';
}

function drawThickLine($im, $x1, $y1, $x2, $y2, $color, $thick = 2) {
    if ($thick <= 1) {
        imageline($im, (int)$x1, (int)$y1, (int)$x2, (int)$y2, $color);
        return;
    }
    $t = $thick / 2;
    if ($x1 == $x2) {
        imagefilledrectangle($im, (int)($x1 - $t), (int)min($y1, $y2), (int)($x1 + $t), (int)max($y1, $y2), $color);
        return;
    }
    if ($y1 == $y2) {
        imagefilledrectangle($im, (int)min($x1, $x2), (int)($y1 - $t), (int)max($x1, $x2), (int)($y1 + $t), $color);
        return;
    }
    $k = ($y2 - $y1) / ($x2 - $x1);
    $a = $t / sqrt(1 + pow($k, 2));
    $points = [
        (int)round($x1 - (1 + $k) * $a), (int)round($y1 + (1 - $k) * $a),
        (int)round($x1 - (1 - $k) * $a), (int)round($y1 - (1 + $k) * $a),
        (int)round($x2 + (1 + $k) * $a), (int)round($y2 - (1 - $k) * $a),
        (int)round($x2 + (1 - $k) * $a), (int)round($y2 + (1 + $k) * $a)
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowDown($im, $x, $y, $color, $size = 7) {
    $points = [
        (int)$x, (int)$y,
        (int)($x - $size), (int)($y - $size * 1.5),
        (int)($x + $size), (int)($y - $size * 1.5)
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowUp($im, $x, $y, $color, $size = 7) {
    $points = [
        (int)$x, (int)$y,
        (int)($x - $size), (int)($y + $size * 1.5),
        (int)($x + $size), (int)($y + $size * 1.5)
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowRight($im, $x, $y, $color, $size = 7) {
    $points = [
        (int)$x, (int)$y,
        (int)($x - $size * 1.5), (int)($y - $size),
        (int)($x - $size * 1.5), (int)($y + $size)
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowLeft($im, $x, $y, $color, $size = 7) {
    $points = [
        (int)$x, (int)$y,
        (int)($x + $size * 1.5), (int)($y - $size),
        (int)($x + $size * 1.5), (int)($y + $size)
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawRoundedRect($im, $x, $y, $w, $h, $radius, $fillColor, $borderColor, $borderWidth = 2) {
    $x = (int)$x; $y = (int)$y; $w = (int)$w; $h = (int)$h; $radius = (int)$radius;
    imagefilledrectangle($im, $x + $radius, $y, $x + $w - $radius, $y + $h, $fillColor);
    imagefilledrectangle($im, $x, $y + $radius, $x + $w, $y + $h - $radius, $fillColor);
    imagefilledellipse($im, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $w - $radius, $y + $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $w - $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fillColor);

    imagesetthickness($im, $borderWidth);
    imageline($im, $x + $radius, $y, $x + $w - $radius, $y, $borderColor);
    imageline($im, $x + $radius, $y + $h, $x + $w - $radius, $y + $h, $borderColor);
    imageline($im, $x, $y + $radius, $x, $y + $h - $radius, $borderColor);
    imageline($im, $x + $w, $y + $radius, $x + $w, $y + $h - $radius, $borderColor);
    imagearc($im, $x + $radius, $y + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
    imagearc($im, $x + $w - $radius, $y + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
    imagearc($im, $x + $radius, $y + $h - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
    imagearc($im, $x + $w - $radius, $y + $h - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
    imagesetthickness($im, 1);
}

function drawTerminalPill($im, $cx, $cy, $w, $h, $lines, $fillColor, $borderColor, $textColor, $fontBold, $fontRegular) {
    $x = $cx - $w / 2;
    $y = $cy - $h / 2;
    $radius = $h / 2;
    drawRoundedRect($im, $x, $y, $w, $h, $radius, $fillColor, $borderColor, 2);

    $totalLines = count($lines);
    $lineHeight = 16;
    $startY = $cy - (($totalLines - 1) * $lineHeight) / 2 + 5;

    foreach ($lines as $i => $l) {
        $font = ($i === 0) ? $fontBold : $fontRegular;
        $size = ($i === 0) ? 10 : 9;
        $bbox = imagettfbbox($size, 0, $font, $l);
        $tw = $bbox[2] - $bbox[0];
        imagettftext($im, $size, 0, (int)($cx - $tw / 2), (int)($startY + $i * $lineHeight), $textColor, $font, $l);
    }
}

function drawParallelogram($im, $cx, $cy, $w, $h, $lines, $fillColor, $borderColor, $textColor, $fontBold, $fontRegular, $skew = 22) {
    $x1 = $cx - $w / 2;
    $y1 = $cy - $h / 2;
    $x2 = $cx + $w / 2;
    $y2 = $cy + $h / 2;

    $points = [
        (int)($x1 + $skew), (int)$y1,
        (int)$x2, (int)$y1,
        (int)($x2 - $skew), (int)$y2,
        (int)$x1, (int)$y2
    ];
    imagefilledpolygon($im, $points, $fillColor);
    imagesetthickness($im, 2);
    imagepolygon($im, $points, $borderColor);
    imagesetthickness($im, 1);

    $totalLines = count($lines);
    $lineHeight = 16;
    $startY = $cy - (($totalLines - 1) * $lineHeight) / 2 + 5;

    foreach ($lines as $i => $l) {
        $font = ($i === 0) ? $fontBold : $fontRegular;
        $size = ($i === 0) ? 10 : 9;
        $bbox = imagettfbbox($size, 0, $font, $l);
        $tw = $bbox[2] - $bbox[0];
        imagettftext($im, $size, 0, (int)($cx - $tw / 2), (int)($startY + $i * $lineHeight), $textColor, $font, $l);
    }
}

function drawDiamond($im, $cx, $cy, $w, $h, $lines, $fillColor, $borderColor, $textColor, $fontBold) {
    $halfW = $w / 2;
    $halfH = $h / 2;
    $points = [
        (int)$cx, (int)($cy - $halfH),
        (int)($cx + $halfW), (int)$cy,
        (int)$cx, (int)($cy + $halfH),
        (int)($cx - $halfW), (int)$cy
    ];
    imagefilledpolygon($im, $points, $fillColor);
    imagesetthickness($im, 2);
    imagepolygon($im, $points, $borderColor);
    imagesetthickness($im, 1);

    $totalLines = count($lines);
    $lineHeight = 15;
    $startY = $cy - (($totalLines - 1) * $lineHeight) / 2 + 5;

    foreach ($lines as $i => $l) {
        $size = 9;
        $bbox = imagettfbbox($size, 0, $fontBold, $l);
        $tw = $bbox[2] - $bbox[0];
        imagettftext($im, $size, 0, (int)($cx - $tw / 2), (int)($startY + $i * $lineHeight), $textColor, $fontBold, $l);
    }
}

function drawProcessRect($im, $cx, $cy, $w, $h, $title, $role, $roleBg, $roleFg, $descLines, $fillColor, $borderColor, $fontBold, $fontRegular) {
    global $cWhite, $cMutedText;
    $x = $cx - $w / 2;
    $y = $cy - $h / 2;
    drawRoundedRect($im, $x, $y, $w, $h, 8, $fillColor, $borderColor, 2);

    $curY = $y + 20;

    // Title
    $bbox = imagettfbbox(10, 0, $fontBold, $title);
    $tw = $bbox[2] - $bbox[0];
    imagettftext($im, 10, 0, (int)($cx - $tw / 2), (int)$curY, $cWhite, $fontBold, $title);
    $curY += 16;

    // Role badge
    if ($role) {
        $bboxR = imagettfbbox(8.5, 0, $fontBold, $role);
        $rw = $bboxR[2] - $bboxR[0];
        $padX = 8;
        $badgeW = $rw + $padX * 2;
        $badgeH = 15;
        $bx = $cx - $badgeW / 2;
        $by = $curY - 11;
        imagefilledrectangle($im, (int)$bx, (int)$by, (int)($bx + $badgeW), (int)($by + $badgeH), $roleBg);
        imagettftext($im, 8.5, 0, (int)($cx - $rw / 2), (int)($curY + 1), $roleFg, $fontBold, $role);
        $curY += 18;
    }

    // Description lines
    foreach ($descLines as $l) {
        $bboxD = imagettfbbox(8.5, 0, $fontRegular, $l);
        $dw = $bboxD[2] - $bboxD[0];
        imagettftext($im, 8.5, 0, (int)($cx - $dw / 2), (int)$curY, $cMutedText, $fontRegular, $l);
        $curY += 14;
    }
}

// =========================================================================
// FLOWCHART LAYOUT COORDINATES (Following Screenshot 2)
// =========================================================================

// --- COLUMN 1: Official Travel Initiation & Approvals (Left Column) ---
$col1X = 420;

// 1. Start Travel Demand
$yStart = 90;
drawTerminalPill($im, $col1X, $yStart, 320, 56, [
    "Start Travel Demand",
    "(Employee Request Official Vehicle Transport)"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Arrow 1 -> 2
drawThickLine($im, $col1X, $yStart + 28, $col1X, 190, $cLine, 2);
drawArrowDown($im, $col1X, 190, $cLine, 6);

// 2. INPUT: Vehicle Request Slip (VRS)
$yVrs = 230;
drawParallelogram($im, $col1X, $yVrs, 360, 68, [
    "INPUT: Vehicle Request Slip (VRS)",
    "(Request file destination, purpose, dates, passengers)"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Arrow 2 -> 3
drawThickLine($im, $col1X, $yVrs + 34, $col1X, 355, $cLine, 2);
drawArrowDown($im, $col1X, 355, $cLine, 6);

// 3. Tier 1 Dispatcher Approval
$yTier1 = 415;
drawDiamond($im, $col1X, $yTier1, 230, 110, [
    "Tier 1 Dispatcher",
    "Approval"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold);

// Branch 3: No (Specification disapproval) -> Left
$rejColX = 110;
$yRej = 530;
drawThickLine($im, $col1X - 115, $yTier1, $rejColX, $yTier1, $cLine, 2);
drawThickLine($im, $rejColX, $yTier1, $rejColX, $yRej - 30, $cLine, 2);
drawArrowDown($im, $rejColX, $yRej - 30, $cLine, 6);

// Annotation for No (Specification disapproval)
imagettftext($im, 8.5, 0, (int)($col1X - 145), (int)($yTier1 - 8), $cWhite, $fontRegular, "No");
imagettftext($im, 8, 0, (int)($col1X - 280), (int)($yTier1 - 8), $cMutedText, $fontRegular, "(Specification disapproval)");

// Request Rejected Terminal Pill
drawTerminalPill($im, $rejColX, $yRej, 170, 60, [
    "Request Rejected",
    "(Requester will be",
    "notified)"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Branch 3: Yes (ENDORSED) -> Down to Tier 2
drawThickLine($im, $col1X, $yTier1 + 55, $col1X, 580, $cLine, 2);
drawArrowDown($im, $col1X, 580, $cLine, 6);
imagettftext($im, 8.5, 0, (int)($col1X + 8), (int)($yTier1 + 95), $cWhite, $fontBold, "Yes (ENDORSED)");

// 4. Tier 2 Head Administrative Division Approval
$yTier2 = 640;
drawDiamond($im, $col1X, $yTier2, 230, 110, [
    "Tier 2 Head",
    "Administrative Division",
    "Approval"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold);

// Branch 4: No (Specific reason for disapproval) -> Left to Request Rejected
drawThickLine($im, $col1X - 115, $yTier2, $rejColX, $yTier2, $cLine, 2);
drawThickLine($im, $rejColX, $yTier2, $rejColX, $yRej + 30, $cLine, 2);
drawArrowUp($im, $rejColX, $yRej + 30, $cLine, 6);

imagettftext($im, 8.5, 0, (int)($col1X - 145), (int)($yTier2 - 8), $cWhite, $fontRegular, "No");
imagettftext($im, 8, 0, (int)($col1X - 295), (int)($yTier2 - 8), $cMutedText, $fontRegular, "(Specific reason for disapproval)");

// Branch 4: Yes (Authorized) -> Down to Dispatch
drawThickLine($im, $col1X, $yTier2 + 55, $col1X, 810, $cLine, 2);
drawArrowDown($im, $col1X, 810, $cLine, 6);
imagettftext($im, 8.5, 0, (int)($col1X + 8), (int)($yTier2 + 95), $cWhite, $fontBold, "Yes (Authorized)");

// 5. DISPATCH & ASSET ALLOCATION (MOTORPOOL DISPATCHER)
$yDisp = 865;
drawProcessRect($im, $col1X, $yDisp, 350, 95,
    "DISPATCH & ASSET ALLOCATION",
    "MOTORPOOL DISPATCHER", $cRedBg, $cWhite,
    [
        "Assign verified vehicle, certified driver, toll RFID &",
        "generate e-DTT"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Connecting Line from Box 5 to Column 2 (Box 6)
$yDispBottom = $yDisp + 48;
$xRouteCol = 625;
$col2X = 890;
$yBlow = 260;

drawThickLine($im, $col1X, $yDispBottom, $col1X, 940, $cLine, 2);
drawThickLine($im, $col1X, 940, $xRouteCol, 940, $cLine, 2);
drawThickLine($im, $xRouteCol, 940, $xRouteCol, 185, $cLine, 2);
drawThickLine($im, $xRouteCol, 185, $col2X, 185, $cLine, 2);
drawThickLine($im, $col2X, 185, $col2X, $yBlow - 46, $cLine, 2);
drawArrowDown($im, $col2X, $yBlow - 46, $cLine, 6);

// --- COLUMN 2: Operations, Inspection, Gate & Telematics (Middle Column) ---

// 6. PRE-TRIP BLOWBAGETS INSPECTION (ASSIGNED DRIVER)
drawProcessRect($im, $col2X, $yBlow, 340, 90,
    "PRE-TRIP BLOWBAGETS INSPECTION",
    "ASSIGNED DRIVER", $cGreenBg, $cBlackText,
    [
        "Battery, Light, Oil, Water, Brake, Air, Gas, Engine, Tire,",
        "Self"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Arrow 6 -> 7
drawThickLine($im, $col2X, $yBlow + 45, $col2X, 400, $cLine, 2);
drawArrowDown($im, $col2X, 400, $cLine, 6);

// 7. BLOWBAGETS PASS? Vehicle Safe for Road?
$yBlowPass = 450;
drawDiamond($im, $col2X, $yBlowPass, 210, 95, [
    "BLOWBAGETS",
    "PASS? Vehicle Safe for",
    "Road?"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold);

// Branch 7: Fail (Defect Found) -> Right to VEHICLE GROUNDED
$col3X = 1450;
$yGround = 260;
drawThickLine($im, $col2X + 105, $yBlowPass, 1180, $yBlowPass, $cLine, 2);
drawThickLine($im, 1180, $yBlowPass, 1180, $yGround, $cLine, 2);
drawThickLine($im, 1180, $yGround, $col3X - 170, $yGround, $cLine, 2);
drawArrowRight($im, $col3X - 170, $yGround, $cLine, 6);

imagettftext($im, 8.5, 0, (int)($col2X + 115), (int)($yBlowPass - 12), $cWhite, $fontRegular, "Fail");
imagettftext($im, 8, 0, (int)($col2X + 115), (int)($yBlowPass + 18), $cMutedText, $fontRegular, "(Defect Found)");

// Branch 7: Pass (Roadworthy) -> Down to Gate Out
drawThickLine($im, $col2X, $yBlowPass + 48, $col2X, 580, $cLine, 2);
drawArrowDown($im, $col2X, 580, $cLine, 6);
imagettftext($im, 8.5, 0, (int)($col2X + 8), (int)($yBlowPass + 75), $cWhite, $fontRegular, "Pass");
imagettftext($im, 8, 0, (int)($col2X + 8), (int)($yBlowPass + 92), $cMutedText, $fontRegular, "(Roadworthy)");

// 8. GATE OUT: QR Code Security Scan
$yGateOut = 625;
drawParallelogram($im, $col2X, $yGateOut, 360, 68, [
    "GATE OUT: QR Code Security Scan",
    "Guard verifies e-DTT, logs departure timestamp &",
    "starting odometer"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Arrow 8 -> 9
drawThickLine($im, $col2X, $yGateOut + 34, $col2X, 740, $cLine, 2);
drawArrowDown($im, $col2X, 740, $cLine, 6);

// 9. EN ROUTE: Live GPS Radar & Fuel Logging
$yRadar = 785;
drawProcessRect($im, $col2X, $yRadar, 350, 90,
    "EN ROUTE: Live GPS Radar & Fuel Logging",
    "TELEMATICS & DRIVER PWA", $cPinkBg, $cWhite,
    [
        "Live ping stream, speed watcher (>80km/h alert), fuel",
        "receipt capture"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Arrow 9 -> 10
drawThickLine($im, $col2X, $yRadar + 45, $col2X, 920, $cLine, 2);
drawArrowDown($im, $col2X, 920, $cLine, 6);

// 10. GATE IN: Return Verification Scan
$yGateIn = 960;
drawParallelogram($im, $col2X, $yGateIn, 360, 68, [
    "GATE IN: Return Verification Scan",
    "Guard logs arrival timestamp, return odometer &",
    "passenger headcount"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Arrow 10 -> 11
drawThickLine($im, $col2X, $yGateIn + 34, $col2X, 1070, $cLine, 2);
drawArrowDown($im, $col2X, 1070, $cLine, 6);

// 11. ODOMETER VARIANCE VALID?
$yOdo = 1125;
drawDiamond($im, $col2X, $yOdo, 220, 105, [
    "ODOMETER",
    "VARIANCE VALID?",
    "Distance matches OSRM",
    "route & fuel?"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold);

// --- COLUMN 3: Grounding, Auditing, Form B & Archiving (Right Side) ---

// Top: VEHICLE GROUNDED & PIR QUEUE
drawProcessRect($im, $col3X, $yGround, 340, 90,
    "VEHICLE GROUNDED & PIR QUEUE",
    "SAFETY TECH/ MECHANIC", $cCyanBg, $cBlackText,
    [
        "Pre-Repair Inspection: diagnose defect & order parts"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Middle: FLAG AUDIT ANOMALY (Directly above Post-Trip Reconciliation)
$yAnomaly = 960;
$colAuditX = 1580;
drawProcessRect($im, $colAuditX, $yAnomaly, 340, 85,
    "FLAG AUDIT ANOMALY",
    "COA AUDITOR INVESTIGATION", $cBlueBg, $cWhite,
    [
        "Lock reimbursement,",
        "inspect route discrepancy"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Branch 11: UNEXPLAINED VARIANCE (>15%) -> Up and into FLAG AUDIT ANOMALY
drawThickLine($im, $col2X + 110, $yOdo - 25, 1320, $yOdo - 25, $cLine, 2);
drawThickLine($im, 1320, $yOdo - 25, 1320, $yAnomaly, $cLine, 2);
drawThickLine($im, 1320, $yAnomaly, $colAuditX - 170, $yAnomaly, $cLine, 2);
drawArrowRight($im, $colAuditX - 170, $yAnomaly, $cLine, 6);

imagettftext($im, 8.5, 0, (int)1230, (int)($yAnomaly + 25), $cWhite, $fontBold, "UNEXPLAINED VARIANCE (>15%)");

// Lower: POST-TRIP RECONCILIATION & COA FORM B
$yFormB = 1125;
drawProcessRect($im, $colAuditX, $yFormB, 340, 95,
    "POST-TRIP RECONCILIATION & COA FORM B",
    "COA RESIDENT AUDITOR & SYSTEM", $cYellowBg, $cBlackText,
    [
        "Compute CPK (Cost per KM), write immutable audit log,",
        "compile Form B report"
    ],
    $cNodeBg, $cNodeBorder, $fontBold, $fontRegular
);

// Connector from FLAG AUDIT ANOMALY down into POST-TRIP RECONCILIATION
drawThickLine($im, $colAuditX, $yAnomaly + 43, $colAuditX, $yFormB - 48, $cLine, 2);
drawArrowDown($im, $colAuditX, $yFormB - 48, $cLine, 6);

// Branch 11: Normal Variance -> Right directly into POST-TRIP RECONCILIATION
drawThickLine($im, $col2X + 110, $yOdo, $colAuditX - 170, $yOdo, $cLine, 2);
drawArrowRight($im, $colAuditX - 170, $yOdo, $cLine, 6);
imagettftext($im, 8.5, 0, (int)($col2X + 140), (int)($yOdo - 8), $cWhite, $fontRegular, "Normal Variance");

// Far Right: TRIP CLOSED & ARCHIVED
$col4X = 2120;
drawTerminalPill($im, $col4X, $yFormB, 340, 56, [
    "TRIP CLOSED & ARCHIVED",
    "COA Compliant - Vehicle Released to Available Pool"
], $cNodeBg, $cNodeBorder, $cWhite, $fontBold, $fontRegular);

// Arrow Form B -> Trip Closed
drawThickLine($im, $colAuditX + 170, $yFormB, $col4X - 170, $yFormB, $cLine, 2);
drawArrowRight($im, $col4X - 170, $yFormB, $cLine, 6);

// Header watermark / branding (Subtle)
imagettftext($im, 11, 0, 50, 40, imagecolorallocate($im, 120, 130, 145), $fontMono, "PHILIPPINE INFORMATION AGENCY • MOTORPOOL FLEET WORKFLOW BLUEPRINT (ANSI/ISO)");

// Save image
$outPath = __DIR__ . '/public/assets/images/system_entire_flowchart.png';
imagepng($im, $outPath, 9);
imagedestroy($im);

echo "SUCCESS: Saved matching flowchart to {$outPath} (" . filesize($outPath) . " bytes)\n";
