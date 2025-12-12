// Cache pour le fichier XML chargé
let usersXmlCache = null;

// Fonction pour charger le fichier XML des utilisateurs
function loadUsersXml() {
    return new Promise((resolve, reject) => {
        // Si le XML est déjà en cache, le retourner directement
        if (usersXmlCache) {
            resolve(usersXmlCache);
            return;
        }
        
        // Charger le fichier XML depuis le dossier public/data
        fetch('/public/data/users-licences.xml')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Impossible de charger le fichier XML');
                }
                return response.text();
            })
            .then(xmlText => {
                // Parser le XML
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
                
                // Vérifier les erreurs de parsing
                const parserError = xmlDoc.querySelector('parsererror');
                if (parserError) {
                    throw new Error('Erreur de parsing XML: ' + parserError.textContent);
                }
                
                // Mettre en cache
                usersXmlCache = xmlDoc;
                resolve(xmlDoc);
            })
            .catch(error => {
                console.error('Erreur lors du chargement du XML:', error);
                reject(error);
            });
    });
}

// Fonction pour rechercher un utilisateur par numéro de licence dans le XML
function searchUserByLicence() {
    const licenceNumber = document.getElementById('licenceNumber').value.trim();
    const resultDiv = document.getElementById('licenceSearchResult');
    
    // Vider le message précédent
    resultDiv.innerHTML = '';
    
    if (!licenceNumber) {
        resultDiv.innerHTML = '<div class="alert alert-warning">Veuillez entrer un numéro de licence</div>';
        return;
    }
    
    // Afficher un indicateur de chargement
    resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Recherche en cours...</div>';
    
    // Désactiver le bouton pendant la recherche
    const searchButton = document.getElementById('searchByLicence');
    searchButton.disabled = true;
    
    // Charger le XML et rechercher
    loadUsersXml()
        .then(xmlDoc => {
            searchButton.disabled = false;
            
            // Rechercher l'utilisateur par numéro de licence dans la structure WINDEV_TABLE
            const users = xmlDoc.querySelectorAll('TABLE_CONTENU');
            let foundUser = null;
            
            for (let i = 0; i < users.length; i++) {
                const userNode = users[i];
                const licenceNode = userNode.querySelector('IDLicence');
                
                if (licenceNode && licenceNode.textContent.trim() === licenceNumber) {
                    // Convertir le nœud XML en objet JavaScript (format WINDEV_TABLE)
                    const nom = getNodeText(userNode, 'NOM');
                    const prenom = getNodeText(userNode, 'PRENOM');
                    const nomComplet = getNodeText(userNode, 'NOMCOMPLET');
                    
                    // Extraire nom et prénom depuis NOMCOMPLET si nécessaire
                    let lastName = nom;
                    let firstName = prenom;
                    if (!lastName && !firstName && nomComplet) {
                        const parts = nomComplet.split(' ', 2);
                        lastName = parts[0] || '';
                        firstName = parts[1] || '';
                    }
                    
                    // Convertir la date de naissance du format DD/MM/YYYY vers YYYY-MM-DD
                    let birthDate = getNodeText(userNode, 'DATENAISSANCE');
                    if (birthDate && birthDate.includes('/')) {
                        const parts = birthDate.split('/');
                        if (parts.length === 3) {
                            birthDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
                        }
                    }
                    
                    // Convertir le sexe (1 = H, 2 = F)
                    const sexe = getNodeText(userNode, 'SEXE');
                    let gender = '';
                    if (sexe === '1') gender = 'H';
                    else if (sexe === '2') gender = 'F';
                    
                    // Convertir le type d'arc (TYPARC)
                    const typArc = getNodeText(userNode, 'TYPARC');
                    let bowType = '';
                    const bowTypeMapping = {
                        '1': 'Arc Classique',
                        '2': 'Arc à poulies',
                        '3': 'Arc droit',
                        '4': 'Arc de chasse',
                        '5': 'Arc Nu',
                        '6': 'Arc Libre'
                    };
                    if (bowTypeMapping[typArc]) {
                        bowType = bowTypeMapping[typArc];
                    }
                    
                    // Convertir la catégorie d'âge (CATAGE) - mapping complet comme dans UserImportController
                    const catage = getNodeText(userNode, 'CATAGE');
                    let ageCategory = catage;
                    const ageCategoryMapping = {
                        '0': 'DECOUVERTE',
                        '2': 'U13 - BENJAMINS',
                        '3': 'U15 - MINIMES',
                        '4': 'U18 - CADETS',
                        '5': 'U21 - JUNIORS',
                        '8': 'U11 - POUSSINS',
                        '11': 'SENIORS1 (S1)',
                        '12': 'SENIORS2 (S2)',
                        '13': 'SENIORS3 (S3)',
                        '14': 'SENIORS1 (T1)',
                        '15': 'SENIORS2 (T2)',
                        '16': 'SENIORS3 (T3)',
                        '17': 'DEBUTANTS',
                        '50': 'U13 - BENJAMINS (N)',
                        '51': 'U15 - MINIMES (N)',
                        '53': 'U18 - CADETS (N)',
                        '54': 'U21 - JUNIORS (N)',
                        '60': 'SENIORS1 (S1) (N)',
                        '61': 'SENIORS2 (S2) (N)',
                        '62': 'SENIORS3 (S3) (N)',
                        '63': 'SENIORS1 (T1) (N)',
                        '64': 'SENIORS2 (T2) (N)',
                        '65': 'SENIORS3 (T3) (N)',
                        '9': 'W1',
                        '10': 'OPEN',
                        '18': 'FEDERAL',
                        '19': 'CHALLENGE',
                        '20': 'CRITERIUM',
                        '21': 'POTENCE',
                        '23': 'HV1',
                        '24': 'HV2-3',
                        '25': 'HV LIBRE',
                        '26': 'SUPPORT 1',
                        '27': 'OPEN VETERAN',
                        '28': 'OPEN U18',
                        '29': 'CHALLENGE U18',
                        '30': 'SUPPORT 2',
                        '31': 'W1 U18',
                        '32': 'FEDERAL U18',
                        '33': 'FEDERAL VETERAN',
                        '34': 'CRITERIUM U18',
                        '35': 'HV U18',
                        '36': 'FEDERAL NATIONAL',
                        '37': 'OPEN NATIONAL',
                        '38': 'W1 NATIONAL'
                    };
                    if (ageCategoryMapping[catage]) {
                        ageCategory = ageCategoryMapping[catage];
                    } else if (catage && /^(U\d+|SENIORS|DEBUTANTS|DECOUVERTE|W1|OPEN|FEDERAL|CHALLENGE|CRITERIUM|POTENCE|HV|SUPPORT)/i.test(catage)) {
                        // Si c'est déjà un nom de catégorie, le garder tel quel
                        ageCategory = catage;
                    }
                    
                    foundUser = {
                        licenceNumber: getNodeText(userNode, 'IDLicence'),
                        firstName: firstName,
                        first_name: firstName,
                        lastName: lastName,
                        name: lastName,
                        last_name: lastName,
                        email: getNodeText(userNode, 'EMAIL'),
                        username: '',
                        role: 'Archer',
                        gender: gender,
                        birthDate: birthDate,
                        birth_date: birthDate,
                        ageCategory: ageCategory,
                        age_category: ageCategory,
                        bowType: bowType,
                        bow_type: bowType,
                        club: getNodeText(userNode, 'CIE'),
                        clubId: getNodeText(userNode, 'CIE'),
                        club_id: getNodeText(userNode, 'CIE')
                    };
                    break;
                }
            }
            
            if (foundUser) {
                // Afficher un message de succès
                resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Utilisateur trouvé ! Le formulaire a été rempli automatiquement.</div>';
                
                // Auto-remplir le formulaire
                fillFormWithUserData(foundUser);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun utilisateur trouvé avec ce numéro de licence.</div>';
            }
        })
        .catch(error => {
            searchButton.disabled = false;
            console.error('Erreur lors de la recherche:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la recherche: ' + error.message + '. Veuillez réessayer.</div>';
        });
}

// Fonction utilitaire pour extraire le texte d'un nœud XML
function getNodeText(parentNode, tagName) {
    const node = parentNode.querySelector(tagName);
    return node ? node.textContent.trim() : '';
}

// Fonction pour remplir le formulaire avec les données de l'utilisateur
function fillFormWithUserData(user) {
    // Remplir les champs d'identité
    if (user.firstName || user.first_name) {
        const firstNameField = document.getElementById('firstName');
        if (firstNameField) {
            firstNameField.value = user.firstName || user.first_name || '';
        }
    }
    
    if (user.name || user.last_name) {
        const nameField = document.getElementById('name');
        if (nameField) {
            nameField.value = user.name || user.last_name || '';
        }
    }
    
    if (user.email) {
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.value = user.email || '';
        }
    }
    
    if (user.phone) {
        const phoneField = document.getElementById('phone');
        if (phoneField) {
            phoneField.value = user.phone || '';
        }
    }
    
    // Remplir les champs de rôle et statut
    if (user.role) {
        const roleField = document.getElementById('role');
        if (roleField) {
            roleField.value = user.role || '';
        }
    }
    
    if (user.is_admin !== undefined) {
        const isAdminField = document.getElementById('is_admin');
        if (isAdminField) {
            isAdminField.value = user.is_admin || user.isAdmin ? '1' : '0';
        }
    }
    
    if (user.is_banned !== undefined) {
        const isBannedField = document.getElementById('is_banned');
        if (isBannedField) {
            isBannedField.value = user.is_banned || user.isBanned ? '1' : '0';
        }
    }
    
    if (user.status) {
        const statusField = document.getElementById('status');
        if (statusField) {
            statusField.value = user.status || '';
        }
    }
    
    // Remplir les champs sportifs
    if (user.birthDate || user.birth_date) {
        const birthDateField = document.getElementById('birthDate');
        if (birthDateField) {
            // Convertir la date au format YYYY-MM-DD si nécessaire
            let birthDate = user.birthDate || user.birth_date || '';
            if (birthDate && birthDate.includes('/')) {
                const parts = birthDate.split('/');
                if (parts.length === 3) {
                    birthDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
            }
            birthDateField.value = birthDate;
        }
    }
    
    if (user.gender) {
        const genderField = document.getElementById('gender');
        if (genderField) {
            genderField.value = user.gender || '';
        }
    }
    
    if (user.ageCategory || user.age_category) {
        const ageCategoryField = document.getElementById('ageCategory');
        if (ageCategoryField) {
            ageCategoryField.value = user.ageCategory || user.age_category || '';
        }
    }
    
    if (user.bowType || user.bow_type) {
        const bowTypeField = document.getElementById('bowType');
        if (bowTypeField) {
            bowTypeField.value = user.bowType || user.bow_type || '';
        }
    }
    
    if (user.arrivalYear || user.arrival_year) {
        const arrivalYearField = document.getElementById('arrivalYear');
        if (arrivalYearField) {
            arrivalYearField.value = user.arrivalYear || user.arrival_year || '';
        }
    }
    
    if (user.club || user.clubId || user.club_id) {
        const clubField = document.getElementById('clubId');
        if (clubField) {
            const clubId = user.club || user.clubId || user.club_id || '';
            clubField.value = clubId;
        }
    }
}

// Initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.getElementById('searchByLicence');
    const licenceInput = document.getElementById('licenceNumber');
    
    if (searchButton) {
        // Recherche au clic sur le bouton
        searchButton.addEventListener('click', searchUserByLicence);
    }
    
    if (licenceInput) {
        // Recherche automatique lors de la saisie (avec délai pour éviter trop de requêtes)
        let searchTimeout;
        licenceInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const licenceNumber = this.value.trim();
            
            // Rechercher automatiquement si au moins 5 caractères sont saisis
            if (licenceNumber.length >= 5) {
                searchTimeout = setTimeout(searchUserByLicence, 500);
            } else {
                // Vider le message si moins de 5 caractères
                const resultDiv = document.getElementById('licenceSearchResult');
                if (resultDiv) {
                    resultDiv.innerHTML = '';
                }
            }
        });
        
        // Recherche aussi avec la touche Entrée
        licenceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUserByLicence();
            }
        });
    }
});

