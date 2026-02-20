// Charger la config depuis data-config (séparation PHP/JS)
(function() {
    var el = document.getElementById('inscription-page');
    var cfg = el && el.getAttribute('data-config');
    if (cfg) {
        try {
            var c = JSON.parse(cfg);
            window.concoursId = c.concoursId;
            window.formAction = c.formAction;
            window.apiInscriptionsUrl = c.apiInscriptionsUrl;
            window.inscriptionCible = !!c.inscriptionCible;
            window.archerSearchUrl = c.archerSearchUrl;
            window.categoriesClassement = c.categoriesClassement || [];
            window.arcs = c.arcs || [];
            window.distancesTir = c.distancesTir || [];
            window.concoursDiscipline = c.concoursDiscipline;
            window.concoursTypeCompetition = c.concoursTypeCompetition;
            window.concoursNombreDepart = c.concoursNombreDepart;
            window.disciplineAbv = c.disciplineAbv;
            window.isNature3DOrCampagne = !!c.isNature3DOrCampagne;
            window.isDirigeant = !!c.isDirigeant;
            window.currentUserLicence = (c.currentUserLicence || '').toString().trim();
            window.currentUserId = c.currentUserId != null ? String(c.currentUserId) : '';
        } catch (e) { console.warn('Config inscription parse error', e); }
    }
})();

// Variables globales
var selectedArcher = null;
var allInscriptionsCache = []; // Liste complète pour filtrage par départs cochés
var concoursIdValue = (typeof concoursId !== 'undefined' && concoursId) ? concoursId :
    (document.querySelector('input[name="concours_id"]')?.value ||
    window.location.pathname.match(/\/concours\/(\d+)/)?.[1]);

// Ajouter event listener au bouton de recherche
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('archer-search-btn');
    const licenceInput = document.getElementById('licence-search-input');
    const confirmBtn = document.getElementById('btn-confirm-inscription');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', searchArcherByLicense);
    }
    
    if (licenceInput) {
        licenceInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        licenceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchArcherByLicense();
            }
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmArcherSelection);
    }
    
    // Ajouter les listeners pour mettre à jour le blason automatiquement
    setupBlasonAutoUpdate();
    
    // Ajouter les listeners pour les avertissements de type de licence
    setupLicenceTypeWarnings();
    
    // Mettre à jour l'affichage des départs dans la modale + "Tout sélectionner"
    window.updateModalDepartDisplay = function() {
        const modalDepartDisplay = document.getElementById('modal-depart-display');
        const departCheckboxes = document.querySelectorAll('.depart-checkbox');
        if (!modalDepartDisplay) return;
        const checked = Array.from(departCheckboxes).filter(cb => cb.checked);
        modalDepartDisplay.textContent = checked.length
            ? checked.map(cb => cb.nextElementSibling?.textContent || cb.value).join(', ')
            : 'Sélectionné(s) en haut de la page';
    };
    const departSelectAll = document.getElementById('depart-select-all');
    const departCheckboxes = document.querySelectorAll('.depart-checkbox');
    departCheckboxes.forEach(cb => cb.addEventListener('change', function() {
        window.updateModalDepartDisplay();
        applyDepartFilterAndRender();
    }));
    if (departSelectAll) {
        departSelectAll.addEventListener('change', function() {
            document.querySelectorAll('.depart-checkbox').forEach(cb => { cb.checked = this.checked; });
            window.updateModalDepartDisplay();
            applyDepartFilterAndRender();
        });
    }

    // Charger la liste des inscriptions depuis le backend
    loadInscriptions();
    
    // Initialiser les handlers pour l'édition
    initEditInscriptionHandlers();

    // Délégation d'événement pour le changement de statut (dropdown)
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.statut-dropdown-item');
        if (item) {
            e.preventDefault();
            const statut = item.getAttribute('data-statut');
            // data-inscription-id sur l'item (robuste si le menu Bootstrap est déplacé hors du tr)
            let inscriptionId = item.getAttribute('data-inscription-id');
            if (!inscriptionId) {
                const row = item.closest('tr');
                inscriptionId = row ? row.getAttribute('data-inscription-id') : null;
            }
            if (inscriptionId && statut && concoursIdValue) {
                updateStatutInscription(parseInt(inscriptionId, 10), statut);
            }
        }
    });
});

// Variable pour stocker la fonction de mise à jour du blason (pour pouvoir la retirer si nécessaire)
let blasonUpdateHandler = null;

/**
 * Configure les listeners pour afficher les avertissements selon le type de licence
 */
function setupLicenceTypeWarnings() {
    const typeLicenceSelect = document.getElementById('type_licence');
    const editTypeLicenceSelect = document.getElementById('edit-type_licence');
    
    // Pour la modale de confirmation
    if (typeLicenceSelect) {
        typeLicenceSelect.addEventListener('change', function() {
            updateLicenceWarning(this.value, 'type_licence_warning');
        });
    }
    
    // Pour la modale d'édition
    if (editTypeLicenceSelect) {
        editTypeLicenceSelect.addEventListener('change', function() {
            updateLicenceWarning(this.value, 'edit-type_licence_warning');
        });
    }
}

/**
 * Met à jour l'affichage du message d'avertissement selon le type de licence
 */
function updateLicenceWarning(licenceType, warningElementId) {
    const warningEl = document.getElementById(warningElementId);
    if (!warningEl) return;
    
    // Trouver le select correspondant
    const selectId = warningElementId.replace('_warning', '');
    const selectEl = document.getElementById(selectId);
    
    // Licences non valides en compétition (L, E, D) - Rouge
    if (['L', 'E', 'D'].includes(licenceType)) {
        warningEl.textContent = 'Attention non valide en compétition';
        warningEl.className = 'licence-warning-message invalid';
        warningEl.classList.remove('d-none');
        // Appliquer le style rouge au select
        if (selectEl) {
            selectEl.classList.add('licence-invalid');
            selectEl.classList.remove('licence-warning');
        }
    }
    // Licence sous conditions (P) - Orange
    else if (licenceType === 'P') {
        warningEl.textContent = 'Sous conditions et limitations';
        warningEl.className = 'licence-warning-message conditional';
        warningEl.classList.remove('d-none');
        // Appliquer le style orange au select
        if (selectEl) {
            selectEl.classList.add('licence-warning');
            selectEl.classList.remove('licence-invalid');
        }
    }
    // Autres licences - Masquer le message et retirer les styles
    else {
        warningEl.classList.add('d-none');
        if (selectEl) {
            selectEl.classList.remove('licence-invalid', 'licence-warning');
        }
    }
}

/**
 * Applique la colorisation rouge à la catégorie si elle n'est pas exacte
 */
function applyCategorieColorization() {
    const categorieSelect = document.getElementById('categorie_classement');
    if (!categorieSelect || !categorieSelect.value) {
        return;
    }
    
    // Vérifier si la catégorie sélectionnée correspond exactement à CATEGORIE du XML
    if (selectedArcher && selectedArcher.CATEGORIE) {
        const categorieXml = (selectedArcher.CATEGORIE || '').trim().toUpperCase();
        const selectedAbv = categorieSelect.value.trim().toUpperCase();
        const sexeXml = (selectedArcher.SEXE || '').trim();
        const sexeLetter = sexeXml === '1' ? 'H' : (sexeXml === '2' ? 'F' : '');
        
        let isExactMatch = false;
        
        // Correspondance exacte directe
        if (selectedAbv === categorieXml) {
            isExactMatch = true;
        } else if (sexeLetter) {
            // Vérifier avec le parsing : construire la catégorie attendue depuis le XML
            const parsed = parseCategorieXml(categorieXml, sexeLetter);
            if (parsed) {
                const categorieAttendue = parsed.ageCategory + parsed.sexe + parsed.arme;
                if (selectedAbv === categorieAttendue) {
                    isExactMatch = true;
                }
            }
        }
        
        // Appliquer ou retirer la classe rouge
        if (!isExactMatch) {
            categorieSelect.classList.add('categorie-auto-selected');
        } else {
            categorieSelect.classList.remove('categorie-auto-selected');
        }
        
        // Retirer la classe si l'utilisateur change manuellement la sélection
        const removeRedClass = function() {
            categorieSelect.classList.remove('categorie-auto-selected');
            categorieSelect.removeEventListener('change', removeRedClass);
        };
        categorieSelect.addEventListener('change', removeRedClass);
    }
}

/**
 * Configure les listeners pour mettre à jour automatiquement le blason
 * quand la catégorie ou la distance change
 */
