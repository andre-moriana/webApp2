// Gestion de l'inscription aux concours

// Variable globale pour stocker l'archer sélectionné
let selectedArcher = null;

// DÉFINIR showConfirmModal IMMÉDIATEMENT au début du fichier
window.showConfirmModal = function(archer) {
    console.log('=== showConfirmModal DÉBUT ===');
    console.log('Archer reçu:', archer);
    console.log('Type archer:', typeof archer);
    
    if (!archer) {
        console.error('showConfirmModal: archer est undefined');
        alert('Erreur: Aucune information d\'archer disponible');
        return;
    }
    
    const modalElement = document.getElementById('confirmInscriptionModal');
    const modalBody = document.getElementById('confirm-modal-body');
    const modalTitle = modalElement ? modalElement.querySelector('.modal-title') : null;
    
    if (!modalElement || !modalBody) {
        console.error('showConfirmModal: Modal introuvable');
        alert('Erreur: Modal introuvable');
        return;
    }
    
    // FORCER le titre AVANT de modifier le contenu
    if (modalTitle) {
        modalTitle.textContent = 'Confirmer l\'inscription';
        modalTitle.innerHTML = 'Confirmer l\'inscription';
        console.log('Titre défini:', modalTitle.textContent);
    }
    
    // Extraire les informations avec String() pour éviter [object Object]
    const nom = String(archer.nom || archer.name || archer.NOM || 'N/A');
    const prenom = String(archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A');
    const licence = String(archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A');
    const club = String(archer.club_name || archer.CLUB || 'N/A');
    const gender = String(archer.gender || archer.GENRE || '');
    const birthDate = String(archer.birth_date || archer.birthDate || archer.DATENAISSANCE || '');
    
    console.log('Données extraites - nom:', nom, 'prenom:', prenom, 'licence:', licence);
    
    // Générer le HTML pour les départs (basé sur nombre_depart du concours)
    let departsHtml = '';
    if (typeof concoursNombreDepart !== 'undefined' && concoursNombreDepart && concoursNombreDepart > 0) {
        departsHtml = `
            <div class="mb-3">
                <label for="depart-select" class="form-label">N° départ <span class="text-danger">*</span></label>
                <select id="depart-select" class="form-control" required>
                    <option value="">Sélectionner un départ</option>
                    ${Array.from({length: parseInt(concoursNombreDepart)}, (_, i) => 
                        `<option value="${i + 1}">Départ ${i + 1}</option>`
                    ).join('')}
                </select>
            </div>
        `;
    }
    
    // Construire le contenu HTML COMPLET avec tous les champs
    const modalContent = `
        <div class="archer-summary mb-3 p-3 bg-light rounded">
            <h5>Informations de l'archer</h5>
            <p class="mb-1"><strong>Nom:</strong> ${nom} ${prenom}</p>
            <p class="mb-1"><strong>Licence:</strong> ${licence}</p>
            <p class="mb-1"><strong>Club:</strong> ${club}</p>
            ${gender ? `<p class="mb-1"><strong>Genre:</strong> ${gender === 'M' || gender === 'Homme' ? 'Homme' : 'Femme'}</p>` : ''}
        </div>
        
        <form id="inscription-form">
            <h5 class="mb-3">Informations d'inscription</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="saison" class="form-label">Saison</label>
                    <input type="text" id="saison" class="form-control" placeholder="Ex: 2024-2025" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="type_certificat_medical" class="form-label">Type Certificat Médical</label>
                    <select id="type_certificat_medical" class="form-control" disabled>
                        <option value="">Sélectionner</option>
                        <option value="Compétition">Compétition</option>
                        <option value="Pratique">Pratique</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="type_licence" class="form-label">Type Licence</label>
                    <select id="type_licence" class="form-control" disabled>
                        <option value="">Sélectionner</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="L">L</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="creation_renouvellement" class="form-label">Création/Renouvellement</label>
                    <input type="text" id="creation_renouvellement" class="form-control" readonly>
                </div>
            </div>
            
            ${departsHtml}
            
            <h6 class="mt-4 mb-3">Classification et équipement</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="categorie_classement" class="form-label">Catégorie de classement</label>
                    <select id="categorie_classement" class="form-control">
                        <option value="">Sélectionner une catégorie</option>
                        ${typeof categoriesClassement !== 'undefined' && categoriesClassement && categoriesClassement.length > 0
                            ? categoriesClassement.map(cat => {
                                const abv = cat.abv_categorie_classement || '';
                                const libelle = cat.lb_categorie_classement || '';
                                return `<option value="${abv}">${libelle} (${abv})</option>`;
                            }).join('')
                            : ''
                        }
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="arme" class="form-label">Arme (utilisée sur le pas de tir)</label>
                    <select id="arme" class="form-control">
                        <option value="">Sélectionner</option>
                        ${typeof arcs !== 'undefined' && arcs && arcs.length > 0
                            ? arcs.map(arc => {
                                const libelle = arc.lb_arc || '';
                                return `<option value="${libelle}">${libelle}</option>`;
                            }).join('')
                            : ''
                        }
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" id="mobilite_reduite" class="form-check-input">
                        <label for="mobilite_reduite" class="form-check-label">Mobilité réduite</label>
                    </div>
                </div>
                ${typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne ? 
                    // Pour les disciplines 3D, Nature et Campagne : afficher Piquet
                    `<div class="col-md-3 mb-3">
                        <label for="piquet" class="form-label">Piquet</label>
                        <select id="piquet" name="piquet" class="form-control">
                            <option value="">Sélectionner</option>
                            <option value="rouge">Rouge</option>
                            <option value="bleu">Bleu</option>
                            <option value="blanc">Blanc</option>
                        </select>
                    </div>` :
                    // Pour les autres disciplines : afficher Distance
                    `<div class="col-md-3 mb-3">
                        <label for="distance" class="form-label">Distance</label>
                        <select id="distance" class="form-control">
                            <option value="">Sélectionner</option>
                            ${typeof distancesTir !== 'undefined' && distancesTir && distancesTir.length > 0 ? distancesTir.map(distance => 
                                `<option value="${distance.distance_valeur || distance.valeur || ''}">${distance.lb_distance || distance.name || distance.nom || ''}</option>`
                            ).join('') : ''}
                        </select>
                    </div>`
                }
                <div class="col-md-3 mb-3">
                    <label for="numero_tir" class="form-label">N° Tir</label>
                    <select id="numero_tir" class="form-control">
                        <option value="">Sélectionner</option>
                        ${concoursNombreDepart && concoursNombreDepart > 0 ? 
                            Array.from({length: parseInt(concoursNombreDepart)}, (_, i) => 
                                `<option value="${i + 1}">${i + 1}</option>`
                            ).join('') : ''}
                    </select>
                </div>
                ${typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne ? 
                    // Pas de champ Blason pour les disciplines 3D, Nature et Campagne
                    '' :
                    // Pour les autres disciplines : afficher Blason
                    `<div class="col-md-3 mb-3">
                        <label for="blason" class="form-label">Blason</label>
                        <input type="number" id="blason" class="form-control" min="0" placeholder="Ex: 40">
                    </div>`
                }
            </div>
            
            ${typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne ? 
                // Les champs Duel et Trispot n'existent pas pour les disciplines 3D, Nature et Campagne
                '' :
                `<div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="duel" class="form-check-input">
                            <label for="duel" class="form-check-label">Duel</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="trispot" class="form-check-input">
                            <label for="trispot" class="form-check-label">Trispot</label>
                        </div>
                    </div>
                </div>`
            }
            
            <h6 class="mt-4 mb-3">Paiement</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tarif_competition" class="form-label">Tarif Compétition</label>
                    <select id="tarif_competition" class="form-control">
                        <option value="">Sélectionner</option>
                        <option value="Tarif standard">Tarif standard</option>
                        <option value="Tarif réduit">Tarif réduit</option>
                        <option value="Tarif jeune">Tarif jeune</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mode_paiement" class="form-label">Mode Paiement</label>
                    <select id="mode_paiement" class="form-control">
                        <option value="Non payé">Non payé</option>
                        <option value="Espèces">Espèces</option>
                        <option value="Chèque">Chèque</option>
                        <option value="Carte bancaire">Carte bancaire</option>
                        <option value="Virement">Virement</option>
                    </select>
                </div>
            </div>
        </form>
    `;
    
    // Définir le contenu
    console.log('Définition du contenu de la modale...');
    modalBody.innerHTML = modalContent;
    console.log('Contenu défini, longueur:', modalBody.innerHTML.length);
    
    // FORCER le titre une dernière fois après avoir défini le contenu
    if (modalTitle) {
        modalTitle.textContent = 'Confirmer l\'inscription';
        modalTitle.innerHTML = 'Confirmer l\'inscription';
    }
    
    // Afficher avec Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modale affichée avec Bootstrap');
        
        // Fonction pour convertir le format XML vers le format base de données
        // Si H ou F n'est pas dans CATEGORIE, utilise SEXE (1=H, 2=F)
        // "D" (Dames) est converti en "F" (Femmes)
        const convertCategorieXmlToDb = (categorieXml, sexeXml = null) => {
            if (!categorieXml || categorieXml.length < 2) return categorieXml;
            
            // Normaliser "D" (Dames) en "F" (Femmes) dans la catégorie
            categorieXml = categorieXml.replace(/D$/i, 'F');
            
            // Vérifier si H ou F est déjà présent dans la catégorie
            const hasSexe = /[HF]$/i.test(categorieXml);
            
            // Si pas de H/F dans CATEGORIE, utiliser SEXE du XML (1=H, 2=F)
            let sexe = '';
            if (!hasSexe && sexeXml) {
                const sexeNum = parseInt(sexeXml);
                if (sexeNum === 1) {
                    sexe = 'H';
                } else if (sexeNum === 2) {
                    sexe = 'F';
                }
                console.log('showConfirmModal - SEXE utilisé depuis XML:', sexeXml, '->', sexe);
            } else if (hasSexe) {
                // Extraire H ou F de la catégorie
                const sexeMatch = categorieXml.match(/([HF])$/i);
                if (sexeMatch) {
                    sexe = sexeMatch[1].toUpperCase();
                }
            }
            
            // Patterns de conversion connus (avec H/F déjà présent)
            const conversions = {
                // Arc à poulies (CO)
                'COS3H': 'S3HCO', 'COS3F': 'S3FCO', 'COS3D': 'S3FCO', // D = Dames = F
                'COS2H': 'S2HCO', 'COS2F': 'S2FCO', 'COS2D': 'S2FCO',
                'COS1H': 'S1HCO', 'COS1F': 'S1FCO', 'COS1D': 'S1FCO',
                'COU21H': 'U21HCO', 'COU21F': 'U21FCO', 'COU21D': 'U21FCO',
                'COU18H': 'U18HCO', 'COU18F': 'U18FCO', 'COU18D': 'U18FCO',
                'COU15H': 'U15HCO', 'COU15F': 'U15FCO', 'COU15D': 'U15FCO',
                'COU13H': 'U13HCO', 'COU13F': 'U13FCO', 'COU13D': 'U13FCO',
                'COU11H': 'U11HCO', 'COU11F': 'U11FCO', 'COU11D': 'U11FCO',
                // Arc classique (CL)
                'CLS3H': 'S3HCL', 'CLS3F': 'S3FCL', 'CLS3D': 'S3FCL', // D = Dames = F
                'CLS2H': 'S2HCL', 'CLS2F': 'S2FCL', 'CLS2D': 'S2FCL',
                'CLS1H': 'S1HCL', 'CLS1F': 'S1FCL', 'CLS1D': 'S1FCL',
                'CLU21H': 'U21HCL', 'CLU21F': 'U21FCL', 'CLU21D': 'U21FCL',
                'CLU18H': 'U18HCL', 'CLU18F': 'U18FCL', 'CLU18D': 'U18FCL',
                'CLU15H': 'U15HCL', 'CLU15F': 'U15FCL', 'CLU15D': 'U15FCL',
                'CLU13H': 'U13HCL', 'CLU13F': 'U13FCL', 'CLU13D': 'U13FCL',
                'CLU11H': 'U11HCL', 'CLU11F': 'U11FCL', 'CLU11D': 'U11FCL',
            };
            
            if (conversions[categorieXml]) {
                return conversions[categorieXml];
            }
            
            // Si on a un sexe (depuis CATEGORIE ou SEXE), construire la catégorie complète
            if (sexe) {
                // Pattern: CO + [Catégorie] (sans H/F à la fin) -> [Catégorie] + [Sexe] + CO
                // Exemple: "COS3" + "H" -> "S3HCO"
                const patternCO = /^CO(U11|U13|U15|U18|U21|S1|S2|S3)$/i;
                const matchCO = categorieXml.match(patternCO);
                if (matchCO) {
                    const categorie = matchCO[1].toUpperCase();
                    return categorie + sexe + 'CO'; // Format: S3HCO
                }
                
                // Pattern: CL + [Catégorie] (sans H/F à la fin) -> [Catégorie] + [Sexe] + CL
                // Exemple: "CLU15" + "F" -> "U15FCL"
                const patternCL = /^CL(U11|U13|U15|U18|U21|S1|S2|S3)$/i;
                const matchCL = categorieXml.match(patternCL);
                if (matchCL) {
                    const categorie = matchCL[1].toUpperCase();
                    return categorie + sexe + 'CL'; // Format: U15FCL
                }
                
                // Pattern: CO + [Catégorie] + [H|F|D] (déjà présent, D = Dames = F)
                const patternWithSexeCO = /^CO(U11|U13|U15|U18|U21|S1|S2|S3)([HFD])$/i;
                const matchWithSexeCO = categorieXml.match(patternWithSexeCO);
                if (matchWithSexeCO) {
                    const categorie = matchWithSexeCO[1].toUpperCase();
                    let sexeFromCat = matchWithSexeCO[2].toUpperCase();
                    // Convertir D (Dames) en F (Femmes)
                    if (sexeFromCat === 'D') {
                        sexeFromCat = 'F';
                    }
                    return categorie + sexeFromCat + 'CO'; // Format: S3HCO
                }
                
                // Pattern: CL + [Catégorie] + [H|F|D] (déjà présent, D = Dames = F)
                const patternWithSexeCL = /^CL(U11|U13|U15|U18|U21|S1|S2|S3)([HFD])$/i;
                const matchWithSexeCL = categorieXml.match(patternWithSexeCL);
                if (matchWithSexeCL) {
                    const categorie = matchWithSexeCL[1].toUpperCase();
                    let sexeFromCat = matchWithSexeCL[2].toUpperCase();
                    // Convertir D (Dames) en F (Femmes)
                    if (sexeFromCat === 'D') {
                        sexeFromCat = 'F';
                    }
                    return categorie + sexeFromCat + 'CL'; // Format: U15FCL
                }
            }
            
            return categorieXml;
        };
        
        // Fonction pour pré-remplir les champs catégorie et arme
        const prefillCategorieAndArme = () => {
            // Pré-remplir la catégorie de classement depuis CATEGORIE (correspond à abv_categorie_classement)
            const categorieSelect = document.getElementById('categorie_classement');
            if (categorieSelect) {
                let categorieXml = (archer.categorie || archer.CATEGORIE || '').trim().toUpperCase();
                const sexeXml = (archer.sexe || archer.SEXE || '').trim();
                console.log('showConfirmModal - Tentative de pré-remplissage catégorie. Valeur XML originale:', categorieXml, 'SEXE XML:', sexeXml);
                
                // Convertir le format XML si nécessaire (en passant SEXE si H/F n'est pas dans CATEGORIE)
                const categorieConvertie = convertCategorieXmlToDb(categorieXml, sexeXml);
                if (categorieConvertie !== categorieXml) {
                    console.log('showConfirmModal - Conversion format XML -> DB:', categorieXml, '->', categorieConvertie);
                    categorieXml = categorieConvertie;
                }
                
                if (categorieXml && typeof categoriesClassement !== 'undefined' && categoriesClassement && categoriesClassement.length > 0) {
                    // La valeur CATEGORIE du XML correspond directement à abv_categorie_classement
                    const categorieFound = categoriesClassement.find(cat => {
                        const abv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                        return abv === categorieXml;
                    });
                    
                    if (categorieFound) {
                        const valueToSet = (categorieFound.abv_categorie_classement || '').trim();
                        // Vérifier que l'option existe dans le select
                        const optionExists = Array.from(categorieSelect.options).some(opt => opt.value === valueToSet);
                        if (optionExists) {
                            categorieSelect.value = valueToSet;
                            console.log('✓ showConfirmModal - Catégorie pré-remplie:', valueToSet, '(depuis XML CATEGORIE convertie:', categorieXml, ')');
                            console.log('Valeur du select après assignation:', categorieSelect.value);
                            
                            // Déclencher immédiatement l'événement change pour remplir automatiquement la distance et le blason
                            // Utiliser plusieurs tentatives avec des délais pour s'assurer que les listeners sont attachés
                            const triggerDistanceAndBlasonFill = (attempt = 1, maxAttempts = 5) => {
                                const delay = attempt * 200; // 200ms, 400ms, 600ms, 800ms, 1000ms
                                setTimeout(() => {
                                    console.log(`[Tentative ${attempt}/${maxAttempts}] Déclenchement remplissage distance/blason pour catégorie: ${valueToSet}`);
                                    console.log('  - concoursDiscipline:', typeof concoursDiscipline !== 'undefined' ? concoursDiscipline : 'undefined');
                                    console.log('  - concoursTypeCompetition:', typeof concoursTypeCompetition !== 'undefined' ? concoursTypeCompetition : 'undefined');
                                    
                                    // Vérifier que les variables sont disponibles
                                    if (typeof concoursDiscipline !== 'undefined' && 
                                        concoursDiscipline !== null &&
                                        typeof concoursTypeCompetition !== 'undefined' && 
                                        concoursTypeCompetition !== null) {
                                        
                                        // Essayer d'abord d'appeler directement la fonction si elle existe
                                        if (typeof fillDistanceAndBlasonFromCategorie === 'function') {
                                            console.log('  → Appel direct de fillDistanceAndBlasonFromCategorie');
                                            fillDistanceAndBlasonFromCategorie(valueToSet);
                                        } else {
                                            console.log('  → fillDistanceAndBlasonFromCategorie non disponible, déclenchement événement change');
                                        }
                                        
                                        // Toujours déclencher l'événement change pour s'assurer que le listener est appelé
                                        try {
                                            const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                                            categorieSelect.dispatchEvent(changeEvent);
                                            console.log('  ✓ Événement change déclenché sur la catégorie');
                                        } catch (error) {
                                            console.error('  ✗ Erreur lors du déclenchement de l\'événement change:', error);
                                        }
                                    } else if (attempt < maxAttempts) {
                                        console.warn(`  ✗ Variables non disponibles, nouvelle tentative dans ${delay}ms...`);
                                        triggerDistanceAndBlasonFill(attempt + 1, maxAttempts);
                                    } else {
                                        console.error('  ✗ Impossible de remplir distance et blason: variables globales non disponibles après', maxAttempts, 'tentatives');
                                    }
                                }, delay);
                            };
                            
                            // Démarrer les tentatives
                            triggerDistanceAndBlasonFill(1);
                        } else {
                            console.warn('✗ showConfirmModal - Option non trouvée dans le select. Valeur recherchée:', valueToSet);
                        }
                    } else {
                        console.warn('✗ showConfirmModal - Catégorie XML non trouvée. Valeur XML (après conversion):', categorieXml);
                    }
                }
            }
            
            // Pré-remplir l'arme depuis TYPARC
            const armeSelect = document.getElementById('arme');
            if (armeSelect) {
                const typarcXml = (archer.typarc || archer.TYPARC || '').trim();
                if (typarcXml && typeof arcs !== 'undefined' && arcs && arcs.length > 0) {
                    const idarc = parseInt(typarcXml);
                    if (!isNaN(idarc)) {
                        const arcFound = arcs.find(arc => {
                            const arcIdarc = parseInt(arc.idarc || 0);
                            return arcIdarc === idarc;
                        });
                        
                        if (arcFound) {
                            armeSelect.value = arcFound.lb_arc || '';
                            console.log('✓ showConfirmModal - Arme pré-remplie:', arcFound.lb_arc);
                        }
                    }
                }
            }
        };
        
        // Pré-remplir les champs depuis le XML (saison, type licence, type certificat médical)
        const saison = (archer.saison || archer.ABREV || '').trim();
        const typeLicence = (archer.type_licence || '').trim();
        // Type de certificat médical depuis le champ XML <certificat_medical>
        const typeCertificatMedical = (archer.type_certificat_medical || archer.certificat_medical || '').trim();
        
        // Fonction pour pré-remplir les champs verrouillés
        const prefillLockedFields = () => {
            // Pré-remplir la saison
            const saisonInput = document.getElementById('saison');
            if (saisonInput && saison) {
                saisonInput.value = saison;
                console.log('showConfirmModal - Saison pré-remplie:', saison);
            }
            
            // Pré-remplir le type de licence
            const typeLicenceSelect = document.getElementById('type_licence');
            if (typeLicenceSelect && typeLicence) {
                const cleanedTypeLicence = typeLicence.trim().toUpperCase();
                const firstLetter = cleanedTypeLicence.length > 0 ? cleanedTypeLicence[0] : '';
                const options = typeLicenceSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value === firstLetter) {
                        typeLicenceSelect.value = options[i].value;
                        typeLicenceSelect.disabled = false; // Activer temporairement
                        typeLicenceSelect.disabled = true; // Re-désactiver
                        console.log('showConfirmModal - Type licence pré-rempli:', options[i].value);
                        break;
                    }
                }
            }
            
            // Pré-remplir le type de certificat médical
            const typeCertificatMedicalSelect = document.getElementById('type_certificat_medical');
            if (typeCertificatMedicalSelect && typeCertificatMedical) {
                const normalizedValue = typeCertificatMedical.trim();
                const options = typeCertificatMedicalSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value.toLowerCase() === normalizedValue.toLowerCase() || 
                        options[i].text.toLowerCase() === normalizedValue.toLowerCase()) {
                        typeCertificatMedicalSelect.value = options[i].value;
                        typeCertificatMedicalSelect.disabled = false; // Activer temporairement
                        typeCertificatMedicalSelect.disabled = true; // Re-désactiver
                        console.log('showConfirmModal - Type certificat médical pré-rempli:', options[i].value);
                        break;
                    }
                }
            }
            
            // Pré-remplir le champ création/renouvellement
            const creationRenouvellement = (archer.creation_renouvellement || archer.Creation_renouvellement || '').trim();
            const creationRenouvellementInput = document.getElementById('creation_renouvellement');
            if (creationRenouvellementInput && creationRenouvellement) {
                creationRenouvellementInput.value = creationRenouvellement;
                console.log('showConfirmModal - Création/Renouvellement pré-rempli:', creationRenouvellement);
            }
        };
        
        // Pré-remplir après que la modale soit affichée
        setTimeout(() => {
            prefillLockedFields();
            prefillCategorieAndArme();
        }, 100);
        
        // Écouter l'événement 'shown.bs.modal' pour s'assurer que la modale est complètement affichée
        modalElement.addEventListener('shown.bs.modal', function() {
            prefillLockedFields();
            prefillCategorieAndArme();
            
            // Après le pré-remplissage, essayer de remplir la distance et le blason
            setTimeout(() => {
                console.log('=== shown.bs.modal - Tentative de remplissage distance et blason ===');
                const categorieSelect = document.getElementById('categorie_classement');
                if (categorieSelect && categorieSelect.value) {
                    console.log('Catégorie sélectionnée:', categorieSelect.value);
                    console.log('Appel de testAndForceFillDistanceBlason...');
                    testAndForceFillDistanceBlason();
                } else {
                    console.warn('Catégorie non sélectionnée ou select non trouvé');
                }
            }, 500);
            
            // Tentative supplémentaire après un délai plus long
            setTimeout(() => {
                console.log('=== Tentative supplémentaire après 1.5s ===');
                testAndForceFillDistanceBlason();
            }, 1500);
        }, { once: true });
    } else {
        console.error('Bootstrap n\'est pas disponible');
        alert('Erreur: Bootstrap n\'est pas chargé');
    }
};

