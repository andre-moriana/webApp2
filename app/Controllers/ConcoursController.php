
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
        $disciplines = []; // Pour vérifier les abréviations de discipline

        try {
            // Récupérer les disciplines pour vérifier les abréviations
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
        
        // Récupérer les disciplines pour vérifier les abréviations dans la vue
        try {
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
            } else {
                $disciplines = [];
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des disciplines: ' . $e->getMessage());
            $disciplines = [];
        }
        
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
        if (!isset($inscriptions) || !is_array($inscriptions)) {
            $inscriptions = [];
        }
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
        $idNiveau_Championnat = $_POST['idniveau_championnat'] ?? '';
        $niveau_championnat_autre = $_POST['niveau_championnat_autre'] ?? '';
        $nombre_cibles = $_POST['nombre_cibles'] ?? 0;
        $nombre_depart = $_POST['nombre_depart'] ?? 1;
        $nombre_tireurs_par_cibles = $_POST['nombre_tireurs_par_cibles'] ?? 0;
        $type_concours = $_POST['type_concours'] ?? 'ouvert';
        $duel = isset($_POST['duel']) ? 1 : 0;
        $division_equipe = $_POST['division_equipe'] ?? 'duels_equipes';
        $code_authentification = $_POST['code_authentification'] ?? '';
        $type_publication_internet = $_POST['type_publication_internet'] ?? '';
        $lien_inscription_cible = $_POST['lien_inscription_cible'] ?? '';
        
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
                'idniveau_championnat' => $idNiveau_Championnat,
                'niveau_championnat_autre' => $niveau_championnat_autre,
                'nombre_cibles' => (int)$nombre_cibles,
                'nombre_depart' => (int)$nombre_depart,
                'nombre_tireurs_par_cibles' => (int)$nombre_tireurs_par_cibles,
                'type_concours' => $type_concours,
                'duel' => $duel,
                'division_equipe' => $division_equipe,
                'code_authentification' => $code_authentification,
                'type_publication_internet' => $type_publication_internet,
                'lien_inscription_cible' => $lien_inscription_cible ?: null,
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
                    $concoursId = $apiResponse['data']['id'] ?? null;
                    
                    // Créer automatiquement les plans de cible si la discipline est S, T, I ou H
                    if ($concoursId && $discipline && $nombre_cibles > 0 && $nombre_tireurs_par_cibles > 0) {
                        try {
                            // Récupérer l'abréviation de la discipline
                            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
                            $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
                            if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                                $disciplinesPayload = $disciplinesPayload['data'];
                            }
                            
                            $abv_discipline = null;
                            if (is_array($disciplinesPayload)) {
                                foreach ($disciplinesPayload as $disc) {
                                    $discId = $disc['iddiscipline'] ?? $disc['id'] ?? null;
                                    if ($discId == $discipline || (string)$discId === (string)$discipline) {
                                        $abv_discipline = $disc['abv_discipline'] ?? null;
                                        break;
                                    }
                                }
                            }
                            
                            // Créer les plans de cible si la discipline est S, T, I ou H
                            if ($abv_discipline && in_array($abv_discipline, ['S', 'T', 'I', 'H'])) {
                                $planData = [
                                    'nombre_cibles' => (int)$nombre_cibles,
                                    'nombre_depart' => (int)$nombre_depart,
                                    'nombre_tireurs_par_cibles' => (int)$nombre_tireurs_par_cibles
                                ];
                                
                                $planResponse = $this->apiService->createPlanCible($concoursId, $planData);
                                
                                if ($planResponse['success']) {
                                    $planMessage = $planResponse['data']['message'] ?? 'Plans de cible créés avec succès';
                                    $_SESSION['success'] = ($apiResponse['message'] ?? 'Concours créé avec succès') . '. ' . $planMessage;
                                } else {
                                    // Ne pas bloquer la création du concours si les plans échouent
                                    $_SESSION['success'] = ($apiResponse['message'] ?? 'Concours créé avec succès') . '. Attention: Les plans de cible n\'ont pas pu être créés automatiquement.';
                                    error_log('Erreur lors de la création automatique des plans de cible: ' . ($planResponse['error'] ?? 'Erreur inconnue'));
                                }
                            } else {
                                $_SESSION['success'] = $apiResponse['message'] ?? 'Concours créé avec succès';
                            }
                        } catch (Exception $e) {
                            // Ne pas bloquer la création du concours si les plans échouent
                            $_SESSION['success'] = ($apiResponse['message'] ?? 'Concours créé avec succès') . '. Attention: Les plans de cible n\'ont pas pu être créés automatiquement.';
                            error_log('Exception lors de la création automatique des plans de cible: ' . $e->getMessage());
                        }
                    } else {
                        $_SESSION['success'] = $apiResponse['message'] ?? 'Concours créé avec succès';
                    }
                    
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
        
        // Créer le lien d'inscription ciblé s'il n'existe pas
        if (empty($concours->lien_inscription_cible)) {
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
            $basePath = preg_replace('#/concours/.*$#', '', $path);
            $basePath = rtrim($basePath, '/');
            $lienInscription = $baseUrl . ($basePath ?: '') . '/concours/' . $id . '/inscription';
            try {
                $updateResponse = $this->apiService->updateConcours($id, ['lien_inscription_cible' => $lienInscription]);
                if ($updateResponse['success'] && isset($updateResponse['data'])) {
                    $concours->lien_inscription_cible = $lienInscription;
                }
            } catch (Exception $e) {
                // Ignorer les erreurs, le lien restera vide
            }
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

        // Recherche d'archer uniquement via XML (pas de table Users)
        
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
        
        // Récupérer l'abréviation de la discipline pour déterminer si c'est 3D, Nature ou Campagne
        $disciplineAbv = null;
        try {
            $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
            if ($iddiscipline) {
                $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
                $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
                if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                    $disciplinesPayload = $disciplinesPayload['data'];
                }
                
                if (is_array($disciplinesPayload)) {
                    foreach ($disciplinesPayload as $discipline) {
                        $discId = $discipline['iddiscipline'] ?? $discipline['id'] ?? null;
                        if ($discId == $iddiscipline || (string)$discId === (string)$iddiscipline) {
                            $disciplineAbv = $discipline['abv_discipline'] ?? null;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Ignorer les erreurs
        }
        // Vérifier si le plan de peloton existe (pour disciplines 3, N, C)
        $planCibleExists = false;
        if (in_array($disciplineAbv, ['S', 'T', 'I', 'H'])) {
            try {
                $plansResponse = $this->apiService->getPlanCible($id);
                if ($plansResponse['success']) {
                    $plansCible = $this->apiService->unwrapData($plansResponse);
                    if (is_array($plansCible) && isset($plansCible['data']) && isset($plansCible['success'])) {
                        $plansCible = $plansCible['data'];
                    }
                    $planCibleExists = !empty($plansCible);
                }
            } catch (Exception $e) {
                // Ignorer les erreurs
            }
        }        
        // Vérifier si le plan de peloton existe (pour disciplines 3, N, C)
        $planPelotonExists = false;
        if (in_array($disciplineAbv, ['3', 'N', 'C'])) {
            try {
                $plansResponse = $this->apiService->getPlanPeloton($id);
                if ($plansResponse['success']) {
                    $plansPeloton = $this->apiService->unwrapData($plansResponse);
                    if (is_array($plansPeloton) && isset($plansPeloton['data']) && isset($plansPeloton['success'])) {
                        $plansPeloton = $plansPeloton['data'];
                    }
                    $planPelotonExists = !empty($plansPeloton);
                }
            } catch (Exception $e) {
                // Ignorer les erreurs
            }
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

        // Mapper le code club vers agreenum pour l'API
        if (isset($data['club_code']) && !isset($data['agreenum'])) {
            $data['agreenum'] = $data['club_code'];
        }
        
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

        // Récupérer l'abréviation de la discipline pour déterminer si c'est 3D, Nature ou Campagne
        $disciplineAbv = null;
        try {
            $iddiscipline = null;
            if (is_object($concours)) {
                $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
            } elseif (is_array($concours)) {
                $iddiscipline = $concours['discipline'] ?? $concours['iddiscipline'] ?? null;
            }
            
            if ($iddiscipline) {
                // Récupérer toutes les disciplines pour trouver l'abv_discipline
                $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
                $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
                if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                    $disciplinesPayload = $disciplinesPayload['data'];
                }
                
                if (is_array($disciplinesPayload)) {
                    foreach ($disciplinesPayload as $discipline) {
                        $discId = $discipline['iddiscipline'] ?? $discipline['id'] ?? null;
                        if ($discId == $iddiscipline || (string)$discId === (string)$iddiscipline) {
                            $disciplineAbv = $discipline['abv_discipline'] ?? null;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de l\'abréviation de la discipline: ' . $e->getMessage());
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

        // Préparer les départs pour la modale d'édition
        $departs = [];
        $nombreDepart = is_object($concours) ? ($concours->nombre_depart ?? null) : ($concours['nombre_depart'] ?? null);
        if ($nombreDepart && is_numeric($nombreDepart) && $nombreDepart > 0) {
            for ($i = 1; $i <= (int)$nombreDepart; $i++) {
                $departs[] = [
                    'numero' => $i,
                    'heure' => '', // Peut être enrichi plus tard si nécessaire
                    'date' => '' // Peut être enrichi plus tard si nécessaire
                ];
            }
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

        error_log("=== storeInscription appelé pour concoursId: " . $concoursId);
        error_log("Données POST reçues: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

        $user_nom = isset($_POST['user_nom']) ? trim($_POST['user_nom']) : null;
        
        if (empty($user_nom)) {
            error_log("ERREUR: Pas de user_nom!");
            $_SESSION['error'] = 'Nom de l\'archer requis';
            header("Location: /concours/{$concoursId}/inscription");
            exit;
        }
        
        if (!isset($_POST['numero_depart']) || $_POST['numero_depart'] === '') {
            error_log("ERREUR: Pas de numero_depart!");
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
        
        // Vérifier que le numero_tir n'est pas supérieur au numero_depart
        if ($numero_tir !== null && $numero_tir > $numero_depart) {
            $_SESSION['error'] = "Le numéro de tir ($numero_tir) ne peut pas être supérieur au numéro de départ ($numero_depart).";
            error_log("ERREUR VALIDATION - numero_tir ($numero_tir) > numero_depart ($numero_depart)");
            header("Location: /concours/{$concoursId}/inscription");
            exit;
        }
        
        // Vérifier si l'archer n'est pas déjà inscrit pour le même numéro de départ OU le même numéro de tir
        // Un archer ne peut pas être inscrit 2 fois pour le même départ
        // Un archer ne peut pas être inscrit 2 fois pour le même numéro de tir
        $doublonDetecte = false;
        $messageErreur = '';
        
        try {
            $inscriptionsResponse = $this->apiService->makeRequest("concours/{$concoursId}/inscriptions", 'GET');
            
            // Log pour déboguer
            error_log("Réponse API inscriptions: " . json_encode($inscriptionsResponse, JSON_UNESCAPED_UNICODE));
            
            // Gérer deux formats possibles :
            // 1. Format avec wrapper : {success: true, data: [...]}
            // 2. Format direct : [...] (tableau directement)
            
            $inscriptions = [];
            
            if (is_array($inscriptionsResponse)) {
                // Si c'est un tableau indexé numériquement, c'est probablement le format direct
                if (isset($inscriptionsResponse[0]) || empty($inscriptionsResponse)) {
                    $inscriptions = $inscriptionsResponse;
                }
                // Sinon, vérifier si c'est un format avec wrapper
                elseif (isset($inscriptionsResponse['success'])) {
                    if ($inscriptionsResponse['success']) {
                        $inscriptions = $this->apiService->unwrapData($inscriptionsResponse);
                    } else {
                        // Vérifier si data contient quand même un tableau (cas où l'API retourne des données même en erreur)
                        $data = $inscriptionsResponse['data'] ?? null;
                        if (is_array($data) && (isset($data[0]) || empty($data))) {
                            error_log("API retourne success: false mais data contient un tableau - Utilisation des données");
                            $inscriptions = $data;
                        } else {
                            // Récupérer tous les messages d'erreur possibles
                            $errorMessage = $inscriptionsResponse['error'] 
                                ?? $inscriptionsResponse['message'] 
                                ?? ($data && is_array($data) ? ($data['error'] ?? $data['message'] ?? null) : null)
                                ?? 'Erreur inconnue';
                            
                            $statusCode = $inscriptionsResponse['status_code'] ?? 'N/A';
                            error_log("Erreur API - success: false - Status: $statusCode - Message: $errorMessage");
                            error_log("Réponse complète: " . json_encode($inscriptionsResponse, JSON_UNESCAPED_UNICODE));
                            
                            // Si c'est une erreur 500 ou autre erreur serveur, continuer avec tableau vide plutôt que bloquer
                            // Cela permet de ne pas bloquer l'inscription si le serveur a un problème temporaire
                            if ($statusCode >= 500 || $statusCode === 'N/A') {
                                error_log("Erreur serveur détectée ($statusCode) - Continuation avec tableau vide pour permettre l'inscription");
                                $inscriptions = [];
                            } else {
                                // Pour les autres erreurs (400, 404, etc.), bloquer l'inscription
                                $_SESSION['error'] = 'Erreur lors de la récupération des inscriptions: ' . $errorMessage;
                                header("Location: /concours/{$concoursId}/inscription");
                                exit;
                            }
                        }
                    }
                }
                // Si c'est un tableau associatif sans 'success', essayer unwrapData quand même
                else {
                    $inscriptions = $this->apiService->unwrapData($inscriptionsResponse);
                }
            } else {
                // Si la réponse n'est pas un tableau, logger et utiliser un tableau vide
                error_log("ERREUR: La réponse de l'API n'est pas un tableau. Type: " . gettype($inscriptionsResponse));
                error_log("Valeur reçue: " . var_export($inscriptionsResponse, true));
                $inscriptions = [];
            }
            
            // Normaliser $inscriptions si nécessaire
            if (!is_array($inscriptions)) {
                error_log("ERREUR: Les inscriptions ne sont pas un tableau après traitement: " . gettype($inscriptions));
                error_log("Valeur reçue: " . var_export($inscriptions, true));
                $inscriptions = [];
            }
            
            // Log pour déboguer
            error_log("=== VÉRIFICATION DOUBLON ===");
            error_log("user_nom: $user_nom, numero_depart: " . var_export($numero_depart, true) . ", numero_tir: " . var_export($numero_tir, true));
            error_log("Nombre d'inscriptions existantes: " . count($inscriptions));
            
            // Si on n'a pas réussi à obtenir les inscriptions mais qu'il n'y a pas d'erreur explicite,
            // continuer avec un tableau vide (pas de doublon possible)
            if (empty($inscriptions)) {
                error_log("Aucune inscription existante trouvée - pas de vérification de doublon nécessaire");
            }
            
            // Parcourir toutes les inscriptions existantes pour cet archer
            foreach ($inscriptions as $inscription) {
                // Vérifier si c'est le même utilisateur (par user_nom)
                // Support aussi user_id pour compatibilité avec les anciennes inscriptions
                $insc_user_nom = isset($inscription['user_nom']) ? trim($inscription['user_nom']) : null;
                //$insc_user_id = isset($inscription['user_id']) ? (int)$inscription['user_id'] : null;
                
                // Comparer par user_nom si disponible, sinon ignorer cette inscription pour la vérification
                if (!empty($insc_user_nom) && $insc_user_nom !== trim($user_nom)) {
                    continue; // Ce n'est pas le même utilisateur, passer à la suivante
                }
                
                // Si l'inscription existante n'a pas de user_nom mais a un user_id, on ne peut pas comparer
                // On ignore cette inscription pour la vérification de doublon basée sur user_nom
                if (empty($insc_user_nom) && !empty($insc_user_id)) {
                    continue; // Ancienne inscription avec user_id, ignorer pour cette vérification
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
                
                error_log("Inscription existante trouvée - user_nom: $insc_user_nom, numero_depart: " . var_export($insc_numero_depart, true) . ", numero_tir: " . var_export($insc_numero_tir, true));
                
                // Vérifier le numero_depart : un archer ne peut pas être inscrit 2 fois pour le même départ
                if ($insc_numero_depart !== null && $insc_numero_depart === $numero_depart) {
                    $doublonDetecte = true;
                    $messageErreur = "Cet archer est déjà inscrit au départ $numero_depart pour ce concours.";
                    error_log("DOUBLON DÉTECTÉ - L'archer est déjà inscrit au départ $numero_depart");
                    break; // Sortir de la boucle
                }
                
                // Vérifier le numero_tir : un archer ne peut pas être inscrit 2 fois pour le même numéro de tir
                // (seulement si numero_tir est fourni dans la nouvelle inscription)
                if ($numero_tir !== null && $insc_numero_tir !== null && $insc_numero_tir === $numero_tir) {
                    $doublonDetecte = true;
                    $messageErreur = "Cet archer est déjà inscrit avec le numéro de tir $numero_tir pour ce concours.";
                    error_log("DOUBLON DÉTECTÉ - L'archer est déjà inscrit avec le numéro de tir $numero_tir");
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
            
            // Vérifier que le numero_tir du départ suivant n'est pas inférieur au numero_tir du départ précédent
            // (seulement si numero_tir est fourni dans la nouvelle inscription)
            if ($numero_tir !== null) {
                // Récupérer toutes les inscriptions de cet archer pour ce concours, triées par numero_depart
                $inscriptionsArcher = [];
                foreach ($inscriptions as $inscription) {
                    $insc_user_nom = isset($inscription['user_nom']) ? trim($inscription['user_nom']) : null;
                    // Comparer seulement si user_nom est disponible et correspond
                    if (!empty($insc_user_nom) && $insc_user_nom === trim($user_nom)) {
                        $insc_numero_depart = null;
                        $insc_numero_tir = null;
                        
                        if (isset($inscription['numero_depart']) && $inscription['numero_depart'] !== '' && $inscription['numero_depart'] !== null) {
                            $insc_numero_depart = (int)$inscription['numero_depart'];
                        }
                        
                        if (isset($inscription['numero_tir']) && $inscription['numero_tir'] !== '' && $inscription['numero_tir'] !== null) {
                            $insc_numero_tir = (int)$inscription['numero_tir'];
                        }
                        
                        // Ne garder que les inscriptions avec un numero_depart et un numero_tir valides
                        if ($insc_numero_depart !== null && $insc_numero_tir !== null) {
                            $inscriptionsArcher[] = [
                                'numero_depart' => $insc_numero_depart,
                                'numero_tir' => $insc_numero_tir
                            ];
                        }
                    }
                }
                
                // Trier les inscriptions par numero_depart
                usort($inscriptionsArcher, function($a, $b) {
                    return $a['numero_depart'] <=> $b['numero_depart'];
                });
                
                error_log("Inscriptions de l'archer triées par départ: " . json_encode($inscriptionsArcher));
                
                // Vérifier que le numero_tir du nouveau départ n'est pas inférieur au numero_tir du départ précédent
                foreach ($inscriptionsArcher as $insc) {
                    // Si on trouve un départ précédent (numero_depart < nouveau numero_depart)
                    if ($insc['numero_depart'] < $numero_depart) {
                        // Vérifier que le numero_tir du nouveau départ n'est pas inférieur
                        if ($numero_tir < $insc['numero_tir']) {
                            $doublonDetecte = true;
                            $messageErreur = "Le numéro de tir du départ $numero_depart ($numero_tir) ne peut pas être inférieur au numéro de tir du départ {$insc['numero_depart']} ({$insc['numero_tir']}).";
                            error_log("DOUBLON DÉTECTÉ - Numéro de tir inférieur: départ $numero_depart (tir $numero_tir) < départ {$insc['numero_depart']} (tir {$insc['numero_tir']})");
                            break;
                        }
                    }
                    // Si on trouve un départ suivant (numero_depart > nouveau numero_depart)
                    elseif ($insc['numero_depart'] > $numero_depart) {
                        // Vérifier que le numero_tir du départ suivant n'est pas inférieur au nouveau numero_tir
                        if ($insc['numero_tir'] < $numero_tir) {
                            $doublonDetecte = true;
                            $messageErreur = "Le numéro de tir du départ {$insc['numero_depart']} ({$insc['numero_tir']}) ne peut pas être inférieur au numéro de tir du départ $numero_depart ($numero_tir).";
                            error_log("DOUBLON DÉTECTÉ - Numéro de tir inférieur: départ {$insc['numero_depart']} (tir {$insc['numero_tir']}) < départ $numero_depart (tir $numero_tir)");
                            break;
                        }
                    }
                }
                
                // Si une violation a été détectée, bloquer l'inscription
                if ($doublonDetecte) {
                    error_log("BLOCAGE DE L'INSCRIPTION - Numéro de tir inférieur détecté");
                    $_SESSION['error'] = $messageErreur;
                    header("Location: /concours/{$concoursId}/inscription");
                    exit; // IMPORTANT: Sortir immédiatement, ne pas continuer vers l'appel API
                }
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
        
        // Récupérer l'abréviation de la discipline pour déterminer si on utilise piquet ou distance
        $disciplineAbv = null;
        try {
            $concoursResponse = $this->apiService->getConcoursById($concoursId);
            if ($concoursResponse['success']) {
                $concoursData = $this->apiService->unwrapData($concoursResponse);
                if (is_array($concoursData) && isset($concoursData['data']) && isset($concoursData['success'])) {
                    $concoursData = $concoursData['data'];
                }
                
                $iddiscipline = is_object($concoursData) ? ($concoursData->discipline ?? $concoursData->iddiscipline ?? null) : ($concoursData['discipline'] ?? $concoursData['iddiscipline'] ?? null);
                
                if ($iddiscipline) {
                    $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
                    $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
                    if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                        $disciplinesPayload = $disciplinesPayload['data'];
                    }
                    
                    if (is_array($disciplinesPayload)) {
                        foreach ($disciplinesPayload as $discipline) {
                            $discId = $discipline['iddiscipline'] ?? $discipline['id'] ?? null;
                            if ($discId == $iddiscipline || (string)$discId === (string)$iddiscipline) {
                                $disciplineAbv = $discipline['abv_discipline'] ?? null;
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de l\'abréviation de la discipline: ' . $e->getMessage());
        }
        
        $isNature3DOrCampagne = isset($disciplineAbv) && in_array($disciplineAbv, ['3', 'N', 'C'], true);
        
        // Préparer toutes les données d'inscription
        $inscriptionData = [
            'user_nom' => $user_nom,
            'numero_depart' => $numero_depart,
            'numero_licence' => !empty($_POST['numero_licence']) ? $_POST['numero_licence'] : null,
            'id_club' => !empty($_POST['id_club']) ? $_POST['id_club'] : null,
            'saison' => $_POST['saison'] ?? null,
            'type_certificat_medical' => $_POST['type_certificat_medical'] ?? null,
            'type_licence' => $_POST['type_licence'] ?? null,
            'creation_renouvellement' => isset($_POST['creation_renouvellement']) && $_POST['creation_renouvellement'] !== '' ? 
                (in_array(strtoupper(trim($_POST['creation_renouvellement'])), ['C', 'R'], true) ? 
                    strtoupper(trim($_POST['creation_renouvellement'])) : null) : null,
            'categorie_classement' => $_POST['categorie_classement'] ?? null,
            'arme' => $_POST['arme'] ?? null,
            'mobilite_reduite' => isset($_POST['mobilite_reduite']) ? (int)$_POST['mobilite_reduite'] : 0,
            'numero_tir' => $numero_tir,
            'tarif_competition' => $_POST['tarif_competition'] ?? null,
            'mode_paiement' => $_POST['mode_paiement'] ?? 'Non payé'
        ];
        
        // Pour les disciplines 3D, Nature et Campagne : utiliser piquet au lieu de distance, pas de blason, pas de duel/trispot
        if ($isNature3DOrCampagne) {
            // Le champ piquet doit être envoyé même s'il est vide (sera null côté base)
            // Utiliser isset pour vérifier l'existence, et accepter les chaînes vides
            if (isset($_POST['piquet'])) {
                $inscriptionData['piquet'] = $_POST['piquet'] !== '' ? $_POST['piquet'] : null;
            } else {
                $inscriptionData['piquet'] = null;
            }
            // Pas de champ blason, duel ni trispot pour ces disciplines
        } else {
            // Pour les autres disciplines : utiliser distance, blason, duel et trispot
            $inscriptionData['distance'] = isset($_POST['distance']) && $_POST['distance'] !== '' ? (int)$_POST['distance'] : null;
            $inscriptionData['blason'] = isset($_POST['blason']) && $_POST['blason'] !== '' ? (int)$_POST['blason'] : null;
            $inscriptionData['duel'] = isset($_POST['duel']) ? (int)$_POST['duel'] : 0;
            $inscriptionData['trispot'] = isset($_POST['trispot']) ? (int)$_POST['trispot'] : 0;
        }

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

    /**
     * Met à jour une inscription de concours
     */
    public function updateInscription($concoursId, $inscriptionId) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            exit;
        }

        // Vérifier les permissions (même que pour l'inscription)
        try {
            $clubId = $_SESSION['user']['clubId'] ?? null;
            PermissionHelper::requirePermission(
                PermissionService::RESOURCE_USERS_ALL,
                PermissionService::ACTION_VIEW,
                $clubId
            );
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Permissions insuffisantes: ' . $e->getMessage()
            ]);
            exit;
        }

        try {
            // Récupérer les données JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit;
            }

            // Appel API pour mettre à jour l'inscription
            $response = $this->apiService->makeRequest("concours/{$concoursId}/inscription/{$inscriptionId}", 'PUT', $data);

            // Vérifier le statut HTTP de la réponse
            $statusCode = $response['status_code'] ?? 200;
            
            // Vérifier si la réponse contient success dans data (format BackendPHP)
            $apiSuccess = false;
            if (isset($response['data']['success'])) {
                $apiSuccess = $response['data']['success'];
            } elseif (isset($response['success'])) {
                $apiSuccess = $response['success'];
            } else {
                // Si pas de success explicite, vérifier le code HTTP
                $apiSuccess = ($statusCode >= 200 && $statusCode < 300);
            }
            
            $isSuccess = $apiSuccess && ($statusCode >= 200 && $statusCode < 300);

            if ($isSuccess) {
                // Si la réponse contient déjà un message, l'utiliser
                $message = $response['data']['message'] ?? $response['message'] ?? 'Inscription mise à jour avec succès';
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'data' => $response['data'] ?? null
                ]);
            } else {
                // Extraire le message d'erreur de différentes façons
                $errorMessage = 'Erreur lors de la mise à jour';
                
                // Vérifier d'abord dans data (format BackendPHP)
                if (isset($response['data']['error'])) {
                    $errorMessage = $response['data']['error'];
                } elseif (isset($response['data']['message'])) {
                    $errorMessage = $response['data']['message'];
                } elseif (isset($response['error'])) {
                    $errorMessage = $response['error'];
                } elseif (isset($response['message'])) {
                    $errorMessage = $response['message'];
                } elseif (isset($response['data']) && is_string($response['data'])) {
                    $errorMessage = $response['data'];
                }
                
                // Si c'est une erreur HTTP, l'indiquer
                if ($statusCode >= 400) {
                    $errorMessage = "Erreur HTTP {$statusCode}: " . $errorMessage;
                }
                
                http_response_code($statusCode >= 400 ? $statusCode : 500);
                echo json_encode([
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'debug' => [
                        'response_structure' => $response
                    ]
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }

    // Méthode utilitaire pour récupérer les concours via l'API
    // plus de méthode fetchConcoursFromApi : tout passe par ApiService

    // Affichage du plan de cible d'un concours
    public function planCible($concoursId)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Nettoyer les messages d'erreur de session
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $concours = null;
        $plans = [];
        $disciplines = [];
        
        // Récupérer le concours
        $response = $this->apiService->getConcoursById($concoursId);
        if ($response['success'] && isset($response['data'])) {
            $concours = (object) $response['data'];
        } else {
            $_SESSION['error'] = 'Impossible de récupérer le concours.';
            header('Location: /concours');
            exit;
        }
        
        // Récupérer l'abréviation de la discipline pour vérifier si on peut afficher le plan
        try {
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
        
        // Vérifier que la discipline est S, T, I ou H
        $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
        $abv_discipline = null;
        
        if ($iddiscipline && is_array($disciplines)) {
            foreach ($disciplines as $disc) {
                $discId = $disc['iddiscipline'] ?? $disc['id'] ?? null;
                if ($discId == $iddiscipline || (string)$discId === (string)$iddiscipline) {
                    $abv_discipline = $disc['abv_discipline'] ?? null;
                    break;
                }
            }
        }
        
        if (!in_array($abv_discipline, ['S', 'T', 'I', 'H'])) {
            $_SESSION['error'] = "Les plans de cible ne sont disponibles que pour les disciplines S, T, I et H. Discipline actuelle: " . ($abv_discipline ?? 'inconnue');
            header('Location: /concours/show/' . $concoursId);
            exit;
        }
        
        // Récupérer les plans de cible
        try {
            $plansResponse = $this->apiService->getPlanCible($concoursId);
            if ($plansResponse['success']) {
                $plans = $this->apiService->unwrapData($plansResponse);
                // Si les données sont encore encapsulées, les extraire
                if (is_array($plans) && isset($plans['data']) && isset($plans['success'])) {
                    $plans = $plans['data'];
                }
                // Si plans n'est pas un tableau, initialiser à vide
                if (!is_array($plans)) {
                    $plans = [];
                }
            } else {
                $plans = [];
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des plans de cible: ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la récupération des plans de cible: ' . $e->getMessage();
            $plans = [];
        }
        
        // Récupérer les noms des utilisateurs assignés et les inscriptions (user_nom + numero_licence)
        $usersMap = [];
        $inscriptionsMap = []; // Clé: numero_licence -> { user_nom, id_club, club_name, ... }
        $trispotMap = []; // Map pour stocker les informations trispot par cible (clé: "depart_cible")
        if (!empty($plans)) {
            $userIds = [];
            $licencesFromPlan = [];
            foreach ($plans as $numeroDepartLoop => $departPlans) {
                if (is_array($departPlans)) {
                    foreach ($departPlans as $plan) {
                        $userId = $plan['user_id'] ?? null;
                        if ($userId && !in_array($userId, $userIds)) {
                            $userIds[] = $userId;
                        }
                        $licence = $plan['numero_licence'] ?? null;
                        if ($licence && !isset($licencesFromPlan[$licence])) {
                            $licencesFromPlan[$licence] = $plan['user_nom'] ?? null;
                        }
                        // Récupérer l'information trispot directement depuis le plan (concours_plan_cible)
                        if (isset($plan['numero_depart']) && isset($plan['numero_cible'])) {
                            $cibleKey = $plan['numero_depart'] . '_' . $plan['numero_cible'];
                            if (!isset($trispotMap[$cibleKey]) && isset($plan['trispot'])) {
                                $trispotMap[$cibleKey] = $plan['trispot'];
                            }
                        }
                    }
                }
            }
            
            // Récupérer les inscriptions pour user_nom + numero_licence (plan utilise inscriptions)
            if (!empty($licencesFromPlan)) {
                try {
                    $inscResponse = $this->apiService->makeRequest("concours/{$concoursId}/inscriptions", 'GET');
                    if ($inscResponse['success']) {
                        $inscriptions = $this->apiService->unwrapData($inscResponse);
                        if (is_array($inscriptions)) {
                            foreach ($inscriptions as $insc) {
                                $lic = $insc['numero_licence'] ?? null;
                                if ($lic) {
                                    $inscriptionsMap[$lic] = [
                                        'user_nom' => $insc['user_nom'] ?? '',
                                        'numero_licence' => $lic,
                                        'id_club' => $insc['id_club'] ?? null,
                                        'club_name' => $insc['club_name'] ?? $insc['id_club'] ?? null,
                                        'nom' => $insc['user_nom'] ?? '',
                                        'name' => $insc['user_nom'] ?? '',
                                        'clubName' => $insc['club_name'] ?? null
                                    ];
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('Erreur récupération inscriptions pour plan cible: ' . $e->getMessage());
                }
            }
            
            // Récupérer les informations des utilisateurs (pour user_id - compatibilité)
            foreach ($userIds as $userId) {
                try {
                    $userResponse = $this->apiService->makeRequest("users/{$userId}", 'GET');
                    if ($userResponse['success'] && isset($userResponse['data'])) {
                        $userData = $this->apiService->unwrapData($userResponse);
                        if (is_array($userData) && isset($userData['data']) && isset($userData['success'])) {
                            $userData = $userData['data'];
                        }
                        if (empty($userData['clubName']) && (isset($userData['club']) || isset($userData['club_id']))) {
                            try {
                                $clubId = $userData['club_id'] ?? $userData['club'] ?? null;
                                if ($clubId) {
                                    $clubResponse = $this->apiService->makeRequest("clubs/{$clubId}", 'GET');
                                    if ($clubResponse['success'] && isset($clubResponse['data'])) {
                                        $clubData = $clubResponse['data'];
                                        $userData['clubName'] = $clubData['name'] ?? $clubData['nom'] ?? '';
                                    }
                                }
                            } catch (Exception $e) {
                                error_log('Erreur lors de la récupération du club pour l\'utilisateur ' . $userId . ': ' . $e->getMessage());
                            }
                        }
                        $usersMap[$userId] = $userData;
                    }
                } catch (Exception $e) {
                    error_log('Erreur lors de la récupération de l\'utilisateur ' . $userId . ': ' . $e->getMessage());
                }
            }
        }
        
        $title = 'Plan de cible - ' . ($concours->titre_competition ?? $concours->nom ?? 'Concours');
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/plan-cible.php';
        include 'app/Views/layouts/footer.php';
    }

    public function planCibleTypeBlason()
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        $concoursId = $_POST['concours_id'] ?? null;
        $numeroDepart = $_POST['numero_depart'] ?? null;
        $numeroCible = $_POST['numero_cible'] ?? null;
        $blasonType = $_POST['blason_type'] ?? null;
        $trispot = $_POST['trispot'] ?? '0';

        if (!$concoursId || !$numeroDepart || !$numeroCible || !$blasonType) {
            $_SESSION['error'] = 'Paramètres manquants pour enregistrer le type de blason.';
            header('Location: /concours/' . urlencode((string)$concoursId) . '/plan-cible');
            exit;
        }

        try {
            $payload = [
                'concours_id' => (int)$concoursId,
                'numero_depart' => (int)$numeroDepart,
                'numero_cible' => (int)$numeroCible,
                'blason_type' => $blasonType,
                'trispot' => (int)$trispot
            ];

            $response = $this->apiService->makeRequest('concours/plan-cible-type-blason', 'POST', $payload);

            $success = false;
            if (isset($response['success'])) {
                $success = (bool)$response['success'];
            } elseif (isset($response['data']['success'])) {
                $success = (bool)$response['data']['success'];
            }

            if ($success) {
                $_SESSION['success'] = 'Type de blason enregistré avec succès.';
            } else {
                $errorMsg = $response['error'] ?? ($response['message'] ?? ($response['data']['error'] ?? 'Erreur lors de l\'enregistrement.'));
                $_SESSION['error'] = $errorMsg;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de l\'enregistrement du type de blason: ' . $e->getMessage();
        }

        header('Location: /concours/' . urlencode((string)$concoursId) . '/plan-cible');
        exit;
    }

    public function planPeloton($concoursId)
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        unset($_SESSION['error']);
        unset($_SESSION['success']);

        $concours = null;
        $plans = [];
        $inscriptionsMap = [];
        $usersMap = [];

        $response = $this->apiService->getConcoursById($concoursId);
        if ($response['success'] && isset($response['data'])) {
            $concours = (object) $response['data'];
        } else {
            $_SESSION['error'] = 'Impossible de récupérer le concours.';
            header('Location: /concours');
            exit;
        }

        $disciplines = [];
        try {
            $disciplinesResponse = $this->apiService->makeRequest('concours/disciplines', 'GET');
            $disciplinesPayload = $this->apiService->unwrapData($disciplinesResponse);
            if (is_array($disciplinesPayload) && isset($disciplinesPayload['data']) && isset($disciplinesPayload['success'])) {
                $disciplinesPayload = $disciplinesPayload['data'];
            }
            if ($disciplinesResponse['success'] && is_array($disciplinesPayload)) {
                $disciplines = array_values($disciplinesPayload);
            }
        } catch (Exception $e) {
            error_log('Erreur disciplines: ' . $e->getMessage());
        }

        $iddiscipline = $concours->discipline ?? $concours->iddiscipline ?? null;
        $abv_discipline = null;
        if ($iddiscipline && is_array($disciplines)) {
            foreach ($disciplines as $disc) {
                $discId = $disc['iddiscipline'] ?? $disc['id'] ?? null;
                if ($discId == $iddiscipline || (string)$discId === (string)$iddiscipline) {
                    $abv_discipline = $disc['abv_discipline'] ?? null;
                    break;
                }
            }
        }

        if (!in_array($abv_discipline, ['3', 'N', 'C'])) {
            $_SESSION['error'] = "Les plans de peloton ne sont disponibles que pour les disciplines Campagne (C), Nature (N) et 3D (3). Discipline actuelle: " . ($abv_discipline ?? 'inconnue');
            header('Location: /concours/show/' . $concoursId);
            exit;
        }

        try {
            $plansResponse = $this->apiService->getPlanPeloton($concoursId);
            if ($plansResponse['success']) {
                $plans = $this->apiService->unwrapData($plansResponse);
                if (is_array($plans) && isset($plans['data']) && isset($plans['success'])) {
                    $plans = $plans['data'];
                }
                if (!is_array($plans)) {
                    $plans = [];
                }
            } else {
                $plans = [];
            }
        } catch (Exception $e) {
            error_log('Erreur plan peloton: ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la récupération des plans de peloton: ' . $e->getMessage();
            $plans = [];
        }

        $licencesFromPlan = [];
        foreach ($plans as $departPlans) {
            if (is_array($departPlans)) {
                foreach ($departPlans as $plan) {
                    $lic = $plan['numero_licence'] ?? null;
                    if ($lic) $licencesFromPlan[$lic] = $plan['user_nom'] ?? null;
                }
            }
        }
        if (!empty($licencesFromPlan)) {
            try {
                $inscResponse = $this->apiService->makeRequest("concours/{$concoursId}/inscriptions", 'GET');
                if ($inscResponse['success']) {
                    $inscriptions = $this->apiService->unwrapData($inscResponse);
                    if (is_array($inscriptions)) {
                        foreach ($inscriptions as $insc) {
                            $lic = $insc['numero_licence'] ?? null;
                            if ($lic) {
                                $inscriptionsMap[$lic] = [
                                    'user_nom' => $insc['user_nom'] ?? '',
                                    'numero_licence' => $lic,
                                    'id_club' => $insc['id_club'] ?? null,
                                    'club_name' => $insc['club_name'] ?? $insc['id_club'] ?? null,
                                    'nom' => $insc['user_nom'] ?? '',
                                    'name' => $insc['user_nom'] ?? '',
                                    'clubName' => $insc['club_name'] ?? null,
                                    'arme' => $insc['arme'] ?? null,
                                    'categorie_classement' => $insc['categorie_classement'] ?? null
                                ];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur inscriptions plan peloton: ' . $e->getMessage());
            }
        }

        $title = 'Plan de peloton - ' . ($concours->titre_competition ?? $concours->nom ?? 'Concours');
        $additionalJS = ['/public/assets/js/plan-peloton.js'];
        include 'app/Views/layouts/header.php';
        include 'app/Views/concours/plan-peloton.php';
        include 'app/Views/layouts/footer.php';
    }
}
