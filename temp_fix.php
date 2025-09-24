// ... existing code ...
                                    <?php foreach ($chatMessages as $message): 
                                        // Utiliser des clés flexibles pour l ID de l auteur
                                        $authorId = $message["author_id"] ?? $message["userId"] ?? $message["user_id"] ?? $message["author"]["id"] ?? $message["author"]["_id"] ?? null;
                                        
                                        // DEBUG: Afficher la structure du message
                                        error_log("index.php: Structure du message: " . json_encode($message));
                                        error_log("index.php: authorId détecté: " . ($authorId ?? "null"));
                                        error_log("index.php: ID utilisateur session: " . ($_SESSION["user"]["id"] ?? "non défini"));
                                        
                                        // Vérifier les permissions de l utilisateur pour ce message
                                        $canEdit = ($_SESSION["user"]["id"] === $authorId) || $_SESSION["user"]["is_admin"];
                                        $canDelete = $_SESSION["user"]["is_admin"] || 
                                                    ($_SESSION["user"]["id"] === $authorId && 
                                                     (time() - strtotime($message["created_at"])) < 3600);
                                        
                                        // Inclure le template de message
                                        include __DIR__ . "/../chat/group-message.php";
                                    endforeach; ?>
// ... existing code ...
