<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-create.css" rel="stylesheet">

<!-- Formulaire de création/édition d'un concours -->
<div class="container-fluid concours-create-container">
<h1><?= isset($concours) ? 'Éditer' : 'Créer' ?> un concours</h1>

<!-- Passer les clubs et disciplines au JavaScript -->
<script>
// Vérifier que les variables PHP sont définies
var clubsFromPHP = <?= json_encode($clubs ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;
var disciplinesFromPHP = <?= json_encode($disciplines ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;

window.clubsData = clubsFromPHP || [];
window.disciplinesData = disciplinesFromPHP || [];
window.typeCompetitionsData = <?= json_encode($typeCompetitions ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?> || [];
window.niveauChampionnatData = <?= json_encode($niveauChampionnat ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?> || [];

console.log('=== DONNÉES DEPUIS PHP ===');
console.log('Clubs depuis PHP:', window.clubsData);
console.log('Nombre de clubs:', window.clubsData ? window.clubsData.length : 0);
console.log('Disciplines depuis PHP:', window.disciplinesData);
console.log('Type disciplines:', typeof window.disciplinesData);
console.log('Est tableau disciplines?', Array.isArray(window.disciplinesData));
console.log('Nombre de disciplines:', window.disciplinesData ? window.disciplinesData.length : 0);
if (window.disciplinesData && window.disciplinesData.length > 0) {
    console.log('Première discipline:', window.disciplinesData[0]);
    console.log('Structure première discipline:', Object.keys(window.disciplinesData[0]));
} else {
    console.error('PROBLÈME: Aucune discipline dans window.disciplinesData!');
    console.error('Valeur brute disciplinesFromPHP:', disciplinesFromPHP);
}
console.log('=== FIN DONNÉES PHP ===');

// Test immédiat pour voir si le select existe
setTimeout(function() {
    var testSelect = document.getElementById('discipline');
    if (testSelect) {
        console.log('✅ Select discipline trouvé dans le DOM');
        console.log('Nombre d\'options actuelles:', testSelect.options.length);
    } else {
        console.error('❌ Select discipline NON trouvé dans le DOM');
    }
}, 100);
</script>
<form method="post" action="<?= isset($concours) ? '/concours/update/' . $concours->id : '/concours/store' ?>" id="concoursForm">
    
    <!-- Section principale -->
    <div class="form-section">
        <!-- Club Organisateur -->
        <div class="form-group">
            <label>Club Organisateur :</label>
            <div class="club-organisateur-fields">
                <input type="text" id="club_search" name="club_search" placeholder="Rechercher un club..." autocomplete="off">
                <select id="club_organisateur" name="club_organisateur" required>
                    <option value="">-- Sélectionner un club --</option>
                </select>
                <input type="text" id="club_code" name="club_code" placeholder="###" maxlength="3" style="width: 60px;">
                <input type="text" id="club_name_display" name="club_name_display" placeholder="Nom du club" readonly>
            </div>
        </div>

        <!-- Discipline -->
        <div class="form-group">
            <label>Discipline :</label>
            <div class="discipline-fields">
                <select id="discipline" name="discipline" required>
                    <option value="">-- Sélectionner une discipline --</option>
                </select>
            </div>
        </div>

        <!-- Type Compétition -->
        <div class="form-group">
            <label>Type Compétition :</label>
            <div class="type-competition-fields">
                <select id="type_competition" name="type_competition" required>
                    <option value="">-- Sélectionner un type --</option>
                </select>
            </div>
        </div>

        <!-- Niveau Championnat -->
        <div class="form-group">
            <label>Niveau Championnat :</label>
            <div class="niveau-championnat-fields">
                <select id="niveau_championnat" name="niveau_championnat" required>
                    <option value="">-- Sélectionner --</option>
                </select>
            </div>
        </div>

        <!-- Titre Compétition -->
        <div class="form-group">
            <label>Titre Compétition :</label>
            <input type="text" id="titre_competition" name="titre_competition" value="<?= $concours->titre_competition ?? '' ?>" required>
        </div>

        <!-- Lieu Compétition -->
        <div class="form-group">
            <label>Lieu Compétition :</label>
            <input type="text" id="lieu_competition" name="lieu_competition" value="<?= $concours->lieu_competition ?? '' ?>" required>
        </div>

        <!-- Dates -->
        <div class="date-fields-row">
            <label>Début Compétition : <input type="date" name="date_debut" value="<?= $concours->date_debut ?? '' ?>" required></label>
            <label>Fin Compétition : <input type="date" name="date_fin" value="<?= $concours->date_fin ?? '' ?>" required></label>
        </div>

        <!-- Nombre cibles, départ, tireurs -->
        <div class="numeric-fields-row">
            <label>Nombre cibles : <input type="number" name="nombre_cibles" value="<?= $concours->nombre_cibles ?? 0 ?>" min="0" required></label>
            <label>Nombre départ : <input type="number" name="nombre_depart" value="<?= $concours->nombre_depart ?? 1 ?>" min="1" required></label>
            <label>Nombre tireurs par cibles : <input type="number" name="nombre_tireurs_par_cibles" value="<?= $concours->nombre_tireurs_par_cibles ?? 0 ?>" min="0" required></label>
        </div>
    </div>

    <!-- Sections encadrées en bas -->
    <div class="bottom-sections">
        <!-- Type Concours -->
        <div class="section-frame">
            <h4>Type Concours</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ouvert" <?= (!isset($concours) || (isset($concours) && $concours->type_concours == 'ouvert')) ? 'checked' : '' ?>>
                    Ouvert
                </label>
                <label class="radio-label">
                    <input type="radio" name="type_concours" value="ferme" <?= (isset($concours) && $concours->type_concours == 'ferme') ? 'checked' : '' ?>>
                    Fermé
                </label>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="duel" value="1" <?= (isset($concours) && $concours->duel) ? 'checked' : '' ?>>
                Duel
            </label>
        </div>

        <!-- Division Equipe -->
        <div class="section-frame">
            <h4>Division Equipe</h4>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="dr" <?= (isset($concours) && $concours->division_equipe == 'dr') ? 'checked' : '' ?>>
                    DR
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="poules_non_filiere" <?= (isset($concours) && $concours->division_equipe == 'poules_non_filiere') ? 'checked' : '' ?>>
                    Poules Non Filière
                </label>
                <label class="radio-label">
                    <input type="radio" name="division_equipe" value="duels_equipes" <?= (!isset($concours) || (isset($concours) && $concours->division_equipe == 'duels_equipes')) ? 'checked' : '' ?>>
                    Duels Equipes
                </label>
            </div>
        </div>

        <!-- Publication WEB -->
        <div class="section-frame">
            <h4>Publication WEB</h4>
            <label>Code d'authentification :</label>
            <input type="text" name="code_authentification" value="<?= $concours->code_authentification ?? '' ?>" placeholder="Code d'authentification">
            <label>Type de publication INTERNET :</label>
            <select id="type_publication_internet" name="type_publication_internet">
                <option value="">-- Sélectionner --</option>
            </select>
        </div>
    </div>

    <button type="submit">Enregistrer</button>
</form>
<a href="/concours">Retour à la liste</a>
</div>

<script src="/public/assets/js/concours-create.js"></script>