function setupBlasonAutoUpdate() {
    const categorieSelect = document.getElementById('categorie_classement');
    const distanceSelect = document.getElementById('distance');
    const blasonInput = document.getElementById('blason');
    
    if (!categorieSelect || !distanceSelect || !blasonInput) {
        console.log('Champs manquants pour le blason auto-update:', {
            categorieSelect: !!categorieSelect,
            distanceSelect: !!distanceSelect,
            blasonInput: !!blasonInput
        });
        return;
    }
    
    // Retirer l'ancien listener s'il existe
    if (blasonUpdateHandler) {
        categorieSelect.removeEventListener('change', blasonUpdateHandler);
        distanceSelect.removeEventListener('change', blasonUpdateHandler);
    }
    
    // Fonction pour mettre à jour le blason
    blasonUpdateHandler = function updateBlason() {
        // Récupérer les valeurs actuelles des champs (pas les références initiales)
        const currentCategorieSelect = document.getElementById('categorie_classement');
        const currentDistanceSelect = document.getElementById('distance');
        const currentBlasonInput = document.getElementById('blason');
        
        if (!currentCategorieSelect || !currentDistanceSelect || !currentBlasonInput) {
            console.error('✗ Champs introuvables dans updateBlason:', {
                categorieSelect: !!currentCategorieSelect,
                distanceSelect: !!currentDistanceSelect,
                blasonInput: !!currentBlasonInput
            });
            return;
        }
        
        const categorie = currentCategorieSelect.value;
        const distance = currentDistanceSelect.value;
        
        console.log('=== updateBlason appelé ===');
        console.log('Catégorie:', categorie, 'Distance:', distance);
        
        if (!categorie || !distance) {
            console.log('Catégorie ou distance manquante, pas de mise à jour du blason');
            if (currentBlasonInput) {
                currentBlasonInput.value = '';
            }
            return;
        }
        
        if (typeof concoursDiscipline === 'undefined' || concoursDiscipline === null) {
            console.log('concoursDiscipline non défini, pas de mise à jour du blason');
            return;
        }
        
        // Vérifier si c'est une discipline 3D, Nature ou Campagne
        const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
        if (isNature) {
            console.log('Discipline 3D/Nature/Campagne - pas de blason');
            return; // Pas de blason pour ces disciplines
        }
        
        console.log('Mise à jour automatique du blason pour catégorie:', categorie, 'distance:', distance, 'discipline:', concoursDiscipline);
        
        // Récupérer l'ID de la catégorie (IDCategorie) depuis categoriesClassement
        let idCategorie = null;
        if (typeof categoriesClassement !== 'undefined' && categoriesClassement) {
            const categorieObj = categoriesClassement.find(cat => {
                const abv = (cat.abv_categorie_classement || '').trim();
                return abv === categorie;
            });
            if (categorieObj) {
                idCategorie = categorieObj.idcategorie || categorieObj.id_categorie || categorieObj.id || null;
                console.log('IDCategorie trouvé:', idCategorie, 'pour abv:', categorie);
            }
        }
        
        if (!idCategorie) {
            console.error('✗ IDCategorie non trouvé pour la catégorie:', categorie);
            if (currentBlasonInput) {
                currentBlasonInput.value = '';
            }
            return;
        }
        
        // Appeler l'API pour récupérer le blason avec IDCategorie
        getBlasonFromAPI(concoursDiscipline, idCategorie, distance)
            .then(blason => {
                if (blason && currentBlasonInput) {
                    currentBlasonInput.value = blason;
                    console.log('✓✓✓ Blason mis à jour automatiquement:', blason, 'cm');
                } else {
                    console.log('✗ Aucun blason trouvé pour cette combinaison');
                    if (currentBlasonInput) {
                        currentBlasonInput.value = '';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération du blason:', error);
                if (currentBlasonInput) {
                    currentBlasonInput.value = '';
                }
            });
    };
    
    // Ajouter les listeners
    categorieSelect.addEventListener('change', blasonUpdateHandler);
    distanceSelect.addEventListener('change', blasonUpdateHandler);
    
    console.log('✓ Listeners pour mise à jour automatique du blason configurés');
    console.log('Éléments:', {
        categorieSelect: categorieSelect.id,
        distanceSelect: distanceSelect.id,
        blasonInput: blasonInput.id
    });
}

/**
 * Cherche un archer par son numéro de licence
 */
function searchArcherByLicense() {
    const licenceInput = document.getElementById('licence-search-input');
    let licence = licenceInput?.value?.trim() || '';
    if (licence.length === 7) {
        licence = '0' + licence;
    }
    if (!licence) {
        alert('Veuillez entrer un numéro de licence');
        return;
    }
    
    // Désactiver le bouton pendant la recherche
    const searchBtn = document.getElementById('archer-search-btn');
    if (searchBtn) searchBtn.disabled = true;
    
    const searchUrl = typeof archerSearchUrl !== 'undefined' ? archerSearchUrl : '/archer/search-or-create';
    fetch(searchUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({
            licence_number: licence
        })
    })
    .then(response => {
        console.log('Réponse reçue:', response.status, response.statusText);
        
        // Vérifier si la réponse est JSON
        const contentType = response.headers.get('content-type');
        const isJson = contentType && contentType.includes('application/json');
        
        // Toujours essayer de parser en JSON si le content-type indique JSON
        // Même si le code HTTP est une erreur (404, 400, etc.)
        if (isJson) {
            return response.json().then(data => {
                // Si le serveur retourne success: false, c'est une erreur métier, pas une erreur HTTP
                if (!data.success) {
                    console.error('Erreur API:', data);
                    throw new Error(data.error || 'Erreur lors de la recherche');
                }
                // Si success: true, retourner les données même si le code HTTP est une erreur
                return data;
            });
        }
        
        // Si ce n'est pas JSON, gérer comme une erreur
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Réponse non-JSON reçue:', text.substring(0, 500));
                throw new Error(`Erreur ${response.status}: ${response.statusText}`);
            });
        }
        
        // Si OK et pas JSON, essayer quand même de parser
        return response.json().catch(() => {
            throw new Error('Le serveur a retourné une réponse non-JSON');
        });
    })
    .then(data => {
        console.log('Données reçues:', data);
        
        if (!data.success) {
            alert('Archer non trouvé: ' + (data.error || 'Erreur inconnue'));
            return;
        }
        
        // Stocker les données de l'archer
        selectedArcher = data.data;
        if (!selectedArcher) {
            console.error('Données archer manquantes:', data);
            alert('Erreur: données archer manquantes dans la réponse');
            return;
        }
        
        selectedArcher.licence_number = licence; // S'assurer que la licence est bien stockée
        
        // Afficher le modal avec les infos
        showSearchResult();
    })
    .catch(error => {
        console.error('Erreur lors de la recherche:', error);
        alert('Erreur lors de la recherche: ' + error.message);
    })
    .finally(() => {
        if (searchBtn) searchBtn.disabled = false;
    });
}

/**
 * Affiche les résultats de la recherche dans le modal
 */
function showSearchResult() {
    if (!selectedArcher) {
        console.error('showSearchResult: selectedArcher est null');
        return;
    }
    
    console.log('=== showSearchResult appelé ===');
    console.log('Données archer complètes:', JSON.stringify(selectedArcher, null, 2));
    
    const modalElement = document.getElementById('confirmInscriptionModal');
    if (!modalElement) {
        console.error('Modal confirmInscriptionModal introuvable');
        return;
    }
    
    // Remplir les infos dans le modal (affichage)
    const nomEl = document.getElementById('modal-archer-nom');
    const prenomEl = document.getElementById('modal-archer-prenom');
    const licenceEl = document.getElementById('modal-archer-licence');
    const clubEl = document.getElementById('modal-archer-club');
    
    if (nomEl) nomEl.textContent = selectedArcher.name || 'N/A';
    if (prenomEl) prenomEl.textContent = selectedArcher.first_name || 'N/A';
    if (licenceEl) licenceEl.textContent = selectedArcher.licence_number || 'N/A';
    if (clubEl) clubEl.textContent = selectedArcher.club || 'N/A';
    
    // Pré-remplir les champs du formulaire AVANT d'afficher le modal
    prefillFormFields(selectedArcher);
    
    // Afficher le modal de confirmation
    const modal = new bootstrap.Modal(modalElement);
    
    // Écouter l'événement 'shown' pour s'assurer que le modal est complètement affiché
    modalElement.addEventListener('shown.bs.modal', function onModalShown() {
        // Réessayer le pré-remplissage au cas où certains champs ne seraient pas encore disponibles
        prefillFormFields(selectedArcher);
        // Vérifier et appliquer la colorisation rouge si nécessaire
        applyCategorieColorization();
        // Configurer les listeners pour le blason maintenant que le modal est ouvert
        setupBlasonAutoUpdate();
        // Configurer les listeners pour les avertissements de licence
        setupLicenceTypeWarnings();
        // Charger les produits buvette
        if (typeof loadBuvetteProduits === 'function') loadBuvetteProduits();
        
        // Ajouter un listener pour retirer la classe is-invalid lorsque l'utilisateur saisit dans le champ email
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }
        
        modalElement.removeEventListener('shown.bs.modal', onModalShown);
    }, { once: true });
    
    modal.show();
}

/**
 * Parse la catégorie XML selon la structure :
 * - 2 premiers caractères = arme (CL, AD, BB, CO, AC, TL)
 * - Si commence par S après l'arme = S1, S2, S3 (2 caractères)
 * - Sinon = U11, U13, U15, U18, U21 (3 caractères)
 * - Le genre H/F vient de SEXE (1=H, 2=F)
 * @param {string} categorieXml - La catégorie du XML (ex: "CLS3H")
 * @param {string} sexeLetter - Le sexe sous forme de lettre (H ou F)
 * @returns {Object|null} - {arme: string, ageCategory: string, sexe: string} ou null
 */
function parseCategorieXml(categorieXml, sexeLetter) {
    if (!categorieXml || categorieXml.length < 3) {
        return null;
    }
    
    const categorieUpper = categorieXml.trim().toUpperCase();
    
    // Extraire l'arme (2 premiers caractères)
    const arme = categorieUpper.substring(0, 2);
    
    // Extraire la catégorie d'âge
    let ageCategory = '';
    const reste = categorieUpper.substring(2);
    
    // Si le reste commence par S, prendre 2 caractères (S1, S2, S3)
    if (reste.startsWith('S')) {
        ageCategory = reste.substring(0, 2); // S1, S2, S3
    } else {
        // Sinon prendre 3 caractères (U11, U13, U15, U18, U21)
        ageCategory = reste.substring(0, 3); // U11, U13, U15, U18, U21
    }
    
    return {
        arme: arme,
        ageCategory: ageCategory,
        sexe: sexeLetter || ''
    };
}

/**
 * Pré-remplit les champs du formulaire avec les données de l'archer
 */
