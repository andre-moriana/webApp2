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
        
        // Debug: afficher l'utilisateur sélectionné
        error_log('DEBUG index() - selectedUserId: ' . $selectedUserId);
        error_log('DEBUG index() - actualUserId: ' . $actualUserId);
        error_log('DEBUG index() - isAdmin: ' . ($isAdmin ? 'true' : 'false'));
        error_log('DEBUG index() - isCoach: ' . ($isCoach ? 'true' : 'false'));
        
        // Récupérer les tirs comptés de l'utilisateur
        $scoredTrainings = $this->getScoredTrainings($selectedUserId);
        
        // Debug: vérifier le type de données retourné
        error_log('DEBUG index() - scoredTrainings type: ' . gettype($scoredTrainings));
        error_log('DEBUG index() - scoredTrainings value: ' . json_encode($scoredTrainings));
        
        // Forcer $scoredTrainings à être un array pour éviter les erreurs
        if (!is_array($scoredTrainings)) {
            error_log('DEBUG index() - scoredTrainings is not an array, forcing to empty array');
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
        // Debug: afficher l'ID reçu
        error_log('DEBUG show() - ID reçu: ' . $id);
        error_log('DEBUG show() - Type ID: ' . gettype($id));
        
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
        
        // Debug: afficher les IDs
        error_log('DEBUG show() - actualUserId: ' . $actualUserId);
        error_log('DEBUG show() - selectedUserId: ' . $selectedUserId);
        
        // Récupérer les détails du tir compté
        $apiResponse = $this->getScoredTrainingById($id);
        
        // Debug: afficher la réponse API
        error_log('DEBUG show() - API Response: ' . var_export($apiResponse, true));
        
        // Debug: afficher le token utilisé
        $token = $_SESSION['token'] ?? 'NULL';
        error_log('DEBUG show() - Token utilisé: ' . substr($token, 0, 20) . '...');
        
        if (!$apiResponse || !$apiResponse['success'] || empty($apiResponse['data'])) {
            error_log('DEBUG show() - Tir compté non trouvé pour ID: ' . $id);
            header('Location: /scored-trainings?error=' . urlencode('Tir compté non trouvé'));
            exit;
        }
        
        // Extraire les données du tir compté
        $scoredTraining = $apiResponse['data'];
        
        // Ajouter l'ID du tir compté s'il n'est pas présent
        if (!isset($scoredTraining['id'])) {
            $scoredTraining['id'] = $id;
        }
        
        // Debug: afficher l'ID du tir compté
        error_log('DEBUG show() - scoredTraining id: ' . $scoredTraining['id']);
        
        // Debug: afficher les IDs pour comparaison
        $scoredTrainingUserId = $scoredTraining['user_id'] ?? null;
        error_log('DEBUG show() - scoredTraining user_id: ' . ($scoredTrainingUserId ?? 'NULL'));
        error_log('DEBUG show() - Comparaison: ' . $scoredTrainingUserId . ' != ' . $selectedUserId . ' = ' . ($scoredTrainingUserId != $selectedUserId ? 'true' : 'false'));
        
        // Vérifier que le tir compté appartient à l'utilisateur sélectionné
        if ($scoredTrainingUserId != $selectedUserId) {
            error_log('DEBUG show() - Accès refusé: user_id du tir (' . $scoredTrainingUserId . ') != selectedUserId (' . $selectedUserId . ')');
            header('Location: /scored-trainings?error=' . urlencode('Tir compté non trouvé pour cet utilisateur'));
            exit;
        }
        
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
        
        // Debug: Vérifier si les scripts sont inclus
        error_log("DEBUG CREATE: additionalJS = " . implode(', ', $additionalJS));
        
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
        
        if (empty($title) || $totalEnds <= 0 || $arrowsPerEnd <= 0) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes ou invalides']);
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->createScoredTraining([
                'title' => $title,
                'total_ends' => (int)$totalEnds,
                'arrows_per_end' => (int)$arrowsPerEnd,
                'exercise_sheet_id' => $exerciseSheetId ? (int)$exerciseSheetId : null,
                'notes' => $notes,
                'shooting_type' => $shootingType
            ]);
            
            // Debug: afficher la réponse de l'API
            error_log('DEBUG store() - Réponse API: ' . json_encode($response));
            
            $this->sendJsonResponse($response);

        } catch (Exception $e) {
            error_log('Erreur lors de la création du tir compté: ' . $e->getMessage());
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
        $shootingType = $data['shooting_type'] ?? null;
        
        if (empty($trainingId)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID du tir compté requis']);
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->endScoredTraining((int)$trainingId, [
                'notes' => $notes,
                'shooting_type' => $shootingType
            ]);
            
            $this->sendJsonResponse($response);
            
        } catch (Exception $e) {
            error_log('Erreur lors de la finalisation du tir compté: ' . $e->getMessage());
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
        
        // Debug: afficher l'ID récupéré
        error_log('DEBUG addEnd() - ID reçu: ' . $trainingId);
        error_log('DEBUG addEnd() - Token: ' . substr($_SESSION['token'] ?? 'NULL', 0, 20) . '...');
        
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $endData = json_decode($input, true);
        
        if (empty($trainingId) || empty($endData)) {
            error_log('DEBUG addEnd() - Données manquantes - trainingId: ' . $trainingId . ', endData: ' . json_encode($endData));
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes']);
        }
        
        try {
            // Appeler l'API backend
            $response = $this->apiService->addScoredEnd((int)$trainingId, $endData);
            
            $this->sendJsonResponse($response);
            
        } catch (Exception $e) {
            error_log('Erreur lors de l\'ajout de la volée: ' . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur serveur']);
        }
    }
    
    private function getScoredTrainings($userId) {
        try {
            $response = $this->apiService->getScoredTrainings($userId);
            
            // Debug: log de la réponse
            error_log('DEBUG getScoredTrainings - userId: ' . $userId);
            error_log('DEBUG getScoredTrainings - response type: ' . gettype($response));
            error_log('DEBUG getScoredTrainings - response: ' . json_encode($response));
            
            // Vérifier si la réponse est valide
            if (!is_array($response)) {
                error_log('DEBUG getScoredTrainings - response is not an array: ' . gettype($response));
                return [];
            }
            
            // Vérifier si la réponse contient success et data
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                $data = $response['data'];
                
                // Vérifier si les données sont imbriquées (API retourne {success: true, data: {success: true, data: [...]}})
                if (is_array($data) && isset($data['success']) && $data['success'] && isset($data['data']) && is_array($data['data'])) {
                    error_log('DEBUG getScoredTrainings - nested structure detected, returning inner data');
                    return $data['data'];
                }
                
                // Si les données sont directement un tableau
                if (is_array($data)) {
                    error_log('DEBUG getScoredTrainings - direct array structure detected, returning data');
                    return $data;
                }
            }
            
            // Si l'API retourne une erreur ou des données vides
            if (isset($response['success']) && !$response['success']) {
                error_log('DEBUG getScoredTrainings - API returned error: ' . ($response['message'] ?? 'Unknown error'));
            }
            
            error_log('DEBUG getScoredTrainings - no valid data found, returning empty array');
            return [];
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des tirs comptés: ' . $e->getMessage());
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
            error_log('Erreur lors de la récupération du tir compté: ' . $e->getMessage());
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
            error_log('Erreur lors de la récupération des configurations: ' . $e->getMessage());
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
                error_log('DEBUG calculateScoredTrainingStats - skipping non-array training: ' . gettype($training));
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
            error_log('Erreur lors de la récupération des exercices: ' . $e->getMessage());
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
    
    public function delete($id) {
        error_log('DEBUG delete() - Méthode appelée avec ID: ' . $id);
        error_log('DEBUG delete() - REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
        error_log('DEBUG delete() - Utilisateur session: ' . json_encode($_SESSION['user'] ?? 'non défini'));
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log('DEBUG delete() - Utilisateur non connecté');
            $this->sendJsonResponse(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
        }
        
        if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
            error_log('DEBUG delete() - Token manquant dans la session');
            $this->sendJsonResponse(['success' => false, 'message' => 'Token d\'authentification manquant. Veuillez vous reconnecter.']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            error_log('DEBUG delete() - Méthode non autorisée: ' . $_SERVER['REQUEST_METHOD']);
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        
        if (empty($id)) {
            error_log('DEBUG delete() - ID vide');
            $this->sendJsonResponse(['success' => false, 'message' => 'ID du tir compté requis']);
        }
        
        try {
            error_log('DEBUG delete() - Appel API pour supprimer ID: ' . $id);
            $response = $this->apiService->deleteScoredTraining((int)$id);
            error_log('DEBUG delete() - Réponse API: ' . json_encode($response));
            
            // S'assurer que la réponse est un tableau
            if (!is_array($response)) {
                error_log('DEBUG delete() - Réponse API invalide (pas un tableau): ' . gettype($response));
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
            error_log('Erreur lors de la suppression du tir compté: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
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
            error_log('Erreur lors du décodage du token: ' . $e->getMessage());
            return null;
        }
    }
}
?>
