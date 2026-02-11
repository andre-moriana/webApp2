// Nouvelle logique simplifiée pour la recherche et sélection d'archer

// Variable globale pour stocker l'archer actuel
let selectedArcher = null;

// Initialiser les écouteurs d'événements pour la recherche d'archer
document.addEventListener('DOMContentLoaded', function() {
    const btnSearchArcher = document.getElementById('btn-search-archer');
    const licenceInput = document.getElementById('licence-search-input');
    const btnConfirmSelection = document.getElementById('btn-confirm-archer-selection');
    
    if (btnSearchArcher) {
        btnSearchArcher.addEventListener('click', searchArcherByLicense);
    }
    
    if (licenceInput) {
        licenceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchArcherByLicense();
            }
        });
    }
    
    if (btnConfirmSelection) {
        btnConfirmSelection.addEventListener('click', confirmArcherSelection);
    }
    
    // Initialiser le bouton de confirmation d'inscription dans la modale
    const btnConfirmInscription = document.getElementById('btn-confirm-inscription');
    if (btnConfirmInscription) {
        btnConfirmInscription.addEventListener('click', submitInscription);
    }
});

/**
 * Recherche un archer par numéro de licence
 * Cherche d'abord en BD, puis en XML si nécessaire
 */
function searchArcherByLicense() {
    const licenceInput = document.getElementById('licence-search-input');
    const licence = (licenceInput.value || '').trim();
    
    if (!licence) {
        alert('Veuillez entrer un numéro de licence');
        return;
    }
    
    showLoading();
    
    fetch('/archer/search-or-create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ licence_number: licence })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showError(data.error || 'Archer non trouvé');
            return;
        }
        
        // Créer l'objet archer avec les données retournées
        selectedArcher = {
            id: data.data.user_id,
            _id: data.data.user_id,
            licence_number: licence,
            licenceNumber: licence,
            IDLicence: licence,
            first_name: data.data.first_name || '',
            firstName: data.data.first_name || '',
            name: data.data.name || '',
            nom: data.data.name || '',
            club_name: data.data.club || '',
            CIE: data.data.club || '',
            ageCategory: data.data.age_category || '',
            bowType: data.data.bow_type || '',
            categorie: data.data.categorie || '',
            source: data.source
        };
        
        // Charger les données XML si nécessaire
        ensureXmlDataForArcher(selectedArcher).then(() => {
            showSearchResult(data.data, data.source);
            hideLoading();
        });
    })
    .catch(error => {
        console.error('Erreur lors de la recherche:', error);
        showError('Erreur lors de la recherche: ' + error.message);
    })
    .finally(() => {
        hideLoading();
    });
}

/**
 * Affiche le résultat de la recherche
 */
function showSearchResult(archerData, source) {
    const resultDiv = document.getElementById('archer-search-result');
    const contentDiv = document.getElementById('archer-result-content');
    
    const sourceLabel = source === 'database' ? 'trouvé en base de données' : 'créé depuis XML';
    const sourceClass = source === 'database' ? 'badge-success' : 'badge-info';
    
    contentDiv.innerHTML = `
        <p>
            <strong>Nom:</strong> ${escapeHtml(archerData.name || '')}<br>
            <strong>Prénom:</strong> ${escapeHtml(archerData.first_name || '')}<br>
            <strong>Licence:</strong> ${escapeHtml(archerData.licence_number || '')}<br>
            <strong>Club:</strong> ${escapeHtml(archerData.club || 'Non renseigné')}<br>
            <span class="badge ${sourceClass}">${sourceLabel}</span>
        </p>
    `;
    
    resultDiv.style.display = 'block';
}

/**
 * Confirme la sélection de l'archer et affiche le modal d'inscription
 */
function confirmArcherSelection() {
    if (!selectedArcher || !selectedArcher.id) {
        alert('Erreur: Aucun archer sélectionné');
        return;
    }
    
    // Remplir le modal avec les données de l'archer
    updateInscriptionModal();
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('confirmInscriptionModal'));
    modal.show();
}

