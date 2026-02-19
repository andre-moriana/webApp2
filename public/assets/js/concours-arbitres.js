// Gestion des arbitres dans le formulaire concours (create/edit) - comme les départs

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('arbitres-tbody');
    const btnAdd = document.getElementById('btn-add-arbitre');
    const form = document.getElementById('concoursForm');
    const arbitresJsonInput = document.getElementById('arbitres_json');
    const arbitreModal = document.getElementById('arbitreModal');
    const licenceSearchInput = document.getElementById('arbitre-licence-search');
    const btnSearchArbitre = document.getElementById('btn-search-arbitre');
    const searchResultDiv = document.getElementById('arbitre-search-result');
    const formFieldsDiv = document.getElementById('arbitre-form-fields');
    const btnAddConfirm = document.getElementById('btn-add-arbitre-confirm');
    const modalRoleSelect = document.getElementById('arbitre-modal-role');
    const modalResponsableCheck = document.getElementById('arbitre-modal-responsable');
    const modalOrdreInput = document.getElementById('arbitre-modal-ordre');

    if (!tbody || !btnAdd) return;

    // Données temporaires après recherche (licence, nom)
    let pendingArbitre = null;

    function getNextNoOrdre() {
        const rows = tbody.querySelectorAll('tr');
        let max = 0;
        rows.forEach(function(row) {
            const input = row.querySelector('.arbitre-no-ordre');
            if (input) {
                const n = parseInt(input.value, 10);
                if (!isNaN(n) && n > max) max = n;
            }
        });
        return max + 1;
    }

    // Jury_arbitre: 1=jury d'appel, 2=arbitre, 3=entraineur
    function addArbitreRow(licence, nom, juryArbitre, responsable, noOrdre) {
        juryArbitre = juryArbitre || 2;
        noOrdre = noOrdre != null ? noOrdre : getNextNoOrdre();
        const existing = tbody.querySelector('tr[data-licence="' + licence.replace(/"/g, '&quot;') + '"]');
        if (existing) return;

        const tr = document.createElement('tr');
        tr.setAttribute('data-licence', licence);
        tr.setAttribute('data-nom', nom || licence);
        tr.setAttribute('data-jury', String(juryArbitre));
        tr.innerHTML =
            '<td><input type="number" class="form-control form-control-sm arbitre-no-ordre" value="' + noOrdre + '" min="0" style="width: 70px;"></td>' +
            '<td class="arbitre-licence">' + escapeHtml(licence) + '</td>' +
            '<td class="arbitre-nom">' + escapeHtml(nom || licence) + '</td>' +
            '<td><select class="form-select form-select-sm arbitre-role">' +
            '<option value="1"' + (juryArbitre == 1 ? ' selected' : '') + '>Jury d\'appel</option>' +
            '<option value="2"' + (juryArbitre == 2 ? ' selected' : '') + '>Arbitre</option>' +
            '<option value="3"' + (juryArbitre == 3 ? ' selected' : '') + '>Entraineur</option>' +
            '</select></td>' +
            '<td><input type="checkbox" class="form-check-input arbitre-responsable" ' + (responsable ? 'checked' : '') + '></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-arbitre"><i class="fas fa-trash"></i></button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.btn-remove-arbitre').addEventListener('click', function() {
            tr.remove();
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function collectArbitres() {
        const arbitres = [];
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row) {
            const licence = (row.getAttribute('data-licence') || '').trim();
            if (!licence) return;
            const responsable = row.querySelector('.arbitre-responsable') ? row.querySelector('.arbitre-responsable').checked : false;
            const roleSelect = row.querySelector('.arbitre-role');
            const Jury_arbitre = roleSelect ? parseInt(roleSelect.value, 10) : 2;
            const noOrdreInput = row.querySelector('.arbitre-no-ordre');
            const no_ordre = noOrdreInput ? (parseInt(noOrdreInput.value, 10) || 0) : 0;
            arbitres.push({
                IDLicence: licence,
                licence_number: licence,
                responsable: responsable ? 1 : 0,
                Jury_arbitre: [1, 2, 3].indexOf(Jury_arbitre) >= 0 ? Jury_arbitre : 2,
                no_ordre: no_ordre
            });
        });
        // Trier par no_ordre pour l'envoi
        arbitres.sort(function(a, b) { return a.no_ordre - b.no_ordre; });
        return arbitres;
    }

    // Ouvrir la modale
    btnAdd.addEventListener('click', function() {
        if (licenceSearchInput) licenceSearchInput.value = '';
        if (searchResultDiv) searchResultDiv.innerHTML = '';
        if (formFieldsDiv) formFieldsDiv.style.display = 'none';
        pendingArbitre = null;
        if (modalOrdreInput) modalOrdreInput.value = String(getNextNoOrdre());
        if (modalRoleSelect) modalRoleSelect.value = '2';
        if (modalResponsableCheck) modalResponsableCheck.checked = false;
        if (arbitreModal && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(arbitreModal).show();
            setTimeout(function() { if (licenceSearchInput) licenceSearchInput.focus(); }, 300);
        }
    });

    // Recherche par licence dans le XML
    function doSearch() {
        const licence = (licenceSearchInput ? licenceSearchInput.value : '').trim();
        if (!licence) {
            if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-warning">Veuillez entrer un numéro de licence.</div>';
            return;
        }
        if (searchResultDiv) searchResultDiv.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> Recherche dans le fichier XML...</div>';

        fetch('/archer/search-or-create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ licence_number: licence })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                const d = data.data;
                const nom = ((d.first_name || '') + ' ' + (d.name || '')).trim() || licence;
                pendingArbitre = { licence: licence, nom: nom };
                if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-success">Licence trouvée dans le fichier XML.</div>';
                if (formFieldsDiv) formFieldsDiv.style.display = 'block';
                if (document.getElementById('arbitre-found-nom')) document.getElementById('arbitre-found-nom').textContent = nom;
                if (document.getElementById('arbitre-found-licence')) document.getElementById('arbitre-found-licence').textContent = '(' + licence + ')';
                if (modalOrdreInput) modalOrdreInput.value = String(getNextNoOrdre());
            } else {
                pendingArbitre = null;
                if (formFieldsDiv) formFieldsDiv.style.display = 'none';
                if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Arbitre non trouvé dans le fichier XML') + '</div>';
            }
        })
        .catch(function(err) {
            pendingArbitre = null;
            if (formFieldsDiv) formFieldsDiv.style.display = 'none';
            if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-danger">Erreur de recherche.</div>';
            console.error(err);
        });
    }

    // Ajouter à la liste (après avoir rempli le formulaire)
    function addFromModal() {
        if (!pendingArbitre) return;
        const role = modalRoleSelect ? parseInt(modalRoleSelect.value, 10) : 2;
        const responsable = modalResponsableCheck ? modalResponsableCheck.checked : false;
        const noOrdre = modalOrdreInput ? (parseInt(modalOrdreInput.value, 10) || 0) : getNextNoOrdre();
        addArbitreRow(pendingArbitre.licence, pendingArbitre.nom, role, responsable, noOrdre);
        if (arbitreModal && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getInstance(arbitreModal).hide();
        }
        pendingArbitre = null;
        if (formFieldsDiv) formFieldsDiv.style.display = 'none';
    }

    if (btnSearchArbitre) btnSearchArbitre.addEventListener('click', doSearch);
    if (licenceSearchInput) {
        licenceSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });
    }
    if (btnAddConfirm) btnAddConfirm.addEventListener('click', addFromModal);

    tbody.querySelectorAll('.btn-remove-arbitre').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tr = btn.closest('tr');
            if (tr) tr.remove();
        });
    });

    if (form && arbitresJsonInput) {
        form.addEventListener('submit', function(e) {
            const arbitres = collectArbitres();
            arbitresJsonInput.value = JSON.stringify(arbitres);
        }, true);
    }
});
