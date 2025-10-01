<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/cors.php';

// S'assurer que les headers CORS sont envoyés
ensureCorsHeaders();

$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$userModel = new User();
$eventModel = new Event();

// Lister tous les événements
if (preg_match('/^\/list$/', $path) && $method === 'GET') {
    header('Content-Type: application/json');
    AuthMiddleware::requireAuth();
    
    try {
        $events = $eventModel->getUpcomingEvents();
        $response = [];
        
        foreach ($events as $event) {
            // Récupérer les membres de l'événement
            $members = $eventModel->getEventMembers($event['id']);
            $membersResponse = [];
            foreach ($members as $member) {
                $membersResponse[] = [
                    '_id' => $member['id'],
                    'name' => $member['name']
                ];
            }
            
            $response[] = [
                '_id' => $event['id'],
                'name' => $event['name'],
                'description' => $event['description'] ?? '',
                'date' => $event['date'],
                'time' => $event['time'],
                'members' => $membersResponse,
                'createdBy' => [
                    '_id' => $event['created_by'],
                    'name' => $event['creator_name']
                ],
                'createdAt' => $event['created_at'],
                'updatedAt' => $event['updated_at']
            ];
        }
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Event list error: ' . $e->getMessage());
        echo json_encode(['error' => 'Erreur lors du chargement des événements: ' . $e->getMessage()]);
        exit();
    }
}

// Créer un événement
elseif (preg_match('/^\/create$/', $path) && $method === 'POST') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit();
    }
    
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    $date = $input['date'] ?? '';
    $time = $input['time'] ?? '';
    
    if (empty($name) || empty($description) || empty($date) || empty($time)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tous les champs sont requis']);
        exit();
    }
    
    try {
        $eventId = $eventModel->create([
            'name' => $name,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'createdBy' => $user['id']
        ]);
        
        $event = $eventModel->findById($eventId);
        $members = $eventModel->getEventMembers($eventId);
        $membersResponse = [];
        foreach ($members as $member) {
            $membersResponse[] = [
                '_id' => $member['id'],
                'name' => $member['name']
            ];
        }
        
        $response = [
            '_id' => $event['id'],
            'name' => $event['name'],
            'description' => $event['description'],
            'date' => $event['date'],
            'time' => $event['time'],
            'members' => $membersResponse,
            'createdBy' => [
                '_id' => $event['created_by'],
                'name' => $event['creator_name']
            ],
            'createdAt' => $event['created_at'],
            'updatedAt' => $event['updated_at']
        ];
        
        http_response_code(201);
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la création de l\'événement']);
        exit();
    }
}

// Rejoindre un événement
elseif (preg_match('/^\/([^\/]+)\/join$/', $path, $matches) && $method === 'POST') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        if ($eventModel->isMember($eventId, $user['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Vous êtes déjà inscrit à cet événement']);
            exit();
        }
        
        $eventModel->addMember($eventId, $user['id']);
        
        $updatedEvent = $eventModel->findById($eventId);
        $members = $eventModel->getEventMembers($eventId);
        $membersResponse = [];
        foreach ($members as $member) {
            $membersResponse[] = [
                '_id' => $member['id'],
                'name' => $member['name']
            ];
        }
        
        $response = [
            '_id' => $updatedEvent['id'],
            'name' => $updatedEvent['name'],
            'description' => $updatedEvent['description'],
            'date' => $updatedEvent['date'],
            'time' => $updatedEvent['time'],
            'members' => $membersResponse,
            'createdBy' => [
                '_id' => $updatedEvent['created_by'],
                'name' => $updatedEvent['creator_name']
            ],
            'createdAt' => $updatedEvent['created_at'],
            'updatedAt' => $updatedEvent['updated_at']
        ];
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de l\'inscription']);
        exit();
    }
}

