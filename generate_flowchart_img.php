<?php
// Scratch script to generate high-resolution professional standard flowchart image
// Dimensions: 2400 x 3600 pixels (High-DPI crisp PNG)

$width = 2400;
$height = 3600;
$im = imagecreatetruecolor($width, $height);
imagealphablending($im, true);
imagesavealpha($im, true);

// Color Palette
$bg = imagecolorallocate($im, 248, 250, 252); // #f8fafc
imagefilledrectangle($im, 0, 0, $width, $height, $bg);

// Grid pattern subtle background
$gridCol = imagecolorallocate($im, 241, 245, 249); // #f1f5f9
for ($x = 0; $x < $width; $x += 40) {
    imageline($im, $x, 0, $x, $height, $gridCol);
}
for ($y = 0; $y < $height; $y += 40) {
    imageline($im, 0, $y, $width, $y, $gridCol);
}

// Colors
$cNavy     = imagecolorallocate($im, 15, 23, 42);     // #0f172a
$cPrimary  = imagecolorallocate($im, 37, 99, 235);    // #2563eb
$cBlueBg   = imagecolorallocate($im, 239, 246, 255);  // #eff6ff
$cBlueBdr  = imagecolorallocate($im, 147, 197, 253);  // #93c5fd

$cGreen    = imagecolorallocate($im, 16, 185, 129);   // #10b981
$cGreenBg  = imagecolorallocate($im, 236, 253, 245);  // #ecfdf5
$cGreenBdr = imagecolorallocate($im, 110, 231, 183);  // #6ee7b7

$cAmber    = imagecolorallocate($im, 217, 119, 6);    // #d97706
$cAmberBg  = imagecolorallocate($im, 254, 243, 199);  // #fef3c7
$cAmberBdr = imagecolorallocate($im, 252, 211, 77);   // #fcd34d

$cRed      = imagecolorallocate($im, 220, 38, 38);    // #dc2626
$cRedBg    = imagecolorallocate($im, 254, 242, 242);  // #fef2f2
$cRedBdr   = imagecolorallocate($im, 252, 165, 165);  // #fca5a5

$cPurple   = imagecolorallocate($im, 124, 58, 237);   // #7c3aed
$cPurpleBg = imagecolorallocate($im, 245, 243, 255);  // #f5f3ff
$cPurpleBdr= imagecolorallocate($im, 196, 181, 253);  // #c4b5fd

$cDark     = imagecolorallocate($im, 30, 41, 59);     // #1e293b
$cMuted    = imagecolorallocate($im, 100, 116, 139);  // #64748b
$cWhite    = imagecolorallocate($im, 255, 255, 255);
$cLine     = imagecolorallocate($im, 71, 85, 105);    // #475569 line color
$cLineSubtle = imagecolorallocate($im, 203, 213, 225);

// Fonts (Windows standard TrueType fonts)
$fontBold = 'C:\\Windows\\Fonts\\arialbd.ttf';
$fontRegular = 'C:\\Windows\\Fonts\\arial.ttf';
$fontMono = 'C:\\Windows\\Fonts\\consola.ttf';

if (!file_exists($fontBold)) {
    $fontBold = 'C:\\Windows\\Fonts\\segoeui.ttf';
    $fontRegular = 'C:\\Windows\\Fonts\\segoeui.ttf';
}

