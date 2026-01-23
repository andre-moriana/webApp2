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
                                    <?php foreach ($concours as $item): ?>
                                    <?php
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
                                        <td data-column="titre_lieu">
                                            <strong><?php echo htmlspecialchars($titre); ?></strong>
                                            <?php if ($lieu && $lieu !== '-'): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($lieu); ?></small>
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
                                                <?php if ($isAdmin || $canEditClub): ?>
                                                <a href="/concours/edit/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-primary" title="Voir/Modifier">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/concours/edit/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/concours/delete/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce concours ?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="/concours/edit/<?php echo $concoursId; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="S'inscrire" onclick="inscrireConcours(<?php echo $concoursId; ?>)">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
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
<script src="/public/assets/js/concours-table.js"></script>
<script>
function inscrireConcours(concoursId) {
    if (!concoursId) {
        alert('ID du concours manquant');
        return;
    }
    
    if (!confirm('Voulez-vous vous inscrire à ce concours ?')) {
        return;
    }
    
    // Appel API pour s'inscrire
    fetch('/api/concours/' + concoursId + '/inscription', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({
            user_id: <?php echo $_SESSION['user']['id'] ?? 'null'; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Inscription réussie !');
            location.reload();
        } else {
            alert('Erreur lors de l\'inscription: ' + (data.error || data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'inscription');
    });
}
</script>
