<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">

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
    <?php foreach ($plans as $numeroDepart => $departPlans): ?>
        <div class="plan-depart-section" style="margin-bottom: 30px;">
            <h2>Départ <?= htmlspecialchars($numeroDepart) ?></h2>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Cible</th>
                            <?php
                            // Générer les en-têtes de colonnes pour les positions (A, B, C, D, etc.)
                            for ($i = 1; $i <= $nombreTireursParCibles; $i++) {
                                $position = chr(64 + $i); // 1->A, 2->B, etc.
                                echo '<th>Position ' . htmlspecialchars($position) . '</th>';
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
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
                        
                        // Afficher chaque cible
                        foreach ($plansParCible as $numeroCible => $ciblePlans): 
                            // Trier les plans par position (A, B, C, D...)
                            usort($ciblePlans, function($a, $b) {
                                $posA = $a['position_archer'] ?? '';
                                $posB = $b['position_archer'] ?? '';
                                return strcmp($posA, $posB);
                            });
                        ?>
                        <?php
                        // Récupérer le blason et la distance de cette cible (toutes les positions ont les mêmes valeurs)
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
                        ?>
                        <tr>
                            <td>
                                <strong>Cible <?= htmlspecialchars($numeroCible) ?></strong>
                                <?php if ($blasonCible !== null || $distanceCible !== null): ?>
                                    <br><small class="text-muted">
                                        <?php if ($blasonCible !== null): ?>
                                            <i class="fas fa-bullseye"></i> Blason: <?= htmlspecialchars($blasonCible) ?>
                                        <?php endif; ?>
                                        <?php if ($blasonCible !== null && $distanceCible !== null): ?>
                                            <br>
                                        <?php endif; ?>
                                        <?php if ($distanceCible !== null): ?>
                                            <i class="fas fa-ruler"></i> Distance: <?= htmlspecialchars($distanceCible) ?>m
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <?php
                            // Créer un tableau associatif position => plan pour faciliter l'accès
                            $plansParPosition = [];
                            foreach ($ciblePlans as $plan) {
                                $position = $plan['position_archer'] ?? '';
                                $plansParPosition[$position] = $plan;
                            }
                            
                            // Afficher chaque position (A, B, C, D, etc.)
                            for ($i = 1; $i <= $nombreTireursParCibles; $i++) {
                                $position = chr(64 + $i); // 1->A, 2->B, etc.
                                $plan = $plansParPosition[$position] ?? null;
                                $userId = $plan['user_id'] ?? null;
                                
                                echo '<td>';
                                if ($userId) {
                                    echo '<span class="badge bg-success">Assigné</span><br>';
                                    echo '<small>User ID: ' . htmlspecialchars($userId) . '</small>';
                                } else {
                                    echo '<span class="badge bg-secondary">Libre</span>';
                                }
                                echo '</td>';
                            }
                            ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
