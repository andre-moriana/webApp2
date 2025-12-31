<?php

require_once __DIR__ . '/../Services/ApiService.php';
require_once __DIR__ . '/AuthController.php';

class UserValidationController {
    private $apiService;

    public function __construct() {
        $this->apiService = new ApiService();
    }

    /**
     * Affiche la page de validation des utilisateurs
     */
    public function index() {
        // Vérifier l'authentification et l'approbation
        $authController = new AuthController();
        $authController->requireAuth();

        try {
            $result = $this->apiService->getPendingUsers();
            
            if ($result['success']) {
                // Gérer la double imbrication de data
                if (isset($result['data']['data'])) {
                    $pendingUsers = $result['data']['data'] ?? [];
                } else {
                    $pendingUsers = $result['data'] ?? [];
                }
            } else {
                $pendingUsers = [];
                $_SESSION['error'] = 'Erreur lors de la récupération des utilisateurs en attente: ' . ($result['message'] ?? 'Erreur inconnue');
            }
        } catch (Exception $e) {
            $pendingUsers = [];
            $_SESSION['error'] = 'Erreur lors de la récupération des utilisateurs en attente: ' . $e->getMessage();
        }

        // Récupérer les utilisateurs en attente de suppression
        try {
            $deletionResult = $this->apiService->getDeletionPendingUsers();
            
            if ($deletionResult['success']) {
                if (isset($deletionResult['data']['data'])) {
                    $deletionPendingUsers = $deletionResult['data']['data'] ?? [];
                } else {
                    $deletionPendingUsers = $deletionResult['data'] ?? [];
                }
            } else {
                $deletionPendingUsers = [];
            }
        } catch (Exception $e) {
            $deletionPendingUsers = [];
        }

        $title = 'Validation des utilisateurs - Portail Archers de Gémenos';
        
        // CSS spécifique à la page
        $additionalCSS = [
            '/public/assets/css/user-validation.css'
        ];
        
        // JS spécifique à la page
        $additionalJS = [
            '/public/assets/js/user-validation.js'
        ];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/user-validation/index.php';
        include 'app/Views/layouts/footer.php';
    }

    /**
     * Valide un utilisateur
     */
    public function approve() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user-validation');
            exit;
        }

        // Vérifier l'authentification et l'approbation
        $authController = new AuthController();
        $authController->requireAuth();

        $userId = $_POST['user_id'] ?? '';
        
        if (empty($userId)) {
            $_SESSION['error'] = 'ID utilisateur manquant';
            header('Location: /user-validation');
            exit;
        }

        try {
            $result = $this->apiService->approveUser($userId);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Utilisateur validé avec succès !';
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur lors de la validation de l\'utilisateur';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la validation de l\'utilisateur';
        }

        header('Location: /user-validation');
        exit;
    }

    /**
     * Rejette un utilisateur
     */
    public function reject() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user-validation');
            exit;
        }

        // Vérifier l'authentification et l'approbation
        $authController = new AuthController();
        $authController->requireAuth();

        $userId = $_POST['user_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (empty($userId)) {
            $_SESSION['error'] = 'ID utilisateur manquant';
            header('Location: /user-validation');
            exit;
        }

        try {
            $result = $this->apiService->rejectUser($userId, $reason);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Utilisateur rejeté avec succès !';
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur lors du rejet de l\'utilisateur';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors du rejet de l\'utilisateur';
        }

        header('Location: /user-validation');
        exit;
    }

    /**
     * Supprime définitivement un utilisateur
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user-validation');
            exit;
        }

        // Vérifier l'authentification et l'approbation
        $authController = new AuthController();
        $authController->requireAuth();

        $userId = $_POST['user_id'] ?? '';
        $confirmUsername = $_POST['confirm_username'] ?? '';
        
        if (empty($userId)) {
            $_SESSION['error'] = 'ID utilisateur manquant';
            header('Location: /user-validation');
            exit;
        }

        try {
            // Vérifier d'abord que l'utilisateur existe et est bien en attente de suppression
            $userResult = $this->apiService->makeRequest('users/' . $userId, 'GET');
            
            if (!$userResult['success']) {
                $_SESSION['error'] = 'Utilisateur non trouvé';
                header('Location: /user-validation');
                exit;
            }
            
            $user = $userResult['data'] ?? [];
            $username = $user['username'] ?? '';
            
            // Vérifier la confirmation du nom d'utilisateur
            if ($confirmUsername !== $username) {
                $_SESSION['error'] = 'Le nom d\'utilisateur de confirmation ne correspond pas';
                header('Location: /user-validation');
                exit;
            }
            
            // Supprimer l'utilisateur via l'API
            $result = $this->apiService->makeRequest('users/' . $userId, 'DELETE');
            
            if ($result['success']) {
                $_SESSION['success'] = 'Utilisateur supprimé définitivement avec succès !';
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur lors de la suppression de l\'utilisateur';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage();
        }

        header('Location: /user-validation');
        exit;
    }

    /**
     * Vérifie si l'utilisateur actuel est un administrateur
     */
    private function isAdmin() {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        $user = $_SESSION['user'];
        return isset($user['is_admin']) && $user['is_admin'] === true;
    }
}