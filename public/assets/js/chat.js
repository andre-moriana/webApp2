// Configuration globale
let currentEditMessageId = null;
let currentDeleteMessageId = null;

// Initialisation du chat
document.addEventListener("DOMContentLoaded", function() {
    const messagesContainer = document.querySelector(".messages-container");
    if (!messagesContainer) return;

    // Faire défiler jusqu au dernier message
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
});

// Fonctions pour les actions sur les messages (gérées par les formulaires PHP)
function editMessage(messageId) {
    // Rediriger vers la page d édition du message
    window.location.href = `/messages/${messageId}/edit`;
}

function deleteMessage(messageId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce message ?")) {
        // Soumettre le formulaire de suppression
        const form = document.createElement("form");
        form.method = "POST";
        form.action = `/messages/${messageId}/delete`;
        
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "_method";
        input.value = "DELETE";
        form.appendChild(input);
        
        document.body.appendChild(form);
        form.submit();
    }
}
