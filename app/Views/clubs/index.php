<?php
$title = "Gestion des clubs - Portail Archers de Gémenos";
?>
<style>
.sortable {
    user-select: none;
    position: relative;
}
.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}
.sort-asc {
    color: var(--primary-color, #14532d);
}
.sort-desc {
    color: var(--primary-color, #14532d);
}
.sortable i {
    opacity: 0.5;
    transition: opacity 0.2s;
}
.sortable:hover i,
.sort-asc i,
.sort-desc i {
    opacity: 1;
}
.card-header .form-check {
    margin-bottom: 0;
    padding: 0.5rem 1rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}
.card-header .form-check:hover {
    background-color: rgba(255, 255, 255, 0.2);
}
.card-header .form-check-label {
    margin-left: 0.5rem;
    cursor: pointer;
    user-select: none;
}
.card-header .form-check-input {
    cursor: pointer;
    margin-top: 0.25rem;
}
@media (max-width: 768px) {
    .card-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .card-header .d-flex > div:last-child {
        margin-top: 1rem;
        flex-direction: column;
        gap: 0.5rem !important;
    }
    .card-header .input-group {
        max-width: 100% !important;
        width: 100%;
        margin-bottom: 1rem;
    }
}
</style>

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
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Liste des clubs
                            </h5>
                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                <div class="input-group" style="max-width: 300px;">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchClubs" placeholder="Rechercher un club...">
                                </div>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filterRegional" checked>
                                        <label class="form-check-label" for="filterRegional">
                                            Comités régionaux
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filterDepartmental" checked>
                                        <label class="form-check-label" for="filterDepartmental">
                                            Comités départementaux
                                        </label>
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
                                        <th class="sortable" data-column="name" style="cursor: pointer;">
                                            Nom <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="nameShort" style="cursor: pointer;">
                                            Nom court <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="city" style="cursor: pointer;">
                                            Ville <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="email" style="cursor: pointer;">
                                            Email <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="sortable" data-column="president" style="cursor: pointer;">
                                            Président <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clubs as $club): ?>
                                    <?php 
                                    $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                    $isRegional = substr($nameShort, -5) === '00000';
                                    $isDepartmental = substr($nameShort, -3) === '000' && !$isRegional;
                                    $isClub = !$isRegional && !$isDepartmental;
                                    $clubType = $isRegional ? 'regional' : ($isDepartmental ? 'departmental' : 'club');
                                    ?>
                                    <tr data-club-type="<?php echo $clubType; ?>">
                                        <td data-column="name">
                                            <strong><?php echo htmlspecialchars($club['name'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td data-column="nameShort"><?php echo htmlspecialchars($club['nameShort'] ?? $club['name_short'] ?? '-'); ?></td>
                                        <td data-column="city"><?php echo htmlspecialchars($club['city'] ?? '-'); ?></td>
                                        <td data-column="email">
                                            <?php if (!empty($club['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($club['email']); ?>">
                                                    <?php echo htmlspecialchars($club['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td data-column="president">
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

