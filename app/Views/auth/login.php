<?php
/** URLs des stores — surcharge via variables d'environnement si besoin */
$arcTrainingPlayStoreUrl = getenv('ARC_PLAY_STORE_URL') ?: 'https://play.google.com/store/apps/details?id=fr.arctraining.mobile';
$arcTrainingAppStoreUrl = getenv('ARC_APP_STORE_URL')
    ?: 'https://apps.apple.com/fr/app/arctraning/id6755296711';
$qrIos = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . rawurlencode($arcTrainingAppStoreUrl);
$qrAndroid = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . rawurlencode($arcTrainingPlayStoreUrl);
?>
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
    <div class="container login-page-wrap py-4">
        <div class="row align-items-start justify-content-center g-4">
            <!-- Appli mobile : App Store (aperçu + QR) -->
            <div class="col-12 col-md-6 col-xl-3 order-2 order-xl-1">
                <div class="login-app-showcase">
                    <div class="login-app-showcase-label text-white text-center mb-2">
                        <i class="fab fa-apple me-2"></i>App Store
                    </div>
                    <div class="login-phone login-phone--iphone mx-auto">
                        <div class="login-phone-notch"></div>
                        <div class="login-phone-screen login-phone-screen--appstore">
                            <a href="<?= htmlspecialchars($arcTrainingAppStoreUrl, ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="login-phone-store-card">
                                <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training" class="login-phone-store-logo" width="72" height="72">
                                <span class="login-phone-store-name">Arc Training</span>
                                <span class="login-phone-store-tagline">Application mobile</span>
                                <span class="login-phone-store-cta login-phone-store-cta--ios">
                                    <i class="fab fa-apple me-1"></i>Ouvrir sur l’App Store
                                </span>
                            </a>
                        </div>
                    </div>
                    <div class="login-app-qr text-center mt-3">
                        <img src="<?= htmlspecialchars($qrIos, ENT_QUOTES, 'UTF-8') ?>" width="160" height="160" alt="QR code App Store Arc Training" class="login-app-qr-img">
                        <div class="small text-white mt-2 opacity-90">Scannez pour télécharger (iPhone)</div>
                        <a href="<?= htmlspecialchars($arcTrainingAppStoreUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light mt-2">
                            Ouvrir l’App Store
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8 col-xl-5 order-1 order-xl-2">
                <div class="login-container">
                    <div class="login-header">
                        <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training Logo" class="login-logo mb-3" style="max-width: 200px; height: auto;">
                        <p class="mb-0">Portail d'administration</p>
                    </div>
                    
                    <div class="login-form">
                        <?php if (isset($_GET['expired']) && $_GET['expired'] == '1'): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-clock me-2"></i>
                                Votre session a expiré. Veuillez vous reconnecter.
                            </div>
                        <?php endif; ?>
                        
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
                            <?php
                            $returnUrl = $_GET['return'] ?? $_SESSION['login_return_url'] ?? '';
                            if ($returnUrl !== ''):
                            ?>
                            <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-user me-2"></i>Identifiant
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       autocomplete="username"
                                       placeholder="Nom d'utilisateur ou numéro de licence">
                                <small class="form-text text-muted">
                                    Saisissez votre nom d'utilisateur ou votre numéro de licence
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
                            <a href="/auth/forgot-password" class="btn btn-link text-info">
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
                                <a href="/auth/delete-account" class="btn btn-link text-danger" style="font-size: 0.875rem;">
                                    <i class="fas fa-user-slash me-1"></i>
                                    Supprimer mon compte
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

            <!-- Appli mobile : Google Play (aperçu + QR) -->
            <div class="col-12 col-md-6 col-xl-3 order-3">
                <div class="login-app-showcase">
                    <div class="login-app-showcase-label text-white text-center mb-2">
                        <i class="fab fa-google-play me-2"></i>Google Play
                    </div>
                    <div class="login-phone login-phone--android mx-auto">
                        <div class="login-phone-punch"></div>
                        <div class="login-phone-screen login-phone-screen--play">
                            <a href="<?= htmlspecialchars($arcTrainingPlayStoreUrl, ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="login-phone-store-card">
                                <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training" class="login-phone-store-logo" width="72" height="72">
                                <span class="login-phone-store-name">Arc Training</span>
                                <span class="login-phone-store-tagline">Application mobile</span>
                                <span class="login-phone-store-cta login-phone-store-cta--play">
                                    <i class="fab fa-google-play me-1"></i>Ouvrir sur Google Play
                                </span>
                            </a>
                        </div>
                    </div>
                    <div class="login-app-qr text-center mt-3">
                        <img src="<?= htmlspecialchars($qrAndroid, ENT_QUOTES, 'UTF-8') ?>" width="160" height="160" alt="QR code Google Play Arc Training" class="login-app-qr-img">
                        <div class="small text-white mt-2 opacity-90">Scannez pour télécharger (Android)</div>
                        <a href="<?= htmlspecialchars($arcTrainingPlayStoreUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light mt-2">
                            Ouvrir Google Play
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/public/assets/js/login.js"></script>
</body>
</html>
