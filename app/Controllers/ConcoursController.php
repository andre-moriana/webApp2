
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
        $clubs = [];
        $disciplines = []; // Initialiser à un tableau vide par défaut
        $typeCompetitions = []; // Initialiser à un tableau vide par défaut
        
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
        
        try {
            // Récupérer les clubs
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $payload = $this->apiService->unwrapData($clubsResponse);
            
            error_log('Clubs response success: ' . ($clubsResponse['success'] ? 'true' : 'false'));
            error_log('Payload is array: ' . (is_array($payload) ? 'true' : 'false'));
            error_log('Payload count: ' . (is_array($payload) ? count($payload) : 0));
            
            if ($clubsResponse['success'] && is_array($payload)) {
                // Normaliser l'ID de chaque club
                foreach ($payload as &$club) {
                    if (!isset($club['id']) && isset($club['_id'])) {
                        $club['id'] = $club['_id'];
                    }
                }
                unset($club); // Libérer la référence
                
                // Filtrer les clubs : exclure ceux dont le nameShort se termine par "000"
                $filtered = array_filter($payload, function($club) {
                    $nameShort = (string)($club['nameShort'] ?? $club['name_short'] ?? '');
                    return $nameShort === '' || substr($nameShort, -3) !== '000';
                });
                
                // Réindexer le tableau pour avoir des clés séquentielles
                $clubs = array_values($filtered);
                
                error_log('Clubs filtrés: ' . count($clubs));
            } else {
                error_log('Erreur dans la réponse clubs: ' . ($clubsResponse['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log('Exception lors de la récupération des clubs: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        
        try {
            // Récupérer les disciplines depuis la table concour_discipline
            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            
            error_log('=== RÉCUPÉRATION DISCIPLINES ===');
            error_log('Disciplines response success: ' . ($disciplinesResponse['success'] ? 'true' : 'false'));
            
            // makeRequest retourne { success, data: { success, data: [...] } }
            // Il faut unwrap deux fois
            $firstUnwrap = $this->apiService->unwrapData($disciplinesResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $disciplinesPayload = $firstUnwrap['data'];
            } else {
                $disciplinesPayload = $firstUnwrap;
            }
            
            error_log('Disciplines payload is array: ' . (is_array($disciplinesPayload) ? 'true' : 'false'));
            error_log('Disciplines payload count: ' . (is_array($disciplinesPayload) ? count($disciplinesPayload) : 0));
            
            if ($disciplinesResponse['success'] && is_array($disciplinesPayload)) {
                // Normaliser l'ID de chaque discipline
                foreach ($disciplinesPayload as &$discipline) {
                    if (!isset($discipline['id']) && isset($discipline['_id'])) {
                        $discipline['id'] = $discipline['_id'];
                    }
                }
                unset($discipline); // Libérer la référence
                
                // Réindexer le tableau pour avoir des clés séquentielles
                $disciplines = array_values($disciplinesPayload);
                
                error_log('Disciplines récupérées: ' . count($disciplines));
                if (count($disciplines) > 0) {
                    error_log('Première discipline: ' . json_encode($disciplines[0], JSON_UNESCAPED_UNICODE));
                }
            } else {
                error_log('Erreur dans la réponse disciplines: ' . ($disciplinesResponse['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log('Exception lors de la récupération des disciplines: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        
        try {
            // Récupérer les types de compétition depuis la table concour_type_competition
            $typeCompetitionsResponse = $this->apiService->makeRequest('concours/type-competitions', 'GET');
            
            error_log('Type competitions response success: ' . ($typeCompetitionsResponse['success'] ? 'true' : 'false'));
            
            // makeRequest retourne { success, data: { success, data: [...] } }
            // Il faut unwrap deux fois
            $firstUnwrap = $this->apiService->unwrapData($typeCompetitionsResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $typeCompetitionsPayload = $firstUnwrap['data'];
            } else {
                $typeCompetitionsPayload = $firstUnwrap;
            }
            
            error_log('Type competitions payload is array: ' . (is_array($typeCompetitionsPayload) ? 'true' : 'false'));
            error_log('Type competitions payload count: ' . (is_array($typeCompetitionsPayload) ? count($typeCompetitionsPayload) : 0));
            
            if ($typeCompetitionsResponse['success'] && is_array($typeCompetitionsPayload)) {
                // Normaliser l'ID de chaque type de compétition
                foreach ($typeCompetitionsPayload as &$typeCompetition) {
                    if (!isset($typeCompetition['id']) && isset($typeCompetition['_id'])) {
                        $typeCompetition['id'] = $typeCompetition['_id'];
                    }
                }
                unset($typeCompetition); // Libérer la référence
                
                // Réindexer le tableau pour avoir des clés séquentielles
                $typeCompetitions = array_values($typeCompetitionsPayload);
                
                error_log('Types de compétition récupérés: ' . count($typeCompetitions));
            } else {
                error_log('Erreur dans la réponse type competitions: ' . ($typeCompetitionsResponse['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log('Exception lors de la récupération des types de compétition: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }

        // Log final avant de passer à la vue
        error_log('=== AVANT INCLUSION VUE ===');
        error_log('Nombre de disciplines à passer à la vue: ' . count($disciplines));
        error_log('Type de $disciplines: ' . gettype($disciplines));
        error_log('Est un tableau: ' . (is_array($disciplines) ? 'oui' : 'non'));
        if (count($disciplines) > 0) {
            error_log('Première discipline (avant vue): ' . json_encode($disciplines[0], JSON_UNESCAPED_UNICODE));
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
