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
        console.log("Chat initialisé avec l'événement ID:", currentEventId);
        
        // Charger automatiquement les messages de l'événement initial
        loadEventMessages(currentEventId);
    } else {
        console.warn("Aucun événement initial trouvé");
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
    messageDiv.setAttribute('data-message-id', message.id);
    
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
        
        // Construire l'URL complète pour l'image
        const fullUrl = attachmentUrl.startsWith('http') ? attachmentUrl : `${backendUrl}${attachmentUrl}`;
        
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
            <button type="button" class="btn btn-edit" onclick="editMessage(${message.id})" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-delete" onclick="deleteMessage(${message.id})" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        `;
        messageContent.appendChild(actionsDiv);
    }
    
    messageDiv.appendChild(messageContent);
    
    console.log("Élément de message créé:", messageDiv);
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
    
    // Vider le container
    messagesContainer.innerHTML = '';
    
    if (!messages || messages.length === 0) {
        console.log("Aucun message, affichage du message par défaut");
        const noMessagesDiv = document.createElement('div');
        noMessagesDiv.className = 'text-center text-muted';
        noMessagesDiv.innerHTML = `
            <i class="fas fa-comments fa-2x mb-2"></i>
            <p>Aucun message dans le chat</p>
        `;
        messagesContainer.appendChild(noMessagesDiv);
        return;
    }
    
    console.log("Affichage de", messages.length, "messages");
    
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

// Fonction pour vérifier l'état d'inscription d'un événement SANS s'inscrire automatiquement
async function checkEventRegistrationStatus(eventId) {
    try {
        // Utiliser l'endpoint GET pour vérifier l'état sans s'inscrire
        const response = await fetch(`${backendUrl}/api/events/${eventId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('Event data:', data);
            
            // Vérifier si l'utilisateur actuel est dans la liste des membres
            const isRegistered = data.members && data.members.some(member => 
                member._id == currentUserId || member.id == currentUserId
            );
            
            console.log('Is registered:', isRegistered);
            updateRegistrationButton(eventId, isRegistered);
            
            // Mettre à jour le nombre d'inscrits
            if (data.members && Array.isArray(data.members)) {
                updateMembersCount(eventId, data.members.length);
            }
            
            return isRegistered;
        } else {
            console.error('Erreur lors de la vérification de l\'événement:', response.status);
            updateRegistrationButton(eventId, false);
            return false;
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'inscription:', error);
        updateRegistrationButton(eventId, false);
        return false;
    }
}

// Fonction pour s'inscrire à un événement (utilise l'endpoint /join)
window.registerToEvent = async function(eventId) {
    console.log('=== REGISTER TO EVENT ===');
    console.log('Event ID:', eventId);
    
    if (!eventId) {
        console.error('Event ID manquant');
        return;
    }
    
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/join`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Inscription réussie:', data);
            updateRegistrationButton(eventId, true);
            
            // Mettre à jour le nombre d'inscrits
            if (data.members && Array.isArray(data.members)) {
                updateMembersCount(eventId, data.members.length);
            }
        } else if (response.status === 400 && data.error && data.error.includes('déjà inscrit')) {
            // L'utilisateur est déjà inscrit, mettre à jour l'interface
            console.log('Utilisateur déjà inscrit, mise à jour de l\'interface');
            updateRegistrationButton(eventId, true);
        } else {
            console.error('Erreur inscription:', data);
        }
    } catch (error) {
        console.error('Erreur lors de l\'inscription:', error);
    }
};

