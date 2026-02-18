<?php
/** Scores - tableau des scores par participant */
$inscriptionsWithScores = [];
foreach ($inscriptions as $insc) {
    $inscId = $insc['id'] ?? $insc['_id'] ?? null;
    $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
    $insc['_resultat'] = $r;
    $inscriptionsWithScores[] = $insc;
}
?>
<div class="edition-scores">
    <h1 class="text-center mb-4">Scores</h1>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N°</th>
                <th>Nom</th>
                <th>Licence</th>
                <th>Club</th>
                <th>Départ</th>
                <th>Score</th>
                <?php if (!empty(array_filter($resultats, function($r) { return isset($r['serie1_score']) || isset($r['serie2_score']); }))): ?>
                <th>Série 1</th>
                <th>Série 2</th>
                <?php endif; ?>
                <?php if (!empty(array_filter($resultats, function($r) { return isset($r['nb_20_15']); }))): ?>
                <th>20-15</th>
                <th>20-10</th>
                <th>15-15</th>
                <th>15-10</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; foreach ($inscriptionsWithScores as $insc):
                $r = $insc['_resultat'] ?? null;
                ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_depart'] ?? '-') ?></td>
                    <td><?= $r ? ($r['score'] ?? '-') : '-' ?></td>
                    <?php if (!empty(array_filter($resultats, function($r) { return isset($r['serie1_score']) || isset($r['serie2_score']); }))): ?>
                    <td><?= $r ? ($r['serie1_score'] ?? '-') : '-' ?></td>
                    <td><?= $r ? ($r['serie2_score'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                    <?php if (!empty(array_filter($resultats, function($r) { return isset($r['nb_20_15']); }))): ?>
                    <td><?= $r ? ($r['nb_20_15'] ?? '-') : '-' ?></td>
                    <td><?= $r ? ($r['nb_20_10'] ?? '-') : '-' ?></td>
                    <td><?= $r ? ($r['nb_15_15'] ?? '-') : '-' ?></td>
                    <td><?= $r ? ($r['nb_15_10'] ?? '-') : '-' ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-4 text-end">
        <p><em>Document généré le <?= date('d/m/Y à H:i') ?></em></p>
    </div>
</div>
