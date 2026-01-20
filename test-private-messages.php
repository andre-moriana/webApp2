<?php
/**
 * Page de test pour diagnostiquer les problèmes de messages privés
 * À supprimer après le débogage
 */

// Démarrer la session
session_start();

// Charger l'autoloader
require_once 'app/Config/Autoloader.php';

// Charger l'API Service
require_once 'app/Services/ApiService.php';

// Vérifier l'authentification
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Vous devez être connecté pour accéder à cette page. <a href="/login">Se connecter</a>');
}

$apiService = new ApiService();

echo "<h1>Test de diagnostic - Messages Privés</h1>";
echo "<p>Page de test pour identifier les problèmes</p>";
echo "<hr>";

// Test 1 : Informations de session
echo "<h2>1. Informations de session</h2>";
echo "<pre>";
echo "User ID: " . ($_SESSION['user']['id'] ?? 'NON DÉFINI') . "\n";
echo "Username: " . ($_SESSION['user']['username'] ?? 'NON DÉFINI') . "\n";
echo "User data: " . json_encode($_SESSION['user'] ?? [], JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Test 2 : Récupération des utilisateurs
echo "<h2>2. Récupération des utilisateurs</h2>";
try {
    $response = $apiService->getUsers();
    
    echo "<p><strong>Succès:</strong> " . ($response['success'] ? 'Oui' : 'Non') . "</p>";
    
    if ($response['success'] && !empty($response['data']['users'])) {
        $users = $response['data']['users'];
        echo "<p><strong>Nombre total d'utilisateurs:</strong> " . count($users) . "</p>";
        
        // Afficher les 3 premiers utilisateurs
        echo "<h3>Premiers utilisateurs (max 3)</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>_id</th><th>Username</th><th>FirstName</th><th>LastName</th><th>Name</th><th>Email</th><th>Status</th></tr>";
        
        $count = 0;
        foreach ($users as $user) {
            if ($count >= 3) break;
            
            echo "<tr>";
            echo "<td>" . ($user['id'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['_id'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['username'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['firstName'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['lastName'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['name'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['email'] ?? '<em>vide</em>') . "</td>";
            echo "<td>" . ($user['status'] ?? '<em>vide</em>') . "</td>";
            echo "</tr>";
            
            $count++;
        }
        
        echo "</table>";
        
        // Test de filtrage
        echo "<h3>Test de filtrage</h3>";
        $currentUserId = $_SESSION['user']['id'] ?? null;
        $filteredUsers = array_filter($users, function($user) use ($currentUserId) {
            $userId = $user['id'] ?? $user['_id'] ?? '';
            $status = $user['status'] ?? 'active';
            return !empty($userId) && $userId !== $currentUserId && $status === 'active';
        });
        
        echo "<p><strong>Utilisateurs après filtrage:</strong> " . count($filteredUsers) . "</p>";
        echo "<p><strong>Utilisateur actuel (filtré):</strong> " . $currentUserId . "</p>";
        
    } else {
        echo "<p style='color: red;'><strong>Erreur:</strong> Pas de données utilisateurs</p>";
        echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 3 : Récupération des conversations
echo "<hr>";
echo "<h2>3. Récupération des conversations</h2>";
try {
    $response = $apiService->makeRequest('private-messages/conversations', 'GET');
    
    echo "<p><strong>Succès:</strong> " . (($response['success'] ?? false) ? 'Oui' : 'Non') . "</p>";
    
    if ($response['success'] ?? false) {
        $conversations = $response['data'] ?? [];
        echo "<p><strong>Nombre de conversations:</strong> " . count($conversations) . "</p>";
        
        if (!empty($conversations)) {
            echo "<h3>Conversations</h3>";
            echo "<pre>" . json_encode($conversations, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>Aucune conversation existante (normal si premier test)</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>Erreur:</strong> Impossible de récupérer les conversations</p>";
        echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4 : Vérifier les routes
echo "<hr>";
echo "<h2>4. Vérification des routes</h2>";
echo "<ul>";
echo "<li><a href='/private-messages'>Page Messages Privés</a></li>";
echo "<li><a href='/api/private-messages/conversations'>API Conversations (JSON)</a></li>";
echo "</ul>";

// Test 5 : Vérifier l'environnement
echo "<hr>";
echo "<h2>5. Configuration</h2>";
echo "<pre>";
echo "API_BASE_URL: " . ($_ENV['API_BASE_URL'] ?? 'NON DÉFINI') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Session ID: " . session_id() . "\n";
echo "</pre>";

echo "<hr>";
echo "<p><a href='/private-messages'>Retour aux Messages Privés</a></p>";
?>
