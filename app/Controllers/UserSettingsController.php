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
        
        // Récupérer les informations de l'utilisateur connecté
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            header('Location: /login');
            exit;
        }
        
        try {
            // Récupérer les informations détaillées de l'utilisateur
            $response = $this->apiService->getUserById($userId);
            
            if ($response['success'] && !empty($response['data'])) {
                $user = $response['data'];
            } else {
                // Utiliser les données de session comme fallback
                $user = $_SESSION['user'];
            }
            
            // Inclure la vue
            include __DIR__ . '/../Views/users/settings.php';
        } catch (Exception $e) {
            // Utiliser les données de session comme fallback
            $user = $_SESSION['user'];
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