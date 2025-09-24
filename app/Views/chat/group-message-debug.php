<?php
// Version simplifiée pour debug avec URL backend correcte
$authorId = $message["author_id"] ?? $message["userId"] ?? $message["user_id"] ?? $message["author"]["id"] ?? $message["author"]["_id"] ?? null;
$isCurrentUser = $authorId && $_SESSION["user"]["id"] && $authorId == $_SESSION["user"]["id"];

// DEBUG: Afficher les valeurs
echo "<!-- DEBUG: authorId = " . ($authorId ?? "null") . " -->";
echo "<!-- DEBUG: session user id = " . ($_SESSION["user"]["id"] ?? "null") . " -->";
echo "<!-- DEBUG: isCurrentUser = " . ($isCurrentUser ? "true" : "false") . " -->";

$alignClass = $isCurrentUser ? "justify-content-end" : "justify-content-start";
$messageClass = $isCurrentUser ? "message-sent" : "message-received";

// URL du backend pour les pièces jointes
$backendUrl = "http://82.67.123.22:25000";
?>
<div class="d-flex <?php echo $alignClass; ?> mb-3" data-message-id="<?php echo htmlspecialchars($message["id"] ?? ""); ?>">
    <div class="message <?php echo $messageClass; ?>">
        <div class="message-header">
            <span class="message-author"><?php echo htmlspecialchars($message["author"]["name"] ?? "Utilisateur"); ?></span>
            <span class="message-time"><?php echo isset($message["created_at"]) ? date("d/m/Y H:i:s", strtotime($message["created_at"])) : ""; ?></span>
        </div>
        
        <?php if (isset($message["attachment"]) && $message["attachment"]): ?>
            <div class="message-attachment mt-2">
                <?php 
                // Construire l URL complète vers le backend
                $attachmentUrl = $message["attachment"]["url"] ?? $message["attachment"]["path"] ?? "";
                if ($attachmentUrl && !str_starts_with($attachmentUrl, "http")) {
                    // Si l URL ne commence pas par http, ajouter l URL du backend
                    $attachmentUrl = $backendUrl . "/" . ltrim($attachmentUrl, "/");
                }
                ?>
                <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" class="attachment-link">
                    <img src="<?php echo htmlspecialchars($attachmentUrl); ?>" 
                         alt="<?php echo htmlspecialchars($message["attachment"]["originalName"] ?? ""); ?>" 
                         class="img-fluid rounded" 
                         style="max-width: 200px; max-height: 200px; object-fit: cover;"
                         onerror="console.log(\"Erreur de chargement de l image: " . htmlspecialchars($attachmentUrl) . "\")">
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
