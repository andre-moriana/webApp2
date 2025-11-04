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
    
    // Recherche d'utilisateur par numéro de licence
    const archerLicenseInput = document.getElementById('archerLicense');
    if (archerLicenseInput) {
        let searchTimeout;
        let isSearching = false;
        
        archerLicenseInput.addEventListener('input', function() {
            const licenseNumber = this.value.trim();
            
            // Retirer l'indicateur de recherche précédent
            const existingIndicator = this.parentElement.querySelector('.search-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Délai de 500ms après la dernière saisie
            clearTimeout(searchTimeout);
            if (licenseNumber.length >= 3 && !isSearching) {
                // Afficher un indicateur de recherche
                const indicator = document.createElement('span');
                indicator.className = 'search-indicator ms-2';
                indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
                this.parentElement.appendChild(indicator);
                
                searchTimeout = setTimeout(() => {
                    isSearching = true;
                    searchUserByLicense(licenseNumber).finally(() => {
                        isSearching = false;
                        if (indicator.parentElement) {
                            indicator.remove();
                        }
                    });
                }, 500);
            } else if (licenseNumber.length < 3) {
                // Retirer l'indicateur si moins de 3 caractères
                const indicator = this.parentElement.querySelector('.search-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
        });
    }
    
    // Initialiser le modal Bootstrap
    const modalElement = document.getElementById('scoreModal');
    if (modalElement) {
        scoreModal = new bootstrap.Modal(modalElement);
    }
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
        console.log('Réponse brute:', responseText.substring(0, 200));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.error('Texte reçu:', responseText);
            throw new Error('Réponse invalide du serveur');
        }
        
        console.log('Données parsées:', data);
        
        if (data.success && data.data) {
            const user = data.data;
            console.log('Utilisateur trouvé:', user);
            
            // Remplir automatiquement les informations
            const nameField = document.getElementById('archerName');
            const categoryField = document.getElementById('archerCategory');
            const weaponField = document.getElementById('archerWeapon');
            const genderField = document.getElementById('archerGender');
            
            if (nameField) {
                const fullName = user.name || 
                                (user.firstName && user.lastName ? `${user.firstName} ${user.lastName}` : '') ||
                                (user.firstName || user.lastName || '');
                nameField.value = fullName;
            }
            
            if (categoryField && user.age_category) {
                categoryField.value = user.age_category;
            }
            
            if (weaponField && user.weapon) {
                weaponField.value = user.weapon;
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
    
    // Initialiser la cible interactive si nécessaire
    targetConfig = config;
    targetHits = row.arrows
        .map((arrow, index) => ({
            x: arrow.hit_x ? (arrow.hit_x * 200 + 200) : null,
            y: arrow.hit_y ? (arrow.hit_y * 200 + 200) : null,
            score: arrow.value || 0
        }))
        .filter(hit => hit.x !== null && hit.y !== null);
    
    // Réinitialiser la cible quand on change d'onglet
    const targetTab = document.getElementById('target-tab');
    if (targetTab) {
        // Retirer les anciens listeners pour éviter les doublons
        const newTargetTab = targetTab.cloneNode(true);
        targetTab.parentNode.replaceChild(newTargetTab, targetTab);
        
        newTargetTab.addEventListener('shown.bs.tab', function() {
            setTimeout(() => {
                initializeTargetForModal();
            }, 100);
        });
    }
    
    scoreModal.show();
}

function initializeTargetForModal() {
    const canvas = document.getElementById('targetCanvas'); // Canvas pour la cible dans le modal
    if (!canvas) return;
    
    targetCanvas = canvas;
    targetCtx = canvas.getContext('2d');
    
    // Dessiner la cible
    drawTarget();
    
    // Événements
    canvas.removeEventListener('click', handleTargetClick);
    canvas.addEventListener('click', handleTargetClick);
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
    
    if (isTargetMode && targetHits.length > 0) {
        // Utiliser les scores de la cible interactive
        targetHits.forEach((hit, index) => {
            if (row.arrows[index]) {
                row.arrows[index].value = hit.score;
                // Convertir les coordonnées en format relatif (0-1)
                if (targetCanvas) {
                    const centerX = targetCanvas.width / 2;
                    const centerY = targetCanvas.height / 2;
                    row.arrows[index].hit_x = (hit.x - centerX) / (targetCanvas.width / 2);
                    row.arrows[index].hit_y = (hit.y - centerY) / (targetCanvas.height / 2);
                }
            }
        });
    } else {
        // Les valeurs sont déjà mises à jour via updateArrowValue
    }
    
    // Réinitialiser les variables de cible
    targetHits = [];
    
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

// ==================== CIBLE INTERACTIVE ====================

let targetCanvas = null;
let targetCtx = null;
let targetHits = [];
let targetConfig = null;

function initializeTarget() {
    targetCanvas = document.getElementById('targetCanvas');
    if (!targetCanvas) return;
    
    targetCtx = targetCanvas.getContext('2d');
    
    // Dessiner la cible
    drawTarget();
    
    // Événements
    targetCanvas.removeEventListener('click', handleTargetClick);
    targetCanvas.addEventListener('click', handleTargetClick);
}

function drawTarget() {
    if (!targetCtx || !targetCanvas) return;
    
    const centerX = targetCanvas.width / 2;
    const centerY = targetCanvas.height / 2;
    const maxRadius = Math.min(targetCanvas.width, targetCanvas.height) / 2 - 10;
    
    // Couleurs des zones (de l'extérieur vers l'intérieur : zone 1 à zone 10)
    // Format: blanc, blanc, noir, noir, bleu, bleu, rouge, rouge, jaune, jaune
    const colors = ['#FFFFFF', '#FFFFFF', '#212121', '#212121', '#1976D2', '#1976D2', '#D32F2F', '#D32F2F', '#FFD700', '#FFD700'];
    
    // Nettoyer le canvas
    targetCtx.clearRect(0, 0, targetCanvas.width, targetCanvas.height);
    
    // Dessiner les 10 zones (de l'extérieur vers l'intérieur)
    for (let i = 0; i < 10; i++) {
        const radius = maxRadius * (10 - i) / 10;
        targetCtx.fillStyle = colors[i];
        targetCtx.beginPath();
        targetCtx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        targetCtx.fill();
        targetCtx.strokeStyle = '#000';
        targetCtx.lineWidth = 1;
        targetCtx.stroke();
    }
    
    // Redessiner les impacts existants
    targetHits.forEach(hit => {
        if (hit.x && hit.y) {
            drawHit(hit.x, hit.y, hit.score);
        }
    });
}

function handleTargetClick(e) {
    if (!targetCtx || !targetConfig) return;
    
    const rect = targetCanvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const centerX = targetCanvas.width / 2;
    const centerY = targetCanvas.height / 2;
    const distance = Math.sqrt(Math.pow(x - centerX, 2) + Math.pow(y - centerY, 2));
    const maxRadius = Math.min(targetCanvas.width, targetCanvas.height) / 2 - 10;
    
    // Calculer le score selon la distance
    const score = Math.max(1, Math.min(10, Math.ceil(10 - (distance / maxRadius) * 10)));
    
    // Ajouter l'impact
    if (targetHits.length < targetConfig.arrows_per_end) {
        targetHits.push({ x, y, score });
        drawHit(x, y, score);
        updateTargetScoresList();
    }
}

function drawHit(x, y, score) {
    targetCtx.fillStyle = '#000';
    targetCtx.beginPath();
    targetCtx.arc(x, y, 5, 0, 2 * Math.PI);
    targetCtx.fill();
    
    // Afficher le score
    targetCtx.fillStyle = '#fff';
    targetCtx.font = 'bold 12px Arial';
    targetCtx.textAlign = 'center';
    targetCtx.fillText(score, x, y + 20);
}

function updateTargetScoresList() {
    const list = document.getElementById('targetScoresList');
    if (!list) return;
    
    list.innerHTML = targetHits.map((hit, index) => 
        `<span class="badge bg-primary me-1">Flèche ${index + 1}: ${hit.score}</span>`
    ).join('');
}

function clearTarget() {
    targetHits = [];
    drawTarget();
    updateTargetScoresList();
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

