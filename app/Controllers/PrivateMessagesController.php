<?php
// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';
require_once __DIR__ . '/../Middleware/SessionGuard.php';

class PrivateMessagesController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    /**
     * Affiche la liste des conversations privées de l'utilisateur
     */
    public function index() {
        error_log("PrivateMessagesController::index() - Début");
        
        // Vérifier la session avec le middleware
        SessionGuard::check();
        
        error_log("PrivateMessagesController::index() - Session valide, chargement des conversations");
        
        $pageTitle = 'Messages Privés - Portail Archers de Gémenos';
        
        // Récupérer toutes les conversations de l'utilisateur connecté
        $conversations = $this->getConversations();
        
        // Récupérer tous les utilisateurs pour pouvoir démarrer de nouvelles conversations
        $users = $this->getAllUsers();
        
        // Définir les fichiers CSS et JS spécifiques
        $additionalCSS = ['/public/assets/css/chat-messages.css'];
        $additionalJS = ['/public/assets/js/private-messages.js'];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue des messages privés
        include 'app/Views/private-messages/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Récupère toutes les conversations privées de l'utilisateur
     */
    private function getConversations() {
        try {
            $response = $this->apiService->makeRequest('private-messages/conversations', 'GET');
            
            error_log("PrivateMessagesController::getConversations() - Response: " . json_encode($response));
            
            if ($response['success'] ?? false) {
                return $response['data'] ?? [];
            } else if (isset($response['unauthorized']) && $response['unauthorized']) {
                // Nettoyer la session et rediriger vers login
                session_unset();
                session_destroy();
                header('Location: /login?expired=1');
                exit;
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des conversations: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère tous les utilisateurs pour pouvoir démarrer de nouvelles conversations
     */
    private function getAllUsers() {
        try {
            $response = $this->apiService->getUsers();
            
            if ($response['success'] && !empty($response['data']['users'])) {
                $users = $response['data']['users'];
                $currentUserId = $_SESSION['user']['id'] ?? null;
                
                // Filtrer pour exclure l'utilisateur actuel et les utilisateurs inactifs
                $filteredUsers = array_filter($users, function($user) use ($currentUserId) {
                    $userId = $user['id'] ?? $user['_id'] ?? '';
                    $status = $user['status'] ?? 'active';
                    return $userId !== $currentUserId && $status === 'active';
                });
                
                // Réindexer le tableau
                return array_values($filteredUsers);
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            return [];
        }
    }
}
