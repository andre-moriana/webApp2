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
        
        // Charger les formulaires de l'événement initial
        loadEventForms(currentEventId);
        
        // Vérifier l'état d'inscription de l'utilisateur
        checkEventRegistrationStatus(currentEventId);
    }
});

// Fonction pour échapper le HTML (similaire à groups-chat.js)
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Créer un élément de message (format similaire à groups-chat.js)
function createMessageElement(message) {
    // Gérer les différentes structures de données du backend (historique event = author { _id, name } comme les groupes)
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
    const att = message.attachment;
    const hasAttachment = att != null
        && typeof att === "object"
        && !Array.isArray(att)
        && (
            att.filename
            || att.url
            || att.path
            || att.storedFilename
            || (typeof att.size === "number" && att.size > 0)
            || att.originalName
            || att.original_name
        );
    
    if (hasAttachment) {
        let attachmentUrl = att.url || att.path || (att.filename ? `/uploads/${att.filename}` : "");

        let isImage = false;
        let isPdf = false;

        const mimeType = att.mimeType || att.mime_type || "";

        if (mimeType) {
            if (mimeType.startsWith("image/")) {
                isImage = true;
            } else if (mimeType === "application/pdf") {
                isPdf = true;
            }
        }

        if (!isImage && !isPdf) {
            const filename = att.filename || att.originalName || att.original_name || att.storedFilename || attachmentUrl;
            const lowerFilename = String(filename).toLowerCase();
            const imageExtensions = [".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".svg"];
            const pdfExtensions = [".pdf"];

            isImage = imageExtensions.some(ext => lowerFilename.endsWith(ext));
            isPdf = pdfExtensions.some(ext => lowerFilename.endsWith(ext));
        }

        const originalName = att.originalName || att.original_name || att.filename || att.storedFilename || "Pièce jointe";

        // Même logique que groups-chat.js : l'URL absolue des fichiers est fournie par le backend WebApp2 (getEventMessages).
        let originalUrl = att.url || att.path || (att.filename ? `/uploads/${att.filename}` : "") || (att.storedFilename ? `/uploads/messages/${att.storedFilename}` : "");

        if (/^https?:\/\//i.test(originalUrl)) {
            // URL absolue déjà normalisée par le backend WebApp2
        } else if (originalUrl.startsWith('/uploads/')) {
            originalUrl = 'https://api.arctraining.fr' + originalUrl;
        } else if (originalUrl.startsWith('uploads/')) {
            originalUrl = 'https://api.arctraining.fr/' + originalUrl;
        } else {
            originalUrl = 'https://api.arctraining.fr/uploads/messages/' + originalUrl;
        }

        if (isImage) {
            attachmentUrl = '/messages/image/' + (message._id || message.id) + '?url=' + encodeURIComponent(originalUrl);
        } else if (isPdf) {
            attachmentUrl = '/messages/attachment/' + (message._id || message.id) + '?inline=1&url=' + encodeURIComponent(originalUrl);
        } else {
            attachmentUrl = '/messages/attachment/' + (message._id || message.id) + '?url=' + encodeURIComponent(originalUrl);
        }

        attachmentHtml = `
            <div class="message-attachment mt-2">
                ${isImage
                    ? `<a href="${attachmentUrl}" target="_blank" class="attachment-link">
                        <img src="${attachmentUrl}"
                             alt="${escapeHtml(originalName)}"
                             class="img-fluid rounded message-image"
                             style="max-width: 300px; max-height: 300px; object-fit: cover; cursor: pointer;"
                             onerror="console.error('Erreur de chargement de l\\'image:', '${escapeHtml(originalName)}'); this.onerror=null; this.style.display='none'; const fallback = this.nextElementSibling; if(fallback) fallback.style.display='block';">
                        <div class="image-fallback" style="display: none;">
                            <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                                <i class="fas fa-image me-2"></i>
                                <span>${escapeHtml(originalName)}</span>
                            </div>
                        </div>
                    </a>`
                    : isPdf
                    ? `<div class="pdf-preview-container">
                        <iframe src="${attachmentUrl}"
                                class="pdf-preview"
                                style="width: 100%; max-width: 600px; height: 400px; border: 1px solid #dee2e6; border-radius: 8px;"
                                title="${escapeHtml(originalName)}"
                                type="application/pdf">
                        </iframe>
                        <div class="mt-2">
                            <a href="${attachmentUrl.replace('?inline=1&', '?')}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Télécharger le PDF
                            </a>
                        </div>
                    </div>`
                    : `<a href="${attachmentUrl}" target="_blank" class="attachment-link">
                        <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                            <i class="fas fa-file me-2"></i>
                            <span>${escapeHtml(originalName)}</span>
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
                    <span class="message-author">${escapeHtml(authorName)}</span>
                    <span class="message-time">${formattedTime}</span>
                </div>
                ${hasContent ? `<div class="message-content">${messageContent}</div>` : ""}
                ${attachmentHtml}
                ${actionButtons}
            </div>
        </div>
    `;
}


// Charger les messages d'un événement (même schéma que le chat groupe / sujet : API interne + jeton)
async function loadEventMessages(eventId) {
    if (!messagesContainer) {
        return;
    }

    messagesContainer.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement des messages...</p>
        </div>
    `;

    const token = (typeof authToken !== "undefined" && authToken)
        ? authToken
        : (localStorage.getItem("token") || sessionStorage.getItem("token") || "");

    try {
        const response = await fetch(`/api/events/${eventId}/messages`, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            credentials: "same-origin"
        });

        if (!response.ok) {
            const errorText = await response.text();
            messagesContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Erreur ${response.status}: ${escapeHtml(errorText.substring(0, 500))}</p>
                </div>
            `;
            return;
        }

        const data = await response.json();

        let messages = [];
        if (Array.isArray(data)) {
            messages = data;
        } else if (data && data.data && Array.isArray(data.data)) {
            messages = data.data;
        } else if (data && data.success && Array.isArray(data.data)) {
            messages = data.data;
        }

        messages.sort((a, b) => {
            const dateA = new Date(a.created_at || a.createdAt || a.timestamp || 0);
            const dateB = new Date(b.created_at || b.createdAt || b.timestamp || 0);
            return dateA.getTime() - dateB.getTime();
        });

        displayMessages(messages);
    } catch (error) {
        console.error("Erreur lors du chargement des messages:", error);
        messagesContainer.innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p>Erreur de connexion: ${escapeHtml(error.message || "")}</p>
            </div>
        `;
    }

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Afficher les messages
function displayMessages(messages) {
    if (!messagesContainer) {
        return;
    }
    
    // Vider le container
    messagesContainer.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <p>Aucun message dans le chat</p>
            </div>
        `;
        return;
    }
    
    // Créer et ajouter chaque message
    let messagesHtml = '';
    messages.forEach(message => {
        const messageHtml = createMessageElement(message);
        if (messageHtml) {
            messagesHtml += messageHtml;
        }
    });
    
    messagesContainer.innerHTML = messagesHtml;
    
    // Faire défiler vers le bas
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
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
        
        const response = await fetch(`/api/events/${currentEventId}/messages`, {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erreur HTTP: ${response.status} - ${errorText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Réinitialiser le formulaire
            if (messageInput) messageInput.value = "";
            const attachmentInput = document.getElementById("message-attachment");
            if (attachmentInput) attachmentInput.value = "";
            
            // Attendre un peu avant de recharger pour s'assurer que le message est sauvegardé
            setTimeout(() => {
                loadEventMessages(currentEventId);
                // Les formulaires seront rechargés automatiquement par loadEventMessages modifié
            }, 500);
        } else {
            alert("Erreur lors de l'envoi du message: " + (data.message || data.error || "Erreur inconnue"));
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi du message:", error);
        alert("Erreur lors de l'envoi du message: " + error.message);
    }
}

// Gestion du formulaire de message
if (messageForm) {
    messageForm.addEventListener("submit", function(e) {
        e.preventDefault();
        
        const content = messageInput.value.trim();
        const attachmentInput = document.getElementById("message-attachment");
        const attachment = attachmentInput && attachmentInput.files && attachmentInput.files[0] ? attachmentInput.files[0] : null;

        if (!content && !attachment) {
            return;
        }

        sendMessage(content, attachment);
        messageInput.value = "";
        if (attachmentInput) attachmentInput.value = "";
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
        // Les formulaires seront rechargés automatiquement par loadEventMessages modifié
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
        // Les formulaires seront rechargés automatiquement par loadEventMessages modifié
        
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

// ============================================
// GESTION DES FORMULAIRES POUR LES ÉVÉNEMENTS
// ============================================

// Variable pour stocker les formulaires de l'événement actuel
let currentEventForms = [];
let currentEventFormResponseCounts = {};

// Fonction pour charger les formulaires d'un événement
function loadEventForms(eventId) {
    //console.log('Chargement des formulaires pour l\'événement:', eventId);
    
    // Réinitialiser les formulaires et compteurs avant de charger les nouveaux
    currentEventForms = [];
    currentEventFormResponseCounts = {};
    
    // Supprimer les formulaires existants du DOM immédiatement
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        const existingForms = messagesContainer.querySelectorAll('.form-message');
        existingForms.forEach(form => form.remove());
    }
    
    fetch(`/api/events/${eventId}/forms`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        //console.log('Réponse API formulaires, status:', response.status);
        return response.json();
    })
    .then(data => {
        //console.log('Données formulaires reçues:', data);
        // Gérer différents formats de réponse API
        let forms = [];
        if (data.success && data.data) {
            if (Array.isArray(data.data)) {
                forms = data.data;
            } else if (data.data.data && Array.isArray(data.data.data)) {
                // Format imbriqué: {success: true, data: {data: [...]}}
                forms = data.data.data;
            } else if (Array.isArray(data.data)) {
                forms = data.data;
            }
        } else if (Array.isArray(data)) {
            forms = data;
        }
        
        if (forms.length > 0) {
            currentEventForms = forms;
            //console.log('Formulaires trouvés:', currentEventForms.length);
            // Initialiser les compteurs à 0 pour tous les formulaires
            currentEventForms.forEach(function(form) {
                currentEventFormResponseCounts[form.id] = 0;
            });
            // Ajouter les formulaires aux messages affichés immédiatement
            addEventFormsToMessages();
            // Charger les compteurs de réponses pour chaque formulaire (asynchrone)
            loadEventFormResponseCounts(forms);
        } else {
            //console.log('Aucun formulaire trouvé');
            currentEventForms = [];
            // Appeler addEventFormsToMessages même s'il n'y a pas de formulaires pour s'assurer que les anciens sont supprimés
            addEventFormsToMessages();
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des formulaires:', error);
        currentEventForms = [];
        // Appeler addEventFormsToMessages même en cas d'erreur pour supprimer les anciens formulaires
        addEventFormsToMessages();
    });
}

// Fonction pour charger les compteurs de réponses
function loadEventFormResponseCounts(forms) {
    forms.forEach(function(form) {
        fetch(`/api/forms/${form.id}/responses`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && Array.isArray(data.data)) {
                currentEventFormResponseCounts[form.id] = data.data.length;
            } else {
                currentEventFormResponseCounts[form.id] = 0;
            }
            // Mettre à jour l'affichage des formulaires
            updateEventFormDisplay();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des réponses:', error);
            currentEventFormResponseCounts[form.id] = 0;
        });
    });
}

// Fonction pour ajouter les formulaires aux messages (en bas de la page)
function addEventFormsToMessages() {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) {
        console.error('Conteneur de messages non trouvé');
        return;
    }
    
    //console.log('Ajout des formulaires, nombre:', currentEventForms.length);
    
    // Vérifier si des formulaires sont déjà affichés
    const existingForms = messagesContainer.querySelectorAll('.form-message');
    if (existingForms.length > 0) {
        // Supprimer les anciens formulaires
        existingForms.forEach(form => form.remove());
    }
    
    if (currentEventForms.length === 0) {
        //console.log('Aucun formulaire à afficher');
        return;
    }
    
    // Ajouter les formulaires à la fin du conteneur (après les messages)
    const fragment = document.createDocumentFragment();
    currentEventForms.forEach(function(form) {
        const responseCount = currentEventFormResponseCounts[form.id] || 0;
        const formElement = document.createElement('div');
        formElement.className = 'form-message mb-3';
        formElement.setAttribute('data-form-id', form.id);
        formElement.innerHTML = `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">📊 ${escapeHtml(form.title || 'Formulaire')}</h6>
                </div>
                <div class="card-body">
                    ${form.description && form.description.trim() ? `<div class="form-description mb-3">
                        <p class="text-muted mb-0">${escapeHtml(form.description)}</p>
                    </div>` : ''}
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-primary" onclick="openEventFormModal(${form.id})">
                            📝 Répondre
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewEventFormResults(${form.id})">
                            📊 Résultats (${responseCount})
                        </button>
                        ${window.isAdmin ? `<button class="btn btn-sm btn-danger" onclick="deleteEventForm(${form.id})">
                            🗑️ Supprimer
                        </button>` : ''}
                    </div>
                </div>
            </div>
        `;
        fragment.appendChild(formElement);
    });
    
    // Ajouter les formulaires à la fin du conteneur
    messagesContainer.appendChild(fragment);
    
    //console.log('Formulaires ajoutés avec succès en bas de page');
}

// Fonction pour mettre à jour l'affichage des formulaires
function updateEventFormDisplay() {
    currentEventForms.forEach(function(form) {
        const formElement = document.querySelector(`[data-form-id="${form.id}"]`);
        if (formElement) {
            const resultsButton = formElement.querySelector('.btn-info');
            if (resultsButton) {
                const responseCount = currentEventFormResponseCounts[form.id] || 0;
                resultsButton.textContent = `📊 Résultats (${responseCount})`;
            }
        }
    });
}

// Fonction pour ouvrir le modal de formulaire
window.openEventFormModal = function(formId) {
    const form = currentEventForms.find(f => f.id == formId);
    if (!form) return;
    
    // Créer ou récupérer le modal
    let modal = document.getElementById('event-form-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'event-form-modal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        document.body.appendChild(modal);
    }
    
    // Générer le HTML du formulaire (identique à groups-topics.js)
    let formHtml = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📊 ${escapeHtml(form.title || 'Formulaire')}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ${form.description ? `<p class="text-muted mb-3">${escapeHtml(form.description)}</p>` : ''}
                    <form id="event-form-response-form">
                        <input type="hidden" id="event-form-id" value="${form.id}">
    `;
    
    if (form.questions && Array.isArray(form.questions)) {
        form.questions.forEach(function(question, index) {
            formHtml += `
                <div class="mb-3">
                    <label class="form-label">
                        ${escapeHtml(question.text || '')}
                        ${question.required ? '<span class="text-danger">*</span>' : ''}
                    </label>
            `;
            
            if (question.type === 'text') {
                formHtml += `
                    <textarea class="form-control" name="question_${question.id}" 
                              ${question.required ? 'required' : ''} 
                              rows="3" placeholder="Votre réponse..."></textarea>
                `;
            } else if (question.type === 'radio' && question.options) {
                question.options.forEach(function(option) {
                    formHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="question_${question.id}" 
                                   value="${escapeHtml(option)}" id="radio_${question.id}_${option}" 
                                   ${question.required ? 'required' : ''}>
                            <label class="form-check-label" for="radio_${question.id}_${option}">
                                ${escapeHtml(option)}
                            </label>
                        </div>
                    `;
                });
            } else if (question.type === 'checkbox' && question.options) {
                question.options.forEach(function(option) {
                    formHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="question_${question.id}[]" 
                                   value="${escapeHtml(option)}" id="checkbox_${question.id}_${option}">
                            <label class="form-check-label" for="checkbox_${question.id}_${option}">
                                ${escapeHtml(option)}
                            </label>
                        </div>
                    `;
                });
            }
            
            formHtml += `</div>`;
        });
    }
    
    formHtml += `
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitEventFormResponse()">Envoyer</button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = formHtml;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
};

// Fonction pour soumettre une réponse de formulaire
window.submitEventFormResponse = function() {
    const formId = document.getElementById('event-form-id')?.value;
    if (!formId) return;
    
    const form = document.getElementById('event-form-response-form');
    const formData = new FormData(form);
    const responses = {};
    
    // Collecter les réponses
    formData.forEach((value, key) => {
        if (key.startsWith('question_')) {
            const questionId = key.replace('question_', '').replace('[]', '');
            if (key.includes('[]')) {
                // Checkbox multiple
                if (!responses[questionId]) {
                    responses[questionId] = [];
                }
                responses[questionId].push(value);
            } else {
                // Radio ou text
                responses[questionId] = value;
            }
        }
    });
    
    // Vérifier les champs obligatoires
    const formObj = currentEventForms.find(f => f.id == formId);
    if (formObj && formObj.questions) {
        const missingRequired = formObj.questions.filter(q => 
            q.required && (!responses[q.id] || (Array.isArray(responses[q.id]) && responses[q.id].length === 0))
        );
        if (missingRequired.length > 0) {
            alert('Veuillez répondre à toutes les questions obligatoires');
            return;
        }
    }
    
    // Envoyer la réponse
    fetch(`/api/forms/${formId}/responses`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ responses: responses })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Votre réponse a été enregistrée');
            bootstrap.Modal.getInstance(document.getElementById('event-form-modal')).hide();
            // Recharger les compteurs de réponses
            if (currentEventId) {
                loadEventForms(currentEventId);
            }
        } else {
            alert('Erreur lors de l\'envoi de la réponse: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'envoi de la réponse');
    });
};

// Fonction pour voir les résultats d'un formulaire (identique à groups-topics.js)
window.viewEventFormResults = function(formId) {
    //console.log('Chargement des résultats pour le formulaire:', formId);
    fetch(`/api/forms/${formId}/responses`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        //console.log('Réponse API résultats, status:', response.status);
        return response.json();
    })
    .then(data => {
        //console.log('Données résultats reçues:', data);
        // Gérer différents formats de réponse API
        let responses = [];
        if (data.success && data.data) {
            if (Array.isArray(data.data)) {
                responses = data.data;
            } else if (data.data.data && Array.isArray(data.data.data)) {
                responses = data.data.data;
            }
        } else if (Array.isArray(data)) {
            responses = data;
        }
        
        //console.log('Réponses extraites:', responses.length);
        
        if (responses.length > 0) {
            const form = currentEventForms.find(f => f.id == formId);
            if (!form) {
                console.error('Formulaire non trouvé:', formId);
                alert('Formulaire non trouvé');
                return;
            }
            showEventFormResultsModal(form, responses);
        } else {
            alert('Aucune réponse disponible pour ce formulaire');
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des résultats:', error);
        alert('Erreur lors du chargement des résultats: ' + error.message);
    });
};

// Fonction pour afficher les résultats dans un modal (identique à groups-topics.js)
function showEventFormResultsModal(form, responses) {
    //console.log('Affichage des résultats pour le formulaire:', form);
    //console.log('Nombre de réponses:', responses.length);
    
    let modal = document.getElementById('event-form-results-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'event-form-results-modal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        document.body.appendChild(modal);
    }
    
    let resultsHtml = `
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📊 Résultats du Sondage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
    `;
    
    if (responses.length === 0) {
        resultsHtml += '<p class="text-center text-muted">Aucune réponse pour le moment</p>';
    } else {
        resultsHtml += `
            <div class="alert alert-info mb-4">
                <strong>📊 ${responses.length}</strong> réponse${responses.length > 1 ? 's' : ''} au total
            </div>
        `;
        
        if (form && form.questions && Array.isArray(form.questions)) {
            form.questions.forEach(function(question, questionIndex) {
                const questionResponses = [];
                
                responses.forEach(function(response) {
                    let parsedResponses = response.responses;
                    if (typeof response.responses === 'string') {
                        try {
                            parsedResponses = JSON.parse(response.responses);
                        } catch (e) {
                            parsedResponses = response.responses;
                        }
                    }
                    
                    let answer = null;
                    if (parsedResponses && typeof parsedResponses === 'object') {
                        answer = parsedResponses[question.id] || parsedResponses[question._id] || parsedResponses[String(question.id)] || parsedResponses[String(question._id)];
                        if (!answer && questionIndex !== undefined) {
                            const keys = Object.keys(parsedResponses);
                            if (keys[questionIndex]) {
                                answer = parsedResponses[keys[questionIndex]];
                            }
                        }
                    }
                    
                    if (answer !== null && answer !== undefined && answer !== '') {
                        const userName = response.user?.name || response.user_name || response.username || `Utilisateur ${response.user_id || ''}`;
                        questionResponses.push({
                            user: userName,
                            answer: Array.isArray(answer) ? answer.join(', ') : String(answer),
                            date: response.submitted_at || response.created_at || response.createdAt
                        });
                    }
                });
                
                const choicesMap = {};
                questionResponses.forEach(function(resp) {
                    const choice = resp.answer;
                    if (!choicesMap[choice]) {
                        choicesMap[choice] = [];
                    }
                    choicesMap[choice].push(resp.user);
                });
                
                resultsHtml += `
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">❓ ${escapeHtml(question.text || 'Question ' + (questionIndex + 1))}</h6>
                        </div>
                        <div class="card-body">
                `;
                
                Object.entries(choicesMap).forEach(function([choice, users]) {
                    resultsHtml += `
                        <div class="mb-3 p-3 border rounded">
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-success me-2">✅</span>
                                <strong>${escapeHtml(choice)}</strong>
                                <span class="badge bg-primary ms-2">${users.length} réponse${users.length > 1 ? 's' : ''}</span>
                            </div>
                            <div class="ms-4">
                                ${users.map(function(user) {
                                    return `<div class="mb-1">👤 ${escapeHtml(user)}</div>`;
                                }).join('')}
                            </div>
                        </div>
                    `;
                });
                
                if (Object.keys(choicesMap).length === 0) {
                    resultsHtml += '<p class="text-muted">Aucune réponse pour cette question</p>';
                }
                
                resultsHtml += `
                        </div>
                    </div>
                `;
            });
        }
    }
    
    resultsHtml += `
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = resultsHtml;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Fonction pour supprimer un formulaire
window.deleteEventForm = function(formId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce formulaire ?')) {
        return;
    }
    
    fetch(`/api/forms/${formId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Formulaire supprimé avec succès');
            // Recharger les formulaires
            if (currentEventId) {
                loadEventForms(currentEventId);
            }
        } else {
            alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    });
};

// Fonction pour ouvrir le constructeur de formulaire
window.openEventFormBuilder = function() {
    if (!currentEventId || currentEventId === "null") {
        alert('Veuillez d\'abord sélectionner un événement');
        return;
    }
    
    // Créer ou récupérer le modal de création de formulaire (identique à groups-topics.js)
    let modal = document.getElementById('event-form-builder-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'event-form-builder-modal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        document.body.appendChild(modal);
    }
    
    let builderHtml = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📊 Créer un formulaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="event-form-builder-form">
                        <input type="hidden" id="builder-event-id" value="${currentEventId}">
                        <div class="mb-3">
                            <label for="event-form-title" class="form-label">Titre du formulaire <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="event-form-title" required placeholder="Ex: Questionnaire de satisfaction">
                        </div>
                        <div class="mb-3">
                            <label for="event-form-description" class="form-label">Description</label>
                            <textarea class="form-control" id="event-form-description" rows="2" placeholder="Description du formulaire (optionnel)"></textarea>
                        </div>
                        <div id="event-form-questions-container">
                            <h6 class="mb-3">Questions</h6>
                            <div id="event-form-questions-list"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addEventFormQuestion()">
                                <i class="fas fa-plus"></i> Ajouter une question
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitEventFormBuilder()">Créer le formulaire</button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = builderHtml;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Réinitialiser la liste des questions
    window.eventFormBuilderQuestions = [];
};

// Variable pour stocker les questions du formulaire en cours de création
window.eventFormBuilderQuestions = [];

// Fonction pour ajouter une question au formulaire (identique à groups-topics.js)
window.addEventFormQuestion = function() {
    const questionId = 'question_' + Date.now();
    const questionIndex = window.eventFormBuilderQuestions.length;
    
    window.eventFormBuilderQuestions.push({
        id: questionId,
        text: '',
        type: 'text',
        options: [],
        required: false
    });
    
    const questionsList = document.getElementById('event-form-questions-list');
    if (!questionsList) return;
    
    const questionHtml = `
        <div class="card mb-3 question-item" data-question-id="${questionId}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Question ${questionIndex + 1}</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeEventFormQuestion('${questionId}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="mb-2">
                    <label class="form-label">Texte de la question <span class="text-danger">*</span></label>
                    <input type="text" class="form-control question-text" data-question-id="${questionId}" 
                           placeholder="Ex: Quel est votre niveau de satisfaction ?" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Type de question</label>
                    <select class="form-select question-type" data-question-id="${questionId}" onchange="updateEventQuestionType('${questionId}')">
                        <option value="text">Texte libre</option>
                        <option value="radio">Choix unique (radio)</option>
                        <option value="checkbox">Choix multiple (checkbox)</option>
                    </select>
                </div>
                <div class="question-options-container" data-question-id="${questionId}" style="display: none;">
                    <label class="form-label">Options</label>
                    <div class="question-options-list" data-question-id="${questionId}">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control option-input" placeholder="Option 1">
                            <button type="button" class="btn btn-outline-danger" onclick="removeEventOption(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control option-input" placeholder="Option 2">
                            <button type="button" class="btn btn-outline-danger" onclick="removeEventOption(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEventOption('${questionId}')">
                        <i class="fas fa-plus"></i> Ajouter une option
                    </button>
                </div>
                <div class="form-check">
                    <input class="form-check-input question-required" type="checkbox" data-question-id="${questionId}" id="required_${questionId}">
                    <label class="form-check-label" for="required_${questionId}">
                        Question obligatoire
                    </label>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = questionHtml;
    questionsList.appendChild(tempDiv.firstChild);
};

// Fonction pour mettre à jour le type de question
window.updateEventQuestionType = function(questionId) {
    const question = window.eventFormBuilderQuestions.find(q => q.id === questionId);
    if (!question) return;
    
    const select = document.querySelector(`.question-type[data-question-id="${questionId}"]`);
    const optionsContainer = document.querySelector(`.question-options-container[data-question-id="${questionId}"]`);
    
    if (select && optionsContainer) {
        question.type = select.value;
        if (question.type === 'radio' || question.type === 'checkbox') {
            optionsContainer.style.display = 'block';
            if (question.options.length === 0) {
                question.options = ['', ''];
                updateEventOptionsDisplay(questionId);
            }
        } else {
            optionsContainer.style.display = 'none';
            question.options = [];
        }
    }
};

// Fonction pour ajouter une option
window.addEventOption = function(questionId) {
    const question = window.eventFormBuilderQuestions.find(q => q.id === questionId);
    if (!question) return;
    
    question.options.push('');
    updateEventOptionsDisplay(questionId);
};

// Fonction pour supprimer une option
window.removeEventOption = function(button) {
    const inputGroup = button.closest('.input-group');
    const questionId = inputGroup.closest('.question-item').getAttribute('data-question-id');
    const question = window.eventFormBuilderQuestions.find(q => q.id === questionId);
    
    if (question && question.options.length > 1) {
        const index = Array.from(inputGroup.parentElement.children).indexOf(inputGroup);
        question.options.splice(index, 1);
        inputGroup.remove();
    }
};

// Fonction pour mettre à jour l'affichage des options
function updateEventOptionsDisplay(questionId) {
    const question = window.eventFormBuilderQuestions.find(q => q.id === questionId);
    if (!question) return;
    
    const optionsList = document.querySelector(`.question-options-list[data-question-id="${questionId}"]`);
    if (!optionsList) return;
    
    optionsList.innerHTML = '';
    question.options.forEach(function(option, index) {
        const optionHtml = `
            <div class="input-group mb-2">
                <input type="text" class="form-control option-input" placeholder="Option ${index + 1}" value="${escapeHtml(option)}">
                <button type="button" class="btn btn-outline-danger" onclick="removeEventOption(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = optionHtml;
        optionsList.appendChild(tempDiv.firstChild);
    });
}

// Fonction pour supprimer une question
window.removeEventFormQuestion = function(questionId) {
    window.eventFormBuilderQuestions = window.eventFormBuilderQuestions.filter(q => q.id !== questionId);
    const questionElement = document.querySelector(`.question-item[data-question-id="${questionId}"]`);
    if (questionElement) {
        questionElement.remove();
    }
};

// Fonction pour soumettre le formulaire créé
window.submitEventFormBuilder = function() {
    const eventId = document.getElementById('builder-event-id')?.value;
    const title = document.getElementById('event-form-title')?.value;
    const description = document.getElementById('event-form-description')?.value || '';
    
    if (!title || !title.trim()) {
        alert('Le titre du formulaire est requis');
        return;
    }
    
    // Collecter les questions
    const questions = [];
    window.eventFormBuilderQuestions.forEach(function(questionData) {
        const questionElement = document.querySelector(`.question-item[data-question-id="${questionData.id}"]`);
        if (!questionElement) return;
        
        const textInput = questionElement.querySelector('.question-text');
        const typeSelect = questionElement.querySelector('.question-type');
        const requiredCheckbox = questionElement.querySelector('.question-required');
        
        if (!textInput || !textInput.value.trim()) {
            alert('Toutes les questions doivent avoir un texte');
            return;
        }
        
        const question = {
            text: textInput.value.trim(),
            type: typeSelect ? typeSelect.value : 'text',
            required: requiredCheckbox ? requiredCheckbox.checked : false
        };
        
        if (question.type === 'radio' || question.type === 'checkbox') {
            const optionInputs = questionElement.querySelectorAll('.option-input');
            question.options = Array.from(optionInputs).map(input => input.value.trim()).filter(opt => opt);
            if (question.options.length < 2) {
                alert('Les questions de type radio ou checkbox doivent avoir au moins 2 options');
                return;
            }
        }
        
        questions.push(question);
    });
    
    if (questions.length === 0) {
        alert('Le formulaire doit contenir au moins une question');
        return;
    }
    
    // Créer le formulaire
    const formData = {
        title: title.trim(),
        description: description.trim(),
        questions: questions,
        eventId: eventId
    };
    
    fetch('/api/forms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Formulaire créé avec succès');
            bootstrap.Modal.getInstance(document.getElementById('event-form-builder-modal')).hide();
            // Recharger les formulaires
            if (eventId) {
                loadEventForms(eventId);
            }
        } else {
            alert('Erreur lors de la création: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création du formulaire');
    });
};

// Modifier loadEventMessages pour appeler loadEventForms après le chargement des messages
const originalLoadEventMessages = loadEventMessages;
window.loadEventMessages = function(eventId) {
    originalLoadEventMessages(eventId);
    // Charger les formulaires après les messages
    if (eventId) {
        setTimeout(() => {
            loadEventForms(eventId);
        }, 100);
    }
};

// Modifier updateEventSelection pour charger les formulaires lors du changement d'événement
const originalUpdateEventSelection = updateEventSelection;
window.updateEventSelection = function(selectedItem) {
    originalUpdateEventSelection(selectedItem);
    // Charger les formulaires du nouvel événement
    if (currentEventId) {
        setTimeout(() => {
            loadEventForms(currentEventId);
        }, 100);
    }
};
