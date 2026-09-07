<?php
include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
include 'layouts/config.php';
require 'assets/libs/fpdf/fpdf.php';
require 'invoice-pdf-helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { die('Invalid invoice id.'); }

$stmt = mysqli_prepare($link, "SELECT e.*, cd.sCandidateName, c.sCompanyName, p.dCtc AS placementCtc, p.sPost, p.sWorkedBy AS recruiterName
                                FROM tblexpense e
                                LEFT JOIN tblplacement p ON p.iPlacementId = e.iPlacementId
                                LEFT JOIN tblcandidate cd ON cd.iCandidateId = p.iCandidateId
                                LEFT JOIN tblcompany c ON c.iCompanyId = p.iCompanyId
                                WHERE e.iExpenseId = ? AND e.sExpenseType = 'Commission'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$inv = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$inv) { die('Recruiter invoice not found.'); }

if (!$inv['sInvoiceNo'] || !$inv['dInvoiceDate']) {
    include 'layouts/head-main.php';
    ?>
    <head><title>Invoice not ready | <?php echo APP_NAME; ?></title><?php include 'layouts/head.php'; ?></head>
    <?php include 'layouts/body.php'; ?>
    <div class="container-fluid p-5">
        <div class="alert alert-warning">
            <h5>Invoice details missing</h5>
            <p>Please fill in the Invoice No and Invoice Date before generating a PDF.</p>
            <a href="add-recruiter-invoice.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">Edit Invoice</a>
        </div>
    </div>
    </body></html>
    <?php
    exit;
}

$ctc = (float) $inv['placementCtc'];
$descLines = ['Recruiter commission for placing ' . ($inv['sCandidateName'] ?: 'candidate') . ($inv['sCompanyName'] ? ' at ' . $inv['sCompanyName'] : '') . ($inv['sPost'] ? ' (' . $inv['sPost'] . ')' : '')];
if ($ctc > 0) {
    $descLines[] = '20% of Annual CTC (' . money($ctc) . '/-)';
}

$custLines = px($inv['recruiterName'] ?: '-');
$custLines .= "\nCommission for: " . px($inv['sCandidateName'] ?: '-');

renderTaxInvoicePdf([
    'invoiceNo' => $inv['sInvoiceNo'],
    'invoiceDate' => $inv['dInvoiceDate'],
    'buyerRefLabel' => 'Placement',
    'buyerRef' => $inv['sCompanyName'],
    'customerLabel' => 'Paid To (Recruiter)',
    'customerLines' => $custLines,
    'descLines' => $descLines,
    'baseAmount' => (float) $inv['dAmount'],
    'sgst' => (float) $inv['dSgst'],
    'cgst' => (float) $inv['dCgst'],
    'totalGst' => (float) $inv['dTotalGst'],
    'grandTotal' => (float) $inv['dInvoiceAmount'],
    'filenamePrefix' => 'Commission-Invoice',
]);