// Fonction globale pour remplir automatiquement la distance et le blason selon la catégorie
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
            const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
            const distanceSelect = !isNature ? document.getElementById('distance') : null;
            const piquetSelect = isNature ? document.getElementById('piquet') : null;
            const blasonInput = !isNature ? document.getElementById('blason') : null;
            
            console.log('Éléments DOM:', {
                distanceSelect: !!distanceSelect,
                piquetSelect: !!piquetSelect,
                blasonInput: !!blasonInput,
                isNature: isNature
            });
            
            // Pour les disciplines 3D, Nature et Campagne, on ne remplit pas distance/blason
            if (isNature) {
                console.log('Discipline 3D/Nature/Campagne détectée - pas de remplissage automatique distance/blason');
                return;
            }
            
            if (!distanceSelect) {
                console.error('✗ Select de distance non trouvé dans le DOM');
                return;
            }
            
            if (!blasonInput) {
                console.error('✗ Input blason non trouvé dans le DOM');
            }
            
            // La réponse peut avoir une structure imbriquée : data.data.data (via ApiService)
            // ou directement : data.data (réponse directe)
            const responseData = data.data.data || data.data;
            
            const distanceValeur = responseData.distance_valeur;
            const blasonValeur = responseData.blason; // Le blason est maintenant inclus dans la réponse
            console.log('Données extraites:', {
                distanceValeur: distanceValeur,
                lb_distance: responseData.lb_distance,
                blasonValeur: blasonValeur,
                responseDataStructure: data.data.data ? 'nested' : 'direct'
            });
            
            // Sélectionner la distance correspondante
            console.log('Recherche de la distance', distanceValeur, 'dans le select...');
            console.log('Options disponibles:', Array.from(distanceSelect.options).map(opt => ({ value: opt.value, text: opt.text })));
            
            let distanceFound = false;
            for (let i = 0; i < distanceSelect.options.length; i++) {
                const optionValue = distanceSelect.options[i].value;
                if (optionValue == distanceValeur || optionValue === String(distanceValeur)) {
                    distanceSelect.value = optionValue;
                    distanceFound = true;
                    console.log('✓✓✓ Distance automatiquement sélectionnée:', responseData.lb_distance, '(valeur:', distanceValeur, ', option value:', optionValue, ')');
                    console.log('Valeur du select après assignation:', distanceSelect.value);
                    
                    // Remplir le blason si disponible dans la réponse
                    if (blasonInput) {
                        if (blasonValeur) {
                            blasonInput.value = blasonValeur;
                            console.log('✓✓✓ Blason automatiquement renseigné depuis la réponse API:', blasonValeur, 'cm');
                            console.log('Valeur du blason après assignation:', blasonInput.value);
                        } else if (concoursDiscipline && abvCategorie) {
                            // Fallback: récupérer le blason via l'API séparée si non inclus dans la réponse
                            console.log('Blason non inclus dans la réponse, récupération via API séparée...');
                            getBlasonFromAPI(concoursDiscipline, abvCategorie, distanceValeur)
                                .then(blason => {
                                    if (blason) {
                                        blasonInput.value = blason;
                                        console.log('✓✓✓ Blason récupéré via API séparée:', blason, 'cm');
                                    } else {
                                        console.warn('✗ Aucun blason trouvé pour cette combinaison');
                                    }
                                })
                                .catch(error => {
                                    console.error('Erreur lors de la récupération du blason:', error);
                                });
                        } else {
                            console.warn('Impossible de récupérer le blason - paramètres manquants');
                        }
                    } else {
                        console.error('✗ Input blason non trouvé dans le DOM');
                    }
                    
                    // Déclencher l'événement change sur le select de distance pour mettre à jour le blason
                    setTimeout(() => {
                        const changeEvent = new Event('change', { bubbles: true });
                        distanceSelect.dispatchEvent(changeEvent);
                        console.log('Événement change déclenché sur le select de distance');
                    }, 100);
                    
                    break;
                }
            }
            if (!distanceFound) {
                console.error('✗✗✗ Distance non trouvée dans le select!');
                console.error('Valeur recherchée:', distanceValeur, '(type:', typeof distanceValeur, ')');
                console.error('Options disponibles:', Array.from(distanceSelect.options).map(opt => ({ value: opt.value, text: opt.text, type: typeof opt.value })));
            }
        } else {
            console.log('✗ Aucune distance recommandée trouvée pour cette combinaison. Réponse:', data);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la récupération de la distance recommandée:', error);
    });
}

