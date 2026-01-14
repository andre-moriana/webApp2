<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <h1 class="h3 mb-0">Entraînements</h1>
                <?php if ($isAdmin || $isCoach || $isDirigeant): ?>
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 w-100 w-md-auto">
                    <label for="userSelect" class="form-label mb-0 text-nowrap">Sélectionner un archer :</label>
                    <select id="userSelect" class="form-select" style="min-width: 200px; width: 100%; max-width: 100%;" onchange="handleUserSelectChange(this)">
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

            <!-- Frise chronologique horizontale -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Frise chronologique</h5>
                </div>
                <div class="card-body position-relative">
                    <!-- Bouton flèche gauche -->
                    <button id="timeline-arrow-left" class="timeline-arrow timeline-arrow-left" onclick="scrollTimeline('left')" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <!-- Bouton flèche droite -->
                    <button id="timeline-arrow-right" class="timeline-arrow timeline-arrow-right" onclick="scrollTimeline('right')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div id="timeline-container" class="timeline-container">
                        <div id="timeline-scroll" class="timeline-scroll">
                            <div id="timeline-content" class="timeline-content">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                    <p>Chargement de la frise chronologique...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center w-100 me-3 gap-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($categoryName); ?></strong>
                                        </div>
                                        <div class="text-muted">
                                            <small>
                                                <span class="d-inline d-md-none">
                                                    <?php echo $categoryData['total_sessions']; ?> séances<br>
                                                    <?php echo $categoryData['total_arrows']; ?> flèches<br>
                                                    <?php 
                                                    $hours = floor($categoryData['total_time_minutes'] / 60);
                                                    $minutes = $categoryData['total_time_minutes'] % 60;
                                                    if ($hours > 0) {
                                                        echo $hours . 'h ' . $minutes . 'min';
                                                    } else {
                                                        echo $minutes . 'min';
                                                    }
                                                    ?>
                                                </span>
                                                <span class="d-none d-md-inline">
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
                                                </span>
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
                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-dumbbell me-2"></i>
                                                    <?php echo htmlspecialchars($exerciseData['exercise_title']); ?>
                                                </h6>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- Statistiques de l'exercice -->
                                            <div class="row text-center mb-3 g-2">
                                                <div class="col-6 col-md-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-primary mb-0"><?php echo $exerciseData['stats']['total_sessions']; ?></h5>
                                                        <small class="text-muted">Séances</small>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-success mb-0"><?php echo $exerciseData['stats']['total_arrows']; ?></h5>
                                                        <small class="text-muted">Flèches</small>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
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
                                                <div class="col-6 col-md-3">
                                                    <div class="exercise-stats">
                                                        <h5 class="text-warning mb-0">
                                                            <?php if ($exerciseData['stats']['last_session']): ?>
                                                                <span class="d-md-none"><?php echo date('d/m/y', strtotime($exerciseData['stats']['last_session'])); ?></span>
                                                                <span class="d-none d-md-inline"><?php echo date('d/m/Y', strtotime($exerciseData['stats']['last_session'])); ?></span>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </h5>
                                                        <small class="text-muted">Dernière</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Liste des séances -->

                                            <!-- Informations détaillées de l'exercice -->
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="card">
                                                    <div class="card-header">
                                                        <h5 class="card-title mb-0">Exercice</h5>
                                                    </div>
                                                    <div class="card-body">
                                                                <div class="row g-3">
                                                                <div class="col-12 col-md-8">
                                                                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($exerciseData['exercise_title'] ?? 'Non défini'); ?></p>
                                                                    <p><strong>Catégorie :</strong> 
                                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($categoryName); ?></span>
                                                                    </p>
                                                                    
                                                                    <!-- Statut de l'exercice -->
                                                                    <p><strong>Statut :</strong> 
                                                                        <?php
                                                                        $progression = $exerciseData['progression'] ?? 'non_actif';
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
                                                                    </p>
                                                                    <?php if ($isAdmin || $isCoach || $isDirigeant): ?>
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                                data-exercise-id="<?php echo $exerciseId ?? $exerciseData['exercise_id'] ?? ''; ?>"
                                                                                data-exercise-title="<?php echo htmlspecialchars($exerciseData['exercise_title'], ENT_QUOTES); ?>"
                                                                                onclick="updateExerciseStatusFromButton(this)"
                                                                                title="Modifier le statut">
                                                                            <i class="fas fa-edit me-1"></i>Statut
                                                                        </button>
                                                                    <?php endif; ?>
 
                                                                    <!-- Description de l'exercice -->
                                                                    <?php if (!empty($exerciseData['description'])): ?>
                                                                    <div class="mt-3">
                                                                        <strong>Description :</strong>
                                                                        <p class="mt-1"><?php echo nl2br(htmlspecialchars($exerciseData['description'])); ?></p>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-12 col-md-4">
                                                                    <!-- Aperçu de la pièce jointe -->
                                                                    <?php if (!empty($exerciseData['attachment_filename'])): ?>
                                                                    <div class="text-center">
                                                                        <strong>Pièce jointe :</strong>
                                                                        <div class="mt-2">
                                                                            <div class="d-flex flex-column align-items-center">
                                                                                <div class="mb-2">
                                                                                    <?php
                                                                                    $mimeType = $exerciseData['attachment_mime_type'] ?? '';
                                                                                    $fileExtension = strtolower(pathinfo($exerciseData['attachment_filename'], PATHINFO_EXTENSION));
                                                        
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
                                                                                        <strong><?php echo htmlspecialchars($exerciseData['attachment_original_name'] ?? $exerciseData['attachment_filename']); ?></strong>
                                                                                    </div>
                                                                                    <div class="text-muted small">
                                                                                        <?php if (!empty($exerciseData['attachment_size'])): ?>
                                                                                            <?php echo number_format($exerciseData['attachment_size'] / 1024, 1); ?> KB
                                                                                        <?php endif; ?>
                                                                                        <?php if (!empty($mimeType)): ?>
                                                                                            • <?php echo htmlspecialchars($mimeType); ?>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="mt-2">
                                                                                    <?php
                                                                                    $backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
                                                                                    $fileUrl = $backendUrl . '/uploads/exercise_sheets/' . $exerciseData['attachment_filename'];
                                                                                    ?>
                                                                                    <?php if (strpos($mimeType, 'pdf') !== false || $fileExtension === 'pdf'): ?>
                                                                                        <button class="btn btn-outline-info btn-sm me-2" 
                                                                                                onclick="showPdfPreview('<?php echo htmlspecialchars($fileUrl); ?>', '<?php echo htmlspecialchars($exerciseData['attachment_original_name'] ?? $exerciseData['attachment_filename']); ?>')"
                                                                                                title="Aperçu du PDF">
                                                                                            <i class="fas fa-eye me-1"></i>Aperçu
                                                                                        </button>
                                                                                    <?php endif; ?>
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
                                                                    <?php else: ?>
                                                                    <div class="text-center text-muted">
                                                                        <i class="fas fa-file fa-2x mb-2"></i>
                                                                        <p class="small mb-0">Aucun fichier joint</p>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php 
                                            // Vérifier s'il y a des séances réelles à afficher
                                            $hasRealSessions = false;
                                            $realSessionCount = 0;
                                            
                                            if (!empty($exerciseData['sessions'])) {
                                                // Compter uniquement les vraies séances (pas les données de progrès)
                                                foreach ($exerciseData['sessions'] as $session) {
                                                    // Ignorer les séances de type "données de progrès"
                                                    if (isset($session['is_progress_data']) && $session['is_progress_data']) {
                                                        continue;
                                                    }
                                                    
                                                    // Vérifier que la séance a un ID valide (une vraie séance enregistrée)
                                                    $sessionId = $session['id'] ?? $session['_id'] ?? null;
                                                    if (!empty($sessionId) && $sessionId !== 'unknown' && $sessionId !== 'null') {
                                                        $realSessionCount++;
                                                    }
                                                }
                                                
                                                // Il y a des séances réelles si le compteur est > 0
                                                $hasRealSessions = $realSessionCount > 0;
                                            }
                                            ?>
                                            <?php if ($hasRealSessions): ?>
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
                                                                // Afficher le texte avec le bon singulier/pluriel
                                                                if ($realSessionCount === 1) {
                                                                    echo "Voir la séance";
                                                                } else {
                                                                    echo "Voir les " . $realSessionCount . " séances";
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
                                                            <?php 
                                                            // Afficher uniquement les vraies séances (pas les données de progrès)
                                                            foreach ($exerciseData['sessions'] as $session): 
                                                                // Ignorer les séances de type "données de progrès"
                                                                if (isset($session['is_progress_data']) && $session['is_progress_data']) {
                                                                    continue;
                                                                }
                                                                
                                                                // Vérifier que la séance a un ID valide avant de l'afficher
                                                                $sessionId = $session['id'] ?? $session['_id'] ?? null;
                                                                if (empty($sessionId) || $sessionId === 'unknown' || $sessionId === 'null') {
                                                                    continue;
                                                                }
                                                            ?>
                                                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                                <div>
                                                                    <small class="text-muted">
                                                                        <?php 
                                                                        $date = $session['start_date'] ?? $session['created_at'] ?? '';
                                                                        if ($date && $date !== 'Date inconnue' && $date !== '0000-00-00 00:00:00') {
                                                                            echo date('d/m/Y H:i', strtotime($date));
                                                                        } else {
                                                                            echo 'Date inconnue';
                                                                        }
                                                                        ?>
                                                                    </small>
                                                                    <br>
                                                                    <small>
                                                                        <?php 
                                                                        $arrows = $session['arrows_shot'] ?? $session['total_arrows'] ?? $session['arrows'] ?? 0;
                                                                        echo $arrows; 
                                                                        ?> flèches
                                                                        <?php if (isset($session['score']) && $session['score'] > 0): ?>
                                                                        - Score: <?php echo $session['score']; ?>
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
                                                                    <a href="/trainings/<?php echo $sessionId; ?><?php echo ($isAdmin || $isCoach || $isDirigeant) && isset($selectedUserId) && $selectedUserId != $actualUserId ? '?user_id=' . $selectedUserId : ''; ?>" 
                                                                       class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-eye"></i> Voir
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
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

<!-- Modal pour modifier le statut d'un exercice -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Modifier le statut de l'exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
</div>

<!-- CSS personnalisé -->
<link href="/public/assets/css/trainings.css" rel="stylesheet">

<!-- Variables JavaScript pour la frise chronologique -->
<script>
window.currentUserId = <?php echo isset($selectedUserId) ? $selectedUserId : (isset($actualUserId) ? $actualUserId : 'null'); ?>;
window.selectedUserId = <?php echo isset($selectedUserId) ? $selectedUserId : 'null'; ?>;

// Fonction globale pour gérer le changement d'utilisateur - recharger la page
function handleUserSelectChange(selectElement) {
    const selectedUserId = selectElement.value;
    let newUrl = '/trainings';
    if (selectedUserId && selectedUserId !== '' && selectedUserId !== 'null' && selectedUserId !== 'undefined') {
        newUrl += '?user_id=' + encodeURIComponent(selectedUserId);
    }
    window.location.href = newUrl;
}
</script>

<!-- JavaScript personnalisé -->
<script src="/public/assets/js/trainings.js"></script>

<!-- Inclure la modale d'aperçu PDF -->
<?php include 'app/Views/components/pdf-preview-modal.php'; ?>


