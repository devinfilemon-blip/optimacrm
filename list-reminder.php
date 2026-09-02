<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Reminders | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Reminders</h4>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Add Reminder</h5>
                                <div><span id="message"></span></div>
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <input type="text" class="form-control" id="description" placeholder="e.g. Call HR at Marvelous Engineering">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="date">
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="addReminder();">Add Reminder</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8">
                        <div class="table-responsive">
                            <table id="datatable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Assigned By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
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

function fngetlistreminder() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistreminder' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                var today = new Date().toISOString().slice(0, 10);
                var data = response.data;
                if (new URLSearchParams(window.location.search).get('dueOnly') === '1') {
                    data = data.filter(function (r) { return r.sStatus === 'Pending' && r.sDate <= today; });
                }
                data.forEach(function (r) {
                    var overdue = r.sStatus === 'Pending' && r.sDate <= today;
                    var badge = r.sStatus === 'Done'
                        ? '<span class="optima-badge optima-badge-closed">Done</span>'
                        : (overdue ? '<span class="optima-badge optima-badge-refine">Overdue</span>' : '<span class="optima-badge optima-badge-searching">Pending</span>');
                    var actionItems = [];
                    if (r.sStatus !== 'Done') actionItems.push({ label: 'Mark Done', icon: 'bx-check', onclick: 'markDone(' + r.rrid + ')' });
                    actionItems.push({ label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleteReminder(' + r.rrid + ')' });
                    rows += '<tr>' +
                        '<td>' + esc(r.sDate) + '</td>' +
                        '<td>' + esc(r.sDescription) + '</td>' +
                        '<td>' + esc(r.sAssignedBy) + '</td>' +
                        '<td>' + badge + '</td>' +
                        crmActionMenu(actionItems) +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows);
                $('#datatable').DataTable({ order: [[0, 'asc']] });
            } else {
                $('#datatable tbody').html('<tr><td colspan="5">No reminders found</td></tr>');
            }
        }
    });
}

function addReminder() {
    var description = document.getElementById('description').value.trim();
    var date = document.getElementById('date').value;
    if (!description || !date) { alert('Please enter description and date.'); return; }

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'addreminder', description: description, date: date })
    })
    .then(r => r.json())
    .then(res => {
        var el = document.getElementById('message');
        el.innerHTML = res.message;
        el.className = res.status === 'success' ? 'add-message' : 'error-message';
        if (res.status === 'success') {
            document.getElementById('description').value = '';
            document.getElementById('date').value = '';
            fngetlistreminder();
        }
    });
}

function markDone(id) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'markreminderdone', id: id })
    }).then(r => r.json()).then(() => fngetlistreminder());
}

function deleteReminder(id) {
    if (!confirm('Delete this reminder?')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deletereminder', id: id })
    }).then(r => r.json()).then(() => fngetlistreminder());
}

$(document).ready(function () { fngetlistreminder(); });
</script>
