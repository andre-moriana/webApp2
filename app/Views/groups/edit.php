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
                    <form method="POST" action="/groups/<?php echo $group['id']; ?>" id="groupEditForm">
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
                                       <?php 
                                       // Gérer les différentes clés possibles (is_private, isPrivate) et valeurs (bool, int, string)
                                       $isPrivate = false;
                                       if (isset($group['is_private'])) {
                                           $isPrivate = (bool)$group['is_private'];
                                       } elseif (isset($group['isPrivate'])) {
                                           $isPrivate = (bool)$group['isPrivate'];
                                       }
                                       echo $isPrivate ? 'checked' : '';
                                       ?>>
                                <label class="form-check-label" for="is_private">
                                    Groupe privé
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveButton" disabled>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('groupEditForm');
    const saveButton = document.getElementById('saveButton');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const isPrivateCheckbox = document.getElementById('is_private');
    
    // Valeurs initiales
    const initialValues = {
        name: nameInput.value,
        description: descriptionInput.value,
        is_private: isPrivateCheckbox.checked
    };
    
    // Fonction pour vérifier s'il y a des modifications
    function checkForChanges() {
        const currentValues = {
            name: nameInput.value,
            description: descriptionInput.value,
            is_private: isPrivateCheckbox.checked
        };
        
        const hasChanges = (
            currentValues.name !== initialValues.name ||
            currentValues.description !== initialValues.description ||
            currentValues.is_private !== initialValues.is_private
        );
        
        // Activer/désactiver le bouton selon les modifications
        saveButton.disabled = !hasChanges;
        
        // Changer l'apparence du bouton
        if (hasChanges) {
            saveButton.classList.remove('btn-secondary');
            saveButton.classList.add('btn-primary');
        } else {
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-secondary');
        }
    }
    
    // Écouter les changements sur tous les champs
    nameInput.addEventListener('input', checkForChanges);
    descriptionInput.addEventListener('input', checkForChanges);
    isPrivateCheckbox.addEventListener('change', checkForChanges);
    
    // Vérifier l'état initial
    checkForChanges();
    
    // Empêcher la soumission si le bouton est désactivé
    form.addEventListener('submit', function(e) {
        if (saveButton.disabled) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
