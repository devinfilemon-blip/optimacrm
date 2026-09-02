<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php'; ?>
<head>
    <title>Change Password | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">Change Password</h4>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-body">
                                <div><span id="message"></span></div>
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newpass">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm">
                                </div>
                                <button type="button" class="btn btn-primary w-md" onclick="changePassword();">Update Password</button>
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
function changePassword() {
    var current = document.getElementById('current').value;
    var newpass = document.getElementById('newpass').value;
    var confirm = document.getElementById('confirm').value;
    var el = document.getElementById('message');

    if (!current || !newpass || !confirm) {
        el.innerHTML = 'Please fill all fields.'; el.className = 'error-message'; return;
    }

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'changePassword', current: current, newpass: newpass, confirm: confirm })
    })
    .then(r => r.json())
    .then(res => {
        el.innerHTML = res.message;
        el.className = res.status === 'success' ? 'add-message' : 'error-message';
        if (res.status === 'success') {
            document.getElementById('current').value = '';
            document.getElementById('newpass').value = '';
            document.getElementById('confirm').value = '';
        }
    });
}
</script>
