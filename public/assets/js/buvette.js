/**
 * Gestion des produits buvette - CRUD dynamique
 */
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap non chargé');
        return;
    }
    const pageEl = document.getElementById('buvette-page');
    if (!pageEl) return;
    const concoursId = pageEl.dataset.concoursId;
    const tbody = document.getElementById('tbody-buvette-produits');
    const loadingRow = document.getElementById('buvette-loading');
    const emptyMsg = document.getElementById('buvette-empty');
    const messageEl = document.getElementById('buvette-message');
    const modalEl = document.getElementById('modal-produit');
    const form = document.getElementById('form-produit');
    if (!tbody || !modalEl) return;
    const modal = new bootstrap.Modal(modalEl);

    function showMessage(text, type) {
        if (!messageEl) return;
        messageEl.textContent = text;
        messageEl.className = 'alert alert-' + (type || 'info') + (type ? '' : ' d-none');
        messageEl.classList.remove('d-none');
    }

    function hideMessage() {
        if (messageEl) messageEl.classList.add('d-none');
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function loadProduits() {
        loadingRow.classList.remove('d-none');
        if (emptyMsg) emptyMsg.classList.add('d-none');
        fetch('/api/concours/' + concoursId + '/buvette/produits', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            loadingRow.classList.add('d-none');
            const produits = Array.isArray(data) ? data : (data.data || []);
            if (produits.length === 0) {
                if (emptyMsg) emptyMsg.classList.remove('d-none');
                tbody.innerHTML = '';
                return;
            }
            if (emptyMsg) emptyMsg.classList.add('d-none');
            tbody.innerHTML = produits.map(p => `
                <tr data-id="${p.id}">
                    <td>${escapeHtml(p.libelle || '')}</td>
                    <td>${p.prix != null ? (parseFloat(p.prix).toFixed(2) + ' €') : '-'}</td>
                    <td>${escapeHtml(p.unite || 'portion')}</td>
                    <td>${p.ordre_affichage ?? 0}</td>
                    <td>${p.actif == 1 ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary edit-produit" data-id="${p.id}"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-produit" data-id="${p.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
            bindRowActions();
        })
        .catch(err => {
            loadingRow.classList.add('d-none');
            showMessage('Erreur lors du chargement: ' + err.message, 'danger');
        });
    }

    function bindRowActions() {
        tbody.querySelectorAll('.edit-produit').forEach(btn => {
            btn.addEventListener('click', () => editProduit(btn.dataset.id));
        });
        tbody.querySelectorAll('.delete-produit').forEach(btn => {
            btn.addEventListener('click', () => deleteProduit(btn.dataset.id));
        });
    }

    function editProduit(id) {
        const row = tbody.querySelector('tr[data-id="' + id + '"]');
        if (!row) return;
        document.getElementById('modal-produit-title').textContent = 'Modifier le produit';
        document.getElementById('produit-id').value = id;
        document.getElementById('produit-libelle').value = row.cells[0].textContent;
        const prixText = row.cells[1].textContent;
        document.getElementById('produit-prix').value = prixText === '-' ? '' : prixText.replace(' €', '').trim();
        document.getElementById('produit-unite').value = row.cells[2].textContent || 'portion';
        document.getElementById('produit-ordre').value = row.cells[3].textContent || '0';
        document.getElementById('produit-actif').checked = row.cells[4].innerHTML.includes('bg-success');
        modal.show();
    }

    function deleteProduit(id) {
        if (!confirm('Supprimer ce produit ?')) return;
        fetch('/api/concours/' + concoursId + '/buvette/produits/' + id, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success !== false && !res.error) {
                loadProduits();
                hideMessage();
            } else {
                showMessage(res.error || res.message || 'Erreur', 'danger');
            }
        })
        .catch(err => showMessage('Erreur: ' + err.message, 'danger'));
    }

    document.getElementById('btn-add-produit').addEventListener('click', () => {
        document.getElementById('modal-produit-title').textContent = 'Ajouter un produit';
        form.reset();
        document.getElementById('produit-id').value = '';
        document.getElementById('produit-actif').checked = true;
        modal.show();
    });

    document.getElementById('btn-save-produit').addEventListener('click', () => {
        const id = document.getElementById('produit-id').value;
        const data = {
            libelle: document.getElementById('produit-libelle').value.trim(),
            prix: document.getElementById('produit-prix').value || null,
            unite: document.getElementById('produit-unite').value.trim() || 'portion',
            ordre_affichage: parseInt(document.getElementById('produit-ordre').value, 10) || 0,
            actif: document.getElementById('produit-actif').checked ? 1 : 0
        };
        if (!data.libelle) {
            showMessage('Le libellé est requis.', 'danger');
            return;
        }
        const url = '/api/concours/' + concoursId + '/buvette/produits' + (id ? '/' + id : '');
        const method = id ? 'PUT' : 'POST';
        fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success !== false && !res.error) {
                modal.hide();
                loadProduits();
                hideMessage();
            } else {
                showMessage(res.error || res.message || 'Erreur', 'danger');
            }
        })
        .catch(err => showMessage('Erreur: ' + err.message, 'danger'));
    });

    loadProduits();
});
