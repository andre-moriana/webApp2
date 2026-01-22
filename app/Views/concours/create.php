<!-- CSS personnalisé -->
<link href="/public/assets/css/concours-create.css" rel="stylesheet">

<!-- Formulaire de création/édition d'un concours avec gestion des départs -->
<div class="container-fluid concours-create-container">
<h1><?= isset($concours) ? 'Éditer' : 'Créer' ?> un concours</h1>
<form method="post" action="<?= isset($concours) ? '/concours/update/' . $concours->id : '/concours/store' ?>">
    <label>Nom : <input type="text" name="nom" value="<?= $concours->nom ?? '' ?>" required></label><br>
    <label>Description : <textarea name="description"><?= $concours->description ?? '' ?></textarea></label><br>
    <label>Date début : <input type="date" name="date_debut" value="<?= $concours->date_debut ?? '' ?>" required></label><br>
    <label>Date fin : <input type="date" name="date_fin" value="<?= $concours->date_fin ?? '' ?>" required></label><br>
    <label>Lieu : <input type="text" name="lieu" value="<?= $concours->lieu ?? '' ?>" required></label><br>
    <label>Type : <input type="text" name="type" value="<?= $concours->type ?? '' ?>" required></label><br>
    <label>Statut : <input type="text" name="statut" value="<?= $concours->statut ?? '' ?>" required></label><br>
    <h3>Départs</h3>
    <div id="departs-list">
        <?php if (!empty($concours->departs)) foreach ($concours->departs as $i => $depart): ?>
            <div class="depart-item">
                <label>Date : <input type="date" name="departs[<?= $i ?>][date]" value="<?= $depart['date'] ?? '' ?>" required></label>
                <label>Heure : <input type="time" name="departs[<?= $i ?>][heure]" value="<?= $depart['heure'] ?? '' ?>" required></label>
                <label>Catégorie : <input type="text" name="departs[<?= $i ?>][categorie]" value="<?= $depart['categorie'] ?? '' ?>"></label>
                <button type="button" onclick="this.parentNode.remove()">Supprimer</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="ajouterDepart()">Ajouter un départ</button><br><br>
    <button type="submit">Enregistrer</button>
</form>
<a href="/concours">Retour à la liste</a>
</div>
<script>
function ajouterDepart() {
    var idx = document.querySelectorAll('#departs-list .depart-item').length;
    var html = `<div class=\"depart-item\">
        <label>Date : <input type=\"date\" name=\"departs[${idx}][date]\" required></label>
        <label>Heure : <input type=\"time\" name=\"departs[${idx}][heure]\" required></label>
        <label>Catégorie : <input type=\"text\" name=\"departs[${idx}][categorie]\"></label>
        <button type=\"button\" onclick=\"this.parentNode.remove()\">Supprimer</button>
    </div>`;
    document.getElementById('departs-list').insertAdjacentHTML('beforeend', html);
}
</script>
