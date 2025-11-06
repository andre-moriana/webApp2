/**
 * JavaScript pour la feuille de marque
 */

// Configuration des types de tir
const SHOOTING_CONFIGS = {
    'Salle': { total_ends: 20, arrows_per_end: 3, total_arrows: 60, series: 2 },
    'TAE': { total_ends: 12, arrows_per_end: 6, total_arrows: 72, series: 2 },
    'Nature': { total_ends: 21, arrows_per_end: 2, total_arrows: 42 },
    '3D': { total_ends: 24, arrows_per_end: 2, total_arrows: 48 },
    'Campagne': { total_ends: 24, arrows_per_end: 3, total_arrows: 72 },
};

const NUM_USERS = 6;

// Données globales
let selectedShootingType = '';
let trainingTitle = '';
let currentUserIndex = 0;
let userSheets = [];
let scoreModal = null;
let currentModalRow = null;
let currentModalUserIndex = null;
let archerSignatures = {}; // Stocker les signatures des archers
let scorerSignature = ''; // Signature du marqueur
let showSignatureModal = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeScoreSheet();
    initializeSignatureCanvas();
});

function initializeScoreSheet() {
    const shootingTypeSelect = document.getElementById('shootingType');
    const trainingTitleInput = document.getElementById('trainingTitle');
    
    if (shootingTypeSelect) {
        shootingTypeSelect.addEventListener('change', function() {
            selectedShootingType = this.value;
            if (selectedShootingType) {
                initializeSheets();
            }
        });
    }
    
    if (trainingTitleInput) {
        trainingTitleInput.addEventListener('input', function() {
            trainingTitle = this.value;
        });
    }
    
    // Recherche d'utilisateur par numéro de licence - utiliser délégation d'événements
    // pour que ça fonctionne même si le champ est caché au départ
    setupLicenseSearch();
    
    // Initialiser le modal Bootstrap
    const modalElement = document.getElementById('scoreModal');
    if (modalElement) {
        scoreModal = new bootstrap.Modal(modalElement);
    }
}

// Variables pour la recherche par licence
let licenseSearchTimeout = null;
let isLicenseSearching = false;

