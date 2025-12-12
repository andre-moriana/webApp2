<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - Portail Archers de Gémenos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="public/assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h3 class="mb-0">Ajouter un utilisateur</h3>
                        <p class="mb-0">Créer un nouveau compte</p>
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
                        
                        <form method="POST" action="/auth/create-user" id="registerForm">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Astuce :</strong> Entrez votre numéro de licence pour rechercher et remplir automatiquement vos informations si vous êtes déjà dans la base de données.
                            </div>
                            
                            <div class="mb-3">
                                <label for="licenceNumber" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Numéro de licence
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="licenceNumber" 
                                           name="licenceNumber" 
                                           placeholder="Entrez votre numéro de licence"
                                           autocomplete="off">
                                    <button type="button" class="btn btn-outline-primary" id="searchByLicence" title="Rechercher">
                                        <i class="fas fa-search me-1"></i>Rechercher
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Si vous avez déjà un numéro de licence, entrez-le pour remplir automatiquement vos informations.
                                </small>
                                <div id="licenceSearchResult" class="mt-2"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">
                                            <i class="fas fa-user me-2"></i>Prénom
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="first_name" 
                                               name="first_name" 
                                               required 
                                               placeholder="Prénom">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user me-2"></i>Nom
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               required 
                                               placeholder="Nom de famille">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-at me-2"></i>Nom d'utilisateur
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       required 
                                       placeholder="Nom d'utilisateur">
                                <small class="form-text text-muted">
                                    Utilisé pour la connexion
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       placeholder="adresse@exemple.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-tag me-2"></i>Rôle
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="Archer">Archer</option>
                                    <option value="Coach">Coach</option>
                                    <option value="Admin">Administrateur</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Mot de passe
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           placeholder="Mot de passe">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirmer le mot de passe
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required 
                                       placeholder="Confirmer le mot de passe">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-login">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Créer l'utilisateur
                                </button>
                                <a href="/login" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Retour à la connexion
                                </a>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Validation requise :</strong> Votre compte sera créé mais devra être validé par un administrateur avant de pouvoir vous connecter.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-white">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour les Archers de Gémenos
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="public/assets/js/register.js"></script>
</body>
</html>
