<?php
/** Scores - tableaux par groupe selon le tri (club, catégorie, départ) */
$inscriptionsWithScores = [];
foreach ($inscriptions as $insc) {
    $inscId = $insc['id'] ?? $insc['_id'] ?? null;
    $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
    $insc['_resultat'] = $r;
    $inscriptionsWithScores[] = $insc;
}

$triScores = $triScores ?? 'club';
$groupKey = function ($insc) use ($triScores) {
    if ($triScores === 'club') {
        $v = trim($insc['club_nom'] ?? '');
        return $v !== '' ? $v : 'Sans club';
    }
    if ($triScores === 'categorie') {
        $v = trim($insc['categorie_libelle'] ?? $insc['categorie_classement'] ?? '');
        return $v !== '' ? $v : 'Sans catégorie';
    }
    if ($triScores === 'depart') {
        $v = $insc['numero_depart'] ?? null;
        return $v !== null && $v !== '' ? (string)$v : 'Non défini';
    }
    return '—';
};

$groups = [];
foreach ($inscriptionsWithScores as $insc) {
    $k = $groupKey($insc);
    if (!isset($groups[$k])) $groups[$k] = [];
    $groups[$k][] = $insc;
}
if ($triScores === 'depart') {
    ksort($groups, SORT_NATURAL);
} else {
    ksort($groups, SORT_FLAG_CASE | SORT_NATURAL);
}

// N'afficher les colonnes Série 1 / Série 2 que s'il existe réellement une 2e série (sinon une seule série)
$hasSeries = !empty(array_filter($resultats, function ($r) {
    $s2 = $r['serie2_score'] ?? null;
    return $s2 !== null && $s2 !== '' && (int)$s2 > 0;
}));

$resultats = $resultats ?? [];
$disciplineAbv = $disciplineAbv ?? null;
$categoriesMap = $categoriesMap ?? [];
$hasNatureDetail = !empty(array_filter($resultats, function ($r) {
    return isset($r['nb_20_15']) || isset($r['nb_20_10']) || isset($r['nb_15_15']) || isset($r['nb_15_10']);
}));
$has3DDetail = !empty(array_filter($resultats, function ($r) {
    return isset($r['nb_11']) || isset($r['nb_10']) || isset($r['nb_8']) || isset($r['nb_5']);
}));
$has3DLabel = false;
foreach ($categoriesMap as $abv => $lb) {
    $abvUpper = strtoupper((string)$abv);
    $lbUpper = strtoupper((string)$lb);
    if (strpos($lbUpper, '3D') !== false || strpos($abvUpper, '3D') !== false) {
        $has3DLabel = true;
    }
}
$is3D = ($disciplineAbv && in_array((string)$disciplineAbv, ['3', '3D'], true)) || $has3DDetail || $has3DLabel;
$hasDetail = $hasNatureDetail || $has3DDetail;
?>
<div class="edition-scores">
    <?php
    $concoursIdScores = $concours->id ?? $concours->_id ?? null;
    $departScoresFfta = isset($departFilterScores) && $departFilterScores !== '' && $departFilterScores !== 'tout' && $departFilterScores !== 'all'
        ? $departFilterScores : 'tout';
    $fftaScoresTousDeparts = ($departScoresFfta === 'tout');
    if ($concoursIdScores):
        $fftaScoresUrl = '/concours/' . (int)$concoursIdScores . '/editions?doc=scores&depart=tout&export=ffta';
    ?>
    <p class="text-center d-print-none mb-3">
        <?php if ($fftaScoresTousDeparts): ?>
        <a href="<?= htmlspecialchars($fftaScoresUrl) ?>" class="btn btn-outline-primary btn-sm" title="Export FFTA : tri par nom, prénom et n° de tir">
            <i class="fas fa-file-export me-1"></i>Export FFTA
        </a>
        <?php else: ?>
        <span class="btn btn-outline-secondary btn-sm disabled" title="Export FFTA : affichez tous les départs">
            <i class="fas fa-file-export me-1"></i>Export FFTA
        </span>
        <?php endif; ?>
    </p>
    <?php endif; ?>
    <h1 class="text-center mb-4">Scores</h1>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>

    <?php
    $groupLabels = [
        'club' => 'Club',
        'categorie' => 'Catégorie',
        'depart' => 'Départ'
    ];
    $groupLabel = $groupLabels[$triScores] ?? 'Groupe';
    foreach ($groups as $libelle => $rows):
    ?>
    <div class="edition-scores-block mb-2">
        <h2 class="h5 edition-scores-block-titre mb-1 mt-2"><?= htmlspecialchars($groupLabel) ?> : <?= htmlspecialchars($libelle) ?></h2>
        <table class="table table-bordered edition-scores-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nom</th>
                    <th>Licence</th>
                    <th>Catégorie</th>
                    <th>Club</th>
                    <th>Départ</th>
                    <th>Score</th>
                    <?php if ($hasSeries): ?>
                    <th>Série 1</th>
                    <th>Série 2</th>
                    <?php endif; ?>
                    <?php if ($hasDetail): ?>
                    <?php if ($is3D): ?>
                    <th>11</th>
                    <th>10</th>
                    <th>8</th>
                    <th>5</th>
                    <?php else: ?>
                    <th>20-15</th>
                    <th>20-10</th>
                    <th>15-15</th>
                    <th>15-10</th>
                    <?php endif; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $n = 1; foreach ($rows as $insc):
                    $r = $insc['_resultat'] ?? null;
                    $rowEven = ($n % 2 === 0);
                ?>
                <?php $tdBg = $rowEven ? ' style="background-color: #e9ecef;"' : ''; ?>
                <tr class="<?= $rowEven ? 'edition-scores-row-even' : '' ?>">
                    <td<?= $tdBg ?>><?= $n++ ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['abv_categorie_classement'] ?? $insc['abv_classement'] ?? '-') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['numero_depart'] ?? '-') ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                    <?php if ($hasSeries): ?>
                    <td<?= $tdBg ?>><?= $r ? ($r['serie1_score'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['serie2_score'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                    <?php if ($hasDetail): ?>
                    <?php if ($is3D): ?>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_11'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_10'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_8'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_5'] ?? '-') : '-' ?></td>
                    <?php else: ?>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_20_15'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_20_10'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_15_15'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_15_10'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
