// Configuration
let currentGroupId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Messages de test par groupe (fallback)
const testMessages = {
    "1": [
        {
            id: "1",
            content: "Bonjour tout le monde ! Bienvenue dans le groupe du Conseil d'administration.",
            author_id: "123",
            author_name: "Admin",
            created_at: new Date(Date.now() - 3600000).toISOString()
        }
    ],
    "2": [
        {
            id: "2",
            content: "Salut tout le monde ! Groupe principal du club.",
            author_id: "123",
            author_name: "Admin",
            created_at: new Date(Date.now() - 3600000).toISOString()
        }
    ]
};

// Initialiser avec le premier groupe
document.addEventListener("DOMContentLoaded", function() {
    if (typeof initialGroupId !== "undefined" && initialGroupId) {
        currentGroupId = initialGroupId.toString();
        console.log("Chat initialisé avec le groupe ID:", currentGroupId);
        console.log("ID utilisateur actuel:", currentUserId);
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
        let attachmentUrl = message.attachment.url || message.attachment.path || `/uploads/${message.attachment.filename}`;
        
        if (attachmentUrl && !attachmentUrl.startsWith("http")) {
            attachmentUrl = backendUrl + "/" + attachmentUrl.replace(/^\//, "");
        }
        
        const isImage = message.attachment.mimeType && message.attachment.mimeType.startsWith("image/");
        const originalName = message.attachment.originalName || message.attachment.filename || "Pièce jointe";
        
        attachmentHtml = `
            <div class="message-attachment mt-2">
                <a href="${attachmentUrl}" target="_blank" class="attachment-link">
                    ${isImage 
                        ? `<img src="${attachmentUrl}" 
                               alt="${originalName}" 
                               class="img-fluid rounded" 
                               style="max-width: 200px; max-height: 200px; object-fit: cover;">`
                        : `<div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                            <i class="fas fa-file me-2"></i>
                            <span>${originalName}</span>
                           </div>`
                    }
                </a>
            </div>`;
    }

    // Boutons d'action (modifier/supprimer)
    let actionButtons = "";
    if (isCurrentUser || (typeof isAdmin !== "undefined" && isAdmin)) {
        actionButtons = `
            <div class="message-actions">
                ${isCurrentUser ? `
                    <button type="button" class="btn btn-edit" onclick="editMessage('${message._id || message.id}')">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                ` : ""}
                ${(isCurrentUser || (typeof isAdmin !== "undefined" && isAdmin)) ? `
                    <button type="button" class="btn btn-delete" onclick="deleteMessage('${message._id || message.id}')">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                ` : ""}
            </div>
        `;
    }

    const messageContent = message.content ? message.content.replace(/\n/g, "<br>") : "";
    const hasContent = messageContent.trim() !== "";
    const hasAttachment = message.attachment && (message.attachment.filename || message.attachment.url || message.attachment.path);
    
    if (!hasContent && !hasAttachment) {
        return "";
    }

    return `
        <div class="d-flex ${alignClass} mb-3" data-message-id="${message._id || message.id}">
            <div class="message ${messageClass}">
                <div class="message-header">
                    <span class="message-author">${authorName}</span>
                    <span class="message-time">${formattedTime}</span>
                </div>
                ${hasContent ? `<div class="message-content">${messageContent}</div>` : ""}
                ${attachmentHtml}
                ${actionButtons}
            </div>
        </div>
    `;
}

// Charger les messages d'un groupe
async function loadGroupMessages(groupId) {
    console.log("Chargement des messages pour le groupe:", groupId);
    console.log("URL de l'API:", `${backendUrl}/api/messages/${groupId}/history`);
    console.log("Token d'auth:", authToken || localStorage.getItem('token') || sessionStorage.getItem('token'));
    
    messagesContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement des messages...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`${backendUrl}/api/messages/${groupId}/history`, {
            headers: {
                'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
            }
        });
        
        console.log("Réponse de l'API:", response.status, response.statusText);
        
        if (response.ok) {
            const data = await response.json();
            console.log("Données reçues:", data);
            
            // L'API retourne directement un tableau de messages
            let messages = [];
            if (Array.isArray(data)) {
                messages = data;
            } else if (data.success && data.data && Array.isArray(data.data)) {
                messages = data.data;
            }
            
            console.log("Messages trouvés:", messages.length);
            
            if (messages.length > 0) {
                const messageElements = messages.map(message => createMessageElement(message)).filter(html => html !== "");
                
                if (messageElements.length > 0) {
                    messagesContainer.innerHTML = messageElements.join("");
                    console.log("Messages affichés:", messageElements.length);
                } else {
                    messagesContainer.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p>Aucun message valide dans le chat</p>
                        </div>
                    `;
                }
            } else {
                console.log("Aucun message dans la réponse API");
                messagesContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>Aucun message dans le chat</p>
                    </div>
                `;
            }
        } else {
            const errorText = await response.text();
            console.error("Erreur API:", response.status, errorText);
            messagesContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Erreur ${response.status}: ${errorText}</p>
                </div>
            `;
        }
        
    } catch (error) {
        console.error("Erreur lors du chargement:", error);
        messagesContainer.innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p>Erreur de connexion: ${error.message}</p>
            </div>
        `;
    }
    
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Gérer la sélection d'un groupe
document.querySelectorAll(".group-item").forEach(item => {
    item.addEventListener("click", async (e) => {
        e.preventDefault();
        
        console.log("Clic sur le groupe:", item.dataset.groupId);
        
        document.querySelectorAll(".group-item").forEach(i => i.classList.remove("active"));
        item.classList.add("active");
        
        const groupName = item.querySelector("h6").textContent;
        chatTitle.textContent = groupName;
        
        currentGroupId = item.dataset.groupId;
        
        const currentGroupIdInput = document.getElementById("current-group-id");
        if (currentGroupIdInput) {
            currentGroupIdInput.value = currentGroupId;
        }
        
        await loadGroupMessages(currentGroupId);
    });
});

// Gérer l'envoi de messages
if (messageForm) {
    messageForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        if (!currentGroupId) return;
        
        const content = messageInput.value.trim();
        if (!content) return;
        
        const newMessage = {
            id: Date.now().toString(),
            content: content,
            author_id: currentUserId,
            author_name: "Vous",
            created_at: new Date().toISOString()
        };
        
        const messageElement = createMessageElement(newMessage);
        messagesContainer.insertAdjacentHTML("beforeend", messageElement);
        
        messageInput.value = "";
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        console.log("Message envoyé:", content);
    });
}

// Rendez les fonctions globales
window.editMessage = async function(messageId) {
    console.log("editMessage appelé avec ID:", messageId);
    
    if (!messageId || messageId === "") {
        alert("Erreur: ID du message manquant");
        return;
    }
    
    // Trouver le message dans le DOM pour récupérer le contenu actuel
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    let currentContent = "";
    
    if (messageElement) {
        const contentElement = messageElement.querySelector('.message-content');
        if (contentElement) {
            // Récupérer le texte sans les balises HTML
            currentContent = contentElement.textContent || contentElement.innerText || "";
        }
    }
    
    // Créer la modal d'édition
    const modal = document.createElement('div');
    modal.className = 'edit-modal';
    modal.innerHTML = `
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h5>Modifier le message</h5>
                <span class="edit-modal-close">&times;</span>
            </div>
            <textarea id="edit-message-content" placeholder="Saisissez votre message...">${currentContent}</textarea>
            <div class="edit-modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveMessageEdit('${messageId}')">Enregistrer</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
    modal.style.zIndex = '9999';
    modal.querySelector('.edit-modal-content').style.zIndex = '10000';
    document.body.style.overflow = 'hidden';
    
    // Focus sur le textarea
    const textarea = modal.querySelector('#edit-message-content');
    textarea.focus();
    textarea.select();
    
    // Fermer la modal en cliquant sur X ou en dehors
    modal.querySelector('.edit-modal-close').onclick = closeEditModal;
    modal.onclick = function(event) {
        if (event.target === modal) {
            closeEditModal();
        }
    };
};

function closeEditModal() {
    const modal = document.querySelector('.edit-modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

async function saveMessageEdit(messageId) {
    const textarea = document.querySelector('#edit-message-content');
    const newContent = textarea.value.trim();
    
    if (!newContent) {
        alert('Le message ne peut pas être vide');
        return;
    }
    
    try {
        const response = await fetch(`${backendUrl}/api/messages/${messageId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
            },
            body: JSON.stringify({
                content: newContent
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('Message modifié avec succès:', data);
            closeEditModal();
            // Recharger la page pour voir les changements
            window.location.reload();
        } else {
            const errorData = await response.json();
            if (response.status === 403) {
                alert('Erreur: Vous ne pouvez modifier que vos propres messages');
            } else {
                alert('Erreur: ' + (errorData.error || 'Erreur lors de la modification'));
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la modification du message');
    }
}

window.deleteMessage = async function(messageId) {
    console.log("deleteMessage appelé avec ID:", messageId);
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        try {
            const response = await fetch(`${backendUrl}/api/messages/${messageId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('Message supprimé avec succès:', data);
                // Recharger la page pour voir les changements
                window.location.reload();
            } else {
                const errorData = await response.json();
                alert('Erreur: ' + (errorData.error || 'Erreur lors de la suppression'));
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du message');
        }
    }
};

// Ajoutez ceci au début du fichier, après les sélecteurs DOM
console.log("Script groups-chat.js chargé");
console.log("Fonctions disponibles:", {
    editMessage: typeof editMessage,
    deleteMessage: typeof deleteMessage,
    loadGroupMessages: typeof loadGroupMessages
});