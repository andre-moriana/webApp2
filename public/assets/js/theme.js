(function () {
    'use strict';

    var STORAGE_KEY = 'arctraining-theme';
    var THEME_COLOR_LIGHT = '#198754';
    var THEME_COLOR_DARK = '#0f172a';

    function getStoredMode() {
        var stored = localStorage.getItem(STORAGE_KEY);
        return (stored === 'light' || stored === 'dark' || stored === 'system') ? stored : 'system';
    }

    function systemPrefersDark() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function resolveTheme(mode) {
        if (mode === 'light' || mode === 'dark') {
            return mode;
        }
        return systemPrefersDark() ? 'dark' : 'light';
    }

    function updateMetaThemeColor(theme) {
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', theme === 'dark' ? THEME_COLOR_DARK : THEME_COLOR_LIGHT);
        }
    }

    function updateThemeUI(mode, resolvedTheme) {
        document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
        document.documentElement.setAttribute('data-theme-mode', mode);

        document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            icon.className = getModeIconClass(mode);
        });

        document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
            var btnMode = btn.getAttribute('data-theme-set');
            var isActive = btnMode === mode;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        document.querySelectorAll('input[name="theme-mode"]').forEach(function (input) {
            input.checked = input.value === mode;
        });

        updateMetaThemeColor(resolvedTheme);
    }

    function getModeIconClass(mode) {
        if (mode === 'light') {
            return 'fas fa-sun';
        }
        if (mode === 'dark') {
            return 'fas fa-moon';
        }
        return 'fas fa-circle-half-stroke';
    }

    function getModeLabel(mode) {
        if (mode === 'light') {
            return 'Clair';
        }
        if (mode === 'dark') {
            return 'Sombre';
        }
        return 'Système';
    }

    function setThemeMode(mode) {
        if (mode !== 'light' && mode !== 'dark' && mode !== 'system') {
            return;
        }
        localStorage.setItem(STORAGE_KEY, mode);
        applyTheme();
    }

    function applyTheme() {
        var mode = getStoredMode();
        var resolvedTheme = resolveTheme(mode);
        updateThemeUI(mode, resolvedTheme);
    }

    function bindControls() {
        document.addEventListener('click', function (event) {
            var target = event.target.closest('[data-theme-set]');
            if (!target) {
                return;
            }
            event.preventDefault();
            setThemeMode(target.getAttribute('data-theme-set'));
        });

        document.addEventListener('change', function (event) {
            var input = event.target;
            if (input && input.matches('input[name="theme-mode"]')) {
                setThemeMode(input.value);
            }
        });
    }

    var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    if (typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', function () {
            if (getStoredMode() === 'system') {
                applyTheme();
            }
        });
    } else if (typeof mediaQuery.addListener === 'function') {
        mediaQuery.addListener(function () {
            if (getStoredMode() === 'system') {
                applyTheme();
            }
        });
    }

    window.ThemeManager = {
        getMode: getStoredMode,
        getResolvedTheme: function () {
            return resolveTheme(getStoredMode());
        },
        getModeLabel: getModeLabel,
        setMode: setThemeMode,
        apply: applyTheme
    };

    bindControls();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyTheme);
    } else {
        applyTheme();
    }
})();
