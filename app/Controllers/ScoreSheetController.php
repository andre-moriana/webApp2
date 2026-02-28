<?php

require_once __DIR__ . '/../Services/ApiService.php';

class ScoreSheetController {
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
        
        $pageTitle = 'Feuille de marque - Portail  Arc Training';
        
        // Charger la liste des concours pour la sélection
        $concours = [];
        $disciplines = [];
        try {
            $response = $this->apiService->getConcours();
            $apiResponse = $response['data'] ?? null;
            if ($response['success'] && isset($apiResponse) && is_array($apiResponse)) {
                $concours = isset($apiResponse[0]) && is_array($apiResponse[0]) 
                    ? $apiResponse 
                    : ($apiResponse['concours'] ?? []);
            }
        } catch (Exception $e) {
            error_log('Erreur chargement concours pour feuille de marque: ' . $e->getMessage());
        }
        
        // Charger les disciplines pour mapper vers le type de tir
        try {
            $discResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            $discPayload = $this->apiService->unwrapData($discResponse);
            if (is_array($discPayload) && isset($discPayload['data']) && isset($discPayload['success'])) {
                $discPayload = $discPayload['data'];
            }
            if (($discResponse['success'] ?? false) && is_array($discPayload)) {
                $disciplines = array_values($discPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur chargement disciplines pour feuille de marque: ' . $e->getMessage());
        }
        
        // Définir les fichiers CSS et JS spécifiques
        $additionalCSS = [
            '/public/assets/css/score-sheet.css',
            '/public/assets/css/scored-trainings.css'
        ];
        $additionalJS = [
            '/public/assets/js/score-sheet.js?v=' . time()
        ];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue de la feuille de marque
        include 'app/Views/score-sheet/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Retourne les catégories de classement (concour_categories_classement)
     * Filtrées par iddiscipline si fourni. Format: value=abv_categorie_classement, affichage=lb_categorie_classement
     */
    public function getCategories() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }
        $iddiscipline = isset($_GET['iddiscipline']) ? (int)$_GET['iddiscipline'] : null;
        $abvs = isset($_GET['abv_categorie_classement']) ? trim($_GET['abv_categorie_classement']) : '';
        $endpoint = 'concours/categories-classement';
        $params = [];
        if ($iddiscipline) $params[] = 'iddiscipline=' . $iddiscipline;
        if ($abvs !== '') $params[] = 'abv_categorie_classement=' . urlencode($abvs);
        if (!empty($params)) $endpoint .= '?' . implode('&', $params);
        try {
            $response = $this->apiService->makeRequest($endpoint, 'GET');
            $raw = $response['data'] ?? null;
            $categories = [];
            if (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
                $categories = $raw['data'];
            } elseif (is_array($raw) && isset($raw[0])) {
                $categories = $raw;
            }
            $this->sendJsonResponse(['success' => true, 'data' => $categories]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'data' => [], 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Créer les sessions (scored_trainings) pour chaque archer lors de l'import automatique.
     * POST body: { shooting_type, training_title, user_sheets: [ { archer_info, user_id }, ... ] }
     * Retourne: { success, data: { training_ids: [ id0, id1, ... ] } }
     */
    public function createSessions() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (empty($data) || !isset($data['user_sheets']) || !isset($data['shooting_type'])) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes']);
        }
        $shootingConfigs = [
            'Salle' => ['total_ends' => 20, 'arrows_per_end' => 3, 'total_arrows' => 60],
            'TAE' => ['total_ends' => 12, 'arrows_per_end' => 6, 'total_arrows' => 72],
            'Nature' => ['total_ends' => 21, 'arrows_per_end' => 2, 'total_arrows' => 42],
            '3D' => ['total_ends' => 24, 'arrows_per_end' => 2, 'total_arrows' => 48],
            'Campagne' => ['total_ends' => 24, 'arrows_per_end' => 3, 'total_arrows' => 72],
        ];
        $shootingType = $data['shooting_type'];
        $trainingTitle = $data['training_title'] ?? '';
        $config = $shootingConfigs[$shootingType] ?? null;
        if (!$config) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Type de tir invalide']);
        }
        $trainingIds = [];
        foreach ($data['user_sheets'] as $userSheet) {
            $archerInfo = $userSheet['archer_info'] ?? [];
            $targetUserId = $userSheet['user_id'] ?? null;
            if (!$targetUserId && !empty($archerInfo['licenseNumber'])) {
                try {
                    $baseUrl = $_ENV["API_BASE_URL"] ?? "https://api.arctraining.fr/api";
                    $url = $baseUrl . "/users?licence_number=" . urlencode($archerInfo['licenseNumber']);
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
                        $userData = json_decode($response, true);
                        if (!empty($userData['success']) && !empty($userData['data']['id'])) {
                            $targetUserId = $userData['data']['id'];
                        }
                    }
                } catch (Exception $e) {
                    // continuer sans user_id
                }
            }
            $archerName = $archerInfo['name'] ?? 'Archer';
            $notesParts = [
                'Licence: ' . ($archerInfo['licenseNumber'] ?? 'N/A'),
                'Catégorie: ' . ($archerInfo['category'] ?? 'N/A'),
            ];
            $finalNotes = implode(', ', array_filter($notesParts));
            $finalTitle = $trainingTitle ? $trainingTitle . ' - ' . $archerName : $shootingType . ' - ' . $archerName . ' - ' . date('d/m/Y');
            $createData = [
                'title' => $finalTitle,
                'total_ends' => $config['total_ends'],
                'arrows_per_end' => $config['arrows_per_end'],
                'total_arrows' => $config['total_arrows'],
                'shooting_type' => $shootingType,
                'notes' => $finalNotes,
                'is_score_sheet' => true,
            ];
            if ($targetUserId) {
                $createData['user_id'] = $targetUserId;
            }
            $response = $this->apiService->createScoredTraining($createData);
            if (!empty($response['success']) && !empty($response['data']['id'])) {
                $trainingIds[] = (int)$response['data']['id'];
            } else {
                $trainingIds[] = null;
            }
        }
        $this->sendJsonResponse(['success' => true, 'data' => ['training_ids' => $trainingIds]]);
    }

    /**
     * Enregistrer une volée (scored_end) et ses flèches (scored_shots) en base.
     * POST body: { training_id, end_number, end_total, arrows: [ { value, hit_x?, hit_y? } ], comment? }
     */
    public function addEnd() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (empty($data) || !isset($data['training_id']) || !isset($data['end_number'])) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes: training_id et end_number requis']);
        }
        $trainingId = (int)$data['training_id'];
        $endNumber = (int)$data['end_number'];
        $endTotal = isset($data['end_total']) ? (int)$data['end_total'] : 0;
        $arrows = $data['arrows'] ?? [];
        $shots = [];
        foreach ($arrows as $i => $arr) {
            $shots[] = [
                'arrow_number' => $i + 1,
                'score' => (int)($arr['value'] ?? 0),
                'hit_x' => isset($arr['hit_x']) ? (float)$arr['hit_x'] : null,
                'hit_y' => isset($arr['hit_y']) ? (float)$arr['hit_y'] : null,
            ];
        }
        if ($endTotal === 0 && !empty($shots)) {
            $endTotal = array_sum(array_column($shots, 'score'));
        }
        $endData = [
            'end_number' => $endNumber,
            'total_score' => $endTotal,
            'shots' => $shots,
            'comment' => $data['comment'] ?? '',
        ];
        $response = $this->apiService->addScoredEnd($trainingId, $endData);
        if (!empty($response['success'])) {
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Volée enregistrée',
                'data' => ['end_id' => $response['end_id'] ?? null]
            ]);
        } else {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $response['message'] ?? 'Erreur lors de l\'enregistrement de la volée'
            ], 400);
        }
    }

    public function save() {
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
        
        if (empty($data) || !isset($data['user_sheets']) || !isset($data['shooting_type'])) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes']);
        }
        
        try {
            $savedTrainings = [];
            $shootingType = $data['shooting_type'];
            $trainingTitle = $data['training_title'] ?? '';
            
            // Configuration des types de tir
            $shootingConfigs = [
                'Salle' => ['total_ends' => 20, 'arrows_per_end' => 3, 'total_arrows' => 60],
                'TAE' => ['total_ends' => 12, 'arrows_per_end' => 6, 'total_arrows' => 72],
                'Nature' => ['total_ends' => 21, 'arrows_per_end' => 2, 'total_arrows' => 42],
                '3D' => ['total_ends' => 24, 'arrows_per_end' => 2, 'total_arrows' => 48],
                'Campagne' => ['total_ends' => 24, 'arrows_per_end' => 3, 'total_arrows' => 72],
            ];
            
            $config = $shootingConfigs[$shootingType] ?? null;
            if (!$config) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Type de tir invalide']);
            }
            
            // Traiter chaque feuille d'archer
            foreach ($data['user_sheets'] as $userSheet) {
                // Vérifier si l'archer a des scores
                $hasScores = false;
                foreach ($userSheet['score_rows'] as $row) {
                    if ($row['end_total'] > 0) {
                        $hasScores = true;
                        break;
                    }
                }
                
                if (!$hasScores) {
                    continue; // Passer cet archer s'il n'a pas de scores
                }
                
                $archerInfo = $userSheet['archer_info'];
                $archerName = $archerInfo['name'] ?? 'Archer inconnu';
                
                // Rechercher l'utilisateur par numéro de licence ou utiliser l'ID fourni
                $targetUserId = $userSheet['user_id'] ?? null;
                if (!$targetUserId && !empty($archerInfo['license_number'])) {
                    try {
                        // Essayer de trouver l'utilisateur par numéro de licence
                        $baseUrl = $_ENV["API_BASE_URL"] ?? "https://api.arctraining.fr/api";
                        $url = $baseUrl . "/users?licence_number=" . urlencode($archerInfo['license_number']);
                        
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
                            $userData = json_decode($response, true);
                            if ($userData['success'] && isset($userData['data']['id'])) {
                                $targetUserId = $userData['data']['id'];
                            }
                        }
                    } catch (Exception $e) {
                        // Continuer sans user_id
                    }
                }
                
                // Construire les notes
                $notesParts = [
                    'Licence: ' . ($archerInfo['license_number'] ?? 'N/A'),
                    'Catégorie: ' . ($archerInfo['category'] ?? 'N/A'),
                    'Arme: ' . ($archerInfo['weapon'] ?? 'N/A'),
                    'Genre: ' . ($archerInfo['gender'] === 'H' ? 'Homme' : ($archerInfo['gender'] === 'F' ? 'Femme' : 'N/A')),
                ];
                
                // Ajouter les informations de signature si présentes
                if (!empty($userSheet['signature_info'])) {
                    $notesParts[] = $userSheet['signature_info'];
                }
                
                // Ajouter les signatures au format JSON
                if (!empty($userSheet['signatures'])) {
                    $signaturesJson = json_encode($userSheet['signatures']);
                    $notesParts[] = '__SIGNATURES__:' . $signaturesJson;
                }
                
                $finalNotes = implode(', ', array_filter($notesParts));
                
                $trainingId = isset($userSheet['scored_training_id']) && $userSheet['scored_training_id'] ? (int)$userSheet['scored_training_id'] : null;
                
                // Si session déjà créée à l'import : mettre à jour les notes/signatures uniquement (les volées sont déjà enregistrées à la saisie)
                if ($trainingId) {
                    $this->apiService->updateScoredTrainingNote($trainingId, $finalNotes);
                    $savedTrainings[] = ['training_id' => $trainingId, 'archer_name' => $archerName];
                    continue;
                }
                
                // Créer le tir compté (saisie manuelle sans import)
                $finalTitle = $trainingTitle 
                    ? $trainingTitle . ' - ' . $archerName
                    : $shootingType . ' - ' . $archerName . ' - ' . date('d/m/Y');
                
                $createData = [
                    'title' => $finalTitle,
                    'total_ends' => $config['total_ends'],
                    'arrows_per_end' => $config['arrows_per_end'],
                    'total_arrows' => $config['total_arrows'],
                    'shooting_type' => $shootingType,
                    'notes' => $finalNotes,
                    'is_score_sheet' => true,
                ];
                if ($targetUserId) {
                    $createData['user_id'] = $targetUserId;
                }
                
                $response = $this->apiService->createScoredTraining($createData);
                
                if ($response['success'] && isset($response['data']['id'])) {
                    $trainingId = $response['data']['id'];
                    
                    // Ajouter les volées (scored_ends + scored_shots)
                    foreach ($userSheet['score_rows'] as $row) {
                        $shots = [];
                        foreach ($row['arrows'] as $arrowIndex => $arrow) {
                            $shot = [
                                'arrow_number' => $arrowIndex + 1,
                                'score' => $arrow['value'] ?? 0,
                            ];
                            
                            if (isset($arrow['hit_x']) && isset($arrow['hit_y'])) {
                                $shot['hit_x'] = $arrow['hit_x'];
                                $shot['hit_y'] = $arrow['hit_y'];
                            }
                            
                            $shots[] = $shot;
                        }
                        
                        $endTotal = (int)($row['end_total'] ?? 0);
                        if ($endTotal === 0 && !empty($shots)) {
                            $endTotal = array_sum(array_column($shots, 'score'));
                        }
                        $endData = [
                            'end_number' => $row['end_number'],
                            'total_score' => $endTotal,
                            'shots' => $shots,
                            'comment' => $row['comment'] ?? '',
                        ];
                        
                        // Ajouter la catégorie de cible si présente
                        if (isset($row['target_category'])) {
                            $endData['target_category'] = $row['target_category'];
                        }
                        
                        // Ajouter la position de tir si présente
                        if (isset($row['shooting_position'])) {
                            $endData['shooting_position'] = $row['shooting_position'];
                        }
                        
                        $this->apiService->addScoredEnd($trainingId, $endData);
                    }
                    
                    $savedTrainings[] = [
                        'training_id' => $trainingId,
                        'archer_name' => $archerName,
                    ];
                }
            }
            
            $this->sendJsonResponse([
                'success' => true,
                'message' => count($savedTrainings) . ' feuille(s) de marque sauvegardée(s)',
                'data' => $savedTrainings
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
    }
    
    private function sendJsonResponse($data, $httpCode = 200) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

