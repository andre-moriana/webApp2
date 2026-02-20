<?php
/** Avis de concours - document officiel */
?>
<div class="edition-avis">
    <h1 class="text-center mb-4">Avis de concours</h1>

    <div class="text-center mb-4">
        <h2><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></h2>
    </div>

    <table class="table table-bordered">
        <tr>
            <th width="35%">Club organisateur</th>
            <td><?= htmlspecialchars($clubName ?: ($concours->club_name ?? 'Non renseigné')) ?></td>
        </tr>
        <tr>
            <th>Discipline</th>
            <td><?= htmlspecialchars($disciplineName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Type de compétition</th>
            <td><?= htmlspecialchars($typeCompetitionName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Niveau championnat</th>
            <td><?= htmlspecialchars($niveauChampionnatName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Lieu</th>
            <td><?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Dates</th>
            <td><?= htmlspecialchars($concours->date_debut ?? '') ?> <?= ($concours->date_debut && $concours->date_fin) ? ' - ' : '' ?> <?= htmlspecialchars($concours->date_fin ?? '') ?></td>
        </tr>
    </table>

    <div class="mt-4">
        <h4>Informations</h4>
        <p>L'inscription à ce concours est obligatoire. Les inscriptions sont à effectuer auprès du club organisateur ou via le lien d'inscription fourni.</p>
    </div>
</div>
