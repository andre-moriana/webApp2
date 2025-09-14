<?php
require_once 'app/Services/ApiService.php';

echo "=== DIAGNOSTIC DÉTAILLÉ API ===\n";

$apiService = new ApiService();

// Connexion
$loginResult = $apiService->login('admin', 'admin123');
if ($loginResult['success']) {
    echo " Connexion réussie\n";
    
    // Test détaillé des groupes
    echo "\n=== TEST DÉTAILLÉ GROUPES ===\n";
    
    // Test groups/list
    echo "1. Endpoint 'groups/list':\n";
    $result1 = $apiService->makeRequest("groups/list", "GET");
    echo "Status: " . ($result1['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($result1['success'] ? 'OUI' : 'NON') . "\n";
    echo "Raw response: " . substr($result1['raw_response'] ?? '', 0, 500) . "...\n";
    echo "Data: " . json_encode($result1['data'], JSON_PRETTY_PRINT) . "\n";
    
    // Test groups
    echo "\n2. Endpoint 'groups':\n";
    $result2 = $apiService->makeRequest("groups", "GET");
    echo "Status: " . ($result2['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($result2['success'] ? 'OUI' : 'NON') . "\n";
    echo "Raw response: " . substr($result2['raw_response'] ?? '', 0, 500) . "...\n";
    echo "Data: " . json_encode($result2['data'], JSON_PRETTY_PRINT) . "\n";
    
    // Test de la méthode getGroups()
    echo "\n3. Méthode getGroups():\n";
    $groupsResult = $apiService->getGroups();
    echo "Success: " . ($groupsResult['success'] ? 'OUI' : 'NON') . "\n";
    echo "Message: " . ($groupsResult['message'] ?? 'N/A') . "\n";
    echo "Groups count: " . count($groupsResult['data']['groups'] ?? []) . "\n";
    echo "Groups: " . json_encode($groupsResult['data']['groups'] ?? [], JSON_PRETTY_PRINT) . "\n";
    
} else {
    echo " Échec de connexion: " . $loginResult['message'] . "\n";
}
?>
