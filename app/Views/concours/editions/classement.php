<?php
/** Classement - tableau trié par classement */
$classement = [];
foreach ($inscriptions as $insc) {
    $inscId = $insc['id'] ?? $insc['_id'] ?? null;
    $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
    $classement[] = [
        'inscription' => $insc,
        'resultat' => $r,
        'classement' => $r ? ($r['classement'] ?? null) : null,
        'score' => $r ? ($r['score'] ?? 0) : 0
    ];
}
usort($classement, function($a, $b) {
    $ca = $a['classement'];
    $cb = $b['classement'];
    if ($ca !== null && $cb !== null) return (int)$ca - (int)$cb;
    if ($ca !== null) return -1;
    if ($cb !== null) return 1;
    return (int)$b['score'] - (int)$a['score'];
});
?>
<div class="edition-classement">
    <h1 class="text-center mb-4">Classement</h1>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>
    <p class="text-center"><?= htmlspecialchars($concours->date_debut ?? '') ?> — <?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? '') ?></p>

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
            <?php $rang = 1; foreach ($classement as $item):
                $insc = $item['inscription'];
                $r = $item['resultat'];
                $pos = $item['classement'] ?? $rang;
                ?>
                <tr>
                    <td><?= $pos ?></td>
                    <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                </tr>
            <?php $rang++; endforeach; ?>
        </tbody>
    </table>

    <div class="mt-4 text-end">
        <p><em>Document généré le <?= date('d/m/Y à H:i') ?></em></p>
    </div>
</div>
