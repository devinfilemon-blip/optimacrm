<?php include 'layouts/session.php';
if (($_SESSION['userRole'] ?? '') !== 'Admin') { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Post / Designation Master | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Post / Designation Master</h4>
                            <div class="page-title-right">
                                <a href="add-post.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Post</a>
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
                                <th>Post / Designation</th>
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
var postRowsById = {};
function fngetlistpost() {
    $.ajax({
        url: 'api.php', method: 'POST', contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistpost' }),
        success: function (response) {
            var rows = '';
            postRowsById = {};
            (response.data || []).forEach(function (s, i) {
                postRowsById[s.iPostId] = s;
                rows += '<tr data-id="' + s.iPostId + '">' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + $('<div>').text(s.sPost).html() + '</td>' +
                    crmActionMenu([
                        { label: 'Edit', icon: 'bx-edit-alt', href: 'add-post.php?id=' + s.iPostId },
                        { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deletePost(' + s.iPostId + ')' }
                    ]) +
                    '</tr>';
            });
            if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
            $('#datatable tbody').html(rows || '<tr><td colspan="3">No records</td></tr>');
            $('#datatable').DataTable();
        }
    });
}
function deletePost(id) {
    if (!confirm('Delete this post / designation?')) return;
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'deletepost', id: id }) })
        .then(r => r.json()).then(res => {
            document.getElementById('message').innerHTML = res.message;
            document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
            fngetlistpost();
        });
}

window.crmInlineEdit = {
    getRowId: function ($row) { return parseInt($row.data('id'), 10); },
    getFullRow: function (id) { return postRowsById[id]; },
    fields: [{ cellIndex: 1, key: 'sPost', type: 'text' }],
    toPayload: function (merged, id) { return { id: id, post: merged.sPost }; },
    saveAction: 'updatepost',
    onSaved: function () { fngetlistpost(); }
};

$(document).ready(function () { fngetlistpost(); });
</script>
