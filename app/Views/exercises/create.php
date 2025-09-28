<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Créer un Nouvel Exercice</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="/exercises" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Titre de l'exercice *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                      placeholder="Décrivez l'exercice, les objectifs, les consignes..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="category">Catégorie *</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php if (is_array($categories) && !empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <?php if (is_array($category) && isset($category['id']) && isset($category['name'])): ?>
                                            <option value="<?php echo htmlspecialchars($category['id']); ?>"
                                                    <?php echo (($_POST['category'] ?? '') === $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Aucune catégorie disponible</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="attachment">Fichier PDF (optionnel)</label>
                            <input type="file" class="form-control-file" id="attachment" name="attachment" accept=".pdf">
                            <small class="form-text text-muted">Taille maximale : 10MB</small>
                        </div>
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Créer l'Exercice
                            </button>
                            <a href="/exercises" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>s
        </div>
    </div>
</div>