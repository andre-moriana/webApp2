// JavaScript pour la page de détails du concours (show.php)
(function() {
    var el = document.getElementById('concours-show-page');
    var cfg = el && el.getAttribute('data-config');
    if (cfg) {
        try {
            var c = JSON.parse(cfg);
            window.concoursIdShow = c.concoursId;
            window.concoursDataShow = c.concoursData || {};
        } catch (e) { console.warn('Config concours show parse error', e); }
    }
})();

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
    .then(response => {
        // Vérifier le Content-Type avant de parser le JSON
        const contentType = response.headers.get('content-type');
        console.log('Content-Type de la réponse:', contentType);
        console.log('Status de la réponse:', response.status);
        
        if (!contentType || !contentType.includes('application/json')) {
            // Si ce n'est pas du JSON, lire comme texte pour voir ce qui est retourné
            return response.text().then(text => {
                console.error('Réponse non-JSON reçue:', text.substring(0, 500));
                throw new Error('La réponse du serveur n\'est pas au format JSON. Réponse: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
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

// Créer le plan de peloton pour un concours (Campagne/Nature/3D)
function createPlanPeloton() {
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    if (!concoursId) {
        alert('Erreur: ID du concours non trouvé');
        return;
    }
    const btn = document.getElementById('btn-create-plan-peloton');
    const messageDiv = document.getElementById('plan-peloton-message');
    if (!btn || !messageDiv) {
        alert('Erreur: Éléments du formulaire non trouvés');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
    messageDiv.innerHTML = '';

    const concoursData = typeof concoursDataShow !== 'undefined' ? concoursDataShow : {};
    const nombrePelotons = concoursData.nombre_pelotons || concoursData.nombre_cibles || 0;
    const nombreDepart = concoursData.nombre_depart || 1;
    const nombreArchersParPeloton = concoursData.nombre_archers_par_peloton || concoursData.nombre_tireurs_par_cibles || 0;

    const data = {
        nombre_pelotons: nombrePelotons,
        nombre_depart: nombreDepart,
        nombre_archers_par_peloton: nombreArchersParPeloton
    };

    fetch(`/api/concours/${concoursId}/plan-peloton`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('La réponse du serveur n\'est pas au format JSON.');
            });
        }
        return response.json();
    })
    .then(result => {
        const apiResponse = result.data || result;
        const success = apiResponse.success || result.success;
        const message = apiResponse.message || apiResponse.error || result.message || result.error || 'Opération terminée';

        if (success) {
            messageDiv.innerHTML = '<div class="alert alert-success">' + message + '</div>';
            btn.innerHTML = '<i class="fas fa-check"></i> Plan de peloton créé';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            setTimeout(() => {
                window.location.href = '/concours/' + concoursId + '/plan-peloton';
            }, 1500);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">Erreur: ' + message + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger">Erreur lors de la création: ' + error.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
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
