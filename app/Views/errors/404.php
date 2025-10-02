<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - Archers de Gémenos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS personnalisé -->
    <link href="/public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-bullseye me-2"></i> Archers de Gémenos
            </a>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center">
                    <!-- Icône d'erreur -->
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                    </div>
                    
                    <!-- Message d'erreur -->
                    <h1 class="display-1 fw-bold text-primary">404</h1>
                    <h2 class="h3 mb-3">Page non trouvée</h2>
                    <p class="lead text-muted mb-4">
                        Désolé, la page que vous recherchez n'existe pas ou a été déplacée.
                    </p>
                    
                    <!-- Actions -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/dashboard" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i> Retour au tableau de bord
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i> Page précédente
                        </button>
                    </div>
                    
                    <!-- Informations supplémentaires -->
                    <div class="mt-5">
                        <small class="text-muted">
                            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h5>Portail Archers de Gémenos</h5>
                    <p class="mb-0">Gestion de l'application mobile de tir à l'arc</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour les Archers de Gémenos
                    </p>
                    <small class="text-muted">Version 1.0.0</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 