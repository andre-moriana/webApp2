<?php
$title = "Détails du groupe - Portail Archers de Gémenos";
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i><?php echo htmlspecialchars($group['name']); ?>
                    </h4>
                    <div class="btn-group">
                        <a href="/groups/<?php echo $group['id']; ?>/edit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $group['id']; ?>)">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                    </div>
                    </div>
                    <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informations générales</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nom :</strong></td>
                                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Description :</strong></td>
                                    <td><?php echo htmlspecialchars($group['description']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Type :</strong></td>
                                    <td>
                                        <span class="badge <?php echo $group['is_private'] ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $group['is_private'] ? 'Privé' : 'Public'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Admin :</strong></td>
                                    <td><?php echo htmlspecialchars($group['admin_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Créé le :</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($group['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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
                Êtes-vous sûr de vouloir supprimer ce groupe ? Cette action est irréversible.
                    </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="/groups" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="group_id" id="deleteGroupId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function confirmDelete(groupId) {
    document.getElementById('deleteGroupId').value = groupId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
