<?php

class ApiController {
    private $apiService;
    private $baseUrl;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->baseUrl = $_ENV["API_BASE_URL"];
    }
    
    /**
     * Vérifie l'authentification de l'utilisateur via la session
     * Retourne true si authentifié, false sinon
     */
    private function isAuthenticated() {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        error_log("[Auth Check] Session ID: " . session_id());
        error_log("[Auth Check] Session logged_in: " . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'not set'));
        error_log("[Auth Check] Session user: " . (isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'not set'));
        
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Envoie une réponse d'erreur d'authentification
     */
    private function sendUnauthenticatedResponse() {
        error_log("[Auth] Utilisateur non authentifié - envoi erreur 401");
        $this->sendJsonResponse([
            'success' => false,
            'message' => 'Non authentifié. Veuillez vous reconnecter.'
        ], 401);
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
    
    public function createGroup() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], 400);
                return;
            }
            
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;
            
            if (empty($name)) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Le nom du groupe est requis'
                ], 400);
                return;
            }
            
            // Utiliser l'ApiService pour créer le groupe
            $response = $this->apiService->createGroup([
                'name' => $name,
                'description' => $description,
                'is_private' => $isPrivate
            ]);
            
            if ($response['success']) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Groupe créé avec succès',
                    'data' => $response['data'] ?? null
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de la création du groupe'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du groupe: ' . $e->getMessage()
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
            // Vérifier si on recherche par numéro de licence
            $licenseNumber = $_GET['licence_number'] ?? null;
            
            if ($licenseNumber) {
                // Recherche spécifique par numéro de licence
                $endpoint = "users?licence_number=" . urlencode(trim($licenseNumber));
                error_log("API WebApp2 - Recherche utilisateur par licence: " . $licenseNumber);
                error_log("API WebApp2 - Endpoint: " . $endpoint);
                
                $response = $this->apiService->makeRequest($endpoint, "GET");
                
                // Log pour debug complet
                error_log("API WebApp2 - Réponse API brute (type): " . gettype($response));
                error_log("API WebApp2 - Réponse API brute (contenu): " . json_encode($response, JSON_PRETTY_PRINT));
                
                // Vérifier la structure de la réponse
                if (!isset($response['data'])) {
                    error_log("API WebApp2 - ERREUR: Pas de clé 'data' dans la réponse");
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => 'Erreur: réponse API invalide (pas de data)'
                    ], 500);
                    return;
                }
                
                $apiData = $response['data'];
                
                // L'API backend retourne {success: true, data: user} qui est encapsulé dans {success: true, data: {success: true, data: user}}
                // Donc response['data'] contient {success: true, data: user}
                if (is_array($apiData) && isset($apiData['success'])) {
                    // C'est la structure de l'API backend
                    if ($apiData['success'] && isset($apiData['data'])) {
                        // Tout est bon, retourner la réponse de l'API backend directement
                        error_log("API WebApp2 - Utilisateur trouvé, retour de la réponse");
                        $this->sendJsonResponse($apiData);
                    } else {
                        // Utilisateur non trouvé
                        error_log("API WebApp2 - Utilisateur non trouvé (success: false)");
                        $this->sendJsonResponse([
                            'success' => false,
                            'message' => $apiData['message'] ?? 'Utilisateur non trouvé avec ce numéro de licence'
                        ], $response['status_code'] ?? 404);
                    }
                } else if (is_array($apiData) && isset($apiData['id'])) {
                    // La structure est directement l'utilisateur (sans wrapper success/data)
                    error_log("API WebApp2 - Structure utilisateur directe détectée");
                    $this->sendJsonResponse([
                        'success' => true,
                        'data' => $apiData
                    ]);
                } else {
                    // Structure inattendue
                    error_log("API WebApp2 - ERREUR: Structure inattendue - " . json_encode($apiData));
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => 'Format de réponse inattendu de l\'API: ' . json_encode($apiData)
                    ], 500);
                }
            } else {
                // Liste normale des utilisateurs
                $response = $this->apiService->makeRequest("users", "GET");
                
                if ($response['success']) {
                    $this->sendJsonResponse($response);
                } else {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => $response['message'] ?? 'Erreur lors de la récupération des utilisateurs'
                    ], $response['status_code'] ?? 500);
                }
            }
        } catch (Exception $e) {
            error_log("Erreur dans ApiController::users: " . $e->getMessage());
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
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Chemin de l'image manquant"
                ]);
                exit;
            }

            // Nettoyer le chemin de l'image (enlever le préfixe /uploads/ si présent)
            $cleanPath = $imagePath;
            if (strpos($cleanPath, '/uploads/') === 0) {
                $cleanPath = substr($cleanPath, strlen('/uploads/'));
            } elseif (strpos($cleanPath, 'uploads/') === 0) {
                $cleanPath = substr($cleanPath, strlen('uploads/'));
            }
            
            // Construire l'URL directe vers l'image sur le serveur backend
            $baseUrlWithoutApi = rtrim($this->baseUrl, '/api');
            $baseUrlClean = rtrim($baseUrlWithoutApi, '/');
            $directUrl = $baseUrlClean . '/uploads/' . $cleanPath;
            
            error_log("DEBUG getUserAvatar: userId=$userId, imagePath=$imagePath, cleanPath=$cleanPath, directUrl=$directUrl");
            
            // Essayer d'abord l'URL directe (plus rapide et plus fiable selon l'utilisateur)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $directUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            // Configuration SSL si l'URL utilise HTTPS
            if (strpos($directUrl, 'https://') === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            
            $imageData = curl_exec($ch);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            // Si l'URL directe ne fonctionne pas, essayer l'API backend
            if ($httpCode !== 200 || $imageData === false || !empty($curlError)) {
                error_log("DEBUG getUserAvatar: URL directe échouée - Code: $httpCode, Errno: $curlErrno, Error: $curlError, tentative API");
                
                // Essayer avec l'API backend /users/{id}/profile-image
                $apiBaseUrl = $this->baseUrl; // Contient déjà /api
                $externalUrl = $apiBaseUrl . '/users/' . $userId . '/profile-image';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $externalUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                
                if (strpos($externalUrl, 'https://') === 0) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }
                
                // Ajouter le token d'authentification si disponible
                $headers = [];
                if (isset($_SESSION['token'])) {
                    $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
                }
                if (!empty($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
                
                $imageData = curl_exec($ch);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);
                
                if ($curlErrno || $httpCode !== 200) {
                    error_log("DEBUG getUserAvatar: Erreur API cURL - Code: $httpCode, Errno: $curlErrno, Error: $curlError, URL: $externalUrl");
                }
            }
            
            // Vérifier si on a réussi à récupérer l'image
            if ($httpCode === 200 && $imageData !== false && !empty($imageData) && empty($curlError)) {
                // Vérifier que ce n'est pas une réponse JSON d'erreur
                $firstChar = substr(trim($imageData), 0, 1);
                if ($firstChar === '{' || $firstChar === '[') {
                    // C'est probablement du JSON, pas une image
                    error_log("DEBUG getUserAvatar: Réponse JSON reçue au lieu d'une image: " . substr($imageData, 0, 200));
                    $this->returnDefaultAvatar();
                    return;
                }
                
                // Nettoyer le buffer de sortie
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Déterminer le Content-Type si non détecté automatiquement
                if (empty($contentType) || strpos($contentType, 'text/html') !== false || strpos($contentType, 'application/json') !== false) {
                    // Détecter le type depuis l'extension du fichier ou le contenu
                    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                    switch ($extension) {
                        case 'jpg':
                        case 'jpeg':
                            $contentType = 'image/jpeg';
                            break;
                        case 'png':
                            $contentType = 'image/png';
                            break;
                        case 'gif':
                            $contentType = 'image/gif';
                            break;
                        case 'webp':
                            $contentType = 'image/webp';
                            break;
                        default:
                            // Détecter depuis le contenu (magic bytes)
                            if (substr($imageData, 0, 2) === "\xFF\xD8") {
                                $contentType = 'image/jpeg';
                            } elseif (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
                                $contentType = 'image/png';
                            } elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
                                $contentType = 'image/gif';
                            } else {
                                $contentType = 'image/jpeg'; // Par défaut
                            }
                    }
                }
                
                header('Content-Type: ' . $contentType);
                header('Content-Length: ' . strlen($imageData));
                header('Cache-Control: public, max-age=3600'); // Cache pendant 1 heure
                
                // Afficher l'image
                echo $imageData;
                exit;
            } else {
                // Si l'image n'est pas trouvée, retourner une image par défaut
                error_log("DEBUG getUserAvatar: Image non trouvée ou erreur - HTTP: $httpCode, Error: $curlError");
                $this->returnDefaultAvatar();
            }
        } catch (Exception $e) {
            error_log("DEBUG getUserAvatar: Exception: " . $e->getMessage());
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
                        if (strpos($downloadUrl, 'https') !== 0) {
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
        // Nettoyer toute sortie précédente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Forcer le type de contenu et l'encodage
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        // Encoder en JSON sans BOM
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // S'assurer qu'il n'y a pas de BOM au début
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
        $json = preg_replace('/^[\x{FEFF}\s]+/u', '', $json);
        
        // Envoyer la réponse
        echo $json;
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
                
                // Corriger les URLs des pièces jointes pour pointer vers /uploads/messages
                foreach ($messages as &$message) {
                    if (isset($message['attachment']) && is_array($message['attachment'])) {
                        $attachment = &$message['attachment'];
                        
                        // Si storedFilename existe, construire l'URL correcte
                        if (isset($attachment['storedFilename'])) {
                            $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $attachment['storedFilename'];
                        }
                        // Sinon extraire depuis url existant
                        elseif (isset($attachment['url'])) {
                            // Si l'URL contient un paramètre url=, l'extraire et utiliser le chemin tel quel
                            if (strpos($attachment['url'], '?') !== false && strpos($attachment['url'], 'url=') !== false) {
                                $urlParts = parse_url($attachment['url']);
                                if (isset($urlParts['query'])) {
                                    parse_str($urlParts['query'], $queryParams);
                                    if (isset($queryParams['url'])) {
                                        $decodedPath = urldecode($queryParams['url']);
                                        // Corriger les URLs incomplètes qui pointent vers /uploads/ sans le dossier messages/events
                                        if (preg_match('#^/uploads/([^/]+\.(pdf|jpg|jpeg|png|gif|bmp|webp|svg))$#i', $decodedPath, $fileMatches)) {
                                            $decodedPath = '/uploads/messages/' . $fileMatches[1];
                                        }
                                        // Le chemin contient déjà /uploads/... on ajoute juste le domaine
                                        $attachment['url'] = 'https://api.arctraining.fr' . $decodedPath;
                                    }
                                }
                            }
                            // Corriger les URLs qui sont juste /uploads/filename.pdf
                            elseif (preg_match('#^/uploads/([^/]+\.(pdf|jpg|jpeg|png|gif|bmp|webp|svg))$#i', $attachment['url'], $fileMatches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $fileMatches[1];
                            }
                            // Sinon extraire le nom de fichier (hash.extension)
                            elseif (preg_match('/\/([a-f0-9]{32}\.[a-zA-Z0-9]+)(?:\?|$)/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                            elseif (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                            // Si l'URL ne contient pas /uploads/messages/ ou /uploads/events/, essayer de la corriger
                            elseif (strpos($attachment['url'], '/uploads/messages/') === false && strpos($attachment['url'], '/uploads/events/') === false) {
                                // Extraire le nom du fichier
                                if (preg_match('#/([^/]+\.(pdf|jpg|jpeg|png|gif|bmp|webp|svg))(?:\?|$)#i', $attachment['url'], $fileMatches)) {
                                    $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $fileMatches[1];
                                }
                            }
                        }
                        // Sinon extraire depuis path
                        elseif (isset($attachment['path'])) {
                            if (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['path'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                        }
                    }
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
        if (!$this->isAuthenticated()) {
            $this->sendUnauthenticatedResponse();
            return;
        }

        $content = $_POST['content'] ?? '';
        $file = $_FILES['attachment'] ?? null;

        error_log("[WebApp] sendGroupMessage - GroupId: {$groupId}");
        error_log("[WebApp] sendGroupMessage - Content: " . ($content ?: '(vide)'));
        error_log("[WebApp] sendGroupMessage - File present: " . ($file ? 'Oui' : 'Non'));
        if ($file) {
            error_log("[WebApp] sendGroupMessage - File error: " . ($file['error'] ?? 'N/A'));
            error_log("[WebApp] sendGroupMessage - File name: " . ($file['name'] ?? 'N/A'));
        }

        // Vérifier qu'il y a au moins un contenu ou un fichier valide
        $hasFile = $file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
        if (empty($content) && !$hasFile) {
            error_log("[WebApp] sendGroupMessage - Erreur: Ni contenu ni fichier valide");
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Le message doit contenir du texte ou une pièce jointe'
            ], 400);
            return;
        }

        try {
            // Préparer les données pour l'API
            $postData = [
                'content' => $content,
                'group_id' => intval($groupId)
            ];

            // Si un fichier est présent, l'ajouter directement aux données
            if ($hasFile) {
                error_log("[WebApp] sendGroupMessage - Ajout du fichier à la requête");
                $postData['attachment'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
            }

            // Envoyer le message avec le fichier si présent
            error_log("[WebApp] sendGroupMessage - Envoi à l'API Backend...");
            $response = $this->apiService->makeRequestWithFile("messages/{$groupId}/send", "POST", $postData);
            error_log("[WebApp] sendGroupMessage - Réponse reçue: " . json_encode($response));
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'envoi du message'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            error_log("[WebApp] sendGroupMessage - Exception: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateMessage($messageId) {
        if (!$this->isAuthenticated()) {
            $this->sendUnauthenticatedResponse();
            return;
        }

        error_log("[WebApp] updateMessage - MessageId: {$messageId}");
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $content = $input['content'] ?? '';
            
            error_log("[WebApp] updateMessage - New content: " . ($content ?: '(vide)'));
            
            if (empty($content)) {
                error_log("[WebApp] updateMessage - Erreur: contenu vide");
                $this->sendJsonResponse([
                    "success" => false,
                    "message" => "Le contenu du message ne peut pas être vide"
                ], 400);
                return;
            }
            
            $response = $this->apiService->makeRequest("messages/{$messageId}", "PUT", ['content' => $content]);
            error_log("[WebApp] updateMessage - Réponse: " . json_encode($response));
            
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
        if (!$this->isAuthenticated()) {
            $this->sendUnauthenticatedResponse();
            return;
        }

        error_log("[WebApp] deleteMessage - MessageId: {$messageId}");
        
        try {
            $response = $this->apiService->makeRequest("messages/{$messageId}", "DELETE");
            error_log("[WebApp] deleteMessage - Réponse: " . json_encode($response));
            
            if ($response['success']) {
                $this->sendJsonResponse([
                    "success" => true,
                    "message" => "Message supprimé avec succès"
                ]);
            } else {
                $this->sendJsonResponse([
                    "success" => false,
                    "message" => "Erreur lors de la suppression: " . ($response['message'] ?? 'Erreur inconnue')
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            error_log("[WebApp] deleteMessage - Exception: " . $e->getMessage());
            $this->sendJsonResponse([
                "success" => false,
                "message" => "Erreur lors de la suppression: " . $e->getMessage()
            ], 500);
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
            
            // Vérifier si on doit afficher inline (pour les PDF)
            $inline = isset($_GET['inline']) && $_GET['inline'] == '1';
            $imageUrl = $_GET['url'] ?? '';
            
            // Si une URL est fournie, utiliser la même logique que pour les images
            if (!empty($imageUrl)) {
                // Décoder l'URL si elle est encodée
                $imageUrl = urldecode($imageUrl);
                
                // S'assurer que l'URL pointe vers l'API externe
                $baseUrlWithoutApi = rtrim($this->baseUrl, '/api');
                $baseUrlClean = rtrim($baseUrlWithoutApi, '/');
                
                // Si baseUrlClean est vide, utiliser l'URL par défaut
                if (empty($baseUrlClean)) {
                    $baseUrlClean = 'https://api.arctraining.fr';
                }
                
                // Si l'URL ne commence pas par https://, la rendre absolue
                if (strpos($imageUrl, 'https://') !== 0) {
                    // Corriger les URLs incomplètes qui pointent vers /uploads/ sans le dossier messages/events
                    if (preg_match('#^/uploads/([^/]+\.(pdf|jpg|jpeg|png|gif|bmp|webp|svg))$#i', $imageUrl, $matches)) {
                        // C'est un fichier directement dans /uploads/, pas dans un sous-dossier
                        $filename = $matches[1];
                        $imageUrl = '/uploads/messages/' . $filename;
                    }
                    
                    // Toujours rendre l'URL absolue
                    $imageUrl = $baseUrlClean . '/' . ltrim($imageUrl, '/');
                }
                
                // Faire une requête pour récupérer le fichier
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
                
                $fileData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                // Si le téléchargement échoue et que l'URL contient /uploads/messages/, essayer /uploads/events/
                if ($httpCode !== 200 && strpos($imageUrl, '/uploads/messages/') !== false) {
                    $imageUrl = str_replace('/uploads/messages/', '/uploads/events/', $imageUrl);
                    
                    // Réessayer avec le nouveau chemin
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $imageUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    
                    if (isset($_SESSION['token'])) {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $_SESSION['token']
                        ]);
                    }
                    
                    $fileData = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                    curl_close($ch);
                }
                
                // Inversement, si l'URL contient /uploads/events/ et échoue, essayer /uploads/messages/
                if ($httpCode !== 200 && strpos($imageUrl, '/uploads/events/') !== false) {
                    $imageUrl = str_replace('/uploads/events/', '/uploads/messages/', $imageUrl);
                    
                    // Réessayer avec le nouveau chemin
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $imageUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    
                    if (isset($_SESSION['token'])) {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $_SESSION['token']
                        ]);
                    }
                    
                    $fileData = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                }
                
                // Si toujours une erreur, essayer de récupérer le message via l'API pour obtenir l'URL correcte
                if ($httpCode !== 200 && !empty($messageId)) {
                    error_log("DEBUG downloadMessageAttachment: Échec du téléchargement direct, tentative via API. URL essayée: " . $imageUrl . ", HTTP Code: " . $httpCode);
                    
                    // Essayer de récupérer le message via l'API pour obtenir l'URL correcte de l'attachment
                    $messageResponse = $this->apiService->makeRequest("messages/{$messageId}", "GET");
                    if ($messageResponse['success'] && isset($messageResponse['data']['attachment'])) {
                        $attachment = $messageResponse['data']['attachment'];
                        $correctUrl = null;
                        
                        // Essayer différents champs pour trouver l'URL
                        if (isset($attachment['url'])) {
                            $correctUrl = $attachment['url'];
                        } elseif (isset($attachment['path'])) {
                            $correctUrl = $attachment['path'];
                        } elseif (isset($attachment['storedFilename'])) {
                            // Essayer les deux dossiers
                            $correctUrl = 'https://api.arctraining.fr/uploads/messages/' . $attachment['storedFilename'];
                        }
                        
                        if ($correctUrl) {
                            // S'assurer que l'URL est complète
                            if (strpos($correctUrl, 'https') !== 0) {
                                $baseUrlClean = rtrim(rtrim($this->baseUrl, '/api'), '/');
                                $correctUrl = $baseUrlClean . '/' . ltrim($correctUrl, '/');
                            }
                            
                            error_log("DEBUG downloadMessageAttachment: URL corrigée depuis l'API: " . $correctUrl);
                            
                            // Réessayer avec l'URL de l'API
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $correctUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                            
                            if (isset($_SESSION['token'])) {
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    'Authorization: Bearer ' . $_SESSION['token']
                                ]);
                            }
                            
                            $fileData = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                            $curlError = curl_error($ch);
                            curl_close($ch);
                            
                            // Si ça échoue encore, essayer l'autre dossier
                            if ($httpCode !== 200 && strpos($correctUrl, '/uploads/messages/') !== false) {
                                $correctUrl = str_replace('/uploads/messages/', '/uploads/events/', $correctUrl);
                                error_log("DEBUG downloadMessageAttachment: Essai avec /uploads/events/: " . $correctUrl);
                                
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $correctUrl);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                
                                if (isset($_SESSION['token'])) {
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Authorization: Bearer ' . $_SESSION['token']
                                    ]);
                                }
                                
                                $fileData = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                                curl_close($ch);
                            }
                        }
                    }
                }
                
                if ($httpCode === 200 && !empty($fileData)) {
                    // Déterminer le type MIME
                    $mimeType = $contentType ?: 'application/octet-stream';
                    if (strpos($fileData, '%PDF-') === 0) {
                        $mimeType = 'application/pdf';
                    }
                    
                    // Nettoyer la sortie et définir les headers appropriés
                    if (ob_get_level()) {
                        ob_clean();
                    }
                    
                    header('Content-Type: ' . $mimeType);
                    
                    // Ajouter les headers CORS pour permettre l'affichage dans un iframe
                    header('Access-Control-Allow-Origin: *');
                    header('Access-Control-Allow-Methods: GET, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('X-Content-Type-Options: nosniff');
                    
                    // Pour les PDF en mode inline, utiliser 'inline', sinon 'attachment'
                    if ($inline && $mimeType === 'application/pdf') {
                        header('Content-Disposition: inline; filename="attachment_' . $messageId . '.pdf"');
                    } else {
                        header('Content-Disposition: attachment; filename="attachment_' . $messageId . '"');
                    }
                    
                    header('Content-Length: ' . strlen($fileData));
                    header('Cache-Control: public, max-age=3600');
                    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                    
                    echo $fileData;
                    exit;
                } else {
                    // Si le téléchargement a échoué, retourner une erreur détaillée
                    $errorMessage = "Erreur HTTP " . $httpCode;
                    if (!empty($curlError)) {
                        $errorMessage .= ": " . $curlError;
                    }
                    $errorMessage .= " (URL: " . $imageUrl . ")";
                    error_log("DEBUG downloadMessageAttachment: Échec du téléchargement - " . $errorMessage);
                    
                    $this->cleanOutput();
                    http_response_code(404);
                    echo json_encode([
                        "success" => false,
                        "message" => "Erreur lors du téléchargement: " . $errorMessage
                    ]);
                    exit;
                }
            }
            
            // Méthode originale : appeler l'API pour récupérer le fichier
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
                    
                    // Ajouter les headers CORS pour permettre l'affichage dans un iframe
                    header('Access-Control-Allow-Origin: *');
                    header('Access-Control-Allow-Methods: GET, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('X-Content-Type-Options: nosniff');
                    
                    // Pour les PDF en mode inline, utiliser 'inline', sinon 'attachment'
                    if ($inline && $mimeType === 'application/pdf') {
                        header('Content-Disposition: inline; filename="attachment_' . $messageId . '.pdf"');
                    } else {
                        header('Content-Disposition: attachment; filename="attachment_' . $messageId . '"');
                    }
                    
                    header('Content-Length: ' . strlen($rawResponse));
                    header('Cache-Control: public, max-age=3600');
                    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                    
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
                        if (strpos($downloadUrl, 'https') !== 0) {
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
                $errorMessage = "Erreur lors du téléchargement: " . ($response['message'] ?? 'Erreur inconnue');
                if (!empty($imageUrl)) {
                    $errorMessage .= " (URL: " . $imageUrl . ")";
                }
                if (isset($httpCode) && $httpCode !== 200) {
                    $errorMessage .= " (HTTP " . $httpCode . ")";
                }
                if (isset($curlError) && !empty($curlError)) {
                    $errorMessage .= " (cURL: " . $curlError . ")";
                }
                error_log("DEBUG downloadMessageAttachment: " . $errorMessage);
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => $errorMessage
                ]);
            }
        } catch (Exception $e) {
            $this->cleanOutput();
            $errorMessage = "Erreur lors du téléchargement: " . $e->getMessage();
            if (!empty($imageUrl)) {
                $errorMessage .= " (URL: " . $imageUrl . ")";
            }
            error_log("DEBUG downloadMessageAttachment Exception: " . $errorMessage);
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $errorMessage
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
            
            // Décoder l'URL si elle est encodée
            $imageUrl = urldecode($imageUrl);
            
            // S'assurer que l'URL pointe vers l'API externe
            $baseUrlWithoutApi = rtrim($this->baseUrl, '/api');
            $baseUrlClean = rtrim($baseUrlWithoutApi, '/');
            
            // Si l'URL est relative, la rendre absolue
            if (strpos($imageUrl, 'https') !== 0) {
                // URL relative, la rendre absolue
                $imageUrl = $baseUrlClean . '/' . ltrim($imageUrl, '/');
            } elseif (strpos($imageUrl, $baseUrlClean) === false && strpos($imageUrl, $this->baseUrl) === false) {
                // URL absolue mais qui ne pointe pas vers notre API, on l'utilise telle quelle
                // (peut être une URL externe valide)
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
                
                // Corriger les URLs des pièces jointes pour pointer vers /uploads/events
                foreach ($messages as &$message) {
                    if (isset($message['attachment']) && is_array($message['attachment'])) {
                        $attachment = &$message['attachment'];
                        
                        // Si storedFilename existe, construire l'URL correcte vers /uploads/events
                        if (isset($attachment['storedFilename'])) {
                            $attachment['url'] = 'https://api.arctraining.fr/uploads/events/' . $attachment['storedFilename'];
                        }
                        // Sinon extraire depuis url existant
                        elseif (isset($attachment['url'])) {
                            // Si l'URL contient un paramètre url=, l'extraire et utiliser le chemin tel quel
                            if (strpos($attachment['url'], '?') !== false && strpos($attachment['url'], 'url=') !== false) {
                                $urlParts = parse_url($attachment['url']);
                                if (isset($urlParts['query'])) {
                                    parse_str($urlParts['query'], $queryParams);
                                    if (isset($queryParams['url'])) {
                                        $decodedPath = urldecode($queryParams['url']);
                                        // Si le chemin contient /uploads/messages, le remplacer par /uploads/events
                                        if (strpos($decodedPath, '/uploads/messages/') !== false) {
                                            $decodedPath = str_replace('/uploads/messages/', '/uploads/events/', $decodedPath);
                                        }
                                        // Le chemin contient déjà /uploads/... on ajoute juste le domaine
                                        $attachment['url'] = 'https://api.arctraining.fr' . $decodedPath;
                                    }
                                }
                            }
                            // Sinon extraire le nom de fichier (hash.extension) et utiliser /uploads/events
                            elseif (preg_match('/\/([a-f0-9]{32}\.[a-zA-Z0-9]+)(?:\?|$)/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/events/' . $matches[1];
                            }
                            elseif (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/events/' . $matches[1];
                            }
                            // Si l'URL contient /uploads/messages, la remplacer par /uploads/events
                            elseif (strpos($attachment['url'], '/uploads/messages/') !== false) {
                                $attachment['url'] = str_replace('/uploads/messages/', '/uploads/events/', $attachment['url']);
                            }
                        }
                        // Sinon extraire depuis path
                        elseif (isset($attachment['path'])) {
                            if (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['path'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/events/' . $matches[1];
                            }
                        }
                    }
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

    public function getTopicMessages($topicId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->makeRequest("messages/topic/{$topicId}/history", "GET");
            // Traiter la réponse comme pour les groupes
            if ($response['success']) {
                // L'API retourne directement un tableau de messages
                $messages = [];
                if (is_array($response['data'])) {
                    $messages = $response['data'];
                } elseif (is_array($response)) {
                    $messages = $response;
                }
                
                // Corriger les URLs des pièces jointes
                foreach ($messages as &$message) {
                    if (isset($message['attachment']) && is_array($message['attachment'])) {
                        $attachment = &$message['attachment'];
                        
                        // Si storedFilename existe, construire l'URL correcte
                        if (isset($attachment['storedFilename'])) {
                            $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $attachment['storedFilename'];
                        }
                        // Sinon extraire depuis url existant
                        elseif (isset($attachment['url'])) {
                            // Si l'URL contient un paramètre url=, l'extraire et utiliser le chemin tel quel
                            if (strpos($attachment['url'], '?') !== false && strpos($attachment['url'], 'url=') !== false) {
                                $urlParts = parse_url($attachment['url']);
                                if (isset($urlParts['query'])) {
                                    parse_str($urlParts['query'], $queryParams);
                                    if (isset($queryParams['url'])) {
                                        $decodedPath = urldecode($queryParams['url']);
                                        // Le chemin contient déjà /uploads/... on ajoute juste le domaine
                                        $attachment['url'] = 'https://api.arctraining.fr' . $decodedPath;
                                    }
                                }
                            }
                            // Sinon extraire le nom de fichier (hash.extension)
                            elseif (preg_match('/\/([a-f0-9]{32}\.[a-zA-Z0-9]+)(?:\?|$)/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                            elseif (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['url'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                        }
                        // Sinon extraire depuis path
                        elseif (isset($attachment['path'])) {
                            if (preg_match('/([a-f0-9]{32}\.[a-zA-Z0-9]+)$/i', $attachment['path'], $matches)) {
                                $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $matches[1];
                            }
                        }
                    }
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

    public function sendTopicMessage($topicId) {
        if (!$this->isAuthenticated()) {
            $this->sendUnauthenticatedResponse();
            return;
        }

        error_log("[WebApp] sendTopicMessage - TopicId: {$topicId}");
        
        // Vérifier si c'est un formulaire multipart (avec fichier)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;
        
        if ($isMultipart) {
            // Récupération depuis $_POST pour multipart/form-data
            $content = $_POST['content'] ?? '';
            $file = $_FILES['attachment'] ?? null;
            error_log("[WebApp] sendTopicMessage - Mode multipart - Content: " . ($content ?: '(vide)'));
            error_log("[WebApp] sendTopicMessage - File present: " . ($file ? 'Oui' : 'Non'));
        } else {
            // Récupération depuis JSON pour application/json
            $input = json_decode(file_get_contents('php://input'), true);
            $content = $input['content'] ?? '';
            $file = null;
            error_log("[WebApp] sendTopicMessage - Mode JSON - Content: " . ($content ?: '(vide)'));
        }

        // Vérifier qu'il y a au moins un contenu ou un fichier valide
        $hasFile = $file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
        if (empty($content) && !$hasFile) {
            error_log("[WebApp] sendTopicMessage - Erreur: Ni contenu ni fichier valide");
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Le message doit contenir du texte ou une pièce jointe'
            ], 400);
            return;
        }

        try {
            $postData = [
                'content' => $content
            ];

            // Si un fichier est présent, utiliser makeRequestWithFile
            if ($hasFile) {
                error_log("[WebApp] sendTopicMessage - Ajout du fichier à la requête");
                $postData['attachment'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
                $response = $this->apiService->makeRequestWithFile("messages/topic/{$topicId}/send", "POST", $postData);
            } else {
                $response = $this->apiService->makeRequest("messages/topic/{$topicId}/send", "POST", $postData);
            }
            
            error_log("[WebApp] sendTopicMessage - Réponse reçue: " . json_encode($response));
            
            if ($response['success']) {
                $this->sendJsonResponse($response);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $response['message'] ?? 'Erreur lors de l\'envoi du message'
                ], $response['status_code'] ?? 500);
            }
        } catch (Exception $e) {
            error_log("[WebApp] sendTopicMessage - Exception: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTopicForms($topicId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->getForms(null, null, $topicId);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des formulaires: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEventForms($eventId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->getForms(null, $eventId, null);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des formulaires: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getScoredTrainings() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $userId = $_GET['user_id'] ?? null;
            $response = $this->apiService->getScoredTrainings($userId);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tirs comptés: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTrainingProgress() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->getTrainingProgress();
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération du progrès: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTrainingDashboard($exerciseId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $userId = $_GET['user_id'] ?? null;
            $endpoint = "/training/dashboard/{$exerciseId}";
            if ($userId) {
                $endpoint .= "?user_id={$userId}";
            }
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération du dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserTrainingSessions($userId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            // Essayer d'abord l'endpoint des sessions
            $endpoint = "/training/sessions/user/{$userId}";
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            
            // Si ça ne marche pas, essayer l'endpoint alternatif
            if (!$response['success'] || empty($response['data'])) {
                $endpoint = "/training?action=sessions&user_id={$userId}";
                $response = $this->apiService->makeRequest($endpoint, 'GET');
            }
            
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getExercises() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $showHidden = isset($_GET['show_hidden']) && $_GET['show_hidden'] === 'true';
            $response = $this->apiService->getExercises($showHidden);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des exercices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getExerciseSheet($exerciseId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->getExerciseDetails($exerciseId);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'exercice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submitFormResponse($formId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $responses = $input['responses'] ?? [];

        if (empty($responses)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Les réponses sont requises'
            ], 400);
        }

        try {
            $response = $this->apiService->submitFormResponse($formId, $responses);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la soumission de la réponse: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFormResponses($formId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->getFormResponses($formId);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réponses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteForm($formId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        try {
            $response = $this->apiService->deleteForm($formId);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du formulaire: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createForm() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['questions']) || !is_array($input['questions'])) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Le titre et au moins une question sont requis'
            ], 400);
        }

        try {
            $response = $this->apiService->createForm($input);
            $this->sendJsonResponse($response);
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du formulaire: ' . $e->getMessage()
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
    
    /**
     * Récupère les messages d'un groupe
     */
    public function getGroupMessages($groupId) {
        try {
            error_log("[ApiController] getGroupMessages - groupId: {$groupId}");
            
            // Vérifier l'authentification
            if (!$this->isAuthenticated()) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Non authentifié. Veuillez vous reconnecter."
                ]);
                return;
            }
            
            // Appeler l'API backend pour récupérer les messages du groupe
            $response = $this->apiService->makeRequest("messages/{$groupId}/history", "GET");
            
            error_log("[ApiController] getGroupMessages - response: " . json_encode($response));
            
            if ($response['success']) {
                http_response_code(200);
                // Retourner directement les messages
                echo json_encode($response['data'] ?? []);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la récupération des messages: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            error_log("[ApiController] getGroupMessages - exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la récupération des messages: " . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Envoie un message dans un groupe
     */
    public function sendGroupMessage($groupId) {
        try {
            error_log("[ApiController] sendGroupMessage - groupId: {$groupId}");
            
            // Vérifier l'authentification
            if (!$this->isAuthenticated()) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Non authentifié. Veuillez vous reconnecter."
                ]);
                return;
            }
            
            // Gérer l'upload de fichier si présent
            $attachmentData = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $attachmentData = $_FILES['attachment'];
                error_log("[ApiController] sendGroupMessage - fichier détecté: " . $attachmentData['name']);
            }
            
            // Récupérer le contenu du message
            $content = $_POST['content'] ?? '';
            if (empty($content) && !isset($_FILES['attachment'])) {
                // Essayer de lire depuis le body JSON
                $rawInput = file_get_contents('php://input');
                $jsonData = json_decode($rawInput, true);
                if ($jsonData && isset($jsonData['content'])) {
                    $content = $jsonData['content'];
                }
            }
            
            error_log("[ApiController] sendGroupMessage - content: {$content}");
            error_log("[ApiController] sendGroupMessage - has attachment: " . ($attachmentData ? 'oui' : 'non'));
            
            if (empty($content) && !$attachmentData) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Le message ne peut pas être vide"
                ]);
                return;
            }
            
            // Préparer les données
            $messageData = [
                'content' => $content
            ];
            
            // Utiliser makeRequestWithFile si on a une pièce jointe
            if ($attachmentData) {
                $response = $this->apiService->makeRequestWithFile(
                    "messages/{$groupId}/send",
                    "POST",
                    $messageData,
                    $attachmentData
                );
            } else {
                $response = $this->apiService->makeRequest(
                    "messages/{$groupId}/send",
                    "POST",
                    $messageData
                );
            }
            
            error_log("[ApiController] sendGroupMessage - response: " . json_encode($response));
            
            if ($response['success']) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Message envoyé avec succès",
                    "data" => $response['data'] ?? null
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de l'envoi du message: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            error_log("[ApiController] sendGroupMessage - exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de l'envoi du message: " . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Récupère les messages d'un topic
     */
    public function getTopicMessages($topicId) {
        try {
            error_log("[ApiController] getTopicMessages - topicId: {$topicId}");
            
            // Vérifier l'authentification
            if (!$this->isAuthenticated()) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Non authentifié. Veuillez vous reconnecter."
                ]);
                return;
            }
            
            // Appeler l'API backend pour récupérer les messages du topic  
            $response = $this->apiService->makeRequest("messages/topic/{$topicId}/history", "GET");
            
            error_log("[ApiController] getTopicMessages - response: " . json_encode($response));
            
            if ($response['success']) {
                http_response_code(200);
                // Retourner directement les messages
                echo json_encode($response['data'] ?? []);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de la récupération des messages: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            error_log("[ApiController] getTopicMessages - exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de la récupération des messages: " . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Envoie un message dans un topic
     */
    public function sendTopicMessage($topicId) {
        try {
            error_log("[ApiController] sendTopicMessage - topicId: {$topicId}");
            
            // Vérifier l'authentification
            if (!$this->isAuthenticated()) {
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Non authentifié. Veuillez vous reconnecter."
                ]);
                return;
            }
            
            // Gérer l'upload de fichier si présent
            $attachmentData = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                error_log("[ApiController] sendTopicMessage - fichier détecté: " . $_FILES['attachment']['name']);
                
                // Créer un objet CURLFile pour l'envoi
                $attachmentData = $_FILES['attachment'];
            }
            
            // Récupérer le contenu du message
            $content = $_POST['content'] ?? '';
            
            error_log("[ApiController] sendTopicMessage - content: {$content}");
            error_log("[ApiController] sendTopicMessage - has attachment: " . ($attachmentData ? 'oui' : 'non'));
            
            if (empty($content) && !$attachmentData) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Le message ne peut pas être vide"
                ]);
                return;
            }
            
            // Préparer les données
            $messageData = [
                'content' => $content
            ];
            
            // Utiliser makeRequestWithFile si on a une pièce jointe
            if ($attachmentData) {
                $response = $this->apiService->makeRequestWithFile(
                    "messages/topic/{$topicId}/send",
                    "POST",
                    $messageData,
                    $attachmentData
                );
            } else {
                $response = $this->apiService->makeRequest(
                    "messages/topic/{$topicId}/send",
                    "POST",
                    $messageData
                );
            }
            
            error_log("[ApiController] sendTopicMessage - response: " . json_encode($response));
            
            if ($response['success']) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Message envoyé avec succès",
                    "data" => $response['data'] ?? null
                ]);
            } else {
                http_response_code($response['status_code'] ?? 500);
                echo json_encode([
                    "success" => false,
                    "message" => "Erreur lors de l'envoi du message: " . ($response['message'] ?? 'Erreur inconnue')
                ]);
            }
        } catch (Exception $e) {
            error_log("[ApiController] sendTopicMessage - exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erreur lors de l'envoi du message: " . $e->getMessage()
            ]);
        }
    }
}
?>