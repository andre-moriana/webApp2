<?php

class ApiController {
    private $apiService;
    private $baseUrl;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
    }
    
    public function userDocuments($userId) {
        // Nettoyer la sortie au début
        $this->cleanOutput();
        
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            // Appel réel à l'API backend pour récupérer les documents
            $response = $this->apiService->makeRequest("documents/user/{$userId}", "GET");
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "documents" => $response['data']['documents'] ?? [],
                    "message" => "Documents récupérés avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "documents" => [],
                    "message" => "Erreur lors de la récupération des documents: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "documents" => [],
                "message" => "Erreur lors de la récupération des documents: " . $e->getMessage()
            ]);
        }
    }
    
    public function documents() {
        // Nettoyer la sortie au début
        $this->cleanOutput();
        
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $response = $this->apiService->makeRequest("documents", "GET");
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "documents" => $response['data']['documents'] ?? [],
                    "message" => "Documents récupérés avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la récupération des documents: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la récupération des documents: " . $e->getMessage()
            ]);
        }
    }
    
    public function uploadDocument($userId) {
        // Nettoyer la sortie au début
        $this->cleanOutput();
        
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            // Vérifier qu'un fichier a été uploadé
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Aucun fichier valide uploadé"
                ]);
                return;
            }
            
            // Log de debug détaillé
            error_log("=== DEBUG UPLOAD DOCUMENT ===");
            error_log("URL complète: " . $_SERVER['REQUEST_URI']);
            error_log("userId reçu: " . $userId);
            error_log("Type de userId: " . gettype($userId));
            error_log("userId converti en int: " . (int)$userId);
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            // Préparer les données pour l'upload
            // Ne pas envoyer user_id car ce champ n'existe pas dans la table
            $documentData = [
                'name' => $_POST['name'] ?? '',
                'uploaded_by' => (int)$userId  // Seulement uploaded_by
            ];
            
            error_log("Données finales envoyées: " . print_r($documentData, true));
            error_log("URL de l'API: documents/{$userId}/upload");
            
            // Utiliser makeRequest avec les fichiers
            $response = $this->apiService->makeRequestWithFile("documents/{$userId}/upload", "POST", $documentData, $_FILES['document']);
            
            error_log("Réponse API complète: " . print_r($response, true));
            error_log("=== FIN DEBUG UPLOAD DOCUMENT ===");
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Document uploadé avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de l'upload: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            error_log("Exception upload: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de l'upload: " . $e->getMessage()
            ]);
        }
    }
    
    public function deleteDocument($userId) {
        // Nettoyer la sortie au début
        $this->cleanOutput();
        
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $documentId = $input['id'] ?? null;
            
            if (!$documentId) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "ID du document manquant"
                ]);
                return;
            }
            
            $response = $this->apiService->makeRequest("documents/{$userId}/delete", "DELETE", ['id' => $documentId]);
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Document supprimé avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la suppression: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la suppression: " . $e->getMessage()
            ]);
        }
    }
    
    public function downloadDocument($documentId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $response = $this->apiService->makeRequest("documents/{$documentId}/download", "GET");
            error_log("=== DEBUG DOWNLOAD DOCUMENT ===");
            error_log("Réponse complète: " . print_r($response, true));
            
            if ($response['success']) {
                // L'API retourne directement le contenu du fichier binaire
                $rawResponse = $response['raw_response'] ?? '';
                $contentType = $response['content_type'] ?? 'application/octet-stream';
                
                // Nettoyer le contenu binaire dès le début
                if (!empty($rawResponse)) {
                    // Supprimer les espaces et caractères de contrôle au début et à la fin
                    $rawResponse = trim($rawResponse);
                    
                    // Pour les fichiers JPEG, s'assurer que la signature est correcte
                    if ($contentType === 'image/jpeg') {
                        // Chercher la vraie signature JPEG
                        $jpegStart = strpos($rawResponse, "\xFF\xD8\xFF");
                        if ($jpegStart !== false && $jpegStart > 0) {
                            error_log("Signature JPEG trouvée à la position: " . $jpegStart . ", nettoyage...");
                            $rawResponse = substr($rawResponse, $jpegStart);
                        }
                        
                        // Chercher la vraie fin JPEG
                        $jpegEnd = false;
                        for ($i = strlen($rawResponse) - 1; $i >= 0; $i--) {
                            if ($rawResponse[$i] === "\xD9" && $i > 0 && $rawResponse[$i-1] === "\xFF") {
                                $jpegEnd = $i + 1;
                                break;
                            }
                        }
                        
                        if ($jpegEnd !== false && $jpegEnd < strlen($rawResponse)) {
                            error_log("Fin JPEG trouvée à la position: " . $jpegEnd . ", nettoyage...");
                            $rawResponse = substr($rawResponse, 0, $jpegEnd);
                        }
                        
                        error_log("Taille finale du JPEG: " . strlen($rawResponse));
                    }
                }
                
                // Vérifier si c'est du contenu binaire (PDF, image, etc.)
                if (!empty($rawResponse) && !json_decode($rawResponse, true)) {
                    // C'est du contenu binaire, le servir directement
                    
                    // Générer un nom de fichier avec l'extension appropriée
                    $fileName = "document_" . $documentId;
                    
                    // Ajouter l'extension appropriée basée sur le type MIME
                    $mimeToExt = [
                        'image/jpeg' => '.jpg',
                        'image/jpg' => '.jpg',
                        'image/png' => '.png',
                        'image/gif' => '.gif',
                        'application/pdf' => '.pdf',
                        'text/plain' => '.txt'
                    ];
                    $extension = $mimeToExt[$contentType] ?? '.bin';
                    $fileName .= $extension;
                    
                    error_log("Nom de fichier final: " . $fileName);
                    error_log("Type MIME final: " . $contentType);
                    
                    // Nettoyer la sortie et définir les headers appropriés
                    if (ob_get_level()) {
                        ob_clean();
                    }
                    
                    header('Content-Type: ' . $contentType);
                    header('Content-Disposition: attachment; filename="' . $fileName . '"');
                    header('Content-Length: ' . strlen($rawResponse));
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    
                    echo $rawResponse;
                    exit;
                } else {
                    // C'est du JSON, essayer de trouver une URL
                    $data = $response['data'] ?? [];
                    $downloadUrl = null;
                    
                    // Essayer différents chemins possibles pour l'URL
                    if (isset($data['download_url'])) {
                        $downloadUrl = $data['download_url'];
                    } elseif (isset($data['url'])) {
                        $downloadUrl = $data['url'];
                    } elseif (isset($data['file_url'])) {
                        $downloadUrl = $data['file_url'];
                    } elseif (isset($data['path'])) {
                        $downloadUrl = $data['path'];
                    } elseif (isset($data['downloadUrl'])) {
                        $downloadUrl = $data['downloadUrl'];
                    }
                    
                    if ($downloadUrl) {
                        // Si l'URL est relative, la rendre absolue
                        if (strpos($downloadUrl, 'http') !== 0) {
                            $baseUrl = rtrim($this->baseUrl, '/api');
                            $downloadUrl = $baseUrl . '/' . ltrim($downloadUrl, '/');
                        }
                        
                        header("Location: " . $downloadUrl);
                        exit;
                    } else {
                        $this->cleanOutput();
                        http_response_code(404);
                        echo json_encode([
                            "success" => false,
                            "message" => "URL de téléchargement non trouvée dans la réponse API"
                        ]);
                    }
                }
            } else {
                $this->cleanOutput();
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors du téléchargement: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            $this->cleanOutput();
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors du téléchargement: " . $e->getMessage()
            ]);
        }
    }
    
    private function sendJsonResponse($data, $statusCode = 200) {
        // Forcer le type de contenu et l'encodage
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        // Nettoyer la sortie précédente
        if (ob_get_length()) ob_clean();

        // S'assurer qu'il n'y a pas de BOM
        ob_start();
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $output = ob_get_clean();
        
        // Supprimer tout BOM ou espace au début
        $output = preg_replace('/^[\x{FEFF}\s]+/u', '', $output);
        
        echo $output;
        exit;
    }

    public function getGroupMessages($groupId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            error_log("Récupération des messages pour le groupe " . $groupId);
            $response = $this->apiService->makeRequest("messages/" . $groupId . "/history", "GET");
            error_log("Réponse de l'API: " . json_encode($response));

            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendGroupMessage($groupId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $content = $_POST['content'] ?? '';
        $file = $_FILES['attachment'] ?? null;

        // Vérifier qu'il y a au moins un contenu ou un fichier
        if (empty($content) && empty($file)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Le message doit contenir du texte ou une pièce jointe'
            ], 400);
        }

        try {
            error_log("Envoi d'un message au groupe " . $groupId);
            error_log("Contenu: " . $content);

            // Préparer les données pour l'API
            $postData = [
                'content' => $content,
                'group_id' => intval($groupId)
            ];

            // Si un fichier est présent, l'ajouter directement aux données
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                error_log("Ajout du fichier: " . print_r($file, true));
                $postData['attachment'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
            }

            // Envoyer le message avec le fichier si présent
            $response = $this->apiService->makeRequestWithFile("messages/{$groupId}/send", "POST", $postData);
            error_log("Données envoyées à l'API: " . json_encode($postData));
            error_log("Réponse de l'API: " . json_encode($response));

            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'envoi du message'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateMessage($messageId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $input = json_decode(file_get_contents('php://input'), true);
            $content = $input['content'] ?? '';
            
            if (empty($content)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Le contenu du message ne peut pas être vide"
                ]);
                return;
            }
            
            $response = $this->apiService->makeRequest("messages/{$messageId}/update", "PUT", ['content' => $content]);
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Message modifié avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la modification: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la modification: " . $e->getMessage()
            ]);
        }
    }
    
    public function deleteMessage($messageId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $response = $this->apiService->makeRequest("messages/{$messageId}/delete", "DELETE");
            
            if ($response['success']) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Message supprimé avec succès"
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la suppression: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la suppression: " . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Nettoie la sortie des caractères BOM et autres caractères invisibles
     */
    private function cleanOutput() {
        // Nettoyer le buffer de sortie
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Définir les headers pour éviter les caractères BOM
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    }

    private function ensureAuthenticated() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            throw new Exception("Non authentifié");
        }

        // Vérifier si nous avons un token API valide
        if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
            throw new Exception("Token API non trouvé");
        }

        // Le token est déjà géré par ApiService, pas besoin de le réinitialiser ici
    }

    public function downloadMessageAttachment($messageId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            // Appeler l'API pour récupérer le fichier
            $response = $this->apiService->makeRequest("messages/{$messageId}/attachment", "GET");
            
            if ($response['success']) {
                // L'API retourne directement le contenu du fichier
                $rawResponse = $response['raw_response'] ?? '';
                
                // Vérifier si c'est du contenu binaire
                if (!empty($rawResponse) && !json_decode($rawResponse, true)) {
                    // C'est du contenu binaire, le servir directement
                    
                    // Déterminer le type MIME basé sur le contenu
                    $mimeType = 'application/octet-stream';
                    if (strpos($rawResponse, '%PDF-') === 0) {
                        $mimeType = 'application/pdf';
                    } elseif (strpos($rawResponse, "\xFF\xD8\xFF") === 0) {
                        $mimeType = 'image/jpeg';
                    } elseif (strpos($rawResponse, "\x89PNG") === 0) {
                        $mimeType = 'image/png';
                    }
                    
                    // Nettoyer la sortie et définir les headers appropriés
                    if (ob_get_level()) {
                        ob_clean();
                    }
                    
                    header('Content-Type: ' . $mimeType);
                    header('Content-Disposition: attachment; filename="attachment_' . $messageId . '"');
                    header('Content-Length: ' . strlen($rawResponse));
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    
                    echo $rawResponse;
                    exit;
                } else {
                    // C'est du JSON, essayer de trouver une URL
                    $data = $response['data'] ?? [];
                    $downloadUrl = null;
                    
                    // Essayer différents chemins possibles pour l'URL
                    if (isset($data['attachment']['url'])) {
                        $downloadUrl = $data['attachment']['url'];
                    } elseif (isset($data['url'])) {
                        $downloadUrl = $data['url'];
                    } elseif (isset($data['path'])) {
                        $downloadUrl = $data['path'];
                    }
                    
                    if ($downloadUrl) {
                        // Si l'URL est relative, la rendre absolue
                        if (strpos($downloadUrl, 'http') !== 0) {
                            $baseUrl = rtrim($this->baseUrl, '/api');
                            $downloadUrl = $baseUrl . '/' . ltrim($downloadUrl, '/');
                        }
                        
                        header("Location: " . $downloadUrl);
                        exit;
                    } else {
                        $this->cleanOutput();
                        http_response_code(404);
                        echo json_encode([
                            "success" => false,
                            "message" => "URL de téléchargement non trouvée dans la réponse API"
                        ]);
                    }
                }
            } else {
                $this->cleanOutput();
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors du téléchargement: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            $this->cleanOutput();
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors du téléchargement: " . $e->getMessage()
            ]);
        }
    }
}
?>