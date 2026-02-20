<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-create.css" rel="stylesheet">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.css" />

<!-- Formulaire d'édition d'un concours -->
<div class="container-fluid concours-create-container">
<h1>Éditer un concours</h1>

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

<form method="post" action="/concours/update/<?= $concours->id ?? '' ?>" id="concoursForm" novalidate
      data-concours-form-config
      data-config="<?= htmlspecialchars(json_encode([
          'clubs' => $clubs ?? [],
          'disciplines' => $disciplines ?? [],
          'typeCompetitions' => $typeCompetitions ?? [],
          'niveauChampionnat' => $niveauChampionnat ?? [],
          'concoursData' => isset($concours) ? $concours : null
      ], JSON_UNESCAPED_UNICODE)) ?>">
    
    <!-- Section principale -->
    <div class="form-section">
        <!-- Club Organisateur -->
        <div class="form-group">
            <label>Club Organisateur :</label>
            <div class="club-organisateur-fields">
                <input type="text" id="club_search" name="club_search" placeholder="Rechercher un club..." autocomplete="off">
                <select id="club_organisateur" name="club_organisateur" required>
                    <option value="">-- Sélectionner un club --</option>
                    <?php if (isset($clubs) && is_array($clubs)): ?>
                        <?php foreach ($clubs as $club): ?>
                            <?php 
                            $clubId = $club['id'] ?? $club['_id'] ?? '';
                            $clubName = $club['name'] ?? '';
                            $clubShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                            $selected = (isset($concours->club_organisateur) && ($concours->club_organisateur == $clubId)) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($clubId) ?>" <?= $selected ?> 
                                    data-code="<?= htmlspecialchars($clubShort) ?>" 
                                    data-name="<?= htmlspecialchars($clubName) ?>">
                                <?= htmlspecialchars($clubName . ($clubShort ? ' (' . $clubShort . ')' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <input type="text" id="club_code" name="club_code" value="<?= htmlspecialchars($concours->agreenum ?? '') ?>" placeholder="###" maxlength="3" style="width: 60px;">
                <input type="text" id="club_name_display" name="club_name_display" value="<?= htmlspecialchars($concours->club_name ?? '') ?>" placeholder="Nom du club" readonly>
            </div>
        </div>

        <!-- Discipline -->
        <div class="form-group">
            <label>Discipline :</label>
            <div class="discipline-fields">
                <select id="discipline" name="discipline" required>
                    <option value="">-- Sélectionner une discipline --</option>
                    <?php if (isset($disciplines) && is_array($disciplines)): ?>
                        <?php foreach ($disciplines as $discipline): ?>
                            <?php 
                            $disciplineId = $discipline['iddiscipline'] ?? $discipline['id'] ?? '';
                            $disciplineName = $discipline['lb_discipline'] ?? $discipline['name'] ?? '';
                            $selected = (isset($concours->discipline) && ($concours->discipline == $disciplineId)) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($disciplineId) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($disciplineName) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Type Compétition -->
        <div class="form-group">
            <label>Type Compétition :</label>
            <div class="type-competition-fields">
                <select id="type_competition" name="type_competition" required>
                    <option value="">-- Sélectionner un type --</option>
                    <?php 
                    // Pré-remplir seulement le type de compétition sélectionné
                    // Le JavaScript chargera les autres types dynamiquement selon la discipline
                    if (isset($concours->type_competition) && isset($typeCompetitions) && is_array($typeCompetitions)): 
                        foreach ($typeCompetitions as $typeComp): 
                            $typeId = $typeComp['idformat_competition'] ?? $typeComp['id'] ?? '';
                            $typeName = $typeComp['lb_format_competition'] ?? $typeComp['name'] ?? '';
                            $selected = ($concours->type_competition == $typeId) ? 'selected' : '';
                            if ($selected): ?>
                                <option value="<?= htmlspecialchars($typeId) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($typeName) ?>
                                </option>
                            <?php endif;
                        endforeach;
                    endif; ?>
                </select>
            </div>
        </div>

        <!-- Niveau Championnat -->
        <div class="form-group">
            <label>Niveau Championnat :</label>
            <div class="niveau-championnat-fields">
                <select id="idniveau_championnat" name="idniveau_championnat">
                    <option value="">-- Sélectionner --</option>
                    <?php if (isset($niveauChampionnat) && is_array($niveauChampionnat)): ?>
                        <?php foreach ($niveauChampionnat as $niveau): ?>
                            <?php 
                            $niveauId = $niveau['idniveau_championnat'] ?? $niveau['id'] ?? '';
                            $niveauName = $niveau['lb_niveauchampionnat'] ?? $niveau['name'] ?? '';
                            $selected = (isset($concours->idniveau_championnat) && ($concours->idniveau_championnat == $niveauId)) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($niveauId) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($niveauName) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Titre Compétition -->
        <div class="form-group">
            <label>Titre Compétition :</label>
            <input type="text" id="titre_competition" name="titre_competition" value="<?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?>" required>
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
            <label>Début Compétition : <input type="date" name="date_debut" value="<?= htmlspecialchars($concours->date_debut ?? '') ?>" required></label>
            <label>Fin Compétition : <input type="date" name="date_fin" value="<?= htmlspecialchars($concours->date_fin ?? '') ?>" required></label>
        </div>

        <!-- Nombre cibles/pelotons, départ, tireurs -->
        <div class="numeric-fields-row">
            <label id="label_nombre_cibles">Nombre cibles : <input type="number" name="nombre_cibles" id="nombre_cibles" value="<?= htmlspecialchars($concours->nombre_cibles ?? 0) ?>" min="0" required></label>
            <label id="label_nombre_tireurs">Nombre tireurs par cibles : <input type="number" name="nombre_tireurs_par_cibles" id="nombre_tireurs_par_cibles" value="<?= htmlspecialchars($concours->nombre_tireurs_par_cibles ?? 0) ?>" min="0" required></label>
        </div>

        <!-- Section Départs -->
        <div class="form-group departs-section" style="margin-top: 20px;">
            <h4>Départs</h4>
            <p class="text-muted small">Ajoutez les départs avec leur date, heure de greffe et heure de départ.</p>
            <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="btn-add-depart">
                <i class="fas fa-plus"></i> Ajouter un départ
            </button>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="departs-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Date</th>
                            <th>Heure de greffe</th>
                            <th>Heure de départ</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departs-tbody">
                        <?php
                        $departs = isset($concours->departs) ? $concours->departs : [];
                        if (!empty($departs)):
                            foreach ($departs as $i => $d):
                                $d = (array)$d;
                                $date = $d['date_depart'] ?? '';
                                $heureGreffe = $d['heure_greffe'] ?? '';
                                $heureDepart = $d['heure_depart'] ?? '';
                                $numero = $d['numero_depart'] ?? ($i + 1);
                        ?>
                        <tr data-index="<?= $i ?>">
                            <td class="depart-numero"><?= (int)$numero ?></td>
                            <td><input type="date" class="form-control form-control-sm depart-date" value="<?= htmlspecialchars($date) ?>"></td>
                            <td><input type="time" class="form-control form-control-sm depart-heure-greffe" value="<?= htmlspecialchars($heureGreffe) ?>"></td>
                            <td><input type="time" class="form-control form-control-sm depart-heure-depart" value="<?= htmlspecialchars($heureDepart) ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-depart"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="departs_json" id="departs_json" value="">
        </div>

        <!-- Section Arbitres -->
        <div class="form-group arbitres-section" style="margin-top: 20px;">
            <h4>Arbitres</h4>
            <p class="text-muted small">Recherchez par numéro de licence dans le fichier XML, puis ajoutez les arbitres (jury, arbitre, entraineur) avec leur rôle et ordre.</p>
            <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="btn-add-arbitre">
                <i class="fas fa-plus"></i> Ajouter un arbitre
            </button>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="arbitres-table">
                    <thead>
                        <tr>
                            <th>N° ordre</th>
                            <th>Licence</th>
                            <th>Nom</th>
                            <th>Rôle</th>
                            <th>Responsable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="arbitres-tbody">
                        <?php
                        $arbitres = isset($concours->arbitres) ? $concours->arbitres : [];
                        if (is_object($arbitres)) $arbitres = (array)$arbitres;
                        if (!empty($arbitres)):
                            foreach ($arbitres as $i => $a):
                                $a = (array)$a;
                                $licence = trim($a['IDLicence'] ?? $a['id_licence'] ?? '');
                                $responsable = !empty($a['responsable']);
                                $juryVal = (int)($a['Jury_arbitre'] ?? 2);
                                if (!in_array($juryVal, [1, 2, 3])) $juryVal = 2;
                                $noOrdre = (int)($a['no_ordre'] ?? $i);
                                $nom = $a['nom_display'] ?? trim(($a['first_name'] ?? '') . ' ' . ($a['name'] ?? '')) ?: $licence;
                        ?>
                        <tr data-licence="<?= htmlspecialchars($licence) ?>" data-nom="<?= htmlspecialchars($nom) ?>" data-jury="<?= $juryVal ?>">
                            <td><input type="number" class="form-control form-control-sm arbitre-no-ordre" value="<?= $noOrdre ?>" min="0" style="width: 70px;"></td>
                            <td class="arbitre-licence"><?= htmlspecialchars($licence) ?></td>
                            <td class="arbitre-nom"><?= htmlspecialchars($nom) ?></td>
                            <td>
                                <select class="form-select form-select-sm arbitre-role">
                                    <option value="1" <?= $juryVal === 1 ? 'selected' : '' ?>>Jury d'appel</option>
                                    <option value="2" <?= $juryVal === 2 ? 'selected' : '' ?>>Arbitre</option>
                                    <option value="3" <?= $juryVal === 3 ? 'selected' : '' ?>>Entraineur</option>
                                </select>
                            </td>
                            <td><input type="checkbox" class="form-check-input arbitre-responsable" <?= $responsable ? 'checked' : '' ?>></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-arbitre"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="arbitres_json" id="arbitres_json" value="">
        </div>
    </div>

    <!-- Sections encadrées en bas -->
    <div class="bottom-sections">
        <!-- Type Concours -->
        <div class="section-frame">
            <h4>Type Concours</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ouvert" <?= (!isset($concours->type_concours) || $concours->type_concours == 'ouvert') ? 'checked' : '' ?>>
                    Ouvert
                </label>
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ferme" <?= (isset($concours->type_concours) && $concours->type_concours == 'ferme') ? 'checked' : '' ?>>
                    Fermé
                </label>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="duel" value="1" <?= (isset($concours->duel) && ($concours->duel == 1 || $concours->duel === true || $concours->duel === "1")) ? 'checked' : '' ?>>
                Duel
            </label>
        </div>

        <!-- Division Equipe -->
        <div class="section-frame">
            <h4>Division Equipe</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="dr" <?= (isset($concours->division_equipe) && $concours->division_equipe == 'dr') ? 'checked' : '' ?>>
                    DR
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="poules_non_filiere" <?= (isset($concours->division_equipe) && $concours->division_equipe == 'poules_non_filiere') ? 'checked' : '' ?>>
                    Poules Non Filière
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="duels_equipes" <?= (!isset($concours->division_equipe) || $concours->division_equipe == 'duels_equipes') ? 'checked' : '' ?>>
                    Duels Equipes
                </label>
            </div>
        </div>

        <!-- Publication WEB -->
        <div class="section-frame">
            <h4>Publication WEB</h4>
            <label>Code d'authentification :</label>
            <input type="text" name="code_authentification" value="<?= htmlspecialchars($concours->code_authentification ?? '') ?>" placeholder="Code d'authentification">
            <label>Type de publication INTERNET :</label>
            <select id="type_publication_internet" name="type_publication_internet">
                <option value="">-- Sélectionner --</option>
            </select>
            <label>Lien formulaire inscription ciblé :</label>
            <input type="url" name="lien_inscription_cible" value="<?= htmlspecialchars($concours->lien_inscription_cible ?? '') ?>" placeholder="https://... (auth temporaire jusqu'au jour du concours, confirmation par email, puis choix peloton/plan cible)">
        </div>
    </div>

    <button type="submit">Enregistrer les modifications</button>
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

<!-- Modale pour ajouter un arbitre -->
<div class="modal fade" id="arbitreModal" tabindex="-1" aria-labelledby="arbitreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="arbitreModalLabel">Ajouter un arbitre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="arbitre-licence-search" class="form-label">Numéro de licence <span class="text-muted">(recherche dans le fichier XML)</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="arbitre-licence-search" placeholder="Entrez le numéro de licence..." autocomplete="off">
                        <button class="btn btn-primary" type="button" id="btn-search-arbitre">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                    <div id="arbitre-search-result" class="mt-2"></div>
                </div>
                <div id="arbitre-form-fields" class="border rounded p-3 mt-2" style="display: none;">
                    <div class="mb-2"><strong id="arbitre-found-nom"></strong> <span class="text-muted" id="arbitre-found-licence"></span></div>
                    <div class="mb-2">
                        <label class="form-label">Rôle</label>
                        <select class="form-select form-select-sm" id="arbitre-modal-role">
                            <option value="1">Jury d'appel</option>
                            <option value="2" selected>Arbitre</option>
                            <option value="3">Entraineur</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" id="arbitre-modal-responsable">
                            Responsable arbitre
                        </label>
                    </div>
                    <div class="mb-2">
                        <label for="arbitre-modal-ordre" class="form-label">N° ordre</label>
                        <input type="number" class="form-control form-control-sm" id="arbitre-modal-ordre" min="0" value="0" style="width: 80px;">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-arbitre-confirm">
                        <i class="fas fa-plus"></i> Ajouter à la liste
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@1.13.0/dist/Control.Geocoder.js"></script>
<script src="/public/assets/js/concours-form-config.js"></script>
<script src="/public/assets/js/concours-departs.js"></script>
<script src="/public/assets/js/concours-arbitres.js"></script>
<script src="/public/assets/js/concours.js"></script>
<script src="/public/assets/js/concours-lieu-map.js"></script>
