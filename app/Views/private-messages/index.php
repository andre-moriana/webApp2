<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-envelope me-2"></i> Messages Privés</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                    <i class="fas fa-plus me-2"></i> Nouvelle conversation
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Liste des conversations -->
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Conversations</h5>
                </div>
                <div class="card-body p-0">
                    <div id="conversations-list" class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($conversations)): ?>
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Aucune conversation</p>
                                <p class="small">Cliquez sur "Nouvelle conversation" pour commencer</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): 
                                $otherUser = $conv['other_user'] ?? [];
                                $otherUserId = $otherUser['_id'] ?? $otherUser['id'] ?? '';
                                // Essayer différents formats de nom
                                $otherUserName = $otherUser['name'] ?? '';
                                if (empty($otherUserName)) {
                                    $firstName = $otherUser['firstName'] ?? '';
                                    $lastName = $otherUser['lastName'] ?? '';
                                    $otherUserName = trim($firstName . ' ' . $lastName);
                                }
                                if (empty($otherUserName)) {
                                    $otherUserName = $otherUser['username'] ?? 'Utilisateur inconnu';
                                }
                                $lastMessage = $conv['last_message'] ?? '';
                                $lastMessageDate = $conv['last_message_date'] ?? '';
                                $unreadCount = $conv['unread_count'] ?? 0;
                                
                                // Debug
                                error_log("Conversation: OtherUserID=$otherUserId, OtherUserName=$otherUserName");
                                
                                // Ne pas afficher les conversations sans utilisateur
                                if (empty($otherUserId)) continue;
                            ?>
                                <a href="#" 
                                   class="list-group-item list-group-item-action conversation-item" 
                                   data-user-id="<?php echo htmlspecialchars($otherUserId); ?>"
                                   data-user-name="<?php echo htmlspecialchars($otherUserName); ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-2x text-secondary me-2"></i>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($otherUserName); ?></h6>
                                                    <?php if (!empty($lastMessage)): ?>
                                                        <p class="mb-0 text-muted small text-truncate" style="max-width: 200px;">
                                                            <?php echo htmlspecialchars(substr($lastMessage, 0, 50)) . (strlen($lastMessage) > 50 ? '...' : ''); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($unreadCount > 0): ?>
                                                <span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($lastMessageDate)): ?>
                                                <small class="text-muted d-block mt-1">
                                                    <?php 
                                                        $date = new DateTime($lastMessageDate);
                                                        echo $date->format('d/m H:i');
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de chat -->
        <div class="col-md-8 col-lg-9">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="conversation-title">
                        <i class="fas fa-comments me-2"></i>
                        <span id="current-user-name">Sélectionnez une conversation</span>
                    </h5>
                </div>
                <div class="card-body" style="height: 500px; overflow-y: auto;" id="messages-container">
                    <div class="text-center text-muted mt-5">
                        <i class="fas fa-comment-dots fa-4x mb-3"></i>
                        <p>Sélectionnez une conversation pour commencer à échanger des messages</p>
                    </div>
                </div>
                <div class="card-footer" id="message-form-container" style="display: none;">
                    <form id="send-message-form" class="d-flex gap-2">
                        <input type="hidden" id="recipient-id" value="">
                        <div class="flex-grow-1">
                            <textarea 
                                class="form-control" 
                                id="message-content" 
                                rows="2" 
                                placeholder="Écrivez votre message..."
                                required></textarea>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i> Envoyer
                            </button>
                            <label class="btn btn-outline-secondary btn-sm" title="Joindre un fichier">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" id="message-attachment" class="d-none" accept="image/*,.pdf,.doc,.docx">
                            </label>
                        </div>
                    </form>
                    <div id="attachment-preview" class="mt-2" style="display: none;">
                        <small class="text-muted">
                            <i class="fas fa-file me-1"></i>
                            <span id="attachment-name"></span>
                            <button type="button" class="btn btn-sm btn-link text-danger" id="remove-attachment">
                                <i class="fas fa-times"></i>
                            </button>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour nouvelle conversation -->
<div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="newConversationModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Nouvelle conversation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="user-search" class="form-label">Rechercher un utilisateur</label>
                    <input type="text" class="form-control" id="user-search" placeholder="Tapez un nom...">
                </div>
                <div id="users-list" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($users)): ?>
                        <p class="text-muted text-center">Aucun utilisateur disponible</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($users as $user): 
                                $userId = $user['id'] ?? $user['_id'] ?? '';
                                // Construire le nom complet - plusieurs formats possibles
                                $firstName = $user['firstName'] ?? '';
                                $lastName = $user['name'] ?? $user['lastName'] ?? '';
                                $userName = trim($firstName . ' ' . $lastName);
                                if (empty($userName)) {
                                    $userName = $user['username'] ?? 'Utilisateur';
                                }
                                $userEmail = $user['email'] ?? '';
                                
                                // Debug
                                error_log("User in modal: ID=$userId, Name=$userName");
                                
                                // Ne pas afficher les utilisateurs sans ID
                                if (empty($userId)) continue;
                            ?>
                                <a href="#" 
                                   class="list-group-item list-group-item-action user-item" 
                                   data-user-id="<?php echo htmlspecialchars($userId); ?>"
                                   data-user-name="<?php echo htmlspecialchars($userName); ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-circle fa-2x text-secondary me-3"></i>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($userName); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($userEmail); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Style pour la conversation active */
    .conversation-item.active {
        background-color: #d4edda;
        border-left: 4px solid #198754;
    }
    
    /* Style pour le hover */
    .conversation-item:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
    // Passer les données PHP au JavaScript
    window.currentUserId = '<?php echo $_SESSION['user']['id'] ?? ''; ?>';
    window.currentUserName = '<?php echo htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>';
    
    // Debug
    console.log('Current user ID:', window.currentUserId);
    console.log('Current user name:', window.currentUserName);
</script>
