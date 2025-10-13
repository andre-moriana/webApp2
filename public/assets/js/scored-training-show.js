/**
 * JavaScript pour la page de détails des tirs comptés
 * Gestion des modales, graphiques et interactions
 */

// scored-training-show.js chargé

// Variables globales
let trainingId, arrowsPerEnd, currentEnds, totalEnds;

// Variables pour mémoriser les valeurs du formulaire
let savedTargetCategory = '';
let savedShootingPosition = '';
let savedScoreMode = 'table'; // Mémoriser le mode de saisie sélectionné

// Variables pour la cible interactive
let targetScores = [];
let isZoomed = false;
let currentArrowIndex = 0;
let isDragging = false;
let currentDragScore = 0;
let zoomCircle = null;
let lastClickTime = 0;
let clickDebounceDelay = 300; // 300ms de délai entre les clics
let justFinishedDragging = false; // Flag pour éviter le clic après drag

// Initialiser les variables depuis les données PHP
function initializeTrainingData() {
    // Récupérer l'ID depuis l'URL (plus fiable que window.scoredTrainingData)
    const pathParts = window.location.pathname.split('/');
    const idFromUrl = pathParts[pathParts.length - 1];
    trainingId = parseInt(idFromUrl) || window.scoredTrainingData?.id || 0;
    
    arrowsPerEnd = window.scoredTrainingData?.arrows_per_end || 3;
    currentEnds = window.endsData?.length || 0;
    totalEnds = window.scoredTrainingData?.total_ends || 0;
 
}

