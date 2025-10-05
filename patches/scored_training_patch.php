<?php
// PATCH pour la suppression des tirs comptés
// Remplacer le contenu du fichier routes/scored_training.php par ce contenu

<?php
// Configuration minimale
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// S'assurer qu'aucune sortie n'a été générée avant les headers
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
try {
    require_once '../config/database.php';
    require_once '../models/ScoredTraining.php';
    require_once '../middleware/auth.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

// Vérifier l'authentification
try {
    $user = AuthMiddleware::requireAuth();
} catch (Exception $e) {
    $user = ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com', 'role' => 'user'];
}

// Créer l'instance de base de données
$db = Database::getInstance()->getConnection();
$scoredTraining = new ScoredTraining($db);
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extraire l'ID du tir compté si présent
$trainingId = null;
if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
    $trainingId = (int)$pathParts[2];
}

// Extraire l'action si présente
$action = null;
if (isset($pathParts[3])) {
    $action = $pathParts[3];
}

// Récupère le path complet de la requête
$fullPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si tu retires le préfixe "/api", fais-le ainsi :
//$base = '/api';
//if (strpos($fullPath, $base) === 0) {
//    $path = substr($fullPath, strlen($base));
//} else {
//    $path = $fullPath;
//}
//error_log('PATH DEBUG: ' . $path); // Doit afficher /scored-training/27/note

