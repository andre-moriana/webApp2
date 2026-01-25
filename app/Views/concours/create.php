<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-create.css" rel="stylesheet">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.css" />

<!-- Formulaire de création/édition d'un concours -->
<div class="container-fluid concours-create-container">
<h1><?= isset($concours) ? 'Éditer' : 'Créer' ?> un concours</h1>


<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <strong>Erreur:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <strong>Succès:</strong> <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Passer les clubs et disciplines au JavaScript -->
<script>
// Vérifier que les variables PHP sont définies
var clubsFromPHP = <?= json_encode($clubs ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;
var disciplinesFromPHP = <?= json_encode($disciplines ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;

window.clubsData = clubsFromPHP || [];
window.disciplinesData = disciplinesFromPHP || [];
window.typeCompetitionsData = <?= json_encode($typeCompetitions ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?> || [];
window.niveauChampionnatData = <?= json_encode($niveauChampionnat ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?> || [];

// Données du concours à éditer (si en mode édition)
window.concoursData = <?= isset($concours) ? json_encode($concours, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'null' ?>;

</script>
<form method="post" action="<?= isset($concours) ? '/concours/update/' . $concours->id : '/concours/store' ?>" id="concoursForm" novalidate>
    
    <!-- Section principale -->
    <div class="form-section">
        <!-- Club Organisateur -->
        <div class="form-group">
            <label>Club Organisateur :</label>
            <div class="club-organisateur-fields">
                <input type="text" id="club_search" name="club_search" placeholder="Rechercher un club..." autocomplete="off">
                <select id="club_organisateur" name="club_organisateur" required>
                    <option value="">-- Sélectionner un club --</option>
                </select>
                <input type="text" id="club_code" name="club_code" placeholder="###" maxlength="3" style="width: 60px;">
                <input type="text" id="club_name_display" name="club_name_display" placeholder="Nom du club" readonly>
            </div>
        </div>

        <!-- Discipline -->
        <div class="form-group">
            <label>Discipline :</label>
            <div class="discipline-fields">
                <select id="discipline" name="discipline" required>
                    <option value="">-- Sélectionner une discipline --</option>
                </select>
            </div>
        </div>

        <!-- Type Compétition -->
        <div class="form-group">
            <label>Type Compétition :</label>
            <div class="type-competition-fields">
                <select id="type_competition" name="type_competition" required>
                    <option value="">-- Sélectionner un type --</option>
                </select>
            </div>
        </div>

        <!-- Niveau Championnat -->
        <div class="form-group">
            <label>Niveau Championnat :</label>
            <div class="niveau-championnat-fields">
                <select id="niveau_championnat" name="niveau_championnat">
                    <option value="">-- Sélectionner --</option>
                </select>
            </div>
        </div>

        <!-- Titre Compétition -->
        <div class="form-group">
            <label>Titre Compétition :</label>
            <input type="text" id="titre_competition" name="titre_competition" value="<?= $concours->titre_competition ?? '' ?>" required>
        </div>

        <!-- Lieu Compétition -->
        <div class="form-group">
            <label>Lieu Compétition :</label>
            <div class="lieu-fields">
                <input type="text" id="lieu_competition" name="lieu_competition" value="<?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? '') ?>" required readonly>
                <button type="button" class="btn btn-sm btn-primary" id="btn-select-lieu" onclick="openLieuModal()">
                    <i class="fas fa-map-marker-alt"></i> Sélectionner sur la carte
                </button>
            </div>
            <!-- Champs cachés pour les coordonnées GPS -->
            <input type="hidden" id="lieu_latitude" name="lieu_latitude" value="<?= htmlspecialchars($concours->lieu_latitude ?? '') ?>">
            <input type="hidden" id="lieu_longitude" name="lieu_longitude" value="<?= htmlspecialchars($concours->lieu_longitude ?? '') ?>">
        </div>

        <!-- Dates -->
        <div class="date-fields-row">
            <label>Début Compétition : <input type="date" name="date_debut" value="<?= $concours->date_debut ?? '' ?>" required></label>
            <label>Fin Compétition : <input type="date" name="date_fin" value="<?= $concours->date_fin ?? '' ?>" required></label>
        </div>

        <!-- Nombre cibles/pelotons, départ, tireurs -->
        <div class="numeric-fields-row">
            <label id="label_nombre_cibles">Nombre cibles : <input type="number" name="nombre_cibles" id="nombre_cibles" value="<?= $concours->nombre_cibles ?? 0 ?>" min="0" required></label>
            <label>Nombre départ : <input type="number" name="nombre_depart" value="<?= $concours->nombre_depart ?? 1 ?>" min="1" required></label>
            <label id="label_nombre_tireurs">Nombre tireurs par cibles : <input type="number" name="nombre_tireurs_par_cibles" id="nombre_tireurs_par_cibles" value="<?= $concours->nombre_tireurs_par_cibles ?? 0 ?>" min="0" required></label>
        </div>
    </div>

    <!-- Sections encadrées en bas -->
    <div class="bottom-sections">
        <!-- Type Concours -->
        <div class="section-frame">
            <h4>Type Concours</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ouvert" <?= (!isset($concours) || (isset($concours) && $concours->type_concours == 'ouvert')) ? 'checked' : '' ?>>
                    Ouvert
                </label>
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ferme" <?= (isset($concours) && $concours->type_concours == 'ferme') ? 'checked' : '' ?>>
                    Fermé
                </label>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="duel" value="1" <?= (isset($concours) && $concours->duel) ? 'checked' : '' ?>>
                Duel
            </label>
        </div>

        <!-- Division Equipe -->
        <div class="section-frame">
            <h4>Division Equipe</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="dr" <?= (isset($concours) && $concours->division_equipe == 'dr') ? 'checked' : '' ?>>
                    DR
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="poules_non_filiere" <?= (isset($concours) && $concours->division_equipe == 'poules_non_filiere') ? 'checked' : '' ?>>
                    Poules Non Filière
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="duels_equipes" <?= (!isset($concours) || (isset($concours) && $concours->division_equipe == 'duels_equipes')) ? 'checked' : '' ?>>
                    Duels Equipes
                </label>
            </div>
        </div>

        <!-- Publication WEB -->
        <div class="section-frame">
            <h4>Publication WEB</h4>
            <label>Code d'authentification :</label>
            <input type="text" name="code_authentification" value="<?= $concours->code_authentification ?? '' ?>" placeholder="Code d'authentification">
            <label>Type de publication INTERNET :</label>
            <select id="type_publication_internet" name="type_publication_internet">
                <option value="">-- Sélectionner --</option>
            </select>
        </div>
    </div>

    <button type="submit">Enregistrer</button>
</form>
<a href="/concours">Retour à la liste</a>
</div>

<!-- Modale pour sélectionner le lieu sur la carte -->
<div class="modal fade" id="lieuModal" tabindex="-1" aria-labelledby="lieuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lieuModalLabel">Sélectionner le lieu sur la carte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="lieu-search" class="form-label">Rechercher une adresse :</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="lieu-search" placeholder="Entrez une adresse...">
                        <button class="btn btn-primary" type="button" id="btn-search-lieu">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </div>
                <div id="map-container" style="height: 500px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
                <div class="mt-3">
                    <strong>Adresse sélectionnée :</strong>
                    <div id="selected-address" class="text-muted">Cliquez sur la carte ou recherchez une adresse</div>
                    <div class="mt-2">
                        <small>Coordonnées GPS : <span id="selected-coords">-</span></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-lieu">Valider</button>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.js"></script>
<script src="/public/assets/js/concours.js"></script>
<script src="/public/assets/js/concours-lieu-map.js"></script>
