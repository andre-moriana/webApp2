<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Portail Arc Training</title>
    
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
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training Logo" class="login-logo mb-3" style="max-width: 200px; height: auto;">
                        <p class="mb-0">Portail d'administration</p>
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
                        
                        <form method="POST" action="/auth/authenticate">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nom d'utilisateur ou Email
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       autocomplete="username"
                                       placeholder="Votre nom d'utilisateur ou email">
                                <small class="form-text text-muted">
                                    Saisissez votre nom d'utilisateur ou votre adresse email
                                </small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Mot de passe
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="current-password"
                                           placeholder="Votre mot de passe">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Se connecter
                                </button>
                            </div>
                        </form>
                         
                        <div class="text-center mt-3">
                            <a href="/auth/reset-password" class="btn btn-link text-info">
                                <i class="fas fa-key me-1"></i>
                                Mot de passe oublié ?
                            </a>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="/auth/register" class="btn btn-link text-success">
                                <i class="fas fa-user-plus me-1"></i>
                                Ajouter un utilisateur
                            </a>
                        </div>
                       
                        <div class="text-center mt-2">
                            <small class="text-info">
                                <i class="fas fa-clock me-1"></i>
                                Votre compte est en attente ? Contactez un administrateur
                            </small>
                        </div>
                        
                        <div class="text-center mt-3 pt-3 border-top">
                            <div class="d-flex flex-column gap-2">
                                <a href="/contact" class="btn btn-link text-muted" style="font-size: 0.875rem;">
                                    <i class="fas fa-envelope me-1"></i>
                                    Formulaire de contact
                                </a>
                                <a href="/privacy" class="btn btn-link text-muted" style="font-size: 0.875rem;">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Protection des données personnelles
                                </a>
                            </div>
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
    <!-- Custom JS -->
    <script src="public/assets/js/login.js"></script>
</body>
</html>
