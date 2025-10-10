// Configuration pour les événements
let currentEventId = null;

// Sélecteurs DOM
const messagesContainer = document.getElementById("messages-container");
const messageForm = document.getElementById("message-form");
const messageInput = document.getElementById("message-input");
const chatTitle = document.getElementById("chat-title");

// Initialiser avec le premier événement
document.addEventListener("DOMContentLoaded", function() {
    
    if (typeof initialEventId !== "undefined" && initialEventId && initialEventId !== "null") {
        currentEventId = initialEventId.toString();
        
        // Charger automatiquement les messages de l'événement initial
        loadEventMessages(currentEventId);
        
        // Vérifier l'état d'inscription de l'utilisateur
        checkEventRegistrationStatus(currentEventId);
    }
});

// Créer un élément de message
function createMessageElement(message) {
    
    // Gérer les différentes structures de données du backend
    const authorId = message.author_id || message.author?._id || message.author?.id || message.userId || message.user_id;
    const authorName = message.author_name || message.author?.name || message.userName || "Utilisateur inconnu";
    const isOwnMessage = authorId == currentUserId;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message-item ${isOwnMessage ? 'message-sent' : 'message-received'}`;
    messageDiv.setAttribute('data-message-id', message._id || message.id);
    
    const messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    
    // Métadonnées du message
    const messageMeta = document.createElement('div');
    messageMeta.className = 'message-meta';
    
    if (isOwnMessage) {
        const date = new Date(message.created_at).toLocaleDateString('fr-FR');
        messageMeta.innerHTML = `
            <span style="float: left;">${date}</span>
            <span style="float: right; font-weight: bold;">Vous</span>
            <div style="clear: both;"></div>
        `;
    } else {
        const date = new Date(message.created_at).toLocaleDateString('fr-FR');
        messageMeta.innerHTML = `<strong>${authorName}</strong> ${date}`;
    }
    
    // Contenu du message
    const messageText = document.createElement('div');
    messageText.className = 'message-text';
    messageText.textContent = message.content;
    
    messageContent.appendChild(messageMeta);
    messageContent.appendChild(messageText);
    
    // Gestion des pièces jointes
    if (message.attachment) {
        const attachmentUrl = message.attachment.url || message.attachment.path || '';
        const filename = message.attachment.filename || message.attachment.original_name || 'Pièce jointe';
        const mimeType = message.attachment.mime_type || '';
        
        // Construire l'URL complète pour l'image via le backend WebApp2
        let fullUrl;
        if (attachmentUrl.startsWith('http')) {
            fullUrl = attachmentUrl;
        } else {
            // Pour les images, utiliser la route d'images du backend WebApp2
            const messageId = message._id || message.id;
            fullUrl = `/messages/image/${messageId}?url=${encodeURIComponent(attachmentUrl)}`;
        }
        
        // Détecter si c'est une image
        const isImage = mimeType.startsWith('image/') || 
                       /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(filename);
        
        if (isImage) {
            // Afficher l'image directement dans le chat
            const attachmentDiv = document.createElement('div');
            attachmentDiv.className = 'message-attachment mt-2';
            attachmentDiv.innerHTML = `
                <div class="image-container">
                    <img src="${fullUrl}" 
                         alt="${filename}" 
                         class="message-image" 
                         onclick="openImageModal('${fullUrl}', '${filename}')"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="image-fallback" style="display: none;">
                        <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-image me-1"></i>
                            ${filename}
                        </a>
                    </div>
                </div>
            `;
            messageContent.appendChild(attachmentDiv);
        } else {
            // Afficher un lien de téléchargement pour les autres fichiers
            const attachmentDiv = document.createElement('div');
            attachmentDiv.className = 'message-attachment mt-2';
            attachmentDiv.innerHTML = `
                <a href="${fullUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-paperclip me-1"></i>
                    ${filename}
                </a>
            `;
            messageContent.appendChild(attachmentDiv);
        }
    }
    
    // Actions du message
    if (isOwnMessage) {
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'message-actions';
        actionsDiv.innerHTML = `
            <button type="button" class="btn btn-edit" onclick="editMessage('${message._id || message.id}')" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-delete" onclick="deleteMessage('${message._id || message.id}')" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        `;
        messageContent.appendChild(actionsDiv);
    }
    
    messageDiv.appendChild(messageContent);
    
    return messageDiv;
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
    try {
        const response = await fetch(`/events/${eventId}/messages`, {
            method: "GET",
            headers: {
                "Authorization": `Bearer ${authToken}`,
                "Content-Type": "application/json"
            }
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
        }
        
        const data = await response.json();
        
        // L'endpoint retourne directement un tableau de messages
        if (Array.isArray(data)) {
            displayMessages(data);
        } else if (data && data.error) {
            displayMessages([]);
        } else if (data && data.success === false) {
            displayMessages([]);
        } else {
            displayMessages([]);
        }
    } catch (error) {
        displayMessages([]);
    }
}

