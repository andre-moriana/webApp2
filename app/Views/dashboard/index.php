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
                        <div class="card border-left-primary shadow-sm h-100 club-card-hover">
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
                                <!-- Liste déroulante -->
                                <div class="club-list mt-3">
                                    <hr>
                                    <div class="text-xs font-weight-bold text-primary mb-2">Liste des comités:</div>
                                    <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                        <?php if (!empty($stats['clubs_regional_list'])): ?>
                                            <?php foreach ($stats['clubs_regional_list'] as $committee): ?>
                                                <li class="mb-1 committee-item" 
                                                    data-committee-id="<?php echo htmlspecialchars($committee['id']); ?>"
                                                    style="cursor: pointer;"
                                                    title="Cliquez pour voir les clubs">
                                                    <i class="fas fa-chevron-right text-primary" style="font-size: 0.6rem;"></i> 
                                                    <?php echo htmlspecialchars($committee['name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted">Aucun comité régional</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-info shadow-sm h-100 club-card-hover">
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
                                <!-- Liste déroulante -->
                                <div class="club-list mt-3">
                                    <hr>
                                    <div class="text-xs font-weight-bold text-info mb-2">Liste des comités:</div>
                                    <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                                        <?php if (!empty($stats['clubs_departmental_list'])): ?>
                                            <?php foreach ($stats['clubs_departmental_list'] as $committee): ?>
                                                <li class="mb-1 committee-item" 
                                                    data-committee-id="<?php echo htmlspecialchars($committee['id']); ?>"
                                                    style="cursor: pointer;"
                                                    title="Cliquez pour voir les clubs">
                                                    <i class="fas fa-chevron-right text-info" style="font-size: 0.6rem;"></i> 
                                                    <?php echo htmlspecialchars($committee['name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted">Aucun comité départemental</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-left-success shadow-sm h-100 club-card-hover" id="clubs-display-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            <span id="clubs-title">Total Clubs</span>
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="clubs-count">
                                            <?php echo $stats['clubs_total']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                                <!-- Liste des clubs -->
                                <div id="clubs-list-container" class="club-list mt-3">
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="text-xs font-weight-bold text-success" id="clubs-list-title">Liste des clubs:</div>
                                        <button class="btn btn-sm btn-outline-secondary" id="reset-clubs-btn" style="font-size: 0.7rem; padding: 2px 8px; display: none;">
                                            <i class="fas fa-times"></i> Réinitialiser
                                        </button>
                                    </div>
                                    <ul class="list-unstyled mb-0" id="clubs-list" style="font-size: 0.85rem; max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($stats['all_clubs'])): ?>
                                            <?php foreach ($stats['all_clubs'] as $club): ?>
                                                <li class="mb-1 club-item d-flex justify-content-between align-items-center" 
                                                    data-club-id="<?php echo htmlspecialchars($club['nameshort']); ?>"
                                                    style="cursor: pointer;"
                                                    title="Cliquez pour voir les utilisateurs">
                                                    <span>
                                                        <i class="fas fa-building text-success" style="font-size: 0.6rem;"></i> 
                                                        <?php echo htmlspecialchars($club['name']); ?>
                                                    </span>
                                                    <a href="/clubs/<?php echo htmlspecialchars($club['id']); ?>" 
                                                       class="btn btn-sm btn-outline-primary club-link" 
                                                       style="font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;"
                                                       title="Voir le club"
                                                       onclick="event.stopPropagation();">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted">Aucun club</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour passer les données PHP à JavaScript -->
<script>
    window.clubsByCommittee = <?php echo json_encode($stats['clubs_by_committee'] ?? []); ?>;
    window.allClubs = <?php echo json_encode($stats['all_clubs'] ?? []); ?>;
    window.totalClubs = <?php echo $stats['clubs_total']; ?>;
    window.usersByClub = <?php echo json_encode($stats['users_by_club'] ?? []); ?>;
    window.allUsers = <?php echo json_encode($stats['users_list'] ?? []); ?>;
    window.totalUsers = <?php echo $stats['users']; ?>;
</script>

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
                        <div class="card border-left-primary shadow-sm h-100 user-card-hover" id="users-display-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            <span id="users-title">Total Utilisateurs</span>
                                        </div>
                                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="users-count">
                                            <?php echo $stats['users']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                                <!-- Liste des utilisateurs -->
                                <div class="user-list mt-3">
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="text-xs font-weight-bold text-primary" id="users-list-title">Liste des utilisateurs:</div>
                                        <button class="btn btn-sm btn-outline-secondary" id="reset-users-btn" style="font-size: 0.7rem; padding: 2px 8px; display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <ul class="list-unstyled mb-0" id="users-list" style="font-size: 0.85rem; max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($stats['users_list'])): ?>
                                            <?php foreach ($stats['users_list'] as $user): ?>
                                                <li class="mb-1"><i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i> <?php echo htmlspecialchars($user['name']); ?></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="text-muted">Aucun utilisateur</li>
                                        <?php endif; ?>
                                    </ul>
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
