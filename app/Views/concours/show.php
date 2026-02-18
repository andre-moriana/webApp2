<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Affichage d'un concours (lecture seule) -->
<div class="container-fluid concours-create-container" id="concours-show-page" data-config="<?= htmlspecialchars(json_encode([
    'concoursId' => $concours->id ?? $concours->_id ?? $id ?? null,
    'concoursData' => $concours
], JSON_UNESCAPED_UNICODE)) ?>">
<h1>Détails du concours</h1>

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
// Fonction helper pour trouver un libellé par ID
function findLabel($items, $id, $idField = 'id', $labelField = 'name') {
    if (!is_array($items) || !$id) return '';
    foreach ($items as $item) {
        $itemId = $item[$idField] ?? $item['_id'] ?? $item['iddiscipline'] ?? $item['idformat_competition'] ?? $item['abv_niveauchampionnat'] ?? null;
        if ($itemId == $id || (string)$itemId === (string)$id) {
            return $item[$labelField] ?? $item['lb_discipline'] ?? $item['lb_format_competition'] ?? $item['lb_niveauchampionnat'] ?? $item['name'] ?? '';
        }
    }
    return '';
}

// Trouver les libellés
$clubName = findLabel($clubs, $concours->club_organisateur ?? null, 'id', 'name');
$disciplineName = findLabel($disciplines, $concours->discipline ?? null, 'iddiscipline', 'lb_discipline');
$typeCompetitionName = findLabel($typeCompetitions, $concours->type_competition ?? null, 'idformat_competition', 'lb_format_competition');
$niveauChampionnatName = findLabel($niveauChampionnat, $concours->idniveau_championnat ?? null, 'idniveau_championnat', 'lb_niveauchampionnat');
?>

