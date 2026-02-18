// JavaScript pour la page de détails du concours (show.php)
(function() {
    var el = document.getElementById('concours-show-page');
    var cfg = el && el.getAttribute('data-config');
    if (cfg) {
        try {
            var c = JSON.parse(cfg);
            window.concoursIdShow = c.concoursId;
            window.concoursDataShow = c.concoursData || {};
            window.isNature3DOrCampagne = !!(c.isNature3DOrCampagne);
        } catch (e) { console.warn('Config concours show parse error', e); }
    }
})();

var currentEditInscription = null;

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

// Délégation d'événement pour le changement de statut (dropdown) - au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.statut-dropdown-item');
        if (item) {
            e.preventDefault();
            const statut = item.getAttribute('data-statut');
            let inscriptionId = item.getAttribute('data-inscription-id');
            if (!inscriptionId) {
                const row = item.closest('tr');
                inscriptionId = row ? row.getAttribute('data-inscription-id') : null;
            }
            const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
            if (inscriptionId && statut && concoursId) {
                updateStatutInscription(parseInt(inscriptionId, 10), statut);
            }
        }
    });
    initEditInscriptionHandlers();
});

/**
 * Met à jour le statut d'une inscription
 */
function updateStatutInscription(inscriptionId, statut) {
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    if (!concoursId) {
        alert('Erreur: ID du concours non disponible');
        return;
    }

    fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ statut_inscription: statut })
    })
    .then(response => {
        return response.json().then(data => ({ ok: response.ok, data }));
    })
    .then(({ ok, data }) => {
        const success = ok && (data.success !== false) && !data.error;
        if (success) {
            location.reload();
        } else {
            const errMsg = data.error || data.message || (data.data && data.data.error) || 'Erreur inconnue';
            alert('Erreur lors de la mise à jour du statut: ' + errMsg + (ok ? '' : ' (Connexion requise)'));
        }
    })
    .catch(err => {
        console.error('Erreur updateStatutInscription:', err);
        alert('Erreur lors de la mise à jour du statut. Vérifiez que vous êtes connecté.');
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

/**
 * Édite une inscription - charge les données et ouvre la modale
 */
window.editInscription = function(inscriptionId) {
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    if (!concoursId || !inscriptionId) {
        alert('Erreur: Informations manquantes');
        return;
    }

    const modalElement = document.getElementById('editInscriptionModal');
    if (!modalElement) {
        alert('Erreur: La modale d\'édition est introuvable');
        return;
    }

    const form = document.getElementById('edit-inscription-form');
    if (form) {
        form.dataset.inscriptionId = inscriptionId;
    }

    currentEditInscription = null;

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
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        let inscription = (data.success && data.data) ? data.data : (data.id ? data : null);
        if (!inscription) {
            alert('Erreur: Aucune donnée trouvée');
            return;
        }
        if (inscription.success && inscription.data) {
            inscription = inscription.data;
        }
        currentEditInscription = inscription;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        const setCheck = (id, checked) => {
            const el = document.getElementById(id);
            if (el) el.checked = !!checked;
        };

        setVal('edit-saison', inscription.saison);
        setVal('edit-type_certificat_medical', inscription.type_certificat_medical);
        setVal('edit-type_licence', inscription.type_licence);
        let crVal = inscription.creation_renouvellement;
        if (crVal === 1 || crVal === '1') crVal = 'C';
        else if (crVal === 2 || crVal === '2') crVal = 'R';
        setVal('edit-creation_renouvellement', crVal || '');
        setVal('edit-depart-select', inscription.numero_depart);
        setVal('edit-categorie_classement', inscription.categorie_classement);
        setVal('edit-arme', inscription.arme);
        setCheck('edit-mobilite_reduite', inscription.mobilite_reduite);

        const isNature = !!(typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne) || !!(inscription.piquet && String(inscription.piquet).trim() !== '');
        const piquetSection = document.querySelector('.edit-piquet-section');
        const distanceSection = document.querySelector('.edit-distance-section');
        const blasonSection = document.querySelector('.edit-blason-section');
        const duelTrispotSection = document.querySelector('.edit-duel-trispot-section');
        if (piquetSection) {
            piquetSection.classList.toggle('d-none', !isNature);
            setVal('edit-piquet', inscription.piquet);
        }
        if (distanceSection) distanceSection.classList.toggle('d-none', isNature);
        if (blasonSection) blasonSection.classList.toggle('d-none', isNature);
        if (duelTrispotSection) duelTrispotSection.classList.toggle('d-none', isNature);
        if (!isNature) {
            const editDistance = document.getElementById('edit-distance');
            const editBlason = document.getElementById('edit-blason');
            const editDuel = document.getElementById('edit-duel');
            const editTrispot = document.getElementById('edit-trispot');
            if (editDistance) editDistance.value = inscription.distance || '';
            if (editBlason) editBlason.value = inscription.blason || '';
            if (editDuel) editDuel.checked = !!inscription.duel;
            if (editTrispot) editTrispot.checked = !!inscription.trispot;
        }

        loadEditBuvetteProduits(concoursId, inscription.buvette_reservations || [], inscription.token_confirmation);
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement: ' + error.message);
    });
};

