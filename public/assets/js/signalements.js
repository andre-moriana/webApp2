/**
 * Script pour la page de liste des signalements
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialiser DataTables si disponible
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#reportsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            order: [[1, 'desc']], // Trier par date décroissante
            pageLength: 25,
            responsive: true
        });
    }
    
    // Gestion des filtres
    const filterForm = document.querySelector('form[action="/signalements"]');
    if (filterForm) {
        // Auto-submit lors du changement de statut
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        // Auto-submit lors du changement de limite
        const limitSelect = document.getElementById('limit');
        if (limitSelect) {
            limitSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }
    }
    
    // Confirmation avant suppression (si cette fonctionnalité est ajoutée)
    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce signalement ?')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Animation des badges de statut
    const statusBadges = document.querySelectorAll('.badge');
    statusBadges.forEach(badge => {
        badge.style.transition = 'all 0.3s ease';
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Mise en évidence des signalements en attente
    const pendingRows = document.querySelectorAll('tr:has(.badge-danger)');
    pendingRows.forEach(row => {
        row.style.backgroundColor = 'rgba(220, 53, 69, 0.05)';
    });
});
