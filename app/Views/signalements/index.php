<!-- Page de liste des signalements -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-flag me-2"></i>
                Gestion des Signalements
            </h1>
            <a href="/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" action="/signalements" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filtrer par statut</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>
                                En attente
                            </option>
                            <option value="reviewed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'reviewed') ? 'selected' : ''; ?>>
                                En cours
                            </option>
                            <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolved') ? 'selected' : ''; ?>>
                                Résolu
                            </option>
                            <option value="dismissed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'dismissed') ? 'selected' : ''; ?>>
                                Rejeté
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="limit" class="form-label">Nombre par page</label>
                        <select name="limit" id="limit" class="form-select">
                            <option value="10" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '10') ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '25') ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == '50') ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '100') ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i>
                            Filtrer
                        </button>
                        <a href="/signalements" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i>
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-danger shadow-sm">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                    En attente
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    <?php 
                    $pendingCount = 0;
                    foreach ($reportsData['reports'] as $r) {
                        if ($r['status'] === 'pending') $pendingCount++;
                    }
                    echo $pendingCount;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-warning shadow-sm">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                    En cours
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    <?php 
                    $reviewedCount = 0;
                    foreach ($reportsData['reports'] as $r) {
                        if ($r['status'] === 'reviewed') $reviewedCount++;
                    }
                    echo $reviewedCount;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-success shadow-sm">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Résolus
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    <?php 
                    $resolvedCount = 0;
                    foreach ($reportsData['reports'] as $r) {
                        if ($r['status'] === 'resolved') $resolvedCount++;
                    }
                    echo $resolvedCount;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-secondary shadow-sm">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                    Total
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    <?php echo $reportsData['total']; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des signalements -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Liste des signalements
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($reportsData['reports'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="reportsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Raison</th>
                                    <th>Signalé par</th>
                                    <th>Utilisateur signalé</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $reasonLabels = [
                                    'harassment' => 'Harcèlement',
                                    'spam' => 'Spam',
                                    'inappropriate_content' => 'Contenu inapproprié',
                                    'violence' => 'Violence',
                                    'hate_speech' => 'Discours de haine',
                                    'fake_news' => 'Fausse information',
                                    'other' => 'Autre'
                                ];
                                
                                foreach ($reportsData['reports'] as $report): 
                                    $statusClass = '';
                                    $statusLabel = '';
                                    switch ($report['status']) {
                                        case 'pending':
                                            $statusClass = 'badge-danger';
                                            $statusLabel = 'En attente';
                                            break;
                                        case 'reviewed':
                                            $statusClass = 'badge-warning';
                                            $statusLabel = 'En cours';
                                            break;
                                        case 'resolved':
                                            $statusClass = 'badge-success';
                                            $statusLabel = 'Résolu';
                                            break;
                                        case 'dismissed':
                                            $statusClass = 'badge-secondary';
                                            $statusLabel = 'Rejeté';
                                            break;
                                        default:
                                            $statusClass = 'badge-secondary';
                                            $statusLabel = $report['status'];
                                    }
                                    
                                    $reasonLabel = $reasonLabels[$report['reason']] ?? $report['reason'];
                                ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($report['id']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($report['created_at'])) {
                                                $date = new DateTime($report['created_at']);
                                                echo $date->format('d/m/Y H:i');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($reasonLabel); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['reporter_username'] ?? 'Inconnu'); ?></td>
                                        <td><?php echo htmlspecialchars($report['reported_username'] ?? $report['reported_user_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/signalements/<?php echo htmlspecialchars($report['id']); ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($reportsData['total'] > $reportsData['limit']): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php
                                $currentPage = floor($reportsData['offset'] / $reportsData['limit']) + 1;
                                $totalPages = ceil($reportsData['total'] / $reportsData['limit']);
                                $status = $_GET['status'] ?? '';
                                
                                for ($i = 1; $i <= $totalPages; $i++):
                                    $offset = ($i - 1) * $reportsData['limit'];
                                    $queryParams = ['limit' => $reportsData['limit'], 'offset' => $offset];
                                    if ($status) {
                                        $queryParams['status'] = $status;
                                    }
                                ?>
                                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                        <a class="page-link" href="/signalements?<?php echo http_build_query($queryParams); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun signalement trouvé.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
