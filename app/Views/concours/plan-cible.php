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
                    
                    // Afficher toutes les cibles de 1 à nombre_cibles, même si elles n'ont pas de plans
                    for ($numeroCible = 1; $numeroCible <= $nombreCibles; $numeroCible++):
                        $ciblePlans = $plansParCible[$numeroCible] ?? []; 
                        // Trier les plans par position (A, B, C, D...)
                        usort($ciblePlans, function($a, $b) {
                            $posA = $a['position_archer'] ?? '';
                            $posB = $b['position_archer'] ?? '';
                            return strcmp($posA, $posB);
                        });
                        
                        // Récupérer le blason et la distance de cette cible
                        $blasonCible = null;
                        $distanceCible = null;
                        $trispotCible = null;
                        foreach ($ciblePlans as $plan) {
                            if (isset($plan['blason']) && $plan['blason'] !== null) {
                                $blasonCible = $plan['blason'];
                            }
                            if (isset($plan['distance']) && $plan['distance'] !== null) {
                                $distanceCible = $plan['distance'];
                            }
                            // Vérifier si c'est un trispot depuis trispotMap
                            $cibleKey = $numeroDepart . '_' . $numeroCible;
                            if (isset($trispotMap[$cibleKey])) {
                                $trispotCible = $trispotMap[$cibleKey];
                            }
                            if ($blasonCible !== null && $distanceCible !== null) {
                                break;
                            }
                        }
                        
                        // Déterminer le type de disposition selon le blason ou par défaut selon le numéro de cible
                        $dispositionType = 'default'; // Par défaut, disposition verticale
                        
                        // Si le blason n'est pas défini, utiliser les valeurs par défaut selon le numéro de cible
                        if ($blasonCible === null && $trispotCible === null) {
                            if ($numeroCible >= 1 && $numeroCible <= 2) {
                                // Cibles 1-2 : blason 60 par défaut
                                $blasonCible = 60;
                                $dispositionType = 'blason60';
                            } elseif ($numeroCible >= 3 && $numeroCible <= 10) {
                                // Cibles 3-10 : blason 40 par défaut
                                $blasonCible = 40;
                                $dispositionType = 'blason40';
                            } elseif ($numeroCible >= 11 && $numeroCible <= 14) {
                                // Cibles 11-14 : trispot par défaut
                                $trispotCible = 1;
                                $dispositionType = 'trispot';
                            }
                        } else {
                            // Utiliser les valeurs définies dans les plans
                            if ($trispotCible == 1 || $trispotCible === '1' || $trispotCible === true) {
                                $dispositionType = 'trispot'; // A C B D de gauche à droite
                            } elseif ($blasonCible == 60 || $blasonCible === '60') {
                                $dispositionType = 'blason60'; // 2 blasons: A-C gauche, B-D droite
                            } elseif ($blasonCible == 40 || $blasonCible === '40') {
                                $dispositionType = 'blason40'; // 4 blasons: A B haut, C D bas
                            }
                        }
                        
                        // Créer un tableau associatif position => plan
                        $plansParPosition = [];
                        foreach ($ciblePlans as $plan) {
                            $position = $plan['position_archer'] ?? '';
                            $plansParPosition[$position] = $plan;
                        }
                        
                        // Définir l'ordre d'affichage selon le type de disposition
                        $ordrePositions = [];
                        if ($dispositionType === 'blason60') {
                            // Blason 60: A-C gauche, B-D droite (2 blasons par cible)
                            $ordrePositions = ['A', 'C', 'B', 'D'];
                        } elseif ($dispositionType === 'blason40') {
                            // Blason 40: A B haut, C D bas (4 blasons par cible)
                            $ordrePositions = ['A', 'B', 'C', 'D'];
                        } elseif ($dispositionType === 'trispot') {
                            // Trispot: A C B D de gauche à droite (4 blasons par cible)
                            $ordrePositions = ['A', 'C', 'B', 'D'];
                        } else {
                            // Ordre par défaut (A, B, C, D...)
                            for ($i = 1; $i <= $nombreTireursParCibles; $i++) {
                                $ordrePositions[] = chr(64 + $i);
                            }
                        }
                        
                        // Limiter le nombre de positions selon le type de blason
                        if ($dispositionType === 'blason60') {
                            // Blason 60 : 4 positions (A, B, C, D) mais 2 blasons physiques
                            // A-C à gauche, B-D à droite
                            $ordrePositions = ['A', 'C', 'B', 'D'];
                        } elseif ($dispositionType === 'blason40' || $dispositionType === 'trispot') {
                            // Blason 40 et Trispot : 4 blasons (A, B, C, D)
                            $ordrePositions = array_slice($ordrePositions, 0, 4);
                        }
                    ?>
                    <div class="pas-de-tir">
                        <div class="pas-de-tir-header">
                            <h3>Cible <?= htmlspecialchars($numeroCible) ?></h3>
                            <div class="pas-de-tir-info">
                                <?php if ($blasonCible !== null): ?>
                                    <i class="fas fa-bullseye"></i> Blason <?= htmlspecialchars($blasonCible) ?>
                                <?php elseif ($trispotCible == 1 || $trispotCible === '1' || $trispotCible === true): ?>
                                    <i class="fas fa-bullseye"></i> Trispot
                                <?php endif; ?>
                                <?php if ($blasonCible !== null && $distanceCible !== null): ?>
                                    <span> - </span>
                                <?php endif; ?>
                                <?php if ($distanceCible !== null): ?>
                                    <i class="fas fa-ruler"></i> <?= htmlspecialchars($distanceCible) ?>m
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="blasons-container blasons-<?= htmlspecialchars($dispositionType) ?>">
                            <?php
                            // Afficher les positions dans l'ordre défini
                            foreach ($ordrePositions as $position) {
                                // Récupérer le plan pour cette position, ou créer un plan vide si elle n'existe pas
                                $plan = $plansParPosition[$position] ?? null;
                                if ($plan === null) {
                                    // Créer un plan vide pour cette position
                                    $plan = [
                                        'numero_cible' => $numeroCible,
                                        'position_archer' => $position,
                                        'user_id' => null,
                                        'blason' => $blasonCible,
                                        'distance' => $distanceCible
                                    ];
                                }
                                $userId = $plan['user_id'] ?? null;
                                $isAssigne = $userId !== null;
                                
                                // Récupérer les informations de l'utilisateur
                                $userNom = '';
                                $userPrenom = '';
                                $nomComplet = '';
                                
                                if ($isAssigne) {
                                    if (isset($usersMap[$userId])) {
                                        $user = $usersMap[$userId];
                                        // Gérer les différents formats de données utilisateur
                                        if (is_array($user)) {
                                            $userNom = $user['nom'] ?? $user['NOM'] ?? $user['name'] ?? '';
                                            $userPrenom = $user['prenom'] ?? $user['PRENOM'] ?? $user['first_name'] ?? $user['firstName'] ?? '';
                                        } else {
                                            $userNom = $user->nom ?? $user->NOM ?? $user->name ?? '';
                                            $userPrenom = $user->prenom ?? $user->PRENOM ?? $user->first_name ?? $user->firstName ?? '';
                                        }
                                        $nomComplet = trim($userPrenom . ' ' . $userNom);
                                    }
                                    
                                    // Si le nom n'a pas pu être récupéré, utiliser l'ID
                                    if (empty($nomComplet)) {
                                        $nomComplet = 'ID: ' . $userId;
                                    }
                                }
                            ?>
                            <?php
                                // Utiliser le blason et la distance du plan, ou ceux de la cible par défaut
                                $planBlason = $plan['blason'] ?? $blasonCible;
                                $planDistance = $plan['distance'] ?? $distanceCible;
                            ?>
                            <div class="blason-item <?= $isAssigne ? 'assigne' : 'libre' ?>" data-position="<?= htmlspecialchars($position) ?>">
                                <div class="blason-numero"><?= htmlspecialchars($numeroCible) ?></div>
                                <div class="blason-position"><?= htmlspecialchars($position) ?></div>
                                
                                <?php if ($planBlason !== null): ?>
                                    <div class="blason-taille"><?= htmlspecialchars($planBlason) ?></div>
                                <?php else: ?>
                                    <div class="blason-taille" style="font-size: 0.9em; color: #adb5bd;">-</div>
                                <?php endif; ?>
                                
                                <?php if ($planDistance !== null): ?>
                                    <div class="blason-distance"><?= htmlspecialchars($planDistance) ?>m</div>
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
                    <?php endfor; ?>
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
