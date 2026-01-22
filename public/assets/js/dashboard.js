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
            
            // Déterminer si c'est un comité régional ou départemental
            // Un comité régional se termine par '00000' (ex: 1300000)
            const isRegional = committeeId && committeeId.length >= 7 && committeeId.substring(committeeId.length - 5) === '00000';
            
            // Retirer la sélection des autres items
            document.querySelectorAll('.committee-item').forEach(function(el) {
                el.classList.remove('selected');
            });
            
            // Ajouter la sélection à l'item cliqué
            this.classList.add('selected');
            
            // Si c'est un comité régional, filtrer les comités départementaux
            if (isRegional) {
                filterDepartmentalCommitteesByRegional(committeeId);
            }
            
            // Afficher les clubs de ce comité
            displayClubsForCommittee(committeeId, committeeName);
            
            // Filtrer les utilisateurs
            filterUsersByCommittee(committeeId, committeeName);
            
            // Filtrer les groupes/sujets et événements
            filterGroupsByCommittee(committeeId, committeeName);
            filterEventsByCommittee(committeeId, committeeName);
        });
    });
    
    // Gérer le bouton de réinitialisation des clubs
    document.getElementById('reset-clubs-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        resetClubsDisplay();
        resetUsersDisplay();
        resetGroupsDisplay();
        resetEventsDisplay();
        resetDepartmentalCommitteesFilter();
    });
    
    // Gérer le bouton de réinitialisation des utilisateurs
    document.getElementById('reset-users-btn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        resetUsersDisplay();
    });
    
    // Délégation d'événements pour les clubs (car ils peuvent être dynamiquement ajoutés)
    // Gérer le clic sur un club
    const clubsListElement = document.getElementById('clubs-list');
    
    clubsListElement?.addEventListener('click', function(e) {
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
            
            // Filtrer les groupes/sujets et événements par club
            filterGroupsByClub(clubId, clubName);
            filterEventsByClub(clubId, clubName);
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
            li.setAttribute('data-club-id', club.id); // Utiliser id (nameshort) pour le filtrage
            li.style.cursor = 'pointer';
            li.title = 'Cliquez pour voir les utilisateurs';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> ${escapeHtml(club.name)}`;
            
            const link = document.createElement('a');
            link.href = '/clubs/' + encodeURIComponent(club.realId); // Utiliser realId pour le lien
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
            li.className = 'mb-1 d-flex justify-content-between align-items-center';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            li.appendChild(span);
            
            // Ajouter le lien uniquement si l'utilisateur a la permission
            if (user.canView) {
                const link = document.createElement('a');
                link.href = 'https://arctraining.fr/users/' + encodeURIComponent(user.id);
                link.className = 'btn btn-sm btn-outline-primary user-link';
                link.style.cssText = 'font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;';
                link.title = "Voir l'utilisateur";
                link.target = '_blank';
                link.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                li.appendChild(link);
            }
            
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
            li.className = 'mb-1 d-flex justify-content-between align-items-center';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            li.appendChild(span);
            
            // Ajouter le lien uniquement si l'utilisateur a la permission
            if (user.canView) {
                const link = document.createElement('a');
                link.href = 'https://arctraining.fr/users/' + encodeURIComponent(user.id);
                link.className = 'btn btn-sm btn-outline-primary user-link';
                link.style.cssText = 'font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;';
                link.title = "Voir l'utilisateur";
                link.target = '_blank';
                link.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                li.appendChild(link);
            }
            
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
            li.setAttribute('data-club-id', club.id); // Utiliser id (nameshort) pour le filtrage
            li.style.cursor = 'pointer';
            li.title = 'Cliquez pour voir les utilisateurs';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> ${escapeHtml(club.name)}`;
            
            const link = document.createElement('a');
            link.href = '/clubs/' + encodeURIComponent(club.realId); // Utiliser realId pour le lien
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
            li.className = 'mb-1 d-flex justify-content-between align-items-center';
            
            const span = document.createElement('span');
            span.innerHTML = `<i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> ${escapeHtml(user.name)}`;
            li.appendChild(span);
            
            // Ajouter le lien uniquement si l'utilisateur a la permission
            if (user.canView) {
                const link = document.createElement('a');
                link.href = 'https://arctraining.fr/users/' + encodeURIComponent(user.id);
                link.className = 'btn btn-sm btn-outline-primary user-link';
                link.style.cssText = 'font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;';
                link.title = "Voir l'utilisateur";
                link.target = '_blank';
                link.innerHTML = '<i class="fas fa-external-link-alt"></i>';
                li.appendChild(link);
            }
            
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
// ==================== GESTION DES GROUPES/SUJETS ET ÉVÉNEMENTS ====================

// Fonction pour filtrer les groupes et sujets par club
function filterGroupsByClub(clubId, clubName) {
    const groupsList = document.getElementById('groups-topics-list');
    const groupsCount = document.getElementById('groups-count');
    const topicsCount = document.getElementById('topics-count');
    const groupsTitle = document.getElementById('groups-title');
    const resetBtn = document.getElementById('reset-groups-btn');
    
    if (!groupsList) return;
    
    // Récupérer les groupes de ce club
    const filteredGroups = window.groupsByClub[clubId] || [];
    
    // Compter les sujets
    let totalTopics = 0;
    filteredGroups.forEach(group => {
        totalTopics += (group.topics || []).length;
    });
    
    // Mettre à jour le titre et le compteur
    groupsTitle.textContent = `Groupes / Sujets de ${clubName}`;
    groupsCount.textContent = filteredGroups.length;
    topicsCount.textContent = totalTopics;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    groupsList.innerHTML = '';
    
    // Ajouter les groupes et sujets
    if (filteredGroups.length > 0) {
        filteredGroups.forEach(function(group) {
            const li = document.createElement('li');
            li.className = 'mb-2';
            
            const groupDiv = document.createElement('div');
            groupDiv.className = 'font-weight-bold';
            const topicsCount = group.topics ? group.topics.length : 0;
            groupDiv.innerHTML = `<i class="fas fa-folder text-info" style="font-size: 0.7rem;"></i> ${escapeHtml(group.name)} <span class="text-muted" style="font-size: 0.8rem; font-weight: normal;">(${topicsCount} sujet${topicsCount > 1 ? 's' : ''})</span>`;
            li.appendChild(groupDiv);
            
            // Ajouter les sujets s'il y en a
            if (group.topics && group.topics.length > 0) {
                const topicsUl = document.createElement('ul');
                topicsUl.className = 'list-unstyled ml-3 mt-1';
                topicsUl.style.fontSize = '0.8rem';
                
                group.topics.forEach(function(topic) {
                    const topicLi = document.createElement('li');
                    topicLi.className = 'mb-1';
                    topicLi.innerHTML = `<i class="fas fa-comment text-muted" style="font-size: 0.6rem;"></i> ${escapeHtml(topic.title)}`;
                    topicsUl.appendChild(topicLi);
                });
                
                li.appendChild(topicsUl);
            }
            
            groupsList.appendChild(li);
        });
    } else {
        groupsList.innerHTML = '<li class="text-muted">Aucun groupe trouvé pour ce club</li>';
    }
}

// Fonction pour filtrer les événements par club
function filterEventsByClub(clubId, clubName) {
    const eventsList = document.getElementById('events-list');
    const eventsCount = document.getElementById('events-count');
    const eventsTitle = document.getElementById('events-title');
    const resetBtn = document.getElementById('reset-events-btn');
    
    if (!eventsList) return;
    
    // Récupérer les événements de ce club
    const filteredEvents = window.eventsByClub[clubId] || [];
    
    // Mettre à jour le titre et le compteur
    eventsTitle.textContent = `Événements de ${clubName}`;
    eventsCount.textContent = filteredEvents.length;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    eventsList.innerHTML = '';
    
    // Ajouter les événements
    if (filteredEvents.length > 0) {
        filteredEvents.forEach(function(event) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            
            let eventHtml = `<i class="fas fa-calendar text-warning" style="font-size: 0.6rem;"></i> ${escapeHtml(event.title)}`;
            
            if (event.date) {
                const eventDate = new Date(event.date);
                if (!isNaN(eventDate.getTime())) {
                    const formattedDate = eventDate.toLocaleDateString('fr-FR');
                    eventHtml += ` <span class="text-muted" style="font-size: 0.75rem;">(${formattedDate})</span>`;
                }
            }
            
            li.innerHTML = eventHtml;
            eventsList.appendChild(li);
        });
    } else {
        eventsList.innerHTML = '<li class="text-muted">Aucun événement trouvé pour ce club</li>';
    }
}

// Fonction pour filtrer les groupes par comité
function filterGroupsByCommittee(committeeId, committeeName) {
    const groupsList = document.getElementById('groups-topics-list');
    const groupsCount = document.getElementById('groups-count');
    const topicsCount = document.getElementById('topics-count');
    const groupsTitle = document.getElementById('groups-title');
    const resetBtn = document.getElementById('reset-groups-btn');
    
    if (!groupsList) return;
    
    // Récupérer tous les clubs de ce comité
    const clubs = window.clubsByCommittee[committeeId] || [];
    const clubIds = clubs.map(club => club.id);
    
    // Filtrer les groupes par ces clubs
    let filteredGroups = [];
    let totalTopics = 0;
    clubIds.forEach(function(clubId) {
        const groupsInClub = window.groupsByClub[clubId] || [];
        filteredGroups = filteredGroups.concat(groupsInClub);
        groupsInClub.forEach(group => {
            totalTopics += (group.topics || []).length;
        });
    });
    
    // Mettre à jour le titre et le compteur
    groupsTitle.textContent = `Groupes / Sujets de ${committeeName}`;
    groupsCount.textContent = filteredGroups.length;
    topicsCount.textContent = totalTopics;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    groupsList.innerHTML = '';
    
    // Ajouter les groupes et sujets
    if (filteredGroups.length > 0) {
        filteredGroups.forEach(function(group) {
            const li = document.createElement('li');
            li.className = 'mb-2';
            
            const groupDiv = document.createElement('div');
            groupDiv.className = 'font-weight-bold';
            const topicsCount = group.topics ? group.topics.length : 0;
            groupDiv.innerHTML = `<i class="fas fa-folder text-info" style="font-size: 0.7rem;"></i> ${escapeHtml(group.name)} <span class="text-muted" style="font-size: 0.8rem; font-weight: normal;">(${topicsCount} sujet${topicsCount > 1 ? 's' : ''})</span>`;
            li.appendChild(groupDiv);
            
            // Ajouter les sujets s'il y en a
            if (group.topics && group.topics.length > 0) {
                const topicsUl = document.createElement('ul');
                topicsUl.className = 'list-unstyled ml-3 mt-1';
                topicsUl.style.fontSize = '0.8rem';
                
                group.topics.forEach(function(topic) {
                    const topicLi = document.createElement('li');
                    topicLi.className = 'mb-1';
                    topicLi.innerHTML = `<i class="fas fa-comment text-muted" style="font-size: 0.6rem;"></i> ${escapeHtml(topic.title)}`;
                    topicsUl.appendChild(topicLi);
                });
                
                li.appendChild(topicsUl);
            }
            
            groupsList.appendChild(li);
        });
    } else {
        groupsList.innerHTML = '<li class="text-muted">Aucun groupe trouvé pour ce comité</li>';
    }
}

// Fonction pour filtrer les événements par comité
function filterEventsByCommittee(committeeId, committeeName) {
    const eventsList = document.getElementById('events-list');
    const eventsCount = document.getElementById('events-count');
    const eventsTitle = document.getElementById('events-title');
    const resetBtn = document.getElementById('reset-events-btn');
    
    if (!eventsList) return;
    
    // Récupérer tous les clubs de ce comité
    const clubs = window.clubsByCommittee[committeeId] || [];
    const clubIds = clubs.map(club => club.id);
    
    // Filtrer les événements par ces clubs
    let filteredEvents = [];
    clubIds.forEach(function(clubId) {
        const eventsInClub = window.eventsByClub[clubId] || [];
        filteredEvents = filteredEvents.concat(eventsInClub);
    });
    
    // Mettre à jour le titre et le compteur
    eventsTitle.textContent = `Événements de ${committeeName}`;
    eventsCount.textContent = filteredEvents.length;
    
    // Afficher le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'inline-block';
    
    // Vider la liste
    eventsList.innerHTML = '';
    
    // Ajouter les événements
    if (filteredEvents.length > 0) {
        filteredEvents.forEach(function(event) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            
            let eventHtml = `<i class="fas fa-calendar text-warning" style="font-size: 0.6rem;"></i> ${escapeHtml(event.title)}`;
            
            if (event.date) {
                const eventDate = new Date(event.date);
                if (!isNaN(eventDate.getTime())) {
                    const formattedDate = eventDate.toLocaleDateString('fr-FR');
                    eventHtml += ` <span class="text-muted" style="font-size: 0.75rem;">(${formattedDate})</span>`;
                }
            }
            
            li.innerHTML = eventHtml;
            eventsList.appendChild(li);
        });
    } else {
        eventsList.innerHTML = '<li class="text-muted">Aucun événement trouvé pour ce comité</li>';
    }
}

