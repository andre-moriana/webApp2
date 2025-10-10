<?php
$title = "Gestion des groupes - Portail Archers de Gémenos";
?>

<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/events.css">
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

        <!-- Section chat -->
        <div class="col-md-8">
            <?php if (!empty($groups)): ?>
                <!-- Chat du premier groupe (affiché par défaut) -->
                <div id="chat-container" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="chat-title"><?php echo htmlspecialchars($groups[0]['name']); ?></h5>
                    </div>
                    <div class="card-body" style="height: 500px;">
                        <div class="chat-container">
                            <div id="messages-container" class="messages-container mb-3">
                                <?php 
                                // Charger les vrais messages depuis l'API si disponibles
                                if (empty($chatMessages) && !empty($groups)) {
                                    $firstGroupId = $groups[0]['id'];
                                    try {
                                        $apiService = new ApiService();
                                        $messagesResponse = $apiService->makeRequest("messages/{$firstGroupId}/history", "GET");
                                        if ($messagesResponse['success']) {
                                            // Vérifier que la clé "data" existe et n'est pas null
                                            if (isset($messagesResponse['data']) && $messagesResponse['data'] !== null) {
                                                $chatMessages = $messagesResponse['data'];
                                                error_log("Messages chargés depuis l'API: " . count($chatMessages) . " messages");
                                            } else {
                                                error_log("Pas de données dans la réponse messages");
                                                $chatMessages = [];
                                            }
                                        } else {
                                            error_log("Erreur API messages: " . ($messagesResponse['message'] ?? 'Erreur inconnue'));
                                            $chatMessages = [];
                                        }
                                    } catch (Exception $e) {
                                        error_log("Exception lors du chargement des messages: " . $e->getMessage());
                                        $chatMessages = [];
                                    }
                                }
                                ?>
                                <?php if (isset($chatMessages) && !empty($chatMessages)): ?>
                                    <?php foreach ($chatMessages as $message): 
                                        // Inclure le template de message
                                        include __DIR__ . "/../chat/group-message.php";
                                    endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-comments fa-2x mb-2"></i>
                                        <p>Aucun message dans le chat</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="message-input-container">
                                <form id="message-form" class="d-flex gap-2">
                                    <input type="hidden" id="current-user-id" value="<?php echo $_SESSION['user']['id']; ?>">
                                    <input type="hidden" id="current-group-id" value="<?php echo $groups[0]['id']; ?>">
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
const currentUserId = <?php echo $_SESSION["user"]["id"]; ?>;
const initialGroupId = <?php echo !empty($groups) ? $groups[0]['id'] : 'null'; ?>;
// backendUrl supprimé - tous les appels passent maintenant par le backend WebApp2
const isAdmin = <?php echo $_SESSION["user"]["is_admin"] ? 'true' : 'false'; ?>;
const authToken = "<?php echo $_SESSION['token'] ?? ''; ?>";
</script>

<!-- Inclusion du JavaScript -->
<script src="/public/assets/js/groups-chat.js"></script>

<!-- Script pour la suppression des groupes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de suppression chargé');
    
    // Gérer les clics sur les boutons de suppression
    document.querySelectorAll('.delete-group-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const groupId = this.getAttribute('data-group-id');
            const groupName = this.getAttribute('data-group-name');
            
            console.log('Clic sur suppression du groupe:', groupId, groupName);
            
            // Mettre à jour le modal avec les informations du groupe
            document.getElementById('groupName').textContent = groupName;
            document.getElementById('deleteGroupId').value = groupId;
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
    
    // Gérer la soumission du formulaire de suppression
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const groupId = document.getElementById('deleteGroupId').value;
            console.log('Suppression du groupe:', groupId);
            
            // Créer un formulaire temporaire pour la suppression
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/groups/' + groupId;
            
            // Ajouter le champ _method pour simuler DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            // Ajouter le champ group_id
            const groupIdInput = document.createElement('input');
            groupIdInput.type = 'hidden';
            groupIdInput.name = 'group_id';
            groupIdInput.value = groupId;
            form.appendChild(groupIdInput);
            
            // Soumettre le formulaire
            document.body.appendChild(form);
            form.submit();
        });
    }
});
</script>
