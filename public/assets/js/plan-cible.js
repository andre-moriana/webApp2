/**
 * Plan de cible - Création et assignation des archers
 */

(function() {
    'use strict';

    var canEditPlanCible = document.querySelector('[data-can-edit-plan]')?.getAttribute('data-can-edit-plan') === '1';

    // Création du plan (bouton quand aucun plan n'existe)
    var btnCreate = document.getElementById('btn-create-plan-cible-empty');
    var msgCreate = document.getElementById('plan-cible-create-message');
    if (btnCreate && msgCreate) {
        btnCreate.addEventListener('click', function() {
            var concoursId = btnCreate.getAttribute('data-concours-id');
            var nombreCibles = parseInt(btnCreate.getAttribute('data-nombre-cibles'), 10) || 0;
            var nombreDepart = parseInt(btnCreate.getAttribute('data-nombre-depart'), 10) || 1;
            var nombreTireurs = parseInt(btnCreate.getAttribute('data-nombre-tireurs'), 10) || 0;
            msgCreate.innerHTML = '<div class="alert alert-info">' +
                '<strong>Vérification des valeurs envoyées :</strong><br>' +
                'Concours ID : ' + concoursId + '<br>' +
                'Nombre de cibles : ' + nombreCibles + '<br>' +
                'Nombre de départs : ' + nombreDepart + '<br>' +
                'Nombre d\'archers par cible : ' + nombreTireurs +
                '</div>';
            if (!concoursId) { alert('ID du concours manquant'); return; }
            btnCreate.disabled = true;
            btnCreate.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
            fetch('/api/concours/' + concoursId + '/plan-cible', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    nombre_cibles: nombreCibles,
                    nombre_depart: nombreDepart,
                    nombre_tireurs_par_cibles: nombreTireurs
                })
            })
            .then(function(r) {
                var ct = r.headers.get('content-type');
                if (!ct || ct.indexOf('application/json') === -1) {
                    return r.text().then(function(t) { throw new Error('Réponse non-JSON'); });
                }
                return r.json();
            })
            .then(function(result) {
                var ok = (result.data && result.data.success) || result.success;
                if (ok) {
                    window.location.href = '/concours/' + concoursId + '/plan-cible';
                } else {
                    var errorMsg = result.message || result.error || (result.data && (result.data.message || result.data.error)) || 'Erreur inconnue';
                    var fullErrorMsg = '<div class="alert alert-danger"><strong>Erreur:</strong> ' + errorMsg + '</div>';
                    fullErrorMsg += '<div class="alert alert-warning" style="margin-top: 10px;"><strong>Réponse JSON complète:</strong><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
                    msgCreate.innerHTML = fullErrorMsg;
                    btnCreate.disabled = false;
                    btnCreate.innerHTML = '<i class="fas fa-bullseye"></i> Créer le plan de cible';
                }
            })
            .catch(function(err) {
                msgCreate.innerHTML = '<div class="alert alert-danger">Erreur : ' + (err.message || 'réseau') + '</div>';
                btnCreate.disabled = false;
                btnCreate.innerHTML = '<i class="fas fa-bullseye"></i> Créer le plan de cible';
            });
        });
    }

    // Mettre à jour le flag trispot quand on change la sélection du type de blason
    document.addEventListener('DOMContentLoaded', function() {
        var dropdowns = document.querySelectorAll('.blason-type-select-dropdown');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('change', function() {
                var form = this.closest('.blason-type-form');
                var trispotInput = form ? form.querySelector('.trispot-flag') : null;
                var selectedOption = this.options[this.selectedIndex];
                var trispotValue = selectedOption ? selectedOption.getAttribute('data-trispot') : null;
                if (trispotInput && trispotValue !== null) {
                    trispotInput.value = trispotValue;
                }
            });
        });
    });

    // Modale assignation / libération
    var modalElement = document.getElementById('blasonAssignModal');
    var listContainer = document.getElementById('blason-archers-list');
    var infoContainer = document.getElementById('blason-modal-info');
    var releaseContainer = document.getElementById('blason-modal-release');
    var releaseButton = document.getElementById('btn-liberer-emplacement');
    var modalInstance = null;
    var currentTarget = null;
    var fallbackBackdrop = null;

    var hasBootstrapModal = modalElement && typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined';
    if (hasBootstrapModal && modalElement) {
        modalInstance = new bootstrap.Modal(modalElement);
    }

    function showModalFallback() {
        if (!modalElement) return;
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        if (!fallbackBackdrop) {
            fallbackBackdrop = document.createElement('div');
            fallbackBackdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(fallbackBackdrop);
            fallbackBackdrop.addEventListener('click', hideModalFallback);
        }
    }

    function hideModalFallback() {
        if (!modalElement) return;
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        if (fallbackBackdrop) {
            fallbackBackdrop.removeEventListener('click', hideModalFallback);
            fallbackBackdrop.remove();
            fallbackBackdrop = null;
        }
    }

    if (modalElement && !hasBootstrapModal) {
        modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(function(button) {
            button.addEventListener('click', hideModalFallback);
        });
    }

    function setListMessage(message, type) {
        type = type || 'info';
        if (!listContainer) return;
        var cssClass = type === 'danger' ? 'alert-danger' : (type === 'warning' ? 'alert-warning' : 'alert-info');
        listContainer.innerHTML = '<div class="alert ' + cssClass + '">' + message + '</div>';
    }

    function formatTargetInfo(target) {
        var trispotLabel = target.trispot === 1 ? 'Trispot' : 'Blason';
        var positionLabel = target.trispot === 1 && target.colonne ? 'Colonne ' + target.colonne : 'Position ' + target.position;
        var distanceText = target.distance ? ' - ' + target.distance + 'm' : '';
        return 'Depart ' + target.depart + ' - Cible ' + target.cible + ' - ' + trispotLabel + ' ' + target.blason + distanceText + ' - ' + positionLabel;
    }

    function fetchArchersDisponibles(target) {
        if (!target || !listContainer) return;
        if (!target.blason) {
            setListMessage('Aucun type de blason defini pour cette cible.', 'warning');
            return;
        }
        setListMessage('Chargement des archers disponibles...');
        var positionQuery = target.position || '';
        if (target.trispot === 1) {
            if (target.colonne) {
                positionQuery = target.colonne;
            } else if (typeof target.position === 'string') {
                var match = target.position.match(/^([A-D])/i);
                if (match) positionQuery = match[1].toUpperCase();
            }
        }
        var params = new URLSearchParams({
            blason: target.blason,
            trispot: target.trispot,
            position_archer: positionQuery
        });
        var requestUrl = '/api/concours/' + target.concoursId + '/plan-cible/' + target.depart + '/archers-dispo?' + params.toString();
        fetch(requestUrl, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'include'
        })
        .then(function(response) {
            var contentType = response.headers.get('content-type') || '';
            return response.text().then(function(text) {
                return { ok: response.ok, status: response.status, contentType: contentType, text: text };
            });
        })
        .then(function(result) {
            var data = null;
            if (result.contentType.indexOf('application/json') !== -1) {
                try { data = JSON.parse(result.text); } catch (e) { data = null; }
            }
            if (!result.ok) throw new Error('HTTP ' + result.status + ': ' + result.text.substring(0, 200));
            if (!data) throw new Error('Reponse non-JSON: ' + result.text.substring(0, 200));
            if (!data.success || !Array.isArray(data.data)) {
                setListMessage(data.error || 'Impossible de charger les archers.', 'danger');
                return;
            }
            var archers = data.data;
            if (archers.length === 0) {
                setListMessage('Aucun archer sans cible pour ce blason/trispot.', 'warning');
                return;
            }
            listContainer.innerHTML = '';
            archers.forEach(function(archer) {
                var name = (archer.user_nom || ((archer.prenom || '') + ' ' + (archer.nom || '')).trim()) || 'Archer';
                var club = archer.club_name ? ' - ' + archer.club_name : '';
                var licence = archer.numero_licence ? ' (' + archer.numero_licence + ')' : '';
                var item = document.createElement('div');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                item.innerHTML = '<div><div><strong>' + name + '</strong>' + licence + '</div><div class="text-muted" style="font-size: 0.9em;">' + (club || '').replace(' - ', '') + '</div></div>' +
                    '<button type="button" class="btn btn-sm btn-primary js-assign-archer user-nom-' + (archer.user_nom || '') + '-numero-licence-' + (archer.numero_licence || '') + '" data-user-nom="' + (archer.user_nom || '') + '" data-numero-licence="' + (archer.numero_licence || '') + '" data-id-club="' + (archer.id_club || '') + '">Affecter</button>';
                listContainer.appendChild(item);
            });
        })
        .catch(function(error) {
            setListMessage(error.message || 'Erreur lors du chargement des archers.', 'danger');
        });
    }

    function openModalForItem(item) {
        if (!item || !modalElement) return;
        var trispotValue = item.dataset.trispot === '1' ? 1 : 0;
        currentTarget = {
            concoursId: item.dataset.concoursId,
            depart: item.dataset.depart,
            cible: item.dataset.cible,
            position: item.dataset.position,
            colonne: item.dataset.colonne || null,
            blason: item.dataset.blason,
            trispot: trispotValue,
            distance: item.dataset.distance,
            userId: item.dataset.userId || null,
            numeroLicence: item.dataset.numeroLicence || null,
            userNom: item.dataset.userNom || null
        };
        if (infoContainer) infoContainer.textContent = formatTargetInfo(currentTarget);
        if (releaseContainer) {
            var isAssigned = currentTarget.userId || currentTarget.numeroLicence;
            releaseContainer.style.display = isAssigned ? 'block' : 'none';
        }
        fetchArchersDisponibles(currentTarget);
        if (modalInstance) modalInstance.show();
        else showModalFallback();
    }

    document.addEventListener('click', function(event) {
        if (!canEditPlanCible) return;
        var item = event.target.closest('.blason-item');
        if (item) openModalForItem(item);
    });

    if (listContainer) {
        listContainer.addEventListener('click', function(event) {
            var button = event.target.closest('.js-assign-archer');
            if (!button || !currentTarget) return;
            var userNom = button.getAttribute('data-user-nom');
            var numeroLicence = button.getAttribute('data-numero-licence');
            if (!numeroLicence) {
                setListMessage('Archer sans numero_licence.', 'danger');
                return;
            }
            var positionToAssign = currentTarget.position;
            if (currentTarget.trispot === 1) {
                if (currentTarget.colonne) positionToAssign = currentTarget.colonne;
                else if (typeof currentTarget.position === 'string') {
                    var m = currentTarget.position.match(/^([A-D])/i);
                    if (m) positionToAssign = m[1].toUpperCase();
                }
            }
            var payload = {
                numero_depart: parseInt(currentTarget.depart, 10),
                numero_cible: parseInt(currentTarget.cible, 10),
                position_archer: positionToAssign,
                id_club: button.getAttribute('data-id-club') || null,
                blason: currentTarget.blason ? parseInt(currentTarget.blason, 10) : null,
                distance: currentTarget.distance ? parseInt(currentTarget.distance, 10) : null,
                trispot: currentTarget.trispot,
                user_nom: userNom || '',
                numero_licence: numeroLicence || ''
            };
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-cible/assign', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(payload)
            })
            .then(function(response) {
                var ct = response.headers.get('content-type') || '';
                return response.text().then(function(text) {
                    return { ok: response.ok, status: response.status, contentType: ct, text: text };
                });
            })
            .then(function(result) {
                var data = null;
                if (result.contentType.indexOf('application/json') !== -1) {
                    try { data = JSON.parse(result.text); } catch (e) { data = null; }
                }
                if (!result.ok) throw new Error('HTTP ' + result.status);
                if (!data) throw new Error('Reponse non-JSON');
                if (data.success) { window.location.reload(); return; }
                setListMessage(data.error || 'Erreur lors de l\'assignation.', 'danger');
            })
            .catch(function() { setListMessage('Erreur lors de l\'assignation.', 'danger'); });
        });
    }

    if (releaseButton) {
        releaseButton.addEventListener('click', function() {
            if (!currentTarget) return;
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-cible/' + currentTarget.depart + '/liberer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    numero_cible: parseInt(currentTarget.cible, 10),
                    position_archer: currentTarget.position
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) { window.location.reload(); return; }
                setListMessage(data.error || 'Erreur lors de la liberation.', 'danger');
            })
            .catch(function() { setListMessage('Erreur lors de la liberation.', 'danger'); });
        });
    }
})();
