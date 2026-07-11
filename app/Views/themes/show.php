<?php
$title = "Détails du thème - Portail Arc Training";
?>

<div class="container-fluid themes-page">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-palette me-2"></i>Détails du thème
                        </h4>
                        <div class="btn-group">
                            <?php if (($theme['id'] ?? '') !== 'default'): ?>
                                <a href="/themes/<?php echo htmlspecialchars($theme['id'] ?? ''); ?>/edit" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </a>
                            <?php endif; ?>
                            <a href="/themes" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informations générales</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">ID :</th>
                                    <td><code><?php echo htmlspecialchars($theme['id'] ?? ''); ?></code></td>
                                </tr>
                                <tr>
                                    <th>Nom :</th>
                                    <td><strong><?php echo htmlspecialchars($theme['name'] ?? ''); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Créé le :</th>
                                    <td>
                                        <?php if (!empty($theme['createdAt'])): ?>
                                            <?php echo date('d/m/Y à H:i', strtotime($theme['createdAt'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (($theme['id'] ?? '') === 'default'): ?>
                                <tr>
                                    <th>Statut :</th>
                                    <td><span class="badge bg-primary">Thème par défaut</span></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($theme['colors'])): ?>
                            <h5>Couleurs</h5>
                            <div class="row g-2">
                                <?php 
                                $colorLabels = [
                                    'primary' => 'Primaire',
                                    'secondary' => 'Secondaire',
                                    'background' => 'Fond',
                                    'surface' => 'Surface',
                                    'text' => 'Texte',
                                    'textSecondary' => 'Texte secondaire',
                                    'accent' => 'Accent',
                                    'button' => 'Boutons',
                                    'error' => 'Erreur',
                                    'success' => 'Succès',
                                    'warning' => 'Avertissement',
                                    'info' => 'Information'
                                ];
                                foreach ($theme['colors'] as $key => $color): 
                                    if (isset($colorLabels[$key])):
                                ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge theme-color-swatch theme-color-swatch--md me-2" style="background-color: <?php echo htmlspecialchars($color); ?>;"></span>
                                            <div>
                                                <strong><?php echo htmlspecialchars($colorLabels[$key]); ?></strong><br>
                                                <small class="text-muted"><code><?php echo htmlspecialchars($color); ?></code></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($theme['colors'])): 
                        $previewColors = $theme['colors'];
                    ?>
                    <hr>
                    <h5>Aperçu du thème</h5>
                    <p class="text-muted small mb-3">L'aperçu reflète le rendu du thème club selon le mode d'affichage du portail.</p>
                    <div class="theme-preview-box p-4 rounded"
                         style="--tp-primary: <?php echo htmlspecialchars($previewColors['primary'] ?? '#14532d'); ?>;
                                --tp-secondary: <?php echo htmlspecialchars($previewColors['secondary'] ?? '#BBCE00'); ?>;
                                --tp-background: <?php echo htmlspecialchars($previewColors['background'] ?? '#14532d'); ?>;
                                --tp-surface: <?php echo htmlspecialchars($previewColors['surface'] ?? '#f8f9fa'); ?>;
                                --tp-text: <?php echo htmlspecialchars($previewColors['text'] ?? '#333333'); ?>;
                                --tp-text-secondary: <?php echo htmlspecialchars($previewColors['textSecondary'] ?? '#666666'); ?>;
                                --tp-accent: <?php echo htmlspecialchars($previewColors['accent'] ?? '#BBCE00'); ?>;
                                --tp-button: <?php echo htmlspecialchars($previewColors['button'] ?? '#007AFF'); ?>;
                                --tp-success: <?php echo htmlspecialchars($previewColors['success'] ?? '#22c55e'); ?>;
                                --tp-warning: <?php echo htmlspecialchars($previewColors['warning'] ?? '#f59e0b'); ?>;
                                --tp-error: <?php echo htmlspecialchars($previewColors['error'] ?? '#dc2626'); ?>;
                                --tp-info: <?php echo htmlspecialchars($previewColors['info'] ?? '#3b82f6'); ?>;">
                        <h6 class="theme-preview-title">Exemple de titre</h6>
                        <p class="theme-preview-subtitle mb-3">Exemple de texte secondaire</p>
                        <div class="theme-preview-surface p-3 rounded mb-3">
                            <span class="theme-preview-surface-label">Zone surface</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn theme-preview-btn theme-preview-btn-primary">Bouton primaire</button>
                            <button type="button" class="btn theme-preview-btn theme-preview-btn-accent">Bouton accent</button>
                        </div>
                        <div class="mt-3 d-flex gap-2 flex-wrap">
                            <span class="badge theme-preview-badge theme-preview-badge-success">Succès</span>
                            <span class="badge theme-preview-badge theme-preview-badge-warning">Avertissement</span>
                            <span class="badge theme-preview-badge theme-preview-badge-error">Erreur</span>
                            <span class="badge theme-preview-badge theme-preview-badge-info">Information</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

