<?php include 'layouts/session.php';
$crmUserRole = $_SESSION['userRole'] ?? '';
$crmIsAdminPage = $crmUserRole === 'Admin';
$crmIsTeamLeaderPage = $crmUserRole === 'Team Leader';
if (!$crmIsAdminPage && !$crmIsTeamLeaderPage) { header('Location: index.php'); exit; }
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle"><?php echo $crmIsAdminPage ? 'Recruiters &amp; Users' : 'My Recruiters'; ?></h4>
                            <div class="page-title-right" id="pageActions">
                                <?php if ($crmIsAdminPage) : ?>
                                <a href="list-user.php?trashed=1" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash"></i> Trash</a>
                                <?php endif; ?>
                                <a href="add-user.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> <?php echo $crmIsAdminPage ? 'Add User' : 'Add Recruiter'; ?></a>
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
                                <?php if ($crmIsAdminPage) : ?><th>Reports To</th><?php endif; ?>
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
var CRM_IS_ADMIN_PAGE = <?php echo $crmIsAdminPage ? 'true' : 'false'; ?>;
var CRM_TRASH_MODE = CRM_IS_ADMIN_PAGE && new URLSearchParams(window.location.search).get('trashed') === '1';
var userRowsById = {};

if (CRM_TRASH_MODE) {
    document.getElementById('pageTitle').textContent = 'Recruiters & Users — Trash';
    document.getElementById('pageActions').innerHTML = '<a href="list-user.php" class="btn btn-primary btn-sm"><i class="bx bx-arrow-back"></i> Back to List</a>';
}

function fngetlistuser() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: CRM_TRASH_MODE ? 'fngetlisttrashuser' : 'fngetlistuser' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                userRowsById = {};
                response.data.forEach(function (u, i) {
                    userRowsById[u.iUserid] = u;
                    var statusBadge = parseInt(u.sIs_active) === 1
                        ? '<span class="optima-badge optima-badge-closed">Active</span>'
                        : '<span class="optima-badge optima-badge-default">Inactive</span>';
                    var actions = CRM_TRASH_MODE
                        ? crmActionMenu([
                            { label: 'Restore', icon: 'bx-undo', onclick: 'restoreuser(' + u.iUserid + ')' },
                            { label: 'Delete Forever', icon: 'bx-trash', danger: true, onclick: 'permanentlydeleteuser(' + u.iUserid + ')' }
                          ])
                        : crmActionMenu([
                            { label: 'View Details', icon: 'bx-detail', href: 'user-details.php?id=' + u.iUserid },
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-user.php?id=' + u.iUserid },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteuser(' + u.iUserid + ')' }
                          ]);
                    var nameCell = CRM_TRASH_MODE
                        ? $('<div>').text(u.sName).html()
                        : '<a href="user-details.php?id=' + u.iUserid + '">' + $('<div>').text(u.sName).html() + '</a>';
                    rows += '<tr data-id="' + u.iUserid + '">' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td>' + nameCell + '</td>' +
                        '<td>' + $('<div>').text(u.sPhone).html() + '</td>' +
                        '<td>' + $('<div>').text(u.sEmail || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(u.sRole).html() + '</td>' +
                        (CRM_IS_ADMIN_PAGE ? '<td>' + $('<div>').text(u.sManagerName || '-').html() + '</td>' : '') +
                        '<td>' + statusBadge + '</td>' +
                        actions +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                // A lone colspan "no records" row confuses DataTables' column
                // auto-detection (it indexes cells off the first tbody row),
                // so only initialize the table when there's real data to show.
                $('#datatable tbody').html(rows || '<tr><td colspan="8">' + (CRM_TRASH_MODE ? 'Trash is empty' : 'No users found') + '</td></tr>');
                if (rows) $('#datatable').DataTable();
            } else {
                $('#datatable tbody').html('<tr><td colspan="8">No users found</td></tr>');
            }
        }
    });
}

// The "Reports To" column only exists in the Admin view, so every cellIndex
// after Role shifts by one depending on who's looking at the page.
var crmUserColOffset = CRM_IS_ADMIN_PAGE ? 1 : 0;
var crmRoleField = CRM_IS_ADMIN_PAGE
    ? { cellIndex: 4, key: 'sRole', type: 'select', options: [{ value: 'Admin', label: 'Admin' }, { value: 'Team Leader', label: 'Team Leader' }, { value: 'Recruiter', label: 'Recruiter' }] }
    : null;

// Trashed records aren't inline-editable — restore them first.
if (!CRM_TRASH_MODE) {
    window.crmInlineEdit = {
        getRowId: function ($row) { return parseInt($row.data('id'), 10); },
        getFullRow: function (id) { return userRowsById[id]; },
        fields: [
            { cellIndex: 1, key: 'sName', type: 'text' },
            { cellIndex: 2, key: 'sPhone', type: 'text' },
            { cellIndex: 3, key: 'sEmail', type: 'text' }
        ].concat(crmRoleField ? [crmRoleField] : []).concat([
            { cellIndex: 5 + crmUserColOffset, key: 'sIs_active', type: 'select', options: [{ value: 1, label: 'Active' }, { value: 0, label: 'Inactive' }] }
        ]),
        toPayload: function (merged, id) {
            return { id: id, name: merged.sName, phone: merged.sPhone, email: merged.sEmail, role: merged.sRole, managerId: merged.iManagerId, isActive: merged.sIs_active };
        },
        saveAction: 'updateuser',
        onSaved: function () { fngetlistuser(); }
    };
}

function deleteuser(id) {
    if (!confirm('Move this user to trash? You can restore it later from the Trash view.')) return;
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

function restoreuser(id) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'restoreuser', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistuser();
    });
}

function permanentlydeleteuser(id) {
    if (!confirm('Permanently delete this user? This cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'permanentlydeleteuser', id: id })
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
