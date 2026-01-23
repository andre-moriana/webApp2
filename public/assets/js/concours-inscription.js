// Gestion de l'inscription aux concours

// Variable globale pour stocker l'archer sélectionné
let selectedArcher = null;

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
        const nom = archer.nom || archer.name || archer.NOM || 'N/A';
        const prenom = archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A';
        const licence = archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A';
        const club = archer.club_name || archer.CLUB || 'N/A';
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

    // Afficher la modale de confirmation
    console.log('=== Appel de showConfirmModal ===');
    console.log('Archer passé à showConfirmModal:', archer);
    console.log('Type de archer:', typeof archer);
    console.log('showConfirmModal est une fonction ?', typeof showConfirmModal);
    
    // Vérifier que la fonction existe
    if (typeof showConfirmModal !== 'function') {
        console.error('showConfirmModal n\'est pas une fonction !');
        console.error('Type de showConfirmModal:', typeof showConfirmModal);
        alert('Erreur: La fonction showConfirmModal n\'existe pas');
        return;
    }
    
    console.log('Appel de showConfirmModal maintenant...');
    try {
        const result = showConfirmModal(archer);
        console.log('showConfirmModal appelée avec succès, résultat:', result);
    } catch (error) {
        console.error('Erreur lors de l\'appel à showConfirmModal:', error);
        console.error('Stack trace:', error.stack);
        alert('Erreur lors de l\'ouverture de la modale: ' + error.message);
    }
    console.log('Après l\'appel à showConfirmModal');
}

