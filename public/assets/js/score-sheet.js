/**
 * JavaScript pour la feuille de marque
 */

// Configuration des types de tir (clé = type logique pour volées/flèches ; le select stocke abv_discipline)
const SHOOTING_CONFIGS = {
    'Salle': { total_ends: 20, arrows_per_end: 3, total_arrows: 60, series: 2 },
    'TAE': { total_ends: 12, arrows_per_end: 6, total_arrows: 72, series: 2 },
    'Nature': { total_ends: 21, arrows_per_end: 2, total_arrows: 42 },
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
            selectedShootingType = this.value;
            if (selectedShootingType) {
                initializeSheets();
            }
            loadCategoriesForDiscipline(getDisciplineIdForAbv(selectedShootingType));
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

// Préremplir les archers depuis le concours (plan cible, peloton ou inscriptions)
async function prefillArchersFromConcours() {
    const departSelect = document.getElementById('departSelect');
    const pelotonSelect = document.getElementById('pelotonSelect');

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
            const lic = i.numero_licence || i.numeroLicence;
            if (lic) inscriptionsMap[lic] = i;
        });
        
        plans.sort((a, b) => (a.position_archer || '').localeCompare(b.position_archer || ''));
        archers = plans.map(p => {
            const lic = p.numero_licence || '';
            const insc = inscriptionsMap[lic] || {};
            const nom = insc.user_nom || insc.nom || insc.name || p.user_nom || '';
            const cat = insc.categorie_classement || insc.categorieClassement || insc.abv_categorie_classement || '';
            return {
                name: nom,
                licenseNumber: lic,
                category: cat,
                gender: (insc.genre || insc.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
                userId: insc.user_id || insc.userId || insc.id_user || p.user_id
            };
        });
    } else if (concoursPlansPeloton && departSelect?.value && pelotonSelect?.value ) {
        // Mode peloton (N/3/C) : extraire les archers du peloton
        const dep = departSelect.value;
        const pel = parseInt(pelotonSelect.value);
        const plans = (concoursPlansPeloton[dep] || []).filter(p => (p.numero_peloton || 0) == pel);
        const inscriptionsMap = {};
        (concoursInscriptions || []).forEach(i => {
            const lic = i.numero_licence || i.numeroLicence;
            if (lic) inscriptionsMap[lic] = i;
        });
        
        plans.sort((a, b) => (a.position_archer || '').localeCompare(b.position_archer || ''));
        archers = plans.map(p => {
            const lic = p.numero_licence || '';
            const insc = inscriptionsMap[lic] || {};
            const nom = insc.user_nom || insc.nom || insc.name || p.user_nom || '';
            const cat = insc.categorie_classement || insc.categorieClassement || insc.abv_categorie_classement || '';
            return {
                name: nom,
                licenseNumber: lic,
                category: cat,
                gender: (insc.genre || insc.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
                userId: insc.user_id || insc.userId || insc.id_user || p.user_id
            };
        });
    } else if (concoursInscriptions && concoursInscriptions.length > 0) {
        // Mode inscriptions : utiliser les 6 premiers (ou tous)
        archers = concoursInscriptions.slice(0, NUM_USERS).map(i => ({
            name: i.user_nom || i.nom || i.name || '',
            licenseNumber: i.numero_licence || i.numeroLicence || '',
            category: i.categorie_classement || i.categorieClassement || i.abv_categorie_classement || '',
            gender: (i.genre || i.gender || '').toUpperCase().startsWith('F') ? 'F' : 'H',
            userId: i.user_id || i.userId || i.id_user
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
                result.data.training_ids.forEach((tid, i) => {
                    const sheetIndex = indicesWithLicence[i];
                    if (sheetIndex != null && userSheets[sheetIndex] && tid != null) {
                        userSheets[sheetIndex].scoredTrainingId = tid;
                    }
                });
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
    if (exportBtn) exportBtn.style.display = 'inline-block';
    
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
    const isNature = getShootingConfigKey(selectedShootingType) === 'Nature';
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
    const hasSeries = !isNature && config.series === 2;
    
    sheet.scoreRows.forEach((row, index) => {
        // Recalculer les totaux
        row.endTotal = row.arrows.reduce((sum, arrow) => sum + (arrow.value || 0), 0);
        
        if (isNature) {
            cumulative += row.endTotal;
            row.cumulativeTotal = cumulative;
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
            rowHtml.innerHTML = `
                <td class="volley-col">
                    <button class="btn btn-sm btn-outline-primary" onclick="openScoreModal(${index})">
                        ${row.endNumber}
                    </button>
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
        }
    });
    
    if (isNature) {
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
    sheet.archerInfo.name = document.getElementById('archerName').value;
    sheet.archerInfo.licenseNumber = document.getElementById('archerLicense').value;
    sheet.archerInfo.category = document.getElementById('archerCategory').value;
}

function openScoreModal(rowIndex) {
    if (userSheets.length === 0) return;
    
    currentModalRow = rowIndex;
    currentModalUserIndex = currentUserIndex;
    
    const sheet = userSheets[currentUserIndex];
    const row = sheet.scoreRows[rowIndex];
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    const isNature = getShootingConfigKey(selectedShootingType) === 'Nature';
    
    document.getElementById('modalVolleyNumber').textContent = row.endNumber;
    
    const scoreInputs = document.getElementById('scoreInputs');
    scoreInputs.innerHTML = '';
    
    const tableTab = document.getElementById('table-tab');
    const targetTab = document.getElementById('target-tab');
    if (isNature) {
        if (targetTab) targetTab.style.display = 'none';
        if (tableTab) tableTab.click();
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
        if (targetTab) targetTab.style.display = '';
    }
    
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

async function saveVolleyScores() {
    if (currentModalUserIndex === null || currentModalRow === null) return;
    
    const sheet = userSheets[currentModalUserIndex];
    const row = sheet.scoreRows[currentModalRow];
    const config = SHOOTING_CONFIGS[getShootingConfigKey(selectedShootingType)];
    const isNature = getShootingConfigKey(selectedShootingType) === 'Nature';
    
    if (isNature) {
        // Lire les boutons d'option Nature
        const f1 = document.querySelector('input[name="nature_f1"]:checked');
        const f2 = document.querySelector('input[name="nature_f2"]:checked');
        if (row.arrows[0]) row.arrows[0].value = f1 ? parseInt(f1.value, 10) : null;
        if (row.arrows[1]) row.arrows[1].value = f2 ? parseInt(f2.value, 10) : null;
    } else {
        // Vérifier si on est en mode cible interactive
        const targetPane = document.getElementById('targetMode');
        const isTargetMode = targetPane && targetPane.classList.contains('active');
        
        if (isTargetMode && targetScores.length > 0) {
            targetScores.forEach((score, index) => {
                if (row.arrows[index] && score !== null && score !== undefined) {
                    row.arrows[index].value = score;
                    if (targetCoordinates[index]) {
                        row.arrows[index].hit_x = targetCoordinates[index].x;
                        row.arrows[index].hit_y = targetCoordinates[index].y;
                    }
                }
            });
        }
        // Sinon les valeurs sont déjà mises à jour via updateArrowValue
    }
    
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
    const isBlasonCampagne = getShootingConfigKey(selectedShootingType) === 'Campagne';
    
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

