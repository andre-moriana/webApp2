// JavaScript spécifique aux événements

// Fonction de confirmation de suppression
window.confirmDelete = function(eventId, eventName) {
    document.getElementById("deleteEventId").value = eventId;
    document.getElementById("eventName").textContent = eventName;
    const form = document.getElementById("deleteForm");
    form.action = "/events/" + eventId;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
};

// Fonction pour supprimer un événement
async function deleteEvent(eventId) {
    if (!eventId) {
        console.error('ID d\'événement manquant pour la suppression');
        return;
    }
    try {
        console.log('Suppression de l\'événement:', eventId);
        const response = await fetch(backendUrl + '/api/events/' + eventId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + authToken,
                'Content-Type': 'application/json'
            }
        });
        if (response.ok) {
            console.log('Événement supprimé avec succès');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) {
                modal.hide();
            }
            // Détecter si on est sur la page de détail ou de liste
            if (window.location.pathname.includes("/events/") && !window.location.pathname.endsWith("/events")) {
                // Page de détail - rediriger vers la liste
                window.location.href = "/events";
            } else {
                // Page de liste - recharger la page
                window.location.reload();
            }
        } else {
            const error = await response.json();
            console.error('Erreur lors de la suppression:', error);
            alert('Erreur lors de la suppression: ' + (error.message || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression: ' + error.message);
    }
}

// Gestion des événements
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-event-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventName = this.getAttribute('data-event-name');
            confirmDelete(eventId, eventName);
        });
    });

    // Gestion du formulaire de suppression
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const eventId = document.getElementById('deleteEventId').value;
            if (eventId) {
                deleteEvent(eventId);
            }
        });
    }
});


