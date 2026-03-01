/**
 * JavaScript pour la feuille de marque
 */

// Configuration des types de tir (clé = type logique pour volées/flèches ; le select stocke abv_discipline)
const SHOOTING_CONFIGS = {
    'Salle': { total_ends: 20, arrows_per_end: 3, total_arrows: 60, series: 2 },
    'TAE': { total_ends: 12, arrows_per_end: 6, total_arrows: 72, series: 2 },
    'Nature': { total_ends: 21, arrows_per_end: 2, total_arrows: 42 },
    'Nature2x21': { total_ends: 42, arrows_per_end: 2, total_arrows: 84, series: 2 },
    '3D': { total_ends: 24, arrows_per_end: 2, total_arrows: 48 },
    'Campagne': { total_ends: 24, arrows_per_end: 3, total_arrows: 72 },
};

/** Retourne la clé SHOOTING_CONFIGS (Salle, TAE, Nature, 3D, Campagne) pour une valeur du select (abv_discipline ou ancienne valeur). */
function getShootingConfigKey(val) {
    if (!val) return '';
    const v = String(val).trim();
    if (SHOOTING_CONFIGS[v]) return v;
    return mapDisciplineToShootingType(v) || v;
}

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

// Données concours pour préremplissage
let selectedConcoursId = null;
let concoursPlansPeloton = null;
let concoursPlansCible = null;
let concoursInscriptions = null;
let concoursDetails = null;

/** True si la feuille a déjà été exportée vers concours_resultats (lu depuis les notes au chargement). */
let exportedToConcours = false;

/** True si les feuilles ont déjà été sauvegardées pour ce batch (concours/départ/cible). */
let sheetsSavedForBatch = false;

/** Retourne l'iddiscipline (de data-disciplines) pour un abv_discipline donné, ou null. */
function getDisciplineIdForAbv(abv) {
    if (!abv) return null;
    const container = document.querySelector('.score-sheet-container');
    const disciplines = container?.dataset?.disciplines ? JSON.parse(container.dataset.disciplines) : [];
    if (!Array.isArray(disciplines)) return null;
    const d = disciplines.find(disc => String(disc.abv_discipline || disc.abv || '').toUpperCase() === String(abv).toUpperCase());
    return d ? (d.iddiscipline ?? d.id ?? d._id ?? null) : null;
}

