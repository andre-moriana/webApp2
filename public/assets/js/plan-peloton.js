/**
 * Plan de peloton - Création et assignation des archers
 */

(function() {
    'use strict';

    // Création du plan (bouton quand aucun plan n'existe)
    var btnCreate = document.getElementById('btn-create-plan-peloton-empty');
    var msgCreate = document.getElementById('plan-peloton-create-message');
    if (btnCreate && msgCreate) {
        btnCreate.addEventListener('click', function() {
            var concoursId = btnCreate.getAttribute('data-concours-id');
            var nombrePelotons = parseInt(btnCreate.getAttribute('data-nombre-pelotons'), 10) || 0;
            var nombreDepart = parseInt(btnCreate.getAttribute('data-nombre-depart'), 10) || 1;
            var nombreArchers = parseInt(btnCreate.getAttribute('data-nombre-archers'), 10) || 0;
            if (!concoursId) {
                alert('ID du concours manquant');
                return;
            }
            if (nombrePelotons <= 0 || nombreArchers <= 0) {
                alert('Veuillez configurer le nombre de pelotons et d\'archers par peloton dans les paramètres du concours.');
                return;
            }
            btnCreate.disabled = true;
            btnCreate.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
            fetch('/api/concours/' + concoursId + '/plan-peloton', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    nombre_pelotons: nombrePelotons,
                    nombre_depart: nombreDepart,
                    nombre_archers_par_peloton: nombreArchers
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success) {
                    window.location.href = '/concours/' + concoursId + '/plan-peloton';
                } else {
                    msgCreate.innerHTML = '<div class="alert alert-danger">' + (result.error || 'Erreur') + '</div>';
                    btnCreate.disabled = false;
                    btnCreate.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
                }
            })
            .catch(function(err) {
                msgCreate.innerHTML = '<div class="alert alert-danger">Erreur : ' + (err.message || 'réseau') + '</div>';
                btnCreate.disabled = false;
                btnCreate.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
            });
        });
    }

    // Modale assignation / libération
    var modalEl = document.getElementById('pelotonAssignModal');
    var listContainer = document.getElementById('peloton-archers-list');
    var infoContainer = document.getElementById('peloton-assign-info');
    var releaseBtn = document.getElementById('peloton-liberer-btn');
    var currentTarget = null;
    var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

    function setListMessage(msg, type) {
        if (!listContainer) return;
        var cssClass = type === 'danger' ? 'danger' : type === 'warning' ? 'warning' : 'info';
        listContainer.innerHTML = '<div class="alert alert-' + cssClass + '">' + msg + '</div>';
    }

    function fetchArchersDispo(target) {
        if (!target || !listContainer) return;
        setListMessage('Chargement...');
        fetch('/api/concours/' + target.concoursId + '/plan-peloton/' + target.depart + '/archers-dispo', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'include'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !Array.isArray(data.data)) {
                setListMessage(data.error || 'Erreur', 'danger');
                return;
            }
            var archers = data.data;
            if (archers.length === 0) {
                setListMessage('Aucun archer disponible.', 'warning');
                return;
            }
            listContainer.innerHTML = '';
            archers.forEach(function(a) {
                var div = document.createElement('div');
                div.className = 'list-group-item d-flex justify-content-between align-items-center';
                div.innerHTML = '<div><strong>' + (a.user_nom || a.nom || '') + '</strong> (' + (a.numero_licence || '') + ')' +
                    (a.piquet ? ' - Piquet ' + a.piquet : '') + '</div>' +
                    '<button type="button" class="btn btn-sm btn-primary js-assign-peloton" ' +
                    'data-user-nom="' + (a.user_nom || '') + '" data-numero-licence="' + (a.numero_licence || '') + '" ' +
                    'data-id-club="' + (a.id_club || '') + '" data-piquet="' + (a.piquet || '') + '">Affecter</button>';
                listContainer.appendChild(div);
            });
        })
        .catch(function() { setListMessage('Erreur réseau', 'danger'); });
    }

    document.addEventListener('click', function(e) {
        var item = e.target.closest('.blason-item');
        if (!item) return;
        var assignable = item.getAttribute('data-assignable') === '1';
        currentTarget = {
            concoursId: item.dataset.concoursId,
            depart: item.dataset.depart,
            peloton: item.dataset.peloton,
            position: item.dataset.position,
            numeroLicence: item.dataset.numeroLicence,
            userNom: item.dataset.userNom
        };
        if (infoContainer) {
            infoContainer.textContent = 'Départ ' + currentTarget.depart + ' - Peloton ' + currentTarget.peloton +
                ' - Position ' + currentTarget.position + ' : sélectionnez un archer à affecter';
        }
        if (releaseBtn) releaseBtn.style.display = assignable ? 'none' : 'block';
        fetchArchersDispo(currentTarget);
        if (modalInstance) modalInstance.show();
    });

    if (listContainer) {
        listContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-assign-peloton');
            if (!btn || !currentTarget) return;
            var payload = {
                numero_depart: parseInt(currentTarget.depart, 10),
                numero_peloton: parseInt(currentTarget.peloton, 10),
                position_archer: currentTarget.position,
                user_nom: btn.getAttribute('data-user-nom') || '',
                numero_licence: btn.getAttribute('data-numero-licence') || '',
                id_club: btn.getAttribute('data-id-club') || null,
                piquet: btn.getAttribute('data-piquet') || null
            };
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-peloton/assign', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) window.location.reload();
                else setListMessage(data.error || 'Erreur', 'danger');
            })
            .catch(function() { setListMessage('Erreur', 'danger'); });
        });
    }

    if (releaseBtn) {
        releaseBtn.addEventListener('click', function() {
            if (!currentTarget) return;
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-peloton/' + currentTarget.depart + '/liberer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    numero_peloton: parseInt(currentTarget.peloton, 10),
                    position_archer: currentTarget.position
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) window.location.reload();
                else setListMessage(data.error || 'Erreur', 'danger');
            });
        });
    }
})();
