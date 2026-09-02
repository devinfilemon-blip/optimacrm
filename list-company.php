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
                            <h4 class="mb-sm-0 font-size-18">Companies</h4>
                            <div class="page-title-right">
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
                                <th>Open Reqs</th>
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
var companyRowsById = {};
function fngetlistcompany() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistcompany' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                companyRowsById = {};
                response.data.forEach(function (c, i) {
                    companyRowsById[c.iCompanyId] = c;
                    var statusBadge = c.sStatus === 'Active'
                        ? '<span class="optima-badge optima-badge-closed">Active</span>'
                        : '<span class="optima-badge optima-badge-default">Inactive</span>';
                    rows += '<tr data-id="' + c.iCompanyId + '">' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + $('<div>').text(c.sCompanyName).html() + '</td>' +
                        '<td>' + $('<div>').text(c.sContactPerson || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(c.sPhone || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(c.sLocation || '-').html() + '</td>' +
                        '<td>' + c.reqCount + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-company.php?id=' + c.iCompanyId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deletecompany(' + c.iCompanyId + ')' }
                        ]) +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows);
                $('#datatable').DataTable();
            } else {
                $('#datatable tbody').html('<tr><td colspan="8">No companies found</td></tr>');
            }
        }
    });
}

function deletecompany(id) {
    if (!confirm('Are you sure you want to delete this company?')) return;
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

$(document).ready(function () { fngetlistcompany(); });
</script>
