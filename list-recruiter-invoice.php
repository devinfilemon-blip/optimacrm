<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Recruiter Tax Invoices | <?php echo APP_NAME; ?></title>
    <?php include 'layouts/head.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<?php include 'layouts/body.php'; ?>
<div id="layout-wrapper">
    <?php include 'layouts/menu.php'; ?>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Recruiter Tax Invoices</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-expense.php">Revenue &amp; Financial</a></li>
                                    <li class="breadcrumb-item active">Recruiter Tax Invoices</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mb-3">One row per placement's recruiter commission (20% of CTC, calculated automatically). Fill in an invoice number to bill/record it formally.</p>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Recruiter</th>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>CTC</th>
                                <th>Commission</th>
                                <th>GST</th>
                                <th>Total Invoice</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>
</body>
</html>

<?php include 'layouts/vendor-scripts.php'; ?>
<script>
function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
function fmtCurrency(n) {
    n = parseFloat(n || 0);
    return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function statusBadge(status) {
    var cls = 'optima-badge-default';
    if (status === 'Paid') cls = 'optima-badge-closed';
    else if (status === 'Partially Paid') cls = 'optima-badge-hold';
    else if (status === 'Unpaid') cls = 'optima-badge-refine';
    else if (status === 'Not Invoiced') cls = 'optima-badge-searching';
    return '<span class="optima-badge ' + cls + '">' + esc(status) + '</span>';
}

function fngetlistrecruiterinvoices() {
    $.ajax({
        url: 'api.php', method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistrecruiterinvoices' }),
        success: function (response) {
            var rows = '';
            (response.data || []).forEach(function (e) {
                var canDownload = e.sInvoiceNo && e.dInvoiceDate;
                rows += '<tr>' +
                    '<td>' + esc(e.sInvoiceNo || '-') + '</td>' +
                    '<td>' + esc(e.dInvoiceDate || '-') + '</td>' +
                    '<td>' + esc(e.recruiterName || '-') + '</td>' +
                    '<td>' + esc(e.sCandidateName || '-') + '</td>' +
                    '<td>' + esc(e.sCompanyName || '-') + '</td>' +
                    '<td>' + fmtCurrency(e.placementCtc) + '</td>' +
                    '<td>' + fmtCurrency(e.dAmount) + '</td>' +
                    '<td>' + fmtCurrency(e.dTotalGst) + '</td>' +
                    '<td>' + fmtCurrency(e.dInvoiceAmount || e.dAmount) + '</td>' +
                    '<td>' + fmtCurrency(e.dPaidAmount) + '</td>' +
                    '<td>' + statusBadge(e.sPaymentStatus) + '</td>' +
                    '<td>' +
                        '<a href="add-recruiter-invoice.php?id=' + e.iExpenseId + '" class="btn btn-outline-primary btn-sm me-1" title="View / Edit"><i class="bx bx-edit-alt"></i></a>' +
                        (canDownload
                            ? '<a href="generate-recruiter-invoice.php?id=' + e.iExpenseId + '" target="_blank" class="btn btn-outline-info btn-sm" title="Download / Print"><i class="bx bx-download"></i></a>'
                            : '<button class="btn btn-outline-secondary btn-sm" disabled title="Add invoice number and date first"><i class="bx bx-download"></i></button>') +
                    '</td>' +
                    '</tr>';
            });
            if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
            $('#datatable tbody').html(rows || '<tr><td colspan="12">No recruiter commissions yet</td></tr>');
            if (rows) $('#datatable').DataTable({ order: [[1, 'desc']] });
        }
    });
}

$(document).ready(function () { fngetlistrecruiterinvoices(); });
</script>
