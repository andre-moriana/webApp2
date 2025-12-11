<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Portail Archers de Gémenos'; ?></title>
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
            <a class="navbar-brand" href="/login">
                <i class="fas fa-bullseye me-2"></i> Archers de Gémenos
            </a>
            <div class="navbar-nav ms-auto">
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

