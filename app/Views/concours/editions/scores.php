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

$hasSeries = !empty(array_filter($resultats, function ($r) { return isset($r['serie1_score']) || isset($r['serie2_score']); }));
$hasDetail = !empty(array_filter($resultats, function ($r) { return isset($r['nb_20_15']); }));
?>
<div class="edition-scores">
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
    <div class="edition-scores-block mb-4">
        <h2 class="h5 mb-2 mt-3"><?= htmlspecialchars($groupLabel) ?> : <?= htmlspecialchars($libelle) ?></h2>
        <table class="table table-bordered edition-scores-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nom</th>
                    <th>Licence</th>
                    <th>Club</th>
                    <th>Catégorie</th>
                    <th>Départ</th>
                    <th>Score</th>
                    <?php if ($hasSeries): ?>
                    <th>Série 1</th>
                    <th>Série 2</th>
                    <?php endif; ?>
                    <?php if ($hasDetail): ?>
                    <th>20-15</th>
                    <th>20-10</th>
                    <th>15-15</th>
                    <th>15-10</th>
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
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['categorie_libelle'] ?? $insc['categorie_classement'] ?? '-') ?></td>
                    <td<?= $tdBg ?>><?= htmlspecialchars($insc['numero_depart'] ?? '-') ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                    <?php if ($hasSeries): ?>
                    <td<?= $tdBg ?>><?= $r ? ($r['serie1_score'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['serie2_score'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                    <?php if ($hasDetail): ?>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_20_15'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_20_10'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_15_15'] ?? '-') : '-' ?></td>
                    <td<?= $tdBg ?>><?= $r ? ($r['nb_15_10'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