// Afficher les messages
function displayMessages(messages) {
    if (!messagesContainer) {
        return;
    }
    
    // Vider le container
    messagesContainer.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        const noMessagesDiv = document.createElement('div');
        noMessagesDiv.className = 'text-center text-muted';
        noMessagesDiv.innerHTML = `
            <i class="fas fa-comments fa-2x mb-2"></i>
            <p>Aucun message dans le chat</p>
        `;
        messagesContainer.appendChild(noMessagesDiv);
        return;
    }
    
    // Créer et ajouter chaque message
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        messagesContainer.appendChild(messageElement);
    });
    
    // Faire défiler vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Envoyer un message
async function sendMessage(content, attachment = null) {
    if (!currentEventId || currentEventId === "null") {
        alert("Veuillez sélectionner un événement avant d'envoyer un message");
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append("content", content);
        if (attachment) {
            formData.append("attachment", attachment);
        }
        
        const response = await fetch(`/events/${currentEventId}/messages`, {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${authToken}`
            },
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Attendre un peu avant de recharger pour s'assurer que le message est sauvegardé
            setTimeout(() => {
                loadEventMessages(currentEventId);
            }, 500);
        } else {
            alert("Erreur lors de l'envoi du message: " + (data.message || data.error || "Erreur inconnue"));
        }
    } catch (error) {
        alert("Erreur lors de l'envoi du message: " + error.message);
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
        
        if (eventId && eventId !== currentEventId && eventId !== "null") {
            // Mettre à jour currentEventId AVANT updateEventSelection
            currentEventId = eventId;
            
            // Mettre à jour l'affichage
            updateEventSelection(eventItem);
            
            // Charger les messages du nouvel événement
            loadEventMessages(eventId);
        }
    }
});

// Fonction pour vérifier l'état d'inscription d'un événement SANS s'inscrire automatiquement
async function checkEventRegistrationStatus(eventId) {
    try {
        // Utiliser l'endpoint API pour vérifier l'état sans s'inscrire
        const response = await fetch(`/events/${eventId}/data`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Vérifier si l'utilisateur actuel est dans la liste des membres
            const eventData = data.data || data;
            const isRegistered = eventData.members && eventData.members.some(member => 
                member._id == currentUserId || member.id == currentUserId
            );
            
            updateRegistrationButton(eventId, isRegistered);
            
            // Mettre à jour le nombre d'inscrits
            if (eventData.members && Array.isArray(eventData.members)) {
                updateMembersCount(eventId, eventData.members.length);
            }
            
            return isRegistered;
        } else {
            updateRegistrationButton(eventId, false);
            return false;
        }
    } catch (error) {
        updateRegistrationButton(eventId, false);
        return false;
    }
}

// Fonction pour s'inscrire à un événement (utilise l'endpoint /join)
window.registerToEvent = async function(eventId) {
    if (!eventId) {
        return;
    }
    
    try {
        const response = await fetch(`/events/${eventId}/join`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            updateRegistrationButton(eventId, true);
            
            // Mettre à jour le nombre d'inscrits
            if (data.members && Array.isArray(data.members)) {
                updateMembersCount(eventId, data.members.length);
            }
        } else if (response.status === 400 && data.error && data.error.includes('déjà inscrit')) {
            // L'utilisateur est déjà inscrit, mettre à jour l'interface
            updateRegistrationButton(eventId, true);
        }
    } catch (error) {
        // Erreur silencieuse
    }
};

// Fonction pour se désinscrire d'un événement (utilise l'endpoint /leave)
window.unregisterFromEvent = async function(eventId) {
    if (!eventId) {
        return;
    }
    
    try {
        const response = await fetch(`/events/${eventId}/leave`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            updateRegistrationButton(eventId, false);
            
            // Mettre à jour le nombre d'inscrits
            if (data.members && Array.isArray(data.members)) {
                updateMembersCount(eventId, data.members.length);
            }
        } else if (response.status === 400 && data.error && data.error.includes('pas inscrit')) {
            // L'utilisateur n'est pas inscrit, mettre à jour l'interface
            updateRegistrationButton(eventId, false);
        }
    } catch (error) {
        // Erreur silencieuse
    }
};

// Fonction pour mettre à jour le bouton d'inscription
function updateRegistrationButton(eventId, isRegistered) {
    const registerBtn = document.getElementById('register-btn');
    const unregisterBtn = document.getElementById('unregister-btn');
    const statusDiv = document.getElementById('registration-status');
    
    // Vérifier si les boutons existent (ils n'existent que dans la page de détail)
    if (registerBtn || unregisterBtn || statusDiv) {
        if (isRegistered) {
            if (registerBtn) registerBtn.style.display = 'none';
            if (unregisterBtn) unregisterBtn.style.display = 'inline-block';
            if (statusDiv) {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Vous êtes inscrit à cet événement';
            }
        } else {
            if (registerBtn) registerBtn.style.display = 'inline-block';
            if (unregisterBtn) unregisterBtn.style.display = 'none';
            if (statusDiv) {
                statusDiv.className = 'alert alert-info';
                statusDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Cliquez sur "Rejoindre" pour vous inscrire à cet événement';
            }
        }
    }
}

// Fonction pour mettre à jour le nombre d'inscrits
function updateMembersCount(eventId, membersCount) {
    // Mettre à jour dans la liste des événements
    const eventItem = document.querySelector(`[data-event-id="${eventId}"]`);
    if (eventItem) {
        const membersCountElement = eventItem.querySelector('.members-count');
        if (membersCountElement) {
            // Garder l'icône et mettre à jour seulement le nombre
            membersCountElement.innerHTML = `<i class="fas fa-users me-1"></i>${membersCount}`;
        }
    }
    
    // Mettre à jour dans le détail de l'événement
    const detailMembersCount = document.getElementById('detail-members-count');
    if (detailMembersCount) {
        detailMembersCount.textContent = `${membersCount} inscrit${membersCount > 1 ? 's' : ''}`;
    }
}

// Variables pour le modal des participants
let participantsModalVisible = false;
let participants = [];
let participantsLoading = false;
let participantsError = null;

// Fonction pour charger les participants d'un événement
async function fetchParticipants(eventId) {
    participantsLoading = true;
    participantsError = null;
    
    try {
        const response = await fetch(`/events/${eventId}/data`, {
            headers: {
                'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Erreur lors du chargement des participants');
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.members) {
            participants = data.data.members.map(member => member.name || 'Nom inconnu');
            participantsLoading = false;
            participantsModalVisible = true;
            showParticipantsModal();
        } else {
            throw new Error('Données de participants invalides');
        }
    } catch (error) {
        participantsError = error.message;
        participantsLoading = false;
        participantsModalVisible = true;
        showParticipantsModal();
    }
}

// Fonction pour afficher le modal des participants
function showParticipantsModal() {
    // Créer le modal s'il n'existe pas
    let modal = document.getElementById('participantsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'participantsModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Participants</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="participantsModalBody">
                        <!-- Contenu sera rempli dynamiquement -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Remplir le contenu du modal
    const modalBody = modal.querySelector('#participantsModalBody');
    
    if (participantsLoading) {
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement des participants...</p>
            </div>
        `;
    } else if (participantsError) {
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${participantsError}
            </div>
        `;
    } else if (participants.length === 0) {
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun participant</h5>
                <p class="text-muted">Personne ne s'est encore inscrit à cet événement.</p>
            </div>
        `;
    } else {
        let participantsHtml = '<div class="list-group">';
        participants.forEach((name, index) => {
            participantsHtml += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            ${name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h6 class="mb-0">${name}</h6>
                        </div>
                    </div>
                    <span class="badge bg-success">Inscrit</span>
                </div>
            `;
        });
        participantsHtml += '</div>';
        modalBody.innerHTML = participantsHtml;
    }
    
    // Afficher le modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Mettre à jour la sélection d'événement
function updateEventSelection(selectedItem) {
    // Retirer la classe active de tous les éléments
    const allEventItems = document.querySelectorAll(".event-item");
    
    allEventItems.forEach(item => {
        item.classList.remove("active");
    });
    
    // Ajouter la classe active à l'élément sélectionné
    selectedItem.classList.add("active");
    
    // Mettre à jour l'ID de l'événement actuel
    const eventId = selectedItem.getAttribute("data-event-id");
    currentEventId = eventId;
    
    // Mettre à jour le titre du chat
    const eventTitle = selectedItem.querySelector(".event-title")?.textContent || "Événement";
    if (chatTitle) {
        chatTitle.textContent = eventTitle;
    }
    
    // Mettre à jour l'input caché pour l'envoi de messages
    const eventIdInput = document.getElementById("current-event-id");
    if (eventIdInput) {
        eventIdInput.value = eventId;
    }
    
    // Mettre à jour le lien "Voir détails"
    const viewDetailsBtn = document.getElementById("view-details-btn");
    if (viewDetailsBtn && eventId) {
        viewDetailsBtn.href = `/events/${eventId}`;
    }
    
    // Charger les messages de l'événement sélectionné
    if (eventId) {
        loadEventMessages(eventId);
        // Vérifier l'état d'inscription SANS s'inscrire automatiquement
        checkEventRegistrationStatus(eventId);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le chat avec l'événement par défaut
    if (initialEventId && initialEventId !== "null") {
        currentEventId = initialEventId;
        loadEventMessages(currentEventId);
        
        // Vérifier l'état d'inscription SANS s'inscrire automatiquement
        checkEventRegistrationStatus(currentEventId);
        
        // Mettre à jour le lien "Voir détails" pour l'événement initial
        const viewDetailsBtn = document.getElementById("view-details-btn");
        if (viewDetailsBtn) {
            viewDetailsBtn.href = `/events/${currentEventId}`;
        }
    }
    
    // Les event listeners sont déjà configurés dans le code existant
});

// Fonctions pour l'édition et suppression des messages
window.editMessage = async function(messageId) {
    if (!messageId || messageId === "") {
        alert("Erreur: ID du message manquant");
        return;
    }
    
    // Trouver le message dans le DOM pour récupérer le contenu actuel
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    let currentContent = "";
    
    if (messageElement) {
        // Récupérer seulement le texte du message, pas les métadonnées
        const messageTextElement = messageElement.querySelector('.message-text');
        if (messageTextElement) {
            currentContent = messageTextElement.textContent || messageTextElement.innerText || "";
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
        const response = await fetch(`/events/messages/${messageId}`, {
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
            } catch (jsonError) {
                // Réponse non-JSON reçue (normal pour la modification)
            }
            
            closeEditModal();
            
            // Mettre à jour l'affichage du message sans recharger
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
        alert('Erreur lors de la modification du message');
    }
}

window.deleteMessage = async function(messageId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        try {
            const response = await fetch(`/events/messages/${messageId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${authToken || localStorage.getItem('token') || sessionStorage.getItem('token')}`
                }
            });
            
            if (response.ok) {
                // Essayer de parser la réponse JSON, mais ne pas échouer si ce n'est pas du JSON
                try {
                    const data = await response.json();
                } catch (jsonError) {
                    // Réponse non-JSON reçue (normal pour la suppression)
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
            alert('Erreur lors de la suppression du message');
        }
    }
};

// Fonctions de test supprimées - elles utilisaient l'API externe directement
// Ces fonctions ne sont plus nécessaires car toutes les requêtes passent maintenant par le backend WebApp2
