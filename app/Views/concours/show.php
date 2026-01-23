<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-create.css" rel="stylesheet">

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
$niveauChampionnatName = findLabel($niveauChampionnat, $concours->niveau_championnat ?? null, 'abv_niveauchampionnat', 'lb_niveauchampionnat');
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
        <p><?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?></p>
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

    <!-- Nombre cibles, départ, tireurs -->
    <div class="numeric-fields-row">
        <div class="form-group">
            <label><strong>Nombre cibles :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_cibles ?? 0) ?></p>
        </div>
        <div class="form-group">
            <label><strong>Nombre départ :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_depart ?? 1) ?></p>
        </div>
        <div class="form-group">
            <label><strong>Nombre tireurs par cibles :</strong></label>
            <p><?= htmlspecialchars($concours->nombre_tireurs_par_cibles ?? 0) ?></p>
        </div>
    </div>
</div>

<!-- Liste des inscrits -->
<div class="inscriptions-section" style="margin-top: 30px;">
    <h2>Liste des inscrits</h2>
    
    <?php if (empty($inscriptions)): ?>
        <p class="alert alert-info">Aucune inscription pour ce concours.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID Inscription</th>
                        <th>ID Utilisateur</th>
                        <th>ID Départ</th>
                        <th>Date d'inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptions as $inscription): ?>
                        <tr>
                            <td><?= htmlspecialchars($inscription['id'] ?? $inscription['insc_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($inscription['user_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($inscription['depart_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($inscription['created_at'] ?? $inscription['date_inscription'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p><strong>Total d'inscrits :</strong> <?= count($inscriptions) ?></p>
    <?php endif; ?>
</div>

<div style="margin-top: 30px;">
    <a href="/concours" class="btn btn-secondary">Retour à la liste</a>
    <?php if (isset($concours->id)): ?>
        <a href="/concours/edit/<?= htmlspecialchars($concours->id) ?>" class="btn btn-primary">Modifier</a>
    <?php endif; ?>
</div>
</div>

<style>
.form-group {
    margin-bottom: 10px;
}

.form-group p {
    margin: 2px 0;
    padding: 4px 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
    line-height: 1.4;
}

.form-group label strong {
    display: block;
    margin-bottom: 2px;
    font-size: 0.95em;
}

.date-fields-row .form-group,
.numeric-fields-row .form-group {
    margin-bottom: 8px;
}

.inscriptions-section {
    border-top: 2px solid #dee2e6;
    padding-top: 15px;
    margin-top: 20px;
}

.inscriptions-section h2 {
    margin-bottom: 10px;
    color: #333;
    font-size: 1.3em;
}

.table {
    margin-top: 10px;
}

.table th {
    background-color: #007bff;
    color: white;
    font-weight: bold;
    padding: 8px;
}

.table td {
    padding: 6px 8px;
}

.table tbody tr {
    line-height: 1.3;
}

.bottom-sections {
    margin-top: 15px;
}

.section-frame {
    margin-bottom: 10px;
}

.section-frame h4 {
    margin-bottom: 8px;
    font-size: 1em;
}

.section-frame p {
    margin: 3px 0;
    line-height: 1.4;
}
</style>
