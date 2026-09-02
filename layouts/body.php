<!--All Vertical Pages-->
 <body class="crm-theme">
<script>
(function () {
    try {
        var dark = false;
        try { dark = localStorage.getItem('crm_theme') === 'dark'; } catch (e) {}
        if (dark || document.documentElement.classList.contains('crm-dark')) {
            document.body.classList.add('crm-dark');
            document.documentElement.classList.add('crm-dark', 'crm-dark-preload');
            document.documentElement.style.colorScheme = 'dark';
            document.documentElement.style.backgroundColor = '#0b0f1e';
        }
    } catch (e) {}
})();
</script>
