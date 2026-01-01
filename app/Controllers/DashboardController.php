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
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/dashboard.js'];
        
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
            'exercises' => 0,
            'clubs_regional' => 0,
            'clubs_departmental' => 0,
            'clubs_total' => 0,
            'users_pending_validation' => 0,
            'users_pending_deletion' => 0,
            'clubs_regional_list' => [],
            'clubs_departmental_list' => []
        ];
        
        try {
            // Récupérer le nombre d'utilisateurs
            $usersResponse = $this->apiService->getUsers();
            if ($usersResponse['success'] && !empty($usersResponse['data']['users'])) {
                $users = $usersResponse['data']['users'];
                $stats['users'] = count($users);
                
                // Compter les utilisateurs en attente de validation et de suppression
                foreach ($users as $user) {
                    $status = $user['status'] ?? 'active';
                    $deletionPending = $user['deletion_pending'] ?? $user['deletionPending'] ?? false;
                    
                    if ($status === 'pending') {
                        $stats['users_pending_validation']++;
                    }
                    if ($deletionPending) {
                        $stats['users_pending_deletion']++;
                    }
                }
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
            
            // Récupérer les clubs et compter par type
            $clubsResponse = $this->apiService->getClubs();
            if ($clubsResponse['success'] && !empty($clubsResponse['data']['clubs'])) {
                $clubs = $clubsResponse['data']['clubs'];
                if (is_array($clubs)) {
                    // IDs spéciaux à exclure du comptage
                    $excludedIds = ['0000000', '0000001', '0000002', '0000005', '0000006'];
                    
                    foreach ($clubs as $club) {
                        $clubId = $club['nameshort'] ?? $club['nameShort'] ?? '';
                        $clubName = $club['name'] ?? 'Club sans nom';
                        
                        // Ignorer les IDs spéciaux
                        if (in_array($clubId, $excludedIds)) {
                            continue;
                        }
                        
                        $stats['clubs_total']++;
                        
                        // Comité Régional : finit par 00000 sauf 0000000
                        if (preg_match('/00000$/', $clubId) && $clubId !== '0000000') {
                            $stats['clubs_regional']++;
                            $stats['clubs_regional_list'][] = $clubName;
                        }
                        // Comité Départemental : finit par 000 (mais pas 00000 qui sont régionaux)
                        elseif (preg_match('/000$/', $clubId) && !preg_match('/00000$/', $clubId)) {
                            $stats['clubs_departmental']++;
                            $stats['clubs_departmental_list'][] = $clubName;
                        }
                        // Sinon c'est un club normal (ne finit pas par 000)
                    }
                }
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
