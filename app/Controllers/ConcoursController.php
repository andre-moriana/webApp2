
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
        $niveauChampionnat = []; // Initialiser à un tableau vide par défaut
        
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
        
        try {
            // Récupérer les niveaux de championnat depuis la table concour_niveau_championnat
            $niveauChampionnatResponse = $this->apiService->makeRequest('concours/niveau-championnat', 'GET');
            
            error_log('Niveau championnat response success: ' . ($niveauChampionnatResponse['success'] ? 'true' : 'false'));
            
            // makeRequest retourne { success, data: { success, data: [...] } }
            // Il faut unwrap deux fois
            $firstUnwrap = $this->apiService->unwrapData($niveauChampionnatResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $niveauChampionnatPayload = $firstUnwrap['data'];
            } else {
                $niveauChampionnatPayload = $firstUnwrap;
            }
            
            error_log('Niveau championnat payload is array: ' . (is_array($niveauChampionnatPayload) ? 'true' : 'false'));
            error_log('Niveau championnat payload count: ' . (is_array($niveauChampionnatPayload) ? count($niveauChampionnatPayload) : 0));
            
            if ($niveauChampionnatResponse['success'] && is_array($niveauChampionnatPayload)) {
                // Normaliser l'ID de chaque niveau
                foreach ($niveauChampionnatPayload as &$niveau) {
                    if (!isset($niveau['id']) && isset($niveau['_id'])) {
                        $niveau['id'] = $niveau['_id'];
                    }
                }
                unset($niveau); // Libérer la référence
                
                // Réindexer le tableau pour avoir des clés séquentielles
                $niveauChampionnat = array_values($niveauChampionnatPayload);
                
                error_log('Niveaux de championnat récupérés: ' . count($niveauChampionnat));
            } else {
                error_log('Erreur dans la réponse niveau championnat: ' . ($niveauChampionnatResponse['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log('Exception lors de la récupération des niveaux de championnat: ' . $e->getMessage());
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
        // Debug: Afficher dans la session pour voir si la méthode est appelée
        $_SESSION['debug_concours_store'] = [
            'called' => true,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Méthode non autorisée. Méthode reçue: ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A');
            header('Location: /concours');
            exit();
        }
        
        // Récupérer tous les champs du formulaire
        $titre_competition = $_POST['titre_competition'] ?? '';
        $lieu_competition = $_POST['lieu_competition'] ?? '';
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $club_organisateur = $_POST['club_organisateur'] ?? '';
        $discipline = $_POST['discipline'] ?? '';
        $type_competition = $_POST['type_competition'] ?? '';
        $niveau_championnat = $_POST['niveau_championnat'] ?? '';
        $niveau_championnat_autre = $_POST['niveau_championnat_autre'] ?? '';
        $nombre_cibles = $_POST['nombre_cibles'] ?? 0;
        $nombre_depart = $_POST['nombre_depart'] ?? 1;
        $nombre_tireurs_par_cibles = $_POST['nombre_tireurs_par_cibles'] ?? 0;
        $type_concours = $_POST['type_concours'] ?? 'ouvert';
        $duel = isset($_POST['duel']) ? 1 : 0;
        $division_equipe = $_POST['division_equipe'] ?? 'duels_equipes';
        $code_authentification = $_POST['code_authentification'] ?? '';
        $type_publication_internet = $_POST['type_publication_internet'] ?? '';
        
        // Debug: Stocker les données POST reçues
        $_SESSION['debug_concours_store']['post_data'] = $_POST;
        
        // Validation des champs requis
        if (empty($titre_competition)) {
            $_SESSION['error'] = 'Le titre de la compétition est requis';
            $_SESSION['debug_concours_store']['validation_failed'] = 'titre_competition';
            header('Location: /concours/create');
            exit;
        }
        
        if (empty($date_debut) || empty($date_fin)) {
            $_SESSION['error'] = 'Les dates de début et de fin sont requises';
            $_SESSION['debug_concours_store']['validation_failed'] = 'dates';
            header('Location: /concours/create');
            exit;
        }
        
        if (empty($lieu_competition)) {
            $_SESSION['error'] = 'Le lieu de la compétition est requis';
            $_SESSION['debug_concours_store']['validation_failed'] = 'lieu_competition';
            header('Location: /concours/create');
            exit;
        }

        $_SESSION['debug_concours_store']['validation_passed'] = true;

        try {
            $_SESSION['debug_concours_store']['api_call_started'] = true;
            // Préparer les données pour l'API
            // Utiliser titre_competition comme nom pour la compatibilité avec la table actuelle
            $data = [
                'nom' => $titre_competition,
                'description' => '', // Peut être ajouté plus tard si nécessaire
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'lieu' => $lieu_competition,
                'type' => $type_competition ?? '',
                'statut' => 'active', // Par défaut
                // Nouveaux champs (seront ajoutés à la table si nécessaire)
                'titre_competition' => $titre_competition,
                'club_organisateur' => $club_organisateur,
                'discipline' => $discipline,
                'type_competition' => $type_competition,
                'niveau_championnat' => $niveau_championnat,
                'niveau_championnat_autre' => $niveau_championnat_autre,
                'nombre_cibles' => (int)$nombre_cibles,
                'nombre_depart' => (int)$nombre_depart,
                'nombre_tireurs_par_cibles' => (int)$nombre_tireurs_par_cibles,
                'type_concours' => $type_concours,
                'duel' => $duel,
                'division_equipe' => $division_equipe,
                'code_authentification' => $code_authentification,
                'type_publication_internet' => $type_publication_internet
            ];
            
            error_log('Données à envoyer à l\'API: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // L'endpoint est 'concours' (pas 'concours/create') car le routing se fait via PATH_INFO
            $_SESSION['debug_concours_store']['api_endpoint'] = 'concours';
            $_SESSION['debug_concours_store']['api_method'] = 'POST';
            $_SESSION['debug_concours_store']['api_data_sent'] = $data;
            
            $response = $this->apiService->makeRequest('concours', 'POST', $data);
            
            $_SESSION['debug_concours_store']['api_response'] = $response;
            $_SESSION['debug_concours_store']['api_response_raw'] = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            // makeRequest retourne { success: bool, data: {...}, status_code: int, message: string }
            // où data contient la réponse JSON de l'API { success: bool, data: {...}, message: string }
            $apiResponse = $response['data'] ?? null;
            
            // Debug: Stocker la réponse complète
            $_SESSION['debug_concours_store']['api_response_full'] = $response;
            $_SESSION['debug_concours_store']['api_response_data'] = $apiResponse;
            
            // Vérifier le succès HTTP ET le succès de l'opération dans la réponse API
            if ($response['success'] && isset($apiResponse) && is_array($apiResponse)) {
                // La réponse API est dans $response['data']
                if (isset($apiResponse['success']) && $apiResponse['success']) {
                    $_SESSION['debug_concours_store']['api_success'] = true;
                    $_SESSION['success'] = $apiResponse['message'] ?? 'Concours créé avec succès';
                    header('Location: /concours');
                    exit;
                } else {
                    // L'API a retourné une erreur - extraire le message d'erreur détaillé
                    $errorMessage = $apiResponse['error'] ?? $apiResponse['message'] ?? 'Erreur lors de la création du concours';
                    
                    // Si le message contient des détails de debug, les inclure
                    if (isset($apiResponse['debug'])) {
                        $errorMessage .= ' - ' . json_encode($apiResponse['debug'], JSON_UNESCAPED_UNICODE);
                    }
                    
                    $_SESSION['error'] = $errorMessage;
                    $_SESSION['debug_concours_store']['api_error'] = $errorMessage;
                    $_SESSION['debug_concours_store']['api_response_data'] = $apiResponse;
                    header('Location: /concours/create');
                    exit;
                }
            } else {
                // Erreur HTTP ou problème de décodage
                $errorMessage = $response['message'] ?? 'Erreur lors de la communication avec l\'API';
                
                // Si l'API a retourné un message d'erreur dans data, l'utiliser
                if (isset($apiResponse['error'])) {
                    $errorMessage = $apiResponse['error'];
                } elseif (isset($apiResponse['message'])) {
                    $errorMessage = $apiResponse['message'];
                }
                
                $_SESSION['error'] = $errorMessage;
                $_SESSION['debug_concours_store']['http_error'] = $errorMessage;
                $_SESSION['debug_concours_store']['http_status'] = $response['status_code'] ?? 'inconnu';
                header('Location: /concours/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['debug_concours_store']['exception'] = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
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