function prefillFormFields(archer) {
    if (!archer) {
        console.error('prefillFormFields: archer est null');
        return;
    }
    
    console.log('=== prefillFormFields appelé ===');
    console.log('Données disponibles:', {
        saison: archer.saison,
        type_licence: archer.type_licence,
        creation_renouvellement: archer.creation_renouvellement,
        certificat_medical: archer.certificat_medical,
        CATEGORIE: archer.CATEGORIE,
        CATAGE: archer.CATAGE,
        TYPARC: archer.TYPARC,
        SEXE: archer.SEXE
    });
    
    // Pré-remplir la saison
    const saisonInput = document.getElementById('saison');
    if (saisonInput) {
        if (archer.saison) {
            saisonInput.value = archer.saison;
            console.log('✓ Saison pré-remplie:', saisonInput.value);
        } else {
            console.log('✗ Saison non disponible dans les données');
        }
    } else {
        console.error('✗ Champ saison introuvable');
    }
    
    // Pré-remplir le type de licence
    const typeLicenceSelect = document.getElementById('type_licence');
    if (typeLicenceSelect) {
        if (archer.type_licence) {
            const cleanedTypeLicence = archer.type_licence.trim().toUpperCase();
            const firstLetter = cleanedTypeLicence.length > 0 ? cleanedTypeLicence[0] : '';
            console.log('Tentative pré-remplissage type_licence:', cleanedTypeLicence, '-> première lettre:', firstLetter);
            // Chercher l'option correspondante
            let found = false;
            for (let i = 0; i < typeLicenceSelect.options.length; i++) {
                if (typeLicenceSelect.options[i].value === firstLetter) {
                    // Activer temporairement pour définir la valeur
                    typeLicenceSelect.disabled = false;
                    typeLicenceSelect.value = firstLetter;
                    typeLicenceSelect.disabled = true; // Re-désactiver
                    console.log('✓ Type licence pré-rempli:', firstLetter);
                    // Afficher l'avertissement si nécessaire
                    updateLicenceWarning(firstLetter, 'type_licence_warning');
                    found = true;
                    break;
                }
            }
            if (!found) {
                console.log('✗ Aucune option trouvée pour type_licence:', firstLetter);
            }
        } else {
            console.log('✗ type_licence non disponible dans les données');
        }
    } else {
        console.error('✗ Champ type_licence introuvable');
    }
    
    // Pré-remplir création/renouvellement
    const creationRenouvellementInput = document.getElementById('creation_renouvellement');
    if (creationRenouvellementInput) {
        if (archer.creation_renouvellement) {
            creationRenouvellementInput.value = archer.creation_renouvellement;
            console.log('✓ Création/renouvellement pré-rempli:', archer.creation_renouvellement);
        } else {
            console.log('✗ creation_renouvellement non disponible dans les données');
        }
    } else {
        console.error('✗ Champ creation_renouvellement introuvable');
    }
    
    // Pré-remplir le type de certificat médical
    const certificatSelect = document.getElementById('type_certificat_medical');
    if (certificatSelect) {
        if (archer.certificat_medical) {
            const certValue = archer.certificat_medical.trim();
            console.log('Tentative pré-remplissage certificat_medical:', certValue);
            
            // Le champ XML contient directement "Compétition" ou "Pratique"
            // Chercher l'option correspondante
            let found = false;
            for (let i = 0; i < certificatSelect.options.length; i++) {
                const optionValue = certificatSelect.options[i].value;
                const optionText = certificatSelect.options[i].textContent.trim();
                
                // Comparaison insensible à la casse et aux accents
                const certValueLower = certValue.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const optionTextLower = optionText.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                
                if (optionValue === certValue || optionText === certValue ||
                    optionTextLower.includes(certValueLower) || certValueLower.includes(optionTextLower)) {
                    // Activer temporairement pour définir la valeur
                    certificatSelect.disabled = false;
                    certificatSelect.value = optionValue;
                    certificatSelect.disabled = true; // Re-désactiver
                    console.log('✓ Certificat médical pré-rempli:', certificatSelect.value);
                    found = true;
                    break;
                }
            }
            if (!found) {
                console.log('✗ Aucune option trouvée pour certificat_medical:', certValue);
                console.log('Options disponibles:', Array.from(certificatSelect.options).map(o => ({ value: o.value, text: o.textContent })));
            }
        } else {
            console.log('✗ certificat_medical non disponible dans les données');
        }
    } else {
        console.error('✗ Champ type_certificat_medical introuvable');
    }
    
    // Pré-remplir la catégorie de classement
    // Utiliser CATAGE (idcategorie), TYPARC (idarc) et SEXE (1=H, 2=F) pour trouver la catégorie
    const categorieSelect = document.getElementById('categorie_classement');
    // Déclarer transformed et categorieXml dans un scope accessible pour la vérification de correspondance exacte
    let transformed = null;
    let categorieXml = '';
    if (categorieSelect) {
        if (typeof categoriesClassement !== 'undefined' && categoriesClassement && categoriesClassement.length > 0) {
            const catage = archer.CATAGE ? String(archer.CATAGE).trim() : '';
            const typarc = archer.TYPARC ? String(archer.TYPARC).trim() : '';
            const sexeXml = (archer.SEXE || '').trim();
            const sexeLetter = sexeXml === '1' ? 'H' : (sexeXml === '2' ? 'F' : '');
            
            console.log('Tentative pré-remplissage catégorie. CATAGE:', catage, 'TYPARC:', typarc, 'SEXE:', sexeXml, '->', sexeLetter);
            
            if (catage && typarc) {
                // Récupérer CATEGORIE du XML pour prioriser la bonne catégorie
                categorieXml = (archer.CATEGORIE || '').trim().toUpperCase();
                
                // Chercher toutes les catégories correspondantes avec idcategorie = CATAGE, idarc = TYPARC et sexe = H/F
                let matchingCategories = categoriesClassement.filter(cat => {
                    const catIdcategorie = String(cat.idcategorie || cat.id_categorie || '').trim();
                    const catIdarc = String(cat.idarc || cat.id_arc || '').trim();
                    const catSexe = (cat.sexe || cat.SEXE || '').trim().toUpperCase();
                    const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                    
                    // Vérifier idcategorie et idarc
                    const matchIds = catIdcategorie === catage && catIdarc === typarc;
                    
                    if (!matchIds) {
                        return false;
                    }
                    
                    // Si on a un sexe, vérifier que le champ sexe de la catégorie correspond
                    if (sexeLetter) {
                        // Le champ sexe de la catégorie doit être H ou F selon le sexe du XML
                        // Si le champ sexe n'existe pas, vérifier que l'abréviation commence par H ou F
                        if (catSexe) {
                            if (catSexe !== sexeLetter) return false;
                        } else {
                            // Fallback: vérifier que l'abréviation commence par H ou F
                            if (!catAbv.startsWith(sexeLetter)) return false;
                        }
                    }
                    
                    return true;
                });
                
                // Prioriser la catégorie qui correspond à CATEGORIE du XML
                let categorieFound = null;
                
                // Parser la catégorie XML selon la structure définie
                if (categorieXml && sexeLetter) {
                    const parsed = parseCategorieXml(categorieXml, sexeLetter);
                    if (parsed) {
                        console.log('Catégorie XML parsée:', parsed, 'depuis:', categorieXml);
                        
                        // Construire la catégorie recherchée : catégorie d'âge + sexe + arme
                        // Format dans la DB: S3HCL, S3HTL, U18HAD, etc.
                        const categorieRecherchee = parsed.ageCategory + parsed.sexe + parsed.arme;
                        console.log('Catégorie recherchée construite:', categorieRecherchee);
                        
                        // Chercher d'abord une correspondance exacte dans toutes les catégories
                        categorieFound = categoriesClassement.find(cat => {
                            const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                            return catAbv === categorieRecherchee;
                        });
                        
                        if (categorieFound) {
                            console.log('  ✓ Correspondance exacte trouvée:', categorieFound.abv_categorie_classement);
                        } else {
                            console.log('  ✗ Correspondance exacte non trouvée pour:', categorieRecherchee);
                            
                            // Chercher dans toutes les catégories avec la catégorie d'âge et le sexe
                            // (même si matchingCategories est vide, car certaines catégories comme CL ne passent pas le filtre initial)
                            const categoriesWithAgeAndSexe = categoriesClassement.filter(cat => {
                                const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                                const catSexe = (cat.sexe || cat.SEXE || '').trim().toUpperCase();
                                
                                // Vérifier que la catégorie commence par la catégorie d'âge + sexe
                                const hasAgeCategoryAndSexe = catAbv.startsWith(parsed.ageCategory + parsed.sexe);
                                // Ou vérifier le sexe dans le champ sexe et que la catégorie contient la catégorie d'âge
                                const hasSexe = catSexe === parsed.sexe && catAbv.includes(parsed.ageCategory);
                                
                                return hasAgeCategoryAndSexe || hasSexe;
                            });
                            
                            if (categoriesWithAgeAndSexe.length > 0) {
                                console.log('Catégories trouvées avec catégorie d\'âge + sexe:', categoriesWithAgeAndSexe.length, categoriesWithAgeAndSexe.map(c => c.abv_categorie_classement));
                                
                                // Prioriser celles qui correspondent aussi à CATAGE et TYPARC
                                const foundWithIds = categoriesWithAgeAndSexe.find(cat => {
                                    const catIdcategorie = String(cat.idcategorie || cat.id_categorie || '').trim();
                                    const catIdarc = String(cat.idarc || cat.id_arc || '').trim();
                                    return catIdcategorie === catage && catIdarc === typarc;
                                });
                                
                                if (foundWithIds) {
                                    categorieFound = foundWithIds;
                                    console.log('  ⚠ Catégorie trouvée avec catégorie d\'âge + sexe + CATAGE/TYPARC:', categorieFound.abv_categorie_classement);
                                } else {
                                    // Prendre la première catégorie avec la catégorie d'âge et le sexe
                                    categorieFound = categoriesWithAgeAndSexe[0];
                                    console.log('  ⚠ Première catégorie avec catégorie d\'âge + sexe sélectionnée:', categorieFound.abv_categorie_classement);
                                }
                            } else {
                                console.log('  ✗ Aucune catégorie trouvée avec catégorie d\'âge:', parsed.ageCategory, 'et sexe:', parsed.sexe);
                            }
                        }
                    }
                }
                
                // Si toujours pas trouvé et qu'on a des catégories correspondantes, utiliser la première
                if (!categorieFound && matchingCategories.length > 0) {
                    console.log('Aucune correspondance trouvée, utilisation de la première catégorie correspondante');
                    categorieFound = matchingCategories[0];
                }
                
                // Si toujours pas trouvé, essayer les transformations AD et BB (pour compatibilité)
                if (!categorieFound && categorieXml && sexeLetter && matchingCategories.length > 0) {
                    const parsed = parseCategorieXml(categorieXml, sexeLetter);
                    if (parsed) {
                        // Pour AD et BB, construire la catégorie recherchée
                        if (parsed.arme === 'AD') {
                            const categorieRecherchee1 = parsed.ageCategory + parsed.sexe + 'AD';
                            const categorieRecherchee2 = parsed.ageCategory + 'AD' + parsed.sexe;
                            
                            categorieFound = matchingCategories.find(cat => {
                                const abv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                                return abv === categorieRecherchee1 || abv === categorieRecherchee2;
                            });
                            
                            if (categorieFound) {
                                transformed = categorieFound.abv_categorie_classement;
                                console.log('  ✓ Catégorie AD trouvée:', categorieFound.abv_categorie_classement);
                            }
                        } else if (parsed.arme === 'BB') {
                            const categorieRecherchee = parsed.ageCategory + parsed.sexe + 'BB';
                            
                            categorieFound = matchingCategories.find(cat => {
                                const abv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                                return abv === categorieRecherchee;
                            });
                            
                            if (categorieFound) {
                                transformed = categorieFound.abv_categorie_classement;
                                console.log('  ✓ Catégorie BB trouvée:', categorieFound.abv_categorie_classement);
                            }
                        }
                    }
                }
                
                if (transformed && !categorieFound) {
                            
                }
                
                if (categorieFound) {
                    categorieSelect.value = categorieFound.abv_categorie_classement || '';
                    
                    // Vérifier si c'est une correspondance exacte avec CATEGORIE XML
                    let isExactMatch = false;
                    if (categorieXml && categorieFound.abv_categorie_classement && sexeLetter) {
                        const foundAbv = categorieFound.abv_categorie_classement.trim().toUpperCase();
                        const xmlAbv = categorieXml.trim().toUpperCase();
                        
                        // Correspondance exacte directe
                        if (foundAbv === xmlAbv) {
                            isExactMatch = true;
                        } else {
                            // Vérifier avec le parsing : construire la catégorie attendue depuis le XML
                            const parsed = parseCategorieXml(categorieXml, sexeLetter);
                            if (parsed) {
                                const categorieAttendue = parsed.ageCategory + parsed.sexe + parsed.arme;
                                if (foundAbv === categorieAttendue) {
                                    isExactMatch = true;
                                }
                            }
                        }
                    }
                    
                    // Si la catégorie exacte n'a pas été trouvée, colorer en rouge
                    if (!isExactMatch && categorieXml) {
                        categorieSelect.classList.add('categorie-auto-selected');
                        console.log('  ⚠ Catégorie sélectionnée automatiquement (non exacte) - colorée en rouge');
                    } else {
                        categorieSelect.classList.remove('categorie-auto-selected');
                    }
                    
                    // Retirer la classe si l'utilisateur change manuellement la sélection
                    const removeRedClass = function() {
                        categorieSelect.classList.remove('categorie-auto-selected');
                        categorieSelect.removeEventListener('change', removeRedClass);
                    };
                    categorieSelect.addEventListener('change', removeRedClass);
                    
                    console.log('✓ Catégorie pré-remplie via CATAGE/TYPARC/SEXE:', categorieSelect.value);
                    console.log('  CATEGORIE XML:', categorieXml || 'non disponible');
                    console.log('  Catégories correspondantes trouvées:', matchingCategories.length);
                    if (matchingCategories.length > 1) {
                        console.log('  Liste des catégories correspondantes:', matchingCategories.map(c => c.abv_categorie_classement));
                    }
                    
                    // Appliquer la colorisation après un court délai pour s'assurer que le DOM est mis à jour
                    setTimeout(() => {
                        applyCategorieColorization();
                    }, 100);
                    
                    // Déclencher le remplissage automatique de la distance et du blason
                    setTimeout(() => {
                        fillDistanceAndBlasonFromCategorie(categorieFound.abv_categorie_classement);
                    }, 300);
                } else {
                    console.log('✗ Catégorie non trouvée avec CATAGE:', catage, 'TYPARC:', typarc, 'SEXE:', sexeLetter);
                    console.log('Catégories disponibles:', categoriesClassement.map(c => ({
                        abv: c.abv_categorie_classement,
                        idcategorie: c.idcategorie || c.id_categorie,
                        idarc: c.idarc || c.id_arc,
                        sexe: c.sexe || c.SEXE || 'N/A'
                    })));
                    
                    // Afficher les catégories qui correspondent à CATAGE et TYPARC (sans le sexe)
                    const matchingCategoriesDebug = categoriesClassement.filter(cat => {
                        const catIdcategorie = String(cat.idcategorie || cat.id_categorie || '').trim();
                        const catIdarc = String(cat.idarc || cat.id_arc || '').trim();
                        return catIdcategorie === catage && catIdarc === typarc;
                    });
                    if (matchingCategoriesDebug.length > 0) {
                        console.log('Catégories correspondant à CATAGE et TYPARC (sans sexe):', matchingCategoriesDebug.map(c => ({
                            abv: c.abv_categorie_classement,
                            sexe: c.sexe || c.SEXE || 'N/A'
                        })));
                        
                        // Si aucune catégorie CL n'a été trouvée mais qu'il y a des catégories correspondant à CATAGE/TYPARC,
                        // sélectionner la première qui correspond au sexe
                        if (!categorieFound && categorieXml && categorieXml.startsWith('CL')) {
                            const foundBySexe = matchingCategoriesDebug.find(cat => {
                                if (sexeLetter) {
                                    const catSexe = (cat.sexe || cat.SEXE || '').trim().toUpperCase();
                                    const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                                    if (catSexe) {
                                        return catSexe === sexeLetter;
                                    } else {
                                        return catAbv.startsWith(sexeLetter);
                                    }
                                }
                                return true;
                            });
                            
                            if (foundBySexe) {
                                categorieFound = foundBySexe;
                                console.log('  ⚠ Catégorie sélectionnée (CL non trouvée, utilisation de', foundBySexe.abv_categorie_classement, 'avec CATAGE:', catage, 'TYPARC:', typarc, 'SEXE:', sexeLetter, ')');
                            } else if (matchingCategoriesDebug.length > 0) {
                                // Prendre la première catégorie même si le sexe ne correspond pas exactement
                                categorieFound = matchingCategoriesDebug[0];
                                console.log('  ⚠ Catégorie sélectionnée (CL non trouvée, utilisation de la première avec CATAGE:', catage, 'TYPARC:', typarc, ')');
                            }
                        }
                    }
                    
                    // Si une catégorie a été trouvée dans le bloc else, la sélectionner
                    if (categorieFound && categorieSelect) {
                        categorieSelect.value = categorieFound.abv_categorie_classement || '';
                        
                        // Vérifier si c'est une correspondance exacte avec CATEGORIE XML
                        let isExactMatch = false;
                        if (categorieXml && categorieFound.abv_categorie_classement) {
                            const foundAbv = categorieFound.abv_categorie_classement.trim().toUpperCase();
                            const xmlAbv = categorieXml.trim().toUpperCase();
                            if (foundAbv === xmlAbv) {
                                isExactMatch = true;
                            }
                        }
                        
                        // Si la catégorie exacte n'a pas été trouvée, colorer en rouge
                        if (!isExactMatch && categorieXml) {
                            categorieSelect.classList.add('categorie-auto-selected');
                            console.log('  ⚠ Catégorie sélectionnée automatiquement (non exacte) - colorée en rouge');
                        } else {
                            categorieSelect.classList.remove('categorie-auto-selected');
                        }
                        
                        // Retirer la classe si l'utilisateur change manuellement la sélection
                        const removeRedClass = function() {
                            categorieSelect.classList.remove('categorie-auto-selected');
                            categorieSelect.removeEventListener('change', removeRedClass);
                        };
                        categorieSelect.addEventListener('change', removeRedClass);
                        
                        console.log('✓ Catégorie pré-remplie:', categorieSelect.value);
                        
                        // Appliquer la colorisation après un court délai
                        setTimeout(() => {
                            applyCategorieColorization();
                        }, 100);
                        
                        // Déclencher le remplissage automatique de la distance et du blason
                        setTimeout(() => {
                            fillDistanceAndBlasonFromCategorie(categorieFound.abv_categorie_classement);
                        }, 300);
                    }
                    
                    // Fallback: essayer avec CATEGORIE si disponible (si aucune catégorie n'a été trouvée)
                    if (!categorieFound) {
                        const categorieXmlFallback = (archer.CATEGORIE || '').trim().toUpperCase();
                        if (categorieXmlFallback && sexeLetter) {
                            console.log('Fallback: Recherche avec CATEGORIE XML:', categorieXmlFallback, 'SEXE:', sexeXml, '->', sexeLetter);
                            
                            // Parser la catégorie XML
                            const parsedFallback = parseCategorieXml(categorieXmlFallback, sexeLetter);
                            if (parsedFallback) {
                                console.log('Fallback: Catégorie XML parsée:', parsedFallback);
                                
                                // Construire la catégorie recherchée
                                const categorieRechercheeFallback = parsedFallback.ageCategory + parsedFallback.sexe + parsedFallback.arme;
                                
                                // Chercher dans toutes les catégories avec la catégorie d'âge et le sexe
                                const categoriesWithAgeAndSexeFallback = categoriesClassement.filter(cat => {
                                    const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                                    return catAbv.startsWith(parsedFallback.ageCategory + parsedFallback.sexe);
                                });
                                
                                if (categoriesWithAgeAndSexeFallback.length > 0) {
                                    categorieFound = categoriesWithAgeAndSexeFallback[0];
                                    console.log('  ⚠ Catégorie sélectionnée via fallback (catégorie d\'âge + sexe):', categorieFound.abv_categorie_classement);
                                    
                                    categorieSelect.value = categorieFound.abv_categorie_classement || '';
                                    
                                    // Vérifier si c'est une correspondance exacte
                                    const foundAbvFallback = categorieFound.abv_categorie_classement.trim().toUpperCase();
                                    const isExactMatchFallback = foundAbvFallback === categorieRechercheeFallback;
                                    
                                    // Si la catégorie exacte n'a pas été trouvée, colorer en rouge
                                    if (!isExactMatchFallback) {
                                        categorieSelect.classList.add('categorie-auto-selected');
                                        console.log('  ⚠ Catégorie sélectionnée via fallback (non exacte) - colorée en rouge');
                                    } else {
                                        categorieSelect.classList.remove('categorie-auto-selected');
                                    }
                                    
                                    // Retirer la classe si l'utilisateur change manuellement la sélection
                                    const removeRedClassFallback = function() {
                                        categorieSelect.classList.remove('categorie-auto-selected');
                                        categorieSelect.removeEventListener('change', removeRedClassFallback);
                                    };
                                    categorieSelect.addEventListener('change', removeRedClassFallback);
                                    
                                    console.log('✓ Catégorie pré-remplie via CATEGORIE (fallback):', categorieSelect.value, 'depuis XML:', categorieXmlFallback);
                                    
                                    // Appliquer la colorisation après un court délai
                                    setTimeout(() => {
                                        applyCategorieColorization();
                                    }, 100);
                                    
                                    setTimeout(() => {
                                        fillDistanceAndBlasonFromCategorie(categorieFound.abv_categorie_classement);
                                    }, 300);
                                } else {
                                    console.log('✗ Aucune catégorie trouvée avec catégorie d\'âge + sexe (fallback)');
                                }
                            }
                        }
                    }
                }
            } else {
                console.log('✗ CATAGE ou TYPARC manquant. CATAGE:', catage, 'TYPARC:', typarc);
            }
        } else {
            console.error('✗ categoriesClassement non défini ou vide');
        }
    } else {
        console.error('✗ Champ categorie_classement introuvable');
    }
    
    // Pré-remplir l'arme (type d'arc)
    // Utiliser TYPARC qui correspond à idarc de la table concour_arcs
    const armeSelect = document.getElementById('arme');
    if (armeSelect) {
        if (typeof arcs !== 'undefined' && arcs && arcs.length > 0) {
            const typarc = archer.TYPARC ? String(archer.TYPARC).trim() : '';
            if (typarc) {
                console.log('Tentative pré-remplissage arme. TYPARC (idarc):', typarc);
                // Chercher l'arc correspondant avec idarc = TYPARC
                const arcFound = arcs.find(arc => {
                    const arcIdarc = String(arc.idarc || arc.id_arc || arc.id || '').trim();
                    return arcIdarc === typarc;
                });
                
                if (arcFound) {
                    armeSelect.value = arcFound.lb_arc || '';
                    console.log('✓ Arme pré-remplie via TYPARC:', armeSelect.value);
                } else {
                    console.log('✗ Arc non trouvé pour TYPARC (idarc):', typarc);
                    console.log('Arcs disponibles:', arcs.map(a => ({
                        lb_arc: a.lb_arc,
                        idarc: a.idarc || a.id_arc || a.id
                    })));
                    
                    // Fallback: chercher par nom si disponible
                    const arcFoundFallback = arcs.find(arc => {
                        const lbArc = (arc.lb_arc || '').trim().toLowerCase();
                        return lbArc.includes(typarc.toLowerCase()) || typarc.toLowerCase().includes(lbArc);
                    });
                    
                    if (arcFoundFallback) {
                        armeSelect.value = arcFoundFallback.lb_arc || '';
                        console.log('✓ Arme pré-remplie via nom (fallback):', armeSelect.value);
                    }
                }
            } else {
                console.log('✗ TYPARC non disponible dans les données');
            }
        } else {
            console.error('✗ arcs non défini ou vide');
        }
    } else {
        console.error('✗ Champ arme introuvable');
    }
    
    console.log('=== Fin prefillFormFields ===');
    
    // Pré-remplir l'affichage des départs sélectionnés
    if (typeof window.updateModalDepartDisplay === 'function') {
        window.updateModalDepartDisplay();
    }
}

