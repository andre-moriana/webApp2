<!-- Liste des concours -->
<?php
$title = "Gestion des concours - Portail Archers de Gémenos";
?>
<link href="/public/assets/css/concours-index.css" rel="stylesheet">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-shield-alt me-2"></i>Gestion des concours
                </h1>
                <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                <div class="btn-group">
                    <a href="/concours/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouveau concours
                    </a>
                </div>
                <?php endif; ?>
            </div>
           

            <?php if (empty($concours)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun concours trouvé</p>
                        <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                        <a href="/concours/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer le premier concours
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Liste des concours
                                </h5>
                                <span class="badge bg-primary" id="clubsCount">
                                    <?php echo count($clubs); ?> concours<?php echo count($concours) > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchClubs" placeholder="Rechercher un club...">
                                </div>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="input-group" style="max-width: 250px;">
                                        <label class="input-group-text" for="filterRegional">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </label>
                                        <select class="form-select" id="filterRegional">
                                            <option value="">Tous les comités régionaux</option>
                                            <?php
                                            $regionalClubs = [];
                                            foreach ($clubs as $club) {
                                                $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                                if (substr($nameShort, -5) === '00000') {
                                                    $regionalClubs[] = $club;
                                                }
                                            }
                                            foreach ($regionalClubs as $club): 
                                                $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                            ?>
                                                <option value="<?php echo htmlspecialchars($nameShort); ?>">
                                                    <?php echo htmlspecialchars($club['name'] ?? $nameShort); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group" style="max-width: 250px;">
                                        <label class="input-group-text" for="filterDepartmental">
                                            <i class="fas fa-map"></i>
                                        </label>
                                        <select class="form-select" id="filterDepartmental">
                                            <option value="">Tous les comités départementaux</option>
                                            <?php
                                            $departmentalClubs = [];
                                            foreach ($clubs as $club) {
                                                $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                                if (substr($nameShort, -3) === '000' && substr($nameShort, -5) !== '00000') {
                                                    $departmentalClubs[] = $club;
                                                }
                                            }
                                            foreach ($departmentalClubs as $club): 
                                                $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                            ?>
                                                <option value="<?php echo htmlspecialchars($nameShort); ?>">
                                                    <?php echo htmlspecialchars($club['name'] ?? $nameShort); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filterClubs" checked>
                                        <label class="form-check-label" for="filterClubs">
                                            Clubs
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="clubsTable">
                                <thead>
                                    <tr>
                                        <th class="sortable" data-column="nom_competition" style="cursor: pointer;">
                                            Nom concours <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="lieu" style="cursor: pointer;">
                                            lieu <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="type" style="cursor: pointer;">
                                            type <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="date_debut" style="cursor: pointer;">
                                            Date de debut <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="date_fin" style="cursor: pointer;">
                                            date de fin <i class="fas fa-sort ms-1"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($concours as $concours): ?>
                                    <?php
                                    $user = $_SESSION['user'] ?? [];
                                    $isAdmin = $user['is_admin'] ?? false;
                                    $isDirigeant = ($user['role'] ?? '') === 'Dirigeant';
                                    // La liste est déjà filtrée par club, autoriser le Dirigeant de toute façon
                                    $canEditClub = $isAdmin || $isDirigeant || $belongsToClub;
                                    ?>
                                    <tr data-club-type="<?php echo $clubType; ?>" data-name-short="<?php echo htmlspecialchars($nameShort); ?>">
                                        <td data-column="name">
                                            <strong><?php echo htmlspecialchars($concours['nom_competition'] ?? 'N/A'); ?></strong>
                                            <br><small class="text-muted">ID: <?php echo htmlspecialchars($concours['id'] ?? $concours['_id'] ?? 'MANQUANT'); ?></small>
                                        </td>
                                        <td data-column="nameShort"><?php echo htmlspecialchars($concours['lieu'] ?? $concours['lieu'] ?? '-'); ?></td>
                                        <td data-column="type"><?php echo htmlspecialchars($concours['type'] ?? '-'); ?></td>
                                        <td data-column="date_debut"><?php echo htmlspecialchars($concours['date_debut'] ?? '-'); ?></td>
                                        <td data-column="date_fin"><?php echo htmlspecialchars($concours['date_fin'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
