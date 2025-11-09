<?php
// Variables disponibles depuis le contrôleur :
// $scoredTraining, $selectedUser, $isAdmin, $isCoach
// Inclure les fichiers CSS et JS spécifiques
$additionalCSS = [
    '/public/assets/css/scored-trainings.css',
    '/public/assets/css/scored-training-show.css',
    '/public/assets/css/svg-target.css'
];
$additionalJS = [
    '/public/assets/js/scored-training-show.js?v=' . time(),
    '/public/assets/js/svg-target.js?v=' . time()
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
        status: '<?= addslashes($scoredTraining['status']) ?>',
    };
    // Données des volées pour le graphique
    window.endsData = <?= json_encode($scoredTraining['ends'] ?? []) ?>;
    // Token d'authentification pour l'API
    //window.token = '<?= $_SESSION['token'] ?? '' ?>';
</script>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?= htmlspecialchars($scoredTraining['title']) ?></h1>
                    <p class="text-muted mb-0">
                        <?= $scoredTraining['exercise_sheet_id'] ? 'Exercice associé' : '' ?>
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
            <!-- Cible SVG interactive -->
            <?php if (!empty($scoredTraining['ends']) && $scoredTraining['shooting_type'] !== '3D' && $scoredTraining['shooting_type'] !== 'Nature'): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card detail-card">
                        <div class="card-header">
                            <h5 class="mb-0">Cible interactive</h5>
                            <small class="text-muted">Visualisation des impacts de toutes les volées</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div id="svgTargetContainer" class="d-flex justify-content-center">
                                        <div class="target-loading">Chargement de la cible...</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="target-controls">
                                        <?php if (($scoredTraining['shooting_type'] ?? '') !== 'Campagne'): ?>
                                        <div class="mb-3">
                                            <label for="targetCategorySelect" class="form-label">Type de cible</label>
                                            <select class="form-select" id="targetCategorySelect">
                                                <option value="blason_80">Blason 80cm</option>
                                                <option value="blason_122">Blason 122cm</option>
                                                <option value="blason_60">Blason 60cm</option>
                                                <option value="blason_40">Blason 40cm</option>
                                                <option value="trispot">Trispot</option>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label for="targetSizeSelect" class="form-label">Taille d'affichage</label>
                                            <select class="form-select" id="targetSizeSelect">
                                                <option value="200">Petite (200px)</option>
                                                <option value="300" selected>Moyenne (300px)</option>
                                                <option value="400">Grande (400px)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <button class="btn btn-outline-primary btn-sm" onclick="refreshTarget()">
                                                <i class="fas fa-sync-alt"></i> Actualiser
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
                                            <th>Cible</th>
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
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <?php 
                                                    $hasCoordinates = false;
                                                    if (isset($end['shots'])) {
                                                        foreach ($end['shots'] as $shot) {
                                                            if ($shot['hit_x'] !== null && $shot['hit_y'] !== null) {
                                                                $hasCoordinates = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($hasCoordinates): ?>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showEndTarget(<?= $end['end_number'] ?>)" title="Voir la cible de cette volée">
                                                        <i class="fas fa-bullseye"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($end['ref_blason'])): ?>
                                                    <button class="btn btn-sm btn-outline-info" onclick="showBlasonImage(<?= $end['ref_blason'] ?>)" title="Voir le blason de cette volée">
                                                        <i class="fas fa-image"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$hasCoordinates && empty($end['ref_blason'])): ?>
                                                    <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
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
                            <?php
                            // Fonction pour filtrer les notes (retirer les signatures)
                            if (!function_exists('filterNotesForDisplay')) {
                                function filterNotesForDisplay($notes) {
                                    if (empty($notes)) return '';
                                    
                                    // Retirer tout ce qui contient __SIGNATURES__ et ce qui suit (y compris le JSON)
                                    $filtered = $notes;
                                    $signaturesIndex = strpos($filtered, '__SIGNATURES__');
                                    if ($signaturesIndex !== false) {
                                        $filtered = substr($filtered, 0, $signaturesIndex);
                                    }
                                    
                                    // Retirer les lignes qui mentionnent des informations de signature
                                    $lines = explode("\n", $filtered);
                                    $filteredLines = [];
                                    foreach ($lines as $line) {
                                        $trimmedLine = trim($line);
                                        $lowerLine = strtolower($trimmedLine);
                                        
                                        // Retirer les lignes qui contiennent "Signatures:" (même au milieu) ou "ont signé"
                                        if (strpos($lowerLine, 'signatures:') !== false || 
                                            strpos($lowerLine, 'signature:') !== false ||
                                            strpos($lowerLine, 'ont signé') !== false ||
                                            strpos($lowerLine, 'ont signe') !== false ||
                                            preg_match('/signatures?[:\s]/i', $trimmedLine)) {
                                            continue;
                                        }
                                        
                                        // Retirer les lignes qui contiennent des données JSON de signature
                                        if (preg_match('/^\s*\{["\']archer["\']|^\s*\{["\']scorer["\']/i', $trimmedLine)) {
                                            continue;
                                        }
                                        
                                        $filteredLines[] = $line;
                                    }
                                    
                                    $filtered = implode("\n", $filteredLines);
                                    
                                    // Nettoyer les virgules et espaces en fin de chaque ligne
                                    $filtered = preg_replace('/,\s*$/', '', $filtered);
                                    $filtered = rtrim($filtered, " \t\n\r\0\x0B");
                                    
                                    // Retirer les lignes vides multiples
                                    $filtered = preg_replace('/\n\s*\n\s*\n/', "\n\n", $filtered);
                                    
                                    return trim($filtered);
                                }
                            }
                            
                            $filteredNotes = filterNotesForDisplay($scoredTraining['notes']);
                            ?>
                            <p class="text-muted"><?= $filteredNotes ? nl2br(htmlspecialchars($filteredNotes)) : 'Aucune note' ?></p>
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
                        <?php if ($scoredTraining['shooting_type'] !== 'Libre'): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_category" class="form-label">Catégorie de cible</label>
                                <select class="form-select" id="target_category" name="target_category" required>
                                    <option value="">Sélectionner</option>
                                    <?php if ($scoredTraining['shooting_type'] === 'TAE'): ?>
                                        <option value="blason_80">Blason 80cm</option>
                                        <option value="blason_122">Blason 122cm</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Salle'): ?>
                                        <option value="trispot">Trispot</option>
                                        <option value="blason_40">Blason 40cm</option>
                                        <option value="blason_60">Blason 60cm</option>
                                        <option value="blason_80">Blason 80cm</option>
                                        <option value="blason_122">Blason 122cm</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === '3D'): ?>
                                        <option value="1">Categorie 1</option>
                                        <option value="2">Categorie 2</option>
                                        <option value="3">Categorie 3</option>
                                        <option value="4">Categorie 4</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Nature'): ?>
                                        <option value="grands_gibiers">Grands Gibiers</option>
                                        <option value="moyens_gibiers">Moyens Gibiers</option>
                                        <option value="petits_gibiers">Petits Gibiers</option>
                                        <option value="petits_animaux">Petits Animaux</option>
                                        <option value="doubles_birdies">Doubles Birdies</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Campagne'): ?>
                                        <option value="birdie_20">Birdie 20cm</option>
                                        <option value="gaziniere_40">Gazinière 40cm</option>
                                        <option value="blason_60">Blason 60cm</option>
                                        <option value="blason_80">Blason 80cm</option>
                                    <?php else: ?>
                                        <option value="trispot">Trispot</option>
                                        <option value="blason_40">Blason 40cm</option>
                                        <option value="blason_60">Blason 60cm</option>
                                        <option value="blason_80">Blason 80cm</option>
                                        <option value="blason_122">Blason 122cm</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Champ select pour le blason (uniquement pour le type Nature) -->
                    <?php if ($scoredTraining['shooting_type'] === 'Nature'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3" id="nature_blason_wrapper" style="display: none;">
                                <label for="nature_blason" class="form-label">Blason</label>
                                <select class="form-select" id="nature_blason" name="ref_blason">
                                    <option value="">Sélectionner un blason</option>
                                </select>
                                <div class="form-text">Sélectionnez le blason pour le tir Nature</div>
                                <div id="nature_blason_loading" class="text-muted small mt-2" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Chargement des blasons...
                                </div>
                                <!-- Conteneur pour l'aperçu de l'image du blason sélectionné -->
                                <div id="nature_blason_preview" class="mt-3" style="display: none;">
                                    <label class="form-label">Aperçu du blason</label>
                                    <div class="border rounded p-2 text-center" style="background-color: #f8f9fa;">
                                        <img id="nature_blason_image" src="" alt="Blason sélectionné" 
                                             class="img-fluid" style="max-width: 100%; max-height: 400px; object-fit: contain; cursor: pointer;"
                                             onclick="showNatureBlasonModal(this.src)">
                                        <div class="mt-2">
                                            <small class="text-muted">Cliquez sur l'image pour l'agrandir</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Champ select pour le blason 3D (uniquement pour le type 3D) -->
                    <?php if ($scoredTraining['shooting_type'] === '3D'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3" id="threeD_blason_wrapper" style="display: none;">
                                <label for="threeD_blason" class="form-label">Cible</label>
                                <select class="form-select" id="threeD_blason" name="ref_blason">
                                    <option value="">Sélectionner une cible</option>
                                </select>
                                <div class="form-text">Sélectionnez la cible pour le tir 3D</div>
                                <div id="threeD_blason_loading" class="text-muted small mt-2" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Chargement des cibles...
                                </div>
                                <!-- Conteneur pour l'aperçu de l'image de la cible sélectionnée -->
                                <div id="threeD_blason_preview" class="mt-3" style="display: none;">
                                    <label class="form-label">Aperçu de la cible</label>
                                    <div class="border rounded p-2 text-center" style="background-color: #f8f9fa;">
                                        <img id="threeD_blason_image" src="" alt="Cible sélectionnée" 
                                             class="img-fluid" style="max-width: 100%; max-height: 400px; object-fit: contain; cursor: pointer;"
                                             onclick="showThreeDBlasonModal(this.src)">
                                        <div class="mt-2">
                                            <small class="text-muted">Cliquez sur l'image pour l'agrandir</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row">
                        <?php if ($scoredTraining['shooting_type'] !== 'Salle' && $scoredTraining['shooting_type'] !== 'TAE' && $scoredTraining['shooting_type'] !== 'Libre'): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="shooting_position" class="form-label">Position de tir</label>
                                <select class="form-select" id="shooting_position" name="shooting_position">
                                    <option value="">Sélectionner</option>
                                    <?php if ($scoredTraining['shooting_type'] === '3D'): ?>
                                        <option value="montant">Montant</option>
                                        <option value="descendant">Descendant</option>
                                        <option value="droit">Droit</option>
                                        <option value="pret_du_sol">Prêt du sol</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Nature'): ?>
                                        <option value="montant">Montant</option>
                                        <option value="descendant">Descendant</option>
                                        <option value="droit">Droit</option>
                                        <option value="pret_du_sol">Prêt du sol</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'Campagne'): ?>
                                        <option value="montant">Montant</option>
                                        <option value="descendant">Descendant</option>
                                        <option value="droit">Droit</option>
                                    <?php elseif ($scoredTraining['shooting_type'] === 'TAE'): ?>
                                        <option value="FITA">FITA</option>
                                        <option value="Federal">Fédéral</option>
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
                    <?php if ($scoredTraining['shooting_type'] !== '3D' && $scoredTraining['shooting_type'] !== 'Nature'): ?>
                    <div class="mb-3 score-mode-container">
                        <label class="form-label">Mode de saisie des scores</label>
                        <div class="btn-group w-100" role="group" aria-label="Mode de saisie">
                            <input type="radio" class="btn-check" name="scoreMode" id="tableMode" value="table" checked>
                            <label class="btn btn-outline-primary" for="tableMode">
                                <i class="fas fa-table"></i> Tableau
                            </label>
                            
                            <input type="radio" class="btn-check" name="scoreMode" id="targetMode" value="target">
                            <label class="btn btn-outline-primary" for="targetMode">
                                <i class="fas fa-bullseye"></i> Cible interactive
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Mode de saisie masqué pour 3D et Nature - seul le mode tableau est disponible -->
                    <input type="radio" class="btn-check" name="scoreMode" id="tableMode" value="table" checked style="display: none;">
                    <input type="radio" class="btn-check" name="scoreMode" id="targetMode" value="target" style="display: none;">
                    <?php endif; ?>
<!-- Mode tableau -->
                    <div class="mb-3" id="tableModeContainer">
                        <label class="form-label">Scores des flèches</label>
                        <div class="row" id="scoresContainer">
                            <!-- Les champs de score seront générés dynamiquement -->
                        </div>
                    </div>
<!-- Mode cible interactive -->
                    <div class="mb-3" id="targetModeContainer" style="display: none;">
                        <label class="form-label">Sélection sur cible</label>
                        <div class="target-interactive-container">
                            <div class="target-wrapper" id="targetWrapper">
                                <div class="target-zoom-container" id="targetZoomContainer">
                                    <svg class="target-svg" id="targetSvg" viewBox="0 0 300 300">
                                        <?php 
                                        // Générer le blason selon le type de tir
                                        $shootingType = $scoredTraining['shooting_type'] ?? '';
                                        $centerX = 150;
                                        $centerY = 150;
                                        
                                        if ($shootingType === 'Campagne') {
                                            // Blason campagne : 6 zones (1-4 noir, 5-6 jaune)
                                            $numRings = 6;
                                            $targetScale = $numRings / ($numRings + 1); // 6/7
                                            $outerRadius = 150 * $targetScale; // 128.571428...
                                            $ringWidth = $outerRadius / $numRings; // 21.428571...
                                            
                                            $colors = ['#212121', '#212121', '#212121', '#212121', '#FFD700', '#FFD700'];
                                            
                                            for ($i = 0; $i < $numRings; $i++) {
                                                $radius = $outerRadius - $i * $ringWidth;
                                                $color = $colors[$i];
                                                $zoneNumber = $numRings - $i; // Zone 6 (centre) à zone 1 (extérieur)
                                                $stroke_color = ($i === 5) ? 'black' : 'white';
                                                echo "<circle cx='$centerX' cy='$centerY' r='$radius' fill='$color' stroke='$stroke_color' stroke-width='1' class='zone-$zoneNumber'></circle>";
                                            }
                                        } else {
                                            // Blason standard : 10 zones
                                            $numRings = 10;
                                            $targetScale = $numRings / ($numRings + 1); // 10/11
                                            $outerRadius = 150 * $targetScale; // 136.363636...
                                            $ringWidth = $outerRadius / $numRings; // 13.636363...
                                            
                                            $colors = ['#FFFFFF','#FFFFFF','#212121','#212121','#1976D2','#1976D2','#D32F2F','#D32F2F','#FFD700','#FFD700'];
                                            
                                            for ($i = 0; $i < $numRings; $i++) {
                                                $radius = $outerRadius - $i * $ringWidth;
                                                $color = $colors[$i];
                                                $zoneNumber = $numRings - $i; // Zone 10 (centre) à zone 1 (extérieur)
                                                echo "<circle cx='$centerX' cy='$centerY' r='$radius' fill='$color' stroke='black' stroke-width='1' class='zone-$zoneNumber'></circle>";
                                            }
                                        }
                                        ?>
                                        <g id="arrowsGroup"></g>
                                    </svg>
                                </div>
                                <div class="target-controls">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetTarget">
                                        <i class="fas fa-undo"></i> Réinitialiser
                                    </button>
                                </div>
                                <div class="target-score-indicator" id="targetScoreIndicator" style="display: none;">
                                    <div class="score-preview">
                                        <span class="score-label">Score:</span>
                                        <span class="score-value" id="currentScore">0</span>
                                    </div>
                                    <div class="score-instructions">
                                        <small>Relâchez pour confirmer</small>
                                    </div>
                                </div>
                            </div>
                            <div class="target-scores-display" id="targetScoresDisplay">
                                <h6>Scores sélectionnés :</h6>
                                <div class="scores-list" id="scoresList">
                                    <!-- Les scores seront affichés ici -->
                                </div>
                            </div>
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
<!-- Overlay pour le mode zoom de la cible -->
<div class="zoom-overlay" id="zoomOverlay"></div>
<!-- Overlay pour le mode zoom drag -->
<div class="zoom-drag-overlay" id="zoomDragOverlay"></div>
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

<!-- Script pour l'initialisation de la cible SVG -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la cible SVG si des volées existent et que ce n'est pas 3D ou Nature
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    if (window.endsData && window.endsData.length > 0 && shootingType !== '3D' && shootingType !== 'Nature') {
        initializeSVGTarget();
    }
});

