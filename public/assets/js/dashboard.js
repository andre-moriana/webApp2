// CSS pour afficher/masquer la liste au survol
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter le style CSS dynamiquement
    const style = document.createElement('style');
    style.textContent = `
        .club-card-hover .club-list {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.4s ease, opacity 0.3s ease;
        }
        
        .club-card-hover:hover .club-list {
            max-height: 500px;
            opacity: 1;
        }
        
        .club-card-hover {
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        .club-card-hover:hover {
            transform: translateY(-5px);
        }
        
        .committee-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
            padding-left: 5px;
            transition: all 0.2s ease;
        }
        
        .committee-item.selected {
            background-color: rgba(40, 167, 69, 0.2);
            font-weight: bold;
            padding-left: 5px;
        }
        
        .club-item:hover {
            background-color: rgba(40, 167, 69, 0.1);
            padding-left: 5px;
            transition: all 0.2s ease;
        }
        
        .club-item.selected {
            background-color: rgba(40, 167, 69, 0.2);
            font-weight: bold;
            padding-left: 5px;
        }
        
        #clubs-display-card .club-list {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.4s ease, opacity 0.3s ease;
        }
        
        #clubs-display-card:hover .club-list {
            max-height: 500px;
            opacity: 1;
        }
        
        .user-card-hover .user-list {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.4s ease, opacity 0.3s ease;
        }
        
        .user-card-hover:hover .user-list {
            max-height: 500px;
            opacity: 1;
        }
        
        .user-card-hover {
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        .user-card-hover:hover {
            transform: translateY(-5px);
        }
    `;
    document.head.appendChild(style);
    
    // Gérer le clic sur un comité
    document.querySelectorAll('.committee-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const committeeId = this.getAttribute('data-committee-id');
            const committeeName = this.textContent.trim();
            
            // Retirer la sélection des autres items
            document.querySelectorAll('.committee-item').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Ajouter la sélection à l'item cliqué
            this.classList.add('selected');
            
            // Afficher les clubs de ce comité
            displayClubsForCommittee(committeeId, committeeName);
            
            // Filtrer les utilisateurs
            filterUsersByCommittee(committeeId, committeeName);
        });
    });
    
    // Gérer le bouton de réinitialisation des clubs
    document.getElementById('reset-clubs-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        resetClubsDisplay();
    });
    
    // Gérer le bouton de réinitialisation des utilisateurs
    document.getElementById('reset-users-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        resetUsersDisplay();
    });
    
    // Délégation d'événements pour les clubs (car ils peuvent être dynamiquement ajoutés)
    document.getElementById('clubs-list')?.addEventListener('click', function(e) {
        const clubItem = e.target.closest('.club-item');
        if (clubItem) {
            e.stopPropagation();
            
            const clubId = clubItem.getAttribute('data-club-id');
            const clubName = clubItem.textContent.trim();
            
            // Retirer la sélection des comités
            document.querySelectorAll('.committee-item').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Retirer la sélection des autres clubs
            document.querySelectorAll('.club-item').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Ajouter la sélection à l'item cliqué
            clubItem.classList.add('selected');
            
            // Filtrer les utilisateurs par club
            filterUsersByClub(clubId, clubName);
        }
    });
});

