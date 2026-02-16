<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-show.css" rel="stylesheet">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Affichage d'un concours (lecture seule) -->
<div class="container-fluid concours-create-container">
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

    <!-- Nombre cibles/pelotons, départ, tireurs -->
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
            <label><strong>Nombre départ :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_depart ?? 1) ?></p>
        </div>
        <div class="form-group">
            <label><strong><?= htmlspecialchars($labelTireurs) ?> :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_tireurs_par_cibles ?? 0) ?></p>
        </div>
    </div>
    
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
    
    $canCreatePlanCible = in_array($abv_discipline_show, ['S', 'T', 'I', 'H']) && 
                          ($concours->nombre_cibles ?? 0) > 0 && 
                          ($concours->nombre_tireurs_par_cibles ?? 0) > 0;
    ?>
    
    <?php if ($canCreatePlanCible): ?>
    <div class="form-group" style="margin-top: 20px;">
        <button type="button" class="btn btn-primary" id="btn-create-plan-cible" onclick="createPlanCible()">
            <i class="fas fa-bullseye"></i> Créer le plan de cible
        </button>
        <div id="plan-cible-message" style="margin-top: 10px;"></div>
    </div>
    <?php endif; ?>
</div>

<!-- Liste des inscrits -->
<?php
// Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
$isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
?>
<div class="inscriptions-section">
    <h2>Liste des inscrits</h2>
    
    <?php if (empty($inscriptions)): ?>
        <p class="alert alert-info">Aucune inscription pour ce concours.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
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
                            $userNom = $inscription['user_nom'] ?? null;
                            $userNumeroLicence = $inscription['numero_licence'] ?? null;
                            // Récupérer la couleur du piquet pour les disciplines 3D, Nature et Campagne
                            $piquetColorRaw = $inscription['piquet'] ?? null;
                            $piquetColor = null;
                            $rowStyle = '';
                            
                            if ($piquetColorRaw && $piquetColorRaw !== '') {
                                $piquetColor = trim(strtolower($piquetColorRaw));
                                $colors = ['rouge' => '#ffe0e0', 'bleu' => '#e0e8ff', 'blanc' => '#f5f5f5'];
                                $rowStyle = ' style="';
                                if (isset($colors[$piquetColor])) {
                                    $rowStyle .= 'background-color: ' . $colors[$piquetColor] . ' !important;';
                                }
                                if (isset($inscription['blason']) && $inscription['blason'] !== null) {
                                    $rowStyle .= ' font-weight: bold; ';
                                }
                                $rowStyle .= '"';
                            }
                       ?>
                            <tr data-inscription-id="<?= htmlspecialchars($inscription['id'] ?? '') ?>">
                                <td<?= $rowStyle ?>><?= htmlspecialchars($userNom ) ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($userNumeroLicence) ?></td>
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
                                    <a href="/concours/<?= htmlspecialchars($concours->id ?? $concours->_id ?? '') ?>/inscription" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-edit"></i>                                     </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
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

<!-- Scripts pour la carte -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Variables pour la carte en mode affichage
let showMap = null;
let showMarker = null;

// Fonction pour ouvrir la modale de la carte
function openMapModal() {
    const modalElement = document.getElementById('mapModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Attendre que la modale soit complètement affichée avant d'initialiser la carte
    modalElement.addEventListener('shown.bs.modal', function onShown() {
        initShowMap();
        // Retirer l'écouteur pour éviter les multiples initialisations
        modalElement.removeEventListener('shown.bs.modal', onShown);
    }, { once: true });
    
    modal.show();
}

// Initialiser la carte en mode affichage (lecture seule)
function initShowMap() {
    // Coordonnées du lieu
    const lat = <?= (float)$concours->lieu_latitude ?>;
    const lng = <?= (float)$concours->lieu_longitude ?>;
    
    // Si la carte existe déjà, la détruire
    if (showMap) {
        showMap.remove();
        showMap = null;
        showMarker = null;
    }
    
    // Créer la carte
    showMap = L.map('map-show-container').setView([lat, lng], 15);
    
    // Ajouter la couche de tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(showMap);
    
    // Ajouter un marqueur au lieu
    showMarker = L.marker([lat, lng]).addTo(showMap);
    
    // Ajouter un popup avec l'adresse
    const address = <?= json_encode($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné', JSON_UNESCAPED_UNICODE) ?>;
    showMarker.bindPopup('<strong>' + address + '</strong><br><small>Coordonnées: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</small>').openPopup();
    
    // Forcer le recalcul de la taille de la carte
    setTimeout(function() {
        showMap.invalidateSize();
    }, 100);
}

// Fonction pour créer un itinéraire
function createItinerary(service = 'google') {
    const lat = <?= (float)$concours->lieu_latitude ?>;
    const lng = <?= (float)$concours->lieu_longitude ?>;
    const address = <?= json_encode($concours->lieu_competition ?? $concours->lieu ?? '', JSON_UNESCAPED_UNICODE) ?>;
    
    let url = '';
    
    switch(service) {
        case 'google':
            // Google Maps avec itinéraire
            url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
            break;
            
        case 'osm':
            // OpenStreetMap avec routing
            url = `https://www.openstreetmap.org/directions?to=${lat},${lng}`;
            break;
            
        case 'waze':
            // Waze (application mobile ou web)
            url = `https://www.waze.com/ul?ll=${lat},${lng}&navigate=yes`;
            break;
            
        case 'native':
            // Utiliser le protocole de navigation natif (ouvre l'app de navigation par défaut)
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;
            const isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
            
            if (isIOS) {
                // iOS - utiliser Apple Maps
                url = `http://maps.apple.com/?daddr=${lat},${lng}&dirflg=d`;
            } else {
                // Android ou autres - utiliser Google Maps
                url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
            }
            break;
            
        default:
            url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
    }
    
    // Ouvrir dans un nouvel onglet
    window.open(url, '_blank');
}
</script>
<?php endif; ?>

<script>
// Variables globales pour la page show
const concoursIdShow = <?= json_encode($concours->id ?? $concours->_id ?? $id ?? null) ?>;
// Données du concours pour JavaScript
const concoursDataShow = <?= json_encode($concours, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/public/assets/js/concours-show.js"></script>
