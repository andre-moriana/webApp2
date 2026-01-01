<?php
// Script pour afficher ce que l'API topics/list retourne vraiment
session_start();

// Vérifier la session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Vous devez être connecté pour accéder à cette page.");
}

require_once __DIR__ . '/app/Services/ApiService.php';

$apiService = new ApiService();

echo "<h1>Test API Topics/List</h1>";
echo "<h2>1. Appel API topics/list</h2>";

try {
    $response = $apiService->makeRequest('topics/list', 'GET');
    
    echo "<h3>Réponse brute:</h3>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    if (isset($response['success']) && $response['success']) {
        echo "<h3 style='color: green;'>✓ API retourne success = true</h3>";
        
        if (isset($response['data']) && is_array($response['data'])) {
            echo "<h3>Nombre de topics: " . count($response['data']) . "</h3>";
            
            if (count($response['data']) > 0) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Title</th><th>Group ID</th><th>Type</th><th>Created By</th></tr>";
                
                foreach ($response['data'] as $topic) {
                    echo "<tr>";
                    echo "<td>" . ($topic['id'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($topic['title'] ?? 'N/A') . "</td>";
                    echo "<td>" . var_export($topic['group_id'] ?? 'N/A', true) . "</td>";
                    echo "<td>" . gettype($topic['group_id'] ?? null) . "</td>";
                    echo "<td>" . htmlspecialchars($topic['created_by_name'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p style='color: orange;'><strong>⚠ L'API retourne un tableau vide!</strong></p>";
            }
        } else {
            echo "<p style='color: red;'><strong>✗ response['data'] n'est pas un tableau!</strong></p>";
            echo "<pre>" . var_export($response['data'] ?? null, true) . "</pre>";
        }
    } else {
        echo "<h3 style='color: red;'>✗ API retourne success = false</h3>";
        echo "<p><strong>Message:</strong> " . ($response['message'] ?? 'N/A') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
