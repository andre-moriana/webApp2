<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-check me-2"></i>Validation des utilisateurs</h2>
                <a href="/dashboard" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour au tableau de bord
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Utilisateurs en attente de validation
                        <span class="badge bg-warning ms-2"><?php echo count($pendingUsers); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingUsers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4 class="text-muted">Aucun utilisateur en attente</h4>
                            <p class="text-muted">Tous les utilisateurs ont été traités.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Rôle demandé</th>
                                        <th>Date de demande</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Debug: Afficher la structure des données
                                    error_log("DEBUG: Nombre d'utilisateurs: " . count($pendingUsers));
                                    error_log("DEBUG: Structure complète: " . json_encode($pendingUsers));
                                    if (!empty($pendingUsers) && isset($pendingUsers[0])) {
                                        error_log("DEBUG: Structure du premier utilisateur: " . json_encode($pendingUsers[0]));
                                    }
                                    ?>
                                    <?php foreach ($pendingUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <?php echo strtoupper(substr($user['name'] ?? '', 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['name'] ?? ''); ?></strong>
                                                        <br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username'] ?? ''); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($user['role'] ?? 'Archer'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $createdAt = $user['created_at'] ?? '';
                                                if ($createdAt) {
                                                    echo date('d/m/Y H:i', strtotime($createdAt));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <form method="POST" action="/user-validation/approve" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" 
                                                                onclick="return confirm('Êtes-vous sûr de vouloir valider cet utilisateur ?')">
                                                            <i class="fas fa-check me-1"></i>Valider
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectModal<?php echo $user['id'] ?? ''; ?>">
                                                        <i class="fas fa-times me-1"></i>Rejeter
                                                    </button>
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

<!-- Modales de rejet pour chaque utilisateur -->
<?php foreach ($pendingUsers as $user): ?>
    <div class="modal fade" id="rejectModal<?php echo $user['id'] ?? ''; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Rejeter l'utilisateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/user-validation/reject">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Vous êtes sur le point de rejeter la demande d'inscription de 
                            <strong><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></strong>.
                        </div>
                        
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                        
                        <div class="mb-3">
                            <label for="reason<?php echo $user['id'] ?? ''; ?>" class="form-label">
                                Raison du rejet (optionnel)
                            </label>
                            <textarea class="form-control" 
                                      id="reason<?php echo $user['id'] ?? ''; ?>" 
                                      name="reason" 
                                      rows="3" 
                                      placeholder="Expliquez pourquoi cette demande est rejetée..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban me-1"></i>Rejeter définitivement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

