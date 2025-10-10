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

// Fonction pour vérifier si le numéro de volée dépasse le maximum
function isEndNumberExceedingMax(endNumber) {
    return totalEnds > 0 && endNumber > totalEnds;
}

// Fonction pour gérer la visibilité des boutons selon le numéro de volée
function updateButtonVisibility(endNumber) {
    const continueButton = document.querySelector('button[onclick="saveEnd()"]');
    const finishButton = document.querySelector('button[onclick="saveEndAndClose()"]');
    
    if (isEndNumberExceedingMax(endNumber)) {
        // Masquer le bouton "Enregistrer et continuer" si on dépasse le maximum
        if (continueButton) {
            continueButton.style.display = 'none';
        }
        // S'assurer que le bouton "Terminer" est visible
        if (finishButton) {
            finishButton.style.display = 'inline-block';
        }
    } else {
        // Afficher les deux boutons si on est dans la limite
        if (continueButton) {
            continueButton.style.display = 'inline-block';
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
    
    initializeScoreFields();
    
    // Restaurer les valeurs mémorisées
    const form = document.getElementById('addEndForm');
    if (form) {
        const targetCategorySelect = form.querySelector('select[name="target_category"]');
        const shootingPositionSelect = form.querySelector('select[name="shooting_position"]');
        const endNumberInput = form.querySelector('input[name="end_number"]');
        
        if (targetCategorySelect && savedTargetCategory) {
            targetCategorySelect.value = savedTargetCategory;
        }
        if (shootingPositionSelect && savedShootingPosition) {
            shootingPositionSelect.value = savedShootingPosition;
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
    }
    
    // Ajouter un événement pour écouter les changements du numéro de volée
    const endNumberInput = document.getElementById('end_number');
    if (endNumberInput) {
        endNumberInput.addEventListener('input', function() {
            const endNumber = parseInt(this.value) || 1;
            updateButtonVisibility(endNumber);
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
    
    const scores = [];
    const scoreInputs = form.querySelectorAll('select[name="scores[]"]');
    
    scoreInputs.forEach((select, index) => {
        const value = parseInt(select.value) || 0;
        scores.push(value);
    });
    
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
            
            // Réinitialiser les champs de score
            initializeScoreFields();
            
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
    
    const scores = [];
    const scoreInputs = form.querySelectorAll('select[name="scores[]"]');
    let hasValidScores = false;
    
    scoreInputs.forEach(select => {
        const value = parseInt(select.value) || 0;
        scores.push(value);
        if (value > 0) {
            hasValidScores = true;
        }
    });
    
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
    
    const data = {
        training_id: trainingId,
        notes: formData.get('final_notes') || ''
    };
    
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

// Initialiser l'application quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    initializeTrainingData();
    createScoresChart();
    
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
