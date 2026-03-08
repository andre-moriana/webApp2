<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-inscription.css" rel="stylesheet">
<link href="/public/assets/css/users-table.css" rel="stylesheet">

<?php
// Permissions pour les actions sur les inscriptions (mêmes règles que show.php)
$currentUserLicence = $currentUserLicence ?? trim((string)($_SESSION['user']['licenceNumber'] ?? $_SESSION['user']['licence_number'] ?? $_SESSION['user']['numero_licence'] ?? ''));
$currentUserId = $currentUserId ?? ($_SESSION['user']['id'] ?? $_SESSION['user']['userId'] ?? $_SESSION['user']['_id'] ?? null);
$isDirigeant = $isDirigeant ?? (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'Dirigeant');
$inscriptionConfig = [
    'concoursId' => $concoursId ?? null,
    'formAction' => $formAction ?? '/concours/' . ($concoursId ?? '') . '/inscription',
    'apiGreffesUrl' => $apiGreffesUrl ?? '/api/concours/' . ($concoursId ?? '') . '/greffes',
    'inscriptionCible' => $inscriptionCible ?? false,
    'archerSearchUrl' => $archerSearchUrl ?? '/archer/search-or-create',
    'categoriesClassement' => $categoriesClassement ?? [],
    'arcs' => $arcs ?? [],
    'distancesTir' => $distancesTir ?? [],
    'concoursDiscipline' => is_object($concours) ? ($concours->discipline ?? $concours->iddiscipline ?? null) : ($concours['discipline'] ?? $concours['iddiscipline'] ?? null),
    'concoursTypeCompetition' => is_object($concours) ? ($concours->type_competition ?? null) : ($concours['type_competition'] ?? null),
    'concoursNombreDepart' => is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null),
    'disciplineAbv' => $disciplineAbv ?? null,
    'isNature3DOrCampagne' => isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true),
    'isDirigeant' => $isDirigeant,
    'currentUserLicence' => $currentUserLicence,
    'currentUserId' => $currentUserId
];
$inscriptionConfigJson = htmlspecialchars(json_encode($inscriptionConfig, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid concours-inscription-container" id="inscription-page" data-config="<?= $inscriptionConfigJson ?>">
    <h1>Greffes</h1>

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

    <!-- Informations du concours -->
    <?php 
    // S'assurer que $concours est un objet ou un tableau accessible
    $concoursTitre = is_object($concours) ? ($concours->titre_competition ?? $concours->nom ?? 'Concours') : ($concours['titre_competition'] ?? $concours['nom'] ?? 'Concours');
    $concoursLieu = is_object($concours) ? ($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') : ($concours['lieu_competition'] ?? $concours['lieu'] ?? 'Non renseigné');
    $concoursDateDebut = is_object($concours) ? ($concours->date_debut ?? '') : ($concours['date_debut'] ?? '');
    $concoursDateFin = is_object($concours) ? ($concours->date_fin ?? '') : ($concours['date_fin'] ?? '');
    $concoursId = is_object($concours) ? ($concours->id ?? $concours->_id ?? null) : ($concours['id'] ?? $concours['_id'] ?? null);
    ?>
    <div class="concours-info-section">
        <h2><?= htmlspecialchars($concoursTitre) ?></h2>
        <p><strong>Lieu:</strong> <?= htmlspecialchars($concoursLieu) ?></p>
        <p><strong>Dates:</strong> <?= htmlspecialchars($concoursDateDebut) ?> - <?= htmlspecialchars($concoursDateFin) ?></p>
    </div>

    <!-- Sélection des départs (plusieurs possibles) -->
    <?php 
    $departsList = is_object($concours) ? ($concours->departs ?? []) : ($concours['departs'] ?? []);
    if (is_object($departsList)) {
        $departsList = array_values((array)$departsList);
    }
    $departsList = is_array($departsList) ? $departsList : [];
    $nombreDepart = is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null);
    if (empty($departsList) && $nombreDepart) {
        $nombreDepart = (int)$nombreDepart;
    }
    ?>
    <div class="depart-selection-section mb-4">
        <h3>Sélectionner un ou plusieurs départs</h3>
        <div class="form-group">
            <label class="form-label">Date et heure du greffe <span class="text-danger">*</span></label>
            <small class="form-text text-muted d-block mb-2">Cochez les départs pour inscrire l'archer à tous les départs sélectionnés en une fois.</small>
            <div id="depart-checkboxes-container" class="border rounded p-3">
                <?php if (!empty($departsList)): ?>
                    <div class="form-check mb-1">
                        <input type="checkbox" id="depart-select-all" class="form-check-input">
                        <label for="depart-select-all" class="form-check-label fw-bold">Tout sélectionner</label>
                    </div>
                    <?php 
                    $getD = function($d, $key, $default = '') {
                        return is_array($d) ? ($d[$key] ?? $default) : ($d->$key ?? $default);
                    };
                    $placesParDepart = $placesParDepart ?? [];
                    foreach ($departsList as $idx => $d): 
                        $dateDep = $getD($d, 'date_depart', '');
                        $heureGreffe = $getD($d, 'heure_greffe', '');
                        $numero = (int)$getD($d, 'numero_depart', 0);
                        $departId = $getD($d, 'id', $idx);
                        if ($dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
                            $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
                        }
                        $heureGreffe = $heureGreffe ? substr((string)$heureGreffe, 0, 5) : '';
                        $label = trim($dateDep . ($heureGreffe ? ' ' . $heureGreffe : ''));
                        if (empty($label)) $label = 'Départ ' . $numero;
                        $placesDispo = isset($placesParDepart[$numero]) ? (int)$placesParDepart[$numero] : null;
                        $cbId = 'depart-cb-' . $departId;
                        ?>
                        <div class="form-check">
                            <input type="checkbox" id="<?= htmlspecialchars($cbId) ?>" class="form-check-input depart-checkbox" name="numero_depart[]" value="<?= $numero ?>" data-date-depart="<?= htmlspecialchars($getD($d, 'date_depart', '')) ?>" data-heure-greffe="<?= htmlspecialchars($getD($d, 'heure_greffe', '')) ?>">
                            <label for="<?= htmlspecialchars($cbId) ?>" class="form-check-label">
                                <?= htmlspecialchars($label) ?>
                                <?php if ($placesDispo !== null): ?>
                                    <span class="text-muted ms-1">(<?= $placesDispo ?> place<?= $placesDispo !== 1 ? 's' : '' ?> disponible<?= $placesDispo !== 1 ? 's' : '' ?>)</span>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($nombreDepart && is_numeric($nombreDepart) && $nombreDepart > 0): ?>
                    <?php $placesParDepart = $placesParDepart ?? []; ?>
                    <div class="form-check mb-1">
                        <input type="checkbox" id="depart-select-all" class="form-check-input">
                        <label for="depart-select-all" class="form-check-label fw-bold">Tout sélectionner</label>
                    </div>
                    <?php for ($i = 1; $i <= (int)$nombreDepart; $i++): 
                        $placesDispo = isset($placesParDepart[$i]) ? (int)$placesParDepart[$i] : null;
                    ?>
                        <div class="form-check">
                            <input type="checkbox" id="depart-cb-<?= $i ?>" class="form-check-input depart-checkbox" name="numero_depart[]" value="<?= $i ?>" data-date-depart="" data-heure-greffe="">
                            <label for="depart-cb-<?= $i ?>" class="form-check-label">
                                Départ <?= $i ?>
                                <?php if ($placesDispo !== null): ?>
                                    <span class="text-muted ms-1">(<?= $placesDispo ?> place<?= $placesDispo !== 1 ? 's' : '' ?> disponible<?= $placesDispo !== 1 ? 's' : '' ?>)</span>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun départ disponible</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Liste des inscrits -->
    <?php
    // Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
    $isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
    ?>
    <div class="inscriptions-section">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Archers inscrits</h3>
                <small id="inscriptions-filter-hint" class="text-muted d-block mb-2">La liste affiche toutes les inscriptions. Cochez des départs ci-dessus pour filtrer.</small>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="search-box">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                    class="form-control" 
                                    id="userSearchInput" 
                                    placeholder="Rechercher un utilisateur..." 
                                    autocomplete="off">
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    id="clearSearchBtn" 
                                    style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered" id="inscriptions-table">
                    <thead class="table-light">
                        <tr>
                            <th>Statut</th>
                            <th>Nom et Prénom</th>
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
                        </tr>
                    </thead>
                    <tbody id="inscriptions-list">
                        <?php
                        if (empty($inscriptions)): ?>
                            <tr id="inscriptions-empty-row"><td colspan="10" class="text-center text-muted">Chargement des inscriptions...</td></tr>
                        <?php else:
                        foreach ($inscriptions as $inscription): 
                            $inscriptionLicence = trim((string)($inscription['numero_licence'] ?? ''));
                            $inscriptionUserId = $inscription['user_id'] ?? null;
                            $isOwnInscription = ($currentUserLicence !== '' && $inscriptionLicence !== '' && $currentUserLicence === $inscriptionLicence)
                                || ($currentUserId && $inscriptionUserId && (string)$currentUserId === (string)$inscriptionUserId);
                            $canManageInscription = $isDirigeant && !$isOwnInscription;
                            $canEditDeleteInscription = $canManageInscription || $isOwnInscription;
                            $userName = $inscription['user_nom'] ?? null;
                            
                            // Récupérer la couleur du piquet pour les disciplines 3D, Nature et Campagne
                            $piquetColorRaw = $inscription['piquet'] ?? null;
                            $piquetColor = null;
                            $rowStyle = '';
                            
                            if ($piquetColorRaw && $piquetColorRaw !== '') {
                                $piquetColor = trim(strtolower($piquetColorRaw));
                                $rowClass = 'piquet-' . $piquetColor;
                                $dataPiquet = ' data-piquet="' . htmlspecialchars($piquetColor) . '"';
                                
                                // Appliquer le style inline
                                $colors = ['rouge' => '#ffe0e0', 'bleu' => '#e0e8ff', 'blanc' => '#f5f5f5'];
                                if (isset($colors[$piquetColor])) {
                                    $rowStyle = ' style="background-color: ' . $colors[$piquetColor] . ' !important;"';
                                }
                            } elseif ($isNature3DOrCampagne) {
                                $rowClass = 'piquet-manquant';
                                $dataPiquet = '';
                                $rowStyle = ' style="background-color: #dee2e6 !important;"';
                            } else {
                                $rowClass = '';
                                $dataPiquet = '';
                            }
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
                                <tr data-inscription-id="<?php echo htmlspecialchars($inscId); ?>" class="<?= htmlspecialchars($rowClass) ?>"<?= $dataPiquet ?><?= $rowStyle ?> data-searchable="<?php 
                                    // Construire une chaîne de recherche avec toutes les données pertinentes
                                    $searchableText = '';
                                    if (!empty($inscription['user_nom'])) $searchableText .= strtolower($inscription['user_nom']) . ' ';
                                    if (!empty($inscription['numero_licence'])) $searchableText .= strtolower($inscription['numero_licence']) . ' ';
                                    // Ajouter le club dans la recherche (nom complet et nom court)
                                    if (!empty($inscription['club_Name'])) {
                                        $searchableText .= strtolower($inscription['club_Name']) . ' ';
                                    } elseif (!empty($inscription['club_name'])) {
                                        $searchableText .= strtolower($inscription['club_name']) . ' ';
                                    }
                                    if (!empty($inscription['id_club'])) {
                                        $searchableText .= strtolower($inscription['id_club']) . ' ';
                                    }
                                    echo htmlspecialchars(trim($searchableText));
                                ?>">
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
                                    <td<?= $rowStyle ?>>
                                        <div class="d-flex align-items-center">
                                             <div class="text-truncate" style="max-width: 150px;">
                                                <?php 
                                                // Construire le nom complet en utilisant les champs disponibles
                                                $fullName = '';
                                                if (!empty($user['firstName'])) {
                                                    $fullName = $user['firstName'];
                                                    if (!empty($user['name'])) {
                                                        $fullName .= ' ' . $user['name'];
                                                    }
                                                } else {
                                                    $fullName = $user['name'] ?? 'Utilisateur';
                                                }
                                                echo htmlspecialchars($fullName); 
                                                ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['numero_licence'] ?? 'N/A') ?></td>
                                    <td<?= $rowStyle ?>>
                                    <?php 
                                    // Afficher le nom du club (lié à id_club), sinon id_club en fallback
                                    $clubDisplay = $inscription['club_name'] ?? $inscription['id_club'] ?? null;
                                    echo htmlspecialchars($clubDisplay ?? 'N/A');
                                    ?>
                                    </td>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['numero_depart'] ?? 'N/A') ?></td>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['numero_tir'] ?? 'N/A') ?></td>
                                <?php if ($isNature3DOrCampagne): ?>
                                    <?php 
                                    // Récupérer la couleur du piquet pour l'affichage
                                    $piquetDisplay = $inscription['piquet'] ?? null;
                                    $piquetDisplay = $piquetDisplay ? ucfirst(trim(strtolower($piquetDisplay))) : 'N/A';
                                    ?>
                                    <td class="piquet-value"<?= $rowStyle ?>><?= htmlspecialchars($piquetDisplay) ?></td>
                                <?php else: ?>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['distance'] ?? 'N/A') ?></td>
                                    <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['blason'] ?? 'N/A') ?></td>
                                <?php endif; ?>
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['created_at'] ?? $inscription['date_inscription'] ?? 'N/A') ?></td>

                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="actions-section">
        <a href="/concours/show/<?= htmlspecialchars($concoursId ?? '') ?>" class="btn btn-secondary">Retour au concours</a>
        <a href="/concours" class="btn btn-secondary">Retour à la liste</a>
    </div>
</div>



<script src="/public/assets/js/concours-inscription-simple.js"></script>
