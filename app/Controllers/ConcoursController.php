
<?php

require_once __DIR__ . '/../Services/ApiService.php';
use App\Models\Concours;

class ConcoursController
{
    // Liste des concours (récupérés via l'API BackendPHP)

    private $apiService;

    public function __construct() {
        $this->apiService = new ApiService();
    }

    public function index()
    {
        $response = $this->apiService->getConcours();
        $concours = [];
        if ($response['success'] && isset($response['data'])) {
            $concours = array_map(fn($c) => new Concours($c), $response['data']);
        } else {
            echo '<div class="alert alert-danger">Impossible de contacter l’API concours. Vérifiez la connexion ou l’URL de l’API.</div>';
        }
        require __DIR__ . '/../Views/concours/index.php';
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
