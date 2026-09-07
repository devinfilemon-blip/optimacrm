<?php
include 'layouts/session.php';
include 'layouts/config.php';
require 'assets/libs/fpdf/fpdf.php';
require 'invoice-pdf-helpers.php';

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

$custLines = px($placement['sCompanyName'] ?: '-');
if (!empty($placement['sCompanyAddress'])) { $custLines .= "\n" . px($placement['sCompanyAddress']); }
if (!empty($placement['sCompanyGstin'])) { $custLines .= "\nGSTIN: " . px($placement['sCompanyGstin']); }

renderTaxInvoicePdf([
    'invoiceNo' => $placement['sInvoiceNo'],
    'invoiceDate' => $placement['dInvoiceDate'],
    'buyerRefLabel' => 'Buyer Ref',
    'buyerRef' => $placement['sRef1'],
    'customerLabel' => 'Customer',
    'customerLines' => $custLines,
    'descLines' => $descLines,
    'baseAmount' => $charges,
    'sgst' => (float) $placement['dSgst'],
    'cgst' => (float) $placement['dCgst'],
    'totalGst' => (float) $placement['dTotalGst'],
    'grandTotal' => (float) $placement['dAmount'],
    'filenamePrefix' => 'Invoice',
]);