// Fonction pour réinitialiser l'affichage des groupes
function resetGroupsDisplay() {
    const groupsList = document.getElementById('groups-topics-list');
    const groupsCount = document.getElementById('groups-count');
    const topicsCount = document.getElementById('topics-count');
    const groupsTitle = document.getElementById('groups-title');
    const resetBtn = document.getElementById('reset-groups-btn');
    
    // Réinitialiser les valeurs
    groupsTitle.textContent = 'Groupes / Sujets';
    groupsCount.textContent = window.totalGroups || 0;
    topicsCount.textContent = window.totalTopics || 0;
    
    // Masquer le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'none';
    
    // Réafficher tous les groupes
    groupsList.innerHTML = '';
    const allGroups = window.allGroups || [];
    
    if (allGroups.length > 0) {
        allGroups.forEach(function(group) {
            const li = document.createElement('li');
            li.className = 'mb-2';
            
            const groupDiv = document.createElement('div');
            groupDiv.className = 'font-weight-bold';
            const topicsCount = group.topics ? group.topics.length : 0;
            groupDiv.innerHTML = `<i class="fas fa-folder text-info" style="font-size: 0.7rem;"></i> ${escapeHtml(group.name)} <span class="text-muted" style="font-size: 0.8rem; font-weight: normal;">(${topicsCount} sujet${topicsCount > 1 ? 's' : ''})</span>`;
            li.appendChild(groupDiv);
            
            // Ajouter les sujets s'il y en a
            if (group.topics && group.topics.length > 0) {
                const topicsUl = document.createElement('ul');
                topicsUl.className = 'list-unstyled ml-3 mt-1';
                topicsUl.style.fontSize = '0.8rem';
                
                group.topics.forEach(function(topic) {
                    const topicLi = document.createElement('li');
                    topicLi.className = 'mb-1';
                    topicLi.innerHTML = `<i class="fas fa-comment text-muted" style="font-size: 0.6rem;"></i> ${escapeHtml(topic.title)}`;
                    topicsUl.appendChild(topicLi);
                });
                
                li.appendChild(topicsUl);
            }
            
            groupsList.appendChild(li);
        });
    } else {
        groupsList.innerHTML = '<li class="text-muted">Aucun groupe</li>';
    }
}

