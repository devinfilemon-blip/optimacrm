<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Candidate | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> Candidate</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-candidate.php">Candidates</a></li>
                                    <li class="breadcrumb-item active"><?php echo $id ? 'Edit' : 'Add'; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>
                                <input type="hidden" id="id" value="<?php echo $id; ?>">

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Candidate Name *</label>
                                            <input type="text" class="form-control" id="candidateName" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mobile No</label>
                                            <input type="text" class="form-control" id="mobile">
                                            <div id="mobileWarning" class="text-danger small mt-1"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Type</label>
                                            <select class="form-control" id="type">
                                                <option value="NT">Non-Technical (NT)</option>
                                                <option value="T">Technical (T)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Education</label>
                                            <select class="form-control" id="education"><option value="">-- Select Education --</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Experience</label>
                                            <input type="text" class="form-control" id="experience" placeholder="e.g. 2 years">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Current Company</label>
                                            <input type="text" class="form-control" id="currentCompany">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea class="form-control" id="address" rows="1"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Source</label>
                                            <select class="form-control" id="source"><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Resume</label>
                                            <input type="file" class="form-control" id="resumeFile" accept=".pdf,.doc,.docx">
                                            <div id="resumeCurrent" class="form-text"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Reference 1</label>
                                            <input type="text" class="form-control" id="ref1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Reference 2</label>
                                            <input type="text" class="form-control" id="ref2">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <textarea class="form-control" id="remark" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-primary w-md" onclick="saveCandidate();">Save</button>
                                <a href="list-candidate.php" class="btn btn-secondary w-md">Cancel</a>
                            </div>
                        </div>

                        <div class="card" id="placementsCard" style="display:none;">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0">Placement History</h5>
                                    <a href="#" id="addPlacementLink" class="btn btn-outline-primary btn-sm"><i class="bx bx-plus"></i> Add Placement</a>
                                </div>
                                <div class="table-responsive" id="placementsTableWrap" style="display:none;">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead><tr><th>Post</th><th>Company</th><th>Joining Date</th><th>Status</th><th></th></tr></thead>
                                        <tbody id="placementsBody"></tbody>
                                    </table>
                                </div>
                                <p class="text-muted small mb-0" id="noPlacementsNote">No placements yet for this candidate.</p>
                            </div>
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
var editId = parseInt(document.getElementById('id').value) || 0;

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}
function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

function loadEducationDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlisteducation' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('education');
        sel.innerHTML = '<option value="">-- Select Education --</option>';
        (res.data || []).forEach(function (e) {
            var opt = document.createElement('option');
            opt.value = e.sEducation;
            opt.textContent = e.sEducation;
            if (selected && selected === e.sEducation) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function loadSourceDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlistsource' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('source');
        sel.innerHTML = '<option value="">-- Select Source --</option>';
        (res.data || []).forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.sSource;
            opt.textContent = s.sSource;
            if (selected && selected === s.sSource) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function checkMobileDuplicate(cb) {
    var mobile = document.getElementById('mobile').value.trim();
    var warnEl = document.getElementById('mobileWarning');
    if (!mobile) { warnEl.textContent = ''; cb(false); return; }
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'checkcandidatemobile', mobile: mobile, excludeId: editId })
    })
    .then(r => r.json())
    .then(res => {
        var dup = !!(res.data && res.data.exists);
        warnEl.textContent = dup ? 'A candidate with this mobile number already exists.' : '';
        cb(dup);
    })
    .catch(function () { cb(false); });
}
document.getElementById('mobile').addEventListener('blur', function () { checkMobileDuplicate(function () {}); });

