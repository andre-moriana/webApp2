<?php

// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';

class TrainingController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $title = 'Entraînements - Portail Archers de Gémenos';
        
        // Récupérer les informations de l'utilisateur connecté
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        $isArcher = ($currentUser['role'] ?? '') === 'Archer';
        
        // Récupérer les utilisateurs pour la sélection (admin et coach seulement)
        $users = [];
        if ($isAdmin || $isCoach) {
            $usersResponse = $this->apiService->getUsers();
            if ($usersResponse['success'] && !empty($usersResponse['data']['users'])) {
                $users = $usersResponse['data']['users'];
            }
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $currentUser['id']; // Par défaut, l'utilisateur connecté
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        
        // Récupérer les entraînements de l'utilisateur sélectionné
        $trainings = $this->getTrainings($selectedUserId);
        
        // Récupérer les statistiques
        $stats = $this->getTrainingStats($selectedUserId);
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue des entraînements
        include 'app/Views/trainings/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    public function show($id) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer les détails de l'entraînement
        $training = $this->getTrainingById($id);
        
        if (!$training) {
            header('Location: /trainings?error=' . urlencode('Entraînement non trouvé'));
            exit;
        }
        
        // Vérifier les permissions
        if (!$isAdmin && !$isCoach && $training['user_id'] != $currentUser['id']) {
            header('Location: /trainings?error=' . urlencode('Accès refusé'));
            exit;
        }
        
        $title = 'Détails de l\'entraînement - Portail Archers de Gémenos';
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue des détails d'entraînement
        include 'app/Views/trainings/show.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    public function stats($id) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer les statistiques de l'entraînement
        $stats = $this->getTrainingStats($id);
        
        // Vérifier les permissions
        if (!$isAdmin && !$isCoach && $id != $currentUser['id']) {
            header('Location: /trainings?error=' . urlencode('Accès refusé'));
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    private function getTrainings($userId) {
        try {
            // Appeler l'API pour récupérer les entraînements
            $response = $this->apiService->getTrainings($userId);
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des entraînements: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getTrainingById($trainingId) {
        try {
            // Appeler l'API pour récupérer un entraînement spécifique
            $response = $this->apiService->getTrainingById($trainingId);
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de l\'entraînement: ' . $e->getMessage());
            return null;
        }
    }
    
    private function getTrainingStats($userId) {
        try {
            // Appeler l'API pour récupérer les statistiques
            $response = $this->apiService->getTrainingStats($userId);
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            return [
                'total_trainings' => 0,
                'total_arrows' => 0,
                'total_ends' => 0,
                'total_score' => 0,
                'average_score' => 0,
                'best_training_score' => 0,
                'last_training_date' => null
            ];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return [
                'total_trainings' => 0,
                'total_arrows' => 0,
                'total_ends' => 0,
                'total_score' => 0,
                'average_score' => 0,
                'best_training_score' => 0,
                'last_training_date' => null
            ];
        }
    }
}
