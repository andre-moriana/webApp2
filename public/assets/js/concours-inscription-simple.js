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
});

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
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            licence_number: licence
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Erreur de recherche');
            });
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            alert('Archer non trouvé: ' + (data.error || 'Erreur inconnue'));
            return;
        }
        
        // Stocker les données de l'archer
        selectedArcher = data.data;
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
    if (!selectedArcher) return;
    
    // Remplir les infos dans le modal
    document.getElementById('modal-archer-nom').textContent = selectedArcher.name || 'N/A';
    document.getElementById('modal-archer-prenom').textContent = selectedArcher.first_name || 'N/A';
    document.getElementById('modal-archer-licence').textContent = selectedArcher.licence_number || 'N/A';
    document.getElementById('modal-archer-club').textContent = selectedArcher.club || 'N/A';
    
    // Afficher le modal de confirmation
    const modal = new bootstrap.Modal(document.getElementById('confirmInscriptionModal'));
    modal.show();
    
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
    
    // Récupérer les données du formulaire
    const formData = {
        numero_depart: numeroDepart,
        numero_licence: selectedArcher.licence_number,
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
