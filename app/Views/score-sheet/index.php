<?php
// Variables disponibles depuis le contrôleur : $concours (liste des concours), $disciplines
$concoursList = $concours ?? [];
$disciplinesList = $disciplines ?? [];
$concoursJson = htmlspecialchars(json_encode($concoursList, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$disciplinesJson = htmlspecialchars(json_encode($disciplinesList, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>

<div class="container-fluid score-sheet-container" data-concours-list="<?= $concoursJson ?>" data-disciplines="<?= $disciplinesJson ?>">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Feuille de marque
                </h1>
                <a href="/scored-trainings" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Sélection du concours -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Concours</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="concoursSelect" class="form-label">Sélectionner un concours</label>
                            <select class="form-select" id="concoursSelect">
                                <option value="">-- Aucun concours (saisie manuelle) --</option>
                                <?php foreach ($concoursList as $c): 
                                    $cId = $c['id'] ?? $c['_id'] ?? null;
                                    $cTitre = $c['titre_competition'] ?? $c['nom'] ?? 'Concours';
                                    $cDate = $c['date_debut'] ?? '';
                                    if ($cDate && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $cDate, $m)) {
                                        $cDate = $m[3] . '/' . $m[2] . '/' . $m[1];
                                    }
                                    if ($cId):
                                ?>
                                <option value="<?= htmlspecialchars($cId) ?>" data-discipline="<?= htmlspecialchars($c['discipline'] ?? $c['iddiscipline'] ?? '') ?>">
                                    <?= htmlspecialchars($cTitre) ?><?= $cDate ? ' (' . htmlspecialchars($cDate) . ')' : '' ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="pelotonSelectorWrapper" style="display: none;">
                            <label for="departSelect" class="form-label" id="departCibleLabel">Départ / Peloton</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <select class="form-select" id="departSelect">
                                        <option value="">-- Départ --</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select class="form-select" id="pelotonSelect">
                                        <option value="">-- Peloton --</option>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted" id="selectorHint">Pour les disciplines Nature, 3D et Campagne</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sélection du type de tir -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Type de tir</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="shootingType" class="form-label">Sélectionner le type de tir</label>
                            <select class="form-select" id="shootingType" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($disciplinesList as $d): 
                                    $abv = $d['abv_discipline'] ?? $d['abv'] ?? '';
                                    $lb = $d['lb_discipline'] ?? $d['name'] ?? $d['nom'] ?? $abv;
                                    if ($abv !== ''): ?>
                                <option value="<?= htmlspecialchars($abv) ?>"><?= htmlspecialchars($lb) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="trainingTitle" class="form-label">Titre de la feuille de marque</label>
                            <input type="text" class="form-control" id="trainingTitle" placeholder="Ex: Compétition du 15/11/2024">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation entre les archers -->
            <div id="archerNavigation" class="mb-3" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-outline-primary" id="prevArcherBtn" onclick="navigateArcher(-1)">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </button>
                        <span class="mx-3">
                            Archer <span id="currentArcherNumber">1</span> / <span id="totalArchers">6</span>
                        </span>
                        <button class="btn btn-outline-primary" id="nextArcherBtn" onclick="navigateArcher(1)">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-info me-2" id="signaturesBtn" onclick="openSignatureModal()" style="display: none;">
                            <i class="fas fa-signature"></i> Signatures
                        </button>
                        <button class="btn btn-primary me-2" id="exportPdfBtn" onclick="exportToPDF()" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Exporter PDF
                        </button>
                        <button class="btn btn-success" id="saveScoreSheetBtn" onclick="saveScoreSheet()" style="display: none;">
                            <i class="fas fa-save"></i> Sauvegarder les feuilles de marque
                        </button>
                    </div>
                </div>
            </div>

            <!-- Informations de l'archer -->
            <div id="archerInfoSection" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">Informations de l'archer <span id="archerHeaderNumber">1</span></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="archerName" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="archerName" placeholder="Nom de l'archer">
                        </div>
                        <div class="col-md-3">
                            <label for="archerLicense" class="form-label">Numéro de licence</label>
                            <input type="text" class="form-control" id="archerLicense" placeholder="Ex: 660035U">
                        </div>
                        <div class="col-md-2">
                            <label for="archerCategory" class="form-label">Catégorie de classement</label>
                            <select class="form-select" id="archerCategory">
                                <option value="">-- Choisir un type de tir pour charger les catégories --</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des scores -->
            <div id="scoreTableSection" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">Scores</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm score-table" id="scoreTable">
                            <thead>
                                <tr>
                                    <th class="volley-col">Volée</th>
                                    <th id="arrowHeaders" colspan="3"></th>
                                    <th class="total-col">Total</th>
                                    <th id="cumulativeHeader" class="cumulative-col" style="display: none;">Cumul</th>
                                </tr>
                            </thead>
                            <tbody id="scoreTableBody">
                                <!-- Les lignes seront générées par JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>TOTAL</th>
                                    <td id="footerArrows" colspan="3"></td>
                                    <th id="grandTotal">0</th>
                                    <th id="footerCumulative" style="display: none;"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Message d'état -->
            <div id="statusMessage" class="alert" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Modal pour saisir les scores d'une volée -->
<div class="modal fade" id="scoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Volée <span id="modalVolleyNumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="scoreInputTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#tableMode" type="button" role="tab">
                            <i class="fas fa-table"></i> Tableau
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="target-tab" data-bs-toggle="tab" data-bs-target="#targetMode" type="button" role="tab">
                            <i class="fas fa-bullseye"></i> Cible interactive
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="scoreInputContent">
                    <div class="tab-pane fade show active" id="tableMode" role="tabpanel">
                        <div id="scoreInputs">
                            <!-- Les inputs seront générés par JavaScript -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="targetMode" role="tabpanel">
                        <div class="target-interactive-container">
                            <div class="target-wrapper" id="targetWrapper">
                                <div class="target-zoom-container" id="targetZoomContainer">
                                    <svg class="target-svg" id="targetSvg" viewBox="0 0 300 300">
                                        <?php 
                                        // Générer le blason selon le type de tir
                                        // Le type de tir sera déterminé par JavaScript
                                        $centerX = 150;
                                        $centerY = 150;
                                        
                                        // Par défaut, générer un blason standard (10 zones)
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
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveVolleyScores()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay pour le mode zoom de la cible -->
<div class="zoom-overlay" id="zoomOverlay"></div>
<!-- Overlay pour le mode zoom drag -->
<div class="zoom-drag-overlay" id="zoomDragOverlay"></div>

<!-- Modal pour les signatures -->
<div class="modal fade" id="signatureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Signatures</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="signatureTabs" class="mb-3">
                    <ul class="nav nav-tabs" id="signatureTabList" role="tablist">
                        <!-- Les onglets seront générés par JavaScript -->
                    </ul>
                    <div class="tab-content" id="signatureTabContent">
                        <!-- Le contenu sera généré par JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" onclick="saveSignatures()">
                    <i class="fas fa-save"></i> Enregistrer les signatures
                </button>
            </div>
        </div>
    </div>
</div>