/**
 * Remplit automatiquement la distance et le blason en fonction de la catégorie
 */
function fillDistanceAndBlasonFromCategorie(abvCategorie) {
    console.log('=== fillDistanceAndBlasonFromCategorie appelée ===');
    console.log('Paramètres:', {
        abvCategorie: abvCategorie,
        concoursDiscipline: typeof concoursDiscipline !== 'undefined' ? concoursDiscipline : 'undefined',
        concoursTypeCompetition: typeof concoursTypeCompetition !== 'undefined' ? concoursTypeCompetition : 'undefined'
    });
    
    if (!abvCategorie) {
        console.error('✗ Catégorie manquante');
        return;
    }
    
    if (typeof concoursDiscipline === 'undefined' || concoursDiscipline === null) {
        console.error('✗ concoursDiscipline non défini ou null');
        return;
    }
    
    if (typeof concoursTypeCompetition === 'undefined' || concoursTypeCompetition === null) {
        console.error('✗ concoursTypeCompetition non défini ou null');
        return;
    }
    
    // Vérifier si c'est une discipline 3D, Nature ou Campagne
    const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
    if (isNature) {
        console.log('Discipline 3D/Nature/Campagne détectée - pas de remplissage automatique distance/blason');
        return;
    }
    
    console.log('✓ Toutes les conditions sont remplies');
    console.log('→ Appel API distance-recommandee avec:', {
        iddiscipline: concoursDiscipline,
        idtype_competition: concoursTypeCompetition,
        abv_categorie_classement: abvCategorie
    });
    
    // Appeler l'API pour récupérer la distance recommandée
    const params = new URLSearchParams({
        iddiscipline: concoursDiscipline,
        idtype_competition: concoursTypeCompetition,
        abv_categorie_classement: abvCategorie
    });
    
    fetch(`/api/concours/distance-recommandee?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('=== Réponse API distance-recommandee reçue ===');
        console.log('Données complètes:', JSON.stringify(data, null, 2));
        
        if (data.success && data.data) {
            const distanceSelect = document.getElementById('distance');
            const blasonInput = document.getElementById('blason');
            
            if (!distanceSelect) {
                console.error('✗ Select de distance non trouvé dans le DOM');
                return;
            }
            
            // La réponse peut avoir une structure imbriquée : data.data.data (via ApiService)
            // ou directement : data.data (réponse directe)
            const responseData = data.data.data || data.data;
            
            const distanceValeur = responseData.distance_valeur;
            const blasonValeur = responseData.blason;
            
            console.log('Données extraites:', {
                distanceValeur: distanceValeur,
                lb_distance: responseData.lb_distance,
                blasonValeur: blasonValeur
            });
            
            // Sélectionner la distance correspondante
            let distanceFound = false;
            for (let i = 0; i < distanceSelect.options.length; i++) {
                const optionValue = distanceSelect.options[i].value;
                if (optionValue == distanceValeur || optionValue === String(distanceValeur)) {
                    distanceSelect.value = optionValue;
                    distanceFound = true;
                    console.log('✓✓✓ Distance automatiquement sélectionnée:', responseData.lb_distance, '(valeur:', distanceValeur, ')');
                    
                    // Remplir le blason si disponible
                    if (blasonInput) {
                        if (blasonValeur) {
                            blasonInput.value = blasonValeur;
                            console.log('✓✓✓ Blason automatiquement renseigné depuis distance-recommandee:', blasonValeur, 'cm');
                        } else {
                            // Fallback: récupérer le blason via l'API séparée
                            console.log('Blason non inclus dans la réponse distance-recommandee, récupération via API séparée...');
                            // Récupérer l'ID de la catégorie
                            let idCategorie = null;
                            if (typeof categoriesClassement !== 'undefined' && categoriesClassement) {
                                const categorieObj = categoriesClassement.find(cat => {
                                    const abv = (cat.abv_categorie_classement || '').trim();
                                    return abv === abvCategorie;
                                });
                                if (categorieObj) {
                                    idCategorie = categorieObj.idcategorie || categorieObj.id_categorie || categorieObj.id || null;
                                }
                            }
                            if (idCategorie) {
                                getBlasonFromAPI(concoursDiscipline, idCategorie, distanceValeur)
                                    .then(blason => {
                                        if (blason && blasonInput) {
                                            blasonInput.value = blason;
                                            console.log('✓✓✓ Blason récupéré via API séparée:', blason, 'cm');
                                        } else {
                                            console.log('✗ Aucun blason trouvé via API séparée');
                                            blasonInput.value = '';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erreur lors de la récupération du blason:', error);
                                        blasonInput.value = '';
                                    });
                            } else {
                                console.log('✗ IDCategorie non trouvé pour récupérer le blason');
                            }
                        }
                    } else {
                        console.error('✗ Input blason introuvable dans le DOM');
                    }
                    
                    // Déclencher l'événement change sur le select de distance pour déclencher les autres listeners
                    // Cela déclenchera aussi updateBlason si les listeners sont attachés
                    setTimeout(() => {
                        const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                        distanceSelect.dispatchEvent(changeEvent);
                        console.log('Événement change déclenché sur le select de distance');
                        
                        // S'assurer que le blason est mis à jour même si les listeners ne sont pas encore attachés
                        if (blasonInput && !blasonInput.value) {
                            const currentCategorie = document.getElementById('categorie_classement')?.value;
                            const currentDistance = distanceSelect.value;
                            if (currentCategorie && currentDistance && typeof concoursDiscipline !== 'undefined') {
                                console.log('Mise à jour manuelle du blason après changement de distance');
                                // Récupérer l'ID de la catégorie
                                let idCategorie = null;
                                if (typeof categoriesClassement !== 'undefined' && categoriesClassement) {
                                    const categorieObj = categoriesClassement.find(cat => {
                                        const abv = (cat.abv_categorie_classement || '').trim();
                                        return abv === currentCategorie;
                                    });
                                    if (categorieObj) {
                                        idCategorie = categorieObj.idcategorie || categorieObj.id_categorie || categorieObj.id || null;
                                    }
                                }
                                if (idCategorie) {
                                    getBlasonFromAPI(concoursDiscipline, idCategorie, currentDistance)
                                        .then(blason => {
                                            if (blason && blasonInput) {
                                                blasonInput.value = blason;
                                                console.log('✓✓✓ Blason mis à jour manuellement:', blason, 'cm');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Erreur lors de la mise à jour manuelle du blason:', error);
                                        });
                                } else {
                                    console.log('✗ IDCategorie non trouvé pour mise à jour manuelle du blason');
                                }
                            }
                        }
                    }, 200);
                    
                    break;
                }
            }
            
            if (!distanceFound) {
                console.warn('✗ Distance non trouvée dans les options:', distanceValeur);
            }
        } else {
            console.warn('✗ Réponse API invalide ou sans données');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la récupération de la distance recommandée:', error);
    });
}

/**
 * Récupère le blason via l'API séparée
 * Utilise IDCategorie (id de la catégorie) au lieu de l'abréviation
 */
function getBlasonFromAPI(iddiscipline, idCategorie, distanceValeur) {
    console.log('=== getBlasonFromAPI appelée ===');
    console.log('Paramètres:', {
        iddiscipline: iddiscipline,
        idCategorie: idCategorie,
        distanceValeur: distanceValeur
    });
    
    const params = new URLSearchParams({
        iddiscipline: iddiscipline,
        IDCategorie: idCategorie,
        distance_valeur: distanceValeur
    });
    
    return fetch(`/api/concours/blason-recommandee?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => {
        console.log('Réponse API blason-recommandee:', response.status, response.statusText);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Erreur HTTP blason-recommandee:', text.substring(0, 500));
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Données API blason-recommandee:', JSON.stringify(data, null, 2));
        if (data.success && data.data) {
            const responseData = data.data.data || data.data;
            const blason = responseData.blason || null;
            console.log('Blason extrait:', blason);
            return blason;
        }
        console.log('✗ Réponse API sans données de blason');
        return null;
    })
    .catch(error => {
        console.error('Erreur dans getBlasonFromAPI:', error);
        throw error;
    });
}

