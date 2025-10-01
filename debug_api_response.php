<?php
// Script de debug pour voir la réponse de l'API
$file = 'app/Controllers/TrainingController.php';
$content = file_get_contents($file);

// Ajouter du debug dans getTrainings
$debugCode = '
            error_log("DEBUG API - Endpoint appelé: " . $endpoint);
            error_log("DEBUG API - UserId passé: " . $userId);
            error_log("DEBUG API - Réponse: " . json_encode($response));
';

$content = str_replace(
    '            $response = $this->apiService->getTrainings($userId);
            
            if ($response[\'success\'] && !empty($response[\'data\'])) {',
    '            $response = $this->apiService->getTrainings($userId);' . $debugCode . '
            
            if ($response[\'success\'] && !empty($response[\'data\'])) {',
    $content
);

file_put_contents($file, $content);
echo "Debug API ajouté avec succès!\n";
?>