// Fonction pour ouvrir la modale
function openModal() {
    const modal = document.getElementById('addEndModal');
    
    if (modal) {
        // Méthode 1: Bootstrap 5
        if (typeof bootstrap !== 'undefined') {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            // Méthode 2: jQuery si disponible
            if (typeof $ !== 'undefined') {
                $(modal).modal('show');
            } else {
                // Méthode 3: JavaScript pur
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
        }
    }
}


// Fonction pour gérer la visibilité des boutons selon le numéro de volée
function updateButtonVisibility(endNumber) {
    const continueButton = document.querySelector('button[onclick="saveEnd()"]');
    const finishButton = document.querySelector('button[onclick="saveEndAndClose()"]');
    
    // Si la volée en cours est supérieure au maximum, cacher le bouton "Enregistrer et continuer"
    if (totalEnds > 0 && endNumber > totalEnds) {
        if (continueButton) {
            continueButton.style.display = 'none';
        }
        if (finishButton) {
            finishButton.style.display = 'inline-block';
        }
    } else {
        // Afficher les deux boutons si on est dans la limite
        if (continueButton) {
            continueButton.style.display = 'inline-block';
            // Restaurer le texte et la couleur originales
            if (continueButton.getAttribute('data-original-text')) {
                continueButton.textContent = continueButton.getAttribute('data-original-text');
                continueButton.classList.remove('btn-warning');
                continueButton.classList.add('btn-success');
            }
        }
        if (finishButton) {
            finishButton.style.display = 'inline-block';
        }
    }
}

// Fonction pour obtenir les scores possibles selon le type de tir
function getPossibleScores(shootingType, arrowNumber = 1) {
    switch (shootingType) {
        case 'TAE':
        case 'Salle':
            return [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0];
        case '3D':
            return [11, 10, 8, 5, 0];
        case 'Nature':
            // Pour le tir Nature, les scores varient selon la flèche
            if (arrowNumber === 1) {
                return [20, 15, 0];
            } else {
                return [15, 10, 0];
            }
        case 'Campagne':
            return [6, 5, 4, 3, 2, 1, 0];
        case 'Libre':
            return [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0];
        default:
            return [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0];
    }
}

// Initialiser les champs de score
function initializeScoreFields() {
    const container = document.getElementById('scoresContainer');
    
    if (!container) {
        console.error('Container scoresContainer non trouvé');
        return;
    }
    
    container.innerHTML = '';
    
    const shootingType = window.scoredTrainingData?.shooting_type || 'Libre';
    
    for (let i = 1; i <= arrowsPerEnd; i++) {
        const col = document.createElement('div');
        col.className = 'col-md-2 mb-2';
        
        const possibleScores = getPossibleScores(shootingType, i);
        const options = possibleScores.map(score => 
            `<option value="${score}">${score}</option>`
        ).join('');
        
        col.innerHTML = `
            <label class="form-label">Flèche ${i}</label>
            <select class="form-select" name="scores[]" required>
                <option value="0">Sélectionner</option>
                ${options}
            </select>
        `;
        container.appendChild(col);
    }
}

// Fonction pour ajouter une volée au tableau localement
function addEndToTable(endData) {
    // Calculer le total et la moyenne
    const totalScore = endData.shots.reduce((sum, shot) => sum + shot.score, 0);
    const average = endData.shots.length > 0 ? (totalScore / endData.shots.length).toFixed(1) : 0;
    
    
    // Vérifier si le tableau existe, sinon le créer
    let tbody = document.querySelector('.table-ends tbody');
    
    if (!tbody) {
        // Remplacer le message "Aucune volée enregistrée" par le tableau
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.outerHTML = `
                <div class="table-responsive">
                    <table class="table table-hover table-ends">
                        <thead>
                            <tr>
                                <th>Volée</th>
                                <th>Scores</th>
                                <th>Total</th>
                                <th>Moyenne</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `;
            tbody = document.querySelector('.table-ends tbody');
        }
    }
    
    if (tbody) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="end-info">
                    <div class="end-number">Volée ${endData.end_number}</div>
                    <div class="end-date">${new Date().toLocaleString('fr-FR')}</div>
                </div>
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    ${endData.shots.map(shot => `<span class="badge bg-primary score-badge">${shot.score}</span>`).join('')}
                </div>
            </td>
            <td>
                <strong class="total-score">${totalScore}</strong>
            </td>
            <td>
                ${average}
            </td>
            <td>
                ${endData.comment ? `<small class="text-muted comment-text">${endData.comment}</small>` : '<span class="text-muted">-</span>'}
            </td>
        `;
        tbody.appendChild(row);
    }
    
    // Mettre à jour les statistiques
    updateStats();
    
    // Mettre à jour le compteur de volées
    currentEnds++;
}

// Fonction pour mettre à jour les statistiques
function updateStats() {
    const rows = document.querySelectorAll('.table-ends tbody tr');
    const totalEnds = rows.length;
    const totalScore = Array.from(rows).reduce((sum, row) => {
        const scoreCell = row.querySelector('td:nth-child(3) strong');
        return sum + (parseInt(scoreCell?.textContent) || 0);
    }, 0);
    
    // Calculer le nombre total de flèches pour la moyenne
    const totalArrows = Array.from(rows).reduce((sum, row) => {
        const badges = row.querySelectorAll('td:nth-child(2) .badge');
        return sum + badges.length;
    }, 0);
    
    const average = totalArrows > 0 ? (totalScore / totalArrows).toFixed(1) : 0;
    
    // Mettre à jour l'affichage des statistiques si les éléments existent
    const totalEndsElement = document.querySelector('.total-ends');
    const totalScoreElement = document.querySelector('.total-score');
    const averageElement = document.querySelector('.average-score');
    
    if (totalEndsElement) {
        totalEndsElement.textContent = totalEnds;
    }
    if (totalScoreElement) {
        totalScoreElement.textContent = totalScore;
    }
    if (averageElement) {
        averageElement.textContent = average;
    }
}

// Fonctions de gestion des volées
function addEnd() {
    // Vérifier si on a déjà atteint le maximum de volées
    const existingRows = document.querySelectorAll('.table-ends tbody tr').length;
    if (totalEnds > 0 && existingRows >= totalEnds) {
        alert(`Vous avez déjà atteint le nombre maximum de volées prévues (${totalEnds}). Veuillez terminer le tir.`);
        return;
    }
    
    // Vérifier si les éléments existent
    const modalElement = document.getElementById('addEndModal');
    const containerElement = document.getElementById('scoresContainer');
    
    if (!modalElement) {
        console.error('Modal addEndModal non trouvée');
        return;
    }
    
    if (!containerElement) {
        console.error('Container scoresContainer non trouvé');
        return;
    }
    
    // Restaurer les valeurs mémorisées AVANT d'initialiser les champs
    const form = document.getElementById('addEndForm');
    let endNumberInput = null;
    
    if (form) {
        const targetCategorySelect = form.querySelector('select[name="target_category"]');
        const shootingPositionSelect = form.querySelector('select[name="shooting_position"]');
        endNumberInput = form.querySelector('input[name="end_number"]');
        
        if (targetCategorySelect && savedTargetCategory) {
            targetCategorySelect.value = savedTargetCategory;
        }
        if (shootingPositionSelect && savedShootingPosition) {
            shootingPositionSelect.value = savedShootingPosition;
        }
        
        // Restaurer le mode de saisie mémorisé AVANT d'initialiser les champs
        if (savedScoreMode) {
            const tableMode = document.getElementById('tableMode');
            const targetMode = document.getElementById('targetMode');
            if (savedScoreMode === 'table' && tableMode) {
                tableMode.checked = true;
            } else if (savedScoreMode === 'target' && targetMode) {
                targetMode.checked = true;
            }
            // Appliquer le mode sélectionné
            toggleScoreMode();
        }
    }
    
    // Initialiser les champs selon le mode sélectionné
    if (savedScoreMode === 'table') {
        initializeScoreFields();
    } else {
        // Initialiser la cible interactive
        targetScores = new Array(arrowsPerEnd).fill(null);
        updateScoresDisplay();
    }
    
    // Initialiser le numéro de volée si ce n'est pas déjà fait
    if (endNumberInput && !endNumberInput.value) {
        // Utiliser le nombre de lignes dans le tableau + 1
        const existingRows = document.querySelectorAll('.table-ends tbody tr').length;
        endNumberInput.value = existingRows + 1;
    }
    
    // Mettre à jour la visibilité des boutons selon le numéro de volée
    if (endNumberInput) {
        const endNumber = parseInt(endNumberInput.value) || 1;
        updateButtonVisibility(endNumber);
    }
    
    // Ajouter un événement pour écouter les changements du numéro de volée
    if (endNumberInput) {
        endNumberInput.addEventListener('input', function() {
            const endNumber = parseInt(this.value) || 1;
            updateButtonVisibility(endNumber);
        });
    }
    
    // Ajouter des événements pour mémoriser le mode de saisie en temps réel
    const tableMode = document.getElementById('tableMode');
    const targetMode = document.getElementById('targetMode');
    if (tableMode) {
        tableMode.addEventListener('change', function() {
            if (this.checked) {
                savedScoreMode = 'table';
            }
        });
    }
    if (targetMode) {
        targetMode.addEventListener('change', function() {
            if (this.checked) {
                savedScoreMode = 'target';
            }
        });
    }
    
    // Vérifier si Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas disponible');
        // Essayer avec jQuery si disponible
        if (typeof $ !== 'undefined') {
            $(modalElement).modal('show');
        } else {
            console.error('Ni Bootstrap ni jQuery ne sont disponibles');
            return;
        }
    } else {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function saveEnd() {
    const form = document.getElementById('addEndForm');
    if (!form) {
        return;
    }
    
    const formData = new FormData(form);
    
    let scores = [];
    
    // Vérifier le mode de saisie sélectionné
    const tableMode = document.getElementById('tableMode');
    const targetMode = document.getElementById('targetMode');
    
    if (tableMode && tableMode.checked) {
        // Mode tableau
        const scoreInputs = form.querySelectorAll('select[name="scores[]"]');
        scoreInputs.forEach((select, index) => {
            const value = parseInt(select.value) || 0;
            scores.push(value);
        });
    } else if (targetMode && targetMode.checked) {
        // Mode cible interactive
        scores = getTargetScores();
        
        // Compléter avec des 0 si nécessaire
        while (scores.length < arrowsPerEnd) {
            scores.push(0);
        }
    } else {
        alert('Veuillez sélectionner un mode de saisie');
        return;
    }
    
    // Calculer le total des scores
    const totalScore = scores.reduce((sum, score) => sum + score, 0);
    
    // Vérifier si des scores valides ont été saisis
    if (totalScore === 0) {
        alert('Veuillez saisir au moins un score avant d\'enregistrer');
        return;
    }
    
    // Transformer les scores en structure attendue par l'API
    const shots = scores.map((score, index) => ({
        arrow_number: index + 1,
        score: score
    }));
    
    const endData = {
        end_number: parseInt(formData.get('end_number')),
        target_category: formData.get('target_category'),
        shooting_position: formData.get('shooting_position'),
        comment: formData.get('comment'),
        shots: shots,  // Structure correcte avec arrow_number et score
        total_score: totalScore  // Ajouter le total calculé
    };
    // Afficher un indicateur de chargement
    const submitBtn = form.querySelector('button[onclick="saveEnd()"]');
    let originalText = '';
    if (submitBtn) {
        originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
        submitBtn.disabled = true;
    }
    
    // Utiliser l'API locale qui fait le pont vers l'API externe
    fetch(`/scored-trainings/${trainingId}/ends`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(endData)
    })
    .then(response => {
        return response.json();
    })
    .then(result => {
       if (result.success) {
            
            // Ajouter la volée au tableau localement en utilisant les données du serveur
            if (result.data && result.data.end) {
                addEndToTable(result.data.end);
            } else if (result.end) {
                addEndToTable(result.end);
            } else {
                // Fallback sur les données locales si le serveur ne retourne pas les données
                addEndToTable(endData);
            }
            
            // Mémoriser les valeurs dans les variables globales
            const targetCategorySelect = form.querySelector('select[name="target_category"]');
            const shootingPositionSelect = form.querySelector('select[name="shooting_position"]');
            const endNumberInput = form.querySelector('input[name="end_number"]');
            
            if (targetCategorySelect) {
                savedTargetCategory = targetCategorySelect.value;
            }
            if (shootingPositionSelect) {
                savedShootingPosition = shootingPositionSelect.value;
            }
            
            // Mémoriser le mode de saisie sélectionné
            const tableMode = document.getElementById('tableMode');
            const targetMode = document.getElementById('targetMode');
            if (tableMode && tableMode.checked) {
                savedScoreMode = 'table';
            } else if (targetMode && targetMode.checked) {
                savedScoreMode = 'target';
            }
            
            // Mémoriser et incrémenter le numéro de volée
            let nextEndNumber = 1;
            if (endNumberInput) {
                const currentNumber = parseInt(endNumberInput.value) || 1;
                nextEndNumber = currentNumber + 1;
            }
            
            // Vider le formulaire pour la volée suivante
            form.reset();
            
            // Restaurer les valeurs mémorisées
            if (targetCategorySelect && savedTargetCategory) {
                targetCategorySelect.value = savedTargetCategory;
            }
            if (shootingPositionSelect && savedShootingPosition) {
                shootingPositionSelect.value = savedShootingPosition;
            }
            if (endNumberInput) {
                endNumberInput.value = nextEndNumber;
            }
            
            // Restaurer le mode de saisie mémorisé APRÈS le reset
            if (savedScoreMode) {
                const tableMode = document.getElementById('tableMode');
                const targetMode = document.getElementById('targetMode');
                if (savedScoreMode === 'table' && tableMode) {
                    tableMode.checked = true;
                } else if (savedScoreMode === 'target' && targetMode) {
                    targetMode.checked = true;
                }
                // Appliquer le mode sélectionné
                toggleScoreMode();
            }
            
            // Réinitialiser les champs de score selon le mode
            if (savedScoreMode === 'table') {
                initializeScoreFields();
            } else {
                // Réinitialiser la cible interactive
                targetScores = new Array(arrowsPerEnd).fill(null);
                updateScoresDisplay();
            }
            
            // Mettre à jour la visibilité des boutons pour la prochaine volée
            updateButtonVisibility(nextEndNumber);
            
            // Afficher un message de succès
            if (submitBtn) {
                const successText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Sauvegardé !';
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-success');
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('btn-success');
                    submitBtn.classList.add('btn-primary');
                    submitBtn.disabled = false;
                }, 2000);
            }
            
        } else {
            console.error('❌ Erreur lors de la sauvegarde:', result);
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
            
            // Réactiver le bouton en cas d'erreur
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la requête:', error);
        alert('Erreur lors de l\'ajout de la volée');
        
        // Réactiver le bouton en cas d'erreur
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function saveEndAndClose() {
    const form = document.getElementById('addEndForm');
    if (!form) {
        console.error('❌ Formulaire addEndForm non trouvé');
        return;
    }
    const formData = new FormData(form);
    
    let scores = [];
    let hasValidScores = false;
    
    // Vérifier le mode de saisie sélectionné
    const tableMode = document.getElementById('tableMode');
    const targetMode = document.getElementById('targetMode');
    
    if (tableMode && tableMode.checked) {
        // Mode tableau
        const scoreInputs = form.querySelectorAll('select[name="scores[]"]');
        scoreInputs.forEach(select => {
            const value = parseInt(select.value) || 0;
            scores.push(value);
            if (value > 0) {
                hasValidScores = true;
            }
        });
    } else if (targetMode && targetMode.checked) {
        // Mode cible interactive
        scores = getTargetScores();
        
        // Compléter avec des 0 si nécessaire
        while (scores.length < arrowsPerEnd) {
            scores.push(0);
        }
        
        // Vérifier s'il y a des scores valides
        hasValidScores = scores.some(score => score > 0);
    } else {
        alert('Veuillez sélectionner un mode de saisie');
        return;
    }
    
    // Calculer le total des scores
    const totalScore = scores.reduce((sum, score) => sum + score, 0);
    
    // Si aucun score valide n'a été saisi, fermer la modal et ouvrir la finalisation
    if (!hasValidScores || totalScore === 0) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addEndModal'));
        if (modal) {
            modal.hide();
        }
        
        // Ouvrir directement la modal de finalisation
        setTimeout(() => {
            endTraining();
        }, 500);
        return;
    }
    
    // Transformer les scores en structure attendue par l'API
    const shots = scores.map((score, index) => ({
        arrow_number: index + 1,
        score: score
    }));
    
    const endData = {
        end_number: parseInt(formData.get('end_number')),
        target_category: formData.get('target_category'),
        shooting_position: formData.get('shooting_position'),
        comment: formData.get('comment'),
        shots: shots,  // Structure correcte avec arrow_number et score
        total_score: totalScore  // Ajouter le total calculé
    };
    
    // Utiliser l'API locale qui fait le pont vers l'API externe
    fetch(`/scored-trainings/${trainingId}/ends`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(endData)
    })
    .then(response => {
        return response.json();
    })
    .then(result => {
        if (result.success) {
            // Mémoriser le mode de saisie avant de fermer la modal
            const tableMode = document.getElementById('tableMode');
            const targetMode = document.getElementById('targetMode');
            if (tableMode && tableMode.checked) {
                savedScoreMode = 'table';
            } else if (targetMode && targetMode.checked) {
                savedScoreMode = 'target';
            }
            
            // Fermer la modale d'ajout de volée
            const addEndModal = bootstrap.Modal.getInstance(document.getElementById('addEndModal'));
            if (addEndModal) {
                addEndModal.hide();
            }
            // Ouvrir la modal de finalisation du tir
            setTimeout(() => {
                endTraining();
            }, 500);
        } else {
            console.error('❌ Erreur lors de la sauvegarde:', result);
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la requête:', error);
        alert('Erreur lors de l\'ajout de la volée');
    });
}

// Fonctions de gestion du tir compté
function endTraining() {
    // S'assurer que les données sont initialisées
    if (!trainingId || trainingId === 0) {
        initializeTrainingData();
    }
    
    // Ajouter la fonctionnalité de capture de cible à la modal
    addTargetCaptureToFinalModal();
    
    const modal = new bootstrap.Modal(document.getElementById('endTrainingModal'));
    modal.show();
}

function confirmEndTraining() {
    const form = document.getElementById('endTrainingForm');
    if (!form) {
        console.error('❌ Formulaire de finalisation non trouvé');
        return;
    }
    
    // Vérifier que trainingId est défini
    if (!trainingId || trainingId === 0) {
        console.error('❌ trainingId non défini:', trainingId);
        alert('Erreur: ID du tir compté non trouvé');
        return;
    }
    
    const formData = new FormData(form);
    
    // Récupérer les données de l'image
    const targetImageData = formData.get('target_image') || '';
    console.log('Données de l\'image récupérées:', targetImageData ? 'Présentes (' + targetImageData.length + ' caractères)' : 'Absentes');
    
    const data = {
        training_id: trainingId,
        notes: formData.get('final_notes') || '',
        target_image: targetImageData
    };
    
    console.log('Données à envoyer:', {
        training_id: data.training_id,
        notes: data.notes,
        target_image: data.target_image ? 'Présente' : 'Absente'
    });
    
    // Récupérer le user_id depuis l'URL si présent
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('user_id');
    let url = `/scored-trainings/${trainingId}/end`;
    if (userId) {
        url += `?user_id=${userId}`;
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert('Tir compté finalisé avec succès ! La page va se recharger.');
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            console.error('❌ Erreur lors de la finalisation:', result);
            console.error('❌ Message d\'erreur:', result.message);
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors de la requête:', error);
        alert('Erreur lors de la finalisation');
    });
}

function continueTraining() {
    // Rediriger vers la page de continuation (même page pour l'instant)
    location.reload();
}

function deleteTraining() {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tir compté ?')) {
        fetch(`/scored-trainings/${trainingId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = '/scored-trainings';
            } else {
                alert('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression');
        });
    }
}

