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
    
    // Générer le HTML pour les départs
    let departsHtml = '';
    if (typeof departs !== 'undefined' && departs && departs.length > 0) {
        departsHtml = `
            <div class="mb-3">
                <label for="depart-select" class="form-label">N° départ <span class="text-danger">*</span></label>
                <select id="depart-select" class="form-control" required>
                    <option value="">Sélectionner un départ</option>
                    ${departs.map(depart => {
                        const departId = depart.id || depart._id || '';
                        const departNum = depart.numero || depart.numero_depart || '';
                        const departHeure = depart.heure || '';
                        return `<option value="${departId}">Départ ${departNum} - ${departHeure}</option>`;
                    }).join('')}
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
    } else {
        console.error('Bootstrap n\'est pas disponible');
        alert('Erreur: Bootstrap n\'est pas chargé');
    }
};

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
    
    console.log('Mise à jour des données dans la modale:', { nom, prenom, licence, club });
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
    
    // Afficher la modale avec Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        console.log('Modale affichée');
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
