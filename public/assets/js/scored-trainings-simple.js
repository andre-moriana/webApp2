// JavaScript simple pour les tirs comptés
console.log('🚀 scored-trainings-simple.js chargé');

// Configuration automatique selon le type de tir
function updateShootingConfiguration() {
    console.log('🎯 updateShootingConfiguration appelée');
    
    const shootingType = document.getElementById('shooting_type').value;
    const totalEndsInput = document.getElementById('total_ends');
    const arrowsPerEndInput = document.getElementById('arrows_per_end');
    
    console.log('Type de tir sélectionné:', shootingType);
    console.log('Champs trouvés:', { totalEndsInput, arrowsPerEndInput });
    
    if (!totalEndsInput || !arrowsPerEndInput) {
        console.error('❌ Champs non trouvés');
        return;
    }
    
    const configurations = {
        'TAE': { totalEnds: 12, arrowsPerEnd: 6 },
        'Salle': { totalEnds: 20, arrowsPerEnd: 3 },
        '3D': { totalEnds: 24, arrowsPerEnd: 2 },
        'Nature': { totalEnds: 21, arrowsPerEnd: 2 },
        'Campagne': { totalEnds: 24, arrowsPerEnd: 3 },
        'Libre': { totalEnds: 10, arrowsPerEnd: 6 }
    };
    
    if (shootingType && configurations[shootingType]) {
        const config = configurations[shootingType];
        totalEndsInput.value = config.totalEnds;
        arrowsPerEndInput.value = config.arrowsPerEnd;
        
        console.log(`✅ Configuration appliquée: ${config.totalEnds} volées, ${config.arrowsPerEnd} flèches`);
    }
}

// Fonctions pour les boutons
function viewTraining(trainingId) {
    console.log('👁️ viewTraining appelée avec ID:', trainingId);
    if (trainingId && trainingId > 0) {
        window.location.href = '/scored-trainings/' + trainingId;
    }
}

function continueTraining(trainingId) {
    console.log('▶️ continueTraining appelée avec ID:', trainingId);
    if (trainingId && trainingId > 0) {
        window.location.href = '/scored-trainings/' + trainingId;
    }
}

function deleteTraining(trainingId) {
    console.log('🗑️ deleteTraining appelée avec ID:', trainingId);
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tir compté ?')) {
        // Logique de suppression
        console.log('Suppression confirmée pour ID:', trainingId);
    }
}

function openAddEndModal() {
    console.log('📝 openAddEndModal appelée');
    
    // Initialiser le numéro de volée (nombre d'ends existants + 1)
    if (window.scoredTrainingData && window.scoredTrainingData.ends) {
        currentEndNumber = window.scoredTrainingData.ends.length + 1;
    } else {
        currentEndNumber = 1;
    }
    
    // Mettre à jour le champ numéro de volée
    const endNumberInput = document.getElementById('end_number');
    if (endNumberInput) {
        endNumberInput.value = currentEndNumber;
    }
    
    // Réinitialiser les valeurs mémorisées
    lastTargetCategory = '';
    lastShootingPosition = '';
    
    // Initialiser les champs de score
    initializeScoreFields();
    
    // Ouvrir le modal avec Bootstrap
    const modal = new bootstrap.Modal(document.getElementById('addEndModal'));
    modal.show();
}

function initializeScoreFields() {
    console.log('🎯 initializeScoreFields appelée');
    
    const container = document.getElementById('scoresContainer');
    if (!container) {
        console.error('❌ Container scoresContainer non trouvé');
        return;
    }
    
    // Récupérer le nombre de flèches depuis les données globales
    let arrowsPerEnd = 6; // Valeur par défaut
    
    if (window.scoredTrainingData && window.scoredTrainingData.arrows_per_end) {
        arrowsPerEnd = parseInt(window.scoredTrainingData.arrows_per_end);
    }
    
    console.log('Nombre de flèches par volée:', arrowsPerEnd);
    
    // Nettoyer le conteneur
    container.innerHTML = '';
    
    // Générer les champs de score
    for (let i = 1; i <= arrowsPerEnd; i++) {
        const col = document.createElement('div');
        col.className = 'col-md-2 col-sm-3 col-4 mb-2';
        
                col.innerHTML = `
                    <label for="arrow_${i}" class="form-label">Flèche ${i}</label>
                    <input type="number" class="form-control arrow-score" id="arrow_${i}" name="arrow_${i}" 
                           min="0" max="11" step="1" placeholder="0">
                `;
        
        container.appendChild(col);
    }
    
    console.log(`✅ ${arrowsPerEnd} champs de score générés`);
}