/**
 * Met à jour le modal d'inscription avec les données de l'archer sélectionné
 */
function updateInscriptionModal() {
    if (!selectedArcher) {
        return;
    }
    
    // Afficher les infos de l'archer en haut du modal
    const nomSpan = document.getElementById('modal-archer-nom');
    const prenomSpan = document.getElementById('modal-archer-prenom');
    const licenceSpan = document.getElementById('modal-archer-licence');
    const clubSpan = document.getElementById('modal-archer-club');
    
    if (nomSpan) nomSpan.textContent = selectedArcher.name || '';
    if (prenomSpan) prenomSpan.textContent = selectedArcher.first_name || '';
    if (licenceSpan) licenceSpan.textContent = selectedArcher.licence_number || '';
    if (clubSpan) clubSpan.textContent = selectedArcher.club_name || '';
    
    // Pré-remplir les champs du formulaire avec les données XML
    prefillFormFromArcherData();
}

/**
 * Pré-remplit les champs du formulaire d'inscription avec les données de l'archer
 */
function prefillFormFromArcherData() {
    if (!selectedArcher) {
        return;
    }
    
    // Saison (ABREV du XML)
    const saisonInput = document.getElementById('saison');
    if (saisonInput && selectedArcher.saison) {
        saisonInput.value = selectedArcher.saison;
    }
    
    // Type de licence
    const typeLicenceSelect = document.getElementById('type_licence');
    if (typeLicenceSelect && selectedArcher.type_licence) {
        for (let option of typeLicenceSelect.options) {
            if (option.value === selectedArcher.type_licence) {
                typeLicenceSelect.value = option.value;
                break;
            }
        }
    }
    
    // Type de certificat médical
    const typeCertSelect = document.getElementById('type_certificat_medical');
    if (typeCertSelect && selectedArcher.type_certificat_medical) {
        for (let option of typeCertSelect.options) {
            if (option.value.toLowerCase() === (selectedArcher.type_certificat_medical || '').toLowerCase()) {
                typeCertSelect.value = option.value;
                break;
            }
        }
    }
    
    // Création/Renouvellement
    const creationInput = document.getElementById('creation_renouvellement');
    if (creationInput && selectedArcher.creation_renouvellement) {
        creationInput.value = selectedArcher.creation_renouvellement;
    }
    
    // Catégorie de classement (si disponible)
    const categorieSelect = document.getElementById('categorie_classement');
    if (categorieSelect && selectedArcher.categorie) {
        // Chercher une option correspondant à la catégorie XML
        for (let option of categorieSelect.options) {
            if (option.value === selectedArcher.categorie) {
                categorieSelect.value = option.value;
                break;
            }
        }
    }
    
    // Arme/Bow (si disponible)
    const armeSelect = document.getElementById('arme');
    if (armeSelect && selectedArcher.bowType) {
        for (let option of armeSelect.options) {
            if (option.value === selectedArcher.bowType) {
                armeSelect.value = option.value;
                break;
            }
        }
    }
}

/**
 * Soumet l'inscription au serveur
 */
