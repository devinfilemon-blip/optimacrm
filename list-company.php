<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Companies | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">Companies</h4>
                            <div class="page-title-right" id="pageActions">
                                <a href="list-company.php?trashed=1" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash"></i> Trash</a>
                                <a href="add-company.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Company</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Location</th>
                                <th id="reqCountHeader">Open Reqs</th>
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
var CRM_TRASH_MODE = new URLSearchParams(window.location.search).get('trashed') === '1';
var companyRowsById = {};

if (CRM_TRASH_MODE) {
    document.getElementById('pageTitle').textContent = 'Companies — Trash';
    document.getElementById('pageActions').innerHTML = '<a href="list-company.php" class="btn btn-primary btn-sm"><i class="bx bx-arrow-back"></i> Back to List</a>';
    document.getElementById('reqCountHeader').textContent = 'Deleted At';
}

function fngetlistcompany() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: CRM_TRASH_MODE ? 'fngetlisttrashcompany' : 'fngetlistcompany' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                companyRowsById = {};
                response.data.forEach(function (c, i) {
                    companyRowsById[c.iCompanyId] = c;
                    var statusBadge = c.sStatus === 'Active'
                        ? '<span class="optima-badge optima-badge-closed">Active</span>'
                        : '<span class="optima-badge optima-badge-default">Inactive</span>';
                    var actions = CRM_TRASH_MODE
                        ? crmActionMenu([
                            { label: 'Restore', icon: 'bx-undo', onclick: 'restorecompany(' + c.iCompanyId + ')' },
                            { label: 'Delete Forever', icon: 'bx-trash', danger: true, onclick: 'permanentlydeletecompany(' + c.iCompanyId + ')' }
                          ])
                        : crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-company.php?id=' + c.iCompanyId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deletecompany(' + c.iCompanyId + ')' }
                          ]);
                    rows += '<tr data-id="' + c.iCompanyId + '">' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + $('<div>').text(c.sCompanyName).html() + '</td>' +
                        '<td>' + $('<div>').text(c.sContactPerson || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(c.sPhone || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(c.sLocation || '-').html() + '</td>' +
                        '<td>' + (CRM_TRASH_MODE ? esc(c.dDeletedAt) : c.reqCount) + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        actions +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                // A lone colspan "no records" row confuses DataTables' column
                // auto-detection (it indexes cells off the first tbody row),
                // so only initialize the table when there's real data to show.
                $('#datatable tbody').html(rows || '<tr><td colspan="8">' + (CRM_TRASH_MODE ? 'Trash is empty' : 'No companies found') + '</td></tr>');
                if (rows) $('#datatable').DataTable();
            } else {
                $('#datatable tbody').html('<tr><td colspan="8">No companies found</td></tr>');
            }
        }
    });
}

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

function deletecompany(id) {
    if (!confirm('Move this company to trash? You can restore it later from the Trash view.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deletecompany', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcompany();
    });
}

function restorecompany(id) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'restorecompany', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcompany();
    });
}

function permanentlydeletecompany(id) {
    if (!confirm('Permanently delete this company? This cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'permanentlydeletecompany', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcompany();
    });
}

// Trashed records aren't inline-editable — restore them first.
if (!CRM_TRASH_MODE) {
    window.crmInlineEdit = {
        getRowId: function ($row) { return parseInt($row.data('id'), 10); },
        getFullRow: function (id) { return companyRowsById[id]; },
        fields: [
            { cellIndex: 1, key: 'sCompanyName', type: 'text' },
            { cellIndex: 2, key: 'sContactPerson', type: 'text' },
            { cellIndex: 3, key: 'sPhone', type: 'text' },
            { cellIndex: 4, key: 'sLocation', type: 'text' },
            { cellIndex: 6, key: 'sStatus', type: 'select', options: [{ value: 'Active', label: 'Active' }, { value: 'Inactive', label: 'Inactive' }] }
        ],
        toPayload: function (merged, id) {
            return {
                id: id, companyName: merged.sCompanyName, contactPerson: merged.sContactPerson, phone: merged.sPhone,
                email: merged.sEmail, industry: merged.sIndustry, location: merged.sLocation, address: merged.sAddress,
                gstin: merged.sGstin, status: merged.sStatus, notes: merged.sNotes
            };
        },
        saveAction: 'updatecompany',
        onSaved: function () { fngetlistcompany(); }
    };
}

$(document).ready(function () { fngetlistcompany(); });
</script>
