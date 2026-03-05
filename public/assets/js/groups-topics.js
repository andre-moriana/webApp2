/**
 * Gestion des groupes et sujets de discussion
 */

document.addEventListener('DOMContentLoaded', function() {
    //console.log('Script de gestion des groupes chargé');
    
    // Gérer les clics sur les groupes
    document.querySelectorAll('.group-item').forEach(function(groupItem) {
        groupItem.addEventListener('click', function(e) {
            // Ignorer si on clique sur un lien ou bouton
            if (e.target.closest('a, button')) {
                return;
            }
            
            const groupId = this.getAttribute('data-group-id');
            
            // Mettre à jour l'état actif
            document.querySelectorAll('.group-item').forEach(function(item) {
                item.classList.remove('active');
            });
            this.classList.add('active');
            
            // Mettre à jour les sujets affichés
            updateTopicsDisplay(groupId);
        });
    });
    
    function updateTopicsDisplay(groupId) {
        const topics = window.groupTopics[groupId] || [];
        const topicsContainer = document.getElementById('topics-container');
        const topicsTitle = document.getElementById('topics-title');
        
        if (!topicsContainer) return;
        
        // Fermer le chat si ouvert
        closeTopicChat();
        
        // Mettre à jour le titre
        const groupName = document.querySelector(`[data-group-id="${groupId}"] h6`).textContent.trim();
        if (topicsTitle) {
            topicsTitle.textContent = groupName;
        }
        
        // Mettre à jour le bouton "Nouveau sujet"
        const createButton = topicsContainer.querySelector('.card-header a');
        if (createButton) {
            createButton.href = `/groups/${groupId}/topics/create`;
        }
        
        // Mettre à jour le contenu
        const cardBody = topicsContainer.querySelector('.card-body');
        if (!cardBody) return;
        
        if (topics.length === 0) {
            cardBody.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Aucun sujet de discussion</p>
                    <a href="/groups/${groupId}/topics/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Créer le premier sujet
                    </a>
                </div>
            `;
        } else {
            let topicsHtml = '<div class="list-group" id="topics-list">';
            topics.forEach(function(topic) {
                const description = topic.description ? 
                    (topic.description.length > 100 ? topic.description.substring(0, 100) + '...' : topic.description) : 
                    '';
                const unreadBadge = topic.unreadCount && topic.unreadCount > 0 ? 
                    `<span class="badge bg-danger ms-2">${topic.unreadCount}</span>` : '';
                const createdDate = topic.created_at ? 
                    new Date(topic.created_at).toLocaleDateString('fr-FR') : '';
                
                topicsHtml += `
                    <div class="list-group-item list-group-item-action topic-item" 
                         data-topic-id="${topic.id}"
                         data-topic-title="${escapeHtml(topic.title || 'Sans titre')}"
                         data-topic-description="${escapeHtml(topic.description || '')}"
                         style="cursor: pointer;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    ${escapeHtml(topic.title || 'Sans titre')}
                                    ${unreadBadge}
                                </h6>
                                ${description ? `<p class="mb-1 text-muted small">${escapeHtml(description)}</p>` : ''}
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Créé par ${escapeHtml(topic.created_by_name || 'Anonyme')}
                                    ${createdDate ? `<span class="ms-2"><i class="fas fa-calendar me-1"></i>${createdDate}</span>` : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });
            topicsHtml += '</div>';
            cardBody.innerHTML = topicsHtml;
            
            // Attacher les gestionnaires de clic aux sujets
            attachTopicClickHandlers();
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Attacher les gestionnaires de clic aux sujets
    function attachTopicClickHandlers() {
        document.querySelectorAll('.topic-item').forEach(function(topicItem) {
            topicItem.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const topicId = this.getAttribute('data-topic-id');
                const topicTitle = this.getAttribute('data-topic-title');
                const topicDescription = this.getAttribute('data-topic-description');
                
                // Mettre à jour l'état actif
                document.querySelectorAll('.topic-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                this.classList.add('active');
                
                // Ouvrir le chat du sujet
                openTopicChat(topicId, topicTitle, topicDescription);
            });
        });
    }
    
    // Fonction pour ouvrir le chat d'un sujet
    function openTopicChat(topicId, topicTitle, topicDescription) {
        const chatContainer = document.getElementById('topic-chat-container');
        const chatTitle = document.getElementById('chat-topic-title');
        const topicIdInput = document.getElementById('current-topic-id-input');
        
        if (!chatContainer || !chatTitle || !topicIdInput) return;
        
        // Mettre à jour le titre
        chatTitle.textContent = topicTitle;
        topicIdInput.value = topicId;
        
        // Afficher le conteneur de chat
        chatContainer.style.display = 'block';
        
        // Charger les messages et les formulaires
        loadTopicMessages(topicId);
        loadTopicForms(topicId);
        
        // Faire défiler vers le chat
        chatContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    // Fonction pour fermer le chat (accessible globalement)
    window.closeTopicChat = function() {
        const chatContainer = document.getElementById('topic-chat-container');
        if (chatContainer) {
            chatContainer.style.display = 'none';
        }
        
        // Retirer l'état actif des sujets
        document.querySelectorAll('.topic-item').forEach(function(item) {
            item.classList.remove('active');
        });
    };
    
    // Variable pour stocker les formulaires du sujet actuel
    let currentTopicForms = [];
    let currentTopicFormResponseCounts = {};
    
    // Fonction pour charger les formulaires d'un sujet
    function loadTopicForms(topicId) {
        //console.log('Chargement des formulaires pour le sujet:', topicId);
        
        // Réinitialiser les formulaires et compteurs avant de charger les nouveaux
        currentTopicForms = [];
        currentTopicFormResponseCounts = {};
        
        // Supprimer les formulaires existants du DOM immédiatement
        const messagesContainer = document.getElementById('topic-messages-container');
        if (messagesContainer) {
            const existingForms = messagesContainer.querySelectorAll('.form-message');
            existingForms.forEach(form => form.remove());
        }
        
        fetch(`/api/topics/${topicId}/forms`, {
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
                currentTopicForms = forms;
                //console.log('Formulaires trouvés:', currentTopicForms.length);
                // Initialiser les compteurs à 0 pour tous les formulaires
                currentTopicForms.forEach(function(form) {
                    currentTopicFormResponseCounts[form.id] = 0;
                });
                // Ajouter les formulaires aux messages affichés immédiatement
                addFormsToMessages();
                // Charger les compteurs de réponses pour chaque formulaire (asynchrone)
                loadFormResponseCounts(forms);
            } else {
                //console.log('Aucun formulaire trouvé');
                currentTopicForms = [];
                // Appeler addFormsToMessages même s'il n'y a pas de formulaires pour s'assurer que les anciens sont supprimés
                addFormsToMessages();
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des formulaires:', error);
            currentTopicForms = [];
            // Appeler addFormsToMessages même en cas d'erreur pour supprimer les anciens formulaires
            addFormsToMessages();
        });
    }
    
    // Fonction pour charger les compteurs de réponses
    function loadFormResponseCounts(forms) {
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
                    currentTopicFormResponseCounts[form.id] = data.data.length;
                } else {
                    currentTopicFormResponseCounts[form.id] = 0;
                }
                // Mettre à jour l'affichage des formulaires
                updateFormDisplay();
            })
            .catch(error => {
                console.error('Erreur lors du chargement des réponses:', error);
                currentTopicFormResponseCounts[form.id] = 0;
            });
        });
    }
    
    // Fonction pour ajouter les formulaires aux messages (en bas de la page)
    function addFormsToMessages() {
        const messagesContainer = document.getElementById('topic-messages-container');
        if (!messagesContainer) {
            console.error('Conteneur de messages non trouvé');
            return;
        }
        
        //console.log('Ajout des formulaires, nombre:', currentTopicForms.length);
        
        // Vérifier si des formulaires sont déjà affichés
        const existingForms = messagesContainer.querySelectorAll('.form-message');
        if (existingForms.length > 0) {
            // Supprimer les anciens formulaires
            existingForms.forEach(form => form.remove());
        }
        
        if (currentTopicForms.length === 0) {
            //console.log('Aucun formulaire à afficher');
            return;
        }
        
        // Ajouter les formulaires à la fin du conteneur (après les messages)
        const fragment = document.createDocumentFragment();
        currentTopicForms.forEach(function(form) {
            const responseCount = currentTopicFormResponseCounts[form.id] || 0;
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
                            <button class="btn btn-sm btn-primary" onclick="openFormModal(${form.id})">
                                📝 Répondre
                            </button>
                            <button class="btn btn-sm btn-info" onclick="viewFormResults(${form.id})">
                                📊 Résultats (${responseCount})
                            </button>
                            ${window.isAdmin ? `<button class="btn btn-sm btn-danger" onclick="deleteForm(${form.id})">
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
    function updateFormDisplay() {
        currentTopicForms.forEach(function(form) {
            const formElement = document.querySelector(`[data-form-id="${form.id}"]`);
            if (formElement) {
                const resultsButton = formElement.querySelector('.btn-info');
                if (resultsButton) {
                    const responseCount = currentTopicFormResponseCounts[form.id] || 0;
                    resultsButton.textContent = `📊 Résultats (${responseCount})`;
                }
            }
        });
    }
    
    // Fonction pour charger les messages d'un sujet (globale pour être accessible depuis les boutons)
    window.loadTopicMessages = function loadTopicMessages(topicId) {
        const messagesContainer = document.getElementById('topic-messages-container');
        if (!messagesContainer) return;
        
        // Ne pas afficher le loader si on attend juste les messages (les formulaires sont déjà chargés)
        const hasForms = messagesContainer.querySelectorAll('.form-message').length > 0;
        const hasLoader = messagesContainer.querySelector('.fa-spinner');
        const hasMessages = messagesContainer.querySelectorAll('[data-message-id]').length > 0;
        
        if (!hasForms && !hasMessages && !hasLoader) {
            // Supprimer tout sauf les formulaires
            const forms = messagesContainer.querySelectorAll('.form-message');
            messagesContainer.innerHTML = '';
            forms.forEach(form => messagesContainer.appendChild(form));
            // Ajouter le loader
            const loaderDiv = document.createElement('div');
            loaderDiv.className = 'text-center text-muted py-3';
            loaderDiv.innerHTML = '<i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Chargement des messages...</p>';
            messagesContainer.appendChild(loaderDiv);
        }
        
        // Appeler le backend de l'application web au lieu de l'API externe directement
        fetch(`/api/topics/${topicId}/messages`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            //console.log('Status HTTP:', response.status);
            //console.log('Content-Type:', response.headers.get('content-type'));
            if (!response.ok) {
                return response.text().then(text => {
                    //console.error('Réponse brute (erreur):', text.substring(0, 500));
                    throw new Error('Erreur HTTP: ' + response.status + ' - ' + text.substring(0, 100));
                });
            }
            return response.text().then(text => {
                //console.log('Réponse brute (100 premiers caractères):', text.substring(0, 100));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    //console.error('Erreur de parsing JSON:', e);
                    //console.error('Texte complet:', text);
                    throw new Error('Réponse invalide: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            //console.log('Données reçues pour le sujet:', topicId, data);
            // L'API peut retourner directement un tableau ou un objet avec data
            let messages = [];
            if (Array.isArray(data)) {
                messages = data;
            } else if (data.success && data.data && Array.isArray(data.data)) {
                messages = data.data;
            } else if (data.data && Array.isArray(data.data)) {
                messages = data.data;
            }
            
            // Trier les messages par date du plus ancien au plus récent
            if (messages.length > 0) {
                messages.sort(function(a, b) {
                    // Extraire les dates
                    const dateAStr = a.createdAt || a.created_at || a.timestamp || '';
                    const dateBStr = b.createdAt || b.created_at || b.timestamp || '';
                    
                    // Créer les objets Date
                    const dateA = dateAStr ? new Date(dateAStr) : new Date(0);
                    const dateB = dateBStr ? new Date(dateBStr) : new Date(0);
                    
                    // Vérifier si les dates sont valides
                    const timeA = isNaN(dateA.getTime()) ? 0 : dateA.getTime();
                    const timeB = isNaN(dateB.getTime()) ? 0 : dateB.getTime();
                    
                    // Tri croissant (plus ancien d'abord)
                    return timeA - timeB;
                });
                displayTopicMessages(messages);
            } else {
                // Si pas de messages, vérifier s'il y a des formulaires
                const hasFormsDisplayed = messagesContainer.querySelectorAll('.form-message').length > 0;
                // Supprimer le loader si présent
                const loader = messagesContainer.querySelector('.fa-spinner');
                if (loader && loader.closest('.text-center')) {
                    loader.closest('.text-center').remove();
                }
                
                // Afficher un message seulement s'il n'y a ni messages ni formulaires
                if (!hasFormsDisplayed) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'text-center text-muted py-3';
                    emptyMessage.innerHTML = '<i class="fas fa-comments fa-2x mb-2"></i><p>Aucun message dans ce sujet</p>';
                    messagesContainer.appendChild(emptyMessage);
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des messages:', error);
            const hasFormsDisplayed = messagesContainer.querySelectorAll('.form-message').length > 0;
            // Supprimer le loader si présent
            const loader = messagesContainer.querySelector('.fa-spinner');
            if (loader && loader.closest('.text-center')) {
                loader.closest('.text-center').remove();
            }
            
            if (!hasFormsDisplayed) {
                messagesContainer.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Erreur lors du chargement des messages: ' + error.message + '</p></div>';
            } else {
                // Afficher un message d'erreur sans écraser les formulaires
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = 'Erreur lors du chargement des messages: ' + error.message;
                messagesContainer.appendChild(errorDiv);
            }
        });
    }
    
    // Fonction pour afficher les messages
    function displayTopicMessages(messages) {
        const messagesContainer = document.getElementById('topic-messages-container');
        if (!messagesContainer) return;
        
        // Supprimer le loader s'il existe
        const loader = messagesContainer.querySelector('.fa-spinner');
        if (loader && loader.closest('.text-center')) {
            loader.closest('.text-center').remove();
        }
        
        // Supprimer uniquement les anciens messages (pas les formulaires)
        const existingMessages = messagesContainer.querySelectorAll('[data-message-id]');
        existingMessages.forEach(msg => msg.remove());
        
        // Supprimer les messages d'état (loader, erreur, vide) qui ne sont pas des formulaires
        const stateMessages = messagesContainer.querySelectorAll('.text-center:not(.form-message)');
        stateMessages.forEach(function(el) {
            const text = el.textContent || '';
            if (text.includes('Chargement') || text.includes('Aucun message')) {
                el.remove();
            }
        });
        
        // Si pas de messages et pas de formulaires, afficher un message
        const hasForms = messagesContainer.querySelectorAll('.form-message').length > 0;
        if (messages.length === 0 && !hasForms) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'text-center text-muted py-3';
            emptyMessage.innerHTML = '<i class="fas fa-comments fa-2x mb-2"></i><p>Aucun message dans ce sujet</p>';
            messagesContainer.appendChild(emptyMessage);
            return;
        }
        
        // Ajouter les nouveaux messages
        let messagesHtml = '';
        messages.forEach(function(message) {
            // Gérer différents formats de messages de l'API
            const authorId = message.author?._id || message.author?.id || message.author_id || message.userId || message.user_id;
            const isCurrentUser = authorId && window.currentUserId && String(authorId) === String(window.currentUserId);
            const alignClass = isCurrentUser ? 'justify-content-end' : 'justify-content-start';
            const messageClass = isCurrentUser ? 'message-sent' : 'message-received';
            const authorName = message.author?.name || message.author_name || message.userName || 'Utilisateur';
            
            // Gérer le contenu du message
            const messageContent = message.content || message.text || '';
            
            // Gérer la date
            let messageTime = '';
            if (message.createdAt || message.created_at || message.timestamp) {
                try {
                    const date = new Date(message.createdAt || message.created_at || message.timestamp);
                    messageTime = date.toLocaleString('fr-FR');
                } catch (e) {
                    messageTime = 'Date inconnue';
                }
            }
            
            // Gérer les pièces jointes/images
            let attachmentHtml = '';
            // Vérifier si le message a une pièce jointe (peut être null, un objet, ou une chaîne)
            if (message.attachment && 
                message.attachment !== null && 
                typeof message.attachment === 'object' &&
                (message.attachment.filename || message.attachment.url || message.attachment.path)) {
                
                let attachmentUrl = message.attachment.url || message.attachment.path || `/uploads/${message.attachment.filename}`;
                
                // Détecter si c'est une image ou un PDF par mimeType ou par extension
                let isImage = false;
                let isPdf = false;
                
                if (message.attachment.mimeType) {
                    if (message.attachment.mimeType.startsWith('image/')) {
                        isImage = true;
                    } else if (message.attachment.mimeType === 'application/pdf') {
                        isPdf = true;
                    }
                }
                
                // Détecter par extension si mimeType n'est pas disponible
                if (!isImage && !isPdf) {
                    const filename = message.attachment.filename || message.attachment.originalName || attachmentUrl;
                    const lowerFilename = filename.toLowerCase();
                    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.svg'];
                    const pdfExtensions = ['.pdf'];
                    
                    isImage = imageExtensions.some(ext => lowerFilename.endsWith(ext));
                    isPdf = pdfExtensions.some(ext => lowerFilename.endsWith(ext));
                }
                
                const originalName = message.attachment.originalName || message.attachment.filename || 'Pièce jointe';
                
                // Définir l'URL originale pour tous les types de fichiers
                const originalUrl = message.attachment.url || message.attachment.path || `/uploads/${message.attachment.filename}`;
                
                // Pour les images, utiliser la route d'images du backend WebApp2
                if (isImage) {
                    // Pour toutes les images, utiliser la route proxy du backend WebApp2
                    attachmentUrl = '/messages/image/' + (message._id || message.id) + '?url=' + encodeURIComponent(originalUrl);
                } else if (isPdf) {
                    // Pour les PDF, utiliser la route d'attachment avec paramètre pour affichage inline
                    attachmentUrl = '/messages/attachment/' + (message._id || message.id) + '?inline=1&url=' + encodeURIComponent(originalUrl);
                } else {
                    // Pour les autres fichiers, utiliser la route de téléchargement
                    attachmentUrl = '/messages/attachment/' + (message._id || message.id) + '?url=' + encodeURIComponent(originalUrl);
                }
                
                const escapedOriginalName = escapeHtml(originalName);
                const escapedOriginalUrl = escapeHtml(originalUrl);
                const messageId = message._id || message.id;
                
                attachmentHtml = `
                    <div class="message-attachment mt-2">
                        ${isImage 
                            ? `<a href="${attachmentUrl}" target="_blank" class="attachment-link">
                                <img src="${attachmentUrl}" 
                                     alt="${escapedOriginalName}" 
                                     class="img-fluid rounded message-image" 
                                     style="max-width: 300px; max-height: 300px; object-fit: cover; cursor: pointer;"
                                     data-original-url="${escapedOriginalUrl}"
                                     onerror="(function(img) { console.error('Erreur de chargement de l\\'image:', img.getAttribute('data-original-url') || '${escapedOriginalUrl}'); img.style.display='none'; const fallback = img.nextElementSibling; if(fallback) fallback.style.display='block'; })(this);">
                                <div class="image-fallback" style="display: none;">
                                    <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                                        <i class="fas fa-image me-2"></i>
                                        <span>${escapedOriginalName}</span>
                                    </div>
                                </div>
                            </a>`
                            : isPdf
                            ? `<div class="pdf-preview-container">
                                <iframe src="${attachmentUrl}" 
                                        class="pdf-preview"
                                        style="width: 100%; max-width: 600px; height: 400px; border: 1px solid #dee2e6; border-radius: 8px;"
                                        title="${escapedOriginalName}">
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
                                    <span>${escapedOriginalName}</span>
                                </div>
                            </a>`
                        }
                    </div>`;
            }
            
            // Déterminer les permissions
            const canEdit = isCurrentUser;
            const canDelete = isCurrentUser || (window.isAdmin === true);
            
            // HTML des boutons d'action
            let actionsHtml = '';
            if (canEdit || canDelete) {
                actionsHtml = '<div class="message-actions">';
                if (canEdit) {
                    actionsHtml += `<button type="button" class="btn btn-sm btn-edit" onclick="editTopicMessage('${message._id || message.id}')"><i class="fas fa-edit"></i></button>`;
                }
                if (canDelete) {
                    actionsHtml += `<button type="button" class="btn btn-sm btn-delete" onclick="deleteTopicMessage('${message._id || message.id}')"><i class="fas fa-trash"></i></button>`;
                }
                actionsHtml += '</div>';
            }
            
            messagesHtml += `
                <div class="d-flex ${alignClass} mb-3" data-message-id="${message._id || message.id || ''}">
                    <div class="message ${messageClass}">
                        <div class="message-header">
                            <span class="message-author">${escapeHtml(authorName)}</span>
                            <span class="message-time">${messageTime}</span>
                        </div>
                        ${messageContent ? `<div class="message-content">${escapeHtml(messageContent).replace(/\n/g, '<br>')}</div>` : ''}
                        ${attachmentHtml}
                        ${actionsHtml}
                    </div>
                </div>
            `;
        });
        
        // Insérer les messages (les formulaires seront ajoutés en bas après)
        if (messagesHtml) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = messagesHtml;
            // Trouver le premier formulaire pour insérer les messages avant
            const firstForm = messagesContainer.querySelector('.form-message');
            if (firstForm) {
                // Insérer les messages avant les formulaires
                while (tempDiv.firstChild) {
                    messagesContainer.insertBefore(tempDiv.firstChild, firstForm);
                }
            } else {
                // Pas de formulaires, ajouter les messages normalement
                while (tempDiv.firstChild) {
                    messagesContainer.appendChild(tempDiv.firstChild);
                }
            }
        }
        
        // Réattacher les gestionnaires d'événements aux formulaires
        attachFormEventHandlers();
        
        // Faire défiler vers le bas
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }
    
    // Gérer l'envoi de messages
    const topicMessageForm = document.getElementById('topic-message-form');
    if (topicMessageForm) {
        topicMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const topicId = document.getElementById('current-topic-id-input').value;
            const messageInput = document.getElementById('topic-message-input');
            const messageContent = messageInput.value.trim();
            const attachmentInput = document.getElementById('topic-message-attachment');
            const hasFile = attachmentInput && attachmentInput.files && attachmentInput.files[0];
            
            if (!messageContent && !hasFile) {
                //console.log('[DEBUG] Aucun contenu ni fichier - abandon');
                return;
            }
            
            if (!topicId) {
                console.error('[DEBUG] Pas de topicId');
                return;
            }
            
           
            // Utiliser FormData pour supporter les fichiers
            const formData = new FormData();
            formData.append('content', messageContent);
            
            if (hasFile) {
                formData.append('attachment', attachmentInput.files[0]);
            }
            
            // Appeler le backend de l'application web au lieu de l'API externe directement
            fetch(`/api/topics/${topicId}/messages`, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {
                //console.log('[DEBUG] Réponse reçue - Status:', response.status, 'OK:', response.ok);
                //console.log('[DEBUG] Content-Type:', response.headers.get('content-type'));
                
                // Lire le texte brut d'abord
                return response.text().then(text => {
                    //console.log('[DEBUG] Réponse brute (100 premiers caractères):', text.substring(0, 100));
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                    }
                    
                    // Essayer de parser en JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('[DEBUG] Erreur parsing JSON:', e);
                        console.error('[DEBUG] Texte complet:', text);
                        throw new Error('La réponse n\'est pas du JSON valide');
                    }
                });
            })
            .then(data => {
                //console.log('[DEBUG] Données JSON parsées:', data);
                if (data.success) {
                    messageInput.value = '';
                    // Vider aussi l'input de fichier
                    if (attachmentInput) {
                        attachmentInput.value = '';
                    }
                    // Recharger les messages
                    loadTopicMessages(topicId);
                } else {
                    alert('Erreur lors de l\'envoi du message');
                }
            })
            .catch(error => {
                //console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du message');
            });
        });
    }
    
    // Fonction pour ouvrir le modal de formulaire
    window.openFormModal = function(formId) {
        const form = currentTopicForms.find(f => f.id == formId);
        if (!form) return;
        
        // Créer ou récupérer le modal
        let modal = document.getElementById('form-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'form-modal';
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            document.body.appendChild(modal);
        }
        
        // Générer le HTML du formulaire
        let formHtml = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">📊 ${escapeHtml(form.title || 'Formulaire')}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${form.description ? `<p class="text-muted mb-3">${escapeHtml(form.description)}</p>` : ''}
                        <form id="form-response-form">
                            <input type="hidden" id="form-id" value="${form.id}">
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
                        <button type="button" class="btn btn-primary" onclick="submitFormResponse()">Envoyer</button>
                    </div>
                </div>
            </div>
        `;
        
        modal.innerHTML = formHtml;
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    };
    
    // Fonction pour soumettre une réponse de formulaire
    window.submitFormResponse = function() {
        const formId = document.getElementById('form-id')?.value;
        if (!formId) return;
        
        const form = document.getElementById('form-response-form');
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
        const formObj = currentTopicForms.find(f => f.id == formId);
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
                bootstrap.Modal.getInstance(document.getElementById('form-modal')).hide();
                // Recharger les compteurs de réponses
                const topicId = document.getElementById('current-topic-id-input').value;
                if (topicId) {
                    loadTopicForms(topicId);
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
    
    // Fonction pour voir les résultats d'un formulaire
    window.viewFormResults = function(formId) {
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
                    // Format imbriqué: {success: true, data: {data: [...]}}
                    responses = data.data.data;
                }
            } else if (Array.isArray(data)) {
                responses = data;
            }
            
            //console.log('Réponses extraites:', responses.length);
            
            if (responses.length > 0) {
                const form = currentTopicForms.find(f => f.id == formId);
                if (!form) {
                    console.error('Formulaire non trouvé:', formId);
                    alert('Formulaire non trouvé');
                    return;
                }
                showFormResultsModal(form, responses);
            } else {
                alert('Aucune réponse disponible pour ce formulaire');
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des résultats:', error);
            alert('Erreur lors du chargement des résultats: ' + error.message);
        });
    };
    
    // Fonction pour afficher les résultats dans un modal (format similaire à l'app mobile)
    function showFormResultsModal(form, responses) {
        //console.log('Affichage des résultats pour le formulaire:', form);
        //console.log('Nombre de réponses:', responses.length);
        
        let modal = document.getElementById('form-results-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'form-results-modal';
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
            // En-tête avec nombre total de réponses
            resultsHtml += `
                <div class="alert alert-info mb-4">
                    <strong>📊 ${responses.length}</strong> réponse${responses.length > 1 ? 's' : ''} au total
                </div>
            `;
            
            // Si on a les données du formulaire, utiliser les vrais titres de questions
            if (form && form.questions && Array.isArray(form.questions)) {
                // Afficher les résultats groupés par question
                form.questions.forEach(function(question, questionIndex) {
                    // Trouver les réponses pour cette question
                    const questionResponses = [];
                    
                    responses.forEach(function(response) {
                        // Parser les réponses si elles sont en JSON string
                        let parsedResponses = response.responses;
                        if (typeof response.responses === 'string') {
                            try {
                                parsedResponses = JSON.parse(response.responses);
                            } catch (e) {
                                parsedResponses = response.responses;
                            }
                        }
                        
                        // Chercher la réponse pour cette question (par ID ou index)
                        let answer = null;
                        if (parsedResponses && typeof parsedResponses === 'object') {
                            // Chercher par ID (peut être numérique ou string)
                            answer = parsedResponses[question.id] || parsedResponses[question._id] || parsedResponses[String(question.id)] || parsedResponses[String(question._id)];
                            // Si pas trouvé, essayer par index
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
                    
                    // Grouper par choix (pour les questions radio/checkbox)
                    const choicesMap = {};
                    questionResponses.forEach(function(resp) {
                        const choice = resp.answer;
                        if (!choicesMap[choice]) {
                            choicesMap[choice] = [];
                        }
                        choicesMap[choice].push(resp.user);
                    });
                    
                    // Afficher la question et ses résultats
                    resultsHtml += `
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">❓ ${escapeHtml(question.text || 'Question ' + (questionIndex + 1))}</h6>
                            </div>
                            <div class="card-body">
                    `;
                    
                    // Afficher chaque choix avec le nombre de réponses et les utilisateurs
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
            } else {
                // Fallback: afficher les réponses brutes si pas de formData
                resultsHtml += '<div class="alert alert-warning">Données du formulaire non disponibles</div>';
                responses.forEach(function(response, index) {
                    const userName = response.user?.name || response.user_name || response.username || `Utilisateur ${response.user_id || ''}`;
                    const date = response.submitted_at || response.created_at || response.createdAt;
                    
                    resultsHtml += `
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>Réponse #${index + 1}</strong>
                                ${date ? `<small class="text-muted ms-2">📅 ${new Date(date).toLocaleString('fr-FR')}</small>` : ''}
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>👤 Utilisateur:</strong> ${escapeHtml(userName)}</p>
                    `;
                    
                    let parsedResponses = response.responses;
                    if (typeof response.responses === 'string') {
                        try {
                            parsedResponses = JSON.parse(response.responses);
                        } catch (e) {
                            parsedResponses = response.responses;
                        }
                    }
                    
                    if (parsedResponses && typeof parsedResponses === 'object') {
                        resultsHtml += '<p class="mb-2"><strong>📝 Réponses:</strong></p>';
                        Object.entries(parsedResponses).forEach(function([questionId, answer]) {
                            resultsHtml += `
                                <div class="mb-2 ms-3">
                                    <strong>❓ Question ${questionId}:</strong>
                                    <div class="ms-3">${escapeHtml(Array.isArray(answer) ? answer.join(', ') : String(answer))}</div>
                                </div>
                            `;
                        });
                    } else {
                        resultsHtml += `<p class="mb-0">${escapeHtml(String(parsedResponses || ''))}</p>`;
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
    window.deleteForm = function(formId) {
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
                const topicId = document.getElementById('current-topic-id-input').value;
                if (topicId) {
                    loadTopicForms(topicId);
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
    
    // Fonction pour réattacher les gestionnaires d'événements aux formulaires
    function attachFormEventHandlers() {
        // Les gestionnaires sont déjà attachés via onclick dans le HTML généré
        // Cette fonction peut être utilisée pour des événements plus complexes si nécessaire
    }
    
    // Fonction pour ouvrir le constructeur de formulaire
    window.openFormBuilder = function() {
        const topicId = document.getElementById('current-topic-id-input')?.value;
        if (!topicId) {
            alert('Veuillez d\'abord sélectionner un sujet');
            return;
        }
        
        // Créer ou récupérer le modal de création de formulaire
        let modal = document.getElementById('form-builder-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'form-builder-modal';
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            document.body.appendChild(modal);
        }
        
        // Générer le HTML du constructeur de formulaire
        let builderHtml = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">📊 Créer un formulaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="form-builder-form">
                            <input type="hidden" id="builder-topic-id" value="${topicId}">
                            <div class="mb-3">
                                <label for="form-title" class="form-label">Titre du formulaire <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="form-title" required placeholder="Ex: Questionnaire de satisfaction">
                            </div>
                            <div class="mb-3">
                                <label for="form-description" class="form-label">Description</label>
                                <textarea class="form-control" id="form-description" rows="2" placeholder="Description du formulaire (optionnel)"></textarea>
                            </div>
                            <div id="form-questions-container">
                                <h6 class="mb-3">Questions</h6>
                                <div id="form-questions-list"></div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFormQuestion()">
                                    <i class="fas fa-plus"></i> Ajouter une question
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="submitFormBuilder()">Créer le formulaire</button>
                    </div>
                </div>
            </div>
        `;
        
        modal.innerHTML = builderHtml;
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Réinitialiser la liste des questions
        window.formBuilderQuestions = [];
    };
    
    // Variable pour stocker les questions du formulaire en cours de création
    window.formBuilderQuestions = [];
    
    // Fonction pour ajouter une question au formulaire
    window.addFormQuestion = function() {
        const questionId = 'question_' + Date.now();
        const questionIndex = window.formBuilderQuestions.length;
        
        window.formBuilderQuestions.push({
            id: questionId,
            text: '',
            type: 'text',
            options: [],
            required: false
        });
        
        const questionsList = document.getElementById('form-questions-list');
        if (!questionsList) return;
        
        const questionHtml = `
            <div class="card mb-3 question-item" data-question-id="${questionId}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Question ${questionIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFormQuestion('${questionId}')">
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
                        <select class="form-select question-type" data-question-id="${questionId}" onchange="updateQuestionType('${questionId}')">
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
                                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control option-input" placeholder="Option 2">
                                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption('${questionId}')">
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
    window.updateQuestionType = function(questionId) {
        const question = window.formBuilderQuestions.find(q => q.id === questionId);
        if (!question) return;
        
        const select = document.querySelector(`.question-type[data-question-id="${questionId}"]`);
        const optionsContainer = document.querySelector(`.question-options-container[data-question-id="${questionId}"]`);
        
        if (select && optionsContainer) {
            question.type = select.value;
            if (question.type === 'radio' || question.type === 'checkbox') {
                optionsContainer.style.display = 'block';
                if (question.options.length === 0) {
                    question.options = ['', ''];
                    updateOptionsDisplay(questionId);
                }
            } else {
                optionsContainer.style.display = 'none';
                question.options = [];
            }
        }
    };
    
    // Fonction pour ajouter une option
    window.addOption = function(questionId) {
        const question = window.formBuilderQuestions.find(q => q.id === questionId);
        if (!question) return;
        
        question.options.push('');
        updateOptionsDisplay(questionId);
    };
    
    // Fonction pour supprimer une option
    window.removeOption = function(button) {
        const inputGroup = button.closest('.input-group');
        const optionInput = inputGroup.querySelector('.option-input');
        const questionId = optionInput.closest('.question-item').getAttribute('data-question-id');
        const question = window.formBuilderQuestions.find(q => q.id === questionId);
        
        if (question && question.options.length > 1) {
            const index = Array.from(inputGroup.parentElement.children).indexOf(inputGroup);
            question.options.splice(index, 1);
            inputGroup.remove();
        }
    };
    
    // Fonction pour mettre à jour l'affichage des options
    function updateOptionsDisplay(questionId) {
        const question = window.formBuilderQuestions.find(q => q.id === questionId);
        if (!question) return;
        
        const optionsList = document.querySelector(`.question-options-list[data-question-id="${questionId}"]`);
        if (!optionsList) return;
        
        optionsList.innerHTML = '';
        question.options.forEach(function(option, index) {
            const optionHtml = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control option-input" placeholder="Option ${index + 1}" value="${escapeHtml(option)}">
                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
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
    window.removeFormQuestion = function(questionId) {
        window.formBuilderQuestions = window.formBuilderQuestions.filter(q => q.id !== questionId);
        const questionElement = document.querySelector(`.question-item[data-question-id="${questionId}"]`);
        if (questionElement) {
            questionElement.remove();
        }
    };
    
    // Fonction pour soumettre le formulaire créé
    window.submitFormBuilder = function() {
        const topicId = document.getElementById('builder-topic-id')?.value;
        const title = document.getElementById('form-title')?.value;
        const description = document.getElementById('form-description')?.value || '';
        
        if (!title || !title.trim()) {
            alert('Le titre du formulaire est requis');
            return;
        }
        
        // Collecter les questions
        const questions = [];
        window.formBuilderQuestions.forEach(function(questionData) {
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
            
            // Ajouter les options si nécessaire
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
            topicId: topicId
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
                bootstrap.Modal.getInstance(document.getElementById('form-builder-modal')).hide();
                // Recharger les formulaires
                if (topicId) {
                    loadTopicForms(topicId);
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
    
    // Attacher les gestionnaires au chargement initial
    attachTopicClickHandlers();
    
    // Gérer les clics sur les boutons de suppression
    document.querySelectorAll('.delete-group-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const groupId = this.getAttribute('data-group-id');
            const groupName = this.getAttribute('data-group-name');
            
            //console.log('Clic sur suppression du groupe:', groupId, groupName);
            
            // Mettre à jour le modal avec les informations du groupe
            document.getElementById('groupName').textContent = groupName;
            document.getElementById('deleteGroupId').value = groupId;
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
    
    // Gérer la soumission du formulaire de suppression
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const groupId = document.getElementById('deleteGroupId').value;
            console.log('Suppression du groupe:', groupId);
            
            // Créer un formulaire temporaire pour la suppression
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/groups/' + groupId;
            
            // Ajouter le champ _method pour simuler DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            // Ajouter le champ group_id
            const groupIdInput = document.createElement('input');
            groupIdInput.type = 'hidden';
            groupIdInput.name = 'group_id';
            groupIdInput.value = groupId;
            form.appendChild(groupIdInput);
            
            // Soumettre le formulaire
            document.body.appendChild(form);
            form.submit();
        });
    }
});

// Fonction globale pour éditer un message de topic
window.editTopicMessage = async function(messageId) {
    //console.log('[Edit] Édition du message:', messageId);
    
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) {
        console.error('[Edit] Message non trouvé');
        return;
    }
    
    const contentElement = messageElement.querySelector('.message-content');
    if (!contentElement) {
        console.error('[Edit] Contenu du message non trouvé');
        return;
    }
    
    const currentContent = contentElement.innerHTML.replace(/<br>/g, '\n');
    const newContent = prompt('Modifier le message:', currentContent);
    
    if (newContent === null || newContent.trim() === currentContent.trim()) {
        return; // Annulé ou pas de changement
    }
    
    if (newContent.trim() === '') {
        alert('Le message ne peut pas être vide');
        return;
    }
    
    try {
        const response = await fetch(`/messages/${messageId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ content: newContent.trim() })
        });
        
        if (response.ok) {
            // Recharger les messages
            const topicId = document.getElementById('current-topic-id-input')?.value;
            if (topicId) {
                loadTopicMessages(topicId);
            }
        } else {
            const errorText = await response.text();
            console.error('[Edit] Erreur:', errorText);
            alert('Erreur lors de la modification du message');
        }
    } catch (error) {
        console.error('[Edit] Exception:', error);
        alert('Erreur lors de la modification du message');
    }
};

// Fonction globale pour supprimer un message de topic
window.deleteTopicMessage = async function(messageId) {
    //console.log('[Delete] Suppression du message:', messageId);
    
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        return;
    }
    
    try {
        const response = await fetch(`/messages/${messageId}`, {
            method: 'DELETE',
            credentials: 'same-origin'
        });
        
        if (response.ok) {
            // Recharger les messages
            const topicId = document.getElementById('current-topic-id-input')?.value;
            if (topicId) {
                loadTopicMessages(topicId);
            }
        } else {
            const errorText = await response.text();
            console.error('[Delete] Erreur:', errorText);
            alert('Erreur lors de la suppression du message');
        }
    } catch (error) {
        console.error('[Delete] Exception:', error);
        alert('Erreur lors de la suppression du message');
    }
};
