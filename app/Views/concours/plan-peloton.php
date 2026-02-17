<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<link href="/public/assets/css/plan-cible.css" rel="stylesheet">
<link href="/public/assets/css/plan-peloton.css" rel="stylesheet">

<div class="container-fluid concours-create-container" data-can-edit-plan="<?= !empty($canEditPlan) ? '1' : '0' ?>">
<h1>Plan de peloton - <?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><strong>Erreur:</strong> <?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><strong>Succès:</strong> <?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php
$getArcherDisplayInfo = function($plan, $inscriptionsMap = []) {
    $numeroLicence = $plan['numero_licence'] ?? null;
    $userNom = $plan['user_nom'] ?? null;
    if ($numeroLicence && isset($inscriptionsMap[$numeroLicence])) {
        $i = $inscriptionsMap[$numeroLicence];
        $nom = $i['user_nom'] ?? $i['nom'] ?? $i['name'] ?? '';
        return ['nom' => $nom, 'club' => $i['club_name'] ?? $i['clubName'] ?? '', 'nomComplet' => $nom];
    }
    if ($userNom) return ['nom' => $userNom, 'club' => '', 'nomComplet' => $userNom];
    return null;
};

// Vérifier si un archer est compound (arc à poulies) via inscription
$isCompound = function($numeroLicence, $inscriptionsMap = []) {
    if (!$numeroLicence || !isset($inscriptionsMap[$numeroLicence])) return false;
    $i = $inscriptionsMap[$numeroLicence];
    $arme = strtolower(trim($i['arme'] ?? ''));
    $cat = strtoupper(trim($i['categorie_classement'] ?? ''));
    if (!empty($arme) && (strpos($arme, 'poulies') !== false || strpos($arme, 'compound') !== false)) return true;
    if (!empty($cat) && substr($cat, -2) === 'CO') return true;
    return false;
};

$nombrePelotons = $concours->nombre_pelotons ?? $concours->nombre_cibles ?? 0;
$nombreDepart = $concours->nombre_depart ?? 1;
$nombreArchersParPeloton = $concours->nombre_archers_par_peloton ?? $concours->nombre_tireurs_par_cibles ?? 0;
$concoursId = $concours->id ?? $concours->_id ?? null;
$piquetColors = ['rouge' => '#ffe0e0', 'bleu' => '#e0e8ff', 'blanc' => '#f5f5f5'];
?>

<?php if (empty($plans)): ?>
    <div class="alert alert-info">
        <p>Aucun plan de peloton n'a été créé pour ce concours.</p>
        <?php if (!empty($canEditPlan)): ?>
        <button type="button" class="btn btn-primary" id="btn-create-plan-peloton-empty" data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                data-nombre-pelotons="<?= (int)$nombrePelotons ?>"
                data-nombre-depart="<?= (int)$nombreDepart ?>"
                data-nombre-archers="<?= (int)$nombreArchersParPeloton ?>">
            <i class="fas fa-users"></i> Créer le plan de peloton
        </button>
        <?php endif; ?>
        <div id="plan-peloton-create-message" style="margin-top: 10px;"></div>
    </div>
