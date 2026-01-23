// Gestion de l'inscription aux concours

// Variable globale pour stocker l'archer sélectionné
let selectedArcher = null;

// DÉFINIR showConfirmModal IMMÉDIATEMENT au début du fichier
window.showConfirmModal = function(archer) {
    console.log('=== showConfirmModal DÉBUT ===');
    console.log('Archer reçu:', archer);
    
    if (!archer) {
        alert('Erreur: Aucune information d\'archer disponible');
        return;
    }
    
    const modalElement = document.getElementById('confirmInscriptionModal');
    const modalBody = document.getElementById('confirm-modal-body');
    const modalTitle = modalElement ? modalElement.querySelector('.modal-title') : null;
    
    if (!modalElement || !modalBody) {
        alert('Erreur: Modal introuvable');
        return;
    }
    
    // FORCER le titre
    if (modalTitle) {
        modalTitle.textContent = 'Confirmer l\'inscription';
        modalTitle.innerHTML = 'Confirmer l\'inscription';
    }
    
    // Extraire les informations avec String()
    const nom = String(archer.nom || archer.name || 'N/A');
    const prenom = String(archer.prenom || archer.first_name || archer.firstName || 'N/A');
    const licence = String(archer.licence_number || archer.licenceNumber || 'N/A');
    const club = String(archer.club_name || archer.CLUB || 'N/A');
    
    // Contenu simple pour tester
    modalBody.innerHTML = `
        <div class="archer-summary mb-3 p-3 bg-light rounded">
            <h5>Informations de l'archer</h5>
            <p><strong>Nom:</strong> ${nom} ${prenom}</p>
            <p><strong>Licence:</strong> ${licence}</p>
            <p><strong>Club:</strong> ${club}</p>
        </div>
    `;
    
    // Afficher avec Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) existingModal.dispose();
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
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
    console.log('showConfirmModal existe ?', typeof window.showConfirmModal);
    console.log('showConfirmModal dans scope ?', typeof showConfirmModal);
    
    // Appeler DIRECTEMENT window.showConfirmModal sans vérification
    console.log('APPEL DIRECT DE window.showConfirmModal...');
    try {
        // Appel direct sans condition
        window.showConfirmModal(archer);
        console.log('window.showConfirmModal appelée - retour immédiat');
    } catch (error) {
        console.error('ERREUR lors de l\'appel à window.showConfirmModal:', error);
        console.error('Message:', error.message);
        console.error('Stack trace:', error.stack);
        alert('Erreur lors de l\'ouverture de la modale: ' + error.message);
    }
    console.log('Après l\'appel à showConfirmModal');
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