// Fonction globale pour récupérer le blason depuis la table concour_discipline_categorie
function getBlasonFromAPI(iddiscipline, abvCategorie, distance) {
    if (!iddiscipline || !abvCategorie || !distance) {
        console.log('Paramètres manquants pour récupérer le blason:', { iddiscipline, abvCategorie, distance });
        return Promise.resolve(null);
    }
    
    const params = new URLSearchParams({
        iddiscipline: iddiscipline,
        abv_categorie_classement: abvCategorie,
        distance: distance
    });
    
    return fetch(`/api/concours/blason-recommandee?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data && data.data.blason) {
            return data.data.blason;
        } else {
            console.log('Aucun blason trouvé dans la table concour_discipline_categorie pour cette combinaison');
            return null;
        }
    })
    .catch(error => {
        console.error('Erreur lors de la récupération du blason:', error);
        return null;
    });
}

// Fonction de test pour vérifier et forcer le remplissage
function testAndForceFillDistanceBlason() {
    console.log('=== TEST: Vérification du remplissage automatique ===');
    const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
    const categorieSelect = document.getElementById('categorie_classement');
    const distanceSelect = !isNature ? document.getElementById('distance') : null;
    const piquetSelect = isNature ? document.getElementById('piquet') : null;
    const blasonInput = !isNature ? document.getElementById('blason') : null;
    
    console.log('Éléments DOM:', {
        categorieSelect: !!categorieSelect,
        distanceSelect: !!distanceSelect,
        piquetSelect: !!piquetSelect,
        blasonInput: !!blasonInput,
        isNature: isNature,
        categorieValue: categorieSelect ? categorieSelect.value : 'N/A'
    });
    
    console.log('Variables globales:', {
        concoursDiscipline: typeof concoursDiscipline !== 'undefined' ? concoursDiscipline : 'undefined',
        concoursTypeCompetition: typeof concoursTypeCompetition !== 'undefined' ? concoursTypeCompetition : 'undefined'
    });
    
    // Pour les disciplines 3D, Nature et Campagne, on ne remplit pas distance/blason
    if (isNature) {
        console.log('Discipline 3D/Nature/Campagne détectée - pas de remplissage automatique distance/blason');
        return;
    }
    
    if (categorieSelect && categorieSelect.value && 
        typeof concoursDiscipline !== 'undefined' && concoursDiscipline !== null &&
        typeof concoursTypeCompetition !== 'undefined' && concoursTypeCompetition !== null) {
        console.log('→ Conditions OK, déclenchement du remplissage...');
        if (typeof fillDistanceAndBlasonFromCategorie === 'function') {
            fillDistanceAndBlasonFromCategorie(categorieSelect.value);
        } else {
            console.error('✗ fillDistanceAndBlasonFromCategorie n\'est pas une fonction');
        }
    } else {
        console.warn('✗ Conditions non remplies pour le remplissage automatique');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchType = document.getElementById('search-type');
    const btnSearch = document.getElementById('btn-search');
    const searchResults = document.getElementById('search-results');
    const resultsList = document.getElementById('results-list');
    

    // Recherche au clic sur le bouton
    if (btnSearch) {
        btnSearch.addEventListener('click', performSearch);
    }

    // Recherche avec Entrée
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
    
    // Synchroniser le select principal du départ avec celui de la modale
    const departSelectMain = document.getElementById('depart-select-main');
    const departSelectModal = document.getElementById('depart-select');
    
    if (departSelectMain && departSelectModal) {
        // Quand le select principal change, mettre à jour celui de la modale
        departSelectMain.addEventListener('change', function() {
            departSelectModal.value = this.value;
        });
        
        // Quand la modale s'ouvre, synchroniser avec le select principal
        const modalElement = document.getElementById('confirmInscriptionModal');
        if (modalElement) {
            modalElement.addEventListener('shown.bs.modal', function() {
                if (departSelectMain.value) {
                    departSelectModal.value = departSelectMain.value;
                }
            });
        }
    }

    // Bouton de confirmation d'inscription
    const btnConfirmInscription = document.getElementById('btn-confirm-inscription');
    if (btnConfirmInscription) {
        btnConfirmInscription.addEventListener('click', function() {
            if (selectedArcher) {
                submitInscription();
            }
        });
    }
    
    // Délégation d'événements pour les cartes d'archers (plus fiable)
    // Utiliser le conteneur parent qui existe toujours
    const searchResultsContainer = document.getElementById('search-results');
    if (searchResultsContainer) {
        searchResultsContainer.addEventListener('click', function(e) {
            console.log('Clic détecté dans search-results, target:', e.target);
            // Trouver la carte parente
            const card = e.target.closest('.archer-card');
            if (card) {
                e.preventDefault();
                e.stopPropagation();
                const archerIndex = card.getAttribute('data-archer-index');
                console.log('Carte trouvée, index:', archerIndex);
                console.log('archersList disponible:', !!window.archersList);
                if (archerIndex !== null && window.archersList && window.archersList[archerIndex]) {
                    console.log('Clic sur carte via délégation, index:', archerIndex);
                    const archerData = window.archersList[archerIndex];
                    console.log('Données archer:', archerData);
                    selectArcher(archerData, card);
                } else {
                    console.error('Données archer introuvables pour index:', archerIndex);
                    console.error('archersList:', window.archersList);
                    alert('Erreur: Impossible de récupérer les données de l\'archer. Index: ' + archerIndex);
                }
            } else {
                console.log('Pas de carte trouvée pour le clic');
            }
        });
        console.log('Délégation d\'événements configurée sur search-results');
    } else {
        console.error('search-results container introuvable');
    }
    
    // Écouter le changement de distance pour renseigner automatiquement le blason (seulement pour les disciplines non-3D/Nature/Campagne)
    const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
    const distanceSelect = !isNature ? document.getElementById('distance') : null;
    if (distanceSelect && !isNature) {
        distanceSelect.addEventListener('change', function() {
            const distance = this.value;
            const blasonInput = document.getElementById('blason');
            const categorieSelect = document.getElementById('categorie_classement');
            
            if (blasonInput && distance) {
                // Récupérer la catégorie et la discipline pour l'appel API
                const abvCategorie = categorieSelect ? categorieSelect.value : null;
                
                if (concoursDiscipline && abvCategorie) {
                    // Utiliser l'endpoint blason-recommandee pour récupérer le blason
                    console.log('Changement de distance détecté - récupération du blason pour:', {
                        iddiscipline: concoursDiscipline,
                        abv_categorie_classement: abvCategorie,
                        distance: distance
                    });
                    
                    const params = new URLSearchParams({
                        iddiscipline: concoursDiscipline,
                        abv_categorie_classement: abvCategorie,
                        distance: distance
                    });
                    
                    fetch(`/api/concours/blason-recommandee?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        credentials: 'include'
                    })
                    .then(response => {
                        console.log('Réponse blason-recommandee - status:', response.status);
                        console.log('Content-Type:', response.headers.get('Content-Type'));
                        
                        if (!response.ok) {
                            // Si 404, vérifier si c'est du JSON ou du HTML
                            if (response.status === 404) {
                                const contentType = response.headers.get('Content-Type') || '';
                                if (contentType.includes('application/json')) {
                                    // C'est du JSON, parser normalement
                                    return response.json().then(debugData => {
                                        console.error('✗✗✗ Endpoint blason-recommandee retourne 404');
                                        console.error('Informations de débogage:', debugData);
                                        if (debugData && debugData.debug) {
                                            console.error('Path exact blason:', debugData.debug.path_exact_blason);
                                            console.error('Path exact distance:', debugData.debug.path_exact_distance);
                                            console.error('Path reçu:', debugData.path);
                                        }
                                        // Essayer de récupérer le blason via distance-recommandee comme fallback
                                        console.warn('Tentative avec distance-recommandee...');
                                        return fetch(`/api/concours/distance-recommandee?${new URLSearchParams({
                                            iddiscipline: concoursDiscipline,
                                            idtype_competition: concoursTypeCompetition,
                                            abv_categorie_classement: abvCategorie,
                                            distance: distance
                                        }).toString()}`, {
                                            method: 'GET',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json'
                                            },
                                            credentials: 'include'
                                        }).then(response => {
                                            if (!response.ok && response.status === 404) {
                                                const contentType2 = response.headers.get('Content-Type') || '';
                                                if (contentType2.includes('application/json')) {
                                                    return response.json().then(debugData => {
                                                        console.error('✗✗✗ Endpoint distance-recommandee retourne aussi 404');
                                                        console.error('Informations de débogage distance-recommandee:', debugData);
                                                        throw new Error('Les deux endpoints retournent 404');
                                                    });
                                                } else {
                                                    return response.text().then(html => {
                                                        console.error('✗✗✗ Endpoint distance-recommandee retourne HTML au lieu de JSON');
                                                        console.error('Réponse HTML (premiers 500 caractères):', html.substring(0, 500));
                                                        throw new Error('Endpoint distance-recommandee retourne HTML (routage non fonctionnel)');
                                                    });
                                                }
                                            }
                                            return response;
                                        });
                                    });
                                } else {
                                    // C'est du HTML, le routage ne fonctionne pas
                                    return response.text().then(html => {
                                        console.error('✗✗✗✗✗ PROBLÈME DE ROUTAGE: Endpoint blason-recommandee retourne HTML au lieu de JSON');
                                        console.error('Cela signifie que le routage ne fonctionne pas - l\'endpoint n\'est pas atteint');
                                        console.error('Réponse HTML (premiers 500 caractères):', html.substring(0, 500));
                                        console.error('URL appelée:', `/api/concours/blason-recommandee?${params.toString()}`);
                                        throw new Error('Endpoint blason-recommandee retourne HTML (routage non fonctionnel)');
                                    });
                                }
                            }
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Si data est une Response (fallback), la traiter
                        if (data instanceof Response) {
                            return data.json().then(jsonData => {
                                console.log('Données reçues pour blason (via distance-recommandee):', jsonData);
                                
                                // La réponse peut avoir une structure imbriquée : data.data.data.blason (via ApiService)
                                // ou directement : data.data.blason (réponse directe)
                                let blasonValeur = null;
                                if (jsonData.success && jsonData.data) {
                                    // Vérifier d'abord la structure imbriquée (via ApiService)
                                    if (jsonData.data.data && jsonData.data.data.blason) {
                                        blasonValeur = jsonData.data.data.blason;
                                    }
                                    // Sinon vérifier la structure directe
                                    else if (jsonData.data.blason) {
                                        blasonValeur = jsonData.data.blason;
                                    }
                                }
                                
                                if (blasonValeur) {
                                    blasonInput.value = blasonValeur;
                                    console.log('✓✓✓ Blason automatiquement renseigné:', blasonValeur, 'cm pour distance', distance, 'm');
                                } else {
                                    console.warn('Aucun blason trouvé dans la réponse pour cette distance');
                                }
                            });
                        }
                        // Sinon, traiter les données normalement
                        console.log('=== Données reçues pour blason ===');
                        console.log('Données complètes:', JSON.stringify(data, null, 2));
                        
                        // La réponse peut avoir une structure imbriquée : data.data.data.blason (via ApiService)
                        // ou directement : data.data.blason (réponse directe)
                        let blasonValeur = null;
                        if (data.success && data.data) {
                            // Vérifier d'abord la structure imbriquée (via ApiService)
                            if (data.data.data && data.data.data.blason) {
                                blasonValeur = data.data.data.blason;
                            }
                            // Sinon vérifier la structure directe
                            else if (data.data.blason) {
                                blasonValeur = data.data.blason;
                            }
                        }
                        
                        if (blasonValeur) {
                            blasonInput.value = blasonValeur;
                            console.log('✓✓✓ Blason automatiquement renseigné:', blasonValeur, 'cm pour distance', distance, 'm');
                        } else {
                            console.warn('Aucun blason trouvé dans la réponse pour cette distance');
                            console.warn('Structure de la réponse:', {
                                hasSuccess: !!data.success,
                                hasData: !!data.data,
                                hasNestedData: !!(data.data && data.data.data),
                                hasBlasonNested: !!(data.data && data.data.data && data.data.data.blason),
                                hasBlasonDirect: !!(data.data && data.data.blason),
                                dataKeys: data.data ? Object.keys(data.data) : 'no data'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la récupération du blason:', error);
                    });
                } else {
                    console.log('Discipline, type compétition ou catégorie manquante pour récupérer le blason:', { 
                        discipline: concoursDiscipline,
                        typeCompetition: concoursTypeCompetition,
                        categorie: abvCategorie 
                    });
                }
            }
        });
    }
    
    // Écouter le changement de catégorie pour sélectionner automatiquement la distance
    const categorieSelect = document.getElementById('categorie_classement');
    if (categorieSelect) {
        console.log('✓ Listener change attaché sur le select de catégorie');
        categorieSelect.addEventListener('change', function() {
            const abvCategorie = this.value;
            console.log('=== Événement change sur catégorie déclenché ===');
            console.log('Valeur catégorie:', abvCategorie);
            console.log('Appel de fillDistanceAndBlasonFromCategorie...');
            fillDistanceAndBlasonFromCategorie(abvCategorie);
        });
    } else {
        console.error('✗ Select de catégorie non trouvé dans le DOM');
    }
});

