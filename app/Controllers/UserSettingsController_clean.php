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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
            return;
        }
        
        try {
            // Vérifier qu'un fichier a été uploadé
            if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Aucun fichier uploadé ou erreur d\'upload');
            }
            
            $file = $_FILES['profile_image'];
            
            // Vérifications de sécurité
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF, WebP');
            }
            
            // Vérifier la taille (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('Fichier trop volumineux. Taille maximum: 5MB');
            }
            
            // Envoyer directement au backend via l'API
            $result = $this->apiService->uploadProfileImage($userId, $file);
            
            if ($result['success']) {
                // Mettre à jour la session
                $_SESSION['user']['profile_image'] = $result['profile_image_path'];
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Photo de profil mise à jour avec succès',
                    'image_url' => $result['image_url']
                ]);
            } else {
                throw new Exception($result['message'] ?? 'Erreur lors de la mise à jour');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
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
                echo json_encode([
                    'success' => true, 
                    'message' => 'Mot de passe modifié avec succès'
                ]);
            } else {
                throw new Exception($result['message'] ?? 'Erreur lors du changement de mot de passe');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}