<?php
$title = "Gestion des clubs - Portail Archers de Gémenos";
?>
<link rel="stylesheet" href="/public/assets/css/users-table.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-shield-alt me-2"></i>Gestion des clubs
                </h1>
                <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                <div class="btn-group">
                    <a href="/clubs/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouveau club
                    </a>
                    <a href="/clubs/import" class="btn btn-success">
                        <i class="fas fa-file-upload me-2"></i>Importer depuis XML
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['success']) && $_SESSION['success']): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error']) && $_SESSION['error']): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($clubs)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun club trouvé</p>
                        <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                        <a href="/clubs/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer le premier club
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <!-- Filtres par type de club -->
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="form-label fw-bold mb-2">
                                <i class="fas fa-filter me-2"></i>Filtrer par type :
                            </label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input club-filter" type="checkbox" id="filterClubs" value="club" checked>
                                <label class="form-check-label" for="filterClubs">
                                    Clubs
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input club-filter" type="checkbox" id="filterRegional" value="regional" checked>
                                <label class="form-check-label" for="filterRegional">
                                    Comités régionaux
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input club-filter" type="checkbox" id="filterDepartmental" value="departmental" checked>
                                <label class="form-check-label" for="filterDepartmental">
                                    Comités départementaux
                                </label>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="clubsCount">0</span> club(s) affiché(s)
                                </small>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="clubsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable" data-column="name">
                                            Nom <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="nameShort">
                                            Nom court <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="city">
                                            Ville <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="email">
                                            Email <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="president">
                                            Président <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clubs as $club): 
                                        // Déterminer le type de club selon le nom court
                                        $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                        $clubType = 'club'; // Par défaut
                                        if (!empty($nameShort)) {
                                            if (substr($nameShort, -5) === '00000') {
                                                $clubType = 'regional';
                                            } elseif (substr($nameShort, -3) === '000') {
                                                $clubType = 'departmental';
                                            }
                                        }
                                    ?>
                                    <tr data-club-type="<?php echo $clubType; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($club['name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($nameShort ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($club['city'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (!empty($club['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($club['email']); ?>">
                                                    <?php echo htmlspecialchars($club['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $president = $club['president'] ?? null;
                                            if ($president && isset($president['name'])) {
                                                echo htmlspecialchars($president['name']);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>" class="btn btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                                                <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>/edit" class="btn btn-outline-secondary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $club['id'] ?? $club['_id']; ?>)" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
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

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce club ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="/clubs" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="club_id" id="deleteClubId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(clubId) {
    document.getElementById('deleteClubId').value = clubId;
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = '/clubs/' + clubId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

