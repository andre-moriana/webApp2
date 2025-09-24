<?php
$title = "Détails du groupe - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-layer-group me-2"></i>
            <?php echo htmlspecialchars($group["name"] ?? "Groupe non trouvé"); ?>
        </h1>
        <div>
            <a href="/groups" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif ($group): ?>
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations détaillées
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="font-weight-bold">Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($group["description"] ?? "Aucune description")); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-comments me-2"></i>
                            Chat du groupe
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($chatError): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($chatError); ?>
                            </div>
                        <?php else: ?>
                            <div class="messages-container">
                                <?php foreach ($chatMessages as $message): 
                                    // Utiliser des clés flexibles pour l ID de l auteur
                                    $authorId = $message["author_id"] ?? $message["userId"] ?? $message["user_id"] ?? $message["author"]["id"] ?? null;
                                    
                                    // Vérifier les permissions de l utilisateur pour ce message
                                    $canEdit = ($_SESSION["user"]["id"] === $authorId) || $_SESSION["user"]["is_admin"];
                                    $canDelete = $_SESSION["user"]["is_admin"] || 
                                                ($_SESSION["user"]["id"] === $authorId && 
                                                 (time() - strtotime($message["created_at"])) < 3600);
                                    
                                    // Inclure le template de message
                                    include __DIR__ . "/../chat/message.php";
                                endforeach; ?>
                            </div>
                            
                            <div class="chat-input mt-3">
                                <form action="/groups/<?php echo htmlspecialchars($group["id"] ?? ""); ?>/chat" method="POST" class="d-flex">
                                    <input type="text" 
                                           name="message" 
                                           class="form-control me-2" 
                                           placeholder="Votre message..." 
                                           required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
