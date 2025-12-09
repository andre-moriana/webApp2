<?php
// Définir l'URL du backend
$backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Fiches d'Exercices</h1>
                <div class="d-flex align-items-center gap-3">
                    <a href="/exercises/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvel Exercice
                    </a>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>


            <div class="row">
                <!-- Message de confirmation -->
                <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                    <div class="col-12 mb-4">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Exercice mis à jour avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Message de confirmation de suppression -->
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                    <div class="col-12 mb-4">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Exercice supprimé avec succès !
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Message d'erreur -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="col-12 mb-4">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php 
                // Vérifier si $exercises contient la réponse complète de l'API et l'extraire
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                ?>

                <?php if (empty($exercises) || !is_array($exercises)): ?>
                    <div class="col-12">
                        <div class="alert alert-info" role="alert">
                            Aucun exercice trouvé. <a href="/exercises/create">Créer le premier exercice</a>
                            <?php if (isset($error)): ?>
                                <br><small class="text-muted">Erreur: <?php echo htmlspecialchars($error); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    // Grouper les exercices par catégorie
                    $exercisesByCategory = [];
                    foreach ($exercises as $exercise) {
                        if (is_array($exercise)) {
                            $category = $exercise['category'] ?? 'Sans catégorie';
                            if (!isset($exercisesByCategory[$category])) {
                                $exercisesByCategory[$category] = [];
                            }
                            $exercisesByCategory[$category][] = $exercise;
                        }
                    }
                    
                    // Trier les catégories par ordre alphabétique
                    ksort($exercisesByCategory);
                    ?>

                    <?php foreach ($exercisesByCategory as $categoryName => $categoryExercises): ?>
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($categoryName); ?>
                                        <span class="badge badge-light ml-2"><?php echo count($categoryExercises); ?> exercice(s)</span>
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($categoryExercises as $exercise): ?>
                                            <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($exercise['title'] ?? 'Sans titre'); ?></h5>
                                                        <p class="mb-1 text-muted">
                                                            <?php echo htmlspecialchars(substr($exercise['description'] ?? '', 0, 150)); ?>
                                                            <?php if (strlen($exercise['description'] ?? '') > 150): ?>
                                                                ...
                                                            <?php endif; ?>
                                                        </p>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (isset($exercise['attachment_filename']) && $exercise['attachment_filename']): ?>
                                                                <span class="badge badge-info mr-2">
                                                                    <i class="fas fa-paperclip"></i> Fichier joint
                                                                </span>
                                                                <button class="btn btn-sm btn-outline-info mr-2" 
                                                                        onclick="showPdfPreview('<?php echo $backendUrl . '/uploads/exercise_sheets/' . $exercise['attachment_filename']; ?>', '<?php echo htmlspecialchars($exercise['attachment_original_name'] ?? $exercise['attachment_filename']); ?>')"
                                                                        title="Aperçu du PDF">
                                                                    <i class="fas fa-eye"></i> Aperçu
                                                                </button>
                                                            <?php endif; ?>
                                                            
                                                            
                                                            <small class="text-muted">
                                                                Créé le <?php echo date('d/m/Y', strtotime($exercise['created_at'] ?? '')); ?>
                                                                par <?php echo htmlspecialchars($exercise['creator_name'] ?? 'Inconnu'); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-4 text-right">
                                                        <div class="btn-group" role="group">
                                                            <a href="/exercises/<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>" 
                                                               class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            
                                                            <?php 
                                                            // Vérifier les permissions pour l'édition et la suppression
                                                            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
                                                            $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach'; // Supprimé strtolower()
                                                            $isDirigeant = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Dirigeant';
                                                            $canEdit = $isAdmin || $isCoach || $isDirigeant;
                                                            ?>
                                                            
                                                            <?php if ($canEdit): ?>
                                                                <a href="/exercises/<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>/edit" 
                                                                   class="btn btn-outline-secondary btn-sm">
                                                                    <i class="fas fa-edit"></i> Modifier
                                                                </a>
                                                                <button class="btn btn-outline-danger btn-sm" 
                                                                        onclick="deleteExercise(<?php echo $exercise['id'] ?? $exercise['_id'] ?? ''; ?>)">
                                                                    <i class="fas fa-trash"></i> Supprimer
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if (isset($exercise['attachment_filename']) && $exercise['attachment_filename']): ?>
                                                            <?php 
                                                            $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                                                            $fileUrl = $backendUrl . '/uploads/exercise_sheets/' . $exercise['attachment_filename'];
                                                            ?>
                                                            <div class="mt-2">
                                                                <a href="<?php echo htmlspecialchars($fileUrl); ?>" 
                                                                   target="_blank" class="btn btn-sm btn-outline-info">
                                                                    <i class="fas fa-download"></i> Télécharger
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

<!-- Inclure la modale d'aperçu PDF -->
<?php include 'app/Views/components/pdf-preview-modal.php'; ?>

<script src="/public/assets/js/exercises.js"></script>
