<?php
$title = "Modifier le groupe - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Modifier le groupe
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="/groups/<?php echo $group['id']; ?>">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du groupe *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($group['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($group['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_private" name="is_private" 
                                       <?php echo (isset($group['is_private']) && $group['is_private']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_private">
                                    Groupe privé
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <a href="/groups" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
