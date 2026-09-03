<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Outstanding Invoices | <?php echo APP_NAME; ?></title>
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
                            <div>
                                <h4 class="mb-0 font-size-18">Outstanding Invoices</h4>
                                <p class="optima-dashboard-subtitle">Placements billed but not yet paid in full</p>
                            </div>
                            <div class="page-title-right">
                                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-rose h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-rose"><i class="bx bx-file-blank"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statOutstandingCount">&mdash;</p>
                                    <p class="optima-stat-label">Outstanding Invoices</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-amber h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-amber"><i class="bx bx-receipt"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statOutstandingInvoiced">&mdash;</p>
                                    <p class="optima-stat-label">Invoiced Amount</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-mint h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-mint"><i class="bx bx-check-double"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statOutstandingReceived">&mdash;</p>
                                    <p class="optima-stat-label">Received So Far</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card optima-stat-card accent-purple h-100">
                            <div class="card-body">
                                <div class="optima-stat-icon bg-purple"><i class="bx bx-wallet"></i></div>
                                <div class="optima-stat-text">
                                    <p class="optima-stat-value" id="statOutstandingPending">&mdash;</p>
                                    <p class="optima-stat-label">Amount Pending</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card optima-section-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <div><span id="message"></span></div>
                            <table id="datatable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Invoice</th>
                                        <th>Candidate</th>
                                        <th>Invoice Amount</th>
                                        <th>Received Amount</th>
                                        <th>Pending Amount</th>
                                        <th>Invoice Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
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
function badgeForPaymentStatus(status) {
    var cls = status === 'Partially Paid' ? 'optima-badge-hold' : 'optima-badge-refine';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Unpaid') + '</span>';
}

function fngetlistoutstandinginvoices() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistoutstandinginvoices' }),
        success: function (response) {
            if (response.status === 'success') {
                var data = response.data || [];
                var invoiced = 0, received = 0, pending = 0;
                var rows = '';
                data.forEach(function (p) {
                    invoiced += parseFloat(p.dAmount || 0);
                    received += parseFloat(p.dRecAmount || 0);
                    pending += parseFloat(p.dPendingAmount || 0);
                    rows += '<tr>' +
                        '<td>' + esc(p.sCompanyName || '-') + '</td>' +
                        '<td>' + esc(p.sInvoiceNo || '-') + '</td>' +
                        '<td>' + esc(p.sCandidateName) + '</td>' +
                        '<td>' + fmtCurrency(p.dAmount) + '</td>' +
                        '<td>' + fmtCurrency(p.dRecAmount) + '</td>' +
                        '<td>' + fmtCurrency(p.dPendingAmount) + '</td>' +
                        '<td>' + esc(p.dInvoiceDate || '-') + '</td>' +
                        '<td>' + badgeForPaymentStatus(p.sPaymentStatus) + '</td>' +
                        '</tr>';
                });

                $('#statOutstandingCount').text(data.length);
                $('#statOutstandingInvoiced').text(fmtCurrency(invoiced));
                $('#statOutstandingReceived').text(fmtCurrency(received));
                $('#statOutstandingPending').text(fmtCurrency(pending));

                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows || '<tr><td colspan="8">No outstanding invoices — everything is paid up.</td></tr>');
                if (rows) $('#datatable').DataTable({ order: [[5, 'desc']] });
            } else {
                $('#datatable tbody').html('<tr><td colspan="8">No outstanding invoices found</td></tr>');
            }
        }
    });
}

$(document).ready(function () { fngetlistoutstandinginvoices(); });
</script>
