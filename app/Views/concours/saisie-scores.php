<?php
/**
 * Page de saisie des scores pour un concours
 * Pour les concours de type Nature : score total + nombre de 20-15, 20-10, 15-15, 15-10
 * Pour les autres types : score total uniquement (extensible)
 * Filtrage par départ : afficher uniquement les tireurs du départ sélectionné
 */
$concoursId = $concoursId ?? ($concours->id ?? $concours->_id ?? null);
$isNature = $isNature ?? false;
$isSalleTae = $isSalleTae ?? false;
$isNature2x21 = $isNature2x21 ?? false; // Nature 21 cibles x2 : 2 séries P1 et P2
$isTwoSeries = $isSalleTae || $isNature2x21; // Salle/TAE ou Nature 21 cibles x2
$resultats = $resultats ?? [];
$departsForSelect = $departsForSelect ?? [];
$departSelected = $departSelected ?? null;
$serieMode = $serieMode ?? 'both';
$baseUrlScores = '/concours/' . (int)$concoursId . '/saisie-scores';
$showSerie1 = ($isTwoSeries && ($serieMode === '1' || $serieMode === 'both'));
$showSerie2 = ($isTwoSeries && ($serieMode === '2' || $serieMode === 'both'));
$showTotaux = ($isTwoSeries && $serieMode === 'both');
$serieLabel1 = $isNature2x21 ? 'P1' : 'S1';
$serieLabel2 = $isNature2x21 ? 'P2' : 'S2';
?>
<div class="container-fluid concours-saisie-scores">
    <h1 class="mb-4">
        <i class="fas fa-calculator me-2"></i>Saisie des scores
        <small class="text-muted d-block mt-1"><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></small>
    </h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($inscriptions) && empty($departsForSelect)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Aucune inscription confirmée pour ce concours. Les scores ne peuvent être saisis que pour les archers dont l'inscription est confirmée.
        </div>
        <a href="/concours/show/<?= (int)$concoursId ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour au concours
        </a>
        <?php return; ?>
    <?php endif; ?>

    <?php if (!empty($departsForSelect) || $isTwoSeries): ?>
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" action="<?= htmlspecialchars($baseUrlScores) ?>" id="form-select-depart" class="d-flex align-items-center gap-3 flex-wrap">
                <?php if ($departSelected !== null && empty($departsForSelect)): ?>
                <input type="hidden" name="depart" value="<?= (int)$departSelected ?>">
                <?php endif; ?>
                <?php if (!empty($departsForSelect)): ?>
                <div class="d-flex align-items-center gap-2">
                    <label for="select-depart" class="mb-0 fw-bold">
                        <i class="fas fa-flag me-1"></i>Départ :
                    </label>
                    <select name="depart" id="select-depart" class="form-select form-select-sm" style="max-width: 280px;">
                        <option value="">-- Tous les départs --</option>
                        <?php foreach ($departsForSelect as $num => $label): ?>
                            <option value="<?= (int)$num ?>"<?= $departSelected === (int)$num ? ' selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if ($isTwoSeries): ?>
                <div class="d-flex align-items-center gap-2">
                    <label for="select-serie" class="mb-0 fw-bold">
                        <i class="fas fa-list-ol me-1"></i><?= $isNature2x21 ? 'Passage' : 'Série' ?> :
                    </label>
                    <select name="serie" id="select-serie" class="form-select form-select-sm" style="max-width: 220px;">
                        <option value="1"<?= $serieMode === '1' ? ' selected' : '' ?>><?= htmlspecialchars($serieLabel1) ?> uniquement</option>
                        <option value="2"<?= $serieMode === '2' ? ' selected' : '' ?>><?= htmlspecialchars($serieLabel2) ?> uniquement</option>
                        <option value="both"<?= $serieMode === 'both' ? ' selected' : '' ?>>Les deux (<?= htmlspecialchars($serieLabel1) ?> + <?= htmlspecialchars($serieLabel2) ?>)</option>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('select-depart')?.addEventListener('change', function() { document.getElementById('form-select-depart').submit(); });
    document.getElementById('select-serie')?.addEventListener('change', function() { document.getElementById('form-select-depart').submit(); });
    </script>
    <?php endif; ?>

    <?php if (empty($inscriptions)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php if ($departSelected !== null): ?>
                Aucun tireur inscrit au départ sélectionné.
            <?php else: ?>
                Aucune inscription confirmée pour ce concours.
            <?php endif; ?>
        </div>
        <a href="/concours/show/<?= (int)$concoursId ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour au concours
        </a>
        <?php return; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <?php if ($isSalleTae): ?>
                    <i class="fas fa-bullseye me-2"></i>Concours Salle / TAE – Saisie des scores
                    <small class="d-block text-muted mt-1">2 séries par départ : nombre de 10 et de 9 par série, total général</small>
                <?php elseif ($isNature2x21): ?>
                    <i class="fas fa-leaf me-2"></i>Concours Nature 21 cibles x 2 – Saisie des scores
                    <small class="d-block text-muted mt-1">2 passages (P1 et P2) par départ, score total = P1 + P2</small>
                <?php elseif ($isNature): ?>
                    <i class="fas fa-leaf me-2"></i>Concours Nature – Saisie des scores
                    <small class="d-block text-muted mt-1">Score total et détail des impacts (20-15, 20-10, 15-15, 15-10, 15, 10, manqués)</small>
                <?php else: ?>
                    <i class="fas fa-bullseye me-2"></i>Saisie des scores
                    <small class="d-block text-muted mt-1">Score total par archer</small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php
            $formActionParams = [];
            if ($departSelected !== null) $formActionParams['depart'] = $departSelected;
            if ($isTwoSeries && $serieMode !== 'both') $formActionParams['serie'] = $serieMode;
            $formAction = $baseUrlScores . (!empty($formActionParams) ? '?' . http_build_query($formActionParams) : '');
            ?>
            <form method="post" action="<?= htmlspecialchars($formAction) ?>" id="form-scores">
                <?php if ($departSelected !== null): ?>
                <input type="hidden" name="depart" value="<?= (int)$departSelected ?>">
                <?php endif; ?>
                <?php if ($isTwoSeries): ?>
                <input type="hidden" name="serie_mode" value="<?= htmlspecialchars($serieMode) ?>">
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Archer</th>
                                <th>N° licence</th>
                                <th>Club</th>
                                <th>Départ</th>
                                <?php if ($isSalleTae): ?>
                                    <?php if ($showSerie1): ?>
                                        <th>Score S1</th>
                                        <th>S1 - 10</th>
                                        <th>S1 - 9</th>
                                    <?php endif; ?>
                                    <?php if ($showSerie2): ?>
                                        <th>Score S2</th>
                                        <th>S2 - 10</th>
                                        <th>S2 - 9</th>
                                    <?php endif; ?>
                                    <?php if ($showTotaux): ?>
                                        <th>Score total</th>
                                        <th>Total 10</th>
                                        <th>Total 9</th>
                                    <?php endif; ?>
                                <?php elseif ($isNature2x21): ?>
                                    <?php if ($showSerie1): ?>
                                        <th>Score <?= htmlspecialchars($serieLabel1) ?></th>
                                    <?php endif; ?>
                                    <?php if ($showSerie2): ?>
                                        <th>Score <?= htmlspecialchars($serieLabel2) ?></th>
                                    <?php endif; ?>
                                    <?php if ($showTotaux): ?>
                                        <th>Score total</th>
                                    <?php endif; ?>
                                <?php elseif ($isNature): ?>
                                    <th>Score total</th>
                                    <th>20-15</th>
                                    <th>20-10</th>
                                    <th>15-15</th>
                                    <th>15-10</th>
                                    <th>15</th>
                                    <th>10</th>
                                    <th>Manqués (0)</th>
                                <?php else: ?>
                                    <th>Score total</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscriptions as $inscription): ?>
                                <?php
                                $inscId = $inscription['id'] ?? '';
                                $res = $resultats[$inscId] ?? [];
                                $userNom = $inscription['user_nom'] ?? 'N/A';
                                $numeroLicence = $inscription['numero_licence'] ?? 'N/A';
                                $clubName = $inscription['club_name'] ?? 'N/A';
                                $numeroDepart = $inscription['numero_depart'] ?? '-';
                                $scoreVal = $res['score'] ?? $res['score_total'] ?? '';
                                $nb2015 = $res['nb_20_15'] ?? '';
                                $nb2010 = $res['nb_20_10'] ?? '';
                                $nb1515 = $res['nb_15_15'] ?? '';
                                $nb1510 = $res['nb_15_10'] ?? '';
                                $nb15 = $res['nb_15'] ?? '';
                                $nb10 = $res['nb_10'] ?? '';
                                $nb0 = $res['nb_0'] ?? '';
                                $s1_score = $res['serie1_score'] ?? '';
                                $s1_10 = $res['serie1_nb_10'] ?? '';
                                $s1_9 = $res['serie1_nb_9'] ?? '';
                                $s2_score = $res['serie2_score'] ?? '';
                                $s2_10 = $res['serie2_nb_10'] ?? '';
                                $s2_9 = $res['serie2_nb_9'] ?? '';
                                $tot10 = ($s1_10 !== '' && $s2_10 !== '') ? ((int)$s1_10 + (int)$s2_10) : (($res['total_nb_10'] ?? ''));
                                $tot9 = ($s1_9 !== '' && $s2_9 !== '') ? ((int)$s1_9 + (int)$s2_9) : (($res['total_nb_9'] ?? ''));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($userNom) ?></td>
                                    <td><?= htmlspecialchars($numeroLicence) ?></td>
                                    <td><?= htmlspecialchars($clubName) ?></td>
                                    <td><?= htmlspecialchars($numeroDepart) ?></td>
                                    <?php if ($isSalleTae): ?>
                                        <?php if ($showSerie1): ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie1_score]" 
                                                   value="<?= htmlspecialchars($s1_score !== '' ? $s1_score : '') ?>" 
                                                   class="form-control form-control-sm serie1-score-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie1_nb_10]" 
                                                   value="<?= htmlspecialchars($s1_10 !== '' ? $s1_10 : '') ?>" 
                                                   class="form-control form-control-sm serie-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie1_nb_9]" 
                                                   value="<?= htmlspecialchars($s1_9 !== '' ? $s1_9 : '') ?>" 
                                                   class="form-control form-control-sm serie-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($showSerie2): ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie2_score]" 
                                                   value="<?= htmlspecialchars($s2_score !== '' ? $s2_score : '') ?>" 
                                                   class="form-control form-control-sm serie2-score-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie2_nb_10]" 
                                                   value="<?= htmlspecialchars($s2_10 !== '' ? $s2_10 : '') ?>" 
                                                   class="form-control form-control-sm serie-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie2_nb_9]" 
                                                   value="<?= htmlspecialchars($s2_9 !== '' ? $s2_9 : '') ?>" 
                                                   class="form-control form-control-sm serie-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($showTotaux): ?>
                                        <td class="text-center score-total-cell"><?= ($s1_score !== '' && $s2_score !== '') ? ((int)$s1_score + (int)$s2_score) : '-' ?></td>
                                        <td class="text-center total-10-cell"><?= ($s1_10 !== '' && $s2_10 !== '') ? (int)$tot10 : '-' ?></td>
                                        <td class="text-center total-9-cell"><?= ($s1_9 !== '' && $s2_9 !== '') ? (int)$tot9 : '-' ?></td>
                                        <?php endif; ?>
                                    <?php elseif ($isNature2x21): ?>
                                        <?php if ($showSerie1): ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie1_score]" 
                                                   value="<?= htmlspecialchars($s1_score !== '' ? $s1_score : '') ?>" 
                                                   class="form-control form-control-sm serie1-score-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($showSerie2): ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][serie2_score]" 
                                                   value="<?= htmlspecialchars($s2_score !== '' ? $s2_score : '') ?>" 
                                                   class="form-control form-control-sm serie2-score-input" min="0" step="1" placeholder="0">
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($showTotaux): ?>
                                        <td class="text-center score-total-cell"><?= ($s1_score !== '' && $s2_score !== '') ? ((int)$s1_score + (int)$s2_score) : '-' ?></td>
                                        <?php endif; ?>
                                    <?php elseif ($isNature): ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][score]" 
                                                   value="<?= htmlspecialchars($scoreVal !== '' ? $scoreVal : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_20_15]" 
                                                   value="<?= htmlspecialchars($nb2015 !== '' ? $nb2015 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_20_10]" 
                                                   value="<?= htmlspecialchars($nb2010 !== '' ? $nb2010 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_15_15]" 
                                                   value="<?= htmlspecialchars($nb1515 !== '' ? $nb1515 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_15_10]" 
                                                   value="<?= htmlspecialchars($nb1510 !== '' ? $nb1510 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_15]" 
                                                   value="<?= htmlspecialchars($nb15 !== '' ? $nb15 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_10]" 
                                                   value="<?= htmlspecialchars($nb10 !== '' ? $nb10 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][nb_0]" 
                                                   value="<?= htmlspecialchars($nb0 !== '' ? $nb0 : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <input type="number" name="scores[<?= (int)$inscId ?>][score]" 
                                                   value="<?= htmlspecialchars($scoreVal !== '' ? $scoreVal : '') ?>" 
                                                   class="form-control form-control-sm" min="0" step="1" placeholder="0">
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($isTwoSeries && $showTotaux): ?>
                <script>
                (function() {
                    function updateTotaux(row) {
                        var s1ScoreInp = row.querySelector('input[name*="[serie1_score]"]');
                        var s2ScoreInp = row.querySelector('input[name*="[serie2_score]"]');
                        var s1_10Inp = row.querySelector('input[name*="[serie1_nb_10]"]');
                        var s1_9Inp = row.querySelector('input[name*="[serie1_nb_9]"]');
                        var s2_10Inp = row.querySelector('input[name*="[serie2_nb_10]"]');
                        var s2_9Inp = row.querySelector('input[name*="[serie2_nb_9]"]');
                        var s1Score = s1ScoreInp && s1ScoreInp.value !== '' ? (parseInt(s1ScoreInp.value) || 0) : null;
                        var s2Score = s2ScoreInp && s2ScoreInp.value !== '' ? (parseInt(s2ScoreInp.value) || 0) : null;
                        var s1_10 = s1_10Inp && s1_10Inp.value !== '' ? (parseInt(s1_10Inp.value) || 0) : null;
                        var s1_9 = s1_9Inp && s1_9Inp.value !== '' ? (parseInt(s1_9Inp.value) || 0) : null;
                        var s2_10 = s2_10Inp && s2_10Inp.value !== '' ? (parseInt(s2_10Inp.value) || 0) : null;
                        var s2_9 = s2_9Inp && s2_9Inp.value !== '' ? (parseInt(s2_9Inp.value) || 0) : null;
                        var scoreTotalCell = row.querySelector('.score-total-cell');
                        var tot10Cell = row.querySelector('.total-10-cell');
                        var tot9Cell = row.querySelector('.total-9-cell');
                        if (scoreTotalCell) scoreTotalCell.textContent = (s1Score !== null && s2Score !== null) ? (s1Score + s2Score) : '-';
                        if (tot10Cell) tot10Cell.textContent = (s1_10 !== null && s2_10 !== null) ? (s1_10 + s2_10) : '-';
                        if (tot9Cell) tot9Cell.textContent = (s1_9 !== null && s2_9 !== null) ? (s1_9 + s2_9) : '-';
                    }
                    function addListeners(row) {
                        var inputs = row.querySelectorAll('.serie-input, .serie1-score-input, .serie2-score-input');
                        inputs.forEach(function(inp) {
                            inp.addEventListener('input', function() { updateTotaux(row); });
                        });
                    }
                    document.querySelectorAll('#form-scores tbody tr').forEach(addListeners);
                })();
                </script>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Enregistrer les scores
                    </button>
                    <a href="/concours/show/<?= (int)$concoursId ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour au concours
                    </a>
                    <a href="/concours" class="btn btn-link text-muted">Liste des concours</a>
                </div>
            </form>
        </div>
    </div>
</div>