<!-- Section principale -->
<div class="form-section">
    <!-- Club Organisateur -->
    <div class="form-group">
        <label><strong>Club Organisateur :</strong></label>
        <div class="club-organisateur-fields">
            <p><?= htmlspecialchars($clubName ?: ($concours->club_name ?? 'Non renseigné')) ?></p>
            <?php if (isset($concours->agreenum) && $concours->agreenum): ?>
                <p><small>Code: <?= htmlspecialchars($concours->agreenum) ?></small></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Discipline -->
    <div class="form-group">
        <label><strong>Discipline :</strong></label>
        <p><?= htmlspecialchars($disciplineName ?: 'Non renseigné') ?></p>
    </div>

    <!-- Type Compétition -->
    <div class="form-group">
        <label><strong>Type Compétition :</strong></label>
        <p><?= htmlspecialchars($typeCompetitionName ?: 'Non renseigné') ?></p>
    </div>

    <!-- Niveau Championnat -->
    <div class="form-group">
        <label><strong>Niveau Championnat :</strong></label>
        <p><?= htmlspecialchars($niveauChampionnatName ?: 'Non renseigné') ?></p>
    </div>

    <!-- Titre Compétition -->
    <div class="form-group">
        <label><strong>Titre Compétition :</strong></label>
        <p><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Non renseigné') ?></p>
    </div>

    <!-- Lieu Compétition -->
    <div class="form-group">
        <label><strong>Lieu Compétition :</strong></label>
        <div class="lieu-display">
            <p><?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?></p>
            <?php if (isset($concours->lieu_latitude) && isset($concours->lieu_longitude) && $concours->lieu_latitude && $concours->lieu_longitude): ?>
                <p><small>Coordonnées GPS : <?= htmlspecialchars($concours->lieu_latitude) ?>, <?= htmlspecialchars($concours->lieu_longitude) ?></small></p>
                <button type="button" class="btn btn-sm btn-primary" id="btn-show-map" onclick="openMapModal()">
                    <i class="fas fa-map-marker-alt"></i> Afficher sur la carte
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dates -->
    <div class="date-fields-row">
        <div class="form-group">
            <label><strong>Début Compétition :</strong></label>
            <p><?= htmlspecialchars($concours->date_debut ?? 'Non renseigné') ?></p>
        </div>
        <div class="form-group">
            <label><strong>Fin Compétition :</strong></label>
            <p><?= htmlspecialchars($concours->date_fin ?? 'Non renseigné') ?></p>
        </div>
    </div>

    <!-- Nombre cibles/pelotons, tireurs -->
    <?php
    // Fonction helper pour déterminer si c'est un concours de type nature 3D ou campagne
    function isNature3DOrCampagneShow($disciplineId, $disciplines) {
        if (!$disciplineId || !is_array($disciplines)) {
            return false;
        }
        foreach ($disciplines as $discipline) {
            $id = $discipline['iddiscipline'] ?? $discipline['id'] ?? null;
            if ($id == $disciplineId || (string)$id === (string)$disciplineId) {
                $name = strtolower($discipline['lb_discipline'] ?? $discipline['name'] ?? '');
                return (strpos($name, 'nature') !== false || 
                        strpos($name, '3d') !== false || 
                        strpos($name, 'campagne') !== false);
            }
        }
        return false;
    }
    
    // Récupérer l'ID de la discipline
    $selectedDisciplineId = $concours->discipline ?? null;
    $isNature3DOrCampagne = isNature3DOrCampagneShow($selectedDisciplineId, $disciplines ?? []);
    
    // Labels conditionnels
    $labelCibles = $isNature3DOrCampagne ? 'Nombre pelotons' : 'Nombre cibles';
    $labelTireurs = $isNature3DOrCampagne ? 'Nombre tireurs par peloton' : 'Nombre tireurs par cibles';
    ?>
    <div class="numeric-fields-row">
        <div class="form-group">
            <label><strong><?= htmlspecialchars($labelCibles) ?> :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_cibles ?? 0) ?></p>
        </div>
        <div class="form-group">
            <label><strong><?= htmlspecialchars($labelTireurs) ?> :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_tireurs_par_cibles ?? 0) ?></p>
        </div>
    </div>

    <!-- Liste des départs -->
    <?php
    $departsList = is_object($concours) ? ($concours->departs ?? []) : ($concours['departs'] ?? []);
    if (is_object($departsList)) {
        $departsList = array_values((array)$departsList);
    }
    $departsList = is_array($departsList) ? $departsList : [];
    $getD = function($d, $key, $default = '') {
        return is_array($d) ? ($d[$key] ?? $default) : ($d->$key ?? $default);
    };
    ?>
    <?php if (!empty($departsList)): ?>
    <div class="form-group" style="margin-top: 20px;">
        <label><strong>Liste des départs :</strong></label>
        <ul class="list-group list-group-flush" style="max-width: 400px;">
            <?php foreach ($departsList as $d): ?>
                <?php
                $dateDep = $getD($d, 'date_depart', '');
                $heureGreffe = $getD($d, 'heure_greffe', '');
                $numero = (int)$getD($d, 'numero_depart', 0);
                if ($dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
                    $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
                }
                $heureGreffe = $heureGreffe ? substr((string)$heureGreffe, 0, 5) : '';
                $label = trim($dateDep . ($heureGreffe ? ' à ' . $heureGreffe : ''));
                if (empty($label)) $label = 'Départ ' . $numero;
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-flag text-muted me-2"></i><?= htmlspecialchars($label) ?></span>
                    <?php if ($numero): ?><span class="badge bg-secondary">N°<?= $numero ?></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Bouton pour créer les plans de cible (uniquement pour les disciplines S, T, I, H) -->
    <?php
    // Vérifier si la discipline est S, T, I ou H
    $abv_discipline_show = null;
    if ($selectedDisciplineId && is_array($disciplines)) {
        foreach ($disciplines as $disc) {
            $discId = $disc['iddiscipline'] ?? $disc['id'] ?? null;
            if ($discId == $selectedDisciplineId || (string)$discId === (string)$selectedDisciplineId) {
                $abv_discipline_show = $disc['abv_discipline'] ?? null;
                break;
            }
        }
    }
    
    $canShowPlanCibleSection = in_array($abv_discipline_show, ['S', 'T', 'I', 'H']) && 
                          ($concours->nombre_cibles ?? 0) > 0 && 
                          ($concours->nombre_tireurs_par_cibles ?? 0) > 0;
    $canCreatePlanCible = $canShowPlanCibleSection && !($planCibleExists ?? false);
    // Plan peloton : utiliser isNature3DOrCampagne (détection par nom) car abv_discipline peut varier (3, 3D, N, etc.)
    $canShowPlanPelotonSection = in_array($abv_discipline_show, ['3', 'N', 'C']) && 
                                 ($concours->nombre_cibles ?? 0) > 0 && 
                                 ($concours->nombre_tireurs_par_cibles ?? 0) > 0;
    $canCreatePlanPeloton = $canShowPlanPelotonSection && !($planPelotonExists ?? false);
    ?>
    
    <?php if ($canShowPlanCibleSection): ?>
    <div class="form-group" style="margin-top: 20px;">
    <?php if ($canCreatePlanCible): ?>
        <button type="button" class="btn btn-primary" id="btn-create-plan-cible" onclick="createPlanCible()">
            <i class="fas fa-bullseye"></i> Créer le plan de cible
        </button>
        <?php endif; ?>
        <a href="/concours/<?= htmlspecialchars($concours->id ?? $concours->_id ?? '') ?>/plan-cible" class="btn btn-outline-primary ms-2">
            <i class="fas fa-list"></i> Voir le plan de cible
        </a>
        <div id="plan-cible-message" style="margin-top: 10px;"></div>
    </div>
    <?php endif; ?>
    
    <?php if ($canShowPlanPelotonSection): ?>
    <div class="form-group" style="margin-top: 20px;">
        <?php if ($canCreatePlanPeloton): ?>
        <button type="button" class="btn btn-primary" id="btn-create-plan-peloton" onclick="createPlanPeloton()">
            <i class="fas fa-users"></i> Créer le plan de peloton
        </button>
        <?php endif; ?>
        <a href="/concours/<?= htmlspecialchars($concours->id ?? $concours->_id ?? '') ?>/plan-peloton" class="btn btn-outline-primary <?= $canCreatePlanPeloton ? 'ms-2' : '' ?>">
            <i class="fas fa-list"></i> Voir le plan de peloton
        </a>
        <div id="plan-peloton-message" style="margin-top: 10px;"></div>
    </div>
    <?php endif; ?>

    <?php
    $isDirigeant = isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'Dirigeant';
    if ($isDirigeant): ?>
    <div class="form-group" style="margin-top: 20px;">
        <a href="/concours/<?= htmlspecialchars($concours->id ?? $concours->_id ?? '') ?>/buvette" class="btn btn-outline-primary">
            <i class="fas fa-coffee"></i> Gestion de la buvette
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($concours->lien_inscription_cible)): ?>
    <div class="form-group" style="margin-top: 20px;">
        <label><strong>Lien inscription ciblé :</strong></label>
        <div class="lien-inscription-cible-qr d-flex align-items-start gap-3 flex-wrap">
            <div class="qr-code-wrapper">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($concours->lien_inscription_cible) ?>" alt="QR Code inscription ciblé" class="qr-code-img" title="<?= htmlspecialchars($concours->lien_inscription_cible) ?>">
            </div>
            <div class="qr-link-info">
                <a href="<?= htmlspecialchars($concours->lien_inscription_cible) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt"></i> Ouvrir le lien
                </a>
                <p class="text-muted small mt-2 mb-0">Scannez le QR code pour accéder au formulaire d'inscription</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Liste des inscrits -->
