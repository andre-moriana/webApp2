<?php
$title = "Gestion des utilisateurs - Portail Archers de Gémenos";

// Débogage temporaire
error_log("=== DEBUG SESSION ===");
error_log("Session: " . print_r($_SESSION, true));
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gestion des utilisateurs</h1>
                <?php if (isset($_SESSION['user']['is_admin']) && (bool)$_SESSION['user']['is_admin']): ?>
                    <div>
                        <a href="/users/import" class="btn btn-success me-2">
                            <i class="fas fa-file-upload me-2"></i>Importer depuis XML
                        </a>
                        <a href="/users/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nouvel utilisateur
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Liste des utilisateurs
                        </h5>
                        <div class="search-box">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="userSearchInput" 
                                       placeholder="Rechercher un utilisateur..." 
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="clearSearchBtn" 
                                        style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="sortable" data-column="id">
                                        ID <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="name">
                                        Nom <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="email">
                                        Email <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="role">
                                        Rôle <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="club">
                                        Club <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="status">
                                        Validation <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="banned">
                                        Bannissement <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th class="sortable" data-column="lastLogin">
                                        Dernière connexion <i class="fas fa-sort ms-1"></i>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr class="no-results-row">
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Aucun utilisateur trouvé</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="user-row" data-searchable="<?php 
                                            // Construire une chaîne de recherche avec toutes les données pertinentes
                                            $searchableText = '';
                                            if (!empty($user['firstName'])) $searchableText .= strtolower($user['firstName']) . ' ';
                                            if (!empty($user['name'])) $searchableText .= strtolower($user['name']) . ' ';
                                            if (!empty($user['email'])) $searchableText .= strtolower($user['email']) . ' ';
                                            if (!empty($user['role'])) $searchableText .= strtolower($user['role']) . ' ';
                                            if (!empty($user['status'])) $searchableText .= strtolower($user['status']) . ' ';
                                            if (!empty($user['licenceNumber'])) $searchableText .= strtolower($user['licenceNumber']) . ' ';
                                            if (!empty($user['id'])) $searchableText .= $user['id'] . ' ';
                                            // Ajouter le club dans la recherche (nom complet et nom court)
                                            if (!empty($user['clubName'])) {
                                                $searchableText .= strtolower($user['clubName']) . ' ';
                                            } elseif (!empty($user['club_name'])) {
                                                $searchableText .= strtolower($user['club_name']) . ' ';
                                            }
                                            if (!empty($user['clubNameShort'])) {
                                                $searchableText .= strtolower($user['clubNameShort']) . ' ';
                                            } elseif (!empty($user['club_name_short'])) {
                                                $searchableText .= strtolower($user['club_name_short']) . ' ';
                                            }
                                            // Fallback si club est un objet/tableau
                                            if (isset($user['club'])) {
                                                if (is_array($user['club'])) {
                                                    $clubName = $user['club']['name'] ?? '';
                                                    $clubNameShort = $user['club']['nameShort'] ?? $user['club']['name_short'] ?? '';
                                                    if ($clubName) $searchableText .= strtolower($clubName) . ' ';
                                                    if ($clubNameShort) $searchableText .= strtolower($clubNameShort) . ' ';
                                                } else {
                                                    $searchableText .= strtolower($user['club']) . ' ';
                                                }
                                            }
                                            echo htmlspecialchars(trim($searchableText));
                                        ?>">
                                            <td class="text-nowrap"><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <?php 
                                                        // Vérifier si une photo de profil existe
                                                        // Essayer plusieurs variantes du nom du champ
                                                        $profileImage = $user['profileImage'] ?? $user['profile_image'] ?? null;
                                                        $displayName = $user['firstName'] ?? $user['first_name'] ?? $user['name'] ?? 'U';
                                                        $initial = strtoupper(substr($displayName, 0, 1));
                                                        
                                                        // Normaliser le chemin de l'image si présent
                                                        if (!empty($profileImage)) {
                                                            // S'assurer que le chemin commence par /uploads/ pour la cohérence
                                                            if (strpos($profileImage, '/uploads/') !== 0 && strpos($profileImage, 'uploads/') !== 0) {
                                                                // Si le chemin ne commence pas par /uploads/, l'ajouter
                                                                if (strpos($profileImage, '/') === 0) {
                                                                    $profileImage = '/uploads' . $profileImage;
                                                                } else {
                                                                    $profileImage = '/uploads/' . $profileImage;
                                                                }
                                                            }
                                                        }
                                                        
                                                        if (!empty($profileImage)): 
                                                            // Construire l'URL directe vers le backend (même approche que dans settings.php ligne 54)
                                                            // S'assurer que le chemin commence par /uploads/
                                                            if (strpos($profileImage, '/uploads/') !== 0) {
                                                                if (strpos($profileImage, 'uploads/') === 0) {
                                                                    $profileImage = '/' . $profileImage;
                                                                } else {
                                                                    $profileImage = '/uploads/' . $profileImage;
                                                                }
                                                            }
                                                            // Utiliser exactement la même URL que dans settings.php (qui fonctionne)
                                                            $imageUrl = 'https://api.arctraining.fr' . $profileImage;
                                                        ?>
                                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                                                 alt="Photo de profil" 
                                                                 class="rounded-circle user-avatar" 
                                                                 style="width: 32px; height: 32px; object-fit: cover;"
                                                                 onerror="this.onerror=null; this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                                                                 loading="lazy">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center user-initial" 
                                                                 style="width: 32px; height: 32px; font-size: 14px; display: none;">
                                                                <?php echo $initial; ?>
                                                            </div>
                                                        <?php else: 
                                                            // Utiliser l'initial si pas de photo
                                                        ?>
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center user-initial" 
                                                                 style="width: 32px; height: 32px; font-size: 14px;">
                                                                <?php echo $initial; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-truncate" style="max-width: 150px;">
                                                        <?php 
                                                        // Construire le nom complet en utilisant les champs disponibles
                                                        $fullName = '';
                                                        if (!empty($user['firstName'])) {
                                                            $fullName = $user['firstName'];
                                                            if (!empty($user['name'])) {
                                                                $fullName .= ' ' . $user['name'];
                                                            }
                                                        } else {
                                                            $fullName = $user['name'] ?? 'Utilisateur';
                                                        }
                                                        echo htmlspecialchars($fullName); 
                                                        ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($user['email']); ?>">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="badge bg-<?php echo ($user['role'] === 'admin' || ($user['is_admin'] ?? $user['isAdmin'] ?? false)) ? 'danger' : 'secondary'; ?>">
                                                    <?php echo ucfirst($user['role'] ?? 'user'); ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap" data-column="club">
                                                <?php 
                                                // Utiliser exactement la même logique que dans show.php
                                                // Récupérer clubName qui a été enrichi par le contrôleur
                                                if (!empty($user['clubName'])) {
                                                    echo htmlspecialchars($user['clubName']);
                                                } elseif (!empty($user['clubNameShort'])) {
                                                    echo htmlspecialchars($user['clubNameShort']);
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php 
                                                $status = $user['status'] ?? 'active';
                                                $statusLabels = [
                                                    'pending' => 'En attente',
                                                    'pending_deletion' => 'En attente de suppression',
                                                    'active' => 'Validé',
                                                    'rejected' => 'Rejeté'
                                                ];
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'pending_deletion' => 'danger',
                                                    'active' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                $label = $statusLabels[$status] ?? 'Inconnu';
                                                $color = $statusColors[$status] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo $label; ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <span class="badge bg-<?php echo ($user['is_banned'] ?? $user['isBanned'] ?? false) ? 'danger' : 'success'; ?>">
                                                    <?php echo ($user['is_banned'] ?? $user['isBanned'] ?? false) ? 'Banni' : 'Actif'; ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php 
                                                if (!empty($user['lastLogin'])) {
                                                    $lastLogin = new DateTime($user['lastLogin']);
                                                    echo $lastLogin->format('d/m/Y H:i');
                                                } else {
                                                    echo 'Jamais';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="btn-group" role="group">
                                                    <a href="/users/<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php 
                                                    // Ne pas afficher le bouton message pour soi-même
                                                    $currentUserId = $_SESSION['user']['id'] ?? null;
                                                    if ($user['id'] != $currentUserId): 
                                                    ?>
                                                    <a href="/private-messages?user=<?php echo $user['id']; ?>&name=<?php echo urlencode($fullName); ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       title="Envoyer un message privé">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="/users/<?php echo $user['id']; ?>/edit" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php 
                                                    // Afficher le bouton de suppression seulement pour les administrateurs
                                                    $isCurrentUserAdmin = $_SESSION['user']['is_admin'] ?? $_SESSION['user']['isAdmin'] ?? false;
                                                    if ($isCurrentUserAdmin): 
                                                    ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?php echo $user['id']; ?>)" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion des fichiers CSS et JS -->
<link rel="stylesheet" href="/public/assets/css/users-table.css">
<script src="/public/assets/js/users-table.js"></script>