// Fonction de recherche
function performSearch() {
    const searchInput = document.getElementById('search-input');
    const searchType = document.getElementById('search-type');
    const searchResults = document.getElementById('search-results');
    const resultsList = document.getElementById('results-list');

    const query = searchInput.value.trim();
    const type = searchType.value;

    if (!query) {
        alert('Veuillez entrer un numéro de licence ou un nom');
        return;
    }

    // Afficher un indicateur de chargement
    resultsList.innerHTML = '<p>Recherche en cours...</p>';
    searchResults.style.display = 'block';

    // Appel API pour rechercher
    const searchParam = type === 'licence' ? 'licence' : 'nom';
    const url = `/api/archers/search?${searchParam}=${encodeURIComponent(query)}`;
    console.log('Recherche d\'archer - URL:', url);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => {
        console.log('Réponse HTTP:', response.status, response.statusText);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Erreur HTTP - Réponse:', text);
                throw new Error('Erreur HTTP: ' + response.status + ' - ' + text);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Réponse de la recherche:', data);
        if (data.success && data.archers && data.archers.length > 0) {
            displaySearchResults(data.archers);
        } else {
            resultsList.innerHTML = '<p class="alert alert-warning">Aucun archer trouvé.</p>';
        }
    })
    .catch(error => {
        console.error('Erreur lors de la recherche:', error);
        resultsList.innerHTML = '<p class="alert alert-danger">Erreur lors de la recherche: ' + error.message + '</p>';
    });
}

