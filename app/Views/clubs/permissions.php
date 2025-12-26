<?php
$additionalCSS = $additionalCSS ?? [];
$additionalCSS[] = '/public/assets/css/club-permissions.css';
$additionalJS = $additionalJS ?? [];
$additionalJS[] = '/public/assets/js/club-permissions.js';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/clubs">Clubs</a></li>
                    <li class="breadcrumb-item"><a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>">
                        <?php echo htmlspecialchars($club['name'] ?? 'Club'); ?>
                    </a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-lock me-2"></i>Configuration des permissions
                </h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars($club['name'] ?? 'Club'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Hiérarchie des rôles :</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Archer</strong> : Niveau de base</li>
                            <li><strong>Coach</strong> : Accès Archer + fonctionnalités supplémentaires</li>
                            <li><strong>Dirigeant</strong> : Accès Coach + gestion complète du club</li>
                            <li><strong>Admin</strong> : Accès total à toutes les fonctionnalités (tous les clubs)</li>
                        </ul>
                        <p class="mt-2 mb-0">
                            <small class="text-muted">
                                Les utilisateurs avec un rôle supérieur héritent automatiquement des permissions des rôles inférieurs.
                            </small>
                        </p>
                    </div>
                    
                    <form method="POST" action="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>/permissions" id="permissionsForm">
                        <div class="table-responsive">
                            <table class="table table-hover permissions-table">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Fonctionnalité</th>
                                        <th style="width: 35%;">Action</th>
                                        <th style="width: 35%;">Rôle minimum requis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configurablePermissions as $resource => $config): ?>
                                        <?php 
                                        $isFirst = true;
                                        $actionCount = count($config['actions']);
                                        ?>
                                        <?php foreach ($config['actions'] as $action => $actionLabel): ?>
                                            <?php
                                            $permKey = $resource . '_' . $action;
                                            $currentRole = $permissions[$permKey] ?? 'Coach';
                                            ?>
                                            <tr>
                                                <?php if ($isFirst): ?>
                                                    <td rowspan="<?php echo $actionCount; ?>" class="resource-label">
                                                        <strong><?php echo htmlspecialchars($config['label']); ?></strong>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="action-label"><?php echo htmlspecialchars($actionLabel); ?></span>
                                                </td>
                                                <td>
                                                    <select name="perm_<?php echo htmlspecialchars($permKey); ?>" class="form-select form-select-sm">
                                                        <?php foreach ($availableRoles as $roleValue => $roleLabel): ?>
                                                            <option value="<?php echo htmlspecialchars($roleValue); ?>"
                                                                    <?php echo ($currentRole === $roleValue) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($roleLabel); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php $isFirst = false; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les permissions
                            </button>
                            <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="button" class="btn btn-outline-secondary ms-auto" id="resetDefaultBtn">
                                <i class="fas fa-undo me-2"></i>Réinitialiser aux valeurs par défaut
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
