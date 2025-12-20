// Récupérer le token d'authentification depuis la session PHP
const authToken = window.authToken || '';
const groupId = window.groupId || null;
const currentMemberIds = window.currentMemberIds || [];

let selectedUsers = [];
let allUsers = [];

// Charger la liste des utilisateurs au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadAllUsers();
});

// Charger tous les utilisateurs via l'API backend
async function loadAllUsers() {
    try {
        // Utiliser l'endpoint du backend PHP
        const response = await fetch('/users', {
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Gérer les deux formats possibles de réponse
            if (data.data && Array.isArray(data.data)) {
                allUsers = data.data;
            } else if (Array.isArray(data)) {
                allUsers = data;
            } else {
                allUsers = [];
            }
            
            // Afficher tous les utilisateurs disponibles dès le chargement
            displayAllUsers();
        } else {
            allUsers = [];
            displayError();
        }
    } catch (error) {
        allUsers = [];
        displayError();
    }
}

// Afficher tous les utilisateurs disponibles
function displayAllUsers() {
    const resultsDiv = document.getElementById('userSearchResults');
    
    if (allUsers.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Aucun utilisateur disponible</p>';
        return;
    }
    
    // Filtrer les utilisateurs qui ne sont pas déjà membres
    const availableUsers = allUsers.filter(user => !currentMemberIds.includes(user.id));
    
    if (availableUsers.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Tous les utilisateurs sont déjà membres de ce groupe</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    html += '<div class="list-group-item bg-light"><strong>Utilisateurs disponibles (' + availableUsers.length + ')</strong></div>';
    
    availableUsers.forEach(user => {
        const isSelected = selectedUsers.some(u => u.id === user.id);
        
        if (!isSelected) {
            html += `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                     onclick="selectUser(${user.id})">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            ${(user.name || 'U').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h6 class="mb-0">${user.name || 'Nom inconnu'}</h6>
                            <small class="text-muted">${user.email || 'Email non défini'}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); selectUser(${user.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
        }
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

// Afficher une erreur
function displayError() {
    const resultsDiv = document.getElementById('userSearchResults');
    resultsDiv.innerHTML = '<p class="text-danger">Erreur lors du chargement des utilisateurs</p>';
}

// Recherche d'utilisateurs
document.getElementById('userSearch').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    
    if (query.length > 0) {
        const filteredUsers = allUsers.filter(user => 
            (user.name && user.name.toLowerCase().includes(query)) ||
            (user.email && user.email.toLowerCase().includes(query)) ||
            (user.username && user.username.toLowerCase().includes(query))
        );
        displaySearchResults(filteredUsers);
    } else {
        // Si la recherche est vide, afficher tous les utilisateurs
        displayAllUsers();
    }
});

// Afficher les résultats de recherche
function displaySearchResults(users) {
    const resultsDiv = document.getElementById('userSearchResults');
    
    if (users.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Aucun utilisateur trouvé</p>';
        return;
    }
    
    // Filtrer les utilisateurs qui ne sont pas déjà membres
    const availableUsers = users.filter(user => !currentMemberIds.includes(user.id));
    
    if (availableUsers.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Aucun utilisateur disponible trouvé</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    html += '<div class="list-group-item bg-light"><strong>Résultats de recherche (' + availableUsers.length + ')</strong></div>';
    
    availableUsers.forEach(user => {
        const isSelected = selectedUsers.some(u => u.id === user.id);
        
        if (!isSelected) {
            html += `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                     onclick="selectUser(${user.id})">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            ${(user.name || 'U').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h6 class="mb-0">${user.name || 'Nom inconnu'}</h6>
                            <small class="text-muted">${user.email || 'Email non défini'}</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); selectUser(${user.id})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
        }
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

// Sélectionner un utilisateur
function selectUser(userId) {
    const user = allUsers.find(u => u.id === userId);
    
    if (user && !selectedUsers.some(u => u.id === userId)) {
        selectedUsers.push(user);
        updateSelectedUsersList();
        updateAddButton();
        
        // Recharger la liste pour retirer l'utilisateur sélectionné
        const query = document.getElementById('userSearch').value;
        if (query.length > 0) {
            displaySearchResults(allUsers.filter(u => 
                (u.name && u.name.toLowerCase().includes(query.toLowerCase())) ||
                (u.email && u.email.toLowerCase().includes(query.toLowerCase())) ||
                (u.username && u.username.toLowerCase().includes(query.toLowerCase()))
            ));
        } else {
            displayAllUsers();
        }
    }
}

// Retirer un utilisateur de la sélection
function removeSelectedUser(userId) {
    selectedUsers = selectedUsers.filter(u => u.id !== userId);
    updateSelectedUsersList();
    updateAddButton();
    
    // Recharger la liste pour remettre l'utilisateur disponible
    const query = document.getElementById('userSearch').value;
    if (query.length > 0) {
        displaySearchResults(allUsers.filter(u => 
            (u.name && u.name.toLowerCase().includes(query.toLowerCase())) ||
            (u.email && u.email.toLowerCase().includes(query.toLowerCase())) ||
            (u.username && u.username.toLowerCase().includes(query.toLowerCase()))
        ));
    } else {
        displayAllUsers();
    }
}

// Mettre à jour la liste des utilisateurs sélectionnés
function updateSelectedUsersList() {
    const listDiv = document.getElementById('selectedUsersList');
    
    if (selectedUsers.length === 0) {
        listDiv.innerHTML = '<p class="text-muted">Aucun utilisateur sélectionné</p>';
        return;
    }
    
    let html = '';
    selectedUsers.forEach(user => {
        html += `
            <div class="badge bg-primary d-flex align-items-center gap-1">
                ${user.name || 'Nom inconnu'}
                <button type="button" class="btn-close btn-close-white" onclick="removeSelectedUser(${user.id})"></button>
            </div>
        `;
    });
    
    listDiv.innerHTML = html;
}

// Mettre à jour le bouton d'ajout
function updateAddButton() {
    const addButton = document.getElementById('addSelectedUsers');
    addButton.disabled = selectedUsers.length === 0;
}

// Ajouter les utilisateurs sélectionnés
document.getElementById('addSelectedUsers').addEventListener('click', async function() {
    if (selectedUsers.length === 0) {
        return;
    }
    
    try {
        const userIds = selectedUsers.map(u => u.id);
        
        const response = await fetch(`/groups/${groupId}/members`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                user_ids: userIds
            })
        });
        
        if (response.ok) {
            location.reload();
        } else {
            const error = await response.json();
            alert('Erreur: ' + (error.message || 'Erreur lors de l\'ajout des membres'));
        }
    } catch (error) {
        alert('Erreur lors de l\'ajout des membres');
    }
});

function removeMember(memberId, memberName) {
    if (confirm('Retirer ' + memberName + ' du groupe ?')) {
        // Appel API pour retirer le membre
        fetch(`/groups/${groupId}/remove-member/${memberId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (data.message) {
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Erreur lors du retrait du membre'));
            }
        })
        .catch(error => {
            alert('Erreur lors du retrait du membre');
        });
    }
} 