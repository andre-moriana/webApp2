/**
 * JavaScript pour la page de détails des tirs comptés
 * Gestion des modales, graphiques et interactions
 */

// scored-training-show.js chargé

// Variables globales
let trainingId, arrowsPerEnd, currentEnds, totalEnds;

// Fonction pour masquer les zones 1-5 en mode trispot
function updateTargetVisualStyle(targetCategory) {
    // Pour le tir campagne, NE RIEN FAIRE - le blason est fixe côté serveur
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (shootingType === 'Campagne') {
        console.log('🚫 Tir campagne : updateTargetVisualStyle BLOQUÉ - aucun redessinage');
        return; // NE RIEN FAIRE
    }
    
    const isTrispot = targetCategory.toLowerCase() === 'trispot';
    const targetSvg = document.getElementById('targetSvg');
    
    if (targetSvg) {
        // Restaurer le SVG standard (10 zones)
        generateStandardSVG(targetSvg);
        
        // Appliquer les modifications trispot si nécessaire
        if (isTrispot) {
            for (let i = 1; i <= 5; i++) {
                const zone = targetSvg.querySelector(`.zone-${i}`);
                if (zone) {
                    zone.setAttribute('fill', '#EEEEEE');
                    zone.setAttribute('stroke', 'none');
                    zone.setAttribute('stroke-width', '0');
                }
            }
        }
    }
}

function generateBlasonCampagneSVG(svgElement) {
    // Pour le tir campagne, NE RIEN FAIRE - le blason est fixe côté serveur
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (shootingType === 'Campagne') {
        console.log('🚫 Tir campagne : generateBlasonCampagneSVG BLOQUÉ - aucun redessinage');
        return; // NE RIEN FAIRE
    }
    
    console.log('🔥 generateBlasonCampagneSVG appelé');
    console.log('🔥 SVG avant vidage:', svgElement.innerHTML);
    
    // Vider complètement le SVG
    svgElement.innerHTML = '';
    console.log('🔥 SVG après vidage:', svgElement.innerHTML);
    
    // Générer les 6 zones du blason campagne
    const centerX = 150;
    const centerY = 150;
    const numRings = 6;
    const targetScale = numRings / (numRings + 1); // 6/7
    const outerRadius = 150 * targetScale; // 128.571428...
    const ringWidth = outerRadius / numRings; // 21.428571...
    
    console.log('🔥 Paramètres:', { centerX, centerY, numRings, outerRadius, ringWidth });
    
    // Palette blason campagne : zones 1-4 (noir), zones 5-6 (jaune)
    const colors = ['#212121', '#212121', '#212121', '#212121', '#FFD700', '#FFD700'];
    
    for (let i = 0; i < numRings; i++) {
        const radius = outerRadius - i * ringWidth;
        const color = colors[i];
        const strokeColor = 'white'; // Tous les traits sont blancs pour le blason campagne
        const zoneNumber = numRings - i; // Zone 6 (centre) à zone 1 (extérieur)
        
        console.log(`🔥 Création zone ${zoneNumber}: radius=${radius}, color=${color}`);
        
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', centerX);
        circle.setAttribute('cy', centerY);
        circle.setAttribute('r', radius);
        circle.setAttribute('fill', color);
        circle.setAttribute('stroke', strokeColor);
        circle.setAttribute('stroke-width', '0.5');
        circle.setAttribute('class', `zone-${zoneNumber}`);
        
        svgElement.appendChild(circle);
    }
    
    // Ajouter le groupe pour les flèches
    const arrowsGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    arrowsGroup.setAttribute('id', 'arrowsGroup');
    svgElement.appendChild(arrowsGroup);
    
    console.log('🔥 SVG final:', svgElement.innerHTML);
    console.log('🔥 Nombre d\'éléments créés:', svgElement.children.length);
}

