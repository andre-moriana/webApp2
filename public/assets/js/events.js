// JavaScript spécifique aux événements

// Fonction de confirmation de suppression
function confirmDelete(eventId, eventName) {
    document.getElementById("deleteEventId").value = eventId;
    document.getElementById("eventName").textContent = eventName;
    
    // Définir l'action du formulaire avec l'ID de l'événement
    const form = document.getElementById("deleteForm");
    form.action = "/events/" + eventId;
    
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}

// Gestion des événements de chat
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le chat si un événement est sélectionné
    if (typeof initialEventId !== 'undefined' && initialEventId !== null) {
        initializeEventChat();
    }
    
    // Gestion des clics sur les événements
    const eventItems = document.querySelectorAll('.event-item');
    eventItems.forEach(item => {
        item.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            if (eventId && eventId !== 'null') {
                selectEvent(eventId);
            }
        });
    });
});

// Sélectionner un événement
function selectEvent(eventId) {
    // Retirer la classe active de tous les événements
    document.querySelectorAll('.event-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Ajouter la classe active à l'événement sélectionné
    const selectedEvent = document.querySelector([data-event-id=""]);
    if (selectedEvent) {
        selectedEvent.classList.add('active');
        
        // Mettre à jour le chat
        loadEventChat(eventId);
    }
}

// Charger le chat d'un événement
function loadEventChat(eventId) {
    // Mettre à jour l'ID de l'événement courant
    document.getElementById('current-event-id').value = eventId;
    
    // Mettre à jour le titre du chat
    const selectedEvent = document.querySelector([data-event-id=""]);
    if (selectedEvent) {
        const eventName = selectedEvent.querySelector('h6').textContent;
        document.getElementById('chat-title').textContent = eventName;
    }
    
    // Charger les messages
    loadMessages(eventId);
}

// Charger les messages d'un événement
async function loadMessages(eventId) {
    try {
        const response = await fetch(${backendUrl}/api/events//messages, {
            method: 'GET',
            headers: {
                'Authorization': Bearer ,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                displayMessages(data.data);
            } else {
                displayMessages([]);
            }
        } else {
            console.error('Erreur lors du chargement des messages');
            displayMessages([]);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des messages:', error);
        displayMessages([]);
    }
}

// Afficher les messages
function displayMessages(messages) {
    const container = document.getElementById('messages-container');
    container.innerHTML = '';
    
    if (messages.length === 0) {
        container.innerHTML = 
            <div class="text-center text-muted">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <p>Aucun message dans le chat</p>
            </div>
        ;
        return;
    }
    
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        container.appendChild(messageElement);
    });
    
    // Scroll vers le bas
    container.scrollTop = container.scrollHeight;
}

// Créer un élément de message
function createMessageElement(message) {
    const authorId = message.author_id || message.userId || message.user_id || 
                     (message.author ? (message.author.id || message.author._id) : null);
    const isOwnMessage = authorId === currentUserId;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = message-item ;
    
    const canEdit = (currentUserId === authorId) || isAdmin;
    const canDelete = isAdmin || (currentUserId === authorId && 
                     (Date.now() - new Date(message.created_at).getTime()) < 3600000);
    
    const editButton = canEdit ? 
        <button class="btn btn-sm btn-outline-primary" onclick="editMessage('')" title="Modifier">
            <i class="fas fa-edit"></i>
        </button>
     : '';
    
    const deleteButton = canDelete ? 
        <button class="btn btn-sm btn-outline-danger" onclick="deleteMessage('')" title="Supprimer">
            <i class="fas fa-trash"></i>
        </button>
     : '';
    
    messageDiv.innerHTML = 
        <div class="message-header">
            <span class="message-author"></span>
            <span class="message-time"></span>
        </div>
        <div class="message-content"></div>
        <div class="message-actions">
            
            
        </div>
    ;
    
    return messageDiv;
}

// Formater une date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // Moins d'une minute
        return 'À l\'instant';
    } else if (diff < 3600000) { // Moins d'une heure
        return ${Math.floor(diff / 60000)} min;
    } else if (diff < 86400000) { // Moins d'un jour
        return ${Math.floor(diff / 3600000)} h;
    } else {
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Initialiser le chat d'événement
function initializeEventChat() {
    const messageForm = document.getElementById('message-form');
    if (messageForm) {
        messageForm.addEventListener('submit', handleMessageSubmit);
    }
    
    // Gestion des pièces jointes
    const attachmentInput = document.getElementById('message-attachment');
    if (attachmentInput) {
        attachmentInput.addEventListener('change', handleAttachmentChange);
    }
}

// Gérer l'envoi de message
async function handleMessageSubmit(e) {
    e.preventDefault();
    
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    const eventId = document.getElementById('current-event-id').value;
    
    if (!message || !eventId) return;
    
    try {
        const response = await fetch(${backendUrl}/api/events//messages, {
            method: 'POST',
            headers: {
                'Authorization': Bearer ,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                content: message
            })
        });
        
        if (response.ok) {
            messageInput.value = '';
            // Recharger les messages
            loadMessages(eventId);
        } else {
            console.error('Erreur lors de l\'envoi du message');
        }
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
    }
}

// Gérer le changement de pièce jointe
function handleAttachmentChange(e) {
    const file = e.target.files[0];
    if (file) {
        // Ici vous pouvez ajouter la logique pour uploader le fichier
        console.log('Fichier sélectionné:', file.name);
    }
}

// Modifier un message
function editMessage(messageId) {
    // Implémentation de l'édition de message
    console.log('Modifier le message:', messageId);
}

// Supprimer un message
async function deleteMessage(messageId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        return;
    }
    
    try {
        const response = await fetch(${backendUrl}/api/messages/, {
            method: 'DELETE',
            headers: {
                'Authorization': Bearer ,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            // Recharger les messages
            const eventId = document.getElementById('current-event-id').value;
            loadMessages(eventId);
        } else {
            console.error('Erreur lors de la suppression du message');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression du message:', error);
    }
}
