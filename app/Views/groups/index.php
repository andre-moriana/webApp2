<?php
$title = "Gestion des groupes - Portail Archers de Gémenos";
?>

<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/events.css">
<link rel="stylesheet" href="/public/assets/css/groups-chat.css?v=<?php echo time(); ?>">

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
                    <?php foreach ($groups as $index => $group): ?>
                        <?php 
                        // Debug: vérifier les données du groupe
                        error_log("Groupe " . $index . ": " . json_encode($group));
                        ?>
                        <div class="list-group-item list-group-item-action group-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                             data-group-id="<?php echo $group['id'] ?? 'null'; ?>">
                            <div class="d-flex">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($group['name'] ?? 'G', 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($group['name'] ?? 'Nom inconnu'); ?></h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ((bool)($group['is_private'] ?? false)): ?>
                                                <a href="/groups/<?php echo $group['id'] ?? 'null'; ?>/members" 
                                                   class="badge bg-warning text-decoration-none" 
                                                   style="cursor: pointer;"
                                                   data-ignore-chat="true"
                                                   title="Gérer les membres du groupe privé">
                                                    <i class="fas fa-users me-1"></i>
                                                    Privé
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-globe me-1"></i>
                                                    Public
                                                </span>
                                            <?php endif; ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/groups/<?php echo $group['id'] ?? 'null'; ?>/edit" class="btn btn-outline-primary btn-sm" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-group-btn" 
                                                        data-group-id="<?php echo $group['id'] ?? 'null'; ?>"
                                                        data-group-name="<?php echo htmlspecialchars($group['name'] ?? 'Groupe', ENT_QUOTES); ?>"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
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
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section sujets -->
        <div class="col-md-8">
            <?php if (!empty($groups)): ?>
                <!-- Sujets du premier groupe (affiché par défaut) -->
                <div id="topics-container" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="topics-title"><?php echo htmlspecialchars($groups[0]['name']); ?></h5>
                        <a href="/groups/<?php echo $groups[0]['id']; ?>/topics/create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-2"></i>Nouveau sujet
                        </a>
                    </div>
                    <div class="card-body">
                        <?php 
                        $firstGroupId = $groups[0]['id'];
                        $topics = $groupTopics[$firstGroupId] ?? [];
                        ?>
                        <?php if (empty($topics)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Aucun sujet de discussion</p>
                                <a href="/groups/<?php echo $firstGroupId; ?>/topics/create" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Créer le premier sujet
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group" id="topics-list">
                                <?php foreach ($topics as $topic): ?>
                                    <div class="list-group-item list-group-item-action topic-item" 
                                         data-topic-id="<?php echo $topic['id']; ?>"
                                         data-topic-title="<?php echo htmlspecialchars($topic['title'] ?? 'Sans titre'); ?>"
                                         data-topic-description="<?php echo htmlspecialchars($topic['description'] ?? ''); ?>"
                                         style="cursor: pointer;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($topic['title'] ?? 'Sans titre'); ?>
                                                    <?php if (isset($topic['unreadCount']) && $topic['unreadCount'] > 0): ?>
                                                        <span class="badge bg-danger ms-2"><?php echo $topic['unreadCount']; ?></span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (!empty($topic['description'])): ?>
                                                    <p class="mb-1 text-muted small">
                                                        <?php echo htmlspecialchars(substr($topic['description'], 0, 100)); ?>
                                                        <?php echo strlen($topic['description']) > 100 ? '...' : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    Créé par <?php echo htmlspecialchars($topic['created_by_name'] ?? 'Anonyme'); ?>
                                                    <?php if (!empty($topic['created_at'])): ?>
                                                        <span class="ms-2">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?php 
                                                            $createdAt = new DateTime($topic['created_at']);
                                                            echo $createdAt->format('d/m/Y');
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section chat du sujet sélectionné -->
                <div id="topic-chat-container" class="card mt-3" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="chat-topic-title">Sujet</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeTopicChat()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="card-body" style="height: 500px; display: flex; flex-direction: column;">
                        <div class="chat-container" style="flex: 1; overflow-y: auto;">
                            <div id="topic-messages-container" class="messages-container mb-3">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-comments fa-2x mb-2"></i>
                                    <p>Chargement des messages...</p>
                                </div>
                            </div>
                            
                            <div class="message-input-container">
                                <form id="topic-message-form" class="d-flex gap-2">
                                    <input type="hidden" id="current-topic-id-input" value="">
                                    <div class="flex-grow-1">
                                        <input type="text" id="topic-message-input" class="form-control" placeholder="Votre message...">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div class="btn btn-outline-secondary position-relative">
                                            <i class="fas fa-paperclip"></i>
                                            <input type="file" id="topic-message-attachment" class="position-absolute top-0 start-0 opacity-0" style="width:100%; height:100%; cursor:pointer;">
                                        </div>
                                        <?php if ($_SESSION["user"]["is_admin"]): ?>
                                        <button type="button" class="btn btn-outline-secondary" onclick="openFormBuilder()" title="Créer un formulaire">
                                            <i class="fas fa-table"></i> 📊
                                        </button>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Aucun groupe disponible -->
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucun groupe disponible</h4>
                    <p class="text-muted">Créez un groupe pour commencer à discuter</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le groupe "<span id="groupName"></span>" ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="group_id" id="deleteGroupId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Variables PHP pour JavaScript -->
<script>
window.currentUserId = <?php echo $_SESSION["user"]["id"]; ?>;
window.initialGroupId = <?php echo !empty($groups) ? $groups[0]['id'] : 'null'; ?>;
window.isAdmin = <?php echo $_SESSION["user"]["is_admin"] ? 'true' : 'false'; ?>;
window.groupTopics = <?php echo json_encode($groupTopics); ?>;
</script>

<!-- Inclusion du script JavaScript externe -->
<script src="/public/assets/js/groups-topics.js"></script>
