/**
 * Script pour la page de détail d'un signalement
 */

/**
 * Fonction globale pour charger un message
 */
window.loadMessage = function(messageId) {
    window.logDebug('Signalements', 'Chargement du message', { messageId });
    const messageContent = document.getElementById('messageContent');
    
    // Afficher le loader
    messageContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement du message...</p>
        </div>
    `;
    
    // URL locale WebApp2 (pas directement l'API backend)
    const apiUrl = `/signalements/message/${messageId}`;
    window.logDebug('Signalements', 'URL de la requête', { apiUrl });
    
    // Faire la requête AJAX vers le backend WebApp2
    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin' // Inclure les cookies de session
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération du message');
        }
        return response.json();
    })
    .then(data => {
        window.logDebug('Signalements', 'Réponse complète', {
            data,
            type: typeof data,
            success: data.success,
            hasMessage: !!data.message
        });
        
        if (data.success && data.message) {
            const message = data.message;
            window.logDebug('Signalements', 'Structure du message', {
                id: message.id,
                content: message.content ? 'présent' : 'absent',
                author: message.author,
                created_at: message.created_at
            });
            
            // Gérer différents formats de date
            let formattedDate = 'Date inconnue';
            if (message.created_at) {
                try {
                    const createdDate = new Date(message.created_at);
                    formattedDate = createdDate.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    window.logError('Signalements', 'Erreur formatage date', e);
                    formattedDate = message.created_at;
                }
            }
            
            // Récupérer le nom de l'auteur avec gestion des différents formats
            let authorName = 'Auteur inconnu';
            if (message.author && message.author.name) {
                authorName = message.author.name;
            } else if (message.author_name) {
                authorName = message.author_name;
            }
            window.logDebug('Signalements', 'Nom auteur utilisé', { authorName });
            
            let attachmentHtml = '';
            if (message.attachment) {
                const isImage = message.attachment.mimeType?.startsWith('image/');
                if (isImage) {
                    attachmentHtml = `
                        <div class="mt-3">
                            <strong>Pièce jointe:</strong><br>
                            <img src="/messages/image/${message.id}" 
                                 alt="Image jointe" 
                                 class="img-fluid rounded mt-2" 
                                 style="max-height: 400px;">
                        </div>
                    `;
                } else {
                    attachmentHtml = `
                        <div class="mt-3">
                            <strong>Pièce jointe:</strong><br>
                            <a href="/messages/attachment/${message.id}" 
                               target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-download me-1"></i>
                                ${message.attachment.originalName || 'Télécharger'}
                            </a>
                        </div>
                    `;
                }
            }
            
            messageContent.innerHTML = `
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-2"></i>
                                <strong>${escapeHtml(authorName)}</strong>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                ${formattedDate}
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="message-content">
                            ${escapeHtml(message.content || 'Contenu non disponible').replace(/\n/g, '<br>')}
                        </div>
                        ${attachmentHtml}
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Message ID: #${message.id || messageId}
                        </small>
                    </div>
                </div>
            `;
        } else {
            throw new Error('Format de réponse invalide');
        }
    })
    .catch(error => {
        window.logError('Signalements', 'Erreur chargement message', {
            error,
            message: error.message,
            stack: error.stack,
            apiUrl,
            messageId
        });
        
        messageContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreur lors du chargement du message</strong><br>
                ${escapeHtml(error.message)}<br>
                <small class="mt-2 d-block">
                    URL tentée : ${apiUrl}<br>
                    Message ID : ${messageId}
                </small>
            </div>
        `;
    });
};

/**
 * Fonction pour échapper les caractères HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    window.logDebug('Signalements', 'Page de détail chargée');
    
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
            window.logDebug('Signalements', 'Brouillon de notes chargé', { reportId });
        }
        
        // Sauvegarder automatiquement toutes les 5 secondes
        let saveTimer;
        adminNotesTextarea.addEventListener('input', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => {
                localStorage.setItem(storageKey, this.value);
                window.logDebug('Signalements', 'Brouillon de notes sauvegardé', { reportId });
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
