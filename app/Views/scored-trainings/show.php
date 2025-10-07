<?php
// Variables disponibles depuis le contrôleur :
// $scoredTraining, $selectedUser, $isAdmin, $isCoach

// Inclure les fichiers CSS et JS spécifiques
$additionalCSS = [
    '/public/assets/css/scored-trainings.css',
    '/public/assets/css/scored-training-show.css'
];
$additionalJS = [
    '/public/assets/js/scored-training-show.js?v=' . time()
];
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Données du tir compté pour JavaScript -->
<script>
    window.scoredTrainingData = {
        id: <?= $scoredTraining['id'] ?>,
        title: '<?= addslashes($scoredTraining['title']) ?>',
        shooting_type: '<?= addslashes($scoredTraining['shooting_type'] ?? '') ?>',
        total_ends: <?= $scoredTraining['total_ends'] ?>,
        arrows_per_end: <?= $scoredTraining['arrows_per_end'] ?>,
        total_arrows: <?= $scoredTraining['total_arrows'] ?>,
        status: '<?= addslashes($scoredTraining['status']) ?>'
    };
    
    // Données des volées pour le graphique
    window.endsData = <?= json_encode($scoredTraining['ends'] ?? []) ?>;
    
    // Token d'authentification pour l'API
    window.token = '<?= $_SESSION['token'] ?? '' ?>';
    
    // Test de la fonction saveEnd
    setTimeout(() => {
        console.log('🔍 Test de la fonction saveEnd:');
        console.log('📊 typeof saveEnd:', typeof saveEnd);
        console.log('📊 typeof window.saveEnd:', typeof window.saveEnd);
        
        if (typeof saveEnd === 'function') {
            console.log('✅ Fonction saveEnd disponible');
        } else {
            console.error('❌ Fonction saveEnd non disponible');
        }
    }, 1000);
    
