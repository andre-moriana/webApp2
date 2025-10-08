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
                    <a href="/users/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouvel utilisateur
                    </a>
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
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Liste des utilisateurs
                    </h5>
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
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Aucun utilisateur trouvé</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <?php 
                                                        // Vérifier si une photo de profil existe
                                                        $profileImage = $user['profileImage'] ?? $user['profile_image'] ?? null;
                                                        if (!empty($profileImage)): 
                                                            // Construire l'URL complète vers le backend
                                                            $imageUrl = 'http://82.67.123.22:25000' . $profileImage;
                                                        ?>
                                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                                                 alt="Photo de profil" 
                                                                 class="rounded-circle user-avatar" 
                                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                                        <?php else: 
                                                            // Utiliser l'initial si pas de photo
                                                            $displayName = $user['firstName'] ?? $user['name'] ?? 'U';
                                                        ?>
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center user-initial" 
                                                                 style="width: 32px; height: 32px; font-size: 14px;">
                                                                <?php echo strtoupper(substr($displayName, 0, 1)); ?>
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
                                            <td class="text-nowrap">
                                                <?php 
                                                $status = $user['status'] ?? 'active';
                                                $statusLabels = [
                                                    'pending' => 'En attente',
                                                    'active' => 'Validé',
                                                    'rejected' => 'Rejeté'
                                                ];
                                                $statusColors = [
                                                    'pending' => 'warning',
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
