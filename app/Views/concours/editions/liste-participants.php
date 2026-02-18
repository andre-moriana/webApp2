<?php
/** Liste des participants - par club et départ */
?>
<div class="edition-liste-participants">
    <h1 class="text-center mb-4">Liste des participants</h1>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>
    <p class="text-center"><?= htmlspecialchars($concours->date_debut ?? '') ?> — <?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? '') ?></p>

    <div class="mb-3"><strong>Total : <?= count($inscriptions) ?> participant(s)</strong></div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N°</th>
                <th>Nom</th>
                <th>N° Licence</th>
                <th>Club</th>
                <th>Départ</th>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; foreach ($inscriptions as $insc): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_depart'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-4 text-end">
        <p><em>Document généré le <?= date('d/m/Y à H:i') ?></em></p>
    </div>
</div>
