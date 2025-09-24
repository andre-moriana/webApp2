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
                    <?php foreach ($groups as $index => $group): ?>
                        <a href="#" class="list-group-item list-group-item-action group-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                           data-group-id="<?php echo $group['id']; ?>">
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
            <?php if (!empty($groups)): ?>
                <!-- Chat du premier groupe (affiché par défaut) -->
                <div id="chat-container" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="chat-title"><?php echo htmlspecialchars($groups[0]['name']); ?></h5>
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
                            <div id="messages-container" class="messages-container mb-3">
                                <?php 
                                // Charger les vrais messages depuis l'API si disponibles
                                if (empty($chatMessages) && !empty($groups)) {
                                    $firstGroupId = $groups[0]['id'];
                                    try {
                                        $apiService = new ApiService();
                                        $messagesResponse = $apiService->makeRequest("messages/{$firstGroupId}/history", "GET");
                                        if ($messagesResponse['success']) {
                                            $chatMessages = $messagesResponse['data'];
                                            error_log("Messages chargés depuis l'API: " . count($chatMessages) . " messages");
                                        } else {
                                            error_log("Erreur API messages: " . ($messagesResponse['message'] ?? 'Erreur inconnue'));
                                        }
                                    } catch (Exception $e) {
                                        error_log("Exception lors du chargement des messages: " . $e->getMessage());
                                    }
                                }
                                
                                // Messages de test si aucun message chargé
                                if (empty($chatMessages)) {
                                    $chatMessages = [
                                        [
                                            "id" => "1",
                                            "content" => "Bonjour tout le monde ! Bienvenue dans ce groupe.",
                                            "author_id" => "123", 
                                            "author_name" => "Admin",
                                            "created_at" => date("Y-m-d H:i:s", time() - 3600)
                                        ],
                                        [
                                            "id" => "2",
                                            "content" => "Comment allez-vous aujourd'hui ?",
                                            "author_id" => "456",
                                            "author_name" => "Utilisateur Test", 
                                            "created_at" => date("Y-m-d H:i:s", time() - 1800)
                                        ],
                                        [
                                            "id" => "3",
                                            "content" => "Très bien merci ! Et vous ?",
                                            "author_id" => "789",
                                            "author_name" => "Autre User",
                                            "created_at" => date("Y-m-d H:i:s", time() - 900)
                                        ],
                                        [
                                            "id" => "4", 
                                            "content" => "Parfait ! On se voit bientôt pour l'entraînement.",
                                            "author_id" => "123",
                                            "author_name" => "Admin",
                                            "created_at" => date("Y-m-d H:i:s", time() - 300)
                                        ]
                                    ];
                                    error_log("Utilisation des messages de test");
                                }
                                ?>
                                
                                <?php if (isset($chatMessages) && !empty($chatMessages)): ?>
                                    <?php foreach ($chatMessages as $message): 
                                        // Utiliser des clés flexibles pour l ID de l auteur
                                        $authorId = $message["author_id"] ?? $message["userId"] ?? $message["user_id"] ?? $message["author"]["id"] ?? $message["author"]["_id"] ?? null;
                                        
                                        // Vérifier les permissions de l utilisateur pour ce message
                                        $canEdit = ($_SESSION["user"]["id"] === $authorId) || $_SESSION["user"]["is_admin"];
                                        $canDelete = $_SESSION["user"]["is_admin"] || 
                                                    ($_SESSION["user"]["id"] === $authorId && 
                                                     (time() - strtotime($message["created_at"])) < 3600);
                                        
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

<!-- Variables PHP pour JavaScript -->
<script>
const currentUserId = <?php echo $_SESSION["user"]["id"]; ?>;
const initialGroupId = <?php echo !empty($groups) ? $groups[0]['id'] : 'null'; ?>;
const backendUrl = "<?php echo str_replace('/api', '', $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000'); ?>";
const isAdmin = <?php echo $_SESSION["user"]["is_admin"] ? 'true' : 'false'; ?>;
const authToken = "<?php echo $_SESSION['token'] ?? ''; ?>";
</script>

<!-- Inclusion du JavaScript -->
<script src="/public/assets/js/groups-chat.js"></script>
