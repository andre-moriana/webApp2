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
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $stats['total_trainings']; ?></h4>
                                    <p class="card-text">Entraînements</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dumbbell fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $stats['total_arrows']; ?></h4>
                                    <p class="card-text">Flèches tirées</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo number_format($stats['average_score'], 1); ?></h4>
                                    <p class="card-text">Moyenne par flèche</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?php echo $stats['best_training_score']; ?></h4>
                                    <p class="card-text">Meilleur score</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-trophy fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des entraînements -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historique des entraînements</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($trainings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun entraînement trouvé</h5>
                        <p class="text-muted">Commencez votre premier entraînement !</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Titre</th>
                                    <th>Type de tir</th>
                                    <th>Volées</th>
                                    <th>Flèches</th>
                                    <th>Score total</th>
                                    <th>Moyenne</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainings as $training): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($training['start_date'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($training['title']); ?></strong>
                                        <?php if ($training['exercise_title']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($training['exercise_title']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($training['shooting_type']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($training['shooting_type']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $training['total_ends']; ?></td>
                                    <td><?php echo $training['total_arrows']; ?></td>
                                    <td>
                                        <strong><?php echo $training['total_score']; ?></strong>
                                    </td>
                                    <td>
                                        <?php echo number_format($training['average_score'], 1); ?>
                                    </td>
                                    <td>
                                        <?php if ($training['status'] === 'en_cours'): ?>
                                        <span class="badge bg-warning">En cours</span>
                                        <?php elseif ($training['status'] === 'terminé'): ?>
                                        <span class="badge bg-success">Terminé</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($training['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/trainings/<?php echo $training['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Voir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
