<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Requirement | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> Requirement</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-requirement.php">Requirements</a></li>
                                    <li class="breadcrumb-item active"><?php echo $id ? 'Edit' : 'Add'; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>
                                <input type="hidden" id="id" value="<?php echo $id; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company *</label>
                                            <select class="form-control" id="companyId" required><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Post / Designation *</label>
                                            <div class="d-flex gap-2">
                                                <select class="form-control" id="post" required><option value="">Loading&hellip;</option></select>
                                                <button type="button" class="btn btn-outline-primary flex-shrink-0" onclick="openAddPostModal();" title="Add new post"><i class="bx bx-plus"></i> Add Post</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">No. of Vacancies</label>
                                            <input type="number" min="1" step="1" class="form-control" id="noOfVacancy" value="1">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Type</label>
                                            <select class="form-control" id="type">
                                                <option value="NT">Non-Technical (NT)</option>
                                                <option value="T">Technical (T)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <input type="text" class="form-control" id="location">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Education</label>
                                            <input type="text" class="form-control" id="education">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Experience</label>
                                            <input type="text" class="form-control" id="experience">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Salary</label>
                                            <input type="text" class="form-control" id="salary" placeholder="e.g. 20-25k">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Open Date</label>
                                            <input type="date" class="form-control" id="openDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Next Follow-up Date</label>
                                            <input type="date" class="form-control" id="followupDate">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Rank</label>
                                            <select class="form-control" id="rank">
                                                <option value="">-</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" id="reqStatusSelect"><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Follow-up By</label>
                                            <input type="text" class="form-control" id="followupBy" placeholder="Recruiter initials/name">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Recruiter</label>
                                            <select class="form-control" id="recruiter"><option value="">Loading&hellip;</option></select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <textarea class="form-control" id="remark" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="saveRequirement();">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="addPostModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Post / Designation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div><span id="addPostMessage"></span></div>
                                <div class="mb-0">
                                    <label class="form-label">Post / Designation name</label>
                                    <input type="text" class="form-control" id="newPostName" placeholder="e.g. Quality Engineer">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveNewPost();">Save</button>
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

function loadCompanyDropdown(selected, cb) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetcompanydropdown' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('companyId');
        sel.innerHTML = '<option value="">-- Select Company --</option>';
        (res.data || []).forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.iCompanyId;
            opt.textContent = c.sCompanyName;
            if (selected && parseInt(selected) === parseInt(c.iCompanyId)) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
        if (cb) cb();
    });
}

function loadStatusDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetliststatus' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('reqStatusSelect');
        sel.innerHTML = '';
        (res.data || []).forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.sStatus;
            opt.textContent = s.sStatus;
            if (selected && selected === s.sStatus) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function loadRecruiterDropdown(selected) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlistrecruiter' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('recruiter');
        sel.innerHTML = '<option value="">-- Select Recruiter --</option>';
        (res.data || []).forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.sRecruiter;
            opt.textContent = s.sRecruiter;
            if (selected && selected === s.sRecruiter) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function loadPostDropdown(selected) {
    return fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetlistpost' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('post');
        sel.innerHTML = '<option value="">-- Select Post / Designation --</option>';
        var found = false;
        (res.data || []).forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.sPost;
            opt.textContent = s.sPost;
            if (selected && selected === s.sPost) { opt.selected = true; found = true; }
            sel.appendChild(opt);
        });
        if (selected && !found) {
            var opt = document.createElement('option');
            opt.value = selected;
            opt.textContent = selected;
            opt.selected = true;
            sel.appendChild(opt);
        }
        crmRefreshSelect2(sel);
    });
}

function openAddPostModal() {
    document.getElementById('newPostName').value = '';
    document.getElementById('addPostMessage').innerHTML = '';
    new bootstrap.Modal(document.getElementById('addPostModal')).show();
}

function saveNewPost() {
    var name = document.getElementById('newPostName').value.trim();
    if (!name) { alert('Enter a post / designation name.'); return; }
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'addpost', post: name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') {
            var el = document.getElementById('addPostMessage');
            el.innerHTML = res.message; el.className = 'error-message';
            return;
        }
        loadPostDropdown(name).then(function () {
            var modalEl = document.getElementById('addPostModal');
            bootstrap.Modal.getInstance(modalEl).hide();
        });
    });
}

function loadRequirement() {
    loadCompanyDropdown(null, function () {});
    loadStatusDropdown(null);
    loadRecruiterDropdown(null);
    loadPostDropdown(null);

    if (!editId) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getrequirementbyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        loadCompanyDropdown(d.iCompanyId);
        loadStatusDropdown(d.sStatus);
        loadRecruiterDropdown(d.sRecruiter);
        loadPostDropdown(d.sPost);
        document.getElementById('noOfVacancy').value = d.iNoOfVacancy || 1;
        document.getElementById('type').value = d.sType || 'NT';
        document.getElementById('location').value = d.sLocation || '';
        document.getElementById('education').value = d.sEducation || '';
        document.getElementById('experience').value = d.sExperience || '';
        document.getElementById('salary').value = d.sSalary || '';
        document.getElementById('openDate').value = d.dOpenDate || '';
        document.getElementById('followupDate').value = d.dFollowupDate || '';
        document.getElementById('rank').value = d.sRank || '';
        document.getElementById('followupBy').value = d.sFollowupBy || '';
        document.getElementById('remark').value = d.sRemark || '';
    });
}

function saveRequirement() {
    var companyId = document.getElementById('companyId').value;
    var post = document.getElementById('post').value.trim();
    if (!companyId) { alert('Please select a company.'); return; }
    if (!post) { alert('Please enter the post / designation.'); return; }

    var data = {
        action: editId ? 'updaterequirement' : 'addrequirement',
        id: editId,
        companyId: companyId,
        post: post,
        noOfVacancy: document.getElementById('noOfVacancy').value,
        type: document.getElementById('type').value,
        location: document.getElementById('location').value,
        education: document.getElementById('education').value,
        experience: document.getElementById('experience').value,
        salary: document.getElementById('salary').value,
        openDate: document.getElementById('openDate').value,
        followupDate: document.getElementById('followupDate').value,
        rank: document.getElementById('rank').value,
        status: document.getElementById('reqStatusSelect').value,
        followupBy: document.getElementById('followupBy').value,
        recruiter: document.getElementById('recruiter').value,
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
        if (res.status === 'success') {
            setTimeout(function () { window.location.href = 'list-requirement.php'; }, 500);
        }
    });
}

loadRequirement();
</script>