// Fonction pour réinitialiser l'affichage des événements
function resetEventsDisplay() {
    const eventsList = document.getElementById('events-list');
    const eventsCount = document.getElementById('events-count');
    const eventsTitle = document.getElementById('events-title');
    const resetBtn = document.getElementById('reset-events-btn');
    
    // Réinitialiser les valeurs
    eventsTitle.textContent = 'Total Événements';
    eventsCount.textContent = window.totalEvents || 0;
    
    // Masquer le bouton de réinitialisation
    if (resetBtn) resetBtn.style.display = 'none';
    
    // Réafficher tous les événements
    eventsList.innerHTML = '';
    const allEvents = window.allEvents || [];
    
    if (allEvents.length > 0) {
        allEvents.forEach(function(event) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            
            let eventHtml = `<i class="fas fa-calendar text-warning" style="font-size: 0.6rem;"></i> ${escapeHtml(event.title)}`;
            
            if (event.date) {
                const eventDate = new Date(event.date);
                if (!isNaN(eventDate.getTime())) {
                    const formattedDate = eventDate.toLocaleDateString('fr-FR');
                    eventHtml += ` <span class="text-muted" style="font-size: 0.75rem;">(${formattedDate})</span>`;
                }
            }
            
            li.innerHTML = eventHtml;
            eventsList.appendChild(li);
        });
    } else {
        eventsList.innerHTML = '<li class="text-muted">Aucun événement</li>';
    }
}