function loadEditBuvetteProduits(concoursId, buvetteReservations, tokenConfirmation) {
    const loadingEl = document.getElementById('edit-buvette-loading');
    const listEl = document.getElementById('edit-buvette-produits-list');
    const emptyEl = document.getElementById('edit-buvette-empty');
    const noTokenEl = document.getElementById('edit-buvette-no-token');
    if (!loadingEl || !listEl) return;

    loadingEl.classList.remove('d-none');
    listEl.classList.add('d-none');
    if (emptyEl) emptyEl.classList.add('d-none');
    if (noTokenEl) noTokenEl.classList.add('d-none');

    if (!tokenConfirmation) {
        loadingEl.classList.add('d-none');
        if (noTokenEl) noTokenEl.classList.remove('d-none');
        return;
    }

    const qtyByProduit = {};
    (buvetteReservations || []).forEach(r => {
        const pid = r.produit_id || r.id;
        if (pid) qtyByProduit[pid] = parseInt(r.quantite, 10) || 0;
    });

    fetch('/api/concours/' + concoursId + '/buvette/produits/public', { credentials: 'include', headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            loadingEl.classList.add('d-none');
            const produits = Array.isArray(data) ? data : (data.data || []);
            if (produits.length === 0) {
                if (emptyEl) emptyEl.classList.remove('d-none');
                return;
            }
            if (emptyEl) emptyEl.classList.add('d-none');
            listEl.innerHTML = produits.map(p => {
                const pid = p.id || '';
                const qty = qtyByProduit[pid] || 0;
                const prix = p.prix != null ? parseFloat(p.prix).toFixed(2) + ' €' : '';
                const unite = p.unite || 'portion';
                return '<div class="d-flex align-items-center justify-content-between mb-2"><label class="mb-0 flex-grow-1">' + (p.libelle || '') + (prix ? ' <span class="text-muted">(' + prix + ')</span>' : '') + '</label><input type="number" class="form-control form-control-sm buvette-qty" data-produit-id="' + pid + '" min="0" value="' + qty + '" style="width:70px;"> <span class="ms-1 small text-muted">' + unite + '</span></div>';
            }).join('');
            listEl.classList.remove('d-none');
        })
        .catch(() => {
            loadingEl.classList.add('d-none');
            if (emptyEl) { emptyEl.textContent = 'Erreur de chargement.'; emptyEl.classList.remove('d-none'); }
        });
}

function getEditBuvetteItems() {
    const container = document.getElementById('edit-buvette-produits-list');
    const inputs = container ? container.querySelectorAll('.buvette-qty') : [];
    const items = [];
    inputs.forEach(inp => {
        const qty = parseInt(inp.value, 10) || 0;
        const pid = inp.getAttribute('data-produit-id');
        if (qty > 0 && pid) items.push({ produit_id: parseInt(pid, 10), quantite: qty });
    });
    return items;
}

function initEditInscriptionHandlers() {
    const btnConfirmEdit = document.getElementById('btn-confirm-edit');
    if (!btnConfirmEdit) return;

    btnConfirmEdit.addEventListener('click', function() {
        const form = document.getElementById('edit-inscription-form');
        const inscriptionId = form?.dataset?.inscriptionId;
        const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;

        if (!concoursId || !inscriptionId) {
            alert('Erreur: Informations manquantes');
            return;
        }

        const piquetSection = document.querySelector('.edit-piquet-section');
        const isNature = piquetSection ? !piquetSection.classList.contains('d-none') : (typeof isNature3DOrCampagne !== 'undefined' && isNature3DOrCampagne);
        const departSelect = document.getElementById('edit-depart-select');
        const numeroDepart = departSelect?.value || (currentEditInscription?.numero_depart ?? '');
        const updateData = {
            saison: document.getElementById('edit-saison')?.value || '',
            type_certificat_medical: document.getElementById('edit-type_certificat_medical')?.value || '',
            type_licence: document.getElementById('edit-type_licence')?.value || '',
            creation_renouvellement: document.getElementById('edit-creation_renouvellement')?.value || '',
            numero_depart: numeroDepart,
            categorie_classement: document.getElementById('edit-categorie_classement')?.value || '',
            arme: document.getElementById('edit-arme')?.value || '',
            mobilite_reduite: document.getElementById('edit-mobilite_reduite')?.checked ? 1 : 0,
            numero_tir: currentEditInscription?.numero_tir ?? '',
        };

        if (isNature) {
            updateData.piquet = document.getElementById('edit-piquet')?.value || '';
        } else {
            updateData.distance = document.getElementById('edit-distance')?.value || '';
            updateData.blason = document.getElementById('edit-blason')?.value || '';
            updateData.duel = document.getElementById('edit-duel')?.checked ? 1 : 0;
            updateData.trispot = document.getElementById('edit-trispot')?.checked ? 1 : 0;
        }

        btnConfirmEdit.disabled = true;
        btnConfirmEdit.textContent = 'Enregistrement...';

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
            btnConfirmEdit.disabled = false;
            btnConfirmEdit.textContent = 'Enregistrer';
            if (data.success !== false && !data.error) {
                const token = currentEditInscription?.token_confirmation;
                if (token) {
                    const items = getEditBuvetteItems();
                    return fetch('/api/concours/' + concoursId + '/buvette/reservations', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ token_confirmation: token, items: items })
                    }).then(r => r.json()).then(buvRes => {
                        if (buvRes && !buvRes.success && buvRes.error) {
                            console.warn('Buvette:', buvRes.error);
                        }
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editInscriptionModal'));
                        if (modal) modal.hide();
                        location.reload();
                    });
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('editInscriptionModal'));
                if (modal) modal.hide();
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            btnConfirmEdit.disabled = false;
            btnConfirmEdit.textContent = 'Enregistrer';
            alert('Erreur lors de la mise à jour: ' + error.message);
        });
    });
}
