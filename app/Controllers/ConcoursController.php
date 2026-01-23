
<?php

require_once 'app/Config/PermissionHelper.php';
require_once 'app/Services/PermissionService.php';

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
            // Vérifier la permission de voir les utilisateurs
            $clubId = $_SESSION['user']['clubId'] ?? null;
            PermissionHelper::requirePermission(
                PermissionService::RESOURCE_USERS_ALL,
                PermissionService::ACTION_VIEW,
                $clubId
            );

        // Nettoyer les messages d'erreur de session
        unset($_SESSION['error']);
        unset($_SESSION['success']);

        $concours = [];
        $error = null;
        $clubsMap = []; // Mapping club_organisateur ID -> nom du club

        try {
            // Récupérer les clubs pour mapper les IDs aux noms
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $clubsPayload = $this->apiService->unwrapData($clubsResponse);
            
            if ($clubsResponse['success'] && is_array($clubsPayload)) {
                foreach ($clubsPayload as $club) {
                    $clubId = $club['id'] ?? $club['_id'] ?? null;
                    if ($clubId) {
                        $clubsMap[$clubId] = [
                            'name' => $club['name'] ?? '',
                            'nameShort' => $club['nameShort'] ?? $club['name_short'] ?? ''
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des clubs: ' . $e->getMessage());
        }

        try {
            // Récupérer les concours depuis l'API
            $response = $this->apiService->getConcours();

            // Vérifier le format de la réponse
            // makeRequest retourne { success: bool, data: {...}, status_code: int }
            // où data contient la réponse JSON de l'API
            $apiResponse = $response['data'] ?? null;

            // L'API /concours retourne directement un tableau de concours, pas { concours: [...] }
            if ($response['success'] && isset($apiResponse) && is_array($apiResponse)) {
                // Si apiResponse est directement un tableau, l'utiliser
                if (isset($apiResponse[0]) && is_array($apiResponse[0])) {
                    $concours = $apiResponse;
                } elseif (isset($apiResponse['concours']) && is_array($apiResponse['concours'])) {
                    $concours = $apiResponse['concours'];
                } else {
                    // Format inattendu
                    error_log('Format de réponse inattendu pour getConcours(): ' . json_encode($apiResponse, JSON_UNESCAPED_UNICODE));
                    $concours = [];
                }
                
                // Enrichir les concours avec les noms de clubs
                foreach ($concours as &$c) {
                    $clubId = $c['club_organisateur'] ?? null;
                    if ($clubId && isset($clubsMap[$clubId])) {
                        $c['club_name'] = $clubsMap[$clubId]['name'];
                        $c['club_nameShort'] = $clubsMap[$clubId]['nameShort'];
                    } else {
                        $c['club_name'] = '';
                        $c['club_nameShort'] = '';
                    }
                }
                unset($c); // Libérer la référence

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
        
        // Nettoyer les données de debug de la session
        if (isset($_SESSION['debug_concours_store'])) {
            unset($_SESSION['debug_concours_store']);
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
            error_log('=== RÉCUPÉRATION DISCIPLINES - Début ===');
            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            
            error_log('=== RÉCUPÉRATION DISCIPLINES ===');
            error_log('Disciplines response success: ' . ($disciplinesResponse['success'] ? 'true' : 'false'));
            error_log('Disciplines response status_code: ' . ($disciplinesResponse['status_code'] ?? 'N/A'));
            error_log('Disciplines response message: ' . ($disciplinesResponse['message'] ?? 'N/A'));
            if (isset($disciplinesResponse['data']) && is_array($disciplinesResponse['data'])) {
                error_log('Disciplines response data keys: ' . implode(', ', array_keys($disciplinesResponse['data'])));
            }
            
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
            error_log('=== RÉCUPÉRATION NIVEAU CHAMPIONNAT - Début ===');
            $niveauChampionnatResponse = $this->apiService->makeRequest('concours/niveau-championnat', 'GET');
            
            error_log('Niveau championnat response success: ' . ($niveauChampionnatResponse['success'] ? 'true' : 'false'));
            error_log('Niveau championnat response status_code: ' . ($niveauChampionnatResponse['status_code'] ?? 'N/A'));
            error_log('Niveau championnat response message: ' . ($niveauChampionnatResponse['message'] ?? 'N/A'));
            
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
        $club_code = $_POST['club_code'] ?? ''; // nameShort du club
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
        
        // Validation des champs requis
        if (empty($titre_competition)) {
            $_SESSION['error'] = 'Le titre de la compétition est requis';
            header('Location: /concours/create');
            exit;
        }
        
        if (empty($date_debut) || empty($date_fin)) {
            $_SESSION['error'] = 'Les dates de début et de fin sont requises';
            header('Location: /concours/create');
            exit;
        }
        
        if (empty($lieu_competition)) {
            $_SESSION['error'] = 'Le lieu de la compétition est requis';
            header('Location: /concours/create');
            exit;
        }

        try {
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
                'type_publication_internet' => $type_publication_internet,
                'agreenum' => $club_code // nameShort du club organisateur
            ];
            
            // L'endpoint est 'concours' (pas 'concours/create') car le routing se fait via PATH_INFO
            $response = $this->apiService->makeRequest('concours', 'POST', $data);
            
            // makeRequest retourne { success: bool, data: {...}, status_code: int, message: string }
            // où data contient la réponse JSON de l'API { success: bool, data: {...}, message: string }
            $apiResponse = $response['data'] ?? null;
            
            // Vérifier le succès HTTP ET le succès de l'opération dans la réponse API
            if ($response['success'] && isset($apiResponse) && is_array($apiResponse)) {
                // La réponse API est dans $response['data']
                if (isset($apiResponse['success']) && $apiResponse['success']) {
                    $_SESSION['success'] = $apiResponse['message'] ?? 'Concours créé avec succès';
                    header('Location: /concours');
                    exit;
                } else {
                    // L'API a retourné une erreur - extraire le message d'erreur détaillé
                    $errorMessage = $apiResponse['error'] ?? $apiResponse['message'] ?? 'Erreur lors de la création du concours';
                    $_SESSION['error'] = $errorMessage;
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
                header('Location: /concours/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du concours: ' . $e->getMessage();
            header('Location: /concours/create');
            exit;
        }
    }
    
    // Affichage d'un concours (lecture seule)
    public function show($id)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Nettoyer les messages d'erreur de session
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $concours = null;
        $inscriptions = [];
        $clubs = [];
        $disciplines = [];
        $typeCompetitions = [];
        $niveauChampionnat = [];
        
        // Récupérer le concours
        $response = $this->apiService->getConcoursById($id);
        if ($response['success'] && isset($response['data'])) {
            $concours = (object) $response['data'];
        } else {
            $_SESSION['error'] = 'Impossible de récupérer le concours.';
            header('Location: /concours');
            exit;
        }
        
        // Récupérer les inscriptions
        try {
            $inscriptionsResponse = $this->apiService->makeRequest("concours/{$id}/inscriptions", 'GET');
            if ($inscriptionsResponse['success'] && isset($inscriptionsResponse['data'])) {
                $inscriptions = is_array($inscriptionsResponse['data']) ? $inscriptionsResponse['data'] : [];
            } else {
                // Si la réponse n'est pas dans data, essayer directement
                $inscriptions = is_array($inscriptionsResponse) && !isset($inscriptionsResponse['success']) 
                    ? $inscriptionsResponse 
                    : [];
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des inscriptions: ' . $e->getMessage());
        }
        
        // Charger les données pour afficher les libellés
        try {
            // Clubs
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $clubsPayload = $this->apiService->unwrapData($clubsResponse);
            if ($clubsResponse['success'] && is_array($clubsPayload)) {
                foreach ($clubsPayload as &$club) {
                    if (!isset($club['id']) && isset($club['_id'])) {
                        $club['id'] = $club['_id'];
                    }
                }
                unset($club);
                $clubs = array_values($clubsPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des clubs: ' . $e->getMessage());
        }
        
        try {
            // Disciplines
            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
            if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                $disciplinesPayload = $disciplinesPayload['data'];
            }
            if ($disciplinesResponse['success'] && is_array($disciplinesPayload)) {
                foreach ($disciplinesPayload as &$discipline) {
                    if (!isset($discipline['id']) && isset($discipline['_id'])) {
                        $discipline['id'] = $discipline['_id'];
                    }
                }
                unset($discipline);
                $disciplines = array_values($disciplinesPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des disciplines: ' . $e->getMessage());
        }
        
        try {
            // Types de compétition
            $typeCompetitionsResponse = $this->apiService->makeRequest('concours/type-competitions', 'GET');
            $typeCompetitionsPayload = $this->apiService->unwrapData($typeCompetitionsResponse);
            if (is_array($typeCompetitionsPayload) && isset($typeCompetitionsPayload['data']) && isset($typeCompetitionsPayload['success'])) {
                $typeCompetitionsPayload = $typeCompetitionsPayload['data'];
            }
            if ($typeCompetitionsResponse['success'] && is_array($typeCompetitionsPayload)) {
                foreach ($typeCompetitionsPayload as &$typeComp) {
                    if (!isset($typeComp['id']) && isset($typeComp['_id'])) {
                        $typeComp['id'] = $typeComp['_id'];
                    }
                }
                unset($typeComp);
                $typeCompetitions = array_values($typeCompetitionsPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des types de compétition: ' . $e->getMessage());
        }
        
        try {
            // Niveaux de championnat
            $niveauChampionnatResponse = $this->apiService->makeRequest('concours/niveau-championnat', 'GET');
            $niveauChampionnatPayload = $this->apiService->unwrapData($niveauChampionnatResponse);
            if (is_array($niveauChampionnatPayload) && isset($niveauChampionnatPayload['data']) && isset($niveauChampionnatPayload['success'])) {
                $niveauChampionnatPayload = $niveauChampionnatPayload['data'];
            }
            if ($niveauChampionnatResponse['success'] && is_array($niveauChampionnatPayload)) {
                foreach ($niveauChampionnatPayload as &$niveau) {
                    if (!isset($niveau['id']) && isset($niveau['_id'])) {
                        $niveau['id'] = $niveau['_id'];
                    }
                }
                unset($niveau);
                $niveauChampionnat = array_values($niveauChampionnatPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des niveaux de championnat: ' . $e->getMessage());
        }
        
        $title = 'Détails du concours - Portail Archers de Gémenos';
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/show.php';
        include 'app/Views/layouts/footer.php';
    }

    // Affichage du formulaire d'édition
    public function edit($id)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Nettoyer les messages d'erreur de session
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $concours = null;
        $themes = [];
        $clubs = [];
        $disciplines = [];
        $typeCompetitions = [];
        $niveauChampionnat = [];
        
        // Récupérer le concours à éditer
        $response = $this->apiService->getConcoursById($id);
        if ($response['success'] && isset($response['data'])) {
            // Convertir le tableau en objet pour compatibilité avec la vue qui utilise $concours->id, etc.
            $concours = (object) $response['data'];
        } else {
            $_SESSION['error'] = 'Impossible de contacter l\'API concours. Vérifiez la connexion ou l\'URL de l\'API.';
            header('Location: /concours');
            exit;
        }
        
        // Charger les mêmes données que create() pour le formulaire
        try {
            // Récupérer les thèmes
            $themesResponse = $this->apiService->makeRequest('themes/list', 'GET');
            if ($themesResponse['success'] && isset($themesResponse['data'])) {
                $themes = is_array($themesResponse['data']) ? $themesResponse['data'] : [];
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des thèmes: ' . $e->getMessage());
        }
        
        try {
            // Récupérer les clubs
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $payload = $this->apiService->unwrapData($clubsResponse);
            
            if ($clubsResponse['success'] && is_array($payload)) {
                // Normaliser l'ID de chaque club
                foreach ($payload as &$club) {
                    if (!isset($club['id']) && isset($club['_id'])) {
                        $club['id'] = $club['_id'];
                    }
                }
                unset($club);
                
                // Filtrer les clubs : exclure ceux dont le nameShort se termine par "000"
                $filtered = array_filter($payload, function($club) {
                    $nameShort = (string)($club['nameShort'] ?? $club['name_short'] ?? '');
                    return $nameShort === '' || substr($nameShort, -3) !== '000';
                });
                
                $clubs = array_values($filtered);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des clubs: ' . $e->getMessage());
        }
        
        try {
            // Récupérer les disciplines
            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            $firstUnwrap = $this->apiService->unwrapData($disciplinesResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $disciplinesPayload = $firstUnwrap['data'];
            } else {
                $disciplinesPayload = $firstUnwrap;
            }
            
            if ($disciplinesResponse['success'] && is_array($disciplinesPayload)) {
                // Normaliser l'ID de chaque discipline
                foreach ($disciplinesPayload as &$discipline) {
                    if (!isset($discipline['id']) && isset($discipline['_id'])) {
                        $discipline['id'] = $discipline['_id'];
                    }
                }
                unset($discipline);
                
                $disciplines = array_values($disciplinesPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des disciplines: ' . $e->getMessage());
        }
        
        try {
            // Récupérer les types de compétition
            $typeCompetitionsResponse = $this->apiService->makeRequest('concours/type-competitions', 'GET');
            $firstUnwrap = $this->apiService->unwrapData($typeCompetitionsResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $typeCompetitionsPayload = $firstUnwrap['data'];
            } else {
                $typeCompetitionsPayload = $firstUnwrap;
            }
            
            if ($typeCompetitionsResponse['success'] && is_array($typeCompetitionsPayload)) {
                // Normaliser l'ID de chaque type de compétition
                foreach ($typeCompetitionsPayload as &$typeCompetition) {
                    if (!isset($typeCompetition['id']) && isset($typeCompetition['_id'])) {
                        $typeCompetition['id'] = $typeCompetition['_id'];
                    }
                }
                unset($typeCompetition);
                
                $typeCompetitions = array_values($typeCompetitionsPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des types de compétition: ' . $e->getMessage());
        }
        
        try {
            // Récupérer les niveaux de championnat
            $niveauChampionnatResponse = $this->apiService->makeRequest('concours/niveau-championnat', 'GET');
            $firstUnwrap = $this->apiService->unwrapData($niveauChampionnatResponse);
            
            // Si firstUnwrap contient encore { success, data }, unwrap une deuxième fois
            if (is_array($firstUnwrap) && isset($firstUnwrap['data']) && isset($firstUnwrap['success'])) {
                $niveauChampionnatPayload = $firstUnwrap['data'];
            } else {
                $niveauChampionnatPayload = $firstUnwrap;
            }
            
            if ($niveauChampionnatResponse['success'] && is_array($niveauChampionnatPayload)) {
                // Normaliser l'ID de chaque niveau
                foreach ($niveauChampionnatPayload as &$niveau) {
                    if (!isset($niveau['id']) && isset($niveau['_id'])) {
                        $niveau['id'] = $niveau['_id'];
                    }
                }
                unset($niveau);
                
                $niveauChampionnat = array_values($niveauChampionnatPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des niveaux de championnat: ' . $e->getMessage());
        }
        
        $title = 'Éditer un concours - Portail Archers de Gémenos';
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/edit.php';
        include 'app/Views/layouts/footer.php';
    }

    // Mise à jour d'un concours
    public function update($id)
    {
        // Transformer les données comme dans store() pour assurer la cohérence
        $data = $_POST;
        
        // Transformer lieu_competition en lieu (comme dans store())
        if (isset($data['lieu_competition'])) {
            $data['lieu'] = $data['lieu_competition'];
        }
        
        // Transformer titre_competition en nom si nécessaire
        if (isset($data['titre_competition']) && !isset($data['nom'])) {
            $data['nom'] = $data['titre_competition'];
        }
        
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
