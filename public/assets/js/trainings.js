// Gestion de la page des entraînements

// Variables globales
let currentExerciseId = null;
let sessionData = {
    volleys: [],
    startTime: null,
    endTime: null,
    totalArrows: 0,
    totalVolleys: 0
};
let sessionTimer = null;
let sessionActive = false;

// Éléments DOM
let trainingSessionModal;
let sessionVolleysEl;
let sessionArrowsEl;
let sessionTimeEl;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initializeEventListeners();
    initializeUserSelection();
    initializeNotesEditing();
});

// Initialiser les éléments DOM
function initializeElements() {
    trainingSessionModal = new bootstrap.Modal(document.getElementById('trainingSessionModal'));
    sessionVolleysEl = document.getElementById('sessionVolleys');
    sessionArrowsEl = document.getElementById('sessionArrows');
    sessionTimeEl = document.getElementById('sessionTime');
}

// Initialiser les écouteurs d'événements
function initializeEventListeners() {
    // Bouton ajouter volée
    const addVolleyBtn = document.getElementById('addVolleyBtn');
    if (addVolleyBtn) {
        addVolleyBtn.addEventListener('click', addVolley);
    }

    // Bouton terminer session
    const endSessionBtn = document.getElementById('endSessionBtn');
    if (endSessionBtn) {
        endSessionBtn.addEventListener('click', showEndSessionForm);
    }

    // Bouton annuler session
    const cancelSessionBtn = document.getElementById('cancelSessionBtn');
    if (cancelSessionBtn) {
        cancelSessionBtn.addEventListener('click', cancelSession);
    }

    // Bouton supprimer session
    const deleteSessionBtn = document.getElementById('deleteSessionBtn');
    if (deleteSessionBtn) {
        deleteSessionBtn.addEventListener('click', confirmDeleteSession);
    }

    // Protection contre la fermeture accidentelle
    const modal = document.getElementById('trainingSessionModal');
    if (modal) {
        modal.addEventListener('hide.bs.modal', preventAccidentalClose);
        modal.addEventListener('hidden.bs.modal', onModalHidden);
    }
}

// Initialiser la sélection d'utilisateur
function initializeUserSelection() {
    const userSelect = document.getElementById('userSelect');
    if (userSelect) {
        userSelect.addEventListener('change', handleUserSelection);
    }
}

// Démarrer une session d'entraînement
function startTrainingSession(exerciseId, exerciseTitle) {
    currentExerciseId = exerciseId;
    document.getElementById('sessionExerciseTitle').textContent = exerciseTitle;
    
    // Réinitialiser les données
    sessionData = {
        volleys: [],
        startTime: new Date(),
        endTime: null,
        totalArrows: 0,
        totalVolleys: 0
    };
    
    // Réinitialiser l'interface
    updateSessionStats();
    document.getElementById('volleysList').innerHTML = '';
    document.getElementById('sessionNotes').value = '';
    document.getElementById('endSessionSection').style.display = 'none';
    document.getElementById('endSessionBtn').style.display = 'none';
    
    // Ne pas réinitialiser l'input du nombre de flèches pour conserver la dernière valeur
    // document.getElementById('arrowCount').value = '6'; // Supprimer cette ligne
    
    // Démarrer la session
    sessionActive = true;
    startSessionTimer();
    trainingSessionModal.show();
}

// Ajouter une volée
function addVolley() {
    const arrowCountInput = document.getElementById('arrowCount');
    const arrowCount = parseInt(arrowCountInput.value);
    
    if (arrowCount > 0) {
        const volley = {
            id: Date.now(),
            arrows: arrowCount,
            timestamp: new Date()
        };
        
        sessionData.volleys.push(volley);
        sessionData.totalArrows += arrowCount;
        sessionData.totalVolleys++;
        
        addVolleyToList(volley);
        updateSessionStats();
        
        // Afficher le bouton de fin après la première volée
        if (sessionData.totalVolleys === 1) {
            document.getElementById('endSessionBtn').style.display = 'inline-block';
        }
        
        // Conserver la dernière valeur saisie au lieu de réinitialiser à 6
        // Ne pas réinitialiser l'input pour garder la dernière valeur
    }
}