<?php
// Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
$isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
$currentUserLicence = trim((string)($_SESSION['user']['licenceNumber'] ?? $_SESSION['user']['licence_number'] ?? $_SESSION['user']['numero_licence'] ?? ''));
$currentUserId = $_SESSION['user']['id'] ?? $_SESSION['user']['userId'] ?? $_SESSION['user']['_id'] ?? null;

// DEBUG - À supprimer après résolution : ajouter ?debug_licence=1 à l'URL pour afficher les valeurs
$debugLicence = isset($_GET['debug_licence']);
?>
<?php if ($debugLicence): ?>
<div class="alert alert-info small">
    <strong>Debug licence (session user) :</strong><br>
    licenceNumber = <?= json_encode($_SESSION['user']['licenceNumber'] ?? 'NON DÉFINI') ?><br>
    licence_number = <?= json_encode($_SESSION['user']['licence_number'] ?? 'NON DÉFINI') ?><br>
    numero_licence = <?= json_encode($_SESSION['user']['numero_licence'] ?? 'NON DÉFINI') ?><br>
    <strong>currentUserLicence :</strong> <?= json_encode($currentUserLicence) ?>
    <?php if ($currentUserLicence === ''): ?><span class="text-danger">← VIDE</span><?php endif; ?><br>
    <strong>currentUserId (fallback) :</strong> <?= json_encode($currentUserId) ?>