// Helpers
function drawThickLine($im, $x1, $y1, $x2, $y2, $color, $thick = 4) {
    if ($thick == 1) {
        return imageline($im, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($im, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1);
    $a = $t / sqrt(1 + pow($k, 2));
    $points = [
        round($x1 - (1 + $k) * $a), round($y1 + (1 - $k) * $a),
        round($x1 - (1 - $k) * $a), round($y1 - (1 + $k) * $a),
        round($x2 + (1 + $k) * $a), round($y2 - (1 - $k) * $a),
        round($x2 + (1 - $k) * $a), round($y2 + (1 + $k) * $a)
    ];
    imagefilledpolygon($im, $points, $color);
    return true;
}

function drawArrowDown($im, $x, $y, $color, $size = 14) {
    $points = [
        $x, $y,
        $x - $size, $y - $size * 1.5,
        $x + $size, $y - $size * 1.5
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowRight($im, $x, $y, $color, $size = 14) {
    $points = [
        $x, $y,
        $x - $size * 1.5, $y - $size,
        $x - $size * 1.5, $y + $size
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawArrowLeft($im, $x, $y, $color, $size = 14) {
    $points = [
        $x, $y,
        $x + $size * 1.5, $y - $size,
        $x + $size * 1.5, $y + $size
    ];
    imagefilledpolygon($im, $points, $color);
}

function drawRoundedRect($im, $x, $y, $w, $h, $radius, $fillColor, $borderColor, $borderWidth = 3) {
    // Fill
    imagefilledrectangle($im, $x + $radius, $y, $x + $w - $radius, $y + $h, $fillColor);
    imagefilledrectangle($im, $x, $y + $radius, $x + $w, $y + $h - $radius, $fillColor);
    imagefilledellipse($im, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $w - $radius, $y + $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fillColor);
    imagefilledellipse($im, $x + $w - $radius, $y + $h - $radius, $radius * 2, $radius * 2, $fillColor);

    // Border
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

function drawTerminal($im, $cx, $cy, $w, $h, $text, $sub, $fill, $border, $textCol, $fontB, $fontR) {
    $x = $cx - $w / 2;
    $y = $cy - $h / 2;
    $radius = $h / 2;
    drawRoundedRect($im, $x, $y, $w, $h, $radius, $fill, $border, 4);

    // Centered text
    $bbox = imagettfbbox(18, 0, $fontB, $text);
    $tw = $bbox[2] - $bbox[0];
    imagettftext($im, 18, 0, $cx - $tw / 2, $cy - ($sub ? 6 : -6), $textCol, $fontB, $text);

    if ($sub) {
        $bbox2 = imagettfbbox(12, 0, $fontR, $sub);
        $tw2 = $bbox2[2] - $bbox2[0];
        imagettftext($im, 12, 0, $cx - $tw2 / 2, $cy + 18, $textCol, $fontR, $sub);
    }
}

function drawProcess($im, $cx, $cy, $w, $h, $title, $role, $desc, $fill, $border, $fontB, $fontR) {
    global $cNavy, $cMuted, $cPrimary;
    $x = $cx - $w / 2;
    $y = $cy - $h / 2;
    drawRoundedRect($im, $x, $y, $w, $h, 16, $fill, $border, 3);

    // Title
    $bbox = imagettfbbox(17, 0, $fontB, $title);
    $tw = $bbox[2] - $bbox[0];
    imagettftext($im, 17, 0, $cx - $tw / 2, $cy - 20, $cNavy, $fontB, $title);

    // Role badge
    if ($role) {
        $badgeText = strtoupper($role);
        $bboxR = imagettfbbox(11, 0, $fontB, $badgeText);
        $rw = $bboxR[2] - $bboxR[0];
        $bx = $cx - $rw / 2 - 12;
        $by = $cy - 6;
        imagefilledrectangle($im, $bx, $by, $bx + $rw + 24, $by + 20, $border);
        imagettftext($im, 11, 0, $cx - $rw / 2, $by + 15, imagecolorallocate($im, 255, 255, 255), $fontB, $badgeText);
    }

    // Desc
    if ($desc) {
        $bboxD = imagettfbbox(12, 0, $fontR, $desc);
        $dw = $bboxD[2] - $bboxD[0];
        imagettftext($im, 12, 0, $cx - $dw / 2, $cy + 34, $cMuted, $fontR, $desc);
    }
}

function drawDecision($im, $cx, $cy, $w, $h, $text1, $text2, $fill, $border, $fontB) {
    global $cNavy;
    $halfW = $w / 2;
    $halfH = $h / 2;
    $points = [
        $cx, $cy - $halfH,
        $cx + $halfW, $cy,
        $cx, $cy + $halfH,
        $cx - $halfW, $cy
    ];
    imagefilledpolygon($im, $points, $fill);
    imagesetthickness($im, 4);
    imagepolygon($im, $points, $border);
    imagesetthickness($im, 1);

    // Decision text
    $bbox1 = imagettfbbox(16, 0, $fontB, $text1);
    $tw1 = $bbox1[2] - $bbox1[0];
    imagettftext($im, 16, 0, $cx - $tw1 / 2, $cy - ($text2 ? 8 : -6), $cNavy, $fontB, $text1);

    if ($text2) {
        $bbox2 = imagettfbbox(15, 0, $fontB, $text2);
        $tw2 = $bbox2[2] - $bbox2[0];
        imagettftext($im, 15, 0, $cx - $tw2 / 2, $cy + 16, $cNavy, $fontB, $text2);
    }
}

function drawIO($im, $cx, $cy, $w, $h, $title, $sub, $fill, $border, $fontB, $fontR) {
    global $cNavy, $cMuted;
    $skew = 30;
    $x1 = $cx - $w / 2;
    $y1 = $cy - $h / 2;
    $x2 = $cx + $w / 2;
    $y2 = $cy + $h / 2;

    $points = [
        $x1 + $skew, $y1,
        $x2, $y1,
        $x2 - $skew, $y2,
        $x1, $y2
    ];
    imagefilledpolygon($im, $points, $fill);
    imagesetthickness($im, 3);
    imagepolygon($im, $points, $border);
    imagesetthickness($im, 1);

    $bbox = imagettfbbox(16, 0, $fontB, $title);
    $tw = $bbox[2] - $bbox[0];
    imagettftext($im, 16, 0, $cx - $tw / 2, $cy - ($sub ? 6 : -6), $cNavy, $fontB, $title);

    if ($sub) {
        $bbox2 = imagettfbbox(12, 0, $fontR, $sub);
        $tw2 = $bbox2[2] - $bbox2[0];
        imagettftext($im, 12, 0, $cx - $tw2 / 2, $cy + 18, $cMuted, $fontR, $sub);
    }
}

// ---------------- HEADER SECTION ----------------
// Top Header Banner
imagefilledrectangle($im, 0, 0, $width, 240, $cNavy);
imageline($im, 0, 240, $width, 240, $cPrimary);

// Republic of the Philippines Header
imagettftext($im, 14, 0, 100, 70, $cBlueBdr, $fontMono, "REPUBLIC OF THE PHILIPPINES • PHILIPPINE INFORMATION AGENCY (PIA)");
imagettftext($im, 36, 0, 100, 135, $cWhite, $fontBold, "PIA-AFMS: Complete Official Fleet Management System Flowchart");
imagettftext($im, 16, 0, 100, 185, $cBlueBdr, $fontRegular, "Standard ANSI / ISO Flowchart — End-to-End Travel Lifecycle, Multi-Tier SLA Approvals, Safety, Telematics & COA Compliance");

// Legend Box in Header right
$legX = 1750; $legY = 40;
drawRoundedRect($im, $legX, $legY, 550, 160, 10, imagecolorallocate($im, 24, 34, 53), $cPrimary, 2);
imagettftext($im, 12, 0, $legX + 20, $legY + 30, $cWhite, $fontBold, "STANDARD FLOWCHART SYMBOL LEGEND");
// Terminal pill
drawRoundedRect($im, $legX + 20, $legY + 45, 60, 22, 11, $cGreenBg, $cGreen, 2);
imagettftext($im, 10, 0, $legX + 90, $legY + 60, $cWhite, $fontRegular, "Terminator (Start / End)");
// Process rect
drawRoundedRect($im, $legX + 20, $legY + 75, 60, 22, 4, $cBlueBg, $cPrimary, 2);
imagettftext($im, 10, 0, $legX + 90, $legY + 90, $cWhite, $fontRegular, "Process / Action Module");
// Decision diamond
$pts = [$legX + 50, $legY + 105, $legX + 75, $legY + 117, $legX + 50, $legY + 129, $legX + 25, $legY + 117];
imagefilledpolygon($im, $pts, $cAmberBg);
imagepolygon($im, $pts, $cAmber);
imagettftext($im, 10, 0, $legX + 90, $legY + 122, $cWhite, $fontRegular, "Decision Branch (Yes / No)");
// I/O Parallelogram
$ptsIO = [$legX + 310, $legY + 45, $legX + 370, $legY + 45, $legX + 355, $legY + 67, $legX + 295, $legY + 67];
imagefilledpolygon($im, $ptsIO, $cPurpleBg);
imagepolygon($im, $ptsIO, $cPurple);
imagettftext($im, 10, 0, $legX + 380, $legY + 60, $cWhite, $fontRegular, "Input / Output (Form/Scan)");

// =========================================================================
// FLOWCHART LAYOUT COORDINATES
// =========================================================================
// Central Column: X = 1200
$cx = 1200;

// 1. START
$y1 = 330;
drawTerminal($im, $cx, $y1, 460, 80, "START TRAVEL DEMAND", "Employee Requires Official Vehicle Transport", $cGreenBg, $cGreen, $cGreen, $fontBold, $fontRegular);

// Arrow 1 -> 2
drawThickLine($im, $cx, $y1 + 40, $cx, $y1 + 90, $cLine, 4);
drawArrowDown($im, $cx, $y1 + 90, $cLine);

// 2. INPUT / DRAFT VRS
$y2 = 480;
drawIO($im, $cx, $y2, 580, 100, "INPUT: Vehicle Request Slip (VRS)", "Requestor fills destination, purpose, dates, passenger count", $cBlueBg, $cPrimary, $fontBold, $fontRegular);

// Arrow 2 -> 3
drawThickLine($im, $cx, $y2 + 50, $cx, $y2 + 100, $cLine, 4);
drawArrowDown($im, $cx, $y2 + 100, $cLine);

// 3. DECISION 1: TIER 1 APPROVAL (Division Head / OIC)
$y3 = 660;
drawDecision($im, $cx, $y3, 460, 160, "TIER 1 OIC APPROVAL?", "Endorsed within SLA?", $cAmberBg, $cAmber, $fontBold);

// Branch: No -> Rejected Termination (Left)
$rejX = 420;
drawThickLine($im, $cx - 230, $y3, $rejX, $y3, $cRed, 4);
drawArrowLeft($im, $rejX + 160, $y3, $cRed);
imagettftext($im, 14, 0, $cx - 330, $y3 - 10, $cRed, $fontBold, "NO (Disapproved)");
drawTerminal($im, $rejX, $y3, 300, 80, "REQUEST REJECTED", "VRS Closed & Notification Sent", $cRedBg, $cRed, $cRed, $fontBold, $fontRegular);

// Branch: Yes -> Tier 2 Approval
drawThickLine($im, $cx, $y3 + 80, $cx, $y3 + 140, $cGreen, 4);
drawArrowDown($im, $cx, $y3 + 140, $cGreen);
imagettftext($im, 14, 0, $cx + 15, $y3 + 115, $cGreen, $fontBold, "YES (Endorsed)");

// 4. DECISION 2: TIER 2 APPROVAL (Admin Division Chief)
$y4 = 880;
drawDecision($im, $cx, $y4, 460, 160, "TIER 2 ADMIN APPROVAL?", "Official Travel Authorized?", $cAmberBg, $cAmber, $fontBold);

// Branch: No -> Disapproved (Left to reject terminal)
drawThickLine($im, $cx - 230, $y4, $rejX, $y4, $cRed, 4);
drawThickLine($im, $rejX, $y4, $rejX, $y3 + 40, $cRed, 4);
drawArrowDown($im, $rejX, $y3 + 40, $cRed);
imagettftext($im, 14, 0, $cx - 520, $y4 - 12, $cRed, $fontBold, "NO (Disapproved / Budget Limit)");

// Branch: Yes -> Dispatch Allocation
drawThickLine($im, $cx, $y4 + 80, $cx, $y4 + 140, $cGreen, 4);
drawArrowDown($im, $cx, $y4 + 140, $cGreen);
imagettftext($im, 14, 0, $cx + 15, $y4 + 115, $cGreen, $fontBold, "YES (Authorized)");

// 5. PROCESS: MOTORPOOL DISPATCH & e-DTT GENERATION
$y5 = 1100;
drawProcess($im, $cx, $y5, 620, 110, "DISPATCH & ASSET ALLOCATION", "MOTORPOOL DISPATCHER", "Assign verified vehicle, certified driver, toll RFID & generate e-DTT", $cBlueBg, $cPrimary, $fontBold, $fontRegular);

// Arrow 5 -> 6
drawThickLine($im, $cx, $y5 + 55, $cx, $y5 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y5 + 115, $cLine);

// 6. PROCESS: PRE-TRIP BLOWBAGETS SAFETY INSPECTION
$y6 = 1290;
drawProcess($im, $cx, $y6, 620, 110, "PRE-TRIP BLOWBAGETS INSPECTION", "ASSIGNED DRIVER", "Battery, Light, Oil, Water, Brake, Air, Gas, Engine, Tire, Self", $cPurpleBg, $cPurple, $fontBold, $fontRegular);

// Arrow 6 -> 7
drawThickLine($im, $cx, $y6 + 55, $cx, $y6 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y6 + 115, $cLine);

// 7. DECISION 3: BLOWBAGETS PASS?
$y7 = 1480;
drawDecision($im, $cx, $y7, 460, 150, "BLOWBAGETS PASS?", "Vehicle Safe for Road?", $cAmberBg, $cAmber, $fontBold);

// Branch: Fail -> MAINTENANCE PIR QUEUE (Right Column)
$maintX = 1960;
drawThickLine($im, $cx + 230, $y7, $maintX, $y7, $cRed, 4);
drawArrowRight($im, $maintX - 160, $y7, $cRed);
imagettftext($im, 14, 0, $cx + 250, $y7 - 10, $cRed, $fontBold, "FAIL (Defect Found)");

// Right column - Maintenance loop
drawProcess($im, $maintX, $y7, 440, 110, "VEHICLE GROUNDED & PIR QUEUE", "SAFETY TECH / MECHANIC", "Pre-Repair Inspection: diagnose defect & order parts", $cRedBg, $cRed, $fontBold, $fontRegular);

// Maintenance Arrow down
drawThickLine($im, $maintX, $y7 + 55, $maintX, $y7 + 140, $cLine, 4);
drawArrowDown($im, $maintX, $y7 + 140, $cLine);

drawProcess($im, $maintX, $y7 + 200, 440, 110, "REPAIR & PMS RECALIBRATION", "WORKSHOP", "Service performed, threshold reset, asset restored to Active", $cGreenBg, $cGreen, $fontBold, $fontRegular);

// Maintenance loop back to Dispatch
drawThickLine($im, $maintX, $y7 + 255, $maintX, $y5, $cGreen, 4);
drawThickLine($im, $maintX, $y5, $cx + 310, $y5, $cGreen, 4);
drawArrowLeft($im, $cx + 310, $y5, $cGreen);
imagettftext($im, 13, 0, $maintX - 220, $y5 - 10, $cGreen, $fontBold, "Re-assign Cleared Vehicle");

// Branch: Pass -> Gate Checkpoint
drawThickLine($im, $cx, $y7 + 75, $cx, $y7 + 135, $cGreen, 4);
drawArrowDown($im, $cx, $y7 + 135, $cGreen);
imagettftext($im, 14, 0, $cx + 15, $y7 + 105, $cGreen, $fontBold, "PASS (Roadworthy)");

// 8. INPUT/OUTPUT: GATE SECURITY DEPARTURE SCAN
$y8 = 1690;
drawIO($im, $cx, $y8, 620, 110, "GATE OUT: QR Code Security Scan", "Guard verifies e-DTT, logs departure timestamp & starting odometer", $cPurpleBg, $cPurple, $fontBold, $fontRegular);

// Arrow 8 -> 9
drawThickLine($im, $cx, $y8 + 55, $cx, $y8 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y8 + 115, $cLine);

// 9. PROCESS: LIVE TRANSIT & TELEMATICS MONITORING
$y9 = 1880;
drawProcess($im, $cx, $y9, 640, 110, "EN ROUTE: Live GPS Radar & Fuel Logging", "TELEMATICS & DRIVER PWA", "Live ping stream, speed watcher (>80km/h alert), fuel receipt capture", $cBlueBg, $cPrimary, $fontBold, $fontRegular);

// Arrow 9 -> 10
drawThickLine($im, $cx, $y9 + 55, $cx, $y9 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y9 + 115, $cLine);

// 10. INPUT/OUTPUT: GATE SECURITY ARRIVAL SCAN
$y10 = 2070;
drawIO($im, $cx, $y10, 620, 110, "GATE IN: Return Verification Scan", "Guard logs arrival timestamp, return odometer & passenger headcount", $cPurpleBg, $cPurple, $fontBold, $fontRegular);

// Arrow 10 -> 11
drawThickLine($im, $cx, $y10 + 55, $cx, $y10 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y10 + 115, $cLine);

// 11. DECISION 4: ODOMETER & FUEL VARIANCE AUDIT
$y11 = 2260;
drawDecision($im, $cx, $y11, 480, 150, "ODOMETER VARIANCE VALID?", "Distance matches OSRM route & fuel?", $cAmberBg, $cAmber, $fontBold);

// Branch: Variance Anomaly (Left side)
$auditFlagX = 380;
drawThickLine($im, $cx - 240, $y11, $auditFlagX + 180, $y11, $cRed, 4);
drawArrowLeft($im, $auditFlagX + 180, $y11, $cRed);
imagettftext($im, 14, 0, $cx - 520, $y11 - 12, $cRed, $fontBold, "UNEXPLAINED VARIANCE (>15%)");

drawProcess($im, $auditFlagX, $y11, 350, 120, "FLAG AUDIT ANOMALY", "COA AUDITOR INVESTIGATION", "Lock reimbursement,\ninspect route discrepancy", $cRedBg, $cRed, $fontBold, $fontRegular);

// Discrepancy joins into audit trail
drawThickLine($im, $auditFlagX, $y11 + 55, $auditFlagX, 2460, $cRed, 4);
drawThickLine($im, $auditFlagX, 2460, $cx - 320, 2460, $cRed, 4);
drawArrowRight($im, $cx - 320, 2460, $cRed);

// Branch: Within Tolerance
drawThickLine($im, $cx, $y11 + 75, $cx, $y11 + 135, $cGreen, 4);
drawArrowDown($im, $cx, $y11 + 135, $cGreen);
imagettftext($im, 14, 0, $cx + 15, $y11 + 105, $cGreen, $fontBold, "NORMAL VARIANCE");

// 12. PROCESS: POST-TRIP AUDIT & COMPLIANCE (Form B & COA Ledger)
$y12 = 2460;
drawProcess($im, $cx, $y12, 640, 110, "POST-TRIP RECONCILIATION & COA FORM B", "COA RESIDENT AUDITOR & SYSTEM", "Compute CPK (Cost per KM), write immutable audit log, compile Form B report", $cGreenBg, $cGreen, $fontBold, $fontRegular);

// Arrow 12 -> 13
drawThickLine($im, $cx, $y12 + 55, $cx, $y12 + 115, $cLine, 4);
drawArrowDown($im, $cx, $y12 + 115, $cLine);

// 13. TERMINATOR: TRIP COMPLETED & ARCHIVED
$y13 = 2650;
drawTerminal($im, $cx, $y13, 480, 80, "TRIP CLOSED & ARCHIVED", "COA Compliant • Vehicle Released to Available Pool", $cNavy, $cGreen, $cWhite, $fontBold, $fontRegular);

// ---------------- FOOTER & SYSTEM SUMMARY PANEL ----------------
$footY = 2800;
drawRoundedRect($im, 100, $footY, 2200, 720, 20, $cWhite, $cLineSubtle, 2);

// Footer Title
imagettftext($im, 22, 0, 140, $footY + 50, $cNavy, $fontBold, "PHILIPPINE INFORMATION AGENCY — ENTERPRISE RBAC RESPONSIBILITY MATRIX");
imagettftext($im, 14, 0, 140, $footY + 80, $cMuted, $fontRegular, "Traceability of Roles across Every Stage of Official Government Travel Workflow");
imageline($im, 140, $footY + 100, 2260, $footY + 100, $cLineSubtle);

// 4 Summary Columns in Footer
$colW = 500;
// Col 1
$c1X = 140;
drawRoundedRect($im, $c1X, $footY + 130, $colW, 540, 12, $cBlueBg, $cPrimary, 2);
imagettftext($im, 16, 0, $c1X + 25, $footY + 170, $cPrimary, $fontBold, "1. INITIATION & APPROVAL");
imagettftext($im, 13, 0, $c1X + 25, $footY + 205, $cDark, $fontBold, "Roles: Requestor, OIC, Admin Head");
$txt1 = "• VRS Form Draft with justification\n• Passenger roster validation\n• Tier 1 Division Head endorsement\n• Tier 2 Director authorization\n• Enforced 4-hour SLA auto-escalation\n• Budget & official itinerary check";
imagettftext($im, 12, 0, $c1X + 25, $footY + 240, $cMuted, $fontRegular, $txt1);

// Col 2
$c2X = $c1X + $colW + 25;
drawRoundedRect($im, $c2X, $footY + 130, $colW, 540, 12, $cPurpleBg, $cPurple, 2);
imagettftext($im, 16, 0, $c2X + 25, $footY + 170, $cPurple, $fontBold, "2. DISPATCH & SAFETY");
imagettftext($im, 13, 0, $c2X + 25, $footY + 205, $cDark, $fontBold, "Roles: Dispatcher, Driver, Tech");
$txt2 = "• Motorpool vehicle fleet registry\n• Driver license & safety scoring check\n• Tollway RFID card allocation\n• Mandatory BLOWBAGETS Checklist\n• Automatic grounding on critical faults\n• PIR Pre-Repair mechanic queue";
imagettftext($im, 12, 0, $c2X + 25, $footY + 240, $cMuted, $fontRegular, $txt2);

// Col 3
$c3X = $c2X + $colW + 25;
drawRoundedRect($im, $c3X, $footY + 130, $colW, 540, 12, $cAmberBg, $cAmber, 2);
imagettftext($im, 16, 0, $c3X + 25, $footY + 170, $cAmber, $fontBold, "3. SECURITY & TELEMATICS");
imagettftext($im, 13, 0, $c3X + 25, $footY + 205, $cDark, $fontBold, "Roles: Security Guard, IoT Engine");
$txt3 = "• Gate QR code checkpoint scan\n• Departure & return odometer locks\n• Real-time GPS radar tracking\n• Overspeeding telemetry alerts\n• Geofence boundary detection\n• Driver Mobile PWA live telemetry";
imagettftext($im, 12, 0, $c3X + 25, $footY + 240, $cMuted, $fontRegular, $txt3);

// Col 4
$c4X = $c3X + $colW + 25;
drawRoundedRect($im, $c4X, $footY + 130, $colW, 540, 12, $cGreenBg, $cGreen, 2);
imagettftext($im, 16, 0, $c4X + 25, $footY + 170, $cGreen, $fontBold, "4. COA AUDIT & GOVERNANCE");
imagettftext($im, 13, 0, $c4X + 25, $footY + 205, $cDark, $fontBold, "Roles: COA Auditor, Super Admin");
$txt4 = "• Post-trip fuel & receipts cross-check\n• Fuel Cost Per Kilometer (CPK) metric\n• Odometer variance anomaly checks\n• Automated COA Form B generation\n• NFR-3 Immutable SHA audit trail\n• Super Admin user provisioning";
imagettftext($im, 12, 0, $c4X + 25, $footY + 240, $cMuted, $fontRegular, $txt4);

// Bottom Copyright Bar
imagettftext($im, 12, 0, 100, 3560, $cMuted, $fontMono, "PIA-AFMS v2.1 • Generated by System Architecture Visualizer • Formally compliant with COA Circulars & DBM Fleet Guidelines");

// Save file
$outPath = __DIR__ . '/public/assets/images/system_entire_flowchart.png';
imagepng($im, $outPath, 9);
imagedestroy($im);

echo "SUCCESS: Saved flowchart image to " . $outPath . " (" . filesize($outPath) . " bytes)\n";
