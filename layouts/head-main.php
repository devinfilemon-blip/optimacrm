<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta content="<?php echo defined('APP_TAGLINE') ? APP_TAGLINE : 'Recruitment & Staffing CRM'; ?>" name="description"/>
<meta content="<?php echo defined('APP_NAME') ? APP_NAME : 'Optima'; ?>" name="author"/>
<!-- App favicon -->
<link rel="shortcut icon" href="<?php echo defined('APP_ICON') ? APP_ICON : 'assets/images/optima-logo-mark.png'; ?>">
<meta name="theme-color" content="#9333ea" id="crmThemeColorMeta">
<style>
  body { background-color: #f8f6fc !important; }
  table { overflow-x: auto; }

  html.crm-dark,
  html.crm-dark-preload {
    color-scheme: dark;
    background-color: #0b0f1e !important;
  }
  html.crm-dark body,
  html.crm-dark-preload body,
  body.crm-dark {
    background-color: #0b0f1e !important;
    color: #f1f5f9;
  }
  html.crm-dark .main-content,
  html.crm-dark-preload .main-content,
  html.crm-dark .page-content,
  html.crm-dark-preload .page-content,
  body.crm-dark .main-content,
  body.crm-dark .page-content {
    background-color: #0b0f1e !important;
  }
</style>
<?php if (empty($crmForceLightTheme)) : ?>
<script>
(function () {
    try {
        if (localStorage.getItem('crm_theme') === 'dark') {
            document.documentElement.classList.add('crm-dark', 'crm-dark-preload');
            document.documentElement.style.colorScheme = 'dark';
            document.documentElement.style.backgroundColor = '#0b0f1e';
            var meta = document.getElementById('crmThemeColorMeta');
            if (meta) meta.setAttribute('content', '#0b0f1e');
        }
    } catch (e) {}
})();
</script>
<?php else : ?>
<script>
(function () {
    try {
        document.documentElement.classList.remove('crm-dark', 'crm-dark-preload');
        document.documentElement.style.colorScheme = 'light';
        document.documentElement.style.backgroundColor = '';
        var meta = document.getElementById('crmThemeColorMeta');
        if (meta) meta.setAttribute('content', '#9333ea');
    } catch (e) {}
})();
</script>
<?php endif; ?>
