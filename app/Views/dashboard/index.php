<!-- Page d'accueil du tableau de bord -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>
                Tableau de bord
            </h1>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Message de bienvenue -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">
                <i class="fas fa-check-circle me-2"></i>
                Connexion réussie !
            </h4>
            <p>Bienvenue dans le portail d'administration des Archers de Gémenos.</p>
            <hr>
            <p class="mb-0">
                <strong>Utilisateur connecté :</strong> 
                <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Admin'); ?> 
                <?php //echo htmlspecialchars($_SESSION['user']['last_name'] ?? 'Gémenos'); ?>
            </p>
        </div>
    </div>
</div>

<!-- Statistiques globales -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Utilisateurs
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['users']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Groupes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['groups']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Entraînements
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['trainings']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Événements
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['events']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Exercices
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['exercises'] ?? 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dumbbell fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques CLUBS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-building me-2"></i>
                    Statistiques Clubs
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-primary shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Comités Régionaux
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['clubs_regional']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-map-marked-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-info shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Comités Départementaux
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['clubs_departmental']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-success shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Clubs
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['clubs_total']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques UTILISATEURS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-users me-2"></i>
                    Statistiques Utilisateurs
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-primary shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Utilisateurs
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['users']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-warning shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            En attente de validation
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['users_pending_validation']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-danger shadow-sm h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            En attente de suppression
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['users_pending_deletion']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>
                    Actions rapides
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (($_SESSION['user']['is_admin'] ?? false) || ($_SESSION['user']['role'] ?? '') === 'Admin'): ?>
                    <div class="col-md-3 mb-3">
                        <a href="/users" class="btn btn-primary btn-block">
                            <i class="fas fa-users me-2"></i>
                            Gérer les utilisateurs
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3 mb-3">
                        <a href="/groups" class="btn btn-success btn-block">
                            <i class="fas fa-layer-group me-2"></i>
                            Gérer les groupes
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/exercises" class="btn btn-secondary btn-block">
                            <i class="fas fa-dumbbell me-2"></i>
                            Gérer les exercices
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/trainings" class="btn btn-info btn-block">
                            <i class="fas fa-chart-line me-2"></i>
                            Voir les entraînements
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="/events" class="btn btn-warning btn-block">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Gérer les événements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Informations système - Visible uniquement pour les administrateurs -->
<?php 
// Vérifier si l'utilisateur est administrateur
$isAdmin = $_SESSION['user']['is_admin'] ?? $_SESSION['user']['isAdmin'] ?? false;
if ($isAdmin): 
?>
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations système
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Version PHP :</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Serveur :</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu'; ?></p>
                        <p><strong>Date/Heure :</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Session ID :</strong> <?php echo session_id(); ?></p>
                        <p><strong>Token :</strong> <?php echo htmlspecialchars($_SESSION['token'] ?? 'Non défini'); ?></p>
                        <p><strong>Rôle :</strong> <?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'admin'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
