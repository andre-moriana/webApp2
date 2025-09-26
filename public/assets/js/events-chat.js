// Configuration pour les événements
let currentEventId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Initialiser avec le premier événement
document.addEventListener("DOMContentLoaded", function() {
    if (typeof initialEventId !== "undefined" && initialEventId) {
        currentEventId = initialEventId.toString();
        console.log("Chat initialisé avec l"événement ID:", currentEventId);
        console.log("ID utilisateur actuel:", currentUserId);
        
        // Charger automatiquement les messages de l"événement initial
        loadEventMessages(currentEventId);
    }
});

// Créer un élément de message
function createMessageElement(message) {
    console.log("Création d"un élément de message:", message);
    
    // Gérer les différentes structures de données du backend
    const authorId = message.author_id || message.author?._id || message.author?.id || message.userId || message.user_id;
    const authorName = message.author_name || message.author?.name || message.userName || "Utilisateur";
    const messageTime = message.created_at || message.createdAt || message.timestamp;
    
    console.log("Author ID:", authorId, "Current User ID:", currentUserId);
    
    const isCurrentUser = authorId && currentUserId && authorId.toString() === currentUserId.toString();
    const messageClass = isCurrentUser ? "message-sent" : "message-received";
    const alignClass = isCurrentUser ? "justify-content-end" : "justify-content-start";
    
    let formattedTime = "Date inconnue";
    if (messageTime) {
        try {
            const date = new Date(messageTime);
            if (!isNaN(date.getTime())) {
                formattedTime = date.toLocaleString("fr-FR");
            }
        } catch (e) {
            console.warn("Erreur de formatage de date:", e);
        }
    }

    let attachmentHtml = "";
    if (message.attachment && (message.attachment.filename || message.attachment.url || message.attachment.path)) {
        const attachmentUrl = message.attachment.url || message.attachment.path || "";
        const filename = message.attachment.filename || "Pièce jointe";
        attachmentHtml = `
            <div class="message-attachment mt-2">
                <a href="${attachmentUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-paperclip me-1"></i>
                    ${filename}
                </a>
            </div>
        `;
    }

    const messageHtml = `
        <div class="message ${messageClass} mb-3">
            <div class="d-flex ${alignClass}">
                <div class="message-content">
                    <div class="message-header d-flex justify-content-between align-items-center mb-1">
                        <span class="message-author fw-bold">${authorName}</span>
                        <span class="message-time text-muted small">${formattedTime}</span>
                    </div>
                    <div class="message-text">${message.content || message.text || message.message || ""}</div>
                    ${attachmentHtml}
                </div>
            </div>
        </div>
    `;
    
    return messageHtml;
}

// Charger les messages d"un événement
async function loadEventMessages(eventId) {
    console.log("Chargement des messages pour l"événement:", eventId);
    
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/messages`, {
            method: "GET",
            headers: {
                "Authorization": `Bearer ${authToken}`,
                "Content-Type": "application/json"
            }
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("Messages reçus:", data);
        
        if (data.success && data.data) {
            displayMessages(data.data);
        } else {
            console.warn("Aucun message trouvé ou erreur dans la réponse");
            displayMessages([]);
        }
    } catch (error) {
        console.error("Erreur lors du chargement des messages:", error);
        displayMessages([]);
    }
}

// Afficher les messages
function displayMessages(messages) {
    if (!messagesContainer) {
        console.error("Container des messages non trouvé");
        return;
    }
    
    messagesContainer.innerHTML = "";
    
    if (messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <p>Aucun message dans le chat</p>
            </div>
        `;
        return;
    }
    
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        messagesContainer.insertAdjacentHTML("beforeend", messageElement);
    });
    
    // Faire défiler vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Envoyer un message
async function sendMessage(content, attachment = null) {
    if (!currentEventId) {
        console.error("Aucun événement sélectionné");
        return;
    }
    
    console.log("Envoi du message:", content, "à l"événement:", currentEventId);
    
    try {
        const formData = new FormData();
        formData.append("content", content);
        if (attachment) {
            formData.append("attachment", attachment);
        }
        
        const response = await fetch(`${backendUrl}/api/events/${currentEventId}/messages`, {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${authToken}`
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("Réponse d"envoi:", data);
        
        if (data.success) {
            // Recharger les messages après envoi
            loadEventMessages(currentEventId);
        } else {
            console.error("Erreur lors de l"envoi:", data.message);
            alert("Erreur lors de l"envoi du message: " + (data.message || "Erreur inconnue"));
        }
    } catch (error) {
        console.error("Erreur lors de l"envoi du message:", error);
        alert("Erreur lors de l"envoi du message: " + error.message);
    }
}

// Gestion du formulaire de message
if (messageForm) {
    messageForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const content = messageInput.value.trim();
        if (!content) {
            return;
        }
        
        const attachmentInput = document.getElementById("message-attachment");
        const attachment = attachmentInput.files[0] || null;
        
        sendMessage(content, attachment);
        messageInput.value = "";
        attachmentInput.value = "";
    });
}

// Gestion des clics sur les événements
document.addEventListener("click", function(e) {
    const eventItem = e.target.closest(".event-item");
    if (eventItem) {
        const eventId = eventItem.getAttribute("data-event-id");
        if (eventId && eventId !== currentEventId) {
            currentEventId = eventId;
            console.log("Changement d"événement vers:", eventId);
            
            // Mettre à jour l"affichage
            updateEventSelection(eventItem);
            
            // Charger les messages du nouvel événement
            loadEventMessages(eventId);
        }
    }
});

// Mettre à jour la sélection d"événement
function updateEventSelection(selectedItem) {
    // Retirer la classe active de tous les éléments
    document.querySelectorAll(".event-item").forEach(item => {
        item.classList.remove("active");
    });
    
    // Ajouter la classe active à l"élément sélectionné
    selectedItem.classList.add("active");
    
    // Mettre à jour le titre du chat
    if (chatTitle) {
        const eventName = selectedItem.querySelector("h6").textContent;
        chatTitle.textContent = eventName;
    }
}

// Auto-refresh des messages toutes les 30 secondes
setInterval(() => {
    if (currentEventId) {
        loadEventMessages(currentEventId);
    }
}, 30000);
