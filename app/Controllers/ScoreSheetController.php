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
     * Vérifie qu'il n'existe pas déjà une session en cours pour ce concours et ce départ (infos en JSON dans notes).
     * POST body: { shooting_type, training_title, concours_id?, depart?, peloton?, user_sheets: [ ... ] }
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
        $concoursId = isset($data['concours_id']) && $data['concours_id'] !== '' ? (string)$data['concours_id'] : null;
        $depart = isset($data['depart']) && $data['depart'] !== '' ? (string)$data['depart'] : null;
        $peloton = isset($data['peloton']) && $data['peloton'] !== '' ? (string)$data['peloton'] : null;
        $config = $shootingConfigs[$shootingType] ?? null;
        if (!$config) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Type de tir invalide']);
        }
        $feuilleMarqueJson = json_encode(array_filter([
            'concours_id' => $concoursId,
            'depart' => $depart,
            'peloton' => $peloton,
        ], function ($v) { return $v !== null && $v !== ''; }));
        $notesSuffix = $feuilleMarqueJson !== '[]' ? ', __FEUILLE_MARQUE__:' . $feuilleMarqueJson : '';
        $trainingIds = [];
        $existingEndsByIndex = [];
        foreach ($data['user_sheets'] as $userSheet) {
            $archerInfo = $userSheet['archer_info'] ?? [];
            $licence = trim((string)($archerInfo['licenseNumber'] ?? ''));
            if ($licence === '') {
                $trainingIds[] = null;
                $existingEndsByIndex[] = null;
                continue;
            }
            $targetUserId = $userSheet['user_id'] ?? null;
            if (!$targetUserId) {
                try {
                    $baseUrl = $_ENV["API_BASE_URL"] ?? "https://api.arctraining.fr/api";
                    $url = $baseUrl . "/users?licence_number=" . urlencode($licence);
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
            $existingTraining = null;
            if ($targetUserId && $feuilleMarqueJson !== '[]') {
                $listResp = $this->apiService->getScoredTrainings($targetUserId);
                $list = null;
                if (!empty($listResp['success']) && isset($listResp['data'])) {
                    $list = is_array($listResp['data']) ? $listResp['data'] : null;
                }
                if (is_array($list)) {
                    foreach ($list as $t) {
                        $status = $t['status'] ?? '';
                        $isSheet = !empty($t['is_score_sheet']);
                        $notes = (string)($t['notes'] ?? '');
                        if ($status === 'en_cours' && $isSheet && $notes !== '' && strpos($notes, '__FEUILLE_MARQUE__:') !== false) {
                            $start = strpos($notes, '__FEUILLE_MARQUE__:');
                            $end = $start + strlen('__FEUILLE_MARQUE__:');
                            $rest = substr($notes, $end);
                            $json = null;
                            if (strpos($rest, '{') === 0) {
                                $depth = 0;
                                $len = strlen($rest);
                                for ($i = 0; $i < $len; $i++) {
                                    if ($rest[$i] === '{') $depth++;
                                    elseif ($rest[$i] === '}') { $depth--; if ($depth === 0) { $json = substr($rest, 0, $i + 1); break; } }
                                }
                            }
                            if ($json !== null) {
                                $dec = json_decode($json, true);
                                if (is_array($dec) && isset($dec['concours_id'], $dec['depart'])
                                    && (string)($dec['concours_id'] ?? '') === (string)$concoursId
                                    && (string)($dec['depart'] ?? '') === (string)$depart
                                ) {
                                    $existingTraining = $t;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            if ($existingTraining !== null) {
                $existingTrainingId = (int)($existingTraining['id'] ?? 0);
                $trainingIds[] = $existingTrainingId;
                $endsForSheet = isset($existingTraining['ends']) && is_array($existingTraining['ends'])
                    ? $existingTraining['ends']
                    : null;
                $existingEndsByIndex[] = $endsForSheet;
                continue;
            }
            $archerName = $archerInfo['name'] ?? 'Archer';
            $notesParts = [
                'Licence: ' . ($archerInfo['licenseNumber'] ?? 'N/A'),
                'Catégorie: ' . ($archerInfo['category'] ?? 'N/A'),
            ];
            $finalNotes = implode(', ', array_filter($notesParts)) . $notesSuffix;
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
            $existingEndsByIndex[] = null;
        }
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'training_ids' => $trainingIds,
                'existing_ends_by_index' => $existingEndsByIndex,
            ],
        ]);
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

    /**
     * Charge une session (scored_training) avec ses volées et flèches pour mettre à jour la feuille de marque.
     * GET params: training_id, user_id (optionnel, pour coach/admin)
     */
    public function loadTraining() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        $trainingId = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        if ($userId === 0) {
            $userId = null;
        }
        if ($trainingId <= 0) {
            $this->sendJsonResponse(['success' => false, 'message' => 'training_id requis']);
        }
        try {
            $response = $this->apiService->getScoredTrainingByIdWithUser($trainingId, $userId ?? $_SESSION['user']['id']);
            if (!empty($response['success']) && !empty($response['data'])) {
                $this->sendJsonResponse(['success' => true, 'data' => $response['data']]);
            } else {
                $this->sendJsonResponse(['success' => false, 'message' => $response['message'] ?? 'Session non trouvée'], 404);
            }
        } catch (Exception $e) {
            error_log('loadTraining: ' . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur lors du chargement de la session'], 500);
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

    /**
     * Exporte les scores vers concours_resultats en passant par le même code que saisie-scores (storeScores).
     * POST body: { concours_id, shooting_type, depart?, serie_mode?, user_sheets: [ { inscription_id?, license_number?, score, nb_20_15?, ... } ] }
     */
    public function exportToConcours() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Non connecté'], 401);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (empty($data) || !isset($data['concours_id']) || !isset($data['user_sheets']) || !is_array($data['user_sheets'])) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Données manquantes: concours_id et user_sheets requis']);
        }
        $concoursId = (int)$data['concours_id'];
        if (empty($_SESSION['token'])) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter pour exporter vers le concours.'], 401);
        }

        $normalizeLicence = function ($lic) {
            $lic = trim((string)$lic);
            if ($lic === '') return '';
            if (strlen($lic) === 7 && ctype_digit($lic)) return '0' . $lic;
            return $lic;
        };

        $licenceToInscription = null;
        $scores = [];
        foreach ($data['user_sheets'] as $sheet) {
            $inscriptionId = isset($sheet['inscription_id']) ? (int)$sheet['inscription_id'] : 0;
            if ($inscriptionId <= 0 && !empty($sheet['license_number'])) {
                if ($licenceToInscription === null) {
                    $licenceToInscription = [];
                    try {
                        $inscResp = $this->apiService->getConcoursInscriptions($concoursId);
                        if (is_array($inscResp) && isset($inscResp['success']) && !$inscResp['success'] && isset($inscResp['message'])) {
                            $this->sendJsonResponse(['success' => false, 'message' => 'API inscriptions: ' . $inscResp['message']]);
                        }
                        $inscriptions = [];
                        $raw = $inscResp['data'] ?? $inscResp ?? [];
                        if (is_array($raw)) {
                            if (isset($raw[0]) && is_array($raw[0])) {
                                $inscriptions = $raw;
                            } elseif (isset($raw['data']) && is_array($raw['data'])) {
                                $inscriptions = $raw['data'];
                            }
                        }
                        foreach ($inscriptions as $i) {
                            $lic = $normalizeLicence($i['numero_licence'] ?? $i['numeroLicence'] ?? '');
                            if ($lic !== '') {
                                $idInsc = (int)($i['id'] ?? $i['id_inscription'] ?? 0);
                                if ($idInsc > 0) {
                                    $licenceToInscription[$lic] = $idInsc;
                                    $lic2 = (strlen($lic) === 8 && $lic[0] === '0') ? substr($lic, 1) : (strlen($lic) === 7 ? '0' . $lic : '');
                                    if ($lic2 !== '' && $lic2 !== $lic) $licenceToInscription[$lic2] = $idInsc;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $this->sendJsonResponse(['success' => false, 'message' => 'Impossible de récupérer les inscriptions: ' . $e->getMessage()]);
                    }
                }
                if (is_array($licenceToInscription)) {
                    $lic = $normalizeLicence($sheet['license_number']);
                    $inscriptionId = $licenceToInscription[$lic] ?? 0;
                    if ($inscriptionId <= 0 && $lic !== '') {
                        $licAlt = (strlen($lic) === 8 && $lic[0] === '0') ? substr($lic, 1) : (strlen($lic) === 7 ? '0' . $lic : '');
                        if ($licAlt !== '') $inscriptionId = $licenceToInscription[$licAlt] ?? 0;
                    }
                }
            }
            if ($inscriptionId <= 0) {
                continue;
            }
            // Format identique au formulaire saisie-scores (scores[inscription_id][champ])
            $scores[$inscriptionId] = [
                'score' => array_key_exists('score', $sheet) ? (string)$sheet['score'] : '',
                'nb_20_15' => array_key_exists('nb_20_15', $sheet) ? (string)$sheet['nb_20_15'] : '',
                'nb_20_10' => array_key_exists('nb_20_10', $sheet) ? (string)$sheet['nb_20_10'] : '',
                'nb_15_15' => array_key_exists('nb_15_15', $sheet) ? (string)$sheet['nb_15_15'] : '',
                'nb_15_10' => array_key_exists('nb_15_10', $sheet) ? (string)$sheet['nb_15_10'] : '',
                'nb_15' => array_key_exists('nb_15', $sheet) ? (string)$sheet['nb_15'] : '',
                'nb_10' => array_key_exists('nb_10', $sheet) ? (string)$sheet['nb_10'] : '',
                'nb_0' => array_key_exists('nb_0', $sheet) ? (string)$sheet['nb_0'] : '',
                'serie1_score' => array_key_exists('serie1_score', $sheet) ? (string)$sheet['serie1_score'] : '',
                'serie1_nb_10' => array_key_exists('serie1_nb_10', $sheet) ? (string)$sheet['serie1_nb_10'] : '',
                'serie1_nb_9' => array_key_exists('serie1_nb_9', $sheet) ? (string)$sheet['serie1_nb_9'] : '',
                'serie2_score' => array_key_exists('serie2_score', $sheet) ? (string)$sheet['serie2_score'] : '',
                'serie2_nb_10' => array_key_exists('serie2_nb_10', $sheet) ? (string)$sheet['serie2_nb_10'] : '',
                'serie2_nb_9' => array_key_exists('serie2_nb_9', $sheet) ? (string)$sheet['serie2_nb_9'] : '',
            ];
        }

        if (empty($scores)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Aucun score à exporter. Vérifiez que les archers ont été préremplis depuis le concours (départ + cible/peloton) et ont des inscriptions valides.']);
        }

        $_POST['serie_mode'] = $data['serie_mode'] ?? 'both';
        $_POST['depart'] = $data['depart'] ?? '';
        $_POST['format'] = $data['format'] ?? '';

        error_log('exportToConcours: nb_scores=' . count($scores) . ', inscription_ids=' . implode(',', array_keys($scores)));

        $concoursController = new ConcoursController();
        $concoursController->storeScores($concoursId, $scores, true);
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