// Afficher la modale de confirmation avec formulaire complet
function showConfirmModal(archer) {
    console.log('=== showConfirmModal DÉBUT ===');
    console.log('Archer reçu:', archer);
    console.log('Type de archer:', typeof archer);
    console.log('Archer est un objet ?', archer instanceof Object);
    
    // Test immédiat pour voir si la fonction s'exécute
    console.log('TEST: showConfirmModal s\'exécute !');
    
    // Vérifier que l'archer est bien défini
    if (!archer) {
        console.error('showConfirmModal: archer est undefined');
        alert('Erreur: Aucune information d\'archer disponible');
        return;
    }
    
    // Récupérer les éléments de la modale AVANT de modifier le contenu
    const modalElement = document.getElementById('confirmInscriptionModal');
    const modalBody = document.getElementById('confirm-modal-body');
    const modalTitle = modalElement ? modalElement.querySelector('.modal-title') : null;
    
    if (!modalElement) {
        console.error('showConfirmModal: Élément modal introuvable');
        alert('Erreur: Modal introuvable');
        return;
    }
    
    if (!modalBody) {
        console.error('showConfirmModal: modal-body introuvable');
        alert('Erreur: Élément modal-body introuvable');
        return;
    }
    
    // S'assurer que le titre est correct AVANT de modifier le contenu
    // IMPORTANT: Toujours utiliser String() pour éviter [object Object]
    if (modalTitle) {
        const titleText = String('Confirmer l\'inscription');
        // Vider d'abord le contenu pour éviter les problèmes
        modalTitle.textContent = '';
        modalTitle.innerHTML = '';
        // Puis définir le titre
        modalTitle.textContent = titleText;
        modalTitle.innerHTML = titleText;
        console.log('Titre de la modale défini à "Confirmer l\'inscription"');
        console.log('Vérification titre:', modalTitle.textContent);
        console.log('Type:', typeof modalTitle.textContent);
        console.log('Valeur brute:', JSON.stringify(modalTitle.textContent));
    } else {
        console.warn('modalTitle introuvable !');
    }
    
    console.log('Extraction des informations de l\'archer...');
    // Extraire les informations de l'archer avec des valeurs par défaut
    const nom = String(archer.nom || archer.name || archer.NOM || 'N/A');
    const prenom = String(archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A');
    const licence = String(archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A');
    const club = String(archer.club_name || archer.CLUB || 'N/A');
    const gender = String(archer.gender || archer.GENRE || '');
    const birthDate = String(archer.birth_date || archer.birthDate || archer.DATENAISSANCE || '');
    
    console.log('showConfirmModal - nom:', nom, 'prenom:', prenom);
    console.log('showConfirmModal - licence:', licence, 'club:', club);
    console.log('showConfirmModal - gender:', gender);

    let departsHtml = '';
    if (departs && departs.length > 0) {
        departsHtml = `
            <div class="mb-3">
                <label for="depart-select" class="form-label">N° départ <span class="text-danger">*</span></label>
                <select id="depart-select" class="form-control" required>
                    <option value="">Sélectionner un départ</option>
                    ${departs.map(depart => `
                        <option value="${depart.id || depart._id || ''}">
                            Départ ${depart.numero || depart.numero_depart || ''} - ${depart.heure || ''}
                        </option>
                    `).join('')}
                </select>
            </div>
        `;
    }

    // Construire le contenu HTML
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
                    <input type="text" id="saison" class="form-control" placeholder="Ex: 2024-2025">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="type_certificat_medical" class="form-label">Type Certificat Médical</label>
                    <select id="type_certificat_medical" class="form-control">
                        <option value="">Sélectionner</option>
                        <option value="Compétition">Compétition</option>
                        <option value="Loisir">Loisir</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="type_licence" class="form-label">Type Licence</label>
                    <select id="type_licence" class="form-control">
                        <option value="">Sélectionner</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" id="creation_renouvellement" class="form-check-input">
                        <label for="creation_renouvellement" class="form-check-label">Création/Renouvellement</label>
                    </div>
                </div>
            </div>
            
            ${departsHtml}
            
            <h6 class="mt-4 mb-3">Classification et équipement</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="categorie_classement" class="form-label">Catégorie de classement</label>
                    <input type="text" id="categorie_classement" class="form-control" placeholder="Ex: S3HCL">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="arme" class="form-label">Arme (utilisée sur le pas de tir)</label>
                    <select id="arme" class="form-control">
                        <option value="">Sélectionner</option>
                        <option value="Arc Classique">Arc Classique</option>
                        <option value="Arc à poulies">Arc à poulies</option>
                        <option value="Arc nu">Arc nu</option>
                        <option value="Arc traditionnel">Arc traditionnel</option>
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
                <div class="col-md-3 mb-3">
                    <label for="distance" class="form-label">Distance</label>
                    <input type="number" id="distance" class="form-control" min="0" placeholder="Ex: 18">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="numero_tir" class="form-label">N° Tir</label>
                    <input type="number" id="numero_tir" class="form-control" min="1" placeholder="Ex: 1">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="blason" class="form-label">Blason</label>
                    <input type="number" id="blason" class="form-control" min="0" placeholder="Ex: 40">
                </div>
            </div>
            
            <div class="row">
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
            </div>
            
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

    // Vérifier que le contenu est valide
    console.log('Contenu HTML généré, longueur:', modalContent.length);
    console.log('Premiers 200 caractères:', modalContent.substring(0, 200));
    
    // Définir le contenu
    try {
        modalBody.innerHTML = modalContent;
        console.log('Contenu modalBody défini avec succès');
        console.log('Vérification - modalBody.innerHTML.length:', modalBody.innerHTML.length);
    } catch (error) {
        console.error('Erreur lors de la définition du contenu:', error);
        alert('Erreur lors de la génération du formulaire: ' + error.message);
        return;
    }
    
    // Vérifier que le contenu a bien été défini
    if (!modalBody.innerHTML || modalBody.innerHTML.trim() === '') {
        console.error('Le contenu de la modale est vide après définition !');
        alert('Erreur: Impossible de générer le formulaire d\'inscription');
        return;
    }
    
    // modalElement et modalTitle sont déjà récupérés au début de la fonction
    console.log('Élément modal trouvé:', modalElement);
    console.log('Titre modal trouvé:', modalTitle);
    
    // FORCER le titre une dernière fois avant d'afficher
    if (modalTitle) {
        modalTitle.textContent = 'Confirmer l\'inscription';
        modalTitle.innerHTML = 'Confirmer l\'inscription';
        console.log('Titre forcé une dernière fois:', modalTitle.textContent);
    }
    
    // Vérifier que le contenu est toujours présent
    console.log('Vérification finale du contenu - longueur:', modalBody.innerHTML.length);
    if (modalBody.innerHTML.trim() === '') {
        console.error('ERREUR CRITIQUE: Le contenu est vide avant affichage !');
        alert('Erreur: Impossible de générer le formulaire. Veuillez réessayer.');
        return;
    }
    
    // Vérifier que Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas disponible !');
        // Essayer d'attendre que Bootstrap soit chargé
        const checkBootstrap = setInterval(() => {
            if (typeof bootstrap !== 'undefined') {
                clearInterval(checkBootstrap);
                console.log('Bootstrap maintenant disponible, affichage de la modale...');
                displayModal(modalElement);
            }
        }, 100);
        
        // Timeout après 5 secondes
        setTimeout(() => {
            clearInterval(checkBootstrap);
            if (typeof bootstrap === 'undefined') {
                alert('Erreur: Bootstrap n\'est pas chargé. Veuillez recharger la page.');
                return;
            }
        }, 5000);
        return;
    }
    
    displayModal(modalElement);
}

// Fonction séparée pour afficher la modale
function displayModal(modalElement) {
    console.log('=== displayModal DÉBUT ===');
    console.log('modalElement:', modalElement);
    
    // Récupérer les éléments
    const modalTitle = modalElement.querySelector('.modal-title');
    const modalBody = document.getElementById('confirm-modal-body');
    
    // Vérifier que les éléments existent
    if (!modalTitle) {
        console.error('ERREUR: modalTitle introuvable !');
        alert('Erreur: Titre de la modale introuvable');
        return;
    }
    
    if (!modalBody) {
        console.error('ERREUR: modalBody introuvable !');
        alert('Erreur: Corps de la modale introuvable');
        return;
    }
    
    // FORCER le titre à être une chaîne de caractères (pas un objet)
    modalTitle.textContent = 'Confirmer l\'inscription';
    modalTitle.innerHTML = 'Confirmer l\'inscription';
    console.log('Titre de la modale défini à:', modalTitle.textContent);
    console.log('Type du titre:', typeof modalTitle.textContent);
    
    // Vérifier que le contenu est bien présent
    console.log('Contenu modalBody présent, longueur:', modalBody.innerHTML.length);
    if (modalBody.innerHTML.trim() === '') {
        console.error('ERREUR CRITIQUE: Le contenu de la modale est vide !');
        alert('Erreur: Le formulaire d\'inscription est vide. Veuillez réessayer.');
        return;
    }
    
    // Vérifier que le contenu ne contient pas [object Object]
    if (modalBody.innerHTML.includes('[object Object]') || modalBody.innerHTML.includes('[Object Object]')) {
        console.error('ERREUR: Le contenu contient [object Object] !');
        console.error('Contenu actuel:', modalBody.innerHTML.substring(0, 500));
        alert('Erreur: Le formulaire contient des données invalides. Veuillez réessayer.');
        return;
    }
    
    // Créer un MutationObserver pour surveiller et corriger le titre si nécessaire
    const titleObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const currentTitle = modalTitle.textContent;
                if (currentTitle && (currentTitle.includes('[object Object]') || currentTitle.includes('[Object Object]'))) {
                    console.warn('MutationObserver: Titre corrompu détecté, correction...');
                    modalTitle.textContent = 'Confirmer l\'inscription';
                    modalTitle.innerHTML = 'Confirmer l\'inscription';
                }
            }
        });
    });
    
    // Observer les changements sur le titre
    titleObserver.observe(modalTitle, {
        childList: true,
        characterData: true,
        subtree: true
    });
    
    // Vérifier si une instance de modale existe déjà et la détruire
    const existingModal = bootstrap.Modal.getInstance(modalElement);
    if (existingModal) {
        console.log('Instance existante trouvée, destruction...');
        existingModal.dispose();
    }
    
    // Créer une nouvelle instance de modale
    console.log('Création d\'une nouvelle instance de modale...');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Afficher la modale
    console.log('Affichage de la modale...');
    
    // Écouter l'événement 'show' de Bootstrap pour s'assurer que le titre et le contenu sont corrects
    modalElement.addEventListener('show.bs.modal', function() {
        console.log('Événement show.bs.modal déclenché');
        // Forcer le titre et le contenu juste avant l'affichage
        const titleEl = modalElement.querySelector('.modal-title');
        if (titleEl) {
            // S'assurer que c'est bien une chaîne
            const titleText = String('Confirmer l\'inscription');
            titleEl.textContent = titleText;
            titleEl.innerHTML = titleText;
            console.log('Titre forcé à "Confirmer l\'inscription", valeur:', titleEl.textContent);
            console.log('Type:', typeof titleEl.textContent);
        } else {
            console.error('Titre introuvable dans show.bs.modal !');
        }
        const bodyEl = document.getElementById('confirm-modal-body');
        if (bodyEl) {
            if (bodyEl.innerHTML.trim() === '') {
                console.error('ERREUR: Le contenu est vide lors de l\'affichage !');
            }
            if (bodyEl.innerHTML.includes('[object Object]')) {
                console.error('ERREUR: Le contenu contient [object Object] lors de l\'affichage !');
            }
        }
    }, { once: true });
    
    try {
        modal.show();
        console.log('modal.show() appelé avec succès');
        
        // Forcer immédiatement le titre et vérifier le contenu
        const titleEl = modalElement.querySelector('.modal-title');
        if (titleEl) {
            const titleText = String('Confirmer l\'inscription');
            titleEl.textContent = titleText;
            titleEl.innerHTML = titleText;
            console.log('Titre forcé après show(), valeur:', titleEl.textContent);
            console.log('Type:', typeof titleEl.textContent);
        } else {
            console.error('Titre introuvable après show() !');
        }
        
        // Vérifier que la modale est bien visible après un court délai
        setTimeout(() => {
            const isVisible = modalElement.classList.contains('show');
            console.log('Modale visible après 100ms:', isVisible);
            
            // Vérifier à nouveau le titre et le contenu
            const titleCheck = modalElement.querySelector('.modal-title');
            const bodyCheck = document.getElementById('confirm-modal-body');
            console.log('Vérification finale - Titre:', titleCheck ? titleCheck.textContent : 'N/A');
            console.log('Vérification finale - Type titre:', titleCheck ? typeof titleCheck.textContent : 'N/A');
            console.log('Vérification finale - Contenu longueur:', bodyCheck ? bodyCheck.innerHTML.length : 0);
            
            // Si le titre contient [object Object], le corriger
            if (titleCheck) {
                const currentTitle = String(titleCheck.textContent || '');
                if (currentTitle.includes('[object Object]') || currentTitle.includes('[Object Object]') || currentTitle === 'undefined') {
                    console.error('ERREUR DÉTECTÉE: Le titre contient [object Object] ou undefined ! Correction...');
                    console.error('Titre actuel:', currentTitle);
                    titleCheck.textContent = 'Confirmer l\'inscription';
                    titleCheck.innerHTML = 'Confirmer l\'inscription';
                    console.log('Titre corrigé:', titleCheck.textContent);
                }
            }
            
            // Si le contenu contient [object Object] ou undefined, afficher une erreur
            if (bodyCheck) {
                const currentContent = String(bodyCheck.innerHTML || '');
                if (currentContent.includes('[object Object]') || currentContent.includes('[Object Object]') || currentContent.includes('undefined')) {
                    console.error('ERREUR DÉTECTÉE: Le contenu contient [object Object] ou undefined !');
                    console.error('Extrait du contenu:', currentContent.substring(0, 500));
                    alert('Erreur: Le formulaire contient des données invalides. Veuillez réessayer.');
                }
            }
            
            // Surveiller et corriger le titre toutes les 100ms pendant 2 secondes
            let checkCount = 0;
            const maxChecks = 20; // 2 secondes à 100ms
            const titleChecker = setInterval(() => {
                checkCount++;
                const titleEl = modalElement.querySelector('.modal-title');
                if (titleEl) {
                    const currentTitle = String(titleEl.textContent || '');
                    if (currentTitle.includes('[object Object]') || currentTitle.includes('[Object Object]') || currentTitle === 'undefined') {
                        console.warn(`Correction automatique du titre (tentative ${checkCount})`);
                        titleEl.textContent = 'Confirmer l\'inscription';
                        titleEl.innerHTML = 'Confirmer l\'inscription';
                    }
                }
                if (checkCount >= maxChecks) {
                    clearInterval(titleChecker);
                    console.log('Surveillance du titre terminée');
                }
            }, 100);
            
            if (!isVisible) {
                console.error('La modale n\'est pas visible !');
                // Essayer de forcer l'affichage manuellement
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                modalElement.setAttribute('aria-hidden', 'false');
                modalElement.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                console.log('Modale forcée à s\'afficher manuellement');
            }
        }, 100);
    } catch (error) {
        console.error('Erreur lors de l\'affichage de la modale:', error);
        console.error('Stack trace:', error.stack);
        alert('Erreur lors de l\'affichage de la modale: ' + error.message);
        
        // Fallback: afficher la modale manuellement
        console.log('Tentative d\'affichage manuel de la modale...');
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-hidden', 'false');
        modalElement.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.setAttribute('data-bs-dismiss', 'modal');
        backdrop.addEventListener('click', function() {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            document.body.classList.remove('modal-open');
            backdrop.remove();
        });
        document.body.appendChild(backdrop);
    }
}

