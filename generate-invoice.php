<?php
include 'layouts/session.php';
include 'layouts/config.php';
require 'assets/libs/fpdf/fpdf.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { die('Invalid placement id.'); }

$stmt = mysqli_prepare($link, "SELECT p.*, c.sCompanyName, c.sAddress AS sCompanyAddress, c.sGstin AS sCompanyGstin,
                                       r.sReqNo, cd.sCandidateName, cd.sRef1
                                FROM tblplacement p
                                LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                                LEFT JOIN tblrequirement r ON r.iReqId = p.iReqId
                                LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                                WHERE p.iPlacementId = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$placement = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$placement) { die('Placement not found.'); }

if (!$placement['sInvoiceNo'] || !$placement['dInvoiceDate'] || (float) $placement['dCharges'] <= 0) {
    include 'layouts/head-main.php';
    ?>
    <head><title>Invoice not ready | <?php echo APP_NAME; ?></title><?php include 'layouts/head.php'; ?></head>
    <?php include 'layouts/body.php'; ?>
    <div class="container-fluid p-5">
        <div class="alert alert-warning">
            <h5>Invoice details missing</h5>
            <p>Please fill in the Invoice No, Invoice Date and Charges in the Invoice &amp; GST section before generating a PDF.</p>
            <a href="add-placement.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">Edit Placement</a>
        </div>
    </div>
    </body></html>
    <?php
    exit;
}

/* ---- helpers ---- */
function px($s) {
    if ($s === null) return '';
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string) $s);
    return $out === false ? '' : $out;
}
function money($n) { return number_format((float) $n, 2); }

function numWordsIndian($num) {
    $num = (int) round($num);
    if ($num === 0) return 'Zero';
    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
        'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    $twoDigits = function ($n) use ($ones, $tens) {
        if ($n < 20) return $ones[$n];
        $t = intdiv($n, 10); $o = $n % 10;
        return trim($tens[$t] . ($o ? ' ' . $ones[$o] : ''));
    };
    $threeDigits = function ($n) use ($ones, $twoDigits) {
        $str = '';
        if ($n >= 100) { $str .= $ones[intdiv($n, 100)] . ' Hundred '; $n %= 100; }
        $str .= $twoDigits($n);
        return trim($str);
    };

    $crore = intdiv($num, 10000000); $num %= 10000000;
    $lakh = intdiv($num, 100000); $num %= 100000;
    $thousand = intdiv($num, 1000); $num %= 1000;
    $hundred = $num;

    $parts = [];
    if ($crore) $parts[] = $threeDigits($crore) . ' Crore';
    if ($lakh) $parts[] = $threeDigits($lakh) . ' Lakh';
    if ($thousand) $parts[] = $threeDigits($thousand) . ' Thousand';
    if ($hundred) $parts[] = $threeDigits($hundred);

    return trim(implode(' ', $parts));
}

/* ---- derived invoice content ---- */
$candidateName = $placement['sCandidateName'];
$charges = (float) $placement['dCharges'];
// Annual CTC is entered directly (an offer letter's real CTC rarely equals
// monthly salary x 12 once PF, gratuity, bonuses etc. are counted). Older
// placements saved before this field existed only have a monthly dSalary —
// fall back to that x 12 for them so their invoices don't go blank.
$annualCtc = (float) $placement['dCtc'];
if ($annualCtc <= 0) {
    $annualCtc = (float) $placement['dSalary'] * 12;
}
$pct = $annualCtc > 0 ? round(($charges / $annualCtc) * 100, 2) : 0;

$joiningCompany = $placement['sCompanyName'];
$descLines = ['Recruitment consulting charges for ' . $candidateName . ($joiningCompany ? ' joining at ' . $joiningCompany : '')];
if ($annualCtc > 0) {
    $descLines[] = $pct . '% of Annual CTC (' . money($annualCtc) . '/-)';
}
$vacancyRef = $placement['sReqNo'] ?: $placement['sExternalReqNo'];
if ($vacancyRef) { $descLines[] = 'Vacancy No- ' . $vacancyRef; }

$navy = [39, 48, 111];

class InvoicePdf extends FPDF {
    function Header() {}
    function Footer() {}
}

// FPDF's own MultiCell wraps by word width, not by counting "\n"s — a block
// with only two explicit line breaks can still render as three or four lines
// once a long line wraps, and the fixed box drawn around it was overflowing
// as a result. This mirrors FPDF's wrapping so a box can be sized to fit.
function pdfWrapLineCount($pdf, $w, $text) {
    $lines = 0;
    foreach (explode("\n", $text) as $para) {
        if ($para === '') { $lines++; continue; }
        $words = explode(' ', $para);
        $line = '';
        foreach ($words as $word) {
            $test = $line === '' ? $word : $line . ' ' . $word;
            if ($pdf->GetStringWidth($test) > $w && $line !== '') {
                $lines++;
                $line = $word;
            } else {
                $line = $test;
            }
        }
        $lines++;
    }
    return max(1, $lines);
}

