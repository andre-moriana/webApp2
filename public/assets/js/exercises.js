/**
 * Fonctions JavaScript pour la gestion des exercices
 */

/**
 * Supprime un exercice après confirmation
 * @param {number} id - ID de l'exercice à supprimer
 */
function deleteExercise(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet exercice ? Cette action est irréversible.')) {
        // Créer un formulaire pour la suppression
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/exercises/' + id;
        
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Initialisation des composants au chargement de la page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-masquer les alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    });
}); 