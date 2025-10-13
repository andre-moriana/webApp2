// Script pour la page des exercices
function deleteExercise(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet exercice ? Cette action est irréversible.')) {
        // Créer un formulaire pour la suppression
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/exercises/' + id;
        
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
