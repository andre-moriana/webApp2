<?php
// Fichier de diagnostic pour les boutons de chat
session_start();

// Simuler une session utilisateur
$_SESSION["logged_in"] = true;
$_SESSION["user"] = [
    "id" => 1,
    "is_admin" => true,
    "isAdmin" => true
];

// Messages de test
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Boutons Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .message-actions {
            opacity: 1 !important;
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid red;
            padding: 5px;
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Diagnostic des boutons de chat</h2>
        
        <div class="debug-info">
            <h5>Informations de debug :</h5>
            <p><strong>currentUserId:</strong> <?php echo $_SESSION["user"]["id"] ?? "null"; ?></p>
            <p><strong>isAdmin:</strong> <?php echo ($_SESSION["user"]["is_admin"] ?? false) ? "true" : "false"; ?></p>
            <p><strong>Nombre de messages:</strong> <?php echo count($chatMessages); ?></p>
        </div>

        <div class="chat-messages" style="max-height: 400px; overflow-y: auto; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
            <?php foreach ($chatMessages as $message): ?>
                <div class="chat-message mb-3" style="margin-bottom: 15px; padding: 10px; background-color: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="d-flex align-items-start">
                        <div class="chat-avatar me-2">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </div>
                        <div class="chat-content flex-grow-1">
                            <div class="chat-header d-flex justify-content-between align-items-center" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                                <div>
                                    <strong class="text-primary"><?php echo htmlspecialchars($message["userName"]); ?></strong>
                                    <small class="text-muted ms-2"><?php echo date("d/m/Y H:i", strtotime($message["timestamp"])); ?></small>
                                </div>
                                <div class="message-actions">
                                    <?php 
                                    $currentUserId = $_SESSION["user"]["id"] ?? null;
                                    $messageUserId = $message["userId"] ?? $message["user_id"] ?? null;
                                    $isCurrentUserMessage = $currentUserId && $messageUserId && $currentUserId == $messageUserId;
                                    $isAdmin = $_SESSION["user"]["is_admin"] ?? $_SESSION["user"]["isAdmin"] ?? false;
                                    
                                    echo "<!-- DEBUG: currentUserId = " . var_export($currentUserId, true) . " -->";
                                    echo "<!-- DEBUG: messageUserId = " . var_export($messageUserId, true) . " -->";
                                    echo "<!-- DEBUG: isCurrentUserMessage = " . var_export($isCurrentUserMessage, true) . " -->";
                                    echo "<!-- DEBUG: isAdmin = " . var_export($isAdmin, true) . " -->";
                                    
                                    // FORCER L AFFICHAGE POUR DEBUG
                                    if (true): 
                                    ?>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-message-btn" 
                                                    data-message-id="<?php echo htmlspecialchars($message["id"]); ?>"
                                                    data-content="<?php echo htmlspecialchars($message["content"]); ?>"
                                                    title="Modifier le message">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-message-btn" 
                                                    data-message-id="<?php echo htmlspecialchars($message["id"]); ?>"
                                                    title="Supprimer le message">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="chat-text">
                                <div class="message-content"><?php echo nl2br(htmlspecialchars($message["content"])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <h5>Instructions :</h5>
            <ol>
                <li>Vérifiez que les boutons sont visibles (ils devraient avoir un fond rouge pour le debug)</li>
                <li>Inspectez le code source pour voir les commentaires de debug</li>
                <li>Testez les clics sur les boutons</li>
            </ol>
        </div>
    </div>
</body>
</html>