// Afficher les résultats de recherche
function displaySearchResults(archers) {
    const resultsList = document.getElementById('results-list');
    if (!resultsList) {
        console.error('displaySearchResults: results-list introuvable');
        return;
    }
    
    resultsList.innerHTML = '';

    // Initialiser le tableau global pour stocker les archers
    if (!window.archersList) {
        window.archersList = [];
    }
    window.archersList = []; // Réinitialiser à chaque nouvelle recherche

    archers.forEach((archer, index) => {
        // IMPORTANT: Les données viennent du XML, le club est dans "CIE"
        const nom = archer.nom || archer.name || archer.NOM || 'N/A';
        const prenom = archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A';
        const licence = archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A';
        // Le XML retourne le club dans club_name (qui vient de CIE), CIE, ou CLUB
        const club = archer.club_name || archer.CIE || archer.CLUB || archer.clubName || 'N/A';
        const dateNaissance = archer.birth_date || archer.birthDate || archer.DATENAISSANCE || 'N/A';
        const genre = archer.gender || archer.GENRE || 'N/A';

        // Stocker l'archer dans le tableau global AVANT de créer la carte
        window.archersList[index] = archer;
        console.log('Archer stocké index', index, ':', archer);

        // Créer la carte avec un data-attribute pour stocker l'index
        const card = document.createElement('div');
        card.className = 'archer-card';
        card.style.cursor = 'pointer';
        card.setAttribute('data-archer-index', index);
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');

        card.innerHTML = `
            <h4>${nom} ${prenom}</h4>
            <div class="archer-info">
                <div class="archer-info-item">
                    <strong>Numéro de licence:</strong>
                    <span>${licence}</span>
                </div>
                <div class="archer-info-item">
                    <strong>Club:</strong>
                    <span>${club}</span>
                </div>
                <div class="archer-info-item">
                    <strong>Date de naissance:</strong>
                    <span>${dateNaissance}</span>
                </div>
                <div class="archer-info-item">
                    <strong>Genre:</strong>
                    <span>${genre}</span>
                </div>
            </div>
        `;

        // L'événement est géré par délégation au niveau du conteneur parent
        // Pas besoin d'attacher directement ici

        resultsList.appendChild(card);
    });
    
    console.log('displaySearchResults: ' + archers.length + ' archers affichés');
    console.log('Archers stockés:', window.archersList);
}

