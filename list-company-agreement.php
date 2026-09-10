<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Company Agreements | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Company Agreements</h4>
                            <div class="page-title-right">
                                <a href="add-company-agreement.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Agreement</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="btn-group mb-3" role="group">
                            <button type="button" class="btn btn-outline-primary crm-agreement-tab" data-status="Completed" onclick="switchAgreementTab('Completed');">Completed <span class="badge bg-light text-dark" id="tabCountCompleted">0</span></button>
                            <button type="button" class="btn btn-outline-primary crm-agreement-tab" data-status="Pending" onclick="switchAgreementTab('Pending');">Pending <span class="badge bg-light text-dark" id="tabCountPending">0</span></button>
                            <button type="button" class="btn btn-outline-primary crm-agreement-tab active" data-status="" onclick="switchAgreementTab('');">All <span class="badge bg-light text-dark" id="tabCountAll">0</span></button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Company</th>
                                <th>% of CTC</th>
                                <th>Govt. Tax</th>
                                <th>Effective Date</th>
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

var allAgreements = [];
var activeTab = '';

function fmtDate(s) {
    if (!s) return '-';
    var parts = s.split('-');
    return parts.length === 3 ? (parts[2] + '-' + parts[1] + '-' + parts[0]) : s;
}

function switchAgreementTab(status) {
    activeTab = status;
    $('.crm-agreement-tab').removeClass('active');
    $('.crm-agreement-tab[data-status="' + status + '"]').addClass('active');
    renderAgreementTable();
}

function renderAgreementTable() {
    var rows = activeTab ? allAgreements.filter(function (a) { return a.sStatus === activeTab; }) : allAgreements;

    var html = '';
    rows.forEach(function (a, i) {
        var statusBadge = a.sStatus === 'Completed'
            ? '<span class="optima-badge optima-badge-closed">Completed</span>'
            : '<span class="optima-badge optima-badge-hold">Pending</span>';
        var toggleAction = a.sStatus === 'Completed'
            ? { label: 'Mark Pending', icon: 'bx-undo', onclick: 'toggleAgreementStatus(' + a.iAgreementId + ', "Pending")' }
            : { label: 'Mark Completed', icon: 'bx-check', onclick: 'toggleAgreementStatus(' + a.iAgreementId + ', "Completed")' };
        var actions = crmActionMenu([
            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-company-agreement.php?id=' + a.iAgreementId },
            { label: 'Print', icon: 'bx-printer', href: 'generate-company-agreement.php?id=' + a.iAgreementId, target: '_blank' },
            toggleAction,
            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteAgreement(' + a.iAgreementId + ')' }
        ]);
        html += '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + esc(a.sCompanyName) + '</td>' +
            '<td>' + (a.dCtcPercentage !== null && a.dCtcPercentage !== '' ? parseFloat(a.dCtcPercentage) + '%' : '-') + '</td>' +
            '<td>' + (a.dGovernmentTax !== null && a.dGovernmentTax !== '' ? parseFloat(a.dGovernmentTax) + '%' : '-') + '</td>' +
            '<td>' + fmtDate(a.dEffectiveDate) + '</td>' +
            '<td>' + statusBadge + '</td>' +
            actions +
            '</tr>';
    });

    if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
    $('#datatable tbody').html(html || '<tr><td colspan="7" class="text-center text-muted">No agreements in this view</td></tr>');
    if (html) $('#datatable').DataTable({ order: [] });
}

function fngetlistcompanyagreement() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistcompanyagreement' }),
        success: function (response) {
            allAgreements = response.status === 'success' ? (response.data || []) : [];
            var completed = allAgreements.filter(function (a) { return a.sStatus === 'Completed'; }).length;
            var pending = allAgreements.filter(function (a) { return a.sStatus === 'Pending'; }).length;
            $('#tabCountCompleted').text(completed);
            $('#tabCountPending').text(pending);
            $('#tabCountAll').text(allAgreements.length);
            renderAgreementTable();
        }
    });
}

function toggleAgreementStatus(id, newStatus) {
    var row = allAgreements.find(function (a) { return a.iAgreementId === id; });
    if (!row) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'updatecompanyagreement',
            id: id,
            companyId: row.iCompanyId,
            status: newStatus,
            ctcPercentage: row.dCtcPercentage,
            governmentTax: row.dGovernmentTax,
            effectiveDate: row.dEffectiveDate,
            replacementMonths: row.iReplacementMonths,
            paymentWithinDays: row.iPaymentWithinDays,
            billingWithinDays: row.iBillingWithinDays,
            remark: row.sRemark
        })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcompanyagreement();
    });
}

function deleteAgreement(id) {
    if (!confirm('Delete this company agreement? This cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deletecompanyagreement', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcompanyagreement();
    });
}

$(document).ready(function () { fngetlistcompanyagreement(); });
</script>
