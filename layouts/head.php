<!-- Bootstrap Css -->
<link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />

<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
<link href="assets/css/crm-theme.css?v=4" rel="stylesheet" type="text/css" />
<link href="assets/css/crm-dark.css?v=1" rel="stylesheet" type="text/css" />
<link href="assets/css/crm-dashboard.css?v=1" rel="stylesheet" type="text/css" />
<link href="assets/css/crm-mobile.css?v=1" rel="stylesheet" type="text/css" />
<link href="assets/css/optima.css?v=16" rel="stylesheet" type="text/css" />
<?php if (empty($crmForceLightTheme)) : ?>
<script>
(function () {
    try {
        if (localStorage.getItem('crm_theme') === 'dark') {
            document.documentElement.classList.add('crm-dark', 'crm-dark-preload');
            document.documentElement.style.colorScheme = 'dark';
            document.documentElement.style.backgroundColor = '#0b0f1e';
        }
    } catch (e) {}
})();
</script>
<?php endif; ?>

    <!-- DataTables -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- Responsive datatable examples -->
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
