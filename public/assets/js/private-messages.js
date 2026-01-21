/**
 * Gestion des messages privés
 */

// Variables globales
let currentConversationUserId = null;
let currentConversationUserName = null;
let messagePollingInterval = null;
let selectedAttachment = null;

// Au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation de la page des messages privés');
    
    // Vérifier si on arrive avec un utilisateur pré-sélectionné (depuis la liste des utilisateurs)
    const urlParams = new URLSearchParams(window.location.search);
    const preSelectedUserId = urlParams.get('user');
    const preSelectedUserName = urlParams.get('name');
    
    console.log('Paramètres URL:', { userId: preSelectedUserId, userName: preSelectedUserName });
    
    if (preSelectedUserId) {
        // Décoder le nom si présent, sinon utiliser "Utilisateur"
        const userName = preSelectedUserName ? decodeURIComponent(preSelectedUserName) : 'Utilisateur';
        console.log('Ouverture automatique de la conversation avec:', userName, '(ID:', preSelectedUserId, ')');
        
        // Vérifier que les éléments DOM nécessaires existent
        const messagesContainer = document.getElementById('messages-container');
        const currentUserNameElement = document.getElementById('current-user-name');
        const recipientIdInput = document.getElementById('recipient-id');
        
        if (messagesContainer && currentUserNameElement && recipientIdInput) {
            // Ouvrir la conversation immédiatement (on est déjà dans DOMContentLoaded)
            openConversation(preSelectedUserId, userName);
            
            // Nettoyer l'URL pour éviter de réouvrir la conversation en rafraîchissant
            if (window.history.replaceState) {
                window.history.replaceState({}, document.title, '/private-messages');
            }
        } else {
            console.error('Éléments DOM manquants pour ouvrir la conversation');
        }
    }
    
    // Gestion de la recherche d'utilisateurs dans la modal
    const userSearch = document.getElementById('user-search');
    if (userSearch) {
        userSearch.addEventListener('input', function() {
            filterUsers(this.value);
        });
    }
    
    // Gestion du clic sur une conversation existante
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            openConversation(userId, userName);
        });
    });
    
    // Gestion du clic sur un utilisateur dans la modal
    document.querySelectorAll('.user-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            
            console.log('Click sur user-item:', {
                userId: userId,
                userName: userName,
                dataset: this.dataset
            });
            
            // Vérifier que les données sont présentes
            if (!userId || userId === '' || userId === 'undefined') {
                console.error('user-item sans userId valide!', this);
                showError('Erreur: Utilisateur invalide');
                return;
            }
            
            // Fermer la modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newConversationModal'));
            if (modal) {
                modal.hide();
            }
            
            // Ouvrir la conversation
            openConversation(userId, userName);
        });
    });
    
    // Gestion de l'envoi de message
    const sendMessageForm = document.getElementById('send-message-form');
    if (sendMessageForm) {
        sendMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    // Gestion de la pièce jointe
    const attachmentInput = document.getElementById('message-attachment');
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                selectedAttachment = this.files[0];
                showAttachmentPreview(this.files[0].name);
            }
        });
    }
    
    // Gestion de la suppression de la pièce jointe
    const removeAttachmentBtn = document.getElementById('remove-attachment');
    if (removeAttachmentBtn) {
        removeAttachmentBtn.addEventListener('click', function() {
            removeAttachment();
        });
    }
    
    // Permettre l'envoi avec Ctrl+Enter
    const messageContent = document.getElementById('message-content');
    if (messageContent) {
        messageContent.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Gestion du bouton de suppression de conversation
    const deleteConversationBtn = document.getElementById('delete-conversation-btn');
    if (deleteConversationBtn) {
        deleteConversationBtn.addEventListener('click', function() {
            deleteConversation();
        });
    }
});

/**
 * Filtre la liste des utilisateurs dans la modal
 */
