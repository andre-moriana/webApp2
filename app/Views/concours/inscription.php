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
    'apiInscriptionsUrl' => $apiInscriptionsUrl ?? '/api/concours/' . ($concoursId ?? '') . '/inscriptions',
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
    <h1>Inscription au concours</h1>

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
                        $cbId = 'depart-cb-' . $departId;
                        ?>
                        <div class="form-check">
                            <input type="checkbox" id="<?= htmlspecialchars($cbId) ?>" class="form-check-input depart-checkbox" name="numero_depart[]" value="<?= $numero ?>" data-date-depart="<?= htmlspecialchars($getD($d, 'date_depart', '')) ?>" data-heure-greffe="<?= htmlspecialchars($getD($d, 'heure_greffe', '')) ?>">
                            <label for="<?= htmlspecialchars($cbId) ?>" class="form-check-label"><?= htmlspecialchars($label) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($nombreDepart && is_numeric($nombreDepart) && $nombreDepart > 0): ?>
                    <div class="form-check mb-1">
                        <input type="checkbox" id="depart-select-all" class="form-check-input">
                        <label for="depart-select-all" class="form-check-label fw-bold">Tout sélectionner</label>
                    </div>
                    <?php for ($i = 1; $i <= (int)$nombreDepart; $i++): ?>
                        <div class="form-check">
                            <input type="checkbox" id="depart-cb-<?= $i ?>" class="form-check-input depart-checkbox" name="numero_depart[]" value="<?= $i ?>" data-date-depart="" data-heure-greffe="">
                            <label for="depart-cb-<?= $i ?>" class="form-check-label">Départ <?= $i ?></label>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun départ disponible</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recherche d'archer dans le fichier XML -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-search me-2"></i>Recherche d'archer
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label for="licence-search-input" class="form-label">Numero de licence</label>
                    <input type="text" class="form-control" id="licence-search-input" placeholder="Entrer le numero de licence" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary w-100" id="archer-search-btn">Chercher</button>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Recherche uniquement dans le fichier XML.</small>
        </div>
    </div>

    <!-- Liste des inscrits -->
    <?php
    // Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
    $isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
    ?>
    <div class="inscriptions-section">
        <h3>Archers inscrits</h3>
        <small id="inscriptions-filter-hint" class="text-muted d-block mb-2">La liste affiche toutes les inscriptions. Cochez des départs ci-dessus pour filtrer.</small>
        <div class="table-responsive">
            <table class="table table-bordered" id="inscriptions-table">
                    <thead>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inscriptions-list">
                        <?php 
                        // $usersMap est passé depuis le contrôleur
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
                            <tr data-inscription-id="<?= htmlspecialchars($inscId) ?>" class="<?= htmlspecialchars($rowClass) ?>"<?= $dataPiquet ?><?= $rowStyle ?>>
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
                                <td<?= $rowStyle ?>><?= htmlspecialchars($inscription['user_nom'] ?? 'N/A') ?></td>
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
                                <td<?= $rowStyle ?>>
                                    <?php if ($canEditDeleteInscription): ?>
                                    <button type="button" class="btn btn-sm btn-primary me-1" onclick="editInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                        endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
    </div>

    <div class="actions-section">
        <a href="/concours/show/<?= htmlspecialchars($concoursId ?? '') ?>" class="btn btn-secondary">Retour au concours</a>
        <a href="/concours" class="btn btn-secondary">Retour à la liste</a>
    </div>
</div>

