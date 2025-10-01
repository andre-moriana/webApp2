<?php
/**
 * Template pour afficher un message du chat
 * @param array $message Les données du message
 * @param bool $canEdit Si l utilisateur peut éditer le message
 * @param bool $canDelete Si l utilisateur peut supprimer le message
 */
?>
<div class="message <?php echo $message["is_system"] ? "system-message" : ""; ?>" data-message-id="<?php echo htmlspecialchars($message["id"]); ?>">
    <div class="message-header">
        <span class="message-author"><?php echo htmlspecialchars($message["author_name"]); ?></span>
        <span class="message-time"><?php echo htmlspecialchars($message["created_at"]); ?></span>
    </div>
    
    <div class="message-content">
        <?php echo nl2br(htmlspecialchars($message["content"])); ?>
    </div>

    <?php if ($canEdit || $canDelete): ?>
        <div class="message-actions">
            <?php if ($canEdit): ?>
                <button type="button" class="btn btn-edit" onclick="editMessage('<?php echo htmlspecialchars($message["id"] ?? ""); ?>')">
                    <i class="fas fa-edit"></i> Modifier
                </button>
            <?php endif; ?>

            <?php if ($canDelete): ?>
                <button type="button" class="btn btn-delete" onclick="deleteMessage('<?php echo htmlspecialchars($message["id"] ?? ""); ?>')">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