// Fonction pour afficher la cible enregistrée
function displayTargetImage() {
    console.log('displayTargetImage() appelée');
    console.log('window.scoredTrainingData:', window.scoredTrainingData);
    
    // Vérifier si une image de cible est disponible dans les données
    const targetImageData = window.scoredTrainingData?.target_image;
    console.log('targetImageData:', targetImageData);
    console.log('Type de targetImageData:', typeof targetImageData);
    console.log('Longueur de targetImageData:', targetImageData ? targetImageData.length : 'undefined');
    
    // Pour le test, on va afficher un placeholder même sans image
    const hasImage = targetImageData && targetImageData.length > 0;
    console.log('hasImage:', hasImage);
    
    if (!hasImage) {
        console.log('Aucune image de cible enregistrée - affichage d\'un placeholder');
        // On va quand même afficher un conteneur pour tester la mise en page
    }
    
    // Chercher le conteneur du graphique
    const chartElement = document.getElementById('scoresChart');
    console.log('chartElement:', chartElement);
    
    if (!chartElement) {
        console.log('Élément du graphique non trouvé');
        return;
    }
    
    // Trouver le conteneur des détails des volées
    const detailsContainer = document.querySelector('.detail-card');
    if (!detailsContainer) {
        console.log('Conteneur des détails non trouvé');
        return;
    }
    
    console.log('Conteneur des détails trouvé:', detailsContainer);
    
    // Créer le conteneur pour l'image de cible
    const targetContainer = document.createElement('div');
    targetContainer.className = 'card mb-3';
    targetContainer.style.cssText = `
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 1rem !important;
        box-sizing: border-box;
    `;
    
    if (hasImage) {
        targetContainer.innerHTML = `
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bullseye"></i> Cliché de la cible
                    </h5>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="mb-3 position-relative">
                        <img src="${targetImageData}" 
                             class="img-fluid rounded shadow-sm target-image-preview" 
                             style="max-width: 100%; height: auto; max-height: 300px; cursor: pointer;"
                             alt="Cliché de la cible"
                             onclick="openTargetModal('${targetImageData}')">
                        <div class="position-absolute top-0 end-0 m-2">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="openTargetModal('${targetImageData}')"
                                    title="Voir en plein écran">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle"></i> 
                        Image capturée lors de la finalisation du tir
                    </p>
                </div>
            </div>
        `;
    } else {
        targetContainer.innerHTML = `
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bullseye"></i> Cliché de la cible
                    </h5>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="fas fa-camera fa-3x text-muted"></i>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle"></i> 
                        Aucun cliché de cible enregistré
                    </p>
                </div>
            </div>
        `;
    }
    
    // Insérer l'image de cible avant le conteneur des détails
    detailsContainer.parentNode.insertBefore(targetContainer, detailsContainer);
    console.log('Image de cible insérée avant les détails des volées');
}

