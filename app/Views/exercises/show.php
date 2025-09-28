<div class="container">
    <div class="row">
        <div class="col-12">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2><?php echo htmlspecialchars($exercise['title'] ?? 'Sans titre'); ?></h2>
                        <div>
                            <a href="/exercises/<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>/edit" class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="/exercises" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>Description</h4>
                                <p><?php echo nl2br(htmlspecialchars($exercise['description'] ?? 'Aucune description')); ?></p>
                                
                                <h4>Détails</h4>
                                <ul class="list-group list-group-flush">
                                    <?php if (isset($exercise['category'])): ?>
                                        <li class="list-group-item">
                                            <strong>Catégorie :</strong> <?php echo htmlspecialchars($exercise['category']); ?>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($exercise['creator_name'])): ?>
                                        <li class="list-group-item">
                                            <strong>Créé par :</strong> <?php echo htmlspecialchars($exercise['creator_name']); ?>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($exercise['progression'])): ?>
                                        <li class="list-group-item">
                                            <strong>Progression :</strong> <?php echo htmlspecialchars($exercise['progression']); ?>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($exercise['created_at'])): ?>
                                        <li class="list-group-item">
                                            <strong>Créé le :</strong> <?php echo date('d/m/Y H:i', strtotime($exercise['created_at'])); ?>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($exercise['updated_at'])): ?>
                                        <li class="list-group-item">
                                            <strong>Modifié le :</strong> <?php echo date('d/m/Y H:i', strtotime($exercise['updated_at'])); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <div class="col-md-4">
                                <?php if (isset($exercise['attachment_filename']) && !empty($exercise['attachment_filename'])): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Fichier joint</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <?php if (isset($exercise['attachment_mime_type']) && strpos($exercise['attachment_mime_type'], 'pdf') !== false): ?>
                                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file fa-3x text-primary mb-3"></i>
                                            <?php endif; ?>
                                            
                                            <p class="card-text">
                                                <strong><?php echo htmlspecialchars($exercise['attachment_original_name'] ?? $exercise['attachment_filename']); ?></strong>
                                            </p>
                                            
                                            <?php if (isset($exercise['attachment_size'])): ?>
                                                <p class="text-muted small">
                                                    <?php echo number_format($exercise['attachment_size'] / 1024, 1); ?> KB
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // Construire l'URL du fichier sur le backend
                                            $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                                            $fileUrl = $backendUrl . '/uploads/exercise_sheets/' . $exercise['attachment_filename'];
                                            ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" 
                                               class="btn btn-primary" target="_blank">
                                                <i class="fas fa-download"></i> Télécharger
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-slash fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Aucun fichier joint</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>