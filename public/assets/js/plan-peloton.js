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
            var nombrePelotons = parseInt(btnCreate.getAttribute('data-nombre-pelotons'), 10) || 4;
            var nombreDepart = parseInt(btnCreate.getAttribute('data-nombre-depart'), 10) || 1;
            var nombreArchers = parseInt(btnCreate.getAttribute('data-nombre-archers'), 10) || 4;
            if (!concoursId) {
                alert('ID du concours manquant');
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
                    msgCreate.innerHTML = '<div class="alert alert-danger">' + ((result.data && result.data.error) || result.error || result.message || 'Erreur') + '</div>';
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
            var archers = (data.data && Array.isArray(data.data.data)) ? data.data.data : (Array.isArray(data.data) ? data.data : null);
            var ok = data.success && archers !== null;
            if (!ok) {
                var errMsg = (data.data && data.data.error) || data.error || data.message || 'Erreur';
                setListMessage(errMsg, 'danger');
                return;
            }
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

    var canEditPlanPeloton = document.querySelector('[data-can-edit-plan]')?.getAttribute('data-can-edit-plan') === '1';
    var planContainer = document.querySelector('.concours-create-container[data-can-edit-plan]');
    var canReleaseAdminDirigeant = planContainer?.getAttribute('data-can-release-admin-dirigeant') === '1';
    var currentUserLicence = (planContainer?.getAttribute('data-current-user-licence') || '').trim();

    // Règles configurables (valeurs initiales depuis data-*)
    function getPelotonRules() {
        if (!planContainer) {
            return {
                maxClubPerPeloton: 2,
                maxPiquetColors: 2,
                pairPerColor: true
            };
        }
        var maxClub = parseInt(planContainer.getAttribute('data-max-club-per-peloton') || '0', 10) || 1;
        var maxColors = parseInt(planContainer.getAttribute('data-max-piquet-colors') || '0', 10) || 2;
        var pairPerColor = (planContainer.getAttribute('data-pair-per-color') || '1') === '1';
        return {
            maxClubPerPeloton: maxClub,
            maxPiquetColors: maxColors,
            pairPerColor: pairPerColor
        };
    }

    // Si les inputs de règles existent et que l'utilisateur peut éditer, synchroniser avec les data-*
    (function initPelotonRulesUI() {
        if (!planContainer || !canEditPlanPeloton) return;
        var maxClubInput = document.getElementById('rule-max-club-input');
        var maxPiquetInput = document.getElementById('rule-max-piquet-input');
        var pairCheckbox = document.getElementById('rule-pair-per-color-input');
        var maxClubDisplay = document.getElementById('rule-max-club-display');
        var maxPiquetDisplay = document.getElementById('rule-max-piquet-display');

        if (maxClubInput) {
            maxClubInput.addEventListener('change', function () {
                var v = parseInt(maxClubInput.value || '0', 10);
                if (!v || v < 1) v = 1;
                maxClubInput.value = v;
                planContainer.setAttribute('data-max-club-per-peloton', String(v));
                if (maxClubDisplay) maxClubDisplay.textContent = String(v);
            });
        }
        if (maxPiquetInput) {
            maxPiquetInput.addEventListener('change', function () {
                var v = parseInt(maxPiquetInput.value || '0', 10);
                if (!v || v < 1) v = 1;
                if (v > 4) v = 4;
                maxPiquetInput.value = v;
                planContainer.setAttribute('data-max-piquet-colors', String(v));
                if (maxPiquetDisplay) maxPiquetDisplay.textContent = String(v);
            });
        }
        if (pairCheckbox) {
            pairCheckbox.addEventListener('change', function () {
                planContainer.setAttribute('data-pair-per-color', pairCheckbox.checked ? '1' : '0');
            });
        }
    })();

    document.addEventListener('click', function(e) {
        var item = e.target.closest('.blason-item');
        if (!item) return;
        if (!canEditPlanPeloton) return;
        var assignable = item.getAttribute('data-assignable') === '1';
        var assignedLicence = (item.dataset.numeroLicence || '').trim();
        var isOwnAssignment = !!assignedLicence && assignedLicence === currentUserLicence;
        var canRelease = !assignable && (canReleaseAdminDirigeant || isOwnAssignment);
        // Position déjà affectée sans droit de libération : interdire le clic (ne pas ouvrir la modale)
        if (!assignable && !canRelease) return;
        currentTarget = {
            concoursId: item.dataset.concoursId,
            depart: item.dataset.depart,
            peloton: item.dataset.peloton,
            position: item.dataset.position,
            numeroLicence: item.dataset.numeroLicence,
            userNom: item.dataset.userNom,
            piquetSouhaites: (item.getAttribute('data-piquet-souhaites') || '').trim()
        };
        if (infoContainer) {
            infoContainer.textContent = assignable
                ? 'Départ ' + currentTarget.depart + ' - Peloton ' + currentTarget.peloton +
                    ' - Position ' + currentTarget.position + ' : sélectionnez un archer à affecter'
                : 'Départ ' + currentTarget.depart + ' - Peloton ' + currentTarget.peloton +
                    ' - Position ' + currentTarget.position + ' (déjà affectée)';
        }
        if (releaseBtn) releaseBtn.style.display = canRelease ? 'block' : 'none';
        if (assignable) {
            fetchArchersDispo(currentTarget);
        } else {
            setListMessage('Position déjà affectée. Utilisez le bouton ci-dessous pour libérer cette position.', 'info');
        }
        if (modalInstance) modalInstance.show();
    });

    if (listContainer) {
        listContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-assign-peloton');
            if (!btn || !currentTarget) return;
            var piquetArcher = (btn.getAttribute('data-piquet') || '').trim().toLowerCase();
            var piquetSouhaites = currentTarget.piquetSouhaites || '';
            if (piquetSouhaites) {
                var listSouhaites = piquetSouhaites.split(',').map(function(s) { return s.trim().toLowerCase(); }).filter(Boolean);
                if (listSouhaites.length && listSouhaites.indexOf(piquetArcher) === -1) {
                    var msg = 'Cet archer n\'a pas la couleur de piquet souhaitée pour respecter la règle du nombre pair dans ce peloton.\nSouhaité : ' + listSouhaites.map(function(c) { return c.charAt(0).toUpperCase() + c.slice(1); }).join(', ') + '.\n\nVoulez-vous quand même l\'affecter ?';
                    if (!confirm(msg)) return;
                }
            }

            // Vérifier les règles de peloton côté client avant d'envoyer au serveur
            var rules = getPelotonRules();
            if (rules && listContainer) {
                // Reconstituer la composition actuelle du peloton (archers déjà affectés + nouvel archer)
                var items = document.querySelectorAll('.blason-item[data-concours-id="' + currentTarget.concoursId + '"][data-depart="' + currentTarget.depart + '"][data-peloton="' + currentTarget.peloton + '"]');
                var clubCounts = {};
                var colorCounts = {};
                var totalArchers = 0;
                items.forEach(function (li) {
                    var licence = (li.getAttribute('data-numero-licence') || '').trim();
                    var clubId = (li.getAttribute('data-id-club') || '').trim();
                    var badge = li.querySelector('.badge');
                    var color = badge ? (badge.textContent || '').trim().toLowerCase() : '';
                    if (licence) {
                        totalArchers++;
                        if (clubId) {
                            clubCounts[clubId] = (clubCounts[clubId] || 0) + 1;
                        }
                        if (color) {
                            colorCounts[color] = (colorCounts[color] || 0) + 1;
                        }
                    }
                });
                // Ajouter le nouvel archer
                totalArchers++;
                var newClubId = (btn.getAttribute('data-id-club') || '').trim();
                if (newClubId) {
                    clubCounts[newClubId] = (clubCounts[newClubId] || 0) + 1;
                }
                if (piquetArcher) {
                    colorCounts[piquetArcher] = (colorCounts[piquetArcher] || 0) + 1;
                }

                // Règle: max N archers du même club
                var maxClub = rules.maxClubPerPeloton || 1;
                for (var club in clubCounts) {
                    if (Object.prototype.hasOwnProperty.call(clubCounts, club) && clubCounts[club] > maxClub) {
                        alert('Règle peloton: maximum ' + maxClub + ' archer(s) du même club par peloton dépassé.');
                        return;
                    }
                }

                // Règle: max M couleurs de piquet différentes
                var maxColors = rules.maxPiquetColors || 2;
                var nbColors = Object.keys(colorCounts).length;
                if (piquetArcher && !(piquetArcher in colorCounts) && nbColors > maxColors) {
                    alert('Règle peloton: maximum ' + maxColors + ' couleurs de piquet différentes par peloton dépassé.');
                    return;
                }

                // Règle: si >2 archers et parité par couleur activée
                if (rules.pairPerColor && totalArchers > 2) {
                    var impairColors = [];
                    for (var c in colorCounts) {
                        if (Object.prototype.hasOwnProperty.call(colorCounts, c) && (colorCounts[c] % 2 !== 0)) {
                            impairColors.push(c.charAt(0).toUpperCase() + c.slice(1) + ' (' + colorCounts[c] + ')');
                        }
                    }
                    if (impairColors.length > 0) {
                        alert('Règle peloton: pour chaque couleur de piquet, le nombre d\'archers doit être pair.\nActuellement impair: ' + impairColors.join(', ') + '.');
                        return;
                    }
                }
            }
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
            .then(function(r) {
                return r.json().catch(function() { return { success: false, error: 'Réponse invalide du serveur' }; });
            })
            .then(function(data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    var err = (data.data && data.data.error) || data.error || data.message || 'Erreur';
                    console.warn('Assign peloton erreur:', err, 'Réponse:', data);
                    setListMessage(err, 'danger');
                }
            })
            .catch(function(e) {
                console.error('Assign peloton exception:', e);
                setListMessage('Erreur réseau ou serveur', 'danger');
            });
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
                else setListMessage((data.data && data.data.error) || data.error || data.message || 'Erreur', 'danger');
            });
        });
    }
})();