// Fonction pour se désinscrire d'un événement (utilise l'endpoint /leave)
window.unregisterFromEvent = async function(eventId) {
    console.log('=== UNREGISTER FROM EVENT ===');
    console.log('Event ID:', eventId);
    
    if (!eventId) {
        console.error('Event ID manquant');
        return;
    }
    
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/leave`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Désinscription réussie:', data);
            updateRegistrationButton(eventId, false);
            
            // Mettre à jour le nombre d'inscrits
            if (data.members && Array.isArray(data.members)) {
                updateMembersCount(eventId, data.members.length);
            }
        } else if (response.status === 400 && data.error && data.error.includes('pas inscrit')) {
            // L'utilisateur n'est pas inscrit, mettre à jour l'interface
            console.log('Utilisateur pas inscrit, mise à jour de l\'interface');
            updateRegistrationButton(eventId, false);
        } else {
            console.error('Erreur désinscription:', data);
        }
    } catch (error) {
        console.error('Erreur lors de la désinscription:', error);
    }
};

// Fonction pour mettre à jour le bouton d'inscription
function updateRegistrationButton(eventId, isRegistered) {
    const registerBtn = document.getElementById('register-btn');
    const unregisterBtn = document.getElementById('unregister-btn');
    const statusDiv = document.getElementById('registration-status');
    
    console.log('=== UPDATE REGISTRATION BUTTON ===');
    console.log('Event ID:', eventId);
    console.log('Is Registered:', isRegistered);
    console.log('Register button found:', registerBtn);
    console.log('Unregister button found:', unregisterBtn);
    
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
    } else {
        console.log('Boutons d\'inscription non trouvés - probablement dans la page de liste');
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
    
    // Mettre à jour l'ID de l'événement actuel
    const eventId = selectedItem.getAttribute("data-event-id");
    currentEventId = eventId;
    console.log("Event ID mis à jour:", currentEventId);
    
    // Mettre à jour le titre du chat
    const eventTitle = selectedItem.querySelector(".event-title")?.textContent || "Événement";
    if (chatTitle) {
        chatTitle.textContent = eventTitle;
        console.log("Titre du chat mis à jour:", eventTitle);
    }
    
    // Mettre à jour l'input caché pour l'envoi de messages
    const eventIdInput = document.getElementById("current-event-id");
    if (eventIdInput) {
        eventIdInput.value = eventId;
        console.log("Input current-event-id mis à jour:", eventId);
    }
    
    // Mettre à jour le lien "Voir détails"
    const viewDetailsBtn = document.getElementById("view-details-btn");
    if (viewDetailsBtn && eventId) {
        viewDetailsBtn.href = `/events/${eventId}`;
        console.log("Lien 'Voir détails' mis à jour:", viewDetailsBtn.href);
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
    console.log('=== DOM LOADED ===');
    console.log('Initial Event ID:', initialEventId);
    console.log('Current User ID:', currentUserId);
    
    // Initialiser le chat avec l'événement par défaut
    if (initialEventId && initialEventId !== "null") {
        currentEventId = initialEventId;
        console.log('Chat initialisé avec l\'événement ID:', currentEventId);
        loadEventMessages(currentEventId);
        
        // Vérifier l'état d'inscription SANS s'inscrire automatiquement
        checkEventRegistrationStatus(currentEventId);
        
        // Mettre à jour le lien "Voir détails" pour l'événement initial
        const viewDetailsBtn = document.getElementById("view-details-btn");
        if (viewDetailsBtn) {
            viewDetailsBtn.href = `/events/${currentEventId}`;
            console.log("Lien 'Voir détails' initial mis à jour:", viewDetailsBtn.href);
        }
    }
    
    // Les event listeners sont déjà configurés dans le code existant
});

// Test ultra-simple
async function testPing(eventId) {
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/ping`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Ping response:', data);
        return data;
    } catch (error) {
        console.error('Ping error:', error);
        return { error: error.message };
    }
}

// Modifier la fonction de test pour commencer par le ping
async function testRegistration(eventId) {
    console.log('=== TEST REGISTRATION DEBUG ===');
    
    // Test 1: Ping simple
    console.log('Test 1: Ping simple');
    const pingResult = await testPing(eventId);
    if (pingResult.error) {
        console.error('Ping failed:', pingResult.error);
        return pingResult;
    }
    
    // Test 2: Test complet
    console.log('Test 2: Test complet');
    try {
        const response = await fetch(`${backendUrl}/api/events/${eventId}/test-register`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('Test registration response:', data);
        return data;
    } catch (error) {
        console.error('Erreur lors du test:', error);
        return { error: error.message };
    }
}
