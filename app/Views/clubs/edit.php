<?php
$title = "Modifier le club - Portail Arc Training";
$additionalJS = $additionalJS ?? [];
$additionalJS[] = '/public/assets/js/clubs-form.js';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Modifier le club
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
                    
                    <form method="POST" action="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>" id="clubEditForm" class="needs-validation" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du club <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($club['name'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Veuillez saisir le nom du club.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nameShort" class="form-label">Nom court</label>
                                    <input type="text" class="form-control" id="nameShort" name="nameShort"
                                           value="<?php echo htmlspecialchars($club['nameShort'] ?? $club['name_short'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($club['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="address" name="address"
                                           value="<?php echo htmlspecialchars($club['address'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                           value="<?php echo htmlspecialchars($club['city'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="postalCode" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="postalCode" name="postalCode"
                                           value="<?php echo htmlspecialchars($club['postalCode'] ?? $club['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($club['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($club['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="website" class="form-label">Site web</label>
                                    <input type="url" class="form-control" id="website" name="website"
                                           value="<?php echo htmlspecialchars($club['website'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo du club</label>
                            <?php if (!empty($club['logo'])): 
                                // Construire l'URL complète du logo si c'est un chemin relatif
                                $logoUrl = $club['logo'];
                                if (!empty($logoUrl) && !preg_match('/^https?:\/\//', $logoUrl)) {
                                    $backendUrl = $_ENV['API_BASE_URL'];
                                    $backendUrl = rtrim($backendUrl, '/');
                                    // Retirer /api de l'URL si présent
                                    if (substr($backendUrl, -4) === '/api') {
                                        $backendUrl = substr($backendUrl, 0, -4);
                                    }
                                }
                            ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" 
                                         alt="Logo actuel" 
                                         style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; object-fit: contain;">
                                    <br>
                                    <small class="text-muted">Logo actuel</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="form-text text-muted">
                                Formats acceptés: JPG, PNG, GIF, SVG, WEBP. Taille maximale: 10MB
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="theme" class="form-label">Thème</label>
                            <select class="form-select" id="theme" name="theme">
                                <option value="">Sélectionner un thème...</option>
                                <?php if (!empty($themes)): ?>
                                    <?php foreach ($themes as $theme): ?>
                                        <option value="<?php echo htmlspecialchars($theme['id'] ?? ''); ?>" 
                                                <?php echo (($club['theme'] ?? '') === ($theme['id'] ?? '')) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($theme['name'] ?? $theme['id'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">
                                Choisissez le thème visuel du club
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="presidentId" class="form-label">Président (ID utilisateur)</label>
                            <input type="number" class="form-control" id="presidentId" name="presidentId"
                                   value="<?php echo htmlspecialchars($club['presidentId'] ?? $club['president_id'] ?? ''); ?>">
                            <small class="form-text text-muted">
                                Optionnel : ID de l'utilisateur qui sera désigné comme président du club
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <a href="/clubs" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