// Variables globales pour la gestion de la modale
let currentEndNumber = 1;
let lastTargetCategory = '';
let lastShootingPosition = '';


// Fonction pour valider les scores selon le type de tir
function validateScores(scores, shootingType) {
    // Définir les scores valides selon le type de tir
    const validScores = {
        'TAE': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'Salle': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        '3D': [0, 5, 8, 10, 11], // Scores spécifiques 3D
        'Nature': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'Campagne': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'Libre': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
    };
    
    const allowedScores = validScores[shootingType] || validScores['Libre'];
    
    for (let i = 0; i < scores.length; i++) {
        const score = scores[i];
        
        // Vérifier que c'est un nombre entier
        if (!Number.isInteger(score)) {
            return {
                valid: false,
                message: `Le score de la flèche ${i + 1} doit être un nombre entier`
            };
        }
        
        // Vérifier que le score est valide pour ce type de tir
        if (!allowedScores.includes(score)) {
            return {
                valid: false,
                message: `Score invalide pour ${shootingType}. Scores autorisés: ${allowedScores.join(', ')}`
            };
        }
    }
    
    return { valid: true };
}

// Fonction pour afficher des messages de validation discrets
function showValidationMessage(message, type = 'info') {
    // Supprimer l'ancien message s'il existe
    const existingMessage = document.getElementById('validation-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Créer le nouveau message
    const messageDiv = document.createElement('div');
    messageDiv.id = 'validation-message';
    messageDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
    messageDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Ajouter au body
    document.body.appendChild(messageDiv);
    
    // Auto-supprimer après 3 secondes
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 3000);
}