function filterUsers(searchTerm) {
    const userItems = document.querySelectorAll('.user-item');
    const term = searchTerm.toLowerCase();
    
    userItems.forEach(item => {
        const userName = item.dataset.userName.toLowerCase();
        if (userName.includes(term)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Ouvre une conversation avec un utilisateur
 */
function openConversation(userId, userName) {
    console.log(`=== OUVERTURE CONVERSATION ===`);
    console.log('User ID:', userId);
    console.log('User Name:', userName);
    
    // Vérifier que l'userId n'est pas vide
    if (!userId || userId === '' || userId === 'undefined') {
        console.error('ID utilisateur invalide:', userId);
        showError('Erreur: ID utilisateur invalide');
        return;
    }
    
    currentConversationUserId = userId;
    currentConversationUserName = userName || 'Utilisateur';
    
    console.log('Mise à jour de l\'en-tête avec:', currentConversationUserName);
    
    // Mettre à jour l'en-tête
    const userNameElement = document.getElementById('current-user-name');
    if (userNameElement) {
        userNameElement.textContent = currentConversationUserName;
        console.log('En-tête mis à jour');
    } else {
        console.error('Élément current-user-name introuvable');
    }
    
    // Afficher le formulaire d'envoi
    const formContainer = document.getElementById('message-form-container');
    if (formContainer) {
        formContainer.style.display = 'block';
        console.log('Formulaire d\'envoi affiché');
    } else {
        console.error('Élément message-form-container introuvable');
    }
    
    // Afficher le bouton de suppression
    const deleteBtn = document.getElementById('delete-conversation-btn');
    if (deleteBtn) {
        deleteBtn.style.display = 'block';
    }
    
    // Définir le destinataire
    const recipientInput = document.getElementById('recipient-id');
    if (recipientInput) {
        recipientInput.value = userId;
        console.log('Destinataire défini:', userId);
    } else {
        console.error('Élément recipient-id introuvable');
    }
    
    // Marquer la conversation comme active dans la liste (si elle existe)
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeConv = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
    if (activeConv) {
        activeConv.classList.add('active');
        console.log('Conversation marquée comme active dans la liste');
    } else {
        console.log('Conversation non trouvée dans la liste (nouvelle conversation)');
    }
    
    // Charger l'historique des messages
    console.log('Chargement de l\'historique des messages...');
    loadMessages(userId);
    
    // Démarrer le polling pour les nouveaux messages
    console.log('Démarrage du polling...');
    startMessagePolling();
    
    console.log('=== FIN OUVERTURE CONVERSATION ===');
}

/**
 * Charge l'historique des messages avec un utilisateur
 */
async function loadMessages(userId) {
    // Vérifier que l'userId est valide
    if (!userId || userId === '' || userId === 'undefined') {
        console.error('loadMessages: ID utilisateur invalide:', userId);
        showError('ID utilisateur invalide');
        return;
    }
    
    console.log('loadMessages: Chargement des messages pour userId:', userId);
    
    try {
        const url = `/api/private-messages/${userId}/history`;
        console.log('loadMessages: URL de la requête:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        console.log('loadMessages: Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('loadMessages: Erreur HTTP:', response.status, errorText);
            throw new Error(`Erreur HTTP ${response.status}: ${errorText}`);
        }
        
        const response_data = await response.json();
        console.log('Messages chargés (brut):', response_data);
        console.log('Type de response_data:', typeof response_data);
        console.log('Est un array?', Array.isArray(response_data));
        
        // Gérer différents formats de réponse
        let messages = [];
        
        if (Array.isArray(response_data)) {
            // C'est déjà un tableau de messages
            messages = response_data;
        } else if (response_data && typeof response_data === 'object') {
            // C'est un objet, essayer d'extraire les messages
            if (response_data.data && Array.isArray(response_data.data)) {
                messages = response_data.data;
            } else if (response_data.error) {
                throw new Error(response_data.error);
            } else {
                console.error('Format de réponse non reconnu:', response_data);
                throw new Error('Format de réponse invalide');
            }
        }
        
        console.log('Messages après traitement:', messages);
        console.log('Nombre de messages:', messages.length);
        
        displayMessages(messages);
        
        // Marquer les messages comme lus
        markMessagesAsRead(userId);
        
    } catch (error) {
        console.error('Erreur lors du chargement des messages:', error);
        showError('Erreur lors du chargement des messages: ' + error.message);
    }
}

/**
 * Affiche les messages dans le conteneur
 */
function displayMessages(messages) {
    const container = document.getElementById('messages-container');
    
    console.log('displayMessages: messages =', messages);
    console.log('displayMessages: type =', typeof messages);
    console.log('displayMessages: is array =', Array.isArray(messages));
    
    // Vérifier que messages est bien un tableau
    if (!Array.isArray(messages)) {
        console.error('displayMessages: messages n\'est pas un tableau!', messages);
        container.innerHTML = `
            <div class="text-center text-muted mt-5">
                <i class="fas fa-exclamation-triangle fa-4x mb-3 text-warning"></i>
                <p>Erreur de format des messages</p>
                <p class="small">Les messages ne sont pas dans le bon format</p>
            </div>
        `;
        return;
    }
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted mt-5">
                <i class="fas fa-comments fa-4x mb-3"></i>
                <p>Aucun message pour le moment</p>
                <p class="small">Envoyez le premier message pour démarrer la conversation</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        container.appendChild(messageElement);
    });
    
    // Scroller vers le bas
    container.scrollTop = container.scrollHeight;
}

/**
 * Crée un élément DOM pour un message
 */
function createMessageElement(message) {
    const div = document.createElement('div');
    const isOwnMessage = message.author._id == window.currentUserId || message.author.id == window.currentUserId;
    
    div.className = `message-item mb-3 ${isOwnMessage ? 'message-own' : 'message-other'}`;
    
    const messageTime = new Date(message.createdAt || message.created_at);
    const timeStr = messageTime.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    let attachmentHtml = '';
    if (message.attachment) {
        const att = message.attachment;
        const isImage = att.mimeType && att.mimeType.startsWith('image/');
        
        if (isImage) {
            attachmentHtml = `
                <div class="message-attachment mt-2">
                    <img src="/api/messages/attachment/${encodeURIComponent(att.filename)}" 
                         alt="${att.originalName}" 
                         class="img-thumbnail" 
                         style="max-width: 300px; max-height: 300px;">
                </div>
            `;
        } else {
            attachmentHtml = `
                <div class="message-attachment mt-2">
                    <a href="/api/messages/attachment/${encodeURIComponent(att.filename)}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-paperclip me-1"></i>
                        ${att.originalName}
                    </a>
                </div>
            `;
        }
    }
    
    div.innerHTML = `
        <div class="d-flex ${isOwnMessage ? 'flex-row-reverse' : ''}">
            <div class="message-bubble ${isOwnMessage ? 'bg-success text-white' : 'bg-light'}" 
                 style="max-width: 70%; padding: 10px; border-radius: 10px;">
                ${!isOwnMessage ? `<div class="message-author fw-bold small mb-1">${message.author.name}</div>` : ''}
                <div class="message-content">${escapeHtml(message.content)}</div>
                ${attachmentHtml}
                <div class="message-time text-end small mt-1" style="opacity: 0.7;">
                    ${timeStr}
                </div>
            </div>
        </div>
    `;
    
    return div;
}

/**
 * Envoie un message
 */
async function sendMessage() {
    const content = document.getElementById('message-content').value.trim();
    const recipientId = document.getElementById('recipient-id').value;
    
    if (!content && !selectedAttachment) {
        showError('Veuillez saisir un message ou joindre un fichier');
        return;
    }
    
    if (!recipientId) {
        showError('Veuillez sélectionner un destinataire');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('content', content);
        formData.append('recipientId', recipientId);
        
        if (selectedAttachment) {
            formData.append('attachment', selectedAttachment);
        }
        
        const response = await fetch('/api/private-messages/send', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Erreur lors de l\'envoi du message');
        }
        
        const result = await response.json();
        console.log('Message envoyé:', result);
        
        // Réinitialiser le formulaire
        document.getElementById('message-content').value = '';
        removeAttachment();
        
        // Recharger les messages
        loadMessages(recipientId);
        
        // Mettre à jour la liste des conversations
        updateConversationsList();
        
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
        showError(error.message || 'Erreur lors de l\'envoi du message');
    }
}

/**
 * Marque les messages comme lus
 */
async function markMessagesAsRead(userId) {
    try {
        await fetch(`/api/private-messages/${userId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        // Mettre à jour le badge de non-lus dans la liste
        const conv = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
        if (conv) {
            const badge = conv.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
        }
    } catch (error) {
        console.error('Erreur lors du marquage des messages comme lus:', error);
    }
}

/**
 * Démarre le polling pour les nouveaux messages
 */
function startMessagePolling() {
    // Arrêter le polling existant
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    // Démarrer un nouveau polling toutes les 5 secondes
    messagePollingInterval = setInterval(() => {
        if (currentConversationUserId) {
            loadMessages(currentConversationUserId);
        }
    }, 5000);
}

/**
 * Met à jour la liste des conversations
 */
async function updateConversationsList() {
    try {
        const response = await fetch('/api/private-messages/conversations', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Erreur lors du chargement des conversations');
        }
        
        const conversations = await response.json();
        
        // Mettre à jour l'affichage de la liste
        // TODO: implémenter la mise à jour dynamique de la liste
        
    } catch (error) {
        console.error('Erreur lors de la mise à jour des conversations:', error);
    }
}

/**
 * Affiche la prévisualisation de la pièce jointe
 */
function showAttachmentPreview(fileName) {
    document.getElementById('attachment-name').textContent = fileName;
    document.getElementById('attachment-preview').style.display = 'block';
}

/**
 * Supprime la pièce jointe sélectionnée
 */
function removeAttachment() {
    selectedAttachment = null;
    document.getElementById('message-attachment').value = '';
    document.getElementById('attachment-preview').style.display = 'none';
}

/**
 * Affiche un message d'erreur
 */
function showError(message) {
    // Créer une alerte Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Échappe les caractères HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Supprime la conversation courante
 */
async function deleteConversation() {
    if (!currentConversationUserId) {
        showError('Aucune conversation sélectionnée');
        return;
    }
    
    // Demander confirmation
    if (!confirm(`Êtes-vous sûr de vouloir supprimer cette conversation avec ${currentConversationUserName} ?\n\nTous les messages seront définitivement supprimés.`)) {
        return;
    }
    
    try {
        const response = await fetch(`/api/private-messages/${currentConversationUserId}/delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Erreur lors de la suppression de la conversation');
        }
        
        // Succès - fermer la conversation
        console.log('Conversation supprimée avec succès');
        
        // Masquer le formulaire et le bouton de suppression
        document.getElementById('message-form-container').style.display = 'none';
        document.getElementById('delete-conversation-btn').style.display = 'none';
        
        // Réinitialiser l'en-tête
        document.getElementById('current-user-name').textContent = 'Sélectionnez une conversation';
        
        // Vider le conteneur de messages
        const container = document.getElementById('messages-container');
        container.innerHTML = `
            <div class="text-center text-muted mt-5">
                <i class="fas fa-comment-dots fa-4x mb-3"></i>
                <p>Sélectionnez une conversation pour commencer à échanger des messages</p>
            </div>
        `;
        
        // Supprimer la conversation de la liste
        const convItem = document.querySelector(`.conversation-item[data-user-id="${currentConversationUserId}"]`);
        if (convItem) {
            convItem.remove();
        }
        
        // Vérifier s'il reste des conversations
        const conversationsList = document.getElementById('conversations-list');
        if (!conversationsList.querySelector('.conversation-item')) {
            conversationsList.innerHTML = `
                <div class="list-group-item text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Aucune conversation</p>
                    <p class="small">Cliquez sur "Nouvelle conversation" pour commencer</p>
                </div>
            `;
        }
        
        // Arrêter le polling
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
        }
        
        // Réinitialiser les variables
        currentConversationUserId = null;
        currentConversationUserName = null;
        
        // Afficher un message de succès
        showSuccess('Conversation supprimée avec succès');
        
    } catch (error) {
        console.error('Erreur lors de la suppression de la conversation:', error);
        showError(error.message || 'Erreur lors de la suppression de la conversation');
    }
}

/**
 * Affiche un message de succès
 */
function showSuccess(message) {
    // Créer une alerte Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Arrêter le polling quand on quitte la page
window.addEventListener('beforeunload', function() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
});
