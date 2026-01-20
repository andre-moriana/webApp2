// JavaScript personnalis� pour le portail Archers de G�menos

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialisation des popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Gestion des alertes avec auto-dismiss
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(function(alert) {
        const delay = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, delay);
    });

    // Gestion des confirmations de suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.confirmDelete || 'êtes-vous sur de vouloir supprimer cet élément ?';
            
            if (confirm(message)) {
                // Si c'est un formulaire, le soumettre
                if (this.tagName === 'BUTTON' && this.form) {
                    this.form.submit();
                }
                // Si c'est un lien, rediriger
                else if (this.tagName === 'A') {
                    window.location.href = this.href;
                }
            }
        });
    });

    // Gestion des formulaires avec validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card) {
        observer.observe(card);
    });

    // Gestion des modales de confirmation
    window.showConfirmModal = function(title, message, callback) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-danger" id="confirmBtn">Confirmer</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        document.getElementById('confirmBtn').addEventListener('click', function() {
            callback();
            bsModal.hide();
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modal);
        });
    };

    // Fonction utilitaire pour formater les dates
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    };

    // Fonction utilitaire pour formater les heures
    window.formatTime = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    // Gestion des tableaux avec tri
    const sortableTables = document.querySelectorAll('.table-sortable');
    sortableTables.forEach(function(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(function(header) {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const column = this.dataset.sort;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                const isAscending = this.classList.contains('sort-asc');
                
                // Retirer les classes de tri de tous les headers
                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                
                // Trier les lignes
                rows.sort(function(a, b) {
                    const aVal = a.querySelector(`td[data-sort="${column}"]`)?.textContent || '';
                    const bVal = b.querySelector(`td[data-sort="${column}"]`)?.textContent || '';
                    
                    if (isAscending) {
                        return bVal.localeCompare(aVal);
                    } else {
                        return aVal.localeCompare(bVal);
                    }
                });
                
                // R�organiser les lignes dans le DOM
                rows.forEach(row => tbody.appendChild(row));
                
                // Ajouter la classe de tri appropri�e
                this.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
            });
        });
    });

    // Gestion des filtres de recherche
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const targetTable = document.querySelector(this.dataset.target);
            
            if (targetTable) {
                const rows = targetTable.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });

    // Gestion des notifications toast
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            toastContainer.removeChild(toast);
        });
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
        return container;
    }
    
    // Fonction de logging centralisée
    window.logDebug = function(context, message, data = null) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            const timestamp = new Date().toISOString();
            const logMessage = `[${timestamp}] [${context}] ${message}`;
            
            if (data !== null) {
                console.log(logMessage, data);
            } else {
                console.log(logMessage);
            }
        }
    };
    
    window.logError = function(context, message, error = null) {
        const timestamp = new Date().toISOString();
        const logMessage = `[${timestamp}] [ERROR] [${context}] ${message}`;
        
        if (error !== null) {
            console.error(logMessage, error);
        } else {
            console.error(logMessage);
        }
    };
});

// Fonctions globales
window.ApiService = {
    // Fonction pour faire des appels API
    request: async function(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            window.logError('ApiService', 'Erreur de requête API', error);
            throw error;
        }
    }
};
