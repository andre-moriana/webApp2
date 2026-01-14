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
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
        
        // Récupérer l'ID utilisateur depuis le token pour éviter l'incohérence
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
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
            }
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId; // Utiliser l'ID du token
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach || $isDirigeant) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
            // Debug: Vérifier que l'utilisateur sélectionné est bien récupéré depuis GET
            error_log("TrainingController::index() - Utilisateur sélectionné depuis GET: " . $selectedUserId);
        } else {
            // Debug: Utilisation de l'utilisateur actuel
            error_log("TrainingController::index() - Utilisation de l'utilisateur actuel (token): " . $selectedUserId);
        }
        // Récupérer les informations de l'utilisateur sélectionné
        $selectedUser = $this->getUserInfo($selectedUserId);
        
        // Récupérer TOUS les exercices disponibles (y compris les masqués pour les admins/coachs)
        $allExercises = $this->getAllExercisesForUser($isAdmin, $isCoach, $isDirigeant);
        
        // Récupérer les entraînements de l'utilisateur sélectionné
        $trainings = $this->getTrainings($selectedUserId);
        
        
        // Récupérer les vraies sessions d'entraînement depuis l'API
        $realSessions = $this->fetchAllTrainingSessions($selectedUserId);
        
        // Alternative: récupérer les sessions depuis les données de progression
        $sessionsFromProgress = [];
        foreach ($trainings as $training) {
            if (is_array($training) && isset($training['exercise_sheet_id'])) {
                $exerciseId = $training['exercise_sheet_id'];
                if (!isset($sessionsFromProgress[$exerciseId])) {
                    $sessionsFromProgress[$exerciseId] = [];
                }
                // Créer une session factice basée sur les données de progression
                $sessionsFromProgress[$exerciseId][] = [
                    'id' => $training['id'] ?? 'unknown',
                    'exercise_sheet_id' => $exerciseId,
                    'user_id' => $selectedUserId,
                    'start_date' => $training['start_date'] ?? null,
                    'end_date' => $training['last_session_date'] ?? null,
                    'total_arrows' => 0, // Sera calculé depuis l'API
                    'duration_minutes' => 0, // Sera calculé depuis l'API
                    'created_at' => $training['created_at'] ?? null,
                    'updated_at' => $training['updated_at'] ?? null
                ];
            }
        }
        
        // Grouper les exercices par catégorie pour l'archer sélectionné (et non l'utilisateur connecté)
        $groupedTrainings = $this->groupAllExercisesByCategory($allExercises, $trainings, $selectedUserId, $selectedUser, $realSessions);
        
        // Récupérer la liste des utilisateurs pour les modals (seulement pour les coaches/admins)
        $users = [];
        if ($isAdmin || $isCoach || $isDirigeant) {
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
                    
                    // Trier les utilisateurs par ordre alphabétique (nom puis prénom)
                    usort($users, function($a, $b) {
                        // Récupérer le nom complet pour la comparaison
                        $nameA = ($a['name'] ?? '') . ' ' . ($a['firstName'] ?? '');
                        $nameB = ($b['name'] ?? '') . ' ' . ($b['firstName'] ?? '');
                        
                        // Nettoyer les espaces et convertir en minuscules pour la comparaison
                        $nameA = trim(strtolower($nameA));
                        $nameB = trim(strtolower($nameB));
                        
                        return strcmp($nameA, $nameB);
                    });
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            }
        }
        
        // Calculer les statistiques à partir des données groupées qui contiennent les stats calculées dynamiquement
        // IMPORTANT: Les statistiques sont calculées avec les données de l'utilisateur sélectionné ($selectedUserId)
        $stats = $this->calculateStatsFromGroupedTrainings($groupedTrainings);
        
        // Debug: Vérifier que les stats sont bien calculées pour le bon utilisateur
        error_log("Stats calculées pour utilisateur ID: " . $selectedUserId . " - Total entraînements: " . ($stats['total_trainings'] ?? 0) . " - Total flèches: " . ($stats['total_arrows'] ?? 0));
        
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
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
        
        // Récupérer l'ID utilisateur depuis le token pour éviter l'incohérence
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId; // Utiliser l'ID du token par défaut
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach || $isDirigeant) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        
        // Récupérer les détails de l'entraînement
        $training = $this->getSessionById($id);
        
        if (!$training) {
            header('Location: /trainings?error=' . urlencode('Entraînement non trouvé'));
            exit;
        }
        
        // Vérifier que l'entraînement appartient à l'utilisateur sélectionné
        if ($training['user_id'] != $selectedUserId) {
            header('Location: /trainings?error=' . urlencode('Entraînement non trouvé pour cet utilisateur'));
            exit;
        }
        
        // Vérifier les permissions en utilisant l'ID du token
        if (!$isAdmin && !$isCoach && !$isDirigeant && $training['user_id'] != $actualUserId) {
            header('Location: /trainings?error=' . urlencode('Accès refusé'));
            exit;
        }
        
        // Récupérer toutes les sessions de l'exercice pour la navigation entre sessions
        $exerciseId = $training['exercise_sheet_id'];
        
        // Récupérer toutes les sessions de l'utilisateur pour créer l'index
        $allSessions = $this->getTrainings($selectedUserId);
        
        // Créer un index des sessions par exercice
        $sessionsByExercise = [];
        if (is_array($allSessions)) {
            foreach ($allSessions as $session) {
                if (is_array($session)) {
                    $sessionExerciseId = $session['exercise_sheet_id'] ?? 'no_exercise';
                    if (!isset($sessionsByExercise[$sessionExerciseId])) {
                        $sessionsByExercise[$sessionExerciseId] = [];
                    }
                    $sessionsByExercise[$sessionExerciseId][] = $session;
                }
            }
        }
        // Récupérer les sessions pour cet exercice depuis les données déjà récupérées
        $sessions = $sessionsByExercise[$exerciseId] ?? [];
        
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
            $exerciseSessions = $this->getSessionsForExercise($exercise['id'], $training['user_id']);
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
        if ($isAdmin || $isCoach || $isDirigeant) {
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
                    
                    // Trier les utilisateurs par ordre alphabétique (nom puis prénom)
                    usort($users, function($a, $b) {
                        // Récupérer le nom complet pour la comparaison
                        $nameA = ($a['name'] ?? '') . ' ' . ($a['firstName'] ?? '');
                        $nameB = ($b['name'] ?? '') . ' ' . ($b['firstName'] ?? '');
                        
                        // Nettoyer les espaces et convertir en minuscules pour la comparaison
                        $nameA = trim(strtolower($nameA));
                        $nameB = trim(strtolower($nameB));
                        
                        return strcmp($nameA, $nameB);
                    });
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            }
        }
        
        $title = 'Détails de l\'entraînement - Portail Archers de Gémenos';
        
        // Passer les variables nécessaires à la vue
        $currentUser = $_SESSION['user'];
        //$selectedUserId = $actualUserId; // Ajouter $selectedUserId
        
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
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
        
        // Vérifier les permissions
        if (!$isAdmin && !$isCoach && !$isDirigeant && $id != $currentUser['id']) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = (int)$id;
        
        // Récupérer les informations de l'utilisateur sélectionné
        $selectedUser = $this->getUserInfo($selectedUserId);
        
        // Récupérer TOUS les exercices disponibles
        $allExercises = $this->getAllExercisesForUser($isAdmin, $isCoach, $isDirigeant);
        
        // Récupérer les entraînements de l'utilisateur sélectionné
        $trainings = $this->getTrainings($selectedUserId);
        
        // Récupérer les vraies sessions d'entraînement depuis l'API
        $realSessions = $this->fetchAllTrainingSessions($selectedUserId);
        
        // Grouper les exercices par catégorie pour l'utilisateur sélectionné
        $groupedTrainings = $this->groupAllExercisesByCategory($allExercises, $trainings, $selectedUserId, $selectedUser, $realSessions);
        
        // Calculer les statistiques à partir des données groupées (même méthode que index())
        $stats = $this->calculateStatsFromGroupedTrainings($groupedTrainings);
        
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
    private function getSessionsForExercise($exerciseId, $userId = null) {
        try {
            $endpoint = "/training?action=sessions&exercise_id=" . $exerciseId;
            
            // Ajouter l'user_id si fourni
            if ($userId !== null) {
                $endpoint .= "&user_id=" . $userId;
            }
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            
            
            if ($response['success'] && !empty($response['data'])) {
                // Vérifier si c'est le message de test
                if (isset($response['data']['message']) && $response['data']['message'] === 'Training route working') {
                    return [];
                }
                
                $sessions = [];
                
                // Si les données sont dans une structure imbriquée
                if (isset($response['data']['success']) && isset($response['data']['data'])) {
                    $sessions = $response['data']['data'];
                }
                // Vérifier si c'est un array de sessions
                else if (is_array($response['data']) && !isset($response['data']['message'])) {
                    $sessions = $response['data'];
                }
                // Si c'est un objet avec des sessions
                else if (isset($response['data']['sessions']) && is_array($response['data']['sessions'])) {
                    $sessions = $response['data']['sessions'];
                }
                
                // FILTRER les sessions par utilisateur côté frontend
                if ($userId !== null && !empty($sessions)) {
                    $filteredSessions = [];
                    foreach ($sessions as $session) {
                        // Vérifier si la session appartient à l'utilisateur sélectionné
                        if (isset($session['user_id']) && (int)$session['user_id'] === (int)$userId) {
                            $filteredSessions[] = $session;
                        }
                    }
                    return $filteredSessions;
                }
                
                return $sessions;
            }
            
            return [];
        } catch (Exception $e) {
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
                    'description' => $exercise['description'] ?? '',
                    'creator_name' => $exercise['creator_name'] ?? 'Inconnu',
                    'created_at' => $exercise['created_at'] ?? '',
                    'progression' => $exercise['progression'] ?? 'non_actif',
                    'attachment_filename' => $exercise['attachment_filename'] ?? '',
                    'attachment_original_name' => $exercise['attachment_original_name'] ?? '',
                    'attachment_mime_type' => $exercise['attachment_mime_type'] ?? '',
                    'attachment_size' => $exercise['attachment_size'] ?? 0,
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
            
            // Récupérer les sessions pour cet exercice depuis les données déjà récupérées
            $realSessions = $sessionsByExercise[$exerciseId] ?? [];
            
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
            
            // Les statistiques sont maintenant calculées dynamiquement depuis training_sessions
            // Utiliser les statistiques calculées par groupAllExercisesByCategory
            $sessions = $training['stats']['total_sessions'] ?? 0;
            $arrows = $training['stats']['total_arrows'] ?? 0;
            $ends = $training['stats']['total_ends'] ?? 0;
            $score = $training['stats']['total_score'] ?? $training['stats']['score'] ?? 0;
            $date = $training['stats']['last_session'] ?? $training['start_date'] ?? '';
            $timeMinutes = $training['stats']['total_time_minutes'] ?? 0;
            
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
     * Calcule les statistiques à partir des données groupées qui contiennent les stats calculées dynamiquement
     * @param array $groupedTrainings Données groupées par catégorie
     * @return array Statistiques calculées
     */
    private function calculateStatsFromGroupedTrainings($groupedTrainings) {
        $stats = [
            'total_trainings' => 0,
            'total_arrows' => 0,
            'total_ends' => 0,
            'total_score' => 0,
            'best_training_score' => 0,
            'average_score' => 0,
            'last_training_date' => null,
            'total_time_minutes' => 0
        ];
        
        if (empty($groupedTrainings)) {
            return $stats;
        }
        
        $totalScore = 0;
        $bestScore = 0;
        $lastDate = null;
        $totalTimeMinutes = 0;
        
        // Parcourir toutes les catégories
        foreach ($groupedTrainings as $categoryData) {
            if (!isset($categoryData['exercises']) || !is_array($categoryData['exercises'])) {
                continue;
            }
            
            // Parcourir tous les exercices de cette catégorie
            foreach ($categoryData['exercises'] as $exerciseData) {
                if (!isset($exerciseData['stats']) || !is_array($exerciseData['stats'])) {
                    continue;
                }
                
                // Utiliser les statistiques calculées dynamiquement
                $sessions = $exerciseData['stats']['total_sessions'] ?? 0;
                $arrows = $exerciseData['stats']['total_arrows'] ?? 0;
                $ends = $exerciseData['stats']['total_ends'] ?? 0;
                $score = $exerciseData['stats']['total_score'] ?? $exerciseData['stats']['score'] ?? 0;
                $date = $exerciseData['stats']['last_session'] ?? null;
                $timeMinutes = $exerciseData['stats']['total_time_minutes'] ?? 0;
                
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
            // Récupérer l'utilisateur connecté depuis la session
            $loggedInUser = $_SESSION['user'];
            $isAdmin = $loggedInUser['is_admin'] ?? false;
            $isCoach = ($loggedInUser['role'] ?? '') === 'Coach';
            
            // Récupérer tous les exercices (pas de filtrage car admin/coach)
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                // Extraire les données de la structure imbriquée
                $exercises = $response['data'];
                
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                $categoryExercises = [];
                
                foreach ($exercises as $exercise) {
                    
                    if (isset($exercise['category']) && $exercise['category'] === $category) {
                        
                        // Pour les admins et coachs, afficher tous les exercices
                        // Pour les autres, ne pas afficher les exercices masqués
                        if ($isAdmin || $isCoach || $isDirigeant || ($exercise['progression'] ?? 'non_actif') !== 'masqué') {
                            $categoryExercises[] = $exercise;
                        }
                    }
                }
                
                return $categoryExercises;
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function getVisibleExercisesByCategory($category, $userId) {
        try {
            // Récupérer l'utilisateur connecté depuis la session
            $loggedInUser = $_SESSION['user'];
            $isAdmin = $loggedInUser['is_admin'] ?? false;
            $isCoach = ($loggedInUser['role'] ?? '') === 'Coach';
            // Récupérer tous les exercices (pas de filtrage car admin/coach)
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                // Extraire les données de la structure imbriquée
                $exercises = $response['data'];
                
                // Si les données sont encore dans une structure imbriquée
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                $visibleExercises = [];
                
                // Filtrer par catégorie et par statut
                foreach ($exercises as $exercise) {
                    
                    if (isset($exercise['category']) && $exercise['category'] === $category) {
                        // Pour les admins et coachs, afficher tous les exercices
                        // Pour les autres, ne pas afficher les exercices masqués
                        if ($isAdmin || $isCoach || $isDirigeant || ($exercise['progression'] ?? 'non_actif') !== 'masqué') {
                            $visibleExercises[] = $exercise;
                        }
                    }
                }
                
                return $visibleExercises;
            }
            
            return [];
        } catch (Exception $e) {
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
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
        
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
            return null;
        }
    }

    /**
     * Met à jour le statut d'un exercice pour un utilisateur
     */
    public function updateStatus() {
        try {
            // Vérifier que la requête est en POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                return;
            }

            // Récupérer les données
            $exerciseId = $_POST['exercise_id'] ?? null;
            $userId = $_POST['user_id'] ?? null;
            $status = $_POST['status'] ?? null;
            // Validation
            if (!$exerciseId || !$userId || !$status) {
                echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
                return;
            }

            // Vérifier les permissions
            $actualUserId = $this->getUserIdFromToken();
            $currentUser = $_SESSION['user'];
            $isAdmin = $currentUser['is_admin'] ?? false;
            $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';

            if (!$isAdmin && !$isCoach && !$isDirigeant && (int)$userId !== (int)$actualUserId) {
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                return;
            }

            // Appeler l'API pour mettre à jour le statut
            $endpoint = "/training/progress/update";
            $data = [
                'exercise_sheet_id' => (int)$exerciseId,
                'user_id' => (int)$userId,
                'progression' => $status
            ];

            $response = $this->apiService->makeRequest($endpoint, 'POST', $data);

            if ($response['success']) {
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => $response['message'] ?? 'Erreur lors de la mise à jour']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur']);
        }
    }

    /**
     * Récupère tous les exercices disponibles pour l'utilisateur
    * @param bool $isAdmin Si l'utilisateur est admin
    * @param bool $isCoach Si l'utilisateur est coach
    * @param bool $isDirigeant Si l'utilisateur est dirigeant
     * @return array Liste des exercices
     */
    private function getAllExercisesForUser($isAdmin, $isCoach, $isDirigeant = false) {
        try {
            
            // Récupérer l'ID de l'utilisateur sélectionné
            $selectedUserId = $_GET['user_id'] ?? null;
            if (!$selectedUserId) {
                $selectedUserId = $this->getUserIdFromToken();
            }
            
            // Récupérer tous les exercices
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                // Extraire les données de la structure imbriquée
                $exercises = $response['data'];
                
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                // Récupérer les statuts spécifiques à l'utilisateur
                $userProgressResponse = $this->apiService->getTrainings($selectedUserId);
                $userProgress = [];
                
                if ($userProgressResponse['success'] && !empty($userProgressResponse['data'])) {
                    $progressData = $userProgressResponse['data'];
                    if (isset($progressData['success']) && isset($progressData['data'])) {
                        $progressData = $progressData['data'];
                    }
                    
                    // Créer un tableau indexé par exercise_sheet_id
                    foreach ($progressData as $progress) {
                        $userProgress[$progress['exercise_sheet_id']] = $progress['progression'];
                    }
                }
                
                
                // Filtrer les exercices selon les permissions et appliquer les statuts utilisateur
                $filteredExercises = [];
                foreach ($exercises as $exercise) {
                    
                    // Utiliser le statut spécifique à l'utilisateur s'il existe, sinon le statut global
                    $exerciseProgression = $userProgress[$exercise['id']] ?? ($exercise['progression'] ?? 'non_actif');
                    $exercise['progression'] = $exerciseProgression;
                    
                    
                    // Pour les admins et coachs, afficher tous les exercices
                    // Pour les autres, ne pas afficher les exercices masqués
                    if ($isAdmin || $isCoach || $isDirigeant || $exerciseProgression !== 'masqué') {
                        $filteredExercises[] = $exercise;
                    }
                }
                
                return $filteredExercises;
            }
            
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Groupe tous les exercices par catégorie, en incluant ceux sans sessions
     * @param array $allExercises Tous les exercices disponibles
     * @param array $trainings Sessions d'entraînement existantes
     * @param int $selectedUserId ID de l'utilisateur sélectionné
     * @param array $selectedUser Informations de l'utilisateur sélectionné
     * @param array $realSessions Vraies sessions d'entraînement
     * @return array Exercices groupés par catégorie
     */
    private function groupAllExercisesByCategory($allExercises, $trainings, $selectedUserId, $selectedUser, $realSessions = []) {
        $grouped = [];
        
        // Si $trainings est une réponse API, extraire les données
        if (isset($trainings['data']) && is_array($trainings['data'])) {
            $trainings = $trainings['data'];
        }
        
        if (!is_array($trainings)) {
            $trainings = [];
        }
        
        // Créer un index des sessions par exercice pour un accès rapide
        $sessionsByExercise = [];
        
        // Utiliser les vraies sessions d'entraînement si disponibles
        // IMPORTANT: Filtrer les sessions pour ne garder que celles de l'utilisateur sélectionné
        if (!empty($realSessions)) {
            foreach ($realSessions as $session) {
                if (!is_array($session)) {
                    continue;
                }
                
                // Filtrer par utilisateur sélectionné pour garantir que seules les sessions de cet utilisateur sont utilisées
                $sessionUserId = $session['user_id'] ?? null;
                if ($sessionUserId != $selectedUserId) {
                    continue; // Ignorer les sessions qui ne correspondent pas à l'utilisateur sélectionné
                }
                
                $exerciseId = $session['exercise_sheet_id'] ?? $session['exercise_id'] ?? 'no_exercise';
                if (!isset($sessionsByExercise[$exerciseId])) {
                    $sessionsByExercise[$exerciseId] = [];
                }
                $sessionsByExercise[$exerciseId][] = $session;
            }
        } else {
            // Fallback: utiliser les données de progression
            // IMPORTANT: Filtrer par utilisateur sélectionné même dans le fallback
            foreach ($trainings as $training) {
                if (!is_array($training)) {
                    continue;
                }
                
                // Filtrer par utilisateur sélectionné pour garantir que seules les données de cet utilisateur sont utilisées
                $trainingUserId = $training['user_id'] ?? null;
                if ($trainingUserId != $selectedUserId) {
                    continue; // Ignorer les entraînements qui ne correspondent pas à l'utilisateur sélectionné
                }
                
                $exerciseId = $training['exercise_sheet_id'] ?? 'no_exercise';
                if (!isset($sessionsByExercise[$exerciseId])) {
                    $sessionsByExercise[$exerciseId] = [];
                }
            $sessionsByExercise[$exerciseId][] = $training;
                
            }
        }
        
        // Grouper seulement les exercices qui ont des sessions ou des données de progression
        $exercisesWithData = [];
        
        // Collecter les IDs d'exercices qui ont des sessions
        $exerciseIdsWithSessions = array_keys($sessionsByExercise);
        
        // Collecter les IDs d'exercices qui ont des données de progression
        $exerciseIdsWithProgress = [];
        foreach ($trainings as $training) {
            if (is_array($training) && isset($training['exercise_sheet_id'])) {
                $exerciseIdsWithProgress[] = $training['exercise_sheet_id'];
            }
        }
        
        // Combiner les deux listes
        $exerciseIdsToShow = array_unique(array_merge($exerciseIdsWithSessions, $exerciseIdsWithProgress));
        
        // Filtrer les exercices pour ne garder que ceux qui ont des données
        foreach ($allExercises as $exercise) {
            if (!is_array($exercise)) {
                continue;
            }
            
            $exerciseId = $exercise['id'] ?? $exercise['_id'] ?? 'no_exercise';
            
            // Ne traiter que les exercices qui ont des sessions ou des données de progression
            if (in_array($exerciseId, $exerciseIdsToShow)) {
                $exercisesWithData[] = $exercise;
            }
        }
        
        // Grouper les exercices filtrés par catégorie
        foreach ($exercisesWithData as $exercise) {
            $category = $exercise['category'] ?? 'Sans catégorie';
            $exerciseTitle = $exercise['title'] ?? 'Sans exercice';
            $exerciseId = $exercise['id'] ?? $exercise['_id'] ?? 'no_exercise';
            
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
                    'description' => $exercise['description'] ?? '',
                    'creator_name' => $exercise['creator_name'] ?? 'Inconnu',
                    'created_at' => $exercise['created_at'] ?? '',
                    'progression' => $exercise['progression'] ?? 'non_actif',
                    'attachment_filename' => $exercise['attachment_filename'] ?? '',
                    'attachment_original_name' => $exercise['attachment_original_name'] ?? '',
                    'attachment_mime_type' => $exercise['attachment_mime_type'] ?? '',
                    'attachment_size' => $exercise['attachment_size'] ?? 0,
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
            
            // Récupérer les sessions pour cet exercice depuis les données déjà récupérées
            $exerciseSessions = $sessionsByExercise[$exerciseId] ?? [];
            
            // Récupérer les statistiques et sessions depuis l'API backend
            $dashboardData = $this->getExerciseDashboardData($exerciseId, $selectedUserId);
            $globalStats = $dashboardData['stats'] ?? null;
            $apiSessions = $dashboardData['sessions'] ?? [];
            
            // Utiliser les sessions de l'API si disponibles et qu'elles correspondent à l'utilisateur sélectionné
            if (!empty($apiSessions)) {
                // Vérifier que les sessions correspondent à l'utilisateur sélectionné
                $validSessions = [];
                foreach ($apiSessions as $session) {
                    if (isset($session['user_id']) && $session['user_id'] == $selectedUserId) {
                        $validSessions[] = $session;
                    }
                }
                
                if (!empty($validSessions)) {
                    $exerciseSessions = $validSessions;
                } else {
                    $exerciseSessions = [];
                }
            }
            
            // Utiliser exerciseSessions au lieu de realSessions pour éviter le conflit de variable
            $realSessionsForExercise = $exerciseSessions;
            
            // Utiliser les statistiques de l'API backend seulement si elles existent et correspondent à l'utilisateur sélectionné
            // IMPORTANT: Vérifier que les statistiques sont bien pour l'utilisateur sélectionné
            if ($globalStats && isset($globalStats['total_sessions'])) {
                // Utiliser les statistiques calculées par l'API backend (elles sont déjà filtrées par user_id dans l'endpoint)
                $totalSessions = (int)$globalStats['total_sessions'];
                $totalArrows = (int)($globalStats['total_arrows'] ?? $globalStats['total_arrows_shot'] ?? 0);
                $totalTime = (int)($globalStats['total_duration_minutes'] ?? $globalStats['total_time_minutes'] ?? 0);
                $lastSessionDate = $globalStats['last_session_date'] ?? $globalStats['last_training_date'] ?? null;
                $firstSessionDate = $globalStats['first_session_date'] ?? $globalStats['first_training_date'] ?? null;
                
                // Debug: Vérifier que les stats sont bien récupérées pour l'utilisateur sélectionné
                error_log("Stats pour exercice $exerciseId, utilisateur $selectedUserId: sessions=$totalSessions, flèches=$totalArrows, temps=$totalTime");
            } else {
                // Pas de statistiques API disponibles - l'utilisateur n'a pas de vraies séances pour cet exercice
                $totalSessions = 0;
                $totalArrows = 0;
                $totalTime = 0;
                $lastSessionDate = null;
                $firstSessionDate = null;
            }
                
            // Mettre à jour les statistiques de l'exercice
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_sessions'] = $totalSessions;
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_arrows'] = $totalArrows;
            $grouped[$category]['exercises'][$exerciseId]['stats']['total_time_minutes'] = $totalTime;
            $grouped[$category]['exercises'][$exerciseId]['stats']['first_session'] = $firstSessionDate;
            $grouped[$category]['exercises'][$exerciseId]['stats']['last_session'] = $lastSessionDate;
            
            // Mettre à jour les totaux de la catégorie
            $grouped[$category]['total_sessions'] += $totalSessions;
            $grouped[$category]['total_arrows'] += $totalArrows;
            $grouped[$category]['total_time_minutes'] += $totalTime;
            
            // Ajouter les sessions
            foreach ($realSessionsForExercise as $session) {
                if (!is_array($session)) {
                    continue; // Ignorer les éléments non-tableaux
                }
            
                $grouped[$category]['exercises'][$exerciseId]['sessions'][] = [
                'id' => $session['id'] ?? $session['_id'] ?? 'unknown',
                    'start_date' => $session['start_date'] ?? $session['created_at'] ?? null,
                    'created_at' => $session['created_at'] ?? null,
                    'end_date' => $session['end_date'] ?? null,
                'arrows_shot' => $session['total_arrows'] ?? $session['arrows_shot'] ?? 0,
                'total_arrows' => $session['total_arrows'] ?? $session['arrows_shot'] ?? 0,
                'duration_minutes' => $session['duration_minutes'] ?? $session['duration'] ?? 0,
                    'total_sessions' => 1,
                'score' => $session['score'] ?? $session['total_score'] ?? 0,
                    'is_aggregated' => false,
                    'user_name' => $selectedUser['name'] ?? 'Utilisateur',
                    'user_id' => $selectedUserId
                ];
            }
        }
        
        return $grouped;
    }

    
    /**
     * Récupère les détails d'une session d'entraînement par son ID
     * @param int $id ID de la session
     * @return array|null Données de la session ou null si non trouvée
     */
    private function getSessionById($id) {
        try {
            // Utiliser l'endpoint pour récupérer les détails d'une session
            $response = $this->apiService->getTrainingById($id);
            
            if ($response['success'] && !empty($response['data'])) {
                $session = $response['data'];
                
                // Vérifier si les données sont dans une structure imbriquée
                if (isset($session['data']) && is_array($session['data'])) {
                    $session = $session['data'];
                }
                
                return $session;
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère toutes les sessions d'entraînement d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Sessions d'entraînement
     */
    private function fetchAllTrainingSessions($userId) {
        try {
            $response = $this->apiService->makeRequest("/training/sessions/user/$userId", 'GET');
            
            // Debug: Log de la réponse complète
            
            if ($response['success'] && isset($response['data'])) {
                $sessions = $response['data'];
                
                // Vérifier si les sessions sont dans une structure imbriquée
                if (isset($sessions['data']) && is_array($sessions['data'])) {
                    $sessions = $sessions['data'];
                }
                
                // Vérifier que c'est bien un tableau
                if (is_array($sessions)) {
                    return $sessions;
            } else {
                    return [];
                }
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les données complètes d'un exercice depuis l'API dashboard
     * @param int $exerciseId ID de l'exercice
     * @param int $userId ID de l'utilisateur
     * @return array Données de l'exercice (stats + sessions)
     */
    private function getExerciseDashboardData($exerciseId, $userId) {
        try {
            // Inclure l'userId dans l'endpoint pour récupérer les données spécifiques à l'utilisateur
            $endpoint = "/training/dashboard/$exerciseId?user_id=$userId";
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            
            if ($response['success'] && isset($response['data']['data'])) {
                $data = $response['data']['data'];
                
                // Extraire les statistiques
                $stats = $data['global_stats'] ?? null;
                
                // Extraire les sessions récentes
                $sessions = $data['recent_sessions'] ?? [];
                
                return [
                    'stats' => $stats,
                    'sessions' => $sessions
                ];
            }
            
            return ['stats' => null, 'sessions' => []];
        } catch (Exception $e) {
            return ['stats' => null, 'sessions' => []];
        }
    }

    /**
     * Récupère les statistiques d'un exercice depuis l'API backend
     * @param int $exerciseId ID de l'exercice
     * @param int $userId ID de l'utilisateur
     * @return array|null Statistiques de l'exercice
     */
    private function getExerciseStats($exerciseId, $userId) {
        try {
            // Utiliser l'endpoint dashboard qui calcule les statistiques dynamiquement
            $endpoint = "/training/dashboard/$exerciseId?user_id=$userId";
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            
            if ($response['success'] && isset($response['data']['global_stats'])) {
                return $response['data']['global_stats'];
            }
            
            // Essayer d'autres structures possibles
            if ($response['success'] && isset($response['data'])) {
                
                // Vérifier si les statistiques sont dans data.data.global_stats
                if (isset($response['data']['data']['global_stats'])) {
                    return $response['data']['data']['global_stats'];
                }
                
                // Vérifier si les statistiques sont dans data.data directement
                if (isset($response['data']['data']['total_sessions'])) {
                    return $response['data']['data'];
                }
                
                // Vérifier si les statistiques sont directement dans data
                if (isset($response['data']['total_sessions'])) {
                    return $response['data'];
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère les sessions d'un exercice pour un utilisateur
     * @param int $exerciseId ID de l'exercice
     * @param int $userId ID de l'utilisateur
     * @return array Liste des sessions
     */
    private function fetchSessionsForExercise($exerciseId, $userId) {
        try {
            // Utiliser l'endpoint pour récupérer les sessions d'un exercice
            $response = $this->apiService->getTrainingSessions($exerciseId);
            
            // Vérifier que la réponse est valide
            if (!is_array($response)) {
                return [];
            }
            
            if ($response['success'] && isset($response['data']) && !empty($response['data'])) {
                $data = $response['data'];
                
                // Si les données sont dans une structure imbriquée
                if (isset($data['recent_sessions']) && is_array($data['recent_sessions'])) {
                    return $data['recent_sessions'];
                } elseif (is_array($data)) {
                    return $data;
                }
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Supprimer une session d'entraînement
     */
    public function deleteSession() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        try {
            // Récupérer les données JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['session_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de session manquant']);
                exit;
            }
            
            $sessionId = $data['session_id'];
            $currentUser = $_SESSION['user'];
            $isAdmin = $currentUser['is_admin'] ?? false;
            $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        $isDirigeant = ($currentUser['role'] ?? '') === 'Dirigeant';
            
            // Vérifier les permissions
            if (!$isAdmin && !$isCoach) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
                exit;
            }
            
            // Appeler l'API backend pour supprimer la session
            $result = $this->apiService->deleteTrainingSession($sessionId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Session supprimée avec succès'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erreur lors de la suppression'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ]);
        }
    }


}
