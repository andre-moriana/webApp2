<?php
/**
 * Feuilles de marques - adaptées à la discipline du concours.
 * Plan de cible (S, T, I, H) ou plan peloton (3, N, C) pour affecter les archers.
 * Discipline Salle : une feuille par série, min 4 archers par page, tableau par archer (flèches, total volée, cumul, 10/9, total série, signatures).
 */
$departs = $departsList ?? [];
if (empty($departs)) {
    $departs = [['numero_depart' => 1, 'date_depart' => '', 'heure_greffe' => '']];
}
$getD = function ($d, $key, $default = '') {
    return is_array($d) ? ($d[$key] ?? $default) : ($d->$key ?? $default);
};

$disciplineAbv = $disciplineAbv ?? '';
$plansCible = $plansCibleFeuilles ?? [];
$plansPeloton = $plansPelotonFeuilles ?? [];
$isSalle = ($disciplineAbv === 'S');
$isCible = in_array($disciplineAbv, ['S', 'T', 'I', 'H'], true);
$isPeloton = in_array($disciplineAbv, ['3', 'N', 'C'], true);

// Filtres d'édition (série et cible)
$filterSerieFeuilles = isset($serieFeuilles) ? (string)$serieFeuilles : 'tout';
$filterCibleFeuilles = isset($cibleFeuilles) ? (string)$cibleFeuilles : 'tout';

// Nombre de volées et de séries pour salle (18m = 10 volées de 3 flèches, 2 séries)
$nbVoleesSalle = 10;
$nbSeriesSalle = 2;

// Extraire les archers assignés du plan cible, groupés par (depart, cible)
$archersParCible = [];
if ($isCible && !empty($plansCible)) {
    $flat = [];
    foreach ($plansCible as $departNum => $plans) {
        if (!is_array($plans)) continue;
        foreach ($plans as $p) {
            $nom = trim($p['user_nom'] ?? '');
            $lic = trim($p['numero_licence'] ?? '');
            if ($nom !== '' || $lic !== '') {
                $flat[] = [
                    'numero_depart' => (int)($p['numero_depart'] ?? $departNum),
                    'numero_cible' => (int)($p['numero_cible'] ?? 0),
                    'position_archer' => $p['position_archer'] ?? '',
                    'user_nom' => $nom,
                    'numero_licence' => $lic
                ];
            }
        }
    }
    foreach ($flat as $a) {
        $k = $a['numero_depart'] . '_' . $a['numero_cible'];
        if (!isset($archersParCible[$k])) $archersParCible[$k] = ['depart' => $a['numero_depart'], 'cible' => $a['numero_cible'], 'archers' => []];
        $archersParCible[$k]['archers'][] = $a;
    }
    ksort($archersParCible);
    // Filtre par cible : ne garder que la cible sélectionnée si différent de TOUT
    if ($filterCibleFeuilles !== '' && $filterCibleFeuilles !== 'tout') {
        $cibleNum = (int)$filterCibleFeuilles;
        $archersParCible = array_filter($archersParCible, function ($g) use ($cibleNum) {
            return ((int)($g['cible'] ?? 0)) === $cibleNum;
        });
    }
}

// Extraire les archers du plan peloton, groupés par (depart, peloton)
$archersParPeloton = [];
if ($isPeloton && !empty($plansPeloton)) {
    $flat = [];
    foreach ($plansPeloton as $departNum => $plans) {
        if (!is_array($plans)) continue;
        foreach ($plans as $p) {
            $nom = trim($p['user_nom'] ?? '');
            $lic = trim($p['numero_licence'] ?? '');
            if ($nom !== '' || $lic !== '') {
                $flat[] = [
                    'numero_depart' => (int)($p['numero_depart'] ?? $departNum),
                    'numero_peloton' => (int)($p['numero_peloton'] ?? 0),
                    'position_archer' => $p['position_archer'] ?? '',
                    'user_nom' => $nom,
                    'numero_licence' => $lic
                ];
            }
        }
    }
    foreach ($flat as $a) {
        $k = $a['numero_depart'] . '_' . $a['numero_peloton'];
        if (!isset($archersParPeloton[$k])) $archersParPeloton[$k] = ['depart' => $a['numero_depart'], 'peloton' => $a['numero_peloton'], 'archers' => []];
        $archersParPeloton[$k]['archers'][] = $a;
    }
    ksort($archersParPeloton);
}

