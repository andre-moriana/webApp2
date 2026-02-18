<!-- Liste des concours -->
<?php
$title = "Gestion des concours - Portail Archers de Gémenos";
?>
<div class="container-fluid" data-concours-index data-user-id="<?= htmlspecialchars($_SESSION['user']['id'] ?? '') ?>">
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
                                        <th class="sortable" data-column="club" style="cursor: pointer;">
                                            Club <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="discipline" style="cursor: pointer;">
                                            Discipline <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="type_competition" style="cursor: pointer;">
                                            Type compétition <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="titre_lieu" style="cursor: pointer;">
                                            Titre / Lieu <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="dates" style="cursor: pointer;">
                                            Dates <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fonction pour tronquer le texte (déclarée une seule fois avant la boucle)
                                    if (!function_exists('truncateText')) {
                                        function truncateText($text, $maxLength = 40) {
                                            if (strlen($text) <= $maxLength) {
                                                return $text;
                                            }
                                            return substr($text, 0, $maxLength) . '...';
                                        }
                                    }
                                    
                                    foreach ($concours as $item):
                                    $user = $_SESSION['user'] ?? [];
                                    $isAdmin = $user['is_admin'] ?? false;
                                    $isDirigeant = ($user['role'] ?? '') === 'Dirigeant';
                                    // La liste est déjà filtrée par club, autoriser le Dirigeant de toute façon
                                    $canEditClub = $isAdmin || $isDirigeant || ($belongsToClub ?? false);
                                    
                                    // Colonne 1: Club + agreenum
                                    $clubName = $item['club_name'] ?? '';
                                    $agreenum = $item['agreenum'] ?? '';
                                    $clubDisplay = trim($clubName . ($agreenum ? ' (' . $agreenum . ')' : ''));
                                    
                                    // Colonne 2: Discipline
                                    $disciplineName = $item['discipline_name'] ?? '-';
                                    
                                    // Colonne 3: Type compétition
                                    $typeCompetitionName = $item['type_competition_name'] ?? '-';
                                    
                                    // Colonne 4: Titre + Lieu
                                    $titre = $item['titre_competition'] ?? $item['nom'] ?? '-';
                                    $lieu = $item['lieu'] ?? $item['lieu_competition'] ?? '-';
                                    
                                    // Tronquer le lieu
                                    $lieuTruncated = ($lieu && $lieu !== '-') ? truncateText($lieu, 40) : $lieu;
                                    $lieuFull = ($lieu && $lieu !== '-') ? $lieu : '';
                                    
                                    // Colonne 5: Dates
                                    $dateDebut = $item['date_debut'] ?? '-';
                                    $dateFin = $item['date_fin'] ?? '-';
                                    ?>
                                    <tr>
                                        <td data-column="club">
                                            <?php echo htmlspecialchars($clubDisplay ?: '-'); ?>
                                        </td>
                                        <td data-column="discipline">
                                            <?php echo htmlspecialchars($disciplineName); ?>
                                        </td>
                                        <td data-column="type_competition">
                                            <?php echo htmlspecialchars($typeCompetitionName); ?>
                                        </td>
                                        <td data-column="titre_lieu" style="max-width: 300px;">
                                            <strong><?php echo htmlspecialchars($titre); ?></strong>
                                            <?php if ($lieu && $lieu !== '-'): ?>
                                                <br><small class="text-muted" title="<?php echo htmlspecialchars($lieuFull); ?>"><?php echo htmlspecialchars($lieuTruncated); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-column="dates">
                                            <div><?php echo htmlspecialchars($dateDebut); ?></div>
                                            <div><small class="text-muted"><?php echo htmlspecialchars($dateFin); ?></small></div>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="btn-group" role="group">
                                                <?php 
                                                $concoursId = $item['id'] ?? $item['_id'] ?? null;
                                                if ($concoursId):
                                                ?>
                                                <a href="/concours/show/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($isAdmin || $canEditClub): ?>
                                                <a href="/concours/edit/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/concours/delete/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce concours ?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="/concours/<?php echo $concoursId; ?>/inscription" class="btn btn-sm btn-outline-success" title="Gérer les inscriptions">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <!--
                                                <button type="button" class="btn btn-sm btn-outline-info" title="S'inscrire rapidement" onclick="inscrireConcours(<?php echo $concoursId; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                -->
                                                <?php 
                                                // Afficher le bouton plan de cible uniquement pour les disciplines S, T, I, H
                                                $disciplineId = $item['discipline'] ?? $item['iddiscipline'] ?? null;
                                                $abv_discipline = null;
                                                if ($disciplineId && isset($disciplines) && is_array($disciplines)) {
                                                    foreach ($disciplines as $disc) {
                                                        $discId = $disc['iddiscipline'] ?? $disc['id'] ?? null;
                                                        if ($discId == $disciplineId || (string)$discId === (string)$disciplineId) {
                                                            $abv_discipline = $disc['abv_discipline'] ?? null;
                                                            break;
                                                        }
                                                    }
                                                }
                                                if ($abv_discipline && in_array($abv_discipline, ['S', 'T', 'I', 'H'])): 
                                                ?>
                                                <a href="/concours/<?php echo $concoursId; ?>/plan-cible" class="btn btn-sm btn-outline-warning" title="Voir le plan de cible">
                                                    <i class="fas fa-bullseye"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($abv_discipline && in_array($abv_discipline, ['3', 'N', 'C', '3D'])): ?>
                                                <a href="/concours/<?php echo $concoursId; ?>/plan-peloton" class="btn btn-sm btn-outline-info" title="Plan de peloton (Nature/3D/Campagne)">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
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
<script src="/public/assets/js/concours-index.js"></script>
