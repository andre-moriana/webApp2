<?php
/** Classement - calculé par catégorie de classement, uniquement 1er tir */
$categoriesMap = $categoriesMap ?? [];

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
                        <th>Rang</th>
                        <th>Nom</th>
                        <th>N° Licence</th>
                        <th>Club</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $insc = $item['inscription']; $r = $item['resultat']; ?>
                        <tr>
                            <td><?= $item['rang'] ?></td>
                            <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                            <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                            <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                            <td><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
