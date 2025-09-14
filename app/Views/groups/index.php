<?php
$title = "Gestion des groupes - Portail Archers de Gémenos";
?>

<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/groups-chat.css">

<div class="container-fluid">
    <div class="row">
        <!-- Liste des groupes -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Groupes</h1>
                <a href="/groups/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>Nouveau groupe
                </a>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="list-group">
                <?php if (empty($groups)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun groupe trouvé</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <a href="#" class="list-group-item list-group-item-action group-item" data-group-id="<?php echo $group['id']; ?>">
                            <div class="d-flex">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($group['name'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($group['name']); ?></h6>
                                        <small class="badge bg-<?php echo (bool)$group['is_private'] ? 'warning' : 'success'; ?>">
                                            <?php echo (bool)$group['is_private'] ? 'Privé' : 'Public'; ?>
                                        </small>
                                    </div>
                                    <p class="text-muted mb-1 small">
                                        <?php echo htmlspecialchars($group['description'] ?? 'Aucune description'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($group['admin_name'] ?? 'Anonyme'); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php 
                                            if (!empty($group['created_at'])) {
                                                $createdAt = new DateTime($group['created_at']);
                                                echo $createdAt->format('d/m/Y');
                                            } else {
                                                echo 'Date inconnue';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section chat -->
        <div class="col-md-8">
            <div id="chat-container" class="card d-none">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="chat-title">Sélectionnez un groupe</h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" id="btn-edit-group">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" id="btn-delete-group">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="height: 500px;">
                    <div class="chat-container">
                        <div id="messages-container" class="messages-container mb-3"></div>
                        <div class="message-input-container">
                            <form id="message-form" class="d-flex gap-2">
                                <input type="hidden" id="current-user-id" value="<?php echo $_SESSION['user']['id']; ?>">
                                <div class="flex-grow-1">
                                    <input type="text" id="message-input" class="form-control" placeholder="Votre message...">
                                </div>
                                <div class="d-flex gap-2">
                                    <div class="btn btn-outline-secondary position-relative">
                                        <i class="fas fa-paperclip"></i>
                                        <input type="file" id="message-attachment" class="position-absolute top-0 start-0 opacity-0" style="width:100%; height:100%; cursor:pointer;">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div id="no-chat-selected" class="text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Sélectionnez un groupe pour voir les messages</h4>
            </div>
        </div>
    </div>
</div>

<!-- Variables PHP pour JavaScript -->
<script>
const currentUserId = <?php echo $_SESSION['user']['id']; ?>;
</script>

<!-- Inclusion du JavaScript -->
<script src="/public/assets/js/groups-chat.js"></script>
