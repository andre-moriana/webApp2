// Script pour le formulaire de création de concours

document.addEventListener('DOMContentLoaded', function() {
    loadClubs();
    loadDisciplines();
    loadTypeCompetitions();
    loadTypePublications();
    
    // Gestion du club organisateur
    setupClubOrganisateur();
});

// Charger les clubs depuis les données passées par PHP
function loadClubs() {
    const select = document.getElementById('club_organisateur');
    if (!select) {
        console.error('Select club_organisateur non trouvé');
        return;
    }
    
    // Utiliser les données passées depuis PHP
    const clubs = window.clubsData || [];
    
    console.log('Clubs reçus depuis PHP:', clubs.length);
    
    if (clubs.length === 0) {
        console.warn('Aucun club disponible');
        return;
    }
    
    // Vider le select (garder seulement l'option par défaut)
    select.innerHTML = '<option value="">-- Sélectionner un club --</option>';
    
    clubs.forEach(club => {
        const option = document.createElement('option');
        option.value = club.id || club._id || '';
        const clubName = club.name || club.nameShort || 'Club';
        const clubShort = club.nameShort || club.name_short || '';
        option.textContent = clubName + (clubShort ? ' (' + clubShort + ')' : '');
        option.dataset.code = clubShort;
        option.dataset.name = clubName;
        select.appendChild(option);
    });
    
    console.log('Clubs ajoutés au select:', select.options.length - 1);
}

// Charger les disciplines depuis l'API
async function loadDisciplines() {
    try {
        // TODO: Remplacer par l'endpoint réel de l'API
        const response = await fetch('/api/disciplines');
        const data = await response.json();
        
        const select = document.getElementById('discipline');
        if (select && data) {
            const disciplines = Array.isArray(data) ? data : (data.data || []);
            disciplines.forEach(discipline => {
                const option = document.createElement('option');
                option.value = discipline.id || discipline._id || discipline;
                option.textContent = discipline.name || discipline.nom || discipline;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des disciplines:', error);
        // Valeurs par défaut si l'API n'existe pas encore
        const select = document.getElementById('discipline');
        if (select) {
            const defaultDisciplines = ['Arc Classique', 'Arc à Poulies', 'Arc Droit', 'Arc Nu'];
            defaultDisciplines.forEach(discipline => {
                const option = document.createElement('option');
                option.value = discipline.toLowerCase().replace(/\s+/g, '_');
                option.textContent = discipline;
                select.appendChild(option);
            });
        }
    }
}

// Charger les types de compétition depuis l'API
async function loadTypeCompetitions() {
    try {
        // TODO: Remplacer par l'endpoint réel de l'API
        const response = await fetch('/api/type-competitions');
        const data = await response.json();
        
        const select = document.getElementById('type_competition');
        if (select && data) {
            const types = Array.isArray(data) ? data : (data.data || []);
            types.forEach(type => {
                const option = document.createElement('option');
                option.value = type.id || type._id || type;
                option.textContent = type.name || type.nom || type;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des types de compétition:', error);
        // Valeurs par défaut si l'API n'existe pas encore
        const select = document.getElementById('type_competition');
        if (select) {
            const defaultTypes = ['Indoor', 'Outdoor', 'Field', '3D'];
            defaultTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type.toLowerCase();
                option.textContent = type;
                select.appendChild(option);
            });
        }
    }
}

// Charger les types de publication depuis l'API
async function loadTypePublications() {
    try {
        // TODO: Remplacer par l'endpoint réel de l'API
        const response = await fetch('/api/type-publications');
        const data = await response.json();
        
        const select = document.getElementById('type_publication_internet');
        if (select && data) {
            const types = Array.isArray(data) ? data : (data.data || []);
            types.forEach(type => {
                const option = document.createElement('option');
                option.value = type.id || type._id || type;
                option.textContent = type.name || type.nom || type;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des types de publication:', error);
    }
}

// Configuration du champ club organisateur
function setupClubOrganisateur() {
    const clubSelect = document.getElementById('club_organisateur');
    const clubCode = document.getElementById('club_code');
    const clubNameDisplay = document.getElementById('club_name_display');
    const clubSearch = document.getElementById('club_search');
    
    if (clubSelect && clubCode && clubNameDisplay) {
        // Mettre à jour le code et le nom quand un club est sélectionné
        clubSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                clubCode.value = selectedOption.dataset.code || '';
                clubNameDisplay.value = selectedOption.dataset.name || '';
            } else {
                clubCode.value = '';
                clubNameDisplay.value = '';
            }
        });
    }
    
    // Recherche de club (filtrage)
    if (clubSearch && clubSelect) {
        clubSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = clubSelect.options;
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const text = option.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    }
}