/**
 * Confirme la sélection de l'archer et soumet l'inscription
 */
function confirmArcherSelection() {
    if (!selectedArcher) {
        alert('Veuillez d\'abord sélectionner un archer');
        return;
    }
    
    submitInscription();
}

/**
 * Soumet les inscriptions pour tous les départs sélectionnés (appels API multiples)
 */
function submitInscription() {
    if (!selectedArcher || !concoursIdValue) {
        alert('Données incomplètes. Veuillez sélectionner un archer et un concours.');
        return;
    }

    const checkedCbs = Array.from(document.querySelectorAll('.depart-checkbox:checked'));
    if (!checkedCbs.length) {
        alert('Veuillez sélectionner au moins un départ (date et heure du greffe).');
        return;
    }

    // Trier les départs par ordre chronologique (15/03 avant 16/03, puis heure) pour assigner numero_tir
    const sortedDeparts = checkedCbs.slice().sort((a, b) => {
        const dateStrA = (a.dataset.dateDepart || '').trim();
        const dateStrB = (b.dataset.dateDepart || '').trim();
        const heureA = (a.dataset.heureGreffe || '00:00').substring(0, 5);
        const heureB = (b.dataset.heureGreffe || '00:00').substring(0, 5);
        if (!dateStrA) return 1;
        if (!dateStrB) return -1;
        const tsA = new Date(dateStrA + 'T' + heureA + ':00').getTime();
        const tsB = new Date(dateStrB + 'T' + heureB + ':00').getTime();
        return tsA - tsB;
    });

    const nom = selectedArcher.name || '';
    const prenom = selectedArcher.first_name || '';
    const user_nom = `${prenom} ${nom}`.trim() || nom || prenom || '';

    const emailInput = document.getElementById('email');
    const emailValue = emailInput?.value?.trim() || '';
    
    // Validation de l'email obligatoire
    if (!emailValue) {
        alert('Veuillez saisir une adresse email pour confirmer l\'inscription.');
        if (emailInput) {
            emailInput.focus();
            emailInput.classList.add('is-invalid');
        }
        return;
    }
    
    // Validation du format email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailValue)) {
        alert('Veuillez saisir une adresse email valide.');
        if (emailInput) {
            emailInput.focus();
            emailInput.classList.add('is-invalid');
        }
        return;
    }
    
    // Retirer la classe d'erreur si elle existe
    if (emailInput) {
        emailInput.classList.remove('is-invalid');
    }
    
    const batchToken = sortedDeparts.length > 1 ? Array.from(crypto.getRandomValues(new Uint8Array(32))).map(b => b.toString(16).padStart(2, '0')).join('') : null;
    let numeroLicence = (selectedArcher.licence_number || '').toString().trim();
    if (numeroLicence.length === 7) {
        numeroLicence = '0' + numeroLicence;
    }
    const baseData = {
        user_nom: user_nom,
        numero_licence: numeroLicence,
        id_club: selectedArcher.id_club || '',
        email: emailValue,
        saison: document.getElementById('saison')?.value || '',
        type_certificat_medical: document.getElementById('type_certificat_medical')?.value || '',
        type_licence: document.getElementById('type_licence')?.value || '',
        creation_renouvellement: document.getElementById('creation_renouvellement')?.value || '',
        categorie_classement: document.getElementById('categorie_classement')?.value || '',
        arme: document.getElementById('arme')?.value || '',
        mobilite_reduite: document.getElementById('mobilite_reduite')?.checked ? 1 : 0
    };
    const piquetSelect = document.getElementById('piquet');
    if (piquetSelect) {
        baseData.piquet = piquetSelect.value || '';
    } else {
        baseData.distance = document.getElementById('distance')?.value || '';
        baseData.blason = document.getElementById('blason')?.value || '';
        baseData.duel = document.getElementById('duel')?.checked ? 1 : 0;
        baseData.trispot = document.getElementById('trispot')?.checked ? 1 : 0;
    }

    const apiUrl = (typeof inscriptionCible !== 'undefined' && inscriptionCible)
        ? '/api/concours/' + concoursIdValue + '/inscription/public'
        : '/api/concours/' + concoursIdValue + '/inscription';
    const confirmBtn = document.getElementById('btn-confirm-inscription');
    const originalBtnText = confirmBtn ? confirmBtn.textContent : '';

    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Enregistrement...';
    }

    // Exécuter les inscriptions séquentiellement pour éviter les erreurs 500 (race condition
    // dans recalculateNumeroTirForArcher quand plusieurs requêtes modifient les mêmes inscriptions)
    const runSequential = async () => {
        const results = [];
        for (const cb of sortedDeparts) {
            const numeroDepart = parseInt(cb.value, 10);
            const data = { ...baseData, numero_depart: numeroDepart };
            if (batchToken) data.token_confirmation = batchToken;
            try {
                const r = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });
                const text = await r.text();
                let body = {};
                try {
                    body = text ? JSON.parse(text) : {};
                } catch (parseErr) {
                    console.error('Inscription départ ' + numeroDepart + ' - Réponse non-JSON:', text.substring(0, 300));
                    body = { error: 'Réponse serveur invalide (HTTP ' + r.status + ')' };
                }
                if (!r.ok) {
                    if (!body.error && text) body.error = text.substring(0, 200);
                    if (!body.error) body.error = 'Erreur HTTP ' + r.status;
                    console.error('Inscription départ ' + numeroDepart + ' - Erreur API:', body.error);
                }
                results.push({ ok: r.ok, status: r.status, body });
            } catch (err) {
                console.error('Inscription départ ' + numeroDepart + ' - Erreur:', err);
                results.push({ ok: false, status: 0, body: { error: err.message || 'Erreur réseau' } });
            }
        }
        return results;
    };

    runSequential().then(results => {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalBtnText;
        }
        const successes = results.filter(r => r.ok && (r.body.success !== false));
        const failures = results.filter(r => !r.ok || r.body.success === false);
        if (failures.length === 0) {
            // Récupérer les données buvette AVANT de fermer la modale (les inputs sont dans la modale)
            const buvetteItems = typeof getBuvetteItems === 'function' ? getBuvetteItems() : [];
            const tokenForBuvette = batchToken || (successes[0] && successes[0].body && successes[0].body.token_confirmation) || null;
            const modal = document.getElementById('confirmInscriptionModal');
            if (modal && typeof bootstrap !== 'undefined') bootstrap.Modal.getInstance(modal)?.hide();
            loadInscriptions();
            // Enregistrer les réservations buvette si des articles ont été sélectionnés
            if (buvetteItems.length > 0 && tokenForBuvette) {
                fetch('/api/concours/' + concoursIdValue + '/buvette/reservations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ token_confirmation: tokenForBuvette, items: buvetteItems })
                }).catch(function() {});
            }
            if (batchToken && emailValue && successes.length > 1) {
                const sendEmailUrl = (typeof inscriptionCible !== 'undefined' && inscriptionCible)
                    ? '/api/concours/' + concoursIdValue + '/inscriptions/send-confirmation-email/public'
                    : '/api/concours/' + concoursIdValue + '/inscriptions/send-confirmation-email';
                fetch(sendEmailUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ token: batchToken, email: emailValue })
                }).catch(function() {});
            }
            alert(successes.length === 1
                ? 'Inscription enregistrée avec succès.'
                : successes.length + ' inscription(s) enregistrée(s) avec succès.');
        } else {
            const alreadyRegistered = failures.filter(f => {
                const m = (f.body?.error || f.body?.message || '').toLowerCase();
                return m.includes('déjà inscrit') || m.includes('deja inscrit');
            });
            const otherFailures = failures.filter(f => !alreadyRegistered.includes(f));
            if (alreadyRegistered.length > 0 && otherFailures.length === 0) {
                alert('Attention : Cet archer est déjà inscrit pour le(s) départ(s) sélectionné(s). Aucune nouvelle inscription n\'a été créée.');
            } else if (alreadyRegistered.length > 0) {
                const otherMsg = otherFailures.map(f => f.body?.error || f.body?.message || 'Erreur ' + f.status).join('\n');
                alert('Attention : Cet archer est déjà inscrit pour certains départs.\n\nErreur(s) pour d\'autres départs :\n' + otherMsg);
            } else {
                const msg = failures.map(f => f.body?.error || f.body?.message || 'Erreur ' + f.status).join('\n');
                alert('Erreur(s) lors de l\'enregistrement :\n' + msg);
            }
            if (successes.length > 0) loadInscriptions();
        }
    }).catch(err => {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalBtnText;
        }
        alert('Erreur réseau : ' + (err.message || err));
    });
}

