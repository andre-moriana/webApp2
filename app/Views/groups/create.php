<?php
$title = "Créer un nouveau groupe - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Créer un nouveau groupe
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/groups" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du groupe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($_SESSION['old_input']['name'] ?? ''); ?>">
                            <?php if (isset($_SESSION['errors']['name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php echo htmlspecialchars($_SESSION['errors']['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                    ><?php echo htmlspecialchars($_SESSION['old_input']['description'] ?? ''); ?></textarea>
                            <?php if (isset($_SESSION['errors']['description'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?php echo htmlspecialchars($_SESSION['errors']['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1"
                                       <?php echo isset($_SESSION['old_input']['is_private']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_private">Groupe privé</label>
                            </div>
                            <small class="form-text text-muted">
                                Les groupes privés ne sont visibles que par leurs membres
                            </small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer le groupe
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

<?php
// Nettoyer les données de session
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?> 