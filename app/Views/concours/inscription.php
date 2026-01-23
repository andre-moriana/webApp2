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
    <div class="inscriptions-section">
        <h3>Archers inscrits</h3>
        <?php if (empty($inscriptions)): ?>
            <p class="alert alert-info">Aucun archer inscrit pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Numéro de licence</th>
                            <th>Club</th>
                            <th>Départ</th>
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
                        ?>
                            <tr data-inscription-id="<?= htmlspecialchars($inscription['id'] ?? '') ?>">
                                <td><?= htmlspecialchars($user['name'] ?? $user['nom'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['first_name'] ?? $user['firstName'] ?? $user['prenom'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['licence_number'] ?? $user['licenceNumber'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['club_name'] ?? $user['clubName'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inscription['depart_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($inscription['created_at'] ?? $inscription['date_inscription'] ?? 'N/A') ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeInscription(<?= htmlspecialchars($inscription['id'] ?? '') ?>, <?= htmlspecialchars($userId ?? 'null') ?>)">
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
                <!-- Contenu dynamique -->
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
const departs = <?= json_encode($departs ?? [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/public/assets/js/concours-inscription.js"></script>
