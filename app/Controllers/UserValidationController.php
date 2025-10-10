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