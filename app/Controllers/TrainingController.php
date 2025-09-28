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
        
        // Grouper les entraînements par exercice
        $trainingsByExercise = $this->groupTrainingsByExercise($trainings);
        
        // Calculer les statistiques à partir des entraînements
        $stats = $this->calculateStatsFromTrainings($trainings);
        
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
            
            error_log("DEBUG getTrainings - Réponse API: " . json_encode($response));
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            // Si pas de données, retourner un array vide
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

    /**
     * Groupe les entraînements par exercice et calcule les statistiques
     * @param array $trainings Liste des entraînements
     * @return array Entraînements groupés par exercice
     */
    private function groupTrainingsByExercise($trainings) {
        $grouped = [];
        
        error_log("DEBUG groupTrainingsByExercise - Type de trainings: " . gettype($trainings));
        error_log("DEBUG groupTrainingsByExercise - Contenu: " . json_encode($trainings));
        
        // Vérifier que $trainings est un array
        if (!is_array($trainings)) {
            error_log("DEBUG groupTrainingsByExercise - trainings n'est pas un array, retour array vide");
            return [];
        }
        
        // Si $trainings est une réponse API, extraire les données
        if (isset($trainings['data']) && is_array($trainings['data'])) {
            $trainings = $trainings['data'];
        }
        
        // Si c'est un array de progrès d'exercices
        if (is_array($trainings)) {
            foreach ($trainings as $progress) {
                if (!is_array($progress)) {
                    continue;
                }
                
                // Adapter selon la structure des données de progrès
                $exerciseTitle = $progress['exercise_sheet_title'] ?? 'Sans exercice';
                $exerciseId = $progress['exercise_sheet_id'] ?? 'no_exercise';
                
                if (!isset($grouped[$exerciseId])) {
                    $grouped[$exerciseId] = [
                        'exercise_title' => $exerciseTitle,
                        'exercise_id' => $exerciseId,
                        'trainings' => [],
                        'stats' => [
                            'total_sessions' => 0,
                            'total_arrows' => 0,
                            'total_time_minutes' => 0,
                            'total_score' => 0,
                            'average_score' => 0,
                            'first_training' => null,
                            'last_training' => null
                        ]
                    ];
                }
                
                // Utiliser les données de progrès comme "entraînement"
                $grouped[$exerciseId]['trainings'][] = $progress;
                
                // Calculer les statistiques à partir des données de progrès
                $stats = &$grouped[$exerciseId]['stats'];
                $stats['total_sessions'] = $progress['total_sessions'] ?? 0;
                $stats['total_arrows'] = $progress['total_arrows'] ?? 0;
                $stats['total_time_minutes'] = $progress['total_duration_minutes'] ?? 0;
                $stats['total_score'] = 0; // Pas de score dans les données de progrès
                $stats['average_score'] = 0; // Pas de score dans les données de progrès
                
                // Dates - gérer les valeurs null
                $firstDate = $progress['start_date'] ?? null;
                $lastDate = $progress['last_session_date'] ?? null;
                
                if ($firstDate && (!$stats['first_training'] || $firstDate < $stats['first_training'])) {
                    $stats['first_training'] = $firstDate;
                }
                if ($lastDate && (!$stats['last_training'] || $lastDate > $stats['last_training'])) {
                    $stats['last_training'] = $lastDate;
                }
            }
        }
        
        // Trier par nombre de sessions (décroissant)
        uasort($grouped, function($a, $b) {
            return $b['stats']['total_sessions'] - $a['stats']['total_sessions'];
        });
        
        return $grouped;
    }

    /**
     * Calcule les statistiques à partir des données d'entraînements
     * @param array $trainings Liste des entraînements
     * @return array Statistiques calculées
     */
    private function calculateStatsFromTrainings($trainings) {
        // Si $trainings est une réponse API, extraire les données
        if (isset($trainings['data']) && is_array($trainings['data'])) {
            $trainings = $trainings['data'];
        }
        
        $stats = [
            'total_trainings' => 0,
            'total_arrows' => 0,
            'total_ends' => 0,
            'total_score' => 0,
            'average_score' => 0,
            'best_training_score' => 0,
            'last_training_date' => null,
            'total_time_minutes' => 0
        ];
        
        if (empty($trainings)) {
            return $stats;
        }
        
        $totalScore = 0;
        $bestScore = 0;
        $lastDate = null;
        $totalTimeMinutes = 0;
        
        error_log("DEBUG calculateStatsFromTrainings - Nombre d'entraînements: " . count($trainings));
        
        foreach ($trainings as $training) {
            if (!is_array($training)) {
                continue;
            }
            
            $stats['total_trainings']++;
            
            // Adapter selon la structure de données
            $arrows = $training['total_arrows'] ?? 0;
            $ends = $training['total_ends'] ?? 0;
            $score = $training['total_score'] ?? $training['score'] ?? 0;
            $date = $training['start_date'] ?? $training['created_at'] ?? '';
            $timeMinutes = $training['total_duration_minutes'] ?? 0;
            
            error_log("DEBUG calculateStatsFromTrainings - Exercice: " . ($training['exercise_sheet_title'] ?? 'Inconnu') . 
                     " - Temps: " . $timeMinutes . " min - Flèches: " . $arrows);
            
            $stats['total_arrows'] += $arrows;
            $stats['total_ends'] += $ends;
            $totalScore += $score;
            $totalTimeMinutes += $timeMinutes;
            
            if ($score > $bestScore) {
                $bestScore = $score;
            }
            
            if ($date && (!$lastDate || $date > $lastDate)) {
                $lastDate = $date;
            }
        }
        
        $stats['total_score'] = $totalScore;
        $stats['best_training_score'] = $bestScore;
        $stats['last_training_date'] = $lastDate;
        $stats['total_time_minutes'] = $totalTimeMinutes;
        
        error_log("DEBUG calculateStatsFromTrainings - Temps total calculé: " . $totalTimeMinutes . " minutes");
        
        if ($stats['total_trainings'] > 0) {
            $stats['average_score'] = $totalScore / $stats['total_trainings'];
        }
        
        return $stats;
    }
}
