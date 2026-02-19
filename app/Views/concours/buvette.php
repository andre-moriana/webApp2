<?php
$concoursId = $concours->id ?? $concours->_id ?? null;
$concoursTitre = $concours->titre_competition ?? $concours->nom ?? 'Concours';
?>
<div class="container-fluid concours-create-container" id="buvette-page" data-concours-id="<?= htmlspecialchars($concoursId) ?>">
    <div class="buvette-header d-flex justify-content-between align-items-center mb-4">
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

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Réservations buvette</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="table-buvette-reservations">
                    <thead>
                        <tr>
                            <th>Inscription</th>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-buvette-reservations">
                        <tr id="reservations-loading">
                            <td colspan="5" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 mb-0">Chargement des réservations...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p id="reservations-empty" class="text-muted text-center py-4 d-none">Aucune réservation pour ce concours.</p>
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
