<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Placements | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">Placements</h4>
                            <div class="page-title-right d-flex align-items-center gap-2 flex-wrap" id="pageActions">
                                <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                                    <option value="">All Statuses</option>
                                    <option value="Offer Accepted">Offer Accepted</option>
                                    <option value="Joined">Joined</option>
                                    <option value="Invoice Sent">Invoice Sent</option>
                                    <option value="Amount Received">Amount Received</option>
                                    <option value="Job Left">Job Left</option>
                                    <option value="Not Joined">Not Joined</option>
                                </select>
                                <a href="list-placement.php?trashed=1" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash"></i> Trash</a>
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
                                <th>Mobile</th>
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
    else if (s === 'invoice sent') cls = 'optima-badge-hold';
    else if (s === 'joined') cls = 'optima-badge-searching';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Offer Accepted') + '</span>';
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

var CRM_TRASH_MODE = new URLSearchParams(window.location.search).get('trashed') === '1';
var placementRowsById = {};

if (CRM_TRASH_MODE) {
    document.getElementById('pageTitle').textContent = 'Placements — Trash';
    document.getElementById('pageActions').innerHTML = '<a href="list-placement.php" class="btn btn-primary btn-sm"><i class="bx bx-arrow-back"></i> Back to List</a>';
}

function fngetlistplacement() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: CRM_TRASH_MODE ? 'fngetlisttrashplacement' : 'fngetlistplacement' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                var data = response.data;
                if (!CRM_TRASH_MODE) {
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
                    var statusVal = document.getElementById('statusFilter') ? document.getElementById('statusFilter').value : '';
                    if (statusVal) {
                        data = data.filter(function (p) { return (p.sJoiningStatus || 'Offer Accepted') === statusVal; });
                    }
                }
                placementRowsById = {};
                data.forEach(function (p) {
                    placementRowsById[p.iPlacementId] = p;
                    var actions = CRM_TRASH_MODE
                        ? crmActionMenu([
                            { label: 'Restore', icon: 'bx-undo', onclick: 'restoreplacement(' + p.iPlacementId + ')' },
                            { label: 'Delete Forever', icon: 'bx-trash', danger: true, onclick: 'permanentlydeleteplacement(' + p.iPlacementId + ')' }
                          ])
                        : crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-placement.php?id=' + p.iPlacementId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteplacement(' + p.iPlacementId + ')' }
                          ]);
                    var candidateCell = p.iCandidateId
                        ? '<a href="add-candidate.php?id=' + p.iCandidateId + '" target="_blank">' + esc(p.sCandidateName) + '</a>'
                        : esc(p.sCandidateName || '-');
                    rows += '<tr data-id="' + p.iPlacementId + '">' +
                        '<td>' + esc(p.sSelectionNo) + '</td>' +
                        '<td>' + candidateCell + '</td>' +
                        '<td>' + esc(p.sMobile || '-') + '</td>' +
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
                        actions +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                // A lone colspan "no records" row confuses DataTables' column
                // auto-detection (it indexes cells off the first tbody row),
                // so only initialize the table when there's real data to show.
                $('#datatable tbody').html(rows || '<tr><td colspan="13">' + (CRM_TRASH_MODE ? 'Trash is empty' : 'No placements found') + '</td></tr>');
                if (rows) $('#datatable').DataTable({ order: [[0, 'desc']] });
            } else {
                $('#datatable tbody').html('<tr><td colspan="13">No placements found</td></tr>');
            }
        }
    });
}

function deleteplacement(id) {
    if (!confirm('Move this placement to trash? You can restore it later from the Trash view.')) return;
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

function restoreplacement(id) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'restoreplacement', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistplacement();
    });
}

function permanentlydeleteplacement(id) {
    if (!confirm('Permanently delete this placement? This cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'permanentlydeleteplacement', id: id })
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

// Trashed records aren't inline-editable — restore them first.
if (!CRM_TRASH_MODE) {
    window.crmInlineEdit = {
        getRowId: function ($row) { return parseInt($row.data('id'), 10); },
        getFullRow: function (id) { return placementRowsById[id]; },
        fields: [
            { cellIndex: 3, key: 'iCompanyId', type: 'select', options: function (place) { withPlacementCompanyOptions(place); } },
            { cellIndex: 4, key: 'sPost', type: 'text' },
            { cellIndex: 5, key: 'dJoiningDate', type: 'date' },
            { cellIndex: 6, key: 'sJoiningStatus', type: 'select', options: [
                { value: 'Offer Accepted', label: 'Offer Accepted' },
                { value: 'Joined', label: 'Joined' },
                { value: 'Invoice Sent', label: 'Invoice Sent' },
                { value: 'Amount Received', label: 'Amount Received' },
                { value: 'Job Left', label: 'Job Left' },
                { value: 'Not Joined', label: 'Not Joined' }
            ] },
            { cellIndex: 8, key: 'dRecAmount', type: 'number' },
            { cellIndex: 9, key: 'sWorkedBy', type: 'text' }
        ],
        // Candidate name/mobile aren't inline-editable here anymore — they
        // live on the Candidate record now (open it via the name link).
        toPayload: function (merged, id) {
            return {
                id: id, candidateId: merged.iCandidateId, reqId: merged.iReqId,
                post: merged.sPost, companyId: merged.iCompanyId, salary: merged.dSalary, joiningDate: merged.dJoiningDate,
                joiningStatus: merged.sJoiningStatus, workedBy: merged.sWorkedBy, remark: merged.sRemark,
                invoiceDate: merged.dInvoiceDate, invoiceNo: merged.sInvoiceNo, charges: merged.dCharges, cgst: merged.dCgst,
                sgst: merged.dSgst, recAmount: merged.dRecAmount, paymentRecDate: merged.dPaymentRecDate, paymentMode: merged.sPaymentMode,
                tds: merged.dTds, ipcInvDate: merged.dIpcInvDate, ipcInvNo: merged.sIpcInvNo, ipcInvAmt: merged.dIpcInvAmt,
                paymentDate: merged.dPaymentDate, paymentDetails: merged.sPaymentDetails
            };
        },
        saveAction: 'updateplacement',
        onSaved: function () { fngetlistplacement(); }
    };
}

$(document).ready(function () {
    fngetlistplacement();
    $('#statusFilter').on('change', fngetlistplacement);
});
</script>
