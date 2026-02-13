// Variables globales
let selectedArcher = null;
const concoursIdValue = (typeof concoursId !== 'undefined' && concoursId) ? concoursId :
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
});

// Variable pour stocker la fonction de mise à jour du blason (pour pouvoir la retirer si nécessaire)
let blasonUpdateHandler = null;

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
    const licence = licenceInput?.value?.trim();
    
    if (!licence) {
        alert('Veuillez entrer un numéro de licence');
        return;
    }
    
    // Désactiver le bouton pendant la recherche
    const searchBtn = document.getElementById('archer-search-btn');
    if (searchBtn) searchBtn.disabled = true;
    
    fetch('/archer/search-or-create', {
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
        // Configurer les listeners pour le blason maintenant que le modal est ouvert
        setupBlasonAutoUpdate();
        modalElement.removeEventListener('shown.bs.modal', onModalShown);
    }, { once: true });
    
    modal.show();
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
    if (categorieSelect) {
        if (typeof categoriesClassement !== 'undefined' && categoriesClassement && categoriesClassement.length > 0) {
            const catage = archer.CATAGE ? String(archer.CATAGE).trim() : '';
            const typarc = archer.TYPARC ? String(archer.TYPARC).trim() : '';
            const sexeXml = (archer.SEXE || '').trim();
            const sexeLetter = sexeXml === '1' ? 'H' : (sexeXml === '2' ? 'F' : '');
            
            console.log('Tentative pré-remplissage catégorie. CATAGE:', catage, 'TYPARC:', typarc, 'SEXE:', sexeXml, '->', sexeLetter);
            
            if (catage && typarc) {
                // Chercher la catégorie correspondante avec idcategorie = CATAGE, idarc = TYPARC et sexe = H/F
                let categorieFound = categoriesClassement.find(cat => {
                    const catIdcategorie = String(cat.idcategorie || cat.id_categorie || '').trim();
                    const catIdarc = String(cat.idarc || cat.id_arc || '').trim();
                    const catSexe = (cat.sexe || cat.SEXE || '').trim().toUpperCase();
                    const catAbv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                    
                    console.log('Comparaison catégorie:', {
                        catIdcategorie,
                        catage,
                        matchIdcategorie: catIdcategorie === catage,
                        catIdarc,
                        typarc,
                        matchIdarc: catIdarc === typarc,
                        catSexe,
                        sexeLetter,
                        matchSexe: sexeLetter ? catSexe === sexeLetter : true,
                        catAbv
                    });
                    
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
                            return catSexe === sexeLetter;
                        } else {
                            // Fallback: vérifier que l'abréviation commence par H ou F
                            return catAbv.startsWith(sexeLetter);
                        }
                    }
                    
                    // Si pas de sexe, retourner la première correspondance
                    return true;
                });
                
                if (categorieFound) {
                    categorieSelect.value = categorieFound.abv_categorie_classement || '';
                    console.log('✓ Catégorie pré-remplie via CATAGE/TYPARC/SEXE:', categorieSelect.value);
                    
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
                    const matchingCategories = categoriesClassement.filter(cat => {
                        const catIdcategorie = String(cat.idcategorie || cat.id_categorie || '').trim();
                        const catIdarc = String(cat.idarc || cat.id_arc || '').trim();
                        return catIdcategorie === catage && catIdarc === typarc;
                    });
                    if (matchingCategories.length > 0) {
                        console.log('Catégories correspondant à CATAGE et TYPARC (sans sexe):', matchingCategories.map(c => ({
                            abv: c.abv_categorie_classement,
                            sexe: c.sexe || c.SEXE || 'N/A'
                        })));
                    }
                    
                    // Fallback: essayer avec CATEGORIE si disponible
                    let categorieXml = (archer.CATEGORIE || '').trim().toUpperCase();
                    if (categorieXml && sexeXml && !categorieXml.match(/[HF]/)) {
                        if (sexeLetter) {
                            categorieXml = sexeLetter + categorieXml;
                        }
                    }
                    
                    if (categorieXml) {
                        const categorieFoundFallback = categoriesClassement.find(cat => {
                            const abv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                            return abv === categorieXml;
                        });
                        
                        if (categorieFoundFallback) {
                            categorieSelect.value = categorieFoundFallback.abv_categorie_classement || '';
                            console.log('✓ Catégorie pré-remplie via CATEGORIE (fallback):', categorieSelect.value);
                            
                            setTimeout(() => {
                                fillDistanceAndBlasonFromCategorie(categorieFoundFallback.abv_categorie_classement);
                            }, 300);
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
    
    // Pré-remplir le numéro de départ (affichage seulement)
    const departSelect = document.getElementById('depart-select-main');
    if (departSelect && departSelect.value) {
        const modalDepartDisplay = document.getElementById('modal-depart-display');
        if (modalDepartDisplay) {
            modalDepartDisplay.textContent = 'Départ ' + departSelect.value;
        }
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
 * Soumet le formulaire d'inscription
 */
function submitInscription() {
    if (!selectedArcher || !concoursIdValue) {
        alert('Données incomplètes. Veuillez sélectionner un archer et un concours.');
        return;
    }

    const departSelect = document.getElementById('depart-select-main');
    const numeroDepart = departSelect?.value || '';
    if (!numeroDepart) {
        alert('Veuillez sélectionner un numéro de départ.');
        return;
    }
    
    // Construire user_nom avec nom et prénom
    const nom = selectedArcher.name || '';
    const prenom = selectedArcher.first_name || '';
    const user_nom = `${prenom} ${nom}`.trim() || nom || prenom || '';
    
    // Récupérer les données du formulaire
    const formData = {
        user_nom: user_nom,
        numero_depart: numeroDepart,
        numero_licence: selectedArcher.licence_number,
        id_club: selectedArcher.id_club || '', // ID unique du club depuis club_unique du XML
        saison: document.getElementById('saison')?.value || '',
        type_certificat_medical: document.getElementById('type_certificat_medical')?.value || '',
        type_licence: document.getElementById('type_licence')?.value || '',
        creation_renouvellement: document.getElementById('creation_renouvellement')?.value || '',
        categorie_classement: document.getElementById('categorie_classement')?.value || '',
        arme: document.getElementById('arme')?.value || '',
        mobilite_reduite: document.getElementById('mobilite_reduite')?.checked ? 1 : 0,
        numero_tir: document.getElementById('numero_tir')?.value || '',
        tarif_competition: document.getElementById('tarif_competition')?.value || '',
        mode_paiement: document.getElementById('mode_paiement')?.value || ''
    };

    const piquetSelect = document.getElementById('piquet');
    if (piquetSelect) {
        formData.piquet = piquetSelect.value || '';
    } else {
        formData.distance = document.getElementById('distance')?.value || '';
        formData.blason = document.getElementById('blason')?.value || '';
        formData.duel = document.getElementById('duel')?.checked ? 1 : 0;
        formData.trispot = document.getElementById('trispot')?.checked ? 1 : 0;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/concours/${concoursIdValue}/inscription`;

    Object.entries(formData).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
