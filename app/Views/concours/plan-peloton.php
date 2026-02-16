<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<link href="/public/assets/css/plan-cible.css" rel="stylesheet">
<link href="/public/assets/css/plan-peloton.css" rel="stylesheet">

<div class="container-fluid concours-create-container">
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

$nombrePelotons = $concours->nombre_pelotons ?? $concours->nombre_cibles ?? 0;
$nombreDepart = $concours->nombre_depart ?? 1;
$nombreArchersParPeloton = $concours->nombre_archers_par_peloton ?? $concours->nombre_tireurs_par_cibles ?? 0;
$concoursId = $concours->id ?? $concours->_id ?? null;
$piquetColors = ['rouge' => '#ffe0e0', 'bleu' => '#e0e8ff', 'blanc' => '#f5f5f5'];
?>

<?php if (empty($plans)): ?>
    <div class="alert alert-info">
        <p>Aucun plan de peloton n'a été créé pour ce concours.</p>
        <button type="button" class="btn btn-primary" id="btn-create-plan-peloton-empty" data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                data-nombre-pelotons="<?= (int)$nombrePelotons ?>"
                data-nombre-depart="<?= (int)$nombreDepart ?>"
                data-nombre-archers="<?= (int)$nombreArchersParPeloton ?>">
            <i class="fas fa-users"></i> Créer le plan de peloton
        </button>
        <div id="plan-peloton-create-message" style="margin-top: 10px;"></div>
    </div>
<?php else: ?>
    <div class="plan-cible-legend">
        <h4><i class="fas fa-info-circle"></i> Règles</h4>
        <p>Max 3 archers du même club par peloton. Max 2 couleurs de piquet par peloton.</p>
        <div class="legend-items">
            <div class="legend-item"><div class="legend-color assigne"></div><span>Position assignée</span></div>
            <div class="legend-item"><div class="legend-color libre"></div><span>Position libre</span></div>
        </div>
    </div>

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
            <h2><i class="fas fa-flag"></i> Départ <?= htmlspecialchars($numeroDepart) ?></h2>
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
                        ?>
                        <div class="pas-de-tir peloton-card">
                            <div class="pas-de-tir-header">
                                <h3>Peloton <?= htmlspecialchars($numeroPeloton) ?></h3>
                            </div>
                            <ul class="list-group list-group-flush peloton-positions-list">
                                <?php foreach ($ordrePositions as $position): ?>
                                    <?php
                                    $plan = $plansParPosition[$position] ?? null;
                                    $isAssigne = $plan && isset($plan['user_nom']) && $plan['user_nom'] !== null && isset($plan['numero_licence']) && $plan['numero_licence'] !== null;
                                    $info = $isAssigne && $plan ? $getArcherDisplayInfo($plan, $inscriptionsMap ?? []) : null;
                                    $nomComplet = $info ? $info['nomComplet'] : ($isAssigne ? ($plan['user_nom'] ?? '') : 'Libre');
                                    $piquetVal = $plan['piquet'] ?? null;
                                    $bgStyle = $piquetVal && isset($piquetColors[strtolower($piquetVal)]) ? 'background-color:' . $piquetColors[strtolower($piquetVal)] . ';' : '';
                                    ?>
                                    <li class="list-group-item peloton-position-item blason-item <?= $isAssigne ? 'assigne' : 'libre' ?>" style="<?= $bgStyle ?>"
                                        data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                                        data-depart="<?= htmlspecialchars($numeroDepart) ?>"
                                        data-peloton="<?= htmlspecialchars($numeroPeloton) ?>"
                                        data-position="<?= htmlspecialchars($position) ?>"
                                        data-numero-licence="<?= htmlspecialchars($plan['numero_licence'] ?? '') ?>"
                                        data-user-nom="<?= htmlspecialchars($plan['user_nom'] ?? '') ?>"
                                        data-assignable="<?= $isAssigne ? '0' : '1' ?>"
                                        title="Cliquer pour <?= $isAssigne ? 'modifier ou libérer' : 'assigner un archer' ?>">
                                        <span class="peloton-position-letter"><?= htmlspecialchars($position) ?></span>
                                        <span class="peloton-position-name"><?= htmlspecialchars($nomComplet) ?></span>
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

<script src="/public/assets/js/plan-peloton.js"></script>
