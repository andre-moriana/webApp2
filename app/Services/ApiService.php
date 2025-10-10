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
        } else {
            $this->baseUrl = $_ENV["API_BASE_URL"];
        }
        
        // Initialiser le token depuis la session
        $this->token = $_SESSION['token'] ?? null;
        
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Récupérer le token depuis la session
        if (isset($_SESSION['token'])) {
            $this->token = $_SESSION['token'];
        } else {
            $this->token = null;
        }
    }
    
    public function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = rtrim($this->baseUrl, '/') . '/' . trim($endpoint, '/');
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
        
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception("Erreur lors de la requête API: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        // Nettoyer le BOM (Byte Order Mark) qui peut causer des erreurs de décodage
        $cleanResponse = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $cleanResponse = preg_replace('/^[\x00-\x1F\x7F]+/', '', $cleanResponse); // Supprimer tous les caractères de contrôle
        $cleanResponse = trim($cleanResponse);
        
        // Nettoyage supplémentaire pour les caractères BOM multiples
        while (substr($cleanResponse, 0, 3) === "\xEF\xBB\xBF") {
            $cleanResponse = substr($cleanResponse, 3);
        }
        
        // Supprimer tous les caractères de contrôle restants au début
        $cleanResponse = ltrim($cleanResponse, "\x00-\x1F\x7F");
        
        // Debug: afficher les premiers caractères en hexadécimal
        if (strlen($cleanResponse) > 0) {
            $firstBytes = '';
            for ($i = 0; $i < min(10, strlen($cleanResponse)); $i++) {
                $firstBytes .= sprintf('%02X ', ord($cleanResponse[$i]));
            }
        }
        
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
        
        return [
            'success' => false,
            'data' => null,
            'status_code' => $httpCode,
            'message' => 'Erreur de décodage JSON: ' . json_last_error_msg()
        ];
    }
    
    public function updateUser($userId, $userData) {
        
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
            $result = $this->makeRequest("users/{$userId}/update-identite", "PUT", $identiteData);
            $results[] = $result;
        }
        
        // 2. Mise à jour des informations sportives
        $sportData = [];
        if (!empty($userData['licenceNumber'])) $sportData['licenceNumber'] = $userData['licenceNumber'];
        if (!empty($userData['ageCategory'])) $sportData['ageCategory'] = $userData['ageCategory'];
        if (!empty($userData['arrivalYear'])) $sportData['arrivalYear'] = $userData['arrivalYear'];
        if (!empty($userData['bowType'])) $sportData['bowType'] = $userData['bowType'];
        if (!empty($userData['role'])) $sportData['role'] = $userData['role'];
        
        if (!empty($sportData)) {
            $result = $this->makeRequest("users/{$userId}/update-sport", "PUT", $sportData);
            $results[] = $result;
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
                }
            }
            
            // Vérifier is_banned
            if (isset($userData['is_banned'])) {
                $newIsBanned = (bool)$userData['is_banned'];
                if ($newIsBanned !== $currentIsBanned) {
                    $endpoint = $newIsBanned ? "users/{$userId}/ban" : "users/{$userId}/unban";
                    $result = $this->makeRequest($endpoint, "POST");
                    $results[] = $result;
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
                        $result = ['success' => true, 'message' => 'Remise en attente non implémentée'];
                    } else {
                        $result = ['success' => true, 'message' => 'Statut non modifié'];
                    }
                    $results[] = $result;
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
        
        
        try {
            $result = $this->makeRequest("auth/login", "POST", $loginData);
            
            if ($result["success"] && isset($result["data"]["token"])) {
                // Stocker le token pour les futures requêtes
                $this->token = $result["data"]["token"];
                
                return [
                    "success" => true,
                    "token" => $this->token,
                    "user" => $result["data"]["user"] ?? null,
                    "message" => $result["data"]["message"] ?? "Connexion réussie"
                ];
            }
            
            return [
                "success" => false,
                "message" => $result["data"]["message"] ?? $result["message"] ?? "Erreur de connexion"
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Erreur de connexion: " . $e->getMessage()
            ];
        }
    }

    private function makeInternalRequest($endpoint, $method = "GET", $data = null) {
        try {
            $url =  $endpoint;
//                 "http://webapp" .

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            
            // Ajouter le token d'authentification si disponible
            if (isset($_SESSION['token'])) {
                $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    "success" => false,
                    "message" => "Erreur cURL: " . $error
                ];
            }
            
            $decodedResponse = json_decode($response, true);
            
            return [
                "success" => $httpCode >= 200 && $httpCode < 300,
                "data" => $decodedResponse,
                "status_code" => $httpCode,
                "message" => $httpCode >= 200 && $httpCode < 300 ? "Succès" : "Erreur HTTP " . $httpCode
            ];
            
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Erreur de connexion interne: " . $e->getMessage()
            ];
        }
    }

    public function getUsers() {
        // Ajouter le token dans les headers
        $result = $this->makeRequest("users", "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                return [
                    "success" => false,
                    "data" => ["users" => []],
                    "message" => "Aucune donnée reçue de l'API"
                ];
            }
            
            $data = $result["data"];
            
            if (is_array($data)) {
                // Format 1: { "users": [...] }
                if (isset($data["users"]) && is_array($data["users"])) {
                    return [
                        "success" => true,
                        "data" => $data,
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 2: { "data": [...] }
                elseif (isset($data["data"]) && is_array($data["data"])) {
                    return [
                        "success" => true,
                        "data" => ["users" => $data["data"]],
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
                // Format 3: [...] (tableau direct)
                elseif (is_array($data) && !empty($data)) {
                    return [
                        "success" => true,
                        "data" => ["users" => $data],
                        "message" => "Utilisateurs récupérés avec succès"
                    ];
                }
            }
        }
        
        return [
            "success" => false,
            "data" => ["users" => []],
            "message" => "Erreur lors de la récupération des utilisateurs"
        ];
    }

    public function getGroups() {
        
        // Les groupes nécessitent une authentification
        if (!$this->token) {
            return [
                "success" => false,
                "data" => ["groups" => []],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /group/list pour les groupes
        $result = $this->makeRequest("groups/list", "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                return [
                    "success" => false,
                    "data" => ["groups" => []],
                    "message" => "Aucune donnée reçue de l'API pour les groupes"
                ];
            }
            
            $data = $result["data"];
            
            // La réponse devrait être un tableau direct de groupes
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => ["groups" => $data],
                    "message" => "Groupes récupérés avec succès"
                ];
            }
            
        }
        
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
    
    public function getSimulatedUsers() {
        return [
            [
                "id" => 1,
                "first_name" => "Admin",
                "last_name" => "Gémenos",
                "name" => "Gémenos",
                "email" => "admin@archers-gemenos.fr",
                "role" => "admin",
                "status" => "active",
                "profileImage" => "/uploads/profiles/admin.jpg",
                "created_at" => "2024-01-01 10:00:00"
            ],
            [
                "id" => 2,
                "first_name" => "Jean",
                "last_name" => "Dupont",
                "name" => "Dupont",
                "email" => "jean.dupont@archers-gemenos.fr",
                "role" => "user",
                "status" => "active",
                "profileImage" => "/uploads/profiles/jean.jpg",
                "created_at" => "2024-01-15 14:30:00"
            ],
            [
                "id" => 3,
                "first_name" => "Marie",
                "last_name" => "Martin",
                "name" => "Martin",
                "email" => "marie.martin@archers-gemenos.fr",
                "role" => "user",
                "status" => "active",
                "created_at" => "2024-02-01 09:15:00"
            ]
        ];
    }

    public function getGroupDetails($groupId) {
        
        // Les groupes nécessitent une authentification
        if (!$this->token) {
            return [
                "success" => false,
                "data" => null,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id} pour les détails d'un groupe
        $result = $this->makeRequest("groups/" . $groupId, "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            
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
            
        }
        
        return [
            "success" => false,
            "data" => null,
            "message" => "Impossible de récupérer les détails du groupe depuis l'API"
        ];
    }

    public function getGroupChat($groupId) {
        
        // Les chats nécessitent une authentification
        if (!$this->token) {
            return [
                "success" => false,
                "data" => null,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/chat pour les messages du chat
        $result = $this->makeRequest("groups/" . $groupId . "/chat", "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => $data,
                    "message" => "Messages du chat récupérés avec succès"
                ];
            }
            
        }
        
        return [
            "success" => false,
            "data" => null,
            "message" => "Impossible de récupérer les messages du chat depuis l'API"
        ];
    }
    public function getUserDocuments($userId) {
        // Vérifier si nous avons un token valide
        if (!$this->token) {
            return [
                "success" => false,
                "data" => ["documents" => []],
                "message" => "Token d'authentification requis"
            ];
        }

        
        // Appel à l'API pour récupérer les documents
        $result = $this->makeRequest("documents/user/{$userId}", "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            $data = $result["data"];
            
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => $data,
                    "message" => "Documents récupérés avec succès"
                ];
            }
            
        }
        
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
            } elseif ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        if ($error) {
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
            // Si c'est un code 200, considérer comme succès même si ce n'est pas du JSON
            if ($httpCode === 200) {
                return [
                    "success" => true,
                    "message" => "Message envoyé avec succès",
                    "status_code" => $httpCode,
                    "raw_response" => $body
                ];
            }
            
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
        
        $result = $this->makeRequest("/users/{$userId}", "DELETE");
        
        return $result;
    }
    
    public function createUser($userData) {
        
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
        
        return $result;
    }

    /**
     * Récupère tous les utilisateurs
     * @return array Liste de tous les utilisateurs
     */
    public function getAllUsers() {
        
        $result = $this->makeRequest("users", "GET");
        
        return $result;
    }

    /**
     * Récupère les utilisateurs en attente de validation
     * @return array Liste des utilisateurs en attente
     */
    public function getPendingUsers() {
        
        $result = $this->makeRequest("users/pending", "GET");
        
        return $result;
    }

    /**
     * Valide un utilisateur en attente
     * @param int $userId ID de l'utilisateur à valider
     * @return array Résultat de la validation
     */
    public function approveUser($userId) {
        
        $result = $this->makeRequest("users/{$userId}/approve", "POST");
        
        return $result;
    }

    /**
     * Rejette un utilisateur en attente
     * @param int $userId ID de l'utilisateur à rejeter
     * @param string $reason Raison du rejet
     * @return array Résultat du rejet
     */
    public function rejectUser($userId, $reason = '') {
        
        $data = [];
        if (!empty($reason)) {
            $data['reason'] = $reason;
        }
        
        $result = $this->makeRequest("users/{$userId}/reject", "POST", $data);
        
        return $result;
    }

    public function getGroupMessages($groupId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /history comme défini dans le backend
        $result = $this->makeRequest("messages/" . $groupId . "/history", "GET");
        
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
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /send comme défini dans le backend
        $result = $this->makeRequest("messages/" . $groupId . "/send", "POST", $messageData);
        
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
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Récupérer l'ID de l'utilisateur depuis le token
        $userId = $this->getCurrentUserId();
        if (!$userId) {
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
        
        // Utiliser l'endpoint /groups/create comme défini dans BackendPHP
        $result = $this->makeRequest("groups/create", "POST", $createData);
        
        return $result;
    }
    
    public function updateGroup($groupId, $groupData) {
        
        if (!$this->token) {
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
        
        // Utiliser l'endpoint PUT /groups/{id}
        $result = $this->makeRequest("groups/{$groupId}", "PUT", $updateData);
        
        return $result;
    }
    
    public function deleteGroup($groupId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint DELETE /groups/{id}
        $result = $this->makeRequest("groups/{$groupId}", "DELETE");
        
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
                return null;
            }
        }
        
        return null;
    }

    public function getGroupMembers($groupId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/authorized-users
        $result = $this->makeRequest("groups/" . $groupId . "/authorized-users", "GET");
        
        return $result;
    }

    public function addGroupMembers($groupId, $userIds) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /groups/{id}/members
        $result = $this->makeRequest("groups/" . $groupId . "/members", "POST", [
            'user_ids' => $userIds
        ]);
        
        return $result;
    }

    public function checkGroupAccess($groupId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /groups/{id}/check-access
        $result = $this->makeRequest("groups/" . $groupId . "/check-access", "GET");
        
        return $result;
    }
    // Méthodes pour les événements
    public function getEvents() {
        
        // Les événements nécessitent une authentification
        if (!$this->token) {
            return [
                "success" => false,
                "data" => ["events" => []],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint /events/list pour les événements
        $result = $this->makeRequest("events/list", "GET");
        
        if ($result["success"] && $result["status_code"] == 200) {
            // Vérifier que la clé "data" existe et n'est pas null
            if (!isset($result["data"]) || $result["data"] === null) {
                return [
                    "success" => false,
                    "data" => ["events" => []],
                    "message" => "Aucune donnée reçue de l'API pour les événements"
                ];
            }
            
            $data = $result["data"];
            
            // La réponse devrait être un tableau direct d'événements
            if (is_array($data)) {
                return [
                    "success" => true,
                    "data" => ["events" => $data],
                    "message" => "Événements récupérés avec succès"
                ];
            }
            
        }
        
        return [
            "success" => false,
            "data" => ["events" => []],
            "message" => "Impossible de récupérer les événements depuis l'API"
        ];
    }
    
    public function getEventDetails($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "GET");
        
        return $result;
    }
    
    public function createEvent($eventData) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Récupérer l'ID de l'utilisateur depuis le token
        $userId = $this->getCurrentUserId();
        if (!$userId) {
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
        
        // Utiliser l'endpoint /events/create
        $result = $this->makeRequest("events/create", "POST", $createData);
        
        return $result;
    }
    
    public function updateEvent($eventId, $eventData) {
        
        if (!$this->token) {
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
        
        // Utiliser l'endpoint PUT /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "PUT", $updateData);
        
        return $result;
    }
    
    public function deleteEvent($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint DELETE /events/{id}
        $result = $this->makeRequest("events/{$eventId}", "DELETE");
        
        return $result;
    }
    
    public function registerToEvent($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /events/{id}/register
        $result = $this->makeRequest("events/{$eventId}/register", "POST");
        
        return $result;
    }
    
    public function unregisterFromEvent($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint POST /events/{id}/unregister
        $result = $this->makeRequest("events/{$eventId}/unregister", "POST");
        
        return $result;
    }
    
    public function checkEventRegistration($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}/registration
        $result = $this->makeRequest("events/{$eventId}/registration", "GET");
        
        return $result;
    }
    
    public function getEventMessages($eventId) {
        
        if (!$this->token) {
            return [
                "success" => false,
                "data" => [],
                "message" => "Token d'authentification requis"
            ];
        }
        
        // Utiliser l'endpoint GET /events/{id}/messages
        $result = $this->makeRequest("events/{$eventId}/messages", "GET");
        
        return $result;
    }
    
    // Méthodes pour les exercices
    public function getExercises($showHidden = false) {
        $endpoint = 'exercise_sheets';
        if ($showHidden) {
            $endpoint .= '?show_hidden=1';
        }
        $result = $this->makeRequest($endpoint, 'GET');
        return $result;
    }
    
    public function getExercisesByUser($userId) {
        // Utiliser l'endpoint training/progress/user/{user_id} pour récupérer les exercices avec progression
        $endpoint = 'training/progress/user/' . $userId;
        $result = $this->makeRequest($endpoint, 'GET');
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
        } else {
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
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
        
        $result = $this->makeRequest($endpoint, 'GET');
        
        // Gérer la structure imbriquée de l'API
        if (isset($result['success']) && $result['success'] && isset($result['data'])) {
            $data = $result['data'];
            
            // Si les données sont encore imbriquées
            if (is_array($data) && isset($data['success']) && $data['success'] && isset($data['data'])) {
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
     * Met à jour la photo de profil d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $profileImagePath Chemin vers la nouvelle image
     * @return array Réponse de l'API
     */
    public function updateUserProfileImage($userId, $profileImagePath) {
        $endpoint = "/users/" . $userId . "/profile-image";
        $data = ['profile_image' => $profileImagePath];
        return $this->makeRequest($endpoint, 'PUT', $data);
    }
    
    /**
     * Upload une photo de profil vers le backend
     * @param int $userId ID de l'utilisateur
     * @param array $file Fichier uploadé
     * @return array Réponse de l'API
     */
    public function uploadProfileImage($userId, $file) {
        $endpoint = $this->baseUrl . "/users/" . $userId . "/upload-profile-image";
        
        // Utiliser cURL pour l'upload de fichier
        $ch = curl_init();
        
        // Préparer les données pour l'upload multipart
        $postData = [
            'profileImage' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
        ];
        
        // Debug: décoder le token JWT pour voir son contenu
        try {
            $tokenParts = explode('.', $this->token);
            if (count($tokenParts) === 3) {
                $payload = json_decode(base64_decode($tokenParts[1]), true);
            }
        } catch (Exception $e) {
            error_log("DEBUG uploadProfileImage - Erreur décodage token: " . $e->getMessage());
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => 'Erreur de connexion: ' . $error];
        }
        
        // Traiter la réponse
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Réponse invalide du serveur'];
        }
        
        $response = [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $responseData,
            'status_code' => $httpCode,
            'message' => $httpCode >= 200 && $httpCode < 300 ? 'Succès' : 'Erreur HTTP ' . $httpCode
        ];
        
        // Adapter la réponse pour correspondre à ce que le contrôleur attend
        if ($response['success'] && isset($response['data']['user']['profileImage'])) {
            return [
                'success' => true,
                'profile_image_path' => $response['data']['user']['profileImage'],
                'image_url' => $this->getBaseUrlWithoutApi() . $response['data']['user']['profileImage']
            ];
        }
        
        // Si la réponse n'a pas la structure attendue, vérifier d'autres formats
        if ($response['success'] && isset($response['data']['user'])) {
            $user = $response['data']['user'];
            if (isset($user['profileImage'])) {
                return [
                    'success' => true,
                    'profile_image_path' => $user['profileImage'],
                    'image_url' => $this->getBaseUrlWithoutApi() . $user['profileImage']
                ];
            }
        }
        
        return $response;
    }
    
    /**
     * Change le mot de passe d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $currentPassword Mot de passe actuel
     * @param string $newPassword Nouveau mot de passe
     * @return array Réponse de l'API
     */
    public function changeUserPassword($userId, $currentPassword, $newPassword) {
        // Utiliser l'endpoint de mise à jour d'identité qui existe
        $endpoint = "/users/" . $userId . "/update-identite";
        $data = [
            'current_password' => $currentPassword,
            'new_password' => $newPassword
        ];
        return $this->makeRequest($endpoint, 'PUT', $data);
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
    
    /**
     * Récupère l'URL de base de l'API sans le suffixe /api
     * @return string URL de base
     */
    private function getBaseUrlWithoutApi() {
        // Récupérer l'URL de base depuis la configuration
        $baseUrl = $this->baseUrl;
        
        // Si l'URL se termine par /api, la retirer
        if (substr($baseUrl, -4) === '/api') {
            return substr($baseUrl, 0, -4);
        }
        
        return $baseUrl;
    }
}
?>







