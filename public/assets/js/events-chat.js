// Configuration pour les événements
let currentEventId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Debug initial
console.log("=== EVENTS CHAT INITIALIZATION ===");
console.log("Messages container:", messagesContainer);
console.log("Message form:", messageForm);
console.log("Chat title:", chatTitle);

// Initialiser avec le premier événement
document.addEventListener("DOMContentLoaded", function() {
    console.log("=== DOM LOADED ===");
    console.log("Initial Event ID:", initialEventId);
    console.log("Current User ID:", currentUserId);
    
    if (typeof initialEventId !== "undefined" && initialEventId && initialEventId !== "null") {
        currentEventId = initialEventId.toString();
        console.log("Chat initialisé avec l'événement ID:", currentEventId);
        
        // Charger automatiquement les messages de l'événement initial
        loadEventMessages(currentEventId);
    } else {
        console.warn("Aucun événement initial trouvé");
    }
});

// Créer un élément de message
function createMessageElement(message) {
    console.log("Création d'un élément de message:", message);
    
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
        const filename = message.attachment.filename || message.attachment.original_name || "Pièce jointe";
        const mimeType = message.attachment.mime_type || "";
        
        // Construire l'URL complète pour l'image
        const fullUrl = attachmentUrl.startsWith('http') ? attachmentUrl : `${backendUrl}${attachmentUrl}`;
        
        // Détecter si c'est une image
        const isImage = mimeType.startsWith('image/') || 
                       /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(filename);
        
        if (isImage) {
            // Afficher l'image directement dans le chat
            attachmentHtml = `
                <div class="message-attachment mt-2">
                    <div class="image-container">
                        <img src="${fullUrl}" 
                             alt="${filename}" 
                             class="message-image img-fluid rounded" 
                             style="max-width: 300px; max-height: 300px; cursor: pointer;"
                             onclick="openImageModal('${fullUrl}', '${filename}')"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="image-fallback" style="display: none;">
                            <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-image me-1"></i>
                                ${filename}
                            </a>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Afficher un lien de téléchargement pour les autres fichiers
            attachmentHtml = `
                <div class="message-attachment mt-2">
                    <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-paperclip me-1"></i>
                        ${filename}
                    </a>
                </div>
            `;
        }
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

// Fonction pour ouvrir l'image en modal
function openImageModal(imageUrl, filename) {
    // Créer le modal s'il n'existe pas
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${filename}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${imageUrl}" class="img-fluid" alt="${filename}">
                    </div>
                    <div class="modal-footer">
                        <a href="${imageUrl}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i>
                            Ouvrir dans un nouvel onglet
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Mettre à jour l'image et le titre
    const modalImage = modal.querySelector('img');
    const modalTitle = modal.querySelector('.modal-title');
    const modalLink = modal.querySelector('a[target="_blank"]');
    
    modalImage.src = imageUrl;
    modalImage.alt = filename;
    modalTitle.textContent = filename;
    modalLink.href = imageUrl;
    
    // Afficher le modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Charger les messages d'un événement
async function loadEventMessages(eventId) {
    console.log("=== LOAD MESSAGES DEBUG ===");
    console.log("Chargement des messages pour l'événement:", eventId);
    console.log("URL:", `${backendUrl}/api/events/${eventId}/messages`);
    
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/messages`, {
            method: "GET",
            headers: {
                "Authorization": `Bearer ${authToken}`,
                "Content-Type": "application/json"
            }
        });
        
        console.log("Réponse GET reçue, status:", response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error("Erreur HTTP GET:", response.status, errorText);
            throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
        }
        
        const data = await response.json();
        console.log("Messages reçus:", data);
        console.log("Type de données:", typeof data);
        console.log("Est un tableau:", Array.isArray(data));
        
        // L'endpoint retourne directement un tableau de messages
        if (Array.isArray(data)) {
            console.log("Affichage de", data.length, "messages");
            displayMessages(data);
        } else if (data.error) {
            console.error("Erreur du serveur:", data.error);
            displayMessages([]);
        } else {
            console.warn("Format de données inattendu:", data);
            displayMessages([]);
        }
    } catch (error) {
        console.error("Erreur lors du chargement des messages:", error);
        displayMessages([]);
    }
}

