<?php
session_start();
require_once "layouts/config.php";
if (!empty($_SESSION['loggedin'])) {
    header("location: index.php");
    exit;
}
$crmForceLightTheme = true;
?>
<?php include 'layouts/head-main.php'; ?>
<head>
    <title><?php echo APP_NAME; ?> &mdash; Sign In</title>
    <?php include 'layouts/head.php'; ?>

    <style>
        .optima-login-page *,
        .optima-login-page *::before,
        .optima-login-page *::after{ box-sizing: border-box; }

        body.crm-auth-page,
        body.crm-auth-page.crm-dark { color-scheme: light; }

        :root{
            --optima-ink:#241b45;
            --optima-muted:#8b87a3;
            --optima-field-bg:#f2f1f8;
            --optima-purple-1:#2c1668;
            --optima-purple-2:#3c1f92;
            --optima-purple-3:#5330c9;
            --optima-blue-1:#4d7bff;
            --optima-blue-2:#6a90ff;
            --optima-mint-1:#7fe9d8;
            --optima-mint-2:#59d2c4;
        }

        body.optima-auth-body{
            margin:0;
            background:
                radial-gradient(circle at 85% 10%, rgba(255,255,255,0.08) 0%, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(255,255,255,0.06) 0%, transparent 40%),
                linear-gradient(135deg, var(--optima-purple-1) 0%, var(--optima-purple-2) 55%, var(--optima-purple-3) 100%) !important;
        }

        .optima-login-page{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding:32px 20px; }

        .optima-shell{
            width:100%; max-width:900px; display:flex; background:#ffffff; border-radius:28px;
            overflow:hidden; box-shadow:0 30px 70px rgba(20,10,60,0.35);
        }

        .optima-visual{
            position:relative; flex:0 0 42%; display:flex; align-items:center; justify-content:center;
            padding:40px 24px;
            background:
                radial-gradient(circle at 30% 20%, rgba(255,255,255,0.10) 0%, transparent 45%),
                linear-gradient(160deg, var(--optima-purple-2) 0%, var(--optima-purple-1) 100%);
            overflow:hidden;
        }
        .optima-visual::before, .optima-visual::after{ content:""; position:absolute; border-radius:50%; background:rgba(255,255,255,0.06); }
        .optima-visual::before{ width:260px; height:260px; top:-90px; left:-80px; }
        .optima-visual::after{ width:180px; height:180px; bottom:-70px; right:-60px; background:rgba(255,255,255,0.05); }

        .optima-visual-inner{ position:relative; text-align:center; color:#fff; }
        .optima-visual-inner img{ width:96px; height:96px; margin-bottom:22px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.25)); }
        .optima-visual-inner h3{ font-size:22px; font-weight:700; margin:0 0 10px; }
        .optima-visual-inner p{ font-size:14px; color:rgba(255,255,255,0.75); max-width:240px; margin:0 auto; line-height:1.5; }

        .optima-form-panel{ flex:1 1 auto; padding:44px 48px 36px; display:flex; flex-direction:column; }

        .optima-brand-row{ display:flex; align-items:center; justify-content:flex-end; gap:10px; margin-bottom:34px; }
        .optima-brand-row img{ height:34px; width:auto; object-fit:contain; }

        .optima-form-panel form{ margin-top:4px; }

        .optima-field{ margin-bottom:18px; }
        .optima-field-label-row{ display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
        .optima-label{ font-size:13.5px; font-weight:600; color:var(--optima-ink); margin:0; }
        .optima-forgot{ font-size:13px; font-weight:600; color:var(--optima-blue-1); text-decoration:none; cursor:pointer; }
        .optima-forgot:hover{ text-decoration:underline; color:var(--optima-blue-1); }

        .optima-input{
            width:100%; border:1px solid transparent; background:var(--optima-field-bg); border-radius:12px;
            padding:12px 16px; font-size:14.5px; color:var(--optima-ink); outline:none;
            transition:border-color .15s ease, background .15s ease;
        }
        .optima-input::placeholder{ color:#a29fbb; }
        .optima-input:focus{ background:#fff; border-color:var(--optima-blue-1); box-shadow:0 0 0 3px rgba(77,123,255,0.12); }

        .optima-remember{ display:flex; align-items:center; gap:8px; margin:4px 0 22px; }
        .optima-remember input{ width:16px; height:16px; accent-color:var(--optima-blue-1); cursor:pointer; }
        .optima-remember label{ font-size:13.5px; color:#5c5876; margin:0; cursor:pointer; }

        .optima-submit{
            width:100%; border:none; border-radius:14px; padding:13px 18px; font-size:15.5px; font-weight:700;
            color:#fff; letter-spacing:0.2px;
            background:linear-gradient(135deg, var(--optima-blue-1) 0%, var(--optima-blue-2) 100%);
            box-shadow:0 12px 24px rgba(77,123,255,0.35); cursor:pointer;
            transition:transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }
        .optima-submit:hover{ transform:translateY(-1px); box-shadow:0 16px 28px rgba(77,123,255,0.4); }
        .optima-submit:disabled{ opacity:0.7; cursor:default; transform:none; }

        .optima-error{
            display:none; font-size:13px; font-weight:600; color:#e0355b; background:rgba(224,53,91,0.08);
            border-radius:10px; padding:10px 12px; margin-bottom:16px;
        }

        .optima-footer{ margin-top:auto; padding-top:26px; text-align:center; font-size:12.5px; color:var(--optima-muted); }

        @media (max-width: 820px){
            .optima-visual{ display:none; }
            .optima-shell{ max-width:420px; border-radius:24px; }
            .optima-form-panel{ padding:36px 28px 28px; }
            .optima-brand-row{ justify-content:center; }
        }
    </style>
</head>

<body class="crm-theme crm-auth-page optima-auth-body">
<script>
(function () {
    try {
        document.documentElement.classList.remove('crm-dark', 'crm-dark-preload');
        document.documentElement.style.colorScheme = 'light';
        document.documentElement.style.backgroundColor = '';
        document.body.classList.remove('crm-dark');
    } catch (e) {}
})();
</script>

    <div class="optima-login-page">
        <div class="optima-shell">

            <div class="optima-visual">
                <div class="optima-visual-inner">
                    <img src="assets/images/optima-logo-mark.png" alt="Optima">
                    <h3>Recruitment, organized.</h3>
                    <p>Track companies, job requirements and candidate placements in one place.</p>
                </div>
            </div>

            <div class="optima-form-panel">
                <div class="optima-brand-row">
                    <img src="assets/images/optima-logo.png" alt="<?php echo APP_NAME; ?> logo">
                </div>

                <div id="optimaLoginError" class="optima-error"></div>

                <form id="loginForm" action="auth-login.php" onsubmit="loginUser(event);" autocomplete="off">
                    <div class="optima-field">
                        <div class="optima-field-label-row">
                            <label for="phone" class="optima-label">Phone / Username</label>
                        </div>
                        <input type="text" class="optima-input" id="phone" name="phone" placeholder="Enter your phone or username" autocomplete="username">
                    </div>

                    <div class="optima-field">
                        <div class="optima-field-label-row">
                            <label for="password" class="optima-label">Password</label>
                            <a href="javascript:void(0);" class="optima-forgot" onclick="alert('Please contact your administrator to reset your password.');">Forgot Password?</a>
                        </div>
                        <input type="password" class="optima-input" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                    </div>

                    <div class="optima-remember">
                        <input type="checkbox" id="rememberMe">
                        <label for="rememberMe">Remember Me</label>
                    </div>

                    <button type="submit" id="loginSubmitBtn" class="optima-submit">Sign In</button>
                </form>

                <div class="optima-footer">
                    &copy; <script>document.write(new Date().getFullYear())</script> <?php echo APP_NAME; ?>. All rights reserved.
                </div>
            </div>

        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function () {
    try {
        var savedUser = localStorage.getItem('optima_remember_username');
        if (savedUser) {
            document.getElementById('phone').value = savedUser;
            document.getElementById('rememberMe').checked = true;
        }
    } catch (e) {}
})();

function loginUser(event) {
    event.preventDefault();

    var phone = document.getElementById("phone").value.trim();
    var password = document.getElementById("password").value;
    var errorBox = document.getElementById("optimaLoginError");
    var submitBtn = document.getElementById("loginSubmitBtn");

    errorBox.style.display = "none";
    errorBox.textContent = "";

    if (phone == "" || password == "") {
        errorBox.textContent = "Please enter both username and password.";
        errorBox.style.display = "block";
        return false;
    }

    try {
        if (document.getElementById('rememberMe').checked) {
            localStorage.setItem('optima_remember_username', phone);
        } else {
            localStorage.removeItem('optima_remember_username');
        }
    } catch (e) {}

    submitBtn.disabled = true;
    submitBtn.textContent = "Signing in...";

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: "loginUser", phone: phone, password: password })
    })
    .then(async function(response) {
        var text = await response.text();
        try { return JSON.parse(text); }
        catch (e) { throw new Error('Invalid server response'); }
    })
    .then(responseData => {
        if (responseData.status === "success") {
            window.location.href = 'index.php';
        } else {
            errorBox.textContent = responseData.message || 'Login failed';
            errorBox.style.display = "block";
            submitBtn.disabled = false;
            submitBtn.textContent = "Sign In";
        }
    })
    .catch(error => {
        errorBox.textContent = "An error occurred. Please try again.";
        errorBox.style.display = "block";
        submitBtn.disabled = false;
        submitBtn.textContent = "Sign In";
    });
}
</script>

</body>
</html>
