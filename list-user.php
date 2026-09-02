<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Recruiters &amp; Users | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Recruiters &amp; Users</h4>
                            <div class="page-title-right">
                                <a href="add-user.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add User</a>
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
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Role</th>
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
var userRowsById = {};
function fngetlistuser() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistuser' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                userRowsById = {};
                response.data.forEach(function (u, i) {
                    userRowsById[u.iUserid] = u;
                    var statusBadge = parseInt(u.sIs_active) === 1
                        ? '<span class="optima-badge optima-badge-closed">Active</span>'
                        : '<span class="optima-badge optima-badge-default">Inactive</span>';
                    rows += '<tr data-id="' + u.iUserid + '">' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + $('<div>').text(u.sName).html() + '</td>' +
                        '<td>' + $('<div>').text(u.sPhone).html() + '</td>' +
                        '<td>' + $('<div>').text(u.sEmail || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(u.sRole).html() + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-user.php?id=' + u.iUserid },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteuser(' + u.iUserid + ')' }
                        ]) +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows);
                $('#datatable').DataTable();
            } else {
                $('#datatable tbody').html('<tr><td colspan="7">No users found</td></tr>');
            }
        }
    });
}

window.crmInlineEdit = {
    getRowId: function ($row) { return parseInt($row.data('id'), 10); },
    getFullRow: function (id) { return userRowsById[id]; },
    fields: [
        { cellIndex: 1, key: 'sName', type: 'text' },
        { cellIndex: 2, key: 'sPhone', type: 'text' },
        { cellIndex: 3, key: 'sEmail', type: 'text' },
        { cellIndex: 4, key: 'sRole', type: 'select', options: [{ value: 'Admin', label: 'Admin' }, { value: 'Recruiter', label: 'Recruiter' }] },
        { cellIndex: 5, key: 'sIs_active', type: 'select', options: [{ value: 1, label: 'Active' }, { value: 0, label: 'Inactive' }] }
    ],
    toPayload: function (merged, id) {
        return { id: id, name: merged.sName, phone: merged.sPhone, email: merged.sEmail, role: merged.sRole, isActive: merged.sIs_active };
    },
    saveAction: 'updateuser',
    onSaved: function () { fngetlistuser(); }
};

function deleteuser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteuser', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistuser();
    });
}

$(document).ready(function () { fngetlistuser(); });
</script>