// Soumettre l'inscription
function submitInscription() {
    if (!selectedArcher) {
        alert('Aucun archer sélectionné');
        return;
    }

    const userId = selectedArcher.id || selectedArcher._id || null;
    if (!userId) {
        // Si l'archer n'a pas d'ID (vient du XML), on devra le créer d'abord
        alert('Cet archer doit être créé dans la base de données avant de pouvoir être inscrit.');
        return;
    }

    // Récupérer tous les champs du formulaire
    const departId = document.getElementById('depart-select')?.value || null;
    const saison = document.getElementById('saison')?.value || null;
    const typeCertificatMedical = document.getElementById('type_certificat_medical')?.value || null;
    const typeLicence = document.getElementById('type_licence')?.value || null;
    const creationRenouvellement = document.getElementById('creation_renouvellement')?.checked ? 1 : 0;
    const categorieClassement = document.getElementById('categorie_classement')?.value || null;
    const arme = document.getElementById('arme')?.value || null;
    const mobiliteReduite = document.getElementById('mobilite_reduite')?.checked ? 1 : 0;
    const distance = document.getElementById('distance')?.value ? parseInt(document.getElementById('distance').value) : null;
    const numeroTir = document.getElementById('numero_tir')?.value ? parseInt(document.getElementById('numero_tir').value) : null;
    const duel = document.getElementById('duel')?.checked ? 1 : 0;
    const blason = document.getElementById('blason')?.value ? parseInt(document.getElementById('blason').value) : null;
    const trispot = document.getElementById('trispot')?.checked ? 1 : 0;
    const tarifCompetition = document.getElementById('tarif_competition')?.value || null;
    const modePaiement = document.getElementById('mode_paiement')?.value || 'Non payé';

    // Créer un formulaire pour soumettre l'inscription
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/concours/${concoursId}/inscription`;

    // Ajouter tous les champs
    const fields = {
        'user_id': userId,
        'depart_id': departId,
        'saison': saison,
        'type_certificat_medical': typeCertificatMedical,
        'type_licence': typeLicence,
        'creation_renouvellement': creationRenouvellement,
        'categorie_classement': categorieClassement,
        'arme': arme,
        'mobilite_reduite': mobiliteReduite,
        'distance': distance,
        'numero_tir': numeroTir,
        'duel': duel,
        'blason': blason,
        'trispot': trispot,
        'tarif_competition': tarifCompetition,
        'mode_paiement': modePaiement
    };

    for (const [name, value] of Object.entries(fields)) {
        if (value !== null && value !== '') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

// Retirer une inscription
function removeInscription(inscriptionId, userId) {
    if (!confirm('Voulez-vous retirer cet archer de l\'inscription ?')) {
        return;
    }

    // Utiliser la route DELETE avec user_id
    fetch(`/api/concours/${concoursId}/inscription/${userId}`, {
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
            location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    });
}