// Quitter un événement
elseif (preg_match('/^\/([^\/]+)\/leave$/', $path, $matches) && $method === 'POST') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        if (!$eventModel->isMember($eventId, $user['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Vous n\'êtes pas inscrit à cet événement']);
            exit();
        }
        
        $eventModel->removeMember($eventId, $user['id']);
        
        $updatedEvent = $eventModel->findById($eventId);
        $members = $eventModel->getEventMembers($eventId);
        $membersResponse = [];
        foreach ($members as $member) {
            $membersResponse[] = [
                '_id' => $member['id'],
                'name' => $member['name']
            ];
        }
        
        $response = [
            '_id' => $updatedEvent['id'],
            'name' => $updatedEvent['name'],
            'description' => $updatedEvent['description'],
            'date' => $updatedEvent['date'],
            'time' => $updatedEvent['time'],
            'members' => $membersResponse,
            'createdBy' => [
                '_id' => $updatedEvent['created_by'],
                'name' => $updatedEvent['creator_name']
            ],
            'createdAt' => $updatedEvent['created_at'],
            'updatedAt' => $updatedEvent['updated_at']
        ];
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors du désistement']);
        exit();
    }
}



// Mettre à jour un événement
elseif (preg_match('/^\/([^\/]+)$/', $path, $matches) && $method === 'PUT') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        exit();
    }
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        // Vérifier si l'utilisateur est admin ou créateur de l'événement
        if (!$user['is_admin'] && !$eventModel->isCreator($eventId, $user['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            exit();
        }
        
        $updateData = [];
        if (isset($input['name'])) {
            $updateData['name'] = $input['name'];
        }
        if (isset($input['description'])) {
            $updateData['description'] = $input['description'];
        }
        if (isset($input['date'])) {
            $updateData['date'] = $input['date'];
        }
        if (isset($input['time'])) {
            $updateData['time'] = $input['time'];
        }
        
        $eventModel->updateEvent($eventId, $updateData);
        
        $updatedEvent = $eventModel->findById($eventId);
        $members = $eventModel->getEventMembers($eventId);
        $membersResponse = [];
        foreach ($members as $member) {
            $membersResponse[] = [
                '_id' => $member['id'],
                'name' => $member['name']
            ];
        }
        
        $response = [
            '_id' => $updatedEvent['id'],
            'name' => $updatedEvent['name'],
            'description' => $updatedEvent['description'],
            'date' => $updatedEvent['date'],
            'time' => $updatedEvent['time'],
            'members' => $membersResponse,
            'createdBy' => [
                '_id' => $updatedEvent['created_by'],
                'name' => $updatedEvent['creator_name']
            ],
            'createdAt' => $updatedEvent['created_at'],
            'updatedAt' => $updatedEvent['updated_at']
        ];
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la mise à jour de l\'événement']);
        exit();
    }
}

// Supprimer un événement
elseif (preg_match('/^\/([^\/]+)$/', $path, $matches) && $method === 'DELETE') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        // Vérifier si l'utilisateur est admin ou créateur de l'événement
        if (!$user['is_admin'] && !$eventModel->isCreator($eventId, $user['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            exit();
        }
        
        $eventModel->deleteEvent($eventId);
        echo json_encode(['message' => 'Événement supprimé']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la suppression']);
        exit();
    }
}

// Lister les événements passés (admin uniquement)
elseif (preg_match('/^\/past$/', $path) && $method === 'GET') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    
    if (!$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès réservé aux administrateurs']);
        exit();
    }
    
    try {
        $events = $eventModel->getPastEvents();
        $response = [];
        
        foreach ($events as $event) {
            $members = $eventModel->getEventMembers($event['id']);
            $membersResponse = [];
            foreach ($members as $member) {
                $membersResponse[] = [
                    '_id' => $member['id'],
                    'name' => $member['name']
                ];
            }
            
            $response[] = [
                '_id' => $event['id'],
                'name' => $event['name'],
                'description' => $event['description'],
                'date' => $event['date'],
                'time' => $event['time'],
                'members' => $membersResponse,
                'createdBy' => [
                    '_id' => $event['created_by'],
                    'name' => $event['creator_name']
                ],
                'createdAt' => $event['created_at'],
                'updatedAt' => $event['updated_at']
            ];
        }
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors du chargement des événements passés']);
        exit();
    }
}

// Mettre à jour la date de dernière visite d'un événement
elseif (preg_match('/^\/([^\/]+)\/visit$/', $path, $matches) && $method === 'POST') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        error_log("Event visit: User {$user['_id']} visiting event {$eventId}");
        $userModel->updateEventVisit($user['id'], $eventId);
        error_log("Event visit: Visit updated successfully");
        echo json_encode(['message' => 'Dernière visite mise à jour']);
        exit();
    } catch (Exception $e) {
        error_log("Event visit error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur']);
        exit();
    }
}

