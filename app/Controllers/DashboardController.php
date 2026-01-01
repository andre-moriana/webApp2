<?php
// Inclure ApiService
require_once __DIR__ . '/../Services/ApiService.php';
require_once __DIR__ . '/../Config/PermissionHelper.php';

class DashboardController {
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
        
        $title = 'Tableau de bord - Portail Archers de Gémenos';
        
        // Récupérer les statistiques
        $stats = $this->getStats();
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/dashboard.js'];
        
        // Inclure le header
        include 'app/Views/layouts/header.php';
        
        // Inclure la vue du dashboard
        include 'app/Views/dashboard/index.php';
        
        // Inclure le footer
        include 'app/Views/layouts/footer.php';
    }
    
    private function getStats() {
        $stats = [
            'users' => 0,
            'groups' => 0,
            'trainings' => 0,
            'events' => 0,
            'exercises' => 0,
            'clubs_regional' => 0,
            'clubs_departmental' => 0,
            'clubs_total' => 0,
            'users_pending_validation' => 0,
            'users_pending_deletion' => 0,
            'clubs_regional_list' => [],
            'clubs_departmental_list' => [],
            'clubs_by_committee' => [],
            'all_clubs' => [],
            'users_list' => [],
            'users_by_club' => [],
            'groups_list' => [],
            'groups_by_club' => [],
            'topics_by_group' => [],
            'topics_total' => 0,
            'events_list' => [],
            'events_by_club' => []
        ];
        
        try {
            // Récupérer le nombre d'utilisateurs
            $usersResponse = $this->apiService->getUsers();
            if ($usersResponse['success'] && !empty($usersResponse['data']['users'])) {
                $users = $usersResponse['data']['users'];
                $stats['users'] = count($users);
                
                $currentUserId = $_SESSION['user']['id'] ?? null;
                $currentUserClubId = $_SESSION['user']['clubId'] ?? null;
                
                // Compter les utilisateurs en attente de validation et de suppression
                foreach ($users as $user) {
                    $status = $user['status'] ?? 'active';
                    $deletionPending = $user['deletion_pending'] ?? $user['deletionPending'] ?? ($status === 'pending_deletion');
                    $clubId = $user['clubId'] ?? $user['club_id'] ?? '';
                    $userId = $user['id'] ?? $user['_id'] ?? '';
                    
                    // Vérifier si l'utilisateur connecté peut voir cet utilisateur
                    $canView = ($currentUserId == $userId) || PermissionHelper::canViewUser($userId, $currentUserClubId);
                    
                    // Stocker les données de l'utilisateur
                    $userData = [
                        'id' => $userId,
                        'name' => ($user['firstName'] ?? '') . ' ' . ($user['name'] ?? $user['lastName'] ?? ''),
                        'email' => $user['email'] ?? '',
                        'clubId' => $clubId,
                        'status' => $status,
                        'canView' => $canView
                    ];
                    
                    $stats['users_list'][] = $userData;
                    
                    // Associer l'utilisateur à son club
                    if (!empty($clubId)) {
                        if (!isset($stats['users_by_club'][$clubId])) {
                            $stats['users_by_club'][$clubId] = [];
                        }
                        $stats['users_by_club'][$clubId][] = $userData;
                    }
                    
                    if ($status === 'pending') {
                        $stats['users_pending_validation']++;
                    }
                    if ($deletionPending || $status === 'pending_deletion') {
                        $stats['users_pending_deletion']++;
                    }
                }
            }
            
            // Récupérer les groupes directement via l'API
            $groupsResponse = $this->apiService->makeRequest('groups/list', 'GET');
            
            if ($groupsResponse['success']) {
                // L'API retourne directement le tableau de groupes dans ['data']
                if (!empty($groupsResponse['data']) && is_array($groupsResponse['data'])) {
                    $groups = $groupsResponse['data'];
                    $stats['groups'] = count($groups);
                    
                    // DEBUG: Afficher le premier groupe
                    if (!empty($groups[0])) {
                        error_log("DEBUG Premier groupe: " . json_encode($groups[0]));
                    }
                    
                    // Traiter chaque groupe
                    $groupsById = []; // Garder une référence indexée par ID
                    foreach ($groups as $group) {
                        $groupId = $group['id'] ?? $group['_id'] ?? '';
                        $groupClubId = $group['club_id'] ?? '';
                        $groupName = $group['name'] ?? 'Groupe sans nom';
                        
                        error_log("DEBUG Groupe ID: " . var_export($groupId, true) . " Type: " . gettype($groupId));
                        
                        $groupData = [
                            'id' => $groupId,
                            'name' => $groupName,
                            'club_id' => $groupClubId,
                            'topics' => []
                        ];
                        
                        $groupsById[$groupId] = $groupData;
                        
                        // Initialiser la liste des sujets pour ce groupe
                        $stats['topics_by_group'][$groupId] = [];
                    }
                    
                    // Récupérer les sujets (topics) via l'API backend
                    try {
                        $topicsResponse = $this->apiService->makeRequest('topics/list', 'GET');
                        
                        // L'API retourne une double encapsulation: $response['data']['data']
                        if ($topicsResponse['success'] && !empty($topicsResponse['data']['data'])) {
                            $topics = is_array($topicsResponse['data']['data']) ? $topicsResponse['data']['data'] : [];
                            $stats['topics_total'] = count($topics);
                            
                            // Associer les sujets aux groupes
                            foreach ($topics as $topic) {
                                // Convertir group_id en integer pour correspondre aux clés de $groupsById
                                $topicGroupId = (int)($topic['group_id'] ?? 0);
                                
                                $topicData = [
                                    'id' => $topic['id'] ?? '',
                                    'title' => $topic['title'] ?? 'Sujet sans titre',
                                    'groupId' => $topicGroupId,
                                    'description' => $topic['description'] ?? '',
                                    'created_by_name' => $topic['created_by_name'] ?? ''
                                ];
                                
                                // Ajouter aux topics par groupe
                                if (!isset($stats['topics_by_group'][$topicGroupId])) {
                                    $stats['topics_by_group'][$topicGroupId] = [];
                                }
                                $stats['topics_by_group'][$topicGroupId][] = $topicData;
                                
                                // Ajouter les sujets aux groupes
                                if (isset($groupsById[$topicGroupId])) {
                                    $groupsById[$topicGroupId]['topics'][] = $topicData;
                                }
                            }
                        } else {
                            $stats['topics_total'] = 0;
                        }
                    } catch (Exception $e) {
                        error_log('Erreur lors de la récupération des topics: ' . $e->getMessage());
                        $stats['topics_total'] = 0;
                    }
                    
                    // TOUJOURS construire groups_list et groups_by_club, même si les topics ont échoué
                    foreach ($groupsById as $groupData) {
                        $stats['groups_list'][] = $groupData;
                        
                        // Associer le groupe à son club
                        if (!empty($groupData['club_id'])) {
                            if (!isset($stats['groups_by_club'][$groupData['club_id']])) {
                                $stats['groups_by_club'][$groupData['club_id']] = [];
                            }
                            $stats['groups_by_club'][$groupData['club_id']][] = $groupData;
                        }
                    }
                }
            }
            
            // Récupérer les événements
            try {
                $eventsResponse = $this->apiService->makeRequest('events/list', 'GET');
                if ($eventsResponse['success'] && !empty($eventsResponse['data'])) {
                    $events = is_array($eventsResponse['data']) ? $eventsResponse['data'] : [];
                    $stats['events'] = count($events);
                    
                    foreach ($events as $event) {
                        $eventClubId = $event['clubId'] ?? $event['club_id'] ?? '';
                        $eventData = [
                            'id' => $event['id'] ?? $event['_id'] ?? '',
                            'title' => $event['title'] ?? $event['name'] ?? 'Événement sans titre',
                            'date' => $event['date'] ?? $event['eventDate'] ?? '',
                            'clubId' => $eventClubId
                        ];
                        
                        $stats['events_list'][] = $eventData;
                        
                        // Associer l'événement à son club
                        if (!empty($eventClubId)) {
                            if (!isset($stats['events_by_club'][$eventClubId])) {
                                $stats['events_by_club'][$eventClubId] = [];
                            }
                            $stats['events_by_club'][$eventClubId][] = $eventData;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des événements: ' . $e->getMessage());
                $stats['events'] = 3; // Valeur par défaut
            }
            
            // Récupérer le nombre d'exercices
            $exercisesResponse = $this->apiService->getExercises();
            if ($exercisesResponse['success'] && !empty($exercisesResponse['data'])) {
                $stats['exercises'] = count($exercisesResponse['data']);
            }
            
            // Récupérer les clubs et compter par type
            $clubsResponse = $this->apiService->getClubs();
            if ($clubsResponse['success'] && !empty($clubsResponse['data']['clubs'])) {
                $clubs = $clubsResponse['data']['clubs'];
                if (is_array($clubs)) {
                    // IDs spéciaux à exclure du comptage
                    $excludedIds = ['0000000', '0000001', '0000002', '0000005', '0000006'];
                    
                    foreach ($clubs as $club) {
                        $clubId = $club['nameshort'] ?? $club['nameShort'] ?? '';
                        $clubName = $club['name'] ?? 'Club sans nom';
                        $clubRealId = $club['id'] ?? $club['_id'] ?? $clubId; // ID MongoDB réel
                        
                        // Ignorer les IDs spéciaux
                        if (in_array($clubId, $excludedIds)) {
                            continue;
                        }
                        
                        // Comité Régional : finit par 00000 sauf 0000000
                        if (preg_match('/00000$/', $clubId) && $clubId !== '0000000') {
                            $stats['clubs_regional']++;
                            $stats['clubs_regional_list'][] = [
                                'id' => $clubId,
                                'realId' => $clubRealId,
                                'name' => $clubName
                            ];
                            $stats['clubs_by_committee'][$clubId] = [];
                        }
                        // Comité Départemental : finit par 000 (mais pas 00000 qui sont régionaux)
                        elseif (preg_match('/000$/', $clubId) && !preg_match('/00000$/', $clubId)) {
                            $stats['clubs_departmental']++;
                            $stats['clubs_departmental_list'][] = [
                                'id' => $clubId,
                                'realId' => $clubRealId,
                                'name' => $clubName
                            ];
                            $stats['clubs_by_committee'][$clubId] = [];
                        }
                        // Sinon c'est un club normal (ne finit pas par 000)
                        else {
                            $stats['clubs_total']++;
                            $clubData = [
                                'id' => $clubId,
                                'realId' => $clubRealId,
                                'name' => $clubName
                            ];
                            $stats['all_clubs'][] = $clubData;
                            
                            // Déterminer le comité parent (comité départemental = 3 premiers chiffres + '000')
                            // ou comité régional (2 premiers chiffres + '00000')
                            if (strlen($clubId) >= 7) {
                                // Vérifier d'abord le comité départemental
                                $departmentalId = substr($clubId, 0, -3) . '000';
                                if (isset($stats['clubs_by_committee'][$departmentalId])) {
                                    $stats['clubs_by_committee'][$departmentalId][] = $clubData;
                                }
                                
                                // Ajouter aussi au comité régional
                                $regionalId = substr($clubId, 0, -5) . '00000';
                                if (isset($stats['clubs_by_committee'][$regionalId])) {
                                    $stats['clubs_by_committee'][$regionalId][] = $clubData;
                                }
                            }
                        }
                    }
                }
            }
            
            // Pour les autres statistiques, on utilise des valeurs par défaut
            $stats['trainings'] = 12; // Valeur par défaut
            
        } catch (Exception $e) {
            // En cas d'erreur, on garde les valeurs par défaut
            error_log('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
        }
        return $stats;
    }
}
