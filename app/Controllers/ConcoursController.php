
<?php

require_once __DIR__ . '/../Services/ApiService.php';

class ConcoursController {
    private $apiService;

    public function __construct() {
        $this->apiService = new ApiService();
    }

    public function index() {
        // Vider le cache opcache temporairement
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        $concours = [];

     //   $response = $this->apiService->getConcours();

        // DEBUG: Afficher l'utilisateur
        $debugUser = $_SESSION['user'] ?? [];

        try {
            $response = $this->apiService->makeRequest('concours/list', 'GET');
            $payload = $this->apiService->unwrapData($response);

            if ($response['success'] && is_array($payload)) {
                 foreach ($payload as &$concours) {
                    if (!isset($concours['id']) && isset($concours['_id'])) {
                        $concours['id'] = $concours['_id'];
                    }
                }
                $concours = $payload;
            } else {
                $error = $response['message'] ?? 'Erreur lors de la récupération des concours';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération des concours: ' . $e->getMessage();
        }
        $title = 'Gestion des concours - Portail Arc Training';
        
        // Définir les fichiers JS spécifiques
        //$additionalJS = ['/public/assets/js/clubs-table.js'];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/index.php';
        include 'app/Views/layouts/footer.php';
    }
    // Affichage du formulaire de création
    public function create()
    {
        require __DIR__ . '/../Views/concours/create.php';
    }

    // Enregistrement d'un nouveau concours
    public function store()
    {
        $data = $_POST;
        $response = $this->apiService->createConcours($data);
        // Optionally handle errors here
        header('Location: /concours');
        exit();
    }

    // Affichage du formulaire d'édition
    public function edit($id)
    {
        $concours = null;
        $response = $this->apiService->getConcoursById($id);
        if ($response['success'] && isset($response['data'])) {
            $concours = new Concours($response['data']);
        } else {
            echo '<div class="alert alert-danger">Impossible de contacter l’API concours. Vérifiez la connexion ou l’URL de l’API.</div>';
        }
        require __DIR__ . '/../Views/concours/edit.php';
    }

    // Mise à jour d'un concours
    public function update($id)
    {
        $data = $_POST;
        $response = $this->apiService->updateConcours($id, $data);
        // Optionally handle errors here
        header('Location: /concours');
        exit();
    }

    // Suppression d'un concours
    public function delete($id)
    {
        $response = $this->apiService->deleteConcours($id);
        // Optionally handle errors here
        header('Location: /concours');
        exit();
    }

    // Méthode utilitaire pour récupérer les concours via l'API
    // plus de méthode fetchConcoursFromApi : tout passe par ApiService
}