// Afficher le formulaire de fin de session
function showEndSessionForm() {
    document.getElementById('endSessionSection').style.display = 'block';
    this.innerHTML = '<i class="fas fa-save"></i> Sauvegarder et terminer';
    this.onclick = saveSession;
}

// Sauvegarder la session
function saveSession() {
    sessionData.endTime = new Date();
    sessionData.notes = document.getElementById('sessionNotes').value;
    
    console.log('Données à sauvegarder:', sessionData);
    
    const selectedUserId = window.selectedUserId || null;
    
    console.log('Selected User ID:', selectedUserId);
    console.log('Current Exercise ID:', currentExerciseId);
    
    // Utiliser l'endpoint local de l'application web
    fetch('/trainings/save-session', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            exercise_sheet_id: currentExerciseId,
            user_id: selectedUserId,
            session_data: sessionData
        })
    })
    .then(response => {
        console.log('Réponse reçue:', response);
        console.log('Status:', response.status);
        console.log('Headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
        }
        
        return response.text().then(text => {
            console.log('Texte brut reçu:', text);
            const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
            console.log('Texte nettoyé:', cleanText);
            
            try {
                return JSON.parse(cleanText);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                console.error('Réponse reçue:', cleanText);
                return { success: false, message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 200) };
            }
        });
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            alert('Session sauvegardée avec succès !');
            sessionActive = false;
            trainingSessionModal.hide();
            
            // Recharger la page pour afficher les nouvelles données
            console.log('Rechargement de la page dans 1 seconde...');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erreur: ' + (data.message || 'Erreur lors de la sauvegarde'));
            console.error('Erreur de sauvegarde:', data);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        console.error('Type d\'erreur:', error.name);
        console.error('Message d\'erreur:', error.message);
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            alert('Erreur de connexion: Impossible de joindre le serveur API. Vérifiez que le backend est démarré.');
        } else if (error.message.includes('HTTP:')) {
            alert('Erreur HTTP: ' + error.message);
        } else {
            alert('Erreur de connexion au serveur: ' + error.message);
        }
    });
}

// Annuler la session
function cancelSession() {
    if (sessionData.totalVolleys > 0) {
        if (confirm('Êtes-vous sûr de vouloir annuler cette session ? Les données seront perdues.')) {
            sessionActive = false;
            trainingSessionModal.hide();
        }
    } else {
        sessionActive = false;
        trainingSessionModal.hide();
    }
}

// Empêcher la fermeture accidentelle
function preventAccidentalClose(event) {
    if (sessionActive) {
        event.preventDefault();
        event.stopPropagation();
        return false;
    }
    return true;
}

// Gérer la fermeture de la modale
function onModalHidden() {
    if (!sessionActive) {
        stopSessionTimer();
    }
}

// Gérer la sélection d'utilisateur
function handleUserSelection() {
    const selectedUserId = this.value;
    if (selectedUserId) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('user_id', selectedUserId);
        window.location.href = currentUrl.toString();
    } else {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.delete('user_id');
        window.location.href = currentUrl.toString();
    }
}

