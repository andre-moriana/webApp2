// Fonction pour rechercher un utilisateur par numéro de licence
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
    
    // Faire la requête à l'API
    fetch(`/api/users?licence_number=${encodeURIComponent(licenceNumber)}`)
        .then(response => response.json())
        .then(data => {
            searchButton.disabled = false;
            
            if (data.success && data.data && data.data.users && data.data.users.length > 0) {
                const user = data.data.users[0];
                
                // Afficher un message de succès
                resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Utilisateur trouvé ! Le formulaire a été rempli automatiquement.</div>';
                
                // Auto-remplir le formulaire
                fillFormWithUserData(user);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun utilisateur trouvé avec ce numéro de licence. Vous pouvez créer un nouvel utilisateur.</div>';
            }
        })
        .catch(error => {
            searchButton.disabled = false;
            console.error('Erreur lors de la recherche:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la recherche. Veuillez réessayer.</div>';
        });
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

