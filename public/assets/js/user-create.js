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
                // Nettoyer le texte XML (supprimer les espaces/retours à la ligne avant la déclaration XML)
                xmlText = xmlText.trim();
                
                // S'assurer que le fichier commence bien par <?xml
                if (!xmlText.startsWith('<?xml')) {
                    // Chercher la déclaration XML dans le texte
                    const xmlDeclMatch = xmlText.match(/<\?xml[^>]*\?>/);
                    if (xmlDeclMatch) {
                        // Extraire tout ce qui précède la déclaration XML et le supprimer
                        const xmlDeclIndex = xmlText.indexOf(xmlDeclMatch[0]);
                        xmlText = xmlText.substring(xmlDeclIndex);
                    }
                }
                
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
                    
                    foundUser = {
                        licenceNumber: getNodeText(userNode, 'IDLicence'),
                        firstName: firstName,
                        first_name: firstName,
                        lastName: lastName,
                        name: lastName,
                        last_name: lastName,
                        email: getNodeText(userNode, 'EMAIL'),
                        username: '', // Sera généré côté client si nécessaire
                        role: 'Archer' // Par défaut
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
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun utilisateur trouvé avec ce numéro de licence. Vous pouvez créer un nouvel utilisateur.</div>';
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
        document.getElementById('name').value = user.name || user.last_name || '';
    }
    
    if (user.first_name || user.firstName) {
        document.getElementById('first_name').value = user.first_name || user.firstName || '';
    }
    
    if (user.email) {
        document.getElementById('email').value = user.email || '';
    }
    
    if (user.username) {
        document.getElementById('username').value = user.username || '';
    }
    
    // Le mot de passe n'est pas rempli pour des raisons de sécurité
    // L'utilisateur devra en saisir un nouveau
    
    // Afficher un message informatif
    const passwordField = document.getElementById('password');
    passwordField.placeholder = 'Veuillez saisir un nouveau mot de passe';
    passwordField.focus();
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
                document.getElementById('licenceSearchResult').innerHTML = '';
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

