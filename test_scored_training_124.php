<?php
// Test de récupération du tir compté 124
$url = 'http://82.67.123.22:25000/api/scored-training/124';

// Headers
$headers = [
    'Accept: application/json'
];

// Faire la requête
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Réponse: $response\n";

// Essayer de décoder le JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "JSON décodé:\n";
    print_r($decoded);
} else {
    echo "Erreur de décodage JSON: " . json_last_error_msg() . "\n";
}
?>
