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
        });
    });
    
    // Gérer le bouton de réinitialisation
    document.getElementById('reset-clubs-btn')?.addEventListener('click', function() {
        resetClubsDisplay();
    });
});

function displayClubsForCommittee(committeeId, committeeName) {
    const clubsList = document.getElementById('clubs-list');
    const clubsContainer = document.getElementById('clubs-list-container');
    const clubsCount = document.getElementById('clubs-count');
    const clubsTitle = document.getElementById('clubs-title');
    
    if (!clubsList || !clubsContainer) return;
    
    const clubs = window.clubsByCommittee[committeeId] || [];
    
    // Mettre à jour le titre et le compteur
    clubsTitle.textContent = `Clubs de ${committeeName}`;
    clubsCount.textContent = clubs.length;
    
    // Vider la liste
    clubsList.innerHTML = '';
    
    // Ajouter les clubs
    if (clubs.length > 0) {
        clubs.forEach(function(club) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            li.innerHTML = `<i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> ${escapeHtml(club.name)}`;
            clubsList.appendChild(li);
        });
        clubsContainer.style.display = 'block';
    } else {
        clubsList.innerHTML = '<li class="text-muted">Aucun club trouvé pour ce comité</li>';
        clubsContainer.style.display = 'block';
    }
}

function resetClubsDisplay() {
    const clubsList = document.getElementById('clubs-list');
    const clubsContainer = document.getElementById('clubs-list-container');
    const clubsCount = document.getElementById('clubs-count');
    const clubsTitle = document.getElementById('clubs-title');
    
    // Réinitialiser les valeurs
    clubsTitle.textContent = 'Total Clubs';
    clubsCount.textContent = window.totalClubs || 0;
    clubsContainer.style.display = 'none';
    clubsList.innerHTML = '';
    
    // Retirer les sélections
    document.querySelectorAll('.committee-item').forEach(function(el) {
        el.classList.remove('selected');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
