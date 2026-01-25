
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
        
        $clubsMap = []; // Mapping clubId -> club pour accès rapide
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
                    // Créer un mapping pour accès rapide par ID (int et string)
                    $clubId = $club['id'] ?? $club['_id'] ?? null;
                    if ($clubId) {
                        $clubsMap[$clubId] = $club;
                        $clubsMap[(string)$clubId] = $club;
                        $clubsMap[(int)$clubId] = $club;
                    }
                    // Créer aussi un mapping par name_short (car id_club peut contenir un name_short)
                    $nameShort = $club['nameShort'] ?? $club['name_short'] ?? null;
                    if ($nameShort) {
                        // Normaliser la valeur (trim, convertir en string)
                        $nameShortNormalized = trim((string)$nameShort);
                        $clubsMap[$nameShortNormalized] = $club;
                        // Aussi avec la valeur originale pour compatibilité
                        $clubsMap[(string)$nameShort] = $club;
                    }
                }
                unset($club);
                
                // Filtrer les clubs : exclure ceux dont le nameShort se termine par "000"
                $filtered = array_filter($payload, function($club) {
                    $nameShort = (string)($club['nameShort'] ?? $club['name_short'] ?? '');
                    return $nameShort === '' || substr($nameShort, -3) !== '000';
                });
                
                // Réindexer le tableau pour avoir des clés séquentielles
                $clubs = array_values($filtered);
            }
        } catch (Exception $e) {
            error_log('Exception lors de la récupération des clubs: ' . $e->getMessage());
        }
        
        // Enrichir les inscriptions avec le nom du club directement
        // id_club peut contenir soit un ID numérique, soit un name_short
        foreach ($inscriptions as &$inscription) {
            $clubId = $inscription['id_club'] ?? null;
            if ($clubId) {
                // Normaliser la valeur (trim, convertir en string)
                $clubIdStr = trim((string)$clubId);
                
                // Chercher dans le mapping (par ID ou par name_short) - essayer toutes les variantes
                $club = null;
                if (isset($clubsMap[$clubIdStr])) {
                    $club = $clubsMap[$clubIdStr];
                } elseif (isset($clubsMap[(string)$clubId])) {
                    $club = $clubsMap[(string)$clubId];
                } elseif (isset($clubsMap[(int)$clubId])) {
                    $club = $clubsMap[(int)$clubId];
                } elseif (isset($clubsMap[$clubId])) {
                    $club = $clubsMap[$clubId];
                } else {
                    // Si pas trouvé dans le mapping, chercher directement dans les clubs
                    if (isset($clubs) && is_array($clubs)) {
                        foreach ($clubs as $c) {
                            $nameShort = trim((string)($c['nameShort'] ?? $c['name_short'] ?? ''));
                            if ($nameShort === $clubIdStr) {
                                $club = $c;
                                break;
                            }
                        }
                    }
                }
                
                if ($club) {
                    // Utiliser le champ "name" (nom complet) comme demandé
                    $inscription['club_name'] = $club['name'] ?? null;
                    $inscription['club_name_short'] = $club['nameShort'] ?? $club['name_short'] ?? null;
                }
            }
        }
        unset($inscription);
        
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
        $lieu_latitude = $_POST['lieu_latitude'] ?? null;
        $lieu_longitude = $_POST['lieu_longitude'] ?? null;
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
                'lieu_latitude' => $lieu_latitude ? (float)$lieu_latitude : null,
                'lieu_longitude' => $lieu_longitude ? (float)$lieu_longitude : null,
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
                'agreenum' => $club_code, // nameShort du club organisateur
                'lieu_latitude' => $lieu_latitude ? (float)$lieu_latitude : null,
                'lieu_longitude' => $lieu_longitude ? (float)$lieu_longitude : null
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
            // Utiliser unwrapData pour être cohérent avec les autres appels API
            $inscriptions = $this->apiService->unwrapData($inscriptionsResponse);
            if (!is_array($inscriptions)) {
                $inscriptions = [];
            }
        } catch (Exception $e) {
            $inscriptions = [];
        }
        
        // Récupérer les informations complètes des utilisateurs inscrits
        $userIds = array_column($inscriptions, 'user_id');
        $usersMap = [];
        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                if ($userId) {
                    try {
                        $userResponse = $this->apiService->makeRequest("users/{$userId}", 'GET');
                        if ($userResponse['success'] && isset($userResponse['data'])) {
                            // Utiliser la même logique que dans inscription() qui fonctionne
                            $usersMap[$userId] = $userResponse['data'];
                        }
                    } catch (Exception $e) {
                        // Ignorer les erreurs pour continuer l'affichage
                    }
                }
            }
        }
        
        // Charger les données pour afficher les libellés
        $clubs = []; // Initialiser pour éviter les erreurs si l'API échoue
        $clubsMap = []; // Mapping clubId -> club pour accès rapide
        try {
            // Clubs
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $clubsPayload = $this->apiService->unwrapData($clubsResponse);
            if ($clubsResponse['success'] && is_array($clubsPayload)) {
                foreach ($clubsPayload as &$club) {
                    if (!isset($club['id']) && isset($club['_id'])) {
                        $club['id'] = $club['_id'];
                    }
                    // Créer un mapping pour accès rapide par ID (int et string)
                    $clubId = $club['id'] ?? $club['_id'] ?? null;
                    if ($clubId) {
                        $clubsMap[$clubId] = $club;
                        $clubsMap[(string)$clubId] = $club;
                        $clubsMap[(int)$clubId] = $club;
                    }
                    // Créer aussi un mapping par name_short (car id_club peut contenir un name_short)
                    $nameShort = $club['nameShort'] ?? $club['name_short'] ?? null;
                    if ($nameShort) {
                        // Normaliser la valeur (trim, convertir en string)
                        $nameShortNormalized = trim((string)$nameShort);
                        $clubsMap[$nameShortNormalized] = $club;
                        // Aussi avec la valeur originale pour compatibilité
                        $clubsMap[(string)$nameShort] = $club;
                    }
                }
                unset($club);
                $clubs = array_values($clubsPayload);
            }
        } catch (Exception $e) {
            // Ignorer les erreurs pour continuer l'affichage
        }
        
        // Enrichir les inscriptions avec le nom du club directement
        // id_club peut contenir soit un ID numérique, soit un name_short
        foreach ($inscriptions as &$inscription) {
            $clubId = $inscription['id_club'] ?? null;
            if ($clubId) {
                // Normaliser la valeur (trim, convertir en string)
                $clubIdStr = trim((string)$clubId);
                
                // Chercher dans le mapping (par ID ou par name_short) - essayer toutes les variantes
                $club = null;
                if (isset($clubsMap[$clubIdStr])) {
                    $club = $clubsMap[$clubIdStr];
                } elseif (isset($clubsMap[(string)$clubId])) {
                    $club = $clubsMap[(string)$clubId];
                } elseif (isset($clubsMap[(int)$clubId])) {
                    $club = $clubsMap[(int)$clubId];
                } elseif (isset($clubsMap[$clubId])) {
                    $club = $clubsMap[$clubId];
                } else {
                    // Si pas trouvé dans le mapping, chercher directement dans les clubs
                    if (isset($clubs) && is_array($clubs)) {
                        foreach ($clubs as $c) {
                            $nameShort = trim((string)($c['nameShort'] ?? $c['name_short'] ?? ''));
                            if ($nameShort === $clubIdStr) {
                                $club = $c;
                                break;
                            }
                        }
                    }
                }
                
                if ($club) {
                    // Utiliser le champ "name" (nom complet) comme demandé
                    $inscription['club_name'] = $club['name'] ?? null;
                    $inscription['club_name_short'] = $club['nameShort'] ?? $club['name_short'] ?? null;
                }
            }
        }
        unset($inscription);
        
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
        
        // Convertir les coordonnées GPS en float si présentes
        if (isset($data['lieu_latitude']) && $data['lieu_latitude'] !== '') {
            $data['lieu_latitude'] = (float)$data['lieu_latitude'];
        } else {
            $data['lieu_latitude'] = null;
        }
        
        if (isset($data['lieu_longitude']) && $data['lieu_longitude'] !== '') {
            $data['lieu_longitude'] = (float)$data['lieu_longitude'];
        } else {
            $data['lieu_longitude'] = null;
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

    // Page d'inscription à un concours
    public function inscription($concoursId)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        // Récupérer les informations du concours
        $concoursResponse = $this->apiService->getConcoursById($concoursId);
        if (!$concoursResponse['success']) {
            $_SESSION['error'] = 'Concours introuvable';
            header('Location: /concours');
            exit;
        }
        
        // Unwrap les données si nécessaire
        $concours = $this->apiService->unwrapData($concoursResponse);
        if (is_array($concours) && isset($concours['data']) && isset($concours['success'])) {
            $concours = $concours['data'];
        }
        
        // Convertir en objet si c'est un tableau pour faciliter l'accès dans la vue
        if (is_array($concours)) {
            $concours = (object)$concours;
        }


        // Récupérer les inscriptions existantes
        $inscriptionsResponse = $this->apiService->makeRequest("concours/{$concoursId}/inscriptions", 'GET');
        // Utiliser unwrapData pour être cohérent avec les autres appels API
        $inscriptions = $this->apiService->unwrapData($inscriptionsResponse);
        if (!is_array($inscriptions)) {
            $inscriptions = [];
        }
        
        // Récupérer les informations complètes des utilisateurs inscrits
        $userIds = array_column($inscriptions, 'user_id');
        $usersMap = [];
        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                if ($userId) {
                    try {
                        $userResponse = $this->apiService->makeRequest("users/{$userId}", 'GET');
                        if ($userResponse['success'] && isset($userResponse['data'])) {
                            $usersMap[$userId] = $userResponse['data'];
                        }
                    } catch (Exception $e) {
                        // Ignorer les erreurs pour continuer l'affichage
                    }
                }
            }
        }
        
        // Charger les données pour afficher les libellés
        $clubs = []; // Initialiser pour éviter les erreurs si l'API échoue
        $clubsMap = []; // Mapping clubId -> club pour accès rapide
        try {
            // Clubs
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            $clubsPayload = $this->apiService->unwrapData($clubsResponse);
            if ($clubsResponse['success'] && is_array($clubsPayload)) {
                foreach ($clubsPayload as &$club) {
                    if (!isset($club['id']) && isset($club['_id'])) {
                        $club['id'] = $club['_id'];
                    }
                    // Créer un mapping pour accès rapide par ID (int et string)
                    $clubId = $club['id'] ?? $club['_id'] ?? null;
                    if ($clubId) {
                        $clubsMap[$clubId] = $club;
                        $clubsMap[(string)$clubId] = $club;
                        $clubsMap[(int)$clubId] = $club;
                    }
                    // Créer aussi un mapping par name_short (car id_club peut contenir un name_short)
                    $nameShort = $club['nameShort'] ?? $club['name_short'] ?? null;
                    if ($nameShort) {
                        // Normaliser la valeur (trim, convertir en string)
                        $nameShortNormalized = trim((string)$nameShort);
                        $clubsMap[$nameShortNormalized] = $club;
                        // Aussi avec la valeur originale pour compatibilité
                        $clubsMap[(string)$nameShort] = $club;
                    }
                }
                unset($club);
                $clubs = array_values($clubsPayload);
            }
        } catch (Exception $e) {
            // Ignorer les erreurs pour continuer l'affichage
        }
        
        // Enrichir les inscriptions avec le nom du club directement
        // id_club peut contenir soit un ID numérique, soit un name_short
        foreach ($inscriptions as &$inscription) {
            $clubId = $inscription['id_club'] ?? null;
            if ($clubId) {
                // Normaliser la valeur (trim, convertir en string)
                $clubIdStr = trim((string)$clubId);
                
                // Chercher dans le mapping (par ID ou par name_short) - essayer toutes les variantes
                $club = null;
                if (isset($clubsMap[$clubIdStr])) {
                    $club = $clubsMap[$clubIdStr];
                } elseif (isset($clubsMap[(string)$clubId])) {
                    $club = $clubsMap[(string)$clubId];
                } elseif (isset($clubsMap[(int)$clubId])) {
                    $club = $clubsMap[(int)$clubId];
                } elseif (isset($clubsMap[$clubId])) {
                    $club = $clubsMap[$clubId];
                } else {
                    // Si pas trouvé dans le mapping, chercher directement dans les clubs
                    if (isset($clubs) && is_array($clubs)) {
                        foreach ($clubs as $c) {
                            $nameShort = trim((string)($c['nameShort'] ?? $c['name_short'] ?? ''));
                            if ($nameShort === $clubIdStr) {
                                $club = $c;
                                break;
                            }
                        }
                    }
                }
                
                if ($club) {
                    // Utiliser le champ "name" (nom complet) comme demandé
                    $inscription['club_name'] = $club['name'] ?? null;
                    $inscription['club_name_short'] = $club['nameShort'] ?? $club['name_short'] ?? null;
                }
            }
        }
        unset($inscription);

        // Récupérer les catégories de classement filtrées par discipline
        $categoriesClassement = [];
        try {
            // Récupérer l'iddiscipline du concours
            $iddiscipline = null;
            if (is_object($concours)) {
                $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
            } elseif (is_array($concours)) {
                $iddiscipline = $concours['discipline'] ?? $concours['iddiscipline'] ?? null;
            }
            
            // Construire l'URL avec le paramètre iddiscipline si disponible
            $endpoint = 'concours/categories-classement';
            if ($iddiscipline) {
                $endpoint .= '?iddiscipline=' . (int)$iddiscipline;
            }
            
            $categoriesResponse = $this->apiService->makeRequest($endpoint, 'GET');
            
            if ($categoriesResponse['success'] && isset($categoriesResponse['data'])) {
                $categoriesPayload = $categoriesResponse['data'];
                
                // Si data contient encore { success, data }, unwrap une deuxième fois
                if (is_array($categoriesPayload) && isset($categoriesPayload['data']) && isset($categoriesPayload['success'])) {
                    $categoriesPayload = $categoriesPayload['data'];
                }
                
                if (is_array($categoriesPayload)) {
                    // Normaliser l'ID de chaque catégorie
                    foreach ($categoriesPayload as &$categorie) {
                        if (!isset($categorie['id']) && isset($categorie['_id'])) {
                            $categorie['id'] = $categorie['_id'];
                        }
                    }
                    unset($categorie);
                    
                    // Trier par ordre alphabétique sur lb_categorie_classement
                    usort($categoriesPayload, function($a, $b) {
                        $libelleA = $a['lb_categorie_classement'] ?? '';
                        $libelleB = $b['lb_categorie_classement'] ?? '';
                        return strcasecmp($libelleA, $libelleB);
                    });
                    
                    // Réindexer le tableau pour avoir des clés séquentielles
                    $categoriesClassement = array_values($categoriesPayload);
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des catégories de classement: ' . $e->getMessage());
        }

        // Récupérer les arcs
        $arcs = [];
        try {
            $arcsResponse = $this->apiService->makeRequest('concours/arcs', 'GET');
            
            if ($arcsResponse['success'] && isset($arcsResponse['data'])) {
                $arcsPayload = $arcsResponse['data'];
                
                // Si data contient encore { success, data }, unwrap une deuxième fois
                if (is_array($arcsPayload) && isset($arcsPayload['data']) && isset($arcsPayload['success'])) {
                    $arcsPayload = $arcsPayload['data'];
                }
                
                if (is_array($arcsPayload)) {
                    // Normaliser l'ID de chaque arc
                    foreach ($arcsPayload as &$arc) {
                        if (!isset($arc['id']) && isset($arc['_id'])) {
                            $arc['id'] = $arc['_id'];
                        }
                    }
                    unset($arc);
                    
                    // Trier par ordre alphabétique sur lb_arc
                    usort($arcsPayload, function($a, $b) {
                        $libelleA = $a['lb_arc'] ?? '';
                        $libelleB = $b['lb_arc'] ?? '';
                        return strcasecmp($libelleA, $libelleB);
                    });
                    
                    // Réindexer le tableau pour avoir des clés séquentielles
                    $arcs = array_values($arcsPayload);
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des arcs: ' . $e->getMessage());
        }

        // Récupérer les distances de tir filtrées par discipline et type de compétition
        $distancesTir = [];
        try {
            // Récupérer l'iddiscipline et le type_competition du concours pour filtrer les distances
            $iddiscipline = null;
            $idtype_competition = null;
            if (is_object($concours)) {
                $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
                $idtype_competition = $concours->type_competition ?? null;
            } elseif (is_array($concours)) {
                $iddiscipline = $concours['discipline'] ?? $concours['iddiscipline'] ?? null;
                $idtype_competition = $concours['type_competition'] ?? null;
            }
            
            // Récupérer le libellé du type de compétition si idtype_competition est disponible
            $type_compet = null;
            if ($idtype_competition) {
                try {
                    $typeCompetResponse = $this->apiService->makeRequest('concours/type-competitions', 'GET');
                    if ($typeCompetResponse['success'] && isset($typeCompetResponse['data'])) {
                        $typeCompetPayload = $this->apiService->unwrapData($typeCompetResponse);
                        if (is_array($typeCompetPayload) && isset($typeCompetPayload['data']) && isset($typeCompetPayload['success'])) {
                            $typeCompetPayload = $typeCompetPayload['data'];
                        }
                        if (is_array($typeCompetPayload)) {
                            foreach ($typeCompetPayload as $tc) {
                                if (($tc['idformat_competition'] ?? null) == $idtype_competition) {
                                    $type_compet = $tc['lb_format_competition'] ?? null;
                                    break;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('Erreur lors de la récupération du type de compétition: ' . $e->getMessage());
                }
            }
            
            // Construire l'URL avec les paramètres si disponibles
            $endpoint = 'concours/distances-tir';
            $params = [];
            if ($iddiscipline) {
                $params[] = 'iddiscipline=' . (int)$iddiscipline;
            }
            if ($type_compet) {
                $params[] = 'type_compet=' . urlencode($type_compet);
            }
            if (!empty($params)) {
                $endpoint .= '?' . implode('&', $params);
            }
            
            $distancesResponse = $this->apiService->makeRequest($endpoint, 'GET');
            
            if ($distancesResponse['success'] && isset($distancesResponse['data'])) {
                $distancesPayload = $distancesResponse['data'];
                
                // Si data contient encore { success, data }, unwrap une deuxième fois
                if (is_array($distancesPayload) && isset($distancesPayload['data']) && isset($distancesPayload['success'])) {
                    $distancesPayload = $distancesPayload['data'];
                }
                
                if (is_array($distancesPayload)) {
                    // Normaliser l'ID de chaque distance
                    foreach ($distancesPayload as &$distance) {
                        if (!isset($distance['id']) && isset($distance['_id'])) {
                            $distance['id'] = $distance['_id'];
                        }
                    }
                    unset($distance);
                    
                    // Trier par distance_valeur (ordre croissant)
                    usort($distancesPayload, function($a, $b) {
                        $valeurA = (int)($a['distance_valeur'] ?? 0);
                        $valeurB = (int)($b['distance_valeur'] ?? 0);
                        return $valeurA <=> $valeurB;
                    });
                    
                    // Réindexer le tableau pour avoir des clés séquentielles
                    $distancesTir = array_values($distancesPayload);
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des distances de tir: ' . $e->getMessage());
        }

        // Inclure header et footer
        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/concours/inscription.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    // Traitement de l'inscription
    public function storeInscription($concoursId)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        $user_id = $_POST['user_id'] ?? null;

        if (!$user_id) {
            $_SESSION['error'] = 'Utilisateur requis';
            header("Location: /concours/{$concoursId}/inscription");
            exit;
        }
        if (!isset($_POST['numero_depart']) || $_POST['numero_depart'] === '') {
            $_SESSION['error'] = 'Numéro de départ requis';
            header("Location: /concours/{$concoursId}/inscription");
            exit;
        }
        
        // Récupérer les valeurs normalisées
        // Pour numero_depart : doit être fourni et non vide
        $numero_depart = null;
        if (isset($_POST['numero_depart']) && $_POST['numero_depart'] !== '' && $_POST['numero_depart'] !== null) {
            $numero_depart = (int)$_POST['numero_depart'];
        }
        
        // Pour numero_tir : peut être 0, donc on vérifie seulement si la clé existe
        $numero_tir = null;
        if (isset($_POST['numero_tir']) && $_POST['numero_tir'] !== '' && $_POST['numero_tir'] !== null) {
            $numero_tir = (int)$_POST['numero_tir'];
        }
        
        // Vérifier que numero_depart est fourni (déjà vérifié plus haut, mais on double-vérifie)
        if ($numero_depart === null) {
            $_SESSION['error'] = 'Numéro de départ requis';
            header("Location: /concours/{$concoursId}/inscription");
            exit;
        }
        
        // Vérifier si l'archer n'est pas déjà inscrit pour le même numéro de départ
        // Un archer ne peut pas être inscrit 2 fois pour le même départ (peu importe le numero_tir)
        $doublonDetecte = false;
        $messageErreur = '';
        
        try {
            $inscriptionsResponse = $this->apiService->makeRequest("concours/{$concoursId}/inscriptions", 'GET');
            
            // Vérifier que la réponse est valide
            if (!$inscriptionsResponse || !isset($inscriptionsResponse['success']) || !$inscriptionsResponse['success']) {
                error_log("Erreur lors de la récupération des inscriptions pour la vérification de doublon - Réponse invalide");
                // Si on ne peut pas vérifier, on bloque l'inscription pour sécurité
                $_SESSION['error'] = 'Impossible de vérifier les inscriptions existantes. Veuillez réessayer.';
                header("Location: /concours/{$concoursId}/inscription");
                exit;
            }
            
            $inscriptions = $this->apiService->unwrapData($inscriptionsResponse);
            
            // Normaliser $inscriptions si nécessaire
            if (!is_array($inscriptions)) {
                $inscriptions = [];
            }
            
            // Log pour déboguer
            error_log("=== VÉRIFICATION DOUBLON ===");
            error_log("user_id: $user_id, numero_depart: " . var_export($numero_depart, true) . ", numero_tir: " . var_export($numero_tir, true));
            error_log("Nombre d'inscriptions existantes: " . count($inscriptions));
            
            // Parcourir toutes les inscriptions existantes pour cet archer
            foreach ($inscriptions as $inscription) {
                // Vérifier si c'est le même utilisateur
                $insc_user_id = isset($inscription['user_id']) ? (int)$inscription['user_id'] : null;
                
                if ($insc_user_id !== (int)$user_id) {
                    continue; // Ce n'est pas le même utilisateur, passer à la suivante
                }
                
                // Normaliser les valeurs de l'inscription existante
                $insc_numero_depart = null;
                $insc_numero_tir = null;
                
                if (isset($inscription['numero_depart']) && $inscription['numero_depart'] !== '' && $inscription['numero_depart'] !== null) {
                    $insc_numero_depart = (int)$inscription['numero_depart'];
                }
                
                if (isset($inscription['numero_tir']) && $inscription['numero_tir'] !== '' && $inscription['numero_tir'] !== null) {
                    $insc_numero_tir = (int)$inscription['numero_tir'];
                }
                
                error_log("Inscription existante trouvée - user_id: $insc_user_id, numero_depart: " . var_export($insc_numero_depart, true) . ", numero_tir: " . var_export($insc_numero_tir, true));
                
                // Vérifier uniquement le numero_depart : un archer ne peut pas être inscrit 2 fois pour le même départ
                // Peu importe le numero_tir
                if ($insc_numero_depart === $numero_depart) {
                    $doublonDetecte = true;
                    $messageErreur = "Cet archer est déjà inscrit au départ $numero_depart pour ce concours.";
                    error_log("DOUBLON DÉTECTÉ - L'archer est déjà inscrit au départ $numero_depart");
                    break; // Sortir de la boucle
                }
            }
            
            // Si un doublon a été détecté, bloquer l'inscription
            if ($doublonDetecte) {
                error_log("BLOCAGE DE L'INSCRIPTION - Doublon détecté");
                $_SESSION['error'] = $messageErreur;
                header("Location: /concours/{$concoursId}/inscription");
                exit; // IMPORTANT: Sortir immédiatement, ne pas continuer vers l'appel API
            }
            
            error_log("Aucun doublon détecté - poursuite de l'inscription");
        } catch (Exception $e) {
            // En cas d'erreur lors de la récupération des inscriptions, bloquer l'inscription pour sécurité
            error_log("ERREUR lors de la vérification de doublon: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Erreur lors de la vérification des inscriptions existantes. Veuillez réessayer.';
            header("Location: /concours/{$concoursId}/inscription");
            exit; // IMPORTANT: Sortir immédiatement
        }
        
        // Préparer toutes les données d'inscription
        $inscriptionData = [
            'user_id' => $user_id,
            'numero_depart' => $numero_depart,
            'numero_licence' => !empty($_POST['numero_licence']) ? $_POST['numero_licence'] : null,
            'id_club' => !empty($_POST['id_club']) ? $_POST['id_club'] : null,
            'saison' => $_POST['saison'] ?? null,
            'type_certificat_medical' => $_POST['type_certificat_medical'] ?? null,
            'type_licence' => $_POST['type_licence'] ?? null,
            'creation_renouvellement' => isset($_POST['creation_renouvellement']) ? (int)$_POST['creation_renouvellement'] : 0,
            'categorie_classement' => $_POST['categorie_classement'] ?? null,
            'arme' => $_POST['arme'] ?? null,
            'mobilite_reduite' => isset($_POST['mobilite_reduite']) ? (int)$_POST['mobilite_reduite'] : 0,
            'distance' => isset($_POST['distance']) && $_POST['distance'] !== '' ? (int)$_POST['distance'] : null,
            'numero_tir' => $numero_tir,
            'duel' => isset($_POST['duel']) ? (int)$_POST['duel'] : 0,
            'blason' => isset($_POST['blason']) && $_POST['blason'] !== '' ? (int)$_POST['blason'] : null,
            'trispot' => isset($_POST['trispot']) ? (int)$_POST['trispot'] : 0,
            'tarif_competition' => $_POST['tarif_competition'] ?? null,
            'mode_paiement' => $_POST['mode_paiement'] ?? 'Non payé'
        ];

        // VÉRIFICATION FINALE : Ne JAMAIS appeler l'API si un doublon a été détecté
        if ($doublonDetecte === true) {
            error_log("ERREUR CRITIQUE: Tentative d'appel API alors qu'un doublon a été détecté ! Blocage immédiat.");
            $_SESSION['error'] = $messageErreur ?: 'Cet archer est déjà inscrit avec cette combinaison pour ce concours.';
            header("Location: /concours/{$concoursId}/inscription");
            exit; // ARRÊT IMMÉDIAT - Ne pas continuer
        }

        // Appel API pour inscrire (seulement si aucun doublon n'a été détecté)
        error_log("APPEL API - Aucun doublon détecté, procédure d'inscription");
        try {
            $response = $this->apiService->makeRequest("concours/{$concoursId}/inscription", 'POST', $inscriptionData);

            if ($response['success']) {
                $_SESSION['success'] = 'Inscription réussie';
            } else {
                $errorMessage = $response['error'] ?? $response['message'] ?? $response['data']['error'] ?? $response['data']['message'] ?? 'Erreur lors de l\'inscription';
                $_SESSION['error'] = $errorMessage;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de l\'inscription: ' . $e->getMessage();
        }

        header("Location: /concours/{$concoursId}/inscription");
        exit;
    }

    // Méthode utilitaire pour récupérer les concours via l'API
    // plus de méthode fetchConcoursFromApi : tout passe par ApiService
}
