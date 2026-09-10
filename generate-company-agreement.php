<?php
include 'layouts/session.php';
include 'layouts/config.php';
require 'assets/libs/fpdf/fpdf.php';
require 'invoice-pdf-helpers.php';

if (($_SESSION['userRole'] ?? '') !== 'Admin') { die('Not authorized.'); }

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { die('Invalid agreement id.'); }

$stmt = mysqli_prepare($link, "SELECT ca.*, c.sCompanyName, c.sContactPerson AS sCompanyContactPerson, c.sAddress AS sCompanyAddress
                                FROM tblcompanyagreement ca
                                JOIN tblcompany c ON c.iCompanyId = ca.iCompanyId
                                WHERE ca.iAgreementId = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$a = mysqli_stmt_get_result($stmt)->fetch_assoc();
if (!$a) { die('Agreement not found.'); }

// The agreement's own Owner Name / Address (entered on the agreement form)
// take priority; fall back to the company record's for agreements saved
// before those fields existed.
$ownerNameText = $a['sOwnerName'] ?: $a['sCompanyContactPerson'];
$addressText = $a['sAddress'] ?: $a['sCompanyAddress'];

// ---- derived display values — a Pending agreement with terms not yet
// filled in prints the same blank-style placeholders as the original
// proposal template ("-----% ...", "---------Date"), so the document is
// still usable before the numbers are finalized. ----
function fmtPct($v, $fallback) {
    if ($v === null || $v === '') return $fallback;
    $s = number_format((float) $v, 2);
    $s = rtrim(rtrim($s, '0'), '.');
    return $s;
}
$pctText = fmtPct($a['dCtcPercentage'], '-----');
$gstText = fmtPct($a['dGovernmentTax'], '18');
$months = $a['iReplacementMonths'] !== null ? (int) $a['iReplacementMonths'] : 3;
$paymentDays = $a['iPaymentWithinDays'] !== null ? (int) $a['iPaymentWithinDays'] : 15;
$billingDays = $a['iBillingWithinDays'] !== null ? (int) $a['iBillingWithinDays'] : 8;
$effDateText = $a['dEffectiveDate'] ? date('d/m/Y', strtotime($a['dEffectiveDate'])) : '---------';
$dateHeaderText = $a['dEffectiveDate'] ? date('d/m/Y', strtotime($a['dEffectiveDate'])) : date('d/m/Y');

$pdf = new InvoicePdf('P', 'mm', 'A4');
$pdf->SetMargins(20, 15, 20);
$pdf->SetAutoPageBreak(true, 18);
$usableW = 170;

function agreementLogo($pdf) {
    $logoPath = __DIR__ . '/' . APP_LOGO;
    if (is_file($logoPath)) {
        $logoH = 12;
        $logoW = $logoH * (1241 / 502);
        $pdf->Image($logoPath, 210 - 20 - $logoW, 10, $logoW, $logoH);
    }
}

function agreementSection($pdf, $usableW, $title, $body) {
    $pdf->SetX(20);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 6, px($title), 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetX(20);
    $pdf->MultiCell($usableW, 5.5, px($body), 0, 'L');
    $pdf->Ln(4);
}

// ============================================================
// Page 1 — cover letter
// ============================================================
$pdf->AddPage();
agreementLogo($pdf);
$pdf->SetY(30);

$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell($usableW, 5, px('Date: ' . $dateHeaderText), 0, 1, 'R');
$pdf->Ln(4);

$pdf->SetFont('Helvetica', 'BU', 13);
$pdf->Cell($usableW, 8, px('Agreement Proposal'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->Cell(0, 6, px('To,'), 0, 1);
$pdf->SetX(20);
$pdf->MultiCell($usableW, 5.5, px('Company Owner name :- ' . ($ownerNameText ?: '')), 0, 'L');
$pdf->SetX(20);
$pdf->Cell(0, 6, px('Designation:-'), 0, 1);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetX(20);
$pdf->MultiCell($usableW, 5.5, px('Company Name :- ' . $a['sCompanyName']), 0, 'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->MultiCell($usableW, 5.5, px('Company Address:- ' . ($addressText ?: '')), 0, 'L');
$pdf->Ln(4);
$pdf->SetX(20);
$pdf->Cell(0, 6, px('Subject: - Recruitment Agreement Proposal.'), 0, 1);
$pdf->Ln(6);

$pdf->SetX(20);
$pdf->Cell(0, 6, px('Dear Sir,'), 0, 1);
$pdf->Ln(2);
$pdf->SetX(20);
$pdf->MultiCell($usableW, 5.5, px("As per our discussion please find our terms and conditions in agreement proposal. Please go through it and revert us with acceptance."), 0, 'L');
$pdf->Ln(14);

$pdf->Cell($usableW, 6, px('Regards,'), 0, 1, 'C');
$pdf->Cell($usableW, 6, px(BILLER_SIGNATORY_SHORT_NAME), 0, 1, 'C');
$pdf->Cell($usableW, 6, px(BILLER_NAME . ' (Optima Jobs)'), 0, 1, 'C');
$pdf->Cell($usableW, 6, px('Ichalkaranji Dist. Kolhapur'), 0, 1, 'C');
$pdf->Cell($usableW, 6, px('Maharashtra'), 0, 1, 'C');
$pdf->Cell($usableW, 6, px(BILLER_EMAIL), 0, 1, 'C');

// ============================================================
// Page 2 — who we are + commercial terms
// ============================================================
$pdf->AddPage();
agreementLogo($pdf);
$pdf->SetY(30);

$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Cell($usableW, 8, px('OPTIMA SERVICES'), 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Helvetica', '', 10);
$bullets = [
    'We at OPTIMA SERVICES provide Human Resource Consulting with an objective to facilitate the corporate sector to drive their Business Operations effectively and efficiently',
    'We provide staffing solutions in diverse industrial segments to clients.',
    "Our mission is to be the fore-runners in providing a platform where employers are able to pick the best talent from a pool of human resources, exclusively available with us.",
    'We have helped companies in hiring personnel for junior, middle and senior level positions.',
    'We have a commitment to serve our clients and nurture passion to make a difference.',
    'We support organizations through our professional and systematic approach.',
];
foreach ($bullets as $b) {
    $pdf->SetX(24);
    $pdf->Cell(4, 5.5, '-', 0, 0);
    $pdf->SetX(28);
    $pdf->MultiCell($usableW - 8, 5.5, px($b), 0, 'L');
}
$pdf->Ln(2);

$pdf->SetX(20);
$pdf->Cell(0, 6, px('Data Base'), 0, 1);
foreach (['Own Data base', 'Job Portal', 'Head Hunting', 'Referral', 'Social Media'] as $it) {
    $pdf->SetX(28);
    $pdf->Cell(4, 5.5, '-', 0, 0);
    $pdf->Cell(0, 5.5, px($it), 0, 1);
}
$pdf->Ln(4);

// Professional fees — "X% of Annual CTC..." highlighted
$pdf->SetX(20);
$pdf->SetFont('Helvetica', 'BU', 10);
$pdf->Cell(0, 6, px('Professional fees'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->Write(5.5, px('As discussed, the charges will be '));
$suffix = $pctText . '% of Annual CTC of the candidate.';
$pdf->SetFillColor(255, 255, 0);
$pdf->Cell($pdf->GetStringWidth(px($suffix)) + 2, 5.5, px($suffix), 0, 1, 'L', true);
$pdf->Ln(4);

// Government tax — "X% GST" highlighted
$pdf->SetX(20);
$pdf->SetFont('Helvetica', 'BU', 10);
$pdf->Cell(0, 6, px('Government tax'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$gstPhrase = $gstText . '% GST';
$pdf->SetFillColor(255, 255, 0);
$pdf->Cell($pdf->GetStringWidth(px($gstPhrase)) + 2, 5.5, px($gstPhrase), 0, 0, 'L', true);
$pdf->Cell(0, 5.5, px(' on professional fees.'), 0, 1);
$pdf->Ln(4);

// Replacement guarantee
$pdf->SetX(20);
$pdf->SetFont('Helvetica', 'BU', 10);
$pdf->Cell(0, 6, px('Replacement guarantee'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$monthWord = $months === 1 ? 'month' : 'months';
$pdf->MultiCell($usableW, 5.5, px("In case if a selected candidate resigns from the services on his / her own within the first {$months} {$monthWord} of joining, Optima Services will give a free replacement within a month period."), 0, 'L');
$pdf->Ln(1);
$pdf->SetX(20);
$pdf->SetFillColor(255, 255, 0);
$pdf->MultiCell($usableW, 5.5, px("*Replacement guarantee only applicable if payment made within {$paymentDays} days from invoice date."), 0, 'L', true);
$pdf->Ln(4);

// Payment schedules
$pdf->SetX(20);
$pdf->SetFont('Helvetica', 'BU', 10);
$pdf->Cell(0, 6, px('Payment schedules'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->SetFillColor(255, 255, 0);
$pdf->MultiCell($usableW, 5.5, px("All bills to be submitted within {$billingDays} days of the candidate's joining."), 0, 'L', true);

// ============================================================
// Page 3 — process terms + validity
// ============================================================
$pdf->AddPage();
agreementLogo($pdf);
$pdf->SetY(30);
$pdf->SetFont('Helvetica', '', 10);

agreementSection($pdf, $usableW, 'Response time', 'Once we share suitable profiles as per your requirements, we expect your reply on the same within 2 days.');
agreementSection($pdf, $usableW, 'Confidentiality', "Optima Services will always maintain the confidentiality of company's any information.");
agreementSection($pdf, $usableW, 'Confidentiality of Candidate Information', 'The Recruiter (Company) agrees that all information relating to candidates, including but not limited to resumes, contact details, employment history, references, compensation details, and any other personal or professional information ("Candidate Information"), shall be treated as strictly confidential. The Recruiter shall not disclose, share, or use such Candidate Information for any purpose unless required by law or with the prior consent of the Client and the Candidate.');
agreementSection($pdf, $usableW, 'Correspondence with candidates', "All correspondence with candidates including interview scheduling will be the responsibility of the Optima Services. The Client will send information of a Candidate's rejection to Optima Services, and it will be the responsibility of the Optima Services to inform the Candidates accordingly.");
agreementSection($pdf, $usableW, 'Offer letter to the candidates', "Upon the completion of the selection decision, the Client will inform the candidate of the selection through Optima Services. Contingent to this the Client will issue an offer letter to the candidate and upon his/her acceptance; convey the offered consolidated package to Optima Services for billing purposes.");
agreementSection($pdf, $usableW, 'Screening', 'Optima Services will do the 1st level screening and only candidates who satisfy the criteria of requirements should be sent to the company.');
agreementSection($pdf, $usableW, 'Right on Candidate', 'Candidates once sponsored by Optima Services will be treated as their candidate, if absorbed for any position by the organization during the next six months.');

$pdf->SetX(20);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(0, 6, px('Validity of terms:'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->Write(5.5, px('This Agreement shall be effective from '));
$pdf->SetFillColor(255, 255, 0);
$pdf->Cell($pdf->GetStringWidth(px($effDateText)) + 2, 5.5, px($effDateText), 0, 0, 'L', true);
$pdf->Write(5.5, px(' and be valid for a period of one year and unless terminated earlier in accordance with the terms specified hereunder.'));

// ============================================================
// Page 4 — termination + acceptance + signature
// ============================================================
$pdf->AddPage();
agreementLogo($pdf);
$pdf->SetY(30);

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetX(20);
$pdf->Cell(0, 6, px('Termination:'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->MultiCell($usableW, 5.5, px('This agreement will terminate on the happening of any one or more of the following events: on the expiry of its term or if either party terminates this agreement by giving a prior notice of two weeks in writing to the other party or by mutual consent of the parties, without assigning any reason whatsoever.'), 0, 'L');
$pdf->Ln(8);

$boxY = $pdf->GetY();
$boxH = 55;
$pdf->Rect(20, $boxY, $usableW, $boxH);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetXY(20, $boxY + 6);
$pdf->Cell($usableW, 7, px('ACCEPTANCE'), 0, 1, 'C');
$pdf->SetFont('Helvetica', 'I', 10);
$pdf->SetX(20);
$pdf->Cell($usableW, 6, px('I have read your terms and conditions, and agree to abide by them.'), 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetXY(25, $boxY + 25);
$pdf->Cell(15, 6, px('For'), 0, 0);
$pdf->Line(40, $boxY + 31, 100, $boxY + 31);
$pdf->SetXY(25, $boxY + 42);
$pdf->Cell(0, 6, px('Name & Designation      :'), 0, 1);

$pdf->SetY($boxY + $boxH + 12);
$stampPath = __DIR__ . '/' . BILLER_STAMP;
$stampSize = 32;
if (is_file($stampPath)) {
    $pdf->Image($stampPath, 20, $pdf->GetY(), $stampSize, $stampSize);
    $pdf->SetY($pdf->GetY() + $stampSize + 4);
} else {
    $pdf->Ln(6);
}
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->Cell(0, 6, px('For'), 0, 1);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetX(20);
$pdf->Cell(0, 6, px(BILLER_NAME . ' (Optima Jobs)'), 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(20);
$pdf->Cell(0, 6, px(BILLER_SIGNATORY_NAME), 0, 1);
$pdf->SetX(20);
$pdf->Cell(0, 6, px(BILLER_SIGNATORY_DESIGNATION), 0, 1);

$filenameSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', $a['sCompanyName']);
$pdf->Output('I', 'Agreement-' . $filenameSafe . '.pdf');
exit;