// Sélectionner un archer
function selectArcher(archer, cardElement) {
    console.log('=== selectArcher appelé ===');
    console.log('Archer:', archer);
    console.log('CardElement:', cardElement);
    
    if (!archer) {
        console.error('selectArcher: archer est undefined');
        alert('Erreur: Aucune information d\'archer disponible');
        return;
    }
    
    if (!cardElement) {
        console.error('selectArcher: cardElement est undefined');
        return;
    }
    
    // Retirer la sélection précédente
    document.querySelectorAll('.archer-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Sélectionner la nouvelle carte
    cardElement.classList.add('selected');
    console.log('Carte sélectionnée visuellement');
    
    // S'assurer que selectedArcher est accessible globalement
    if (typeof window !== 'undefined') {
        window.selectedArcher = archer;
    }
    selectedArcher = archer;

    console.log('Archer sélectionné:', selectedArcher);
    console.log('ID archer:', selectedArcher.id || selectedArcher._id);

    // Afficher la modale de confirmation - METTRE À JOUR LES DONNÉES DANS LA MODALE
    const modalElement = document.getElementById('confirmInscriptionModal');
    if (!modalElement) {
        console.error('Modal introuvable');
        alert('Erreur: Modal introuvable');
        return;
    }
    
    // Extraire les données de l'archer
    // IMPORTANT: Les données viennent du XML, le club est dans "CIE"
    const nom = archer.nom || archer.name || archer.NOM || 'N/A';
    const prenom = archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A';
    const licence = archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A';
    // Le XML retourne le club dans club_name (qui vient de CIE), CIE, ou CLUB
    const club = archer.club_name || archer.CIE || archer.CLUB || archer.clubName || 'N/A';
    
    // Extraire les données pour le formulaire d'inscription
    const saison = (archer.saison || archer.ABREV || '').trim();
    const typeLicence = (archer.type_licence || '').trim();
    const creationRenouvellement = (archer.creation_renouvellement || archer.Creation_renouvellement || '').trim();
    // Type de certificat médical depuis le champ XML <certificat_medical>
    const typeCertificatMedical = (archer.type_certificat_medical || archer.certificat_medical || '').trim();
    
    console.log('Mise à jour des données dans la modale:', { nom, prenom, licence, club, saison, typeLicence, creationRenouvellement });
    console.log('Données archer complètes:', archer);
    console.log('Club depuis archer.club_name:', archer.club_name);
    console.log('Club depuis archer.CIE:', archer.CIE);
    console.log('Club depuis archer.CLUB:', archer.CLUB);
    
    // Mettre à jour les spans dans la modale
    const nomSpan = document.getElementById('modal-archer-nom');
    const prenomSpan = document.getElementById('modal-archer-prenom');
    const licenceSpan = document.getElementById('modal-archer-licence');
    const clubSpan = document.getElementById('modal-archer-club');
    
    if (nomSpan) {
        nomSpan.textContent = nom;
        console.log('Nom mis à jour:', nom);
    } else {
        console.error('Span modal-archer-nom introuvable');
    }
    
    if (prenomSpan) {
        prenomSpan.textContent = prenom;
        console.log('Prénom mis à jour:', prenom);
    } else {
        console.error('Span modal-archer-prenom introuvable');
    }
    
    if (licenceSpan) {
        licenceSpan.textContent = licence;
        console.log('Licence mise à jour:', licence);
    } else {
        console.error('Span modal-archer-licence introuvable');
    }
    
    if (clubSpan) {
        clubSpan.textContent = club;
        console.log('Club mis à jour:', club);
    } else {
        console.error('Span modal-archer-club introuvable');
    }
    
    // Pré-remplir les champs du formulaire d'inscription
    const saisonInput = document.getElementById('saison');
    if (saisonInput && saison) {
        saisonInput.value = saison;
        console.log('Saison pré-remplie:', saison);
    }
    
    const typeLicenceSelect = document.getElementById('type_licence');
    if (typeLicenceSelect && typeLicence) {
        // Nettoyer la valeur (enlever les espaces) et prendre la première lettre en majuscule
        const cleanedTypeLicence = typeLicence.trim().toUpperCase();
        const firstLetter = cleanedTypeLicence.length > 0 ? cleanedTypeLicence[0] : '';
        
        // Chercher une option qui correspond (A, B, C, L)
        const options = typeLicenceSelect.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === firstLetter) {
                typeLicenceSelect.value = options[i].value;
                typeLicenceSelect.disabled = false; // Activer temporairement pour définir la valeur
                typeLicenceSelect.disabled = true; // Re-désactiver
                console.log('Type licence pré-rempli:', options[i].value, '(depuis:', typeLicence, ')');
                break;
            }
        }
    }
    
    // Pré-remplir le type de certificat médical
    const typeCertificatMedicalSelect = document.getElementById('type_certificat_medical');
    if (typeCertificatMedicalSelect && typeCertificatMedical) {
        // Normaliser la valeur (Compétition, Pratique, Loisir, etc.)
        const normalizedValue = typeCertificatMedical.trim();
        const options = typeCertificatMedicalSelect.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value.toLowerCase() === normalizedValue.toLowerCase() || 
                options[i].text.toLowerCase() === normalizedValue.toLowerCase()) {
                typeCertificatMedicalSelect.value = options[i].value;
                typeCertificatMedicalSelect.disabled = false; // Activer temporairement pour définir la valeur
                typeCertificatMedicalSelect.disabled = true; // Re-désactiver
                console.log('Type certificat médical pré-rempli:', options[i].value, '(depuis XML:', typeCertificatMedical, ')');
                break;
            }
        }
    }
    
    const creationRenouvellementInput = document.getElementById('creation_renouvellement');
    if (creationRenouvellementInput && creationRenouvellement) {
        // Afficher la valeur depuis le XML (R pour Renouvellement, C pour Création, etc.)
        creationRenouvellementInput.value = creationRenouvellement;
        console.log('Création/Renouvellement pré-rempli:', creationRenouvellement);
    }
    
    // Fonction pour convertir le format XML vers le format base de données
    // Exemple: "COS3H" -> "S3HCO" (CO + S3 + H -> S3 + H + CO)
    // Si H ou F n'est pas dans CATEGORIE, utilise SEXE (1=H, 2=F)
    // "D" (Dames) est converti en "F" (Femmes)
    const convertCategorieXmlToDb = (categorieXml, sexeXml = null) => {
        if (!categorieXml || categorieXml.length < 2) return categorieXml;
        
        // Normaliser "D" (Dames) en "F" (Femmes) dans la catégorie
        categorieXml = categorieXml.replace(/D$/i, 'F');
        
        // Vérifier si H ou F est déjà présent dans la catégorie
        const hasSexe = /[HF]$/i.test(categorieXml);
        
        // Si pas de H/F dans CATEGORIE, utiliser SEXE du XML (1=H, 2=F)
        let sexe = '';
        if (!hasSexe && sexeXml) {
            const sexeNum = parseInt(sexeXml);
            if (sexeNum === 1) {
                sexe = 'H';
            } else if (sexeNum === 2) {
                sexe = 'F';
            }
            console.log('SEXE utilisé depuis XML:', sexeXml, '->', sexe);
        } else if (hasSexe) {
            // Extraire H ou F de la catégorie
            const sexeMatch = categorieXml.match(/([HF])$/i);
            if (sexeMatch) {
                sexe = sexeMatch[1].toUpperCase();
            }
        }
        
        // Patterns de conversion connus (avec H/F déjà présent)
        const conversions = {
            // Arc à poulies (CO)
            'COS3H': 'S3HCO', 'COS3F': 'S3FCO', 'COS3D': 'S3FCO', // D = Dames = F
            'COS2H': 'S2HCO', 'COS2F': 'S2FCO', 'COS2D': 'S2FCO',
            'COS1H': 'S1HCO', 'COS1F': 'S1FCO', 'COS1D': 'S1FCO',
            'COU21H': 'U21HCO', 'COU21F': 'U21FCO', 'COU21D': 'U21FCO',
            'COU18H': 'U18HCO', 'COU18F': 'U18FCO', 'COU18D': 'U18FCO',
            'COU15H': 'U15HCO', 'COU15F': 'U15FCO', 'COU15D': 'U15FCO',
            'COU13H': 'U13HCO', 'COU13F': 'U13FCO', 'COU13D': 'U13FCO',
            'COU11H': 'U11HCO', 'COU11F': 'U11FCO', 'COU11D': 'U11FCO',
            // Arc classique (CL)
            'CLS3H': 'S3HCL', 'CLS3F': 'S3FCL', 'CLS3D': 'S3FCL', // D = Dames = F
            'CLS2H': 'S2HCL', 'CLS2F': 'S2FCL', 'CLS2D': 'S2FCL',
            'CLS1H': 'S1HCL', 'CLS1F': 'S1FCL', 'CLS1D': 'S1FCL',
            'CLU21H': 'U21HCL', 'CLU21F': 'U21FCL', 'CLU21D': 'U21FCL',
            'CLU18H': 'U18HCL', 'CLU18F': 'U18FCL', 'CLU18D': 'U18FCL',
            'CLU15H': 'U15HCL', 'CLU15F': 'U15FCL', 'CLU15D': 'U15FCL',
            'CLU13H': 'U13HCL', 'CLU13F': 'U13FCL', 'CLU13D': 'U13FCL',
            'CLU11H': 'U11HCL', 'CLU11F': 'U11FCL', 'CLU11D': 'U11FCL',
        };
        
        // Vérifier d'abord les conversions directes
        if (conversions[categorieXml]) {
            return conversions[categorieXml];
        }
        
        // Si on a un sexe (depuis CATEGORIE ou SEXE), construire la catégorie complète
        if (sexe) {
            // Pattern: CO + [Catégorie] (sans H/F à la fin) -> [Catégorie] + [Sexe] + CO
            // Exemple: "COS3" + "H" -> "S3HCO"
            const patternCO = /^CO(U11|U13|U15|U18|U21|S1|S2|S3)$/i;
            const matchCO = categorieXml.match(patternCO);
            if (matchCO) {
                const categorie = matchCO[1].toUpperCase();
                return categorie + sexe + 'CO'; // Format: S3HCO
            }
            
            // Pattern: CL + [Catégorie] (sans H/F à la fin) -> [Catégorie] + [Sexe] + CL
            // Exemple: "CLU15" + "F" -> "U15FCL"
            const patternCL = /^CL(U11|U13|U15|U18|U21|S1|S2|S3)$/i;
            const matchCL = categorieXml.match(patternCL);
            if (matchCL) {
                const categorie = matchCL[1].toUpperCase();
                return categorie + sexe + 'CL'; // Format: U15FCL
            }
            
            // Pattern: CO + [Catégorie] + [H|F|D] (déjà présent, D = Dames = F)
            const patternWithSexeCO = /^CO(U11|U13|U15|U18|U21|S1|S2|S3)([HFD])$/i;
            const matchWithSexeCO = categorieXml.match(patternWithSexeCO);
            if (matchWithSexeCO) {
                const categorie = matchWithSexeCO[1].toUpperCase();
                let sexeFromCat = matchWithSexeCO[2].toUpperCase();
                // Convertir D (Dames) en F (Femmes)
                if (sexeFromCat === 'D') {
                    sexeFromCat = 'F';
                }
                return categorie + sexeFromCat + 'CO'; // Format: S3HCO
            }
            
            // Pattern: CL + [Catégorie] + [H|F|D] (déjà présent, D = Dames = F)
            const patternWithSexeCL = /^CL(U11|U13|U15|U18|U21|S1|S2|S3)([HFD])$/i;
            const matchWithSexeCL = categorieXml.match(patternWithSexeCL);
            if (matchWithSexeCL) {
                const categorie = matchWithSexeCL[1].toUpperCase();
                let sexeFromCat = matchWithSexeCL[2].toUpperCase();
                // Convertir D (Dames) en F (Femmes)
                if (sexeFromCat === 'D') {
                    sexeFromCat = 'F';
                }
                return categorie + sexeFromCat + 'CL'; // Format: U15FCL
            }
        }
        
        // Si aucune conversion trouvée, retourner la valeur originale
        return categorieXml;
    };
    
    // Fonction pour pré-remplir les champs catégorie et arme
    const prefillCategorieAndArme = () => {
        // Pré-remplir la catégorie de classement depuis CATEGORIE (correspond à abv_categorie_classement)
        const categorieSelect = document.getElementById('categorie_classement');
        if (categorieSelect) {
            let categorieXml = (archer.categorie || archer.CATEGORIE || '').trim().toUpperCase();
            const sexeXml = (archer.sexe || archer.SEXE || '').trim();
            console.log('Tentative de pré-remplissage catégorie. Valeur XML originale:', categorieXml, 'SEXE XML:', sexeXml);
            
            // Convertir le format XML si nécessaire (en passant SEXE si H/F n'est pas dans CATEGORIE)
            const categorieConvertie = convertCategorieXmlToDb(categorieXml, sexeXml);
            if (categorieConvertie !== categorieXml) {
                console.log('Conversion format XML -> DB:', categorieXml, '->', categorieConvertie);
                categorieXml = categorieConvertie;
            }
            
            console.log('CategoriesClassement disponible:', typeof categoriesClassement !== 'undefined', 'Count:', typeof categoriesClassement !== 'undefined' ? categoriesClassement.length : 0);
            
            if (categorieXml && typeof categoriesClassement !== 'undefined' && categoriesClassement && categoriesClassement.length > 0) {
                // La valeur CATEGORIE du XML correspond directement à abv_categorie_classement
                const categorieFound = categoriesClassement.find(cat => {
                    const abv = (cat.abv_categorie_classement || '').trim().toUpperCase();
                    return abv === categorieXml;
                });
                
                if (categorieFound) {
                    const valueToSet = (categorieFound.abv_categorie_classement || '').trim();
                    // Vérifier que l'option existe dans le select
                    const optionExists = Array.from(categorieSelect.options).some(opt => opt.value === valueToSet);
                    if (optionExists) {
                        categorieSelect.value = valueToSet;
                        console.log('✓ Catégorie pré-remplie avec succès:', valueToSet, '(depuis XML CATEGORIE convertie:', categorieXml, ')');
                        console.log('Valeur du select après assignation:', categorieSelect.value);
                    } else {
                        console.warn('✗ Option non trouvée dans le select. Valeur recherchée:', valueToSet);
                        console.log('Options disponibles:', Array.from(categorieSelect.options).map(opt => opt.value).slice(0, 10).join(', '));
                    }
                } else {
                    console.warn('✗ Catégorie XML non trouvée. Valeur XML (après conversion):', categorieXml);
                    console.log('Premières catégories disponibles:', categoriesClassement.slice(0, 10).map(c => c.abv_categorie_classement).join(', '));
                }
            } else {
                console.warn('Impossible de pré-remplir la catégorie. categorieXml:', categorieXml, 'categoriesClassement:', typeof categoriesClassement);
            }
        } else {
            console.warn('Select categorie_classement introuvable dans le DOM');
        }
        
        // Pré-remplir l'arme depuis TYPARC (idarc) -> mapper vers lb_arc
        const armeSelect = document.getElementById('arme');
        if (armeSelect) {
            const typarcXml = (archer.typarc || archer.TYPARC || '').trim();
            if (typarcXml && typeof arcs !== 'undefined' && arcs && arcs.length > 0) {
                // TYPARC contient l'idarc, chercher l'arc correspondant
                const idarc = parseInt(typarcXml);
                if (!isNaN(idarc)) {
                    const arcFound = arcs.find(arc => {
                        const arcIdarc = parseInt(arc.idarc || 0);
                        return arcIdarc === idarc;
                    });
                    
                    if (arcFound) {
                        armeSelect.value = arcFound.lb_arc || '';
                        console.log('✓ Arme pré-remplie avec succès:', arcFound.lb_arc, '(depuis XML TYPARC:', typarcXml, 'idarc:', idarc, ')');
                    } else {
                        console.warn('✗ Arc non trouvé pour TYPARC:', typarcXml, 'idarc:', idarc);
                    }
                }
            }
        } else {
            console.warn('Select arme introuvable dans le DOM');
        }
    };
    
    // Pré-remplir immédiatement si le select existe déjà (formulaire statique)
    prefillCategorieAndArme();
    
    // Afficher la modale avec Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modale affichée');
        
        // Attendre que la modale soit complètement affichée avant de pré-remplir les champs
        // (nécessaire car la modale peut être générée dynamiquement)
        setTimeout(() => {
            prefillCategorieAndArme();
        }, 200);
        
        // Écouter l'événement 'shown.bs.modal' pour s'assurer que la modale est complètement affichée
        modalElement.addEventListener('shown.bs.modal', function() {
            prefillCategorieAndArme();
        }, { once: true });
    } else {
        console.error('Bootstrap n\'est pas chargé');
        alert('Erreur: Bootstrap n\'est pas chargé');
    }
}

