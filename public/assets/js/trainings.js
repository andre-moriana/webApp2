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
    // Utiliser setTimeout pour s'assurer que le DOM est complètement chargé
    setTimeout(function() {
        const userSelect = document.getElementById('userSelect');
        if (userSelect) {
            // Supprimer tous les anciens gestionnaires en clonant l'élément
            const newSelect = userSelect.cloneNode(true);
            userSelect.parentNode.replaceChild(newSelect, userSelect);
            
            // Réattacher le gestionnaire sur le nouvel élément
            const finalSelect = document.getElementById('userSelect');
            if (finalSelect) {
                finalSelect.addEventListener('change', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    // Utiliser la fonction globale handleUserSelectChange
                    handleUserSelectChange(this);
                }, true); // Utiliser capture phase
            }
        }
    }, 100);
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
    const selectedUserId = window.selectedUserId || null;
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
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
        }
        
        return response.text().then(text => {
            const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
            try {
                return JSON.parse(cleanText);
            } catch (e) {
                return { success: false, message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 200) };
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Session sauvegardée avec succès !');
            sessionActive = false;
            trainingSessionModal.hide();
            
            // Recharger la page pour afficher les nouvelles données
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erreur: ' + (data.message || 'Erreur lors de la sauvegarde'));
        }
    })
    .catch(error => {
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

// Gérer la sélection d'utilisateur - Recharger la page avec le paramètre user_id
function handleUserSelection(event) {
    // Empêcher toute autre action
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    const selectedUserId = this.value;
    
    // Construire l'URL avec le paramètre user_id
    let newUrl = '/trainings';
    if (selectedUserId && selectedUserId !== '' && selectedUserId !== 'null' && selectedUserId !== 'undefined') {
        newUrl += '?user_id=' + encodeURIComponent(selectedUserId);
    }
    
    // Recharger la page immédiatement - utiliser window.location.replace pour éviter l'historique
    window.location.replace(newUrl);
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
    const exerciseId = button.getAttribute('data-exercise-id');
    const exerciseTitle = button.getAttribute('data-exercise-title');
    if (!exerciseId || exerciseId === '') {
        alert('Erreur: ID de l\'exercice manquant ou vide');
        return;
    }
    
    updateExerciseStatus(exerciseId, exerciseTitle);
}

// Fonction pour ouvrir le modal de modification de statut
function updateExerciseStatus(exerciseId, exerciseTitle) {
    if (!exerciseId || exerciseId === '' || exerciseId === 'null') {
        alert('Erreur: ID de l\'exercice invalide');
        return;
    }
    
    const exerciseIdField = document.getElementById('statusExerciseId');
    
    if (!exerciseIdField) {
        alert('Erreur: Champ exercice non trouvé');
        return;
    }
    exerciseIdField.value = exerciseId;
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
        alert('Erreur: Formulaire non trouvé');
        return;
    }
    
    const formData = new FormData(form);
    
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
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
        }
        
        return response.text().then(text => {
            const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
            try {
                return JSON.parse(cleanText);
            } catch (e) {
                return { success: false, message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 200) };
            }
        });
    })
    .then(data => {
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

// Fonction globale pour gérer le changement d'utilisateur - recharger la page
function handleUserSelectChange(selectElement) {
    const selectedUserId = selectElement.value;
    let newUrl = '/trainings';
    if (selectedUserId && selectedUserId !== '' && selectedUserId !== 'null' && selectedUserId !== 'undefined') {
        newUrl += '?user_id=' + encodeURIComponent(selectedUserId);
    }
    window.location.href = newUrl;
}

// Rendre les fonctions globales
window.startTrainingSession = startTrainingSession;
window.removeVolley = removeVolley;
window.updateExerciseStatus = updateExerciseStatus;
window.saveExerciseStatus = saveExerciseStatus;
window.handleUserSelectChange = handleUserSelectChange;

// ============================================
// FRISE CHRONOLOGIQUE HORIZONTALE
// ============================================

// Mapping des catégories vers les icônes (identique à l'app mobile)
function getCategoryIcon(category) {
    if (!category) {
        return { name: 'target', color: '#14532d' };
    }
    
    const categoryLower = category.toLowerCase();
    
    // Catégories mentales - utilise une icône représentant le mental
    if (categoryLower.includes('mental') || categoryLower.includes('psycho') || categoryLower.includes('concentration') || categoryLower.includes('mentale')) {
        return { name: 'brain', color: '#8b5cf6' }; // Icône cerveau pour mental en violet
    }
    
    // Catégories physiques
    if (categoryLower.includes('physique') || categoryLower.includes('corps') || categoryLower.includes('souplesse') || categoryLower.includes('force') || categoryLower.includes('physique')) {
        return { name: 'heartbeat', color: '#ef4444' }; // Icône physique en rouge
    }
    
    // Catégories technique
    if (categoryLower.includes('technique') || categoryLower.includes('posture') || categoryLower.includes('geste') || categoryLower.includes('technique')) {
        return { name: 'cog', color: '#3b82f6' }; // Icône technique en bleu
    }
    
    // Catégories préparation
    if (categoryLower.includes('préparation') || categoryLower.includes('preparation') || categoryLower.includes('échauffement') || categoryLower.includes('echauffement')) {
        return { name: 'fire', color: '#f59e0b' }; // Icône préparation en orange
    }
    
    // Catégories récupération
    if (categoryLower.includes('récupération') || categoryLower.includes('recuperation') || categoryLower.includes('repos')) {
        return { name: 'moon-o', color: '#06b6d4' }; // Icône récupération en cyan
    }
    
    // Catégories compétition
    if (categoryLower.includes('compétition') || categoryLower.includes('competition') || categoryLower.includes('match')) {
        return { name: 'trophy', color: '#eab308' }; // Icône compétition en jaune
    }
    
    // Catégorie par défaut
    return { name: 'book', color: '#6b7280' }; // Icône livre en gris
}

// Fonction pour formater la date
function formatTimelineDate(dateString) {
    if (!dateString) return 'Date inconnue';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'Date inconnue';
    }
}

// Fonction pour formater la durée
function formatDuration(minutes) {
    if (!minutes) return '';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return hours > 0 ? `${hours}h${mins.toString().padStart(2, '0')}` : `${mins}min`;
}

// Fonction pour filtrer les notes (retirer les signatures)
function filterNotesForDisplay(notes) {
    if (!notes) return '';
    
    // Retirer tout ce qui contient __SIGNATURES__ et ce qui suit (y compris le JSON)
    let filtered = notes;
    const signaturesIndex = filtered.indexOf('__SIGNATURES__');
    if (signaturesIndex !== -1) {
        filtered = filtered.substring(0, signaturesIndex).trim();
    }
    
    // Retirer les lignes qui mentionnent des informations de signature
    // Exemples: "Signatures: ... et Marqueur ont signé", "Signatures:", etc.
    const lines = filtered.split('\n');
    filtered = lines
        .filter(line => {
            const trimmedLine = line.trim();
            const lowerLine = trimmedLine.toLowerCase();
            
            // Retirer les lignes qui contiennent "Signatures:" (même au milieu) ou "ont signé"
            if (lowerLine.includes('signatures:') || 
                lowerLine.includes('signature:')) {
                return false;
            }
            if (lowerLine.includes('ont signé') || 
                lowerLine.includes('ont signe')) {
                return false;
            }
            
            // Retirer les lignes qui contiennent des données JSON de signature
            if (/^\s*\{["']archer["']|^\s*\{["']scorer["']/i.test(trimmedLine)) {
                return false;
            }
            
            return true;
        })
        .join('\n')
        .trim();
    
    // Nettoyer les virgules et espaces en fin de chaque ligne
    filtered = filtered.replace(/,\s*$/gm, '');
    filtered = filtered.replace(/[,\s]+$/, '');
    
    // Retirer les lignes vides multiples
    filtered = filtered.replace(/\n\s*\n\s*\n/g, '\n\n');
    
    return filtered.trim();
}

// Fonction pour charger les données de la frise chronologique
async function loadTimeline() {
    const timelineContent = document.getElementById('timeline-content');
    if (!timelineContent) return;
    
    // Afficher le loader
    timelineContent.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
            <p>Chargement de la frise chronologique...</p>
        </div>
    `;
    
    try {
        // Récupérer l'ID utilisateur depuis le select ou utiliser l'utilisateur actuel
        const userId = document.getElementById('userSelect')?.value || window.currentUserId || null;
        if (!userId) {
            timelineContent.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                    <p>Aucun utilisateur sélectionné</p>
                </div>
            `;
            return;
        }
        
        // Charger les tirs comptés
        const scoredResponse = await fetch(`/api/scored-trainings?user_id=${userId}`);
        let scoredTrainings = [];
        if (scoredResponse.ok) {
            const scoredData = await scoredResponse.json();
            if (scoredData.success && Array.isArray(scoredData.data)) {
                scoredTrainings = scoredData.data;
            } else if (Array.isArray(scoredData)) {
                scoredTrainings = scoredData;
            }
        }
        
        // Charger toutes les sessions d'entraînement de l'utilisateur
        const sessionsResponse = await fetch(`/api/training/sessions/user/${userId}`);
        let allSessions = [];
        
        if (sessionsResponse.ok) {
            const sessionsData = await sessionsResponse.json();
            console.log('Données sessions reçues:', sessionsData);
            
            // Extraire les sessions de la réponse
            if (sessionsData.success && sessionsData.data) {
                if (Array.isArray(sessionsData.data)) {
                    allSessions = sessionsData.data;
                } else if (sessionsData.data.data && Array.isArray(sessionsData.data.data)) {
                    allSessions = sessionsData.data.data;
                }
            } else if (Array.isArray(sessionsData)) {
                allSessions = sessionsData;
            }
        }
        
        // Récupérer toutes les catégories d'exercices
        // Méthode 1 : Récupérer directement depuis les exercices
        const exercisesResponse = await fetch('/api/exercises');
        let exerciseCategories = {}; // Map exercise_id -> category
        
        if (exercisesResponse.ok) {
            const exercisesData = await exercisesResponse.json();
            console.log('Données exercices reçues:', exercisesData);
            
            let exercises = [];
            if (exercisesData.success && exercisesData.data) {
                if (Array.isArray(exercisesData.data)) {
                    exercises = exercisesData.data;
                } else if (exercisesData.data.data && Array.isArray(exercisesData.data.data)) {
                    exercises = exercisesData.data.data;
                }
            } else if (Array.isArray(exercisesData)) {
                exercises = exercisesData;
            }
            
            // Créer un map des catégories par ID d'exercice
            exercises.forEach(exercise => {
                const exerciseId = exercise.id || exercise._id;
                const category = exercise.category;
                if (exerciseId && category) {
                    exerciseCategories[exerciseId] = category;
                }
            });
        }
        
        // Méthode 2 : Compléter avec les catégories depuis les progress (fallback)
        if (Object.keys(exerciseCategories).length === 0) {
            const progressResponse = await fetch('/api/training/progress');
            if (progressResponse.ok) {
                const progressData = await progressResponse.json();
                if (progressData.success && Array.isArray(progressData.data)) {
                    // Pour chaque progression, récupérer la catégorie
                    for (const progress of progressData.data) {
                        try {
                            const dashboardResponse = await fetch(`/api/training/dashboard/${progress.exercise_sheet_id}?user_id=${userId}`);
                            if (dashboardResponse.ok) {
                                const dashboardData = await dashboardResponse.json();
                                if (dashboardData.success && dashboardData.data) {
                                    let category = dashboardData.data?.progress?.exercise_sheet?.category;
                                    if (!category && dashboardData.data?.data?.progress?.exercise_sheet?.category) {
                                        category = dashboardData.data.data.progress.exercise_sheet.category;
                                    }
                                    if (category && !exerciseCategories[progress.exercise_sheet_id]) {
                                        exerciseCategories[progress.exercise_sheet_id] = category;
                                    }
                                }
                            }
                        } catch (error) {
                            console.error(`Erreur lors de la récupération de la catégorie pour l'exercice ${progress.exercise_sheet_id}:`, error);
                        }
                    }
                }
            }
        }
        
        console.log('Map des catégories:', exerciseCategories);
        
        // Filtrer les sessions pour l'utilisateur et ajouter les catégories
        // Pour chaque session, récupérer la catégorie si elle n'est pas déjà dans le map
        const allSessionsWithCategory = await Promise.all(
            allSessions
                .filter(session => session.user_id == userId || session.user_id?.toString() === userId.toString())
                .map(async (session) => {
                    const exerciseId = session.exercise_sheet_id || session.exercise_id;
                    let category = exerciseCategories[exerciseId];
                    
                    // Si la catégorie n'est pas trouvée, essayer de la récupérer directement depuis l'exercise sheet
                    if (!category && exerciseId) {
                        try {
                            const exerciseResponse = await fetch(`/api/exercises/${exerciseId}`);
                            if (exerciseResponse.ok) {
                                const exerciseData = await exerciseResponse.json();
                                if (exerciseData.success && exerciseData.data) {
                                    category = exerciseData.data.category || exerciseData.data.data?.category;
                                    if (category) {
                                        exerciseCategories[exerciseId] = category;
                                    }
                                }
                            }
                        } catch (error) {
                            console.error(`Erreur lors de la récupération de la catégorie pour l'exercice ${exerciseId}:`, error);
                        }
                    }
                    
                    return {
                        session,
                        category: category || null
                    };
                })
        );
        
        console.log('Sessions avec catégories:', allSessionsWithCategory);
        console.log('Map des catégories final:', exerciseCategories);
        
        // Fusionner et trier par date
        const items = [
            ...scoredTrainings.map((training) => ({
                id: `scored-${training.id || training._id}`,
                type: 'scored',
                date: new Date(training.start_date || training.created_at || training.date),
                data: training,
                category: undefined
            })),
            ...allSessionsWithCategory.map(({ session, category }) => ({
                id: `training-${session.id || session._id}`,
                type: 'training',
                date: new Date(session.start_date || session.created_at || session.date),
                data: session,
                category
            }))
        ];
        
        // Trier par date décroissante (plus récent en premier)
        items.sort((a, b) => b.date.getTime() - a.date.getTime());
        
        // Afficher la frise
        displayTimeline(items);
        
    } catch (error) {
        console.error('Erreur lors du chargement de la frise chronologique:', error);
        timelineContent.innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p>Erreur lors du chargement de la frise chronologique</p>
            </div>
        `;
    }
}

// Fonction pour afficher la frise chronologique
function displayTimeline(items) {
    const timelineContent = document.getElementById('timeline-content');
    if (!timelineContent) return;
    
    if (items.length === 0) {
        timelineContent.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-calendar fa-2x mb-2"></i>
                <p>Aucun entraînement enregistré</p>
            </div>
        `;
        return;
    }
    
    let timelineHtml = '';
    
    items.forEach((item, index) => {
        const isTraining = item.type === 'training';
        const training = isTraining ? item.data : null;
        const scored = !isTraining ? item.data : null;
        
        timelineHtml += `
            <div class="timeline-item" data-item-id="${item.id}">
                <!-- Ligne horizontale (sauf pour le dernier) -->
                ${index < items.length - 1 ? '<div class="timeline-line"></div>' : ''}
                
                <!-- Point de la frise -->
                ${isTraining ? (() => {
                    const categoryIcon = getCategoryIcon(item.category);
                    console.log('Rendu point frise - catégorie:', item.category, 'icône:', categoryIcon.name, 'couleur:', categoryIcon.color);
                    return `
                        <div class="timeline-dot" 
                             style="background-color: ${categoryIcon.color || '#14532d'}">
                            <i class="fas fa-${categoryIcon.name}"></i>
                        </div>
                    `;
                })() : (() => {
                    // Vérifier si le tir compté provient d'une feuille de marque
                    // MySQL retourne 0/1 au lieu de true/false pour les BOOLEAN
                    const isFromScoreSheet = Boolean(scored?.is_score_sheet);
                    return `
                        <div class="timeline-dot timeline-dot-scored">
                            <i class="fas fa-${isFromScoreSheet ? 'trophy' : 'bullseye'}"></i>
                        </div>
                    `;
                })()}
                
                <!-- Contenu de l'item -->
                <div class="timeline-card">
                    <div class="timeline-header">
                        <span class="timeline-date">${formatTimelineDate(item.date)}</span>
                        <span class="timeline-badge ${isTraining ? 'timeline-badge-training' : 'timeline-badge-scored'}">
                            ${isTraining ? 'Entraînement' : 'Tir compté'}
                        </span>
                    </div>
                    
                    ${isTraining && training ? `
                        <div class="timeline-details">
                            <div class="timeline-stat-row">
                                <i class="fas fa-bullseye"></i>
                                <span>${training.total_arrows || 0} flèches</span>
                                <i class="fas fa-arrow-right"></i>
                                <span>${training.total_ends || 0} volées</span>
                            </div>
                            ${training.duration_minutes > 0 ? `
                                <div class="timeline-stat-row">
                                    <i class="fas fa-clock"></i>
                                    <span>${formatDuration(training.duration_minutes)}</span>
                                </div>
                            ` : ''}
                            ${training.notes ? `
                                <div class="timeline-notes">${escapeHtml(filterNotesForDisplay(training.notes))}</div>
                            ` : ''}
                        </div>
                    ` : scored ? `
                        <div class="timeline-details">
                            <div class="timeline-title">${escapeHtml(scored.title || 'Tir compté')}</div>
                            <div class="timeline-stat-row">
                                <i class="fas fa-trophy"></i>
                                <span>Score: ${scored.total_score || 0} / ${(scored.total_arrows || 0) * 10}</span>
                                ${scored.average_score !== undefined && scored.average_score !== null ? `
                                    <span>(${Number(scored.average_score).toFixed(1)} moyenne)</span>
                                ` : ''}
                            </div>
                            <div class="timeline-stat-row">
                                <i class="fas fa-bullseye"></i>
                                <span>${scored.total_arrows || 0} flèches - ${scored.total_ends || 0} volées</span>
                            </div>
                            ${scored.shooting_type ? `
                                <div class="timeline-stat-row">
                                    <i class="fas fa-tag"></i>
                                    <span>${escapeHtml(scored.shooting_type)}</span>
                                </div>
                            ` : ''}
                            ${scored.notes ? `
                                <div class="timeline-notes">${escapeHtml(filterNotesForDisplay(scored.notes))}</div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    timelineContent.innerHTML = timelineHtml;
    
    // Mettre à jour l'état des flèches après l'affichage
    setTimeout(() => {
        updateTimelineArrows();
    }, 100);
}

// Fonction pour échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction pour faire défiler la frise avec les flèches
function scrollTimeline(direction) {
    const timelineContainer = document.getElementById('timeline-container');
    if (!timelineContainer) return;
    
    const scrollAmount = 320; // Largeur d'un item + marge
    const currentScroll = timelineContainer.scrollLeft;
    const maxScroll = timelineContainer.scrollWidth - timelineContainer.clientWidth;
    
    if (direction === 'left') {
        timelineContainer.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else if (direction === 'right') {
        timelineContainer.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
    
    // Mettre à jour l'état des boutons après un court délai
    setTimeout(() => {
        updateTimelineArrows();
    }, 100);
}

// Fonction pour mettre à jour l'état des boutons de navigation
function updateTimelineArrows() {
    const timelineContainer = document.getElementById('timeline-container');
    const arrowLeft = document.getElementById('timeline-arrow-left');
    const arrowRight = document.getElementById('timeline-arrow-right');
    
    if (!timelineContainer || !arrowLeft || !arrowRight) return;
    
    const currentScroll = timelineContainer.scrollLeft;
    const maxScroll = timelineContainer.scrollWidth - timelineContainer.clientWidth;
    
    // Désactiver la flèche gauche si on est au début
    arrowLeft.disabled = currentScroll <= 0;
    
    // Désactiver la flèche droite si on est à la fin
    arrowRight.disabled = currentScroll >= maxScroll - 1; // -1 pour gérer les erreurs d'arrondi
}

// Charger la frise au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Charger la frise après un court délai pour laisser le temps aux autres scripts de se charger
    setTimeout(() => {
        loadTimeline();
    }, 500);
    
    // Note: Le changement d'utilisateur recharge maintenant la page complète
    // donc pas besoin de recharger la frise via AJAX
    
    // Écouter les événements de scroll pour mettre à jour les flèches
    const timelineContainer = document.getElementById('timeline-container');
    if (timelineContainer) {
        timelineContainer.addEventListener('scroll', updateTimelineArrows);
        
        // Observer les changements de taille pour mettre à jour les flèches
        const resizeObserver = new ResizeObserver(() => {
            updateTimelineArrows();
        });
        resizeObserver.observe(timelineContainer);
    }
});