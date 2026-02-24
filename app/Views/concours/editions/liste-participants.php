<?php
/** Liste des participants - tableaux séparés selon le tri (club, départ, catégorie) */
$tri = $triListeParticipants ?? 'club';
$groupes = [];
foreach ($inscriptions as $insc) {
    if ($tri === 'club') {
        $cle = trim((string)($insc['club_nom'] ?? ''));
        if ($cle === '') $cle = '— Club non renseigné';
    } elseif ($tri === 'depart') {
        $nd = $insc['numero_depart'] ?? null;
        $cle = $nd !== null && $nd !== '' ? (string)$nd : '—';
    } else {
        $cle = trim((string)($insc['categorie_libelle'] ?? $insc['categorie_classement'] ?? ''));
        if ($cle === '') $cle = '— Catégorie non renseignée';
    }
    if (!isset($groupes[$cle])) $groupes[$cle] = [];
    $groupes[$cle][] = $insc;
}
// Ordre des groupes : naturel pour départ (numérique), alphabétique pour club et catégorie
if ($tri === 'depart') {
    uksort($groupes, function ($a, $b) {
        if ($a === '—' || $b === '—') return strcmp($a, $b);
        return (int)$a - (int)$b;
    });
} else {
    ksort($groupes, SORT_FLAG_CASE | SORT_STRING);
}
// Dans chaque groupe : ordre alphabétique par nom
$getNom = function ($i) {
    return trim((string)($i['user_nom'] ?? $i['nom'] ?? ''));
};
foreach ($groupes as $cle => $liste) {
    usort($groupes[$cle], function ($a, $b) use ($getNom) {
        return strcasecmp($getNom($a), $getNom($b));
    });
}
?>
<div class="edition-liste-participants">
    <h1 class="text-center mb-4">Liste des participants</h1>
    <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong></p>
    <p class="text-center"><?= htmlspecialchars($concours->date_debut ?? '') ?> — <?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? '') ?></p>

    <div class="mb-3"><strong>Total : <?= count($inscriptions) ?> participant(s)</strong></div>

    <?php foreach ($groupes as $cleGroupe => $liste): ?>
    <div class="liste-participants-groupe mt-4">
        <h5 class="mb-2">
            <?php
            if ($tri === 'club') echo 'Club : ' . htmlspecialchars($cleGroupe) . ' <span class="text-muted">(' . count($liste) . ' participant(s))</span>';
            elseif ($tri === 'depart') echo 'Départ n° ' . htmlspecialchars($cleGroupe) . ' <span class="text-muted">(' . count($liste) . ' participant(s))</span>';
            else echo 'Catégorie : ' . htmlspecialchars($cleGroupe) . ' <span class="text-muted">(' . count($liste) . ' participant(s))</span>';
            ?>
        </h5>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nom</th>
                    <th>N° Licence</th>
                    <th>Club</th>
                    <th>Départ</th>
                    <th>Catégorie</th>
                </tr>
            </thead>
            <tbody>
                <?php $n = 1; foreach ($liste as $insc): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                    <td><?= htmlspecialchars($insc['numero_depart'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($insc['categorie_libelle'] ?? $insc['categorie_classement'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