// Note: showConfirmModal est déjà définie au début du fichier (ligne 7)
// La fonction displayModal n'est plus utilisée car showConfirmModal affiche directement la modale

// Soumettre l'inscription
function submitInscription() {
    if (!selectedArcher) {
        alert('Aucun archer sélectionné');
        return;
    }

    console.log('=== submitInscription ===');
    console.log('selectedArcher:', selectedArcher);
    console.log('selectedArcher.id:', selectedArcher.id);
    console.log('selectedArcher._id:', selectedArcher._id);
    console.log('Toutes les clés de selectedArcher:', Object.keys(selectedArcher));

    const userId = selectedArcher.id || selectedArcher._id || selectedArcher.ID || null;
    if (!userId) {
        // Si l'archer n'a pas d'ID, il devrait avoir été créé automatiquement lors de la recherche
        // Mais si ce n'est pas le cas, afficher un message d'erreur
        console.error('Aucun ID trouvé pour l\'archer sélectionné');
        console.error('Structure complète de l\'archer:', JSON.stringify(selectedArcher, null, 2));
        alert('Erreur: L\'archer sélectionné n\'a pas d\'ID. Veuillez réessayer la recherche ou contacter l\'administrateur.');
        return;
    }
    
    console.log('ID utilisateur trouvé:', userId);
    
    // Récupérer le numéro de départ
    const numeroDepart = document.getElementById('depart-select-main')?.value || document.getElementById('depart-select')?.value || null;
    console.log('numeroDepart récupéré:', numeroDepart);
    
    // Vérifier si l'archer est déjà inscrit pour ce départ
    if (numeroDepart) {
        checkExistingInscription(userId, numeroDepart, () => {
            proceedWithInscriptionSubmission();
        });
    } else {
        // Pas de numéro de départ, continuer directement
        proceedWithInscriptionSubmission();
    }
}

// Fonction pour vérifier si l'archer est déjà inscrit pour ce départ
function checkExistingInscription(userId, numeroDepart, callback) {
    fetch(`/api/concours/${concoursId}/inscriptions`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        const inscriptions = data.success ? (data.data || []) : [];
        const dejaInscrit = inscriptions.some(inscription => 
            inscription.user_id == userId && 
            inscription.numero_depart == numeroDepart
        );
        
        if (dejaInscrit) {
            const confirmer = confirm(
                `⚠️ ATTENTION\n\n` +
                `Cet archer est déjà inscrit au départ ${numeroDepart} pour ce concours.\n\n` +
                `Voulez-vous quand même continuer ?\n\n` +
                `(Si vous continuez, l'inscription sera refusée par le serveur)`
            );
            
            if (!confirmer) {
                console.log('Inscription annulée par l\'utilisateur');
                return;
            }
        }
        
        // Continuer avec la soumission
        callback();
    })
    .catch(error => {
        console.error('Erreur lors de la vérification des inscriptions:', error);
        // En cas d'erreur, continuer quand même
        callback();
    });
}

// Fonction pour procéder avec la soumission de l'inscription
function proceedWithInscriptionSubmission() {
    if (!selectedArcher) {
        alert('Aucun archer sélectionné');
        return;
    }

    const userId = selectedArcher.id || selectedArcher._id || selectedArcher.ID || null;
    if (!userId) {
        console.error('Aucun ID trouvé pour l\'archer sélectionné');
        alert('Erreur: L\'archer sélectionné n\'a pas d\'ID. Veuillez réessayer la recherche ou contacter l\'administrateur.');
        return;
    }
    
    console.log('ID utilisateur trouvé:', userId);
    
    // Récupérer le numéro de départ
    const numeroDepart = document.getElementById('depart-select-main')?.value || document.getElementById('depart-select')?.value || null;
    console.log('numeroDepart récupéré:', numeroDepart);
    
    // Vérifier si l'archer est déjà inscrit pour ce départ
    if (numeroDepart) {
        // Récupérer les inscriptions existantes pour vérifier
        fetch(`/api/concours/${concoursId}/inscriptions`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            const inscriptions = data.success ? (data.data || []) : [];
            const dejaInscrit = inscriptions.some(inscription => 
                inscription.user_id == userId && 
                inscription.numero_depart == numeroDepart
            );
            
            if (dejaInscrit) {
                alert(
                    `⚠️ ATTENTION\n\n` +
                    `Cet archer est déjà inscrit au départ ${numeroDepart} pour ce concours.\n\n` +
                    `Veuillez sélectionner un autre départ ou retirer l'inscription existante avant de continuer.`
                );
                console.log('Inscription annulée - archer déjà inscrit pour ce départ');
                return;
            }
            
            // Continuer avec la soumission
            proceedWithInscriptionSubmission();
        })
        .catch(error => {
            console.error('Erreur lors de la vérification des inscriptions:', error);
            // En cas d'erreur, continuer quand même
            proceedWithInscriptionSubmission();
        });
    } else {
        // Pas de numéro de départ, continuer directement
        proceedWithInscriptionSubmission();
    }
    
    return; // Sortir de la fonction, proceedWithInscriptionSubmission sera appelé de manière asynchrone
}

