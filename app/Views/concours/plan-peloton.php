<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<link href="/public/assets/css/plan-cible.css" rel="stylesheet">
<style>
/* Plan peloton : cadres avec liste A, B, C... */
.peloton-card {
    width: 280px;
    min-width: 280px;
    height: auto;
    min-height: 200px;
}
.peloton-positions-list {
    width: 100%;
    flex: 1;
    margin: 0;
    padding: 0;
}
.peloton-position-item {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    transition: background-color 0.2s;
}
.peloton-position-item:hover {
    background-color: rgba(13, 110, 253, 0.08) !important;
}
.peloton-position-item.assigne {
    font-weight: 500;
}
.peloton-position-letter {
    font-weight: bold;
    min-width: 28px;
    text-align: center;
    background: #e9ecef;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 1em;
}
.peloton-position-item.assigne .peloton-position-letter {
    background: #cfe2ff;
}
.peloton-position-name {
    flex: 1;
    font-size: 0.95em;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

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
    <script>
    (function() {
        var btn = document.getElementById('btn-create-plan-peloton-empty');
        var msg = document.getElementById('plan-peloton-create-message');
        if (btn && msg) {
            btn.addEventListener('click', function() {
                var concoursId = btn.getAttribute('data-concours-id');
                var nombrePelotons = parseInt(btn.getAttribute('data-nombre-pelotons'), 10) || 0;
                var nombreDepart = parseInt(btn.getAttribute('data-nombre-depart'), 10) || 1;
                var nombreArchers = parseInt(btn.getAttribute('data-nombre-archers'), 10) || 0;
                if (!concoursId) { alert('ID du concours manquant'); return; }
                if (nombrePelotons <= 0 || nombreArchers <= 0) { alert('Veuillez configurer le nombre de pelotons et d\'archers par peloton dans les paramètres du concours.'); return; }
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
                fetch('/api/concours/' + concoursId + '/plan-peloton', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ nombre_pelotons: nombrePelotons, nombre_depart: nombreDepart, nombre_archers_par_peloton: nombreArchers })
                })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        window.location.href = '/concours/' + concoursId + '/plan-peloton';
                    } else {
                        msg.innerHTML = '<div class="alert alert-danger">' + (result.error || 'Erreur') + '</div>';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
                    }
                })
                .catch(function(err) {
                    msg.innerHTML = '<div class="alert alert-danger">Erreur : ' + (err.message || 'réseau') + '</div>';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-users"></i> Créer le plan de peloton';
                });
            });
        }
    })();
    </script>
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

<script>
(function() {
    var modalEl = document.getElementById('pelotonAssignModal');
    var listContainer = document.getElementById('peloton-archers-list');
    var infoContainer = document.getElementById('peloton-assign-info');
    var releaseBtn = document.getElementById('peloton-liberer-btn');
    var currentTarget = null;
    var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

    function setListMessage(msg, type) {
        if (!listContainer) return;
        listContainer.innerHTML = '<div class="alert alert-' + (type === 'danger' ? 'danger' : type === 'warning' ? 'warning' : 'info') + '">' + msg + '</div>';
    }

    function fetchArchersDispo(target) {
        if (!target || !listContainer) return;
        setListMessage('Chargement...');
        fetch('/api/concours/' + target.concoursId + '/plan-peloton/' + target.depart + '/archers-dispo', {
            method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'include'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !Array.isArray(data.data)) {
                setListMessage(data.error || 'Erreur', 'danger');
                return;
            }
            var archers = data.data;
            if (archers.length === 0) {
                setListMessage('Aucun archer disponible.', 'warning');
                return;
            }
            listContainer.innerHTML = '';
            archers.forEach(function(a) {
                var div = document.createElement('div');
                div.className = 'list-group-item d-flex justify-content-between align-items-center';
                div.innerHTML = '<div><strong>' + (a.user_nom || a.nom || '') + '</strong> (' + (a.numero_licence || '') + ')' + (a.piquet ? ' - Piquet ' + a.piquet : '') + '</div>' +
                    '<button type="button" class="btn btn-sm btn-primary js-assign-peloton" data-user-nom="' + (a.user_nom || '') + '" data-numero-licence="' + (a.numero_licence || '') + '" data-id-club="' + (a.id_club || '') + '" data-piquet="' + (a.piquet || '') + '">Affecter</button>';
                listContainer.appendChild(div);
            });
        })
        .catch(function() { setListMessage('Erreur réseau', 'danger'); });
    }

    document.addEventListener('click', function(e) {
        var item = e.target.closest('.blason-item');
        if (!item) return;
        var assignable = item.getAttribute('data-assignable') === '1';
        currentTarget = {
            concoursId: item.dataset.concoursId,
            depart: item.dataset.depart,
            peloton: item.dataset.peloton,
            position: item.dataset.position,
            numeroLicence: item.dataset.numeroLicence,
            userNom: item.dataset.userNom
        };
        if (infoContainer) infoContainer.textContent = 'Départ ' + currentTarget.depart + ' - Peloton ' + currentTarget.peloton + ' - Position ' + currentTarget.position + ' : sélectionnez un archer à affecter';
        if (releaseBtn) releaseBtn.style.display = assignable ? 'none' : 'block';
        fetchArchersDispo(currentTarget);
        if (modalInstance) modalInstance.show();
    });

    if (listContainer) {
        listContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-assign-peloton');
            if (!btn || !currentTarget) return;
            var payload = {
                numero_depart: parseInt(currentTarget.depart, 10),
                numero_peloton: parseInt(currentTarget.peloton, 10),
                position_archer: currentTarget.position,
                user_nom: btn.getAttribute('data-user-nom') || '',
                numero_licence: btn.getAttribute('data-numero-licence') || '',
                id_club: btn.getAttribute('data-id-club') || null,
                piquet: btn.getAttribute('data-piquet') || null
            };
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-peloton/assign', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) window.location.reload();
                else setListMessage(data.error || 'Erreur', 'danger');
            })
            .catch(function() { setListMessage('Erreur', 'danger'); });
        });
    }

    if (releaseBtn) {
        releaseBtn.addEventListener('click', function() {
            if (!currentTarget) return;
            fetch('/api/concours/' + currentTarget.concoursId + '/plan-peloton/' + currentTarget.depart + '/liberer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ numero_peloton: parseInt(currentTarget.peloton, 10), position_archer: currentTarget.position })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) window.location.reload();
                else setListMessage(data.error || 'Erreur', 'danger');
            });
        });
    }
})();
</script>
