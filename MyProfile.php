<?php include 'layouts/session.php'; ?>
<?php include 'layouts/head-main.php'; ?>
<?php include 'layouts/config.php';
$stmt = mysqli_prepare($link, "SELECT sName, sEmail, sPhone, sRole FROM tbluser WHERE iUserid = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$me = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
?>
<head>
    <title>My Profile | <?php echo APP_NAME; ?></title>
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
                            <h4 class="mb-sm-0 font-size-18">My Profile</h4>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Account Details</h5>
                                <table class="table table-borderless mb-0">
                                    <tr><th style="width:140px;">Name</th><td><?php echo htmlspecialchars($me['sName'] ?? ''); ?></td></tr>
                                    <tr><th>Phone</th><td><?php echo htmlspecialchars($me['sPhone'] ?? ''); ?></td></tr>
                                    <tr><th>Email</th><td><?php echo htmlspecialchars($me['sEmail'] ?? '-'); ?></td></tr>
                                    <tr><th>Role</th><td><?php echo htmlspecialchars($me['sRole'] ?? ''); ?></td></tr>
                                </table>
                                <a href="newpassword.php" class="btn btn-outline-primary btn-sm mt-3">Change Password</a>
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
