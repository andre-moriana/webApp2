// JavaScript pour la page de configuration des permissions

document.addEventListener('DOMContentLoaded', function() {
    const resetDefaultBtn = document.getElementById('resetDefaultBtn');
    
    if (resetDefaultBtn) {
        resetDefaultBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les permissions aux valeurs par défaut ?')) {
                resetToDefaults();
            }
        });
    }
    
    // Valeurs par défaut
    const defaults = {
        'groups_view': 'Archer',
        'groups_edit': 'Coach',
        'groups_create': 'Coach',
        'groups_delete': 'Dirigeant',
        'events_view': 'Archer',
        'events_edit': 'Coach',
        'events_create': 'Coach',
        'events_delete': 'Dirigeant',
        'users_view': 'Coach',
        'users_self_edit': 'Archer',
        'users_edit': 'Dirigeant',
        'users_create': 'Dirigeant',
        'users_delete': 'Dirigeant',
        'exercises_view': 'Archer',
        'exercises_create': 'Coach',
        'exercises_edit': 'Coach',
        'exercises_delete': 'Coach',
        'training_progress_view': 'Archer',
        'training_progress_edit': 'Coach',
        'stats_other_view': 'Coach',
        'scored_training_view': 'Archer',
        'scored_training_create': 'Archer',
        'scored_training_edit': 'Archer',
        'score_sheet_view': 'Archer',
        'score_sheet_create': 'Archer',
        'trainings_view': 'Archer',
        'trainings_create': 'Coach',
        'trainings_edit': 'Coach'
    };
    
    function resetToDefaults() {
        for (const [key, value] of Object.entries(defaults)) {
            const select = document.querySelector(`select[name="perm_${key}"]`);
            if (select) {
                select.value = value;
            }
        }
        
        // Afficher un message de confirmation
        const alert = document.createElement('div');
        alert.className = 'alert alert-info alert-dismissible fade show mt-3';
        alert.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            Les valeurs par défaut ont été appliquées. N'oubliez pas d'enregistrer les modifications.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.getElementById('permissionsForm');
        form.insertBefore(alert, form.firstChild);
        
        // Scroll vers le haut pour voir le message
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
