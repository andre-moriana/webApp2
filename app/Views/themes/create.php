<?php
$title = "Créer un thème - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-palette me-2"></i>Créer un nouveau thème
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error']) && $_SESSION['error']): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="/themes" id="themeCreateForm" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du thème <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Veuillez saisir le nom du thème.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="clubName" class="form-label">Nom du club <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="clubName" name="clubName"
                                           value="<?php echo htmlspecialchars($_POST['clubName'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Veuillez saisir le nom du club.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="clubNameShort" class="form-label">Nom court du club</label>
                            <input type="text" class="form-control" id="clubNameShort" name="clubNameShort"
                                   value="<?php echo htmlspecialchars($_POST['clubNameShort'] ?? ''); ?>">
                            <small class="form-text text-muted">
                                Le logo sera géré par la table clubs, pas par le thème
                            </small>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3"><i class="fas fa-paint-brush me-2"></i>Couleurs du thème</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorPrimary" class="form-label">Couleur primaire</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorPrimary" name="colorPrimary" 
                                               value="<?php echo htmlspecialchars($_POST['colorPrimary'] ?? '#14532d'); ?>">
                                        <input type="text" class="form-control" id="colorPrimaryText" 
                                               value="<?php echo htmlspecialchars($_POST['colorPrimary'] ?? '#14532d'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorSecondary" class="form-label">Couleur secondaire</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorSecondary" name="colorSecondary" 
                                               value="<?php echo htmlspecialchars($_POST['colorSecondary'] ?? '#BBCE00'); ?>">
                                        <input type="text" class="form-control" id="colorSecondaryText" 
                                               value="<?php echo htmlspecialchars($_POST['colorSecondary'] ?? '#BBCE00'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorBackground" class="form-label">Couleur de fond</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorBackground" name="colorBackground" 
                                               value="<?php echo htmlspecialchars($_POST['colorBackground'] ?? '#14532d'); ?>">
                                        <input type="text" class="form-control" id="colorBackgroundText" 
                                               value="<?php echo htmlspecialchars($_POST['colorBackground'] ?? '#14532d'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorSurface" class="form-label">Couleur de surface</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorSurface" name="colorSurface" 
                                               value="<?php echo htmlspecialchars($_POST['colorSurface'] ?? '#f8f9fa'); ?>">
                                        <input type="text" class="form-control" id="colorSurfaceText" 
                                               value="<?php echo htmlspecialchars($_POST['colorSurface'] ?? '#f8f9fa'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorText" class="form-label">Couleur du texte</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorText" name="colorText" 
                                               value="<?php echo htmlspecialchars($_POST['colorText'] ?? '#333333'); ?>">
                                        <input type="text" class="form-control" id="colorTextText" 
                                               value="<?php echo htmlspecialchars($_POST['colorText'] ?? '#333333'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorTextSecondary" class="form-label">Couleur du texte secondaire</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorTextSecondary" name="colorTextSecondary" 
                                               value="<?php echo htmlspecialchars($_POST['colorTextSecondary'] ?? '#666666'); ?>">
                                        <input type="text" class="form-control" id="colorTextSecondaryText" 
                                               value="<?php echo htmlspecialchars($_POST['colorTextSecondary'] ?? '#666666'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorAccent" class="form-label">Couleur d'accent</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorAccent" name="colorAccent" 
                                               value="<?php echo htmlspecialchars($_POST['colorAccent'] ?? '#BBCE00'); ?>">
                                        <input type="text" class="form-control" id="colorAccentText" 
                                               value="<?php echo htmlspecialchars($_POST['colorAccent'] ?? '#BBCE00'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="colorButton" class="form-label">Couleur des boutons</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorButton" name="colorButton" 
                                               value="<?php echo htmlspecialchars($_POST['colorButton'] ?? '#007AFF'); ?>">
                                        <input type="text" class="form-control" id="colorButtonText" 
                                               value="<?php echo htmlspecialchars($_POST['colorButton'] ?? '#007AFF'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="colorError" class="form-label">Couleur d'erreur</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorError" name="colorError" 
                                               value="<?php echo htmlspecialchars($_POST['colorError'] ?? '#dc2626'); ?>">
                                        <input type="text" class="form-control" id="colorErrorText" 
                                               value="<?php echo htmlspecialchars($_POST['colorError'] ?? '#dc2626'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="colorSuccess" class="form-label">Couleur de succès</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorSuccess" name="colorSuccess" 
                                               value="<?php echo htmlspecialchars($_POST['colorSuccess'] ?? '#22c55e'); ?>">
                                        <input type="text" class="form-control" id="colorSuccessText" 
                                               value="<?php echo htmlspecialchars($_POST['colorSuccess'] ?? '#22c55e'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="colorWarning" class="form-label">Couleur d'avertissement</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="colorWarning" name="colorWarning" 
                                               value="<?php echo htmlspecialchars($_POST['colorWarning'] ?? '#f59e0b'); ?>">
                                        <input type="text" class="form-control" id="colorWarningText" 
                                               value="<?php echo htmlspecialchars($_POST['colorWarning'] ?? '#f59e0b'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="colorInfo" class="form-label">Couleur d'information</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="colorInfo" name="colorInfo" 
                                       value="<?php echo htmlspecialchars($_POST['colorInfo'] ?? '#3b82f6'); ?>">
                                <input type="text" class="form-control" id="colorInfoText" 
                                       value="<?php echo htmlspecialchars($_POST['colorInfo'] ?? '#3b82f6'); ?>" 
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer le thème
                            </button>
                            <a href="/themes" class="btn btn-secondary">
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
// Synchroniser les champs couleur et texte
document.addEventListener('DOMContentLoaded', function() {
    const colorFields = ['Primary', 'Secondary', 'Background', 'Surface', 'Text', 'TextSecondary', 'Accent', 'Button', 'Error', 'Success', 'Warning', 'Info'];
    
    colorFields.forEach(function(field) {
        const colorInput = document.getElementById('color' + field);
        const textInput = document.getElementById('color' + field + 'Text');
        
        if (colorInput && textInput) {
            colorInput.addEventListener('input', function() {
                textInput.value = this.value;
            });
            
            textInput.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/i.test(this.value)) {
                    colorInput.value = this.value;
                }
            });
        }
    });
});

// Validation Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