// Ajouter une volée à la liste
function addVolleyToList(volley) {
    const volleyDiv = document.createElement('div');
    volleyDiv.className = 'volley-item d-flex justify-content-between align-items-center';
    volleyDiv.innerHTML = `
        <div>
            <strong>Volée ${sessionData.totalVolleys}</strong>
            <small class="text-muted ms-2">${volley.arrows} flèche${volley.arrows > 1 ? 's' : ''}</small>
        </div>
        <div>
            <small class="text-muted">${volley.timestamp.toLocaleTimeString()}</small>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeVolley(${volley.id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    volleyDiv.id = `volley-${volley.id}`;
    document.getElementById('volleysList').appendChild(volleyDiv);
}

// Supprimer une volée
function removeVolley(volleyId) {
    const volleyIndex = sessionData.volleys.findIndex(v => v.id === volleyId);
    if (volleyIndex !== -1) {
        const volley = sessionData.volleys[volleyIndex];
        sessionData.totalArrows -= volley.arrows;
        sessionData.totalVolleys--;
        sessionData.volleys.splice(volleyIndex, 1);
        
        const volleyEl = document.getElementById(`volley-${volleyId}`);
        if (volleyEl) {
            volleyEl.remove();
        }
        
        updateSessionStats();
        
        if (sessionData.totalVolleys === 0) {
            document.getElementById('endSessionBtn').style.display = 'none';
        }
    }
}

// Mettre à jour les statistiques
function updateSessionStats() {
    sessionVolleysEl.textContent = sessionData.totalVolleys;
    sessionArrowsEl.textContent = sessionData.totalArrows;
}

// Démarrer le timer
function startSessionTimer() {
    sessionTimer = setInterval(() => {
        if (sessionData.startTime) {
            const now = new Date();
            const diff = now - sessionData.startTime;
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            sessionTimeEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }, 1000);
}

// Arrêter le timer
function stopSessionTimer() {
    if (sessionTimer) {
        clearInterval(sessionTimer);
        sessionTimer = null;
    }
}

// Fonction pour ouvrir le modal de modification de statut depuis un bouton
function updateExerciseStatusFromButton(button) {
    console.log('Bouton cliqué:', button);
    console.log('Attributs du bouton:', button.attributes);
    
    const exerciseId = button.getAttribute('data-exercise-id');
    const exerciseTitle = button.getAttribute('data-exercise-title');
    
    console.log('ID récupéré:', exerciseId);
    console.log('Titre récupéré:', exerciseTitle);
    
    if (!exerciseId || exerciseId === '') {
        console.error('ID d\'exercice manquant ou vide');
        console.log('Tous les attributs data:', {
            'data-exercise-id': button.getAttribute('data-exercise-id'),
            'data-exercise-title': button.getAttribute('data-exercise-title')
        });
        alert('Erreur: ID de l\'exercice manquant ou vide');
        return;
    }
    
    updateExerciseStatus(exerciseId, exerciseTitle);
}

// Fonction pour ouvrir le modal de modification de statut
function updateExerciseStatus(exerciseId, exerciseTitle) {
    console.log('updateExerciseStatus appelée avec:', exerciseId, exerciseTitle);
    console.log('Type de exerciseId:', typeof exerciseId);
    console.log('Valeur de exerciseId:', exerciseId);
    
    if (!exerciseId || exerciseId === '' || exerciseId === 'null') {
        console.error('ID d\'exercice invalide:', exerciseId);
        alert('Erreur: ID de l\'exercice invalide');
        return;
    }
    
    const exerciseIdField = document.getElementById('statusExerciseId');
    console.log('Champ statusExerciseId trouvé:', exerciseIdField);
    
    if (!exerciseIdField) {
        console.error('Champ statusExerciseId non trouvé');
        alert('Erreur: Champ exercice non trouvé');
        return;
    }
    
    exerciseIdField.value = exerciseId;
    console.log('Valeur définie pour statusExerciseId:', exerciseIdField.value);
    
    const modalLabel = document.getElementById('statusModalLabel');
    if (modalLabel) {
        modalLabel.textContent = 'Modifier le statut de : ' + exerciseTitle;
    }
    
    // Charger le statut actuel de l'exercice
    loadCurrentStatus(exerciseId);
    
    // Afficher le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
    if (modal) {
        modal.show();
    } else {
        const newModal = new bootstrap.Modal(document.getElementById('statusModal'));
        newModal.show();
    }
}

// Fonction pour charger le statut actuel
function loadCurrentStatus(exerciseId) {
    // Ici vous pouvez faire un appel API pour récupérer le statut actuel
    // Pour l'instant, on laisse vide
    document.getElementById('statusSelect').value = '';
}

// Fonction pour sauvegarder le statut
function saveExerciseStatus() {
    const form = document.getElementById('statusForm');
    if (!form) {
        console.error('Formulaire statusForm non trouvé');
        alert('Erreur: Formulaire non trouvé');
        return;
    }
    
    const formData = new FormData(form);
    
    // Logs de débogage
    console.log('Données du formulaire:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // Validation
    if (!formData.get('status')) {
        alert('Veuillez sélectionner un statut');
        return;
    }
    
    if (!formData.get('exercise_id')) {
        alert('Erreur: ID de l\'exercice manquant');
        return;
    }
    
    // Trouver le bouton de sauvegarde de plusieurs façons
    let saveBtn = document.querySelector('#statusModal .btn-primary');
    if (!saveBtn) {
        saveBtn = document.querySelector('button[onclick="saveExerciseStatus()"]');
    }
    if (!saveBtn) {
        saveBtn = document.querySelector('#statusModal button[type="button"]:last-child');
    }
    
    if (!saveBtn) {
        console.error('Bouton de sauvegarde non trouvé');
        alert('Erreur: Bouton de sauvegarde non trouvé');
        return;
    }
    
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...';
    saveBtn.disabled = true;
    
    // Envoyer la requête
    fetch('/trainings/update-status', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Nettoyer la réponse avant de parser le JSON
        return response.text().then(text => {
            // Supprimer les caractères BOM et espaces en début/fin
            const cleanText = text.trim().replace(/^\uFEFF/, '');
            return JSON.parse(cleanText);
        });
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
            if (modal) {
                modal.hide();
            }
            
            // Afficher un message de succès
            showAlert('Statut mis à jour avec succès', 'success');
            
            // Recharger la page pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('Erreur lors de la mise à jour : ' + (data.message || 'Erreur inconnue'), 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur lors de la sauvegarde', 'danger');
    })
    .finally(() => {
        // Restaurer le bouton seulement s'il existe
        if (saveBtn) {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
}

// Fonction pour afficher des alertes
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer l'alerte en haut de la page
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Gestion de l'édition des notes
function initializeNotesEditing() {
    const editNotesBtn = document.getElementById('editNotesBtn');
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const cancelNotesBtn = document.getElementById('cancelNotesBtn');
    const notesDisplay = document.getElementById('notesDisplay');
    const notesEdit = document.getElementById('notesEdit');
    const notesTextarea = document.getElementById('notesTextarea');
    
    if (!editNotesBtn || !saveNotesBtn || !cancelNotesBtn || !notesDisplay || !notesEdit || !notesTextarea) {
        return; // Éléments non trouvés, probablement pas sur la bonne page
    }
    
    // Récupérer l'ID de session depuis un attribut data ou une variable globale
    const sessionId = window.sessionId || document.querySelector('[data-session-id]')?.getAttribute('data-session-id');
    
    if (editNotesBtn) {
        editNotesBtn.addEventListener('click', function() {
            notesDisplay.style.display = 'none';
            notesEdit.style.display = 'block';
            notesTextarea.focus();
        });
    }
    
    if (cancelNotesBtn) {
        cancelNotesBtn.addEventListener('click', function() {
            notesDisplay.style.display = 'block';
            notesEdit.style.display = 'none';
            // Restaurer le contenu original
            notesTextarea.value = notesTextarea.defaultValue;
        });
    }
    
    if (saveNotesBtn) {
        saveNotesBtn.addEventListener('click', function() {
            const notes = notesTextarea.value;
            
            // Afficher un indicateur de chargement
            saveNotesBtn.disabled = true;
            saveNotesBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Enregistrement...';
            
            // Envoyer la requête AJAX
            fetch('/trainings/update-notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    notes: notes
                })
            })
            .then(response => {
                // Nettoyer la réponse des caractères BOM
                return response.text().then(text => {
                    // Supprimer les caractères BOM et espaces invisibles
                    const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
                    
                    // Essayer de parser comme JSON
                    try {
                        return JSON.parse(cleanText);
                    } catch (e) {
                        console.error('Erreur de parsing JSON:', e);
                        console.error('Réponse reçue:', cleanText);
                        
                        // Si ce n'est pas du JSON valide, retourner une erreur
                        return { 
                            success: false, 
                            message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 100) 
                        };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    if (notes.trim() === '') {
                        notesDisplay.innerHTML = '<p class="text-muted mt-1">Aucune note</p>';
                        editNotesBtn.innerHTML = '<i class="fas fa-plus"></i> Ajouter des notes';
                    } else {
                        notesDisplay.innerHTML = '<p class="mt-1">' + notes.replace(/\n/g, '<br>').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                        editNotesBtn.innerHTML = '<i class="fas fa-edit"></i> Modifier';
                    }
                    
                    // Revenir à l'affichage normal
                    notesDisplay.style.display = 'block';
                    notesEdit.style.display = 'none';
                    
                    // Afficher un message de succès
                    showAlert('Notes mises à jour avec succès', 'success');
                } else {
                    showAlert(data.message || 'Erreur lors de la mise à jour des notes', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('Erreur de connexion au serveur', 'danger');
            })
            .finally(() => {
                saveNotesBtn.disabled = false;
                saveNotesBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
            });
        });
    }
}

// Confirmer la suppression d'une session
function confirmDeleteSession() {
    const sessionId = this.getAttribute('data-session-id');
    
    if (!sessionId) {
        showAlert('Erreur: ID de session manquant', 'danger');
        return;
    }
    
    if (confirm('Êtes-vous sûr de vouloir supprimer cette session d\'entraînement ? Cette action est irréversible.')) {
        deleteSession(sessionId);
    }
}

// Supprimer une session
function deleteSession(sessionId) {
    const deleteBtn = document.getElementById('deleteSessionBtn');
    const originalText = deleteBtn.innerHTML;
    
    // Afficher un indicateur de chargement
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Suppression...';
    
    fetch('/trainings/delete-session', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session_id: sessionId
        })
    })
    .then(response => {
        console.log('Réponse reçue:', response);
        console.log('Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
        }
        
        return response.text().then(text => {
            console.log('Texte brut reçu:', text);
            const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
            console.log('Texte nettoyé:', cleanText);
            
            try {
                return JSON.parse(cleanText);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                console.error('Réponse reçue:', cleanText);
                return { success: false, message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 200) };
            }
        });
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            showAlert('Session supprimée avec succès', 'success');
            // Rediriger vers la liste des entraînements après un délai
            setTimeout(() => {
                window.location.href = '/trainings';
            }, 1500);
        } else {
            showAlert('Erreur: ' + (data.message || 'Erreur lors de la suppression'), 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        console.error('Type d\'erreur:', error.name);
        console.error('Message d\'erreur:', error.message);
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            showAlert('Erreur de connexion: Impossible de joindre le serveur', 'danger');
        } else if (error.message.includes('HTTP:')) {
            showAlert('Erreur HTTP: ' + error.message, 'danger');
        } else {
            showAlert('Erreur de connexion au serveur: ' + error.message, 'danger');
        }
    })
    .finally(() => {
        // Restaurer le bouton
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalText;
    });
}

// Rendre les fonctions globales
window.startTrainingSession = startTrainingSession;
window.removeVolley = removeVolley;
window.updateExerciseStatus = updateExerciseStatus;
window.saveExerciseStatus = saveExerciseStatus; 