<?php
// Test direct de l'API pour le tir compté 125
require_once 'app/Services/ApiService.php';

$apiService = new ApiService();

echo "=== Test de l'API pour le tir compté 125 ===\n";

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
