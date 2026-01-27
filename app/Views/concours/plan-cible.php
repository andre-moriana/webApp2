<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<link href="/public/assets/css/plan-cible.css" rel="stylesheet">

<!-- Affichage du plan de cible d'un concours -->
<div class="container-fluid concours-create-container">
<h1>Plan de cible - <?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <strong>Erreur:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <strong>Succès:</strong> <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php
// Récupérer les informations du concours
$nombreCibles = $concours->nombre_cibles ?? 0;
$nombreDepart = $concours->nombre_depart ?? 1;
$nombreTireursParCibles = $concours->nombre_tireurs_par_cibles ?? 0;
$concoursId = $concours->id ?? $concours->_id ?? null;
?>

<div class="form-section">
    <div class="form-group">
        <label><strong>Nombre de cibles :</strong></label>
        <p><?= htmlspecialchars($nombreCibles) ?></p>
    </div>
    <div class="form-group">
        <label><strong>Nombre de départs :</strong></label>
        <p><?= htmlspecialchars($nombreDepart) ?></p>
    </div>
    <div class="form-group">
        <label><strong>Nombre d'archers par cible :</strong></label>
        <p><?= htmlspecialchars($nombreTireursParCibles) ?></p>
    </div>
</div>

<?php if (empty($plans)): ?>
    <div class="alert alert-info">
        <p>Aucun plan de cible n'a été créé pour ce concours.</p>
        <a href="/concours/show/<?= $concoursId ?>" class="btn btn-primary">
            <i class="fas fa-bullseye"></i> Créer le plan de cible
        </a>
    </div>
<?php else: ?>
    <!-- Légende -->
    <div class="plan-cible-legend">
        <h4><i class="fas fa-info-circle"></i> Légende</h4>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-color assigne"></div>
                <span>Position assignée</span>
            </div>
            <div class="legend-item">
                <div class="legend-color libre"></div>
                <span>Position libre</span>
            </div>
        </div>
    </div>
    
    <?php foreach ($plans as $numeroDepart => $departPlans): ?>
        <div class="plan-depart-section" style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 20px;">
                <i class="fas fa-flag"></i> Départ <?= htmlspecialchars($numeroDepart) ?>
            </h2>
            
            <!-- Container avec scroll horizontal -->
            <div class="plan-cible-container">
                <div class="plan-cible-scroll">
                    <?php
                    // Grouper les plans par cible
                    $plansParCible = [];
                    foreach ($departPlans as $plan) {
                        $cible = $plan['numero_cible'] ?? 0;
                        if (!isset($plansParCible[$cible])) {
                            $plansParCible[$cible] = [];
                        }
                        $plansParCible[$cible][] = $plan;
                    }
                    
                    // Trier par numéro de cible
                    ksort($plansParCible);
                    
                    // Afficher chaque cible (pas de tir)
                    foreach ($plansParCible as $numeroCible => $ciblePlans): 
                        // Trier les plans par position (A, B, C, D...)
                        usort($ciblePlans, function($a, $b) {
                            $posA = $a['position_archer'] ?? '';
                            $posB = $b['position_archer'] ?? '';
                            return strcmp($posA, $posB);
                        });
                        
                        // Récupérer le blason et la distance de cette cible
                        $blasonCible = null;
                        $distanceCible = null;
                        foreach ($ciblePlans as $plan) {
                            if (isset($plan['blason']) && $plan['blason'] !== null) {
                                $blasonCible = $plan['blason'];
                            }
                            if (isset($plan['distance']) && $plan['distance'] !== null) {
                                $distanceCible = $plan['distance'];
                            }
                            if ($blasonCible !== null && $distanceCible !== null) {
                                break;
                            }
                        }
                        
                        // Créer un tableau associatif position => plan
                        $plansParPosition = [];
                        foreach ($ciblePlans as $plan) {
                            $position = $plan['position_archer'] ?? '';
                            $plansParPosition[$position] = $plan;
                        }
                    ?>
                    <div class="pas-de-tir">
                        <div class="pas-de-tir-header">
                            <h3>Cible <?= htmlspecialchars($numeroCible) ?></h3>
                            <?php if ($blasonCible !== null || $distanceCible !== null): ?>
                                <div class="pas-de-tir-info">
                                    <?php if ($blasonCible !== null): ?>
                                        <i class="fas fa-bullseye"></i> Blason <?= htmlspecialchars($blasonCible) ?>
                                    <?php endif; ?>
                                    <?php if ($blasonCible !== null && $distanceCible !== null): ?>
                                        <span> - </span>
                                    <?php endif; ?>
                                    <?php if ($distanceCible !== null): ?>
                                        <i class="fas fa-ruler"></i> <?= htmlspecialchars($distanceCible) ?>m
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="blasons-container">
                            <?php
                            // Afficher chaque position (A, B, C, D, etc.)
                            for ($i = 1; $i <= $nombreTireursParCibles; $i++) {
                                $position = chr(64 + $i); // 1->A, 2->B, etc.
                                $plan = $plansParPosition[$position] ?? null;
                                $userId = $plan['user_id'] ?? null;
                                $isAssigne = $userId !== null;
                                
                                // Récupérer les informations de l'utilisateur
                                $userNom = '';
                                $userPrenom = '';
                                if ($isAssigne && isset($usersMap[$userId])) {
                                    $user = $usersMap[$userId];
                                    $userNom = is_array($user) ? ($user['nom'] ?? $user['NOM'] ?? '') : ($user->nom ?? $user->NOM ?? '');
                                    $userPrenom = is_array($user) ? ($user['prenom'] ?? $user['PRENOM'] ?? '') : ($user->prenom ?? $user->PRENOM ?? '');
                                }
                                
                                $nomComplet = trim($userPrenom . ' ' . $userNom);
                                if (empty($nomComplet)) {
                                    $nomComplet = 'User ID: ' . $userId;
                                }
                            ?>
                            <div class="blason-item <?= $isAssigne ? 'assigne' : 'libre' ?>">
                                <div class="blason-numero"><?= htmlspecialchars($numeroCible) ?></div>
                                <div class="blason-position"><?= htmlspecialchars($position) ?></div>
                                
                                <?php if ($blasonCible !== null): ?>
                                    <div class="blason-taille"><?= htmlspecialchars($blasonCible) ?></div>
                                <?php else: ?>
                                    <div class="blason-taille" style="font-size: 0.9em; color: #adb5bd;">-</div>
                                <?php endif; ?>
                                
                                <?php if ($distanceCible !== null): ?>
                                    <div class="blason-distance"><?= htmlspecialchars($distanceCible) ?>m</div>
                                <?php endif; ?>
                                
                                <?php if ($isAssigne): ?>
                                    <div class="blason-nom" title="<?= htmlspecialchars($nomComplet) ?>">
                                        <?= htmlspecialchars($nomComplet) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="blason-nom" style="color: #adb5bd;">Libre</div>
                                <?php endif; ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="mt-4">
    <a href="/concours/show/<?= $concoursId ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour au concours
    </a>
    <a href="/concours" class="btn btn-secondary">
        <i class="fas fa-list"></i> Retour à la liste
    </a>
</div>
</div>
