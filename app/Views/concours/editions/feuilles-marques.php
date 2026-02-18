<?php
/** Feuilles de marques - une feuille par cible ou par peloton */
$departs = $departsList;
if (empty($departs)) {
    $departs = [['numero_depart' => 1, 'date_depart' => '', 'heure_greffe' => '']];
}
$getD = function($d, $key, $default = '') {
    return is_array($d) ? ($d[$key] ?? $default) : ($d->$key ?? $default);
};
?>
<div class="edition-feuilles-marques">
    <?php foreach ($departs as $idx => $d): ?>
        <?php
        $num = (int)$getD($d, 'numero_depart', $idx + 1);
        $dateDep = $getD($d, 'date_depart', '');
        $heureGreffe = $getD($d, 'heure_greffe', '');
        if ($dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
            $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        $heureGreffe = $heureGreffe ? substr((string)$heureGreffe, 0, 5) : '';
        $label = trim($dateDep . ($heureGreffe ? ' à ' . $heureGreffe : ''));
        if (empty($label)) $label = 'Départ ' . $num;
        ?>
        <div class="feuille-marque mb-4 page-break">
            <h2 class="text-center mb-3">Feuille de marques</h2>
            <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong> — <?= htmlspecialchars($label) ?></p>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nom</th>
                        <th>Licence</th>
                        <th>Club</th>
                        <th>Score</th>
                        <th>Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $inscDepart = array_filter($inscriptions, function($i) use ($num) {
                        $n = $i['numero_depart'] ?? null;
                        return ($n !== null && $n !== '') ? (int)$n === (int)$num : ($num == 1 && empty($i['numero_depart']));
                    });
                    $inscDepart = array_values($inscDepart);
                    if (empty($inscDepart)) {
                        for ($i = 1; $i <= 8; $i++): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor;
                    } else {
                        foreach ($inscDepart as $i => $insc): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($insc['user_nom'] ?? $insc['nom'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['numero_licence'] ?? '') ?></td>
                                <td><?= htmlspecialchars($insc['club_nom'] ?? '') ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endforeach;
                        $rest = 8 - count($inscDepart);
                        for ($i = 0; $i < $rest && $i < 8; $i++): ?>
                            <tr>
                                <td><?= count($inscDepart) + $i + 1 ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
