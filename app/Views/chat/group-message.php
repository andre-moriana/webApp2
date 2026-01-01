<?php
/**
 * Template pour afficher un message dans la liste des groupes
 * @param array $message Les données du message
 * @param bool $canEdit Si l'utilisateur peut éditer le message
 * @param bool $canDelete Si l'utilisateur peut supprimer le message
 */

// DEBUG: Afficher la structure complète du message
error_log("group-message.php - Structure du message: " . print_r($message, true));

// Utiliser des clés flexibles pour l'ID du message
$messageId = $message["id"] ?? $message["_id"] ?? $message["message_id"] ?? null;

// Utiliser des clés flexibles pour l'ID de l'auteur
$authorId = $message["author"]["_id"] ?? $message["author"]["id"] ?? $message["author_id"] ?? $message["userId"] ?? $message["user_id"] ?? null;

// DEBUG: Afficher les valeurs
error_log("group-message.php: authorId = " . ($authorId ?? "null"));
error_log("group-message.php: session user id = " . ($_SESSION["user"]["id"] ?? "null"));

// Calculer correctement si l'utilisateur actuel est l'auteur
$isCurrentUser = false;
if ($authorId && isset($_SESSION["user"]["id"])) {
    // Convertir en string pour la comparaison
    $isCurrentUser = (string)$authorId === (string)$_SESSION["user"]["id"];
}

error_log("group-message.php: isCurrentUser = " . ($isCurrentUser ? "true" : "false"));

// Calculer les permissions
$canEdit = $isCurrentUser; // Seul l'auteur peut éditer
$canDelete = $isCurrentUser || (isset($_SESSION["user"]["is_admin"]) && $_SESSION["user"]["is_admin"]); // Auteur ou admin peut supprimer

// DEBUG: Afficher les permissions
error_log("group-message.php - Permissions: canEdit=" . ($canEdit ? "true" : "false") . ", canDelete=" . ($canDelete ? "true" : "false"));

$alignClass = $isCurrentUser ? "justify-content-end" : "justify-content-start";
$messageClass = $isCurrentUser ? "message-sent" : "message-received";

// Nom de l'auteur
$authorName = $message["author_name"] ?? $message["userName"] ?? $message["author"]["name"] ?? "Utilisateur";

// Date du message
$messageTime = "";
if (isset($message["created_at"]) || isset($message["timestamp"])) {
    $timeValue = $message["created_at"] ?? $message["timestamp"];
    try {
        $date = new DateTime($timeValue);
        $messageTime = $date->format("d/m/Y H:i:s");
    } catch (Exception $e) {
        $messageTime = "Date inconnue";
    }
}

// URL du backend pour les pièces jointes
$backendUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000';
$backendUrl = str_replace('/api', '', $backendUrl); // Supprimer /api si présent

?>
<div class="d-flex <?php echo $alignClass; ?> mb-3" data-message-id="<?php echo htmlspecialchars($messageId ?? ""); ?>">
    <div class="message <?php echo $messageClass; ?>">
        <div class="message-header">
            <span class="message-author"><?php echo htmlspecialchars($authorName); ?></span>
            <span class="message-time"><?php echo $messageTime; ?></span>
        </div>

        <?php if (isset($message["content"]) && $message["content"]): ?>
            <div class="message-content"><?php echo nl2br(htmlspecialchars($message["content"])); ?></div>
        <?php endif; ?>

        <?php if (isset($message["attachment"]) && $message["attachment"]): ?>
            <div class="message-attachment mt-2">
                <?php
                $attachmentUrl = $message["attachment"]["url"] ?? $message["attachment"]["path"] ?? "";
                $originalName = $message["attachment"]["originalName"] ?? $message["attachment"]["filename"] ?? "Pièce jointe";
                $mimeType = $message["attachment"]["mimeType"] ?? "";

                // Construire l'URL complète vers le backend
                if ($attachmentUrl && !str_starts_with($attachmentUrl, "http")) {
                    $attachmentUrl = rtrim($backendUrl, '/') . "/" . ltrim($attachmentUrl, "/");
                }
                ?>
                <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" class="attachment-link">
                    <?php if (strpos($mimeType, "image/") === 0): ?>
                        <img src="<?php echo htmlspecialchars($attachmentUrl); ?>"
                             alt="<?php echo htmlspecialchars($originalName); ?>"
                             class="img-fluid rounded"
                             style="max-width: 200px; max-height: 200px; object-fit: cover;"
                             onerror="handleImageError(this)">
                    <?php else: ?>
                        <div class="file-attachment p-2 bg-light rounded d-flex align-items-center">
                            <i class="fas fa-file me-2"></i>
                            <span><?php echo htmlspecialchars($originalName); ?></span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($canEdit || $canDelete): ?>
            <div class="message-actions">
                <?php if ($canEdit): ?>
                    <button type="button" class="btn btn-edit" onclick="editMessage('<?php echo htmlspecialchars($messageId ?? ""); ?>')">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                    <button type="button" class="btn btn-delete" onclick="deleteMessage('<?php echo htmlspecialchars($messageId ?? ""); ?>')">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
