<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Portail Archers de Gémenos'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS personnalisé -->
    <link href="/public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-bullseye me-2"></i>
                Archers de Gémenos
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/users">
                            <i class="fas fa-users me-1"></i>
                            Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/groups">
                            <i class="fas fa-layer-group me-1"></i>
                            Groupes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/exercises">
                            <i class="fas fa-clipboard-list me-1"></i>
                            Exercices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/trainings">
                            <i class="fas fa-chart-line me-1"></i>
                            Entraînements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/events">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Événements
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php 
                            // Debug temporaire - à supprimer après résolution
                            error_log("DEBUG Header - Session user data: " . json_encode($_SESSION['user'] ?? 'No user data'));
                            
                            // Construire le nom complet de l'utilisateur
                            $username = $_SESSION['user']['username'] ?? '';
                            //$firstName = $_SESSION['user']['first_name'] ?? '';
                            //$lastName = $_SESSION['user']['last_name'] ?? '';
                            
                            //error_log("DEBUG Header - firstName: '$firstName', lastName: '$lastName'");
                            
                            $displayName = '';
                            if (!empty($username)) {
                                $displayName = $username;
                            } else {
                                $displayName = 'Utilisateur';
                            }
                            
                            error_log("DEBUG Header - displayName: '$displayName'");
                            
                            echo htmlspecialchars($displayName);
                            ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="container-fluid py-4">