$pdf = new InvoicePdf('P', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pageW = 190;

/* Header — two rows, matching the original template: a full-width navy
   "TAX INVOICE" bar, then a plain row underneath with the company logo. */
$pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->Rect(10, 10, $pageW, 10, 'DF');
$pdf->SetXY(10, 10);
$pdf->Cell($pageW, 10, px('TAX INVOICE'), 0, 0, 'C');
$pdf->SetTextColor(0, 0, 0);

$logoRowH = 12;
$pdf->Rect(10, 20, $pageW, $logoRowH);
$logoPath = __DIR__ . '/' . APP_LOGO;
if (is_file($logoPath)) {
    $logoH = 8.5;
    $logoW = $logoH * (1241 / 502); // source image's native aspect ratio
    $pdf->Image($logoPath, 10 + $pageW - $logoW - 3, 20 + ($logoRowH - $logoH) / 2, $logoW, $logoH);
}
$pdf->SetY(32);

/* Biller (left) / Invoice meta (right) — drawn to a shared height so neither
   box's border falls short of the other's. */
$leftW = 110; $rightW = 80;
$topY = $pdf->GetY();
$pad = 2;

$pdf->SetFont('Helvetica', 'B', 11);
$titleLines = pdfWrapLineCount($pdf, $leftW - 2 * $pad, px(BILLER_NAME));
$addressText = px(BILLER_ADDRESS_LINE1 . "\n" . BILLER_ADDRESS_LINE2 . "\nContact: " . BILLER_CONTACT . "\n" . BILLER_EMAIL);
$pdf->SetFont('Helvetica', '', 9);
$addressLines = pdfWrapLineCount($pdf, $leftW - 2 * $pad, $addressText);
$leftH = $pad + $titleLines * 5.5 + $addressLines * 4.5 + $pad;

$rightRowH = max(7, ($leftH) / 3);
$boxH = max($leftH, $rightRowH * 3);

$pdf->Rect(10, $topY, $leftW, $boxH);
$pdf->SetXY(10 + $pad, $topY + $pad);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->MultiCell($leftW - 2 * $pad, 5.5, px(BILLER_NAME), 0, 'L');
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetX(10 + $pad);
$pdf->MultiCell($leftW - 2 * $pad, 4.5, $addressText, 0, 'L');

$metaRowH = $boxH / 3;
$metaRows = [
    ['Invoice No', px($placement['sInvoiceNo'])],
    ['Invoice Date', px(date('d/m/Y', strtotime($placement['dInvoiceDate'])))],
    ['Buyer Ref', px($placement['sRef1'] ?: '-')],
];
foreach ($metaRows as $i => $mr) {
    $pdf->SetXY(10 + $leftW, $topY + $i * $metaRowH);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(32, $metaRowH, px($mr[0]), 1, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell($rightW - 32, $metaRowH, $mr[1], 1, 0, 'L');
}

$pdf->SetY($topY + $boxH);
$pdf->Ln(4);

/* Customer */
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetX(10);
$pdf->Cell($pageW, 5, px('Customer'), 0, 1);
$custLines = px($placement['sCompanyName'] ?: '-');
if (!empty($placement['sCompanyAddress'])) { $custLines .= "\n" . px($placement['sCompanyAddress']); }
if (!empty($placement['sCompanyGstin'])) { $custLines .= "\nGSTIN: " . px($placement['sCompanyGstin']); }
$pdf->SetX(10);
$pdf->SetFont('Helvetica', 'B', 10);
$custStartY = $pdf->GetY();
$pdf->MultiCell($pageW, 5, $custLines, 1, 'L');
$pdf->Ln(2);

/* Item table — plain bordered header (no fill), matching the original. */
$colSr = 15; $colDesc = 110; $colSac = 30; $colAmt = 35;
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetX(10);
$pdf->Cell($colSr, 7, px('SR NO'), 1, 0, 'C');
$pdf->Cell($colDesc, 7, px('DESCRIPTION'), 1, 0, 'C');
$pdf->Cell($colSac, 7, px('SAC Code'), 1, 0, 'C');
$pdf->Cell($colAmt, 7, px('AMOUNT'), 1, 1, 'C');

// Each description line is its own bordered row (as in the original), with
// the SR NO / SAC Code / AMOUNT columns merged down the side across all of
// them — FPDF has no real cell merge, so that merge is one tall Rect drawn
// after the fact, sized to whatever total height the description rows end up needing.
$pdf->SetFont('Helvetica', '', 9);
$descPad = 1.5;
$startY = $pdf->GetY();
$curY = $startY;
foreach ($descLines as $line) {
    $subLines = pdfWrapLineCount($pdf, $colDesc - 2 * $descPad, px($line));
    $lineH = max(6, $subLines * 4.5 + 2 * $descPad);
    $pdf->Rect(10 + $colSr, $curY, $colDesc, $lineH);
    $pdf->SetXY(10 + $colSr + $descPad, $curY + $descPad);
    $pdf->MultiCell($colDesc - 2 * $descPad, 4.5, px($line), 0, 'L');
    $curY += $lineH;
}
$rowH = $curY - $startY;

$pdf->Rect(10, $startY, $colSr, $rowH);
$pdf->SetXY(10, $startY + ($rowH - 5) / 2);
$pdf->Cell($colSr, 5, '1', 0, 0, 'C');
$pdf->Rect(10 + $colSr + $colDesc, $startY, $colSac, $rowH);
$pdf->SetXY(10 + $colSr + $colDesc, $startY + ($rowH - 5) / 2);
$pdf->Cell($colSac, 5, px(BILLER_SAC_CODE), 0, 0, 'C');
$pdf->Rect(10 + $colSr + $colDesc + $colSac, $startY, $colAmt, $rowH);
$pdf->SetXY(10 + $colSr + $colDesc + $colSac, $startY + ($rowH - 5) / 2);
$pdf->Cell($colAmt, 5, money($charges), 0, 0, 'R');
$pdf->SetXY(10, $startY + $rowH);

/* Totals */
$sgst = (float) $placement['dSgst'];
$cgst = (float) $placement['dCgst'];
$totalGst = (float) $placement['dTotalGst'];
$grandTotal = (float) $placement['dAmount'];

$totalsX = 10 + $colSr + $colDesc;
$totalsW = $colSac + $colAmt;
$labelW = $colSac;

$totalsRows = [
    ['TOTAL', money($charges), false],
    ['SGST', money($sgst), false],
    ['CGST', money($cgst), false],
    ['Total GST', money($totalGst), false],
    ['Grand Total', money($grandTotal), true],
];
foreach ($totalsRows as $tr) {
    $pdf->SetX($totalsX);
    $pdf->SetFont('Helvetica', $tr[2] ? 'B' : '', 9);
    $pdf->Cell($labelW, 7, px($tr[0]), 1, 0, 'L');
    $pdf->Cell($totalsW - $labelW, 7, $tr[1], 1, 1, 'R');
}

/* Amount in words */
$pdf->Ln(2);
$pdf->SetFillColor(255, 249, 196);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetX(10);
$pdf->MultiCell($pageW, 6, px('Total Invoice Amount in words: Rupees ' . numWordsIndian($grandTotal) . ' Only'), 1, 'L', true);
$pdf->Ln(3);

/* Declaration + Bank details */
$declText = "We declare that the details mentioned in the invoice are correct. Payment must be realized within " . BILLER_PAYMENT_TERMS_DAYS . " days. Complaints if any will be entertained only within 2 days from the date of invoice in writing. All disputes are subject to " . BILLER_JURISDICTION . " Jurisdiction only.";
$bankText = "Company's Bank Details:\nBank Name: " . BILLER_BANK_NAME . "\nA/c No: " . BILLER_BANK_ACCOUNT_NO . "\nBranch: " . BILLER_BANK_BRANCH . "\nIFSC Code: " . BILLER_BANK_IFSC;

$declW = 105; $bankW = 85;
$blockY = $pdf->GetY();
$pdf->SetXY(10, $blockY);
$pdf->SetFont('Helvetica', '', 8);
$pdf->MultiCell($declW, 4.5, px("Declaration:\n" . $declText), 1, 'L');
$declBottomY = $pdf->GetY();

$pdf->SetXY(10 + $declW, $blockY);
$pdf->SetFillColor(214, 245, 214);
$pdf->SetFont('Helvetica', '', 8);
$pdf->MultiCell($bankW, 4.5, px($bankText), 1, 'L', true);
$bankBottomY = $pdf->GetY();

$pdf->SetY(max($declBottomY, $bankBottomY) + 12);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell($pageW, 5, px('For ' . strtoupper(BILLER_NAME)), 0, 1, 'R');
$pdf->Ln(12);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell($pageW, 5, px('Authorized Signatory'), 0, 1, 'R');

$filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $placement['sInvoiceNo']);
$pdf->Output('I', 'Invoice-' . $filenameSafe . '.pdf');
exit;
