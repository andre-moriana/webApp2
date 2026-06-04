<?php
/**
 * Édition imprimable du plan de cibles (S, T, I, H) ou des pelotons (3, N, C).
 */
$disciplineAbv = $disciplineAbv ?? '';
$plansCible = $plansCibleFeuilles ?? [];
$plansPeloton = $plansPelotonFeuilles ?? [];
$isCible = in_array($disciplineAbv, ['S', 'T', 'I', 'H'], true);
$isPeloton = in_array($disciplineAbv, ['3', 'N', 'C'], true);
$unitLabel = $isPeloton ? 'Peloton' : 'Cible';
$unitsLabel = $isPeloton ? 'Pelotons' : 'Cibles';

$filterDepart = isset($departFeuilles) ? (string)$departFeuilles : 'tout';
$filterCible = isset($cibleFeuilles) ? (string)$cibleFeuilles : 'toutes';
$filterPeloton = isset($pelotonFeuilles) ? (string)$pelotonFeuilles : 'tout';
$filterUnit = $isPeloton ? $filterPeloton : $filterCible;
$filterUnitAllValues = ['tout', 'toutes'];

if ($filterDepart !== '' && $filterDepart !== 'tout') {
    $depNumFilter = (int)$filterDepart;
    if ($isCible && !empty($plansCible) && isset($plansCible[$depNumFilter])) {
        $plansCible = [$depNumFilter => $plansCible[$depNumFilter]];
    }
    if ($isPeloton && !empty($plansPeloton) && isset($plansPeloton[$depNumFilter])) {
        $plansPeloton = [$depNumFilter => $plansPeloton[$depNumFilter]];
    }
}

$categorieParLicence = [];
$clubNomParLicence = [];
$numeroTirParLicence = [];
$piquetParLicence = [];
if (!empty($inscriptions) && is_array($inscriptions)) {
    foreach ($inscriptions as $insc) {
        $lic = trim((string)($insc['numero_licence'] ?? ''));
        if ($lic === '') {
            continue;
        }
        $cat = trim((string)($insc['abv_categorie_classement'] ?? $insc['categorie_classement'] ?? ''));
        if ($cat !== '') {
            $categorieParLicence[$lic] = $cat;
        }
        $clubNom = trim((string)($insc['club_nom'] ?? $insc['club_name'] ?? ''));
        if ($clubNom !== '') {
            $clubNomParLicence[$lic] = $clubNom;
        }
        if (isset($insc['numero_tir']) && $insc['numero_tir'] !== '' && $insc['numero_tir'] !== null) {
            $numeroTirParLicence[$lic] = (int)$insc['numero_tir'];
        }
        if (isset($insc['piquet']) && $insc['piquet'] !== '' && $insc['piquet'] !== null) {
            $piquetParLicence[$lic] = trim((string)$insc['piquet']);
        }
    }
}

