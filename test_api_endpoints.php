<?php
require_once 'app/Services/ApiService.php';

echo "=== DIAGNOSTIC API BACKEND ===\n";

$apiService = new ApiService();

// 1. Test de connexion
echo "1. Connexion avec admin/admin123:\n";
$loginResult = $apiService->login('admin', 'admin123');
if ($loginResult['success']) {
    echo "✓ Connexion réussie\n";
    echo "Token: " . substr($loginResult['token'], 0, 30) . "...\n";
    echo "User: " . json_encode($loginResult['user'], JSON_PRETTY_PRINT) . "\n";
    
    // 2. Test des endpoints disponibles
    echo "\n2. Test des endpoints:\n";
    
    // Test users
    echo "Test endpoint 'users':\n";
    $usersResult = $apiService->makeRequest("users", "GET");
    echo "Status: " . ($usersResult['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($usersResult['success'] ? 'OUI' : 'NON') . "\n";
    echo "Message: " . ($usersResult['message'] ?? 'N/A') . "\n";
    
    // Test groups/list
    echo "\nTest endpoint 'groups/list':\n";
    $groupsResult = $apiService->makeRequest("groups/list", "GET");
    echo "Status: " . ($groupsResult['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($groupsResult['success'] ? 'OUI' : 'NON') . "\n";
    echo "Message: " . ($groupsResult['message'] ?? 'N/A') . "\n";
    
    // Test groups
    echo "\nTest endpoint 'groups':\n";
    $groupsResult2 = $apiService->makeRequest("groups", "GET");
    echo "Status: " . ($groupsResult2['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($groupsResult2['success'] ? 'OUI' : 'NON') . "\n";
    echo "Message: " . ($groupsResult2['message'] ?? 'N/A') . "\n";
    
    // Test group/list
    echo "\nTest endpoint 'group/list':\n";
    $groupsResult3 = $apiService->makeRequest("group/list", "GET");
    echo "Status: " . ($groupsResult3['status_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($groupsResult3['success'] ? 'OUI' : 'NON') . "\n";
    echo "Message: " . ($groupsResult3['message'] ?? 'N/A') . "\n";
    
} else {
    echo " Échec de connexion: " . $loginResult['message'] . "\n";
}
?>
