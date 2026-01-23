<!-- Liste des concours -->
<?php
$title = "Gestion des concours - Portail Archers de Gémenos";
?>
<div class="container-fluid">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gestion des concours</h1>
                <?php if (isset($_SESSION['user']['is_admin']) && (bool)$_SESSION['user']): ?>
                <div>
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
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Liste des concours
                            </h5>
                            <span class="badge bg-primary" id="clubsCount">
                                <?php echo count($concours); ?> concours<?php echo count($concours) > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="concoursTable">
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
<link href="/public/assets/css/concours-index.css" rel="stylesheet">
<script src="/public/assets/js/concours-table.js"></script>
