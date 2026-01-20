/**
 * Script pour la page de détail d'un signalement
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page de détail du signalement chargée');
    
    // Gestion du formulaire de mise à jour
    const updateForm = document.querySelector('form[action*="/update"]');
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            const status = document.getElementById('status').value;
            const adminNotes = document.getElementById('admin_notes').value;
            
            // Validation
            if (!status) {
                e.preventDefault();
                alert('Veuillez sélectionner un statut');
                return false;
            }
            
            // Confirmation pour changement de statut important
            if (status === 'resolved' || status === 'dismissed') {
                if (!confirm('Êtes-vous sûr de vouloir changer le statut à "' + 
                    (status === 'resolved' ? 'Résolu' : 'Rejeté') + '" ?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Vérifier si des notes sont ajoutées pour les statuts finaux
            if ((status === 'resolved' || status === 'dismissed') && !adminNotes.trim()) {
                if (!confirm('Aucune note n\'a été ajoutée. Voulez-vous continuer ?')) {
                    e.preventDefault();
                    document.getElementById('admin_notes').focus();
                    return false;
                }
            }
        });
    }
    
    // Auto-save des notes (brouillon local)
    const adminNotesTextarea = document.getElementById('admin_notes');
    if (adminNotesTextarea) {
        const reportId = window.location.pathname.split('/').pop();
        const storageKey = `signalement_${reportId}_notes`;
        
        // Charger le brouillon au chargement de la page
        const savedNotes = localStorage.getItem(storageKey);
        if (savedNotes && !adminNotesTextarea.value) {
            adminNotesTextarea.value = savedNotes;
            console.log('Brouillon de notes chargé');
        }
        
        // Sauvegarder automatiquement toutes les 5 secondes
        let saveTimer;
        adminNotesTextarea.addEventListener('input', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => {
                localStorage.setItem(storageKey, this.value);
                console.log('Brouillon de notes sauvegardé');
            }, 5000);
        });
        
        // Nettoyer le brouillon lors de la soumission du formulaire
        if (updateForm) {
            updateForm.addEventListener('submit', function() {
                localStorage.removeItem(storageKey);
            });
        }
    }
    
    // Compteur de caractères pour les notes
    if (adminNotesTextarea) {
        const maxLength = 1000;
        const counterElement = document.createElement('small');
        counterElement.className = 'text-muted';
        counterElement.style.display = 'block';
        counterElement.style.marginTop = '5px';
        adminNotesTextarea.parentNode.appendChild(counterElement);
        
        function updateCounter() {
            const remaining = maxLength - adminNotesTextarea.value.length;
            counterElement.textContent = `${adminNotesTextarea.value.length} / ${maxLength} caractères`;
            
            if (remaining < 100) {
                counterElement.classList.remove('text-muted');
                counterElement.classList.add('text-warning');
            } else {
                counterElement.classList.remove('text-warning');
                counterElement.classList.add('text-muted');
            }
        }
        
        adminNotesTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Animation du changement de statut
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const statusColor = {
                'pending': 'danger',
                'reviewed': 'warning',
                'resolved': 'success',
                'dismissed': 'secondary'
            };
            
            this.className = `form-select border-${statusColor[this.value] || 'primary'}`;
        });
        
        // Définir la couleur initiale
        const event = new Event('change');
        statusSelect.dispatchEvent(event);
    }
    
    // Confirmation pour les actions critiques
    const deleteButton = document.querySelector('[onclick*="Supprimer"]');
    if (deleteButton) {
        deleteButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('⚠️ ATTENTION ⚠️\n\nÊtes-vous sûr de vouloir supprimer définitivement ce signalement ?\n\nCette action est irréversible.')) {
                alert('Fonctionnalité en cours de développement');
            }
        });
    }
    
    // Auto-dismiss des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000);
    });
    
    // Copier l'ID du signalement au clic
    const reportIdElements = document.querySelectorAll('[id*="signalement"]');
    reportIdElements.forEach(element => {
        if (element.textContent.includes('#')) {
            element.style.cursor = 'pointer';
            element.title = 'Cliquer pour copier l\'ID';
            element.addEventListener('click', function() {
                const id = this.textContent.replace('#', '').trim();
                navigator.clipboard.writeText(id).then(() => {
                    // Afficher un feedback visuel
                    const originalText = this.textContent;
                    this.textContent = '✓ Copié !';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                }).catch(err => {
                    console.error('Erreur lors de la copie:', err);
                });
            });
        }
    });
});