/**
 * Parse une réponse fetch en JSON. Si le serveur renvoie du HTML (404, redirect, erreur),
 * lance une erreur explicite au lieu de "Unexpected token '<'".
 */
function parseJsonResponse(response) {
    const ct = (response.headers.get('content-type') || '').toLowerCase();
    return response.text().then(function(text) {
        if (!ct.includes('application/json') && !ct.includes('text/json')) {
            if (text.trim().startsWith('<')) {
                throw new Error('Le serveur a renvoyé une page HTML au lieu de JSON (vérifiez l\'URL de l\'API ou la configuration du proxy).');
            }
        }
        try {
            return text ? JSON.parse(text) : {};
        } catch (e) {
            if (text.trim().startsWith('<')) {
                throw new Error('Le serveur a renvoyé une page HTML au lieu de JSON. Vérifiez que l\'API est accessible.');
            }
            throw e;
        }
    });
}

/**
 * Extrait le tableau d'inscriptions depuis la réponse API
 */
function getInscriptionsFromResponse(data) {
    if (Array.isArray(data)) {
        return data;
    }
    if (data && data.success && Array.isArray(data.data)) {
        return data.data;
    }
    if (data && data.data && Array.isArray(data.data.data)) {
        return data.data.data;
    }
    if (data && Array.isArray(data.inscriptions)) {
        return data.inscriptions;
    }
    return [];
}

/**
 * Charge la liste des inscriptions depuis l'API et met à jour le tableau
 */