function displayClubsForCommittee(committeeId, committeeName) {
    const clubsList = document.getElementById('clubs-list');
    const clubsContainer = document.getElementById('clubs-list-container');
    const clubsCount = document.getElementById('clubs-count');
    const clubsTitle = document.getElementById('clubs-title');
    const resetBtn = document.getElementById('reset-clubs-btn');
    
    if (!clubsList || !clubsContainer) return;
    
    const clubs = window.clubsByCommittee[committeeId] || [];
    
    // Mettre à jour le titre et le compteur
    clubsTitle.textContent = `Clubs de ${committeeName}`;
    clubsCount.textContent = clubs.length;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    clubsList.innerHTML = '';
    
    // Ajouter les clubs
    if (clubs.length > 0) {
        clubs.forEach(function(club) {
            const li = document.createElement('li');
            li.className = 'mb-1 club-item d-flex justify-content-between align-items-center';
            li.setAttribute('data-club-id', club.id);
            li.style.cursor = 'pointer';
            li.title = 'Cliquez pour voir les utilisateurs';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> ${escapeHtml(club.name)}`;
            
            const link = document.createElement('a');
            link.href = '/clubs/' + encodeURIComponent(club.id);
            link.className = 'btn btn-sm btn-outline-primary club-link';
            link.style.cssText = 'font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;';
            link.title = 'Voir le club';
            link.innerHTML = '<i class="fas fa-external-link-alt"></i>';
            link.onclick = function(e) { e.stopPropagation(); };
            
            li.appendChild(span);
            li.appendChild(link);
            clubsList.appendChild(li);
        });
    } else {
        clubsList.innerHTML = '<li class="text-muted">Aucun club trouvé pour ce comité</li>';
    }
}

function filterUsersByClub(clubId, clubName) {
    const usersList = document.getElementById('users-list');
    const usersCount = document.getElementById('users-count');
    const usersTitle = document.getElementById('users-title');
    const resetBtn = document.getElementById('reset-users-btn');
    
    if (!usersList) return;
    
    // Récupérer les utilisateurs de ce club
    const filteredUsers = window.usersByClub[clubId] || [];
    
    // Mettre à jour le titre et le compteur
    usersTitle.textContent = `Utilisateurs de ${clubName}`;
    usersCount.textContent = filteredUsers.length;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    usersList.innerHTML = '';
    
    // Ajouter les utilisateurs
    if (filteredUsers.length > 0) {
        filteredUsers.forEach(function(user) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            li.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            usersList.appendChild(li);
        });
    } else {
        usersList.innerHTML = '<li class="text-muted">Aucun utilisateur trouvé pour ce club</li>';
    }
}

function filterUsersByCommittee(committeeId, committeeName) {
    const usersList = document.getElementById('users-list');
    const usersCount = document.getElementById('users-count');
    const usersTitle = document.getElementById('users-title');
    const resetBtn = document.getElementById('reset-users-btn');
    
    if (!usersList) return;
    
    // Récupérer tous les clubs de ce comité
    const clubs = window.clubsByCommittee[committeeId] || [];
    const clubIds = clubs.map(club => club.id);
    
    // Filtrer les utilisateurs par ces clubs
    let filteredUsers = [];
    clubIds.forEach(function(clubId) {
        const usersInClub = window.usersByClub[clubId] || [];
        filteredUsers = filteredUsers.concat(usersInClub);
    });
    
    // Mettre à jour le titre et le compteur
    usersTitle.textContent = `Utilisateurs de ${committeeName}`;
    usersCount.textContent = filteredUsers.length;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    usersList.innerHTML = '';
    
    // Ajouter les utilisateurs
    if (filteredUsers.length > 0) {
        filteredUsers.forEach(function(user) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            li.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            usersList.appendChild(li);
        });
    } else {
        usersList.innerHTML = '<li class="text-muted">Aucun utilisateur trouvé pour ce comité</li>';
    }
}

function resetClubsDisplay() {
    const clubsList = document.getElementById('clubs-list');
    const clubsContainer = document.getElementById('clubs-list-container');
    const clubsCount = document.getElementById('clubs-count');
    const clubsTitle = document.getElementById('clubs-title');
    const resetBtn = document.getElementById('reset-clubs-btn');
    
    // Réinitialiser les valeurs
    clubsTitle.textContent = 'Total Clubs';
    clubsCount.textContent = window.totalClubs || 0;
    
    // Masquer le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'none';
    
    // Réafficher tous les clubs
    clubsList.innerHTML = '';
    const allClubs = window.allClubs || [];
    
    if (allClubs.length > 0) {
        allClubs.forEach(function(club) {
            const li = document.createElement('li');
            li.className = 'mb-1 club-item d-flex justify-content-between align-items-center';
            li.setAttribute('data-club-id', club.id);
            li.style.cursor = 'pointer';
            li.title = 'Cliquez pour voir les utilisateurs';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> ${escapeHtml(club.name)}`;
            
            const link = document.createElement('a');
            link.href = '/clubs/' + encodeURIComponent(club.id);
            link.className = 'btn btn-sm btn-outline-primary club-link';
            link.style.cssText = 'font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;';
            link.title = 'Voir le club';
            link.innerHTML = '<i class="fas fa-external-link-alt"></i>';
            link.onclick = function(e) { e.stopPropagation(); };
            
            li.appendChild(span);
            li.appendChild(link);
            clubsList.appendChild(li);
        });
    } else {
        clubsList.innerHTML = '<li class="text-muted">Aucun club</li>';
    }
    
    // Retirer les sélections
    document.querySelectorAll('.committee-item').forEach(function(el) {
        el.classList.remove('selected');
    });
    
    document.querySelectorAll('.club-item').forEach(function(el) {
        el.classList.remove('selected');
    });
    
    // Réinitialiser aussi les utilisateurs
    resetUsersDisplay();
}

function resetUsersDisplay() {
    const usersList = document.getElementById('users-list');
    const usersCount = document.getElementById('users-count');
    const usersTitle = document.getElementById('users-title');
    const resetBtn = document.getElementById('reset-users-btn');
    
    // Réinitialiser les valeurs
    usersTitle.textContent = 'Total Utilisateurs';
    usersCount.textContent = window.totalUsers || 0;
    
    // Masquer le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'none';
    
    // Réafficher tous les utilisateurs
    usersList.innerHTML = '';
    const allUsers = window.allUsers || [];
    
    if (allUsers.length > 0) {
        allUsers.forEach(function(user) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            li.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            usersList.appendChild(li);
        });
    } else {
        usersList.innerHTML = '<li class="text-muted">Aucun utilisateur</li>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