// Configurer la recherche par numéro de licence
function setupLicenseSearch() {
    // Utiliser la délégation d'événements sur le conteneur parent
    const archerInfoSection = document.getElementById('archerInfoSection');
    if (!archerInfoSection) {
        // Si la section n'existe pas encore, réessayer après un court délai
        setTimeout(setupLicenseSearch, 100);
        return;
    }
    
    // Supprimer l'ancien écouteur s'il existe en ajoutant un flag
    if (archerInfoSection.hasAttribute('data-search-setup')) {
        return; // Déjà configuré
    }
    
    // Marquer comme configuré
    archerInfoSection.setAttribute('data-search-setup', 'true');
    
    // Utiliser la délégation d'événements
    archerInfoSection.addEventListener('input', function(e) {
        // Vérifier si c'est le champ de licence
        if (e.target.id === 'archerLicense') {
            const licenseNumber = e.target.value.trim();
            
            // Retirer l'indicateur de recherche précédent
            const existingIndicator = e.target.parentElement.querySelector('.search-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Délai de 500ms après la dernière saisie
            if (licenseSearchTimeout) {
                clearTimeout(licenseSearchTimeout);
            }
            
            if (licenseNumber.length >= 3 && !isLicenseSearching) {
                // Afficher un indicateur de recherche
                const indicator = document.createElement('span');
                indicator.className = 'search-indicator ms-2';
                indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
                e.target.parentElement.appendChild(indicator);
                
                licenseSearchTimeout = setTimeout(() => {
                    isLicenseSearching = true;
                    console.log('Déclenchement de la recherche pour:', licenseNumber);
                    searchUserByLicense(licenseNumber).finally(() => {
                        isLicenseSearching = false;
                        if (indicator.parentElement) {
                            indicator.remove();
                        }
                    });
                }, 500);
            } else if (licenseNumber.length < 3) {
                // Retirer l'indicateur si moins de 3 caractères
                const indicator = e.target.parentElement.querySelector('.search-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
        }
    });
}

// Rechercher un utilisateur par numéro de licence
async function searchUserByLicense(licenseNumber) {
    if (!licenseNumber || licenseNumber.length < 3) {
        return Promise.resolve();
    }
    
    try {
        const url = `/api/users?licence_number=${encodeURIComponent(licenseNumber.trim())}`;
        console.log('Recherche utilisateur par licence:', url);
        
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            console.log('Réponse HTTP non OK:', response.status, response.statusText);
            if (response.status === 404) {
                // Utilisateur non trouvé - c'est normal, pas d'erreur
                return Promise.resolve();
            }
            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
        
        const responseText = await response.text();
        console.log('Réponse brute (complète):', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.error('Texte reçu:', responseText);
            throw new Error('Réponse invalide du serveur');
        }
        
        console.log('Données parsées:', data);
        console.log('Structure data:', {
            success: data.success,
            hasData: !!data.data,
            dataType: typeof data.data,
            dataKeys: data.data ? Object.keys(data.data) : []
        });
        
        if (data.success && data.data) {
            const user = data.data;
            console.log('Utilisateur trouvé (complet):', JSON.stringify(user, null, 2));
            console.log('Champs utilisateur disponibles:', Object.keys(user));
            console.log('bow_type:', user.bow_type);
            console.log('bowType:', user.bowType);
            console.log('weapon:', user.weapon);
            
            // Remplir automatiquement les informations
            const nameField = document.getElementById('archerName');
            const categoryField = document.getElementById('archerCategory');
            const weaponField = document.getElementById('archerWeapon');
            const genderField = document.getElementById('archerGender');
            
            if (nameField) {
                // Construire le nom complet en concaténant first_name et name
                // La table users a: name (nom de famille) et first_name (prénom)
                let fullName = '';
                if (user.firstName || user.first_name) {
                    fullName = (user.firstName || user.first_name || '');
                }
                if (user.name) {
                    const name = user.name || '';
                    fullName = fullName ? `${fullName} ${name}` : name;
                }
                // Si pas de prénom/nom, utiliser name seul
                if (!fullName && user.name) {
                    fullName = user.name;
                }
                nameField.value = fullName;
            }
            
            if (categoryField) {
                const category = user.age_category || user.ageCategory || '';
                if (category) {
                    categoryField.value = category;
                }
            }
            
            if (weaponField) {
                // Récupérer bow_type depuis l'API
                const weaponRaw = user.bow_type || user.bowType || '';
                
                // Mapping entre les valeurs de la base de données et les options du select
                const weaponMapping = {
                    'Classique': 'Arc classique',
                    'classique': 'Arc classique',
                    'Arc classique': 'Arc classique',
                    'Poulies': 'Arc à poulies',
                    'poulies': 'Arc à poulies',
                    'Arc à poulies': 'Arc à poulies',
                    'Barebow': 'Arc nu (barebow)',
                    'barebow': 'Arc nu (barebow)',
                    'Arc nu (barebow)': 'Arc nu (barebow)',
                    'Arc nu': 'Arc nu (barebow)',
                    'Longbow': 'Longbow',
                    'longbow': 'Longbow',
                    'Chasse': 'Arc de chasse',
                    'chasse': 'Arc de chasse',
                    'Arc de chasse': 'Arc de chasse'
                };
                
                // Mapper la valeur si elle existe dans le mapping, sinon utiliser la valeur brute
                const weapon = weaponMapping[weaponRaw] || weaponRaw;
                
                console.log('Valeur weapon brute:', weaponRaw);
                console.log('Valeur weapon mappée:', weapon);
                
                if (weapon && weapon !== 'null' && weapon !== '') {
                    // Vérifier si la valeur existe dans les options du select
                    const options = Array.from(weaponField.options).map(opt => opt.value);
                    if (options.includes(weapon)) {
                        weaponField.value = weapon;
                        console.log('Champ weapon rempli avec:', weapon);
                    } else {
                        console.warn('Valeur weapon non trouvée dans les options du select:', weapon);
                        console.warn('Options disponibles:', options);
                        // Essayer de trouver une correspondance partielle
                        const partialMatch = options.find(opt => 
                            opt.toLowerCase().includes(weaponRaw.toLowerCase()) || 
                            weaponRaw.toLowerCase().includes(opt.toLowerCase().replace('arc ', '').replace('(', '').replace(')', ''))
                        );
                        if (partialMatch) {
                            weaponField.value = partialMatch;
                            console.log('Correspondance partielle trouvée:', partialMatch);
                        }
                    }
                } else {
                    console.warn('Aucune valeur weapon trouvée dans les données utilisateur');
                }
            }
            
            if (genderField && user.gender) {
                // Convertir différents formats de genre
                const gender = user.gender.toUpperCase();
                if (gender === 'M' || gender === 'HOMME' || gender === 'H') {
                    genderField.value = 'H';
                } else if (gender === 'F' || gender === 'FEMME' || gender === 'F') {
                    genderField.value = 'F';
                } else {
                    genderField.value = user.gender;
                }
            }
            
            // Sauvegarder l'ID utilisateur pour la sauvegarde
            if (userSheets[currentUserIndex]) {
                userSheets[currentUserIndex].userId = user.id;
            }
            
            showStatus('Utilisateur trouvé et informations remplies automatiquement', 'success');
        } else {
            console.log('Utilisateur non trouvé ou format de réponse incorrect:', data);
            // Utilisateur non trouvé, mais on continue silencieusement
            if (userSheets[currentUserIndex]) {
                userSheets[currentUserIndex].userId = null;
            }
        }
        
        return Promise.resolve();
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
        showStatus('Erreur lors de la recherche d\'utilisateur: ' + error.message, 'danger');
        return Promise.resolve(); // Ne pas bloquer l'interface
    }
}

// Initialiser le canvas de signature
function initializeSignatureCanvas() {
    // Le canvas sera créé dynamiquement dans le modal
}

function initializeSheets() {
    if (!selectedShootingType || !SHOOTING_CONFIGS[selectedShootingType]) {
        return;
    }
    
    const config = SHOOTING_CONFIGS[selectedShootingType];
    userSheets = [];
    
    // Créer les feuilles pour chaque archer
    for (let userIndex = 0; userIndex < NUM_USERS; userIndex++) {
        const rows = [];
        
        for (let i = 1; i <= config.total_ends; i++) {
            rows.push({
                endNumber: i,
                arrows: Array(config.arrows_per_end).fill(null).map(() => ({ value: 0 })),
                endTotal: 0,
                cumulativeTotal: 0,
                seriesNumber: config.series ? (i <= config.total_ends / config.series ? 1 : 2) : undefined
            });
        }
        
        userSheets.push({
            archerInfo: {
                name: `Archer ${userIndex + 1}`,
                licenseNumber: '',
                category: '',
                weapon: '',
                gender: ''
            },
            scoreRows: rows
        });
    }
    
    // Afficher l'interface
    document.getElementById('archerNavigation').style.display = 'block';
    document.getElementById('archerInfoSection').style.display = 'block';
    document.getElementById('scoreTableSection').style.display = 'block';
    document.getElementById('saveScoreSheetBtn').style.display = 'block';
    document.getElementById('signaturesBtn').style.display = 'block';
    document.getElementById('exportPdfBtn').style.display = 'block';
    
    // Réinitialiser la recherche par licence maintenant que la section est visible
    setupLicenseSearch();
    
    // Afficher le premier archer
    currentUserIndex = 0;
    displayCurrentArcher();
}

function displayCurrentArcher() {
    if (userSheets.length === 0) return;
    
    const sheet = userSheets[currentUserIndex];
    const config = SHOOTING_CONFIGS[selectedShootingType];
    
    // Mettre à jour les informations de l'archer
    document.getElementById('archerHeaderNumber').textContent = currentUserIndex + 1;
    document.getElementById('currentArcherNumber').textContent = currentUserIndex + 1;
    document.getElementById('totalArchers').textContent = NUM_USERS;
    
    document.getElementById('archerName').value = sheet.archerInfo.name;
    document.getElementById('archerLicense').value = sheet.archerInfo.licenseNumber;
    document.getElementById('archerCategory').value = sheet.archerInfo.category;
    document.getElementById('archerWeapon').value = sheet.archerInfo.weapon;
    document.getElementById('archerGender').value = sheet.archerInfo.gender;
    
    // Mettre à jour le tableau des scores
    updateScoreTable(sheet);
    
    // Gérer les boutons de navigation
    document.getElementById('prevArcherBtn').disabled = currentUserIndex === 0;
    document.getElementById('nextArcherBtn').disabled = currentUserIndex === NUM_USERS - 1;
}

function updateScoreTable(sheet) {
    const config = SHOOTING_CONFIGS[selectedShootingType];
    const tableBody = document.getElementById('scoreTableBody');
    const arrowHeaders = document.getElementById('arrowHeaders');
    const cumulativeHeader = document.getElementById('cumulativeHeader');
    
    // Nettoyer le tableau
    tableBody.innerHTML = '';
    
    // Générer les en-têtes de flèches
    let arrowHeadersHtml = '';
    for (let i = 1; i <= config.arrows_per_end; i++) {
        arrowHeadersHtml += `<th class="arrow-col">F${i}</th>`;
    }
    arrowHeaders.innerHTML = arrowHeadersHtml;
    arrowHeaders.setAttribute('colspan', config.arrows_per_end);
    
    // Afficher/masquer la colonne cumul selon le type de tir
    const hasSeries = config.series === 2;
    if (hasSeries) {
        cumulativeHeader.style.display = 'table-cell';
        document.getElementById('footerCumulative').style.display = 'table-cell';
    } else {
        cumulativeHeader.style.display = 'none';
        document.getElementById('footerCumulative').style.display = 'none';
    }
    
    // Générer les lignes
    let cumulative = 0;
    let series1Total = 0;
    let series2Total = 0;
    let currentSeries = 1;
    
    sheet.scoreRows.forEach((row, index) => {
        // Recalculer les totaux
        row.endTotal = row.arrows.reduce((sum, arrow) => sum + (arrow.value || 0), 0);
        
        // Déterminer la série
        if (hasSeries) {
            const endsPerSeries = config.total_ends / config.series;
            row.seriesNumber = Math.floor(index / endsPerSeries) + 1;
            
            // Réinitialiser le cumul à chaque nouvelle série
            if (row.seriesNumber !== currentSeries) {
                cumulative = 0;
                currentSeries = row.seriesNumber;
            }
        }
        
        cumulative += row.endTotal;
        row.cumulativeTotal = cumulative;
        
        // Ajouter au total de la série
        if (row.seriesNumber === 1) {
            series1Total += row.endTotal;
        } else if (row.seriesNumber === 2) {
            series2Total += row.endTotal;
        }
        
        const rowHtml = document.createElement('tr');
        rowHtml.className = hasSeries && row.seriesNumber === 2 ? 'second-series' : '';
        rowHtml.innerHTML = `
            <td class="volley-col">
                <button class="btn btn-sm btn-outline-primary" onclick="openScoreModal(${index})">
                    ${row.endNumber}
                </button>
            </td>
            ${row.arrows.map((arrow, arrowIndex) => `
                <td class="arrow-col">${arrow.value > 0 ? arrow.value : ''}</td>
            `).join('')}
            <td class="total-col">${row.endTotal}</td>
            ${hasSeries ? `<td class="cumulative-col">${row.cumulativeTotal}</td>` : ''}
        `;
        tableBody.appendChild(rowHtml);
    });
    
    // Ajouter les lignes de sous-totaux par série si nécessaire
    if (hasSeries) {
        const series1Row = document.createElement('tr');
        series1Row.className = 'series-total';
        series1Row.innerHTML = `
            <td class="volley-col"><strong>Série 1</strong></td>
            ${Array(config.arrows_per_end).fill('<td></td>').join('')}
            <td class="total-col"><strong>${series1Total}</strong></td>
            <td class="cumulative-col"></td>
        `;
        tableBody.appendChild(series1Row);
        
        const series2Row = document.createElement('tr');
        series2Row.className = 'series-total';
        series2Row.innerHTML = `
            <td class="volley-col"><strong>Série 2</strong></td>
            ${Array(config.arrows_per_end).fill('<td></td>').join('')}
            <td class="total-col"><strong>${series2Total}</strong></td>
            <td class="cumulative-col"></td>
        `;
        tableBody.appendChild(series2Row);
    }
    
    // Mettre à jour le footer
    const grandTotal = hasSeries ? (series1Total + series2Total) : sheet.scoreRows.reduce((sum, row) => sum + row.endTotal, 0);
    document.getElementById('grandTotal').textContent = grandTotal;
    
    const footerArrows = document.getElementById('footerArrows');
    footerArrows.setAttribute('colspan', config.arrows_per_end);
}

function navigateArcher(direction) {
    // Sauvegarder les informations de l'archer actuel
    saveCurrentArcherInfo();
    
    // Naviguer
    currentUserIndex += direction;
    currentUserIndex = Math.max(0, Math.min(NUM_USERS - 1, currentUserIndex));
    
    displayCurrentArcher();
}

function saveCurrentArcherInfo() {
    if (userSheets.length === 0) return;
    
    const sheet = userSheets[currentUserIndex];
    sheet.archerInfo.name = document.getElementById('archerName').value;
    sheet.archerInfo.licenseNumber = document.getElementById('archerLicense').value;
    sheet.archerInfo.category = document.getElementById('archerCategory').value;
    sheet.archerInfo.weapon = document.getElementById('archerWeapon').value;
    sheet.archerInfo.gender = document.getElementById('archerGender').value;
}

function openScoreModal(rowIndex) {
    if (userSheets.length === 0) return;
    
    currentModalRow = rowIndex;
    currentModalUserIndex = currentUserIndex;
    
    const sheet = userSheets[currentUserIndex];
    const row = sheet.scoreRows[rowIndex];
    const config = SHOOTING_CONFIGS[selectedShootingType];
    
    document.getElementById('modalVolleyNumber').textContent = row.endNumber;
    
    const scoreInputs = document.getElementById('scoreInputs');
    scoreInputs.innerHTML = '';
    
    // Créer les inputs pour chaque flèche
    row.arrows.forEach((arrow, arrowIndex) => {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'mb-3';
        inputGroup.innerHTML = `
            <label for="arrow${arrowIndex}" class="form-label">Flèche ${arrowIndex + 1}</label>
            <input type="number" class="form-control" id="arrow${arrowIndex}" 
                   min="0" max="10" value="${arrow.value || 0}" 
                   onchange="updateArrowValue(${arrowIndex}, this.value)">
        `;
        scoreInputs.appendChild(inputGroup);
    });
    
    // Sauvegarder la config pour la cible
    targetConfig = config;
    
    // Charger les hits existants (exactement comme dans scored-training-show.js)
    // Les coordonnées sont déjà dans le format relatif (hit_x, hit_y)
    const existingHits = row.arrows
        .map((arrow, index) => {
            if (arrow.hit_x !== undefined && arrow.hit_y !== undefined && arrow.value > 0) {
                return {
                    hit_x: arrow.hit_x,
                    hit_y: arrow.hit_y,
                    score: arrow.value,
                    arrow_number: index + 1
                };
            }
            return null;
        })
        .filter(hit => hit !== null);
    
    // Stocker pour l'initialisation
    targetHits = existingHits;
    
    // Initialiser la cible interactive quand on passe à l'onglet cible
    const targetTab = document.getElementById('target-tab');
    const targetPane = document.getElementById('targetMode');
    
    if (targetTab && targetPane) {
        // Supprimer l'ancien listener s'il existe
        targetTab.removeEventListener('shown.bs.tab', initializeTargetForModal);
        // Ajouter le nouveau listener
        targetTab.addEventListener('shown.bs.tab', initializeTargetForModal);
    }
    
    // Initialiser aussi au chargement si on est déjà sur l'onglet cible
    if (targetPane && targetPane.classList.contains('active')) {
        setTimeout(initializeTargetForModal, 100);
    }
    
    scoreModal.show();
}

// Initialisation de la cible interactive (exactement comme dans scored-training-show.js)
function initializeTargetForModal() {
    const targetSvg = document.getElementById('targetSvg');
    const resetButton = document.getElementById('resetTarget');
    
    if (!targetSvg) return;
    
    // Initialiser les variables
    const arrowsPerEnd = targetConfig ? targetConfig.arrows_per_end : 3;
    targetScores = new Array(arrowsPerEnd).fill(null);
    targetCoordinates = new Array(arrowsPerEnd).fill(null);
    
    // Charger les hits existants depuis targetHits
    if (targetHits && targetHits.length > 0) {
        targetHits.forEach((hit, index) => {
            if (hit.hit_x !== undefined && hit.hit_y !== undefined) {
                // Convertir les coordonnées relatives en coordonnées absolues
                const centerX = 150;
                const centerY = 150;
                const absX = centerX + (hit.hit_x * (150 * (10/11) / 2));
                const absY = centerY + (hit.hit_y * (150 * (10/11) / 2));
                
                addArrowToTarget(absX, absY, hit.score, index);
            }
        });
    }
    
    // Supprimer les anciens gestionnaires d'événements s'ils existent
    targetSvg.removeEventListener('click', handleTargetClick);
    targetSvg.removeEventListener('mousedown', handleTargetMouseDown);
    
    // Ajouter les nouveaux gestionnaires d'événements
    targetSvg.addEventListener('click', handleTargetClick);
    targetSvg.addEventListener('mousedown', handleTargetMouseDown);
    
    if (resetButton) {
        resetButton.onclick = resetTarget;
    }
    
    // Mettre à jour l'affichage des scores
    updateScoresDisplay();
}

function updateArrowValue(arrowIndex, value) {
    if (currentModalUserIndex === null || currentModalRow === null) return;
    
    const sheet = userSheets[currentModalUserIndex];
    const row = sheet.scoreRows[currentModalRow];
    
    if (row.arrows[arrowIndex]) {
        row.arrows[arrowIndex].value = parseInt(value) || 0;
    }
}

function saveVolleyScores() {
    if (currentModalUserIndex === null || currentModalRow === null) return;
    
    const sheet = userSheets[currentModalUserIndex];
    const row = sheet.scoreRows[currentModalRow];
    const config = SHOOTING_CONFIGS[selectedShootingType];
    
    // Vérifier si on est en mode cible interactive
    const targetTab = document.getElementById('target-tab');
    const targetPane = document.getElementById('targetMode');
    const isTargetMode = targetPane && targetPane.classList.contains('active');
    
    if (isTargetMode && targetScores.length > 0) {
        // Utiliser les scores de la cible interactive (exactement comme dans scored-training-show.js)
        targetScores.forEach((score, index) => {
            if (row.arrows[index] && score !== null && score !== undefined) {
                row.arrows[index].value = score;
                // Les coordonnées sont déjà dans targetCoordinates
                if (targetCoordinates[index]) {
                    row.arrows[index].hit_x = targetCoordinates[index].x;
                    row.arrows[index].hit_y = targetCoordinates[index].y;
                }
            }
        });
    } else {
        // Les valeurs sont déjà mises à jour via updateArrowValue
    }
    
    // Réinitialiser les variables de cible
    targetScores = [];
    targetCoordinates = [];
    
    scoreModal.hide();
    
    // Mettre à jour l'affichage
    displayCurrentArcher();
}

function saveScoreSheet() {
    if (!selectedShootingType || userSheets.length === 0) {
        showStatus('Veuillez sélectionner un type de tir', 'danger');
        return;
    }
    
    // Sauvegarder les informations de l'archer actuel
    saveCurrentArcherInfo();
    
    // Préparer les données à envoyer
    const dataToSend = {
        shooting_type: selectedShootingType,
        training_title: trainingTitle || '',
        user_sheets: userSheets.map((sheet, userIndex) => {
            // Vérifier si l'archer a des scores
            const hasScores = sheet.scoreRows.some(row => row.endTotal > 0);
            
            if (!hasScores) {
                return null; // Ne pas inclure les archers sans scores
            }
            
            // Construire les informations de signature
            const hasArcherSignature = !!archerSignatures[userIndex];
            const signatureInfo = hasArcherSignature && scorerSignature 
                ? `Signatures: ${sheet.archerInfo.name} et Marqueur ont signé la feuille de marque.`
                : '';
            
            const signatures = {};
            if (archerSignatures[userIndex]) {
                signatures.archer = archerSignatures[userIndex];
            }
            if (scorerSignature) {
                signatures.scorer = scorerSignature;
            }
            
            return {
                archer_info: sheet.archerInfo,
                user_id: sheet.userId || null,
                signature_info: signatureInfo,
                signatures: Object.keys(signatures).length > 0 ? signatures : null,
                score_rows: sheet.scoreRows.map(row => ({
                    end_number: row.endNumber,
                    arrows: row.arrows.map(arrow => ({
                        value: arrow.value || 0,
                        hit_x: arrow.hit_x,
                        hit_y: arrow.hit_y
                    })),
                    end_total: row.endTotal,
                    cumulative_total: row.cumulativeTotal,
                    series_number: row.seriesNumber,
                    comment: row.comment || ''
                }))
            };
        }).filter(sheet => sheet !== null)
    };
    
    // Afficher un indicateur de chargement
    const saveBtn = document.getElementById('saveScoreSheetBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde en cours...';
    
    // Envoyer les données
    fetch('/score-sheet/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dataToSend)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus(data.message || 'Feuilles de marque sauvegardées avec succès !', 'success');
            
            // Rediriger vers les tirs comptés après 2 secondes
            setTimeout(() => {
                window.location.href = '/scored-trainings';
            }, 2000);
        } else {
            showStatus(data.message || 'Erreur lors de la sauvegarde', 'danger');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showStatus('Erreur de connexion au serveur', 'danger');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('statusMessage');
    statusDiv.className = `alert alert-${type}`;
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
    
    // Masquer après 5 secondes
    setTimeout(() => {
        statusDiv.style.display = 'none';
    }, 5000);
}

// ==================== GESTION DES SIGNATURES ====================

let signatureCanvas = null;
let signatureCtx = null;
let isDrawing = false;
let currentSignatureType = null; // 'archer' ou 'scorer'
let currentSignatureUserIndex = null;

function openSignatureModal() {
    // Trouver les archers actifs (qui ont des scores)
    const activeArchers = userSheets
        .map((sheet, index) => ({ sheet, index }))
        .filter(({ sheet }) => sheet.scoreRows.some(row => row.endTotal > 0));
    
    if (activeArchers.length === 0) {
        showStatus('Aucun archer avec des scores à signer', 'warning');
        return;
    }
    
    // Générer les onglets
    const tabList = document.getElementById('signatureTabList');
    const tabContent = document.getElementById('signatureTabContent');
    
    tabList.innerHTML = '';
    tabContent.innerHTML = '';
    
    // Onglets pour chaque archer
    activeArchers.forEach(({ sheet, index }, tabIndex) => {
        const isActive = tabIndex === 0;
        const tabId = `archer-tab-${index}`;
        const paneId = `archer-pane-${index}`;
        
        // Créer l'onglet
        const tab = document.createElement('li');
        tab.className = 'nav-item';
        tab.innerHTML = `
            <button class="nav-link ${isActive ? 'active' : ''}" id="${tabId}" data-bs-toggle="tab" 
                    data-bs-target="#${paneId}" type="button" role="tab"
                    onclick="loadSignatureCanvas('archer', ${index})">
                ${sheet.archerInfo.name || `Archer ${index + 1}`}
            </button>
        `;
        tabList.appendChild(tab);
        
        // Créer le contenu
        const pane = document.createElement('div');
        pane.className = `tab-pane fade ${isActive ? 'show active' : ''}`;
        pane.id = paneId;
        pane.innerHTML = `
            <div class="text-center">
                <h6>Signature de ${sheet.archerInfo.name || `Archer ${index + 1}`}</h6>
                <canvas id="signatureCanvas-archer-${index}" width="600" height="200" 
                        style="border: 2px solid #ccc; border-radius: 5px; cursor: crosshair;"></canvas>
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSignature('archer', ${index})">
                        <i class="fas fa-undo"></i> Effacer
                    </button>
                </div>
            </div>
        `;
        tabContent.appendChild(pane);
    });
    
    // Onglet pour le marqueur
    const scorerTab = document.createElement('li');
    scorerTab.className = 'nav-item';
    scorerTab.innerHTML = `
        <button class="nav-link" id="scorer-tab" data-bs-toggle="tab" 
                data-bs-target="#scorer-pane" type="button" role="tab"
                onclick="loadSignatureCanvas('scorer', null)">
            Marqueur
        </button>
    `;
    tabList.appendChild(scorerTab);
    
    const scorerPane = document.createElement('div');
    scorerPane.className = 'tab-pane fade';
    scorerPane.id = 'scorer-pane';
    scorerPane.innerHTML = `
        <div class="text-center">
            <h6>Signature du marqueur</h6>
            <canvas id="signatureCanvas-scorer" width="600" height="200" 
                    style="border: 2px solid #ccc; border-radius: 5px; cursor: crosshair;"></canvas>
            <div class="mt-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="clearSignature('scorer', null)">
                    <i class="fas fa-undo"></i> Effacer
                </button>
            </div>
        </div>
    `;
    tabContent.appendChild(scorerPane);
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('signatureModal'));
    modal.show();
    
    // Charger le premier canvas
    if (activeArchers.length > 0) {
        loadSignatureCanvas('archer', activeArchers[0].index);
    }
}

function loadSignatureCanvas(type, userIndex) {
    currentSignatureType = type;
    currentSignatureUserIndex = userIndex;
    
    const canvasId = type === 'scorer' 
        ? 'signatureCanvas-scorer'
        : `signatureCanvas-archer-${userIndex}`;
    
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    signatureCanvas = canvas;
    signatureCtx = canvas.getContext('2d');
    
    // Configuration du canvas
    signatureCtx.strokeStyle = '#000';
    signatureCtx.lineWidth = 2;
    signatureCtx.lineCap = 'round';
    signatureCtx.lineJoin = 'round';
    
    // Restaurer la signature existante si elle existe
    let existingSignature = '';
    if (type === 'scorer') {
        existingSignature = scorerSignature;
    } else if (archerSignatures[userIndex]) {
        existingSignature = archerSignatures[userIndex];
    }
    
    if (existingSignature) {
        const img = new Image();
        img.onload = () => {
            signatureCtx.drawImage(img, 0, 0);
        };
        img.src = existingSignature;
    } else {
        signatureCtx.clearRect(0, 0, canvas.width, canvas.height);
    }
    
    // Événements de dessin
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Événements tactiles
    canvas.addEventListener('touchstart', handleTouchStart);
    canvas.addEventListener('touchmove', handleTouchMove);
    canvas.addEventListener('touchend', handleTouchEnd);
}

function startDrawing(e) {
    isDrawing = true;
    const rect = signatureCanvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;
    signatureCtx.beginPath();
    signatureCtx.moveTo(x, y);
}

function draw(e) {
    if (!isDrawing) return;
    e.preventDefault();
    const rect = signatureCanvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;
    signatureCtx.lineTo(x, y);
    signatureCtx.stroke();
}

function stopDrawing() {
    isDrawing = false;
}

function handleTouchStart(e) {
    e.preventDefault();
    startDrawing(e);
}

function handleTouchMove(e) {
    e.preventDefault();
    draw(e);
}

function handleTouchEnd(e) {
    e.preventDefault();
    stopDrawing();
}

function clearSignature(type, userIndex) {
    if (!signatureCanvas || !signatureCtx) return;
    
    signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
    
    if (type === 'scorer') {
        scorerSignature = '';
    } else if (userIndex !== null) {
        delete archerSignatures[userIndex];
    }
}

function saveSignatures() {
    // Sauvegarder la signature actuelle
    if (signatureCanvas && signatureCtx) {
        const signatureData = signatureCanvas.toDataURL('image/png');
        
        if (currentSignatureType === 'scorer') {
            scorerSignature = signatureData;
        } else if (currentSignatureUserIndex !== null) {
            archerSignatures[currentSignatureUserIndex] = signatureData;
        }
    }
    
    // Sauvegarder toutes les signatures visibles
    document.querySelectorAll('[id^="signatureCanvas-"]').forEach(canvas => {
        if (canvas.id.includes('archer')) {
            const userIndex = parseInt(canvas.id.split('-')[2]);
            if (!isNaN(userIndex)) {
                archerSignatures[userIndex] = canvas.toDataURL('image/png');
            }
        } else if (canvas.id === 'signatureCanvas-scorer') {
            scorerSignature = canvas.toDataURL('image/png');
        }
    });
    
    showStatus('Signatures enregistrées', 'success');
    bootstrap.Modal.getInstance(document.getElementById('signatureModal')).hide();
}

// ==================== CIBLE INTERACTIVE (MÊME CODE QUE TIR COMPTÉ) ====================

// Variables pour la cible interactive (exactement comme dans scored-training-show.js)
let targetScores = [];
let targetCoordinates = []; // Stocker les coordonnées x,y des flèches
let targetHits = []; // Pour stocker temporairement les hits lors du chargement
let isZoomed = false;
let currentArrowIndex = 0;
let isDragging = false;
let currentDragScore = 0;
let zoomCircle = null;
let lastClickTime = 0;
let clickDebounceDelay = 300; // 300ms de délai entre les clics
let justFinishedDragging = false; // Flag pour éviter le clic après drag

// Fonction pour calculer le score depuis la position (exactement comme dans scored-training-show.js)
function calculateScoreFromPosition(x, y) {
    const centerX = 150; // Centre du viewBox 300x300 (même que l'app mobile)
    const centerY = 150;
    const distance = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    
    // Déterminer le type de cible
    const targetCategorySelect = document.querySelector('select[name="target_category"]') || 
                                 document.getElementById('targetCategorySelect');
    const targetCategory = targetCategorySelect ? targetCategorySelect.value : 'blason_80';
    
    // Pour le blason campagne, détecter par le type de tir, pas par la catégorie
    const isBlasonCampagne = selectedShootingType === 'Campagne';
    
    let zones;
    
    if (isBlasonCampagne) {
        // Zones pour le blason campagne : 6 zones
        zones = [
            { radius: 21.428571 , score: 6 },    // Zone 6 (centre jaune)
            { radius: 42.857143 , score: 5 },    // Zone 5 (jaune)
            { radius: 64.285714 , score: 4 },    // Zone 4 (noir)
            { radius: 85.714286 , score: 3 },    // Zone 3 (noir)
            { radius: 107.142857 , score: 2 },   // Zone 2 (noir)
            { radius: 128.571428 , score: 1 },   // Zone 1 (noir)
            { radius: Infinity, score: 0 }      // Manqué
        ];
    } else {
        // Rayons des zones (en unités SVG) - EXACTES calculs de l'app mobile
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
    const strokeWidth = 0.6;
    
    for (let i = 0; i < zones.length - 1; i++) {
        const currentZone = zones[i];
        const nextZone = zones[i + 1];
        
        if (distance <= (currentZone.radius + strokeWidth)) {
            if (distance >= (currentZone.radius - strokeWidth)) {
                return currentZone.score;
            } else {
                return currentZone.score;
            }
        }
    }
    
    let finalScore = zones[zones.length - 1].score;
    
    // Règle spécifique TRISPOT: seules les zones 6 à 10 scorent
    if (targetCategory.toLowerCase() === 'trispot') {
        if (finalScore < 6) {
            finalScore = 0;
        }
    }
    
    return finalScore;
}

// Fonction pour ajouter une flèche sur la cible (exactement comme dans scored-training-show.js)
function addArrowToTarget(x, y, score, arrowIndex) {
    const svg = document.getElementById('targetSvg');
    const arrowsGroup = document.getElementById('arrowsGroup');
    
    if (!svg || !arrowsGroup) return;
    
    // Créer un cercle pour représenter la flèche
    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', x);
    circle.setAttribute('cy', y);
    circle.setAttribute('r', '0.3');
    circle.setAttribute('class', 'arrow-marker');
    circle.setAttribute('data-score', score);
    circle.setAttribute('data-arrow-index', arrowIndex);
    
    // Déterminer la couleur de la flèche en fonction de la zone
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
    const centerX = 150;
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
    
    targetScores[arrowIndex] = null;
    targetCoordinates[arrowIndex] = null;
    updateScoresDisplay();
}

// Fonction pour mettre à jour l'affichage des scores
function updateScoresDisplay() {
    const scoresList = document.getElementById('scoresList');
    if (!scoresList) return;
    
    scoresList.innerHTML = '';
    
    const arrowsPerEnd = targetConfig ? targetConfig.arrows_per_end : 3;
    
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

// Fonction pour gérer le clic sur la cible (exactement comme dans scored-training-show.js)
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
    const arrowsPerEnd = targetConfig ? targetConfig.arrows_per_end : 3;
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

// Fonction pour gérer le début du drag (exactement comme dans scored-training-show.js)
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
    const arrowsPerEnd = targetConfig ? targetConfig.arrows_per_end : 3;
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

// Fonction pour créer une loupe autour du curseur (exactement comme dans scored-training-show.js)
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
    
    // Calculer la position relative dans le SVG (0-300)
    const x = ((mouseX - rect.left) / rect.width) * 300;
    const y = ((mouseY - rect.top) / rect.height) * 300;
    
    // Créer un SVG cloné pour la loupe
    const clonedSvg = svg.cloneNode(true);
    
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
        const viewBoxX = x - 5;
        const viewBoxY = y - 5;
        const viewBoxSize = 10;
        
        clonedSvg.setAttribute('viewBox', `${viewBoxX} ${viewBoxY} ${viewBoxSize} ${viewBoxSize}`);
    }
    
    // Mettre à jour le score affiché dans la loupe
    const scoreDisplay = window.currentMagnifyingGlass.querySelector('div');
    if (scoreDisplay && scoreDisplay.textContent) {
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
    
    const arrowsPerEnd = targetConfig ? targetConfig.arrows_per_end : 3;
    targetScores = new Array(arrowsPerEnd).fill(null);
    targetCoordinates = new Array(arrowsPerEnd).fill(null);
    updateScoresDisplay();
}

// Alias pour compatibilité
function clearTarget() {
    resetTarget();
}

// ==================== EXPORT PDF ====================

function exportToPDF() {
    // Utiliser jsPDF pour générer le PDF
    if (typeof window.jspdf === 'undefined' && typeof jsPDF === 'undefined') {
        showStatus('Bibliothèque PDF non chargée. Veuillez actualiser la page.', 'danger');
        return;
    }
    
    // Récupérer jsPDF depuis window.jspdf ou window.jsPDF
    const jsPDFLib = window.jspdf?.jsPDF || window.jsPDF;
    if (!jsPDFLib) {
        showStatus('Bibliothèque PDF non chargée. Veuillez actualiser la page.', 'danger');
        return;
    }
    
    const doc = new jsPDFLib();
    let yPosition = 20;
    
    // Titre
    doc.setFontSize(16);
    doc.text('Feuille de marque', 105, yPosition, { align: 'center' });
    yPosition += 10;
    
    doc.setFontSize(12);
    doc.text(`Type de tir: ${selectedShootingType}`, 20, yPosition);
    yPosition += 10;
    if (trainingTitle) {
        doc.text(`Titre: ${trainingTitle}`, 20, yPosition);
        yPosition += 10;
    }
    
    // Pour chaque archer avec des scores
    userSheets.forEach((sheet, index) => {
        const hasScores = sheet.scoreRows.some(row => row.endTotal > 0);
        if (!hasScores) return;
        
        // Nouvelle page pour chaque archer (sauf le premier)
        if (index > 0) {
            doc.addPage();
            yPosition = 20;
        }
        
        // Informations de l'archer
        doc.setFontSize(14);
        doc.text(`Archer ${index + 1}: ${sheet.archerInfo.name}`, 20, yPosition);
        yPosition += 10;
        
        doc.setFontSize(10);
        doc.text(`Licence: ${sheet.archerInfo.licenseNumber || 'N/A'}`, 20, yPosition);
        yPosition += 5;
        doc.text(`Catégorie: ${sheet.archerInfo.category || 'N/A'}`, 20, yPosition);
        yPosition += 5;
        doc.text(`Arme: ${sheet.archerInfo.weapon || 'N/A'}`, 20, yPosition);
        yPosition += 10;
        
        // Tableau des scores
        const tableData = [];
        sheet.scoreRows.forEach(row => {
            if (row.endTotal > 0) {
                const scores = row.arrows.map(a => a.value || 0).join(', ');
                tableData.push([
                    row.endNumber.toString(),
                    scores,
                    row.endTotal.toString(),
                    row.cumulativeTotal ? row.cumulativeTotal.toString() : ''
                ]);
            }
        });
        
        // Utiliser autoTable si disponible
        if (typeof doc.autoTable !== 'undefined') {
            doc.autoTable({
                startY: yPosition,
                head: [['Volée', 'Scores', 'Total', 'Cumul']],
                body: tableData,
                theme: 'grid'
            });
            yPosition = doc.lastAutoTable.finalY + 10;
        } else {
            // Fallback : créer un tableau simple
            let tableY = yPosition;
            doc.setFontSize(10);
            doc.text('Volée', 20, tableY);
            doc.text('Scores', 60, tableY);
            doc.text('Total', 120, tableY);
            doc.text('Cumul', 160, tableY);
            tableY += 5;
            doc.line(20, tableY, 190, tableY);
            tableY += 5;
            
            tableData.forEach(row => {
                doc.text(row[0], 20, tableY);
                doc.text(row[1], 60, tableY);
                doc.text(row[2], 120, tableY);
                doc.text(row[3], 160, tableY);
                tableY += 5;
            });
            yPosition = tableY + 5;
        }
        const grandTotal = sheet.scoreRows.reduce((sum, row) => sum + row.endTotal, 0);
        doc.setFontSize(12);
        doc.text(`TOTAL: ${grandTotal}`, 20, yPosition);
    });
    
    // Télécharger le PDF
    doc.save(`feuille-marque-${selectedShootingType}-${new Date().toISOString().split('T')[0]}.pdf`);
    showStatus('PDF généré avec succès', 'success');
}

