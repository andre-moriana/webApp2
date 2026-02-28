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
$isNature = ($disciplineAbv === 'N');
$isCible = in_array($disciplineAbv, ['S', 'T', 'I', 'H'], true);
$isPeloton = in_array($disciplineAbv, ['3', 'N', 'C'], true);

// Filtres d'édition (départ, série et cible)
$filterDepartFeuilles = isset($departFeuilles) ? (string)$departFeuilles : 'tout';
$filterSerieFeuilles = isset($serieFeuilles) ? (string)$serieFeuilles : 'toutes';
$filterCibleFeuilles = isset($cibleFeuilles) ? (string)$cibleFeuilles : 'toutes';

// Appliquer le filtre par départ sur les plans
if ($filterDepartFeuilles !== '' && $filterDepartFeuilles !== 'tout') {
    $depNumFilter = (int)$filterDepartFeuilles;
    if ($isCible && !empty($plansCible) && isset($plansCible[$depNumFilter])) {
        $plansCible = [$depNumFilter => $plansCible[$depNumFilter]];
    }
    if ($isPeloton && !empty($plansPeloton) && isset($plansPeloton[$depNumFilter])) {
        $plansPeloton = [$depNumFilter => $plansPeloton[$depNumFilter]];
    }
}

// Index licence -> abv catégorie de classement (depuis les inscriptions du concours)
$categorieParLicence = [];
$clubNomParLicence = []; // licence -> nom du club (depuis fiche inscription id_club, résolu via base club dans le contrôleur)
if (!empty($inscriptions) && is_array($inscriptions)) {
    foreach ($inscriptions as $insc) {
        $lic = trim((string)($insc['numero_licence'] ?? ''));
        if ($lic !== '') {
            $cat = trim((string)($insc['categorie_classement'] ?? $insc['abv_categorie_classement'] ?? ''));
            if ($cat !== '') {
                $categorieParLicence[$lic] = $cat;
            }
            $clubNom = trim((string)($insc['club_nom'] ?? $insc['club_name'] ?? ''));
            if ($clubNom !== '') {
                $clubNomParLicence[$lic] = $clubNom;
            }
        }
    }
}

// Nombre de volées et de séries pour salle (18m = 10 volées de 3 flèches, 2 séries)
$nbVoleesSalle = 10;
$nbSeriesSalle = 2;

