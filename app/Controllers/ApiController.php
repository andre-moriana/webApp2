<?php

class ApiController {
    private $apiService;
    private $baseUrl;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
    }
    
    public function testMessages() {
        echo json_encode([
            'success' => true,
            'message' => 'Route messages fonctionne',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function addGroupMembers($groupId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userIds = $input['user_ids'] ?? [];

            if (empty($userIds)) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Aucun utilisateur sélectionné'
                ], 400);
            }

            $response = $this->apiService->makeRequest("groups/{$groupId}/members", "POST", ['user_ids' => $userIds]);
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'ajout des membres'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des membres: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function removeGroupMember($groupId, $memberId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("groups/{$groupId}/remove-member/{$memberId}", "DELETE");
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la suppression du membre'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du membre: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function users() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("users", "GET");
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la récupération des utilisateurs'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteEvent($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("events/{$eventId}", "DELETE");
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la suppression de l\'événement'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getUserAvatar($userId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            // Récupérer le chemin de l'image depuis les paramètres GET
            $imagePath = $_GET['path'] ?? '';
            if (empty($imagePath)) {
                $this->cleanOutput();
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Chemin de l'image manquant"
                ]);
                return;
            }

            // Construire l'URL complète vers l'API externe
            $externalUrl = $this->baseUrl.$imagePath;
            
            // Faire une requête pour récupérer l'image avec authentification
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $externalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Ajouter le token d'authentification si disponible
            if (isset($_SESSION['token'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $_SESSION['token']
                ]);
            }
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            if ($httpCode === 200 && $imageData !== false) {
                // Nettoyer la sortie et définir les headers appropriés
                $this->cleanOutput();
                header('Content-Type: ' . $contentType);
                header('Content-Length: ' . strlen($imageData));
                header('Cache-Control: public, max-age=3600'); // Cache pendant 1 heure
                
                // Afficher l'image
                echo $imageData;
            } else {
                // Si l'image n'est pas trouvée, retourner une image par défaut
                $this->returnDefaultAvatar();
            }
        } catch (Exception $e) {
            $this->returnDefaultAvatar();
        }
    }
    
    private function returnDefaultAvatar() {
        // Créer une image SVG par défaut
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <circle cx="16" cy="16" r="16" fill="#6c757d"/>
            <text x="16" y="20" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="14" font-weight="bold">?</text>
        </svg>';
        
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=3600');
        echo $svg;
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

            // Préparer les données pour l'upload
            // Ne pas envoyer user_id car ce champ n'existe pas dans la table
            $documentData = [
                'name' => $_POST['name'] ?? '',
                'uploaded_by' => (int)$userId  // Seulement uploaded_by
            ];
            // Utiliser makeRequest avec les fichiers
            $response = $this->apiService->makeRequestWithFile("documents/{$userId}/upload", "POST", $documentData, $_FILES['document']);
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
                            $rawResponse = substr($rawResponse, 0, $jpegEnd);
                        }
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
            $response = $this->apiService->makeRequest("messages/" . $groupId . "/history", "GET");
            // Traiter la réponse pour retourner directement un tableau de messages
            if ($response['success']) {
                // L'API retourne directement un tableau de messages
                $messages = [];
                if (is_array($response['data'])) {
                    $messages = $response['data'];
                } elseif (is_array($response)) {
                    $messages = $response;
                }
                
                $this->sendJsonResponse($messages);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la récupération des messages'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
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
            // Préparer les données pour l'API
            $postData = [
                'content' => $content,
                'group_id' => intval($groupId)
            ];

            // Si un fichier est présent, l'ajouter directement aux données
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $postData['attachment'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
            }

            // Envoyer le message avec le fichier si présent
            $response = $this->apiService->makeRequestWithFile("messages/{$groupId}/send", "POST", $postData);
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'envoi du message'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
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
            
            $response = $this->apiService->makeRequest("messages/{$messageId}", "PUT", ['content' => $content]);
            
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
            
            $response = $this->apiService->makeRequest("messages/{$messageId}", "DELETE");
            
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

    public function getMessageImage($messageId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            // Récupérer l'URL de l'image depuis les paramètres GET
            $imageUrl = $_GET['url'] ?? '';
            if (empty($imageUrl)) {
                $this->cleanOutput();
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "URL de l'image manquante"
                ]);
                return;
            }
            
            // S'assurer que l'URL pointe vers l'API externe
            if (strpos($imageUrl, $this->baseUrl) !== 0) {
                // Si l'URL est relative, la rendre absolue
                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = rtrim($this->baseUrl, '/api') . '/' . ltrim($imageUrl, '/');
                }
            }
            // Faire une requête pour récupérer l'image
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Ajouter le token d'authentification si disponible
            if (isset($_SESSION['token'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $_SESSION['token']
                ]);
            }
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($imageData)) {
                // Nettoyer la sortie et définir les headers appropriés pour l'affichage
                if (ob_get_level()) {
                    ob_clean();
                }
                
                header('Content-Type: ' . ($contentType ?: 'image/jpeg'));
                header('Content-Length: ' . strlen($imageData));
                header('Cache-Control: public, max-age=3600'); // Cache pour 1 heure
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                
                echo $imageData;
                exit;
            } else {
                $this->cleanOutput();
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Image non trouvée"
                ]);
            }
        } catch (Exception $e) {
            $this->cleanOutput();
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la récupération de l'image: " . $e->getMessage()
            ]);
        }
    }

    public function getEventMessages($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("events/" . $eventId . "/messages", "GET");
            // Traiter la réponse comme pour les groupes
            if ($response['success']) {
                // L'API retourne directement un tableau de messages
                $messages = [];
                if (is_array($response['data'])) {
                    $messages = $response['data'];
                } elseif (is_array($response)) {
                    $messages = $response;
                }
                
                $this->sendJsonResponse($messages);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la récupération des messages'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendEventMessage($eventId) {
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
            // Préparer les données pour l'API
            // Utiliser group_id car l'API externe traite les événements comme des groupes
            $postData = [
                'content' => $content,
                'group_id' => intval($eventId)
            ];

            // Si un fichier est présent, l'ajouter directement aux données
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $postData['attachment'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
            }

            // Envoyer le message avec le fichier si présent
            // Utiliser l'endpoint spécifique aux événements
            $response = $this->apiService->makeRequestWithFile("messages/event/{$eventId}/send", "POST", $postData);
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'envoi du message'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEvent($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("events/" . $eventId, "GET");

            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'événement: ' . $e->getMessage()
            ], 500);
        }
    }

    public function joinEvent($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("events/" . $eventId . "/join", "POST");

            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription à l\'événement: ' . $e->getMessage()
            ], 500);
        }
    }

    public function leaveEvent($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("events/" . $eventId . "/leave", "POST");

            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la désinscription de l\'événement: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateEventMessage($messageId) {
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
            
            $response = $this->apiService->makeRequest("messages/{$messageId}", "PUT", ['content' => $content]);
            
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
    
    public function deleteEventMessage($messageId) {
        try {
            // S'assurer qu'on est authentifié
            $this->ensureAuthenticated();
            
            $response = $this->apiService->makeRequest("messages/{$messageId}", "DELETE");
            
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
}
?>