<?php
// Test direct de l'API externe pour ajouter une volée
session_start();

// Simuler une session utilisateur
$_SESSION['logged_in'] = true;
$_SESSION['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGV4YW1wbGUuY29tIiwiaWF0IjoxNzM1OTg2MjExLCJleHAiOjE3MzU5ODk4MTF9.abc123'; // Token de test

$trainingId = 128; // ID du tir compté
$endData = [
    'end_number' => 1,
    'total_score' => 27,
    'comment' => 'Test volée',
    'target_category' => '40cm',
    'shooting_position' => null,
    'shots' => [
        ['arrow_number' => 1, 'score' => 9],
        ['arrow_number' => 2, 'score' => 9],
        ['arrow_number' => 3, 'score' => 9]
    ]
];

$endpoint = "http://82.67.123.22:25000/api/scored-training?training_id=" . $trainingId . "&action=ends";

echo "=== TEST API EXTERNE ===\n";
echo "URL: " . $endpoint . "\n";
echo "Données: " . json_encode($endData, JSON_PRETTY_PRINT) . "\n";
echo "Token: " . substr($_SESSION['token'], 0, 20) . "...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($endData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $_SESSION['token']
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== RÉPONSE ===\n";
echo "Code HTTP: " . $httpCode . "\n";
echo "Erreur cURL: " . ($error ?: 'Aucune') . "\n";
echo "Réponse: " . $response . "\n";

$data = json_decode($response, true);
if ($data) {
    echo "JSON décodé: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Erreur de décodage JSON\n";
}
?>

