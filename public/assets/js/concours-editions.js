document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('diffusionEditionModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalEl);
    const docInput = document.getElementById('diffusionDocInput');
    const docLabel = document.getElementById('diffusionDocLabel');

    document.querySelectorAll('.btn-edition-mail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            docInput.value = btn.getAttribute('data-doc') || '';
            docLabel.textContent = btn.getAttribute('data-doc-label') || 'Document';
            modal.show();
        });
    });

    const groupMap = {
        'clubs': '.group-clubs',
        'comites-regionaux': '.group-comites-regionaux',
        'comites-departementaux': '.group-comites-departementaux',
        'archers': '.group-archers'
    };

    document.querySelectorAll('.check-all-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const group = btn.getAttribute('data-target-group');
            const selector = groupMap[group];
            if (!selector) return;
            modalEl.querySelectorAll(selector).forEach(function (cb) {
                cb.checked = true;
            });
        });
    });

    document.querySelectorAll('.uncheck-all-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const group = btn.getAttribute('data-target-group');
            const selector = groupMap[group];
            if (!selector) return;
            modalEl.querySelectorAll(selector).forEach(function (cb) {
                cb.checked = false;
            });
        });
    });
});
