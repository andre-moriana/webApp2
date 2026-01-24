// JavaScript pour la page de détails du concours (show.php)

// Retirer une inscription par ID
function removeInscription(inscriptionId) {
    if (!confirm('Voulez-vous retirer cet archer de l\'inscription ?')) {
        return;
    }

    // Utiliser concoursIdShow défini dans la page PHP
    const concoursId = typeof concoursIdShow !== 'undefined' ? concoursIdShow : null;
    
    if (!concoursId) {
        alert('Erreur: ID du concours non trouvé');
        return;
    }

    console.log('Suppression de l\'inscription ID:', inscriptionId);

    // Utiliser la route DELETE avec l'ID d'inscription
    fetch(`/api/concours/${concoursId}/inscription/${inscriptionId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Réponse suppression:', data);
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression: ' + error.message);
    });
}
