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
                                <a href="/trainings/<?php echo $previousExercise['session_id'] ?? ''; ?><?php echo ($isAdmin || $isCoach || $isDirigeant) && isset($selectedUserId) ? '?user_id=' . $selectedUserId : ''; ?>" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-chevron-left"></i> Exercice précédent
                                </a>
                                <?php else: ?>
                                <button class="btn btn-outline-primary btn-sm me-2" disabled>
                                    <i class="fas fa-chevron-left"></i> Exercice précédent
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($nextExercise): ?>
                                <a href="/trainings/<?php echo $nextExercise['session_id'] ?? ''; ?><?php echo ($isAdmin || $isCoach) && isset($selectedUserId) ? '?user_id=' . $selectedUserId : ''; ?>" 
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
                            
                            <!-- Bouton de suppression pour admin/coach -->
                            <?php if ($isAdmin || $isCoach): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm me-2" id="deleteSessionBtn" data-session-id="<?php echo $training['id']; ?>">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
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
                    
            <!-- Informations de la session avec boutons de navigation -->
            <div class="card" data-session-id="<?php echo $training['id']; ?>">
                <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Informations de la session</h5>
                                <!-- Boutons de navigation entre sessions du même exercice -->
                                <?php if (count($sessions) > 1): ?>
                                <div class="d-flex align-items-center">
                                    <?php if ($previousSession): ?>
                                    <a href="/trainings/<?php echo $previousSession['id']; ?><?php echo ($isAdmin || $isCoach) && isset($selectedUserId) ? '?user_id=' . $selectedUserId : ''; ?>" 
                                       class="btn btn-outline-secondary btn-sm me-2">
                                        <i class="fas fa-chevron-left"></i> Session précédente
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm me-2" disabled>
                                        <i class="fas fa-chevron-left"></i> Session précédente
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($nextSession): ?>
                                    <a href="/trainings/<?php echo $nextSession['id']; ?><?php echo ($isAdmin || $isCoach) && isset($selectedUserId) ? '?user_id=' . $selectedUserId : ''; ?>" 
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
<?php if (($currentUser['role'] ?? '') === 'Coach' || ($currentUser['role'] ?? '') === 'Dirigeant' || ($currentUser['is_admin'] ?? false)): ?>
<div class="modal fade" id="editProgressionModal" tabindex="-1" aria-labelledby="editProgressionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProgressionModalLabel">Modifier le statut de l'exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm">
                <input type="hidden" id="statusExerciseId" name="exercise_id">
                <input type="hidden" id="statusUserId" name="user_id" value="<?php echo $selectedUserId ?? $actualUserId; ?>">
                
                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Statut de l'exercice :</label>
                    <select class="form-select" id="statusSelect" name="status" required>
                        <option value="">-- Sélectionner un statut --</option>
                        <option value="non_actif">Non actif</option>
                        <option value="en_cours">En cours</option>
                        <option value="acquis">Acquis</option>
                        <option value="masqué">Masqué</option>
                    </select>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveExerciseStatus()">Sauvegarder</button>
            </div>
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