// Construire les "feuilles" : une feuille par cible (tous les archers d'une feuille sont affectés à la même cible). 4 ou 8 tableaux par page.
$feuillesSalle = [];
if ($isSalle) {
    $departDefaut = 1;
    if (!empty($departsList)) {
        $first = is_array($departsList[0] ?? null) ? ($departsList[0]['numero_depart'] ?? 1) : ($departsList[0]->numero_depart ?? 1);
        $departDefaut = (int)$first ?: 1;
    }
    $archerVide = ['user_nom' => '', 'numero_licence' => '', 'numero_cible' => 0, 'depart' => $departDefaut];
    $maxParFeuille = 8;

    if (empty($archersParCible)) {
        // Aucun archer affecté : une feuille avec 4 emplacements vides (cible = filtre ou 1)
        $cibleVide = ($filterCibleFeuilles !== '' && $filterCibleFeuilles !== 'tout') ? (int)$filterCibleFeuilles : 1;
        $feuillesSalle[] = ['depart' => $departDefaut, 'cible' => $cibleVide, 'archers' => array_fill(0, 4, $archerVide)];
    } else {
        // Une feuille par cible (par groupe depart+cible) : on ne mélange jamais deux cibles sur une même page
        foreach ($archersParCible as $g) {
            $dep = (int)($g['depart'] ?? $departDefaut);
            $numCible = (int)($g['cible'] ?? 0);
            $archers = $g['archers'];
            foreach ($archers as $i => $a) {
                $archers[$i] = array_merge($a, ['depart' => $dep, 'numero_cible' => $numCible]);
            }
            $chunk = [];
            foreach ($archers as $a) {
                $chunk[] = $a;
                if (count($chunk) >= 4) {
                    $feuillesSalle[] = ['depart' => $dep, 'cible' => $numCible, 'archers' => $chunk];
                    $chunk = [];
                } elseif (count($chunk) >= $maxParFeuille) {
                    $feuillesSalle[] = ['depart' => $dep, 'cible' => $numCible, 'archers' => $chunk];
                    $chunk = [];
                }
            }
            if (!empty($chunk)) {
                $feuillesSalle[] = ['depart' => $dep, 'cible' => $numCible, 'archers' => $chunk];
            }
        }
    }

    // Compléter chaque feuille à 4 ou 8 tableaux
    foreach ($feuillesSalle as $i => $f) {
        $n = count($feuillesSalle[$i]['archers']);
        $cible = 4;
        if ($n > 4) $cible = 8;
        $numCible = (int)($f['cible'] ?? 0);
        while (count($feuillesSalle[$i]['archers']) < $cible) {
            $feuillesSalle[$i]['archers'][] = array_merge($archerVide, ['depart' => $f['depart'], 'numero_cible' => $numCible]);
        }
    }
}
?>
<div class="edition-feuilles-marques">
    <?php if ($isSalle && !empty($feuillesSalle)): ?>
        <?php
        $seriesAffichees = ($filterSerieFeuilles !== '' && $filterSerieFeuilles !== 'tout')
            ? [max(1, min((int)$filterSerieFeuilles, $nbSeriesSalle))]
            : range(1, $nbSeriesSalle);
        ?>
        <?php foreach ($feuillesSalle as $f): ?>
            <?php foreach ($seriesAffichees as $numSerie): ?>
                <div class="feuille-marque-salle feuille-marque-salle-landscape mb-4 page-break">
                    <p class="text-center mb-3"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong> — Départ <?= (int)$f['depart'] ?> — Cible <?= (int)($f['cible'] ?? 0) ?> — Série <?= $numSerie ?></p>

                    <div class="feuille-marque-salle-grid">
                    <?php foreach ($f['archers'] as $archer): ?>
                        <div class="feuille-marque-archer-block">
                            <div class="feuille-marque-archer-header border-bottom pb-1 mb-2">
                                <strong><?= htmlspecialchars($archer['user_nom'] ?: '—') ?></strong><br>N° licence : <?= htmlspecialchars($archer['numero_licence'] ?: '—') ?>
                            </div>
                            <table class="table table-bordered table-sm feuille-marque-table-volees">
                                <thead>
                                    <tr>
                                        <th rowspan="2">N°</th>
                                        <th colspan="3">Flèches</th>
                                        <th rowspan="2">Total</th>
                                        <th rowspan="2">Cumul</th>
                                    </tr>
                                    <tr>
                                        <th style="width:16%">1</th>
                                        <th style="width:16%">2</th>
                                        <th style="width:16%">3</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($v = 1; $v <= $nbVoleesSalle; $v++): ?>
                                    <tr>
                                        <td><?= $v ?></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php endfor; ?>
                                    <tr class="table-secondary feuille-marque-ligne-resume">
                                        <td colspan="2"><strong>Nbre 10</strong></td>
                                        <td colspan="2"><strong>Nbre  9</strong></td>
                                        <td colspan="2"><strong>Total général</strong></td>
                                    </tr>
                                    <tr class="feuille-marque-ligne-resume-valeurs">
                                        <td colspan="2"></td>
                                        <td colspan="2"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="feuille-marque-signatures row g-2 small mt-2">
                                <div class="col-6">Signature du marqueur </div>
                                <div class="col-6">Signature de l'archer </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

    <?php elseif ($isPeloton && !empty($archersParPeloton)): ?>
        <?php foreach ($archersParPeloton as $g): ?>
            <div class="feuille-marque mb-4 page-break">
                <h2 class="text-center mb-3">Feuille de marques</h2>
                <p class="text-center"><strong><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '') ?></strong> — Départ <?= (int)$g['depart'] ?> — Peloton <?= (int)$g['peloton'] ?></p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nom</th>
                            <th>Licence</th>
                            <th>Score</th>
                            <th>Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['archers'] as $i => $a): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($a['user_nom'] ?? '') ?></td>
                            <td><?= htmlspecialchars($a['numero_licence'] ?? '') ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
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
                        $inscDepart = array_filter($inscriptions, function ($i) use ($num) {
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
    <?php endif; ?>
</div>
