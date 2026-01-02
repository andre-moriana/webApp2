// Configuration
let currentGroupId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Initialiser avec le premier groupe
document.addEventListener("DOMContentLoaded", function() {
   
    if (typeof initialGroupId !== "undefined" && initialGroupId) {
        currentGroupId = initialGroupId.toString();
        // Charger automatiquement les messages du groupe initial
        loadGroupMessages(currentGroupId);
    }
});

// Créer un élément de message
function createMessageElement(message) {
    // Gérer les différentes structures de données du backend
    const authorId = message.author_id || message.author?._id || message.author?.id || message.userId || message.user_id;
    const authorName = message.author_name || message.author?.name || message.userName || "Utilisateur";
    const messageTime = message.created_at || message.createdAt || message.timestamp;
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
    // Vérifier si le message a une pièce jointe (peut être null, un objet, ou une chaîne)
    const hasAttachment = message.attachment && 
        (message.attachment !== null) && 
        (typeof message.attachment === 'object') &&
        (message.attachment.filename || message.attachment.url || message.attachment.path);
    
    if (hasAttachment) {
        let attachmentUrl = message.attachment.url || message.attachment.path || `/uploads/${message.attachment.filename}`;
        
        // Détecter si c'est une image ou un PDF par mimeType ou par extension
        let isImage = false;
        let isPdf = false;
        
        if (message.attachment.mimeType) {
            if (message.attachment.mimeType.startsWith("image/")) {
                isImage = true;
            } else if (message.attachment.mimeType === "application/pdf") {
                isPdf = true;
            }
        }
        
        // Détecter par extension si mimeType n'est pas disponible
        if (!isImage && !isPdf) {
            const filename = message.attachment.filename || message.attachment.originalName || attachmentUrl;
            const lowerFilename = filename.toLowerCase();
            const imageExtensions = [".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".svg"];
            const pdfExtensions = [".pdf"];
            
            isImage = imageExtensions.some(ext => lowerFilename.endsWith(ext));
            isPdf = pdfExtensions.some(ext => lowerFilename.endsWith(ext));
        }
        
        const originalName = message.attachment.originalName || message.attachment.filename || "Pièce jointe";
        
        // Définir l'URL originale pour tous les types de fichiers
        let originalUrl = message.attachment.url || message.attachment.path || `/uploads/${message.attachment.filename}`;
        
        // IMPORTANT: Corriger l'URL pour tous les fichiers
        // Si l'URL contient déjà api.arctraining.fr, l'utiliser telle quelle
        if (originalUrl.includes('api.arctraining.fr')) {
            attachmentUrl = originalUrl;
        } else {
            // Pour tous les autres cas, construire l'URL correcte vers api.arctraining.fr
            if (originalUrl.startsWith('/uploads/')) {
                attachmentUrl = 'https://api.arctraining.fr/uploads/messages/' + originalUrl;
            } else if (originalUrl.startsWith('uploads/')) {
                attachmentUrl = 'https://api.arctraining.fr/' + originalUrl;
            } else {
                // Si c'est juste un nom de fichier
                attachmentUrl = 'https://api.arctraining.fr/uploads/messages/' + originalUrl;
          }
        }
        
        attachmentHtml = `
            <div class="message-attachment mt-2">
                ${isImage 
                    ? `<a href="${attachmentUrl}" target="_blank" class="attachment-link">
                        <img src="${attachmentUrl}" 
                             alt="${originalName}" 
                             class="img-fluid rounded message-image" 
                             style="max-width: 300px; max-height: 300px; object-fit: cover; cursor: pointer;"
                             onerror="console.error('Erreur de chargement de l\\'image:', '${originalName}'); this.onerror=null; this.style.display='none'; const fallback = this.nextElementSibling; if(fallback) fallback.style.display='block';">
                        <div class="image-fallback" style="display: none;">
                            <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                                <i class="fas fa-image me-2"></i>
                                <span>${originalName}</span>
                            </div>
                        </div>
                    </a>`
                    : isPdf
                    ? `<div class="pdf-preview-container">
                        <iframe src="${attachmentUrl}" 
                                class="pdf-preview"
                                style="width: 100%; max-width: 600px; height: 400px; border: 1px solid #dee2e6; border-radius: 8px;"
                                title="${originalName}">
                        </iframe>
                        <div class="mt-2">
                            <a href="${attachmentUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Télécharger le PDF
                            </a>
                        </div>
                    </div>`
                    : `<a href="${attachmentUrl}" target="_blank" class="attachment-link">
                        <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                            <i class="fas fa-file me-2"></i>
                            <span>${originalName}</span>
                        </div>
                    </a>`
                }
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
    messagesContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement des messages...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`/api/messages/${groupId}/history`, {
            headers: {
                'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
            }
        });
        
        
        if (response.ok) {
            const data = await response.json();
            // L'API retourne directement un tableau de messages
            let messages = [];
            if (Array.isArray(data)) {
                messages = data;
            } else if (data.success && data.data && Array.isArray(data.data)) {
                messages = data.data;
            }
            
            if (messages.length > 0) {
                const messageElements = messages.map(message => createMessageElement(message)).filter(html => html !== "");
                
                if (messageElements.length > 0) {
                    messagesContainer.innerHTML = messageElements.join("");
               } else {
                    messagesContainer.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p>Aucun message valide dans le chat</p>
                        </div>
                    `;
                }
            } else {
               messagesContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>Aucun message dans le chat</p>
                    </div>
                `;
            }
        } else {
            const errorText = await response.text();
            messagesContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Erreur ${response.status}: ${errorText}</p>
                </div>
            `;
        }
        
    } catch (error) {
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
            return; // Laisser le navigateur gérer le clic normalement
        }
        
        e.preventDefault();
        
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
        
        const content = messageInput.value.trim();
        if (!content) return;
        
        // Vérifier si on est sur une page de sujet
        const isTopicPage = typeof window.isTopicPage !== 'undefined' && window.isTopicPage;
        const topicId = isTopicPage ? (window.currentTopicId || document.getElementById('current-topic-id')?.value) : null;
        
        if (!isTopicPage && !currentGroupId) return;
        
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
        if (messagesContainer) {
            messagesContainer.insertAdjacentHTML("beforeend", messageElement);
        }
        
        // Vider le champ de saisie
        messageInput.value = "";
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
     
        // Envoyer le message au backend
        try {
            let response;
            
            // Récupérer l'input de fichier une seule fois
            const attachmentInput = document.getElementById('message-attachment');
            
            if (isTopicPage && topicId) {
                // Envoyer un message de sujet via le backend PHP
                // Note: Pour l'instant, on n'envoie que le texte (les pièces jointes peuvent être ajoutées plus tard)
                response = await fetch(`/api/topics/${topicId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        content: content
                    })
                });
            } else {
                // Envoyer un message de groupe (code existant)
                // Utiliser FormData comme l'app mobile
                const formData = new FormData();
                formData.append('content', content);
                
                // Ajouter le fichier s'il y en a un
                if (attachmentInput && attachmentInput.files && attachmentInput.files[0]) {
                    const file = attachmentInput.files[0];
                    formData.append('attachment', file);
                }
                
                response = await fetch(`/messages/${currentGroupId}/send`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
                        // Ne pas définir Content-Type, laissez le navigateur le faire automatiquement pour FormData
                    },
                    body: formData
                });
            }
            
            if (response.ok) {
                const data = await response.json();
                // Mettre à jour l'ID du message avec celui du backend
                // Pour les sujets, la réponse peut être dans data.data
                const responseData = data.data || data;
                const realMessageId = responseData._id || responseData.id || responseData.message_id || data._id || data.id || data.message_id;
                if (realMessageId) {
                     // Mettre à jour l'attribut data-message-id dans le DOM
                    const messageElement = document.querySelector(`[data-message-id="${newMessage.id}"]`);
                    if (messageElement) {
                        messageElement.setAttribute('data-message-id', realMessageId);
                       
                        // Mettre à jour aussi les boutons d'action dans ce message
                        const editButton = messageElement.querySelector('button[onclick*="editMessage"]');
                        const deleteButton = messageElement.querySelector('button[onclick*="deleteMessage"]');
                        
                        if (editButton) {
                            editButton.setAttribute('onclick', `editMessage('${realMessageId}')`);
                         }
                        
                        if (deleteButton) {
                            deleteButton.setAttribute('onclick', `deleteMessage('${realMessageId}')`);
                         }
                    }
                    
                    // Mettre à jour l'ID dans l'objet newMessage pour les boutons d'action
                    newMessage.id = realMessageId;
                    newMessage._id = realMessageId;
                }
                
                // Vider l'input de fichier après envoi réussi
                if (attachmentInput) {
                    attachmentInput.value = '';
                }
                
                // Recharger les messages pour s'assurer que tout est à jour
                console.log("Message envoyé avec succès, rechargement des messages...");
                if (isTopicPage && topicId) {
                    // Pour les sujets, recharger la page pour afficher le nouveau message
                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                } else if (currentGroupId) {
                    // Pour les groupes, recharger les messages
                    setTimeout(() => {
                        loadGroupMessages(currentGroupId);
                    }, 500);
                }
            } else {
                 // Optionnel: Afficher un message d'erreur à l'utilisateur
                alert("Erreur lors de l'envoi du message");
            }
        } catch (error) {
            alert("Erreur lors de l'envoi du message");
        }
    });
}

// Rendez les fonctions globales
window.editMessage = async function(messageId) {
    
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
        const response = await fetch(`/messages/${messageId}`, {
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
            // Essayer de parser la réponse JSON, mais ne pas échouer si ce n'est pas du JSON
            try {
                const data = await response.json();
                console.log('Message modifié:', data);
            } catch (jsonError) {
                console.log('Réponse non-JSON reçue (normal pour la modification)');
            }
            
            closeEditModal();
            
            // Mettre à jour l'affichage du message sans recharger la page
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (messageElement) {
                const contentElement = messageElement.querySelector('.message-content');
                if (contentElement) {
                    contentElement.textContent = newContent;
                    contentElement.innerHTML = newContent.replace(/\n/g, "<br>");
                }
            }
        } else {
            try {
                const errorData = await response.json();
                if (response.status === 403) {
                    alert('Erreur: Vous ne pouvez modifier que vos propres messages');
                } else {
                    alert('Erreur: ' + (errorData.error || 'Erreur lors de la modification'));
                }
            } catch (jsonError) {
                alert('Erreur lors de la modification du message (code: ' + response.status + ')');
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la modification du message');
    }
}

window.deleteMessage = async function(messageId) {
     
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        try {
            const response = await fetch(`/messages/${messageId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
                }
            });
            
            if (response.ok) {
                // Essayer de parser la réponse JSON, mais ne pas échouer si ce n'est pas du JSON
                try {
                    const data = await response.json();
                    console.log('Message supprimé:', data);
                } catch (jsonError) {
                    console.log('Réponse non-JSON reçue (normal pour la suppression)');
                }
                
                // Supprimer visuellement le message du DOM
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.remove();
                }
            } else {
                try {
                    const errorData = await response.json();
                    alert('Erreur: ' + (errorData.error || 'Erreur lors de la suppression'));
                } catch (jsonError) {
                    alert('Erreur lors de la suppression du message (code: ' + response.status + ')');
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression du message');
        }
    }
};
