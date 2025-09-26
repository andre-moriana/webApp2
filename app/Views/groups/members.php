<?php
$title = "Membres du groupe privé - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Membres du groupe privé : <?php echo htmlspecialchars($group['name'] ?? 'Groupe'); ?>
                        <span class="badge bg-warning ms-2">
                            <i class="fas fa-lock me-1"></i>Privé
                        </span>
                    </h4>
                    <div class="btn-group">
                        <a href="/groups" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fas fa-user-plus me-1"></i>Ajouter un membre
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Groupe privé :</strong> Seuls les membres autorisés peuvent voir et participer à ce groupe.
                    </div>
                    
                    <?php if (!empty($members)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                        <?php echo strtoupper(substr($member['name'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($member['name'] ?? 'Nom inconnu'); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email'] ?? 'Email non défini'); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="removeMember(<?php echo $member['id'] ?? 'null'; ?>, '<?php echo htmlspecialchars($member['name'] ?? 'Membre'); ?>')"
                                                            title="Retirer du groupe">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun membre dans ce groupe privé</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="fas fa-user-plus me-1"></i>Ajouter le premier membre
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de membre -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter des membres au groupe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="userSearch" class="form-label">Rechercher un utilisateur</label>
                    <input type="text" class="form-control" id="userSearch" placeholder="Nom, email ou username...">
                </div>
                
                <div id="userSearchResults" class="mb-3">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p>Chargement des utilisateurs...</p>
                    </div>
                </div>
                
                <div id="selectedUsers" class="mb-3">
                    <h6>Utilisateurs sélectionnés :</h6>
                    <div id="selectedUsersList" class="d-flex flex-wrap gap-2">
                        <p class="text-muted">Aucun utilisateur sélectionné</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="addSelectedUsers" disabled>
                    <i class="fas fa-user-plus me-1"></i>Ajouter les membres sélectionnés
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Passer les variables PHP au JavaScript
window.authToken = '<?php echo $_SESSION['token'] ?? ''; ?>';
window.groupId = <?php echo $group['id'] ?? 'null'; ?>;
window.currentMemberIds = <?php echo json_encode(array_column($members ?? [], 'id')); ?>;
</script>

<script src="/public/assets/js/groups-members.js"></script> 