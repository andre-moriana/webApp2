let currentGroupId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire de clic sur un groupe
    document.querySelectorAll('.group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const groupId = this.dataset.groupId;
            selectGroup(groupId);
        });
    });

    // Gestionnaire d'envoi de message
    document.getElementById('message-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!currentGroupId) return;

        const input = document.getElementById('message-input');
        const message = input.value.trim();
        if (message) {
            sendMessage(currentGroupId, message);
            input.value = '';
        }
    });

    // Gestionnaires des boutons d'action
    document.getElementById('btn-edit-group').addEventListener('click', function() {
        if (currentGroupId) {
            window.location.href = `/groups/${currentGroupId}/edit`;
        }
    });

    document.getElementById('btn-delete-group').addEventListener('click', function() {
        if (currentGroupId) {
            confirmDelete(currentGroupId);
        }
    });
});

function selectGroup(groupId) {
    currentGroupId = groupId;
    
    // Mise à jour de l'UI
    document.querySelectorAll('.group-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.groupId === groupId) {
            item.classList.add('active');
        }
    });

    // Afficher le conteneur de chat
    document.getElementById('chat-container').classList.remove('d-none');
    document.getElementById('no-chat-selected').classList.add('d-none');

    // Charger les messages
    loadMessages(groupId);

    // Mettre à jour le titre
    const groupName = document.querySelector(`.group-item[data-group-id="${groupId}"] h6`).textContent;
    document.getElementById('chat-title').textContent = groupName;
}

function loadMessages(groupId) {
    const container = document.getElementById('messages-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    // Utiliser notre API locale
    fetch(`/api/messages/${groupId}/history`)
    .then(response => {
        console.log('Réponse du serveur:', response);
        return response.json();
    })
    .then(response => {
        console.log('Messages reçus:', response);
        if (response.success && Array.isArray(response.data)) {
            displayMessages(response.data);
        } else {
            container.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des messages</div>';
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des messages:', error);
        container.innerHTML = '<div class="alert alert-danger">Erreur de connexion</div>';
    });
}

function displayMessages(messages) {
    const container = document.getElementById('messages-container');
    const currentUserId = document.getElementById('current-user-id').value;
    
    if (!Array.isArray(messages) || messages.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">Aucun message</div>';
        return;
    }

    const html = messages.map(message => {
        const isCurrentUser = message.author._id === parseInt(currentUserId);
        const messageClass = isCurrentUser ? 'message-sent' : 'message-received';
        const alignClass = isCurrentUser ? 'justify-content-end' : 'justify-content-start';
        
        // Construire l'URL complète pour l'image
        let attachmentHtml = '';
        if (message.attachment) {
            const attachmentUrl = `/api/messages/${message._id}/attachment`;
            attachmentHtml = `
                <div class="message-attachment mt-2">
                    <a href="${attachmentUrl}" target="_blank">
                        <img src="${attachmentUrl}" alt="${message.attachment.originalName}" 
                             class="img-fluid rounded" style="max-width: 200px; max-height: 200px;">
                    </a>
                </div>
            `;
        }

        // Construire le message
        return `
            <div class="d-flex ${alignClass} mb-3">
                <div class="message ${messageClass}">
                    <div class="message-author">${message.author.name}</div>
                    ${message.content ? `<div class="message-content">${message.content}</div>` : ''}
                    ${attachmentHtml}
                    <div class="message-time">${new Date(message.createdAt).toLocaleString()}</div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

function sendMessage(groupId, content) {
    // Utiliser notre API locale
    const formData = new FormData();
    formData.append('content', content);

    // Récupérer le fichier s'il y en a un
    const fileInput = document.getElementById('message-attachment');
    if (fileInput && fileInput.files.length > 0) {
        formData.append('attachment', fileInput.files[0]);
    }

    fetch(`/api/messages/${groupId}/send`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Réponse envoi message:', response);
        return response.json();
    })
    .then(response => {
        console.log('Données message envoyé:', response);
        if (response.success) {
            loadMessages(groupId); // Recharger les messages après l'envoi
            // Vider le champ de message et le champ de fichier
            document.getElementById('message-input').value = '';
            if (fileInput) {
                fileInput.value = '';
            }
        } else {
            console.error('Erreur lors de l\'envoi du message:', response.message);
            alert('Erreur lors de l\'envoi du message: ' + response.message);
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'envoi du message:', error);
        alert('Erreur de connexion lors de l\'envoi du message');
    });
}

function confirmDelete(groupId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce groupe ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/groups/' + groupId + '/delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'confirm';
        input.value = 'yes';
        form.appendChild(input);
        
        document.body.appendChild(form);
        form.submit();
    }
} 