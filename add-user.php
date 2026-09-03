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
    });
}

function loadUser() {
    if (CRM_IS_ADMIN_PAGE) {
        loadTeamLeaderDropdown(null);
        document.getElementById('role').addEventListener('change', toggleManagerField);
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

loadUser();
</script>
