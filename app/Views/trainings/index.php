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
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>
                            <?php if ($user['role']): ?>
                                (<?php echo htmlspecialchars($user['role']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
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
                    <div class="card bg-primary text-white">
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
                    <div class="card bg-success text-white">
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
                    <div class="card bg-info text-white">
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

            <!-- Liste des entraînements groupés par exercice -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Entraînements par exercice</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trainingsByExercise)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun entraînement trouvé</h5>
                        <p class="text-muted">Commencez votre premier entraînement !</p>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($trainingsByExercise as $exerciseId => $exerciseData): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-dumbbell me-2"></i>
                                        <?php echo htmlspecialchars($exerciseData['exercise_title']); ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Statistiques principales -->
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="border-end">
                                                <h4 class="text-primary mb-0"><?php echo $exerciseData['stats']['total_sessions']; ?></h4>
                                                <small class="text-muted">Séances</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border-end">
                                                <h4 class="text-success mb-0"><?php echo $exerciseData['stats']['total_arrows']; ?></h4>
                                                <small class="text-muted">Flèches</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <h4 class="text-info mb-0"><?php echo gmdate('H:i', $exerciseData['stats']['total_time_minutes'] * 60); ?></h4>
                                            <small class="text-muted">Temps</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Statistiques secondaires -->
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Séances :</small><br>
                                            <strong><?php echo $exerciseData['stats']['total_sessions']; ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Durée :</small><br>
                                            <strong><?php echo gmdate('H:i', $exerciseData['stats']['total_time_minutes'] * 60); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <!-- Période -->
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php if ($exerciseData['stats']['first_training'] && $exerciseData['stats']['last_training']): ?>
                                                Du <?php echo date('d/m/Y', strtotime($exerciseData['stats']['first_training'])); ?>
                                                au <?php echo date('d/m/Y', strtotime($exerciseData['stats']['last_training'])); ?>
                                            <?php elseif ($exerciseData['stats']['first_training']): ?>
                                                Depuis le <?php echo date('d/m/Y', strtotime($exerciseData['stats']['first_training'])); ?>
                                            <?php else: ?>
                                                Aucune session
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Détail des entraînements -->
                                    <div class="accordion" id="accordion<?php echo $exerciseId; ?>">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $exerciseId; ?>">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $exerciseId; ?>" 
                                                        aria-expanded="false" 
                                                        aria-controls="collapse<?php echo $exerciseId; ?>">
                                                    <small>Détail des <?php echo count($exerciseData['trainings']); ?> séances</small>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $exerciseId; ?>" 
                                                 class="accordion-collapse collapse" 
                                                 aria-labelledby="heading<?php echo $exerciseId; ?>" 
                                                 data-bs-parent="#accordion<?php echo $exerciseId; ?>">
                                                <div class="accordion-body p-2">
                                                    <?php foreach ($exerciseData['trainings'] as $training): ?>
                                                    <?php if (is_array($training)): ?>
                                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                        <div>
                                                            <small class="text-muted">
                                                                <?php 
                                                                $date = $training['start_date'] ?? $training['created_at'] ?? '';
                                                                if ($date) {
                                                                    echo date('d/m/Y H:i', strtotime($date));
                                                                } else {
                                                                    echo 'Date inconnue';
                                                                }
                                                                ?>
                                                            </small>
                                                            <br>
                                                            <small>
                                                                <?php echo $training['total_arrows'] ?? 0; ?> flèches - 
                                                                <?php echo $training['total_sessions'] ?? 0; ?> séances
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <a href="/trainings/<?php echo $training['id'] ?? ''; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('userSelect');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const userId = this.value;
            if (userId) {
                window.location.href = '/trainings?user_id=' + userId;
            } else {
                window.location.href = '/trainings';
            }
        });
    }
});
</script>