function submitInscription() {
    if (!selectedArcher || !selectedArcher.id) {
        alert('Erreur: Aucun archer sélectionné');
        return;
    }
    
    // Vérifier qu'un départ est sélectionné
    const departSelect = document.getElementById('depart-select-main');
    const numeroDepart = departSelect ? departSelect.value : null;
    
    if (!numeroDepart) {
        alert('Veuillez sélectionner un numéro de départ');
        return;
    }
    
    // Récupérer tous les champs du formulaire
    const saison = document.getElementById('saison')?.value || null;
    const typeCertificatMedical = document.getElementById('type_certificat_medical')?.value || null;
    const typeLicence = document.getElementById('type_licence')?.value || null;
    const creationRenouvellement = document.getElementById('creation_renouvellement')?.value || null;
    const categorieClassement = document.getElementById('categorie_classement')?.value || null;
    const arme = document.getElementById('arme')?.value || null;
    const mobiliteReduite = document.getElementById('mobilite_reduite')?.checked ? 1 : 0;
    const tarifCompetition = document.getElementById('tarif_competition')?.value || null;
    const modePaiement = document.getElementById('mode_paiement')?.value || 'Non payé';
    
    // Construire l'objet de données
    const data = {
        user_id: selectedArcher.id,
        numero_depart: parseInt(numeroDepart),
        numero_licence: selectedArcher.licence_number,
        id_club: selectedArcher.id_club || null,
        saison: saison,
        type_certificat_medical: typeCertificatMedical,
        type_licence: typeLicence,
        creation_renouvellement: creationRenouvellement,
        categorie_classement: categorieClassement,
        arme: arme,
        mobilite_reduite: mobiliteReduite,
        tarif_competition: tarifCompetition,
        mode_paiement: modePaiement
    };
    
    console.log('Données d\'inscription à soumettre:', data);
    
    // Soumettre le formulaire
    const concoursId = getConcoursId();
    if (!concoursId) {
        alert('Erreur: ID du concours manquant');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/concours/${concoursId}/inscription`;
    
    Object.keys(data).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = data[key] !== null ? data[key] : '';
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Récupère l'ID du concours depuis la page
 */
function getConcoursId() {
    // Chercher dans les attributs de données
    const container = document.querySelector('[data-concours-id]');
    if (container) {
        return container.getAttribute('data-concours-id');
    }
    
    // Chercher dans les variables globales
    if (typeof concoursId !== 'undefined') {
        return concoursId;
    }
    
    // Chercher dans la page PHP de concours
    const titleMatch = document.title.match(/Concours/);
    if (titleMatch) {
        // Parser l'URL si possible
        const urlMatch = window.location.pathname.match(/\/concours\/(\d+)/);
        if (urlMatch) {
            return urlMatch[1];
        }
    }
    
    return null;
}

/**
 * Affiche le message de chargement
 */
function showLoading() {
    document.getElementById('archer-search-loading').style.display = 'block';
    document.getElementById('archer-search-result').style.display = 'none';
    document.getElementById('archer-search-error').style.display = 'none';
}

/**
 * Masque le message de chargement
 */
function hideLoading() {
    document.getElementById('archer-search-loading').style.display = 'none';
}

/**
 * Affiche un message d'erreur
 */
function showError(message) {
    const errorDiv = document.getElementById('archer-search-error');
    errorDiv.innerHTML = `<div class="alert alert-danger" role="alert">${escapeHtml(message)}</div>`;
    errorDiv.style.display = 'block';
    document.getElementById('archer-search-result').style.display = 'none';
}

/**
 * Échappe les caractères HTML pour la sécurité
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Charge les données XML pour un archer s'il n'en a pas
 * Cette fonction est reprise du code original
 */
function ensureXmlDataForArcher(archer) {
    if (!archer) {
        return Promise.resolve(false);
    }

    const hasXmlFields = !!(
        archer.categorie || archer.CATEGORIE ||
        archer.typarc || archer.TYPARC ||
        archer.sexe || archer.SEXE
    );

    if (hasXmlFields) {
        return Promise.resolve(false);
    }

    const licence = (archer.licence_number || archer.licenceNumber || archer.IDLicence || '').trim();
    if (!licence) {
        return Promise.resolve(false);
    }

    return loadXmlArchersCache().then(archers => {
        if (!Array.isArray(archers) || archers.length === 0) {
            return false;
        }
        
        const match = archers.find(item => {
            const xmlLicence = (item.licence_number || item.IDLicence || '').trim();
            return xmlLicence && xmlLicence === licence;
        });
        
        if (!match) {
            return false;
        }

        // Copier les données XML manquantes
        if (!archer.categorie && match.categorie) archer.categorie = match.categorie;
        if (!archer.CATEGORIE && match.CATEGORIE) archer.CATEGORIE = match.CATEGORIE;
        if (!archer.typarc && match.typarc) archer.typarc = match.typarc;
        if (!archer.TYPARC && match.TYPARC) archer.TYPARC = match.TYPARC;
        if (!archer.sexe && match.sexe) archer.sexe = match.sexe;
        if (!archer.SEXE && match.SEXE) archer.SEXE = match.SEXE;
        if (!archer.birth_date && match.birth_date) archer.birth_date = match.birth_date;
        if (!archer.DATENAISSANCE && match.DATENAISSANCE) archer.DATENAISSANCE = match.DATENAISSANCE;
        if (!archer.type_licence && match.type_licence) archer.type_licence = match.type_licence;
        if (!archer.creation_renouvellement && match.creation_renouvellement) archer.creation_renouvellement = match.creation_renouvellement;
        if (!archer.type_certificat_medical && match.certificat_medical) archer.type_certificat_medical = match.certificat_medical;
        if (!archer.saison && match.saison) archer.saison = match.saison;

        return true;
    });
}

/**
 * Charge le cache des archers du XML
 * Reprise du code original
 */
let xmlArchersCache = null;
let xmlArchersCacheLoading = null;

function loadXmlArchersCache() {
    if (xmlArchersCache) {
        return Promise.resolve(xmlArchersCache);
    }
    if (xmlArchersCacheLoading) {
        return xmlArchersCacheLoading;
    }

    xmlArchersCacheLoading = fetch('/public/data/users-licences.xml', {
        method: 'GET',
        headers: {
            'Accept': 'application/xml'
        },
        credentials: 'include'
    })
    .then(response => response.text())
    .then(text => {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(text, 'application/xml');
        if (xmlDoc.getElementsByTagName('parsererror').length) {
            throw new Error('XML invalide');
        }
        const nodes = Array.from(xmlDoc.getElementsByTagName('TABLE_CONTENU'));
        const archers = nodes.map(node => {
            const getText = (tag) => {
                const el = node.getElementsByTagName(tag)[0];
                return el ? (el.textContent || '').trim() : '';
            };
            const licence = getText('IDLicence');
            const nom = getText('NOM');
            const prenom = getText('PRENOM');
            const clubName = getText('CIE');
            const clubShort = getText('AGREMENTNR') || getText('club_unique');
            return {
                nom: nom,
                prenom: prenom,
                name: nom,
                firstName: prenom,
                licence_number: licence,
                IDLicence: licence,
                xml_source: true,
                club_name: clubName,
                CIE: clubName,
                clubNameShort: clubShort,
                AGREMENTNR: clubShort,
                categorie: getText('CATEGORIE'),
                CATEGORIE: getText('CATEGORIE'),
                typarc: getText('TYPARC'),
                TYPARC: getText('TYPARC'),
                sexe: getText('SEXE'),
                SEXE: getText('SEXE'),
                birth_date: getText('DATENAISSANCE'),
                DATENAISSANCE: getText('DATENAISSANCE'),
                ABREV: getText('ABREV'),
                saison: getText('ABREV'),
                certificat_medical: getText('certificat_medical') || getText('CERTIFICAT'),
                certificat_medical_raw: getText('CERTIFICAT'),
                CERTIFICAT: getText('CERTIFICAT'),
                type_licence: getText('type_licence'),
                type_licence_raw: getText('type_licence'),
                creation_renouvellement: getText('Creation_renouvellement')
            };
        });
        xmlArchersCache = archers;
        return archers;
    })
    .catch(() => {
        xmlArchersCache = [];
        return xmlArchersCache;
    })
    .finally(() => {
        xmlArchersCacheLoading = null;
    });

    return xmlArchersCacheLoading;
}
