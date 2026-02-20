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

// Filtre type de classement : général (tous), régional (2 premiers chiffres licence = club organisateur), départemental (4 premiers chiffres)
$typeClassement = $typeClassement ?? 'general';
$clubOrganisateurCode = $clubOrganisateurCode ?? '';
if ($typeClassement === 'regional' && strlen($clubOrganisateurCode) >= 2) {
    $prefixOrg = substr($clubOrganisateurCode, 0, 2);
    $inscriptions1erTir = array_filter($inscriptions1erTir, function($insc) use ($prefixOrg) {
        $lic = trim((string)($insc['numero_licence'] ?? ''));
        return $lic !== '' && strlen($lic) >= 2 && substr($lic, 0, 2) === $prefixOrg;
    });
} elseif ($typeClassement === 'departemental' && strlen($clubOrganisateurCode) >= 4) {
    $prefixOrg = substr($clubOrganisateurCode, 0, 4);
    $inscriptions1erTir = array_filter($inscriptions1erTir, function($insc) use ($prefixOrg) {
        $lic = trim((string)($insc['numero_licence'] ?? ''));
        return $lic !== '' && strlen($lic) >= 4 && substr($lic, 0, 4) === $prefixOrg;
    });
}

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

// Pour chaque catégorie : trier par score décroissant, puis en cas d'égalité Nature : 20-15, 20-10, 15-15, 15-10, 15, 10 (décroissant), manqués (croissant)
foreach ($byCategorie as $cat => &$items) {
    usort($items, function($a, $b) use ($isNature) {
        $diff = $b['score'] - $a['score'];
        if ($diff !== 0) return $diff;
        if (!$isNature) return 0;
        $rA = $a['resultat'] ?? [];
        $rB = $b['resultat'] ?? [];
        $tiebreakers = ['nb_20_15', 'nb_20_10', 'nb_15_15', 'nb_15_10', 'nb_15', 'nb_10'];
        foreach ($tiebreakers as $k) {
            $vA = (int)($rA[$k] ?? 0);
            $vB = (int)($rB[$k] ?? 0);
            if ($vA !== $vB) return $vB - $vA;
        }
        $nb0A = (int)($rA['nb_0'] ?? 0);
        $nb0B = (int)($rB['nb_0'] ?? 0);
        return $nb0A - $nb0B;
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
    <?php if ($typeClassement === 'regional'): ?>
    <p class="text-center text-muted small"><strong>Classement régional</strong> — archers dont la licence commence par les 2 mêmes chiffres que le club organisateur</p>
    <?php elseif ($typeClassement === 'departemental'): ?>
    <p class="text-center text-muted small"><strong>Classement départemental</strong> — archers dont la licence commence par les 4 mêmes chiffres que le club organisateur</p>
    <?php endif; ?>
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
