<?php include 'layouts/session.php';
$crmUserRole = $_SESSION['userRole'] ?? '';
$crmIsAdminPage = $crmUserRole === 'Admin';
$crmIsTeamLeaderPage = $crmUserRole === 'Team Leader';
if (!$crmIsAdminPage && !$crmIsTeamLeaderPage) { header('Location: index.php'); exit; }
?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<head>
    <title><?php echo $id ? 'Edit' : 'Add'; ?> User | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18"><?php echo $id ? 'Edit' : 'Add'; ?> <?php echo $crmIsAdminPage ? 'User' : 'Recruiter'; ?></h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="list-user.php"><?php echo $crmIsAdminPage ? 'Users' : 'My Recruiters'; ?></a></li>
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
                                            <label class="form-label">Name *</label>
                                            <input type="text" class="form-control" id="name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone (login id) *</label>
                                            <input type="text" class="form-control" id="phone" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email">
                                        </div>
                                    </div>
                                    <?php if ($crmIsAdminPage) : ?>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-control" id="role">
                                                <option value="Recruiter">Recruiter</option>
                                                <option value="Team Leader">Team Leader</option>
                                                <option value="Admin">Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="managerField">
                                        <div class="mb-3">
                                            <label class="form-label">Reports To (Team Leader)</label>
                                            <select class="form-control" id="managerId"><option value="">-- None --</option></select>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $id ? 'New Password (leave blank to keep unchanged)' : 'Password *'; ?></label>
                                            <input type="password" class="form-control" id="password">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" id="isActive">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="saveUser();">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" id="documentsRow" style="display:none;">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-1">Documents</h5>
                                <p class="text-muted small mb-3">PDF, DOC, DOCX, JPG or PNG — up to 5 MB each. You can select multiple files at once.</p>

                                <div class="mb-3">
                                    <input type="file" class="form-control" id="documentFiles" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                                </div>
                                <div id="uploadProgress" class="text-muted small mb-3" style="display:none;"></div>

                                <div id="documentsList">
                                    <p class="text-muted small mb-0">No documents uploaded yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" id="documentsNewNote" style="display:none;">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-1">Documents</h5>
                                <p class="text-muted small mb-0">Save this <?php echo $crmIsAdminPage ? 'user' : 'recruiter'; ?> first — then you'll be able to upload documents here.</p>
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
var CRM_IS_ADMIN_PAGE = <?php echo $crmIsAdminPage ? 'true' : 'false'; ?>;

function showMessage(msg, ok) {
    var el = document.getElementById('message');
    el.innerHTML = msg;
    el.className = ok ? 'add-message' : 'error-message';
}

function toggleManagerField() {
    var roleEl = document.getElementById('role');
    var managerField = document.getElementById('managerField');
    if (!roleEl || !managerField) return;
    managerField.style.display = roleEl.value === 'Recruiter' ? '' : 'none';
}

function loadTeamLeaderDropdown(selected) {
    if (!CRM_IS_ADMIN_PAGE) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fngetteamleaderdropdown' })
    })
    .then(r => r.json())
    .then(res => {
        var sel = document.getElementById('managerId');
        sel.innerHTML = '<option value="">-- None --</option>';
        (res.data || []).forEach(function (tl) {
            var opt = document.createElement('option');
            opt.value = tl.iUserid;
            opt.textContent = tl.sName;
            if (selected && parseInt(selected) === parseInt(tl.iUserid)) opt.selected = true;
            sel.appendChild(opt);
        });
        crmRefreshSelect2(sel);
    });
}

function loadUser() {
    if (CRM_IS_ADMIN_PAGE) {
        loadTeamLeaderDropdown(null);
        // jQuery's .on(), not addEventListener: Select2 fires its change
        // notifications through jQuery's event system, which a native
        // addEventListener('change', ...) listener never sees.
        $('#role').on('change', toggleManagerField);
        toggleManagerField();
    }
    if (!editId) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getuserbyid', id: editId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') { showMessage(res.message, false); return; }
        var d = res.data;
        document.getElementById('name').value = d.sName || '';
        document.getElementById('phone').value = d.sPhone || '';
        document.getElementById('email').value = d.sEmail || '';
        document.getElementById('isActive').value = String(d.sIs_active);
        if (CRM_IS_ADMIN_PAGE) {
            document.getElementById('role').value = d.sRole || 'Recruiter';
            loadTeamLeaderDropdown(d.iManagerId);
            toggleManagerField();
        }
    });
}