</div>
<script>
(function() {
    var debug = {
        licenceNumber: <?= json_encode($_SESSION['user']['licenceNumber'] ?? null) ?>,
        licence_number: <?= json_encode($_SESSION['user']['licence_number'] ?? null) ?>,
        numero_licence: <?= json_encode($_SESSION['user']['numero_licence'] ?? null) ?>,
        currentUserLicence: <?= json_encode($currentUserLicence) ?>,
        currentUserId: <?= json_encode($currentUserId) ?>
    };
    console.log('[Concours Debug Licence]', debug);
    document.querySelectorAll('tr[data-debug-licence]').forEach(function(tr) {
        console.log('[Ligne inscription] licence=' + tr.getAttribute('data-debug-licence') + ' own=' + tr.getAttribute('data-debug-own') + ' canEdit=' + tr.getAttribute('data-debug-can-edit'));
    });
})();
</script>
<?php endif; ?>
<div class="inscriptions-section">
    <h2>Liste des inscrits</h2>
    
    <?php if (empty($inscriptions)): ?>
        <p class="alert alert-info">Aucune inscription pour ce concours.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Nom - Prénom</th>
                            <th>Numéro de licence</th>
                            <th>Club</th>
                            <th>Départ</th>
                            <th>N°Tir</th>
                            <?php if ($isNature3DOrCampagne): ?>
                                <th>Piquet</th>
                            <?php else: ?>
                                <th>Distance</th>
                                <th>Blason</th>
                            <?php endif; ?>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inscriptions-list">
                        <?php 
                        // $usersMap est passé depuis le contrôleur
                        foreach ($inscriptions as $inscription):
                            $inscriptionLicence = trim((string)($inscription['numero_licence'] ?? ''));
                            $inscriptionUserId = $inscription['user_id'] ?? null;
                            // Identification : par numéro de licence (prioritaire) ou par user_id si licence absent
                            $isOwnInscription = ($currentUserLicence !== '' && $inscriptionLicence !== '' && $currentUserLicence === $inscriptionLicence)
                                || ($currentUserId && $inscriptionUserId && (string)$currentUserId === (string)$inscriptionUserId);
                            $canManageInscription = $isDirigeant && !$isOwnInscription; // Dirigeant : actions sur les inscriptions des autres
                            $canEditDeleteInscription = $canManageInscription || $isOwnInscription; // Dirigeant sur autres OU archer sur sa propre inscription
                            $userNom = $inscription['user_nom'] ?? null;
                            // Récupérer la couleur du piquet pour les disciplines 3D, Nature et Campagne
                            $piquetColorRaw = $inscription['piquet'] ?? null;
                            $piquetColor = null;
                            $rowStyleParts = [];

                            // Couleur de fond selon piquet (3D/Nature/Campagne)
                            if ($piquetColorRaw && $piquetColorRaw !== '') {
                                $piquetColor = trim(strtolower($piquetColorRaw));
                                $colors = ['rouge' => '#ffe0e0', 'bleu' => '#e0e8ff', 'blanc' => '#f5f5f5'];
                                if (isset($colors[$piquetColor])) {
                                    $rowStyleParts[] = 'background-color: ' . $colors[$piquetColor] . ' !important';
                                }
                            }

                            $rowStyle = !empty($rowStyleParts) ? ' style="' . implode('; ', $rowStyleParts) . '"' : '';
                       ?>
                            <?php
                            $statut = $inscription['statut_inscription'] ?? 'en_attente';
                            $inscId = $inscription['id'] ?? '';
                            if ($statut === 'confirmee') {
                                $statutIcon = 'fa-check-circle text-success';
                                $statutTitle = 'Confirmée';
                            } elseif (in_array($statut, ['refuse', 'annule'], true)) {
                                $statutIcon = 'fa-times-circle text-danger';
                                $statutTitle = $statut === 'refuse' ? 'Refusée' : 'Annulée';
                            } else {
                                $statutIcon = 'fa-clock text-warning';
                                $statutTitle = 'En attente';
                            }
                            ?>
                            <tr data-inscription-id="<?= htmlspecialchars($inscription['id'] ?? '') ?>"<?php if ($debugLicence): ?> data-debug-licence="<?= htmlspecialchars($inscriptionLicence) ?>" data-debug-own="<?= $isOwnInscription ? '1' : '0' ?>" data-debug-can-edit="<?= $canEditDeleteInscription ? '1' : '0' ?>"<?php endif; ?>>
                            <td class="statut-cell"<?= $rowStyle ?>>
                                    <?php if ($canManageInscription): ?>
                                    <div class="dropdown statut-dropdown" data-inscription-id="<?= htmlspecialchars($inscId) ?>">
                                        <button class="btn btn-link p-0 border-0 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars($statutTitle) ?>">
                                            <i class="fas <?= $statutIcon ?>"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="en_attente" data-inscription-id="<?= htmlspecialchars($inscId) ?>"><i class="fas fa-clock text-warning me-2"></i>En attente</a></li>
                                            <li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="confirmee" data-inscription-id="<?= htmlspecialchars($inscId) ?>"><i class="fas fa-check-circle text-success me-2"></i>Confirmée</a></li>
                                            <li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="refuse" data-inscription-id="<?= htmlspecialchars($inscId) ?>"><i class="fas fa-times-circle text-danger me-2"></i>Refusée</a></li>
                                            <li><a class="dropdown-item statut-dropdown-item" href="#" data-statut="annule" data-inscription-id="<?= htmlspecialchars($inscId) ?>"><i class="fas fa-times-circle text-danger me-2"></i>Annulée</a></li>
                                        </ul>
                                    </div>
                                    <?php else: ?>
                                    <span title="<?= htmlspecialchars($statutTitle) ?>"><i class="fas <?= $statutIcon ?>"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($userNom ?? '') ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscriptionLicence ?: 'N/A') ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['club_name'] ?? 'N/A') ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['numero_depart'] ?? 'N/A') ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['numero_tir'] ?? 'N/A') ?></td>
                                <?php if ($isNature3DOrCampagne): ?>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($piquetColor ? ucfirst($piquetColor) : 'N/A') ?></td>
                                <?php else: ?>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['distance'] ?? 'N/A') ?></td>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['blason'] ?? 'N/A') ?> <?= htmlspecialchars($inscription['trispot'] ? 'T' : '') ?></td>
                                <?php endif; ?>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['created_at'] ?? $inscription['date_inscription'] ?? 'N/A') ?></td>
                                <td<?= $rowStyle ?>>
                                    <?php if ($canEditDeleteInscription): ?>
                                    <a href="/concours/<?= htmlspecialchars($concours->id ?? $concours->_id ?? '') ?>/inscription" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

        </div>
        <p><strong>Total d'inscrits :</strong> <?= count($inscriptions) ?></p>
    <?php endif; ?>
