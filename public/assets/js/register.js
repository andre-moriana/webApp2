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
        resultDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Veuillez entrer un numéro de licence</div>';
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
                    
                    foundUser = {
                        licenceNumber: getNodeText(userNode, 'IDLicence'),
                        firstName: firstName,
                        first_name: firstName,
                        lastName: lastName,
                        name: lastName,
                        last_name: lastName,
                        email: getNodeText(userNode, 'EMAIL'),
                        username: '', // Sera généré côté client si nécessaire
                        role: 'Archer', // Par défaut
                        club: getNodeText(userNode, 'CIE'),
                        birthDate: getNodeText(userNode, 'DATENAISSANCE'),
                        gender: getNodeText(userNode, 'SEXE') === '1' ? 'H' : (getNodeText(userNode, 'SEXE') === '2' ? 'F' : ''),
                        ageCategory: getNodeText(userNode, 'CATAGE'),
                        categorie: getNodeText(userNode, 'CATEGORIE'),
                        bowType: getNodeText(userNode, 'TYPARC')
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
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun utilisateur trouvé avec ce numéro de licence. Vous pouvez créer un nouveau compte.</div>';
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
    // Remplir les champs du formulaire
    if (user.name || user.last_name) {
        const nameField = document.getElementById('name');
        if (nameField) {
            nameField.value = user.name || user.last_name || '';
        }
    }
    
    if (user.first_name || user.firstName) {
        const firstNameField = document.getElementById('first_name');
        if (firstNameField) {
            firstNameField.value = user.first_name || user.firstName || '';
        }
    }
    
    if (user.email) {
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.value = user.email || '';
        }
    }
    
    if (user.username) {
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.value = user.username || '';
        }
    }
    
    if (user.role) {
        const roleField = document.getElementById('role');
        if (roleField) {
            roleField.value = user.role || '';
        }
    }
    
    // Le mot de passe n'est pas rempli pour des raisons de sécurité
    // L'utilisateur devra en saisir un nouveau
}

document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const registerForm = document.getElementById('registerForm');
    const confirmPasswordField = document.getElementById('confirm_password');
    const searchButton = document.getElementById('searchByLicence');
    const licenceInput = document.getElementById('licenceNumber');
    
    // Gérer la recherche par licence
    if (searchButton) {
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

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Form validation
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                confirmPasswordField.focus();
                return false;
            }
            
            // Check password strength
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
                passwordField.focus();
                return false;
            }
            
            // Check if username is valid
            const username = document.getElementById('username').value;
            if (username.length < 3) {
                e.preventDefault();
                alert('Le nom d\'utilisateur doit contenir au moins 3 caractères');
                document.getElementById('username').focus();
                return false;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
            submitButton.disabled = true;
            
            // Re-enable button after 5 seconds (fallback)
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 5000);
        });
    }

    // Real-time password confirmation
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const password = passwordField.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }

    // Auto-generate username from first and last name
    const firstNameField = document.getElementById('first_name');
    const lastNameField = document.getElementById('last_name');
    const usernameField = document.getElementById('username');

    if (firstNameField && lastNameField && usernameField) {
        function generateUsername() {
            const firstName = firstNameField.value.toLowerCase().trim();
            const lastName = lastNameField.value.toLowerCase().trim();
            
            if (firstName && lastName) {
                // Remove accents and special characters
                const cleanFirstName = firstName.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const cleanLastName = lastName.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                
                // Generate username (first letter of first name + last name)
                const username = cleanFirstName.charAt(0) + cleanLastName;
                usernameField.value = username;
            }
        }

        firstNameField.addEventListener('blur', generateUsername);
        lastNameField.addEventListener('blur', generateUsername);
    }
});
