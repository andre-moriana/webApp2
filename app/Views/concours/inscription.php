<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-inscription.css" rel="stylesheet">

<div class="container-fluid concours-inscription-container">
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

    <!-- Sélection du départ -->
    <?php 
    $nombreDepart = is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null);
    // Debug: vérifier la valeur
    // error_log("DEBUG nombre_depart: " . var_export($nombreDepart, true));
    // error_log("DEBUG concours object: " . var_export($concours, true));
    ?>
    <div class="depart-selection-section mb-4">
        <h3>Sélectionner un départ</h3>
        <div class="form-group">
            <label for="depart-select-main" class="form-label">N° départ <span class="text-danger">*</span></label>
            <select id="depart-select-main" class="form-control" required name="numero_depart">
                <option value="">Sélectionner un départ</option>
                <?php if ($nombreDepart && is_numeric($nombreDepart) && $nombreDepart > 0): ?>
                    <?php for ($i = 1; $i <= (int)$nombreDepart; $i++): ?>
                        <option value="<?= $i ?>">Départ <?= $i ?></option>
                    <?php endfor; ?>
                <?php else: ?>
                    <option value="" disabled>Aucun départ disponible</option>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <!-- Formulaire de recherche d'archer -->
    <div class="search-section">
        <h3>Rechercher un archer</h3>
        <div class="search-form">
            <div class="form-group">
                <label>Rechercher par :</label>
                <select id="search-type" class="form-control">
                    <option value="licence">Numéro de licence</option>
                    <option value="nom">Nom</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" id="search-input" class="form-control" placeholder="Entrez le numéro de licence ou le nom">
                <button type="button" class="btn btn-primary" id="btn-search">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </div>
        </div>
    </div>

    <!-- Résultats de recherche -->
    <div id="search-results" class="search-results" style="display: none;">
        <h3>Résultats de la recherche</h3>
        <div id="results-list"></div>
    </div>

    <!-- Liste des inscrits -->
    <?php
    // Déterminer si c'est une discipline 3D, Nature ou Campagne (abv_discipline = "3", "N" ou "C")
    $isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
    ?>
    <div class="inscriptions-section">
        <h3>Archers inscrits</h3>
        <?php if (empty($inscriptions)): ?>
            <p class="alert alert-info">Aucun archer inscrit pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" id="inscriptions-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
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
                            $userId = $inscription['user_id'] ?? null;
                            $user = isset($usersMap) && isset($usersMap[$userId]) ? $usersMap[$userId] : null;
                            
                            // Récupérer la couleur du piquet pour les disciplines 3D, Nature et Campagne
                            $piquetColor = $inscription['piquet'] ?? null;
                            $rowClass = '';
                            $rowStyle = '';
                            $dataPiquet = '';
                            if ($piquetColor && $piquetColor !== '') {
                                // Nettoyer et normaliser la couleur (enlever les espaces, convertir en minuscule)
                                $piquetColor = trim(strtolower($piquetColor));
                                // Ajouter une classe CSS selon la couleur du piquet
                                $rowClass = 'piquet-' . $piquetColor;
                                
                                // Ajouter un attribut data-piquet pour JavaScript
                                $dataPiquet = ' data-piquet="' . htmlspecialchars($piquetColor) . '"';
                                
                                // Ajouter aussi un style inline comme solution de secours
                                $colorMap = [
                                    'rouge' => '#ffe0e0',
                                    'bleu' => '#e0e8ff',
                                    'blanc' => '#f5f5f5'
                                ];
                                if (isset($colorMap[$piquetColor])) {
                                    $rowStyle = ' style="background-color: ' . htmlspecialchars($colorMap[$piquetColor]) . ' !important;"';
                                }
                            }
                        ?>
                            <tr data-inscription-id="<?= htmlspecialchars($inscription['id'] ?? '') ?>" class="<?= htmlspecialchars($rowClass) ?>"<?= $dataPiquet ?><?= $rowStyle ?>>
                                <td><?= htmlspecialchars($user['name'] ?? $user['nom'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['first_name'] ?? $user['firstName'] ?? $user['prenom'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inscription['numero_licence'] ?? $user['licence_number'] ?? $user['licenceNumber'] ?? 'N/A') ?></td>
                                <td>
                                    <?php 
                                    // Afficher le champ "name" (nom complet) du club comme demandé
                                    $clubName = $inscription['club_name'] ?? $inscription['club_name_short'] ?? $user['clubName'] ?? $user['club_name'] ?? $user['clubNameShort'] ?? $user['club_name_short'] ?? null;
                                    echo htmlspecialchars($clubName ?? 'N/A');
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($inscription['numero_depart'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inscription['numero_tir'] ?? 'N/A') ?></td>
                                <?php if ($isNature3DOrCampagne): ?>
                                    <td><?= htmlspecialchars(ucfirst($piquetColor ?? 'N/A')) ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($inscription['distance'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($inscription['blason'] ?? 'N/A') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($inscription['created_at'] ?? $inscription['date_inscription'] ?? 'N/A') ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>)">
                                        <i class="fas fa-trash"></i> Retirer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
                                <option value="L">L</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="creation_renouvellement" class="form-label">Création/Renouvellement</label>
                            <input type="text" id="creation_renouvellement" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <?php if (!empty($departs)): ?>
                    <div class="mb-3">
                        <label for="depart-select" class="form-label">N° départ <span class="text-danger">*</span></label>
                        <select id="depart-select" class="form-control" required>
                            <option value="">Sélectionner un départ</option>
                            <?php foreach ($departs as $index => $depart): ?>
                                <option value="<?= htmlspecialchars($depart['id'] ?? $depart['_id'] ?? '') ?>">
                                    Départ <?= ($index + 1) ?> - <?= htmlspecialchars($depart['heure'] ?? '') ?><?= !empty($depart['date']) ? ' (' . htmlspecialchars($depart['date']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
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
                                    <?php foreach ($categoriesClassement as $categorie): ?>
                                        <option value="<?= htmlspecialchars($categorie['abv_categorie_classement'] ?? '') ?>">
                                            <?= htmlspecialchars($categorie['lb_categorie_classement'] ?? '') ?> (<?= htmlspecialchars($categorie['abv_categorie_classement'] ?? '') ?>)
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
                        <div class="col-md-3 mb-3">
                            <label for="numero_tir" class="form-label">N° Tir</label>
                            <select id="numero_tir" class="form-control">
                                <option value="">Sélectionner</option>
                                <?php 
                                $nombreDepart = is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null);
                                if ($nombreDepart && is_numeric($nombreDepart) && $nombreDepart > 0):
                                    for ($i = 1; $i <= (int)$nombreDepart; $i++):
                                ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php 
                                    endfor;
                                endif;
                                ?>
                            </select>
                        </div>
                        <?php if (!$isNature3DOrCampagne): ?>
                            <!-- Le champ Blason n'existe pas pour les disciplines 3D, Nature et Campagne -->
                            <div class="col-md-3 mb-3">
                                <label for="blason" class="form-label">Blason</label>
                                <input type="number" id="blason" class="form-control" min="0" placeholder="Ex: 40">
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
                    
                    <h6 class="mt-4 mb-3">Paiement</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tarif_competition" class="form-label">Tarif Compétition</label>
                            <select id="tarif_competition" class="form-control">
                                <option value="">Sélectionner</option>
                                <option value="Tarif standard">Tarif standard</option>
                                <option value="Tarif réduit">Tarif réduit</option>
                                <option value="Tarif jeune">Tarif jeune</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mode_paiement" class="form-label">Mode Paiement</label>
                            <select id="mode_paiement" class="form-control">
                                <option value="Non payé">Non payé</option>
                                <option value="Espèces">Espèces</option>
                                <option value="Chèque">Chèque</option>
                                <option value="Carte bancaire">Carte bancaire</option>
                                <option value="Virement">Virement</option>
                            </select>
                        </div>
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

<script>
// Variables globales - doivent être définies avant le chargement du script
const concoursId = <?= json_encode($concoursId ?? null) ?>;
const categoriesClassement = <?= json_encode($categoriesClassement ?? [], JSON_UNESCAPED_UNICODE) ?>;
const arcs = <?= json_encode($arcs ?? [], JSON_UNESCAPED_UNICODE) ?>;
const distancesTir = <?= json_encode($distancesTir ?? [], JSON_UNESCAPED_UNICODE) ?>;
const concoursDiscipline = <?= json_encode(is_object($concours) ? ($concours->discipline ?? $concours->iddiscipline ?? null) : ($concours['discipline'] ?? $concours['iddiscipline'] ?? null)) ?>;
const concoursTypeCompetition = <?= json_encode(is_object($concours) ? ($concours->type_competition ?? null) : ($concours['type_competition'] ?? null)) ?>;
const concoursNombreDepart = <?= json_encode(is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null)) ?>;
const disciplineAbv = <?= json_encode($disciplineAbv ?? null) ?>;
const isNature3DOrCampagne = <?= json_encode(isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true)) ?>;
</script>
<script>
// Forcer l'application des couleurs de piquet après le chargement de la page
function applyPiquetColors() {
    const colorMap = {
        'rouge': '#ffe0e0',
        'bleu': '#e0e8ff',
        'blanc': '#f5f5f5'
    };
    
    // Appliquer les couleurs aux lignes avec classe piquet-* ou attribut data-piquet
    const table = document.getElementById('inscriptions-table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row, index) {
        let piquetColor = null;
        
        // Vérifier l'attribut data-piquet d'abord
        if (row.hasAttribute('data-piquet')) {
            piquetColor = row.getAttribute('data-piquet').toLowerCase().trim();
        } else {
            // Sinon, chercher dans les classes
            const classes = row.className.split(' ');
            for (let cls of classes) {
                if (cls.startsWith('piquet-')) {
                    piquetColor = cls.replace('piquet-', '').toLowerCase().trim();
                    break;
                }
            }
        }
        
        if (piquetColor && colorMap[piquetColor]) {
            // Forcer l'application avec setProperty et important
            row.style.cssText = 'background-color: ' + colorMap[piquetColor] + ' !important;';
            console.log('Couleur appliquée:', piquetColor, 'à la ligne', index, row);
        } else {
            // Pour les lignes sans piquet, appliquer un style alterné si nécessaire
            if (index % 2 === 1) {
                row.style.cssText = 'background-color: rgba(0,0,0,.05) !important;';
            }
        }
    });
}

// Exécuter immédiatement et après le chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyPiquetColors);
} else {
    applyPiquetColors();
}

// Réappliquer après des délais pour surcharger Bootstrap
setTimeout(applyPiquetColors, 50);
setTimeout(applyPiquetColors, 200);
setTimeout(applyPiquetColors, 500);
</script>
<script src="/public/assets/js/concours-inscription.js"></script>
