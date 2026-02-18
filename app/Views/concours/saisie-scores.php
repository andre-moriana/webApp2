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
$resultats = $resultats ?? [];
$departsForSelect = $departsForSelect ?? [];
$departSelected = $departSelected ?? null;
$baseUrlScores = '/concours/' . (int)$concoursId . '/saisie-scores';
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

    <?php if (!empty($departsForSelect)): ?>
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" action="<?= htmlspecialchars($baseUrlScores) ?>" id="form-select-depart" class="d-flex align-items-center gap-2 flex-wrap">
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
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('select-depart').addEventListener('change', function() {
        document.getElementById('form-select-depart').submit();
    });
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
                <?php elseif ($isNature): ?>
                    <i class="fas fa-leaf me-2"></i>Concours Nature – Saisie des scores
                    <small class="d-block text-muted mt-1">Score total et détail des impacts (20-15, 20-10, 15-15, 15-10, 15, 10)</small>
                <?php else: ?>
                    <i class="fas fa-bullseye me-2"></i>Saisie des scores
                    <small class="d-block text-muted mt-1">Score total par archer</small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($baseUrlScores) ?><?= $departSelected !== null ? '?depart=' . (int)$departSelected : '' ?>" id="form-scores">
                <?php if ($departSelected !== null): ?>
                <input type="hidden" name="depart" value="<?= (int)$departSelected ?>">
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Archer</th>
                                <th>N° licence</th>
                                <th>Club</th>
                                <th>Départ</th>
                                <th>Score total</th>
                                <?php if ($isSalleTae): ?>
                                    <th>S1 - 10</th>
                                    <th>S1 - 9</th>
                                    <th>S2 - 10</th>
                                    <th>S2 - 9</th>
                                    <th>Total 10</th>
                                    <th>Total 9</th>
                                <?php elseif ($isNature): ?>
                                    <th>20-15</th>
                                    <th>20-10</th>
                                    <th>15-15</th>
                                    <th>15-10</th>
                                    <th>15</th>
                                    <th>10</th>
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
                                $s1_10 = $res['serie1_nb_10'] ?? '';
                                $s1_9 = $res['serie1_nb_9'] ?? '';
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
                                    <td>
                                        <input type="number" name="scores[<?= (int)$inscId ?>][score]" 
                                               value="<?= htmlspecialchars($scoreVal !== '' ? $scoreVal : '') ?>" 
                                               class="form-control form-control-sm score-total" min="0" step="1" 
                                               placeholder="0">
                                    </td>
                                    <?php if ($isSalleTae): ?>
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
                                        <td class="text-center total-10-cell"><?= $tot10 !== '' ? (int)$tot10 : '-' ?></td>
                                        <td class="text-center total-9-cell"><?= $tot9 !== '' ? (int)$tot9 : '-' ?></td>
                                    <?php elseif ($isNature): ?>
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
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($isSalleTae): ?>
                <script>
                (function() {
                    document.querySelectorAll('.serie-input').forEach(function(inp) {
                        inp.addEventListener('input', function() {
                            var row = inp.closest('tr');
                            if (!row) return;
                            var s1_10 = parseInt(row.querySelector('input[name*="[serie1_nb_10]"]')?.value || 0) || 0;
                            var s1_9 = parseInt(row.querySelector('input[name*="[serie1_nb_9]"]')?.value || 0) || 0;
                            var s2_10 = parseInt(row.querySelector('input[name*="[serie2_nb_10]"]')?.value || 0) || 0;
                            var s2_9 = parseInt(row.querySelector('input[name*="[serie2_nb_9]"]')?.value || 0) || 0;
                            var tot10Cell = row.querySelector('.total-10-cell');
                            var tot9Cell = row.querySelector('.total-9-cell');
                            if (tot10Cell) tot10Cell.textContent = s1_10 + s2_10;
                            if (tot9Cell) tot9Cell.textContent = s1_9 + s2_9;
                        });
                    });
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
