<?php
$title = htmlspecialchars($topic['title'] ?? 'Sujet') . " - Portail Archers de Gémenos";
?>

<link rel="stylesheet" href="/public/assets/css/groups-chat.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="/groups" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Retour aux groupes
                        </a>
                        <h4 class="mb-0 d-inline">
                            <i class="fas fa-comments me-2"></i><?php echo htmlspecialchars($topic['title'] ?? 'Sujet'); ?>
                        </h4>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($topic['description'])): ?>
                        <div class="alert alert-info mb-3">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($topic['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="chat-container" style="height: 500px; overflow-y: auto;">
                        <div id="messages-container" class="messages-container mb-3">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $message): 
                                    // Inclure le template de message
                                    include __DIR__ . "/../chat/group-message.php";
                                endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-comments fa-2x mb-2"></i>
                                    <p>Aucun message dans ce sujet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="message-input-container">
                            <form id="message-form" class="d-flex gap-2">
                                <input type="hidden" id="current-user-id" value="<?php echo $_SESSION['user']['id']; ?>">
                                <input type="hidden" id="current-topic-id" value="<?php echo $topic['id']; ?>">
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
        </div>
    </div>
</div>

<!-- Variables PHP pour JavaScript -->
<script>
window.currentUserId = <?php echo $_SESSION["user"]["id"]; ?>;
window.currentTopicId = <?php echo $topic['id']; ?>;
window.isTopicPage = true;
</script>

<!-- Inclusion du script JavaScript externe -->
<script src="/public/assets/js/groups-chat.js"></script>

