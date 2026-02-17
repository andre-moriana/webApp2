<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<link href="/public/assets/css/plan-cible.css" rel="stylesheet">

<!-- Affichage du plan de cible d'un concours -->
<div class="container-fluid concours-create-container" data-can-edit-plan="<?= !empty($canEditPlan) ? '1' : '0' ?>">
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
// Helper: récupérer les infos d'affichage d'un archer (user_id ou user_nom+numero_licence)
$getArcherDisplayInfo = function($plan, $usersMap, $inscriptionsMap = []) {
    $numeroLicence = $plan['numero_licence'] ?? null;
    $userNom = $plan['user_nom'] ?? null;
    if ($numeroLicence && isset($inscriptionsMap[$numeroLicence])) {
        $i = $inscriptionsMap[$numeroLicence];
        $nom = $i['user_nom'] ?? $i['nom'] ?? $i['name'] ?? '';
        return [
            'nom' => $nom,
            'club' => $i['club_name'] ?? $i['clubName'] ?? $i['id_club'] ?? '',
            'nomComplet' => $nom
        ];
    }
    if ($userNom) {
        return ['nom' => $userNom, 'club' => '', 'nomComplet' => $userNom];
    }
    return null;
};
// Récupérer les informations du concours
$nombreCibles = $concours->nombre_cibles ?? 0;
$nombreDepart = $concours->nombre_depart ?? 1;
$nombreTireursParCibles = $concours->nombre_tireurs_par_cibles ?? 0;
$concoursId = $concours->id ?? $concours->_id ?? null;
?>

