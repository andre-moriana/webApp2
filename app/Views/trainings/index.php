<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Entraînements</h1>
                <?php if ($isAdmin || $isCoach): ?>
                <div class="d-flex align-items-center">
                    <label for="userSelect" class="form-label me-2 mb-0">Sélectionner un archer :</label>
                    <select id="userSelect" class="form-select" style="width: auto;">
                        <option value="">-- Choisir un archer --</option>
                        <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                            <?php 
                            $displayName = $user['name'];
                            if (!empty($user['firstName'])) {
                                $displayName .= ' ' . $user['firstName'];
                            }
                            echo htmlspecialchars($displayName);
                            ?>
                            <?php if (!empty($user['role'])): ?>
                                (<?php echo htmlspecialchars($user['role']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

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

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $stats['total_trainings'] ?? 0; ?></h4>
                                    <p class="card-text">Entraînements</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dumbbell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-success text-white stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $stats['total_arrows'] ?? 0; ?></h4>
                                    <p class="card-text">Flèches</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-info text-white stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php 
                                    $totalMinutes = $stats['total_time_minutes'] ?? 0;
                                    $hours = floor($totalMinutes / 60);
                                    $minutes = $totalMinutes % 60;
                                    if ($hours > 0) {
                                        $timeDisplay = $hours . 'h ' . $minutes . 'min';
                                    } else {
                                        $timeDisplay = $minutes . 'min';
                                    }
                                    ?>
                                    <h4 class="card-title"><?php echo $timeDisplay; ?></h4>
                                    <p class="card-text">Temps global</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations de l'utilisateur sélectionné -->
            <?php if (isset($selectedUser) && !empty($selectedUser)): ?>
            <div class="card mb-4 user-info-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <?php if (!empty($selectedUser['profile_image'])): ?>
                                <img src="<?php echo $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000'; ?><?php echo htmlspecialchars($selectedUser['profile_image']); ?>" 
                                     class="rounded-circle user-avatar" 
                                     alt="Photo de profil"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php else : ?>
                                <div class="rounded-circle bg-primary user-avatar-placeholder">
                                    <i class="fas fa-user text-white fa-2x"></i>
                                </div>
                            <?php endif; ?> 
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-1">
                                <?php 
                                $displayName = $selectedUser['name'] ?? 'Utilisateur';
                                if (!empty($selectedUser['firstName'])) {
                                    $displayName .= ' ' . $selectedUser['firstName'];
                                }
                                echo htmlspecialchars($displayName);
                                ?>
                            </h4>
                            <p class="text-muted mb-0">
                                <?php if (!empty($selectedUser['role'])): ?>
                                    <?php echo htmlspecialchars($selectedUser['role']); ?>
                                <?php else: ?>
                                    Archer
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Liste des entraînements groupés par catégorie -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Entraînements par catégorie d'exercice</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trainingsByCategory)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun entraînement trouvé</h5>
                        <p class="text-muted">Commencez votre premier entraînement !</p>
                    </div>
                    <?php else: ?>
                    <div class="accordion" id="categoriesAccordion">
                        <?php foreach ($trainingsByCategory as $categoryName => $categoryData): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo md5($categoryName); ?>">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo md5($categoryName); ?>" 
                                        aria-expanded="false" 
                                        aria-controls="collapse<?php echo md5($categoryName); ?>">
                                    <div class="d-flex justify-content-between w-100 me-3">
                                        <div>
                                            <strong><?php echo htmlspecialchars($categoryName); ?></strong>
                                        </div>
                                        <div class="text-muted">
                                            <small>
                                                <?php echo $categoryData['total_sessions']; ?> séances - 
                                                <?php echo $categoryData['total_arrows']; ?> flèches - 
                                                <?php 
                                                $hours = floor($categoryData['total_time_minutes'] / 60);
                                                $minutes = $categoryData['total_time_minutes'] % 60;
                                                if ($hours > 0) {
                                                    echo $hours . 'h ' . $minutes . 'min';
                                                } else {
                                                    echo $minutes . 'min';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo md5($categoryName); ?>" 
                                 class="accordion-collapse collapse" 
                                 aria-labelledby="heading<?php echo md5($categoryName); ?>" 
                                 data-bs-parent="#categoriesAccordion">
                                <div class="accordion-body">
                                    <?php foreach ($categoryData['exercises'] as $exerciseId => $exerciseData): ?>
                                    <div class="card mb-3 exercise-card">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-dumbbell me-2"></i>
                                                    <?php echo htmlspecialchars($exerciseData['exercise_title']); ?>
                                                </h6>
                                                <button type="button" class="btn btn-success btn-sm start-session-btn" 
                                                        onclick="startTrainingSession(<?php echo $exerciseId; ?>, '<?php echo htmlspecialchars($exerciseData['exercise_title'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-play me-1"></i>Commencer une session
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- Statistiques de l'exercice -->
                                            <div class="row text-center mb-3">
                                                <div class="col-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-primary mb-0"><?php echo $exerciseData['stats']['total_sessions']; ?></h5>
                                                        <small class="text-muted">Séances</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-success mb-0"><?php echo $exerciseData['stats']['total_arrows']; ?></h5>
                                                        <small class="text-muted">Flèches</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-info mb-0">
                                                            <?php 
                                                            $hours = floor($exerciseData['stats']['total_time_minutes'] / 60);
                                                            $minutes = $exerciseData['stats']['total_time_minutes'] % 60;
                                                            if ($hours > 0) {
                                                                echo $hours . 'h ' . $minutes . 'min';
                                                            } else {
                                                                echo $minutes . 'min';
                                                            }
                                                            ?>
                                                        </h5>
                                                        <small class="text-muted">Temps</small>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <h5 class="text-warning mb-0">
                                                        <?php if ($exerciseData['stats']['last_session']): ?>
                                                            <?php echo date('d/m/Y', strtotime($exerciseData['stats']['last_session'])); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </h5>
                                                    <small class="text-muted">Dernière</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Liste des séances -->
                                            <div class="accordion" id="sessionsAccordion<?php echo $exerciseId; ?>">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="sessionsHeading<?php echo $exerciseId; ?>">
                                                        <button class="accordion-button collapsed" type="button" 
                                                                data-bs-toggle="collapse" 
                                                                data-bs-target="#sessionsCollapse<?php echo $exerciseId; ?>" 
                                                                aria-expanded="false" 
                                                                aria-controls="sessionsCollapse<?php echo $exerciseId; ?>">
                                                            <small>
                                                                <?php 
                                                                $sessionCount = count($exerciseData['sessions']);
                                                                if ($sessionCount === 1 && isset($exerciseData['sessions'][0]['is_progress_data'])) {
                                                                    echo "Données de progrès (" . $exerciseData['stats']['total_sessions'] . " séances)";
                                                                } else {
                                                                    echo "Voir les " . $sessionCount . " séances";
                                                                }
                                                                ?>
                                                            </small>
                                                        </button>
                                                    </h2>
                                                    <div id="sessionsCollapse<?php echo $exerciseId; ?>" 
                                                         class="accordion-collapse collapse" 
                                                         aria-labelledby="sessionsHeading<?php echo $exerciseId; ?>" 
                                                         data-bs-parent="#sessionsAccordion<?php echo $exerciseId; ?>">
                                                        <div class="accordion-body p-2">
                                                            <?php if (empty($exerciseData['sessions'])): ?>
                                                            <div class="text-center py-2">
                                                                <small class="text-muted">Aucune séance trouvée</small>
                                                            </div>
                                                            <?php else: ?>
                                                            <?php foreach ($exerciseData['sessions'] as $session): ?>
                                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                                <div>
                                                                    <small class="text-muted">
                                                                        <?php 
                                                                        if (isset($session['is_progress_data']) && $session['is_progress_data']): ?>
                                                                            <strong>Données de progrès</strong>
                                                                        <?php else:
                                                                            $date = $session['start_date'] ?? $session['created_at'] ?? '';
                                                                            if ($date && $date !== 'Date inconnue' && $date !== '0000-00-00 00:00:00') {
                                                                                echo date('d/m/Y H:i', strtotime($date));
                                                                            } else {
                                                                                echo 'Date inconnue';
                                                                            }
                                                                        endif;
                                                                        ?>
                                                                    </small>
                                                                    <br>
                                                                    <small>
                                                                        <?php if (isset($session['is_progress_data']) && $session['is_progress_data']): ?>
                                                                            <?php echo $session['total_arrows']; ?> flèches total - 
                                                                            <?php echo $session['total_sessions']; ?> séances
                                                                        <?php else: ?>
                                                                            <?php 
                                                                            $arrows = $session['arrows_shot'] ?? $session['total_arrows'] ?? $session['arrows'] ?? 0;
                                                                            echo $arrows; 
                                                                            ?> flèches
                                                                            <?php if (isset($session['score']) && $session['score'] > 0): ?>
                                                                            - Score: <?php echo $session['score']; ?>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                        <?php if (isset($session['is_aggregated']) && $session['is_aggregated']): ?>
                                                                        <span class="badge bg-info">Données agrégées</span>
                                                                        <?php endif; ?>
                                                                    </small>
                                                                    <?php if (isset($session['notes'])): ?>
                                                                    <br><small class="text-info"><?php echo htmlspecialchars($session['notes']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div>
                                                                    <?php if (isset($session['is_progress_data']) && $session['is_progress_data']): ?>
                                                                    <span class="text-muted">
                                                                        <small>Données de progrès</small>
                                                                    </span>
                                                                    <?php else: ?>
                                                                    <a href="/trainings/<?php echo $session['id'] ?? ''; ?><?php echo ($isAdmin || $isCoach) && isset($selectedUserId) && $selectedUserId != $actualUserId ? '?user_id=' . $selectedUserId : ''; ?>" 
                                                                       class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-eye"></i> Voir
                                                                    </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour commencer une session d'entraînement -->
<div class="modal fade" id="trainingSessionModal" tabindex="-1" aria-labelledby="trainingSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content session-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="trainingSessionModalLabel">
                    <i class="fas fa-bullseye me-2"></i>Session d'entraînement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Informations de la session -->
                <div class="text-center mb-3">
                    <h6 id="sessionExerciseTitle">Exercice</h6>
                </div>
                
                <!-- Statistiques de la session -->
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="session-stats">
                            <div class="h5 mb-0" id="sessionVolleys">0</div>
                            <small class="text-muted">Volées</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="session-stats">
                            <div class="h5 mb-0" id="sessionArrows">0</div>
                            <small class="text-muted">Flèches</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="session-stats">
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
                <div id="volleysList" class="volleys-list mb-3">
                    <!-- Les volées seront ajoutées ici dynamiquement -->
                </div>
                
                <!-- Section de fin de session -->
                <div id="endSessionSection" class="end-session-section" style="display: none;">
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

<!-- CSS personnalisé -->
<link href="/public/assets/css/trainings.css" rel="stylesheet">

<!-- JavaScript personnalisé -->
<script>
// Passer les variables PHP au JavaScript
window.selectedUserId = <?php echo isset($selectedUserId) ? $selectedUserId : 'null'; ?>;
</script>
<script src="/public/assets/js/trainings.js"></script>
