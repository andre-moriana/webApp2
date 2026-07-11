<!DOCTYPE html>
<html lang="fr" data-bs-theme="light" data-theme-mode="system">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/theme-head.php'; ?>
    <title><?php echo $pageTitle ?? 'Portail Arc Training'; ?></title>
<!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<!-- CSS personnalisé -->
    <link href="/public/assets/css/style.css" rel="stylesheet">
    <!-- CSS spécifique à la page (si défini) -->
    <?php if (isset($additionalCSS)): 
        foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; 
    endif; ?>
</head>
<body>
    <!-- Navigation simplifiée pour les pages publiques -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="/login">
                <img src="/public/assets/images/arc-training-logo.png" alt="Arc Training Logo" class="navbar-logo me-2">
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <div class="nav-item dropdown me-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-light border-0"
                            id="themeToggleBtnPublic"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Changer le thème"
                            title="Thème d'affichage">
                        <i class="fas fa-circle-half-stroke" data-theme-icon aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow app-theme-menu" aria-labelledby="themeToggleBtnPublic">
                        <li><h6 class="dropdown-header">Apparence</h6></li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="system">
                                <i class="fas fa-circle-half-stroke me-2"></i>Système
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="light">
                                <i class="fas fa-sun me-2"></i>Clair
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="dark">
                                <i class="fas fa-moon me-2"></i>Sombre
                            </button>
                        </li>
                    </ul>
                </div>
                <a class="nav-link" href="/contact">
                    <i class="fas fa-envelope me-1"></i> Contact
                </a>
                <a class="nav-link" href="/privacy">
                    <i class="fas fa-shield-alt me-1"></i> Données personnelles
                </a>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <a class="nav-link" href="/dashboard">
                        <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                    </a>
                    <a class="nav-link" href="/logout">
                        <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="/login">
                        <i class="fas fa-sign-in-alt me-1"></i> Connexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
<!-- Contenu principal -->
    <main class="container-fluid py-4">