function generateStandardSVG(svgElement) {
    // Pour le tir campagne, NE RIEN FAIRE - le blason est fixe côté serveur
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (shootingType === 'Campagne') {
        console.log('🚫 Tir campagne : generateStandardSVG BLOQUÉ - aucun redessinage');
        return; // NE RIEN FAIRE
    }
    
    // Vider le SVG
    svgElement.innerHTML = '';
    
    // Générer les 10 zones standard
    const centerX = 150;
    const centerY = 150;
    const numRings = 10;
    const targetScale = numRings / (numRings + 1); // 10/11
    const outerRadius = 150 * targetScale; // 136.363636...
    const ringWidth = outerRadius / numRings; // 13.6363636...
    
    // Palette standard
    const colors = ['white', 'white', 'black', 'black', 'blue', 'blue', 'red', 'red', 'yellow', 'yellow'];
    
    for (let i = 0; i < numRings; i++) {
        const radius = outerRadius - i * ringWidth;
        const color = colors[i];
        const zoneNumber = numRings - i; // Zone 10 (centre) à zone 1 (extérieur)
        
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', centerX);
        circle.setAttribute('cy', centerY);
        circle.setAttribute('r', radius);
        circle.setAttribute('fill', color);
        circle.setAttribute('stroke', 'black');
        circle.setAttribute('stroke-width', '1');
        circle.setAttribute('class', `zone-${zoneNumber}`);
        
        svgElement.appendChild(circle);
    }
    
    // Ajouter le groupe pour les flèches
    const arrowsGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    arrowsGroup.setAttribute('id', 'arrowsGroup');
    svgElement.appendChild(arrowsGroup);
}

// Variables pour mémoriser les valeurs du formulaire
let savedTargetCategory = '';
let savedShootingPosition = '';
let savedScoreMode = 'table'; // Mémoriser le mode de saisie sélectionné

