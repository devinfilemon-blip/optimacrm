<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Company Tax Invoices | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Company Tax Invoices</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-placement.php">Placements</a></li>
                                    <li class="breadcrumb-item active">Company Tax Invoices</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mb-3">Every placement that has been invoiced to the client company. Invoice details are edited on the placement itself.</p>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Company</th>
                                <th>Candidate</th>
                                <th>Post</th>
                                <th>CTC</th>
                                <th>Charges</th>
                                <th>GST</th>
                                <th>Total</th>
                                <th>Received</th>
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
    return '<span class="optima-badge ' + cls + '">' + esc(status) + '</span>';
}

function fngetlistcompanyinvoices() {
    $.ajax({
        url: 'api.php', method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistcompanyinvoices' }),
        success: function (response) {
            var rows = '';
            (response.data || []).forEach(function (p) {
                rows += '<tr>' +
                    '<td>' + esc(p.sInvoiceNo) + '</td>' +
                    '<td>' + esc(p.dInvoiceDate) + '</td>' +
                    '<td>' + esc(p.sCompanyName || '-') + '</td>' +
                    '<td>' + esc(p.sCandidateName || '-') + '</td>' +
                    '<td>' + esc(p.sPost || '-') + '</td>' +
                    '<td>' + fmtCurrency(p.dCtc) + '</td>' +
                    '<td>' + fmtCurrency(p.dCharges) + '</td>' +
                    '<td>' + fmtCurrency(p.dTotalGst) + '</td>' +
                    '<td>' + fmtCurrency(p.dAmount) + '</td>' +
                    '<td>' + fmtCurrency(p.dRecAmount) + '</td>' +
                    '<td>' + statusBadge(p.sPaymentStatus) + '</td>' +
                    '<td>' +
                        '<a href="add-placement.php?id=' + p.iPlacementId + '" class="btn btn-outline-primary btn-sm me-1" title="View / Edit"><i class="bx bx-edit-alt"></i></a>' +
                        '<a href="generate-invoice.php?id=' + p.iPlacementId + '" target="_blank" class="btn btn-outline-info btn-sm" title="Download / Print"><i class="bx bx-download"></i></a>' +
                    '</td>' +
                    '</tr>';
            });
            if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
            $('#datatable tbody').html(rows || '<tr><td colspan="12">No company invoices found yet</td></tr>');
            if (rows) $('#datatable').DataTable({ order: [[1, 'desc']] });
        }
    });
}

$(document).ready(function () { fngetlistcompanyinvoices(); });
</script>