// Afficher les messages
function displayMessages(messages) {
    console.log("=== DISPLAY MESSAGES DEBUG ===");
    console.log("Container des messages:", messagesContainer);
    console.log("Messages à afficher:", messages);
    
    if (!messagesContainer) {
        console.error("Container des messages non trouvé");
        return;
    }
    
    console.log("Affichage de", messages.length, "messages");
    
    messagesContainer.innerHTML = "";
    
    if (messages.length === 0) {
        console.log("Aucun message, affichage du message par défaut");
        messagesContainer.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <p>Aucun message dans le chat</p>
            </div>
        `;
        return;
    }
    
    console.log("Création des éléments de message...");
    messages.forEach((message, index) => {
        console.log(`Message ${index}:`, message);
        const messageElement = createMessageElement(message);
        messagesContainer.insertAdjacentHTML("beforeend", messageElement);
    });
    
    // Faire défiler vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    console.log("Messages affichés avec succès");
}

// Envoyer un message
async function sendMessage(content, attachment = null) {
    console.log("=== SEND MESSAGE DEBUG ===");
    console.log("Content:", content);
    console.log("Current Event ID:", currentEventId);
    console.log("Backend URL:", backendUrl);
    console.log("Auth Token:", authToken ? "Présent" : "Manquant");
    
    if (!currentEventId || currentEventId === "null") {
        console.error("Aucun événement sélectionné ou ID invalide:", currentEventId);
        alert("Veuillez sélectionner un événement avant d'envoyer un message");
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append("content", content);
        if (attachment) {
            formData.append("attachment", attachment);
        }
        
        // Debug: vérifier le contenu de FormData
        console.log("FormData contents:");
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        
        console.log("Envoi de la requête vers:", `${backendUrl}/api/events/${currentEventId}/messages`);
        
        const response = await fetch(`${backendUrl}/api/events/${currentEventId}/messages`, {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${authToken}`
            },
            body: formData
        });
        
        console.log("Réponse reçue, status:", response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error("Erreur HTTP:", response.status, errorText);
            throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
        }
        
        const data = await response.json();
        console.log("Réponse d'envoi:", data);
        
        if (data.success) {
            console.log("Message envoyé avec succès, rechargement des messages...");
            // Attendre un peu avant de recharger pour s'assurer que le message est sauvegardé
            setTimeout(() => {
                loadEventMessages(currentEventId);
            }, 500);
        } else {
            console.error("Erreur lors de l'envoi:", data.message || data.error);
            alert("Erreur lors de l'envoi du message: " + (data.message || data.error || "Erreur inconnue"));
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi du message:", error);
        alert("Erreur lors de l'envoi du message: " + error.message);
    }
}

// Gestion du formulaire de message
if (messageForm) {
    console.log("Formulaire de message trouvé, ajout de l'event listener");
    
    messageForm.addEventListener("submit", function(e) {
        e.preventDefault();
        console.log("=== FORM SUBMIT DEBUG ===");
        
        const content = messageInput.value.trim();
        console.log("Contenu du message:", content);
        
        if (!content) {
            console.warn("Contenu vide, envoi annulé");
            return;
        }
        
        const attachmentInput = document.getElementById("message-attachment");
        const attachment = attachmentInput.files[0] || null;
        console.log("Attachment:", attachment);
        
        sendMessage(content, attachment);
        messageInput.value = "";
        attachmentInput.value = "";
    });
} else {
    console.error("Formulaire de message non trouvé !");
}

// Gestion des clics sur les événements
document.addEventListener("click", function(e) {
    console.log("=== CLICK EVENT ===");
    console.log("Target:", e.target);
    console.log("Closest event-item:", e.target.closest(".event-item"));
    
    const eventItem = e.target.closest(".event-item");
    if (eventItem) {
        const eventId = eventItem.getAttribute("data-event-id");
        console.log("Event ID from data attribute:", eventId);
        console.log("Current Event ID:", currentEventId);
        
        if (eventId && eventId !== currentEventId && eventId !== "null") {
            console.log("Changement d'événement vers:", eventId);
            
            // Mettre à jour currentEventId AVANT updateEventSelection
            currentEventId = eventId;
            
            // Mettre à jour l'affichage
            updateEventSelection(eventItem);
            
            // Charger les messages du nouvel événement
            loadEventMessages(eventId);
        } else {
            console.log("Même événement ou ID invalide, pas de changement");
        }
    }
});

// Mettre à jour la sélection d'événement
function updateEventSelection(selectedItem) {
    console.log("=== UPDATE EVENT SELECTION ===");
    console.log("Selected item:", selectedItem);
    
    // Retirer la classe active de tous les éléments
    const allEventItems = document.querySelectorAll(".event-item");
    console.log("Tous les éléments événement:", allEventItems);
    
    allEventItems.forEach(item => {
        item.classList.remove("active");
    });
    
    // Ajouter la classe active à l'élément sélectionné
    selectedItem.classList.add("active");
    console.log("Classe active ajoutée à:", selectedItem);
    
    // Mettre à jour le titre du chat
    if (chatTitle) {
        const eventNameElement = selectedItem.querySelector("h6");
        if (eventNameElement) {
            const eventName = eventNameElement.textContent;
            chatTitle.textContent = eventName;
            console.log("Titre du chat mis à jour:", eventName);
        } else {
            console.warn("Élément h6 non trouvé dans l'événement sélectionné");
        }
    } else {
        console.warn("Élément chat-title non trouvé");
    }
    
    // IMPORTANT: Mettre à jour l'input caché current-event-id
    const currentEventIdInput = document.getElementById("current-event-id");
    if (currentEventIdInput) {
        currentEventIdInput.value = currentEventId;
        console.log("Input current-event-id mis à jour:", currentEventId);
    } else {
        console.warn("Input current-event-id non trouvé !");
    }
}

// Auto-refresh des messages toutes les 30 secondes
setInterval(() => {
    if (currentEventId) {
        loadEventMessages(currentEventId);
    }
}, 30000);