function saveEnd() {
    console.log('💾 saveEnd appelée');
    
    const form = document.getElementById('addEndForm');
    if (!form) {
        console.error('❌ Formulaire addEndForm non trouvé');
        return;
    }
    
    const formData = new FormData(form);
    
    // Récupérer les scores des flèches
    const scores = [];
    const scoreInputs = document.querySelectorAll('.arrow-score');
    
    scoreInputs.forEach(input => {
        const value = parseFloat(input.value);
        if (!isNaN(value) && value >= 0) {
            scores.push(value);
        }
    });
    
    console.log('Scores récupérés:', scores);
    
    // Préparer les données
    const endData = {
        end_number: parseInt(formData.get('end_number')),
        target_category: formData.get('target_category'),
        shooting_position: formData.get('shooting_position'),
        comment: formData.get('comment'),
        scores: scores
    };
    
    // Validation
    if (scores.length === 0) {
        showValidationMessage('Veuillez saisir au moins un score', 'error');
        return;
    }
    
    // Validation des champs obligatoires
    if (!endData.end_number || endData.end_number < 1) {
        showValidationMessage('Le numéro de volée est obligatoire', 'error');
        return;
    }
    
    if (!endData.target_category) {
        showValidationMessage('La catégorie de cible est obligatoire', 'error');
        return;
    }
    
    // Vérifier si la position de tir est requise (pas pour les tirs en salle)
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (shootingType !== 'Salle' && !endData.shooting_position) {
        showValidationMessage('La position de tir est obligatoire', 'error');
        return;
    }
    
    // Validation du nombre maximum de volées
    const maxEnds = window.scoredTrainingData?.total_ends || 0;
    if (endData.end_number > maxEnds) {
        showValidationMessage(`Le nombre maximum de volées est ${maxEnds}`, 'error');
        return;
    }
    
    // Validation des scores selon le type de tir
    const scoreValidation = validateScores(scores, shootingType);
    if (!scoreValidation.valid) {
        showValidationMessage(scoreValidation.message, 'error');
        return;
    }
    
    console.log('Données de la volée:', endData);
    
    // Calculer le score total de la volée
    const totalScore = scores.reduce((sum, score) => sum + score, 0);
    
    // Préparer les données pour l'API (format attendu par le backend)
    const apiData = {
        end_number: endData.end_number,
        total_score: totalScore,
        comment: endData.comment,
        target_category: endData.target_category,
        shooting_position: endData.shooting_position || null,
        shots: scores.map((score, index) => ({
            arrow_number: index + 1,
            score: score
        }))
    };
    
    console.log('Données API:', apiData);
    console.log('ID du tir compté:', window.scoredTrainingData?.id);
    
    // Vérifier que l'ID est valide
    if (!window.scoredTrainingData?.id || window.scoredTrainingData.id === 0) {
        showValidationMessage('Erreur: ID du tir compté invalide', 'error');
        return;
    }
    
    // Envoyer les données via le backend WebApp2 (qui appelle l'API externe)
    fetch(`/scored-trainings/${window.scoredTrainingData.id}/ends`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(apiData)
    })
    .then(response => {
        console.log('Réponse HTTP:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            // Afficher un message de succès discret
            showValidationMessage(`Volée ${endData.end_number} enregistrée avec succès`, 'success');
            
            // Mémoriser les valeurs pour la prochaine volée
            lastTargetCategory = endData.target_category;
            lastShootingPosition = endData.shooting_position;
            
            // Préparer la prochaine volée
            prepareNextEnd();
        } else {
            showValidationMessage(`Erreur: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'enregistrement:', error);
        showValidationMessage('Erreur lors de l\'enregistrement de la volée', 'error');
    });
}

function prepareNextEnd() {
    console.log('🔄 Préparation de la volée suivante');
    
    // Vérifier si on a atteint le nombre maximum de volées
    const maxEnds = window.scoredTrainingData?.total_ends || 0;
    if (currentEndNumber >= maxEnds) {
        showValidationMessage(`Nombre maximum de volées atteint (${maxEnds}). Utilisez "Terminer" pour finaliser.`, 'info');
        return;
    }
    
    // Incrémenter le numéro de volée
    currentEndNumber++;
    
    // Mettre à jour le numéro de volée
    const endNumberInput = document.getElementById('end_number');
    if (endNumberInput) {
        endNumberInput.value = currentEndNumber;
    }
    
    // Restaurer la catégorie de cible mémorisée
    const targetCategorySelect = document.getElementById('target_category');
    if (targetCategorySelect && lastTargetCategory) {
        targetCategorySelect.value = lastTargetCategory;
    }
    
    // Restaurer la position de tir mémorisée (si applicable)
    const shootingPositionSelect = document.getElementById('shooting_position');
    if (shootingPositionSelect && lastShootingPosition) {
        shootingPositionSelect.value = lastShootingPosition;
    }
    
    // Vider le commentaire
    const commentInput = document.getElementById('comment');
    if (commentInput) {
        commentInput.value = '';
    }
    
    // Vider les scores
    const scoreInputs = document.querySelectorAll('.arrow-score');
    scoreInputs.forEach(input => {
        input.value = '';
    });
    
    // Focus sur le premier champ de score
    if (scoreInputs.length > 0) {
        scoreInputs[0].focus();
    }
    
    console.log('✅ Volée ' + currentEndNumber + ' prête');
}

function saveEndAndClose() {
    console.log('💾 saveEndAndClose appelée');
    
    // Sauvegarder la volée actuelle
    saveEnd();
    
    // Fermer le modal après un court délai
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addEndModal'));
        if (modal) {
            modal.hide();
        }
    }, 500);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé, initialisation des événements');
    console.log('🔍 window.scoredTrainingData:', window.scoredTrainingData);
    
    // Attacher l'événement de changement de type de tir
    const shootingTypeSelect = document.getElementById('shooting_type');
    if (shootingTypeSelect) {
        shootingTypeSelect.addEventListener('change', updateShootingConfiguration);
        console.log('✅ Event listener attaché au select shooting_type');
    } else {
        console.warn('⚠️ Select shooting_type non trouvé');
    }
});
