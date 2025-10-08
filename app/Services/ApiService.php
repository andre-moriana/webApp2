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
        
        // Utiliser l'URL de l'API depuis la configuration .env ou une valeur par défaut
        if (!isset($_ENV["API_BASE_URL"])) {
            // URL par défaut si pas de configuration .env
            $this->baseUrl = "http://82.67.123.22:25000/api";
            error_log("Avertissement: API_BASE_URL non configurée, utilisation de l'URL par défaut: " . $this->baseUrl);
        } else {
            $this->baseUrl = $_ENV["API_BASE_URL"];
        }
        error_log("URL de l'API configurée: " . $this->baseUrl);
        
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
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        
        // Headers pour accepter tous les types de contenu
        $headers = [
            'Accept: */*'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
            }
        } else if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
            }
        } else if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
            }
        } else if ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        error_log("Code HTTP: " . $httpCode);
        error_log("Type de contenu: " . $contentType);
        error_log("Taille de la réponse: " . strlen($response));
        
        if (curl_errno($ch)) {
            error_log("Erreur cURL: " . curl_error($ch));
            curl_close($ch);
            throw new Exception("Erreur lors de la requête API: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        // Nettoyer le BOM (Byte Order Mark) qui peut causer des erreurs de décodage
        $cleanResponse = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $cleanResponse = trim($cleanResponse);
        
        // Essayer de parser comme JSON même si le Content-Type n'est pas application/json
        $decodedResponse = json_decode($cleanResponse, true);
        
        // Si le décodage JSON a réussi, traiter comme JSON
        if ($decodedResponse !== null && json_last_error() === JSON_ERROR_NONE) {
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'data' => $decodedResponse,
                'status_code' => $httpCode,
                'message' => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
            ];
        }
        
        // Si ce n'est pas du JSON valide, retourner comme contenu binaire
        if ($contentType && strpos($contentType, 'application/json') === false) {
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'raw_response' => $response,
                'content_type' => $contentType
            ];
        }
        
        // Si on arrive ici, c'est que le Content-Type était application/json mais le décodage a échoué
        error_log("Erreur décodage JSON: " . json_last_error_msg());
        error_log("Début de la réponse: " . substr($cleanResponse, 0, 1000));
        
        return [
            'success' => false,
            'data' => null,
            'status_code' => $httpCode,
            'message' => 'Erreur de décodage JSON: ' . json_last_error_msg()
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
            $currentIsAdmin = (bool)($currentData['is_admin'] ?? $currentData['isAdmin'] ?? false);
            $currentIsBanned = (bool)($currentData['is_banned'] ?? $currentData['isBanned'] ?? false);
            
            // Vérifier is_admin
            if (isset($userData['is_admin'])) {
                $newIsAdmin = (bool)$userData['is_admin'];
                if ($newIsAdmin !== $currentIsAdmin) {
                    $endpoint = $newIsAdmin ? "users/{$userId}/make-admin" : "users/{$userId}/remove-admin";
                    $result = $this->makeRequest($endpoint, "POST");
                    $results[] = $result;
                    error_log("DEBUG updateUser - Réponse is_admin: " . json_encode($result));
                } else {
                    error_log("DEBUG updateUser - is_admin inchangé: current=$currentIsAdmin, new=$newIsAdmin");
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
                } else {
                    error_log("DEBUG updateUser - is_banned inchangé: current=$currentIsBanned, new=$newIsBanned");
                }
            }
            
            // Vérifier status
            if (isset($userData['status'])) {
                $currentStatus = $currentData['status'] ?? 'active';
                $newStatus = $userData['status'];
                if ($newStatus !== $currentStatus) {
                    if ($newStatus === 'active') {
                        // Utiliser l'endpoint d'approbation existant
                        $result = $this->makeRequest("users/{$userId}/approve", "POST");
                    } elseif ($newStatus === 'rejected') {
                        // Utiliser l'endpoint de rejet existant
                        $result = $this->makeRequest("users/{$userId}/reject", "POST", ['reason' => 'Statut modifié par un administrateur']);
                    } elseif ($newStatus === 'pending') {
                        // Pour remettre en attente, on ne peut pas utiliser les endpoints existants
                        // On pourrait créer un endpoint spécifique ou utiliser une méthode directe
                        error_log("DEBUG updateUser - Remise en attente non gérée par les endpoints existants");
                        $result = ['success' => true, 'message' => 'Remise en attente non implémentée'];
                    } else {
                        error_log("DEBUG updateUser - Statut non géré: $newStatus");
                        $result = ['success' => true, 'message' => 'Statut non modifié'];
                    }
                    $results[] = $result;
                    error_log("DEBUG updateUser - Réponse status: " . json_encode($result));
                } else {
                    error_log("DEBUG updateUser - status inchangé: current=$currentStatus, new=$newStatus");
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
        // Ajouter le token dans les headers
        $result = $this->makeRequest("users", "GET");
        // Supprimer le log verbeux
        // error_log("Réponse brute de l'API users: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                error_log("Erreur: Pas de données dans la réponse API");
                return [
                    "success" => false,
                    "data" => ["users" => []],
                    "message" => "Aucune donnée reçue de l'API"
                ];
            }
            
            $data = $result["data"];
            // Supprimer le log qui affiche les données des utilisateurs
            // error_log("[GET_USER] Données reçues de l'API: " . print_r($data, true));
            
            if (is_array($data)) {
                // Format 1: { "users": [...] }
                if (isset($data["users"]) && is_array($data["users"])) {
                    // Supprimer le log de debug
                    // error_log("Format 1 détecté");
                    return [
                        "success" => true,
                        "data" => $data,
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 2: { "data": [...] }
                elseif (isset($data["data"]) && is_array($data["data"])) {
                    // Supprimer le log de debug
                    // error_log("Format 2 détecté");
                    return [
                        "success" => true,
                        "data" => ["users" => $data["data"]],
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 3: [...] (tableau direct)
                elseif (is_array($data) && !empty($data)) {
                    // Supprimer le log de debug
                    // error_log("Format 3 détecté");
                    return [
                        "success" => true,
                        "data" => ["users" => $data],
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
            }
        }
        
        // En cas d'erreur, retourner un tableau vide
        error_log("Erreur lors de la récupération des utilisateurs: " . ($result["message"] ?? "Erreur inconnue"));
        return [
            "success" => false,
            "data" => ["users" => []],
            "message" => "Erreur lors de la récupération des utilisateurs"
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
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                error_log("Erreur: Pas de données dans la réponse API pour les groupes");
                return [
                    "success" => false,
                    "data" => ["groups" => []],
                    "message" => "Aucune donnée reçue de l'API pour les groupes"
                ];
            }
            
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
    public function makeRequestWithFile($endpoint, $method = "GET", $data = null, $file = null) {
        // Nettoyer l'endpoint pour éviter les doubles slashes
        $endpoint = trim($endpoint, '/');
        $url = rtrim($this->baseUrl, '/') . '/' . $endpoint;
        
        error_log("=== Requête API avec fichier ===");
        error_log("URL complète: " . $url);
        error_log("Méthode: " . $method);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true); // Pour récupérer les headers
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
        
        // Headers pour l'upload de fichier
        $headers = [
            "Accept: */*"
        ];
        
        if ($this->token) {
            $headers[] = "Authorization: Bearer " . $this->token;
            error_log("Token ajouté aux headers");
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === "POST" || $method === "PUT") {
            if ($method === "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            }
            
            if ($file && is_array($file) && isset($file['tmp_name'])) {
                $postData = $data ?? [];
                $postData['document'] = new CURLFile(
                    $file['tmp_name'],
                    $file['type'],
                    $file['name']
                );
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                error_log("Fichier ajouté à la requête: " . $file['name']);
            } elseif ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        error_log("Code HTTP: " . $httpCode);
        error_log("Type de contenu: " . $contentType);
        error_log("Taille des headers: " . $headerSize);
        
        if ($error) {
            error_log("Erreur cURL: " . $error);
            curl_close($ch);
            return [
                "success" => false,
                "message" => "Erreur de connexion: " . $error,
                "status_code" => 0
            ];
        }
        
        // Séparer les headers et le corps de la réponse
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        error_log("Headers reçus: " . str_replace("\r\n", " | ", $headers));
        error_log("Taille du corps: " . strlen($body));
        
        // Extraire le type de contenu des headers si non détecté par curl_getinfo
        if (preg_match('/Content-Type: (.*?)(?:\r\n|\r|\n|$)/', $headers, $matches)) {
            $contentType = trim($matches[1]);
        }
        
        // Extraire le nom du fichier s'il est présent dans les headers
        $filename = null;
        if (preg_match('/Content-Disposition:.*filename="([^"]+)"/', $headers, $matches)) {
            $filename = $matches[1];
        }
        
        curl_close($ch);
        
        // Si le type de contenu n'est pas JSON, traiter comme binaire
        if ($contentType && strpos($contentType, 'application/json') === false) {
            error_log("Réponse traitée comme binaire");
            return [
                "success" => true,
                "status_code" => $httpCode,
                "raw_response" => $body,
                "content_type" => $contentType,
                "filename" => $filename,
                "headers" => $headers
            ];
        }
        
        // Essayer de décoder comme JSON
        $decodedResponse = json_decode($body, true);
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erreur décodage JSON: " . json_last_error_msg());
            error_log("Début de la réponse: " . substr($body, 0, 1000));
            return [
                "success" => false,
                "message" => "Erreur lors du décodage de la réponse JSON",
                "status_code" => $httpCode,
                "raw_response" => $body
            ];
        }
        
        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "data" => $decodedResponse,
            "status_code" => $httpCode,
            "message" => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
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
            'first_name' => $userData['first_name'] ?? '',
            'name' => $userData['name'] ?? '',
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'role' => $userData['role'] ?? 'Archer',
            'status' => 'pending', // Statut en attente de validation
            'requires_approval' => true
        ];
        
        $result = $this->makeRequest("auth/register", "POST", $registerData);
        error_log("DEBUG createUser - Réponse: " . json_encode($result));
        
        return $result;
    }

    /**
     * Récupère tous les utilisateurs
     * @return array Liste de tous les utilisateurs
     */
    public function getAllUsers() {
        error_log("DEBUG getAllUsers - Récupération de tous les utilisateurs");
        
        $result = $this->makeRequest("users", "GET");
        error_log("DEBUG getAllUsers - Réponse: " . json_encode($result));
        
        return $result;
    }

    /**
     * Récupère les utilisateurs en attente de validation
     * @return array Liste des utilisateurs en attente
     */
    public function getPendingUsers() {
        error_log("DEBUG getPendingUsers - Récupération des utilisateurs en attente");
        
        $result = $this->makeRequest("users/pending", "GET");
        error_log("DEBUG getPendingUsers - Réponse: " . json_encode($result));
        
        return $result;
    }

    /**
     * Valide un utilisateur en attente
     * @param int $userId ID de l'utilisateur à valider
     * @return array Résultat de la validation
     */
    public function approveUser($userId) {
        error_log("DEBUG approveUser - Validation de l'utilisateur ID: " . $userId);
        
        $result = $this->makeRequest("users/{$userId}/approve", "POST");
        error_log("DEBUG approveUser - Réponse: " . json_encode($result));
        
        return $result;
    }

    /**
     * Rejette un utilisateur en attente
     * @param int $userId ID de l'utilisateur à rejeter
     * @param string $reason Raison du rejet
     * @return array Résultat du rejet
     */
    public function rejectUser($userId, $reason = '') {
        error_log("DEBUG rejectUser - Rejet de l'utilisateur ID: " . $userId);
        
        $data = [];
        if (!empty($reason)) {
            $data['reason'] = $reason;
        }
        
        $result = $this->makeRequest("users/{$userId}/reject", "POST", $data);
        error_log("DEBUG rejectUser - Réponse: " . json_encode($result));
        
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
        
        // Utiliser l'endpoint /history comme défini dans le backend
        $result = $this->makeRequest("messages/" . $groupId . "/history", "GET");
        error_log("Réponse messages: " . json_encode($result));
        
        if ($result["success"] && $result["status_code"] == 200) {
            return [
                "success" => true,
                "data" => $result["data"] ?? []
            ];
        }
        
        return [
            "success" => false,
            "message" => "Erreur lors de la récupération des messages",
            "data" => []
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
        
        // Utiliser l'endpoint /send comme défini dans le backend
        $result = $this->makeRequest("messages/" . $groupId . "/send", "POST", $messageData);
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

    public function createGroup($groupData) {
        error_log("DEBUG createGroup - Création d'un nouveau groupe");
        error_log("DEBUG createGroup - Données reçues: " . json_encode($groupData));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Récupérer l'ID de l'utilisateur depuis le token
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            error_log("Impossible de récupérer l'ID utilisateur");
            return [
                "success" => false,
                "message" => "Impossible de récupérer l'ID utilisateur"
            ];
        }
        
        // Préparation des données pour l'endpoint /groups/create (BackendPHP)
        // Format attendu: {"name":"...","description":"...","admin_id":"1","is_private":0}
        $createData = [
            'name' => $groupData['name'],
            'description' => $groupData['description'] ?? '',
            'admin_id' => $userId, // Utiliser admin_id au lieu de admin
            'is_private' => $groupData['is_private'] ? 1 : 0 // Convertir booléen en entier
        ];
        
        error_log("DEBUG createGroup - Données formatées: " . json_encode($createData));
        
        // Utiliser l'endpoint /groups/create comme défini dans BackendPHP
        $result = $this->makeRequest("groups/create", "POST", $createData);
        error_log("DEBUG createGroup - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function updateGroup($groupId, $groupData) {
        error_log("DEBUG updateGroup - Mise à jour du groupe ID: " . $groupId);
        error_log("DEBUG updateGroup - Données reçues: " . json_encode($groupData));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Préparation des données pour l'endpoint PUT /groups/{id}
        $updateData = [];
        if (isset($groupData['name'])) {
            $updateData['name'] = $groupData['name'];
        }
        if (isset($groupData['description'])) {
            $updateData['description'] = $groupData['description'];
        }
        if (isset($groupData['is_private'])) {
            $updateData['is_private'] = $groupData['is_private'] ? 1 : 0;
        }
        
        error_log("DEBUG updateGroup - Données formatées: " . json_encode($updateData));
        
        // Utiliser l'endpoint PUT /groups/{id}
        $result = $this->makeRequest("groups/{$groupId}", "PUT", $updateData);
        error_log("DEBUG updateGroup - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function deleteGroup($groupId) {
        error_log("DEBUG deleteGroup - Suppression du groupe ID: " . $groupId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint DELETE /groups/{id}
        $result = $this->makeRequest("groups/{$groupId}", "DELETE");
        error_log("DEBUG deleteGroup - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    private function getCurrentUserId() {
        // Récupérer l'ID utilisateur depuis la session ou le token
        if (isset($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'];
        }
        
        // Si pas dans la session, essayer de décoder le token
        if ($this->token) {
            try {
                // Décoder le token JWT pour récupérer l'ID utilisateur
                $decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $this->token)[1])), true);
                return $decoded['user_id'] ?? null;
            } catch (Exception $e) {
                error_log("Erreur lors du décodage du token: " . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }

    public function getGroupMembers($groupId) {
        error_log("DEBUG getGroupMembers - Récupération des membres du groupe: " . $groupId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/authorized-users
        $result = $this->makeRequest("groups/" . $groupId . "/authorized-users", "GET");
        error_log("DEBUG getGroupMembers - Réponse: " . json_encode($result));
        
        return $result;
    }

    public function addGroupMembers($groupId, $userIds) {
        error_log("DEBUG addGroupMembers - Ajout de membres au groupe: " . $groupId);
        error_log("DEBUG addGroupMembers - IDs utilisateurs: " . json_encode($userIds));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /groups/{id}/members
        $result = $this->makeRequest("groups/" . $groupId . "/members", "POST", [
            'user_ids' => $userIds
        ]);
        error_log("DEBUG addGroupMembers - Réponse: " . json_encode($result));
        
        return $result;
    }

    public function checkGroupAccess($groupId) {
        error_log("DEBUG checkGroupAccess - Vérification d'accès au groupe: " . $groupId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/check-access
        $result = $this->makeRequest("groups/" . $groupId . "/check-access", "GET");
        error_log("DEBUG checkGroupAccess - Réponse: " . json_encode($result));
        
        return $result;
    }
    // Méthodes pour les événements
    public function getEvents() {
        error_log("Début de getEvents()");
        
        // Les événements nécessitent une authentification
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => ["events" => []],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /events/list pour les événements
        $result = $this->makeRequest("events/list", "GET");
        error_log("Réponse brute de l'API events/list: " . print_r($result, true));
        
        if ($result["success"] && $result["status_code"] == 200) {
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                error_log("Erreur: Pas de données dans la réponse API pour les événements");
                return [
                    "success" => false,
                    "data" => ["events" => []],
                    "message" => "Aucune donnée reçue de l'API pour les événements"
                ];
            }
            
            $data = $result["data"];
            error_log("[GETEVENTS] Données reçues de l'API: " . print_r($data, true));
            
            // La réponse devrait être un tableau direct d'événements
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => ["events" => $data],
                    "message" => "Événements récupérés avec succès"
                ];
            }
            
            error_log("Format de données non reconnu: " . print_r($data, true));
        }
        
        error_log("Échec de récupération des événements. Code: " . ($result["status_code"] ?? "inconnu") . ", Message: " . ($result["message"] ?? "aucun"));
        return [
            "success" => false,
            "data" => ["events" => []],
            "message" => "Impossible de récupérer les événements depuis l'API"
        ];
    }
    
    public function getEventDetails($eventId) {
        error_log("DEBUG getEventDetails - Récupération des détails de l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "GET");
        error_log("DEBUG getEventDetails - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function createEvent($eventData) {
        error_log("DEBUG createEvent - Création d'un nouvel événement");
        error_log("DEBUG createEvent - Données reçues: " . json_encode($eventData));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Récupérer l'ID de l'utilisateur depuis le token
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            error_log("Impossible de récupérer l'ID utilisateur");
            return [
                "success" => false,
                "message" => "Impossible de récupérer l'ID utilisateur"
            ];
        }
        
        // Préparation des données pour l'endpoint /events/create
        $createData = [
            "name" => $eventData["name"],
            "description" => $eventData["description"] ?? "",
            "date" => $eventData["date"],
            "time" => $eventData["time"],

            // max_participants supprimé du formulaire
            "organizer_id" => $userId
        ];
        
        error_log("DEBUG createEvent - Données formatées: " . json_encode($createData));
        
        // Utiliser l'endpoint /events/create
        $result = $this->makeRequest("events/create", "POST", $createData);
        error_log("DEBUG createEvent - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function updateEvent($eventId, $eventData) {
        error_log("DEBUG updateEvent - Mise à jour de l'événement ID: " . $eventId);
        error_log("DEBUG updateEvent - Données reçues: " . json_encode($eventData));
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Préparation des données pour l'endpoint PUT /events/{id}
        $updateData = [];
        if (isset($eventData["name"])) {
            $updateData["name"] = $eventData["name"];
        }
        if (isset($eventData["description"])) {
            $updateData["description"] = $eventData["description"];
        }
        if (isset($eventData["date"])) {
            $updateData["date"] = $eventData["date"];
        }
        if (isset($eventData["time"])) {
            $updateData["time"] = $eventData["time"];
        }
        if (isset($eventData["location"])) {
            $updateData["date"] = $eventData["date"];
        }
        if (isset($eventData["location"])) {
            $updateData["location"] = $eventData["location"];
        }
        if (isset($eventData["max_participants"])) {
            $updateData["max_participants"] = $eventData["max_participants"];
        }
        
        error_log("DEBUG updateEvent - Données formatées: " . json_encode($updateData));
        
        // Utiliser l'endpoint PUT /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "PUT", $updateData);
        error_log("DEBUG updateEvent - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function deleteEvent($eventId) {
        error_log("DEBUG deleteEvent - Suppression de l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint DELETE /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "DELETE");
        error_log("DEBUG deleteEvent - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function registerToEvent($eventId) {
        error_log("DEBUG registerToEvent - Inscription à l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /events/{id}/register
        $result = $this->makeRequest("events/{$eventId}/register", "POST");
        error_log("DEBUG registerToEvent - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function unregisterFromEvent($eventId) {
        error_log("DEBUG unregisterFromEvent - Désinscription de l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /events/{id}/unregister
        $result = $this->makeRequest("events/{$eventId}/unregister", "POST");
        error_log("DEBUG unregisterFromEvent - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function checkEventRegistration($eventId) {
        error_log("DEBUG checkEventRegistration - Vérification de l'inscription à l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}/registration
        $result = $this->makeRequest("events/{$eventId}/registration", "GET");
        error_log("DEBUG checkEventRegistration - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    public function getEventMessages($eventId) {
        error_log("DEBUG getEventMessages - Récupération des messages de l'événement ID: " . $eventId);
        
        if (!$this->token) {
            error_log("Pas de token valide");
            return [
                "success" => false,
                "data" => [],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}/messages
        $result = $this->makeRequest("events/{$eventId}/messages", "GET");
        error_log("DEBUG getEventMessages - Réponse: " . json_encode($result));
        
        return $result;
    }
    
    // Méthodes pour les exercices
    public function getExercises($showHidden = false) {
        error_log("DEBUG ApiService::getExercises - showHidden=" . ($showHidden ? "true" : "false"));
        $endpoint = 'exercise_sheets';
        if ($showHidden) {
            $endpoint .= '?show_hidden=1';
        }
        error_log("DEBUG ApiService::getExercises - endpoint=" . $endpoint);
        $result = $this->makeRequest($endpoint, 'GET');
        error_log("DEBUG ApiService::getExercises - result=" . json_encode($result));
        return $result;
    }
    
    public function getExerciseDetails($id) {
        return $this->makeRequest("exercise_sheets?action=get&id={$id}", 'GET');
    }
    
    public function createExercise($data) {
        return $this->makeRequest('exercise_sheets?action=create', 'POST', $data);
    }
    
    public function updateExercise($id, $data) {
        return $this->makeRequest("exercise_sheets?action=update&id={$id}", 'POST', $data);
    }
    
    public function deleteExercise($id) {
        return $this->makeRequest("exercise_sheets?action=delete&id={$id}", 'DELETE');
    }
    
    public function getExerciseCategories() {
        return $this->makeRequest('exercise_sheets?action=categories', 'GET');
    }

    public function createExerciseWithFile($data, $file = null) {
        $url = rtrim($this->baseUrl, '/') . '/exercise_sheets?action=create';
        error_log("Requête API vers: " . $url);
        error_log("Méthode: POST avec FormData");
        error_log("Fichier reçu: " . json_encode($file));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Headers
        $headers = [
            'Accept: */*'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        // Préparer les données POST
        $postFields = $data;
        
        // Ajouter le fichier si présent
        if ($file && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
            error_log("Ajout du fichier au FormData: " . $file['name']);
            $postFields['attachment'] = new CURLFile(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            );
        } else {
            error_log("Aucun fichier à ajouter");
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Erreur cURL: " . curl_error($ch));
            curl_close($ch);
            throw new Exception("Erreur lors de la requête API: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse,
            'status_code' => $httpCode,
            'message' => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
        ];
    }

    public function makePostRequestWithFormData($endpoint, $data, $file = null) {
        $url = rtrim($this->baseUrl, '/') . '/' . trim($endpoint, '/');
        error_log("Requête API vers: " . $url);
        error_log("Méthode: POST");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Headers
        $headers = [
            'Accept: */*'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        // Préparer les données POST
        $postFields = $data;
        
        // Ajouter le fichier si présent
        if ($file && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
            $postFields['attachment'] = new CURLFile(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            // Données form-encoded
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Erreur cURL: " . curl_error($ch));
            curl_close($ch);
            throw new Exception("Erreur lors de la requête API: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse,
            'status_code' => $httpCode,
            'message' => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
        ];
    }

    /**
     * Récupère la liste des entraînements pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Réponse de l'API
     */
    public function getTrainings($userId) {
        // Utiliser l'endpoint des progrès d'entraînement qui contient les sessions
        $endpoint = "/training/progress/user/" . $userId;
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère les sessions d'entraînement pour un exercice spécifique
     * @param int $exerciseId ID de l'exercice
     * @return array Réponse de l'API
     */
    public function getTrainingSessions($exerciseId) {
        $endpoint = "/training?action=dashboard&exercise_id=" . $exerciseId;
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère les détails d'un entraînement spécifique
     * @param int $trainingId ID de l'entraînement
     * @return array Réponse de l'API
     */
    public function getTrainingById($trainingId) {
        $endpoint = "/training/" . $trainingId;
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère les statistiques d'entraînement pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Réponse de l'API
     */
    public function getTrainingStats($userId) {
        // Utiliser l'endpoint des stats utilisateur
        $endpoint = "/training/stats/user/" . $userId;
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère le dashboard des entraînements comptés
     * @param int|null $exerciseId ID de l'exercice (optionnel)
     * @param string|null $shootingType Type de tir (optionnel)
     * @return array Réponse de l'API
     */
    public function getScoredTrainingDashboard($exerciseId = null, $shootingType = null) {
        $endpoint = "/scored-training?action=dashboard";
        
        $params = [];
        if ($exerciseId) {
            $params[] = "exercise_id=" . $exerciseId;
        }
        if ($shootingType && $shootingType !== 'Tous') {
            $params[] = "shooting_type=" . urlencode($shootingType);
        }
        
        if (!empty($params)) {
            $endpoint .= "&" . implode("&", $params);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère les tirs comptés
     * @param int|null $userId ID de l'utilisateur (optionnel)
     * @param int|null $exerciseId ID de l'exercice (optionnel)
     * @return array Réponse de l'API
     */
    public function getScoredTrainings($userId = null, $exerciseId = null) {
        $endpoint = "/scored-training";
        
        $params = [];
        if ($userId) {
            $params[] = "user_id=" . $userId;
        }
        if ($exerciseId) {
            $params[] = "exercise_id=" . $exerciseId;
        }
        
        if (!empty($params)) {
            $endpoint .= "?" . implode("&", $params);
        }
        
        error_log('DEBUG ApiService::getScoredTrainings - endpoint: ' . $endpoint);
        error_log('DEBUG ApiService::getScoredTrainings - userId: ' . $userId);
        
        $result = $this->makeRequest($endpoint, 'GET');
        
        error_log('DEBUG ApiService::getScoredTrainings - result: ' . json_encode($result));
        
        // Gérer la structure imbriquée de l'API
        if (isset($result['success']) && $result['success'] && isset($result['data'])) {
            $data = $result['data'];
            
            // Si les données sont encore imbriquées
            if (is_array($data) && isset($data['success']) && $data['success'] && isset($data['data'])) {
                error_log('DEBUG ApiService::getScoredTrainings - nested structure detected, unwrapping');
                return [
                    'success' => true,
                    'data' => $data['data'],
                    'status_code' => $result['status_code'] ?? 200,
                    'message' => $result['message'] ?? 'Succès'
                ];
            }
        }
        
        return $result;
    }

    /**
     * Récupère un tir compté par ID
     * @param int $trainingId ID du tir compté
     * @return array Réponse de l'API
     */
    public function getScoredTrainingById($trainingId) {
        $endpoint = "/scored-training/" . $trainingId;
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Crée un nouveau tir compté
     * @param array $data Données du tir compté
     * @return array Réponse de l'API
     */
    public function createScoredTraining($data) {
        $endpoint = "/scored-training";
        error_log("DEBUG CREATE SCORED TRAINING: " . json_encode($data));
        return $this->makeRequest($endpoint, 'POST', $data);
    }

    /**
     * Finalise un tir compté
     * @param int $trainingId ID du tir compté
     * @param array $data Données de finalisation
     * @return array Réponse de l'API
     */
    public function endScoredTraining($trainingId, $data) {
        $endpoint = "/scored-training/" . $trainingId . "/end";
        return $this->makeRequest($endpoint, 'POST', $data);
    }

    /**
     * Ajoute une volée à un tir compté
     * @param int $trainingId ID du tir compté
     * @param array $endData Données de la volée
     * @return array Réponse de l'API
     */
    public function addScoredEnd($trainingId, $endData) {
        // Utiliser l'API locale comme toutes les autres pages
        $endpoint = "/scored-training/" . $trainingId . "/ends";
        return $this->makeRequest($endpoint, 'POST', $endData);
    }

    /**
     * Supprime un tir compté
     * @param int $trainingId ID du tir compté
     * @return array Réponse de l'API
     */
    public function deleteScoredTraining($trainingId) {
        $endpoint = "/scored-training/" . $trainingId;
        return $this->makeRequest($endpoint, 'DELETE');
    }

    /**
     * Récupère les configurations des types de tir
     * @return array Réponse de l'API
     */
    public function getScoredTrainingConfigurations() {
        $endpoint = "/scored-training?action=configurations";
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Met à jour les notes d'un tir compté
     * @param int $trainingId ID du tir compté
     * @param string $note Note
     * @return array Réponse de l'API
     */
    public function updateScoredTrainingNote($trainingId, $note) {
        $endpoint = "/scored-training/" . $trainingId . "/note";
        return $this->makeRequest($endpoint, 'PATCH', ['note' => $note]);
    }

    /**
     * Supprime une volée d'un tir compté
     * @param int $endId ID de la volée
     * @return array Réponse de l'API
     */
    public function deleteScoredEnd($endId) {
        $endpoint = "/scored-training/end/" . $endId;
        return $this->makeRequest($endpoint, 'DELETE');
    }

    /**
     * Récupère le progrès d'entraînement
     * @return array Réponse de l'API
     */
    public function getTrainingProgress() {
        $endpoint = "/training/progress";
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Récupère le dashboard d'entraînement pour un exercice
     * @param int $exerciseId ID de l'exercice
     * @return array Réponse de l'API
     */
    public function getTrainingDashboard($exerciseId) {
        $endpoint = "/training/dashboard/" . $exerciseId;
        return $this->makeRequest($endpoint, 'GET');
    }

    public function updateTrainingNotes($sessionId, $notes) {
        $endpoint = "/training/sessions/notes";
        return $this->makeRequest($endpoint, 'PATCH', [
            'session_id' => $sessionId,
            'notes' => $notes
        ]);
    }

    /**
     * Sauvegarde une session d'entraînement
     * @param int $exerciseSheetId ID de la fiche d'exercice
     * @param array $sessionData Données de la session
     * @param int|null $userId ID de l'utilisateur (optionnel)
     * @return array Réponse de l'API
     */
    public function saveTrainingSession($exerciseSheetId, $sessionData, $userId = null) {
        $endpoint = "/training/save-session";
        
        $data = [
            'exercise_sheet_id' => $exerciseSheetId,
            'session_data' => $sessionData
        ];
        
        // Ajouter l'user_id si fourni
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }
        
        error_log("🔍 [API_SERVICE] Données envoyées à l'API: " . json_encode($data));
        
        return $this->makeRequest($endpoint, 'POST', $data);
    }

    /**
     * Récupère les informations d'un utilisateur par son ID
     * @param int $userId ID de l'utilisateur
     * @return array Réponse de l'API
     */
    public function getUserById($userId) {
        $endpoint = "/users/" . $userId;
        return $this->makeRequest($endpoint, 'GET');
    }
    
    /**
     * Supprimer une session d'entraînement
     * @param int $sessionId ID de la session
     * @return array Réponse de l'API
     */
    public function deleteTrainingSession($sessionId) {
        $endpoint = "/training/session/" . $sessionId;
        return $this->makeRequest($endpoint, 'DELETE');
    }
}
?>







