<?php
$title = "Détails du thème - Portail Archers de Gémenos";
?>

<div class="container-fluid">
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
                                    <th>Nom du club :</th>
                                    <td><?php echo htmlspecialchars($theme['clubName'] ?? ''); ?></td>
                                </tr>
                                <?php if (!empty($theme['clubNameShort'])): ?>
                                <tr>
                                    <th>Nom court du club :</th>
                                    <td><?php echo htmlspecialchars($theme['clubNameShort']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Logo :</th>
                                    <td>
                                        <span class="text-muted">Le logo est géré par la table clubs</span>
                                    </td>
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
                                            <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($color); ?>; width: 30px; height: 30px; border-radius: 4px;"></span>
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
                    
                    <?php if (!empty($theme['colors'])): ?>
                    <hr>
                    <h5>Aperçu du thème</h5>
                    <div class="p-4 rounded" style="background-color: <?php echo htmlspecialchars($theme['colors']['background'] ?? '#14532d'); ?>; color: <?php echo htmlspecialchars($theme['colors']['text'] ?? '#333333'); ?>;">
                        <h6 style="color: <?php echo htmlspecialchars($theme['colors']['text'] ?? '#333333'); ?>;">Exemple de titre</h6>
                        <p style="color: <?php echo htmlspecialchars($theme['colors']['textSecondary'] ?? '#666666'); ?>;">Exemple de texte secondaire</p>
                        <div class="d-flex gap-2">
                            <button class="btn" style="background-color: <?php echo htmlspecialchars($theme['colors']['button'] ?? '#007AFF'); ?>; color: white;">Bouton primaire</button>
                            <button class="btn" style="background-color: <?php echo htmlspecialchars($theme['colors']['accent'] ?? '#BBCE00'); ?>; color: white;">Bouton accent</button>
                        </div>
                        <div class="mt-3">
                            <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($theme['colors']['success'] ?? '#22c55e'); ?>;">Succès</span>
                            <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($theme['colors']['warning'] ?? '#f59e0b'); ?>;">Avertissement</span>
                            <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($theme['colors']['error'] ?? '#dc2626'); ?>;">Erreur</span>
                            <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($theme['colors']['info'] ?? '#3b82f6'); ?>;">Information</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

