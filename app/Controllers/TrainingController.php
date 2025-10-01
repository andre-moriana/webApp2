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
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer l'ID utilisateur depuis le token JWT pour éviter l'incohérence
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Mettre à jour les données de session avec les vraies données de l'utilisateur
        if ($actualUserId != ($currentUser['id'] ?? null)) {
            $actualUser = $this->getUserInfo($actualUserId);
            if ($actualUser) {
                $_SESSION['user'] = array_merge($currentUser, $actualUser);
                $currentUser = $_SESSION['user'];
                $isAdmin = $currentUser['is_admin'] ?? false;
                $isCoach = ($currentUser['role'] ?? '') === 'Coach';
            }
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId; // Utiliser l'ID du token
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        
        // Récupérer les informations de l'utilisateur sélectionné
        $selectedUser = $this->getUserInfo($selectedUserId);
        
        // Récupérer les entraînements de l'utilisateur sélectionné
        $trainings = $this->getTrainings($selectedUserId);
        
        // Grouper les entraînements par catégorie d'exercice
        $groupedTrainings = $this->groupTrainingsByCategory($trainings, $selectedUserId, $selectedUser);
        
        // TODO: Filtrer les exercices masqués pour les archers
        // Le filtrage doit se faire au niveau de la récupération des exercices
        // et non au niveau du groupement des sessions
        
        // Récupérer la liste des utilisateurs pour les modals (seulement pour les coaches/admins)
        $users = [];
        if ($isAdmin || $isCoach) {
            try {
                $usersResponse = $this->apiService->getUsers();
                if ($usersResponse['success'] && !empty($usersResponse['data'])) {
                    // Extraire les utilisateurs de la structure imbriquée
                    $usersData = $usersResponse['data'];
                    if (isset($usersData['users']) && is_array($usersData['users'])) {
                        $users = $usersData['users'];
                    } else {
                        $users = $usersData;
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            }
        }
        
        $stats = $this->calculateStatsFromTrainings($trainings);
        
        // Passer les données à la vue
        $trainingsByCategory = $groupedTrainings;
        
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
        
        // Récupérer l'ID utilisateur depuis le token pour éviter l'incohérence
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Récupérer les détails de l'entraînement
        $training = $this->getTrainingById($id);
        
        if (!$training) {
            header('Location: /trainings?error=' . urlencode('Entraînement non trouvé'));
            exit;
        }
        
        // Vérifier les permissions en utilisant l'ID du token
        if (!$isAdmin && !$isCoach && $training['user_id'] != $actualUserId) {
            header('Location: /trainings?error=' . urlencode('Accès refusé'));
            exit;
        }
        
        // Récupérer toutes les sessions de l'exercice pour la navigation entre sessions
        $exerciseId = $training['exercise_sheet_id'];
        $sessions = $this->getSessionsForExercise($exerciseId);
        
        // S'assurer que $sessions est un tableau
        if (!is_array($sessions)) {
            $sessions = [];
        }
        
        // Trouver la position de la session actuelle
        $currentSessionIndex = -1;
        $previousSession = null;
        $nextSession = null;
        
        if (!empty($sessions)) {
            foreach ($sessions as $index => $session) {
                if ($session['id'] == $id) {
                    $currentSessionIndex = $index;
                    break;
                }
            }
            
            // Déterminer les sessions précédente et suivante
            if ($currentSessionIndex > 0 && isset($sessions[$currentSessionIndex - 1])) {
                $previousSession = $sessions[$currentSessionIndex - 1];
            }
            if ($currentSessionIndex >= 0 && $currentSessionIndex < count($sessions) - 1 && isset($sessions[$currentSessionIndex + 1])) {
                $nextSession = $sessions[$currentSessionIndex + 1];
            }
        }
        
        // Récupérer tous les exercices de la même catégorie pour la navigation entre exercices
        $category = $training['category'];
        $categoryExercises = $this->getExercisesByCategory($category);
        
        // Pour chaque exercice, récupérer la première session pour la navigation
        $categoryExercisesWithSessions = [];
        foreach ($categoryExercises as $exercise) {
            $exerciseSessions = $this->getSessionsForExercise($exercise['id']);
            if (!empty($exerciseSessions)) {
                // Prendre la première session de l'exercice
                $exercise['session_id'] = $exerciseSessions[0]['id'];
                $categoryExercisesWithSessions[] = $exercise;
            }
        }
        
        // Trouver la position de l'exercice actuel dans la catégorie
        $currentExerciseIndex = -1;
        $previousExercise = null;
        $nextExercise = null;
        
        if (!empty($categoryExercisesWithSessions)) {
            foreach ($categoryExercisesWithSessions as $index => $exerciseData) {
                if (isset($exerciseData['id']) && $exerciseData['id'] == $exerciseId) {
                    $currentExerciseIndex = $index;
                    break;
                }
            }
            
            // Déterminer les exercices précédent et suivant
            if ($currentExerciseIndex > 0 && isset($categoryExercisesWithSessions[$currentExerciseIndex - 1])) {
                $previousExercise = $categoryExercisesWithSessions[$currentExerciseIndex - 1];
            }
            if ($currentExerciseIndex >= 0 && $currentExerciseIndex < count($categoryExercisesWithSessions) - 1 && isset($categoryExercisesWithSessions[$currentExerciseIndex + 1])) {
                $nextExercise = $categoryExercisesWithSessions[$currentExerciseIndex + 1];
            }
        }
        
        // Récupérer la liste des utilisateurs pour la modal (seulement pour les coaches/admins)
        $users = [];
        if ($isAdmin || $isCoach) {
            try {
                $usersResponse = $this->apiService->getUsers();
                if ($usersResponse['success'] && !empty($usersResponse['data'])) {
                    // Extraire les utilisateurs de la structure imbriquée
                    $usersData = $usersResponse['data'];
                    if (isset($usersData['users']) && is_array($usersData['users'])) {
                        $users = $usersData['users'];
                    } else {
                        $users = $usersData;
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            }
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
            
            // Si erreur 403, l'utilisateur n'a pas les permissions
            if (isset($response['status_code']) && $response['status_code'] === 403) {
                return [];
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
                // Extraire les données de la structure imbriquée
                $training = $response['data'];
                
                // Si les données sont encore dans une structure imbriquée
                if (isset($training['success']) && isset($training['data'])) {
                    $training = $training['data'];
                }
                
                return $training;
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
     * Récupère les sessions pour un exercice donné
     * @param int $exerciseId ID de l'exercice
     * @return array Liste des sessions
     */
    private function getSessionsForExercise($exerciseId) {
        try {
            $endpoint = "/training?action=sessions&exercise_id=" . $exerciseId;
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            
            if ($response['success'] && !empty($response['data'])) {
                // Vérifier si c'est le message de test
                if (isset($response['data']['message']) && $response['data']['message'] === 'Training route working') {
                    return [];
                }
                
                // Si les données sont dans une structure imbriquée
                if (isset($response['data']['success']) && isset($response['data']['data'])) {
                    return $response['data']['data'];
                }
                
                // Vérifier si c'est un array de sessions
                if (is_array($response['data']) && !isset($response['data']['message'])) {
                    return $response['data'];
                }
                
                // Si c'est un objet avec des sessions
                if (isset($response['data']['sessions']) && is_array($response['data']['sessions'])) {
                    return $response['data']['sessions'];
                }
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des sessions pour l\'exercice ' . $exerciseId . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Groupe les entraînements par catégorie d'exercice
     * @param array $trainings Liste des entraînements
     * @param int $selectedUserId ID de l'utilisateur sélectionné
     * @param array $selectedUser Informations de l'utilisateur sélectionné
     * @return array Entraînements groupés par catégorie
     */
    private function groupTrainingsByCategory($trainings, $selectedUserId, $selectedUser) {
        $grouped = [];
        
        // Si $trainings est une réponse API, extraire les données
        if (isset($trainings['data']) && is_array($trainings['data'])) {
            $trainings = $trainings['data'];
        }
        
        if (!is_array($trainings)) {
            return [];
        }
        
        foreach ($trainings as $training) {
            if (!is_array($training)) {
                continue;
            }
            
            $category = $training['category'] ?? 'Sans catégorie';
            $exerciseTitle = $training['exercise_sheet_title'] ?? 'Sans exercice';
            $exerciseId = $training['exercise_sheet_id'] ?? 'no_exercise';
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'category_name' => $category,
                    'exercises' => [],
                    'total_sessions' => 0,
                    'total_arrows' => 0,
                    'total_time_minutes' => 0
                ];
            }
            
            if (!isset($grouped[$category]['exercises'][$exerciseId])) {
                $grouped[$category]['exercises'][$exerciseId] = [
                    'exercise_title' => $exerciseTitle,
                    'exercise_id' => $exerciseId,
                    'sessions' => [],
                    'stats' => [
                        'total_sessions' => 0,
                        'total_arrows' => 0,
                        'total_time_minutes' => 0,
                        'first_session' => null,
                        'last_session' => null
                    ]
                ];
            }
            
            // Utiliser les données de progrès comme statistiques
            $totalSessions = $training['total_sessions'] ?? 0;
            $totalArrows = $training['total_arrows'] ?? 0;
            $totalTime = $training['total_duration_minutes'] ?? 0;
            $lastSessionDate = $training['last_session_date'] ?? $training['start_date'] ?? null;
            
            // Mettre à jour les statistiques de l'exercice
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_sessions'] = $totalSessions;
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_arrows'] = $totalArrows;
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_time_minutes'] = $totalTime;
            $grouped[$category]['exercises'][$exerciseId]['stats']['last_session'] = $lastSessionDate;
            
            // Mettre à jour les totaux de la catégorie
            $grouped[$category]['total_sessions'] += $totalSessions;
            $grouped[$category]['total_arrows'] += $totalArrows;
            $grouped[$category]['total_time_minutes'] += $totalTime;
            
            // Récupérer les vraies sessions pour cet exercice
            $realSessions = $this->getSessionsForExercise($exerciseId);
            
            // Ajouter les vraies sessions si elles existent
            if (!empty($realSessions)) {
                foreach ($realSessions as $session) {
                    $grouped[$category]['exercises'][$exerciseId]['sessions'][] = [
                        'id' => $session['id'],
                        'start_date' => $session['start_date'] ?? $session['created_at'] ?? null,
                        'created_at' => $session['created_at'] ?? null,
                        'end_date' => $session['end_date'] ?? null,
                        'arrows_shot' => $session['total_arrows'] ?? 0, // Utiliser total_arrows au lieu de arrows_shot
                        'total_arrows' => $session['total_arrows'] ?? 0,
                        'duration_minutes' => $session['duration_minutes'] ?? 0,
                        'total_sessions' => 1, // Chaque session compte pour 1
                        'score' => $session['score'] ?? 0,
                        'is_aggregated' => false,
                        'user_name' => $selectedUser['name'] ?? 'Utilisateur',
                        'user_id' => $selectedUserId
                    ];
                }
            } else {
                // Si pas de vraies sessions, créer une session représentative avec les données de progrès
                if ($totalSessions > 0) {
                    $grouped[$category]['exercises'][$exerciseId]['sessions'][] = [
                        'id' => null, // Pas d'ID pour éviter les liens cliquables
                        'start_date' => $training['start_date'] ?? null,
                        'created_at' => $training['start_date'] ?? null,
                        'end_date' => $training['last_session_date'] ?? null,
                        'arrows_shot' => $totalArrows, // Utiliser le total des flèches
                        'total_arrows' => $totalArrows,
                        'duration_minutes' => $totalTime,
                        'total_sessions' => $totalSessions,
                        'score' => 0,
                        'is_aggregated' => true,
                        'is_progress_data' => true, // Marquer comme données de progrès
                        'user_name' => $selectedUser['name'] ?? 'Utilisateur',
                        'user_id' => $selectedUserId
                    ];
                }
            }
        }
        
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
        
        foreach ($trainings as $training) {
            if (!is_array($training)) {
                continue;
            }
            
            // Utiliser les statistiques cumulées de chaque exercice
            $sessions = $training['total_sessions'] ?? 0;
            $arrows = $training['total_arrows'] ?? 0;
            $ends = $training['total_ends'] ?? 0;
            $score = $training['total_score'] ?? $training['score'] ?? 0;
            $date = $training['last_session_date'] ?? $training['start_date'] ?? '';
            $timeMinutes = $training['total_duration_minutes'] ?? 0;
            
            // Ajouter les statistiques de cet exercice
            $stats['total_trainings'] += $sessions;  // Nombre total de sessions
            $stats['total_arrows'] += $arrows;       // Nombre total de flèches
            $stats['total_ends'] += $ends;           // Nombre total de volées
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
        
        if ($stats['total_trainings'] > 0) {
            $stats['average_score'] = $totalScore / $stats['total_trainings'];
        }
        
        return $stats;
    }

    /**
     * Récupère toutes les sessions d'entraînement pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Réponse de l'API
     */
    public function getAllTrainingSessions($userId) {
        // Essayer d'abord l'endpoint des sessions
        $endpoint = "/training/sessions?user_id=" . $userId;
        $response = $this->apiService->makeRequest($endpoint, 'GET');
        
        // Si ça ne marche pas, essayer l'endpoint des sessions d'entraînement
        if (!$response['success'] || empty($response['data'])) {
            $endpoint = "/training?action=sessions&user_id=" . $userId;
            $response = $this->apiService->makeRequest($endpoint, 'GET');
        }
        
        return $response;
    }

    private function getExercisesByCategory($category) {
        try {
            // Récupérer tous les exercices
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                // Extraire les données de la structure imbriquée
                $exercises = $response['data'];
                
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                $categoryExercises = [];
                $currentUser = $_SESSION['user'];
                $isAdmin = $currentUser['is_admin'] ?? false;
                $isCoach = ($currentUser['role'] ?? '') === 'Coach';
                
                foreach ($exercises as $exercise) {
                    if (isset($exercise['category']) && $exercise['category'] === $category) {
                        if (!($isAdmin || $isCoach) && ($exercise['progression'] ?? 'non_actif') === 'masqué') {
                            continue;
                        }
                        $categoryExercises[] = $exercise;
                    }
                }
                
                return $categoryExercises;
            }
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des exercices par catégorie: ' . $e->getMessage());
            return [];
        }
    }

    private function getVisibleExercisesByCategory($category, $userId) {
        try {
            // Récupérer tous les exercices
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                // Extraire les données de la structure imbriquée
                $exercises = $response['data'];
                
                // Si les données sont encore dans une structure imbriquée
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                $visibleExercises = [];
                $currentUser = $_SESSION['user'];
                $isAdmin = $currentUser['is_admin'] ?? false;
                $isCoach = ($currentUser['role'] ?? '') === 'Coach';
                
                // Filtrer par catégorie et par statut (masquer les exercices "masqués" pour les archers)
                foreach ($exercises as $exercise) {
                    if (isset($exercise['category']) && $exercise['category'] === $category) {
                        // Si l'utilisateur est archer et l'exercice est masqué, ne pas l'inclure
                        if (!($isAdmin || $isCoach) && ($exercise['progression'] ?? 'non_actif') === 'masqué') {
                            continue;
                        }
                        $visibleExercises[] = $exercise;
                    }
                }
                
                return $visibleExercises;
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des exercices visibles par catégorie: ' . $e->getMessage());
            return [];
        }
    }

    public function updateProgression() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Vérifier les permissions
        if (!$isAdmin && !$isCoach) {
            header('Location: /trainings?error=' . urlencode('Permissions insuffisantes'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /trainings?error=' . urlencode('Méthode non autorisée'));
            exit;
        }
        
        $exerciseSheetId = $_POST['exercise_sheet_id'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        $progression = $_POST['progression'] ?? '';
        $sessionId = $_POST['session_id'] ?? '';
        
        if (empty($exerciseSheetId) || empty($userId) || empty($progression)) {
            header('Location: /trainings?error=' . urlencode('Paramètres manquants'));
            exit;
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->makeRequest('training/progress', 'POST', [
                'exercise_sheet_id' => (int)$exerciseSheetId,
                'user_id' => (int)$userId,
                'progression' => $progression
            ]);
            
            if ($response['success']) {
                // Rediriger vers la page show actuelle si on a l'ID de session, sinon vers l'index
                if (!empty($sessionId)) {
                    header('Location: /trainings/' . $sessionId . '?success=' . urlencode('Statut mis à jour avec succès'));
                } else {
                    header('Location: /trainings?success=' . urlencode('Statut mis à jour avec succès'));
                }
            } else {
                // Rediriger vers la page show actuelle si on a l'ID de session, sinon vers l'index
                if (!empty($sessionId)) {
                    header('Location: /trainings/' . $sessionId . '?error=' . urlencode($response['message'] ?? 'Erreur lors de la mise à jour'));
                } else {
                    header('Location: /trainings?error=' . urlencode($response['message'] ?? 'Erreur lors de la mise à jour'));
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour de la progression: ' . $e->getMessage());
            // Rediriger vers la page show actuelle si on a l'ID de session, sinon vers l'index
            if (!empty($sessionId)) {
                header('Location: /trainings/' . $sessionId . '?error=' . urlencode('Erreur serveur'));
            } else {
                header('Location: /trainings?error=' . urlencode('Erreur serveur'));
            }
        }
    }
    
    /**
     * Met à jour les notes d'une session d'entraînement
     */
    public function updateNotes() {
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        
        $data = json_decode($input, true);
        
        $sessionId = $data['session_id'] ?? '';
        $notes = $data['notes'] ?? '';
        
        if (empty($sessionId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'session_id requis']);
            exit;
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->updateTrainingNotes($sessionId, $notes);
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour des notes: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * Sauvegarde une session d'entraînement
     */
    public function saveSession() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $exerciseSheetId = $data['exercise_sheet_id'] ?? '';
        $userId = $data['user_id'] ?? null;
        $sessionData = $data['session_data'] ?? [];
        
        if (empty($exerciseSheetId) || empty($sessionData)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }
        
        try {
            // Appeler l'API backend pour sauvegarder la session
            $response = $this->apiService->saveTrainingSession($exerciseSheetId, $sessionData, $userId);
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('Erreur lors de la sauvegarde de la session: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
        }
    }

    /**
     * Récupère les informations d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Informations de l'utilisateur
     */
    private function getUserInfo($userId) {
        try {
            $actualUserId = $this->getUserIdFromToken();
            
            // Si l'utilisateur consulte ses propres informations, récupérer depuis l'API
            if ((string)$userId === (string)$actualUserId) {
                // Récupérer les vraies données depuis l'API
                $response = $this->apiService->getUserById($userId);
                if ($response['success'] && !empty($response['data'])) {
                    return $response['data'];
                }
                
                // Fallback : utiliser les données de session si l'API échoue
                $currentUser = $_SESSION['user'];
                return [
                    'id' => $actualUserId,
                    'name' => $currentUser['name'] ?? 'Utilisateur ' . $userId,
                    'profile_image' => $currentUser['profile_image'] ?? null,
                    'firstName' => $currentUser['firstName'] ?? 'Utilisateur',
                    'lastName' => $currentUser['lastName'] ?? $userId
                ];
            }
            
            // Pour les autres utilisateurs, essayer l'API
            $response = $this->apiService->getUserById($userId);
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            // Fallback : retourner les informations de base
            return [
                'id' => $userId,
                'name' => 'Utilisateur ' . $userId,
                'profile_image' => null,
                'firstName' => 'Utilisateur',
                'lastName' => $userId
            ];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des informations utilisateur: ' . $e->getMessage());
            return [
                'id' => $userId,
                'name' => 'Utilisateur ' . $userId,
                'profile_image' => null,
                'firstName' => 'Utilisateur',
                'lastName' => $userId
            ];
        }
    }

    /**
     * Récupère l'ID utilisateur depuis le token JWT
     * @return int|null ID de l'utilisateur
     */
    private function getUserIdFromToken() {
        if (!isset($_SESSION['token'])) {
            return null;
        }
        
        try {
            $token = $_SESSION['token'];
            // Décoder le token JWT pour récupérer l'ID utilisateur
            $decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $token)[1])), true);
            return $decoded['user_id'] ?? null;
        } catch (Exception $e) {
            error_log('Erreur lors du décodage du token: ' . $e->getMessage());
            return null;
        }
    }

}

