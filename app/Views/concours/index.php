<!-- Liste des concours -->
<h1>Liste des concours</h1>
<a href="/concours/create">Créer un concours</a>
<table border="1" cellpadding="8">
    <tr>
        <th>Nom</th>
        <th>Date début</th>
        <th>Date fin</th>
        <th>Lieu</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($concours as $c): ?>
    <tr>
        <td><?= htmlspecialchars($c->nom) ?></td>
        <td><?= htmlspecialchars($c->date_debut) ?></td>
        <td><?= htmlspecialchars($c->date_fin) ?></td>
        <td><?= htmlspecialchars($c->lieu) ?></td>
        <td><?= htmlspecialchars($c->type) ?></td>
        <td><?= htmlspecialchars($c->statut) ?></td>
        <td>
            <a href="/concours/edit/<?= $c->id ?>">Éditer</a> |
            <a href="/concours/delete/<?= $c->id ?>" onclick="return confirm('Supprimer ce concours ?')">Supprimer</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
