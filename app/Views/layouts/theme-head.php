<script>
(function () {
    var STORAGE_KEY = 'arctraining-theme';
    var stored = localStorage.getItem(STORAGE_KEY);
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = (stored === 'light' || stored === 'dark') ? stored : (prefersDark ? 'dark' : 'light');
    var mode = (stored === 'light' || stored === 'dark') ? stored : 'system';
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.setAttribute('data-theme-mode', mode);
})();
</script>