/** Charge les catégories (concour_categories_classement) filtrées par iddiscipline et remplit le select archerCategory (value=abv_categorie_classement, text=lb_categorie_classement). */
async function loadCategoriesForDiscipline(iddiscipline) {
    console.log('loadCategoriesForDiscipline', iddiscipline);
    const categorySelect = document.getElementById('archerCategory');
    if (!categorySelect) return;
    categorySelect.innerHTML = '<option value="">--</option>';
    if (iddiscipline == null || iddiscipline === '') {
        categorySelect.options[0].text = '-- Choisir un type de tir pour charger les catégories --';
        return;
    }
    try {
        const response = await fetch('/api/concours/categories-classement?iddiscipline=' + encodeURIComponent(iddiscipline));
        const res = await response.json().catch(() => null);
        if (!response.ok) {
            categorySelect.options[0].text = '-- Erreur ' + (response.status || '') + ' --';
            return;
        }
        // Accepter plusieurs formats : { data: [...] }, { success, data: [...] }, ou tableau direct
        let list = [];
        if (Array.isArray(res)) {
            list = res;
        } else if (res && Array.isArray(res.data)) {
            list = res.data;
        } else if (res && res.data && Array.isArray(res.data.data)) {
            list = res.data.data;
        } else if (res && typeof res.data === 'object' && res.data !== null && !Array.isArray(res.data)) {
            list = Object.values(res.data);
        }
        if (list.length === 0 && res) {
            console.warn('loadCategoriesForDiscipline: réponse reçue mais liste vide. res=', res);
        }
        // Re-récupérer le select au cas où le DOM aurait été mis à jour pendant le fetch
        const select = document.getElementById('archerCategory');
        if (!select) return;
        select.options[0].text = '--';
        list.forEach(cat => {
            const abv = String(cat.abv_categorie_classement ?? cat.abv ?? '').trim();
            const libelle = String(cat.lb_categorie_classement ?? cat.name ?? cat.nom ?? abv).trim() || '—';
            const opt = document.createElement('option');
            opt.value = abv;
            opt.textContent = libelle;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error('Erreur chargement catégories:', e);
        categorySelect.options[0].text = '-- Erreur chargement --';
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeScoreSheet();
    initializeSignatureCanvas();
    setupConcoursSelector();
});

function initializeScoreSheet() {
    const shootingTypeSelect = document.getElementById('shootingType');
    const trainingTitleInput = document.getElementById('trainingTitle');
    
    if (shootingTypeSelect) {
        shootingTypeSelect.addEventListener('change', function() {
            if (this.disabled) return;
            selectedShootingType = this.value;
            if (selectedShootingType) {
                initializeSheets();
            }
            loadCategoriesForDiscipline(getDisciplineIdForAbv(selectedShootingType));
        });
    }
    
    if (trainingTitleInput) {
        trainingTitleInput.addEventListener('input', function() {
            if (this.readOnly) return;
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
    
    // Délégation : clic sur N° cible / Volée — vérifier feuille signée avant d'ouvrir le modal
    const scoreTableSection = document.getElementById('scoreTableSection');
    if (scoreTableSection) {
        scoreTableSection.addEventListener('click', function(e) {
            const btn = e.target.closest('.volley-open-btn');
            if (!btn || btn.classList.contains('volley-disabled')) return;
            e.preventDefault();
            const idx = parseInt(btn.getAttribute('data-row-index'), 10);
            if (isNaN(idx)) return;
            const sheet = userSheets[currentUserIndex];
            if (!sheet) return;
            const locked = !!exportedToConcours || !!sheet.signed || (!!sheet.notes && String(sheet.notes).indexOf('__SIGNED__:1') !== -1) || (!!scorerSignature && !!archerSignatures[currentUserIndex]);
            if (locked) {
                showStatus('Feuille signée : les scores ne sont plus modifiables.', 'info');
                return;
            }
            openScoreModal(idx);
        });
    }
    
    // Initialiser le select catégories (placeholder) pour que loadCategoriesForDiscipline soit bien relié
    loadCategoriesForDiscipline(null);
}

// Configuration du sélecteur de concours et peloton
function setupConcoursSelector() {
    const concoursSelect = document.getElementById('concoursSelect');
    const pelotonWrapper = document.getElementById('pelotonSelectorWrapper');
    const departSelect = document.getElementById('departSelect');
    const pelotonSelect = document.getElementById('pelotonSelect');
    
    if (!concoursSelect) return;
    
    concoursSelect.addEventListener('change', async function() {
        selectedConcoursId = this.value || null;
        concoursPlansPeloton = null;
        concoursPlansCible = null;
        concoursInscriptions = null;
        concoursDetails = null;
        
        pelotonWrapper.style.display = 'none';
        departSelect.innerHTML = '<option value="">-- Départ --</option>';
        pelotonSelect.innerHTML = '<option value="">-- Peloton --</option>';
        
        if (!selectedConcoursId) {
            // Réinitialiser le type de tir, le titre et les catégories
            const shootingTypeSelect = document.getElementById('shootingType');
            const trainingTitleInput = document.getElementById('trainingTitle');
            if (shootingTypeSelect) shootingTypeSelect.value = '';
            if (trainingTitleInput) trainingTitleInput.value = '';
            selectedShootingType = '';
            userSheets.forEach(s => { delete s.inscriptionId; });
            exportedToConcours = false;
            sheetsSavedForBatch = false;
            updateConcoursImportLock();
            loadCategoriesForDiscipline(null);
            document.getElementById('archerNavigation')?.style?.setProperty('display', 'none');
            document.getElementById('archerInfoSection')?.style?.setProperty('display', 'none');
            document.getElementById('scoreTableSection')?.style?.setProperty('display', 'none');
            return;
        }
        
        try {
            // Charger d'abord le concours et les inscriptions pour connaître la discipline
            const [concoursRes, inscRes] = await Promise.all([
                fetch(`/api/concours/${selectedConcoursId}/public`).then(r => r.json()).catch(() => null),
                fetch(`/api/concours/${selectedConcoursId}/inscriptions`).then(r => r.json()).catch(() => null)
            ]);
            
            concoursDetails = concoursRes?.data || concoursRes;
            if (concoursDetails && concoursDetails.titre_competition) {
                document.getElementById('trainingTitle').value = concoursDetails.titre_competition || '';
            }
            
            // Déduire le type de tir depuis la discipline du concours
            const shootingTypeSelect = document.getElementById('shootingType');
            const container = document.querySelector('.score-sheet-container');
            const disciplines = container?.dataset?.disciplines ? JSON.parse(container.dataset.disciplines) : [];
            const discId = concoursDetails?.discipline ?? concoursDetails?.iddiscipline ?? null;
            let abvDiscipline = null;
            if (discId && Array.isArray(disciplines)) {
                const disc = disciplines.find(d => (d.iddiscipline ?? d.id ?? d._id) == discId);
                abvDiscipline = disc?.abv_discipline ?? disc?.abv ?? null;
            }
            const abvUpper = String(abvDiscipline || '').toUpperCase();
            const isCibleDiscipline = ['T', 'S', 'I', 'H'].includes(abvUpper);
            const isPelotonDiscipline = ['3', 'N', 'C', '3D'].includes(abvUpper);
            
            // N'appeler que l'API du plan correspondant à la discipline (plan-cible pour T/S/I/H, plan-peloton pour N/3/C)
            concoursPlansCible = null;
            concoursPlansPeloton = null;
            if (isCibleDiscipline) {
                const planCibleRes = await fetch(`/api/concours/${selectedConcoursId}/plan-cible`).then(r => r.json()).catch(() => null);
                let plansCible = planCibleRes?.data ?? planCibleRes;
                if (plansCible && typeof plansCible === 'object' && !Array.isArray(plansCible) && plansCible.data) {
                    plansCible = plansCible.data;
                }
                if (plansCible && typeof plansCible === 'object' && Object.keys(plansCible).length > 0) {
                    concoursPlansCible = plansCible;
                }
            } else if (isPelotonDiscipline) {
                const planPelotonRes = await fetch(`/api/concours/${selectedConcoursId}/plan-peloton`).then(r => r.json()).catch(() => null);
                let plansPeloton = planPelotonRes?.data ?? planPelotonRes;
                if (plansPeloton && typeof plansPeloton === 'object' && !Array.isArray(plansPeloton) && plansPeloton.data) {
                    plansPeloton = plansPeloton.data;
                }
                if (plansPeloton && typeof plansPeloton === 'object' && Object.keys(plansPeloton).length > 0) {
                    concoursPlansPeloton = plansPeloton;
                }
            }
            
            // Sélectionner le type de tir = abv_discipline du concours
            if (abvDiscipline && shootingTypeSelect) {
                const opt = Array.from(shootingTypeSelect.options).find(o => (o.value || '').toUpperCase() === String(abvDiscipline).toUpperCase());
                if (opt) {
                    shootingTypeSelect.value = opt.value;
                    selectedShootingType = opt.value;
                    initializeSheets();
                }
                shootingTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (discId) {
                loadCategoriesForDiscipline(discId);
            }
            
            // Inscriptions - filtrer confirmées (format API: { data: [...] } ou tableau direct)
            let inscriptions = Array.isArray(inscRes) ? inscRes : (inscRes?.data || []);
            if (Array.isArray(inscriptions)) {
                concoursInscriptions = inscriptions.filter(i => (i.statut_inscription || i.statutInscription || '') === 'confirmee');
            } else {
                concoursInscriptions = [];
            }
            
            const isPlanCibleMode = ['T', 'S', 'I', 'H'].includes(String(abvDiscipline || '').toUpperCase()) && concoursPlansCible && Object.keys(concoursPlansCible).length > 0;
            const isPlanPelotonMode = ['3', 'N', 'C', '3D'].includes(String(abvDiscipline || '').toUpperCase()) && concoursPlansPeloton && Object.keys(concoursPlansPeloton).length > 0;
            console.log('isPlanCibleMode:', isPlanCibleMode);
            console.log('isPlanPelotonMode:', isPlanPelotonMode);
            // Afficher sélecteur Départ / Cible (T/S/I/H) ou Départ / Peloton (N/3/C)
            if (isPlanCibleMode || isPlanPelotonMode) {
                pelotonWrapper.style.display = 'block';
                const labelEl = document.getElementById('departCibleLabel');
                const hintEl = document.getElementById('selectorHint');
                if (labelEl) labelEl.textContent = isPlanCibleMode ? 'Départ / Cible' : 'Départ / Peloton';
                if (hintEl) hintEl.textContent = isPlanCibleMode ? 'Pour les disciplines Salle, TAE (T, S, I, H)' : 'Pour les disciplines Nature, 3D et Campagne';
                
                const plansSource = isPlanCibleMode ? concoursPlansCible : concoursPlansPeloton;
                const departs = Object.keys(plansSource).sort((a, b) => parseInt(a) - parseInt(b));
                departSelect.innerHTML = '<option value="">-- Départ --</option>';
                departs.forEach(dep => {
                    departSelect.innerHTML += `<option value="${dep}">Départ ${dep}</option>`;
                });
                
                departSelect.onchange = function() {
                    const dep = this.value;
                    pelotonSelect.innerHTML = '<option value="">-- ' + (isPlanCibleMode ? 'Cible' : 'Peloton') + ' --</option>';
                    if (!dep || !plansSource[dep]) return;
                    if (isPlanCibleMode) {
                        const cibles = [...new Set((plansSource[dep] || []).map(p => p.numero_cible).filter(Boolean))].sort((a, b) => a - b);
                        cibles.forEach(c => {
                            pelotonSelect.innerHTML += `<option value="${c}">Cible ${c}</option>`;
                        });
                    } else {
                        const pelotons = [...new Set((plansSource[dep] || []).map(p => p.numero_peloton).filter(Boolean))].sort((a, b) => a - b);
                        pelotons.forEach(pel => {
                            pelotonSelect.innerHTML += `<option value="${pel}">Peloton ${pel}</option>`;
                        });
                    }
                };
                
                // Préremplir les archers automatiquement à la sélection d'une cible ou d'un peloton
                pelotonSelect.onchange = function() {
                    if (departSelect.value && this.value) {
                        prefillArchersFromConcours();
                    }
                };
                
                departSelect.dispatchEvent(new Event('change'));
            }
        } catch (e) {
            console.error('Erreur chargement concours:', e);
            showStatus('Erreur lors du chargement des données du concours', 'danger');
        }
    });
}

/**
 * Applique les volées et flèches d'une session existante sur la feuille de marque (scoreRows).
 * @param {Object} sheet - userSheets[i]
 * @param {Object} trainingData - { ends: [ { end_number, total_score, shots: [ { arrow_number, score, hit_x?, hit_y? } ] } ] }
 */
function applyTrainingToSheet(sheet, trainingData) {
    const ends = Array.isArray(trainingData?.ends) ? trainingData.ends : (trainingData?.data?.ends);
    if (!sheet?.scoreRows || !Array.isArray(ends) || ends.length === 0) return;
    const rows = sheet.scoreRows;
    ends.forEach(end => {
        const rowIndex = (parseInt(end.end_number, 10) || 0) - 1;
        if (rowIndex < 0 || rowIndex >= rows.length) return;
        const row = rows[rowIndex];
        const total = parseInt(end.total_score, 10);
        row.endTotal = isNaN(total) ? 0 : total;
        row.savedToServer = true;
        (end.shots || []).forEach(shot => {
            const arrowIndex = (parseInt(shot.arrow_number, 10) || 0) - 1;
            if (arrowIndex >= 0 && row.arrows && row.arrows[arrowIndex]) {
                const score = parseInt(shot.score, 10);
                row.arrows[arrowIndex].value = isNaN(score) ? 0 : score;
                if (shot.hit_x != null) row.arrows[arrowIndex].hit_x = Number(shot.hit_x);
                if (shot.hit_y != null) row.arrows[arrowIndex].hit_y = Number(shot.hit_y);
            }
        });
    });
    // Recalcul du cumul
    let cumul = 0;
    rows.forEach(r => {
        cumul += Number(r.endTotal) || 0;
        r.cumulativeTotal = cumul;
    });
}

/**
 * Extrait les signatures et le flag signé depuis la chaîne notes (backend).
 * @param {string} notes
 * @returns {{ signatures: object|null, signed: boolean }}
 */
function parseSignaturesFromNotes(notes) {
    if (!notes || typeof notes !== 'string') return { signatures: null, signed: false };
    const signed = notes.indexOf('__SIGNED__:1') !== -1;
    const idx = notes.indexOf('__SIGNATURES__:');
    let signatures = null;
    if (idx !== -1) {
        const start = idx + '__SIGNATURES__:'.length;
        let depth = 0;
        let end = start;
        for (let i = start; i < notes.length; i++) {
            if (notes[i] === '{') depth++;
            else if (notes[i] === '}') { depth--; if (depth === 0) { end = i + 1; break; } }
        }
        try {
            const json = notes.substring(start, end);
            signatures = JSON.parse(json);
        } catch (e) { /* ignore */ }
    }
    return { signatures, signed };
}

/**
 * Pour chaque feuille ayant un scoredTrainingId, charge la session (volées + flèches) et met à jour la feuille.
 * @param {number[]} indicesWithLicence - indices des userSheets avec licence (ceux qui ont reçu un training_id)
 */
async function loadExistingTrainingData(indicesWithLicence) {
    if (!indicesWithLicence || indicesWithLicence.length === 0) return;
    const loadOne = async (sheetIndex) => {
        const sheet = userSheets[sheetIndex];
        if (!sheet?.scoredTrainingId) return;
        const userId = sheet.userId != null && sheet.userId !== '' ? sheet.userId : null;
        const tid = sheet.scoredTrainingId;
        let url = `/score-sheet/load-training?training_id=${encodeURIComponent(tid)}`;
        try {
            let resp = await fetch(url);
            let result = await resp.json().catch(() => ({}));
            if (!result.success || !result.data) {
                if (userId && (resp.status === 404 || !result.success)) {
                    resp = await fetch(url + (url.includes('?') ? '&' : '?') + 'user_id=' + encodeURIComponent(userId));
                    result = await resp.json().catch(() => ({}));
                }
            }
            if (result.success && result.data) {
                applyTrainingToSheet(sheet, result.data);
                const notes = result.data.notes || (result.data.data && result.data.data.notes) || '';
                if (notes) sheet.notes = notes;
                if (result.data.signed === true) sheet.signed = true;
                if (notes) {
                    const { signatures, signed } = parseSignaturesFromNotes(notes);
                    if (signed) sheet.signed = true;
                    if (signatures && typeof signatures === 'object') {
                        if (signatures.archer) archerSignatures[sheetIndex] = signatures.archer;
                        if (signatures.scorer) scorerSignature = signatures.scorer;
                    }
                    if (notes.indexOf('__EXPORTED_TO_CONCOURS__:1') !== -1) exportedToConcours = true;
                }
                if (result.data.exported_to_concours === true) exportedToConcours = true;
            }
        } catch (e) {
            console.warn('Chargement session existante pour feuille', sheetIndex, e);
        }
    };
    await Promise.all(indicesWithLicence.map(loadOne));
}

// Préremplir les archers depuis le concours (plan cible, peloton ou inscriptions)
async function prefillArchersFromConcours() {
    const departSelect = document.getElementById('departSelect');
    const pelotonSelect = document.getElementById('pelotonSelect');

    const dep = departSelect?.value ?? '';
    const pel = pelotonSelect?.value ?? '';
    // Réinitialiser l'état pour le nouveau départ/peloton (évite de garder scores et boutons du précédent)
    exportedToConcours = false;
    sheetsSavedForBatch = false;
    archerSignatures = {};
    scorerSignature = '';
    const storageKey = 'scoreSheet_exported_' + (selectedConcoursId || '') + '_' + dep + '_' + pel;
    if (selectedConcoursId && (dep || pel) && localStorage.getItem(storageKey)) {
        exportedToConcours = true;
    }
    const savedKey = 'scoreSheet_saved_' + (selectedConcoursId || '') + '_' + dep + '_' + pel;
    if (selectedConcoursId && (dep || pel) && localStorage.getItem(savedKey)) {
        sheetsSavedForBatch = true;
    }
    // Réinitialiser les scores et infos session de chaque feuille pour le nouveau départ/peloton
    const config = selectedShootingType && SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    if (config && userSheets.length > 0) {
        userSheets.forEach(sheet => {
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
            sheet.scoreRows = rows;
            delete sheet.scoredTrainingId;
            sheet.signed = false;
            sheet.notes = null;
        });
    }

    let archers = [];
    // Si plan cible existe (T/S/I/H), exiger départ + cible
    if (concoursPlansCible && Object.keys(concoursPlansCible).length > 0) {
        if (!departSelect?.value || !pelotonSelect?.value) {
            showStatus('Veuillez sélectionner un départ et une cible.', 'warning');
            return;
        }
    }
    
    // Si plan peloton existe (tir N/3/C), exiger départ + peloton
    if (concoursPlansPeloton && Object.keys(concoursPlansPeloton).length > 0) {
        if (!departSelect?.value || !pelotonSelect?.value) {
            showStatus('Veuillez sélectionner un départ et un peloton.', 'warning');
            return;
        }
    }
    console.log('concoursPlansCible:', concoursPlansCible);
    console.log('concoursPlansPeloton:', concoursPlansPeloton);
    console.log('departSelect:', departSelect.value);
    console.log('pelotonSelect:', pelotonSelect.value);
    if (concoursPlansCible && departSelect?.value && pelotonSelect?.value ) {
        // Mode plan cible (T/S/I/H) : extraire les archers de la cible
        const dep = departSelect.value;
        const cible = parseInt(pelotonSelect.value);
        const plans = (concoursPlansCible[dep] || []).filter(p => (p.numero_cible || 0) == cible);
        const inscriptionsMap = {};
        (concoursInscriptions || []).forEach(i => {
            const lic = (i.numero_licence || i.numeroLicence || '').toString().trim();
            if (!lic) return;
            inscriptionsMap[lic] = i;
            if (lic.length === 7 && /^\d+$/.test(lic)) inscriptionsMap['0' + lic] = i;
            if (lic.length === 8 && lic[0] === '0' && /^\d+$/.test(lic)) inscriptionsMap[lic.slice(1)] = i;
        });
        
        plans.sort((a, b) => (a.position_archer || '').localeCompare(b.position_archer || ''));
        archers = plans.map(p => {
            const lic = (p.numero_licence || '').toString().trim();
            const insc = inscriptionsMap[lic] || inscriptionsMap['0' + lic] || inscriptionsMap[lic.replace(/^0/, '')] || {};
            const nom = insc.user_nom || insc.nom || insc.name || p.user_nom || '';
            const cat = insc.categorie_classement || insc.categorieClassement || insc.abv_categorie_classement || '';
            return {
                name: nom,
                licenseNumber: lic,
                category: cat,
                gender: (insc.genre || insc.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
                userId: insc.user_id || insc.userId || insc.id_user || p.user_id,
                inscriptionId: insc.id || insc.id_inscription
            };
        });
    } else if (concoursPlansPeloton && departSelect?.value && pelotonSelect?.value ) {
        // Mode peloton (N/3/C) : extraire les archers du peloton
        const dep = departSelect.value;
        const pel = parseInt(pelotonSelect.value);
        const plans = (concoursPlansPeloton[dep] || []).filter(p => (p.numero_peloton || 0) == pel);
        const inscriptionsMap = {};
        (concoursInscriptions || []).forEach(i => {
            const lic = (i.numero_licence || i.numeroLicence || '').toString().trim();
            if (!lic) return;
            inscriptionsMap[lic] = i;
            if (lic.length === 7 && /^\d+$/.test(lic)) inscriptionsMap['0' + lic] = i;
            if (lic.length === 8 && lic[0] === '0' && /^\d+$/.test(lic)) inscriptionsMap[lic.slice(1)] = i;
        });
        
        plans.sort((a, b) => (a.position_archer || '').localeCompare(b.position_archer || ''));
        archers = plans.map(p => {
            const lic = (p.numero_licence || '').toString().trim();
            const insc = inscriptionsMap[lic] || inscriptionsMap['0' + lic] || inscriptionsMap[lic.replace(/^0/, '')] || {};
            const nom = insc.user_nom || insc.nom || insc.name || p.user_nom || '';
            const cat = insc.categorie_classement || insc.categorieClassement || insc.abv_categorie_classement || '';
            return {
                name: nom,
                licenseNumber: lic,
                category: cat,
                gender: (insc.genre || insc.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
                userId: insc.user_id || insc.userId || insc.id_user || p.user_id,
                inscriptionId: insc.id || insc.id_inscription
            };
        });
    } else if (concoursInscriptions && concoursInscriptions.length > 0) {
        // Mode inscriptions : utiliser les 6 premiers (ou tous)
        archers = concoursInscriptions.slice(0, NUM_USERS).map(i => ({
            name: i.user_nom || i.nom || i.name || '',
            licenseNumber: i.numero_licence || i.numeroLicence || '',
            category: i.categorie_classement || i.categorieClassement || i.abv_categorie_classement || '',
            gender: (i.genre || i.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
            userId: i.user_id || i.userId || i.id_user,
            inscriptionId: i.id || i.id_inscription
        }));
    }
    
    if (archers.length === 0) {
        showStatus('Aucun archer à préremplir. Sélectionnez une cible/peloton ou vérifiez les inscriptions.', 'warning');
        return;
    }
    
    // S'assurer que le type de tir est sélectionné et les feuilles initialisées
    if (!selectedShootingType) {
        showStatus('Veuillez d\'abord sélectionner le type de tir.', 'warning');
        return;
    }
    if (userSheets.length === 0) {
        initializeSheets();
    }
    
    // Appliquer les archers
    archers.forEach((archer, idx) => {
        if (userSheets[idx]) {
            userSheets[idx].archerInfo = {
                name: archer.name,
                licenseNumber: archer.licenseNumber,
                category: archer.category,
                gender: archer.gender
            };
            if (archer.userId) userSheets[idx].userId = archer.userId;
            if (archer.inscriptionId) userSheets[idx].inscriptionId = archer.inscriptionId;
        }
    });
    
    // Créer les sessions (scored_trainings) uniquement pour les emplacements utilisés (licence non vide)
    try {
        const sheetsInUse = userSheets.slice(0, archers.length);
        const indicesWithLicence = [];
        const sheetsWithLicence = sheetsInUse.filter((s, idx) => {
            const lic = (s.archerInfo?.licenseNumber ?? '').toString().trim();
            if (lic !== '') {
                indicesWithLicence.push(idx);
                return true;
            }
            return false;
        });
        if (sheetsWithLicence.length > 0) {
            const shootingTypeKey = getShootingConfigKey(selectedShootingType);
            const payload = {
                shooting_type: shootingTypeKey,
                training_title: document.getElementById('trainingTitle')?.value || '',
                concours_id: selectedConcoursId || null,
                depart: departSelect?.value || null,
                peloton: pelotonSelect?.value || null,
                user_sheets: sheetsWithLicence.map(s => ({
                    archer_info: {
                        name: s.archerInfo?.name ?? '',
                        licenseNumber: s.archerInfo?.licenseNumber ?? '',
                        category: s.archerInfo?.category ?? '',
                        gender: s.archerInfo?.gender ?? ''
                    },
                    user_id: s.userId || null
                }))
            };
            const resp = await fetch('/score-sheet/create-sessions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await resp.json().catch(() => ({}));
            if (result.success && Array.isArray(result.data?.training_ids)) {
                if (result.data.exported_to_concours === true) exportedToConcours = true;
                result.data.training_ids.forEach((tid, i) => {
                    const sheetIndex = indicesWithLicence[i];
                    if (sheetIndex != null && userSheets[sheetIndex] && tid != null) {
                        userSheets[sheetIndex].scoredTrainingId = tid;
                    }
                    // Préremplir les scores depuis les volées renvoyées par le serveur (session existante)
                    const ends = result.data.existing_ends_by_index?.[i];
                    if (sheetIndex != null && userSheets[sheetIndex] && Array.isArray(ends) && ends.length > 0) {
                        applyTrainingToSheet(userSheets[sheetIndex], { ends: ends });
                    }
                });
                // Toujours charger les données complètes (notes, __SIGNED__) pour chaque session existante,
                // afin de verrouiller les scores si la feuille est signée.
                await loadExistingTrainingData(indicesWithLicence);
            }
        }
    } catch (e) {
        console.warn('Création des sessions à l\'import:', e);
    }
    
    // Afficher les blocs navigation + infos archer + tableau des scores pour que le préremplissage soit visible
    const navEl = document.getElementById('archerNavigation');
    const archerSectionEl = document.getElementById('archerInfoSection');
    const scoreSectionEl = document.getElementById('scoreTableSection');
    if (navEl) navEl.style.display = 'block';
    if (archerSectionEl) archerSectionEl.style.display = 'block';
    if (scoreSectionEl) scoreSectionEl.style.display = 'block';
    const saveBtn = document.getElementById('saveScoreSheetBtn');
    const sigBtn = document.getElementById('signaturesBtn');
    const exportBtn = document.getElementById('exportPdfBtn');
    if (saveBtn) saveBtn.style.display = 'inline-block';
    if (sigBtn) sigBtn.style.display = 'inline-block';
    updateExportButtonVisibility();
    
    currentUserIndex = 0;
    displayCurrentArcher();
    showStatus(`${archers.length} archer(s) prérempli(s) depuis le concours`, 'success');
}

// Mappe abv_discipline (API concours) vers le type de tir de la feuille de marque
function mapDisciplineToShootingType(abv) {
    if (!abv) return null;
    const a = String(abv).toUpperCase().trim();
    if (a === 'S' || a === 'I' || a === 'H') return 'Salle';
    if (a === 'T') return 'TAE';
    if (a === 'N') return 'Nature';
    if (a === '3') return '3D';
    if (a === 'C') return 'Campagne';
    return null;
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
        if (e.target.id === 'archerLicense') {
            if (e.target.readOnly) return;
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
            
            // Remplir automatiquement les informations
            const nameField = document.getElementById('archerName');
            const categoryField = document.getElementById('archerCategory');
            
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
    if (!selectedShootingType || !SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)]) {
        return;
    }
    
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
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
    updateExportButtonVisibility();
    
    // Réinitialiser la recherche par licence maintenant que la section est visible
    setupLicenseSearch();
    
    // Afficher le premier archer
    currentUserIndex = 0;
    displayCurrentArcher();
}

function displayCurrentArcher() {
    if (userSheets.length === 0) return;
    
    const sheet = userSheets[currentUserIndex];
    if (!sheet || !sheet.archerInfo) return;
    
    // Mettre à jour les informations de l'archer (toujours en premier pour que le tableau se remplisse)
    const headerNum = document.getElementById('archerHeaderNumber');
    const currentNum = document.getElementById('currentArcherNumber');
    const totalNum = document.getElementById('totalArchers');
    const nameInput = document.getElementById('archerName');
    const licenseInput = document.getElementById('archerLicense');
    const categorySelect = document.getElementById('archerCategory');
    
    if (headerNum) headerNum.textContent = currentUserIndex + 1;
    if (currentNum) currentNum.textContent = currentUserIndex + 1;
    if (totalNum) totalNum.textContent = NUM_USERS;
    
    if (nameInput) nameInput.value = sheet.archerInfo.name || '';
    if (licenseInput) licenseInput.value = sheet.archerInfo.licenseNumber || '';
    if (categorySelect) categorySelect.value = sheet.archerInfo.category || '';
    
    const fromConcoursImport = !!(sheet.inscriptionId);
    if (nameInput) {
        nameInput.readOnly = fromConcoursImport;
        nameInput.classList.toggle('bg-light', fromConcoursImport);
        nameInput.title = fromConcoursImport ? 'Champ verrouillé (import concours)' : '';
    }
    if (licenseInput) {
        licenseInput.readOnly = fromConcoursImport;
        licenseInput.classList.toggle('bg-light', fromConcoursImport);
        licenseInput.title = fromConcoursImport ? 'Champ verrouillé (import concours)' : '';
    }
    if (categorySelect) {
        categorySelect.disabled = fromConcoursImport;
        categorySelect.classList.toggle('bg-light', fromConcoursImport);
        categorySelect.title = fromConcoursImport ? 'Champ verrouillé (import concours)' : '';
    }
    
    updateConcoursImportLock();
    
    // Mettre à jour le tableau des scores (uniquement si type de tir et config valides)
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    if (config) {
        updateScoreTable(sheet);
    }
    
    // Gérer les boutons de navigation
    const prevBtn = document.getElementById('prevArcherBtn');
    const nextBtn = document.getElementById('nextArcherBtn');
    if (prevBtn) prevBtn.disabled = currentUserIndex === 0;
    if (nextBtn) nextBtn.disabled = currentUserIndex === NUM_USERS - 1;
}

/** Verrouille / déverrouille le type de tir et le titre de la feuille lorsque des archers ont été importés depuis un concours. */
function updateConcoursImportLock() {
    const fromConcoursImport = userSheets.length > 0 && userSheets.some(s => s.inscriptionId);
    const shootingTypeSelect = document.getElementById('shootingType');
    const trainingTitleInput = document.getElementById('trainingTitle');
    if (shootingTypeSelect) {
        shootingTypeSelect.disabled = fromConcoursImport;
        shootingTypeSelect.classList.toggle('bg-light', fromConcoursImport);
        shootingTypeSelect.title = fromConcoursImport ? 'Champ verrouillé (import concours)' : '';
    }
    if (trainingTitleInput) {
        trainingTitleInput.readOnly = fromConcoursImport;
        trainingTitleInput.classList.toggle('bg-light', fromConcoursImport);
        trainingTitleInput.title = fromConcoursImport ? 'Champ verrouillé (import concours)' : '';
    }
}

/** Pour le tir Nature : retourne la colonne croix (20-15, 20-10, 15-15, 15-10) selon les scores des 2 flèches, ou '' si aucune. */
function getNatureCrossColumn(score1, score2) {
    if (score1 === 20 && score2 === 15) return '20-15';
    if (score1 === 20 && score2 === 10) return '20-10';
    if (score1 === 15 && score2 === 15) return '15-15';
    if (score1 === 15 && score2 === 10) return '15-10';
    return '';
}

function updateScoreTable(sheet) {
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    const isNature = (getShootingConfigKey(selectedShootingType) === 'Nature' || getShootingConfigKey(selectedShootingType) === 'Nature2x21');
    // Même condition que pour masquer le bouton signature (exportedToConcours) et verrouiller les scores (signé / notes / signatures)
    const signedFromNotes = !!(sheet.notes && String(sheet.notes).indexOf('__SIGNED__:1') !== -1);
    const scoresLocked = !!exportedToConcours || !!sheet.signed || signedFromNotes || (!!scorerSignature && !!archerSignatures[currentUserIndex]);
    const tableBody = document.getElementById('scoreTableBody');
    
    // Nettoyer le tableau
    tableBody.innerHTML = '';
    
    if (isNature) {
        // En-têtes tableau Nature (modèle feuilles-marques édition concours) — 2 lignes
        const thead = document.querySelector('#scoreTable thead');
        if (thead) {
            thead.innerHTML = `
                <tr>
                    <th class="volley-col">N° cible</th>
                    <th colspan="3">Flèche 1</th>
                    <th colspan="3">Flèche 2</th>
                    <th class="total-col">Total 2 flèches</th>
                    <th id="cumulativeHeader" class="cumulative-col">Cumul</th>
                    <th class="nature-cross-header">20-15</th>
                    <th class="nature-cross-header">20-10</th>
                    <th class="nature-cross-header">15-15</th>
                    <th class="nature-cross-header">15-10</th>
                </tr>
                <tr>
                    <th></th>
                    <th>20</th><th>15</th><th>0</th>
                    <th>15</th><th>10</th><th>0</th>
                    <th></th><th></th>
                    <th class="nature-cross-header"></th>
                    <th class="nature-cross-header"></th>
                    <th class="nature-cross-header"></th>
                    <th class="nature-cross-header"></th>
                </tr>
            `;
        }
        const cumHead = document.getElementById('cumulativeHeader');
        if (cumHead) cumHead.style.display = 'table-cell';
        const footerCum = document.getElementById('footerCumulative');
        if (footerCum) footerCum.style.display = 'table-cell';
    } else {
        // Remettre un thead à une seule ligne pour les autres types
        const thead = document.querySelector('#scoreTable thead');
        if (thead) {
            thead.innerHTML = `
                <tr>
                    <th class="volley-col">Volée</th>
                    <th id="arrowHeaders" colspan="3"></th>
                    <th class="total-col">Total</th>
                    <th id="cumulativeHeader" class="cumulative-col" style="display: none;">Cumul</th>
                </tr>
            `;
        }
        const arrowHeadersEl = document.getElementById('arrowHeaders');
        let arrowHeadersHtml = '';
        for (let i = 1; i <= config.arrows_per_end; i++) {
            arrowHeadersHtml += `<th class="arrow-col">F${i}</th>`;
        }
        if (arrowHeadersEl) {
            arrowHeadersEl.innerHTML = arrowHeadersHtml;
            arrowHeadersEl.setAttribute('colspan', config.arrows_per_end);
        }
        const hasSeries = config.series === 2;
        const cumHeadEl = document.getElementById('cumulativeHeader');
        const footerCumEl = document.getElementById('footerCumulative');
        if (hasSeries) {
            if (cumHeadEl) cumHeadEl.style.display = 'table-cell';
            if (footerCumEl) footerCumEl.style.display = 'table-cell';
        } else {
            if (cumHeadEl) cumHeadEl.style.display = 'none';
            if (footerCumEl) footerCumEl.style.display = 'none';
        }
    }
    
    // Générer les lignes
    let cumulative = 0;
    let series1Total = 0;
    let series2Total = 0;
    let currentSeries = 1;
    const hasSeries = config.series === 2;
    const endsPerSeriesDisplay = hasSeries ? config.total_ends / config.series : 0;
    
    sheet.scoreRows.forEach((row, index) => {
        // Recalculer les totaux
        row.endTotal = row.arrows.reduce((sum, arrow) => sum + (arrow.value || 0), 0);
        if (hasSeries && endsPerSeriesDisplay > 0) {
            row.seriesNumber = Math.floor(index / endsPerSeriesDisplay) + 1;
        }
        if (isNature) {
            cumulative += row.endTotal;
            row.cumulativeTotal = cumulative;
            if (row.seriesNumber === 1) series1Total += row.endTotal;
            else if (row.seriesNumber === 2) series2Total += row.endTotal;
        } else {
            // Déterminer la série
            if (hasSeries) {
                const endsPerSeries = config.total_ends / config.series;
                row.seriesNumber = Math.floor(index / endsPerSeries) + 1;
                if (row.seriesNumber !== currentSeries) {
                    cumulative = 0;
                    currentSeries = row.seriesNumber;
                }
            }
            cumulative += row.endTotal;
            row.cumulativeTotal = cumulative;
            if (row.seriesNumber === 1) series1Total += row.endTotal;
            else if (row.seriesNumber === 2) series2Total += row.endTotal;
        }
        
        if (isNature) {
            const s1 = row.arrows[0]?.value;
            const s2 = row.arrows[1]?.value;
            const cross = getNatureCrossColumn(s1, s2);
            const f1_20 = (s1 === 20) ? '✗' : '';
            const f1_15 = (s1 === 15) ? '✗' : '';
            const f1_0 = (s1 === 0) ? '✗' : '';
            const f2_15 = (s2 === 15) ? '✗' : '';
            const f2_10 = (s2 === 10) ? '✗' : '';
            const f2_0 = (s2 === 0) ? '✗' : '';
            const rowHtml = document.createElement('tr');
            rowHtml.className = (index % 2 === 1) ? 'feuille-marque-row-even' : '';
            console.log('scoresLocked', scoresLocked);
            const volleyBtnNature = scoresLocked
                ? `<span class="btn btn-sm btn-outline-secondary volley-disabled" title="Feuille signée, scores non modifiables" style="pointer-events:none;cursor:not-allowed">${row.endNumber}</span>`
                : `<button type="button" class="btn btn-sm btn-outline-primary volley-open-btn" data-row-index="${index}" title="Modifier la volée">${row.endNumber}</button>`;
            rowHtml.innerHTML = `
                <td class="volley-col">
                    ${volleyBtnNature}
                </td>
                <td class="arrow-col nature-score">${f1_20}</td>
                <td class="arrow-col nature-score">${f1_15}</td>
                <td class="arrow-col nature-score">${f1_0}</td>
                <td class="arrow-col nature-score">${f2_15}</td>
                <td class="arrow-col nature-score">${f2_10}</td>
                <td class="arrow-col nature-score">${f2_0}</td>
                <td class="total-col">${row.endTotal > 0 ? row.endTotal : ''}</td>
                <td class="cumulative-col">${row.cumulativeTotal > 0 ? row.cumulativeTotal : ''}</td>
                <td class="nature-cross">${cross === '20-15' ? '✗' : ''}</td>
                <td class="nature-cross">${cross === '20-10' ? '✗' : ''}</td>
                <td class="nature-cross">${cross === '15-15' ? '✗' : ''}</td>
                <td class="nature-cross">${cross === '15-10' ? '✗' : ''}</td>
            `;
            tableBody.appendChild(rowHtml);
        } else {
            const rowHtml = document.createElement('tr');
            rowHtml.className = hasSeries && row.seriesNumber === 2 ? 'second-series' : '';
            const volleyBtn = scoresLocked
                ? `<span class="btn btn-sm btn-outline-secondary volley-disabled" title="Feuille signée, scores non modifiables" style="pointer-events:none;cursor:not-allowed">${row.endNumber}</span>`
                : `<button type="button" class="btn btn-sm btn-outline-primary volley-open-btn" data-row-index="${index}" title="Modifier la volée">${row.endNumber}</button>`;
            rowHtml.innerHTML = `
                <td class="volley-col">
                    ${volleyBtn}
                </td>
                ${row.arrows.map((arrow, arrowIndex) => `
                    <td class="arrow-col">${arrow.value > 0 ? arrow.value : ''}</td>
                `).join('')}
                <td class="total-col">${row.endTotal}</td>
                ${hasSeries ? `<td class="cumulative-col">${row.cumulativeTotal}</td>` : ''}
            `;
            tableBody.appendChild(rowHtml);
        }
    });
    
    if (isNature) {
        if (hasSeries && (series1Total > 0 || series2Total > 0)) {
            const series1Row = document.createElement('tr');
            series1Row.className = 'series-total table-secondary';
            series1Row.innerHTML = `<td colspan="7"><strong>Passage 1 (Série 1)</strong></td><td class="total-col"><strong>${series1Total}</strong></td><td class="cumulative-col"></td><td colspan="4"></td>`;
            tableBody.appendChild(series1Row);
            const series2Row = document.createElement('tr');
            series2Row.className = 'series-total table-secondary';
            series2Row.innerHTML = `<td colspan="7"><strong>Passage 2 (Série 2)</strong></td><td class="total-col"><strong>${series2Total}</strong></td><td class="cumulative-col"></td><td colspan="4"></td>`;
            tableBody.appendChild(series2Row);
        }
        // Totaux 20-15, 20-10, 15-15, 15-10
        let count20_15 = 0, count20_10 = 0, count15_15 = 0, count15_10 = 0;
        sheet.scoreRows.forEach(row => {
            const cross = getNatureCrossColumn(row.arrows[0]?.value, row.arrows[1]?.value);
            if (cross === '20-15') count20_15++;
            else if (cross === '20-10') count20_10++;
            else if (cross === '15-15') count15_15++;
            else if (cross === '15-10') count15_10++;
        });
        // Ligne "Total des cibles" en pied du tableau Nature
        const totalRow = document.createElement('tr');
        totalRow.className = 'table-secondary feuille-marque-ligne-resume';
        const grandTotal = sheet.scoreRows.reduce((sum, row) => sum + row.endTotal, 0);
        totalRow.innerHTML = `
            <td colspan="7"><strong>Total des cibles</strong></td>
            <td class="total-col"><strong>${grandTotal}</strong></td>
            <td class="cumulative-col"></td>
            <td class="nature-cross"><strong>${count20_15}</strong></td>
            <td class="nature-cross"><strong>${count20_10}</strong></td>
            <td class="nature-cross"><strong>${count15_15}</strong></td>
            <td class="nature-cross"><strong>${count15_10}</strong></td>
        `;
        tableBody.appendChild(totalRow);
        document.getElementById('grandTotal').textContent = grandTotal;
        const tfoot = document.querySelector('#scoreTable tfoot tr');
        if (tfoot) {
            tfoot.innerHTML = `<th>TOTAL</th><td colspan="7"></td><th id="grandTotal">${grandTotal}</th><th id="footerCumulative"></th><td colspan="4"></td>`;
        }
    } else {
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
        const grandTotal = hasSeries ? (series1Total + series2Total) : sheet.scoreRows.reduce((sum, row) => sum + row.endTotal, 0);
        const tfootTr = document.querySelector('#scoreTable tfoot tr');
        if (tfootTr && tfootTr.querySelector('td[colspan="7"]')) {
            tfootTr.innerHTML = '<th>TOTAL</th><td id="footerArrows" colspan="3"></td><th id="grandTotal">0</th><th id="footerCumulative" style="display: none;"></th>';
        }
        document.getElementById('grandTotal').textContent = grandTotal;
        const footerArrows = document.getElementById('footerArrows');
        if (footerArrows) footerArrows.setAttribute('colspan', config.arrows_per_end);
    }
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
    if (sheet.inscriptionId) return;
    sheet.archerInfo.name = document.getElementById('archerName').value;
    sheet.archerInfo.licenseNumber = document.getElementById('archerLicense').value;
    sheet.archerInfo.category = document.getElementById('archerCategory').value;
}

function openScoreModal(rowIndex) {
    if (userSheets.length === 0) return;
    
    const sheet = userSheets[currentUserIndex];
    const signedFromNotes = !!(sheet.notes && String(sheet.notes).indexOf('__SIGNED__:1') !== -1);
    if (!!exportedToConcours || !!sheet.signed || signedFromNotes || (!!scorerSignature && !!archerSignatures[currentUserIndex])) {
        showStatus('Feuille signée : les scores ne sont plus modifiables.', 'info');
        return;
    }

    currentModalRow = rowIndex;
    currentModalUserIndex = currentUserIndex;
    
    const row = sheet.scoreRows[rowIndex];
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    const isNature = (getShootingConfigKey(selectedShootingType) === 'Nature' || getShootingConfigKey(selectedShootingType) === 'Nature2x21');
    
    document.getElementById('modalVolleyNumber').textContent = row.endNumber;
    
    const scoreInputs = document.getElementById('scoreInputs');
    scoreInputs.innerHTML = '';
    
    if (isNature) {
        // Tir Nature : boutons d'option 20-15-0 et 15-10-0
        const v1 = row.arrows[0]?.value;
        const v2 = row.arrows[1]?.value;
        scoreInputs.innerHTML = `
            <div class="mb-4">
                <label class="form-label fw-bold">Flèche 1 (20 - 15 - 0)</label>
                <div class="btn-group nature-score-options d-flex" role="group">
                    <input type="radio" class="btn-check" name="nature_f1" id="f1_20" value="20" ${v1 === 20 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f1_20">20</label>
                    <input type="radio" class="btn-check" name="nature_f1" id="f1_15" value="15" ${v1 === 15 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f1_15">15</label>
                    <input type="radio" class="btn-check" name="nature_f1" id="f1_0" value="0" ${v1 === 0 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f1_0">0</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Flèche 2 (15 - 10 - 0)</label>
                <div class="btn-group nature-score-options d-flex" role="group">
                    <input type="radio" class="btn-check" name="nature_f2" id="f2_15" value="15" ${v2 === 15 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f2_15">15</label>
                    <input type="radio" class="btn-check" name="nature_f2" id="f2_10" value="10" ${v2 === 10 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f2_10">10</label>
                    <input type="radio" class="btn-check" name="nature_f2" id="f2_0" value="0" ${v2 === 0 ? 'checked' : ''}>
                    <label class="btn btn-outline-primary" for="f2_0">0</label>
                </div>
            </div>
        `;
    } else {
        // Créer les inputs pour chaque flèche (autres types)
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
    }
    
    scoreModal.show();
}

function updateArrowValue(arrowIndex, value) {
    if (currentModalUserIndex === null || currentModalRow === null) return;
    
    const sheet = userSheets[currentModalUserIndex];
    const row = sheet.scoreRows[currentModalRow];
    
    if (row.arrows[arrowIndex]) {
        row.arrows[arrowIndex].value = parseInt(value) || 0;
    }
}

async function saveVolleyScores() {
    if (currentModalUserIndex === null || currentModalRow === null) return;
    
    const sheet = userSheets[currentModalUserIndex];
    const row = sheet.scoreRows[currentModalRow];
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    const isNature = (getShootingConfigKey(selectedShootingType) === 'Nature' || getShootingConfigKey(selectedShootingType) === 'Nature2x21');
    
    if (isNature) {
        // Lire les boutons d'option Nature
        const f1 = document.querySelector('input[name="nature_f1"]:checked');
        const f2 = document.querySelector('input[name="nature_f2"]:checked');
        if (row.arrows[0]) row.arrows[0].value = f1 ? parseInt(f1.value, 10) : null;
        if (row.arrows[1]) row.arrows[1].value = f2 ? parseInt(f2.value, 10) : null;
    }
    // Pour les autres types : les valeurs sont déjà mises à jour via updateArrowValue (mode tableau)
    
    // Recalculer total volée et cumul
    row.endTotal = row.arrows.reduce((sum, arrow) => sum + (arrow.value || 0), 0);
    let cumul = 0;
    const sheetRows = sheet.scoreRows;
    for (let i = 0; i < sheetRows.length; i++) {
        cumul += sheetRows[i].arrows.reduce((s, a) => s + (a.value || 0), 0);
        sheetRows[i].cumulativeTotal = cumul;
    }
    
    // Enregistrer en base : scored_ends (valeur calculée de la volée) et scored_shots (valeur de chaque flèche),
    // associés à la session (scored_trainings) de l'archer créée précédemment à l'import.
    const trainingId = sheet.scoredTrainingId;
    if (sheet.signed) {
        showStatus('Feuille signée : les scores ne sont plus modifiables.', 'info');
        scoreModal.hide();
        displayCurrentArcher();
        return;
    }
    if (trainingId && !row.savedToServer) {
        try {
            const addEndPayload = {
                training_id: trainingId,
                end_number: row.endNumber,
                end_total: row.endTotal,
                arrows: row.arrows.map(a => ({
                    value: a.value ?? 0,
                    hit_x: a.hit_x,
                    hit_y: a.hit_y
                })),
                comment: row.comment || ''
            };
            const addResp = await fetch('/score-sheet/add-end', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(addEndPayload)
            });
            const addResult = await addResp.json().catch(() => ({}));
            if (addResult.success) row.savedToServer = true;
        } catch (err) {
            console.warn('Enregistrement volée:', err);
        }
    }
    
    scoreModal.hide();
    
    // Mettre à jour l'affichage
    displayCurrentArcher();
}

/**
 * Sauvegarde les feuilles de marque (scored_trainings).
 * @param {Object} options - Options de sauvegarde
 * @param {boolean} [options.redirect=true] - Rediriger vers /scored-trainings après succès
 * @param {boolean} [options.silent=false] - Ne pas afficher de message de statut
 * @returns {Promise<{success: boolean, message?: string}>}
 */
function saveScoreSheet(options = {}) {
    const { redirect = true, silent = false } = options;
    if (!selectedShootingType || userSheets.length === 0) {
        if (!silent) showStatus('Veuillez sélectionner un type de tir', 'danger');
        return Promise.resolve({ success: false, message: 'Données manquantes' });
    }
    
    // Sauvegarder les informations de l'archer actuel
    saveCurrentArcherInfo();
    
    // Préparer les données à envoyer
    const dataToSend = {
        shooting_type: getShootingConfigKey(selectedShootingType) || selectedShootingType,
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
            
            const out = {
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
            if (sheet.scoredTrainingId) out.scored_training_id = sheet.scoredTrainingId;
            return out;
        }).filter(sheet => sheet !== null)
    };
    
    const saveBtn = document.getElementById('saveScoreSheetBtn');
    const originalText = saveBtn?.innerHTML;
    if (saveBtn && redirect) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde en cours...';
    }
    
    return fetch('/score-sheet/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSend)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!silent) showStatus(data.message || 'Feuilles de marque sauvegardées avec succès !', 'success');
            // Marquer comme signées les feuilles pour lesquelles on a envoyé archer + marqueur
            userSheets.forEach((sheet, userIndex) => {
                if (archerSignatures[userIndex] && scorerSignature) {
                    sheet.signed = true;
                    if (sheet.notes == null || String(sheet.notes).indexOf('__SIGNED__:1') === -1) {
                        sheet.notes = (sheet.notes || '') + (sheet.notes ? '\n' : '') + '__SIGNED__:1';
                    }
                }
            });
            displayCurrentArcher(); // Refaire le tableau pour afficher les scores verrouillés
            const departSelect = document.getElementById('departSelect');
            const pelotonSelect = document.getElementById('pelotonSelect');
            const dep = departSelect?.value ?? '';
            const pel = pelotonSelect?.value ?? '';
            const savedKey = 'scoreSheet_saved_' + (selectedConcoursId || '') + '_' + dep + '_' + pel;
            try { localStorage.setItem(savedKey, '1'); } catch (e) {}
            sheetsSavedForBatch = true;
            updateExportButtonVisibility();
            if (redirect) {
                setTimeout(() => { window.location.href = '/scored-trainings'; }, 2000);
            }
        } else {
            if (!silent) showStatus(data.message || 'Erreur lors de la sauvegarde', 'danger');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        }
        return data;
    })
    .catch(error => {
        if (!silent) showStatus('Erreur de connexion au serveur', 'danger');
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        return { success: false, message: error?.message || 'Erreur réseau' };
    });
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('statusMessage');
    if (!statusDiv) return;
    statusDiv.className = `alert alert-${type}`;
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
    statusDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Masquer après 10 secondes
    setTimeout(() => {
        statusDiv.style.display = 'none';
    }, 10000);
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
    // Sauvegarder la signature actuelle (canvas courant)
    if (signatureCanvas && signatureCtx) {
        const signatureData = signatureCanvas.toDataURL('image/png');
        
        if (currentSignatureType === 'scorer') {
            scorerSignature = signatureData;
        } else if (currentSignatureUserIndex !== null) {
            archerSignatures[currentSignatureUserIndex] = signatureData;
        }
    }
    
    // Sauvegarder toutes les signatures visibles depuis les canvas
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
    
    const activeArchers = userSheets
        .map((sheet, index) => ({ sheet, index }))
        .filter(({ sheet }) => sheet.scoreRows.some(row => row.endTotal > 0));
    
    if (!scorerSignature) {
        showStatus('Le marqueur doit signer avant d\'enregistrer.', 'warning');
        return;
    }
    const missing = activeArchers.filter(({ index }) => !archerSignatures[index]);
    if (missing.length > 0) {
        const names = missing.map(({ sheet }) => sheet.archerInfo?.name || 'Archer').join(', ');
        showStatus(`Tous les archers doivent signer : ${names} n'ont pas encore signé.`, 'warning');
        return;
    }
    
    showStatus('Signatures enregistrées', 'success');
    bootstrap.Modal.getInstance(document.getElementById('signatureModal')).hide();
    updateExportButtonVisibility();
    displayCurrentArcher();
    saveScoreSheet({ redirect: false, silent: true }).then((data) => {
        if (data && data.success) showStatus('Signatures enregistrées et sauvegardées.', 'success');
    }).catch(() => {});
}

/** Vérifie si toutes les feuilles avec scores ont archer + marqueur signé */
function areAllSheetsSigned() {
    if (!scorerSignature) return false;
    const sheetsWithScores = userSheets
        .map((s, i) => ({ sheet: s, index: i }))
        .filter(({ sheet }) => sheet.scoreRows.some(row => row.endTotal > 0));
    if (sheetsWithScores.length === 0) return false;
    for (const { index } of sheetsWithScores) {
        if (!archerSignatures[index]) return false;
    }
    return true;
}

/** Affiche les boutons Signatures, Export et Sauvegarder selon l'état (exporté, sauvegardé, signé). */
function updateExportButtonVisibility() {
    const exportBtn = document.getElementById('exportPdfBtn');
    const sigBtn = document.getElementById('signaturesBtn');
    const saveBtn = document.getElementById('saveScoreSheetBtn');
    if (exportedToConcours) {
        if (exportBtn) exportBtn.style.display = 'none';
        if (sigBtn) sigBtn.style.display = 'none';
    } else {
        if (exportBtn) exportBtn.style.display = areAllSheetsSigned() ? 'inline-block' : 'none';
        if (sigBtn) sigBtn.style.display = 'inline-block';
    }
    if (saveBtn) saveBtn.style.display = (exportedToConcours || sheetsSavedForBatch) ? 'none' : 'inline-block';
}

// ==================== EXPORT VERS CONCOURS ====================

/** Saisit les scores dans concours_resultats puis propose l'export PDF */
async function exportToConcours() {
    console.log('exportToConcours appelé');
    if (!selectedConcoursId) {
        showStatus('Veuillez sélectionner un concours pour exporter les scores.', 'warning');
        return;
    }
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    if (!config) {
        showStatus('Type de tir invalide.', 'danger');
        return;
    }
    const sheetsToExport = userSheets.filter(s => {
        const lic = (s.archerInfo?.licenseNumber ?? '').toString().trim();
        const hasScores = s.scoreRows?.some(row => (row.endTotal || 0) > 0);
        return lic !== '' && hasScores && (s.inscriptionId || lic);
    });
    if (sheetsToExport.length === 0) {
        showStatus('Aucune feuille avec licence et scores à exporter.', 'warning');
        return;
    }
    const configKey = getShootingConfigKey(selectedShootingType);
    const isNature = configKey === 'Nature' || configKey === 'Nature2x21';
    const hasSeries = config.series === 2;
    const endsPerSeries = hasSeries ? config.total_ends / config.series : 0;

    const departSelect = document.getElementById('departSelect');
    // Recalculer endTotal depuis les flèches avant export (évite score vide si désynchronisation)
    sheetsToExport.forEach(sheet => {
        (sheet.scoreRows || []).forEach(row => {
            const fromArrows = (row.arrows || []).reduce((s, a) => s + (parseInt(a?.value, 10) || 0), 0);
            row.endTotal = fromArrows > 0 ? fromArrows : (row.endTotal || 0);
        });
    });
    const payload = {
        concours_id: selectedConcoursId,
        shooting_type: getShootingConfigKey(selectedShootingType),
        depart: departSelect?.value || '',
        serie_mode: 'both',
        user_sheets: sheetsToExport.map(sheet => {
            const score = sheet.scoreRows.reduce((sum, row) => {
                const et = row.endTotal || 0;
                const fromArrows = (row.arrows || []).reduce((s, a) => s + (parseInt(a?.value, 10) || 0), 0);
                return sum + (et > 0 ? et : fromArrows);
            }, 0);
            const lic = (sheet.archerInfo?.licenseNumber ?? '').toString().trim();
            const data = { score, license_number: lic };
            if (sheet.inscriptionId) data.inscription_id = sheet.inscriptionId;
            if (sheet.scoredTrainingId) data.scored_training_id = sheet.scoredTrainingId;
            if (isNature) {
                let nb_20_15 = 0, nb_20_10 = 0, nb_15_15 = 0, nb_15_10 = 0, nb_15 = 0, nb_10 = 0, nb_0 = 0;
                sheet.scoreRows.forEach(row => {
                    const s1 = parseInt(row.arrows[0]?.value, 10) || 0;
                    const s2 = parseInt(row.arrows[1]?.value, 10) || 0;
                    const cross = getNatureCrossColumn(s1, s2);
                    if (cross === '20-15') nb_20_15++;
                    else if (cross === '20-10') nb_20_10++;
                    else if (cross === '15-15') nb_15_15++;
                    else if (cross === '15-10') nb_15_10++;
                    else if ((s1 === 15 && s2 === 0) || (s1 === 0 && s2 === 15)) nb_15++;
                    else if (s1 === 10 || s2 === 10) nb_10 += (s1 === 10 ? 1 : 0) + (s2 === 10 ? 1 : 0);
                    else if (s1 === 0 && s2 === 0) nb_0++;
                });
                data.nb_20_15 = nb_20_15;
                data.nb_20_10 = nb_20_10;
                data.nb_15_15 = nb_15_15;
                data.nb_15_10 = nb_15_10;
                data.nb_15 = nb_15;
                data.nb_10 = nb_10;
                data.nb_0 = nb_0;
                if (hasSeries && endsPerSeries > 0) {
                    const s1 = sheet.scoreRows.slice(0, endsPerSeries).reduce((sum, row) => sum + (row.endTotal || 0), 0);
                    const s2 = sheet.scoreRows.slice(endsPerSeries, 2 * endsPerSeries).reduce((sum, row) => sum + (row.endTotal || 0), 0);
                    data.serie1_score = s1;
                    data.serie2_score = s2;
                } else {
                    data.serie1_score = score;
                }
            } else if (hasSeries) {
                let s1 = 0, s2 = 0, s1_10 = 0, s1_9 = 0, s2_10 = 0, s2_9 = 0;
                sheet.scoreRows.forEach((row, idx) => {
                    const tot = row.endTotal || 0;
                    const arrows = row.arrows || [];
                    if (idx < endsPerSeries) {
                        s1 += tot;
                        arrows.forEach(a => {
                            const v = parseInt(a?.value, 10) || 0;
                            if (v === 10) s1_10++;
                            else if (v === 9) s1_9++;
                        });
                    } else {
                        s2 += tot;
                        arrows.forEach(a => {
                            const v = parseInt(a?.value, 10) || 0;
                            if (v === 10) s2_10++;
                            else if (v === 9) s2_9++;
                        });
                    }
                });
                data.serie1_score = s1;
                data.serie2_score = s2;
                data.serie1_nb_10 = s1_10;
                data.serie1_nb_9 = s1_9;
                data.serie2_nb_10 = s2_10;
                data.serie2_nb_9 = s2_9;
            }
            return data;
        })
    };

    const btn = document.getElementById('exportPdfBtn');
    const origHtml = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Export...'; }

    try {
        // Synchroniser les données du modal de saisie si ouvert (évite score vide)
        if (currentModalUserIndex !== null && currentModalRow !== null) {
            await saveVolleyScores();
        }
        // 1. Sauvegarder d'abord les scores dans les tirs comptés (scored_trainings)
        const saveResult = await saveScoreSheet({ redirect: false, silent: true });
        if (!saveResult.success && saveResult.message && saveResult.message !== 'Données manquantes') {
            showStatus('Sauvegarde des scores échouée: ' + (saveResult.message || ''), 'warning');
            // On continue quand même l'export vers le concours (les scores sont dans le payload)
        }

        // 2. Exporter vers concours_resultats
        console.log('Export: envoi requête...', { concours_id: selectedConcoursId, nb_sheets: payload.user_sheets.length, scores: payload.user_sheets.map(s => ({ lic: s.license_number, score: s.score })) });
        const resp = await fetch('/score-sheet/export-to-concours', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        console.log('Export: réponse reçue', { status: resp.status, ok: resp.ok, contentType: resp.headers.get('Content-Type') });
        if (resp.status === 401) {
            showStatus('Session expirée. Veuillez vous reconnecter.', 'danger');
            return;
        }
        const rawText = await resp.text();
        console.log('Export: rawText (premiers 300 car.)=', rawText ? rawText.slice(0, 300) : '(vide)');
        const result = (() => {
            try {
                return JSON.parse(rawText);
            } catch (e) {
                console.error('Export: réponse non-JSON', { status: resp.status, preview: rawText?.slice(0, 200) });
                return { success: false, message: 'Réponse serveur invalide (status ' + resp.status + '). Vérifiez la console.' };
            }
        })();
        console.log('Export: result=', JSON.stringify(result));
        if (result.success) {
            showStatus(result.message || 'Scores exportés vers le concours avec succès.', 'success');
            exportedToConcours = true;
            const departSelect = document.getElementById('departSelect');
            const pelotonSelect = document.getElementById('pelotonSelect');
            const dep = departSelect?.value ?? '';
            const pel = pelotonSelect?.value ?? '';
            const storageKey = 'scoreSheet_exported_' + (selectedConcoursId || '') + '_' + dep + '_' + pel;
            try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            updateExportButtonVisibility();
        } else {
            const errMsg = result.message || result.error || 'Erreur lors de l\'export vers le concours.';
            console.error('Export concours échoué:', { status: resp.status, result, payload });
            showStatus(errMsg, 'danger');
        }
    } catch (e) {
        console.error('Export concours exception:', e);
        showStatus('Erreur lors de l\'export: ' + (e.message || 'voir la console F12'), 'danger');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = origHtml || '<i class="fas fa-upload"></i> Exporter vers concours'; }
    }
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
    const shootingTypeLabel = document.getElementById('shootingType')?.selectedOptions?.[0]?.textContent ?? selectedShootingType;
    doc.text(`Type de tir: ${shootingTypeLabel}`, 20, yPosition);
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

