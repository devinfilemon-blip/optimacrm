<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Candidates &amp; Placements | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Candidates &amp; Placements</h4>
                            <div class="page-title-right">
                                <a href="add-placement.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Placement</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Selection No</th>
                                <th>Candidate</th>
                                <th>Company</th>
                                <th>Post</th>
                                <th>Joining Date</th>
                                <th>Status</th>
                                <th>Invoice Amount</th>
                                <th>Received</th>
                                <th>Worked By</th>
                                <th>Resume</th>
                                <th>Invoice</th>
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
function badgeForJoining(status) {
    var cls = 'optima-badge-default';
    var s = (status || '').toLowerCase();
    if (s === 'amount received') cls = 'optima-badge-closed';
    else if (s === 'job left' || s === 'not joined') cls = 'optima-badge-refine';
    else if (s === 'pending') cls = 'optima-badge-hold';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Pending') + '</span>';
}

function pad2(n) { return n < 10 ? '0' + n : '' + n; }
function ymd(d) { return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); }

function getPeriodRange(period) {
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var start, end;
    if (period === 'weekly') {
        var dow = today.getDay(); // 0=Sun..6=Sat
        var diffToMonday = (dow === 0 ? 6 : dow - 1);
        start = new Date(today); start.setDate(today.getDate() - diffToMonday);
        end = new Date(start); end.setDate(start.getDate() + 6);
    } else if (period === 'quarterly') {
        var qStartMonth = Math.floor(today.getMonth() / 3) * 3;
        start = new Date(today.getFullYear(), qStartMonth, 1);
        end = new Date(today.getFullYear(), qStartMonth + 3, 0);
    } else if (period === 'yearly') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    }
    return { start: ymd(start), end: ymd(end) };
}

var placementRowsById = {};
function fngetlistplacement() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistplacement' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                var data = response.data;
                var params = new URLSearchParams(window.location.search);
                if (params.get('pendingOnly') === '1') {
                    data = data.filter(function (p) { return parseFloat(p.dAmount || 0) > parseFloat(p.dRecAmount || 0); });
                }
                var period = params.get('period');
                var dateField = params.get('dateField');
                if (period && dateField) {
                    var range = getPeriodRange(period);
                    data = data.filter(function (p) { return p[dateField] && p[dateField] >= range.start && p[dateField] <= range.end; });
                }
                placementRowsById = {};
                data.forEach(function (p) {
                    placementRowsById[p.iPlacementId] = p;
                    rows += '<tr data-id="' + p.iPlacementId + '">' +
                        '<td>' + esc(p.sSelectionNo) + '</td>' +
                        '<td>' + esc(p.sCandidateName) + '</td>' +
                        '<td>' + esc(p.sCompanyName || '-') + '</td>' +
                        '<td>' + esc(p.sPost) + '</td>' +
                        '<td>' + esc(p.dJoiningDate) + '</td>' +
                        '<td>' + badgeForJoining(p.sJoiningStatus) + '</td>' +
                        '<td>' + fmtCurrency(p.dAmount) + '</td>' +
                        '<td>' + fmtCurrency(p.dRecAmount) + '</td>' +
                        '<td>' + esc(p.sWorkedBy) + '</td>' +
                        '<td>' + (p.sResumePath ?
                            '<a href="download-resume.php?id=' + p.iPlacementId + '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bx bx-file-blank"></i> Resume</a>' :
                            '<span class="text-muted small">-</span>') + '</td>' +
                        '<td>' + (p.sInvoiceNo ?
                            '<a href="generate-invoice.php?id=' + p.iPlacementId + '" target="_blank" class="btn btn-info btn-sm"><i class="bx bx-file"></i> Invoice</a>' :
                            '<span class="text-muted small">Not billed</span>') + '</td>' +
                        crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-placement.php?id=' + p.iPlacementId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteplacement(' + p.iPlacementId + ')' }
                        ]) +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows);
                $('#datatable').DataTable({ order: [[0, 'desc']] });
            } else {
                $('#datatable tbody').html('<tr><td colspan="12">No placements found</td></tr>');
            }
        }
    });
}

function deleteplacement(id) {
    if (!confirm('Are you sure you want to delete this placement?')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteplacement', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistplacement();
    });
}

var cachedPlacementCompanyOptions = null;
function withPlacementCompanyOptions(place) {
    if (cachedPlacementCompanyOptions) { place(cachedPlacementCompanyOptions); return; }
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'fngetlistcompany' }) })
        .then(r => r.json())
        .then(res => {
            var opts = (res.data || []).map(function (c) { return { value: c.iCompanyId, label: c.sCompanyName }; });
            opts.sort(function (a, b) { return String(a.label).localeCompare(String(b.label)); });
            cachedPlacementCompanyOptions = opts;
            place(opts);
        });
}

window.crmInlineEdit = {
    getRowId: function ($row) { return parseInt($row.data('id'), 10); },
    getFullRow: function (id) { return placementRowsById[id]; },
    fields: [
        { cellIndex: 1, key: 'sCandidateName', type: 'text' },
        { cellIndex: 2, key: 'iCompanyId', type: 'select', options: function (place) { withPlacementCompanyOptions(place); } },
        { cellIndex: 3, key: 'sPost', type: 'text' },
        { cellIndex: 4, key: 'dJoiningDate', type: 'date' },
        { cellIndex: 5, key: 'sJoiningStatus', type: 'select', options: [
            { value: 'Pending', label: 'Pending' },
            { value: 'Amount Received', label: 'Amount Received' },
            { value: 'Job Left', label: 'Job Left' },
            { value: 'Not Joined', label: 'Not Joined' }
        ] },
        { cellIndex: 7, key: 'dRecAmount', type: 'number' },
        { cellIndex: 8, key: 'sWorkedBy', type: 'text' }
    ],
    toPayload: function (merged, id) {
        return {
            id: id, reqId: merged.iReqId, type: merged.sType, candidateName: merged.sCandidateName, mobile: merged.sMobile,
            post: merged.sPost, companyId: merged.iCompanyId, salary: merged.dSalary, joiningDate: merged.dJoiningDate,
            joiningStatus: merged.sJoiningStatus, workedBy: merged.sWorkedBy, source: merged.sSource, remark: merged.sRemark,
            invoiceDate: merged.dInvoiceDate, invoiceNo: merged.sInvoiceNo, charges: merged.dCharges, cgst: merged.dCgst,
            sgst: merged.dSgst, recAmount: merged.dRecAmount, paymentRecDate: merged.dPaymentRecDate, paymentMode: merged.sPaymentMode,
            tds: merged.dTds, ipcInvDate: merged.dIpcInvDate, ipcInvNo: merged.sIpcInvNo, ipcInvAmt: merged.dIpcInvAmt,
            paymentDate: merged.dPaymentDate, paymentDetails: merged.sPaymentDetails, ref1: merged.sRef1, ref2: merged.sRef2
        };
    },
    saveAction: 'updateplacement',
    onSaved: function () { fngetlistplacement(); }
};

$(document).ready(function () { fngetlistplacement(); });
</script>
