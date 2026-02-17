<?php
$concoursId = $concours->id ?? $concours->_id ?? null;
$concoursTitre = $concours->titre_competition ?? $concours->nom ?? 'Concours';
?>
<link href="/public/assets/css/concours-show.css" rel="stylesheet">

<div class="container-fluid concours-create-container" id="buvette-page" data-concours-id="<?= htmlspecialchars($concoursId) ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="fas fa-coffee"></i> Gestion de la buvette</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($concoursTitre) ?></p>
        </div>
        <a href="/concours/show/<?= htmlspecialchars($concoursId) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour au concours
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Produits disponibles à la buvette</h5>
            <button type="button" class="btn btn-primary" id="btn-add-produit">
                <i class="fas fa-plus"></i> Ajouter un produit
            </button>
        </div>
        <div class="card-body">
            <div id="buvette-message" class="alert d-none"></div>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="table-buvette-produits">
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Prix</th>
                            <th>Unité</th>
                            <th>Ordre</th>
                            <th>Actif</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-buvette-produits">
                        <tr id="buvette-loading">
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 mb-0">Chargement...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p id="buvette-empty" class="text-muted text-center py-4 d-none">Aucun produit. Cliquez sur « Ajouter un produit » pour commencer.</p>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modification produit -->
<div class="modal fade" id="modal-produit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-produit-title">Ajouter un produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-produit">
                    <input type="hidden" id="produit-id" name="id">
                    <div class="mb-3">
                        <label for="produit-libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="produit-libelle" name="libelle" required placeholder="Ex: Sandwich, Café, Repas...">
                    </div>
                    <div class="mb-3">
                        <label for="produit-prix" class="form-label">Prix (€)</label>
                        <input type="number" class="form-control" id="produit-prix" name="prix" step="0.01" min="0" placeholder="Optionnel">
                    </div>
                    <div class="mb-3">
                        <label for="produit-unite" class="form-label">Unité</label>
                        <input type="text" class="form-control" id="produit-unite" name="unite" placeholder="portion, pièce, repas...">
                    </div>
                    <div class="mb-3">
                        <label for="produit-ordre" class="form-label">Ordre d'affichage</label>
                        <input type="number" class="form-control" id="produit-ordre" name="ordre_affichage" value="0" min="0">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="produit-actif" name="actif" checked>
                        <label class="form-check-label" for="produit-actif">Produit actif (visible à l'inscription)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-save-produit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const concoursId = document.getElementById('buvette-page').dataset.concoursId;
    const tbody = document.getElementById('tbody-buvette-produits');
    const loadingRow = document.getElementById('buvette-loading');
    const emptyMsg = document.getElementById('buvette-empty');
    const messageEl = document.getElementById('buvette-message');
    const modal = new bootstrap.Modal(document.getElementById('modal-produit'));
    const form = document.getElementById('form-produit');

    function showMessage(text, type) {
        messageEl.textContent = text;
        messageEl.className = 'alert alert-' + (type || 'info') + (type ? '' : ' d-none');
        messageEl.classList.remove('d-none');
    }

    function hideMessage() {
        messageEl.classList.add('d-none');
    }

    function loadProduits() {
        loadingRow.classList.remove('d-none');
        emptyMsg.classList.add('d-none');
        fetch('/api/concours/' + concoursId + '/buvette/produits', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            loadingRow.classList.add('d-none');
            const produits = Array.isArray(data) ? data : (data.data || []);
            if (produits.length === 0) {
                emptyMsg.classList.remove('d-none');
                tbody.innerHTML = '';
                return;
            }
            emptyMsg.classList.add('d-none');
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

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function bindRowActions() {
        tbody.querySelectorAll('.edit-produit').forEach(btn => {
            btn.addEventListener('click', () => editProduit(btn.dataset.id));
        });
        tbody.querySelectorAll('.delete-produit').forEach(btn => {
            btn.addEventListener('click', () => deleteProduit(btn.dataset.id));
        });
    }

    document.getElementById('btn-add-produit').addEventListener('click', () => {
        document.getElementById('modal-produit-title').textContent = 'Ajouter un produit';
        form.reset();
        document.getElementById('produit-id').value = '';
        document.getElementById('produit-actif').checked = true;
        modal.show();
    });

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

    loadProduits();
})();
</script>
