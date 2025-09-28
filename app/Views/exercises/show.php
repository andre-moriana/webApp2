<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Détails de l'Exercice</h1>
                <a href="/exercises" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif ($exercise): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-dumbbell me-2"></i>
                            <?php echo htmlspecialchars($exercise['title'] ?? 'Sans titre'); ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Description</h5>
                                <p class="text-muted">
                                    <?php echo nl2br(htmlspecialchars($exercise['description'] ?? 'Aucune description')); ?>
                                </p>

                                <h5>Informations</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Catégorie :</strong> 
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($exercise['category'] ?? 'Non définie'); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Créé le :</strong> 
                                            <?php echo date('d/m/Y à H:i', strtotime($exercise['created_at'] ?? '')); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if (isset($exercise['creator_name'])): ?>
                                    <p><strong>Créé par :</strong> 
                                        <?php echo htmlspecialchars($exercise['creator_name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4">
                                <?php if (isset($exercise['attachment_filename']) && $exercise['attachment_filename']): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-paperclip me-2"></i>
                                                Fichier joint
                                            </h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                            <p class="mb-2">
                                                <strong><?php echo htmlspecialchars($exercise['attachment_original_name'] ?? $exercise['attachment_filename']); ?></strong>
                                            </p>
                                            <?php 
                                            $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                                            $fileUrl = $backendUrl . '/uploads/exercise_sheets/' . $exercise['attachment_filename'];
                                            ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" 
                                               target="_blank" class="btn btn-outline-danger">
                                                <i class="fas fa-download"></i> Télécharger
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="card">
                                        <div class="card-body text-center text-muted">
                                            <i class="fas fa-file-pdf fa-3x mb-3"></i>
                                            <p>Aucun fichier joint</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php 
                                // Vérifier les permissions pour l'édition et la suppression
                                $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
                                $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach';
                                $canEdit = $isAdmin || $isCoach;
                                ?>
                                
                                <?php if ($canEdit): ?>
                                    <a href="/exercises/<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>/edit" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <button class="btn btn-outline-danger ms-2" 
                                            onclick="deleteExercise(<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>)">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <a href="/exercises" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> Voir tous les exercices
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> Aucun exercice trouvé.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 