// Gestion des arbitres dans le formulaire concours (create/edit)

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('arbitres-tbody');
    const btnAdd = document.getElementById('btn-add-arbitre');
    const form = document.getElementById('concoursForm');
    const arbitresJsonInput = document.getElementById('arbitres_json');
    const arbitreModal = document.getElementById('arbitreModal');
    const licenceSearchInput = document.getElementById('arbitre-licence-search');
    const btnSearchArbitre = document.getElementById('btn-search-arbitre');
    const searchResultDiv = document.getElementById('arbitre-search-result');

    if (!tbody || !btnAdd) return;

    // Jury_arbitre: 1=jury d'appel, 2=arbitre, 3=entraineur
    function addArbitreRow(licence, nom, juryArbitre, responsable) {
        juryArbitre = juryArbitre || 2;
        const existing = tbody.querySelector('tr[data-licence="' + licence.replace(/"/g, '&quot;') + '"]');
        if (existing) return;

        const tr = document.createElement('tr');
        tr.setAttribute('data-licence', licence);
        tr.setAttribute('data-nom', nom || licence);
        tr.setAttribute('data-jury', String(juryArbitre));
        tr.innerHTML =
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
        rows.forEach(function(row, i) {
            const licence = (row.getAttribute('data-licence') || '').trim();
            if (!licence) return;
            const responsable = row.querySelector('.arbitre-responsable') ? row.querySelector('.arbitre-responsable').checked : false;
            const roleSelect = row.querySelector('.arbitre-role');
            const Jury_arbitre = roleSelect ? parseInt(roleSelect.value, 10) : 2;
            arbitres.push({
                IDLicence: licence,
                licence_number: licence,
                responsable: responsable ? 1 : 0,
                Jury_arbitre: [1, 2, 3].indexOf(Jury_arbitre) >= 0 ? Jury_arbitre : 2,
                no_ordre: i
            });
        });
        return arbitres;
    }

    // Ouvrir la modale
    btnAdd.addEventListener('click', function() {
        if (licenceSearchInput) licenceSearchInput.value = '';
        if (searchResultDiv) searchResultDiv.innerHTML = '';
        if (arbitreModal && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(arbitreModal).show();
            setTimeout(function() { if (licenceSearchInput) licenceSearchInput.focus(); }, 300);
        }
    });

    // Recherche par licence
    function doSearch() {
        const licence = (licenceSearchInput ? licenceSearchInput.value : '').trim();
        if (!licence) {
            if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-warning">Veuillez entrer un numéro de licence.</div>';
            return;
        }
        if (searchResultDiv) searchResultDiv.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</div>';

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
                addArbitreRow(licence, nom, 2, false);
                if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-success">Arbitre ajouté : ' + escapeHtml(nom) + '</div>';
                if (arbitreModal && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(arbitreModal).hide();
                }
            } else {
                if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Arbitre non trouvé') + '</div>';
            }
        })
        .catch(function(err) {
            if (searchResultDiv) searchResultDiv.innerHTML = '<div class="alert alert-danger">Erreur de recherche.</div>';
            console.error(err);
        });
    }

    if (btnSearchArbitre) btnSearchArbitre.addEventListener('click', doSearch);
    if (licenceSearchInput) {
        licenceSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });
    }

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
