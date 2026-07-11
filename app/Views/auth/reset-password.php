<!DOCTYPE html>
<html lang="fr" data-bs-theme="light" data-theme-mode="system">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/../layouts/theme-head.php'; ?>
    <title>Nouveau mot de passe - Portail Arc Training</title>
    
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
                        <i class="fas fa-lock fa-3x mb-3"></i>
                        <h3 class="mb-0">Nouveau mot de passe</h3>
                        <p class="mb-0">Choisissez un nouveau mot de passe</p>
                    </div>
                    
                    <div class="login-form">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/auth/update-password">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Nouveau mot de passe
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       minlength="6"
                                       autocomplete="new-password"
                                       placeholder="Au moins 6 caractères">
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
                                       minlength="6"
                                       autocomplete="new-password"
                                       placeholder="Confirmez votre mot de passe">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-check me-2"></i>
                                    Enregistrer le mot de passe
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <a href="/auth/forgot-password" class="btn btn-link text-info">
                                Demander un nouveau lien
                            </a>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="/login" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour à la connexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/theme.js"></script>
</body>
</html>