<!-- Modale pour confirmer l'inscription -->
<div class="modal fade" id="confirmInscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer l'inscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirm-modal-body">
                <!-- Contenu dynamique rempli par JavaScript -->
                <div class="archer-summary mb-3 p-3 bg-light rounded">
                    <h5>Informations de l'archer</h5>
                    <p class="mb-1"><strong>Nom:</strong> <span id="modal-archer-nom"></span> <span id="modal-archer-prenom"></span></p>
                    <p class="mb-1"><strong>Licence:</strong> <span id="modal-archer-licence"></span></p>
                    <p class="mb-1"><strong>Club:</strong> <span id="modal-archer-club"></span></p>
                </div>
                
                <form id="inscription-form">
                    <h5 class="mb-3">Informations d'inscription</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="saison" class="form-label">Saison</label>
                            <input type="text" id="saison" class="form-control" placeholder="Ex: 2024-2025" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="type_certificat_medical" class="form-label">Type Certificat Médical</label>
                            <select id="type_certificat_medical" class="form-control" disabled>
                                <option value="">Sélectionner</option>
                                <option value="Compétition">Compétition</option>
                                <option value="Pratique">Pratique</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type_licence" class="form-label">Type Licence</label>
                            <select id="type_licence" class="form-control" disabled>
                                <option value="">Sélectionner</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="E" class="licence-invalid">E</option>
                                <option value="L" class="licence-invalid">L</option>
                                <option value="D" class="licence-invalid">D</option>
                                <option value="J">J</option>
                                <option value="P" class="licence-warning">P</option>
                            </select>
                            <div id="type_licence_warning" class="licence-warning-message d-none"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="creation_renouvellement" class="form-label">Création/Renouvellement</label>
                            <input type="text" id="creation_renouvellement" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <!-- Les départs sont sélectionnés dans la page principale -->
                    <div class="mb-3">
                        <label class="form-label">Date(s) et heure(s) du greffe</label>
                        <div class="form-control bg-light" style="pointer-events: none; min-height: 2.5rem;">
                            <span id="modal-depart-display">Sélectionné(s) en haut de la page</span>
                        </div>
                        <small class="form-text text-muted">Les départs sont sélectionnés en haut de la page</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span> <span class="text-muted">(pour confirmation d'inscription)</span></label>
                        <input type="email" id="email" class="form-control" placeholder="exemple@email.com" autocomplete="email" required>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Classification et équipement</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categorie_classement" class="form-label">Catégorie de classement</label>
                            <select id="categorie_classement" class="form-control">
                                <option value="">Sélectionner une catégorie</option>
                                <?php 
                                // Debug temporaire - à retirer après test
                                if (!isset($categoriesClassement)) {
                                    echo '<!-- DEBUG: $categoriesClassement n\'est pas définie -->';
                                } else {
                                    echo '<!-- DEBUG: $categoriesClassement count: ' . count($categoriesClassement) . ' -->';
                                }
                                if (!empty($categoriesClassement)): ?>
                                    <?php 
                                    // Pour Nature et 3D, remplacer CL par TL dans l'affichage
                                    $isNatureOr3D = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N'], true);
                                    foreach ($categoriesClassement as $categorie): 
                                        $abv = $categorie['abv_categorie_classement'] ?? '';
                                        $lb = $categorie['lb_categorie_classement'] ?? '';
                                        // Remplacer CL par TL dans l'affichage pour Nature et 3D
                                        if ($isNatureOr3D && $abv === 'CL') {
                                            $abvDisplay = 'TL';
                                            $lbDisplay = str_replace('CL', 'TL', $lb);
                                        } else {
                                            $abvDisplay = $abv;
                                            $lbDisplay = $lb;
                                        }
                                    ?>
                                        <option value="<?= htmlspecialchars($abv) ?>">
                                            <?= htmlspecialchars($lbDisplay) ?> (<?= htmlspecialchars($abvDisplay) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Debug: Aucune catégorie disponible -->
                                    <?php error_log('DEBUG: Aucune catégorie dans $categoriesClassement'); ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="arme" class="form-label">Arme (utilisée sur le pas de tir)</label>
                            <select id="arme" class="form-control">
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
                    
                    <?php
                    // Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
                    $isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
                    ?>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" id="mobilite_reduite" class="form-check-input">
                                <label for="mobilite_reduite" class="form-check-label">Mobilité réduite</label>
                            </div>
                        </div>
                        <?php if ($isNature3DOrCampagne): ?>
                            <!-- Pour les disciplines 3D, Nature et Campagne : afficher Piquet au lieu de Distance -->
                            <div class="col-md-3 mb-3">
                                <label for="piquet" class="form-label">Piquet</label>
                                <select id="piquet" name="piquet" class="form-control">
                                    <option value="">Sélectionner</option>
                                    <option value="rouge">Rouge</option>
                                    <option value="bleu">Bleu</option>
                                    <option value="blanc">Blanc</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <!-- Pour les autres disciplines : afficher Distance normalement -->
                            <div class="col-md-3 mb-3">
                                <label for="distance" class="form-label">Distance</label>
                                <select id="distance" class="form-control">
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
                            <!-- Le champ Blason n'existe pas pour les disciplines 3D, Nature et Campagne -->
                            <div class="col-md-3 mb-3">
                                <label for="blason" class="form-label">Blason</label>
                                <input type="number" id="blason" class="form-control" min="0" placeholder="Ex: 40" readonly>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$isNature3DOrCampagne): ?>
                        <!-- Les champs Duel et Trispot n'existent pas pour les disciplines 3D, Nature et Campagne -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" id="duel" class="form-check-input">
                                    <label for="duel" class="form-check-label">Duel</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input type="checkbox" id="trispot" class="form-check-input">
                                    <label for="trispot" class="form-check-label">Trispot</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-4 mb-3"><i class="fas fa-coffee"></i> Buvette</h6>
                    <p class="text-muted small mb-2">Réservation optionnelle des articles de la buvette</p>
                    <div id="buvette-produits-container" class="border rounded p-3 bg-light">
                        <div id="buvette-loading" class="text-center py-2 text-muted small">Chargement des produits...</div>
                        <div id="buvette-produits-list" class="d-none"></div>
                        <div id="buvette-empty" class="text-muted small d-none">Aucun produit disponible pour ce concours.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-inscription">Confirmer</button>
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
                                <option value="E" class="licence-invalid">E</option>
                                <option value="L" class="licence-invalid">L</option>
                                <option value="D" class="licence-invalid">D</option>
                                <option value="J">J</option>
                                <option value="P" class="licence-warning">P</option>
                            </select>
                            <div id="edit-type_licence_warning" class="licence-warning-message d-none"></div>
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
                                <?php 
                                // Pour Nature et 3D, remplacer CL par TL dans l'affichage
                                $isNatureOr3D = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N'], true);
                                if (!empty($categoriesClassement)): ?>
                                    <?php foreach ($categoriesClassement as $categorie): 
                                        $abv = $categorie['abv_categorie_classement'] ?? '';
                                        $lb = $categorie['lb_categorie_classement'] ?? '';
                                        // Remplacer CL par TL dans l'affichage pour Nature et 3D
                                        if ($isNatureOr3D && $abv === 'CL') {
                                            $abvDisplay = 'TL';
                                            $lbDisplay = str_replace('CL', 'TL', $lb);
                                        } else {
                                            $abvDisplay = $abv;
                                            $lbDisplay = $lb;
                                        }
                                    ?>
                                        <option value="<?= htmlspecialchars($abv) ?>">
                                            <?= htmlspecialchars($lbDisplay) ?> (<?= htmlspecialchars($abvDisplay) ?>)
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

<script src="/public/assets/js/concours-inscription-simple.js"></script>
