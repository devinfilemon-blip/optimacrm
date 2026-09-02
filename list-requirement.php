<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Job Requirements | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Job Requirements</h4>
                            <div class="page-title-right">
                                <a href="add-requirement.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Requirement</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Req No</th>
                                <th>Open Date</th>
                                <th>Company</th>
                                <th>Post</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Salary</th>
                                <th>Status</th>
                                <th>Recruiter</th>
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

function badgeForStatus(status) {
    var cls = 'optima-badge-default';
    var s = (status || '').toLowerCase();
    if (s.indexOf('search') !== -1) cls = 'optima-badge-searching';
    else if (s.indexOf('closed') !== -1 || s === 'joined') cls = 'optima-badge-closed';
    else if (s.indexOf('hold') !== -1) cls = 'optima-badge-hold';
    else if (s.indexOf('refine') !== -1 || s.indexOf('not') !== -1) cls = 'optima-badge-refine';
    return '<span class="optima-badge ' + cls + '">' + esc(status || 'Unknown') + '</span>';
}

// A requirement is resolved (not "pending") once it's filled, closed, or the
// company backed out — everything else can still go overdue.
function isPendingReqStatus(status) {
    return ['Closed by Co.', 'Joined', 'Not Joining'].indexOf(status) === -1;
}
function daysSince(dateStr) {
    if (!dateStr) return null;
    var opened = new Date(dateStr + 'T00:00:00');
    if (isNaN(opened.getTime())) return null;
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    return Math.round((today - opened) / 86400000);
}
// 0 = on track, 1 = crossed 15 days (yellow), 2 = crossed a month (orange).
function overdueTier(status, days) {
    if (!isPendingReqStatus(status) || days === null) return 0;
    if (days > 30) return 2;
    if (days > 15) return 1;
    return 0;
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

var requirementRowsById = {};
function fngetlistrequirement() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'fngetlistrequirement' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                var data = response.data;
                var params = new URLSearchParams(window.location.search);
                if (params.get('openOnly') === '1') {
                    data = data.filter(function (r) { return ['Closed by Co.', 'Not Joining'].indexOf(r.sStatus) === -1; });
                }
                var period = params.get('period');
                if (period) {
                    var range = getPeriodRange(period);
                    data = data.filter(function (r) { return r.dOpenDate && r.dOpenDate >= range.start && r.dOpenDate <= range.end; });
                }
                requirementRowsById = {};
                data.forEach(function (r) {
                    requirementRowsById[r.iReqId] = r;
                    var pendingDays = daysSince(r.dOpenDate);
                    var tier = overdueTier(r.sStatus, pendingDays);
                    var rowClass = tier === 2 ? ' class="optima-row-critical"' : (tier === 1 ? ' class="optima-row-warning"' : '');
                    var overdueBadge = tier
                        ? ' <span class="optima-badge ' + (tier === 2 ? 'optima-badge-critical' : 'optima-badge-warning') + '" title="Open ' + pendingDays + ' days">' + pendingDays + 'd</span>'
                        : '';
                    rows += '<tr data-id="' + r.iReqId + '"' + rowClass + '>' +
                        '<td>' + esc(r.sReqNo) + '</td>' +
                        '<td>' + esc(r.dOpenDate) + '</td>' +
                        '<td>' + esc(r.sCompanyName || '-') + '</td>' +
                        '<td>' + esc(r.sPost) + '</td>' +
                        '<td>' + esc(r.sType) + '</td>' +
                        '<td>' + esc(r.sLocation) + '</td>' +
                        '<td>' + esc(r.sSalary) + '</td>' +
                        '<td>' + badgeForStatus(r.sStatus) + overdueBadge + '</td>' +
                        '<td>' + esc(r.sRecruiter) + '</td>' +
                        crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-requirement.php?id=' + r.iReqId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deleterequirement(' + r.iReqId + ')' }
                        ]) +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows);
                var dt = $('#datatable').DataTable({ order: [[0, 'desc']] });
                var q = new URLSearchParams(window.location.search).get('q');
                if (q) dt.search(q).draw();
            } else {
                $('#datatable tbody').html('<tr><td colspan="10">No requirements found</td></tr>');
            }
        }
    });
}

function deleterequirement(id) {
    if (!confirm('Are you sure you want to delete this requirement?')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleterequirement', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistrequirement();
    });
}

var cachedCompanyOptions = null, cachedStatusOptions = null, cachedRecruiterOptions = null;
function withCachedOptions(cacheKey, action, valueKey, labelKey, place) {
    var cached = { company: cachedCompanyOptions, status: cachedStatusOptions, recruiter: cachedRecruiterOptions }[cacheKey];
    if (cached) { place(cached); return; }
    fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: action }) })
        .then(r => r.json())
        .then(res => {
            var opts = (res.data || []).map(function (o) { return { value: o[valueKey], label: o[labelKey] }; });
            opts.sort(function (a, b) { return String(a.label).localeCompare(String(b.label)); });
            if (cacheKey === 'company') cachedCompanyOptions = opts;
            else if (cacheKey === 'status') cachedStatusOptions = opts;
            else cachedRecruiterOptions = opts;
            place(opts);
        });
}

window.crmInlineEdit = {
    getRowId: function ($row) { return parseInt($row.data('id'), 10); },
    getFullRow: function (id) { return requirementRowsById[id]; },
    fields: [
        { cellIndex: 1, key: 'dOpenDate', type: 'date' },
        { cellIndex: 2, key: 'iCompanyId', type: 'select', options: function (place) { withCachedOptions('company', 'fngetlistcompany', 'iCompanyId', 'sCompanyName', place); } },
        { cellIndex: 3, key: 'sPost', type: 'text' },
        { cellIndex: 4, key: 'sType', type: 'select', options: [{ value: 'NT', label: 'NT' }, { value: 'T', label: 'T' }] },
        { cellIndex: 5, key: 'sLocation', type: 'text' },
        { cellIndex: 6, key: 'sSalary', type: 'text' },
        { cellIndex: 7, key: 'sStatus', type: 'select', options: function (place) { withCachedOptions('status', 'fngetliststatus', 'sStatus', 'sStatus', place); } },
        { cellIndex: 8, key: 'sRecruiter', type: 'select', options: function (place) { withCachedOptions('recruiter', 'fngetlistrecruiter', 'sRecruiter', 'sRecruiter', place); } }
    ],
    toPayload: function (merged, id) {
        return {
            id: id, companyId: merged.iCompanyId, post: merged.sPost, noOfVacancy: merged.iNoOfVacancy, type: merged.sType,
            location: merged.sLocation, education: merged.sEducation, experience: merged.sExperience, salary: merged.sSalary,
            openDate: merged.dOpenDate, followupDate: merged.dFollowupDate, rank: merged.sRank, status: merged.sStatus,
            followupBy: merged.sFollowupBy, recruiter: merged.sRecruiter, remark: merged.sRemark
        };
    },
    saveAction: 'updaterequirement',
    onSaved: function () { fngetlistrequirement(); }
};

$(document).ready(function () { fngetlistrequirement(); });
</script>
