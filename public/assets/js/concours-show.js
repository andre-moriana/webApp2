// JavaScript pour la page de détails du concours (show.php)

// Créer le plan de cible pour un concours
function createPlanCible() {
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    
    if (!concoursId) {
        alert('Erreur: ID du concours non trouvé');
        return;
    }
    
    const btn = document.getElementById('btn-create-plan-cible');
    const messageDiv = document.getElementById('plan-cible-message');
    
    if (!btn || !messageDiv) {
        alert('Erreur: Éléments du formulaire non trouvés');
        return;
    }
    
    // Désactiver le bouton pendant la requête
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
    messageDiv.innerHTML = '';
    
    // Récupérer les données du concours depuis la page
    const concoursData = typeof concoursDataShow !== 'undefined' ? concoursDataShow : {};
    const nombreCibles = concoursData.nombre_cibles || 0;
    const nombreDepart = concoursData.nombre_depart || 1;
    const nombreTireursParCibles = concoursData.nombre_tireurs_par_cibles || 0;
    
    const data = {
        nombre_cibles: nombreCibles,
        nombre_depart: nombreDepart,
        nombre_tireurs_par_cibles: nombreTireursParCibles
    };
    
    fetch(`/api/concours/${concoursId}/plan-cible`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        console.log('Réponse création plan de cible:', result);
        
        // Utiliser unwrapData si nécessaire
        const apiResponse = result.data || result;
        const success = apiResponse.success || result.success;
        const message = apiResponse.message || apiResponse.error || result.message || result.error || 'Opération terminée';
        
        if (success) {
            messageDiv.innerHTML = '<div class="alert alert-success">' + message + '</div>';
            btn.innerHTML = '<i class="fas fa-check"></i> Plan de cible créé';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">Erreur: ' + message + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bullseye"></i> Créer le plan de cible';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        messageDiv.innerHTML = '<div class="alert alert-danger">Erreur lors de la création: ' + error.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bullseye"></i> Créer le plan de cible';
    });
}

// Retirer une inscription par ID
function removeInscription(inscriptionId) {
    if (!confirm('Voulez-vous retirer cet archer de l\'inscription ?')) {
        return;
    }

    // Utiliser concoursIdShow défini dans la page PHP
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    
    if (!concoursId) {
        alert('Erreur: ID du concours non trouvé');
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