// Variables pour la cible interactive
let targetScores = [];
let targetCoordinates = []; // Stocker les coordonnées x,y des flèches
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
        return;
    }
    
    if (!containerElement) {
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
        
        // Positionnement automatique pour le tir campagne
        if (targetCategorySelect && window.scoredTrainingData?.shooting_type === 'Campagne' && !savedTargetCategory) {
            targetCategorySelect.value = 'blason_campagne';
        }
        
        // Ajouter l'événement de changement pour masquer les zones en mode trispot
        if (targetCategorySelect) {
            targetCategorySelect.addEventListener('change', function() {
                const shootingType = window.scoredTrainingData?.shooting_type || '';
                
                // Pour le tir campagne, NE RIEN FAIRE (le blason est fixe côté serveur)
                if (shootingType === 'Campagne') {
                    console.log('🎯 Tir campagne : AUCUNE modification du blason, catégorie sélectionnée:', this.value);
                    return; // NE RIEN FAIRE
                }
                
                // Pour les autres types de tir, régénérer le blason
                updateTargetVisualStyle(this.value);
            });
            
            // NE PAS appliquer le style initial pour le tir campagne (le SVG est déjà généré côté serveur)
            const shootingType = window.scoredTrainingData?.shooting_type || '';
            if (shootingType !== 'Campagne') {
                updateTargetVisualStyle(targetCategorySelect.value);
            } else {
                console.log('🎯 Tir campagne : SVG déjà généré côté serveur, pas de régénération JavaScript');
            }
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
    
    // Déterminer le mode de saisie selon le type de tir
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    const shouldUseTableMode = shootingType === '3D' || shootingType === 'Nature';
    
    // Initialiser les champs selon le mode déterminé
    if (shouldUseTableMode) {
        // Forcer le mode tableau pour 3D et Nature (pas de cible interactive)
        const tableMode = document.getElementById('tableMode');
        const targetMode = document.getElementById('targetMode');
        
        if (targetMode && tableMode) {
            tableMode.checked = true;
            targetMode.checked = false;
            toggleScoreMode();
        }
        
        // Initialiser les champs de score en mode tableau
        initializeScoreFields();
    } else {
        // Mode selon la préférence utilisateur pour les autres types de tir
        if (savedScoreMode === 'table') {
            initializeScoreFields();
        } else {
            // Initialiser la cible interactive
            targetScores = new Array(arrowsPerEnd).fill(null);
            targetCoordinates = new Array(arrowsPerEnd).fill(null);
            updateScoresDisplay();
        }
    }
    
    // Initialiser le numéro de volée - toujours mettre à jour
    if (endNumberInput) {
        // Utiliser le nombre de lignes dans le tableau + 1
        const existingRows = document.querySelectorAll('.table-ends tbody tr').length;
        endNumberInput.value = existingRows + 1;
        
        // Déclencher la réévaluation du blocage après la mise à jour automatique
        setTimeout(() => {
            const targetCategorySelect = form.querySelector('select[name="target_category"]');
            if (targetCategorySelect) {
                const shootingType = window.scoredTrainingData?.shooting_type || '';
                const endNumber = parseInt(endNumberInput.value) || 1;
                const canModifyTarget = !(shootingType === 'Salle' || shootingType === 'TAE' || shootingType === 'Libre') || endNumber <= 1;
                
                if (!canModifyTarget) {
                    targetCategorySelect.disabled = true;
                    targetCategorySelect.title = 'Le type de blason ne peut pas être modifié après la première volée pour ce type de tir';
                } else {
                    targetCategorySelect.disabled = false;
                    targetCategorySelect.title = '';
                }
            }
        }, 100);
    }
    
    // Bloquer le type de blason APRÈS la mise à jour du numéro de volée
    const targetCategorySelect = form.querySelector('select[name="target_category"]');
    if (targetCategorySelect) {
        const shootingType = window.scoredTrainingData?.shooting_type || '';
        const endNumber = parseInt(endNumberInput?.value || '1');
        const canModifyTarget = !(shootingType === 'Salle' || shootingType === 'TAE' || shootingType === 'Libre') || endNumber <= 1;
        
        
        if (!canModifyTarget) {
            targetCategorySelect.disabled = true;
            targetCategorySelect.title = 'Le type de blason ne peut pas être modifié après la première volée pour ce type de tir';
        } else {
            targetCategorySelect.disabled = false;
            targetCategorySelect.title = '';
        }
    }
    
    // Mettre à jour la visibilité des boutons selon le numéro de volée
    if (endNumberInput) {
        const endNumber = parseInt(endNumberInput.value) || 1;
        updateButtonVisibility(endNumber);
    }
    
    // Ajouter un événement pour écouter les changements du numéro de volée
    if (endNumberInput) {
        
        const updateTargetCategory = function() {
            const endNumber = parseInt(endNumberInput.value) || 1;
            updateButtonVisibility(endNumber);
            
            // Bloquer/débloquer la catégorie de cible selon le numéro de volée
            const targetCategorySelect = form.querySelector('select[name="target_category"]');
            
            if (targetCategorySelect) {
                const shootingType = window.scoredTrainingData?.shooting_type || '';
                const canModifyTarget = !(shootingType === 'Salle' || shootingType === 'TAE' || shootingType === 'Libre') || endNumber <= 1;
                
                if (!canModifyTarget) {
                    targetCategorySelect.disabled = true;
                    targetCategorySelect.title = 'Le type de blason ne peut pas être modifié après la première volée pour ce type de tir';
                } else {
                    targetCategorySelect.disabled = false;
                    targetCategorySelect.title = '';
                }
            }
        };
        
        endNumberInput.addEventListener('input', updateTargetCategory);
        endNumberInput.addEventListener('change', updateTargetCategory);
        endNumberInput.addEventListener('keyup', updateTargetCategory);
        endNumberInput.addEventListener('paste', updateTargetCategory);
        
        // Appeler la fonction au chargement initial
        updateTargetCategory();
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
        // Essayer avec jQuery si disponible
        if (typeof $ !== 'undefined') {
            $(modalElement).modal('show');
        } else {
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
    
    // Récupérer les données AVANT de bloquer le select
    const formData = new FormData(form);
    
    // BLOQUER LE SELECT À L'APPUI DU BOUTON ENREGISTRER
    const targetCategorySelect = form.querySelector('select[name="target_category"]');
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    
    if (targetCategorySelect && (shootingType === 'Salle' || shootingType === 'TAE' || shootingType === 'Libre')) {
        targetCategorySelect.disabled = true;
    }
    
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
        // Mode cible interactive - utiliser les données complètes avec coordonnées
        const targetData = getTargetData();
        
        // Compléter avec des flèches manquées si nécessaire
        while (targetData.length < arrowsPerEnd) {
            targetData.push({
                arrow_number: targetData.length + 1,
                score: 0,
                hit_x: null,
                hit_y: null
            });
        }
        
        // Extraire les scores pour le calcul du total
        scores = targetData.map(shot => shot.score);
    } else {
        alert('Veuillez sélectionner un mode de saisie');
        return;
    }
    
    // Calculer le total des scores
    const totalScore = scores.reduce((sum, score) => sum + score, 0);
    
    // Vérifier si le type de blason est sélectionné (obligatoire)
    if (targetCategorySelect && !targetCategorySelect.value) {
        alert('Veuillez sélectionner un type de blason');
        targetCategorySelect.focus();
        return;
    }
    
    // Transformer les scores en structure attendue par l'API
    let shots;
    if (targetMode && targetMode.checked) {
        // Utiliser les données complètes avec coordonnées
        shots = getTargetData();
    } else {
        // Mode tableau - structure simple
        shots = scores.map((score, index) => ({
            arrow_number: index + 1,
            score: score
        }));
    }
    
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
                targetCoordinates = new Array(arrowsPerEnd).fill(null);
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
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
            
            // Réactiver le bouton en cas d'erreur
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    })
    .catch(error => {
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
        return;
    }
    
    // Récupérer les données AVANT de bloquer le select
    const formData = new FormData(form);
    
    // BLOQUER LE SELECT À L'APPUI DU BOUTON ENREGISTRER
    const targetCategorySelect = form.querySelector('select[name="target_category"]');
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    
    if (targetCategorySelect && (shootingType === 'Salle' || shootingType === 'TAE' || shootingType === 'Libre')) {
        targetCategorySelect.disabled = true;
    }
    
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
        // Mode cible interactive - utiliser les données complètes avec coordonnées
        const targetData = getTargetData();
        
        // Compléter avec des flèches manquées si nécessaire
        while (targetData.length < arrowsPerEnd) {
            targetData.push({
                arrow_number: targetData.length + 1,
                score: 0,
                hit_x: null,
                hit_y: null
            });
        }
        
        // Extraire les scores pour le calcul du total
        scores = targetData.map(shot => shot.score);
        
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
    let shots;
    if (targetMode && targetMode.checked) {
        // Utiliser les données complètes avec coordonnées
        shots = getTargetData();
    } else {
        // Mode tableau - structure simple
        shots = scores.map((score, index) => ({
            arrow_number: index + 1,
            score: score
        }));
    }
    
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
        }
    })
    .catch(error => {
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
        return;
    }
    
    // Vérifier que trainingId est défini
    if (!trainingId || trainingId === 0) {
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
//            alert('Tir compté finalisé avec succès ! La page va se recharger.');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
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

// ===== FONCTIONS POUR LA CIBLE INTERACTIVE =====

// Fonction pour calculer le score basé sur la position du clic
function calculateScoreFromPosition(x, y) {
    const centerX = 150; // Centre du viewBox 300x300 (même que l'app mobile)
    const centerY = 150;
    const distance = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    
    // Déterminer le type de cible
    const targetCategorySelect = document.querySelector('select[name="target_category"]');
    const targetCategory = targetCategorySelect ? targetCategorySelect.value : 'blason_40';
    
    // Pour le blason campagne, détecter par le type de tir, pas par la catégorie
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    const isBlasonCampagne = shootingType === 'Campagne';
    
    let zones;
    
    if (isBlasonCampagne) {
        // Zones pour le blason campagne : 6 zones
        // RINGS = 6, targetScale = 6/7, outerRadius = 150 * (6/7) = 128.571428...
        // ringWidth = outerRadius / RINGS = 128.571428 / 6 = 21.428571...
        // Épaisseur du trait = 1px, diamètre du point d'impact = 4px (rayon = 2px)
        
        zones = [
            { radius: 21.428571 , score: 6 },    // Zone 6 (centre jaune)
            { radius: 42.857143 , score: 5 },    // Zone 5 (jaune)
            { radius: 64.285714 , score: 4 },    // Zone 4 (noir)
            { radius: 85.714286 , score: 3 },    // Zone 3 (noir)
            { radius: 107.142857 , score: 2 },   // Zone 2 (noir)
            { radius: 128.571428 , score: 1 },   // Zone 1 (noir) - plus grand rayon
            { radius: Infinity, score: 0 }      // Manqué
        ];
    } else {
        // Rayons des zones (en unités SVG) - EXACTES calculs de l'app mobile
        // RINGS = 10, targetScale = 10/11, outerRadius = 150 * (10/11) = 136.363636...
        // ringWidth = outerRadius / RINGS = 136.363636 / 10 = 13.6363636...
        zones = [
            { radius: 13.636364, score: 10 },    // Zone 10 (centre jaune)
            { radius: 27.272727, score: 9 },     // Zone 9 (jaune)
            { radius: 40.909091, score: 8 },     // Zone 8 (rouge)
            { radius: 54.545455, score: 7 },     // Zone 7 (rouge)
            { radius: 68.181818, score: 6 },    // Zone 6 (bleu)
            { radius: 81.818182, score: 5 },    // Zone 5 (bleu)
            { radius: 95.454545, score: 4 },    // Zone 4 (noir)
            { radius: 109.090909, score: 3 },   // Zone 3 (noir)
            { radius: 122.727273, score: 2 },   // Zone 2 (blanc)
            { radius: 136.363636, score: 1 },   // Zone 1 (blanc)
            { radius: Infinity, score: 0 }      // Manqué
        ];
    }
    
    // Logique avec épaisseur de trait : trouver la zone en tenant compte de l'épaisseur des traits
    // L'épaisseur des traits est de 0.6 selon la cible SVG
    const strokeWidth = 0.6;
    
    for (let i = 0; i < zones.length - 1; i++) {
        const currentZone = zones[i];
        const nextZone = zones[i + 1];
        
        // Si la distance est dans la zone actuelle (en tenant compte de l'épaisseur complète du trait)
        if (distance <= (currentZone.radius + strokeWidth)) {
            // Vérifier si on est sur le trait de séparation
            if (distance >= (currentZone.radius - strokeWidth)) {
                // On est sur le trait, prendre la zone extérieure (actuelle)
                return currentZone.score;
            } else {
                // On est dans la zone, prendre la zone actuelle
                return currentZone.score;
            }
        }
    }
    
    // Si on arrive ici, on est dans la zone la plus extérieure
    let finalScore = zones[zones.length - 1].score;
    
    // Règle spécifique TRISPOT: seules les zones 6 à 10 scorent, le reste est considéré comme manqué (0)
    if (targetCategory.toLowerCase() === 'trispot') {
        if (finalScore < 6) {
            finalScore = 0;
        }
    }
    
    return finalScore;
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
    
    // Convertir les coordonnées absolues en coordonnées relatives au centre
    const centerX = 150; // Centre de la cible SVG
    const centerY = 150;
    const relativeX = x - centerX;
    const relativeY = y - centerY;
    
    // Ajouter le score et les coordonnées relatives à la liste
    targetScores[arrowIndex] = score;
    targetCoordinates[arrowIndex] = { x: relativeX, y: relativeY };
    updateScoresDisplay();
}

// Fonction pour supprimer une flèche de la cible
function removeArrowFromTarget(arrowIndex) {
    const arrowsGroup = document.getElementById('arrowsGroup');
    const arrowElement = arrowsGroup.querySelector(`[data-arrow-index="${arrowIndex}"]`);
    
    if (arrowElement) {
        arrowElement.remove();
    }
    
    // Supprimer le score et les coordonnées de la liste
    targetScores[arrowIndex] = null;
    targetCoordinates[arrowIndex] = null;
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
        justFinishedDragging = false;
        return;
    }
    
    // Protection contre les doubles clics avec debounce
    const currentTime = Date.now();
    if (currentTime - lastClickTime < clickDebounceDelay) {
        return;
    }
    lastClickTime = currentTime;
    
    // Protection contre les doubles clics
    if (event.detail > 1) {
        return;
    }
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    const rect = svg.getBoundingClientRect();
    // Convertir les coordonnées en coordonnées SVG (0-300)
    const x = ((event.clientX - rect.left) / rect.width) * 300;
    const y = ((event.clientY - rect.top) / rect.height) * 300;
    
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
    
    addArrowToTarget(x, y, score, arrowIndex);
}

// Fonction pour gérer le début du drag
function handleTargetMouseDown(event) {
    if (isDragging) return;
    
    const svg = document.getElementById('targetSvg');
    if (!svg) return;
    
    isDragging = true;
    const rect = svg.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 300;
    const y = ((event.clientY - rect.top) / rect.height) * 300;
    
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
    const x = ((event.clientX - rect.left) / rect.width) * 300;
    const y = ((event.clientY - rect.top) / rect.height) * 300;
    
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
    const x = ((event.clientX - rect.left) / rect.width) * 300;
    const y = ((event.clientY - rect.top) / rect.height) * 300;
    
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
        return; // Ne pas afficher la loupe si le curseur est en dehors
    }
    
    // Calculer la position relative dans le SVG (0-120)
    const x = ((mouseX - rect.left) / rect.width) * 300;
    const y = ((mouseY - rect.top) / rect.height) * 300;
    
    // Créer un SVG cloné pour la loupe
    const clonedSvg = svg.cloneNode(true);
    
    // Appliquer le zoom x3 et centrer sur le point pointé
    // Pour centrer le point (x,y) au centre de la loupe (75,75)
    // Le point (x,y) doit être au centre de la loupe, donc on déplace l'image
    const offsetX = 75 - x; // Déplacement pour centrer le point (x,y) au centre (75,75)
    const offsetY = 75 - y; // Déplacement pour centrer le point (x,y) au centre (75,75)
    
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
    const x = ((mouseX - rect.left) / rect.width) * 300;
    const y = ((mouseY - rect.top) / rect.height) * 300;
    
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
        const svgX = ((x / 300) * rect.width) + rect.left;
        const svgY = ((y / 300) * rect.height) + rect.top;
        
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
    targetCoordinates = new Array(arrowsPerEnd).fill(null);
    updateScoresDisplay();
}

