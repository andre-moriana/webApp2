<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Messages d'erreur/succès -->
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Informations de l'archer en haut de la page -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <?php if (!empty($training['profile_image'])): ?>
                            <?php
                            // Construire l'URL complète de l'image de profil
                            $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                            $profileImageUrl = $backendUrl . $training['profile_image'];
                            ?>
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                                 class="rounded-circle" 
                                 style="width: 60px; height: 60px; object-fit: cover;" 
                                 alt="Photo de profil"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-user text-white fa-2x"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-1"><?php echo htmlspecialchars($training['user_name'] ?? 'Archer inconnu'); ?></h4>
                            <p class="text-muted mb-0">
                                Session du <?php echo date('d/m/Y à H:i', strtotime($training['start_date'] ?? '')); ?>
                                <?php if (!empty($training['end_date'])): ?>
                                - <?php echo date('H:i', strtotime($training['end_date'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="d-flex align-items-center">
                            <!-- Boutons de navigation entre exercices de la même catégorie -->
                            <?php if (count($categoryExercisesWithSessions) > 1): ?>
                            <div class="me-3">
                                <?php if ($previousExercise): ?>
                                <a href="/trainings/<?php echo $previousExercise['session_id'] ?? ''; ?>" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-chevron-left"></i> Exercice précédent
                                </a>
                                <?php else: ?>
                                <button class="btn btn-outline-primary btn-sm me-2" disabled>
                                    <i class="fas fa-chevron-left"></i> Exercice précédent
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($nextExercise): ?>
                                <a href="/trainings/<?php echo $nextExercise['session_id'] ?? ''; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    Exercice suivant <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-outline-primary btn-sm" disabled>
                                    Exercice suivant <i class="fas fa-chevron-right"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <a href="/trainings" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Indicateur de position pour les exercices -->
            <?php if (count($categoryExercisesWithSessions) > 1 && $currentExerciseIndex >= 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Exercice <?php echo $currentExerciseIndex + 1; ?> sur <?php echo count($categoryExercisesWithSessions); ?> 
                dans la catégorie "<?php echo htmlspecialchars($training['category'] ?? ''); ?>"
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <!-- Informations de l'exercice -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Exercice</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($training['title'] ?? 'Non défini'); ?></p>
                                    <p><strong>Catégorie :</strong> 
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($training['category'] ?? 'Non définie'); ?></span>
                                    </p>
                                    
                                    <!-- Statut de l'exercice -->
                                    <p><strong>Statut :</strong> 
                                        <?php
                                        $progression = $training['progression'] ?? 'non_actif';
                                        $progressionClass = '';
                                        $progressionText = '';
                                        
                                        switch($progression) {
                                            case 'en_cours':
                                                $progressionClass = 'bg-warning';
                                                $progressionText = 'En cours';
                                                break;
                                            case 'acquis':
                                                $progressionClass = 'bg-success';
                                                $progressionText = 'Acquis';
                                                break;
                                            case 'masqué':
                                                $progressionClass = 'bg-danger';
                                                $progressionText = 'Masqué';
                                                break;
                                            default:
                                                $progressionClass = 'bg-secondary';
                                                $progressionText = 'Non actif';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $progressionClass; ?>"><?php echo $progressionText; ?></span>
                                        
                                        <!-- Bouton de modification du statut pour les coaches/admins -->
                                        <?php if (($currentUser['role'] ?? '') === 'Coach' || ($currentUser['is_admin'] ?? false)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#editProgressionModal">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <!-- Bouton pour commencer une session - Plus visible -->
                                    <div class="alert alert-info mt-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Prêt à s'entraîner ?</strong>
                                                <br><small>Commencez une nouvelle session d'entraînement</small>
                                            </div>
                                            <button type="button" class="btn btn-success btn-lg" id="startSessionBtn">
                                                <i class="fas fa-play me-2"></i>Commencer une session
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Description de l'exercice -->
                                    <?php if (!empty($training['description'])): ?>
                                    <div class="mt-3">
                                        <strong>Description :</strong>
                                        <p class="mt-1"><?php echo nl2br(htmlspecialchars($training['description'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <!-- Aperçu de la pièce jointe -->
                                    <?php if (!empty($training['attachment_filename'])): ?>
                                    <div class="text-center">
                                        <strong>Pièce jointe :</strong>
                                        <div class="mt-2">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="mb-2">
                                                    <?php
                                                    $mimeType = $training['attachment_mime_type'] ?? '';
                                                    $fileExtension = strtolower(pathinfo($training['attachment_filename'], PATHINFO_EXTENSION));
                                                    
                                                    // Icône selon le type de fichier
                                                    if (strpos($mimeType, 'pdf') !== false || $fileExtension === 'pdf') {
                                                        echo '<i class="fas fa-file-pdf fa-3x text-danger"></i>';
                                                    } elseif (strpos($mimeType, 'image') !== false || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                                                        echo '<i class="fas fa-file-image fa-3x text-primary"></i>';
                                                    } elseif (strpos($mimeType, 'word') !== false || in_array($fileExtension, ['doc', 'docx'])) {
                                                        echo '<i class="fas fa-file-word fa-3x text-primary"></i>';
                                                    } elseif (strpos($mimeType, 'excel') !== false || in_array($fileExtension, ['xls', 'xlsx'])) {
                                                        echo '<i class="fas fa-file-excel fa-3x text-success"></i>';
                                                    } else {
                                                        echo '<i class="fas fa-file fa-3x text-secondary"></i>';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="text-center">
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($training['attachment_original_name'] ?? $training['attachment_filename']); ?></strong>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?php if (!empty($training['attachment_size'])): ?>
                                                            <?php echo number_format($training['attachment_size'] / 1024, 1); ?> KB
                                                        <?php endif; ?>
                                                        <?php if (!empty($mimeType)): ?>
                                                            • <?php echo htmlspecialchars($mimeType); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <?php
                                                    $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                                                    $fileUrl = $backendUrl . '/uploads/exercise_sheets/' . $training['attachment_filename'];
                                                    ?>
                                                    <a href="<?php echo htmlspecialchars($fileUrl); ?>" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="fas fa-download me-1"></i>Télécharger
                                                    </a>
                                                </div>
                                                
                                                <!-- Aperçu pour les images -->
                                                <?php if (strpos($mimeType, 'image') !== false || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo htmlspecialchars($fileUrl); ?>" 
                                                         class="img-fluid rounded" 
                                                         style="max-height: 150px; max-width: 100%;" 
                                                         alt="Aperçu de l'image">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations de la session avec boutons de navigation -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Informations de la session</h5>
                                <!-- Boutons de navigation entre sessions du même exercice -->
                                <?php if (count($sessions) > 1): ?>
                                <div class="d-flex align-items-center">
                                    <?php if ($previousSession): ?>
                                    <a href="/trainings/<?php echo $previousSession['id']; ?>" 
                                       class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-chevron-left"></i> Session précédente
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm me-2" disabled>
                                        <i class="fas fa-chevron-left"></i> Session précédente
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($nextSession): ?>
                                    <a href="/trainings/<?php echo $nextSession['id']; ?>" 
                                       class="btn btn-outline-secondary btn-sm">
                                        Session suivante <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                        Session suivante <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Indicateur de position pour les sessions -->
                            <?php if (count($sessions) > 1 && $currentSessionIndex >= 0): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Session <?php echo $currentSessionIndex + 1; ?> sur <?php echo count($sessions); ?> 
                                pour l'exercice "<?php echo htmlspecialchars($training['title'] ?? ''); ?>"
                            </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre de volées :</strong> <?php echo $training['total_ends'] ?? 0; ?></p>
                                    <p><strong>Total flèches :</strong> <?php echo $training['total_arrows'] ?? 0; ?></p>
                                    <p><strong>Durée :</strong> 
                                        <?php 
                                        $duration = $training['duration_minutes'] ?? 0;
                                        if ($duration > 0) {
                                            $hours = floor($duration / 60);
                                            $minutes = $duration % 60;
                                            if ($hours > 0) {
                                                echo $hours . 'h ' . $minutes . 'min';
                                            } else {
                                                echo $minutes . 'min';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Flèches par volée :</strong> 
                                        <?php 
                                        $totalArrows = $training['total_arrows'] ?? 0;
                                        $totalEnds = $training['total_ends'] ?? 0;
                                        if ($totalEnds > 0) {
                                            echo round($totalArrows / $totalEnds, 1);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                    <!-- SUPPRIMÉ : Champ Archer déplacé en haut -->
                                    <p><strong>Statut :</strong> 
                                        <?php if (!empty($training['end_date'])): ?>
                                        <span class="badge bg-success">Terminé</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">En cours</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($training['notes'])): ?>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Notes :</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="editNotesBtn">
                                        <i class="fas fa-edit"></i> Modifier
                                    </button>
                                </div>
                                <div id="notesDisplay">
                                    <p class="mt-1"><?php echo nl2br(htmlspecialchars($training['notes'])); ?></p>
                                </div>
                                <div id="notesEdit" style="display: none;">
                                    <textarea class="form-control" id="notesTextarea" rows="4"><?php echo htmlspecialchars($training['notes']); ?></textarea>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary" id="saveNotesBtn">
                                            <i class="fas fa-save"></i> Enregistrer
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="cancelNotesBtn">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Notes :</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="editNotesBtn">
                                        <i class="fas fa-plus"></i> Ajouter des notes
                                    </button>
                                </div>
                                <div id="notesDisplay">
                                    <p class="text-muted mt-1">Aucune note</p>
                                </div>
                                <div id="notesEdit" style="display: none;">
                                    <textarea class="form-control" id="notesTextarea" rows="4"></textarea>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary" id="saveNotesBtn">
                                            <i class="fas fa-save"></i> Enregistrer
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="cancelNotesBtn">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour modifier le statut de l'exercice -->
<?php if (($currentUser['role'] ?? '') === 'Coach' || ($currentUser['is_admin'] ?? false)): ?>
<div class="modal fade" id="editProgressionModal" tabindex="-1" aria-labelledby="editProgressionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProgressionModalLabel">Modifier le statut de l'exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/trainings/update-progression" method="POST">
                <div class="modal-body">
                    <!-- Champs cachés -->
                    <input type="hidden" name="exercise_sheet_id" value="<?php echo $training['exercise_sheet_id']; ?>">
                    <input type="hidden" name="session_id" value="<?php echo $training['id']; ?>">
                    
                    <!-- Sélection de l'utilisateur -->
                    <div class="mb-3">
                        <label for="userSelect" class="form-label">Archer</label>
                        <select class="form-select" id="userSelect" name="user_id" required>
                            <option value="">Sélectionner un archer...</option>
                            <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $training['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($user['name'] ?? '') . ' ' . ($user['first_name'] ?? '')); ?>
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="progressionSelect" class="form-label">Statut de l'exercice</label>
                        <select class="form-select" id="progressionSelect" name="progression" required>
                            <option value="non_actif" <?php echo ($progression === 'non_actif') ? 'selected' : ''; ?>>Non actif</option>
                            <option value="en_cours" <?php echo ($progression === 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                            <option value="acquis" <?php echo ($progression === 'acquis') ? 'selected' : ''; ?>>Acquis</option>
                            <option value="masqué" <?php echo ($progression === 'masqué') ? 'selected' : ''; ?>>Masqué</option>
                        </select>
                        <div class="form-text">
                            <strong>Masqué :</strong> L'exercice ne sera pas visible pour les archers.<br>
                            <strong>Non actif :</strong> L'exercice est visible mais pas encore commencé.<br>
                            <strong>En cours :</strong> L'exercice est en cours d'apprentissage.<br>
                            <strong>Acquis :</strong> L'exercice est maîtrisé.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour commencer une session d'entraînement -->
<div class="modal fade" id="trainingSessionModal" tabindex="-1" aria-labelledby="trainingSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="max-width: 375px; margin: 0 auto;">
            <div class="modal-header">
                <h5 class="modal-title" id="trainingSessionModalLabel">
                    <i class="fas fa-bullseye me-2"></i>Session d'entraînement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Informations de la session -->
                <div class="text-center mb-3">
                    <h6><?php echo htmlspecialchars($training['title'] ?? 'Exercice'); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($training['category'] ?? 'Catégorie'); ?></small>
                </div>
                
                <!-- Statistiques de la session -->
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0" id="sessionVolleys">0</div>
                            <small class="text-muted">Volées</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0" id="sessionArrows">0</div>
                            <small class="text-muted">Flèches</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <div class="h5 mb-0" id="sessionTime">00:00</div>
                            <small class="text-muted">Temps</small>
                        </div>
                    </div>
                </div>
                
                <!-- Saisie des flèches -->
                <div id="arrowInputSection">
                    <label for="arrowCount" class="form-label">Nombre de flèches dans cette volée :</label>
                    <div class="input-group mb-3">
                        <input type="number" class="form-control" id="arrowCount" min="1" max="20" value="6" placeholder="6">
                        <button class="btn btn-primary" type="button" id="addVolleyBtn">
                            <i class="fas fa-plus"></i> Ajouter volée
                        </button>
                    </div>
                </div>
                
                <!-- Liste des volées -->
                <div id="volleysList" class="mb-3" style="max-height: 200px; overflow-y: auto;">
                    <!-- Les volées seront ajoutées ici dynamiquement -->
                </div>
                
                <!-- Section de fin de session -->
                <div id="endSessionSection" style="display: none;">
                    <hr>
                    <div class="mb-3">
                        <label for="sessionNotes" class="form-label">Notes de la session (optionnel) :</label>
                        <textarea class="form-control" id="sessionNotes" rows="3" placeholder="Commentaires sur cette session..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelSessionBtn">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-danger" id="endSessionBtn" style="display: none;">
                    <i class="fas fa-stop"></i> Terminer la session
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript spécifique à la page des détails d'entraînement -->
<script src="/public/assets/js/trainings.js"></script>

<script>
// Gestion de l'édition des notes
document.addEventListener('DOMContentLoaded', function() {
    const editNotesBtn = document.getElementById('editNotesBtn');
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const cancelNotesBtn = document.getElementById('cancelNotesBtn');
    const notesDisplay = document.getElementById('notesDisplay');
    const notesEdit = document.getElementById('notesEdit');
    const notesTextarea = document.getElementById('notesTextarea');
    const sessionId = <?php echo $training['id']; ?>;
    
    if (editNotesBtn) {
        editNotesBtn.addEventListener('click', function() {
            notesDisplay.style.display = 'none';
            notesEdit.style.display = 'block';
            notesTextarea.focus();
        });
    }
    
    if (cancelNotesBtn) {
        cancelNotesBtn.addEventListener('click', function() {
            notesDisplay.style.display = 'block';
            notesEdit.style.display = 'none';
            // Restaurer le contenu original
            notesTextarea.value = notesTextarea.defaultValue;
        });
    }
    
    if (saveNotesBtn) {
        saveNotesBtn.addEventListener('click', function() {
            const notes = notesTextarea.value;
            
            // Afficher un indicateur de chargement
            saveNotesBtn.disabled = true;
            saveNotesBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Enregistrement...';
            
            // Envoyer la requête AJAX
            fetch('/trainings/update-notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    notes: notes
                })
            })
            .then(response => {
                // Nettoyer la réponse des caractères BOM
                return response.text().then(text => {
                    // Supprimer les caractères BOM et espaces invisibles
                    const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
                    
                    // Essayer de parser comme JSON
                    try {
                        return JSON.parse(cleanText);
                    } catch (e) {
                        console.error('Erreur de parsing JSON:', e);
                        console.error('Réponse reçue:', cleanText);
                        
                        // Si ce n'est pas du JSON valide, retourner une erreur
                        return { 
                            success: false, 
                            message: 'Réponse invalide du serveur: ' + cleanText.substring(0, 100) 
                        };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    if (notes.trim() === '') {
                        notesDisplay.innerHTML = '<p class="text-muted mt-1">Aucune note</p>';
                        editNotesBtn.innerHTML = '<i class="fas fa-plus"></i> Ajouter des notes';
                    } else {
                        notesDisplay.innerHTML = '<p class="mt-1">' + notes.replace(/\n/g, '<br>').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                        editNotesBtn.innerHTML = '<i class="fas fa-edit"></i> Modifier';
                    }
                    
                    // Revenir à l'affichage normal
                    notesDisplay.style.display = 'block';
                    notesEdit.style.display = 'none';
                    
                    // Afficher un message de succès
                    showAlert('success', 'Notes mises à jour avec succès');
                } else {
                    showAlert('danger', data.message || 'Erreur lors de la mise à jour des notes');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('danger', 'Erreur de connexion au serveur');
            })
            .finally(() => {
                saveNotesBtn.disabled = false;
                saveNotesBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
            });
        });
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container-fluid > .row > .col-12');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Faire défiler vers le haut pour voir l'alerte
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});

// Gestion de la session d'entraînement
document.addEventListener('DOMContentLoaded', function() {
    const startSessionBtn = document.getElementById('startSessionBtn');
    const trainingSessionModal = new bootstrap.Modal(document.getElementById('trainingSessionModal'));
    const addVolleyBtn = document.getElementById('addVolleyBtn');
    const endSessionBtn = document.getElementById('endSessionBtn');
    const cancelSessionBtn = document.getElementById('cancelSessionBtn');
    const arrowCountInput = document.getElementById('arrowCount');
    const volleysList = document.getElementById('volleysList');
    const sessionNotes = document.getElementById('sessionNotes');
    const endSessionSection = document.getElementById('endSessionSection');
    
    // Statistiques de la session
    let sessionData = {
        volleys: [],
        startTime: null,
        endTime: null,
        totalArrows: 0,
        totalVolleys: 0
    };
    
    // Éléments d'affichage des statistiques
    const sessionVolleysEl = document.getElementById('sessionVolleys');
    const sessionArrowsEl = document.getElementById('sessionArrows');
    const sessionTimeEl = document.getElementById('sessionTime');
    
    // Timer pour le temps de session
    let sessionTimer = null;
    
    // Démarrer une session
    if (startSessionBtn) {
        startSessionBtn.addEventListener('click', function() {
            // Réinitialiser les données
            sessionData = {
                volleys: [],
                startTime: new Date(),
                endTime: null,
                totalArrows: 0,
                totalVolleys: 0
            };
            
            // Mettre à jour l'affichage
            updateSessionStats();
            volleysList.innerHTML = '';
            sessionNotes.value = '';
            endSessionSection.style.display = 'none';
            endSessionBtn.style.display = 'none';
            
            // Démarrer le timer
            startSessionTimer();
            
            // Ouvrir la modale
            trainingSessionModal.show();
        });
    }
    
    // Ajouter une volée
    if (addVolleyBtn) {
        addVolleyBtn.addEventListener('click', function() {
            const arrowCount = parseInt(arrowCountInput.value);
            if (arrowCount > 0) {
                const volley = {
                    id: Date.now(),
                    arrows: arrowCount,
                    timestamp: new Date()
                };
                
                sessionData.volleys.push(volley);
                sessionData.totalArrows += arrowCount;
                sessionData.totalVolleys++;
                
                // Ajouter à la liste
                addVolleyToList(volley);
                
                // Mettre à jour les statistiques
                updateSessionStats();
                
                // Afficher le bouton de fin de session après la première volée
                if (sessionData.totalVolleys === 1) {
                    endSessionBtn.style.display = 'inline-block';
                }
                
                // Réinitialiser l'input
                arrowCountInput.value = '6';
            }
        });
    }
    
    // Terminer la session
    if (endSessionBtn) {
        endSessionBtn.addEventListener('click', function() {
            // Afficher la section de notes
            endSessionSection.style.display = 'block';
            
            // Changer le bouton
            endSessionBtn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder et terminer';
            endSessionBtn.onclick = saveSession;
        });
    }
    
    // Sauvegarder la session
    function saveSession() {
        sessionData.endTime = new Date();
        sessionData.notes = sessionNotes.value;
        
        console.log('Données à sauvegarder:', sessionData);
        
        // Envoyer les données au serveur
        fetch('/webapp/trainings/save-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                exercise_sheet_id: <?php echo $training['exercise_sheet_id']; ?>,
                session_data: sessionData
            })
        })
        .then(response => {
            console.log('Réponse reçue:', response);
            return response.text().then(text => {
                // Nettoyer les caractères BOM
                const cleanText = text.replace(/^[\s\x00-\x1F\x7F]*/, '').trim();
                try {
                    return JSON.parse(cleanText);
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    console.error('Réponse reçue:', cleanText);
                    return { success: false, message: 'Réponse invalide du serveur' };
                }
            });
        })
        .then(data => {
            console.log('Données reçues:', data);
            if (data.success) {
                // Afficher un message de succès simple
                alert('Session sauvegardée avec succès !');
                trainingSessionModal.hide();
                // Recharger la page pour voir les nouvelles données
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Erreur: ' + (data.message || 'Erreur lors de la sauvegarde'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de connexion au serveur');
        });
    }
    
    // Annuler la session
    if (cancelSessionBtn) {
        cancelSessionBtn.addEventListener('click', function() {
            if (sessionData.totalVolleys > 0) {
                if (confirm('Êtes-vous sûr de vouloir annuler cette session ? Les données seront perdues.')) {
                    trainingSessionModal.hide();
                }
            } else {
                trainingSessionModal.hide();
            }
        });
    }
    
    // Ajouter une volée à la liste
    function addVolleyToList(volley) {
        const volleyDiv = document.createElement('div');
        volleyDiv.className = 'd-flex justify-content-between align-items-center border rounded p-2 mb-2';
        volleyDiv.innerHTML = `
            <div>
                <strong>Volée ${sessionData.totalVolleys}</strong>
                <small class="text-muted ms-2">${volley.arrows} flèche${volley.arrows > 1 ? 's' : ''}</small>
            </div>
            <div>
                <small class="text-muted">${volley.timestamp.toLocaleTimeString()}</small>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeVolley(${volley.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        volleyDiv.id = `volley-${volley.id}`;
        volleysList.appendChild(volleyDiv);
    }
    
    // Supprimer une volée
    window.removeVolley = function(volleyId) {
        const volleyIndex = sessionData.volleys.findIndex(v => v.id === volleyId);
        if (volleyIndex !== -1) {
            const volley = sessionData.volleys[volleyIndex];
            sessionData.totalArrows -= volley.arrows;
            sessionData.totalVolleys--;
            sessionData.volleys.splice(volleyIndex, 1);
            
            // Supprimer de l'affichage
            const volleyEl = document.getElementById(`volley-${volleyId}`);
            if (volleyEl) {
                volleyEl.remove();
            }
            
            // Mettre à jour les statistiques
            updateSessionStats();
            
            // Masquer le bouton de fin si plus de volées
            if (sessionData.totalVolleys === 0) {
                endSessionBtn.style.display = 'none';
            }
        }
    };
    
    // Mettre à jour les statistiques
    function updateSessionStats() {
        sessionVolleysEl.textContent = sessionData.totalVolleys;
        sessionArrowsEl.textContent = sessionData.totalArrows;
    }
    
    // Démarrer le timer
    function startSessionTimer() {
        sessionTimer = setInterval(() => {
            if (sessionData.startTime) {
                const now = new Date();
                const diff = now - sessionData.startTime;
                const minutes = Math.floor(diff / 60000);
                const seconds = Math.floor((diff % 60000) / 1000);
                sessionTimeEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
    
    // Arrêter le timer
    function stopSessionTimer() {
        if (sessionTimer) {
            clearInterval(sessionTimer);
            sessionTimer = null;
        }
    }
    
    // Arrêter le timer quand la modale se ferme
    document.getElementById('trainingSessionModal').addEventListener('hidden.bs.modal', function() {
        stopSessionTimer();
    });
});
</script>
