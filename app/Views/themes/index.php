<?php
$title = "Gestion des thèmes - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-palette me-2"></i>Gestion des thèmes
                </h1>
                <a href="/themes/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau thème
                </a>
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
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($themes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-palette fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun thème trouvé.</p>
                            <a href="/themes/create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Créer le premier thème
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Club</th>
                                        <th>Couleurs</th>
                                        <th>Créé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($themes as $theme): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars($theme['id'] ?? ''); ?></code>
                                                <?php if (($theme['id'] ?? '') === 'default'): ?>
                                                    <span class="badge bg-primary ms-2">Par défaut</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($theme['name'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($theme['clubName'] ?? ''); ?>
                                                <?php if (!empty($theme['clubNameShort'])): ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($theme['clubNameShort']); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($theme['colors'])): ?>
                                                    <div class="d-flex gap-1">
                                                        <?php 
                                                        $colorKeys = ['primary', 'secondary', 'accent', 'background'];
                                                        foreach ($colorKeys as $key): 
                                                            $color = $theme['colors'][$key] ?? null;
                                                            if ($color):
                                                        ?>
                                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($color); ?>; width: 20px; height: 20px; border-radius: 50%;" title="<?php echo htmlspecialchars($key . ': ' . $color); ?>"></span>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($theme['createdAt'])): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($theme['createdAt'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/themes/<?php echo htmlspecialchars($theme['id'] ?? ''); ?>" class="btn btn-outline-info" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (($theme['id'] ?? '') !== 'default'): ?>
                                                        <a href="/themes/<?php echo htmlspecialchars($theme['id'] ?? ''); ?>/edit" class="btn btn-outline-primary" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" action="/themes/<?php echo htmlspecialchars($theme['id'] ?? ''); ?>" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce thème ?');">
                                                            <input type="hidden" name="_method" value="DELETE">
                                                            <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted" title="Le thème par défaut ne peut pas être modifié ou supprimé">
                                                            <i class="fas fa-lock"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

