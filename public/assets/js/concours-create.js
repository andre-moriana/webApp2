// Script pour le formulaire de création de concours

document.addEventListener('DOMContentLoaded', function() {
    loadClubs();
    loadDisciplines();
    loadNiveauChampionnat();
    // loadTypeCompetitions() sera appelée quand une discipline est sélectionnée
    loadTypePublications();
    
    // Gestion du club organisateur
    setupClubOrganisateur();
    
    // Gestion du changement de discipline pour charger les types de compétition
    setupDisciplineChange();
    
    // Gestion de la soumission du formulaire
    const form = document.getElementById('concoursForm');
    if (form) {
        console.log('Formulaire concoursForm trouvé - Action:', form.action);
        console.log('Formulaire concoursForm - Method:', form.method);
        console.log('Formulaire concoursForm - ID:', form.id);
        
        // Ajouter un listener avec capture pour s'assurer qu'on intercepte tôt
        form.addEventListener('submit', function(e) {
            console.log('=== SOUMISSION DU FORMULAIRE ===');
            console.log('Event target:', e.target);
            console.log('Event currentTarget:', e.currentTarget);
            console.log('Action:', form.action);
            console.log('Method:', form.method);
            console.log('Form action URL:', new URL(form.action, window.location.origin).href);
            
            // Vérifier que tous les champs requis sont remplis
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            const emptyFields = [];
            requiredFields.forEach(function(field) {
                if (!field.value || field.value.trim() === '') {
                    console.error('Champ requis vide:', field.name, field.id);
                    emptyFields.push(field.name || field.id || 'champ inconnu');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                console.error('Formulaire invalide - soumission annulée. Champs vides:', emptyFields);
                e.preventDefault();
                e.stopPropagation();
                alert('Veuillez remplir tous les champs requis: ' + emptyFields.join(', '));
                return false;
            }
            
            console.log('Formulaire valide - soumission normale vers:', form.action);
            console.log('Tous les champs requis sont remplis - soumission autorisée');
            
            // Log de tous les champs du formulaire avant soumission
            const formData = new FormData(form);
            console.log('=== DONNÉES DU FORMULAIRE ===');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            console.log('=== FIN DONNÉES ===');
            
            // Vérifier l'URL finale
            const finalUrl = new URL(form.action, window.location.origin);
            console.log('URL finale de soumission:', finalUrl.href);
            console.log('Pathname:', finalUrl.pathname);
            
            // Ne PAS appeler preventDefault() - laisser le formulaire se soumettre normalement
        }, true); // Utiliser capture phase
        
        // Test: vérifier si le formulaire peut être soumis manuellement
        console.log('Test: form.submit existe?', typeof form.submit === 'function');
    } else {
        console.error('Formulaire concoursForm non trouvé!');
    }
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

// Charger les niveaux de championnat depuis les données passées par PHP
function loadNiveauChampionnat() {
    console.log('=== loadNiveauChampionnat() appelée ===');
    console.log('window.niveauChampionnatData:', window.niveauChampionnatData);
    
    const select = document.getElementById('niveau_championnat');
    if (!select) {
        console.error('ERREUR: Select niveau_championnat non trouvé dans le DOM');
        return;
    }
    
    // Utiliser les données passées depuis PHP
    const niveaux = window.niveauChampionnatData || [];
    
    console.log('Niveaux reçus depuis PHP:', niveaux);
    console.log('Nombre de niveaux:', niveaux.length);
    
    if (!Array.isArray(niveaux)) {
        console.error('ERREUR: window.niveauChampionnatData n\'est pas un tableau:', typeof niveaux);
        return;
    }
    
    if (niveaux.length === 0) {
        console.warn('ATTENTION: Aucun niveau disponible dans window.niveauChampionnatData');
        return;
    }
    
    // Vider le select (garder seulement l'option par défaut)
    select.innerHTML = '<option value="">-- Sélectionner --</option>';
    
    let addedCount = 0;
    niveaux.forEach(function(niveau, index) {
        try {
            const option = document.createElement('option');
            // Utiliser abv_niveauchampionnat comme valeur (abréviation) ou idniveau_championnat
            const value = niveau.abv_niveauchampionnat || niveau.idniveau_championnat || niveau.id || '';
            const text = niveau.lb_niveauchampionnat || niveau.name || niveau.nom || 'Niveau';
            
            option.value = value;
            option.textContent = text;
            select.appendChild(option);
            addedCount++;
        } catch (error) {
            console.error('Erreur lors de l\'ajout du niveau', index, ':', error);
        }
    });
    
    console.log('Niveaux de championnat ajoutés au select:', addedCount);
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
        const select = document.getElementById('type_publication_internet');
        if (!select) {
            console.warn('Select type_publication_internet non trouvé');
            return;
        }
        
        // Utiliser l'endpoint direct type-publications
        const response = await fetch('/api/type-publications', {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include' // Inclure les cookies pour l'authentification
        });
        
        // Vérifier le Content-Type avant de parser JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Réponse non-JSON reçue pour type-publications:', contentType);
            return;
        }
        
        if (!response.ok) {
            console.warn('Erreur HTTP lors du chargement des types de publication:', response.status);
            return;
        }
        
        const data = await response.json();
        
        // Extraire les données (format { success: true, data: [...] })
        const types = data.success && data.data ? data.data : (Array.isArray(data) ? data : []);
        
        if (types.length > 0) {
            types.forEach(type => {
                const option = document.createElement('option');
                option.value = type.code || type.id || type._id || type;
                option.textContent = type.name || type.nom || type;
                select.appendChild(option);
            });
            console.log('Types de publication chargés:', types.length);
        } else {
            console.warn('Aucun type de publication trouvé');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des types de publication:', error);
        // Ne pas bloquer l'application si cette fonction échoue
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