function saveUser() {
    var name = document.getElementById('name').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var password = document.getElementById('password').value;
    if (!name || !phone) { alert('Please enter name and phone.'); return; }
    if (!editId && !password) { alert('Please enter a password.'); return; }

    var data = {
        action: editId ? 'updateuser' : 'adduser',
        id: editId,
        name: name,
        phone: phone,
        email: document.getElementById('email').value,
        isActive: document.getElementById('isActive').value,
        password: password
    };
    if (CRM_IS_ADMIN_PAGE) {
        data.role = document.getElementById('role').value;
        data.managerId = document.getElementById('managerId').value;
    }

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        if (res.status === 'success') {
            setTimeout(function () { window.location.href = 'list-user.php'; }, 500);
        }
    });
}

function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

function fmtFileSize(bytes) {
    bytes = parseInt(bytes, 10) || 0;
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function iconForFileType(ext) {
    ext = (ext || '').toLowerCase();
    if (ext === 'pdf') return 'bx-file-blank';
    if (ext === 'doc' || ext === 'docx') return 'bx-file-doc';
    if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') return 'bx-image';
    return 'bx-file';
}

function loadDocuments() {
    if (!editId) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fnlistuserdocuments', userId: editId })
    })
    .then(r => r.json())
    .then(res => {
        var wrap = document.getElementById('documentsList');
        var docs = (res.status === 'success') ? (res.data || []) : [];
        if (!docs.length) {
            wrap.innerHTML = '<p class="text-muted small mb-0">No documents uploaded yet.</p>';
            return;
        }
        wrap.innerHTML = '<ul class="list-group">' + docs.map(function (d) {
            return '<li class="list-group-item d-flex align-items-center justify-content-between flex-wrap gap-2">' +
                '<span><i class="bx ' + iconForFileType(d.sFileType) + ' me-2"></i>' + esc(d.sFileName) +
                '<span class="text-muted small ms-2">' + fmtFileSize(d.iFileSize) + ' &middot; ' + esc((d.dUploadedAt || '').substring(0, 10)) + '</span></span>' +
                '<span>' +
                '<a href="download-user-document.php?id=' + d.iDocId + '" target="_blank" class="btn btn-outline-primary btn-sm me-1"><i class="bx bx-download"></i> Download</a>' +
                '<a href="javascript:void(0);" onclick="removeDocument(' + d.iDocId + ');" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash"></i> Remove</a>' +
                '</span></li>';
        }).join('') + '</ul>';
    });
}

function uploadOneDocument(file) {
    var fd = new FormData();
    fd.append('action', 'uploaduserdocument');
    fd.append('userId', editId);
    fd.append('document', file);
    return fetch('api.php', { method: 'POST', body: fd }).then(r => r.json());
}

function uploadSelectedDocuments() {
    var input = document.getElementById('documentFiles');
    var files = Array.prototype.slice.call(input.files || []);
    if (!files.length) return;

    var progress = document.getElementById('uploadProgress');
    progress.style.display = '';
    var done = 0;
    var errors = [];

    function next() {
        if (done >= files.length) {
            progress.style.display = 'none';
            input.value = '';
            loadDocuments();
            if (errors.length) { showMessage(errors.join(' '), false); }
            return;
        }
        var file = files[done];
        progress.textContent = 'Uploading ' + (done + 1) + ' of ' + files.length + ' — ' + file.name + '…';
        uploadOneDocument(file).then(function (res) {
            if (res.status !== 'success') errors.push(file.name + ': ' + res.message);
            done++;
            next();
        }).catch(function () {
            errors.push(file.name + ': upload failed.');
            done++;
            next();
        });
    }
    next();
}

function removeDocument(docId) {
    if (!confirm('Remove this document? This cannot be undone.')) return;
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteuserdocument', id: docId })
    })
    .then(r => r.json())
    .then(res => {
        showMessage(res.message, res.status === 'success');
        loadDocuments();
    });
}

document.getElementById('documentFiles').addEventListener('change', uploadSelectedDocuments);

if (editId) {
    document.getElementById('documentsRow').style.display = '';
    loadDocuments();
} else {
    document.getElementById('documentsNewNote').style.display = '';
}

loadUser();
</script>