function initializeSVGTarget() {
    // Vérifier que le conteneur existe
    const container = document.getElementById('svgTargetContainer');
    if (!container) {
        console.error('Container svgTargetContainer not found');
        return;
    }
    
    // Collecter tous les impacts de toutes les volées
    const allHits = [];
    
    // Déterminer le type de cible selon le type de tir
    const shootingType = window.scoredTrainingData?.shooting_type || '';
    let detectedTargetCategory = 'blason_80'; // Valeur par défaut
    
    if (shootingType === 'Campagne') {
        detectedTargetCategory = 'blason_campagne';
    } else if (shootingType === 'Salle') {
        detectedTargetCategory = 'trispot';
    } else if (shootingType === 'TAE') {
        detectedTargetCategory = 'blason_122';
    }
    
    console.log('🎯 Type de tir détecté:', shootingType);
    console.log('🎯 Type de cible déterminé:', detectedTargetCategory);
    
    window.endsData.forEach(end => {
        if (end.shots && end.shots.length > 0) {
            end.shots.forEach(shot => {
                if (shot.hit_x !== null && shot.hit_y !== null) {
                    allHits.push({
                        hit_x: shot.hit_x,
                        hit_y: shot.hit_y,
                        score: shot.score,
                        arrow_number: shot.arrow_number,
                        endNumber: end.end_number
                    });
                }
            });
        }
    });
    
    console.log('🎯 Nombre d\'impacts collectés:', allHits.length);
    
    // Créer la cible SVG avec le type détecté
    const target = createSVGTarget('svgTargetContainer', allHits, {
        size: 300,
        targetCategory: detectedTargetCategory
    });
    
    
    // Gérer le changement de catégorie de cible
    const categorySelect = document.getElementById('targetCategorySelect');
    if (categorySelect) {
        // Sélectionner automatiquement le type de cible détecté
        categorySelect.value = detectedTargetCategory;
        
        categorySelect.addEventListener('change', function() {
            target.setTargetCategory(this.value);
        });
    }
    
    // Gérer le changement de taille
    const sizeSelect = document.getElementById('targetSizeSelect');
    if (sizeSelect) {
        sizeSelect.addEventListener('change', function() {
            target.resize(parseInt(this.value));
        });
    }
    
    // Fonction de rafraîchissement
    window.refreshTarget = function() {
        target.render();
    };
}

// Fonction pour afficher une cible spécifique à une volée
function showEndTarget(endNumber) {
    const end = window.endsData.find(e => e.end_number === endNumber);
    if (!end || !end.shots) return;
    
    const endHits = end.shots
        .filter(shot => shot.hit_x !== null && shot.hit_y !== null)
        .map(shot => ({
            hit_x: shot.hit_x,
            hit_y: shot.hit_y,
            score: shot.score,
            arrow_number: shot.arrow_number, // Ajouter le numéro de flèche
            endNumber: endNumber
        }));
    
    const target = createSVGTarget('svgTargetContainer', endHits, {
        size: 300,
        targetCategory: document.getElementById('targetCategorySelect')?.value || 'blason_40'
    });
}
</script>

<!-- Modal pour afficher la cible interactive -->
<div class="modal fade" id="targetModal" tabindex="-1" aria-labelledby="targetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="targetModalLabel">Cible Interactive</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="interactiveTarget" class="text-center">
                    <!-- La cible sera générée ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