<?php else: ?>
    <div class="plan-cible-legend">
        <h4><i class="fas fa-info-circle"></i> Règles</h4>
        <p>Max 50% d'archers du même club par peloton (ex: 2 pour 4, 3 pour 6). Max 2 couleurs de piquet par peloton.</p>
        <div class="legend-items">
            <div class="legend-item"><div class="legend-color assigne"></div><span>Position assignée</span></div>
            <div class="legend-item"><div class="legend-color libre"></div><span>Position libre</span></div>
        </div>
    </div>

    <?php
    $departsList = is_object($concours) ? ($concours->departs ?? []) : ($concours['departs'] ?? []);
    $departsLabelMap = [];
    foreach ($departsList as $d) {
        $num = (int)($d['numero_depart'] ?? 0);
        $dateDep = $d['date_depart'] ?? '';
        $heureGreffe = $d['heure_greffe'] ?? '';
        if ($num && $dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
            $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
        }
        $heureGreffe = $heureGreffe ? substr($heureGreffe, 0, 5) : '';
        $departsLabelMap[$num] = trim($dateDep . ($heureGreffe ? ' ' . $heureGreffe : '')) ?: 'Départ ' . $num;
    }
    ?>
    <?php foreach ($plans as $numeroDepart => $departPlans): ?>
        <?php
        $plansParPeloton = [];
        foreach ($departPlans as $plan) {
            $pel = $plan['numero_peloton'] ?? 0;
            if (!isset($plansParPeloton[$pel])) $plansParPeloton[$pel] = [];
            $plansParPeloton[$pel][] = $plan;
        }
        $nbPelotons = max($nombrePelotons, count($plansParPeloton));
        ?>
        <div class="plan-depart-section" style="margin-bottom: 40px;">
            <h2><i class="fas fa-flag"></i> <?= htmlspecialchars($departsLabelMap[$numeroDepart] ?? 'Départ ' . $numeroDepart) ?></h2>
            <div class="plan-cible-container">
                <div class="plan-cible-scroll plan-peloton-scroll">
                    <?php for ($numeroPeloton = 1; $numeroPeloton <= $nbPelotons; $numeroPeloton++): ?>
                        <?php
                        $pelotonPlans = $plansParPeloton[$numeroPeloton] ?? [];
                        usort($pelotonPlans, function($a, $b) { return strcmp($a['position_archer'] ?? '', $b['position_archer'] ?? ''); });
                        $plansParPosition = [];
                        foreach ($pelotonPlans as $plan) {
                            $plansParPosition[$plan['position_archer'] ?? ''] = $plan;
                        }
                        $ordrePositions = [];
                        for ($i = 1; $i <= $nombreArchersParPeloton; $i++) {
                            $ordrePositions[] = chr(64 + $i);
                        }
                        $nbCompound = 0;
                        foreach ($pelotonPlans as $p) {
                            if (!empty($p['numero_licence']) && $isCompound($p['numero_licence'], $inscriptionsMap ?? [])) {
                                $nbCompound++;
                            }
                        }
                        ?>
                        <div class="pas-de-tir peloton-card">
                            <div class="pas-de-tir-header">
                                <h3>Peloton <?= htmlspecialchars($numeroPeloton) ?></h3>
                                <?php if ($nbCompound >= 2): ?>
                                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Attention :</strong> <?= $nbCompound ?> tireur(s) à compound dans ce peloton (max 2 recommandé).
                                    </div>
                                <?php endif; ?>
                            </div>
                            <ul class="list-group list-group-flush peloton-positions-list">
                                <?php foreach ($ordrePositions as $position): ?>
                                    <?php
                                    $plan = $plansParPosition[$position] ?? null;
                                    $isAssigne = $plan && isset($plan['user_nom']) && $plan['user_nom'] !== null && isset($plan['numero_licence']) && $plan['numero_licence'] !== null;
                                    $info = $isAssigne && $plan ? $getArcherDisplayInfo($plan, $inscriptionsMap ?? []) : null;
                                    $nomComplet = $info ? $info['nomComplet'] : ($isAssigne ? ($plan['user_nom'] ?? '') : 'Libre');
                                    $clubComplet = $info ? ($info['club'] ?? '') : '';
                                    $piquetVal = $plan['piquet'] ?? null;
                                    $piquetColor = $piquetVal && isset($piquetColors[strtolower($piquetVal)]) ? $piquetColors[strtolower($piquetVal)] : null;
                                    $letterRectStyle = ($isAssigne && $piquetColor) ? 'background-color:' . $piquetColor . ';' : '';
                                    $frameBorderStyle = ($isAssigne && $piquetColor) ? 'border-color:' . $piquetColor . ' !important;' : '';
                                    $tooltipText = $isAssigne ? $nomComplet . (!empty($clubComplet) ? ' - ' . $clubComplet : '') : 'Cliquer pour assigner un archer';
                                    ?>
                                    <li class="list-group-item peloton-position-item blason-item <?= $isAssigne ? 'assigne' : 'libre' ?>" <?= $frameBorderStyle ? 'style="' . $frameBorderStyle . '"' : '' ?>
                                        data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                                        data-depart="<?= htmlspecialchars($numeroDepart) ?>"
                                        data-peloton="<?= htmlspecialchars($numeroPeloton) ?>"
                                        data-position="<?= htmlspecialchars($position) ?>"
                                        data-numero-licence="<?= htmlspecialchars($plan['numero_licence'] ?? '') ?>"
                                        data-user-nom="<?= htmlspecialchars($plan['user_nom'] ?? '') ?>"
                                        data-assignable="<?= $isAssigne ? '0' : '1' ?>"
                                        title="<?= htmlspecialchars($tooltipText) ?>">
                                        <span class="peloton-position-letter"<?= $letterRectStyle ? ' style="' . $letterRectStyle . '"' : '' ?>><?= htmlspecialchars($position) ?></span>
                                        <div class="peloton-position-content">
                                            <span class="peloton-position-name"><?= htmlspecialchars($nomComplet) ?></span>
                                            <?php if (!empty($clubComplet)): ?>
                                                <span class="peloton-position-club"><?= htmlspecialchars($clubComplet) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($piquetVal): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($piquetVal)) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p style="margin-top: 20px;">
    <a href="/concours/show/<?= htmlspecialchars($concoursId) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour au concours</a>
</p>
</div>

<!-- Modale : liste des archers inscrits sans peloton -->
<div class="modal fade" id="pelotonAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archers inscrits sans peloton</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="peloton-assign-info" class="text-muted mb-2"></p>
                <div id="peloton-archers-list" class="list-group"></div>
                <button type="button" class="btn btn-outline-secondary mt-3" id="peloton-liberer-btn" style="display:none;">Libérer cette position</button>
            </div>
        </div>
    </div>
</div>
