<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? $title ?? 'Portail Arc Training'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/public/assets/images/favicon/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/public/favicon.ico">
    <link rel="icon" type="image/png" sizes="96x96" href="/public/assets/images/favicon/favicon-96x96.png">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/public/assets/images/favicon/apple-touch-icon.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/public/assets/images/favicon/site.webmanifest">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#198754">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- CSS personnalisé -->
    <link href="/public/assets/css/style.css" rel="stylesheet">
    <link href="/public/assets/css/nav-modern.css" rel="stylesheet">
    <link href="/public/assets/css/chat-messages.css" rel="stylesheet">
    <?php if (isset($additionalCSS)): 
        foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; 
    endif; ?>
</head>
<body class="<?php echo !empty($dashboardFullPage) ? 'dashboard-fullpage' : ''; ?>">
    <!-- Barre supérieure minimaliste -->
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <a class="app-topbar-logo" href="/dashboard" aria-label="Tableau de bord">
                <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training" class="app-topbar-logo-img">
            </a>
            <div class="app-topbar-actions">
                <a href="/dashboard" class="app-topbar-link d-md-none" title="Tableau de bord">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
                <button type="button" class="app-topbar-menu-btn" id="openNavModalBtn" aria-label="Ouvrir le menu">
                    <i class="fas fa-bars"></i>
                    <span class="d-none d-sm-inline ms-1">Menu</span>
                </button>
                <div class="dropdown app-topbar-user">
                    <button class="app-topbar-user-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Utilisateur'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/user-settings"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Modale du menu principal -->
    <div class="modal fade" id="navModal" tabindex="-1" aria-labelledby="navModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content nav-modal-content">
                <div class="modal-header nav-modal-header">
                    <h5 class="modal-title" id="navModalLabel">
                        <i class="fas fa-compass me-2"></i>Navigation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body nav-modal-body p-0">
                    <?php include __DIR__ . '/nav-menu-content.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <main class="app-main <?php echo !empty($dashboardFullPage) ? 'app-main-fullpage' : ''; ?>">
    <div class="container-fluid py-4 <?php echo !empty($dashboardFullPage) ? 'app-container-fullpage' : ''; ?>">
