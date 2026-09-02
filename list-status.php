<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Requirement Status | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Requirement Status Master</h4>
                            <div class="page-title-right">
                                <a href="add-status.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Status</a>
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
var statusRowsById = {};
function fngetliststatus() {
    $.ajax({
        url: 'api.php', method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetliststatus' }),
        success: function (response) {
            var rows = '';
            statusRowsById = {};
            (response.data || []).forEach(function (s, i) {
                statusRowsById[s.iStatusId] = s;
                rows += '<tr data-id="' + s.iStatusId + '">' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + $('<div>').text(s.sStatus).html() + '</td>' +
                    crmActionMenu([
                        { label: 'Edit', icon: 'bx-edit-alt', href: 'add-status.php?id=' + s.iStatusId },
                        { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteStatus(' + s.iStatusId + ')' }
                    ]) +
                    '</tr>';
            });
            if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
            $('#datatable tbody').html(rows || '<tr><td colspan="3">No records</td></tr>');
            $('#datatable').DataTable();
        }
    });
}

window.crmInlineEdit = {
    getRowId: function ($row) { return parseInt($row.data('id'), 10); },
    getFullRow: function (id) { return statusRowsById[id]; },
    fields: [{ cellIndex: 1, key: 'sStatus', type: 'text' }],
    toPayload: function (merged, id) { return { id: id, status: merged.sStatus }; },
    saveAction: 'updatestatus',
    onSaved: function () { fngetliststatus(); }
};
function deleteStatus(id) {
    if (!confirm('Delete this status?')) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deletestatus', id: id }) })
        .then(r => r.json()).then(res => {
            document.getElementById('message').innerHTML = res.message;
            document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
            fngetliststatus();
        });
}
$(document).ready(function () { fngetliststatus(); });
</script>
