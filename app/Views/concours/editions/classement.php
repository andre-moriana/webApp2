<?php
/** Classement - calculé par catégorie de classement, uniquement 1er tir */
$categoriesMap = $categoriesMap ?? [];
$resultatsByLicence = $resultatsByLicence ?? [];
$disciplineAbv = $disciplineAbv ?? null;
// Nature : discipline N/3/C/3D OU présence des scores 20-15, 20-10, etc. OU libellé catégorie (ARC CHASSE, NATURE, 3D)
$hasNatureScores = !empty(array_filter($resultats ?? [], function($r) {
    return isset($r['nb_20_15']) || isset($r['nb_20_10']) || isset($r['nb_15_15']) || isset($r['nb_15_10']);
}));
$hasNatureLabel = false;
foreach ($categoriesMap as $abv => $lb) {
    $abvUpper = strtoupper((string)$abv);
    $lbUpper = strtoupper((string)$lb);
    if (strpos($lbUpper, 'ARC CHASSE') !== false || strpos($lbUpper, 'ARC DROIT') !== false || strpos($lbUpper, 'NATURE') !== false || strpos($lbUpper, '3D') !== false
        || strpos($lbUpper, 'CHASSE') !== false || strpos($abvUpper, 'FAC') !== false || strpos($abvUpper, 'HAD') !== false) {
        $hasNatureLabel = true;
        break;
    }
}
$isNature = ($disciplineAbv && in_array($disciplineAbv, ['N', '3', 'C', '3D'], true)) || $hasNatureScores || $hasNatureLabel;
// P2 : uniquement pour Nature 2×21 cibles (série2 réellement renseignée, pas 0)
$has2x21 = $isNature && !empty(array_filter($resultats ?? [], function($r) {
    $s2 = $r['serie2_score'] ?? null;
    return $s2 !== null && $s2 !== '' && (int)$s2 > 0;
}));

// Ne prendre en compte que le 1er tir (numero_tir = 1 ou null)
$inscriptions1erTir = array_filter($inscriptions, function($insc) {
    $nt = $insc['numero_tir'] ?? null;
    return $nt === null || $nt === '' || (int)$nt === 1;
});

// Grouper par catégorie de classement
$byCategorie = [];
foreach ($inscriptions1erTir as $insc) {
    $cat = trim((string)($insc['categorie_classement'] ?? ''));
    if ($cat === '') $cat = 'Sans catégorie';
    if (!isset($byCategorie[$cat])) {
        $byCategorie[$cat] = [];
    }
    $inscId = $insc['id'] ?? $insc['_id'] ?? null;
    $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
    if ($r === null) {
        $lic = trim((string)($insc['numero_licence'] ?? ''));
        $r = ($lic !== '' && isset($resultatsByLicence)) ? ($resultatsByLicence[$lic] ?? null) : null;
    }
    $byCategorie[$cat][] = [
        'inscription' => $insc,
        'resultat' => $r,
        'score' => $r ? (int)($r['score'] ?? 0) : 0
    ];
}

// Trier les catégories (ordre alphabétique du libellé, "Sans catégorie" en dernier)
uksort($byCategorie, function($a, $b) use ($categoriesMap) {
    if ($a === 'Sans catégorie') return 1;
    if ($b === 'Sans catégorie') return -1;
    $lbA = $categoriesMap[$a] ?? $a;
    $lbB = $categoriesMap[$b] ?? $b;
    return strcasecmp($lbA, $lbB);
});

// Pour chaque catégorie : trier par score décroissant et attribuer les rangs
foreach ($byCategorie as $cat => &$items) {
    usort($items, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    $rang = 1;
    foreach ($items as &$item) {
        $item['rang'] = $rang++;
    }
    unset($item);
}
unset($items);
?>
<div class="edition-classement">
    <h1 class="text-center mb-4">Classement</h1>
    <p class="text-center text-muted small">(1er tir uniquement)</p>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>
    <p class="text-center"><?= htmlspecialchars($concours->date_debut ?? '') ?> — <?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? '') ?></p>

    <?php foreach ($byCategorie as $catAbv => $items): ?>
        <?php $catLabel = $categoriesMap[$catAbv] ?? $catAbv; ?>
        <div class="classement-categorie mb-4 page-break">
            <h3 class="mb-3"><?= htmlspecialchars($catLabel) ?> <?= $catAbv !== 'Sans catégorie' ? '(' . htmlspecialchars($catAbv) . ')' : '' ?></h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <?php if ($isNature): ?>
                            <th>Clt</th>
                            <th>Nom</th>
                            <th>Club</th>
                            <th>Licence</th>
                            <th>Cat.</th>
                            <th>P1</th>
                            <?php if ($has2x21): ?><th>P2</th><?php endif; ?>
                            <th>Total</th>
                            <th>20-15</th>
                            <th>20-10</th>
                            <th>15-15</th>
                            <th>15-10</th>
                        <?php else: ?>
                            <th>Rang</th>
                            <th>Nom</th>
                            <th>N° Licence</th>
                            <th>Club</th>
                            <th>Score</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $insc = $item['inscription']; $r = $item['resultat']; ?>
                        <tr>
                            <?php if ($isNature): ?>
                                <td><?= $item['rang'] ?></td>
                                <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                                <td><?= htmlspecialchars($catAbv !== 'Sans catégorie' ? $catAbv : '') ?></td>
                                <td><?= $r ? (($v = $r['serie1_score'] ?? $r['score'] ?? null) !== null && $v !== '' ? (int)$v : '-') : '-' ?></td>
                                <?php if ($has2x21): ?><td><?= $r ? ($r['serie2_score'] ?? '-') : '-' ?></td><?php endif; ?>
                                <td><?= $r ? (($v = $r['score'] ?? null) !== null && $v !== '' ? (int)$v : '-') : '-' ?></td>
                                <td><?= $r ? ($r['nb_20_15'] ?? '-') : '-' ?></td>
                                <td><?= $r ? ($r['nb_20_10'] ?? '-') : '-' ?></td>
                                <td><?= $r ? ($r['nb_15_15'] ?? '-') : '-' ?></td>
                                <td><?= $r ? ($r['nb_15_10'] ?? '-') : '-' ?></td>
                            <?php else: ?>
                                <td><?= $item['rang'] ?></td>
                                <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                                <td><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
