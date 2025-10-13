<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Modifier l'exercice</h2>
                    <a href="/exercises" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> Exercice mis à jour avec succès !
                        </div>
                    <?php endif; ?>

                    <?php if (!$exercise): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Erreur :</strong> Impossible de charger les données de l'exercice. L'API backend ne répond pas.
                        </div>
                        <a href="/exercises" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    <?php else: ?>
                    <form method="POST" action="/exercises/<?php echo $exercise['id']; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="_method" value="PUT">
                        <div class="form-group">
                            <label for="title">Titre de l'exercice *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($exercise['title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                      placeholder="Décrivez l'exercice, les objectifs, les consignes..."><?php echo htmlspecialchars($exercise['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="category">Catégorie *</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($exercise['category_id']) && $exercise['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="attachment">Fichier PDF (optionnel)</label>
                            <input type="file" class="form-control-file" id="attachment" name="attachment" accept=".pdf">
                            <small class="form-text text-muted">Taille maximale : 10MB</small>
                            <?php if (isset($exercise['attachment_filename']) && $exercise['attachment_filename']): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Fichier actuel : <?php echo htmlspecialchars($exercise['attachment_filename']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                            <a href="/exercises/<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