// Fonction pour basculer entre les modes de saisie
function toggleScoreMode() {
    const tableMode = document.getElementById('tableMode');
    const targetMode = document.getElementById('targetMode');
    const tableContainer = document.getElementById('tableModeContainer');
    const targetContainer = document.getElementById('targetModeContainer');
    
    if (!tableMode || !targetMode || !tableContainer || !targetContainer) return;
    
    // Vérifier le type de tir pour empêcher l'utilisation de la cible interactive
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (shootingType === '3D' || shootingType === 'Nature') {
        // Forcer le mode tableau pour 3D et Nature
        tableMode.checked = true;
        targetMode.checked = false;
        tableContainer.style.display = 'block';
        targetContainer.style.display = 'none';
        initializeScoreFields();
        return;
    }
    
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
            targetCoordinates = new Array(arrowsPerEnd).fill(null);
        }
        updateScoresDisplay();
    }
}

// Fonction pour obtenir les scores et coordonnées depuis la cible
function getTargetScores() {
    return targetScores.filter(score => score !== null && score !== undefined);
}

// Fonction pour obtenir les données complètes des flèches (scores + coordonnées)
function getTargetData() {
    const data = [];
    for (let i = 0; i < targetScores.length; i++) {
        if (targetScores[i] !== null && targetScores[i] !== undefined) {
            // Les coordonnées sont déjà relatives au centre
            data.push({
                arrow_number: i + 1,
                score: targetScores[i],
                hit_x: targetCoordinates[i] ? targetCoordinates[i].x : null,
                hit_y: targetCoordinates[i] ? targetCoordinates[i].y : null
            });
        }
    }
    return data;
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

// Initialiser l'application quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    initializeTrainingData();
    createScoresChart();
    
    // Filtrer les notes pour masquer les informations de signature
    // Chercher l'élément qui contient les notes (après le titre "Notes:")
    const infoCard = document.querySelector('.card.detail-card');
    if (infoCard) {
        const notesSection = Array.from(infoCard.querySelectorAll('h6')).find(h6 => h6.textContent.trim() === 'Notes:');
        if (notesSection) {
            const notesElement = notesSection.nextElementSibling;
            if (notesElement && notesElement.classList.contains('text-muted')) {
                // Récupérer le texte original (en remplaçant les <br> par des \n)
                const originalHTML = notesElement.innerHTML;
                const originalNotes = originalHTML.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '').trim();
                const filteredNotes = filterNotesForDisplay(originalNotes);
                if (filteredNotes !== originalNotes) {
                    // Reconstruire le HTML avec les <br> pour les retours à la ligne
                    notesElement.innerHTML = filteredNotes ? filteredNotes.replace(/\n/g, '<br>') : 'Aucune note';
                }
            }
        }
    }
    
    // Déterminer le type de blason selon le type de tir
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    const targetCategorySelect = document.getElementById('targetCategorySelect');
    
    // Déterminer le type de cible selon le type de tir
    let targetCategory = 'blason_80'; // Valeur par défaut
    if (shootingType === 'Campagne') {
        targetCategory = 'blason_campagne';
    } else if (shootingType === 'Salle') {
        targetCategory = 'trispot';
    } else if (shootingType === 'TAE') {
        targetCategory = 'blason_122';
    }
    
    // Positionner le sélecteur
    if (targetCategorySelect) {
        targetCategorySelect.value = targetCategory;
    }
    
    // NE PAS générer le blason au chargement - il est déjà généré côté serveur PHP
    // Le JavaScript ne doit intervenir que quand l'utilisateur change le type de cible
    
    // Écouter les changements du type de cible
    if (targetCategorySelect) {
        targetCategorySelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            const shootingType = window.scoredTrainingData?.shooting_type || '';
            
            // Pour le tir campagne, NE RIEN FAIRE (le blason est fixe côté serveur)
            if (shootingType === 'Campagne') {
                console.log('🎯 Tir campagne : AUCUNE modification du blason, catégorie sélectionnée:', selectedCategory);
                return; // NE RIEN FAIRE
            }
            
            // Pour les autres types de tir, régénérer le blason
            updateTargetVisualStyle(selectedCategory);
        });
    }
    
    // Masquer la cible interactive pour les types de tir 3D et Nature
    if (shootingType === '3D' || shootingType === 'Nature') {
        // Masquer les options de mode de saisie pour ces types de tir
        const scoreModeContainer = document.querySelector('.score-mode-container');
        if (scoreModeContainer) {
            scoreModeContainer.style.display = 'none';
        }
        
        // Forcer le mode tableau pour ces types de tir
        const targetMode = document.getElementById('targetMode');
        const tableMode = document.getElementById('tableMode');
        
        if (targetMode && tableMode) {
            tableMode.checked = true;
            targetMode.checked = false;
            toggleScoreMode();
        }
    }
    
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
window.showEndTarget = showEndTarget;


