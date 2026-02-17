/**
 * Charge la config du formulaire concours (create/edit) depuis data-config
 */
(function() {
    var el = document.querySelector('[data-concours-form-config]');
    if (!el) return;
    var cfg = el.getAttribute('data-config');
    if (!cfg) return;
    try {
        var c = JSON.parse(cfg);
        window.clubsData = c.clubs || [];
        window.disciplinesData = c.disciplines || [];
        window.typeCompetitionsData = c.typeCompetitions || [];
        window.niveauChampionnatData = c.niveauChampionnat || [];
        window.concoursData = c.concoursData || null;
    } catch (e) {
        console.warn('Config concours form parse error', e);
    }
})();