// Nature : 21 volées de 2 flèches, comptage 20-15, 20-10, 15-15, 15-10
$nbVoleesNature = 21;
$nbFlechesNature = 2;

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
                $abvCat = $categorieParLicence[$lic] ?? trim($p['abv_categorie_classement'] ?? $p['categorie_classement'] ?? '');
                $nt = isset($p['numero_tir']) && $p['numero_tir'] !== '' && $p['numero_tir'] !== null ? (int)$p['numero_tir'] : null;
                $clubNom = $clubNomParLicence[$lic] ?? trim($p['club_nom'] ?? $p['club_name'] ?? '');
                $flat[] = [
                    'numero_depart' => (int)($p['numero_depart'] ?? $departNum),
                    'numero_cible' => (int)($p['numero_cible'] ?? 0),
                    'position_archer' => $p['position_archer'] ?? '',
                    'user_nom' => $nom,
                    'numero_licence' => $lic,
                    'abv_categorie_classement' => $abvCat,
                    'numero_tir' => $nt,
                    'club_nom' => $clubNom
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
            $nt = isset($p['numero_tir']) && $p['numero_tir'] !== '' && $p['numero_tir'] !== null ? (int)$p['numero_tir'] : null;
            $clubNom = $clubNomParLicence[$lic] ?? trim($p['club_nom'] ?? $p['club_name'] ?? '');
            if ($nom !== '' || $lic !== '') {
                $flat[] = [
                    'numero_depart' => (int)($p['numero_depart'] ?? $departNum),
                    'numero_peloton' => (int)($p['numero_peloton'] ?? 0),
                    'position_archer' => $p['position_archer'] ?? '',
                    'user_nom' => $nom,
                    'numero_licence' => $lic,
                    'abv_categorie_classement' => $categorieParLicence[$lic] ?? trim($p['abv_categorie_classement'] ?? $p['categorie_classement'] ?? ''),
                    'numero_tir' => $nt,
                    'club_nom' => $clubNom
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

// Ordre des positions blason : tableau 1 = A, tableau 2 = B, etc.
$positionsBlasonOrdre = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

// Construire les "feuilles" : une feuille par cible, 4 tableaux par page. Page 1 = A,B,C,D, page 2 = E,F,G,H (pages séparées).
$feuillesSalle = [];
if ($isSalle) {
    $departDefaut = 1;
    if (!empty($departsList)) {
        $first = is_array($departsList[0] ?? null) ? ($departsList[0]['numero_depart'] ?? 1) : ($departsList[0]->numero_depart ?? 1);
        $departDefaut = (int)$first ?: 1;
    }
    $archerVide = ['user_nom' => '', 'numero_licence' => '', 'categorie_classement' => '', 'position_archer' => '', 'numero_cible' => 0, 'depart' => $departDefaut, 'numero_tir' => null];
    $nbSlotsParPage = 4;

    if (empty($archersParCible)) {
        // Aucun archer affecté : une feuille avec 4 emplacements vides (A, B, C, D)
        $cibleVide = ($filterCibleFeuilles !== '' && $filterCibleFeuilles !== 'tout') ? (int)$filterCibleFeuilles : 1;
        $archersOrdre = [];
        for ($idx = 0; $idx < $nbSlotsParPage; $idx++) {
            $archersOrdre[] = array_merge($archerVide, ['depart' => $departDefaut, 'numero_cible' => $cibleVide, 'position_archer' => $positionsBlasonOrdre[$idx]]);
        }
        $feuillesSalle[] = ['depart' => $departDefaut, 'cible' => $cibleVide, 'archers' => $archersOrdre];
    } else {
        foreach ($archersParCible as $g) {
            $dep = (int)($g['depart'] ?? $departDefaut);
            $numCible = (int)($g['cible'] ?? 0);
            $archers = $g['archers'];
            foreach ($archers as $i => $a) {
                $archers[$i] = array_merge($a, ['depart' => $dep, 'numero_cible' => $numCible]);
            }
            // Construire la liste ordonnée sur 8 slots (A à H), puis découper en 2 pages de 4 : ABCD et EFGH
            $byPosition = [];
            $sansPosition = [];
            foreach ($archers as $a) {
                $pos = strtoupper(trim($a['position_archer'] ?? ''));
                if ($pos !== '' && in_array($pos, $positionsBlasonOrdre, true) && !isset($byPosition[$pos])) {
                    $byPosition[$pos] = $a;
                } else {
                    $sansPosition[] = $a;
                }
            }
            $idxSans = 0;
            $listeComplete = [];
            for ($idx = 0; $idx < 8; $idx++) {
                $lettre = $positionsBlasonOrdre[$idx];
                if (isset($byPosition[$lettre])) {
                    $listeComplete[] = $byPosition[$lettre];
                } elseif ($idxSans < count($sansPosition)) {
                    $a = $sansPosition[$idxSans++];
                    $listeComplete[] = array_merge($a, ['position_archer' => $lettre]);
                } else {
                    $listeComplete[] = array_merge($archerVide, ['depart' => $dep, 'numero_cible' => $numCible, 'position_archer' => $lettre]);
                }
            }
            // Page 1 = ABCD. Page 2 = EFGH uniquement si au moins 5 archers sur la cible.
            $feuillesSalle[] = ['depart' => $dep, 'cible' => $numCible, 'archers' => array_slice($listeComplete, 0, 4)];
            if (count($archers) >= 5) {
                $feuillesSalle[] = ['depart' => $dep, 'cible' => $numCible, 'archers' => array_slice($listeComplete, 4, 4)];
            }
        }
    }
}

// Feuilles Nature : même principe que Salle mais 21 volées x 2 flèches, archers du plan peloton, une feuille par peloton
$feuillesNature = [];
if ($isNature) {
    $departDefaut = 1;
    if (!empty($departsList)) {
        $first = is_array($departsList[0] ?? null) ? ($departsList[0]['numero_depart'] ?? 1) : ($departsList[0]->numero_depart ?? 1);
        $departDefaut = (int)$first ?: 1;
    }
    $archerVideNature = ['user_nom' => '', 'numero_licence' => '', 'abv_categorie_classement' => '', 'position_archer' => '', 'numero_peloton' => 0, 'depart' => $departDefaut];
    $nbSlotsParPageNature = 2;

    if (empty($archersParPeloton)) {
        $pelotonVide = 1;
        $archersOrdre = [];
        for ($idx = 0; $idx < $nbSlotsParPageNature; $idx++) {
            $archersOrdre[] = array_merge($archerVideNature, ['depart' => $departDefaut, 'numero_peloton' => $pelotonVide, 'position_archer' => $positionsBlasonOrdre[$idx]]);
        }
        $feuillesNature[] = ['depart' => $departDefaut, 'peloton' => $pelotonVide, 'archers' => $archersOrdre];
    } else {
        foreach ($archersParPeloton as $g) {
            $dep = (int)($g['depart'] ?? $departDefaut);
            $numPeloton = (int)($g['peloton'] ?? 0);
            $archers = $g['archers'];
            foreach ($archers as $i => $a) {
                $archers[$i] = array_merge($a, ['depart' => $dep, 'numero_peloton' => $numPeloton]);
            }
            $byPosition = [];
            $sansPosition = [];
            foreach ($archers as $a) {
                $pos = strtoupper(trim($a['position_archer'] ?? ''));
                if ($pos !== '' && in_array($pos, $positionsBlasonOrdre, true) && !isset($byPosition[$pos])) {
                    $byPosition[$pos] = $a;
                } else {
                    $sansPosition[] = $a;
                }
            }
            $idxSans = 0;
            $listeComplete = [];
            for ($idx = 0; $idx < 8; $idx++) {
                $lettre = $positionsBlasonOrdre[$idx];
                if (isset($byPosition[$lettre])) {
                    $listeComplete[] = $byPosition[$lettre];
                } elseif ($idxSans < count($sansPosition)) {
                    $a = $sansPosition[$idxSans++];
                    $listeComplete[] = array_merge($a, ['position_archer' => $lettre]);
                } else {
                    $listeComplete[] = array_merge($archerVideNature, ['depart' => $dep, 'numero_peloton' => $numPeloton, 'position_archer' => $lettre]);
                }
            }
            // Nature : 2 archers par page (A,B puis C,D etc.), au plus 4 pages
            $nbPagesNature = min(4, max(1, (int)ceil(count($archers) / $nbSlotsParPageNature)));
            for ($p = 0; $p < $nbPagesNature; $p++) {
                $feuillesNature[] = ['depart' => $dep, 'peloton' => $numPeloton, 'archers' => array_slice($listeComplete, $p * $nbSlotsParPageNature, $nbSlotsParPageNature)];
            }
        }
    }
}
?>
<div class="edition-feuilles-marques">
    <?php if ($isSalle && !empty($feuillesSalle)): ?>
        <?php
        $seriesAffichees = ($filterSerieFeuilles !== '' && $filterSerieFeuilles !== 'toutes')
            ? [max(1, min((int)$filterSerieFeuilles, $nbSeriesSalle))]
            : range(1, $nbSeriesSalle);
        ?>
        <?php foreach ($feuillesSalle as $f): ?>
            <?php foreach ($seriesAffichees as $numSerie): ?>
                <div class="feuille-marque-salle feuille-marque-salle-landscape mb-4 page-break">
                    <div class="feuille-marque-salle-grid">
                    <?php foreach ($f['archers'] as $archer): ?>
                        <div class="feuille-marque-archer-block">
                            <div class="feuille-marque-archer-header border-bottom pb-1 mb-2">
                                <div class="d-flex justify-content-between align-items-center"><span><strong><?= htmlspecialchars($archer['user_nom'] ?: '—') ?></strong></span><span class="feuille-marque-blason text-nowrap" style="font-size: 1.15em;"><strong>Cible n° <?= sprintf('%02d', (int)($f['cible'] ?? 0)) ?><?= htmlspecialchars(trim($archer['position_archer'] ?? '') ?: '') ?></strong></span></div>
                                <div class="d-flex justify-content-between align-items-center"><span><?= htmlspecialchars($archer['club_nom'] ?? $archer['club_name'] ?? '—') ?></span><span class="feuille-marque-categorie"><?= htmlspecialchars($archer['abv_categorie_classement'] ?? '') ?: '—' ?></span></div>
                                <div class="mb-1"></div>
                                <div class="d-flex justify-content-between align-items-center"><span>N° licence : <?= htmlspecialchars($archer['numero_licence'] ?: '—') ?></span><span>N° départ <?= (int)($f['depart'] ?? $archer['depart'] ?? 0) ?> — N° tir <?= isset($archer['numero_tir']) && $archer['numero_tir'] !== '' && $archer['numero_tir'] !== null ? (int)$archer['numero_tir'] : '—' ?></span></div>
                            </div>
                            <table class="table table-bordered table-sm feuille-marque-table-volees">
                                <thead>
                                    <tr>
                                        <th rowspan="2">N°</th>
                                        <th colspan="3">Points par flèche</th>
                                        <th rowspan="2">Total 3 flèches</th>
                                        <th rowspan="2">total cumulé</th>
                                    </tr>
                                    <tr>
                                        <th style="width:16%">1</th>
                                        <th style="width:16%">2</th>
                                        <th style="width:16%">3</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($v = 1; $v <= $nbVoleesSalle; $v++):
                                        $rowEven = ($v % 2 === 0);
                                        $tdBgSalle = $rowEven ? ' style="background-color: #e9ecef;"' : '';
                                    ?>
                                    <tr>
                                        <td<?= $tdBgSalle ?>><?= $v ?></td>
                                        <td<?= $tdBgSalle ?>></td>
                                        <td<?= $tdBgSalle ?>></td>
                                        <td<?= $tdBgSalle ?>></td>
                                        <td<?= $tdBgSalle ?>></td>
                                        <td<?= $tdBgSalle ?>></td>
                                    </tr>
                                    <?php endfor; ?>
                                    <tr class="table-secondary feuille-marque-ligne-resume">
                                        <td colspan="2"><strong>Nbre 10</strong></td>
                                        <td colspan="2"><strong>Nbre  9</strong></td>
                                        <td colspan="2"><strong>Tot. série</strong></td>
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

    <?php elseif ($isNature && !empty($feuillesNature)): ?>
        <?php
            $logoBgStyleNature = '';
            if (!empty($clubLogoUrl)) {
                $logoEsc = htmlspecialchars($clubLogoUrl, ENT_QUOTES, 'UTF-8');
                $logoBgStyleNature = "background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url('" . $logoEsc . "') no-repeat center; background-size: 35%;";
            }
        ?>
        <?php foreach ($feuillesNature as $f): ?>
                <div class="feuille-marque-nature feuille-marque-salle-landscape mb-4 page-break">
                    <div class="feuille-marque-nature-grid">
                    <?php foreach ($f['archers'] as $archer): ?>
                        <div class="feuille-marque-archer-block">
                            <div class="feuille-marque-archer-header border-bottom pb-1 mb-2 d-flex justify-content-between align-items-start">
                                <div class="d-flex justify-content-between align-items-center"><span><strong><?= htmlspecialchars($archer['user_nom'] ?: '—') ?></strong></span><span class="feuille-marque-blason text-nowrap" style="font-size: 1.15em;"><strong>N° peloton : <?= (int)($f['peloton'] ?? 0) ?></strong></span></div>
                                <div class="d-flex justify-content-between align-items-center"><span><?= htmlspecialchars($archer['club_nom'] ?? $archer['club_name'] ?? '—') ?></span><span class="feuille-marque-categorie"><?= htmlspecialchars($archer['abv_categorie_classement'] ?? '') ?: '—' ?></span></div>
                                <div class="mb-1"></div>
                                <div class="d-flex justify-content-between align-items-center"><span>N° licence : <?= htmlspecialchars($archer['numero_licence'] ?: '—') ?></span><span>N° départ <?= (int)($f['depart'] ?? $archer['depart'] ?? 0) ?> — N° tir <?= isset($archer['numero_tir']) && $archer['numero_tir'] !== '' && $archer['numero_tir'] !== null ? (int)$archer['numero_tir'] : '—' ?></span></div>
                            </div>
                            <?php if ($logoBgStyleNature !== ''): ?><div class="feuille-marque-table-nature-logo-wrap" style="<?= $logoBgStyleNature ?>"><?php endif; ?>
                            <table class="table table-bordered table-sm feuille-marque-table-volees feuille-marque-table-nature<?= $logoBgStyleNature !== '' ? ' has-logo-bg' : '' ?>">
                                <thead>
                                    <tr>
                                        <th >N° cible</th>
                                        <th colspan="3">Flèche 1</th>
                                        <th colspan="3">Flèche 2</th>
                                        <th >Total</th>
                                        <th >Cumul</th>
                                        <th >20-15</th>
                                        <th >20-10</th>
                                        <th >15-15</th>
                                        <th >15-10</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($v = 1; $v <= $nbVoleesNature; $v++):
                                        $rowEven = ($v % 2 === 0);
                                        $tdBgNature = $rowEven ? ' style="background-color: #e9ecef;"' : '';
                                    ?>
                                    <tr class="<?= $rowEven ? 'feuille-marque-row-even' : '' ?>">
                                        <td<?= $tdBgNature ?>><?= $v ?></td>
                                        <td<?= $tdBgNature ?>>20</td>
                                        <td<?= $tdBgNature ?>>15</td>
                                        <td<?= $tdBgNature ?>>0</td>
                                        <td<?= $tdBgNature ?>>15</td>
                                        <td<?= $tdBgNature ?>>10</td>
                                        <td<?= $tdBgNature ?>>0</td>
                                        <td<?= $tdBgNature ?>></td>
                                        <td<?= $tdBgNature ?>></td>
                                        <td<?= $tdBgNature ?>></td>
                                        <td<?= $tdBgNature ?>></td>
                                        <td<?= $tdBgNature ?>></td>
                                        <td<?= $tdBgNature ?>></td>
                                    </tr>
                                    <?php endfor; ?>
                                    <tr class="table-secondary feuille-marque-ligne-resume">
                                        <td colspan="8"><strong>Total des cibles</strong></td>
                                        <td></td>
                                        <td></td>   
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php if ($logoBgStyleNature !== ''): ?></div><?php endif; ?>
                            <div class="feuille-marque-signatures row g-2 small mt-2">
                                <div class="col-6">Signature du marqueur </div>
                                <div class="col-6">Signature de l'archer </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
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
