<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppression de compte - Portail Arc Training</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/public/assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-user-slash fa-3x text-danger mb-3"></i>
                        <h2 class="mb-0">Suppression de compte</h2>
                        <p class="text-muted mt-2">Demande de suppression de vos données personnelles</p>
                    </div>
                    
                    <div class="login-form">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention !</strong> Cette action est irréversible. La suppression de votre compte entraînera :
                            <ul class="mt-2 mb-0">
                                <li>La suppression définitive de toutes vos données personnelles</li>
                                <li>La perte d'accès à tous vos entraînements et statistiques</li>
                                <li>La suppression de votre historique d'activités</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="/auth/delete-account-request" id="deleteAccountForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email ou identifiant
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       placeholder="Votre email ou identifiant">
                                <small class="form-text text-muted">
                                    Saisissez votre adresse email ou votre identifiant pour confirmer votre identité
                                </small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="reason" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Raison de la suppression (optionnel)
                                </label>
                                <textarea class="form-control" 
                                          id="reason" 
                                          name="reason" 
                                          rows="4"
                                          placeholder="Dites-nous pourquoi vous souhaitez supprimer votre compte (optionnel)"></textarea>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="confirmDelete" 
                                       name="confirmDelete" 
                                       required>
                                <label class="form-check-label" for="confirmDelete">
                                    Je comprends que cette action est irréversible et je souhaite supprimer mon compte définitivement
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                                    <i class="fas fa-trash-alt me-2"></i>
                                    Demander la suppression de mon compte
                                </button>
                                <a href="/auth/login" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Retour à la connexion
                                </a>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Traitement de votre demande :</strong><br>
                            Votre demande sera traitée par un administrateur sous 30 jours conformément au RGPD. 
                            Vous recevrez un email de confirmation une fois la suppression effectuée.
                        </div>
                        
                        <div class="text-center mt-3 pt-3 border-top">
                            <a href="/privacy" class="btn btn-link text-muted" style="font-size: 0.875rem;">
                                <i class="fas fa-shield-alt me-1"></i>
                                Politique de confidentialité
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-white">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour Arc Training
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer/désactiver le bouton de soumission selon la checkbox
        document.getElementById('confirmDelete').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = !this.checked;
        });
    </script>
</body>
</html>