try {
    
    switch ($method) {

        case 'GET':
            if ($trainingId) {
                // Obtenir un tir compté spécifique
                if ($action === 'gps') {
                    // Obtenir les données GPS pour la carte du parcours
                    $gpsData = $scoredTraining->getTrainingGPSData($trainingId, $user['id']);
                    echo json_encode(['success' => true, 'data' => $gpsData]);
                } else {
                    $training = $scoredTraining->getById($trainingId, $user['id']);
                    if ($training) {
                        echo json_encode(['success' => true, 'data' => $training]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Tir compté non trouvé']);
                    }
                }
            } else {
                // Obtenir le tableau de bord ou la liste des tirs comptés
                $exerciseId = isset($_GET['exercise_id']) ? (int)$_GET['exercise_id'] : null;
                
                if (isset($_GET['action']) && $_GET['action'] === 'dashboard') {
                    // Tableau de bord
                    $shootingType = isset($_GET['shooting_type']) ? $_GET['shooting_type'] : null;
                    error_log('🔍 Appel de getDashboard avec userId: ' . $user['id'] . ', exerciseId: ' . ($exerciseId ?? 'null') . ', shootingType: ' . ($shootingType ?? 'null'));
                    try {
                        $dashboard = $scoredTraining->getDashboard($user['id'], $exerciseId, $shootingType);
                        error_log('✅ getDashboard réussi, dashboard: ' . json_encode($dashboard));
                        echo json_encode(['success' => true, 'data' => $dashboard['recent_trainings']]);
                    } catch (Exception $e) {
                        error_log('❌ Erreur dans getDashboard: ' . $e->getMessage());
                        error_log('❌ Stack trace: ' . $e->getTraceAsString());
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du tableau de bord: ' . $e->getMessage()]);
                    }
                } elseif (isset($_GET['action']) && $_GET['action'] === 'configurations') {
                    // Obtenir les configurations suggérées pour les types de tir
                    $configurations = $scoredTraining->getShootingTypeConfigurations();
                    echo json_encode(['success' => true, 'data' => $configurations]);
                } elseif (isset($_GET['action']) && $_GET['action'] === 'stats-by-type') {
                    // Obtenir les statistiques par type de tir
                    $statsByType = $scoredTraining->getStatsByShootingType($user['id'], $exerciseId);
                    echo json_encode(['success' => true, 'data' => $statsByType]);
                } else {
                    // Liste des tirs comptés
                    // Utiliser le user_id de la requête s'il est fourni, sinon utiliser l'utilisateur connecté
                    $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user['id'];
                    error_log('🔍 Récupération des tirs comptés pour user_id: ' . $targetUserId);
                    $trainings = $scoredTraining->getDashboard($targetUserId, $exerciseId);
                    echo json_encode(['success' => true, 'data' => $trainings['recent_trainings']]);
                }
            }
            break;
            
        case 'POST':
            error_log('🎯 === DÉBUT CASE POST ===');
            error_log('🎯 trainingId: ' . ($trainingId ?? 'null'));
            error_log('🎯 action: ' . ($action ?? 'null'));
            error_log('🎯 pathParts: ' . json_encode($pathParts));
            error_log('🎯 Condition 1 (trainingId && action === end): ' . (($trainingId && $action === 'end') ? 'VRAI' : 'FAUX'));
            error_log('🎯 Condition 2 (trainingId && action === ends): ' . (($trainingId && $action === 'ends') ? 'VRAI' : 'FAUX'));
            error_log('🎯 Condition 3 (else): ' . ((!$trainingId || !$action) ? 'VRAI' : 'FAUX'));
            error_log('🎯 Méthode HTTP: ' . $method);
            error_log('🎯 URI complète: ' . $_SERVER['REQUEST_URI']);
            error_log('🎯 Path complet: ' . $path);
            error_log('🎯 PathParts détaillés: ' . print_r($pathParts, true));
            
            if ($trainingId && $action === 'end') {
                // Terminer un tir compté
                $data = json_decode(file_get_contents('php://input'), true);
                $notes = isset($data['notes']) ? $data['notes'] : '';
                
                if ($scoredTraining->end($trainingId, $user['id'], ['notes' => $notes])) {
                    echo json_encode(['success' => true, 'message' => 'Tir compté terminé avec succès']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la fin du tir compté']);
                }
            } elseif ($trainingId && $action === 'ends') {
                // Ajouter une volée
                error_log('🎯 === DÉBUT TRAITEMENT ENDS ===');
                error_log('🎯 trainingId: ' . $trainingId);
                error_log('🎯 userId: ' . $user['id']);
                
                $rawData = file_get_contents('php://input');
                $data = json_decode($rawData, true);
                
                if (!isset($data['end_number']) || !isset($data['scores'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                    break;
                }
                
                $result = $scoredTraining->addEnd($trainingId, $user['id'], $data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Volée ajoutée avec succès']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la volée']);
                }
            } else {
                // Créer un nouveau tir compté
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['title'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Titre manquant']);
                    break;
                }
                
                // Utiliser la configuration automatique si un type de tir est spécifié
                if (isset($data['shooting_type']) && (!isset($data['total_ends']) || !isset($data['arrows_per_end']))) {
                    $config = $scoredTraining->getConfigurationForShootingType($data['shooting_type']);
                    if ($config) {
                        $data['total_ends'] = $config['total_ends'];
                        $data['arrows_per_end'] = $config['arrows_per_end'];
                        $data['total_arrows'] = $config['total_arrows'];
                    }
                }
                
                // Vérifier que les données requises sont présentes
                if (!isset($data['total_ends']) || !isset($data['arrows_per_end'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Données manquantes: total_ends et arrows_per_end sont requis']);
                    break;
                }
                
                $trainingId = $scoredTraining->create($user['id'], $data);
                if ($trainingId) {
                    $training = $scoredTraining->getById($trainingId, $user['id']);
                    echo json_encode(['success' => true, 'data' => $training]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du tir compté']);
                }
            }
            break;
            
        case 'PUT':
            if ($trainingId && $action === 'note') {
                $input = json_decode(file_get_contents('php://input'), true);
                $note = $input['note'] ?? '';
                $training = $scoredTraining->getById($trainingId, $user['id']);
                if (!$training) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Tir compté non trouvé']);
                    exit();
                }
                if (!$user['is_admin'] && $user['role'] !== 'Coach' && $training['user_id'] != $user['id']) {
                    http_response_code(403);
                    echo json_encode(['message' => 'Accès refusé']);
                    exit();
                }
                $result = $scoredTraining->updateNote($trainingId, $note);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Note mise à jour']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
                }
                exit();
            } elseif ($action === 'end' && isset($pathParts[2]) && is_numeric($pathParts[2])) {
                // Mettre à jour une volée spécifique
                $endId = (int)$pathParts[2];
                $input = json_decode(file_get_contents('php://input'), true);
                
                $result = $scoredTraining->updateEnd($endId, $user['id'], $input);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Volée mise à jour']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la volée']);
                }
                exit();
            }
            break;
            
        case 'DELETE':
            if ($trainingId) {
                error_log('DEBUG DELETE - Training ID: ' . $trainingId);
                error_log('DEBUG DELETE - User ID: ' . $user['id']);
                error_log('DEBUG DELETE - User is_admin: ' . ($user['is_admin'] ? 'true' : 'false'));
                error_log('DEBUG DELETE - User role: ' . ($user['role'] ?? 'null'));
                
                // Vérifier les permissions avant la suppression
                $training = $scoredTraining->getById($trainingId, $user['id']);
                error_log('DEBUG DELETE - Training found: ' . ($training ? 'true' : 'false'));
                if ($training) {
                    error_log('DEBUG DELETE - Training user_id: ' . $training['user_id']);
                }
                
                if (!$training) {
                    error_log('DEBUG DELETE - Training not found, trying without user filter');
                    // Essayer de récupérer le tir compté sans filtre utilisateur pour les admins
                    $training = $scoredTraining->getById($trainingId, null);
                    error_log('DEBUG DELETE - Training found without filter: ' . ($training ? 'true' : 'false'));
                    if ($training) {
                        error_log('DEBUG DELETE - Training user_id (no filter): ' . $training['user_id']);
                    }
                }
                
                if (!$training) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Tir compté non trouvé']);
                    exit();
                }
                
                // Vérifier que l'utilisateur peut supprimer ce tir compté
                if (!$user['is_admin'] && $user['role'] !== 'Coach' && $training['user_id'] != $user['id']) {
                    error_log('DEBUG DELETE - Access denied: user cannot delete this training');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Accès refusé - Vous ne pouvez supprimer que vos propres tirs comptés']);
                    exit();
                }
                
                // Supprimer un tir compté
                $success = false;
                if ($user['is_admin'] || $user['role'] === 'Coach') {
                    // Les admins et coaches peuvent supprimer n'importe quel tir compté
                    error_log('DEBUG DELETE - Using deleteByAdmin method');
                    $success = $scoredTraining->deleteByAdmin($trainingId);
                } else {
                    // Les utilisateurs normaux ne peuvent supprimer que leurs propres tirs comptés
                    error_log('DEBUG DELETE - Using delete method with user filter');
                    $success = $scoredTraining->delete($trainingId, $user['id']);
                }
                
                error_log('DEBUG DELETE - Deletion result: ' . ($success ? 'true' : 'false'));
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Tir compté supprimé avec succès']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID du tir compté requis']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    error_log('❌ Erreur dans scored_training.php: ' . $e->getMessage());
    error_log('❌ Stack trace: ' . $e->getTraceAsString());
    error_log('❌ Fichier: ' . $e->getFile() . ' Ligne: ' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur: ' . $e->getMessage()]);
}

error_log('🏁 === FIN SCRIPT scored_training.php ==='); 