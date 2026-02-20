// Script pour le formulaire de création et d'édition de concours

function ensureHiddenField(form, name, value) {
    let input = form.querySelector('input[name="' + name + '"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value != null ? String(value) : '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on est sur la page edit (les selects sont déjà remplis en PHP)
    const clubSelect = document.getElementById('club_organisateur');
    const disciplineSelect = document.getElementById('discipline');
    const isEditPage = clubSelect && clubSelect.options.length > 1; // Plus que l'option par défaut
    
    if (!isEditPage) {
        // Page create : charger les selects dynamiquement
        loadClubs();
        loadDisciplines();
        loadNiveauChampionnat();
    } else {
        // Page edit : les selects sont déjà remplis en PHP
        console.log('Page edit détectée - selects déjà remplis');
        
        // Charger les types de compétition si une discipline est déjà sélectionnée
        const selectedDiscipline = disciplineSelect ? disciplineSelect.value : null;
        if (selectedDiscipline) {
            console.log('Discipline déjà sélectionnée:', selectedDiscipline);
            // Charger les types de compétition pour cette discipline
            // La fonction loadTypeCompetitions gère déjà la sauvegarde et restauration de la valeur
            loadTypeCompetitions(selectedDiscipline);
            // Mettre à jour les labels selon le type de discipline
            updateLabelsForDiscipline(selectedDiscipline);
        }
        
        // Mettre à jour les champs club_code et club_name_display si un club est sélectionné
        if (clubSelect && clubSelect.value) {
            const selectedOption = clubSelect.options[clubSelect.selectedIndex];
            if (selectedOption) {
                const clubCodeInput = document.getElementById('club_code');
                const clubNameDisplay = document.getElementById('club_name_display');
                if (clubCodeInput && selectedOption.dataset.code) {
                    clubCodeInput.value = selectedOption.dataset.code;
                }
                if (clubNameDisplay && selectedOption.dataset.name) {
                    clubNameDisplay.value = selectedOption.dataset.name;
                }
            }
        }
    }
    
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
            
            // Renseigner les champs derives avant soumission
            const clubSelectForSubmit = document.getElementById('club_organisateur');
            const clubCodeInput = document.getElementById('club_code');
            if (clubSelectForSubmit && clubCodeInput && clubSelectForSubmit.value) {
                const clubOption = clubSelectForSubmit.options[clubSelectForSubmit.selectedIndex];
                const clubCode = clubOption && clubOption.dataset ? clubOption.dataset.code : '';
                if (clubCode) {
                    clubCodeInput.value = clubCode;
                }
            }

            const niveauSelect = document.getElementById('idniveau_championnat');
            if (niveauSelect && niveauSelect.value) {
                niveauSelect.value = String(niveauSelect.value);
            }

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
// Fonction helper pour déterminer si c'est un concours de type nature 3D ou campagne
function isNature3DOrCampagne(disciplineId) {
    if (!disciplineId || !window.disciplinesData || !Array.isArray(window.disciplinesData)) {
        return false;
    }
    const discipline = window.disciplinesData.find(function(d) {
        const id = d.iddiscipline || d.id;
        return id == disciplineId || String(id) === String(disciplineId);
    });
    if (!discipline) {
        return false;
    }
    const name = (discipline.lb_discipline || discipline.name || '').toLowerCase();
    return name.includes('nature') || name.includes('3d') || name.includes('campagne');
}

// Fonction pour mettre à jour les labels selon le type de concours
function updateLabelsForDiscipline(disciplineId) {
    const isNature = isNature3DOrCampagne(disciplineId);
    
    // Mettre à jour le label "Nombre cibles" / "Nombre pelotons"
    const labelCibles = document.getElementById('label_nombre_cibles');
    if (labelCibles) {
        const input = document.getElementById('nombre_cibles');
        if (input) {
            // Sauvegarder la valeur et les attributs de l'input
            const value = input.value;
            const min = input.getAttribute('min');
            const required = input.hasAttribute('required');
            
            // Remplacer le contenu du label
            const labelText = (isNature ? 'Nombre pelotons' : 'Nombre cibles') + ' :';
            const newInput = document.createElement('input');
            newInput.type = 'number';
            newInput.name = 'nombre_cibles';
            newInput.id = 'nombre_cibles';
            newInput.value = value;
            if (min) newInput.setAttribute('min', min);
            if (required) newInput.setAttribute('required', 'required');
            
            labelCibles.textContent = labelText + ' ';
            labelCibles.appendChild(newInput);
        }
    }
    
    // Mettre à jour le label "Nombre tireurs par cibles" / "Nombre tireurs par peloton"
    const labelTireurs = document.getElementById('label_nombre_tireurs');
    if (labelTireurs) {
        const input = document.getElementById('nombre_tireurs_par_cibles');
        if (input) {
            // Sauvegarder la valeur et les attributs de l'input
            const value = input.value;
            const min = input.getAttribute('min');
            const required = input.hasAttribute('required');
            
            // Remplacer le contenu du label
            const labelText = (isNature ? 'Nombre tireurs par peloton' : 'Nombre tireurs par cibles') + ' :';
            const newInput = document.createElement('input');
            newInput.type = 'number';
            newInput.name = 'nombre_tireurs_par_cibles';
            newInput.id = 'nombre_tireurs_par_cibles';
            newInput.value = value;
            if (min) newInput.setAttribute('min', min);
            if (required) newInput.setAttribute('required', 'required');
            
            labelTireurs.textContent = labelText + ' ';
            labelTireurs.appendChild(newInput);
        }
    }
}

function setupDisciplineChange() {
    const disciplineSelect = document.getElementById('discipline');
    if (disciplineSelect) {
        disciplineSelect.addEventListener('change', function() {
            const selectedDisciplineId = this.value;
            console.log('Discipline changée:', selectedDisciplineId);
            loadTypeCompetitions(selectedDisciplineId);
            // Mettre à jour les labels selon le type de discipline
            updateLabelsForDiscipline(selectedDisciplineId);
        });
        
        // Mettre à jour les labels au chargement si une discipline est déjà sélectionnée
        if (disciplineSelect.value) {
            updateLabelsForDiscipline(disciplineSelect.value);
        }
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
    
    // Si le select a déjà des options (page edit), ne pas le recharger
    if (select.options.length > 1) {
        console.log('Select club_organisateur déjà rempli (page edit), pas de rechargement');
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
    
    // Si le select a déjà des options (page edit), ne pas le recharger
    if (select.options.length > 1) {
        console.log('Select discipline déjà rempli (page edit), pas de rechargement');
        return;
    }
    
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
    
    const select = document.getElementById('idniveau_championnat');
    if (!select) {
        console.error('ERREUR: Select idniveau_championnat non trouvé dans le DOM');
        return;
    }
    
    // Si le select a déjà des options (page edit), ne pas le recharger
    if (select.options.length > 1) {
        console.log('Select idniveau_championnat déjà rempli (page edit), pas de rechargement');
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
            // Utiliser l'ID numerique du niveau
            const value = niveau.idniveau_championnat || niveau.id || '';
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
    
    // Sauvegarder la valeur sélectionnée si elle existe (pour la page edit)
    const savedValue = select.value;
    const hasSelectedValue = savedValue && savedValue !== '';
    
    // Vider le select (garder seulement l'option par défaut)
    // Même sur la page edit, on recharge pour avoir tous les types de la discipline
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
    
    // Restaurer la valeur sauvegardée si elle existe (pour la page edit)
    if (hasSelectedValue && savedValue) {
        // Vérifier que l'option existe avant de la sélectionner
        const optionExists = Array.from(select.options).some(opt => {
            const optValue = String(opt.value);
            const savedValueStr = String(savedValue);
            return optValue === savedValueStr || opt.value == savedValue;
        });
        if (optionExists) {
            select.value = String(savedValue);
            console.log('✓ Valeur type_competition restaurée:', savedValue);
        } else {
            console.warn('✗ Option type_competition non trouvée pour la valeur:', savedValue);
        }
    }
}

// Charger les types de publication depuis l'API
async function loadTypePublications() {
    try {
        const select = document.getElementById('type_publication_internet');
        if (!select) {
            console.warn('Select type_publication_internet non trouvé');
            return;
        }
        
        // Endpoint type-publications (sous /api/concours/ comme les autres routes concours)
        const response = await fetch('/api/concours/type-publications', {
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

// Pré-remplir le formulaire avec les données du concours à éditer
function prefillFormForEdit() {
    if (!window.concoursData) {
        console.log('Aucune donnée de concours à pré-remplir');
        return;
    }
    
    const concours = window.concoursData;
    console.log('=== Pré-remplissage du formulaire avec les données du concours ===');
    console.log('Données du concours:', concours);
    
    // Club organisateur
    if (concours.club_organisateur) {
        const clubSelect = document.getElementById('club_organisateur');
        if (clubSelect) {
            console.log('Recherche du club_organisateur:', concours.club_organisateur);
            console.log('Options disponibles:', Array.from(clubSelect.options).map(opt => ({value: opt.value, text: opt.text})));
            // Vérifier que l'option existe (comparaison flexible avec ==)
            const optionExists = Array.from(clubSelect.options).some(opt => {
                const optValue = String(opt.value);
                const concoursValue = String(concours.club_organisateur);
                return optValue === concoursValue || opt.value == concours.club_organisateur;
            });
            if (optionExists) {
                clubSelect.value = String(concours.club_organisateur);
                console.log('✓ Club organisateur défini:', concours.club_organisateur);
                
                // Pré-remplir le code du club si disponible (agreenum)
                if (concours.agreenum) {
                    const clubCodeInput = document.getElementById('club_code');
                    if (clubCodeInput) {
                        clubCodeInput.value = String(concours.agreenum);
                        console.log('✓ Code club défini:', concours.agreenum);
                    }
                }
                
                // Déclencher l'événement change pour mettre à jour le code et le nom
                const event = new Event('change', { bubbles: true });
                clubSelect.dispatchEvent(event);
            } else {
                console.warn('✗ Option club_organisateur non trouvée pour la valeur:', concours.club_organisateur);
                console.warn('Valeurs disponibles:', Array.from(clubSelect.options).map(opt => opt.value));
            }
        }
    }
    
    // Discipline
    if (concours.discipline) {
        const disciplineSelect = document.getElementById('discipline');
        if (disciplineSelect) {
            console.log('Recherche de la discipline:', concours.discipline);
            console.log('Options disponibles:', Array.from(disciplineSelect.options).map(opt => ({value: opt.value, text: opt.text})));
            // Vérifier que l'option existe (comparaison flexible)
            const optionExists = Array.from(disciplineSelect.options).some(opt => {
                const optValue = String(opt.value);
                const concoursValue = String(concours.discipline);
                return optValue === concoursValue || opt.value == concours.discipline;
            });
            if (optionExists) {
                disciplineSelect.value = String(concours.discipline);
                console.log('✓ Discipline définie:', concours.discipline);
                // Déclencher le changement de discipline pour charger les types de compétition
                const event = new Event('change', { bubbles: true });
                disciplineSelect.dispatchEvent(event);
                
                // Type compétition (après que la discipline soit sélectionnée et les types chargés)
                if (concours.type_competition) {
                    setTimeout(function() {
                        const typeCompetitionSelect = document.getElementById('type_competition');
                        if (typeCompetitionSelect) {
                            console.log('Recherche du type_competition:', concours.type_competition);
                            console.log('Options disponibles:', Array.from(typeCompetitionSelect.options).map(opt => ({value: opt.value, text: opt.text})));
                            const typeOptionExists = Array.from(typeCompetitionSelect.options).some(opt => {
                                const optValue = String(opt.value);
                                const concoursValue = String(concours.type_competition);
                                return optValue === concoursValue || opt.value == concours.type_competition;
                            });
                            if (typeOptionExists) {
                                typeCompetitionSelect.value = String(concours.type_competition);
                                console.log('✓ Type compétition défini:', concours.type_competition);
                            } else {
                                console.warn('✗ Option type_competition non trouvée pour la valeur:', concours.type_competition);
                                console.warn('Valeurs disponibles:', Array.from(typeCompetitionSelect.options).map(opt => opt.value));
                            }
                        }
                    }, 1000);
                }
            } else {
                console.warn('✗ Option discipline non trouvée pour la valeur:', concours.discipline);
                console.warn('Valeurs disponibles:', Array.from(disciplineSelect.options).map(opt => opt.value));
            }
        }
    }
    
    // Niveau championnat
    if (concours.idniveau_championnat) {
        const niveauSelect = document.getElementById('idniveau_championnat');
        if (niveauSelect) {
            console.log('Recherche du idniveau_championnat:', concours.idniveau_championnat);
            console.log('Options disponibles:', Array.from(niveauSelect.options).map(opt => ({value: opt.value, text: opt.text})));
            const concoursValue = String(concours.idniveau_championnat);
            const optionExists = Array.from(niveauSelect.options).some(opt => {
                const optValue = String(opt.value);
                return optValue === concoursValue || opt.value == concours.idniveau_championnat;
            });
            if (optionExists) {
                niveauSelect.value = concoursValue;
                console.log('✓ idniveau_championnat défini:', concoursValue);
            } else {
                console.warn('✗ Option idniveau_championnat non trouvée pour la valeur:', concours.idniveau_championnat);
                console.warn('Valeurs disponibles:', Array.from(niveauSelect.options).map(opt => opt.value));
            }
        }
    }
    
    // Type concours (radio buttons)
    if (concours.type_concours) {
        const typeConcoursRadio = document.querySelector(`input[name="type_concours"][value="${concours.type_concours}"]`);
        if (typeConcoursRadio) {
            typeConcoursRadio.checked = true;
            console.log('Type concours défini:', concours.type_concours);
        }
    }
    
    // Duel (checkbox) - vérifier si c'est 1, true, ou "1"
    if (concours.duel == 1 || concours.duel === true || concours.duel === "1") {
        const duelCheckbox = document.querySelector('input[name="duel"]');
        if (duelCheckbox) {
            duelCheckbox.checked = true;
            console.log('Duel coché');
        }
    }
    
    // Division equipe (radio buttons)
    if (concours.division_equipe) {
        const divisionRadio = document.querySelector(`input[name="division_equipe"][value="${concours.division_equipe}"]`);
        if (divisionRadio) {
            divisionRadio.checked = true;
            console.log('Division equipe définie:', concours.division_equipe);
        }
    }
    
    // Type publication internet
    if (concours.type_publication_internet) {
        setTimeout(function() {
            const typePubSelect = document.getElementById('type_publication_internet');
            if (typePubSelect) {
                const optionExists = Array.from(typePubSelect.options).some(opt => opt.value == concours.type_publication_internet);
                if (optionExists) {
                    typePubSelect.value = concours.type_publication_internet;
                    console.log('Type publication internet défini:', concours.type_publication_internet);
                } else {
                    console.warn('Option type_publication_internet non trouvée pour la valeur:', concours.type_publication_internet);
                }
            }
        }, 1000);
    }
    
    // Les autres champs (titre, lieu, dates, nombres) sont déjà pré-remplis par PHP
    console.log('=== Fin du pré-remplissage du formulaire ===');
}
