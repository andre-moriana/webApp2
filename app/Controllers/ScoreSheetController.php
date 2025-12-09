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
        
        $pageTitle = 'Feuille de marque - Portail Archers de Gémenos';
        
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
                        $baseUrl = $_ENV["API_BASE_URL"] ?? "http://82.67.123.22:25000/api";
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
                
                // Créer le titre
                $finalTitle = $trainingTitle 
                    ? $trainingTitle . ' - ' . $archerName
                    : $shootingType . ' - ' . $archerName . ' - ' . date('d/m/Y');
                
                // Créer le tir compté
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
                    
                    // Ajouter les volées
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
                        
                        $endData = [
                            'end_number' => $row['end_number'],
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
    
    private function sendJsonResponse($data) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

