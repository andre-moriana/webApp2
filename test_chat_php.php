<?php
session_start();

// Simuler une session utilisateur pour le test
$_SESSION["logged_in"] = true;
$_SESSION["user"] = [
    "id" => 1,
    "is_admin" => true,
    "isAdmin" => true
];

// Simuler des messages de chat
$chatMessages = [
    [
        "id" => 1,
        "content" => "Message de test 1",
        "userName" => "Utilisateur 1",
        "userId" => 1,
        "timestamp" => "2024-01-01 10:00:00"
    ],
    [
        "id" => 2,
        "content" => "Message de test 2",
        "userName" => "Utilisateur 2", 
        "userId" => 2,
        "timestamp" => "2024-01-01 10:05:00"
    ]
];

$title = "Test Chat Messages";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/public/assets/css/chat-messages.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test des boutons de chat avec PHP</h2>
        <div class="chat-messages">
            <?php if (empty($chatMessages)): ?>
                <div class="text-center text-muted">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <p>Aucun message dans le chat</p>
                </div>
            <?php else: ?>
                <?php foreach ($chatMessages as $message): ?>
                    <div class="chat-message mb-3" data-message-id="<?php echo htmlspecialchars($message["id"] ?? ""); ?>">
                        <div class="d-flex align-items-start">
                            <div class="chat-avatar me-2">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div class="chat-content flex-grow-1">
                                <div class="chat-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-primary">
                                            <?php echo htmlspecialchars($message["userName"] ?? "Utilisateur"); ?>
                                        </strong>
                                        <small class="text-muted ms-2">
                                            <?php 
                                            if (isset($message["timestamp"])) {
                                                $date = new DateTime($message["timestamp"]);
                                                echo $date->format("d/m/Y H:i");
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div class="message-actions">
                                        <?php 
                                        $currentUserId = $_SESSION["user"]["id"] ?? null;
                                        $messageUserId = $message["userId"] ?? $message["user_id"] ?? null;
                                        $isCurrentUserMessage = $currentUserId && $messageUserId && $currentUserId == $messageUserId;
                                        $isAdmin = $_SESSION["user"]["is_admin"] ?? $_SESSION["user"]["isAdmin"] ?? false;
                                        
                                        // Debug: Afficher les valeurs
                                        echo "<!-- DEBUG: currentUserId = " . var_export($currentUserId, true) . " -->";
                                        echo "<!-- DEBUG: messageUserId = " . var_export($messageUserId, true) . " -->";
                                        echo "<!-- DEBUG: isCurrentUserMessage = " . var_export($isCurrentUserMessage, true) . " -->";
                                        echo "<!-- DEBUG: isAdmin = " . var_export($isAdmin, true) . " -->";
                                        
                                        // Afficher les boutons d action si c est le message de l utilisateur ou si c est un admin
                                        // TEMPORAIRE: Afficher toujours les boutons pour debug
                                        if (true || $isCurrentUserMessage || $isAdmin): 
                                        ?>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-message-btn" 
                                                        data-message-id="<?php echo htmlspecialchars($message["id"] ?? ""); ?>"
                                                        data-content="<?php echo htmlspecialchars($message["content"] ?? ""); ?>"
                                                        title="Modifier le message">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-message-btn" 
                                                        data-message-id="<?php echo htmlspecialchars($message["id"] ?? ""); ?>"
                                                        title="Supprimer le message">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="chat-text">
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($message["content"] ?? "")); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
