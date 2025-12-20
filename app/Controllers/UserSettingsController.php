<?php

class UserSettingsController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    /**
     * Afficher la page des paramètres utilisateur
     */
    public function index() {
        $pageTitle = "Paramètres utilisateur - Portail Archers de Gémenos";
        
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur connecté (gérer différents formats)
        $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['_id'] ?? null;
        
        // Si pas d'ID dans la session, essayer de récupérer depuis le token JWT
        if (!$userId && isset($_SESSION['token'])) {
            try {
                $tokenParts = explode('.', $_SESSION['token']);
                if (count($tokenParts) === 3) {
                    $payload = json_decode(base64_decode($tokenParts[1]), true);
                    $userId = $payload['user_id'] ?? $payload['id'] ?? null;
                }
            } catch (Exception $e) {
                error_log("Erreur décodage token dans UserSettingsController: " . $e->getMessage());
            }
        }
        
        if (!$userId) {
            $_SESSION['error'] = 'Impossible de récupérer les informations de l\'utilisateur';
            header('Location: /login');
            exit;
        }
        
        try {
            // Essayer d'abord de récupérer l'utilisateur connecté via /auth/me si disponible
            // Cet endpoint retourne TOUJOURS l'utilisateur connecté, peu importe l'ID en session
            $user = null;
            $userIdFromMe = null;
            try {
                $meResponse = $this->apiService->makeRequest('auth/me', 'GET');
                
                if ($meResponse['success'] && !empty($meResponse['data'])) {
                    $user = $meResponse['data']['user'] ?? $meResponse['data'];
                    
                    // Extraire l'ID de l'utilisateur depuis /auth/me
                    $userIdFromMe = $user['id'] ?? $user['_id'] ?? null;
                    
                    // Si on a récupéré un ID différent de celui de la session, mettre à jour la session
                    if ($userIdFromMe && $userIdFromMe != $userId) {
                        $_SESSION['user']['id'] = $userIdFromMe;
                        $_SESSION['user']['_id'] = $userIdFromMe;
                        $userId = $userIdFromMe; // Utiliser le bon ID
                    }
                }
            } catch (Exception $e) {
                // L'endpoint /auth/me n'existe peut-être pas, continuer avec getUserById
                error_log("Endpoint /auth/me non disponible, utilisation de getUserById: " . $e->getMessage());
            }
            
            // Si /auth/me n'a pas fonctionné, utiliser getUserById avec le bon ID
            if (!$user || empty($user['username'])) {
                // Utiliser l'ID récupéré de /auth/me si disponible, sinon celui de la session
                $userIdToUse = $userIdFromMe ?? $userId;
                $response = $this->apiService->getUserById($userIdToUse);
                
                // Gérer différents formats de réponse de l'API
                if ($response['success']) {
                    // Format 1: response['data'] contient directement l'utilisateur
                    if (!empty($response['data']) && (isset($response['data']['username']) || isset($response['data']['email']) || isset($response['data']['id']) || isset($response['data']['_id']))) {
                        $user = $response['data'];
                    }
                    // Format 2: response['data']['user'] contient l'utilisateur
                    elseif (!empty($response['data']['user'])) {
                        $user = $response['data']['user'];
                    }
                    // Format 3: response['user'] contient l'utilisateur
                    elseif (!empty($response['user'])) {
                        $user = $response['user'];
                    }
                }
            }
            
            // Si on n'a toujours pas récupéré de données de l'API, utiliser la session
            if (!$user || (empty($user['username']) && empty($user['email']))) {
                $user = $_SESSION['user'];
            } else {
                // Les données de l'API sont disponibles - elles ont PRIORITÉ ABSOLUE
                // On commence par copier les données de session (pour les champs non présents dans l'API)
                // Puis on ÉCRASE avec les données de l'API pour les champs critiques
                $userFromAPI = $user; // Sauvegarder les données brutes de l'API
                $user = array_merge($_SESSION['user'] ?? [], $user);
                
                if (isset($userFromAPI['firstName'])) {
                   $user['firstName'] = $userFromAPI['firstName'];
                }
                
                // Nom - chercher dans tous les formats possibles
                if (isset($userFromAPI['name'])) {
                    $user['name'] = $userFromAPI['name'];
                }
              
                // Email - chercher dans tous les formats possibles
                if (isset($userFromAPI['email'])) {
                    $user['email'] = $userFromAPI['email'];
                }
                
                // Username - chercher dans tous les formats possibles
                if (isset($userFromAPI['username'])) {
                    $user['username'] = $userFromAPI['username'];
                }
            }
            
            // Normaliser les champs (gérer id/_id, firstName/first_name, etc.)
            // S'assurer que les deux formats sont présents pour compatibilité
            if (!empty($user['_id']) && empty($user['id'])) {
                $user['id'] = $user['_id'];
            }
            if (!empty($user['id']) && empty($user['_id'])) {
                $user['_id'] = $user['id'];
            }
            
            // Normaliser firstName/first_name
            if (!empty($user['first_name']) && empty($user['firstName'])) {
                $user['firstName'] = $user['first_name'];
            }
            if (!empty($user['firstName']) && empty($user['first_name'])) {
                $user['first_name'] = $user['firstName'];
            }
            
            // Normaliser name/last_name
            if (!empty($user['last_name']) && empty($user['name'])) {
                $user['name'] = $user['last_name'];
            }
            if (!empty($user['name']) && empty($user['last_name'])) {
                $user['last_name'] = $user['name'];
            }
            
            // S'assurer que l'email est bien celui de la base de données (pas celui construit)
            // Si l'email dans $user semble être construit (contient '@archers-gemenos.fr' et correspond au username),
            // et qu'on a un email différent dans la session, vérifier lequel est le bon
            if (isset($user['email']) && strpos($user['email'], '@archers-gemenos.fr') !== false) {
                $emailFromUsername = ($user['username'] ?? '') . '@archers-gemenos.fr';
            }
            
            // S'assurer que l'ID de l'utilisateur correspond bien à celui de la session
            $sessionUserId = $_SESSION['user']['id'] ?? $_SESSION['user']['_id'] ?? null;
            $retrievedUserId = $user['id'] ?? $user['_id'] ?? null;
            
            // Vérifier que l'utilisateur récupéré correspond bien à l'utilisateur connecté
            if ($sessionUserId && $retrievedUserId && $retrievedUserId != $sessionUserId) {
                // Si l'ID récupéré correspond à l'ID demandé, c'est que l'ID de session est incorrect
                // Il faut mettre à jour la session avec le bon ID
                if ($retrievedUserId == $userId) {
                    // Mettre à jour l'ID dans la session avec le bon ID
                    $_SESSION['user']['id'] = $retrievedUserId;
                    $_SESSION['user']['_id'] = $retrievedUserId;
                } else {
                    try {
                        $sessionResponse = $this->apiService->getUserById($sessionUserId);
                        if ($sessionResponse['success'] && !empty($sessionResponse['data'])) {
                            $sessionUser = $sessionResponse['data']['user'] ?? $sessionResponse['data'];
                        }
                    } catch (Exception $e) {
                        error_log("Erreur lors de la récupération avec l'ID de session: " . $e->getMessage());
                    }
                }
            } elseif ($sessionUserId && !$retrievedUserId) {
                $user = $_SESSION['user'];
            } elseif (!$sessionUserId && $retrievedUserId) {
                $_SESSION['user']['id'] = $retrievedUserId;
                $_SESSION['user']['_id'] = $retrievedUserId;
            }
            
            // Inclure la vue avec les données de l'utilisateur
            include __DIR__ . '/../Views/users/settings.php';
        } catch (Exception $e) {
            // Utiliser les données de session comme fallback
            $user = $_SESSION['user'];
            // Normaliser les champs
            if (empty($user['id']) && !empty($user['_id'])) {
                $user['id'] = $user['_id'];
            }
            if (empty($user['firstName']) && !empty($user['first_name'])) {
                $user['firstName'] = $user['first_name'];
            }
            if (empty($user['name']) && !empty($user['last_name'])) {
                $user['name'] = $user['last_name'];
            }
            include __DIR__ . '/../Views/users/settings.php';
        }
    }
    
    /**
     * Mettre à jour la photo de profil
     */
    public function updateProfileImage() {
        // Nettoyer le buffer de sortie pour éviter les BOM
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->sendCleanJson(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        // Utiliser l'ID du token JWT au lieu de la session pour éviter les incohérences
        $token = $_SESSION['token'] ?? null;
        $userId = null;
        
        if ($token) {
            try {
                $tokenParts = explode('.', $token);
                if (count($tokenParts) === 3) {
                    $payload = json_decode(base64_decode($tokenParts[1]), true);
                    $userId = $payload['user_id'] ?? null;
                }
            } catch (Exception $e) {
                error_log("DEBUG updateProfileImage - Erreur décodage token: " . $e->getMessage());
            }
        }
        
        // Fallback sur la session si le token ne fonctionne pas
        if (!$userId) {
            $userId = $_SESSION['user']['id'] ?? null;
        }
        
        // Debug: afficher les données de session
        
        if (!$userId) {
            http_response_code(401);
            $this->sendCleanJson(['success' => false, 'message' => 'Non authentifié']);
            return;
        }
        
        try {
            // Vérifier qu'un fichier a été uploadé (gérer les deux noms possibles)
            $file = null;
            if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profileImage'];
            } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
            } else {
                throw new Exception('Aucun fichier uploadé ou erreur d\'upload');
            }
            
            // Vérifications de sécurité
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF, WebP');
            }
            
            // Vérifier la taille (max 1MB)
            if ($file['size'] > 1 * 1024 * 1024) {
                throw new Exception('Fichier trop volumineux. Taille maximum: 1MB');
            }
            
            // Envoyer directement au backend via l'API
            $result = $this->apiService->uploadProfileImage($userId, $file);
            
            if ($result['success']) {
                // Mettre à jour la session
                $_SESSION['user']['profile_image'] = $result['profile_image_path'];
                
                $this->sendCleanJson([
                    'success' => true, 
                    'message' => 'Photo de profil mise à jour avec succès',
                    'image_url' => $result['image_url']
                ]);
            } else {
                throw new Exception($result['message'] ?? 'Erreur lors de la mise à jour');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            $this->sendCleanJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword() {
        // Nettoyer le buffer de sortie pour éviter les BOM
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->sendCleanJson(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        // Utiliser l'ID du token JWT au lieu de la session pour éviter les incohérences
        $token = $_SESSION['token'] ?? null;
        $userId = null;
        
        if ($token) {
            try {
                $tokenParts = explode('.', $token);
                if (count($tokenParts) === 3) {
                    $payload = json_decode(base64_decode($tokenParts[1]), true);
                    $userId = $payload['user_id'] ?? null;
                }
            } catch (Exception $e) {
                error_log("DEBUG changePassword - Erreur décodage token: " . $e->getMessage());
            }
        }
        
        // Fallback sur la session si le token ne fonctionne pas
        if (!$userId) {
            $userId = $_SESSION['user']['id'] ?? null;
        }
        
        if (!$userId) {
            http_response_code(401);
            $this->sendCleanJson(['success' => false, 'message' => 'Non authentifié']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            // Validations
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Tous les champs sont requis');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Les nouveaux mots de passe ne correspondent pas');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères');
            }
            
            // Appeler l'API pour changer le mot de passe
            $result = $this->apiService->changeUserPassword($userId, $currentPassword, $newPassword);
            
            if ($result['success']) {
                $this->sendCleanJson([
                    'success' => true, 
                    'message' => 'Mot de passe modifié avec succès'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            $this->sendCleanJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Envoyer une réponse JSON propre sans BOM
     */
    private function sendCleanJson($data) {
        // Nettoyer complètement le buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Headers pour JSON propre
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Encoder et envoyer le JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}