// Fonction pour procéder avec la soumission de l'inscription après vérification
function proceedWithInscriptionSubmission() {
    if (!selectedArcher) {
        alert('Aucun archer sélectionné');
        return;
    }

    const userId = selectedArcher.id || selectedArcher._id || selectedArcher.ID || null;
    if (!userId) {
        console.error('Aucun ID trouvé pour l\'archer sélectionné');
        alert('Erreur: L\'archer sélectionné n\'a pas d\'ID. Veuillez réessayer la recherche ou contacter l\'administrateur.');
        return;
    }
    
    console.log('ID utilisateur trouvé:', userId);
    
    // Récupérer le numéro de départ
    const departSelectMain = document.getElementById('depart-select-main');
    const departSelectModal = document.getElementById('depart-select');
    const numeroDepart = departSelectMain?.value || departSelectModal?.value || null;
    console.log('numeroDepart récupéré:', numeroDepart);
    console.log('depart-select-main value:', departSelectMain?.value);
    console.log('depart-select value:', departSelectModal?.value);

    // Activer temporairement les champs disabled pour récupérer leurs valeurs
    const typeCertificatMedicalSelect = document.getElementById('type_certificat_medical');
    const typeLicenceSelect = document.getElementById('type_licence');
    if (typeCertificatMedicalSelect && typeCertificatMedicalSelect.disabled) {
        typeCertificatMedicalSelect.disabled = false;
    }
    if (typeLicenceSelect && typeLicenceSelect.disabled) {
        typeLicenceSelect.disabled = false;
    }
    
    // Récupérer tous les champs du formulaire
    const saison = document.getElementById('saison')?.value || null;
    const typeCertificatMedical = document.getElementById('type_certificat_medical')?.value || null;
    const typeLicence = document.getElementById('type_licence')?.value || null;
    const creationRenouvellement = document.getElementById('creation_renouvellement')?.value || null;
    
    // Re-désactiver les champs après récupération des valeurs
    if (typeCertificatMedicalSelect) {
        typeCertificatMedicalSelect.disabled = true;
    }
    if (typeLicenceSelect) {
        typeLicenceSelect.disabled = true;
    }
    const categorieClassement = document.getElementById('categorie_classement')?.value || null;
    const arme = document.getElementById('arme')?.value || null;
    const mobiliteReduite = document.getElementById('mobilite_reduite')?.checked ? 1 : 0;
    
    // Pour les disciplines 3D, Nature et Campagne : utiliser piquet au lieu de distance, pas de blason
    const isNature = typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne;
    const distance = !isNature && document.getElementById('distance')?.value ? parseInt(document.getElementById('distance').value) : null;
    const piquet = isNature ? (document.getElementById('piquet')?.value || null) : null;
    const blason = !isNature && document.getElementById('blason')?.value ? parseInt(document.getElementById('blason').value) : null;
    
    const numeroTir = document.getElementById('numero_tir')?.value ? parseInt(document.getElementById('numero_tir').value) : null;
    
    // Pour les disciplines 3D, Nature et Campagne : les champs duel et trispot n'existent pas
    const duel = !isNature && document.getElementById('duel') ? (document.getElementById('duel').checked ? 1 : 0) : null;
    const trispot = !isNature && document.getElementById('trispot') ? (document.getElementById('trispot').checked ? 1 : 0) : null;
    
    const tarifCompetition = document.getElementById('tarif_competition')?.value || null;
    const modePaiement = document.getElementById('mode_paiement')?.value || 'Non payé';

    // Récupérer numero_licence et id_club depuis selectedArcher
    const numeroLicence = selectedArcher.licence_number || selectedArcher.licenceNumber || selectedArcher.IDLicence || null;
    // id_club doit être le name_short du club (ex: "0657108" avec zéro initial)
    // Priorité: id_club, club_name_short, clubNameShort, AGREMENTNR
    const idClub = selectedArcher.id_club || selectedArcher.club_name_short || selectedArcher.clubNameShort || selectedArcher.AGREMENTNR || null; 
    
    console.log('numero_licence récupéré:', numeroLicence);
    console.log('id_club récupéré:', idClub);
    console.log('selectedArcher complet:', selectedArcher);

    // Créer un formulaire pour soumettre l'inscription
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/concours/${concoursId}/inscription`;

    // Ajouter tous les champs
    // Pour numero_depart, convertir en entier seulement si la valeur existe et est valide
    let numeroDepartInt = null;
    if (numeroDepart && numeroDepart !== '' && numeroDepart !== '0') {
        const parsed = parseInt(numeroDepart);
        if (!isNaN(parsed) && parsed > 0) {
            numeroDepartInt = parsed;
        }
    }
    console.log('numeroDepartInt final:', numeroDepartInt);
    
    // Construire les champs selon le type de discipline
    const fields = {
        'user_id': userId,
        'numero_depart': numeroDepartInt,
        'numero_licence': numeroLicence,
        'id_club': idClub,
        'saison': saison,
        'type_certificat_medical': typeCertificatMedical,
        'type_licence': typeLicence,
        'creation_renouvellement': creationRenouvellement,
        'categorie_classement': categorieClassement,
        'arme': arme,
        'mobilite_reduite': mobiliteReduite,
        'numero_tir': numeroTir,
        'tarif_competition': tarifCompetition,
        'mode_paiement': modePaiement
    };
    
    // Pour les disciplines 3D, Nature et Campagne : utiliser piquet au lieu de distance, pas de blason, pas de duel/trispot
    if (isNature) {
        fields['piquet'] = piquet;
        // Pas de duel ni trispot pour ces disciplines
    } else {
        fields['distance'] = distance;
        fields['blason'] = blason;
        fields['duel'] = duel;
        fields['trispot'] = trispot;
    }

    for (const [name, value] of Object.entries(fields)) {
        // Traitement spécial pour numero_depart : toujours l'envoyer s'il a une valeur valide
        if (name === 'numero_depart') {
            // Si la valeur est un nombre valide > 0, l'envoyer
            if (value !== null && value !== '' && !isNaN(value) && parseInt(value) > 0) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = parseInt(value); // S'assurer que c'est un entier
                form.appendChild(input);
                console.log('✓ numero_depart ajouté au formulaire:', parseInt(value));
            } else {
                console.warn('✗ numero_depart non valide ou vide. Valeur:', value, '- Non envoyé au serveur');
            }
            continue;
        }
        
        // Inclure les valeurs même si elles sont 0 ou null pour certains champs numériques
        // Pour numero_licence et id_club, inclure même si vides (seront null côté serveur)
        if (value !== null && value !== '') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        } else if (value === 0 || value === '0') {
            // Inclure les valeurs 0 pour les champs numériques (mobilite_reduite, duel, trispot)
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        } else if ((name === 'numero_licence' || name === 'id_club') && value === null) {
            // Inclure numero_licence et id_club même s'ils sont null (seront traités côté serveur)
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = '';
            form.appendChild(input);
        }
    }
    
    console.log('Formulaire créé avec les champs:', Object.fromEntries(Array.from(form.querySelectorAll('input')).map(input => [input.name, input.value])));
    console.log('Formulaire prêt à être soumis. Action:', form.action);
    
    document.body.appendChild(form);
    console.log('Formulaire ajouté au DOM, soumission...');
    form.submit();
}

// Retirer une inscription par ID
function removeInscription(inscriptionId) {
    if (!confirm('Voulez-vous retirer cet archer de l\'inscription ?')) {
        return;
    }

    console.log('Suppression de l\'inscription ID:', inscriptionId);

    // Utiliser la route DELETE avec l'ID d'inscription
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
        console.log('Réponse suppression:', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression: ' + error.message);
    });
}

// Fonction pour éditer une inscription - Charge les données AVANT d'ouvrir la modale
window.editInscription = function(inscriptionId) {
    if (!concoursId || !inscriptionId) {
        alert('Erreur: Informations manquantes');
        return;
    }
    
    const modalElement = document.getElementById('editInscriptionModal');
    if (!modalElement) {
        alert('Erreur: La modale d\'édition est introuvable');
        return;
    }
    
    // Stocker l'ID de l'inscription dans le formulaire
    const form = document.getElementById('edit-inscription-form');
    if (form) {
        form.dataset.inscriptionId = inscriptionId;
    }
    
    // CHARGER LES DONNÉES AVANT d'ouvrir la modale
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
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Extraire l'inscription
        let inscription = null;
        if (data.success && data.data) {
            inscription = data.data;
        } else if (data.id) {
            inscription = data;
        } else {
            alert('Erreur: Format de réponse inattendu');
            return;
        }
        
        if (!inscription) {
            alert('Erreur: Aucune donnée trouvée');
            return;
        }
        
        // MAINTENANT que les données sont chargées, ouvrir la modale et remplir
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }
            const modal = new bootstrap.Modal(modalElement);
            
            // Fonction pour remplir les champs
            const fillForm = () => {
                const saisonInput = document.getElementById('edit-saison');
                if (saisonInput) saisonInput.value = inscription.saison || '';
                
                const typeCertificatSelect = document.getElementById('edit-type_certificat_medical');
                if (typeCertificatSelect) typeCertificatSelect.value = inscription.type_certificat_medical || '';
                
                const typeLicenceSelect = document.getElementById('edit-type_licence');
                if (typeLicenceSelect) typeLicenceSelect.value = inscription.type_licence || '';
                
                const creationRenouvellementInput = document.getElementById('edit-creation_renouvellement');
                if (creationRenouvellementInput) creationRenouvellementInput.value = inscription.creation_renouvellement || '';
                
                const departSelect = document.getElementById('edit-depart-select');
                if (departSelect) departSelect.value = inscription.numero_depart || '';
                
                const categorieSelect = document.getElementById('edit-categorie_classement');
                if (categorieSelect) categorieSelect.value = inscription.categorie_classement || '';
                
                const armeSelect = document.getElementById('edit-arme');
                if (armeSelect) armeSelect.value = inscription.arme || '';
                
                const mobiliteReduiteCheckbox = document.getElementById('edit-mobilite_reduite');
                if (mobiliteReduiteCheckbox) mobiliteReduiteCheckbox.checked = inscription.mobilite_reduite == 1 || inscription.mobilite_reduite === true;
                
                if (isNature3DOrCampagne) {
                    const piquetSelect = document.getElementById('edit-piquet');
                    if (piquetSelect) piquetSelect.value = inscription.piquet || '';
                } else {
                    const distanceSelect = document.getElementById('edit-distance');
                    if (distanceSelect) distanceSelect.value = inscription.distance || '';
                    
                    const blasonInput = document.getElementById('edit-blason');
                    if (blasonInput) blasonInput.value = inscription.blason || '';
                    
                    const duelCheckbox = document.getElementById('edit-duel');
                    if (duelCheckbox) duelCheckbox.checked = inscription.duel == 1 || inscription.duel === true;
                    
                    const trispotCheckbox = document.getElementById('edit-trispot');
                    if (trispotCheckbox) trispotCheckbox.checked = inscription.trispot == 1 || inscription.trispot === true;
                }
                
                const numeroTirSelect = document.getElementById('edit-numero_tir');
                if (numeroTirSelect) numeroTirSelect.value = inscription.numero_tir || '';
                
                const tarifSelect = document.getElementById('edit-tarif_competition');
                if (tarifSelect) tarifSelect.value = inscription.tarif_competition || '';
                
                const modePaiementSelect = document.getElementById('edit-mode_paiement');
                if (modePaiementSelect) modePaiementSelect.value = inscription.mode_paiement || 'Non payé';
            };
            
            // Ouvrir la modale
            modal.show();
            
            // Remplir après que la modale soit affichée
            setTimeout(() => {
                fillForm();
            }, 100);
            
            // Re-remplir quand la modale est complètement affichée
            modalElement.addEventListener('shown.bs.modal', function() {
                fillForm();
            }, { once: true });
        } else {
            alert('Erreur: Bootstrap n\'est pas chargé');
        }
    })
    .catch(error => {
        alert('Erreur lors de la récupération: ' + error.message);
    });
};

// Gestionnaire pour le bouton de confirmation d'édition
document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmEdit = document.getElementById('btn-confirm-edit');
    if (btnConfirmEdit) {
        btnConfirmEdit.addEventListener('click', function() {
            const form = document.getElementById('edit-inscription-form');
            const inscriptionId = form.dataset.inscriptionId;
            
            if (!concoursId || !inscriptionId) {
                alert('Erreur: Informations manquantes');
                return;
            }
            
            // Fonction helper pour récupérer une valeur de manière sécurisée
            const getValue = (id) => {
                const element = document.getElementById(id);
                return element ? element.value : null;
            };
            
            const getChecked = (id) => {
                const element = document.getElementById(id);
                return element ? element.checked : false;
            };
            
            // Récupérer les valeurs du formulaire
            const updateData = {
                saison: getValue('edit-saison') || null,
                type_certificat_medical: getValue('edit-type_certificat_medical') || null,
                type_licence: getValue('edit-type_licence') || null,
                creation_renouvellement: getValue('edit-creation_renouvellement') ? parseInt(getValue('edit-creation_renouvellement')) : 0,
                numero_depart: getValue('edit-depart-select') ? parseInt(getValue('edit-depart-select')) : null,
                categorie_classement: getValue('edit-categorie_classement') || null,
                arme: getValue('edit-arme') || null,
                mobilite_reduite: getChecked('edit-mobilite_reduite') ? 1 : 0,
                numero_tir: getValue('edit-numero_tir') ? parseInt(getValue('edit-numero_tir')) : null,
                tarif_competition: getValue('edit-tarif_competition') || null,
                mode_paiement: getValue('edit-mode_paiement') || 'Non payé'
            };
            
            if (isNature3DOrCampagne) {
                const piquetValue = getValue('edit-piquet');
                if (piquetValue !== null) {
                    updateData.piquet = piquetValue || null;
                }
            } else {
                const distanceValue = getValue('edit-distance');
                const blasonValue = getValue('edit-blason');
                if (distanceValue !== null) {
                    updateData.distance = distanceValue ? parseInt(distanceValue) : null;
                }
                if (blasonValue !== null) {
                    updateData.blason = blasonValue ? parseInt(blasonValue) : null;
                }
                updateData.duel = getChecked('edit-duel') ? 1 : 0;
                updateData.trispot = getChecked('edit-trispot') ? 1 : 0;
            }
            
            // Envoyer la requête de mise à jour
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
                    // Fermer la modale
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editInscriptionModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Recharger la page pour afficher les modifications
                    location.reload();
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
});
