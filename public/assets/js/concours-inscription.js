// Gestion de l'inscription aux concours

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
    resultsList.innerHTML = '';

    archers.forEach(archer => {
        const card = document.createElement('div');
        card.className = 'archer-card';
        card.onclick = () => selectArcher(archer, card);

        const nom = archer.nom || archer.name || archer.NOM || 'N/A';
        const prenom = archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A';
        const licence = archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A';
        const club = archer.club_name || archer.CLUB || 'N/A';
        const dateNaissance = archer.birth_date || archer.birthDate || archer.DATENAISSANCE || 'N/A';
        const genre = archer.gender || archer.GENRE || 'N/A';

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

        resultsList.appendChild(card);
    });
}

// Sélectionner un archer
function selectArcher(archer, cardElement) {
    // Retirer la sélection précédente
    document.querySelectorAll('.archer-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Sélectionner la nouvelle carte
    cardElement.classList.add('selected');
    selectedArcher = archer;

    // Afficher la modale de confirmation
    showConfirmModal(archer);
}

// Afficher la modale de confirmation avec formulaire complet
function showConfirmModal(archer) {
    // Vérifier que l'archer est bien défini
    if (!archer) {
        console.error('showConfirmModal: archer est undefined');
        alert('Erreur: Aucune information d\'archer disponible');
        return;
    }
    
    const modalBody = document.getElementById('confirm-modal-body');
    if (!modalBody) {
        console.error('showConfirmModal: modal-body introuvable');
        alert('Erreur: Élément modal introuvable');
        return;
    }
    
    // Extraire les informations de l'archer avec des valeurs par défaut
    const nom = archer.nom || archer.name || archer.NOM || 'N/A';
    const prenom = archer.prenom || archer.first_name || archer.firstName || archer.PRENOM || 'N/A';
    const licence = archer.licence_number || archer.licenceNumber || archer.IDLicence || 'N/A';
    const club = archer.club_name || archer.CLUB || 'N/A';
    const gender = archer.gender || archer.GENRE || '';
    const birthDate = archer.birth_date || archer.birthDate || archer.DATENAISSANCE || '';
    
    console.log('showConfirmModal - archer:', archer);
    console.log('showConfirmModal - nom:', nom, 'prenom:', prenom);

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

    modalBody.innerHTML = `
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

    // S'assurer que le contenu est bien défini avant d'afficher
    if (!modalBody.innerHTML || modalBody.innerHTML.trim() === '') {
        console.error('showConfirmModal: Le contenu de la modale est vide');
        alert('Erreur: Impossible de générer le formulaire d\'inscription');
        return;
    }
    
    // Initialiser et afficher la modale Bootstrap
    const modalElement = document.getElementById('confirmInscriptionModal');
    if (!modalElement) {
        console.error('showConfirmModal: Élément modal introuvable');
        alert('Erreur: Modal introuvable');
        return;
    }
    
    // Vérifier si une instance de modale existe déjà et la détruire
    const existingModal = bootstrap.Modal.getInstance(modalElement);
    if (existingModal) {
        existingModal.dispose();
    }
    
    // Créer une nouvelle instance de modale
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // S'assurer que le titre de la modale est correct
    const modalTitle = modalElement.querySelector('.modal-title');
    if (modalTitle) {
        modalTitle.textContent = 'Confirmer l\'inscription';
    }
    
    // Afficher la modale
    modal.show();
    
    console.log('showConfirmModal: Modale affichée avec succès');
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