function renderPlacements(list) {
    document.getElementById('placementsCard').style.display = '';
    document.getElementById('addPlacementLink').href = 'add-placement.php?candidateId=' + editId;

    var tableWrap = document.getElementById('placementsTableWrap');
    var note = document.getElementById('noPlacementsNote');
    if (!list || !list.length) { tableWrap.style.display = 'none'; note.style.display = ''; return; }
    tableWrap.style.display = '';
    note.style.display = 'none';

    var body = document.getElementById('placementsBody');
    body.innerHTML = '';
    list.forEach(function (p) {
        body.innerHTML += '<tr>' +
            '<td>' + esc(p.sPost) + '</td>' +
            '<td>' + esc(p.sCompanyName || '-') + '</td>' +
            '<td>' + esc(p.dJoiningDate || '-') + '</td>' +
            '<td>' + esc(p.sJoiningStatus) + '</td>' +
            '<td><a href="add-placement.php?id=' + p.iPlacementId + '" class="btn btn-outline-primary btn-sm">Open</a></td>' +
            '</tr>';
    });
}

function loadCandidate() {
    loadEducationDropdown(null);
    loadSourceDropdown(null);
    if (!editId) return;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getcandidatebyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        loadEducationDropdown(d.sEducation);
        loadSourceDropdown(d.sSource);

        document.getElementById('candidateName').value = d.sCandidateName || '';
        document.getElementById('mobile').value = d.sMobile || '';
        document.getElementById('type').value = d.sType || 'NT';
        crmRefreshSelect2(document.getElementById('type'));
        document.getElementById('experience').value = d.sExperience || '';
        document.getElementById('currentCompany').value = d.sCurrentCompany || '';
        document.getElementById('address').value = d.sAddress || '';
        document.getElementById('ref1').value = d.sRef1 || '';
        document.getElementById('ref2').value = d.sRef2 || '';
        document.getElementById('remark').value = d.sRemark || '';

        renderResumeCurrent(d.sResumePath);
        renderPlacements(d.placements);
    });
}

function saveCandidate() {
    var candidateName = document.getElementById('candidateName').value.trim();
    if (!candidateName) { alert('Please enter the candidate name.'); return; }

    checkMobileDuplicate(function (isDuplicate) {
        if (isDuplicate) { showMessage('A candidate with this mobile number already exists.', false); return; }
        doSaveCandidate(candidateName);
    });
}

function doSaveCandidate(candidateName) {
    var data = {
        action: editId ? 'updatecandidate' : 'addcandidate',
        id: editId,
        candidateName: candidateName,
        mobile: document.getElementById('mobile').value,
        type: document.getElementById('type').value,
        education: document.getElementById('education').value,
        experience: document.getElementById('experience').value,
        currentCompany: document.getElementById('currentCompany').value,
        address: document.getElementById('address').value,
        source: document.getElementById('source').value,
        ref1: document.getElementById('ref1').value,
        ref2: document.getElementById('ref2').value,
        remark: document.getElementById('remark').value
    };

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        if (res.status !== 'success') return;
        var savedId = editId || (res.data && res.data.id);
        var pendingResume = document.getElementById('resumeFile').files[0];
        if (savedId && pendingResume) {
            uploadResume(savedId, pendingResume, function () {
                setTimeout(function () { window.location.href = 'list-candidate.php'; }, 500);
            });
        } else {
            setTimeout(function () { window.location.href = 'list-candidate.php'; }, 500);
        }
    });
}

function renderResumeCurrent(path) {
    var el = document.getElementById('resumeCurrent');
    if (!path) { el.innerHTML = 'No resume uploaded yet.'; return; }
    el.innerHTML = '<a href="download-resume.php?id=' + editId + '" target="_blank">View current resume</a> &middot; ' +
        '<a href="javascript:void(0);" onclick="removeResume();" class="text-danger">Remove</a>';
}

function uploadResume(id, file, cb) {
    var fd = new FormData();
    fd.append('action', 'uploadresume');
    fd.append('id', id);
    fd.append('resume', file);
    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') { showMessage(res.message, false); }
            if (cb) cb();
        })
        .catch(function () { if (cb) cb(); });
}

function removeResume() {
    if (!editId) return;
    if (!confirm('Remove the uploaded resume?')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteresume', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        if (res.status === 'success') { renderResumeCurrent(null); document.getElementById('resumeFile').value = ''; }
    });
}

loadCandidate();
</script>