</script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?= htmlspecialchars($scoredTraining['title']) ?></h1>
                    <p class="text-muted mb-0">
                        <?= $scoredTraining['exercise_sheet_id'] ? 'Exercice associé' : 'Tir libre' ?>
                        <?php if ($scoredTraining['shooting_type']): ?>
                        • <?= htmlspecialchars($scoredTraining['shooting_type']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/scored-trainings" class="btn btn-outline-secondary nav-button">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <?php if ($scoredTraining['status'] === 'en_cours'): ?>
                    <button class="btn btn-success btn-action" onclick="addEnd()">
                        <i class="fas fa-plus"></i> Ajouter une volée
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-action" onclick="deleteTraining()">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>

            <!-- Informations générales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary total-score"><?= $scoredTraining['total_score'] ?? 0 ?></h5>
                            <p class="card-text">Score total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success average-score"><?= $scoredTraining['average_score'] ?? 0 ?></h5>
                            <p class="card-text">Score moyen</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info"><?= $scoredTraining['total_arrows'] ?? 0 ?></h5>
                            <p class="card-text">Flèches tirées</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h5 class="card-title text-warning"><?= $scoredTraining['total_ends'] ?? 0 ?></h5>
                            <p class="card-text">Volées</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails du tir compté -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card detail-card">
                        <div class="card-header">
                            <h5 class="mb-0">Détails des volées</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($scoredTraining['ends'])): ?>
                            <div class="empty-state">
                                <i class="fas fa-bullseye fa-3x mb-3"></i>
                                <p>Aucune volée enregistrée</p>
                                <?php if ($scoredTraining['status'] === 'en_cours'): ?>
                                <button class="btn btn-primary btn-action" onclick="addEnd()">
                                    <i class="fas fa-plus"></i> Ajouter une volée
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-ends">
                            <thead>
                                <tr>
                                    <th>Volée</th>
                                    <th>Scores</th>
                                    <th>Total</th>
                                    <th>Moyenne</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                                    <tbody>
                                        <?php foreach ($scoredTraining['ends'] as $end): ?>
                                        <tr>
                                            <td>
                                                <div class="end-info">
                                                    <div class="end-number">Volée <?= $end['end_number'] ?></div>
                                                    <div class="end-date">
                                                        <?= date('d/m/Y H:i', strtotime($end['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($end['shots'] as $shot): ?>
                                                    <span class="badge bg-primary score-badge"><?= $shot['score'] ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="total-score"><?= $end['total_score'] ?></strong>
                                            </td>
                                            <td>
                                                <span class="average-score"><?= count($end['shots']) > 0 ? number_format($end['total_score'] / count($end['shots']), 1) : '0.0' ?></span>
                                            </td>
                                            <td>
                                                <?php if ($end['comment']): ?>
                                                <small class="comment-text"><?= htmlspecialchars($end['comment']) ?></small>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($scoredTraining['status'] === 'en_cours'): ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-primary btn-action" onclick="addEnd()">
                                    <i class="fas fa-plus"></i> Ajouter une volée
                                </button>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
            <!-- Graphique des scores par volée -->
            <?php if (!empty($scoredTraining['ends'])): ?>
            <div class="card mt-4 detail-card">
                <div class="card-header">
                    <h5 class="mb-0 chart-title">Graphique des scores par volée</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="scoresChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card detail-card">
                        <div class="card-header">
                            <h5 class="mb-0">Informations</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Statut:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($scoredTraining['status'] === 'en_cours'): ?>
                                    <span class="badge bg-warning status-badge status-en-cours">En cours</span>
                                    <?php else: ?>
                                    <span class="badge bg-success status-badge status-termine">Terminé</span>
                                    <?php endif; ?>
                                </dd>
                                
                                <dt class="col-sm-4">Début:</dt>
                                <dd class="col-sm-8">
                                    <?= date('d/m/Y H:i', strtotime($scoredTraining['start_date'])) ?>
                                </dd>
                                
                                <?php if ($scoredTraining['end_date']): ?>
                                <dt class="col-sm-4">Fin:</dt>
                                <dd class="col-sm-8">
                                    <?= date('d/m/Y H:i', strtotime($scoredTraining['end_date'])) ?>
                                </dd>
                                <?php endif; ?>
                                
                                <dt class="col-sm-4">Type de tir:</dt>
                                <dd class="col-sm-8">
                                    <?= $scoredTraining['shooting_type'] ? htmlspecialchars($scoredTraining['shooting_type']) : '-' ?>
                                </dd>
                                
                                <dt class="col-sm-4">Flèches/volée:</dt>
                                <dd class="col-sm-8"><?= $scoredTraining['arrows_per_end'] ?></dd>
                                
                                <dt class="col-sm-4">Volées prévues:</dt>
                                <dd class="col-sm-8"><?= $scoredTraining['total_ends'] ?></dd>
                            </dl>
                            
                            <?php if ($scoredTraining['notes']): ?>
                            <hr>
                            <h6>Notes:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($scoredTraining['notes'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($scoredTraining['status'] === 'en_cours'): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Finaliser le tir</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-success w-100" onclick="endTraining()">
                                <i class="fas fa-stop"></i> Finaliser le tir compté
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de volée -->
<div class="modal fade" id="addEndModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une volée</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addEndForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_number" class="form-label">Numéro de volée</label>
                                <input type="number" class="form-control" id="end_number" name="end_number" 
                                       value="<?= count($scoredTraining['ends']) + 1 ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_category" class="form-label">Catégorie de cible</label>
                                <select class="form-select" id="target_category" name="target_category">
                                    <option value="">Sélectionner</option>
                                    <?php if ($scoredTraining['shooting_type'] === 'TAE'): ?>
                                        <option value="122cm">122cm</option>
                                        <option value="80cm">80cm</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Salle'): ?>
                                        <option value="60cm">60cm</option>
                                        <option value="40cm">40cm</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === '3D'): ?>
                                        <option value="3D">Cible 3D</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Nature'): ?>
                                        <option value="Nature">Cible nature</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Campagne'): ?>
                                        <option value="Campagne">Cible campagne</option>
                                    <?php else: ?>
                                        <option value="122cm">122cm</option>
                                        <option value="80cm">80cm</option>
                                        <option value="60cm">60cm</option>
                                        <option value="40cm">40cm</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($scoredTraining['shooting_type'] !== 'Salle'): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="shooting_position" class="form-label">Position de tir</label>
                                <select class="form-select" id="shooting_position" name="shooting_position">
                                    <option value="">Sélectionner</option>
                                    <?php if ($scoredTraining['shooting_type'] === 'TAE'): ?>
                                        <option value="Debout">Debout</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === '3D'): ?>
                                        <option value="Debout">Debout</option>
                                        <option value="Assis">Assis</option>
                                        <option value="À genoux">À genoux</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Nature'): ?>
                                        <option value="Debout">Debout</option>
                                        <option value="Assis">Assis</option>
                                        <option value="À genoux">À genoux</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Campagne'): ?>
                                        <option value="Debout">Debout</option>
                                        <option value="Assis">Assis</option>
                                        <option value="À genoux">À genoux</option>
                                    <?php else: ?>
                                        <option value="Debout">Debout</option>
                                        <option value="Assis">Assis</option>
                                        <option value="À genoux">À genoux</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Commentaire</label>
                                <input type="text" class="form-control" id="comment" name="comment">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scores des flèches</label>
                        <div class="row" id="scoresContainer">
                            <!-- Les champs de score seront générés dynamiquement -->
                        </div>
                    </div>
                </form>
            </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="button" class="btn btn-success" onclick="saveEnd()">Enregistrer et continuer</button>
                                    <button type="button" class="btn btn-primary" onclick="saveEndAndClose()">Terminer</button>
                                </div>
        </div>
    </div>
</div>

<!-- Modal de finalisation -->
<div class="modal fade" id="endTrainingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finaliser le tir compté</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="endTrainingForm">
                    <div class="mb-3">
                        <label for="final_notes" class="form-label">Notes finales <small class="text-muted">(optionnel)</small></label>
                        <textarea class="form-control" id="final_notes" name="final_notes" rows="3" placeholder="Ajoutez des notes sur votre performance..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="confirmEndTraining()">Finaliser</button>
            </div>
        </div>
    </div>
</div>

