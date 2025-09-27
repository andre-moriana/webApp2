// Configuration
let currentGroupId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Logs de débogage
console.log("Script groups-chat.js chargé");
console.log("Variables disponibles:", {
    currentUserId: typeof currentUserId !== 'undefined' ? currentUserId : 'undefined',
    initialGroupId: typeof initialGroupId !== 'undefined' ? initialGroupId : 'undefined',
    backendUrl: typeof backendUrl !== 'undefined' ? backendUrl : 'undefined',
    isAdmin: typeof isAdmin !== 'undefined' ? isAdmin : 'undefined',
    authToken: typeof authToken !== 'undefined' ? authToken : 'undefined'
});

// Initialiser avec le premier groupe
document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM chargé, initialisation du chat");
    console.log("Éléments DOM trouvés:", {
        messagesContainer: !!messagesContainer,
        messageForm: !!messageForm,
        messageInput: !!messageInput,
        chatTitle: !!chatTitle
    });
    
    if (typeof initialGroupId !== "undefined" && initialGroupId) {
        currentGroupId = initialGroupId.toString();
        console.log("Chat initialisé avec le groupe ID:", currentGroupId);
        console.log("ID utilisateur actuel:", currentUserId);
        
        // Charger automatiquement les messages du groupe initial
        loadGroupMessages(currentGroupId);
    } else {
        console.log("Aucun groupe initial défini");
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
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ""}
                ${(isCurrentUser || (typeof isAdmin !== "undefined" && isAdmin)) ? `
                    <button type="button" class="btn btn-delete" onclick="deleteMessage('${message._id || message.id}')">
                        <i class="fas fa-trash"></i>
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
        // Ne pas intercepter les clics sur les boutons d'action
        if (e.target.closest('.btn-group') || 
            e.target.closest('a') || 
            e.target.closest('button') ||
            e.target.closest('.badge') ||
            e.target.closest('form') ||
            e.target.hasAttribute('data-ignore-chat') ||
            e.target.closest('[data-ignore-chat]') ||
            e.target.tagName === 'A' ||
            e.target.tagName === 'BUTTON' ||
            e.target.tagName === 'I' ||
            e.target.tagName === 'FORM') {
            console.log("Clic sur un bouton/lien/badge/form, laisser le navigateur gérer");
            return; // Laisser le navigateur gérer le clic normalement
        }
        
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
        
        // Créer le message temporaire pour l'affichage immédiat
        const newMessage = {
            id: Date.now().toString(),
            content: content,
            author_id: currentUserId,
            author_name: "Vous",
            created_at: new Date().toISOString()
        };
        
        // Afficher le message immédiatement
        const messageElement = createMessageElement(newMessage);
        messagesContainer.insertAdjacentHTML("beforeend", messageElement);
        
        // Vider le champ de saisie
        messageInput.value = "";
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        console.log("Message envoyé:", content);
        
        // Envoyer le message au backend
        try {
            // Utiliser FormData comme l'app mobile
            const formData = new FormData();
            formData.append('content', content);
            
            // Ajouter le fichier s'il y en a un
            const attachmentInput = document.getElementById('message-attachment');
            if (attachmentInput && attachmentInput.files && attachmentInput.files[0]) {
                const file = attachmentInput.files[0];
                formData.append('attachment', file);
                console.log("Fichier ajouté:", file.name, "Type:", file.type, "Taille:", file.size);
            }
            
            const response = await fetch(`${backendUrl}/api/messages/${currentGroupId}/send`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
                    // Ne pas définir Content-Type, laissez le navigateur le faire automatiquement pour FormData
                },
                body: formData
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log("Message sauvegardé avec succès:", data);
                
                // Mettre à jour l'ID du message avec celui du backend
                const realMessageId = data._id || data.id || data.message_id;
                if (realMessageId) {
                    console.log("Mise à jour de l'ID du message:", newMessage.id, "->", realMessageId);
                    
                    // Mettre à jour l'attribut data-message-id dans le DOM
                    const messageElement = document.querySelector(`[data-message-id="${newMessage.id}"]`);
                    if (messageElement) {
                        messageElement.setAttribute('data-message-id', realMessageId);
                        console.log("ID mis à jour dans le DOM");
                        
                        // Mettre à jour aussi les boutons d'action dans ce message
                        const editButton = messageElement.querySelector('button[onclick*="editMessage"]');
                        const deleteButton = messageElement.querySelector('button[onclick*="deleteMessage"]');
                        
                        if (editButton) {
                            editButton.setAttribute('onclick', `editMessage('${realMessageId}')`);
                            console.log("Bouton d'édition mis à jour");
                        }
                        
                        if (deleteButton) {
                            deleteButton.setAttribute('onclick', `deleteMessage('${realMessageId}')`);
                            console.log("Bouton de suppression mis à jour");
                        }
                    } else {
                        console.error("Élément de message non trouvé pour mise à jour de l'ID");
                    }
                    
                    // Mettre à jour l'ID dans l'objet newMessage pour les boutons d'action
                    newMessage.id = realMessageId;
                    newMessage._id = realMessageId;
                } else {
                    console.warn("Aucun ID de message reçu du backend");
                }
                
                // Vider l'input de fichier après envoi réussi
                if (attachmentInput) {
                    attachmentInput.value = '';
                }
            } else {
                console.error("Erreur lors de la sauvegarde du message:", response.status);
                // Optionnel: Afficher un message d'erreur à l'utilisateur
                alert("Erreur lors de l'envoi du message");
            }
        } catch (error) {
            console.error("Erreur lors de l'envoi du message:", error);
            alert("Erreur lors de l'envoi du message");
        }
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