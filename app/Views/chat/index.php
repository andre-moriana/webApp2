<?php
/**
 * Vue principale du chat
 * @var array $messages Liste des messages
 * @var array $currentUser Utilisateur connecté
 */
?>

<div class="chat-container">
    <div class="chat-messages" id="chat-messages">
        <?php foreach ($messages as $message): 
            // Vérifier les permissions de l'utilisateur pour ce message
            $canEdit = ($currentUser['id'] === $message['author_id']) || $currentUser['is_admin'];
            $canDelete = $currentUser['is_admin'] || 
                        ($currentUser['id'] === $message['author_id'] && 
                         (time() - strtotime($message['created_at'])) < 3600); // 1 heure pour supprimer son propre message
            
            // Inclure le template de message
            include __DIR__ . '/message.php';
        endforeach; ?>
    </div>

    <div class="chat-input">
        <form action="/messages/send" method="post" id="chat-form">
            <div class="input-group">
                <input type="text" name="message" class="form-control" placeholder="Votre message..." required>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </form>
    </div>
</div> 