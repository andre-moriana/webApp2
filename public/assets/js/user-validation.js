/**
 * JavaScript pour la page de validation des utilisateurs
 * Gère les interactions et validations côté client
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de validation des utilisateurs chargé');
    
    // Initialiser les fonctionnalités
    initializeApprovalButtons();
    initializeRejectModals();
    initializeAlerts();
    initializeTableInteractions();
});

/**
 * Initialise les boutons d'approbation
 */
function initializeApprovalButtons() {
    const approveButtons = document.querySelectorAll('button[type="submit"]');
    
    approveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            const userId = form.querySelector('input[name="user_id"]').value;
            const userName = this.closest('tr').querySelector('.user-name').textContent.trim();
            
            // Confirmation personnalisée
            if (!confirm(`Êtes-vous sûr de vouloir approuver l'utilisateur "${userName}" ?`)) {
                e.preventDefault();
                return false;
            }
            
            // Afficher un indicateur de chargement
            showLoadingState(this);
        });
    });
}

/**
 * Initialise les modales de rejet
 */
function initializeRejectModals() {
    const rejectButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    
    rejectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = document.querySelector(modalId);
            
            if (modal) {
                // Réinitialiser le formulaire de rejet
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
                
                // Ajouter un gestionnaire pour la soumission du formulaire
                form.addEventListener('submit', function(e) {
                    const userId = form.querySelector('input[name="user_id"]').value;
                    const userName = modal.querySelector('.alert-warning strong').textContent.trim();
                    
                    if (!confirm(`Êtes-vous sûr de vouloir rejeter l'utilisateur "${userName}" ?`)) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Afficher un indicateur de chargement
                    const submitButton = form.querySelector('button[type="submit"]');
                    showLoadingState(submitButton);
                });
            }
        });
    });
}

/**
 * Initialise la gestion des alertes
 */
function initializeAlerts() {
    // Auto-masquer les alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Gestionnaire pour les boutons de fermeture des alertes
    const closeButtons = document.querySelectorAll('.alert .btn-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    });
}

/**
 * Initialise les interactions avec le tableau
 */
function initializeTableInteractions() {
    const table = document.querySelector('.table');
    if (!table) return;
    
    // Ajouter des classes pour le style hover
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.classList.add('table-hover-active');
        });
        
        row.addEventListener('mouseleave', function() {
            this.classList.remove('table-hover-active');
        });
    });
    
    // Gestion des clics sur les lignes (optionnel)
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Éviter de déclencher le clic si on clique sur un bouton
            if (e.target.closest('button, a, input')) {
                return;
            }
            
            // Ajouter une classe pour indiquer la sélection
            rows.forEach(r => r.classList.remove('table-row-selected'));
            this.classList.add('table-row-selected');
        });
    });
}

/**
 * Affiche un état de chargement sur un bouton
 */
function showLoadingState(button) {
    const originalText = button.innerHTML;
    const originalDisabled = button.disabled;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';
    
    // Restaurer l'état après 3 secondes (fallback)
    setTimeout(() => {
        button.disabled = originalDisabled;
        button.innerHTML = originalText;
    }, 3000);
}

/**
 * Affiche une notification toast
 */
function showToast(message, type = 'info') {
    // Créer l'élément toast
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Initialiser et afficher le toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Supprimer l'élément après fermeture
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Crée le conteneur pour les toasts
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

/**
 * Valide un formulaire de rejet
 */
function validateRejectForm(form) {
    const reason = form.querySelector('textarea[name="reason"]').value.trim();
    
    // La raison est optionnelle, donc pas de validation stricte
    return true;
}

/**
 * Gère les erreurs de validation
 */
function handleValidationError(message) {
    showToast(message, 'danger');
}

/**
 * Gère les succès d'opération
 */
function handleSuccess(message) {
    showToast(message, 'success');
}

// Export des fonctions pour utilisation externe
window.UserValidation = {
    showToast,
    showLoadingState,
    validateRejectForm,
    handleValidationError,
    handleSuccess
};