$departsRaw = is_object($concours) ? ($concours->departs ?? []) : ($concours['departs'] ?? []);
$departsListConcours = array_values(is_array($departsRaw) ? $departsRaw : (array)$departsRaw);
$departsLabelMap = [];
foreach ($departsListConcours as $d) {
    $num = (int)($d['numero_depart'] ?? 0);
    $dateDep = $d['date_depart'] ?? '';
    $heureGreffe = $d['heure_greffe'] ?? '';
    if ($num && $dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
        $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    $heureGreffe = $heureGreffe ? substr($heureGreffe, 0, 5) : '';
    $departsLabelMap[$num] = trim($dateDep . ($heureGreffe ? ' ' . $heureGreffe : '')) ?: ('Départ ' . $num);
}

$nombreCibles = (int)(is_object($concours) ? ($concours->nombre_cibles ?? 0) : ($concours['nombre_cibles'] ?? 0));
$nombrePelotons = (int)(is_object($concours) ? ($concours->nombre_pelotons ?? $concours->nombre_cibles ?? 0) : ($concours['nombre_pelotons'] ?? $concours['nombre_cibles'] ?? 0));
$nombreArchersCible = (int)(is_object($concours) ? ($concours->nombre_tireurs_par_cibles ?? 4) : ($concours['nombre_tireurs_par_cibles'] ?? 4)) ?: 4;
$nombreArchersPeloton = (int)(is_object($concours) ? ($concours->nombre_archers_par_peloton ?? 4) : ($concours['nombre_archers_par_peloton'] ?? 4)) ?: 4;

$ordrePosition = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

$buildRow = function (array $plan, string $position) use ($categorieParLicence, $clubNomParLicence, $numeroTirParLicence, $piquetParLicence) {
    $lic = trim((string)($plan['numero_licence'] ?? ''));
    $nom = trim((string)($plan['user_nom'] ?? ''));
    $isAssigne = $nom !== '' || $lic !== '';
    return [
        'position' => $position,
        'nom' => $isAssigne ? ($nom !== '' ? $nom : '—') : 'Libre',
        'licence' => $lic,
        'club' => $clubNomParLicence[$lic] ?? trim((string)($plan['club_nom'] ?? $plan['club_name'] ?? '')),
        'categorie' => $categorieParLicence[$lic] ?? trim((string)($plan['abv_categorie_classement'] ?? $plan['categorie_classement'] ?? '')),
        'numero_tir' => isset($plan['numero_tir']) && $plan['numero_tir'] !== '' && $plan['numero_tir'] !== null
            ? (int)$plan['numero_tir']
            : ($numeroTirParLicence[$lic] ?? null),
        'piquet' => $piquetParLicence[$lic] ?? trim((string)($plan['piquet'] ?? '')),
        'blason' => trim((string)($plan['blason'] ?? '')),
        'distance' => trim((string)($plan['distance'] ?? '')),
        'libre' => !$isAssigne,
    ];
};

$sections = [];

if ($isCible && !empty($plansCible)) {
    foreach ($plansCible as $departNum => $plans) {
        if (!is_array($plans)) {
            continue;
        }
        $plansParCible = [];
        foreach ($plans as $p) {
            $nc = (int)($p['numero_cible'] ?? 0);
            if ($nc <= 0) {
                continue;
            }
            if (!isset($plansParCible[$nc])) {
                $plansParCible[$nc] = [];
            }
            $plansParCible[$nc][] = $p;
        }
        $nbUnits = max($nombreCibles, count($plansParCible));
        for ($numUnit = 1; $numUnit <= $nbUnits; $numUnit++) {
            if ($filterUnit !== '' && !in_array($filterUnit, $filterUnitAllValues, true) && (int)$filterUnit !== $numUnit) {
                continue;
            }
            $unitPlans = $plansParCible[$numUnit] ?? [];
            $plansParPosition = [];
            foreach ($unitPlans as $p) {
                $pos = strtoupper(trim((string)($p['position_archer'] ?? '')));
                if ($pos !== '') {
                    $plansParPosition[$pos] = $p;
                }
            }
            $positionsAffichees = !empty($plansParPosition)
                ? array_values(array_unique(array_merge(
                    array_intersect($ordrePosition, array_keys($plansParPosition)),
                    array_diff(array_keys($plansParPosition), $ordrePosition)
                )))
                : array_slice($ordrePosition, 0, max(4, $nombreArchersCible));
            $rows = [];
            foreach ($positionsAffichees as $pos) {
                $plan = $plansParPosition[$pos] ?? [];
                $rows[] = $buildRow(is_array($plan) ? $plan : [], $pos);
            }
            $blason = '';
            $distance = '';
            foreach ($unitPlans as $p) {
                if ($blason === '' && !empty($p['blason'])) {
                    $blason = trim((string)$p['blason']);
                }
                if ($distance === '' && !empty($p['distance'])) {
                    $distance = trim((string)$p['distance']);
                }
            }
            $sections[] = [
                'depart' => (int)$departNum,
                'numero' => $numUnit,
                'rows' => $rows,
                'meta' => array_filter([
                    $blason !== '' ? 'Blason ' . $blason : '',
                    $distance !== '' ? $distance . ' m' : '',
                ]),
            ];
        }
    }
}

if ($isPeloton && !empty($plansPeloton)) {
    foreach ($plansPeloton as $departNum => $plans) {
        if (!is_array($plans)) {
            continue;
        }
        $plansParPeloton = [];
        foreach ($plans as $p) {
            $np = (int)($p['numero_peloton'] ?? 0);
            if ($np <= 0) {
                continue;
            }
            if (!isset($plansParPeloton[$np])) {
                $plansParPeloton[$np] = [];
            }
            $plansParPeloton[$np][] = $p;
        }
        $nbUnits = max($nombrePelotons, count($plansParPeloton));
        for ($numUnit = 1; $numUnit <= $nbUnits; $numUnit++) {
            if ($filterUnit !== '' && !in_array($filterUnit, $filterUnitAllValues, true) && (int)$filterUnit !== $numUnit) {
                continue;
            }
            $unitPlans = $plansParPeloton[$numUnit] ?? [];
            $plansParPosition = [];
            foreach ($unitPlans as $p) {
                $pos = strtoupper(trim((string)($p['position_archer'] ?? '')));
                if ($pos !== '') {
                    $plansParPosition[$pos] = $p;
                }
            }
            $positionsAffichees = !empty($plansParPosition)
                ? array_values(array_unique(array_merge(
                    array_intersect($ordrePosition, array_keys($plansParPosition)),
                    array_diff(array_keys($plansParPosition), $ordrePosition)
                )))
                : array_slice($ordrePosition, 0, max(4, $nombreArchersPeloton));
            $rows = [];
            foreach ($positionsAffichees as $pos) {
                $plan = $plansParPosition[$pos] ?? [];
                $rows[] = $buildRow(is_array($plan) ? $plan : [], $pos);
            }
            $sections[] = [
                'depart' => (int)$departNum,
                'numero' => $numUnit,
                'rows' => $rows,
                'meta' => [],
            ];
        }
    }
}

usort($sections, function ($a, $b) {
    if ($a['depart'] !== $b['depart']) {
        return $a['depart'] - $b['depart'];
    }
    return $a['numero'] - $b['numero'];
});

$sectionsParDepart = [];
foreach ($sections as $section) {
    $d = (int)$section['depart'];
    if (!isset($sectionsParDepart[$d])) {
        $sectionsParDepart[$d] = [];
    }
    $sectionsParDepart[$d][] = $section;
}
ksort($sectionsParDepart);
?>
<div class="edition-plan-pelotons-cibles">
    <h1 class="text-center mb-3">Plan des <?= htmlspecialchars($unitsLabel) ?></h1>
    <?php if (!$isCible && !$isPeloton): ?>
        <p class="alert alert-warning text-center">Discipline non reconnue pour l'édition des plans (cible ou peloton).</p>
    <?php elseif (empty($sectionsParDepart)): ?>
        <p class="alert alert-info text-center">
            Aucun plan <?= $isPeloton ? 'de peloton' : 'de cible' ?> n'a été créé ou aucun résultat pour les filtres sélectionnés.
        </p>
    <?php else: ?>
        <?php foreach ($sectionsParDepart as $departNum => $departSections): ?>
        <?php $depLabel = $departsLabelMap[$departNum] ?? ('Départ ' . $departNum); ?>
        <h2 class="edition-plan-depart-title mt-4 mb-3"><?= htmlspecialchars($depLabel) ?></h2>
        <div class="edition-plan-units-grid">
            <?php foreach ($departSections as $section): ?>
            <div class="edition-plan-unit-card">
                <h3 class="edition-plan-unit-title"><?= htmlspecialchars($unitLabel) ?> <?= (int)$section['numero'] ?></h3>
                <?php if (!empty($section['meta'])): ?>
                    <p class="edition-plan-unit-meta text-muted small mb-2"><?= htmlspecialchars(implode(' — ', $section['meta'])) ?></p>
                <?php endif; ?>
                <table class="table table-bordered table-sm edition-plan-unit-table mb-0">
                    <thead>
                        <tr>
                            <th>Pos.</th>
                            <th>Nom</th>
                            <th>Club</th>
                            <th>Cat.</th>
                            <?php if ($isPeloton): ?><th>Piquet</th><?php endif; ?>
                            <th>N° tir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section['rows'] as $row): ?>
                        <tr class="<?= !empty($row['libre']) ? 'edition-plan-row-libre' : '' ?>">
                            <td class="text-center"><strong><?= htmlspecialchars($row['position']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nom']) ?></td>
                            <td><?= htmlspecialchars($row['club'] !== '' ? $row['club'] : '—') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['categorie'] !== '' ? $row['categorie'] : '—') ?></td>
                            <?php if ($isPeloton): ?>
                            <td class="text-center"><?= htmlspecialchars($row['piquet'] !== '' ? ucfirst($row['piquet']) : '—') ?></td>
                            <?php endif; ?>
                            <td class="text-center"><?= $row['numero_tir'] !== null ? (int)$row['numero_tir'] : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
