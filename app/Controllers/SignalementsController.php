<?php
// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';
require_once __DIR__ . '/../Middleware/SessionGuard.php';

class SignalementsController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    /**
     * Liste tous les signalements
     */
    public function index() {
        error_log("SignalementsController::index() - Début");
        
        // Vérifier la session
        SessionGuard::check();
        
        $title = 'Gestion des Signalements - Portail Archers de Gémenos';
        
        // Récupérer les paramètres de filtrage
        $status = $_GET['status'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Construire les paramètres de requête
        $queryParams = [];
        if ($status) {
            $queryParams['status'] = $status;
        }
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = $offset;
        
        // Récupérer les signalements
        $reportsData = [
            'reports' => [],
            'total' => 0,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        try {
            $reportsResponse = $this->apiService->makeRequest('reports?' . http_build_query($queryParams), 'GET');
            if ($reportsResponse['success'] && !empty($reportsResponse['data'])) {
                $reportsData = [
                    'reports' => $reportsResponse['data']['reports'] ?? [],
                    'total' => $reportsResponse['data']['total'] ?? 0,
                    'limit' => $reportsResponse['data']['limit'] ?? $limit,
                    'offset' => $reportsResponse['data']['offset'] ?? $offset
                ];
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des signalements: ' . $e->getMessage());
        }
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/signalements.js'];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue de la liste des signalements
        include 'app/Views/signalements/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Affiche le détail d'un signalement
     */
    public function show($id) {
        error_log("SignalementsController::show($id) - Début");
        
        // Vérifier la session
        SessionGuard::check();
        
        $title = 'Détails du Signalement - Portail Archers de Gémenos';
        
        // Récupérer le signalement
        $report = null;
        
        try {
            $reportsResponse = $this->apiService->makeRequest('reports', 'GET');
            if ($reportsResponse['success'] && !empty($reportsResponse['data']['reports'])) {
                foreach ($reportsResponse['data']['reports'] as $r) {
                    if ($r['id'] == $id) {
                        $report = $r;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération du signalement: ' . $e->getMessage());
        }
        
        if (!$report) {
            // Rediriger vers la liste si le signalement n'existe pas
            header('Location: /signalements');
            exit;
        }
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/signalement-detail.js'];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue du détail du signalement
        include 'app/Views/signalements/show.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Met à jour le statut d'un signalement
     */
    public function update($id) {
        error_log("SignalementsController::update($id) - Début");
        
        // Vérifier la session
        SessionGuard::check();
        
        // Récupérer les données POST
        $status = $_POST['status'] ?? null;
        $adminNotes = $_POST['admin_notes'] ?? null;
        
        if (!$status) {
            $_SESSION['error'] = 'Le statut est requis';
            header('Location: /signalements/' . $id);
            exit;
        }
        
        try {
            $updateData = [
                'status' => $status,
                'adminNotes' => $adminNotes
            ];
            
            $response = $this->apiService->makeRequest('reports/' . $id, 'PUT', $updateData);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Signalement mis à jour avec succès';
            } else {
                $_SESSION['error'] = $response['error'] ?? 'Erreur lors de la mise à jour';
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour du signalement: ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur serveur lors de la mise à jour';
        }
        
        header('Location: /signalements/' . $id);
        exit;
    }
}