function loadInscriptions() {
    if (!concoursIdValue) {
        console.warn('loadInscriptions: concoursId non disponible');
        return;
    }

    const tbody = document.getElementById('inscriptions-list');
    if (!tbody) {
        console.warn('loadInscriptions: #inscriptions-list introuvable');
        return;
    }

    const inscriptionsUrl = typeof apiInscriptionsUrl !== 'undefined' ? apiInscriptionsUrl : `/api/concours/${concoursIdValue}/inscriptions`;
    fetch(inscriptionsUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => parseJsonResponse(response))
    .then(data => {
        allInscriptionsCache = getInscriptionsFromResponse(data);
        try {
            applyDepartFilterAndRender();
        } catch (err) {
            console.error('Erreur renderInscriptions:', err);
            tbody.innerHTML = '<tr id="inscriptions-empty-row"><td colspan="10" class="text-center text-danger">Erreur lors de l\'affichage des inscriptions.</td></tr>';
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des inscriptions:', error);
        tbody.innerHTML = '<tr id="inscriptions-empty-row"><td colspan="10" class="text-center text-danger">Erreur lors du chargement des inscriptions.</td></tr>';
    });
}

/**
 * Filtre les inscriptions selon les départs cochés et affiche le tableau
 */
function applyDepartFilterAndRender() {
    const checkedDeparts = Array.from(document.querySelectorAll('.depart-checkbox:checked')).map(cb => cb.value);
    let toRender = allInscriptionsCache;
    if (checkedDeparts.length > 0) {
        const depSet = new Set(checkedDeparts.map(String));
        toRender = allInscriptionsCache.filter(ins => depSet.has(String(ins.numero_depart ?? '')));
    }
    renderInscriptions(toRender);
}

/**
 * Affiche les inscriptions dans le tableau
 */
function renderInscriptions(inscriptions) {
    const tbody = document.getElementById('inscriptions-list');
    if (!tbody) return;

    const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
    const checkedDeparts = Array.from(document.querySelectorAll('.depart-checkbox:checked')).map(cb => cb.value);
    const isFiltered = checkedDeparts.length > 0;

    const hintEl = document.getElementById('inscriptions-filter-hint');
    if (hintEl) {
        hintEl.textContent = isFiltered
            ? 'Affichage filtré : inscriptions des départs sélectionnés (' + checkedDeparts.length + ' départ(s)).'
            : 'La liste affiche toutes les inscriptions. Cochez des départs ci-dessus pour filtrer.';
    }

    if (!inscriptions || inscriptions.length === 0) {
        const msg = isFiltered
            ? 'Aucune inscription pour les départs sélectionnés.'
            : 'Aucun archer inscrit pour le moment.';
        tbody.innerHTML = '<tr id="inscriptions-empty-row"><td colspan="10" class="text-center text-muted">' + msg + '</td></tr>';
        return;
    }

    const isDirigeant = !!(typeof window !== 'undefined' && window.isDirigeant);
    const currentUserLicence = (typeof window !== 'undefined' && window.currentUserLicence != null ? window.currentUserLicence : '').toString().trim();
    const currentUserId = (typeof window !== 'undefined' && window.currentUserId != null) ? String(window.currentUserId) : '';

    const rows = inscriptions.map(inscription => {
        const piquetColorRaw = inscription.piquet || null;
        let rowClass = '';
        let dataPiquet = '';
        let rowStyle = '';

        if (piquetColorRaw && piquetColorRaw !== '') {
            const piquetColor = piquetColorRaw.trim().toLowerCase();
            rowClass = 'piquet-' + piquetColor;
            dataPiquet = ' data-piquet="' + piquetColor + '"';
            const colors = { rouge: '#ffe0e0', bleu: '#e0e8ff', blanc: '#f5f5f5' };
            if (colors[piquetColor]) {
                rowStyle = ' style="background-color: ' + colors[piquetColor] + ' !important;"';
            }
        } else if (isNature) {
            rowClass = 'piquet-manquant';
            rowStyle = ' style="background-color: #dee2e6 !important;"';
        }

        const inscriptionLicence = (inscription.numero_licence || '').toString().trim();
        const inscriptionUserId = inscription.user_id != null ? String(inscription.user_id) : '';
        const isOwnInscription = (currentUserLicence && inscriptionLicence && currentUserLicence === inscriptionLicence) ||
            (currentUserId && inscriptionUserId && currentUserId === inscriptionUserId);
        const canManageInscription = isDirigeant && !isOwnInscription;
        const canEditDeleteInscription = canManageInscription || isOwnInscription;

        const clubDisplay = inscription.club_name || inscription.id_club || 'N/A';
        const piquetDisplay = piquetColorRaw ? piquetColorRaw.charAt(0).toUpperCase() + piquetColorRaw.slice(1).toLowerCase() : 'N/A';
        const dateDisplay = inscription.created_at || inscription.date_inscription || 'N/A';
        const id = inscription.id || '';

        const statut = inscription.statut_inscription || 'en_attente';
        let statutIconClass, statutTitle;
        if (statut === 'confirmee') {
            statutIconClass = 'fa-check-circle text-success';
            statutTitle = 'Confirmée';
        } else if (statut === 'refuse' || statut === 'annule') {
            statutIconClass = 'fa-times-circle text-danger';
            statutTitle = statut === 'refuse' ? 'Refusée' : 'Annulée';
        } else {
            statutIconClass = 'fa-clock text-warning';
            statutTitle = 'En attente';
        }

        let statutCell;
        if (canManageInscription) {
            statutCell = '<td class="statut-cell"' + rowStyle + '>' +
                '<div class="dropdown statut-dropdown" data-inscription-id="' + escapeHtml(String(id)) + '">' +
                '<button class="btn btn-link p-0 border-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="' + escapeHtml(statutTitle) + '">' +
                '<i class="fas ' + statutIconClass + '"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="en_attente" data-inscription-id="' + escapeHtml(String(id)) + '"><i class="fas fa-clock text-warning me-2"></i>En attente</a></li>' +
                '<li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="confirmee" data-inscription-id="' + escapeHtml(String(id)) + '"><i class="fas fa-check-circle text-success me-2"></i>Confirmée</a></li>' +
                '<li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="refuse" data-inscription-id="' + escapeHtml(String(id)) + '"><i class="fas fa-times-circle text-danger me-2"></i>Refusée</a></li>' +
                '<li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="annule" data-inscription-id="' + escapeHtml(String(id)) + '"><i class="fas fa-times-circle text-danger me-2"></i>Annulée</a></li>' +
                '</ul></div></td>';
        } else {
            statutCell = '<td class="statut-cell"' + rowStyle + '><span title="' + escapeHtml(statutTitle) + '"><i class="fas ' + statutIconClass + '"></i></span></td>';
        }

        let actionsCell;
        if (canEditDeleteInscription) {
            actionsCell = '<button type="button" class="btn btn-sm btn-primary me-1" onclick="editInscription(' + id + ')"><i class="fas fa-edit"></i></button> ' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(' + id + ')"><i class="fas fa-trash"></i></button>';
        } else {
            actionsCell = '—';
        }

        let cells = [
            statutCell,
            '<td' + rowStyle + '>' + escapeHtml(inscription.user_nom || 'N/A') + '</td>',
            '<td' + rowStyle + '>' + escapeHtml(inscription.numero_licence || 'N/A') + '</td>',
            '<td' + rowStyle + '>' + escapeHtml(clubDisplay) + '</td>',
            '<td' + rowStyle + '>' + escapeHtml(String(inscription.numero_depart ?? 'N/A')) + '</td>',
            '<td' + rowStyle + '>' + escapeHtml(String(inscription.numero_tir ?? 'N/A')) + '</td>'
        ];

        if (isNature) {
            cells.push('<td class="piquet-value"' + rowStyle + '>' + escapeHtml(piquetDisplay) + '</td>');
        } else {
            cells.push('<td' + rowStyle + '>' + escapeHtml(String(inscription.distance ?? 'N/A')) + '</td>');
            cells.push('<td' + rowStyle + '>' + escapeHtml(String(inscription.blason ?? 'N/A')) + '</td>');
        }

        cells.push('<td' + rowStyle + '>' + escapeHtml(dateDisplay) + '</td>');
        cells.push('<td' + rowStyle + '>' + actionsCell + '</td>');

        return '<tr data-inscription-id="' + id + '" class="' + rowClass + '"' + dataPiquet + rowStyle + '>' + cells.join('') + '</tr>';
    });

    tbody.innerHTML = rows.join('');
}

function escapeHtml(text) {
    if (text == null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

/**
 * Met à jour le statut d'une inscription
 */
function updateStatutInscription(inscriptionId, statut) {
    const concoursId = concoursIdValue || (typeof concoursId !== 'undefined' ? concoursId : null);
    if (!concoursId) {
        alert('Erreur: ID du concours non disponible');
        return;
    }

    fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ statut_inscription: statut })
    })
    .then(response => {
        return response.json().then(data => ({ ok: response.ok, data }));
    })
    .then(({ ok, data }) => {
        const success = ok && (data.success !== false) && !data.error;
        if (success) {
            loadInscriptions();
        } else {
            const errMsg = data.error || data.message || (data.data && data.data.error) || 'Erreur inconnue';
            alert('Erreur lors de la mise à jour du statut: ' + errMsg + (ok ? '' : ' (Connexion requise)'));
        }
    })
    .catch(err => {
        console.error('Erreur updateStatutInscription:', err);
        alert('Erreur lors de la mise à jour du statut. Vérifiez que vous êtes connecté.');
    });
}

/**
 * Retire une inscription par ID
 */
function removeInscription(inscriptionId) {
    if (!confirm('Voulez-vous retirer cet archer de l\'inscription ?')) {
        return;
    }

    const concoursId = concoursIdValue || (typeof concoursId !== 'undefined' ? concoursId : null);
    if (!concoursId) {
        alert('Erreur: ID du concours non disponible');
        return;
    }

    fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadInscriptions();
        } else {
            alert('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression: ' + error.message);
    });
}

// Inscription en cours d'édition (pour conserver numero_depart si le select n'existe pas)
let currentEditInscription = null;

/**
 * Édite une inscription - charge les données et ouvre la modale
 */