// Fonction pour afficher la cible d'une volée spécifique
function showEndTarget(endNumber) {
    // Trouver les données de la volée
    const endData = window.endsData.find(end => end.end_number === endNumber);
    if (!endData) {
        alert('Données de la volée non trouvées');
        return;
    }
    
    // Créer les hits pour l'affichage
    const endHits = endData.shots.map(shot => ({
        hit_x: shot.hit_x,
        hit_y: shot.hit_y,
        score: shot.score,
        arrow_number: shot.arrow_number
    }));
    
    // Créer la cible interactive avec le type de cible enregistré
    const targetContainer = document.getElementById('interactiveTarget');
    if (targetContainer) {
        // Vider le conteneur
        targetContainer.innerHTML = '';
        
        // Déterminer le type de cible selon le type de tir
        const shootingType = window.scoredTrainingData?.shooting_type || '';
        let targetCategory = 'blason_80'; // Valeur par défaut
        
        if (shootingType === 'Campagne') {
            targetCategory = 'blason_campagne';
        } else if (shootingType === 'Salle') {
            targetCategory = 'trispot';
        } else if (shootingType === 'TAE') {
            targetCategory = 'blason_122';
        }
        
        console.log('🎯 Modal - Type de tir:', shootingType);
        console.log('🎯 Modal - Type de cible:', targetCategory);
        
        // Créer la cible SVG
        const target = createSVGTarget('interactiveTarget', endHits, {
            size: 300,
            targetCategory: targetCategory
        });
        
        // Afficher la modal
        const modal = new bootstrap.Modal(document.getElementById('targetModal'));
        modal.show();
    } else {
        console.error('Container interactiveTarget not found');
    }
}

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
