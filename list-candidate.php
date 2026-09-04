<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Candidates | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18" id="pageTitle">Candidates</h4>
                            <div class="page-title-right d-flex align-items-center gap-2 flex-wrap" id="pageActions">
                                <a href="list-candidate.php?trashed=1" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash"></i> Trash</a>
                                <a href="bulk-upload-candidates.php" class="btn btn-outline-primary btn-sm"><i class="bx bx-upload"></i> Bulk Upload</a>
                                <a href="add-candidate.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add Candidate</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div><span id="message"></span></div>
                    <table id="datatable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Type</th>
                                <th>Education</th>
                                <th>Experience</th>
                                <th>Current Company</th>
                                <th>Source</th>
                                <th>Placements</th>
                                <th>Resume</th>
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

var CRM_TRASH_MODE = new URLSearchParams(window.location.search).get('trashed') === '1';
var candidateRowsById = {};

if (CRM_TRASH_MODE) {
    document.getElementById('pageTitle').textContent = 'Candidates — Trash';
    document.getElementById('pageActions').innerHTML = '<a href="list-candidate.php" class="btn btn-primary btn-sm"><i class="bx bx-arrow-back"></i> Back to List</a>';
}

function fngetlistcandidate() {
    $.ajax({
        url: 'api.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: CRM_TRASH_MODE ? 'fngetlisttrashcandidate' : 'fngetlistcandidate' }),
        success: function (response) {
            if (response.status === 'success') {
                var rows = '';
                candidateRowsById = {};
                (response.data || []).forEach(function (c) {
                    candidateRowsById[c.iCandidateId] = c;
                    var actions = CRM_TRASH_MODE
                        ? crmActionMenu([
                            { label: 'Restore', icon: 'bx-undo', onclick: 'restorecandidate(' + c.iCandidateId + ')' },
                            { label: 'Delete Forever', icon: 'bx-trash', danger: true, onclick: 'permanentlydeletecandidate(' + c.iCandidateId + ')' }
                          ])
                        : crmActionMenu([
                            { label: 'Edit', icon: 'bx-edit-alt', href: 'add-candidate.php?id=' + c.iCandidateId },
                            { label: 'Add Placement', icon: 'bx-plus', href: 'add-placement.php?candidateId=' + c.iCandidateId },
                            { label: 'Delete', icon: 'bx-trash', danger: true, onclick: 'deletecandidate(' + c.iCandidateId + ')' }
                          ]);
                    rows += '<tr data-id="' + c.iCandidateId + '">' +
                        '<td>' + esc(c.sCandidateName) + '</td>' +
                        '<td>' + esc(c.sMobile || '-') + '</td>' +
                        '<td>' + esc(c.sType) + '</td>' +
                        '<td>' + esc(c.sEducation || '-') + '</td>' +
                        '<td>' + esc(c.sExperience || '-') + '</td>' +
                        '<td>' + esc(c.sCurrentCompany || '-') + '</td>' +
                        '<td>' + esc(c.sSource || '-') + '</td>' +
                        '<td>' + (CRM_TRASH_MODE ? '-' : (parseInt(c.placementCount, 10) || 0)) + '</td>' +
                        '<td>' + (c.sResumePath ?
                            '<a href="download-resume.php?id=' + c.iCandidateId + '" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bx bx-file-blank"></i> Resume</a>' :
                            '<span class="text-muted small">-</span>') + '</td>' +
                        actions +
                        '</tr>';
                });
                if ($.fn.DataTable.isDataTable('#datatable')) $('#datatable').DataTable().destroy();
                $('#datatable tbody').html(rows || '<tr><td colspan="10">' + (CRM_TRASH_MODE ? 'Trash is empty' : 'No candidates found') + '</td></tr>');
                if (rows) $('#datatable').DataTable({ order: [[0, 'asc']] });
            } else {
                $('#datatable tbody').html('<tr><td colspan="10">No candidates found</td></tr>');
            }
        }
    });
}

function deletecandidate(id) {
    if (!confirm('Move this candidate to trash? You can restore it later from the Trash view.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deletecandidate', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcandidate();
    });
}

function restorecandidate(id) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'restorecandidate', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcandidate();
    });
}

function permanentlydeletecandidate(id) {
    if (!confirm('Permanently delete this candidate? This also removes any uploaded resume file and cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'permanentlydeletecandidate', id: id })
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('message').innerHTML = res.message;
        document.getElementById('message').className = res.status === 'success' ? 'add-message' : 'error-message';
        fngetlistcandidate();
    });
}

// Trashed records aren't inline-editable — restore them first.
if (!CRM_TRASH_MODE) {
    window.crmInlineEdit = {
        getRowId: function ($row) { return parseInt($row.data('id'), 10); },
        getFullRow: function (id) { return candidateRowsById[id]; },
        fields: [
            { cellIndex: 0, key: 'sCandidateName', type: 'text' },
            { cellIndex: 1, key: 'sMobile', type: 'text' },
            { cellIndex: 2, key: 'sType', type: 'select', options: [
                { value: 'NT', label: 'NT' },
                { value: 'T', label: 'T' }
            ] },
            { cellIndex: 4, key: 'sExperience', type: 'text' },
            { cellIndex: 5, key: 'sCurrentCompany', type: 'text' }
        ],
        toPayload: function (merged, id) {
            return {
                id: id, candidateName: merged.sCandidateName, mobile: merged.sMobile, type: merged.sType,
                education: merged.sEducation, experience: merged.sExperience, currentCompany: merged.sCurrentCompany,
                address: merged.sAddress, source: merged.sSource, ref1: merged.sRef1, ref2: merged.sRef2, remark: merged.sRemark
            };
        },
        saveAction: 'updatecandidate',
        onSaved: function () { fngetlistcandidate(); }
    };
}

$(document).ready(function () { fngetlistcandidate(); });
</script>