<?php if (empty($plans)): ?>
    <div class="alert alert-info">
        <p>Aucun plan de cible n'a été créé pour ce concours.</p>
        <?php if (!empty($canEditPlan)): ?>
        <button type="button" class="btn btn-primary" id="btn-create-plan-cible-empty" data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                data-nombre-cibles="<?= (int)$nombreCibles ?>"
                data-nombre-depart="<?= (int)$nombreDepart ?>"
                data-nombre-tireurs="<?= (int)$nombreTireursParCibles ?>">
            <i class="fas fa-bullseye"></i> Créer le plan de cible
        </button>
        <?php endif; ?>
        <div id="plan-cible-create-message" style="margin-top: 10px;"></div>
    </div>
    <?php endif; ?>
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
                        $cibleHasAssigned = false;
                        foreach ($ciblePlans as $plan) {
                            // Vérifier si un archer est assigné à cette position
                            if (isset($plan['user_nom']) && $plan['user_nom'] !== null && $plan['numero_licence'] !== null) {
                                $cibleHasAssigned = true;
                            }
                            
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
                        }
                        
                        // Déterminer le type de disposition selon le blason ou par défaut selon le numéro de cible
                        $dispositionType = 'default'; // Par défaut, disposition verticale
                        
                        // Si le blason n'est pas défini, utiliser les valeurs par défaut selon le numéro de cible
                        if ($blasonCible === null && $trispotCible === null) {
                            if ($numeroCible == 1 ) {
                                // Cibles 1 : blason 80 par défaut
                                $blasonCible = '80';
                                $dispositionType = 'blason80';
                            } elseif ($numeroCible == 2){
                                // Cibles 2 : blason 60 par défaut
                                $blasonCible = '60';
                                $dispositionType = 'blason60';
                            } elseif ($numeroCible >= 3 && $numeroCible <= 10) {
                                // Cibles 3-10 : blason 40 par défaut
                                $blasonCible = '40';
                                $dispositionType = 'blason40';
                            } elseif ($numeroCible >= 11 && $numeroCible <= 14) {
                                // Cibles 11-14 : trispot par défaut
                                $blasonCible = '40';
                                $trispotCible = 1;
                                $dispositionType = 'trispot';
                            }
                        } else {
                            // Utiliser les valeurs définies dans les plans
                            if ($trispotCible == 1 || $trispotCible === '1' || $trispotCible === true ) {
                                $dispositionType = 'trispot'; // A C B D de gauche à droite
                            } elseif ($blasonCible == 80 || $blasonCible === '80') {
                                $dispositionType = 'blason80'; // 1 blasons: A-B-C-D
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
                        if ($dispositionType === 'blason80') {
                            // Blason 80: 1 blason physique seulement
                           $ordrePositions = ['A'];
                        }elseif ($dispositionType === 'blason60') {
                            // Blason 60: 2 blasons physiques seulement
                            // Blason gauche: A et C (afficher seulement A)
                            // Blason droit: B et D (afficher seulement B)
                            $ordrePositions = ['A', 'B'];
                        } elseif ($dispositionType === 'blason40') {
                            // Blason 40: A B haut, C D bas (4 blasons par cible)
                            $ordrePositions = ['A', 'B', 'C', 'D'];
                        } elseif ($dispositionType === 'trispot') {
                            // Trispot: grille 3 lignes x 4 colonnes (ordre: A, C, B, D)
                            // 12 positions : A1-A3, C1-C3, B1-B3, D1-D3
                            // Chaque colonne est affectée à un seul archer
                            $ordrePositions = [];
                            foreach (['A', 'C', 'B', 'D'] as $colonne) {
                                for ($ligne = 1; $ligne <= 3; $ligne++) {
                                    $ordrePositions[] = $colonne . $ligne;
                                }
                            }
                        } else {
                            // Ordre par défaut (A, B, C, D...)
                            for ($i = 1; $i <= $nombreTireursParCibles; $i++) {
                                $ordrePositions[] = chr(64 + $i);
                            }
                        }
                       
                    ?>
                    <div class="pas-de-tir">
                        <div class="pas-de-tir-header">
                            <?php if ($dispositionType === 'trispot'): ?>
                                <h3>Cible <?= htmlspecialchars($numeroCible) ?> - Trispot (4 Blasons 40)</h3>
                            <?php else: ?>
                                <h3>Cible <?= htmlspecialchars($numeroCible) ?></h3>
                            <?php endif; ?>
                            <div class="pas-de-tir-info">
                                <?php if ($blasonCible !== null && $blasonCible !== 'T40'): ?>
                                    <i class="fas fa-bullseye"></i> Blason <?= htmlspecialchars($blasonCible) ?>
                                <?php elseif ($trispotCible == 1 || $trispotCible === '1' || $trispotCible === true || $blasonCible === 'T40'): ?>
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
                                // Pour les trispots, extraire la colonne de la position (A1 -> A)
                                $colonne = null;
                                if ($dispositionType === 'trispot' && preg_match('/^([A-D])(\d+)$/', $position, $matches)) {
                                    $colonne = $matches[1];
                                }
                                
                                // Pour les trispots, récupérer le user_id de la colonne (toutes les positions de la colonne ont le même)
                                $plan = null;
                                $userIdTrispot = null;
                                if ($dispositionType === 'trispot' && $colonne !== null) {
                                    // Récupérer le plan pour cette position spécifique (A1, A2, A3, etc.)
                                    $plan = $plansParPosition[$position] ?? null;
                                    
                                    // Chercher d'abord par position complète (A1, A2, A3)
                                    // Si pas trouvé, chercher par colonne seule (A, C, B, D) pour compatibilité
                                    if (!$plan && isset($plansParPosition[$colonne])) {
                                        $plan = $plansParPosition[$colonne];
                                    }
                                    
                                    // Récupérer user_id ou user_nom+numero_licence de la colonne (vérifier les 3 positions)
                                    $numeroLicenceTrispot = null;
                                    $userNomTrispot = null;
                                    for ($ligne = 1; $ligne <= 3; $ligne++) {
                                        $posTrispot = $colonne . $ligne;
                                        $planPos = $plansParPosition[$posTrispot] ?? null;
                                        if ($planPos) {
                                            if ($planPos['user_nom'] !== null && $planPos['numero_licence'] !== null) {
                                                $userIdTrispot = $planPos['user_nom'] . '-' . $planPos['numero_licence'];
                                                break;
                                            }
                                            if (!empty($planPos['numero_licence'])) {
                                                $numeroLicenceTrispot = $planPos['numero_licence'];
                                                $userNomTrispot = $planPos['user_nom'] ?? null;
                                                break;
                                            }
                                        }
                                    }
                                    if ($userIdTrispot === null && $numeroLicenceTrispot === null && isset($plansParPosition[$colonne])) {
                                        $planPos = $plansParPosition[$colonne];
                                        if ($planPos && $planPos['user_nom'] !== null && $planPos['numero_licence'] !== null) {
                                            $userIdTrispot = $planPos['user_nom'] . '-' . $planPos['numero_licence'];
                                            $numeroLicenceTrispot = $planPos['numero_licence'];
                                            $userNomTrispot = $planPos['user_nom'];
                                        } elseif ($planPos && !empty($planPos['numero_licence'])) {
                                            $numeroLicenceTrispot = $planPos['numero_licence'];
                                            $userNomTrispot = $planPos['user_nom'] ?? null;
                                        }
                                    }
                                    
                                    if ($plan === null) {
                                        $plan = [
                                            'numero_cible' => $numeroCible,
                                            'position_archer' => $position,
                                            'numero_licence' => $numeroLicenceTrispot,
                                            'user_nom' => $userNomTrispot,
                                            'blason' => $blasonCible,
                                            'distance' => $distanceCible
                                        ];
                                    } else {
                                        $plan['numero_licence'] = $numeroLicenceTrispot;
                                        $plan['user_nom'] = $userNomTrispot;
                                    }
                                } else {
                                    // Pour les blasons normaux, récupérer le plan normalement
                                    $plan = $plansParPosition[$position] ?? null;
                                    
                                    // Pour les blasons 60, si on affiche A, chercher aussi C (même blason gauche)
                                    // Si on affiche B, chercher aussi D (même blason droit)
                                    if ($plan === null && $dispositionType === 'blason60') {
                                        if ($position === 'A') {
                                            $plan = $plansParPosition['C'] ?? null;
                                        } elseif ($position === 'B') {
                                            $plan = $plansParPosition['D'] ?? null;
                                        }
                                    }
                                    
                                    if ($plan === null) {
                                        // Créer un plan vide pour cette position
                                        $plan = [
                                            'numero_cible' => $numeroCible,
                                            'position_archer' => $position,
                                            'blason' => $blasonCible,
                                            'distance' => $distanceCible
                                        ];
                                    }
                                }
                                // Pour les blasons 80/60, récupérer user_id ou numero_licence des positions
                                $userIdsBlason = [];
                                $licencesBlason = [];
                                if ($dispositionType === 'blason80') {
                                    if ($position === 'A') {
                                        foreach (['A','B','C','D'] as $p) {
                                            $pp = $plansParPosition[$p] ?? null;
                                                if ($pp && $pp['user_nom'] !== null && $pp['numero_licence'] !== null) $userIdsBlason[] = $pp['user_nom'] . '-' . $pp['numero_licence'];
                                            if ($pp && !empty($pp['numero_licence'])) $licencesBlason[] = $pp['numero_licence'];
                                        }
                                    }
                                }
                                if ($dispositionType === 'blason60') {
                                    if ($position === 'A') {
                                        foreach (['A','C'] as $p) {
                                            $pp = $plansParPosition[$p] ?? null;
                                            if ($pp && $pp['user_nom'] !== null && $pp['numero_licence'] !== null) $userIdsBlason[] = $pp['user_nom'] . '-' . $pp['numero_licence'];
                                            if ($pp && !empty($pp['numero_licence'])) $licencesBlason[] = $pp['numero_licence'];
                                        }
                                    } elseif ($position === 'B') {
                                        foreach (['B','D'] as $p) {
                                            $pp = $plansParPosition[$p] ?? null;
                                            if ($pp && $pp['user_nom'] !== null && $pp['numero_licence'] !== null) $userIdsBlason[] = $pp['user_nom'] . '-' . $pp['numero_licence'];
                                            if ($pp && !empty($pp['numero_licence'])) $licencesBlason[] = $pp['numero_licence'];
                                        }
                                    }
                                }
                                
                                $userId = $plan['user_nom'] !== null && $plan['numero_licence'] !== null ? $plan['user_nom'] . '-' . $plan['numero_licence'] : null;
                                $numeroLicence = $plan['numero_licence'] ?? null;
                                if ($dispositionType === 'trispot') {
                                    $isAssigne = $userIdTrispot !== null || $numeroLicenceTrispot !== null;
                                } else {
                                    $isAssigne = $userId !== null || $numeroLicence !== null || !empty($userIdsBlason) || !empty($licencesBlason);
                                }
                                
                                // Récupérer les informations de l'utilisateur
                                $userNom = '';
                                $userPrenom = '';
                                $nomComplet = '';
                                $clubComplet = '';
                                $nomsArchers = [];
                                
                                $inscriptionsMap = $inscriptionsMap ?? [];
                                if ($dispositionType === 'blason80') {
                                    if ($position === 'A') {
                                        foreach (['A','B','C','D'] as $pos) {
                                            $planPos = $plansParPosition[$pos] ?? null;
                                            if ($planPos && ($planPos['user_nom'] !== null && $planPos['numero_licence'] !== null || !empty($planPos['numero_licence']))) {
                                                $info = $getArcherDisplayInfo($planPos, $usersMap, $inscriptionsMap);
                                                if ($info && !empty($info['nomComplet'])) {
                                                    $nomsArchers[] = $info['nomComplet'];
                                                }
                                            }
                                        }
                                        $nomComplet = implode(', ', array_unique($nomsArchers));
                                        if (empty($nomComplet)) $nomComplet = 'Libre';
                                    }
                                } elseif ($dispositionType === 'blason60') {
                                    if ($position === 'A') {
                                        foreach (['A','C'] as $pos) {
                                            $planPos = $plansParPosition[$pos] ?? null;
                                            if ($planPos && ($planPos['user_nom'] !== null && $planPos['numero_licence'] !== null || !empty($planPos['numero_licence']))) {
                                                $info = $getArcherDisplayInfo($planPos, $usersMap, $inscriptionsMap);
                                                if ($info && !empty($info['nomComplet'])) {
                                                    $nomsArchers[] = $info['nomComplet'];
                                                }
                                            }
                                        }
                                    } elseif ($position === 'B') {
                                        foreach (['B','D'] as $pos) {
                                            $planPos = $plansParPosition[$pos] ?? null;
                                            if ($planPos && ($planPos['user_nom'] !== null && $planPos['numero_licence'] !== null || !empty($planPos['numero_licence']))) {
                                                $info = $getArcherDisplayInfo($planPos, $usersMap, $inscriptionsMap);
                                                if ($info && !empty($info['nomComplet'])) {
                                                    $nomsArchers[] = $info['nomComplet'];
                                                }
                                            }
                                        }
                                    }
                                    $nomComplet = implode(', ', array_unique($nomsArchers));
                                    if (empty($nomComplet)) $nomComplet = 'Libre';
                                } elseif ($dispositionType === 'trispot' && ($userIdTrispot !== null || $numeroLicenceTrispot !== null)) {
                                    $info = $getArcherDisplayInfo($plan, $usersMap, $inscriptionsMap);
                                    if ($info) {
                                        $nomComplet = $info['nomComplet'] ?: ($numeroLicenceTrispot ? 'Licence: ' . $numeroLicenceTrispot : 'Libre');
                                        $clubComplet = $info['club'] ?? '';
                                    } else {
                                        $nomComplet = $numeroLicenceTrispot ? 'Licence: ' . $numeroLicenceTrispot : 'Libre';
                                    }
                                } elseif ($isAssigne) {
                                    $info = $getArcherDisplayInfo($plan, $usersMap, $inscriptionsMap);
                                    if ($info) {
                                        $nomComplet = $info['nomComplet'] ?: ($numeroLicence ? 'Licence: ' . $numeroLicence : 'ID: ' . $userId);
                                        $clubComplet = $info['club'] ?? '';
                                    } else {
                                        $nomComplet = $numeroLicence ? 'Licence: ' . $numeroLicence : 'ID: ' . $userId;
                                    }
                                }
                                if ($dispositionType === 'trispot' && !$isAssigne && empty($nomComplet)) {
                                    $nomComplet = 'Libre';
                                }
                            ?>
                            <?php
                                // Utiliser le blason et la distance du plan, ou ceux de la cible par défaut
                                $planBlason = $plan['blason'] ?? $blasonCible;
                                $planDistance = $plan['distance'] ?? $distanceCible;
                            ?>
                            <?php
                                // Pour les blasons 80, déterminer quelles positions afficher
                                $positionsBlason = [];
                                if ($dispositionType === 'blason80') {
                                    if ($position === 'A') {
                                        // Blason gauche : A et C (affichés séparément)
                                        $positionsBlason = ['A', 'B', 'C', 'D'];
                                    }
                                }    
                                if ($dispositionType === 'blason60') {
                                    if ($position === 'A') {
                                        // Blason gauche : A et C (affichés séparément)
                                        $positionsBlason = ['A', 'C'];
                                    } elseif ($position === 'B') {
                                        // Blason droit : B et D (affichés séparément)
                                        $positionsBlason = ['B', 'D'];
                                    }
                                }
                            ?>
                            <?php
                                $tooltipText = '';
                                if ($isAssigne || ($dispositionType === 'blason60' && !empty($nomComplet) && $nomComplet !== 'Libre')) {
                                    $tooltipText = $nomComplet;
                                    if (!empty($clubComplet)) {
                                        $tooltipText .= ' - ' . $clubComplet;
                                    }
                                }
                            ?>
                            <?php
                                $dataUserId = $dispositionType === 'trispot' ? $userIdTrispot : $userId;
                                $dataNumeroLicence = $dispositionType === 'trispot' ? ($numeroLicenceTrispot ?? $numeroLicence) : $numeroLicence;
                                $dataUserNom = $dispositionType === 'trispot' ? ($userNomTrispot ?? ($plan['user_nom'] ?? '')) : ($plan['user_nom'] ?? '');
                                $dataTrispot = ($dispositionType === 'trispot' || $trispotCible == 1 || $trispotCible === '1' || $trispotCible === true || $blasonCible === 'T40') ? '1' : '0';
                                $dataBlason = $planBlason ?? $blasonCible;
                                $dataDistance = $planDistance ?? $distanceCible;
                            ?>
                            <div class="blason-item <?= $isAssigne ? 'assigne' : 'libre' ?> <?= $dispositionType === 'blason80' ? 'blason-80-size' : '' ?> <?= $dispositionType === 'blason60' ? 'blason-60-size' : '' ?>"
                                 data-concours-id="<?= htmlspecialchars($concoursId) ?>"
                                 data-depart="<?= htmlspecialchars($numeroDepart) ?>"
                                 data-cible="<?= htmlspecialchars($numeroCible) ?>"
                                 data-position="<?= htmlspecialchars($position) ?>"
                                 data-colonne="<?= htmlspecialchars($colonne ?? '') ?>"
                                 data-blason="<?= htmlspecialchars($dataBlason ?? '') ?>"
                                 data-trispot="<?= htmlspecialchars($dataTrispot) ?>"
                                 data-distance="<?= htmlspecialchars($dataDistance ?? '') ?>"
                                 data-user-id="<?= htmlspecialchars($dataUserId ?? '') ?>"
                                 data-numero-licence="<?= htmlspecialchars($dataNumeroLicence ?? '') ?>"
                                 data-user-nom="<?= htmlspecialchars($dataUserNom ?? '') ?>"
                                 data-assignable="<?= $isAssigne ? '0' : '1' ?>"
                                <?= !empty($tooltipText) ? ' title="' . htmlspecialchars($tooltipText) . '"' : '' ?>>
                                <?php if ($dispositionType === 'trispot'): ?>
                                    <!-- Pour les trispots, afficher le numéro du blason (1, 2, 3) au lieu du numéro de la cible -->
                                    <?php
                                    // Mapper les colonnes (A, C, B, D) aux numéros de blason (1, 2, 3, 4)
                                    $blasonTrispotMap = ['A' => 1, 'C' => 2, 'B' => 3, 'D' => 4];
                                    $blasonNumber = isset($blasonTrispotMap[$colonne]) ? $blasonTrispotMap[$colonne] : '?';
                                    ?>
                                    <div class="blason-numero"><?= htmlspecialchars($blasonNumber) ?></div>
                                <?php else: ?>
                                    <div class="blason-numero"><?= htmlspecialchars($numeroCible) ?></div>
                                <?php endif; ?>
                                <?php if ($dispositionType === 'blason80' && !empty($positionsBlason)): ?>
                                    <!-- Pour les blasons 60, afficher deux badges séparés -->
                                    <div class="blason-positions-container">
                                        <?php foreach ($positionsBlason as $pos): ?>
                                            <div class="blason-position"><?= htmlspecialchars($pos) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($dispositionType === 'blason60' && !empty($positionsBlason)): ?>
                                    <!-- Pour les blasons 60, afficher deux badges séparés -->
                                    <div class="blason-positions-container">
                                        <?php foreach ($positionsBlason as $pos): ?>
                                            <div class="blason-position"><?= htmlspecialchars($pos) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($dispositionType !== 'blason60' && $dispositionType !== 'blason80'): ?>
                                    <!-- Position standard (pour trispot et autres) -->
                                    <?php 
                                    // Pour les trispots, afficher seulement la colonne (A, C, B, D)
                                    $positionDisplay = $position;
                                    if ($dispositionType === 'trispot' && $colonne !== null) {
                                        $positionDisplay = $colonne;
                                    }
                                    ?>
                                    <div class="blason-position"><?= htmlspecialchars($positionDisplay) ?></div>
                                <?php endif; ?>
                                <!-- Taille du blason -->                                
                                <?php if ($planBlason !== null): ?>
                                    <div class="blason-taille"><?= htmlspecialchars($planBlason) ?></div>
                                <?php else: ?>
                                    <div class="blason-taille" style="font-size: 0.9em; color: #adb5bd;">-</div>
                                <?php endif; ?>
                                <!-- Distance -->
                                <?php if ($planDistance !== null): ?>
                                    <div class="blason-distance"><?= htmlspecialchars($planDistance) ?>m</div>
                                <?php endif; ?>
                                
                                <?php
                                // Pour les trispots, afficher le nom seulement sur la première position de chaque colonne (A1, C1, B1, D1)
                                $afficherNom = true;
                                if ($dispositionType === 'trispot' && $colonne !== null && preg_match('/^([A-D])(\d+)$/', $position, $matches)) {
                                    $ligne = (int)$matches[2];
                                    // Afficher le nom seulement si c'est la première ligne (ligne 1)
                                    $afficherNom = ($ligne === 1);
                                }
                                ?>
                                
                                <!-- Nom de l'archer -->
                                <?php if ($afficherNom && ($isAssigne || ($dispositionType === 'blason60' && !empty($nomComplet) && $nomComplet !== 'Libre'))): ?>
                                    <?php if (!empty($nomComplet) && $nomComplet !== 'Libre'): ?>
                                        <div class="blason-archer-name"><?= htmlspecialchars($nomComplet) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($afficherNom && ($isAssigne || ($dispositionType === 'blason60' && !empty($nomComplet) && $nomComplet !== 'Libre'))): ?>
                                    <span class="visually-hidden"><?= htmlspecialchars($tooltipText) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php } ?>
                        </div>
                        
                        <!-- Sélecteur de type de blason en bas du cadre -->
                        <div class="blason-type-select">
                            <form method="post" action="/concours/plan-cible-type-blason" class="blason-type-form">
                                <input type="hidden" name="concours_id" value="<?= htmlspecialchars($concoursId) ?>">
                                <input type="hidden" name="numero_depart" value="<?= htmlspecialchars($numeroDepart) ?>">
                                <input type="hidden" name="numero_cible" value="<?= htmlspecialchars($numeroCible) ?>">
                                <input type="hidden" name="trispot" value="<?= htmlspecialchars($trispotCible ? '1' : '0') ?>" class="trispot-flag">
                                <label for="blason-type-<?= htmlspecialchars($numeroDepart) ?>-<?= htmlspecialchars($numeroCible) ?>" style="font-weight: 500; margin-right: 8px;">Type de blason :</label>
                                <select name="blason_type" class="blason-type-select-dropdown" id="blason-type-<?= htmlspecialchars($numeroDepart) ?>-<?= htmlspecialchars($numeroCible) ?>" style="display: inline-block; width: auto;" <?= $cibleHasAssigned ? 'disabled' : '' ?>>
                                    <option value="80" data-trispot="0" <?= ($blasonCible == 80 && !$trispotCible) ? 'selected' : '' ?>>Blason 80</option>
                                    <option value="60" data-trispot="0" <?= ($blasonCible == 60 && !$trispotCible) ? 'selected' : '' ?>>Blason 60</option>
                                    <option value="40" data-trispot="0" <?= ($blasonCible == 40 && !$trispotCible) ? 'selected' : '' ?>>Blason 40</option>
                                    <option value="40" data-trispot="1" <?= ($blasonCible == 40 && $trispotCible) ? 'selected' : '' ?>>Trispot 40</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary" style="margin-left: 8px;" <?= $cibleHasAssigned ? 'disabled' : '' ?>>Enregistrer</button>
                            </form>
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

<div class="modal fade" id="blasonAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Affecter un archer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div id="blason-modal-info" class="text-muted"></div>
                </div>
                <div id="blason-modal-release" class="mb-3" style="display: none;">
                    <div class="alert alert-warning d-flex justify-content-between align-items-center">
                        <span>Emplacement deja affecte.</span>
                        <button type="button" class="btn btn-sm btn-danger" id="btn-liberer-emplacement">Liberer l'emplacement</button>
                    </div>
                </div>
                <div id="blason-archers-list" class="list-group"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>