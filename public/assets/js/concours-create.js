// Script pour le formulaire de création de concours

document.addEventListener('DOMContentLoaded', function() {
    loadClubs();
    loadDisciplines();
    // loadTypeCompetitions() sera appelée quand une discipline est sélectionnée
    loadTypePublications();
    
    // Gestion du club organisateur
    setupClubOrganisateur();
    
    // Gestion du changement de discipline pour charger les types de compétition
    setupDisciplineChange();
});

// Configuration du changement de discipline
function setupDisciplineChange() {
    const disciplineSelect = document.getElementById('discipline');
    if (disciplineSelect) {
        disciplineSelect.addEventListener('change', function() {
            const selectedDisciplineId = this.value;
            console.log('Discipline changée:', selectedDisciplineId);
            loadTypeCompetitions(selectedDisciplineId);
        });
    }
}

// Charger les clubs depuis les données passées par PHP
function loadClubs() {
    console.log('loadClubs() appelée');
    console.log('window.clubsData:', window.clubsData);
    
    const select = document.getElementById('club_organisateur');
    if (!select) {
        console.error('Select club_organisateur non trouvé');
        return;
    }
    
    // Utiliser les données passées depuis PHP
    const clubs = window.clubsData || [];
    
    console.log('Clubs reçus depuis PHP:', clubs);
    console.log('Nombre de clubs:', clubs.length);
    
    if (!Array.isArray(clubs)) {
        console.error('window.clubsData n\'est pas un tableau:', typeof clubs);
        return;
    }
    
    if (clubs.length === 0) {
        console.warn('Aucun club disponible dans window.clubsData');
        return;
    }
    
    // Vider le select (garder seulement l'option par défaut)
    select.innerHTML = '<option value="">-- Sélectionner un club --</option>';
    
    let addedCount = 0;
    clubs.forEach((club, index) => {
        try {
            const option = document.createElement('option');
            option.value = club.id || club._id || '';
            const clubName = club.name || club.nameShort || 'Club';
            const clubShort = club.nameShort || club.name_short || '';
            option.textContent = clubName + (clubShort ? ' (' + clubShort + ')' : '');
            option.dataset.code = clubShort;
            option.dataset.name = clubName;
            select.appendChild(option);
            addedCount++;
        } catch (error) {
            console.error('Erreur lors de l\'ajout du club', index, ':', error, club);
        }
    });
    
    console.log('Clubs ajoutés au select:', addedCount, '/', clubs.length);
}

// Charger les disciplines depuis les données passées par PHP
function loadDisciplines() {
    console.log('=== loadDisciplines() appelée ===');
    console.log('window.disciplinesData:', window.disciplinesData);
    console.log('Type de window.disciplinesData:', typeof window.disciplinesData);
    console.log('Est un tableau?', Array.isArray(window.disciplinesData));
    
    const select = document.getElementById('discipline');
    if (!select) {
        console.error('ERREUR: Select discipline non trouvé dans le DOM');
        return;
    }
    console.log('Select discipline trouvé:', select);
    
    // Utiliser les données passées depuis PHP
    const disciplines = window.disciplinesData || [];
    
    console.log('Disciplines après || []:', disciplines);
    console.log('Nombre de disciplines:', disciplines.length);
    console.log('Est un tableau?', Array.isArray(disciplines));
    
    if (!Array.isArray(disciplines)) {
        console.error('ERREUR: window.disciplinesData n\'est pas un tableau:', typeof disciplines);
        console.error('Valeur complète:', JSON.stringify(disciplines, null, 2));
        return;
    }
    
    if (disciplines.length === 0) {
        console.warn('ATTENTION: Aucune discipline disponible dans window.disciplinesData');
        console.warn('Vérifiez que la table concour_discipline existe et contient des données');
        return;
    }
    
    // Vider le select (garder seulement l'option par défaut)
    select.innerHTML = '<option value="">-- Sélectionner une discipline --</option>';
    console.log('Select vidé, ajout des options...');
    
    let addedCount = 0;
    let errorCount = 0;
    disciplines.forEach((discipline, index) => {
        try {
            console.log(`Traitement discipline ${index}:`, discipline);
            const option = document.createElement('option');
            // Utiliser iddiscipline comme valeur (ID original) ou id (ID de la table)
            const value = discipline.iddiscipline || discipline.id || '';
            const text = discipline.lb_discipline || discipline.name || discipline.nom || 'Discipline';
            
            option.value = value;
            option.textContent = text;
            
            console.log(`  -> Option créée: value="${value}", text="${text}"`);
            select.appendChild(option);
            addedCount++;
        } catch (error) {
            errorCount++;
            console.error(`ERREUR lors de l'ajout de la discipline ${index}:`, error);
            console.error('Discipline problématique:', discipline);
        }
    });
    
    console.log('=== Résumé ===');
    console.log('Disciplines ajoutées au select:', addedCount, '/', disciplines.length);
    console.log('Erreurs:', errorCount);
    console.log('Options finales dans le select:', select.options.length);
    
    if (addedCount === 0 && disciplines.length > 0) {
        console.error('PROBLÈME: Aucune discipline n\'a pu être ajoutée malgré', disciplines.length, 'disciplines disponibles');
    }
}

// Charger les types de compétition depuis les données passées par PHP
// Cette fonction sera appelée quand une discipline est sélectionnée
function loadTypeCompetitions(iddiscipline) {
    console.log('=== loadTypeCompetitions() appelée ===');
    console.log('ID Discipline sélectionnée:', iddiscipline);
    console.log('window.typeCompetitionsData:', window.typeCompetitionsData);
    
    const select = document.getElementById('type_competition');
    if (!select) {
        console.error('ERREUR: Select type_competition non trouvé dans le DOM');
        return;
    }
    
    // Vider le select (garder seulement l'option par défaut)
    select.innerHTML = '<option value="">-- Sélectionner un type --</option>';
    
    if (!iddiscipline || iddiscipline === '') {
        console.log('Aucune discipline sélectionnée, types de compétition non chargés');
        return;
    }
    
    // Utiliser les données passées depuis PHP
    const allTypeCompetitions = window.typeCompetitionsData || [];
    
    // Filtrer par discipline
    const filteredTypes = allTypeCompetitions.filter(function(typeComp) {
        return typeComp.iddiscipline == iddiscipline;
    });
    
    console.log('Types de compétition filtrés pour discipline', iddiscipline, ':', filteredTypes.length);
    
    if (filteredTypes.length === 0) {
        console.warn('Aucun type de compétition trouvé pour la discipline', iddiscipline);
        return;
    }
    
    // Trier par nb_ordre
    filteredTypes.sort(function(a, b) {
        return (a.nb_ordre || 0) - (b.nb_ordre || 0);
    });
    
    let addedCount = 0;
    filteredTypes.forEach(function(typeComp, index) {
        try {
            const option = document.createElement('option');
            option.value = typeComp.idformat_competition || typeComp.id || '';
            option.textContent = typeComp.lb_format_competition || typeComp.name || typeComp.nom || 'Type';
            select.appendChild(option);
            addedCount++;
        } catch (error) {
            console.error('Erreur lors de l\'ajout du type de compétition', index, ':', error);
        }
    });
    
    console.log('Types de compétition ajoutés au select:', addedCount);
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
