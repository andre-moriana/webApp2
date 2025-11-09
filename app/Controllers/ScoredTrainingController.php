<?php

// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';

class ScoredTrainingController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    // Fonction utilitaire pour envoyer du JSON propre
    private function sendJsonResponse($data) {
        // Nettoyer complètement la sortie
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
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
        
        // Récupérer l'ID utilisateur depuis le token
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId;
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        
        // Récupérer les informations de l'utilisateur sélectionné
        $selectedUser = $this->getUserInfo($selectedUserId);
        
        // Récupérer les exercices disponibles
        $exercises = $this->getAllExercisesForUser($isAdmin, $isCoach);
        
        // Récupérer les tirs comptés de l'utilisateur
        $scoredTrainings = $this->getScoredTrainings($selectedUserId);
        
        // Forcer $scoredTrainings à être un array pour éviter les erreurs
        if (!is_array($scoredTrainings)) {
            $scoredTrainings = [];
        }
        
        // Debug visible pour tester
        if (gettype($scoredTrainings) !== 'array') {
            echo "<!-- DEBUG: scoredTrainings type: " . gettype($scoredTrainings) . " -->";
            echo "<!-- DEBUG: scoredTrainings value: " . json_encode($scoredTrainings) . " -->";
        }
        
        // Récupérer les configurations des types de tir
        $shootingConfigurations = $this->getShootingConfigurations();
        
        // Récupérer la liste des utilisateurs pour les modals (seulement pour les coaches/admins)
        $users = [];
        if ($isAdmin || $isCoach) {
            try {
                $usersResponse = $this->apiService->getUsers();
                if ($usersResponse['success'] && !empty($usersResponse['data'])) {
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
        
        $stats = $this->calculateScoredTrainingStats($scoredTrainings);
        
        $title = 'Tirs comptés - Portail Archers de Gémenos';
        
        // Définir les fichiers CSS et JS spécifiques
        $additionalCSS = ['/public/assets/css/scored-trainings.css'];
        $additionalJS = ['/public/assets/js/scored-trainings-simple.js?v=' . time()];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue des tirs comptés
        include 'app/Views/scored-trainings/index.php';
        
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
        
        // Récupérer l'ID utilisateur depuis le token
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId;
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        // Récupérer les détails du tir compté via l'API externe avec le user_id sélectionné
        $apiResponse = $this->getScoredTrainingByIdWithUserId($id, $selectedUserId);
        
        if (!$apiResponse || !$apiResponse['success'] || empty($apiResponse['data'])) {
            header('Location: /scored-trainings?error=' . urlencode('Tir compté non trouvé pour cet utilisateur'));
            exit;
        }
        
        // Extraire les données du tir compté
        $scoredTraining = $apiResponse['data'];
        
        
        // Les données de l'API externe sont maintenant correctes
        
        // Ajouter l'ID du tir compté s'il n'est pas présent
        if (!isset($scoredTraining['id'])) {
            $scoredTraining['id'] = $id;
        }
        
        
        // Debug: afficher les IDs pour comparaison
        $scoredTrainingUserId = $scoredTraining['user_id'] ?? null;
        // Vérifier les permissions
        if (!$isAdmin && !$isCoach && $scoredTraining['user_id'] != $actualUserId) {
            header('Location: /scored-trainings?error=' . urlencode('Accès refusé'));
            exit;
        }
        
        // Récupérer les informations de l'utilisateur
        $selectedUser = $this->getUserInfo($selectedUserId);
        
        $title = 'Détails du tir compté - Portail Archers de Gémenos';
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue des détails du tir compté
        include 'app/Views/scored-trainings/show.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $currentUser = $_SESSION['user'];
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer l'ID utilisateur depuis le token
        $actualUserId = $this->getUserIdFromToken();
        if (!$actualUserId) {
            header('Location: /login?error=' . urlencode('Session invalide'));
            exit;
        }
        
        // Récupérer l'ID de l'utilisateur sélectionné
        $selectedUserId = $actualUserId;
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
        
        // Récupérer les exercices disponibles
        $exercises = $this->getAllExercisesForUser($isAdmin, $isCoach);
        
        // Récupérer les configurations des types de tir
        $shootingConfigurations = $this->getShootingConfigurations();
        
        $title = 'Nouveau tir compté - Portail Archers de Gémenos';
        
        // Définir les fichiers CSS et JS spécifiques AVANT d'inclure le header
        $additionalCSS = ['/public/assets/css/scored-trainings.css'];
        $additionalJS = ['/public/assets/js/scored-trainings-simple.js?v=' . time()];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue de création du tir compté
        include 'app/Views/scored-trainings/create.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    public function store() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $title = $data['title'] ?? '';
        $totalEnds = $data['total_ends'] ?? 0;
        $arrowsPerEnd = $data['arrows_per_end'] ?? 0;
        $exerciseSheetId = $data['exercise_sheet_id'] ?? null;
        $notes = $data['notes'] ?? '';
        $shootingType = $data['shooting_type'] ?? null;
        $refBlason = $data['ref_blason'] ?? null;
        
        if (empty($title) || $totalEnds <= 0 || $arrowsPerEnd <= 0) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes ou invalides']);
        }
        
        try {
            // Préparer les données pour l'API
            $trainingData = [
                'title' => $title,
                'total_ends' => (int)$totalEnds,
                'arrows_per_end' => (int)$arrowsPerEnd,
                'exercise_sheet_id' => $exerciseSheetId ? (int)$exerciseSheetId : null,
                'notes' => $notes,
                'shooting_type' => $shootingType
            ];
            
            // Ajouter ref_blason si le type est Nature et qu'un blason est sélectionné
            if ($shootingType === 'Nature' && $refBlason !== null) {
                $trainingData['ref_blason'] = (int)$refBlason;
            }
            
            // Appeler l'API backend
            $response = $this->apiService->createScoredTraining($trainingData);
            
            $this->sendJsonResponse($response);

        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur serveur']);
        }
    }
    
    public function endTraining() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $trainingId = $data['training_id'] ?? '';
        $notes = $data['notes'] ?? '';
        
        // Log de débogage
        error_log('endTraining - Données reçues: ' . json_encode([
            'training_id' => $trainingId,
            'notes_length' => strlen($notes)
        ]));
        
        if (empty($trainingId)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID du tir compté requis']);
        }
        
        // Récupérer l'utilisateur connecté
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté']);
        }
        
        $actualUserId = $currentUser['id'] ?? null;
        if (!$actualUserId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Utilisateur non identifié']);
        }
        
        // Vérifier les permissions (comme dans show())
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer l'ID de l'utilisateur sélectionné (par défaut l'utilisateur connecté)
        $selectedUserId = $actualUserId;
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }

        if (!$selectedUserId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Utilisateur non identifié']);
        }
        
        try {
            
            // Construire l'URL avec le user_id en paramètre
            $baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
            $url = $baseUrl . "/scored-training/" . $trainingId . "/end?user_id=" . $selectedUserId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            
            $token = $_SESSION['token'] ?? '';
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            $postData = [
                'notes' => $notes
            ];
            
            // Log de débogage
            error_log('endTraining - Données envoyées à l\'API: ' . json_encode($postData));
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $this->sendJsonResponse($data);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => 'Erreur API externe']);
            }
            
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur serveur']);
        }
    }
    
    public function addEnd($id = null) {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        
        // Récupérer l'ID du tir compté depuis le paramètre de route
        $trainingId = $id;
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $endData = json_decode($input, true);
        
        if (empty($trainingId) || empty($endData)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes']);
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->addScoredEnd((int)$trainingId, $endData);
            
            $this->sendJsonResponse($response);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur serveur']);
        }
    }
    
    private function getScoredTrainings($userId) {
        try {
            $response = $this->apiService->getScoredTrainings($userId);
            
            // Vérifier si la réponse est valide
            if (!is_array($response)) {
                return [];
            }
            
            // Vérifier si la réponse contient success et data
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                $data = $response['data'];
                
                // Vérifier si les données sont imbriquées (API retourne {success: true, data: {success: true, data: [...]}})
                if (is_array($data) && isset($data['success']) && $data['success'] && isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }
                
                // Si les données sont directement un tableau
                if (is_array($data)) {
                    return $data;
                }
            }
            
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getScoredTrainingById($trainingId) {
        try {
            $response = $this->apiService->getScoredTrainingById($trainingId);
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function getScoredTrainingByIdWithUserId($trainingId, $selectedUserId) {
        try {
            // Utiliser l'API externe avec le user_id sélectionné
            $baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
            $url = $baseUrl . "/scored-training/" . $trainingId . "?user_id=" . $selectedUserId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . ($_SESSION['token'] ?? ''),
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
           if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data;
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function getShootingConfigurations() {
        try {
            $response = $this->apiService->getScoredTrainingConfigurations();
            
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function calculateScoredTrainingStats($scoredTrainings) {
        $stats = [
            'total_trainings' => 0,
            'total_arrows' => 0,
            'total_ends' => 0,
            'total_score' => 0,
            'average_score' => 0,
            'best_training_score' => 0,
            'last_training_date' => null
        ];
        
        // Vérifier que $scoredTrainings est un tableau
        if (!is_array($scoredTrainings) || empty($scoredTrainings)) {
            return $stats;
        }
        
        $totalScore = 0;
        $bestScore = 0;
        $lastDate = null;
        
        foreach ($scoredTrainings as $training) {
            // Vérifier que chaque élément est un tableau
            if (!is_array($training)) {
                continue;
            }
            
            $stats['total_trainings']++;
            $stats['total_arrows'] += $training['total_arrows'] ?? 0;
            $stats['total_ends'] += $training['total_ends'] ?? 0;
            $totalScore += $training['total_score'] ?? 0;
            
            if (($training['total_score'] ?? 0) > $bestScore) {
                $bestScore = $training['total_score'] ?? 0;
            }
            
            $date = $training['start_date'] ?? $training['created_at'] ?? '';
            if ($date && (!$lastDate || $date > $lastDate)) {
                $lastDate = $date;
            }
        }
        
        $stats['total_score'] = $totalScore;
        $stats['best_training_score'] = $bestScore;
        $stats['last_training_date'] = $lastDate;
        
        if ($stats['total_trainings'] > 0) {
            $stats['average_score'] = $totalScore / $stats['total_trainings'];
        }
        
        return $stats;
    }
    
    private function getAllExercisesForUser($isAdmin, $isCoach) {
        try {
            $response = $this->apiService->getExercises();
            
            if ($response['success'] && !empty($response['data'])) {
                $exercises = $response['data'];
                
                if (isset($exercises['success']) && isset($exercises['data'])) {
                    $exercises = $exercises['data'];
                }
                
                // Filtrer les exercices selon les permissions
                $filteredExercises = [];
                foreach ($exercises as $exercise) {
                    $exerciseProgression = $exercise['progression'] ?? 'non_actif';
                    
                    if ($isAdmin || $isCoach || $exerciseProgression !== 'masqué') {
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
    
    private function getUserInfo($userId) {
        try {
            $actualUserId = $this->getUserIdFromToken();
            
            if ((string)$userId === (string)$actualUserId) {
                $response = $this->apiService->getUserById($userId);
                if ($response['success'] && !empty($response['data'])) {
                    return $response['data'];
                }
                
                $currentUser = $_SESSION['user'];
                return [
                    'id' => $actualUserId,
                    'name' => $currentUser['name'] ?? 'Utilisateur ' . $userId,
                    'profile_image' => $currentUser['profile_image'] ?? null,
                    'firstName' => $currentUser['firstName'] ?? 'Utilisateur',
                    'lastName' => $currentUser['lastName'] ?? $userId
                ];
            }
            
            $response = $this->apiService->getUserById($userId);
            if ($response['success'] && !empty($response['data'])) {
                return $response['data'];
            }
            
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
    
    public function delete($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
        }
        
        if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
             $this->sendJsonResponse(['success' => false, 'message' => 'Token d\'authentification manquant. Veuillez vous reconnecter.']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        
        if (empty($id)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID du tir compté requis']);
        }
        // Récupérer l'utilisateur connecté
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté']);
        }
        
        $actualUserId = $currentUser['id'] ?? null;
        if (!$actualUserId) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Utilisateur non identifié']);
        }
        
        // Vérifier les permissions (comme dans show() et endTraining())
        $isAdmin = $currentUser['is_admin'] ?? false;
        $isCoach = ($currentUser['role'] ?? '') === 'Coach';
        
        // Récupérer l'ID de l'utilisateur sélectionné (par défaut l'utilisateur connecté)
        $selectedUserId = $actualUserId;
        
        // Si un utilisateur est sélectionné via GET et que l'utilisateur a les permissions
        if (($isAdmin || $isCoach) && isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }
       
        try {
            // Ne pas passer user_id - l'API backend doit utiliser l'utilisateur connecté pour vérifier les permissions
            $baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
            $url = $baseUrl . "/scored-training/" . $id;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            
            $token = $_SESSION['token'] ?? '';
            $currentUser = $_SESSION['user'] ?? null;
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200) {
                $response = json_decode($response, true);
            } else {
                $response = ['success' => false, 'message' => 'Erreur API externe'];
            }
            // S'assurer que la réponse est un tableau
            if (!is_array($response)) {
                $response = ['success' => false, 'message' => 'Réponse API invalide'];
            }
             // S'assurer que les clés requises existent
            if (!isset($response['success'])) {
                $response['success'] = false;
            }
            if (!isset($response['message']) && !$response['success']) {
                $response['message'] = 'Erreur inconnue';
            }
            
            $this->sendJsonResponse($response);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false, 
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getUserIdFromToken() {
        if (!isset($_SESSION['token'])) {
            return null;
        }
        
        try {
            $token = $_SESSION['token'];
            $decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $token)[1])), true);
            return $decoded['user_id'] ?? null;
        } catch (Exception $e) {
           return null;
        }
    }
    
    /**
     * Récupère les images nature (blasons) pour le formulaire
     * GET /scored-trainings/images-nature?type=... ou ?label=...
     */
    public function getNatureImages() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non authentifié']);
            return;
        }
        
        $type = $_GET['type'] ?? null;
        $label = $_GET['label'] ?? null;
        
        try {
            $response = $this->apiService->getNatureImages($type, $label);
            
            // Log pour débogage
            error_log("ScoredTrainingController::getNatureImages - Type: " . ($type ?? 'null') . ", Label: " . ($label ?? 'null'));
            error_log("ScoredTrainingController::getNatureImages - Réponse API: " . json_encode($response));
            
            // Vérifier la structure de la réponse
            // L'API externe retourne: ['success' => true, 'data' => [...], 'status_code' => 200]
            // Il faut extraire le 'data' qui contient le JSON décodé
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                // Le 'data' de ApiService contient la réponse JSON décodée de l'API externe
                $apiData = $response['data'];
                
                // Si apiData contient déjà success et data, on le renvoie tel quel
                if (isset($apiData['success']) && isset($apiData['data'])) {
                    $this->sendJsonResponse($apiData);
                } else {
                    // Sinon, on renvoie la structure attendue
                    $this->sendJsonResponse([
                        'success' => true,
                        'data' => $apiData,
                        'count' => is_array($apiData) ? count($apiData) : (isset($apiData['count']) ? $apiData['count'] : 0)
                    ]);
                }
            } else {
                // Si la réponse n'est pas dans le format attendu, la renvoyer telle quelle
                $this->sendJsonResponse($response);
            }
        } catch (Exception $e) {
            error_log("ScoredTrainingController::getNatureImages - Erreur: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des images nature: ' . $e->getMessage()
            ]);
        }
    }
}
?>
