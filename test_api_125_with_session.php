<?php
// Test avec session simulée
session_start();

// Simuler un token (utilisez un vrai token de votre session)
$_SESSION['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vODIuNjcuMTIzLjIyOjI1MDAwL2FwaS9hdXRoL2xvZ2luIiwiaWF0IjoxNzM1OTU0NDQyLCJleHAiOjE3MzU5NTgwNDIsIm5iZiI6MTczNTk1NDQ0MiwianRpIjoiV2J1V1F6V2J1V1F6Iiwic3ViIjoiMSIsInByZXZpb3VzX2NsYWltX3RpbWUiOjE3MzU5NTQ0NDJ9.example';

require_once 'app/Services/ApiService.php';

$apiService = new ApiService();

echo "=== Test de l'API pour le tir compté 125 avec session ===\n";

try {
    $response = $apiService->getScoredTrainingById(125);
    
    echo "Réponse complète:\n";
    print_r($response);
    
    if ($response['success'] && !empty($response['data'])) {
        echo "\n=== Données du tir compté ===\n";
        print_r($response['data']);
        
        if (isset($response['data']['user_id'])) {
            echo "\n✅ user_id trouvé: " . $response['data']['user_id'] . "\n";
        } else {
            echo "\n❌ user_id manquant dans les données\n";
            echo "Clés disponibles: " . implode(', ', array_keys($response['data'])) . "\n";
        }
    } else {
        echo "\n❌ Échec de la récupération du tir compté\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