// Créer le graphique des scores par volée
function createScoresChart() {
    const ctx = document.getElementById('scoresChart');
    if (!ctx || !window.endsData || window.endsData.length === 0) {
        return;
    }
    
    // Préparer les données
    const labels = window.endsData.map(end => `Volée ${end.end_number}`);
    const scores = window.endsData.map(end => end.total_score);
    const averages = window.endsData.map(end => (end.total_score / end.shots.length).toFixed(1));
    
    // Calculer la moyenne générale
    const overallAverage = scores.reduce((sum, score) => sum + score, 0) / scores.length;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Score total par volée',
                data: scores,
                borderColor: '#14532d',
                backgroundColor: 'rgba(20, 83, 45, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#14532d',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }, {
                label: 'Moyenne générale',
                data: new Array(scores.length).fill(overallAverage),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Évolution des scores par volée',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            if (context.datasetIndex === 0) {
                                const endIndex = context.dataIndex;
                                const average = averages[endIndex];
                                return `Moyenne: ${average}`;
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: Math.max(...scores) + 5,
                    title: {
                        display: true,
                        text: 'Score total'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Volées'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// ===== FONCTIONS POUR LA CIBLE INTERACTIVE =====

// Fonction pour calculer le score basé sur la position du clic
function calculateScoreFromPosition(x, y) {
    const centerX = 60; // Centre du viewBox 120x120
    const centerY = 60;
    const distance = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    
    console.log('Score calculation - Position:', x, y, 'Distance:', distance);
    
    // Rayons des zones (en unités SVG) - Blason FITA standard (viewBox 120x120)
    // Correspondance exacte avec les rayons de la cible SVG
    // Les zones sont triées du plus petit au plus grand rayon
    const zones = [
        { radius: 3, score: 10 },    // Zone 10 (centre jaune) - r="3"
        { radius: 9, score: 9 },     // Zone 9 (jaune) - r="9"
        { radius: 15, score: 8 },    // Zone 8 (rouge) - r="15"
        { radius: 21, score: 7 },    // Zone 7 (rouge) - r="21"
        { radius: 27, score: 6 },    // Zone 6 (bleu) - r="27"
        { radius: 33, score: 5 },    // Zone 5 (bleu) - r="33"
        { radius: 39, score: 4 },    // Zone 4 (noir) - r="39"
        { radius: 45, score: 3 },    // Zone 3 (noir) - r="45"
        { radius: 51, score: 2 },    // Zone 2 (blanc) - r="51"
        { radius: 57, score: 1 },    // Zone 1 (blanc) - r="57"
        { radius: Infinity, score: 0 } // Manqué
    ];
    
    // Logique avec épaisseur de trait : trouver la zone en tenant compte de l'épaisseur des traits
    // L'épaisseur des traits est de 0.6 selon la cible SVG
    const strokeWidth = 0.6;
    
    for (let i = 0; i < zones.length - 1; i++) {
        const currentZone = zones[i];
        const nextZone = zones[i + 1];
        
        console.log('Checking between zones:', currentZone.radius, 'and', nextZone.radius);
        console.log('Distance:', distance, 'Current radius:', currentZone.radius, 'Next radius:', nextZone.radius);
        
        // Si la distance est dans la zone actuelle (en tenant compte de l'épaisseur complète du trait)
        if (distance <= (currentZone.radius + strokeWidth)) {
            // Vérifier si on est sur le trait de séparation
            if (distance >= (currentZone.radius - strokeWidth)) {
                // On est sur le trait, prendre la zone extérieure (actuelle)
                console.log('On stroke, taking outer zone:', currentZone.score);
                return currentZone.score;
            } else {
                // On est dans la zone, prendre la zone actuelle
                console.log('In zone:', currentZone.score);
                return currentZone.score;
            }
        }
    }
    
    // Si on arrive ici, on est dans la zone la plus extérieure
    console.log('Outer zone:', zones[zones.length - 1].score);
    return zones[zones.length - 1].score;
    
    console.log('Score: 0 (missed)');
    return 0; // Manqué
    
    console.log('Score: 0 (missed)');
    return 0; // Manqué
}

// Fonction pour ajouter une flèche sur la cible
function addArrowToTarget(x, y, score, arrowIndex) {
    const svg = document.getElementById('targetSvg');
    const arrowsGroup = document.getElementById('arrowsGroup');
    
    if (!svg || !arrowsGroup) return;
    
    // Créer un cercle pour représenter la flèche (10 fois plus petit)
    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', x);
    circle.setAttribute('cy', y);
    circle.setAttribute('r', '0.3'); // 10 fois plus petit que 3
    circle.setAttribute('class', 'arrow-marker');
    circle.setAttribute('data-score', score);
    circle.setAttribute('data-arrow-index', arrowIndex);
    
    // Déterminer la couleur de la flèche en fonction de la zone
    // Les zones noires (scores 3 et 4) doivent avoir des flèches blanches
    if (score === 3 || score === 4) {
        circle.setAttribute('fill', 'white');
        circle.setAttribute('stroke', 'black');
        circle.setAttribute('stroke-width', '0.1');
    } else {
        circle.setAttribute('fill', '#dc3545');
        circle.setAttribute('stroke', '#fff');
        circle.setAttribute('stroke-width', '0.1');
    }
    
    // Ajouter un événement de clic pour supprimer la flèche
    circle.addEventListener('click', function(e) {
        e.stopPropagation();
        removeArrowFromTarget(arrowIndex);
    });
    
    arrowsGroup.appendChild(circle);
    
    // Ajouter le score à la liste
    targetScores[arrowIndex] = score;
    updateScoresDisplay();
}

// Fonction pour supprimer une flèche de la cible
function removeArrowFromTarget(arrowIndex) {
    const arrowsGroup = document.getElementById('arrowsGroup');
    const arrowElement = arrowsGroup.querySelector(`[data-arrow-index="${arrowIndex}"]`);
    
    if (arrowElement) {
        arrowElement.remove();
    }
    
    // Supprimer le score de la liste
    targetScores[arrowIndex] = null;
    updateScoresDisplay();
}

// Fonction pour mettre à jour l'affichage des scores
function updateScoresDisplay() {
    const scoresList = document.getElementById('scoresList');
    if (!scoresList) return;
    
    scoresList.innerHTML = '';
    
    for (let i = 0; i < arrowsPerEnd; i++) {
        const score = targetScores[i];
        const scoreItem = document.createElement('div');
        scoreItem.className = 'score-item';
        
        if (score !== null && score !== undefined) {
            scoreItem.innerHTML = `
                <span>Flèche ${i + 1}:</span>
                <span class="score-value">${score}</span>
                <span class="remove-score" onclick="removeArrowFromTarget(${i})">
                    <i class="fas fa-times"></i>
                </span>
            `;
        } else {
            scoreItem.innerHTML = `
                <span>Flèche ${i + 1}:</span>
                <span class="text-muted">Non placée</span>
                <span></span>
            `;
        }
        
        scoresList.appendChild(scoreItem);
    }
}

// Fonction pour gérer le clic sur la cible
function handleTargetClick(event) {
    if (isDragging) return;
    
    // Ignorer les clics après un drag
    if (justFinishedDragging) {
        console.log('Click ignored - just finished dragging');
        justFinishedDragging = false;
        return;
    }
    
    // Protection contre les doubles clics avec debounce
    const currentTime = Date.now();
    if (currentTime - lastClickTime < clickDebounceDelay) {
        console.log('Click ignored - too soon after last click');
        return;
    }
    lastClickTime = currentTime;
    
    // Protection contre les doubles clics
    if (event.detail > 1) {
        console.log('Click ignored - multiple clicks detected');
        return;
    }
    
    console.log('Processing click at:', event.clientX, event.clientY);
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 120;
    const y = ((event.clientY - rect.top) / rect.height) * 120;
    
    const score = calculateScoreFromPosition(x, y);
    
    // Trouver le prochain index disponible
    let arrowIndex = -1;
    for (let i = 0; i < arrowsPerEnd; i++) {
        if (targetScores[i] === null || targetScores[i] === undefined) {
            arrowIndex = i;
            break;
        }
    }
    
    if (arrowIndex === -1) {
        // Toutes les flèches sont placées, remplacer la première
        arrowIndex = 0;
        removeArrowFromTarget(0);
    }
    
    console.log('Adding arrow at index:', arrowIndex, 'with score:', score);
    addArrowToTarget(x, y, score, arrowIndex);
}

// Fonction pour gérer le début du drag
function handleTargetMouseDown(event) {
    if (isDragging) return;
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    isDragging = true;
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 120;
    const y = ((event.clientY - rect.top) / rect.height) * 120;
    
    // Activer l'overlay de zoom
    const overlay = document.getElementById('zoomDragOverlay');
    if (overlay) {
        overlay.classList.add('active');
    }
    
    // Créer la loupe
    createMagnifyingGlass(event.clientX, event.clientY);
    
    // Afficher l'indicateur de score
    showScoreIndicator();
    
    // Calculer et afficher le score initial
    updateScoreIndicator(x, y);
    
    // Ajouter les événements de drag
    document.addEventListener('mousemove', handleTargetMouseMove);
    document.addEventListener('mouseup', handleTargetMouseUp);
    
    event.preventDefault();
}

// Fonction pour gérer le mouvement de la souris pendant le drag
function handleTargetMouseMove(event) {
    if (!isDragging) return;
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 120;
    const y = ((event.clientY - rect.top) / rect.height) * 120;
    
    // Mettre à jour l'indicateur de score
    updateScoreIndicator(x, y);
    
    // Mettre à jour la position de la loupe
    updateMagnifyingGlass(event.clientX, event.clientY);
}

// Fonction pour gérer la fin du drag
function handleTargetMouseUp(event) {
    if (!isDragging) return;
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 120;
    const y = ((event.clientY - rect.top) / rect.height) * 120;
    
    const score = calculateScoreFromPosition(x, y);
    
    // Trouver le prochain index disponible
    let arrowIndex = -1;
    for (let i = 0; i < arrowsPerEnd; i++) {
        if (targetScores[i] === null || targetScores[i] === undefined) {
            arrowIndex = i;
            break;
        }
    }
    
    if (arrowIndex === -1) {
        // Toutes les flèches sont placées, remplacer la première
        arrowIndex = 0;
        removeArrowFromTarget(0);
    }
    
    // Ajouter la flèche avec le score final
    addArrowToTarget(x, y, score, arrowIndex);
    
    // Nettoyer
    cleanupDrag();
    
    // Supprimer les événements
    document.removeEventListener('mousemove', handleTargetMouseMove);
    document.removeEventListener('mouseup', handleTargetMouseUp);
}

// Fonction pour dessiner la zone de la cible dans la loupe
function drawTargetZone(ctx, x, y) {
    // Effacer le canvas
    ctx.clearRect(0, 0, 150, 150);
    
    // Dessiner un fond blanc
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, 150, 150);
    
    // Sauvegarder l'état du contexte
    ctx.save();
    
    // Appliquer le zoom x2 avec scale
    ctx.scale(2, 2);
    
    // Centrer sur le point pointé
    ctx.translate(-x + 75, -y + 75);
    
    // Dessiner les zones de la cible
    const centerX = 60;
    const centerY = 60;
    
    const zones = [
        { radius: 57, color: 'white', strokeWidth: 1.2 },
        { radius: 51, color: 'white', strokeWidth: 0.6 },
        { radius: 45, color: 'black', strokeWidth: 0.6 },
        { radius: 39, color: 'black', strokeWidth: 0.6 },
        { radius: 33, color: 'blue', strokeWidth: 0.6 },
        { radius: 27, color: 'blue', strokeWidth: 0.6 },
        { radius: 21, color: 'red', strokeWidth: 0.6 },
        { radius: 15, color: 'red', strokeWidth: 0.6 },
        { radius: 9, color: 'yellow', strokeWidth: 0.6 },
        { radius: 3, color: 'yellow', strokeWidth: 0.6 }
    ];
    
    // Dessiner toutes les zones
    for (let i = 0; i < zones.length; i++) {
        const zone = zones[i];
        ctx.beginPath();
        ctx.arc(centerX, centerY, zone.radius, 0, 2 * Math.PI);
        ctx.fillStyle = zone.color;
        ctx.fill();
        ctx.strokeStyle = 'black';
        ctx.lineWidth = zone.strokeWidth;
        ctx.stroke();
    }
    
    // Restaurer l'état du contexte
    ctx.restore();
}

// Fonction pour créer une loupe autour du curseur
function createMagnifyingGlass(mouseX, mouseY) {
    // Supprimer l'ancienne loupe si elle existe
    const existingGlass = document.getElementById('magnifyingGlass');
    if (existingGlass) {
        existingGlass.remove();
    }
    
    // Créer la loupe
    const magnifyingGlass = document.createElement('div');
    magnifyingGlass.id = 'magnifyingGlass';
    magnifyingGlass.style.cssText = `
        position: fixed;
        width: 150px;
        height: 150px;
        border: 3px solid #007bff;
        border-radius: 50%;
        background: white;
        box-shadow: 0 0 20px rgba(0, 123, 255, 0.8);
        z-index: 9999;
        pointer-events: none;
        overflow: hidden;
        transform: translate(-50%, -50%);
    `;
    
    // Calculer la position pointée pour centrer la loupe
    const svg = document.getElementById('targetSvg');
    const rect = svg.getBoundingClientRect();
    
    // Vérifier si le curseur est dans les limites de la cible
    if (mouseX < rect.left || mouseX > rect.right || mouseY < rect.top || mouseY > rect.bottom) {
        console.log('Curseur en dehors de la cible');
        return; // Ne pas afficher la loupe si le curseur est en dehors
    }
    
    // Calculer la position relative dans le SVG (0-120)
    const x = ((mouseX - rect.left) / rect.width) * 120;
    const y = ((mouseY - rect.top) / rect.height) * 120;
    
    console.log('Position pointée:', x, y);
    console.log('Rect SVG:', rect);
    console.log('Mouse position:', mouseX, mouseY);
    
    // Créer un SVG cloné pour la loupe
    const clonedSvg = svg.cloneNode(true);
    
    // Appliquer le zoom x3 et centrer sur le point pointé
    // Pour centrer le point (x,y) au centre de la loupe (75,75)
    // Le point (x,y) doit être au centre de la loupe, donc on déplace l'image
    const offsetX = 75 - x; // Déplacement pour centrer le point (x,y) au centre (75,75)
    const offsetY = 75 - y; // Déplacement pour centrer le point (x,y) au centre (75,75)
    
    console.log('Offset calculé:', offsetX, offsetY);
    
    // Créer un viewBox qui centre sur le point pointé avec zoom important
    const viewBoxX = x - 5; // Centre moins la moitié de la zone visible (10/2)
    const viewBoxY = y - 5; // Centre moins la moitié de la zone visible (10/2)
    const viewBoxSize = 10; // Zone visible dans la loupe (très petite = zoom très important)
    
    clonedSvg.setAttribute('viewBox', `${viewBoxX} ${viewBoxY} ${viewBoxSize} ${viewBoxSize}`);
    clonedSvg.style.cssText = `
        width: 150px;
        height: 150px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    `;
    
    // Calculer le score pour la position pointée
    const score = calculateScoreFromPosition(x, y);
    
    // Créer un élément pour afficher le score dans la loupe
    const scoreDisplay = document.createElement('div');
    scoreDisplay.style.cssText = `
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 18px;
        font-weight: bold;
        z-index: 10;
        pointer-events: none;
    `;
    scoreDisplay.textContent = score;
    
    // Créer un point d'impact au centre de la loupe
    const impactPoint = document.createElement('div');
    impactPoint.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 8px;
        height: 8px;
        background: #ff0000;
        border: 2px solid #ffffff;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        z-index: 15;
        pointer-events: none;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
    `;
    
    magnifyingGlass.appendChild(clonedSvg);
    magnifyingGlass.appendChild(scoreDisplay);
    magnifyingGlass.appendChild(impactPoint);
    document.body.appendChild(magnifyingGlass);
    
    // Positionner la loupe
    magnifyingGlass.style.left = mouseX + 'px';
    magnifyingGlass.style.top = mouseY + 'px';
    
    // Stocker la référence
    window.currentMagnifyingGlass = magnifyingGlass;
}

// Fonction pour mettre à jour la loupe pendant le drag
function updateMagnifyingGlass(mouseX, mouseY) {
    if (!window.currentMagnifyingGlass) return;
    
    // Mettre à jour la position de la loupe
    window.currentMagnifyingGlass.style.left = mouseX + 'px';
    window.currentMagnifyingGlass.style.top = mouseY + 'px';
    
    // Mettre à jour le contenu de la loupe pour suivre le curseur
    const svg = document.getElementById('targetSvg');
    const rect = svg.getBoundingClientRect();
    const x = ((mouseX - rect.left) / rect.width) * 120;
    const y = ((mouseY - rect.top) / rect.height) * 120;
    
    console.log('Update - Position pointée:', x, y);
    console.log('Update - Offset calculé:', 75 - x, 75 - y);
    
    // Mettre à jour le SVG cloné avec la nouvelle position
    const clonedSvg = window.currentMagnifyingGlass.querySelector('svg');
    if (clonedSvg) {
        // Créer un viewBox qui centre sur le point pointé avec zoom important
        const viewBoxX = x - 5; // Centre moins la moitié de la zone visible (10/2)
        const viewBoxY = y - 5; // Centre moins la moitié de la zone visible (10/2)
        const viewBoxSize = 10; // Zone visible dans la loupe (très petite = zoom très important)
        
        clonedSvg.setAttribute('viewBox', `${viewBoxX} ${viewBoxY} ${viewBoxSize} ${viewBoxSize}`);
    }
    
    // Mettre à jour le score affiché dans la loupe
    const scoreDisplay = window.currentMagnifyingGlass.querySelector('div');
    if (scoreDisplay) {
        const score = calculateScoreFromPosition(x, y);
        scoreDisplay.textContent = score;
    }
    
    // S'assurer que le point d'impact est visible
    let impactPoint = window.currentMagnifyingGlass.querySelector('.impact-point');
    if (!impactPoint) {
        impactPoint = document.createElement('div');
        impactPoint.className = 'impact-point';
        impactPoint.style.cssText = `
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            background: #ff0000;
            border: 2px solid #ffffff;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: 15;
            pointer-events: none;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
        `;
        window.currentMagnifyingGlass.appendChild(impactPoint);
    }
}

// Fonction pour afficher l'indicateur de score
function showScoreIndicator() {
    const indicator = document.getElementById('targetScoreIndicator');
    if (indicator) {
        indicator.style.display = 'block';
    }
}

// Fonction pour mettre à jour l'indicateur de score
function updateScoreIndicator(x, y) {
    const score = calculateScoreFromPosition(x, y);
    currentDragScore = score;
    
    const scoreElement = document.getElementById('currentScore');
    if (scoreElement) {
        scoreElement.textContent = score;
    }
    
    // Positionner l'indicateur à côté du pointeur
    const indicator = document.getElementById('targetScoreIndicator');
    if (indicator) {
        const svg = document.getElementById('targetSvg');
        const rect = svg.getBoundingClientRect();
        const svgX = ((x / 120) * rect.width) + rect.left;
        const svgY = ((y / 120) * rect.height) + rect.top;
        
        indicator.style.left = (svgX + 15) + 'px';
        indicator.style.top = (svgY - 10) + 'px';
    }
}

// Fonction pour nettoyer après le drag
function cleanupDrag() {
    isDragging = false;
    currentDragScore = 0;
    
    // Activer le flag pour éviter le clic après drag
    justFinishedDragging = true;
    
    // Supprimer l'overlay de zoom
    const overlay = document.getElementById('zoomDragOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
    
    // Supprimer la loupe
    if (window.currentMagnifyingGlass) {
        window.currentMagnifyingGlass.remove();
        window.currentMagnifyingGlass = null;
    }
    
    // Masquer l'indicateur de score
    const indicator = document.getElementById('targetScoreIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}


// Fonction pour réinitialiser la cible
function resetTarget() {
    const arrowsGroup = document.getElementById('arrowsGroup');
    if (arrowsGroup) {
        arrowsGroup.innerHTML = '';
    }
    
    targetScores = new Array(arrowsPerEnd).fill(null);
    updateScoresDisplay();
}

// Fonction pour basculer entre les modes de saisie
function toggleScoreMode() {
    const tableMode = document.getElementById('tableMode');
    const targetMode = document.getElementById('targetMode');
    const tableContainer = document.getElementById('tableModeContainer');
    const targetContainer = document.getElementById('targetModeContainer');
    
    if (!tableMode || !targetMode || !tableContainer || !targetContainer) return;
    
    if (tableMode.checked) {
        tableContainer.style.display = 'block';
        targetContainer.style.display = 'none';
        
        // S'assurer que les champs de score du mode tableau sont initialisés
        initializeScoreFields();
    } else if (targetMode.checked) {
        tableContainer.style.display = 'none';
        targetContainer.style.display = 'block';
        
        // Initialiser la cible si ce n'est pas déjà fait
        if (targetScores.length === 0) {
            targetScores = new Array(arrowsPerEnd).fill(null);
        }
        updateScoresDisplay();
    }
}

// Fonction pour obtenir les scores depuis la cible
function getTargetScores() {
    return targetScores.filter(score => score !== null && score !== undefined);
}

// Fonction pour capturer la cible et la convertir en image
function captureTarget() {
    console.log('captureTarget() appelée');
    
    const svg = document.getElementById('targetSvg');
    console.log('SVG trouvé:', svg);
    
    if (!svg) {
        console.error('Cible SVG non trouvée');
        return null;
    }
    
    // Cloner le SVG pour éviter de modifier l'original
    const clonedSvg = svg.cloneNode(true);
    console.log('SVG cloné');
    
    // Définir la taille de l'image de sortie
    const width = 400;
    const height = 400;
    
    // Créer un canvas pour la conversion
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    
    // Créer un fond blanc
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, width, height);
    
    // Convertir le SVG en image
    const svgData = new XMLSerializer().serializeToString(clonedSvg);
    console.log('SVG sérialisé, taille:', svgData.length);
    
    const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
    const svgUrl = URL.createObjectURL(svgBlob);
    console.log('URL SVG créée:', svgUrl);
    
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
            console.log('Image SVG chargée');
            // Dessiner l'image sur le canvas
            ctx.drawImage(img, 0, 0, width, height);
            
            // Convertir en base64
            const dataURL = canvas.toDataURL('image/png');
            console.log('Image convertie en base64, taille:', dataURL.length);
            
            // Nettoyer l'URL
            URL.revokeObjectURL(svgUrl);
            
            resolve(dataURL);
        };
        img.onerror = function(error) {
            console.error('Erreur lors du chargement de l\'image SVG:', error);
            URL.revokeObjectURL(svgUrl);
            reject(error);
        };
        img.src = svgUrl;
    });
}

// Fonction pour ajouter la capture de cible à la modal de finalisation
function addTargetCaptureToFinalModal() {
    const finalNotesTextarea = document.getElementById('final_notes');
    if (!finalNotesTextarea) return;
    
    // Créer un conteneur pour la capture de cible
    const captureContainer = document.createElement('div');
    captureContainer.className = 'mb-3';
    captureContainer.innerHTML = `
        <label class="form-label">Cliché de la cible</label>
        <div class="d-flex gap-2 mb-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="captureTargetImage()">
                <i class="fas fa-camera"></i> Capturer la cible
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearTargetImage()" id="clearTargetBtn" style="display: none;">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
        <div id="targetImagePreview" class="text-center" style="display: none;">
            <img id="targetImage" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
        </div>
        <input type="hidden" id="targetImageData" name="target_image">
    `;
    
    // Insérer avant le textarea des notes
    finalNotesTextarea.parentNode.insertBefore(captureContainer, finalNotesTextarea);
}

// Fonction pour capturer et afficher l'image de la cible
async function captureTargetImage() {
    console.log('captureTargetImage() appelée');
    
    try {
        const captureBtn = document.querySelector('button[onclick="captureTargetImage()"]');
        const originalText = captureBtn.innerHTML;
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Capture...';
        captureBtn.disabled = true;
        
        console.log('Début de la capture...');
        const imageData = await captureTarget();
        console.log('Image capturée, taille:', imageData ? imageData.length : 'null');
        
        if (imageData) {
            // Afficher l'aperçu
            const preview = document.getElementById('targetImagePreview');
            const img = document.getElementById('targetImage');
            const hiddenInput = document.getElementById('targetImageData');
            const clearBtn = document.getElementById('clearTargetBtn');
            
            console.log('Éléments trouvés:', { preview, img, hiddenInput, clearBtn });
            
            img.src = imageData;
            hiddenInput.value = imageData;
            preview.style.display = 'block';
            clearBtn.style.display = 'inline-block';
            
            console.log('Cible capturée avec succès, données stockées dans:', hiddenInput.name);
        } else {
            console.error('Aucune donnée d\'image retournée');
            alert('Erreur: Aucune image n\'a pu être capturée');
        }
        
        captureBtn.innerHTML = originalText;
        captureBtn.disabled = false;
    } catch (error) {
        console.error('Erreur lors de la capture:', error);
        alert('Erreur lors de la capture de la cible: ' + error.message);
        
        const captureBtn = document.querySelector('button[onclick="captureTargetImage()"]');
        captureBtn.innerHTML = '<i class="fas fa-camera"></i> Capturer la cible';
        captureBtn.disabled = false;
    }
}

// Fonction pour supprimer l'image de la cible
function clearTargetImage() {
    const preview = document.getElementById('targetImagePreview');
    const hiddenInput = document.getElementById('targetImageData');
    const clearBtn = document.getElementById('clearTargetBtn');
    
    preview.style.display = 'none';
    hiddenInput.value = '';
    clearBtn.style.display = 'none';
}

// Initialiser l'application quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    initializeTrainingData();
    createScoresChart();
    displayTargetImage();
    
    // Vérifier si on doit ouvrir automatiquement la modale d'ajout de volée
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('add_end') === 'true') {
        // Attendre un peu que la page soit complètement chargée
        setTimeout(() => {
            addEnd();
        }, 500);
        
        // Nettoyer l'URL en supprimant le paramètre
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Ajouter les événements pour la cible interactive
    const targetSvg = document.getElementById('targetSvg');
    const resetButton = document.getElementById('resetTarget');
    const scoreModeRadios = document.querySelectorAll('input[name="scoreMode"]');
    
    if (targetSvg) {
        // Supprimer les anciens gestionnaires d'événements s'ils existent
        targetSvg.removeEventListener('click', handleTargetClick);
        targetSvg.removeEventListener('mousedown', handleTargetMouseDown);
        
        // Ajouter les nouveaux gestionnaires d'événements
        targetSvg.addEventListener('click', handleTargetClick);
        targetSvg.addEventListener('mousedown', handleTargetMouseDown);
    }
    
    if (resetButton) {
        resetButton.addEventListener('click', resetTarget);
    }
    
    scoreModeRadios.forEach(radio => {
        radio.addEventListener('change', toggleScoreMode);
    });
});

// Exposer les fonctions globalement pour les appels depuis HTML
window.openModal = openModal;
window.addEnd = addEnd;
window.saveEnd = saveEnd;
window.saveEndAndClose = saveEndAndClose;
window.endTraining = endTraining;
window.confirmEndTraining = confirmEndTraining;
window.continueTraining = continueTraining;
window.deleteTraining = deleteTraining;

// Fonctions pour la cible interactive
window.toggleScoreMode = toggleScoreMode;
window.resetTarget = resetTarget;
window.removeArrowFromTarget = removeArrowFromTarget;

// Fonctions pour la capture de cible
window.captureTargetImage = captureTargetImage;
window.clearTargetImage = clearTargetImage;

// Fonction de test pour forcer l'affichage de la cible
window.testTargetDisplay = function() {
    console.log('Test de l\'affichage de la cible');
    displayTargetImage();
};

// Fonction pour ouvrir la modal de visualisation de la cible en plein écran
function openTargetModal(imageData) {
    // Créer la modal si elle n'existe pas
    let modal = document.getElementById('targetModal');
    if (!modal) {
        modal = createTargetModal();
        document.body.appendChild(modal);
    }
    
    // Mettre à jour l'image
    const modalImage = modal.querySelector('#targetModalImage');
    modalImage.src = imageData;
    
    // Afficher la modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Initialiser la loupe après que l'image soit chargée
    if (modalImage.complete) {
        // Image déjà chargée
        setupTargetModalEvents();
    } else {
        // Attendre que l'image soit chargée
        modalImage.addEventListener('load', function() {
            setupTargetModalEvents();
        });
    }
}

// Fonction pour créer la modal de visualisation de la cible
function createTargetModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'targetModal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'targetModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="targetModalLabel">
                        <i class="fas fa-bullseye"></i> Visualisation de la cible
                    </h5>
                    <div class="btn-group me-2" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="zoomIn()" title="Zoom avant">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="zoomOut()" title="Zoom arrière">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetZoom()" title="Zoom normal">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                    </div>
                    <div class="me-2">
                        <small class="text-muted">
                            <i class="fas fa-search"></i> Loupe: <span id="magnifierZoomDisplay">2.0x</span> 
                            <small>(Ctrl + roulette)</small>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative" style="background-color: #f8f9fa;">
                    <div id="imageContainer" class="position-relative d-flex justify-content-center align-items-center" style="height: calc(100vh - 120px); overflow: hidden;">
                        <img id="targetModalImage" 
                             class="img-fluid" 
                             style="max-width: none; max-height: none; transition: transform 0.3s ease; cursor: grab;"
                             alt="Cible en plein écran">
                        <div id="magnifier" class="position-absolute" style="
                            width: 150px; 
                            height: 150px; 
                            border: 3px solid #007bff; 
                            border-radius: 50%; 
                            background: rgba(255, 255, 255, 0.8); 
                            pointer-events: none; 
                            display: none;
                            z-index: 1000;
                            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
                        "></div>
                    </div>
                    <div class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                        <div class="text-center text-white">
                            <small>
                                <i class="fas fa-mouse"></i> Cliquez et glissez pour naviguer • 
                                <i class="fas fa-search-plus"></i> Molette pour zoomer • 
                                <i class="fas fa-hand-paper"></i> Survolez pour la loupe
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter les événements après création
    setTimeout(() => {
        setupTargetModalEvents();
    }, 100);
    
    return modal;
}

// Variables globales pour la modal
let currentZoom = 1;
let magnifierZoom = 2; // Facteur de zoom de la loupe
let isTargetDragging = false;
let dragStart = { x: 0, y: 0 };
let imagePosition = { x: 0, y: 0 };

// Configuration des événements de la modal
function setupTargetModalEvents() {
    const modal = document.getElementById('targetModal');
    const image = document.getElementById('targetModalImage');
    const container = document.getElementById('imageContainer');
    const magnifier = document.getElementById('magnifier');
    
    if (!image || !container || !magnifier) return;
    
    // Événements de zoom avec la molette
    container.addEventListener('wheel', function(e) {
        e.preventDefault();
        
        if (e.ctrlKey) {
            // Ctrl + roulette = zoom de la loupe
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            magnifierZoom *= delta;
            magnifierZoom = Math.max(1.0, Math.min(5.0, magnifierZoom)); // Limiter entre 1x et 5x
            
            // Mettre à jour l'affichage du zoom
            updateMagnifierZoomDisplay();
            
            // Redessiner la loupe si elle est visible
            if (magnifier.style.display === 'block') {
                const rect = container.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                updateTargetMagnifier(x, y, image, magnifier);
            }
        } else {
            // Roulette normale = zoom de l'image
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            zoomImage(delta);
        }
    });
    
    // Événements de drag
    image.addEventListener('mousedown', function(e) {
        isTargetDragging = true;
        dragStart.x = e.clientX - imagePosition.x;
        dragStart.y = e.clientY - imagePosition.y;
        image.style.cursor = 'grabbing';
    });
    
    document.addEventListener('mousemove', function(e) {
        if (isTargetDragging) {
            imagePosition.x = e.clientX - dragStart.x;
            imagePosition.y = e.clientY - dragStart.y;
            updateImagePosition();
        }
        
        // Gestion de la loupe
        if (modal && modal.classList.contains('show')) {
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                magnifier.style.display = 'block';
                magnifier.style.left = (x - 75) + 'px';
                magnifier.style.top = (y - 75) + 'px';
                
                // Créer la loupe avec SVG cloné comme dans l'écran de saisie
                updateTargetMagnifier(x, y, image, magnifier);
            } else {
                magnifier.style.display = 'none';
            }
        }
    });
    
    document.addEventListener('mouseup', function() {
        isTargetDragging = false;
        if (image) {
            image.style.cursor = 'grab';
        }
    });
    
    // Empêcher le drag par défaut
    image.addEventListener('dragstart', function(e) {
        e.preventDefault();
    });
}

// Fonction de zoom
function zoomImage(factor) {
    currentZoom *= factor;
    currentZoom = Math.max(0.1, Math.min(5, currentZoom)); // Limiter le zoom entre 0.1x et 5x
    
    const image = document.getElementById('targetModalImage');
    if (image) {
        image.style.transform = `scale(${currentZoom})`;
    }
}

// Fonctions de zoom exposées globalement
window.zoomIn = function() {
    zoomImage(1.2);
};

window.zoomOut = function() {
    zoomImage(0.8);
};

window.resetZoom = function() {
    currentZoom = 1;
    imagePosition = { x: 0, y: 0 };
    const image = document.getElementById('targetModalImage');
    if (image) {
        image.style.transform = 'scale(1)';
        image.style.left = '0px';
        image.style.top = '0px';
    }
};

// Fonction pour mettre à jour la position de l'image
function updateImagePosition() {
    const image = document.getElementById('targetModalImage');
    if (image) {
        image.style.left = imagePosition.x + 'px';
        image.style.top = imagePosition.y + 'px';
    }
}

// Fonction pour mettre à jour la loupe de la cible (comme dans l'écran de saisie)
function updateTargetMagnifier(x, y, image, magnifier) {
    // Vider le contenu de la loupe
    magnifier.innerHTML = '';
    
    // Créer un canvas pour dessiner la zone agrandie
    const canvas = document.createElement('canvas');
    canvas.width = 150;
    canvas.height = 150;
    canvas.style.cssText = `
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
    `;
    
    const ctx = canvas.getContext('2d');
    
    // Calculer les dimensions de l'image dans le conteneur
    const containerRect = document.getElementById('imageContainer').getBoundingClientRect();
    const imageRect = image.getBoundingClientRect();
    
    // Calculer la position relative dans l'image
    const imageX = ((x - (imageRect.left - containerRect.left)) / imageRect.width) * image.naturalWidth;
    const imageY = ((y - (imageRect.top - containerRect.top)) / imageRect.height) * image.naturalHeight;
    
    // Zone à afficher dans la loupe (plus petite = plus de zoom)
    const zoomFactor = magnifierZoom; // Facteur de zoom variable
    const viewSize = 150 / zoomFactor; // Taille de la zone visible
    
    // Calculer la zone source dans l'image
    const sourceX = imageX - viewSize / 2;
    const sourceY = imageY - viewSize / 2;
    const sourceWidth = viewSize;
    const sourceHeight = viewSize;
    
    // Dessiner le fond blanc
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, 150, 150);
    
    // Dessiner la zone agrandie de l'image
    ctx.drawImage(
        image,
        sourceX, sourceY, sourceWidth, sourceHeight, // Zone source
        0, 0, 150, 150 // Zone destination
    );
    
    // Ajouter un cercle de cible au centre
    ctx.strokeStyle = '#007bff';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.arc(75, 75, 3, 0, 2 * Math.PI);
    ctx.stroke();
    
    // Ajouter un point de visée
    ctx.strokeStyle = '#dc3545';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(75, 70);
    ctx.lineTo(75, 80);
    ctx.moveTo(70, 75);
    ctx.lineTo(80, 75);
    ctx.stroke();
    
    magnifier.appendChild(canvas);
}

// Fonction pour mettre à jour l'affichage du zoom de la loupe
function updateMagnifierZoomDisplay() {
    const display = document.getElementById('magnifierZoomDisplay');
    if (display) {
        display.textContent = magnifierZoom.toFixed(1) + 'x';
    }
}

// Exposer la fonction globalement
window.openTargetModal = openTargetModal;

// Ajouter les styles CSS pour la modal de cible
function addTargetModalStyles() {
    if (document.getElementById('targetModalStyles')) return;
    
    const style = document.createElement('style');
    style.id = 'targetModalStyles';
    style.textContent = `
        .target-image-preview {
            transition: transform 0.2s ease;
        }
        
        .target-image-preview:hover {
            transform: scale(1.02);
        }
        
        #targetModal .modal-content {
            background-color: #f8f9fa;
        }
        
        #targetModalImage {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        #magnifier {
            border: 3px solid #007bff;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.5);
            background: white;
            overflow: hidden;
        }
        
        #magnifier canvas {
            border-radius: 50%;
        }
        
        #imageContainer {
            background: 
                radial-gradient(circle at 20% 20%, rgba(0, 123, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 123, 255, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .btn-group .btn {
            border-radius: 0.375rem;
        }
        
        .btn-group .btn:not(:last-child) {
            margin-right: 0.25rem;
        }
        
        /* Alignement des cartes côte à côte */
        .chart-container-with-target,
        .target-image-container {
            display: flex;
            flex-direction: column;
        }
        
        /* Espacement uniforme entre tous les cadres */
        .chart-container-with-target {
            padding-right: 16px;
        }
        
        .target-image-container {
            padding-left: 16px;
        }
        
        /* Assurer que les cartes ont la même hauteur */
        .chart-container-with-target .card,
        .target-image-container .card {
            height: 100%;
        }
        
        /* Espacement uniforme entre les colonnes */
        .row .col-md-6:first-child {
            padding-right: 8px;
        }
        
        .row .col-md-6:last-child {
            padding-left: 8px;
        }
        
        /* Espacement vertical uniforme */
        .row {
            margin-bottom: 16px;
        }
        
        .row:last-child {
            margin-bottom: 0;
        }
        
        /* Alignement spécifique pour les conteneurs côte à côte */
        .chart-container-with-target,
        .target-image-container {
            margin-top: 0;
            padding-top: 0;
        }
        
        /* Assurer que les cartes sont alignées en haut */
        .chart-container-with-target .card,
        .target-image-container .card {
            margin-top: 0;
        }
    `;
    
    document.head.appendChild(style);
}

// Initialiser les styles au chargement
document.addEventListener('DOMContentLoaded', function() {
    addTargetModalStyles();
});
