<?php
// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';

class DashboardController {
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
        
        $title = 'Tableau de bord - Portail Archers de Gémenos';
        
        // Récupérer les statistiques
        $stats = $this->getStats();
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue du dashboard
        include 'app/Views/dashboard/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    private function getStats() {
        $stats = [
            'users' => 0,
            'groups' => 0,
            'trainings' => 0,
            'events' => 0,
            'exercises' => 0
        ];
        
        try {
            // Récupérer le nombre d'utilisateurs
            $usersResponse = $this->apiService->getUsers();
            if ($usersResponse['success'] && !empty($usersResponse['data']['users'])) {
                $stats['users'] = count($usersResponse['data']['users']);
            }
            
            // Récupérer le nombre de groupes
            $groupsResponse = $this->apiService->getGroups();
            
            if ($groupsResponse['success']) {
                if (!empty($groupsResponse['data']['groups'])) {
                    $stats['groups'] = count($groupsResponse['data']['groups']);
                }
            }
            
            // Récupérer le nombre d'exercices
            $exercisesResponse = $this->apiService->getExercises();
            if ($exercisesResponse['success'] && !empty($exercisesResponse['data'])) {
                $stats['exercises'] = count($exercisesResponse['data']);
            }
            
            // Pour les autres statistiques, on utilise des valeurs par défaut
            $stats['trainings'] = 12; // Valeur par défaut
            $stats['events'] = 3; // Valeur par défaut
            
        } catch (Exception $e) {
            // En cas d'erreur, on garde les valeurs par défaut
            error_log('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
        }
        return $stats;
    }
}
