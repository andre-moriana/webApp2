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
    
    fetch('/webapp/trainings/save-session', {
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
        return response.text().then(text => {
            const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
            try {
                return JSON.parse(cleanText);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                console.error('Réponse reçue:', cleanText);
                return { success: false, message: 'Réponse invalide du serveur' };
            }
        });
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            alert('Session sauvegardée avec succès !');
            sessionActive = false;
            trainingSessionModal.hide();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erreur: ' + (data.message || 'Erreur lors de la sauvegarde'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
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

// Fonction pour ouvrir le modal de modification de statut
function updateExerciseStatus(exerciseId, exerciseTitle) {
    document.getElementById('statusExerciseId').value = exerciseId;
    document.getElementById('statusModalLabel').textContent = 'Modifier le statut de : ' + exerciseTitle;
    
    // Charger le statut actuel de l'exercice
    loadCurrentStatus(exerciseId);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// Fonction pour charger le statut actuel
function loadCurrentStatus(exerciseId) {
    // Ici vous pouvez faire un appel API pour récupérer le statut actuel
    // Pour l'instant, on laisse vide
    document.getElementById('statusSelect').value = '';
    document.getElementById('statusNotes').value = '';
}

// Fonction pour sauvegarder le statut
function saveExerciseStatus() {
    const form = document.getElementById('statusForm');
    const formData = new FormData(form);
    
    // Validation
    if (!formData.get('status')) {
        alert('Veuillez sélectionner un statut');
        return;
    }
    
    // Afficher un indicateur de chargement
    const saveBtn = document.querySelector('#statusModal .btn-primary');
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
            modal.hide();
            
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
        // Restaurer le bouton
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
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

// Rendre les fonctions globales
window.startTrainingSession = startTrainingSession;
window.removeVolley = removeVolley;
window.updateExerciseStatus = updateExerciseStatus;
window.saveExerciseStatus = saveExerciseStatus; 