window.editInscription = function(inscriptionId) {
    const concoursId = concoursIdValue || (typeof concoursId !== 'undefined' ? concoursId : null);
    if (!concoursId || !inscriptionId) {
        alert('Erreur: Informations manquantes');
        return;
    }

    const modalElement = document.getElementById('editInscriptionModal');
    if (!modalElement) {
        alert('Erreur: La modale d\'édition est introuvable');
        return;
    }

    const form = document.getElementById('edit-inscription-form');
    if (form) {
        form.dataset.inscriptionId = inscriptionId;
    }

    currentEditInscription = null;

    fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        let inscription = (data.success && data.data) ? data.data : (data.id ? data : null);
        if (!inscription) {
            alert('Erreur: Aucune donnée trouvée');
            return;
        }
        if (inscription.success && inscription.data) {
            inscription = inscription.data;
        }
        currentEditInscription = inscription;

        const fillForm = () => {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };
            const setCheck = (id, checked) => {
                const el = document.getElementById(id);
                if (el) el.checked = !!checked;
            };

            setVal('edit-saison', inscription.saison);
            setVal('edit-type_certificat_medical', inscription.type_certificat_medical);
            setVal('edit-type_licence', inscription.type_licence);
            // Afficher l'avertissement si nécessaire
            if (inscription.type_licence) {
                updateLicenceWarning(inscription.type_licence, 'edit-type_licence_warning');
            }
            let crVal = inscription.creation_renouvellement;
            if (crVal === 1 || crVal === '1') crVal = 'C';
            else if (crVal === 2 || crVal === '2') crVal = 'R';
            setVal('edit-creation_renouvellement', crVal || '');
            setVal('edit-depart-select', inscription.numero_depart);
            setVal('edit-categorie_classement', inscription.categorie_classement);
            setVal('edit-arme', inscription.arme);
            setCheck('edit-mobilite_reduite', inscription.mobilite_reduite);

            if (typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne) {
                setVal('edit-piquet', inscription.piquet);
            } else {
                setVal('edit-distance', inscription.distance);
                setVal('edit-blason', inscription.blason);
                setCheck('edit-duel', inscription.duel);
                setCheck('edit-trispot', inscription.trispot);
            }

        };

        fillForm();
        loadEditBuvetteProduits(concoursId, inscription.buvette_reservations || [], inscription.token_confirmation);
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement: ' + error.message);
    });
};

/**
 * Initialise les handlers pour le formulaire d'édition
 */
function initEditInscriptionHandlers() {
    const btnConfirmEdit = document.getElementById('btn-confirm-edit');
    if (!btnConfirmEdit) return;

    btnConfirmEdit.addEventListener('click', function() {
        const form = document.getElementById('edit-inscription-form');
        const inscriptionId = form?.dataset?.inscriptionId;
        const concoursId = concoursIdValue || (typeof concoursId !== 'undefined' ? concoursId : null);

        if (!concoursId || !inscriptionId) {
            alert('Erreur: Informations manquantes');
            return;
        }

        const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
        const departSelect = document.getElementById('edit-depart-select');
        const numeroDepart = departSelect?.value || (currentEditInscription?.numero_depart ?? '');
        const updateData = {
            saison: document.getElementById('edit-saison')?.value || '',
            type_certificat_medical: document.getElementById('edit-type_certificat_medical')?.value || '',
            type_licence: document.getElementById('edit-type_licence')?.value || '',
            creation_renouvellement: document.getElementById('edit-creation_renouvellement')?.value || '',
            numero_depart: numeroDepart,
            categorie_classement: document.getElementById('edit-categorie_classement')?.value || '',
            arme: document.getElementById('edit-arme')?.value || '',
            mobilite_reduite: document.getElementById('edit-mobilite_reduite')?.checked ? 1 : 0,
            numero_tir: currentEditInscription?.numero_tir ?? '',
        };

        if (isNature) {
            updateData.piquet = document.getElementById('edit-piquet')?.value || '';
        } else {
            updateData.distance = document.getElementById('edit-distance')?.value || '';
            updateData.blason = document.getElementById('edit-blason')?.value || '';
            updateData.duel = document.getElementById('edit-duel')?.checked ? 1 : 0;
            updateData.trispot = document.getElementById('edit-trispot')?.checked ? 1 : 0;
        }

        fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(updateData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const token = currentEditInscription?.token_confirmation;
                if (token) {
                    const items = getEditBuvetteItems();
                    return fetch('/api/concours/' + concoursId + '/buvette/reservations', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ token_confirmation: token, items: items })
                    }).then(r => r.json()).then(buvRes => {
                        if (buvRes && !buvRes.success && buvRes.error) {
                            console.warn('Buvette:', buvRes.error);
                        }
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editInscriptionModal'));
                        if (modal) modal.hide();
                        loadInscriptions();
                    });
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('editInscriptionModal'));
                if (modal) modal.hide();
                loadInscriptions();
            } else {
                alert('Erreur lors de la mise à jour: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la mise à jour: ' + error.message);
        });
    });
}

/**
 * Charge les produits buvette pour le concours et les affiche dans le modal
 */
function loadBuvetteProduits() {
    const cid = (typeof concoursIdValue !== 'undefined' && concoursIdValue) ? concoursIdValue : (document.querySelector('input[name="concours_id"]')?.value || window.location.pathname.match(/\/concours\/(\d+)/)?.[1] || window.location.pathname.match(/\/inscription-cible\/(\d+)/)?.[1]);
    if (!cid) return;
    const loadingEl = document.getElementById('buvette-loading');
    const listEl = document.getElementById('buvette-produits-list');
    const emptyEl = document.getElementById('buvette-empty');
    if (!loadingEl || !listEl) return;
    loadingEl.classList.remove('d-none');
    listEl.classList.add('d-none');
    if (emptyEl) emptyEl.classList.add('d-none');
    const url = '/api/concours/' + cid + '/buvette/produits/public';
    fetch(url, { credentials: 'include', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            loadingEl.classList.add('d-none');
            const produits = Array.isArray(data) ? data : (data.data || []);
            if (produits.length === 0) {
                if (emptyEl) { emptyEl.classList.remove('d-none'); }
                return;
            }
            if (emptyEl) emptyEl.classList.add('d-none');
            listEl.innerHTML = produits.map(p => {
                const prix = p.prix != null ? parseFloat(p.prix).toFixed(2) + ' €' : '';
                const unite = p.unite || 'portion';
                return '<div class="d-flex align-items-center justify-content-between mb-2"><label class="mb-0 flex-grow-1">' + (p.libelle || '') + (prix ? ' <span class="text-muted">(' + prix + ')</span>' : '') + '</label><input type="number" class="form-control form-control-sm buvette-qty" data-produit-id="' + (p.id || '') + '" min="0" value="0" style="width:70px;"> <span class="ms-1 small text-muted">' + unite + '</span></div>';
            }).join('');
            listEl.classList.remove('d-none');
        })
        .catch(() => {
            loadingEl.classList.add('d-none');
            if (emptyEl) { emptyEl.textContent = 'Erreur de chargement.'; emptyEl.classList.remove('d-none'); }
        });
}

/**
 * Récupère les articles buvette sélectionnés (quantité > 0)
 */
function getBuvetteItems() {
    const inputs = document.querySelectorAll('.buvette-qty');
    const items = [];
    inputs.forEach(inp => {
        const qty = parseInt(inp.value, 10) || 0;
        const pid = inp.getAttribute('data-produit-id');
        if (qty > 0 && pid) items.push({ produit_id: parseInt(pid, 10), quantite: qty });
    });
    return items;
}

/**
 * Récupère les articles buvette du modal d'édition (quantité > 0)
 */
function getEditBuvetteItems() {
    const container = document.getElementById('edit-buvette-produits-list');
    const inputs = container ? container.querySelectorAll('.buvette-qty') : [];
    const items = [];
    inputs.forEach(inp => {
        const qty = parseInt(inp.value, 10) || 0;
        const pid = inp.getAttribute('data-produit-id');
        if (qty > 0 && pid) items.push({ produit_id: parseInt(pid, 10), quantite: qty });
    });
    return items;
}

/**
 * Charge les produits buvette dans le modal d'édition et préremplit les quantités
 */
function loadEditBuvetteProduits(concoursId, buvetteReservations, tokenConfirmation) {
    const loadingEl = document.getElementById('edit-buvette-loading');
    const listEl = document.getElementById('edit-buvette-produits-list');
    const emptyEl = document.getElementById('edit-buvette-empty');
    const noTokenEl = document.getElementById('edit-buvette-no-token');
    if (!loadingEl || !listEl) return;

    loadingEl.classList.remove('d-none');
    listEl.classList.add('d-none');
    if (emptyEl) emptyEl.classList.add('d-none');
    if (noTokenEl) noTokenEl.classList.add('d-none');

    if (!tokenConfirmation) {
        loadingEl.classList.add('d-none');
        if (noTokenEl) noTokenEl.classList.remove('d-none');
        return;
    }

    const qtyByProduit = {};
    (buvetteReservations || []).forEach(r => {
        const pid = r.produit_id || r.id;
        if (pid) qtyByProduit[pid] = parseInt(r.quantite, 10) || 0;
    });

    fetch('/api/concours/' + concoursId + '/buvette/produits/public', { credentials: 'include', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            loadingEl.classList.add('d-none');
            const produits = Array.isArray(data) ? data : (data.data || []);
            if (produits.length === 0) {
                if (emptyEl) emptyEl.classList.remove('d-none');
                return;
            }
            if (emptyEl) emptyEl.classList.add('d-none');
            listEl.innerHTML = produits.map(p => {
                const pid = p.id || '';
                const qty = qtyByProduit[pid] || 0;
                const prix = p.prix != null ? parseFloat(p.prix).toFixed(2) + ' €' : '';
                const unite = p.unite || 'portion';
                return '<div class="d-flex align-items-center justify-content-between mb-2"><label class="mb-0 flex-grow-1">' + (p.libelle || '') + (prix ? ' <span class="text-muted">(' + prix + ')</span>' : '') + '</label><input type="number" class="form-control form-control-sm buvette-qty" data-produit-id="' + pid + '" min="0" value="' + qty + '" style="width:70px;"> <span class="ms-1 small text-muted">' + unite + '</span></div>';
            }).join('');
            listEl.classList.remove('d-none');
        })
        .catch(() => {
            loadingEl.classList.add('d-none');
            if (emptyEl) { emptyEl.textContent = 'Erreur de chargement.'; emptyEl.classList.remove('d-none'); }
        });
}