</div>

<div class="actions-section">
    <a href="/concours" class="btn btn-secondary">Retour à la liste</a>
    <?php if (isset($concours->id) || isset($concours->_id)): ?>
        <?php $concoursId = $concours->id ?? $concours->_id; ?>
        <a href="/concours/<?= htmlspecialchars($concoursId) ?>/inscription" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Gérer les inscriptions
        </a>
    <?php endif; ?>
</div>
</div>

<!-- Modale pour afficher la carte (lecture seule) -->
<?php if (isset($concours->lieu_latitude) && isset($concours->lieu_longitude) && $concours->lieu_latitude && $concours->lieu_longitude): ?>
<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true"
     data-lat="<?= htmlspecialchars((float)$concours->lieu_latitude) ?>"
     data-lng="<?= htmlspecialchars((float)$concours->lieu_longitude) ?>"
     data-address="<?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalLabel">Localisation du concours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Adresse :</strong>
                    <p><?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?></p>
                    <small>Coordonnées GPS : <?= htmlspecialchars($concours->lieu_latitude) ?>, <?= htmlspecialchars($concours->lieu_longitude) ?></small>
                </div>
                <div id="map-show-container" style="height: 500px; width: 100%; border: 1px solid #ddd; border-radius: 4px;"></div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-route"></i> Créer un itinéraire
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="createItinerary('google'); return false;">
                                    <i class="fab fa-google"></i> Google Maps
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="createItinerary('osm'); return false;">
                                    <i class="fas fa-map"></i> OpenStreetMap
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="createItinerary('waze'); return false;">
                                    <i class="fas fa-car"></i> Waze
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="createItinerary('native'); return false;">
                                    <i class="fas fa-mobile-alt"></i> Application de navigation
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<!-- Modale pour éditer une inscription -->
<div class="modal fade" id="editInscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Éditer l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="edit-modal-body">
                <form id="edit-inscription-form">
                    <h5 class="mb-3">Informations d'inscription</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-saison" class="form-label">Saison</label>
                            <input type="text" id="edit-saison" class="form-control" placeholder="Ex: 2024-2025">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-type_certificat_medical" class="form-label">Type Certificat Médical</label>
                            <select id="edit-type_certificat_medical" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="Compétition">Compétition</option>
                                <option value="Pratique">Pratique</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-type_licence" class="form-label">Type Licence</label>
                            <select id="edit-type_licence" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="L">L</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-creation_renouvellement" class="form-label">Création/Renouvellement</label>
                            <select id="edit-creation_renouvellement" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="C">Création</option>
                                <option value="R">Renouvellement</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (!empty($departs)): ?>
                    <div class="mb-3">
                        <label for="edit-depart-select" class="form-label">Date et heure du greffe</label>
                        <select id="edit-depart-select" class="form-control">
                            <option value="">Sélectionner un départ</option>
                            <?php foreach ($departs as $depart): ?>
                                <?php
                                $dateDep = $depart['date_depart'] ?? $depart['date'] ?? '';
                                $heureGreffe = $depart['heure_greffe'] ?? $depart['heure'] ?? '';
                                $numero = (int)($depart['numero_depart'] ?? $depart['numero'] ?? 0);
                                if ($dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
                                    $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
                                }
                                $heureGreffe = $heureGreffe ? substr($heureGreffe, 0, 5) : '';
                                $label = trim($dateDep . ($heureGreffe ? ' ' . $heureGreffe : ''));
                                if (empty($label)) $label = 'Départ ' . $numero;
                                ?>
                                <option value="<?= $numero ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <h6 class="mt-4 mb-3">Classification et équipement</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit-categorie_classement" class="form-label">Catégorie de classement</label>
                            <select id="edit-categorie_classement" class="form-control">
                                <option value="">Sélectionner une catégorie</option>
                                <?php if (!empty($categoriesClassement)): ?>
                                    <?php foreach ($categoriesClassement as $categorie): ?>
                                        <option value="<?= htmlspecialchars($categorie['abv_categorie_classement'] ?? '') ?>">
                                            <?= htmlspecialchars($categorie['lb_categorie_classement'] ?? '') ?> (<?= htmlspecialchars($categorie['abv_categorie_classement'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit-arme" class="form-label">Arme (utilisée sur le pas de tir)</label>
                            <select id="edit-arme" class="form-control">
                                <option value="">Sélectionner</option>
                                <?php if (!empty($arcs)): ?>
                                    <?php foreach ($arcs as $arc): ?>
                                        <option value="<?= htmlspecialchars($arc['lb_arc'] ?? '') ?>">
                                            <?= htmlspecialchars($arc['lb_arc'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" id="edit-mobilite_reduite" class="form-check-input">
                                <label for="edit-mobilite_reduite" class="form-check-label">Mobilité réduite</label>
                            </div>
                        </div>
                        <?php if ($isNature3DOrCampagne): ?>
                            <div class="col-md-3 mb-3">
                                <label for="edit-piquet" class="form-label">Piquet</label>
                                <select id="edit-piquet" name="piquet" class="form-control">
                                    <option value="">Sélectionner</option>
                                    <option value="rouge">Rouge</option>
                                    <option value="bleu">Bleu</option>
                                    <option value="blanc">Blanc</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-3 mb-3">
                                <label for="edit-distance" class="form-label">Distance</label>
                                <select id="edit-distance" class="form-control">
                                    <option value="">Sélectionner</option>
                                    <?php if (!empty($distancesTir)): ?>
                                        <?php foreach ($distancesTir as $distance): ?>
                                            <option value="<?= htmlspecialchars($distance['distance_valeur'] ?? '') ?>">
                                                <?= htmlspecialchars($distance['lb_distance'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <?php if (!$isNature3DOrCampagne): ?>
                            <div class="col-md-3 mb-3">
                                <label for="edit-blason" class="form-label">Blason</label>
                                <input type="number" id="edit-blason" class="form-control" min="0" placeholder="Ex: 40">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$isNature3DOrCampagne): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" id="edit-duel" class="form-check-input">
                                    <label for="edit-duel" class="form-check-label">Duel</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" id="edit-trispot" class="form-check-input">
                                    <label for="edit-trispot" class="form-check-label">Trispot</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-4 mb-3"><i class="fas fa-coffee"></i> Buvette</h6>
                    <p class="text-muted small mb-2">Réservation optionnelle des articles de la buvette</p>
                    <div id="edit-buvette-produits-container" class="border rounded p-3 bg-light">
                        <div id="edit-buvette-loading" class="text-center py-2 text-muted small">Chargement des produits...</div>
                        <div id="edit-buvette-produits-list" class="d-none"></div>
                        <div id="edit-buvette-empty" class="text-muted small d-none">Aucun produit disponible pour ce concours.</div>
                        <div id="edit-buvette-no-token" class="text-muted small d-none">Les réservations buvette ne sont pas disponibles pour cette inscription (token manquant).</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-edit">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/public/assets/js/concours-show-map.js"></script>
<?php endif; ?>

<script src="/public/assets/js/concours-show.js"></script>