// Gestionnaire d'événements pour les boutons de réinitialisation
document.getElementById('reset-groups-btn')?.addEventListener('click', function() {
    resetGroupsDisplay();
});

document.getElementById('reset-events-btn')?.addEventListener('click', function() {
    resetEventsDisplay();
});

// Fonction pour filtrer les comités départementaux selon le comité régional sélectionné
function filterDepartmentalCommitteesByRegional(regionalCommitteeId) {
    // Trouver le conteneur des comités départementaux
    const departmentalContainer = document.querySelector('.col-md-4:nth-child(2) .club-list ul');
    
    if (!departmentalContainer) {
        return;
    }
    
    // Extraire les 2 premiers caractères du comité régional (ex: '13' pour '1300000')
    const regionalPrefix = regionalCommitteeId ? regionalCommitteeId.substring(0, 2) : '';
    
    if (!regionalPrefix) {
        return;
    }
    
    // Parcourir tous les éléments de comités départementaux
    const departmentalItems = departmentalContainer.querySelectorAll('.committee-item');
    
    departmentalItems.forEach(function(item) {
        const committeeId = item.getAttribute('data-committee-id');
        
        // Un comité départemental se termine par '000' mais pas '00000' (ex: 1301000)
        const isDepartmental = committeeId && 
                               committeeId.length >= 7 && 
                               committeeId.substring(committeeId.length - 3) === '000' &&
                               committeeId.substring(committeeId.length - 5) !== '00000';
        
        if (isDepartmental) {
            // Vérifier si le comité départemental appartient à cette région
            const departmentalPrefix = committeeId.substring(0, 2);
            if (departmentalPrefix === regionalPrefix) {
                // Afficher le comité départemental
                item.style.display = '';
            } else {
                // Masquer le comité départemental
                item.style.display = 'none';
            }
        }
    });
}

// Fonction pour réinitialiser le filtre des comités départementaux
function resetDepartmentalCommitteesFilter() {
    const departmentalContainer = document.querySelector('.col-md-4:nth-child(2) .club-list ul');
    
    if (!departmentalContainer) {
        return;
    }
    
    const departmentalItems = departmentalContainer.querySelectorAll('.committee-item');
    
    departmentalItems.forEach(function(item) {
        item.style.display = '';
    });
}