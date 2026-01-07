
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
    public function create()    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
         $themes = [];
        try {
            // Récupérer les thèmes
            $themesResponse = $this->apiService->makeRequest('themes/list', 'GET');
            if ($themesResponse['success'] && isset($themesResponse['data'])) {
                $themes = is_array($themesResponse['data']) ? $themesResponse['data'] : [];
            }
        } catch (Exception $e) {
            // En cas d'erreur, continuer avec un tableau vide
            error_log('Erreur lors de la récupération des thèmes: ' . $e->getMessage());
        }

        $title = 'Créer un concours - Portail Archers de Gémenos';
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/create.php';
        include 'app/Views/layouts/footer.php';

    }

    // Enregistrement d'un nouveau concours
    public function store()
    {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Optionally handle errors here
        header('Location: /concours');
        exit();
    }
        $nom = $_POST['nom'] ?? '';
        $description = $_POST['description'] ?? '';
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $lieu = $_POST['lieu'] ?? '';
        $type = $_POST['type'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du concours est requis';
            header('Location: /concours/create');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest('concours/create', 'POST', [
                'nom' => $nom,
                'nameShort' => $nameShort,
                'description' => $description,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'lieu' => $lieu,
                'type' => $type,
                'status' => $status
            ]);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Concours créé avec succès';
                header('Location: /concours');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la création du concours';
                header('Location: /concours/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du concours: ' . $e->getMessage();
            header('Location: /concours/create');
            exit;
        }
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