// Bannir ou débannir un utilisateur d'un événement
elseif (preg_match('/^\/([^\/]+)\/ban$/', $path, $matches) && $method === 'POST') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['userId'] ?? '';
    $ban = $input['ban'] ?? false;

    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID utilisateur requis']);
        exit();
    }
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement introuvable']);
            exit();
        }

        if ($group['admin_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Non autorisé']);
            exit();
        }
        
        $targetUser = $userModel->findById($userId);
        if (!$targetUser) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur introuvable']);
            exit();
        }
        
        $userModel->updateUser($userId, ['isBanned' => $ban]);
        echo json_encode(['message' => $ban ? 'Utilisateur banni' : 'Utilisateur débanni']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur']);
        exit();
    }
}

// Récupérer la liste des participants d'un événement
elseif (preg_match('/^\/([^\/]+)\/participants$/', $path, $matches) && $method === 'GET') {
    header('Content-Type: application/json');
    error_log("DEBUG: Endpoint participants appelé pour l'événement: " . $matches[1]);
    
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            error_log("DEBUG: Événement non trouvé: " . $eventId);
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        error_log("DEBUG: Événement trouvé, récupération des membres");
        $members = $eventModel->getEventMembers($eventId);
        $participants = [];
        
        foreach ($members as $member) {
            $participants[] = [
                '_id' => $member['id'],
                'name' => $member['name'],
                'username' => $member['username'] ?? '',
                'email' => $member['email'] ?? ''
            ];
        }
        
        error_log("DEBUG: " . count($participants) . " participants trouvés");
        echo json_encode($participants);
        exit();
    } catch (Exception $e) {
        error_log("DEBUG: Erreur lors du chargement des participants: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors du chargement des participants']);
        exit();
    }
}

// Récupérer les détails d'un événement
elseif (preg_match('/^\/([^\/]+)$/', $path, $matches) && $method === 'GET') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        $members = $eventModel->getEventMembers($eventId);
        $membersResponse = [];
        foreach ($members as $member) {
            $membersResponse[] = [
                '_id' => $member['id'],
                'name' => $member['name']
            ];
        }
        
        $response = [
            '_id' => $event['id'],
            'name' => $event['name'],
            'description' => $event['description'] ?? '',
            'date' => $event['date'],
            'time' => $event['time'],
            'members' => $membersResponse,
            'createdBy' => [
                '_id' => $event['created_by'],
                'name' => $event['creator_name'] ?? 'Utilisateur inconnu'
            ],
            'createdAt' => $event['created_at'],
            'updatedAt' => $event['updated_at']
        ];
        
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la récupération de l\'événement']);
        exit();
    }
}

// Récupérer les messages d'un événement
elseif (preg_match('/^\/([^\/]+)\/messages$/', $path, $matches) && $method === 'GET') {
    header('Content-Type: application/json');
    $user = AuthMiddleware::requireAuth();
    $eventId = $matches[1];
    
    try {
        $event = $eventModel->findById($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Événement non trouvé']);
            exit();
        }
        
        // Pour l'instant, retourner un tableau vide car la table messages n'existe pas encore
        $messages = [];
        
        echo json_encode($messages);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la récupération des messages']);
        exit();
    }
}

// Récupérer les informations d'un utilisateur par son nom
elseif (preg_match('/^\/user\/(.+)$/', $path, $matches) && $method === 'GET') {
    $user = AuthMiddleware::requireAuth();
    $username = urldecode($matches[1]);
    
   if (!$user['is_admin']) {
         http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé']);
        exit();
    }
    
    try {
        $targetUser = $userModel->findByName($username);
        if (!$targetUser) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur introuvable']);
            exit();
        }
        
        echo json_encode([
            'id' => $targetUser['_id'],
            'name' => $targetUser['name'],
            'isBanned' => $targetUser['is_banned']
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur']);
        exit();
    }
}

// Route non trouvée
else {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvée']);
    exit();
}
?> 
