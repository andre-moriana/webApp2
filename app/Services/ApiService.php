<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ApiService {
    private $baseUrl;
    private $token;
    
    public function __construct() {
        if (file_exists(".env")) {
            $lines = file(".env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, "=") !== false && strpos($line, "#") !== 0) {
                    list($key, $value) = explode("=", $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        $this->baseUrl = 'http://82.67.123.22:25000/api/';
        
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Récupérer le token depuis la session
        if (isset($_SESSION['token'])) {
            $this->token = $_SESSION['token'];
            error_log("Token récupéré depuis la session: " . substr($this->token, 0, 10) . "...");
        } else {
            $this->token = null;
            error_log("Aucun token trouvé dans la session");
        }
    }
    
    public function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = rtrim($this->baseUrl, '/') . '/' . trim($endpoint, '/');
        error_log("Requête API vers: " . $url);
        error_log("Méthode: " . $method);
        if ($data) {
            error_log("Données à envoyer: " . print_r($data, true));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Préparer les en-têtes avec le token d'authentification
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
            error_log("Token ajouté aux en-têtes: " . substr($this->token, 0, 10) . "...");
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                error_log("Données JSON envoyées: " . $jsonData);
            }
        } else if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        error_log("Code HTTP: " . $httpCode);
        error_log("Réponse brute: " . $response);

        if (curl_errno($ch)) {
            error_log("Erreur cURL: " . curl_error($ch));
            curl_close($ch);
            throw new Exception("Erreur lors de la requête API: " . curl_error($ch));
        }

        curl_close($ch);

        // Nettoyer la réponse des caractères BOM et espaces
        if ($response !== false && is_string($response)) {
            $response = preg_replace('/^[\x{FEFF}\s]+/u', '', $response);
            error_log("Réponse nettoyée: " . $response);
        }

        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur décodage JSON: " . json_last_error_msg());
            throw new Exception("Erreur lors du décodage de la réponse JSON: " . json_last_error_msg());
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse,
            'status_code' => $httpCode,
            'message' => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode,
            'raw_response' => $response
        ];
    }
    
    public function updateUser($userId, $userData) {
        error_log("DEBUG updateUser - Données reçues: " . json_encode($userData));
        
        $results = [];
        
        // 1. Mise à jour des informations d'identité
        $identiteData = [];
        if (!empty($userData['firstName'])) $identiteData['firstName'] = $userData['firstName'];
        if (!empty($userData['name'])) $identiteData['name'] = $userData['name'];
        if (!empty($userData['email'])) $identiteData['email'] = $userData['email'];
        if (!empty($userData['phone'])) $identiteData['phone'] = $userData['phone'];
        if (!empty($userData['gender'])) $identiteData['gender'] = $userData['gender'];
        if (!empty($userData['birthDate'])) $identiteData['birthDate'] = $userData['birthDate'];
        
        if (!empty($identiteData)) {
            error_log("DEBUG updateUser - Données d'identité à envoyer: " . json_encode($identiteData));
            $result = $this->makeRequest("users/{$userId}/update-identite", "PUT", $identiteData);
            $results[] = $result;
            error_log("DEBUG updateUser - Réponse identité: " . json_encode($result));
        }
        
        // 2. Mise à jour des informations sportives
        $sportData = [];
        if (!empty($userData['licenceNumber'])) $sportData['licenceNumber'] = $userData['licenceNumber'];
        if (!empty($userData['ageCategory'])) $sportData['ageCategory'] = $userData['ageCategory'];
        if (!empty($userData['arrivalYear'])) $sportData['arrivalYear'] = $userData['arrivalYear'];
        if (!empty($userData['bowType'])) $sportData['bowType'] = $userData['bowType'];
        if (!empty($userData['role'])) $sportData['role'] = $userData['role'];
        
        if (!empty($sportData)) {
            error_log("DEBUG updateUser - Données sport à envoyer: " . json_encode($sportData));
            $result = $this->makeRequest("users/{$userId}/update-sport", "PUT", $sportData);
            $results[] = $result;
            error_log("DEBUG updateUser - Réponse sport: " . json_encode($result));
        }
        
        // 3. Mise à jour des droits (is_admin, is_banned)
        // Récupérer les données actuelles de l'utilisateur via l'API
        $currentUserResponse = $this->makeRequest("users/{$userId}", "GET");
        if ($currentUserResponse && $currentUserResponse['success']) {
            $currentData = $currentUserResponse['data'];
            $currentIsAdmin = $currentData['is_admin'] ?? $currentData['isAdmin'] ?? false;
            $currentIsBanned = $currentData['is_banned'] ?? $currentData['isBanned'] ?? false;
            
            // Vérifier is_admin
            if (isset($userData['is_admin'])) {
                $newIsAdmin = (bool)$userData['is_admin'];
                if ($newIsAdmin !== $currentIsAdmin) {
                    $endpoint = $newIsAdmin ? "users/{$userId}/make-admin" : "users/{$userId}/remove-admin";
                    $result = $this->makeRequest($endpoint, "POST");
                    $results[] = $result;
                    error_log("DEBUG updateUser - Réponse is_admin: " . json_encode($result));
                }
            }
            
            // Vérifier is_banned
            if (isset($userData['is_banned'])) {
                $newIsBanned = (bool)$userData['is_banned'];
                if ($newIsBanned !== $currentIsBanned) {
                    $endpoint = $newIsBanned ? "users/{$userId}/ban" : "users/{$userId}/unban";
                    $result = $this->makeRequest($endpoint, "POST");
                    $results[] = $result;
                    error_log("DEBUG updateUser - Réponse is_banned: " . json_encode($result));
                }
            }
        }
        
        // Compiler les résultats
        $success = true;
        $messages = [];
        
        foreach ($results as $result) {
            if (!$result['success']) {
                $success = false;
            }
            if (!empty($result['message'])) {
                $messages[] = $result['message'];
            }
        }
        
        return [
            'success' => $success,
            'message' => implode(' | ', $messages),
            'data' => null
        ];
    }
    
    public function login($username, $password) {
        $loginData = [
            "username" => $username,
            "password" => $password
        ];
        
        error_log("Tentative de connexion à l'API avec username: " . $username);
        
        try {
            $result = $this->makeRequest("auth/login", "POST", $loginData);
            error_log("Réponse login API: " . print_r($result, true));
            
            if ($result["success"] && isset($result["data"]["token"])) {
                // Stocker le token pour les futures requêtes
                $this->token = $result["data"]["token"];
                error_log("Token obtenu et stocké: " . substr($this->token, 0, 10) . "...");
                
                return [
                    "success" => true,
                    "token" => $this->token,
                    "user" => $result["data"]["user"] ?? null,
                    "message" => $result["data"]["message"] ?? "Connexion réussie"
                ];
            }
            
            error_log("Échec de connexion à l'API: " . ($result["message"] ?? "Raison inconnue"));
            return [
                "success" => false,
                "message" => $result["data"]["message"] ?? $result["message"] ?? "Erreur de connexion"
            ];
        } catch (Exception $e) {
            error_log("Exception lors de la connexion à l'API: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Erreur de connexion: " . $e->getMessage()
            ];
        }
    }

    public function getUsers() {
        // Vérifier si nous avons un token valide
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => ["users" => []],
                "message" => "Token d'authentification requis"
            ];
        }

        error_log("Token actuel: " . ($this->token ?? "aucun"));
        
        // Ajouter le token dans les headers
        $result = $this->makeRequest("users", "GET");
        error_log("Réponse brute de l'API users: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            error_log("[GET_USER] Données reçues de l'API: " . print_r($data, true));
            
            if (is_array($data)) {
                // Format 1: { "users": [...] }
                if (isset($data["users"]) && is_array($data["users"])) {
                    error_log("Format 1 détecté");
                    return [
                        "success" => true,
                        "data" => $data,
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 2: { "data": [...] }
                elseif (isset($data["data"]) && is_array($data["data"])) {
                    error_log("Format 2 détecté");
                    return [
                        "success" => true,
                        "data" => ["users" => $data["data"]],
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 3: [...] (tableau direct)
                elseif (is_array($data) && !empty($data)) {
                    error_log("Format 3 détecté");
                    // Vérifier si c'est un tableau d'utilisateurs
                    $firstItem = reset($data);
                    if (is_array($firstItem) && (
                        isset($firstItem["name"]) || 
                        isset($firstItem["email"]) ||
                        isset($firstItem["id"])
                    )) {
                        return [
                            "success" => true,
                            "data" => ["users" => $data],
                            "message" => "Utilisateurs récupérés avec succès"
                        ];
                    }
                }
            }
            
            error_log("Format de données non reconnu");
        }
        
        error_log("Échec de récupération des utilisateurs. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => ["users" => []],
            "message" => "Impossible de récupérer les utilisateurs"
        ];
    }

    public function getGroups() {
        error_log("Début de getGroups()");
        
        // Les groupes nécessitent une authentification
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => ["groups" => []],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /group/list pour les groupes
        $result = $this->makeRequest("groups/list", "GET");
        error_log("Réponse brute de l'API group/list: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            error_log("[GETGROUP] Données reçues de l'API: " . print_r($data, true));
            
            // La réponse devrait être un tableau direct de groupes
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => ["groups" => $data],
                    "message" => "Groupes récupérés avec succès"
                ];
            }
            
            error_log("Format de données non reconnu: " . print_r($data, true));
        }
        
        error_log("Échec de récupération des groupes. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => ["groups" => []],
            "message" => "Impossible de récupérer les groupes depuis l'API"
        ];
    }
    
    private function getSimulatedGroups() {
        return [
            [
                "id" => 1,
                "name" => "Débutants",
                "description" => "Groupe pour les archers débutants",
                "level" => "débutant",
                "memberCount" => 8,
                "createdAt" => "2024-01-01 10:00:00",
                "status" => "active"
            ],
            [
                "id" => 2,
                "name" => "Compétiteurs",
                "description" => "Groupe pour les archers de compétition",
                "level" => "avancé",
                "memberCount" => 12,
                "createdAt" => "2024-01-15 14:30:00",
                "status" => "active"
            ],
            [
                "id" => 3,
                "name" => "Loisir",
                "description" => "Groupe pour la pratique du tir en loisir",
                "level" => "intermédiaire",
                "memberCount" => 15,
                "createdAt" => "2024-02-01 09:15:00",
                "status" => "active"
            ],
            [
                "id" => 4,
                "name" => "Jeunes",
                "description" => "Groupe pour les jeunes archers (moins de 18 ans)",
                "level" => "débutant",
                "memberCount" => 6,
                "createdAt" => "2024-02-10 16:45:00",
                "status" => "active"
            ]
        ];
    }
    
    private function getSimulatedUsers() {
        return [
            [
                "id" => 1,
                "first_name" => "Admin",
                "last_name" => "Gémenos",
                "email" => "admin@archers-gemenos.fr",
                "role" => "admin",
                "status" => "active",
                "created_at" => "2024-01-01 10:00:00"
            ],
            [
                "id" => 2,
                "first_name" => "Jean",
                "last_name" => "Dupont",
                "email" => "jean.dupont@archers-gemenos.fr",
                "role" => "user",
                "status" => "active",
                "created_at" => "2024-01-15 14:30:00"
            ]
        ];
    }

    public function getGroupDetails($groupId) {
        error_log("Début de getGroupDetails($groupId)");
        
        // Les groupes nécessitent une authentification
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => null,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id} pour les détails d'un groupe
        $result = $this->makeRequest("groups/" . $groupId, "GET");
        error_log("[MAKEREQUEST] Réponse brute de l'API groups/$groupId: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            error_log("Données reçues de l'API: " . print_r($data, true));
            
            if (is_array($data)) {
                // Ajouter des statistiques simulées pour l'exemple
                $data['levelStats'] = [
                    'beginner' => rand(1, 5),
                    'intermediate' => rand(1, 5),
                    'advanced' => rand(1, 5)
                ];
                
                return [
                    "success" => true,
                    "data" => $data,
                    "message" => "Détails du groupe récupérés avec succès"
                ];
            }
            
            error_log("Format de données non reconnu: " . print_r($data, true));
        }
        
        error_log("Échec de récupération des détails du groupe. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => null,
            "message" => "Impossible de récupérer les détails du groupe depuis l'API"
        ];
    }

    public function getGroupChat($groupId) {
        error_log("Début de getGroupChat($groupId)");
        
        // Les chats nécessitent une authentification
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => null,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/chat pour les messages du chat
        $result = $this->makeRequest("groups/" . $groupId . "/chat", "GET");
        error_log("Réponse brute de l'API groups/$groupId/chat: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            error_log("Données reçues de l'API: " . print_r($data, true));
            
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => $data,
                    "message" => "Messages du chat récupérés avec succès"
                ];
            }
            
            error_log("Format de données non reconnu: " . print_r($data, true));
        }
        
        error_log("Échec de récupération des messages du chat. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => null,
            "message" => "Impossible de récupérer les messages du chat depuis l'API"
        ];
    }
    public function getUserDocuments($userId) {
        // Vérifier si nous avons un token valide
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => ["documents" => []],
                "message" => "Token d'authentification requis"
            ];
        }

        error_log("Token actuel: " . ($this->token ?? "aucun"));
        
        // Appel à l'API pour récupérer les documents
        $result = $this->makeRequest("documents/user/{$userId}", "GET");
        error_log("Réponse brute de l'API documents/user/{$userId}: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            error_log("Données reçues de l'API: " . print_r($data, true));
            
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => $data,
                    "message" => "Documents récupérés avec succès"
                ];
            }
            
            error_log("Format de données non reconnu: " . print_r($data, true));
        }
        
        error_log("Échec de récupération des documents. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => ["documents" => []],
            "message" => "Impossible de récupérer les documents depuis l'API"
        ];
    }
    public function makeRequestWithFile($endpoint, $method = "POST", $data = null) {
        // Nettoyer l'endpoint pour éviter les doubles slashes
        $endpoint = trim($endpoint, '/');
        $url = rtrim($this->baseUrl, '/') . '/' . $endpoint;
        
        error_log("Appel API avec fichier: " . $method . " " . $url);
        error_log("Données à envoyer: " . print_r($data, true));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout plus long pour les uploads
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $headers = [
            "Accept: application/json"
            // Ne pas définir Content-Type, cURL le fera automatiquement avec le bon boundary
        ];
        
        if ($this->token) {
            $headers[] = "Authorization: Bearer " . $this->token;
            error_log("Ajout du token dans les headers: Bearer " . substr($this->token, 0, 10) . "...");
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            
            if ($data !== null) {
                error_log("Données multipart à envoyer: " . print_r($data, true));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        error_log("Réponse HTTP: " . $httpCode);
        error_log("Réponse brute: " . substr($response, 0, 500) . "...");
        if ($error) {
            error_log("Erreur cURL: " . $error);
        }
        
        curl_close($ch);
        
        if ($error) {
            return [
                "success" => false,
                "message" => "Erreur de connexion: " . $error,
                "status_code" => 0
            ];
        }
        
        // Nettoyer la réponse des caractères BOM et espaces
        if ($response !== false && is_string($response)) {
            $response = preg_replace('/^[\x{FEFF}\s]+/u', '', $response);
            error_log("Réponse nettoyée: " . $response);
        }
        
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur décodage JSON: " . json_last_error_msg());
            return [
                "success" => false,
                "message" => "Erreur lors du décodage de la réponse",
                "status_code" => $httpCode
            ];
        }
        
        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "data" => $decodedResponse,
            "status_code" => $httpCode,
            "message" => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode,
            "raw_response" => $response
        ];
    }
    
    public function deleteUser($userId) {
        error_log("DEBUG deleteUser - Suppression de l'utilisateur ID: " . $userId);
        
        $result = $this->makeRequest("/users/{$userId}", "DELETE");
        error_log("DEBUG deleteUser - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function createUser($userData) {
        error_log("DEBUG createUser - Création d'un nouvel utilisateur");
        error_log("DEBUG createUser - Données: " . json_encode($userData));
        
        // Préparation des données pour l'endpoint auth/register
        $registerData = [
            'name' => $userData['name'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => $userData['password']
        ];
        
        $result = $this->makeRequest("auth/register", "POST", $registerData);
        error_log("DEBUG createUser - Réponse: " . json_encode($result));
        
        return $result;
    }

    public function getGroupMessages($groupId) {
        error_log("Récupération des messages du groupe " . $groupId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        $result = $this->makeRequest("groups/" . $groupId . "/chat", "GET");
        error_log("Réponse messages: " . json_encode($result));
        
        if ($result["success"] && $result["status_code"] == 200) {
            return [
                "success" => true,
                "messages" => $result["data"] ?? []
            ];
        }
        
        return [
            "success" => false,
            "message" => "Erreur lors de la récupération des messages",
            "messages" => []
        ];
    }

    public function sendGroupMessage($groupId, $messageData) {
        error_log("Envoi d'un message au groupe " . $groupId);
        error_log("Données du message: " . json_encode($messageData));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        $result = $this->makeRequest("groups/" . $groupId . "/chat", "POST", $messageData);
        error_log("Réponse envoi message: " . json_encode($result));
        
        if ($result["success"] && $result["status_code"] == 201) {
            return [
                "success" => true,
                "message" => "Message envoyé avec succès",
                "data" => $result["data"] ?? null
            ];
        }
        
        return [
            "success" => false,
            "message" => "Erreur lors de l'envoi du message"
        ];
    }

    public function uploadFile($file) {
        // Utiliser l'endpoint d'upload de messages
        $endpoint = 'messages/upload';
        $url = rtrim($this->baseUrl, '/') . '/' . $endpoint;
        
        error_log("Upload de fichier vers: " . $url);
        error_log("Fichier à uploader: " . print_r($file, true));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout plus long pour les uploads
        
        // Préparer les en-têtes avec le token d'authentification
        $headers = [
            "Accept: application/json"
        ];
        
        if ($this->token) {
            $headers[] = "Authorization: Bearer " . $this->token;
            error_log("Ajout du token dans les headers: Bearer " . $this->token);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Préparer le fichier pour l'upload
        $postData = [
            'attachment' => new CURLFile(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            )
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        error_log("Réponse HTTP: " . $httpCode);
        error_log("Réponse brute: " . substr($response, 0, 500) . "...");
        if ($error) {
            error_log("Erreur cURL: " . $error);
        }
        
        curl_close($ch);
        
        if ($error) {
            return [
                "success" => false,
                "message" => "Erreur lors de l'upload: " . $error,
                "status_code" => 0
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "data" => $decodedResponse,
            "status_code" => $httpCode,
            "message" => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
        ];
    }

    private function uploadFileAlternative($file) {
        // Utiliser l'endpoint alternatif pour l'upload de fichiers
        $endpoint = 'attachments/upload';
        $url = rtrim($this->baseUrl, '/') . '/' . $endpoint;
        
        error_log("Tentative d'upload avec l'endpoint alternatif: " . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $headers = [
            "Accept: application/json"
        ];
        
        if ($this->token) {
            $headers[] = "Authorization: Bearer " . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $postData = [
            'attachment' => new CURLFile(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            )
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        error_log("Réponse HTTP (alternatif): " . $httpCode);
        error_log("Réponse brute (alternatif): " . substr($response, 0, 500) . "...");
        
        curl_close($ch);
        
        if ($error) {
            return [
                "success" => false,
                "message" => "Erreur lors de l'upload: " . $error,
                "status_code" => 0
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "data" => $decodedResponse,
            "status_code" => $httpCode,
            "message" => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
        ];
    }
}
?>